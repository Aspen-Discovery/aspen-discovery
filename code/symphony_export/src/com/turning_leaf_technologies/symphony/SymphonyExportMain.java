package com.turning_leaf_technologies.symphony;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.*;
import org.marc4j.marc.*;

import java.io.*;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

public class SymphonyExportMain {
	private static Logger logger;
	private static IndexingProfile indexingProfile;
	private static final SimpleDateFormat dateTimeFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");

	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;

	private static IlsExtractLogEntry logEntry;

	private static Date reindexStartTime;

	private static boolean hadErrors = false;

	public static void main(String[] args){
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}

		String profileToLoad = "ils";

		String processName = "symphony_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");

		while (true) {
			reindexStartTime = new Date();
			logger.info(reindexStartTime.toString() + ": Starting Symphony Extract");

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			int numChanges;

			//Connect to the aspen database
			try {
				String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
				if (databaseConnectionInfo == null) {
					logger.error("Please provide database_aspen_jdbc within config.pwd.ini");
					System.exit(1);
				}
				dbConn = DriverManager.getConnection(databaseConnectionInfo);
				if (dbConn == null) {
					logger.error("Could not establish connection to database at " + databaseConnectionInfo);
					System.exit(1);
				}

				logEntry = new IlsExtractLogEntry(dbConn, profileToLoad, logger);
				//Remove log entries older than 45 days
				long earliestLogToKeep = (reindexStartTime.getTime() / 1000) - (60 * 60 * 24 * 45);
				try {
					int numDeletions = dbConn.prepareStatement("DELETE from ils_extract_log WHERE startTime < " + earliestLogToKeep + " AND indexingProfile = '" + profileToLoad + "'").executeUpdate();
					logger.info("Deleted " + numDeletions + " old log entries");
				} catch (SQLException e) {
					logger.error("Error deleting old log entries", e);
				}
			} catch (Exception e) {
				System.out.println("Error connecting to aspen database " + e.toString());
				System.exit(1);
			}

			//TODO: Load the account profile with additional information about Symphony connection if needed.

			indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);
			logEntry.setIsFullUpdate(indexingProfile.isRunFullUpdate());

			//Check for new marc out
			exportVolumes(dbConn, indexingProfile, profileToLoad);

			exportHolds(dbConn, indexingProfile, profileToLoad);

			processCourseReserves(dbConn, indexingProfile, logEntry);

			numChanges = updateRecords(dbConn);
			processRecordsToReload(indexingProfile, logEntry);

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
			}

			//Check for a new holds file
			processNewHoldsFile(dbConn);

			//Check for new orders file(lastExportTime, dbConn);
			processOrdersFile();

			logEntry.setFinished();

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				disconnectDatabase();
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				disconnectDatabase();
				break;
			}

			disconnectDatabase();

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				//Quit and we will restart after if finishes
				System.exit(0);
			}else {
				//Pause before running the next export (longer if we didn't get any actual changes)
				//But not too much longer since we get regular marc delta files that we want to catch as quickly as possible
				try {
					if (numChanges == 0 || logEntry.hasErrors()) {
						//noinspection BusyWait
						Thread.sleep(1000 * 60 * 2);
					} else {
						//noinspection BusyWait
						Thread.sleep(1000 * 60);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		}
	}

	private static void processCourseReserves(Connection dbConn, IndexingProfile indexingProfile, IlsExtractLogEntry logEntry) {
		File exportDir = new File(indexingProfile.getMarcPath() + "/../course_reserves/");
		File[] courseReservesFiles = exportDir.listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				return name.matches("course-reserves.*\\.txt");
			}
		});
		if (courseReservesFiles == null){
			return;
		}
		File newestFile = null;
		long newestFileDate = 0;
		for (File courseReservesFile: courseReservesFiles){
			if (courseReservesFile.lastModified() > newestFileDate){
				newestFileDate = courseReservesFile.lastModified();
				newestFile = courseReservesFile;
			}
		}
		for (File courseReservesFile: courseReservesFiles){
			if (courseReservesFile != newestFile){
				if (courseReservesFile.delete()){
					logEntry.addNote("Deleted old course reserves file " + courseReservesFile.getAbsolutePath());
				}
			}
		}
		if (newestFile == null){
			return;
		}
		//Make sure the file is not still changing
		long newestFileSize = 0;
		while (newestFileSize != newestFile.length()){
			newestFileSize = newestFile.length();
			try {
				Thread.sleep(1000);
			} catch (InterruptedException e) {
				logEntry.incErrors("Sleeping while looking for Course Reserve file changes was interrupted");
			}
		}

		//Process the file
		logEntry.addNote("Processing course reserves file " + newestFile.getAbsolutePath());
		try {
			//Setup statements
			PreparedStatement getExistingCourseReservesStmt = dbConn.prepareStatement("SELECT * FROM course_reserve", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement addCourseReserveListStmt = dbConn.prepareStatement("INSERT INTO course_reserve (created, dateUpdated, courseLibrary, courseInstructor, courseNumber, courseTitle) VALUES (?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			PreparedStatement undeleteCourseReserveStmt = dbConn.prepareStatement("UPDATE course_reserve set deleted = 0, dateUpdated = ? where id = ?");
			PreparedStatement getWorksForListStmt = dbConn.prepareStatement("SELECT * FROM course_reserve_entry WHERE courseReserveId = ?");
			PreparedStatement getWorkIdForBarcodeStmt = dbConn.prepareStatement("SELECT permanent_id, full_title FROM grouped_work_record_items inner join grouped_work_records ON groupedWorkRecordId = grouped_work_records.id inner join grouped_work on grouped_work.id = groupedWorkId where itemId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement addWorkToListStmt = dbConn.prepareStatement("INSERT INTO course_reserve_entry (source, sourceId, courseReserveId, title, dateAdded) VALUES ('GroupedWork', ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			PreparedStatement removeWorkFromListStmt = dbConn.prepareStatement("DELETE FROM course_reserve_entry WHERE id = ?");
			PreparedStatement updateListDateUpdatedStmt = dbConn.prepareStatement("UPDATE course_reserve SET dateUpdated = ? where id = ?");
			PreparedStatement deleteListStmt = dbConn.prepareStatement("UPDATE course_reserve set deleted = 1, dateUpdated = ? where id = ?");

			ResultSet existingCourseReservesRS = getExistingCourseReservesStmt.executeQuery();
			HashMap<String, CourseInfo> existingCourses = new HashMap<>();
			while (existingCourseReservesRS.next()){
				CourseInfo courseInfo = new CourseInfo(
						existingCourseReservesRS.getLong("id"),
						existingCourseReservesRS.getString("courseLibrary"),
						existingCourseReservesRS.getString("courseInstructor"),
						existingCourseReservesRS.getString("courseNumber"),
						existingCourseReservesRS.getString("courseTitle"),
						existingCourseReservesRS.getBoolean("deleted")
				);
				existingCourses.put(courseInfo.toString(), courseInfo);
			}
			//Get existing grouped works for each course
			for (CourseInfo courseInfo : existingCourses.values()){
				getWorksForListStmt.setLong(1, courseInfo.id);
				ResultSet worksForListRS = getWorksForListStmt.executeQuery();
				while (worksForListRS.next()){
					CourseTitle existingTitle = new CourseTitle(
						worksForListRS.getLong("id"),
						worksForListRS.getString("sourceId")
					);
					courseInfo.existingWorks.put(existingTitle.groupedWorkPermanentId, existingTitle);
				}
			}


			@SuppressWarnings("deprecation")
			CSVReader reader = new CSVReader(new FileReader(newestFile), '|');
			String[] columns;
			while ((columns = reader.readNext()) != null){
				if (columns.length >= 6) {
					String barcode = columns[0].trim();
					//String status = columns[1];
					String courseLibrary = columns[2];
					String courseNumber = columns[3];
					String courseTitle = columns[4];
					String courseInstructor = columns[5];

					//Get the grouped work id for the barcode, we won't add to the course if we can't find the book
					getWorkIdForBarcodeStmt.setString(1, barcode);
					ResultSet getWorkIdForBarcodeRS = getWorkIdForBarcodeStmt.executeQuery();
					if (getWorkIdForBarcodeRS.next()){
						String permanentId = getWorkIdForBarcodeRS.getString("permanent_id");
						String title = getWorkIdForBarcodeRS.getString("full_title");

						//Get the current user list for this course
						String key = courseLibrary + "-" + courseInstructor + "-" + courseNumber + "-" + courseTitle;
						CourseInfo course = existingCourses.get(key);
						if (course != null){
							course.stillExists = true;
							if (course.isDeleted){
								//Restore the course
								long now = new Date().getTime() / 1000;
								undeleteCourseReserveStmt.setLong(1, now);
								undeleteCourseReserveStmt.setLong(2, course.id);
								undeleteCourseReserveStmt.executeUpdate();
								course.isDeleted = false;
							}
						}else{
							long now = new Date().getTime() / 1000;
							addCourseReserveListStmt.setLong(1, now);
							addCourseReserveListStmt.setLong(2, now);
							addCourseReserveListStmt.setString(3, courseLibrary);
							addCourseReserveListStmt.setString(4, courseInstructor);
							addCourseReserveListStmt.setString(5, courseNumber);
							addCourseReserveListStmt.setString(6, courseTitle);
							addCourseReserveListStmt.executeUpdate();
							ResultSet generatedKeys = addCourseReserveListStmt.getGeneratedKeys();
							if (generatedKeys.next()){
								course = new CourseInfo(
										generatedKeys.getLong(1),
										courseLibrary,
										courseInstructor,
										courseNumber,
										courseTitle,
										false
								);
								course.stillExists = true;
								existingCourses.put(key, course);
							}else{
								logEntry.incErrors("Failed to create Course Reserve.");
							}
						}

						if (course != null) {
							//Check to see if the title is already on the work
							CourseTitle existingTitle = course.existingWorks.get(permanentId);
							if (course.existingWorks.containsKey(permanentId)) {
								existingTitle.stillExists = true;
							}else{
								//Add the title to the list
								addWorkToListStmt.setString(1, permanentId);
								addWorkToListStmt.setLong(2, course.id);
								String truncatedTitle = title;
								if (truncatedTitle.length() > 50){
									truncatedTitle = truncatedTitle.substring(0, 50);
								}
								addWorkToListStmt.setString(3, truncatedTitle);
								addWorkToListStmt.setLong(4, new Date().getTime() / 1000);
								addWorkToListStmt.executeUpdate();

								//Get the id for the new entry
								ResultSet generatedKeys = addWorkToListStmt.getGeneratedKeys();
								if (generatedKeys.next()){
									existingTitle = new CourseTitle(
											generatedKeys.getLong(1),
											permanentId
									);
									existingTitle.stillExists = true;
									course.existingWorks.put(existingTitle.groupedWorkPermanentId, existingTitle);
								}

								course.isUpdated = true;
							}
						}
					}
				}
			}

			//Check each course for titles that no longer exist
			for (CourseInfo courseInfo : existingCourses.values()){
				int numValidWorks = 0;
				for (CourseTitle courseTitle : courseInfo.existingWorks.values()){
					if (!courseTitle.stillExists){
						removeWorkFromListStmt.setLong(1, courseTitle.id);
						removeWorkFromListStmt.executeUpdate();
						courseInfo.isUpdated = true;
					}else{
						numValidWorks++;
					}
				}
				//Remove any courses that no longer exist or are empty
				if (!courseInfo.stillExists || numValidWorks == 0){
					deleteListStmt.setLong(1, new Date().getTime() /1000);
					deleteListStmt.setLong(2, courseInfo.id);
					deleteListStmt.executeUpdate();
				}else if (courseInfo.isUpdated){
					//Mark the course as updated in the database if isUpdated is true
					updateListDateUpdatedStmt.setLong(1, new Date().getTime() /1000);
					updateListDateUpdatedStmt.setLong(2, courseInfo.id);
					updateListDateUpdatedStmt.executeUpdate();
				}
			}

			//Delete the file
			reader.close();
			if (!newestFile.delete()){
				logEntry.incErrors("Could not delete course reserves file " + newestFile.getAbsolutePath());
			}

		} catch (IOException | SQLException e) {
			logEntry.incErrors("Error processing course reserves file", e);
		}
	}

	private static void processRecordsToReload(IndexingProfile indexingProfile, IlsExtractLogEntry logEntry) {
		try {
			MarcRecordGrouper recordGroupingProcessor = getRecordGroupingProcessor(dbConn);
			GroupedWorkIndexer indexer = getGroupedWorkIndexer(dbConn);

			PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='" + indexingProfile.getName() + "'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordIdentifier = getRecordsToReloadRS.getString("identifier");
				Record marcRecord = indexer.loadMarcRecordFromDatabase(indexingProfile.getName(), recordIdentifier, logEntry);
				if (marcRecord != null) {
					logEntry.incRecordsRegrouped();
					//Regroup the record
					String permanentId = recordGroupingProcessor.processMarcRecord(marcRecord, true, null);
					//Reindex the record
					indexer.processGroupedWork(permanentId);
				}

				markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
				markRecordToReloadAsProcessedStmt.executeUpdate();
				numRecordsToReloadProcessed++;
			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private static void exportHolds(Connection dbConn, IndexingProfile indexingProfile, String profileToLoad){
		File holdsExportFile = new File(indexingProfile.getMarcPath() + "/Holds.csv");
		if (holdsExportFile.exists()){
			long fileTimeStamp = holdsExportFile.lastModified();

			logEntry.saveResults();
			boolean fileChanging = true;
			while (fileChanging){
				fileChanging = false;
				try {
					Thread.sleep(1000);
				} catch (InterruptedException e) {
					logger.debug("Thread interrupted while checking if holds file is changing");
				}
				if (fileTimeStamp != holdsExportFile.lastModified()){
					fileTimeStamp = holdsExportFile.lastModified();
					fileChanging = true;
				}
			}

			//Holds file exists and isn't changing, import it.
			try {
				logEntry.addNote("Starting export of holds " + dateTimeFormatter.format(new Date()));

				//Start a transaction so we can rebuild an entire table
				dbConn.setAutoCommit(false);
				dbConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeUpdate();

				PreparedStatement addIlsHoldSummary = dbConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

				BufferedReader csvReader = new BufferedReader(new FileReader(holdsExportFile));
				String holdInfoLine = csvReader.readLine();
				while (holdInfoLine != null) {
					String[] holdInfoFields = holdInfoLine.split("\\|");
					if (holdInfoFields.length == 2) {
						String bibNumber = "a" + holdInfoFields[0].trim();
						addIlsHoldSummary.setString(1, bibNumber);
						addIlsHoldSummary.setString(2, holdInfoFields[1]);
						addIlsHoldSummary.executeUpdate();
					}
					holdInfoLine = csvReader.readLine();

				}
				logEntry.addNote("Finished export of holds " + dateTimeFormatter.format(new Date()));

				csvReader.close();
				dbConn.setAutoCommit(true);
				if (!holdsExportFile.delete()){
					logEntry.incErrors("Could not delete holds export file");
				}
			} catch (FileNotFoundException e) {
				logEntry.incErrors("Error loading holds", e);
			} catch (IOException e) {
				logEntry.incErrors("Error reading holds information", e);
			} catch (SQLException e) {
				logEntry.incErrors("Error reading and writing from database while loading holds", e);
			}
			logEntry.addNote("Finished export of hold information " + dateTimeFormatter.format(new Date()));
		}else{
			logEntry.addNote("Hold export file (Holds.csv) did not exist in " + SymphonyExportMain.indexingProfile.getMarcPath());
		}
	}

	private static void exportVolumes(Connection dbConn, IndexingProfile indexingProfile, String profileToLoad) {
		File volumeExportFile = new File(indexingProfile.getMarcPath() + "/volumes.txt");
		if (volumeExportFile.exists()){
			long lastVolumeTimeStamp = indexingProfile.getLastVolumeExportTimestamp();
			long fileTimeStamp = volumeExportFile.lastModified();
			if ((fileTimeStamp / 1000) > lastVolumeTimeStamp){
				logEntry.addNote("Checking to see if the volume file is still changing");
				logEntry.saveResults();
				boolean fileChanging = true;
				while (fileChanging){
					fileChanging = false;
					try {
						Thread.sleep(1000);
					} catch (InterruptedException e) {
						logger.debug("Thread interrupted while checking if volume file is changing");
					}
					if (fileTimeStamp != volumeExportFile.lastModified()){
						fileTimeStamp = volumeExportFile.lastModified();
						fileChanging = true;
					}
				}

				//Now update the volumes
				try {
					VolumeUpdateInfo volumeUpdateInfo = new VolumeUpdateInfo();
					logEntry.addNote("Updating Volumes, loading existing volumes from database");
					PreparedStatement allRecordsWithVolumesStmt = dbConn.prepareStatement("SELECT DISTINCT(recordId) from ils_volume_info where recordId like '" + profileToLoad + ":%'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
					PreparedStatement addVolumeStmt = dbConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems, displayOrder) VALUES (?,?,?,?, ?) ON DUPLICATE KEY update recordId = VALUES(recordId), displayLabel = VALUES(displayLabel), relatedItems = VALUES(relatedItems), displayOrder = VALUES(displayOrder)");
					PreparedStatement deleteVolumeStmt = dbConn.prepareStatement("DELETE from ils_volume_info where recordId = ?");

					//Get the existing records with volumes from the database, we will use this to figure out which records no longer have volumes
					HashSet<String> allRecordsWithVolumes = new HashSet<>();
					ResultSet allRecordsWithVolumesRS = allRecordsWithVolumesStmt.executeQuery();
					while (allRecordsWithVolumesRS.next()){
						allRecordsWithVolumes.add(allRecordsWithVolumesRS.getString("recordId") );
					}
					allRecordsWithVolumesRS.close();
					allRecordsWithVolumesStmt.close();

					//Load all volumes in the export
					logEntry.addNote("Updating Volumes, loading volumes from the export");
					logEntry.saveResults();
					BufferedReader csvReader = new BufferedReader(new FileReader(volumeExportFile));
					String volumeInfoLine = csvReader.readLine();
					HashMap<String, VolumeInfo> volumesForRecord = new HashMap<>();
					String curIlsId = null;
					int curRow = 0;
					int numMalformattedRows = 0;
					while (volumeInfoLine != null) {
						String[] volumeInfoFields = volumeInfoLine.split("\\|");
						if (volumeInfoFields.length > 7) {
							String[] repairedVolumeInfo = new String[8];
							repairedVolumeInfo[0] = volumeInfoFields[0];
							repairedVolumeInfo[1] = volumeInfoFields[1] + "|" + volumeInfoFields[2];
							System.arraycopy(volumeInfoFields, 3, repairedVolumeInfo, 2, 6);
							volumeInfoFields = repairedVolumeInfo;
						}
						if (volumeInfoFields.length == 7) {
							//It is more reliable to get the volume from the short bib number rather than the first field
							//String bibNumber = profileToLoad + ":" + volumeInfoFields[0].trim();
							String bibNumber = profileToLoad + ":a" + volumeInfoFields[5].trim();
							if (!bibNumber.equals(curIlsId)){
								if (curIlsId != null) {
									//Save the current volume information
									saveVolumes(curIlsId, volumesForRecord, addVolumeStmt, volumeUpdateInfo, deleteVolumeStmt);
								}
								volumesForRecord = new HashMap<>();
								curIlsId = bibNumber;
								allRecordsWithVolumes.remove(curIlsId);
							}
							String fullCallNumber = volumeInfoFields[1];
							try {
								int startOfVolumeInfo = Integer.parseInt(volumeInfoFields[2].trim());
								//String dateUpdated = volumeInfoFields[3];
								String relatedItemNumber = volumeInfoFields[4].trim();
								String shortBibNumber = volumeInfoFields[5].trim();
								String volumeNumber = volumeInfoFields[6].trim();
								String volumeIdentifier = shortBibNumber + ":" + volumeNumber;
								//startOfVolumeInfo = 0 indicates this item is not part of a volume. Will need separate handling.
								if (startOfVolumeInfo > 0 && startOfVolumeInfo < fullCallNumber.length()) {
									String volume = fullCallNumber.substring(startOfVolumeInfo).trim();
									VolumeInfo curVolume;

									if (volumesForRecord.containsKey(volume)) {
										curVolume = volumesForRecord.get(volume);
									} else {
										curVolume = new VolumeInfo();
										curVolume.bibNumber = bibNumber;
										curVolume.volume = volume;
										//So technically there isn't a volume identifier, this is really the identifier
										//of the first call number we find which works just fine when placing the hold
										curVolume.volumeIdentifier = volumeIdentifier;
										curVolume.displayOrder = curRow;
										volumesForRecord.put(volume, curVolume);
									}
									curVolume.relatedItems.add(relatedItemNumber);
								}
							} catch (NumberFormatException nfe) {
								logger.debug("Mal formatted volume information " + volumeInfoLine);
								numMalformattedRows++;
							}
						}else{
							logger.debug("Mal formatted volume information " + volumeInfoLine);
							numMalformattedRows++;
						}

						//Read the next line
						curRow++;
						if (curRow % 50000 == 0){
							logEntry.addNote("Read " + curRow + "rows in the volume table");
							logEntry.saveResults();
						}
						volumeInfoLine = csvReader.readLine();
					}
					if (curIlsId != null) {
						//Save the last volume information
						saveVolumes(curIlsId, volumesForRecord, addVolumeStmt, volumeUpdateInfo, deleteVolumeStmt);
					}

					logEntry.addNote(numMalformattedRows + " rows were mal formatted in the volume export");
					logEntry.saveResults();

					if (volumeUpdateInfo.maxRelatedItemsLength > 0){
						logEntry.incErrors("Related items were too long for the field, max length should be at least " + volumeUpdateInfo.maxRelatedItemsLength);
					}
					if (volumeUpdateInfo.maxDisplayLabelLength > 0){
						logEntry.addNote("Volume Name was too long for the field, max length should be at least " + volumeUpdateInfo.maxDisplayLabelLength);
					}

					long numVolumesDeleted = 0;
					for (String existingVolume : allRecordsWithVolumes){
						logEntry.addNote("Deleted volume " + existingVolume);
						deleteVolumeStmt.setString(1, existingVolume);
						deleteVolumeStmt.executeUpdate();
						numVolumesDeleted++;
					}
					logEntry.addNote("Updated " + volumeUpdateInfo.numVolumesUpdated + " volumes and deleted " + numVolumesDeleted + " volumes");
					logEntry.saveResults();

					//Update the indexing profile to store the last volume time change
					PreparedStatement updateLastVolumeExportTimeStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastVolumeExportTimestamp = ? where id = ?");
					updateLastVolumeExportTimeStmt.setLong(1, fileTimeStamp / 1000);
					updateLastVolumeExportTimeStmt.setLong(2, indexingProfile.getId());
					updateLastVolumeExportTimeStmt.executeUpdate();
				} catch (FileNotFoundException e) {
					logEntry.incErrors("Error loading volumes", e);
				} catch (IOException e) {
					logEntry.incErrors("Error reading volume information", e);
				} catch (SQLException e) {
					logEntry.incErrors("Error reading and writing from database while loading volumes", e);
				}
				logEntry.addNote("Finished export of volume information " + dateTimeFormatter.format(new Date()));
			}else{
				logEntry.addNote("Volumes File has not changed");
			}
		}else{
			logEntry.addNote("Volume export file (volumes.txt) did not exist in " + SymphonyExportMain.indexingProfile.getMarcPath());
		}
	}

	private static void saveVolumes(String recordId, HashMap<String, VolumeInfo> volumesForRecord, PreparedStatement addVolumeStmt, VolumeUpdateInfo volumeUpdateInfo, PreparedStatement deleteVolumeStmt) {
		//Update the database
		try {
			deleteVolumeStmt.setString(1, recordId);
			deleteVolumeStmt.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Could not delete old volumes for recordId");
		}
		for (String curVolumeKey : volumesForRecord.keySet()){
			VolumeInfo curVolume = volumesForRecord.get(curVolumeKey);
			try{
				addVolumeStmt.setString(1, curVolume.bibNumber);
				addVolumeStmt.setString(2, curVolume.volumeIdentifier);
				addVolumeStmt.setString(3, curVolume.volume);
				addVolumeStmt.setString(4, curVolume.getRelatedItemsAsString());
				addVolumeStmt.setLong(5, curVolume.displayOrder);
				int numUpdates = addVolumeStmt.executeUpdate();
				if (numUpdates > 0) {
					volumeUpdateInfo.numVolumesUpdated++;
				}
			}catch (SQLException sqlException){
				if (sqlException.toString().contains("Data too long for column 'relatedItems'")){
					if (curVolume.getRelatedItemsAsString().length() > volumeUpdateInfo.maxRelatedItemsLength){
						volumeUpdateInfo.maxRelatedItemsLength = curVolume.getRelatedItemsAsString().length();
					}
				}else if (sqlException.toString().contains("Data too long for column 'displayLabel'")){
					if (curVolume.volume.length() > volumeUpdateInfo.maxDisplayLabelLength){
						logger.debug("Long volume name (" + curVolume.volume.length() + ") " + curVolume.volume);
						volumeUpdateInfo.maxDisplayLabelLength = curVolume.volume.length();
					}
				}else{
					logEntry.incErrors("Error adding volume - volume length = " + curVolume.volume.length() + " related Items length = " + curVolume.getRelatedItemsAsString().length(), sqlException);
				}
			}
		}
	}

	private static int updateRecords(Connection dbConn){
		//Check to see if we should regroup all existing records
		try {
			if (indexingProfile.isRegroupAllRecords()) {
				MarcRecordGrouper recordGrouper = getRecordGroupingProcessor(dbConn);
				recordGrouper.regroupAllRecords(dbConn, indexingProfile, getGroupedWorkIndexer(dbConn), logEntry);
			}
		}catch (Exception e){
			logEntry.incErrors("Error regrouping all records", e);
		}

		//Get the last export from MARC time
		long lastUpdateFromMarc = indexingProfile.getLastUpdateFromMarcExport();

		//These are all the full exports, we only want one full export to be processed
		File marcExportPath = new File(indexingProfile.getMarcPath());
		File[] exportedMarcFiles = marcExportPath.listFiles((dir, name) -> name.endsWith("mrc") || name.endsWith("marc"));
		ArrayList<File> filesToProcess = new ArrayList<>();
		File latestFile = null;
		long latestMarcFile = 0;
		boolean hasFullExportFile = false;
		File fullExportFile = null;
		if (exportedMarcFiles != null && exportedMarcFiles.length > 0){
			for (File exportedMarcFile : exportedMarcFiles) {
				//Remove any files that are older than the last time we processed files.
				if (exportedMarcFile.lastModified() / 1000 < lastUpdateFromMarc){
					if (exportedMarcFile.delete()){
						logEntry.addNote("Removed old file " + exportedMarcFile.getAbsolutePath());
					}
				}else{
					if (exportedMarcFile.lastModified() / 1000 > latestMarcFile){
						latestMarcFile = exportedMarcFile.lastModified();
						latestFile = exportedMarcFile;
					}
				}
			}
		}

		if (latestFile != null) {
			filesToProcess.add(latestFile);
			hasFullExportFile = true;
			fullExportFile = latestFile;
		}

		//Get a list of marc deltas since the last marc record
		File marcDeltaPath = new File(marcExportPath.getParentFile() + "/marc_delta");
		File[] exportedMarcDeltaFiles = marcDeltaPath.listFiles((dir, name) -> name.endsWith("mrc") || name.endsWith("marc"));
		if (exportedMarcDeltaFiles != null && exportedMarcDeltaFiles.length > 0){
			for (File exportedMarcDeltaFile : exportedMarcDeltaFiles) {
				if (exportedMarcDeltaFile.lastModified() / 1000 < lastUpdateFromMarc){
					if (exportedMarcDeltaFile.delete()){
						logEntry.addNote("Removed old delta file " + exportedMarcDeltaFile.getAbsolutePath());
					}
				}else{
					if (exportedMarcDeltaFile.lastModified() > latestMarcFile){
						filesToProcess.add(exportedMarcDeltaFile);
					}
				}
			}
		}

		if (filesToProcess.size() > 0){
			//Update all records based on the MARC export
			logEntry.addNote("Updating based on MARC extract");
			return updateRecordsUsingMarcExtract(filesToProcess, hasFullExportFile, fullExportFile, dbConn);
		}else{
			//TODO: See if we can get more runtime info from SirsiDynix APIs;
			return 0;
		}
	}

	/**
	 * Updates Aspen using the MARC export or exports provided.
	 * To see which records are deleted it needs to get a list of all records that are already in the database
	 * so it can detect what has been deleted.
	 *
	 * @param exportedMarcFiles - An array of files to process
	 * @param hasFullExportFile - Whether or not we are including a full export.  We will only delete records if we have a full export.
	 * @param fullExportFile
	 * @param dbConn            - Connection to the Aspen database
	 * @return - total number of changes that were found
	 */
	private static int updateRecordsUsingMarcExtract(ArrayList<File> exportedMarcFiles, boolean hasFullExportFile, File fullExportFile, Connection dbConn) {
		int totalChanges = 0;
		MarcRecordGrouper recordGroupingProcessor = getRecordGroupingProcessor(dbConn);
		if (!recordGroupingProcessor.isValid()){
			logEntry.incErrors("Record Grouping Processor was not valid");
			return totalChanges;
		}else if (!recordGroupingProcessor.loadExistingTitles(logEntry)){
			return totalChanges;
		}

		//Make sure that none of the files are still changing
		for (File curBibFile : exportedMarcFiles) {
			//Make sure the file is not currently changing.
			boolean isFileChanging = true;
			long lastSizeCheck = curBibFile.length();
			while (isFileChanging) {
				try {
					Thread.sleep(5000); //Wait 5 seconds
				} catch (InterruptedException e) {
					logEntry.incErrors("Error checking if a file is still changing", e);
				}
				if (lastSizeCheck == curBibFile.length()) {
					isFileChanging = false;
				} else {
					lastSizeCheck = curBibFile.length();
				}
			}
		}

		//Validate that the FullMarcExportRecordIdThreshold has been met if we are running a full export.
		long maxIdInExport = 0;
		if (hasFullExportFile){
			logEntry.addNote("Validating that full export is the correct size");
			logEntry.saveResults();

			int numRecordsRead = 0;
			String lastRecordProcessed = "";
			try {
				FileInputStream marcFileStream = new FileInputStream(fullExportFile);
				MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, indexingProfile.getMarcEncoding());
				while (catalogReader.hasNext()) {
					numRecordsRead++;
					Record curBib = catalogReader.next();
					RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, indexingProfile);
					if (recordIdentifier != null) {
						String recordNumber = recordIdentifier.getIdentifier();
						lastRecordProcessed = recordNumber;
						recordNumber = recordNumber.replaceAll("[^\\d]", "");
						long recordNumberDigits = Long.parseLong(recordNumber);
						if (recordNumberDigits > maxIdInExport) {
							maxIdInExport = recordNumberDigits;
						}
					}
				}
			} catch (Exception e) {
				logEntry.incErrors("Error loading Symphony bibs on record " + numRecordsRead + " in profile " + indexingProfile.getName() + " the last record processed was " + lastRecordProcessed + " file " + fullExportFile.getAbsolutePath(), e);
				logEntry.addNote("Not processing MARC export due to error reading MARC files.");
				return totalChanges;
			}
			logEntry.addNote("Full export " + fullExportFile + " contains " + numRecordsRead + " records.");
			logEntry.saveResults();

			if (maxIdInExport < indexingProfile.getFullMarcExportRecordIdThreshold()){
				logEntry.incErrors("Full MARC export appears to be truncated, MAX Record ID in the export was " + maxIdInExport + " expected to be greater than or equal to " + indexingProfile.getFullMarcExportRecordIdThreshold());
				logEntry.addNote("Not processing the full export");
				exportedMarcFiles.remove(fullExportFile);
				hasFullExportFile = false;
			}else{
				logEntry.addNote("The full export is the correct size.");
				logEntry.saveResults();
			}
		}

		GroupedWorkIndexer reindexer = getGroupedWorkIndexer(dbConn);
		for (File curBibFile : exportedMarcFiles) {
			logEntry.addNote("Processing file " + curBibFile.getAbsolutePath());

			String lastRecordProcessed = "";
			if (hasFullExportFile && curBibFile.equals(fullExportFile) && indexingProfile.getLastChangeProcessed() > 0){
				logEntry.addNote("Skipping the first " + indexingProfile.getLastChangeProcessed() + " records because they were processed previously see (Last Record ID Processed for the Indexing Profile).");
			}
			int numRecordsRead = 0;
			try {
				FileInputStream marcFileStream = new FileInputStream(curBibFile);
				MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, indexingProfile.getMarcEncoding());
				//Symphony handles bib records with a large number of items by breaking the MARC export into multiple records. The records are always sequential.
				//To solve this, we need to track which id we processed last and if the record has already been processed, we will need to append items from the new
				//record to the old record and then reprocess it.
				RecordIdentifier lastIdentifier = null;
				while (catalogReader.hasNext()) {
					logEntry.incProducts();
					try{
						Record curBib = catalogReader.next();
						numRecordsRead++;
						if (hasFullExportFile && curBibFile.equals(fullExportFile) && (numRecordsRead < indexingProfile.getLastChangeProcessed())) {
							RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, indexingProfile);
							if (recordIdentifier != null) {
								recordGroupingProcessor.removeExistingRecord(recordIdentifier.getIdentifier());
							}
							logEntry.incSkipped();
						}else {
							RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, indexingProfile);
							boolean deleteRecord = false;
							if (recordIdentifier == null) {
								//logger.debug("Record with control number " + curBib.getControlNumber() + " was suppressed or is eContent");
								String controlNumber = curBib.getControlNumber();
								if (controlNumber == null) {
									logger.warn("Bib did not have control number or identifier");
								}
							} else if (!recordIdentifier.isSuppressed()) {
								String recordNumber = recordIdentifier.getIdentifier();
								GroupedWorkIndexer.MarcStatus marcStatus;
								if (lastIdentifier != null && lastIdentifier.equals(recordIdentifier)) {
									marcStatus = reindexer.appendItemsToExistingRecord(indexingProfile, curBib, recordNumber);
								} else {
									marcStatus = reindexer.saveMarcRecordToDatabase(indexingProfile, recordNumber, curBib);
								}

								if (marcStatus != GroupedWorkIndexer.MarcStatus.UNCHANGED || indexingProfile.isRunFullUpdate()) {
									String permanentId = recordGroupingProcessor.processMarcRecord(curBib, marcStatus != GroupedWorkIndexer.MarcStatus.UNCHANGED, null);
									if (permanentId == null) {
										//Delete the record since it is suppressed
										deleteRecord = true;
									} else {
										if (marcStatus == GroupedWorkIndexer.MarcStatus.NEW) {
											logEntry.incAdded();
										} else {
											logEntry.incUpdated();
										}
										getGroupedWorkIndexer(dbConn).processGroupedWork(permanentId);
										totalChanges++;
									}
								} else {
									logEntry.incSkipped();
								}
								if (totalChanges > 0 && totalChanges % 5000 == 0) {
									getGroupedWorkIndexer(dbConn).commitChanges();
								}
								//Mark that the record was processed
								recordGroupingProcessor.removeExistingRecord(recordIdentifier.getIdentifier());
								lastRecordProcessed = recordNumber;
							} else {
								//Delete the record since it is suppressed
								deleteRecord = true;
							}
							lastIdentifier = recordIdentifier;
							indexingProfile.setLastChangeProcessed(numRecordsRead);
							if (deleteRecord) {
								RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), recordIdentifier.getIdentifier());
								if (result.reindexWork) {
									getGroupedWorkIndexer(dbConn).processGroupedWork(result.permanentId);
								} else if (result.deleteWork) {
									//Delete the work from solr and the database
									getGroupedWorkIndexer(dbConn).deleteRecord(result.permanentId);
								}
								logEntry.incDeleted();
								totalChanges++;
							}
						}
					}catch (MarcException me){
						logEntry.incErrors("Error processing individual record  on record " + numRecordsRead + " of " + curBibFile.getAbsolutePath() + " the last record processed was " + lastRecordProcessed + " trying to continue", me);
					}
					if (numRecordsRead % 250 == 0) {
						logEntry.saveResults();
						indexingProfile.updateLastChangeProcessed(dbConn, logEntry);
					}
				}
				marcFileStream.close();

				if (hasFullExportFile){
					indexingProfile.setLastChangeProcessed(0);
					indexingProfile.updateLastChangeProcessed(dbConn, logEntry);
					logEntry.addNote("Updated " + numRecordsRead + " records");
					logEntry.saveResults();
				}
			} catch (Exception e) {
				logEntry.incErrors("Error loading Symphony bibs on record " + numRecordsRead + " in profile " + indexingProfile.getName() + " the last record processed was " + lastRecordProcessed + " file " + curBibFile.getAbsolutePath(), e);
			}
		}

		//Loop through remaining records and delete them
		if (hasFullExportFile) {
			logEntry.addNote("Deleting " + recordGroupingProcessor.getExistingRecords().size() + " records that were not contained in the export");
			for (String identifier : recordGroupingProcessor.getExistingRecords().keySet()) {
				RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), identifier);
				if (result.reindexWork){
					getGroupedWorkIndexer(dbConn).processGroupedWork(result.permanentId);
				}else if (result.deleteWork){
					//Delete the work from solr and the database
					getGroupedWorkIndexer(dbConn).deleteRecord(result.permanentId);
				}
				logEntry.incDeleted();
				totalChanges++;
				if (logEntry.getNumDeleted() % 250 == 0){
					logEntry.saveResults();
				}
			}
			logEntry.saveResults();

			try {
				PreparedStatement updateMarcExportStmt = dbConn.prepareStatement("UPDATE indexing_profiles set fullMarcExportRecordIdThreshold = ? where id = ?");
				updateMarcExportStmt.setLong(1, maxIdInExport);
				updateMarcExportStmt.setLong(2, indexingProfile.getId());
				updateMarcExportStmt.executeUpdate();
			}catch (Exception e){
				logEntry.incErrors("Error updating lastUpdateFromMarcExport", e);
			}
		}

		try {
			PreparedStatement updateMarcExportStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateFromMarcExport = ? where id = ?");
			updateMarcExportStmt.setLong(1, reindexStartTime.getTime() / 1000);
			updateMarcExportStmt.setLong(2, indexingProfile.getId());
			updateMarcExportStmt.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Error updating lastUpdateFromMarcExport", e);
		}

		if (hasFullExportFile && indexingProfile.isRunFullUpdate()){
			//Disable runFullUpdate
			try {
				PreparedStatement updateIndexingProfileStmt = dbConn.prepareStatement("UPDATE indexing_profiles set runFullUpdate = 0 where id = ?");
				updateIndexingProfileStmt.setLong(1, indexingProfile.getId());
				updateIndexingProfileStmt.executeUpdate();
			}catch (Exception e){
				logEntry.incErrors("Error updating disabling runFullUpdate", e);
			}
		}

		return totalChanges;
	}

	private static MarcRecordGrouper getRecordGroupingProcessor(Connection dbConn){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new MarcRecordGrouper(serverName, dbConn, indexingProfile, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer(Connection dbConn) {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private static void disconnectDatabase() {
		try {
			//Close the connection
			if (dbConn != null) {
				dbConn.close();
				dbConn = null;
			}
		} catch (Exception e) {
			System.out.println("Error closing aspen connection: " + e);
			e.printStackTrace();
		}
	}

	private static void processOrdersFile() {
		File mainFile = new File(indexingProfile.getMarcPath() + "/fullexport.mrc");
		HashSet<String> idsInMainFile = new HashSet<>();
		if (mainFile.exists()){
			try {
				MarcReader reader = new MarcPermissiveStreamReader(new FileInputStream(mainFile), true, true);
				int numRecordsRead = 0;
				while (reader.hasNext()) {
					try {
						Record marcRecord = reader.next();
						numRecordsRead++;
						String id = getPrimaryIdentifierFromMarcRecord(marcRecord);
						idsInMainFile.add(id);
					}catch (MarcException me){
						logger.warn("Error processing individual record  on record " + numRecordsRead + " of " + mainFile.getAbsolutePath(), me);
					}
				}
			}catch (Exception e){
				logger.error("Error loading existing marc ids", e);
			}
		}

		//We have gotten 2 different exports a single export as CSV and a second daily version as XLSX.  If the XLSX exists, we will
		//process that and ignore the CSV version.
		File ordersFileMarc = new File(indexingProfile.getMarcPath() + "/orders.mrc");
		File ordersFile = new File(indexingProfile.getMarcPath() + "/onorderfile.txt");
		convertOrdersFileToMarc(ordersFile, ordersFileMarc, idsInMainFile);

	}

	private static void convertOrdersFileToMarc(File ordersFile, File ordersFileMarc, HashSet<String> idsInMainFile) {
		if (ordersFile.exists()){
			long now = new Date().getTime();
			long ordersFileLastModified = ordersFile.lastModified();
			if (now - ordersFileLastModified > 7 * 24 * 60 * 60 * 1000){
				logger.warn("Orders File was last written more than 7 days ago");
			}
			//Always process since we only received one export and we are gradually removing records as they appear in the full export.
			try{
				MarcWriter writer = new MarcStreamWriter(new FileOutputStream(ordersFileMarc, false), "UTF-8", true);
				BufferedReader ordersReader = new BufferedReader(new InputStreamReader(new FileInputStream(ordersFile)));
				String line = ordersReader.readLine();
				int numOrderRecordsWritten = 0;
				int numOrderRecordsSkipped = 0;
				while (line != null){
					int firstPipePos = line.indexOf('|');
					if (firstPipePos != -1){
						String recordNumber = line.substring(0, firstPipePos);
						line = line.substring(firstPipePos + 1);
						if (recordNumber.matches("^\\d+$")) {
							if (!idsInMainFile.contains("a" + recordNumber)){
								if (line.endsWith("|")){
									line = line.substring(0, line.length() - 1);
								}
								int lastPipePosition = line.lastIndexOf('|');
								String title = line.substring(lastPipePosition + 1);
								line = line.substring(0, lastPipePosition);
								lastPipePosition = line.lastIndexOf('|');
								String author = line.substring(lastPipePosition + 1);
								line = line.substring(0, lastPipePosition);
								String ohohseven = line.replace("|", " ");
								//The marc record does not exist, create a temporary bib in the orders file which will get processed by record grouping
								MarcFactory factory = MarcFactory.newInstance();
								Record marcRecord = factory.newRecord();
								marcRecord.addVariableField(factory.newControlField("001", "a" + recordNumber));
								if (!ohohseven.equals("-")) {
									marcRecord.addVariableField(factory.newControlField("007", ohohseven));
								}
								if (!author.equals("-")){
									marcRecord.addVariableField(factory.newDataField("100", '0', '0', "a", author));
								}
								marcRecord.addVariableField(factory.newDataField("245", '0', '0', "a", title));
								writer.write(marcRecord);
								numOrderRecordsWritten++;
							}else{
								logger.info("Marc record already exists for a" + recordNumber);
								numOrderRecordsSkipped++;
							}
						}
					}
					line = ordersReader.readLine();
				}
				writer.close();
				logger.info("Finished writing Orders to MARC record");
				logger.info("Wrote " + numOrderRecordsWritten);
				logger.info("Skipped " + numOrderRecordsSkipped + " because they are in the main export");
			}catch (Exception e){
				logger.error("Error reading orders file ", e);
			}
		}else{
			logger.warn("Could not find orders file at " + ordersFile.getAbsolutePath());
		}
	}

	/**
	 * Check the marc folder to see if the holds files have been updated since the last export time.
	 *
	 * If so, load a count of holds per bib and then update the database.
	 *
	 * @param aspenConn       the connection to the database
	 */
	private static void processNewHoldsFile(Connection aspenConn) {
		HashMap<String, Integer> holdsByBib = new HashMap<>();
		boolean writeHolds = false;
		File holdFile = new File(indexingProfile.getMarcPath() + "/Holds.csv");
		if (holdFile.exists()){
			long now = new Date().getTime();
			long holdFileLastModified = holdFile.lastModified();
			if (now - holdFileLastModified > 2 * 24 * 60 * 60 * 1000){
				logger.warn("Holds File was last written more than 2 days ago");
			}else{
				writeHolds = true;
				String lastCatalogIdRead = "";
				try {
					BufferedReader reader = new BufferedReader(new FileReader(holdFile));
					String line = reader.readLine();
					while (line != null){
						int firstComma = line.indexOf(',');
						if (firstComma > 0){
							String catalogId = line.substring(0, firstComma);
							catalogId = catalogId.replaceAll("\\D", "");
							lastCatalogIdRead = catalogId;
							//Make sure the catalog is numeric
							if (catalogId.length() > 0 && catalogId.matches("^\\d+$")){
								if (holdsByBib.containsKey(catalogId)){
									holdsByBib.put(catalogId, holdsByBib.get(catalogId) +1);
								}else{
									holdsByBib.put(catalogId, 1);
								}
							}
						}
						line = reader.readLine();
					}
				}catch (Exception e){
					logger.error("Error reading holds file ", e);
					hadErrors = true;
				}
				logger.info("Read " + holdsByBib.size() + " bibs with holds, lastCatalogIdRead = " + lastCatalogIdRead);
			}
		}else{
			logger.warn("No holds file found at " + indexingProfile.getMarcPath() + "/Holds.csv");
			hadErrors = true;
		}

		File periodicalsHoldFile = new File(indexingProfile.getMarcPath() + "/Hold_Periodicals.csv");
		if (periodicalsHoldFile.exists()){
			long now = new Date().getTime();
			long holdFileLastModified = periodicalsHoldFile.lastModified();
			if (now - holdFileLastModified > 2 * 24 * 60 * 60 * 1000){
				logger.warn("Periodicals Holds File was last written more than 2 days ago");
			}else {
				writeHolds = true;
				try {
					BufferedReader reader = new BufferedReader(new FileReader(periodicalsHoldFile));
					String line = reader.readLine();
					String lastCatalogIdRead = "";
					while (line != null){
						int firstComma = line.indexOf(',');
						if (firstComma > 0){
							String catalogId = line.substring(0, firstComma);
							catalogId = catalogId.replaceAll("\\D", "");
							lastCatalogIdRead = catalogId;
							//Make sure the catalog is numeric
							if (catalogId.length() > 0 && catalogId.matches("^\\d+$")){
								if (holdsByBib.containsKey(catalogId)){
									holdsByBib.put(catalogId, holdsByBib.get(catalogId) +1);
								}else{
									holdsByBib.put(catalogId, 1);
								}
							}
						}
						line = reader.readLine();
					}
					logger.info(holdsByBib.size() + " bibs with holds (including periodicals) lastCatalogIdRead for periodicals = " + lastCatalogIdRead);
				}catch (Exception e){
					logger.error("Error reading periodicals holds file ", e);
					hadErrors = true;
				}
			}
		}else{
			logger.warn("No periodicals holds file found at " + indexingProfile.getMarcPath() + "/Hold_Periodicals.csv" );
			hadErrors = true;
		}

		//Now that we've counted all the holds, update the database
		if (!hadErrors && writeHolds){
			try {
				aspenConn.setAutoCommit(false);
				aspenConn.prepareCall("DELETE FROM ils_hold_summary").executeUpdate();
				logger.info("Removed existing holds");
				PreparedStatement updateHoldsStmt = aspenConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");
				for (String ilsId : holdsByBib.keySet()){
					updateHoldsStmt.setString(1, "a" + ilsId);
					updateHoldsStmt.setInt(2, holdsByBib.get(ilsId));
					int numUpdates = updateHoldsStmt.executeUpdate();
					if (numUpdates != 1){
						logger.info("Hold was not inserted " + "a" + ilsId + " " + holdsByBib.get(ilsId));
					}
				}
				aspenConn.commit();
				aspenConn.setAutoCommit(true);
				logger.info("Finished adding new holds to the database");
			}catch (Exception e){
				logger.error("Error updating holds database", e);
				hadErrors = true;
			}
		}
	}


	private static String getPrimaryIdentifierFromMarcRecord(Record marcRecord) {
		List<VariableField> recordNumberFields = marcRecord.getVariableFields(indexingProfile.getRecordNumberTag());
		String recordNumber = null;
		//Make sure we only get one ils identifier
		for (VariableField curVariableField : recordNumberFields) {
			if (curVariableField instanceof DataField) {
				DataField curRecordNumberField = (DataField) curVariableField;
				Subfield subfieldA = curRecordNumberField.getSubfield('a');
				if (subfieldA != null && (indexingProfile.getRecordNumberPrefix().length() == 0 || subfieldA.getData().length() > indexingProfile.getRecordNumberPrefix().length())) {
					if (curRecordNumberField.getSubfield('a').getData().startsWith(indexingProfile.getRecordNumberPrefix())) {
						recordNumber = curRecordNumberField.getSubfield('a').getData().trim();
						break;
					}
				}
			} else {
				//It's a control field
				ControlField curRecordNumberField = (ControlField) curVariableField;
				recordNumber = curRecordNumberField.getData().trim();
				break;
			}
		}
		return recordNumber;
	}
}
