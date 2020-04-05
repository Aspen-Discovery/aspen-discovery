package com.turning_leaf_technologies.sideloading;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.BaseMarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.grouping.SideLoadedRecordGrouper;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.SideLoadSettings;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcException;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.sql.*;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

public class SideLoadingMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Connection aspenConn;

	private static SideLoadLogEntry logEntry;

	private static long startTimeForLogging;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static HashMap<String, SideLoadedRecordGrouper> recordGroupingProcessors = new HashMap<>();

	public static void main(String[] args) {
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}

		String processName = "sideload_processing";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long recordGroupingChecksumAtStart = JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar");

		while (true) {
			Date startTime = new Date();
			logger.info(startTime.toString() + ": Starting Side Load Export");
			startTimeForLogging = startTime.getTime() / 1000;

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the Aspen database
			aspenConn = connectToDatabase();

			//Start a log entry
			createDbLogEntry(startTime, aspenConn);
			logEntry.addNote("Starting Update of Side Loaded eContent");
			logEntry.saveResults();

			//Get a list of side loads
			try {
				PreparedStatement getSideloadsStmt = aspenConn.prepareStatement("SELECT * FROM sideloads");
				ResultSet getSideloadsRS = getSideloadsStmt.executeQuery();
				while (getSideloadsRS.next()) {
					SideLoadSettings settings = new SideLoadSettings(getSideloadsRS);
					processSideLoad(settings);
				}
			} catch (SQLException e) {
				logger.error("Error loading sideloads to run", e);
			}

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				recordGroupingProcessors = new HashMap<>();
				groupedWorkIndexer = null;
			}

			logger.info("Finished exporting data " + new Date().toString());
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime.getTime();
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			//Mark that indexing has finished
			logEntry.setFinished();

			disconnectDatabase(aspenConn);

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				break;
			}
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				break;
			}
			//Pause 30 minutes before running the next export
			try {
				System.gc();
				Thread.sleep(1000 * 60 * 30);
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}
	}

	private static void processSideLoad(SideLoadSettings settings) {
		File marcDirectory = new File(settings.getMarcPath());
		if (!marcDirectory.exists()) {
			logEntry.incErrors("Marc Directory " + settings.getMarcPath() + " did not exist");
		} else {
			HashSet<String> existingRecords = new HashSet<>();
			if (settings.isRunFullUpdate()){
				//Get a list of existing IDs for the side load
				try {
					PreparedStatement existingRecordsStmt = aspenConn.prepareStatement("select ilsId from ils_marc_checksums where source = ?");
					existingRecordsStmt.setString(1, settings.getName());
					ResultSet existingRecordsRS = existingRecordsStmt.executeQuery();
					while (existingRecordsRS.next()){
						existingRecords.add(existingRecordsRS.getString("ilsId"));
					}
					existingRecordsStmt.close();
				}catch (Exception e){
					logEntry.incErrors("Error loading existing records for " + settings.getName(), e);
				}
			}

			long startTime = Math.max(settings.getLastUpdateOfAllRecords(), settings.getLastUpdateOfChangedRecords()) * 1000;
			File[] marcFiles = marcDirectory.listFiles((dir, name) -> name.matches(settings.getFilenamesToInclude()));
			if (marcFiles != null) {
				ArrayList<File> filesToProcess = new ArrayList<>();
				for (File marcFile : marcFiles) {
					if (settings.isRunFullUpdate() || (marcFile.lastModified() > startTime)) {
						filesToProcess.add(marcFile);
					}
				}
				if (filesToProcess.size() > 0) {
					logEntry.addUpdatedSideLoad(settings.getName());
					for (File fileToProcess : filesToProcess) {
						processSideLoadFile(fileToProcess, existingRecords, settings);
					}
				}
			}

			//Remove any records that no longer exist
			if (settings.isRunFullUpdate()) {
				try {
					PreparedStatement deleteFromIlsMarcChecksums = aspenConn.prepareStatement("DELETE FROM ils_marc_checksums where source = ? and ilsId = ?");
					for (String existingIdentifier : existingRecords) {
						//Delete from ils_marc_checksums
						SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
						RemoveRecordFromWorkResult result = recordGrouper.removeRecordFromGroupedWork(settings.getName(), existingIdentifier);
						if (result.reindexWork) {
							getGroupedWorkIndexer().processGroupedWork(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
						}

						deleteFromIlsMarcChecksums.setString(1, settings.getName());
						deleteFromIlsMarcChecksums.setString(2, existingIdentifier);
						deleteFromIlsMarcChecksums.executeUpdate();
						logEntry.incDeleted();
					}
					deleteFromIlsMarcChecksums.close();
				} catch (Exception e) {
					logEntry.incErrors("Error deleting records from " + settings.getName(), e);
				}
			}

			processRecordsToReload(settings, logEntry);

			try {
				PreparedStatement updateSideloadStmt;
				if (settings.isRunFullUpdate()) {
					updateSideloadStmt = aspenConn.prepareStatement("UPDATE sideloads set lastUpdateOfAllRecords = ?, runFullUpdate = 0 where id = ?");
				} else {
					updateSideloadStmt = aspenConn.prepareStatement("UPDATE sideloads set lastUpdateOfChangedRecords = ? where id = ?");
				}

				updateSideloadStmt.setLong(1, startTimeForLogging);
				updateSideloadStmt.setLong(2, settings.getId());
				updateSideloadStmt.executeUpdate();
			} catch (Exception e) {
				logEntry.incErrors("Error updating lastUpdateFromMarcExport", e);
			}
		}
	}

	private static void processRecordsToReload(SideLoadSettings settings, SideLoadLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='" + settings.getName() + "'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordIdentifier = getRecordsToReloadRS.getString("identifier");
				File marcFile = settings.getFileForIlsRecord(recordIdentifier);
				if (!marcFile.exists()) {
					logEntry.incErrors("Could not find marc for record to reload " + recordIdentifier);
				} else {
					FileInputStream marcFileStream = new FileInputStream(marcFile);
					MarcPermissiveStreamReader streamReader = new MarcPermissiveStreamReader(marcFileStream, true, true);
					if (streamReader.hasNext()) {
						Record marcRecord = streamReader.next();
						//Regroup the record
						String groupedWorkId = recordGrouper.processMarcRecord(marcRecord, true);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
					} else {
						logEntry.incErrors("Could not read file " + marcFile);
					}
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

	private static void processSideLoadFile(File fileToProcess, HashSet<String> existingRecords, SideLoadSettings settings) {
		try {
			SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
			MarcReader marcReader = new MarcPermissiveStreamReader(new FileInputStream(fileToProcess), true, true, settings.getMarcEncoding());
			while (marcReader.hasNext()) {
				try {
					Record marcRecord = marcReader.next();
					RecordIdentifier recordIdentifier = recordGrouper.getPrimaryIdentifierFromMarcRecord(marcRecord, settings.getName());
					if (recordIdentifier != null) {
						existingRecords.remove(recordIdentifier.getIdentifier());
						logEntry.incNumProducts(1);
						boolean deleteRecord = false;
						String recordNumber = recordIdentifier.getIdentifier();
						BaseMarcRecordGrouper.MarcStatus marcStatus = recordGrouper.writeIndividualMarc(settings, marcRecord, recordNumber, logger);
						if (marcStatus != BaseMarcRecordGrouper.MarcStatus.UNCHANGED || settings.isRunFullUpdate()) {
							String permanentId = recordGrouper.processMarcRecord(marcRecord, marcStatus != BaseMarcRecordGrouper.MarcStatus.UNCHANGED);
							if (permanentId == null) {
								//Delete the record since it is suppressed
								deleteRecord = true;
							} else {
								if (marcStatus == BaseMarcRecordGrouper.MarcStatus.NEW) {
									logEntry.incAdded();
								} else {
									logEntry.incUpdated();
								}
								getGroupedWorkIndexer().processGroupedWork(permanentId);
							}
						} else {
							logEntry.incSkipped();
						}
						if (deleteRecord) {
							RemoveRecordFromWorkResult result = recordGrouper.removeRecordFromGroupedWork(settings.getName(), recordIdentifier.getIdentifier());
							if (result.reindexWork) {
								getGroupedWorkIndexer().processGroupedWork(result.permanentId);
							} else if (result.deleteWork) {
								//Delete the work from solr and the database
								getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
							}
							logEntry.incDeleted();
						}
						if (logEntry.getNumProducts() % 250 == 0) {
							logEntry.saveResults();
						}
					}
				}catch (MarcException e){
					logEntry.incErrors("Error reading MARC file " + fileToProcess, e);
				}
			}
			logEntry.saveResults();
		} catch (FileNotFoundException e) {
			logEntry.incErrors("Could not find file " + fileToProcess.getAbsolutePath());
		} catch (Exception e){
			logEntry.incErrors("Error reading MARC file " + fileToProcess, e);
		}
	}

	private static Connection connectToDatabase() {
		Connection aspenConn = null;
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo != null) {
				aspenConn = DriverManager.getConnection(databaseConnectionInfo);
			} else {
				logger.error("Aspen database connection information was not provided");
				System.exit(1);
			}
		} catch (Exception e) {
			logger.error("Error connecting to Aspen database " + e.toString());
			System.exit(1);
		}
		return aspenConn;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from sideload_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		logEntry = new SideLoadLogEntry(aspenConn, logger);
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, false, logger);
		}
		return groupedWorkIndexer;
	}

	private static SideLoadedRecordGrouper getRecordGroupingProcessor(SideLoadSettings settings) {
		SideLoadedRecordGrouper recordGroupingProcessor = recordGroupingProcessors.get(settings.getName());
		if (recordGroupingProcessor == null) {
			recordGroupingProcessor = new SideLoadedRecordGrouper(serverName, aspenConn, settings, logger, false);
			recordGroupingProcessors.put(settings.getName(), recordGroupingProcessor);
		}
		return recordGroupingProcessor;
	}
}
