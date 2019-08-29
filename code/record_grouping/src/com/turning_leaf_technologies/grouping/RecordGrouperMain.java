package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.SideLoadSettings;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONObject;
import org.marc4j.MarcException;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.*;

import java.io.*;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.regex.Pattern;

/**
 * Groups records so that we can show single multiple titles as one rather than as multiple lines.
 *
 * Grouping happens at 3 different levels:
 *
 */
public class RecordGrouperMain {
	private static Logger logger;
	private static String serverName;

	static String groupedWorkTableName = "grouped_work";

	private static HashMap<String, Long> marcRecordChecksums = new HashMap<>();
	private static HashMap<String, Long> marcRecordFirstDetectionDates = new HashMap<>();
	private static HashMap<String, Long> marcRecordIdsInDatabase = new HashMap<>();
	private static HashMap<String, Long> primaryIdentifiersInDatabase = new HashMap<>();
	private static PreparedStatement insertMarcRecordChecksum;
	private static PreparedStatement removeMarcRecordChecksum;
	private static PreparedStatement removePrimaryIdentifier;

	private static Long lastGroupingTime;
	private static Long lastGroupingTimeVariableId;
	private static boolean fullRegrouping = false;
	private static boolean fullRegroupingNoClear = false;
	private static boolean validateChecksumsFromDisk = false;

	//Reporting information
	private static long groupingLogId;
	private static PreparedStatement addNoteToGroupingLogStmt;

	public static void main(String[] args) {
		// Get the configuration filename
		if (args.length == 0) {
			System.out.println("Welcome to the Record Grouping Application developed by Marmot Library Network.  \n" +
					"This application will group works by title, author, and format to create a \n" +
					"unique work id.  \n" +
					"\n" +
					"Additional information about the grouping process can be found at: \n" +
					"TBD\n" +
					"\n" +
					"This application can be used in several distinct ways based on the command line parameters\n" +
					"1) Generate a work id for an individual title/author/format\n" +
					"   record_grouping.jar generateWorkId <title> <author> <format> <subtitle (optional)>\n" +
					"   \n" +
					"   format should be one of: \n" +
					"   - book\n" +
					"   - music\n" +
					"   - movie\n" +
					"   \n" +
					"2) Generate work ids for a Pika site based on the exports for the site\n" +
					"   record_grouping.jar <pika_site_name>\n" +
					"   \n" +
					"3) Only run record grouping cleanup\n" +
					"   record_grouping.jar <pika_site_name> runPostGroupingCleanup\n" +
					"4) Only explode records into individual records (no grouping)\n" +
					"   record_grouping.jar <pika_site_name> explodeMarcs\n" +
					"5) Record Group a specific indexing profile\n" +
					"   record_grouping.jar <pika_site_name> \"<profile name>\"");
			System.exit(1);
		}

		serverName = args[0];

		String processName = "record_grouping";
		logger = LoggingUtil.setupLogging(serverName, processName);

		// Parse the configuration file
		Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		//Connect to the database
		Connection dbConn = null;
		try{
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			dbConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to database " + e.toString());
			System.exit(1);
		}

		if ("generateWorkId".equals(args[1])) {
			String title;
			String author;
			String format;
			String subtitle;
			if (args.length >= 6) {
				title = args[2];
				author = args[3];
				format = args[4];
				subtitle = args[5];
			} else {
				title = getInputFromCommandLine("Enter the title");
				subtitle = getInputFromCommandLine("Enter the subtitle");
				author = getInputFromCommandLine("Enter the author");
				format = getInputFromCommandLine("Enter the format");
			}
			RecordGroupingProcessor processor = new RecordGroupingProcessor(dbConn, serverName, logger);
			GroupedWorkBase work = GroupedWorkFactory.getInstance(-1, processor);
			work.setTitle(title, 0, subtitle);
			work.setAuthor(author);
			work.setGroupingCategory(format);
			JSONObject result = new JSONObject();
			try {
				result.put("normalizedAuthor", work.getAuthoritativeAuthor());
				result.put("normalizedTitle", work.getAuthoritativeTitle());
				result.put("workId", work.getPermanentId());
			} catch (Exception e) {
				logger.error("Error generating response", e);
			}
			System.out.print(result.toString());
		} else {
			doStandardRecordGrouping(args, dbConn);
		}
	}

	private static String getInputFromCommandLine(String prompt) {
		//Prompt for the work to process
		System.out.print(prompt + ": ");

		//  open up standard input
		BufferedReader br = new BufferedReader(new InputStreamReader(System.in));

		//  read the work from the command-line; need to use try/catch with the
		//  readLine() method
		String value = null;
		try {
			value = br.readLine().trim();
		} catch (IOException ioe) {
			System.out.println("IO error trying to read " + prompt);
			System.exit(1);
		}
		return value;
	}

	private static void doStandardRecordGrouping(String[] args, Connection dbConn) {
		long processStartTime = new Date().getTime();

		logger.info("Starting grouping of records " + new Date().toString());

		//Start a reindex log entry
		try {
			logger.info("Creating log entry for index");
			PreparedStatement createLogEntryStatement = dbConn.prepareStatement("INSERT INTO record_grouping_log (startTime, lastUpdate, notes) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			createLogEntryStatement.setLong(1, processStartTime / 1000);
			createLogEntryStatement.setLong(2, processStartTime / 1000);
			createLogEntryStatement.setString(3, "Initialization complete");
			createLogEntryStatement.executeUpdate();
			ResultSet generatedKeys = createLogEntryStatement.getGeneratedKeys();
			if (generatedKeys.next()){
				groupingLogId = generatedKeys.getLong(1);
			}

			addNoteToGroupingLogStmt = dbConn.prepareStatement("UPDATE record_grouping_log SET notes = ?, lastUpdate = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Unable to create log entry for record grouping process", e);
			System.exit(0);
		}

		//Make sure that our export is valid
		try{
			PreparedStatement bypassValidationStmt = dbConn.prepareStatement("SELECT * from variables WHERE name = 'bypass_export_validation'");
			ResultSet bypassValidationRS = bypassValidationStmt.executeQuery();
			boolean bypassValidation = false;
			if (bypassValidationRS.next()){
				bypassValidation = bypassValidationRS.getBoolean("value");
			}else{
				//This variable hasn't been created yet
				dbConn.prepareStatement("INSERT INTO variables (name, value) VALUES ('bypass_export_validation', 0)").executeUpdate();
			}
			bypassValidationRS.close();
			bypassValidationStmt.close();

			PreparedStatement loadExportValid = dbConn.prepareStatement("SELECT * from variables WHERE name = 'last_export_valid'");
			ResultSet lastExportValidRS = loadExportValid.executeQuery();
			boolean lastExportValid = false;
			if (lastExportValidRS.next()){
				lastExportValid = lastExportValidRS.getBoolean("value");
			}
			lastExportValidRS.close();
			loadExportValid.close();

			if (!lastExportValid){
				if (bypassValidation){
					logger.warn("The last export was not valid.  Still regrouping because bypass validation is on.");
				} else{
					logger.error("The last export was not valid.  Not regrouping to avoid loading incorrect records.");
					System.exit(1);
				}
			}
		} catch (Exception e){
			logger.error("Error loading whether or not the last export was valid", e);
			addNoteToGroupingLog("Error loading whether or not the last export was valid " + e.toString());
			System.exit(1);
		}

		//Get the last grouping time
		try{
			PreparedStatement loadLastGroupingTime = dbConn.prepareStatement("SELECT * from variables WHERE name = 'last_grouping_time'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastGroupingTime = lastGroupingTimeRS.getLong("value");
				lastGroupingTimeVariableId = lastGroupingTimeRS.getLong("id");
			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logger.error("Error loading last grouping time", e);
			addNoteToGroupingLog("Error loading last grouping time " + e.toString());
			System.exit(1);
		}

		//Check to see if we need to clear the database
		boolean clearDatabasePriorToGrouping = false;
		boolean onlyDoCleanup = false;
		boolean explodeMarcsOnly = false;
		String indexingProfileToRun = null;
		if (args.length >= 2 && args[1].equalsIgnoreCase("explodeMarcs")) {
			explodeMarcsOnly = true;
			clearDatabasePriorToGrouping = false;
		} else if (args.length >= 2 && args[1].equalsIgnoreCase("fullNoClear")) {
			fullRegroupingNoClear = true;
		}else if (args.length >= 2 && args[1].equalsIgnoreCase("full")){
			clearDatabasePriorToGrouping = true;
			fullRegrouping = true;
		}else if (args.length >= 2 && args[1].equalsIgnoreCase("runPostGroupingCleanup")){
			fullRegrouping = false;
			onlyDoCleanup = true;
		}else if (args.length >= 2){
			//The last argument is the indexing profile to run
			indexingProfileToRun = args[1];
			fullRegrouping = args.length >= 3 && args[2].equalsIgnoreCase("full");
			fullRegroupingNoClear = args.length >= 3 && args[2].equalsIgnoreCase("fullNoClear");
			//Never clear the database if we are doing full grouping since we are only processing a single profile
		}else{
			fullRegrouping = false;
		}

		RecordGroupingProcessor recordGroupingProcessor = null;
		OverDriveRecordGrouper overDriveRecordGrouper;
		if (!onlyDoCleanup) {
			recordGroupingProcessor = new RecordGroupingProcessor(dbConn, serverName, logger);
			overDriveRecordGrouper = new OverDriveRecordGrouper(dbConn, serverName, logger, fullRegrouping);

			if (!explodeMarcsOnly) {
				markRecordGroupingRunning(dbConn, true);

				clearDatabase(dbConn, clearDatabasePriorToGrouping);
			}

			//Determine if we want to validateChecksumsFromDisk
			try{
				PreparedStatement getValidateChecksumsFromDiskVariableStmt = dbConn.prepareStatement("SELECT * FROM variables where name = 'validateChecksumsFromDisk'");
				ResultSet getValidateChecksumsFromDiskVariableRS = getValidateChecksumsFromDiskVariableStmt.executeQuery();
				if (getValidateChecksumsFromDiskVariableRS.next()){
					validateChecksumsFromDisk = getValidateChecksumsFromDiskVariableRS.getString("value").equalsIgnoreCase("true");
				}
			}catch (Exception e){
				logger.error("Error loading validateChecksumsFromDisk value", e);
				System.exit(1);
			}

			ArrayList<IndexingProfile> indexingProfiles = new ArrayList<>();
			try{
				PreparedStatement getIndexingProfilesStmt = dbConn.prepareStatement("SELECT name FROM indexing_profiles");
				if (indexingProfileToRun != null){
					getIndexingProfilesStmt = dbConn.prepareStatement("SELECT name FROM indexing_profiles where name like '" + indexingProfileToRun + "'");
				}
				ResultSet indexingProfilesRS = getIndexingProfilesStmt.executeQuery();
				while (indexingProfilesRS.next()){
					IndexingProfile profile = IndexingProfile.loadIndexingProfile(dbConn, indexingProfilesRS.getString("name"), logger);

					indexingProfiles.add(profile);
				}
			} catch (Exception e){
				logger.error("Error loading indexing profiles", e);
				System.exit(1);
			}

			if (indexingProfileToRun == null || indexingProfileToRun.equalsIgnoreCase("overdrive")) {
				groupOverDriveRecords(dbConn, overDriveRecordGrouper, explodeMarcsOnly);
			}
			if (indexingProfileToRun == null || indexingProfileToRun.equalsIgnoreCase("rbdigital")) {
				groupRbdigitalRecords(dbConn, recordGroupingProcessor, explodeMarcsOnly);
			}
			//TODO: group hoopla
			//TODO: group cloud library

			if (indexingProfiles.size() > 0) {
				groupIlsRecords(dbConn, indexingProfiles, explodeMarcsOnly);
			}

			//Group side loaded records
			ArrayList<SideLoadSettings> sideLoadSettings = new ArrayList<>();
			try{
				PreparedStatement getSideLoadSettingsStmt = dbConn.prepareStatement("SELECT name FROM sideloads");
				if (indexingProfileToRun != null){
					getSideLoadSettingsStmt = dbConn.prepareStatement("SELECT name FROM sideloads where name like '" + indexingProfileToRun + "'");
				}
				ResultSet sideLoadSettingsRS = getSideLoadSettingsStmt.executeQuery();
				while (sideLoadSettingsRS.next()){
					SideLoadSettings settings = new SideLoadSettings(sideLoadSettingsRS);

					sideLoadSettings.add(settings);
				}
			} catch (Exception e){
				logger.error("Error loading side load settings", e);
				System.exit(1);
			}
			if (indexingProfiles.size() > 0) {
				groupSideLoads(dbConn, sideLoadSettings, explodeMarcsOnly);
			}
		}

		if (!explodeMarcsOnly) {
			try{
				logger.info("Doing post processing of record grouping");
				dbConn.setAutoCommit(false);

				//Cleanup the data
				removeGroupedWorksWithoutPrimaryIdentifiers(dbConn);
				dbConn.commit();
				//removeUnlinkedIdentifiers(dbConn);
				//dbConn.commit();
				//makeIdentifiersLinkingToMultipleWorksInvalidForEnrichment(dbConn);
				//dbConn.commit();
				updateLastGroupingTime(dbConn);
				dbConn.commit();

				dbConn.setAutoCommit(true);
				logger.info("Finished doing post processing of record grouping");
			}catch (SQLException e){
				logger.error("Error in grouped work post processing", e);
			}

			markRecordGroupingRunning(dbConn, false);
		}

		if (recordGroupingProcessor != null) {
			recordGroupingProcessor.dumpStats();
		}

		logger.info("Finished grouping records " + new Date().toString());
		long endTime = new Date().getTime();
		long elapsedTime = endTime - processStartTime;
		logger.info("Elapsed Minutes " + (elapsedTime / 60000));

		try {
			PreparedStatement finishedStatement = dbConn.prepareStatement("UPDATE record_grouping_log SET endTime = ? WHERE id = ?");
			finishedStatement.setLong(1, endTime / 1000);
			finishedStatement.setLong(2, groupingLogId);
			finishedStatement.executeUpdate();
		} catch (SQLException e) {
			logger.error("Unable to update record grouping log with completion time.", e);
		}

		try{
			dbConn.close();
		}catch (Exception e){
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void removeDeletedRecords(String curProfile) {
		if (marcRecordIdsInDatabase.size() > 0) {
			logger.info("Deleting " + marcRecordIdsInDatabase.size() + " record ids for profile " + curProfile + " from the database since they are no longer in the export.");
			addNoteToGroupingLog("Deleting " + marcRecordIdsInDatabase.size() + " record ids for profile " + curProfile + " from the database since they are no longer in the export.");
			for (String recordNumber : marcRecordIdsInDatabase.keySet()) {
				//Remove the record from the ils_marc_checksums table
				try {
					removeMarcRecordChecksum.setLong(1, marcRecordIdsInDatabase.get(recordNumber));
					int numRemoved = removeMarcRecordChecksum.executeUpdate();
					if (numRemoved != 1) {
						logger.warn("Could not delete " + recordNumber + " from ils_marc_checksums table");
					}
				} catch (SQLException e) {
					logger.error("Error removing ILS id " + recordNumber + " from ils_marc_checksums table", e);
				}
			}
			marcRecordIdsInDatabase.clear();
		}

		if (primaryIdentifiersInDatabase.size() > 0) {
			logger.info("Deleting " + primaryIdentifiersInDatabase.size() + " primary identifiers for profile " + curProfile + " from the database since they are no longer in the export.");
			for (String recordNumber : primaryIdentifiersInDatabase.keySet()) {
				//Remove the record from the grouped_work_primary_identifiers table
				try {
					removePrimaryIdentifier.setLong(1, primaryIdentifiersInDatabase.get(recordNumber));
					int numRemoved = removePrimaryIdentifier.executeUpdate();
					if (numRemoved != 1) {
						logger.warn("Could not delete " + recordNumber + " from grouped_work_primary_identifiers table");
					}
				} catch (SQLException e) {
					logger.error("Error removing " + recordNumber + " from grouped_work_primary_identifiers table", e);
				}
			}
			primaryIdentifiersInDatabase.clear();
		}
	}

	private static void markRecordGroupingRunning(Connection dbConn, boolean isRunning) {
		try {
			PreparedStatement updateRecordGroupingRunningStmt = dbConn.prepareStatement("INSERT INTO variables (name, value) VALUES('record_grouping_running', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
			updateRecordGroupingRunningStmt.setString(1, isRunning ? "true" : "false");
			updateRecordGroupingRunningStmt.executeUpdate();
		}catch (Exception e){
			logger.error("Unable to set record_grouping_running variable", e);
		}
	}



	private static void updateLastGroupingTime(Connection dbConn) {
		//Update the last grouping time in the variables table
		try{
			long finishTime = new Date().getTime() / 1000;
			if (lastGroupingTimeVariableId != null){
				PreparedStatement updateVariableStmt  = dbConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, lastGroupingTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = dbConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_grouping_time', ?)");
				insertVariableStmt.setString(1, Long.toString(finishTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error setting last grouping time", e);
		}
	}

	private static void removeGroupedWorksWithoutPrimaryIdentifiers(Connection dbConn) {
		//Remove any grouped works that no longer link to a primary identifier
		try{
			boolean autoCommit = dbConn.getAutoCommit();
			dbConn.setAutoCommit(false);
			PreparedStatement groupedWorksWithoutIdentifiersStmt = dbConn.prepareStatement("SELECT grouped_work.id from grouped_work where id NOT IN (SELECT DISTINCT grouped_work_id from grouped_work_primary_identifiers)", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet groupedWorksWithoutIdentifiersRS = groupedWorksWithoutIdentifiersStmt.executeQuery();
			PreparedStatement deleteWorkStmt = dbConn.prepareStatement("DELETE from grouped_work WHERE id = ?");
			int numWorksNotLinkedToPrimaryIdentifier = 0;
			while (groupedWorksWithoutIdentifiersRS.next()){
				deleteWorkStmt.setLong(1, groupedWorksWithoutIdentifiersRS.getLong(1));
				deleteWorkStmt.executeUpdate();

				numWorksNotLinkedToPrimaryIdentifier++;
				if (numWorksNotLinkedToPrimaryIdentifier % 500 == 0){
					dbConn.commit();
				}
			}
			logger.info("Removed " + numWorksNotLinkedToPrimaryIdentifier + " grouped works that were not linked to primary identifiers");
			groupedWorksWithoutIdentifiersRS.close();
			dbConn.commit();
			dbConn.setAutoCommit(autoCommit);
		}catch (Exception e){
			logger.error("Unable to remove grouped works that no longer have a primary identifier", e);
		}
	}

	private static void loadExistingPrimaryIdentifiers(Connection dbConn, String indexingProfileToRun) {
		//Load MARC Existing MARC Record checksums from VuFind
		try{
			if (insertMarcRecordChecksum == null) {
				insertMarcRecordChecksum = dbConn.prepareStatement("INSERT INTO ils_marc_checksums (ilsId, source, checksum, dateFirstDetected) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE checksum = VALUES(checksum), dateFirstDetected=VALUES(dateFirstDetected), source=VALUES(source)");
				removeMarcRecordChecksum = dbConn.prepareStatement("DELETE FROM ils_marc_checksums WHERE id = ?");
			}

			//MDN 2/23/2015 - Always load checksums so we can optimize writing to the database
			PreparedStatement loadIlsMarcChecksums;
			if (indexingProfileToRun == null) {
				loadIlsMarcChecksums = dbConn.prepareStatement("SELECT * from ils_marc_checksums", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}else{
				loadIlsMarcChecksums = dbConn.prepareStatement("SELECT * from ils_marc_checksums where source like ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				loadIlsMarcChecksums.setString(1, indexingProfileToRun);
			}
			ResultSet ilsMarcChecksumRS = loadIlsMarcChecksums.executeQuery();
			Long zero = 0L;
			while (ilsMarcChecksumRS.next()){
				Long checksum = ilsMarcChecksumRS.getLong("checksum");
				if (checksum.equals(zero)){
					checksum = null;
				}
				String fullIdentifier = ilsMarcChecksumRS.getString("source") + ":" + ilsMarcChecksumRS.getString("ilsId").trim();
				marcRecordChecksums.put(fullIdentifier, checksum);
				marcRecordFirstDetectionDates.put(fullIdentifier, ilsMarcChecksumRS.getLong("dateFirstDetected"));
				if (ilsMarcChecksumRS.wasNull()){
					marcRecordFirstDetectionDates.put(fullIdentifier, null);
				}
				String identifierLowerCase = fullIdentifier.toLowerCase();
				if (marcRecordIdsInDatabase.containsKey(identifierLowerCase)){
					logger.warn(identifierLowerCase + " was already loaded in marcRecordIdsInDatabase");
				}else {
					marcRecordIdsInDatabase.put(identifierLowerCase, ilsMarcChecksumRS.getLong("id"));
				}
			}
			ilsMarcChecksumRS.close();
		}catch (Exception e){
			logger.error("Error loading marc checksums for ILS records", e);
			System.exit(1);
		}
	}

	private static void loadIlsChecksums(Connection dbConn, String indexingProfileToRun) {
		//Load Existing Primary Identifiers so we can clean up
		try {
			if (removePrimaryIdentifier == null){
				removePrimaryIdentifier = dbConn.prepareStatement("DELETE FROM grouped_work_primary_identifiers WHERE id = ?");
			}

			PreparedStatement loadPrimaryIdentifiers;
			if (indexingProfileToRun == null) {
				loadPrimaryIdentifiers = dbConn.prepareStatement("SELECT * from grouped_work_primary_identifiers", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}else{
				loadPrimaryIdentifiers = dbConn.prepareStatement("SELECT * from grouped_work_primary_identifiers where type like ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				loadPrimaryIdentifiers.setString(1, indexingProfileToRun);
			}
			ResultSet primaryIdentifiersRS = loadPrimaryIdentifiers.executeQuery();
			while (primaryIdentifiersRS.next()){
				String fullIdentifier = primaryIdentifiersRS.getString("type") + ":" + primaryIdentifiersRS.getString("identifier").trim();
				String identifierLowerCase = fullIdentifier.toLowerCase();
				if (primaryIdentifiersInDatabase.containsKey(identifierLowerCase)){
					logger.warn(identifierLowerCase + " was already loaded in primaryIdentifiersInDatabase");
				}else {
					primaryIdentifiersInDatabase.put(identifierLowerCase, primaryIdentifiersRS.getLong("id"));
				}
			}
			primaryIdentifiersRS.close();
		}catch (Exception e){
			logger.error("Error loading primary identifiers ", e);
			System.exit(1);
		}
	}

	private static void clearDatabase(Connection dbConn, boolean clearDatabasePriorToGrouping) {
		if (clearDatabasePriorToGrouping){
			try{
				dbConn.prepareStatement("TRUNCATE ils_marc_checksums").executeUpdate();
				dbConn.prepareStatement("TRUNCATE " + groupedWorkTableName).executeUpdate();
				String groupedWorkPrimaryIdentifiersTableName = "grouped_work_primary_identifiers";
				dbConn.prepareStatement("TRUNCATE " + groupedWorkPrimaryIdentifiersTableName).executeUpdate();
			}catch (Exception e){
				System.out.println("Error clearing database " + e.toString());
				System.exit(1);
			}
		}
	}


	private static void groupSideLoads(Connection dbConnection, ArrayList<SideLoadSettings> sideLoadSettings, boolean explodeMarcsOnly) {
		for (SideLoadSettings curSettings : sideLoadSettings) {
			addNoteToGroupingLog("Processing side load settings " + curSettings.getName());

			String marcPath = curSettings.getMarcPath();

			//Check to see if we should process the profile
			boolean processProfile = false;
			ArrayList<File> filesToProcess = new ArrayList<>();
			//Check to see if we have any new files, if so we will process all of them to be sure deletes and overlays process properly
			Pattern filesToMatchPattern = Pattern.compile(curSettings.getFilenamesToInclude(), Pattern.CASE_INSENSITIVE);
			File[] catalogBibFiles = new File(marcPath).listFiles();
			if (catalogBibFiles != null) {
				for (File curBibFile : catalogBibFiles) {
					if (filesToMatchPattern.matcher(curBibFile.getName()).matches()) {
						filesToProcess.add(curBibFile);
						//If the file has changed since the last grouping time we should process it again
						if (curBibFile.lastModified() > lastGroupingTime * 1000){
							processProfile = true;
						}
					}
				}
			}
			if (fullRegrouping || fullRegroupingNoClear){
				processProfile = true;
			}

			if (!processProfile) {
				addNoteToGroupingLog("Skipping processing profile " + curSettings.getName() + " because nothing has changed");
			}else{
				loadIlsChecksums(dbConnection, curSettings.getName());
				loadExistingPrimaryIdentifiers(dbConnection, curSettings.getName());

				SideLoadedRecordGrouper recordGroupingProcessor;
				if ("SideLoadedRecordGrouper".equals(curSettings.getGroupingClass())) {
					recordGroupingProcessor = new SideLoadedRecordGrouper(serverName, dbConnection, curSettings, logger, fullRegrouping);
				} else {
					logger.error("Unknown class for record grouping " + curSettings.getGroupingClass());
					continue;
				}

				String marcEncoding = curSettings.getMarcEncoding();
				TreeSet<String> recordNumbersInExport = new TreeSet<>();
				TreeSet<String> suppressedRecordNumbersInExport = new TreeSet<>();
				TreeSet<String> marcRecordsOverwritten = new TreeSet<>();
				TreeSet<String> marcRecordsWritten = new TreeSet<>();

				String lastRecordProcessed = "";
				for (File curBibFile : filesToProcess) {
					int numRecordsProcessed = 0;
					int numRecordsRead = 0;
					try {
						FileInputStream marcFileStream = new FileInputStream(curBibFile);
						MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);
						while (catalogReader.hasNext()) {
							try{
								Record curBib = catalogReader.next();
								RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, curSettings.getName());
								if (recordIdentifier == null) {
									//logger.debug("Record with control number " + curBib.getControlNumber() + " was suppressed or is eContent");
									String controlNumber = curBib.getControlNumber();
									if (controlNumber == null) {
										logger.warn("Bib did not have control number or identifier");
									}
								}else if (!recordIdentifier.isSuppressed()) {
									String recordNumber = recordIdentifier.getIdentifier();

									boolean marcUpToDate = writeIndividualMarc(curSettings, curBib, recordNumber, marcRecordsWritten, marcRecordsOverwritten);
									recordNumbersInExport.add(recordIdentifier.toString());
									if (!explodeMarcsOnly) {
										if (!marcUpToDate || fullRegroupingNoClear) {
											if (recordGroupingProcessor.processMarcRecord(curBib, !marcUpToDate) == null) {
												suppressedRecordNumbersInExport.add(recordIdentifier.toString());
											}
											numRecordsProcessed++;
										}
										//Mark that the record was processed
										String fullId = recordIdentifier.toString().toLowerCase();
										marcRecordIdsInDatabase.remove(fullId);
										primaryIdentifiersInDatabase.remove(fullId);
									}
									lastRecordProcessed = recordNumber;
								}
							}catch (MarcException me){
								logger.warn("Error processing individual record  on record " + numRecordsRead + " of " + curBibFile.getAbsolutePath() + " the last record processed was " + lastRecordProcessed + " trying to continue", me);
							}
							numRecordsRead++;
							if (numRecordsRead % 100000 == 0) {
								recordGroupingProcessor.dumpStats();
							}
							if (numRecordsRead % 5000 == 0) {
								updateLastUpdateTimeInLog();
								//Let the hard drives rest a bit so other things can happen.
								Thread.sleep(100);
							}
						}
						marcFileStream.close();
					} catch (Exception e) {
						logger.error("Error loading catalog bibs on record " + numRecordsRead + " in profile " + curSettings.getName() + " the last record processed was " + lastRecordProcessed, e);
					}
					logger.info("Finished grouping " + numRecordsRead + " records with " + numRecordsProcessed + " actual changes from the ils file " + curBibFile.getName() + " in profile " + curSettings.getName());
					addNoteToGroupingLog("&nbsp;&nbsp; - Finished grouping " + numRecordsRead + " records from the ils file " + curBibFile.getName());
				}

				addNoteToGroupingLog("&nbsp;&nbsp; - Records Processed:" + recordNumbersInExport.size());
				addNoteToGroupingLog("&nbsp;&nbsp; - Records Suppressed:" + suppressedRecordNumbersInExport.size());
				addNoteToGroupingLog("&nbsp;&nbsp; - Records Written:" + marcRecordsWritten.size());
				addNoteToGroupingLog("&nbsp;&nbsp; - Records Overwritten:" + marcRecordsOverwritten.size());

				removeDeletedRecords(curSettings.getName());
			}
		}
	}

	private static void groupIlsRecords(Connection dbConnection, ArrayList<IndexingProfile> indexingProfiles, boolean explodeMarcsOnly) {
		//Get indexing profiles
		for (IndexingProfile curProfile : indexingProfiles) {
			addNoteToGroupingLog("Processing profile " + curProfile.getName());

			String marcPath = curProfile.getMarcPath();

			//Check to see if we should process the profile
			boolean processProfile = false;
			ArrayList<File> filesToProcess = new ArrayList<>();
			//Check to see if we have any new files, if so we will process all of them to be sure deletes and overlays process properly
			Pattern filesToMatchPattern = Pattern.compile(curProfile.getFilenamesToInclude(), Pattern.CASE_INSENSITIVE);
			File[] catalogBibFiles = new File(marcPath).listFiles();
			if (catalogBibFiles != null) {
				for (File curBibFile : catalogBibFiles) {
					if (filesToMatchPattern.matcher(curBibFile.getName()).matches()) {
						filesToProcess.add(curBibFile);
						//If the file has changed since the last grouping time we should process it again
						if (curBibFile.lastModified() > lastGroupingTime * 1000){
							processProfile = true;
						}
					}
				}
			}
			if (curProfile.isGroupUnchangedFiles() || fullRegrouping || fullRegroupingNoClear){
				processProfile = true;
			}

			if (!processProfile) {
				addNoteToGroupingLog("Skipping processing profile " + curProfile.getName() + " because nothing has changed");
			}else{
				loadIlsChecksums(dbConnection, curProfile.getName());
				loadExistingPrimaryIdentifiers(dbConnection, curProfile.getName());

				MarcRecordGrouper recordGroupingProcessor;
				switch (curProfile.getGroupingClass()) {
					case "MarcRecordGrouper":
						recordGroupingProcessor = new MarcRecordGrouper(serverName, dbConnection, curProfile, logger, fullRegrouping);
						break;
					case "SideLoadedRecordGrouper":
						logger.error("Side Load settings should be updated for " + curProfile.getName());
						continue;
					case "HooplaRecordGrouper":
						recordGroupingProcessor = new HooplaRecordGrouper(serverName, dbConnection, curProfile, logger, fullRegrouping);
						break;
					default:
						logger.error("Unknown class for record grouping " + curProfile.getGroupingClass());
						continue;
				}

				String marcEncoding = curProfile.getMarcEncoding();
				TreeSet<String> recordNumbersInExport = new TreeSet<>();
				TreeSet<String> suppressedRecordNumbersInExport = new TreeSet<>();
				TreeSet<String> marcRecordsOverwritten = new TreeSet<>();
				TreeSet<String> marcRecordsWritten = new TreeSet<>();

				String lastRecordProcessed = "";
				for (File curBibFile : filesToProcess) {
					int numRecordsProcessed = 0;
					int numRecordsRead = 0;
					try {
						FileInputStream marcFileStream = new FileInputStream(curBibFile);
						MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);
						while (catalogReader.hasNext()) {
							try{
								Record curBib = catalogReader.next();
								RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, curProfile.getName(), curProfile.isDoAutomaticEcontentSuppression());
								if (recordIdentifier == null) {
									//logger.debug("Record with control number " + curBib.getControlNumber() + " was suppressed or is eContent");
									String controlNumber = curBib.getControlNumber();
									if (controlNumber == null) {
										logger.warn("Bib did not have control number or identifier");
									}
								}else if (!recordIdentifier.isSuppressed()) {
									String recordNumber = recordIdentifier.getIdentifier();

									boolean marcUpToDate = writeIndividualMarc(curProfile, curBib, recordNumber, marcRecordsWritten, marcRecordsOverwritten);
									recordNumbersInExport.add(recordIdentifier.toString());
									if (!explodeMarcsOnly) {
										if (!marcUpToDate || fullRegroupingNoClear) {
											if (recordGroupingProcessor.processMarcRecord(curBib, !marcUpToDate) == null) {
												suppressedRecordNumbersInExport.add(recordIdentifier.toString());
											}
											numRecordsProcessed++;
										}
										//Mark that the record was processed
										String fullId = recordIdentifier.toString().toLowerCase();
										marcRecordIdsInDatabase.remove(fullId);
										primaryIdentifiersInDatabase.remove(fullId);
									}
									lastRecordProcessed = recordNumber;
								}
							}catch (MarcException me){
								logger.warn("Error processing individual record  on record " + numRecordsRead + " of " + curBibFile.getAbsolutePath() + " the last record processed was " + lastRecordProcessed + " trying to continue", me);
							}
							numRecordsRead++;
							if (numRecordsRead % 100000 == 0) {
								recordGroupingProcessor.dumpStats();
							}
							if (numRecordsRead % 5000 == 0) {
								updateLastUpdateTimeInLog();
								//Let the hard drives rest a bit so other things can happen.
								Thread.sleep(100);
							}
						}
						marcFileStream.close();
					} catch (Exception e) {
						logger.error("Error loading catalog bibs on record " + numRecordsRead + " in profile " + curProfile.getName() + " the last record processed was " + lastRecordProcessed, e);
					}
					logger.info("Finished grouping " + numRecordsRead + " records with " + numRecordsProcessed + " actual changes from the ils file " + curBibFile.getName() + " in profile " + curProfile.getName());
					addNoteToGroupingLog("&nbsp;&nbsp; - Finished grouping " + numRecordsRead + " records from the ils file " + curBibFile.getName());
				}

				addNoteToGroupingLog("&nbsp;&nbsp; - Records Processed:" + recordNumbersInExport.size());
				addNoteToGroupingLog("&nbsp;&nbsp; - Records Suppressed:" + suppressedRecordNumbersInExport.size());
				addNoteToGroupingLog("&nbsp;&nbsp; - Records Written:" + marcRecordsWritten.size());
				addNoteToGroupingLog("&nbsp;&nbsp; - Records Overwritten:" + marcRecordsOverwritten.size());

				removeDeletedRecords(curProfile.getName());
			}
		}
	}

	private static void groupRbdigitalRecords(Connection dbConn, RecordGroupingProcessor recordGroupingProcessor, boolean explodeMarcsOnly) {
		if (explodeMarcsOnly){
			//Nothing to do since we don't have marc records to process
			return;
		}
		addNoteToGroupingLog("Starting to group rbdigital records");
		loadIlsChecksums(dbConn, "rbdigital");
		loadExistingPrimaryIdentifiers(dbConn, "rbdigital");

		int numRecordsProcessed = 0;
		try{
			PreparedStatement rbdigitalRecordsStmt;
			if (lastGroupingTime != null && !fullRegrouping && !fullRegroupingNoClear){
				rbdigitalRecordsStmt = dbConn.prepareStatement("SELECT * FROM rbdigital_title WHERE lastChange >= ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				rbdigitalRecordsStmt.setLong(1, lastGroupingTime);
			}else{
				rbdigitalRecordsStmt = dbConn.prepareStatement("SELECT * FROM rbdigital_title", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}

			ResultSet rbdigitalRecordRS = rbdigitalRecordsStmt.executeQuery();
			while (rbdigitalRecordRS.next()) {
				String rbdigitalId = rbdigitalRecordRS.getString("rbdigitalId");
				String title = rbdigitalRecordRS.getString("title");
				String author = StringUtils.swapFirstLastNames(rbdigitalRecordRS.getString("primaryAuthor"));
				String mediaType = rbdigitalRecordRS.getString("mediaType");

				RecordIdentifier primaryIdentifier = new RecordIdentifier("rbdigital", rbdigitalId);
				JSONObject rawResponse = new JSONObject(rbdigitalRecordRS.getString("rawResponse"));
				String subtitle = null;
				if (rawResponse.getBoolean("hasSubtitle")) {
					subtitle = rawResponse.getString("subtitle");
				}

				recordGroupingProcessor.processRecord(primaryIdentifier, title, subtitle, author, mediaType, true);
				primaryIdentifiersInDatabase.remove(primaryIdentifier.toString().toLowerCase());
				numRecordsProcessed++;
			}
			rbdigitalRecordRS.close();
			removeDeletedRecords("rbdigital");
			addNoteToGroupingLog("Finished grouping " + numRecordsProcessed + " records from rbdigital ");
		}catch (Exception e){
			logger.error("Error loading rbdigital records: ", e);
		}
	}

	private static void groupOverDriveRecords(Connection dbConn, OverDriveRecordGrouper recordGroupingProcessor, boolean explodeMarcsOnly) {
		if (explodeMarcsOnly){
			//Nothing to do since we don't have marc records to process
			return;
		}
		addNoteToGroupingLog("Starting to group overdrive records");
		loadIlsChecksums(dbConn, "overdrive");
		loadExistingPrimaryIdentifiers(dbConn, "overdrive");

		int numRecordsProcessed = 0;
		try{
			PreparedStatement overDriveRecordsStmt;
			if (lastGroupingTime != null && !fullRegrouping && !fullRegroupingNoClear){
				overDriveRecordsStmt = dbConn.prepareStatement("SELECT overdrive_api_products.id, overdriveId, mediaType, title, subtitle, primaryCreatorRole, primaryCreatorName FROM overdrive_api_products INNER JOIN overdrive_api_product_metadata ON overdrive_api_product_metadata.productId = overdrive_api_products.id WHERE deleted = 0 and isOwnedByCollections = 1 and (dateUpdated >= ? OR lastMetadataChange >= ? OR lastAvailabilityChange >= ?)", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				overDriveRecordsStmt.setLong(1, lastGroupingTime);
				overDriveRecordsStmt.setLong(2, lastGroupingTime);
				overDriveRecordsStmt.setLong(3, lastGroupingTime);
			}else{
				overDriveRecordsStmt = dbConn.prepareStatement("SELECT overdrive_api_products.id, overdriveId, mediaType, title, subtitle, primaryCreatorRole, primaryCreatorName, series FROM overdrive_api_products INNER JOIN overdrive_api_product_metadata ON overdrive_api_product_metadata.productId = overdrive_api_products.id WHERE deleted = 0 and isOwnedByCollections = 1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}
			ResultSet overDriveRecordRS = overDriveRecordsStmt.executeQuery();
			while (overDriveRecordRS.next()){
				String overdriveId = overDriveRecordRS.getString("overdriveId");
				String mediaType = overDriveRecordRS.getString("mediaType");
				String title = overDriveRecordRS.getString("title");
				String subtitle = overDriveRecordRS.getString("subtitle");
				String series = overDriveRecordRS.getString("series");
				String author = overDriveRecordRS.getString("primaryCreatorName");

				recordGroupingProcessor.processOverDriveRecord(overdriveId, title, subtitle, series, author, mediaType, true);
				primaryIdentifiersInDatabase.remove("overdrive:" + overdriveId.toLowerCase());
				numRecordsProcessed++;
			}
			overDriveRecordRS.close();

			//This is no longer needed because we do cleanup differently now (get a list of everything in the database and then cleanup anything that isn't in the API anymore
			removeDeletedRecords("overdrive");
			addNoteToGroupingLog("Finished grouping " + numRecordsProcessed + " records from overdrive ");
		}catch (Exception e){
			logger.error("Error loading OverDrive records: ", e);
		}
	}


	private static boolean writeIndividualMarc(BaseIndexingSettings indexingSettings, Record marcRecord, String recordNumber, TreeSet<String> marcRecordsWritten, TreeSet<String> marcRecordsOverwritten) {
		boolean marcRecordUpToDate = false;
		//Copy the record to the individual marc path
		if (recordNumber != null){
			long checksum = MarcUtil.getChecksum(marcRecord);
			File individualFile = indexingSettings.getFileForIlsRecord(recordNumber);

			String recordNumberWithSource = indexingSettings.getName() + ":" + recordNumber;
			Long existingChecksum = getExistingChecksum(recordNumberWithSource);
			//If we are doing partial regrouping or full regrouping without clearing the previous results,
			//Check to see if the record needs to be written before writing it.
			if (!fullRegrouping){
				boolean checksumUpToDate = existingChecksum != null && existingChecksum.equals(checksum);
				boolean fileExists = individualFile.exists();
				marcRecordUpToDate = fileExists && checksumUpToDate;
				if (!fileExists){
					marcRecordsWritten.add(recordNumber);
				}else if (!checksumUpToDate){
					marcRecordsOverwritten.add(recordNumber);
				}
				//Temporary confirmation of CRC
				if (marcRecordUpToDate && validateChecksumsFromDisk){
					try {
						MarcReader marcReader = new MarcPermissiveStreamReader(new FileInputStream(individualFile), true, true);
						Record recordOnDisk = marcReader.next();
						Long actualChecksum = MarcUtil.getChecksum(recordOnDisk);
						if (!actualChecksum.equals(checksum)){
							//checksum in the database is wrong
							marcRecordUpToDate = false;
							marcRecordsOverwritten.add(recordNumber);
						}
					} catch (Exception e) {
						logger.error("Error getting checksum for file", e);
					}
				}
			}

			if (!marcRecordUpToDate){
				try {
					MarcUtil.outputMarcRecord(marcRecord, individualFile, logger);
					MarcUtil.getDateAddedForRecord(marcRecord, recordNumber, indexingSettings.getName(), individualFile, logger);
					updateMarcRecordChecksum(recordNumber, indexingSettings.getName(), checksum);
					//logger.debug("checksum changed for " + recordNumber + " was " + existingChecksum + " now its " + checksum);
				} catch (IOException e) {
					logger.error("Error writing marc", e);
				}
			}else {
				//Update date first detected if needed
				if (marcRecordFirstDetectionDates.containsKey(recordNumberWithSource) && marcRecordFirstDetectionDates.get(recordNumberWithSource) == null){
					MarcUtil.getDateAddedForRecord(marcRecord, recordNumber, indexingSettings.getName(), individualFile, logger);
					updateMarcRecordChecksum(recordNumber, indexingSettings.getName(), checksum);
				}
			}
		}else{
			logger.error("Error did not find record number for MARC record");
			marcRecordUpToDate = true;
		}
		return marcRecordUpToDate;
	}



	private static Long getExistingChecksum(String recordNumber) {
		return marcRecordChecksums.get(recordNumber);
	}

	private static void updateMarcRecordChecksum(String recordNumber, String source, long checksum) {
		long dateFirstDetected;
		String recordNumberWithSource = source + ":" + recordNumber;
		if (marcRecordFirstDetectionDates.containsKey(recordNumberWithSource) && marcRecordFirstDetectionDates.get(recordNumberWithSource) != null){
			dateFirstDetected = marcRecordFirstDetectionDates.get(recordNumberWithSource);
		}else {
			dateFirstDetected = new Date().getTime() / 1000;
		}
		try{
			insertMarcRecordChecksum.setString(1, recordNumber);
			insertMarcRecordChecksum.setString(2, source);
			insertMarcRecordChecksum.setLong(3, checksum);
			insertMarcRecordChecksum.setLong(4, dateFirstDetected);
			insertMarcRecordChecksum.executeUpdate();
		}catch (SQLException e){
			logger.error("Unable to update checksum for ils marc record", e);
		}
	}

	private static StringBuffer notes = new StringBuffer();
	private static SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	private static void addNoteToGroupingLog(String note) {
		try {
			Date date = new Date();
			notes.append("<br>").append(dateFormat.format(date)).append(": ").append(note);
			addNoteToGroupingLogStmt.setString(1, trimLogEntry(notes.toString()));
			addNoteToGroupingLogStmt.setLong(2, new Date().getTime() / 1000);
			addNoteToGroupingLogStmt.setLong(3, groupingLogId);
			addNoteToGroupingLogStmt.executeUpdate();
			logger.info(note);
		} catch (SQLException e) {
			logger.error("Error adding note to Record Grouping Log", e);
		}
	}

	private static void updateLastUpdateTimeInLog() {
		try {
			addNoteToGroupingLogStmt.setString(1, trimLogEntry(notes.toString()));
			addNoteToGroupingLogStmt.setLong(2, new Date().getTime() / 1000);
			addNoteToGroupingLogStmt.setLong(3, groupingLogId);
			addNoteToGroupingLogStmt.executeUpdate();
		} catch (SQLException e) {
			logger.error("Error adding note to Record Grouping Log", e);
		}
	}

	private static String trimLogEntry(String stringToTrim) {
		if (stringToTrim == null) {
			return null;
		}
		if (stringToTrim.length() > 65535) {
			stringToTrim = stringToTrim.substring(0, 65535);
		}
		return stringToTrim.trim();
	}
}
