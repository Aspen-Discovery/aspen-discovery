package com.turning_leaf_technologies.koha_export;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.MarcXmlReader;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;

import java.io.*;
import java.net.URLEncoder;
import java.nio.charset.Charset;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

public class KohaExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;
	private static PreparedStatement getBaseMarcRecordStmt;
	private static PreparedStatement getBibItemsStmt;
	private static MarcFactory marcFactory = MarcFactory.newInstance();
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;

	private static Long startTimeForLogging;
	private static IlsExtractLogEntry logEntry;

	public static void main(String[] args) {
		boolean extractSingleWork = false;
		String singleWorkId = null;
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String extractSingleWorkResponse = StringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleWork = true;
			}
		} else {
			serverName = args[0];
			if (args.length > 1){
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord")){
					extractSingleWork = true;
				}
			}
		}
		if (extractSingleWork) {
			singleWorkId = StringUtils.getInputFromCommandLine("Enter the id of the title to extract");
		}
		String profileToLoad = "ils";

		String processName = "koha_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long recordGroupingChecksumAtStart = JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar");

		while (true) {
			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info(startTime.toString() + ": Starting Koha Extract");

			// Read the base INI file to get information about the server (current directory/conf/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			int numChanges = 0;

			try {
				//Connect to the Aspen Database
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
				long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
				try {
					int numDeletions = dbConn.prepareStatement("DELETE from ils_extract_log WHERE startTime < " + earliestLogToKeep + " AND indexingProfile = '" + profileToLoad + "'").executeUpdate();
					logger.info("Deleted " + numDeletions + " old log entries");
				} catch (SQLException e) {
					logger.error("Error deleting old log entries", e);
				}

				//Connect to the Koha database
				Connection kohaConn;
				KohaInstanceInformation kohaInstanceInformation = initializeKohaConnection(dbConn);
				if (kohaInstanceInformation == null) {
					logEntry.incErrors("Could not connect to the Koha database");
					logEntry.setFinished();
					continue;
				} else {
					kohaConn = kohaInstanceInformation.kohaConnection;
					profileToLoad = kohaInstanceInformation.indexingProfileName;
				}

				indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);

				if (!extractSingleWork) {
					updateBranchInfo(dbConn, kohaConn);
					logEntry.addNote("Finished updating branch information");

					updatePatronTypes(dbConn, kohaConn);
					logEntry.addNote("Finished updating patron types");

					updateTranslationMaps(dbConn, kohaConn);
					logEntry.addNote("Finished updating translation maps");

					exportHolds(dbConn, kohaConn);
					logEntry.addNote("Finished loading holds");

					exportVolumes(dbConn, kohaConn);

					updateNovelist(dbConn, kohaConn);
				}

				//Update works that have changed since the last index
				numChanges = updateRecords(dbConn, kohaConn, singleWorkId);

				logEntry.setFinished();

				Date currentTime = new Date();
				logger.info(currentTime.toString() + ": Finished Koha Extract");
			} catch (Exception e) {
				logger.error("Error connecting to database ", e);
				//Don't exit, we will try again in a few minutes
			}

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
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				disconnectDatabase();
				break;
			}
			if (extractSingleWork) {
				disconnectDatabase();
				break;
			}
			disconnectDatabase();

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				while (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
					try {
						System.gc();
						Thread.sleep(1000 * 60 * 5);
					} catch (InterruptedException e) {
						logger.info("Thread was interrupted");
					}
				}
			}else{
				//Pause before running the next export (longer if we didn't get any actual changes)
				try {
					System.gc();
					if (numChanges == 0) {
						Thread.sleep(1000 * 60 * 5);
					} else {
						Thread.sleep(1000 * 60);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		} //Infinite loop
	}

	private static void disconnectDatabase() {
		try {
			//Close the connection
			if (dbConn != null) {
				dbConn.close();
				dbConn = null;
			}
		} catch (Exception e) {
			System.out.println("Error closing aspen connection: " + e.toString());
			e.printStackTrace();
		}
	}

	private static void updateNovelist(Connection dbConn, Connection kohaConn) {
		try{
			PreparedStatement getExistingNovelistSettingsStmt = dbConn.prepareStatement("SELECT * from novelist_settings");
			ResultSet existingNovelistSettingsRS = getExistingNovelistSettingsStmt.executeQuery();
			if (!existingNovelistSettingsRS.next()){
				PreparedStatement kohaNovelistSettingsStmt = kohaConn.prepareStatement("SELECT * from systempreferences where variable LIKE 'Novelist%'");
				ResultSet kohaNovelistSettingsRS = kohaNovelistSettingsStmt.executeQuery();
				boolean novelistEnabled = false;
				String novelistPassword = "";
				String novelistProfile = "";
				while (kohaNovelistSettingsRS.next()){
					String variableName = kohaNovelistSettingsRS.getString("variable");
					switch (variableName){
						case "NovelistSelectEnabled":
							novelistEnabled = kohaNovelistSettingsRS.getBoolean("value");
						case "NovelistSelectPassword":
							novelistPassword = kohaNovelistSettingsRS.getString("value");
						case "NovelistSelectProfile":
							novelistProfile = kohaNovelistSettingsRS.getString("value");
					}
				}
				if (novelistEnabled){
					PreparedStatement addNovelistSettingsStmt = dbConn.prepareStatement("INSERT INTO novelist_settings (profile, pwd) VALUES (?, ?)");
					addNovelistSettingsStmt.setString(1, novelistProfile);
					addNovelistSettingsStmt.setString(2, novelistPassword);
					addNovelistSettingsStmt.executeUpdate();
					logEntry.addNote("Added Novelist settings from Koha");
				}
			}
		}catch (Exception e){
			logEntry.incErrors("Error updating Novelist information", e);
		}
	}

	private static void exportVolumes(Connection dbConn, Connection kohaConn) {
		try{
			logEntry.addNote("Starting export of volume information");
			//Get the existing volumes
			PreparedStatement getExistingVolumes = dbConn.prepareStatement("SELECT volumeId from ils_volume_info", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			HashSet<Long> existingVolumes = new HashSet<>();
			ResultSet existingVolumesRS = getExistingVolumes.executeQuery();
			while (existingVolumesRS.next()){
				existingVolumes.add(existingVolumesRS.getLong("volumeId"));
			}
			existingVolumesRS.close();

			PreparedStatement getVolumeInfoStmt = kohaConn.prepareStatement("SELECT * from volumes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getItemsForVolumeStmt = kohaConn.prepareStatement("SELECT * from volume_items", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement addVolumeStmt = dbConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems, displayOrder) VALUES (?,?,?,?, ?) ON DUPLICATE KEY update recordId = VALUES(recordId), displayLabel = VALUES(displayLabel), relatedItems = VALUES(relatedItems)");
			PreparedStatement deleteVolumeStmt = dbConn.prepareStatement("DELETE from ils_volume_info where volumeId = ?");

			ResultSet volumeInfoRS = null;
			boolean loadError = false;

			HashMap<Long, String> itemsForVolume = new HashMap<>(); //Volume Id, list of item
			try {
				volumeInfoRS = getVolumeInfoStmt.executeQuery();

				ResultSet getItemsForVolumeRS = getItemsForVolumeStmt.executeQuery();
				while (getItemsForVolumeRS.next()){
					String itemRecordNum = getItemsForVolumeRS.getString("itemnumber");

					Long volumeId = getItemsForVolumeRS.getLong("volume_id");
					itemsForVolume.merge(volumeId, itemRecordNum, (a, b) -> a + "|" + b);
				}
				getItemsForVolumeRS.close();
			} catch (SQLSyntaxErrorException e1) {
				logEntry.addNote("Volume table does not exist within the database");
				return;
			} catch (SQLException e1) {
				logEntry.incErrors("Error loading volume information", e1);
				loadError = true;
			}

			if (!loadError) {
				int numVolumesUpdated = 0;
				while (volumeInfoRS.next()) {
					long recordId = volumeInfoRS.getLong("biblionumber");
					long volumeId = volumeInfoRS.getLong("id");
					int displayOrder = volumeInfoRS.getInt("display_order");
					String description = volumeInfoRS.getString("description");
					existingVolumes.remove(volumeId);
					String relatedItems = itemsForVolume.get(recordId);
					if (relatedItems == null){
						relatedItems = "";
					}

					try {
						addVolumeStmt.setString(1, "ils:" + recordId);
						addVolumeStmt.setLong(2, volumeId);
						addVolumeStmt.setString(3, description);
						addVolumeStmt.setString(4, relatedItems);
						addVolumeStmt.setInt(5, displayOrder);
						int numUpdates = addVolumeStmt.executeUpdate();
						if (numUpdates > 0) {
							numVolumesUpdated++;
						}
					}catch (SQLException sqlException){
						logEntry.incErrors("Error adding volume", sqlException);
					}
				}
				volumeInfoRS.close();


				//Remove anything that no longer exists
				long numVolumesDeleted = 0;
				for (Long existingVolume : existingVolumes){
					logEntry.addNote("Deleted volume " + existingVolume);
					deleteVolumeStmt.setLong(1, existingVolume);
					deleteVolumeStmt.executeUpdate();
					numVolumesDeleted++;
				}
				logEntry.addNote("Updated " + numVolumesUpdated + " volumes and deleted " + numVolumesDeleted + " volumes");
			}
		}catch (Exception e){
			logEntry.incErrors("Error exporting volume information", e);
		}
		logEntry.saveResults();
	}

	private static KohaInstanceInformation initializeKohaConnection(Connection dbConn) throws SQLException {
		//Get information about the account profile for koha
		PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = 'koha'");
		ResultSet accountProfileRS = accountProfileStmt.executeQuery();
		KohaInstanceInformation kohaInstanceInformation = null;
		if (accountProfileRS.next()) {
			try {
				String host = accountProfileRS.getString("databaseHost");
				String port = accountProfileRS.getString("databasePort");
				if (port == null || port.length() == 0) {
					port = "3306";
				}
				String databaseName = accountProfileRS.getString("databaseName");
				String user = accountProfileRS.getString("databaseUser");
				String password = accountProfileRS.getString("databasePassword");
				String timezone = accountProfileRS.getString("databaseTimezone");

				String kohaConnectionJDBC = "jdbc:mysql://" +
						host + ":" + port +
						"/" + databaseName +
						"?user=" + user +
						"&password=" + password +
						"&useUnicode=yes&characterEncoding=UTF-8";
				if (timezone != null && timezone.length() > 0) {
					kohaConnectionJDBC += "&serverTimezone=" + URLEncoder.encode(timezone, "UTF8");
				}

				Connection kohaConn = connectToKohaDatabase(kohaConnectionJDBC);
				if (kohaConn != null) {
					kohaInstanceInformation = new KohaInstanceInformation();
					kohaInstanceInformation.kohaConnection = kohaConn;
					kohaInstanceInformation.indexingProfileName = accountProfileRS.getString("recordSource");
				}
			} catch (Exception e) {
				logger.error("Error connecting to koha database ", e);
			}
		} else {
			logger.error("Could not find an account profile for Koha stopping");
			System.exit(1);
		}
		return kohaInstanceInformation;
	}

	private static Connection connectToKohaDatabase(String kohaConnectionJDBC) {
		int tries = 0;
		while (tries < 3) {
			try {
				Connection kohaConn = DriverManager.getConnection(kohaConnectionJDBC);

				getBaseMarcRecordStmt = kohaConn.prepareStatement("SELECT * from biblio_metadata where biblionumber = ?");
				getBibItemsStmt = kohaConn.prepareStatement("SELECT * from items where biblionumber = ?");

				return kohaConn;
			} catch (Exception e) {
				tries++;
				logger.error("Could not connect to the koha database, try " + tries);
				try {
					Thread.sleep(15000);
				} catch (InterruptedException ex) {
					logger.debug("Thread was interrupted");
				}
			}

		}
		return null;
	}

	private static void updateTranslationMaps(Connection dbConn, Connection kohaConn) {
		try {
			PreparedStatement createTranslationMapStmt = dbConn.prepareStatement("INSERT INTO translation_maps (name, indexingProfileId) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement getTranslationMapStmt = dbConn.prepareStatement("SELECT id from translation_maps WHERE name = ? and indexingProfileId = ?");
			PreparedStatement getExistingValuesForMapStmt = dbConn.prepareStatement("SELECT * from translation_map_values where translationMapId = ?");
			PreparedStatement insertTranslationStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");
			PreparedStatement getExistingValuesForFormatMapStmt = dbConn.prepareStatement("SELECT * from format_map_values where indexingProfileId = ?");
			PreparedStatement insertFormatStmt = dbConn.prepareStatement("INSERT INTO format_map_values (indexingProfileId, value, format, formatCategory, formatBoost) VALUES (?, ?, ?, 'Other', 1)");

			//Load branches into location
			PreparedStatement kohaBranchesStmt = kohaConn.prepareStatement("SELECT branchcode, branchname from branches");
			Long translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "location");
			HashMap<String, String> existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaBranchesStmt, "branchcode", "branchname", insertTranslationStmt, translationMapId, existingValues);

			//Load sub location
			String authorizedValueType = getAuthorizedValueTypeForSubfield(indexingProfile.getSubLocationSubfield());
			if (authorizedValueType != null){
				PreparedStatement kohaLocStmt = kohaConn.prepareStatement("SELECT * FROM authorised_values where category = '" + authorizedValueType + "'");
				translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "sub_location");
				existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
				updateTranslationMap(kohaLocStmt, "authorised_value", "lib", insertTranslationStmt, translationMapId, existingValues);
			}

			//Load shelf location
			authorizedValueType = getAuthorizedValueTypeForSubfield(indexingProfile.getShelvingLocationSubfield());
			if (authorizedValueType != null) {
				PreparedStatement kohaCCodesStmt = kohaConn.prepareStatement("SELECT * FROM authorised_values where category = '" + authorizedValueType + "'");
				translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "shelf_location");
				existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
				updateTranslationMap(kohaCCodesStmt, "authorised_value", "lib", insertTranslationStmt, translationMapId, existingValues);
			}

			//Load collection
			authorizedValueType = getAuthorizedValueTypeForSubfield(indexingProfile.getCollectionSubfield());
			if (authorizedValueType != null) {
				PreparedStatement kohaCCodesStmt = kohaConn.prepareStatement("SELECT * FROM authorised_values where category = '" + authorizedValueType + "'");
				translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "collection");
				existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
				updateTranslationMap(kohaCCodesStmt, "authorised_value", "lib", insertTranslationStmt, translationMapId, existingValues);
			}

			//Load itemtypes into formats for the indexing profile
			PreparedStatement kohaItemTypesStmt = kohaConn.prepareStatement("SELECT itemtype, description FROM itemtypes");
			existingValues = getExistingFormatValues(getExistingValuesForFormatMapStmt, indexingProfile.getId());
			updateFormatMap(kohaItemTypesStmt, insertFormatStmt, indexingProfile.getId(), existingValues);

			//Also load item types into itype
			translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "itype");
			existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaItemTypesStmt, "itemtype", "description", insertTranslationStmt, translationMapId, existingValues);

		} catch (SQLException e) {
			logger.error("Error updating translation maps", e);
		}
	}

	private static String getAuthorizedValueTypeForSubfield(char subfield) {
		String authorizedValueType = null;
		if (subfield == '8'){
			authorizedValueType = "CCODE";
		}else if (indexingProfile.getSubLocationSubfield() == 'c'){
			authorizedValueType = "LOC";
		}
		return authorizedValueType;
	}

	private static void updateFormatMap(PreparedStatement kohaValuesStmt, PreparedStatement insertFormatStmt, Long indexingProfileId, HashMap<String, String> existingValues) throws SQLException {
		ResultSet kohaValuesRS = kohaValuesStmt.executeQuery();
		while (kohaValuesRS.next()) {
			String value = kohaValuesRS.getString("itemtype");
			String translation = kohaValuesRS.getString("description");
			if (existingValues.containsKey(value)) {
				if (!existingValues.get(value).equals(translation)) {
					logger.warn("Translation for " + value + " has changed from " + existingValues.get(value) + " to " + translation);
				}
			} else {
				if (translation == null) {
					translation = value;
				}
				if (value.length() > 0) {
					insertFormatStmt.setLong(1, indexingProfileId);
					insertFormatStmt.setString(2, value);
					insertFormatStmt.setString(3, translation);
					insertFormatStmt.executeUpdate();
				}
			}
		}
	}

	private static void updateTranslationMap(PreparedStatement kohaValuesStmt, String valueColumn, String translationColumn, PreparedStatement insertTranslationStmt, Long translationMapId, HashMap<String, String> existingValues) throws SQLException {
		ResultSet kohaValuesRS = kohaValuesStmt.executeQuery();
		while (kohaValuesRS.next()) {
			String value = kohaValuesRS.getString(valueColumn);
			String translation = kohaValuesRS.getString(translationColumn);
			if (existingValues.containsKey(value.toLowerCase())) {
				if (!existingValues.get(value.toLowerCase()).equals(translation)) {
					logger.warn("Translation for " + value + " has changed from " + existingValues.get(value) + " to " + translation);
				}
			} else {
				if (translation == null) {
					translation = value;
				}
				if (value.length() > 0) {
					insertTranslationStmt.setLong(1, translationMapId);
					insertTranslationStmt.setString(2, value);
					insertTranslationStmt.setString(3, translation);
					insertTranslationStmt.executeUpdate();
				}
			}
		}
	}

	private static HashMap<String, String> getExistingFormatValues(PreparedStatement getExistingValuesForMapStmt, Long indexingProfileId) throws SQLException {
		HashMap<String, String> existingValues = new HashMap<>();
		getExistingValuesForMapStmt.setLong(1, indexingProfileId);
		ResultSet getExistingValuesForMapRS = getExistingValuesForMapStmt.executeQuery();
		while (getExistingValuesForMapRS.next()) {
			existingValues.put(getExistingValuesForMapRS.getString("value"), getExistingValuesForMapRS.getString("format"));
		}
		return existingValues;
	}

	private static HashMap<String, String> getExistingTranslationMapValues(PreparedStatement getExistingValuesForMapStmt, Long translationMapId) throws SQLException {
		HashMap<String, String> existingValues = new HashMap<>();
		getExistingValuesForMapStmt.setLong(1, translationMapId);
		ResultSet getExistingValuesForMapRS = getExistingValuesForMapStmt.executeQuery();
		while (getExistingValuesForMapRS.next()) {
			existingValues.put(getExistingValuesForMapRS.getString("value").toLowerCase(), getExistingValuesForMapRS.getString("translation"));
		}
		return existingValues;
	}

	private static Long getTranslationMapId(PreparedStatement createTranslationMapStmt, PreparedStatement getTranslationMapStmt, String mapName) throws SQLException {
		Long translationMapId = null;
		getTranslationMapStmt.setString(1, mapName);
		getTranslationMapStmt.setLong(2, indexingProfile.getId());
		ResultSet getTranslationMapRS = getTranslationMapStmt.executeQuery();
		if (getTranslationMapRS.next()) {
			translationMapId = getTranslationMapRS.getLong("id");
		} else {
			//Map does not exist, create it
			createTranslationMapStmt.setString(1, mapName);
			createTranslationMapStmt.setLong(2, indexingProfile.getId());
			createTranslationMapStmt.executeUpdate();
			ResultSet generatedIds = createTranslationMapStmt.getGeneratedKeys();
			if (generatedIds.next()) {
				translationMapId = generatedIds.getLong(1);
			}
		}
		return translationMapId;
	}

	private static void updatePatronTypes(Connection dbConn, Connection kohaConn) {
		try {
			PreparedStatement kohaPatronTypeStmt = kohaConn.prepareStatement("SELECT * from categories");
			PreparedStatement existingAspenPatronTypesStmt = dbConn.prepareStatement("SELECT id from ptype where pType = ?");
			PreparedStatement addAspenPatronTypeStmt = dbConn.prepareStatement("INSERT INTO ptype (pType) VALUES (?)");

			ResultSet kohaPTypes = kohaPatronTypeStmt.executeQuery();
			while (kohaPTypes.next()) {
				existingAspenPatronTypesStmt.setString(1, kohaPTypes.getString("categorycode"));
				ResultSet existingAspenPatronTypesRS = existingAspenPatronTypesStmt.executeQuery();
				if (!existingAspenPatronTypesRS.next()) {
					addAspenPatronTypeStmt.setString(1, kohaPTypes.getString("categorycode"));
					addAspenPatronTypeStmt.executeUpdate();
				}
				existingAspenPatronTypesRS.close();
			}
			kohaPTypes.close();
		} catch (Exception e) {
			logger.error("Error updating patron type information from Koha", e);
		}
	}

	private static void updateBranchInfo(Connection dbConn, Connection kohaConn) {
		try {
			PreparedStatement kohaLibraryGroupStmt = kohaConn.prepareStatement("SELECT * from library_groups where id = ?");
			PreparedStatement kohaLibraryGroupForBranchCodeStmt = kohaConn.prepareStatement("SELECT parent_id from library_groups where branchcode = ?");
			PreparedStatement kohaBranchesStmt = kohaConn.prepareStatement("SELECT * from branches");
			PreparedStatement existingAspenLocationStmt = dbConn.prepareStatement("SELECT libraryId, locationId, isMainBranch from location where code = ?");
			PreparedStatement existingAspenLibraryStmt = dbConn.prepareStatement("SELECT libraryId from library where subdomain = ?");
			PreparedStatement addAspenLibraryStmt = dbConn.prepareStatement("INSERT INTO library (subdomain, displayName, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, 1, 1)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement addAspenLocationStmt = dbConn.prepareStatement("INSERT INTO location (libraryId, displayName, code, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, -1, -1)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement addAspenLocationRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO location_records_owned (locationId, indexingProfileId, location, subLocation) VALUES (?, ?, ?, '')");
			PreparedStatement addAspenLocationRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO location_records_to_include (locationId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
			PreparedStatement addAspenLibraryRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO library_records_owned (libraryId, indexingProfileId, location, subLocation) VALUES (?, ?, ?, '') ON DUPLICATE KEY UPDATE location = CONCAT(location, '|', VALUES(location))");
			PreparedStatement addAspenLibraryRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO library_records_to_include (libraryId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
			PreparedStatement kohaRepeatableHolidaysStmt = kohaConn.prepareStatement("SELECT * FROM repeatable_holidays where branchcode = ?");
			PreparedStatement kohaSpecialHolidaysStmt = kohaConn.prepareStatement("SELECT * FROM special_holidays where (year = ? or year = ?) AND branchcode = ? order by  year, month, day");
			PreparedStatement existingHoursStmt = dbConn.prepareStatement("SELECT count(*) FROM location_hours where locationId = ?");
			PreparedStatement addHoursStmt = dbConn.prepareStatement("INSERT INTO location_hours (locationId, day, closed, open, close) VALUES (?, ?, 0, '00:30', '00:30') ");
			PreparedStatement existingHolidaysStmt = dbConn.prepareStatement("SELECT * FROM holiday where libraryId = ? and date >= ?");
			PreparedStatement addHolidayStmt = dbConn.prepareStatement("INSERT INTO holiday (libraryId, date, name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
			PreparedStatement removeHolidayStmt = dbConn.prepareStatement("DELETE FROM holiday WHERE id = ?");
			PreparedStatement markLibraryClosed = dbConn.prepareStatement("UPDATE location_hours set closed = 1 where locationId = ? and day = ?");
			ResultSet kohaBranches = kohaBranchesStmt.executeQuery();
			String currentYear = new SimpleDateFormat("yyyy").format(new Date());
			GregorianCalendar nextYearCal = new GregorianCalendar();
			nextYearCal.roll(GregorianCalendar.YEAR, 1);
			String nextYear = new SimpleDateFormat("yyyy").format(nextYearCal.getTime());
			while (kohaBranches.next()) {
				String ilsCode = kohaBranches.getString("branchcode");
				existingAspenLocationStmt.setString(1, ilsCode);
				ResultSet existingAspenLocationRS = existingAspenLocationStmt.executeQuery();

				long existingLocationId = 0;
				if (!existingAspenLocationRS.next()) {
					//The branch does not exist yet, create it

					//Check to see if there is a library for the branch
					kohaLibraryGroupForBranchCodeStmt.setString(1, ilsCode);
					ResultSet kohaLibraryGroupForBranchCodeRS = kohaLibraryGroupForBranchCodeStmt.executeQuery();
					String libraryCode = ilsCode;
					String branchDisplayName = kohaBranches.getString("branchname");
					String libraryDisplayName = branchDisplayName;
					if (kohaLibraryGroupForBranchCodeRS.next()){
						//The library is nested in a search group, get information about the group
						long groupId = kohaLibraryGroupForBranchCodeRS.getLong("parent_id");
						kohaLibraryGroupStmt.setLong(1, groupId);
						ResultSet kohaLibraryGroupRS = kohaLibraryGroupStmt.executeQuery();
						if (kohaLibraryGroupRS.next()){
							libraryDisplayName = kohaLibraryGroupRS.getString("title");
							libraryCode = libraryDisplayName.replaceAll("\\W", "");

						}
						kohaLibraryGroupRS.close();
					}
					kohaLibraryGroupForBranchCodeRS.close();

					libraryCode = libraryCode.toLowerCase();
					libraryCode = StringUtils.trimTo(25, libraryCode);
					libraryDisplayName = StringUtils.trimTo(50, libraryDisplayName);

					existingAspenLibraryStmt.setString(1, libraryCode);
					ResultSet existingLibraryRS = existingAspenLibraryStmt.executeQuery();
					long libraryId = 0;
					if (existingLibraryRS.next()){
						libraryId = existingLibraryRS.getLong("libraryId");
					}else{
						addAspenLibraryStmt.setString(1, libraryCode);
						addAspenLibraryStmt.setString(2, libraryDisplayName);
						addAspenLibraryStmt.executeUpdate();
						ResultSet addAspenLibraryRS = addAspenLibraryStmt.getGeneratedKeys();
						if (addAspenLibraryRS.next()){
							libraryId = addAspenLibraryRS.getLong(1);
						}
					}

					if (libraryId != 0){
						addAspenLocationStmt.setLong(1, libraryId);
						addAspenLocationStmt.setString(2, StringUtils.trimTo(60, branchDisplayName));
						addAspenLocationStmt.setString(3, ilsCode);
						addAspenLocationStmt.executeUpdate();
						ResultSet addAspenLocationRS = addAspenLocationStmt.getGeneratedKeys();
						if (addAspenLocationRS.next()){
							long locationId = addAspenLocationRS.getLong(1);
							//Add records owned for the location
							addAspenLocationRecordsOwnedStmt.setLong(1, locationId);
							addAspenLocationRecordsOwnedStmt.setLong(2, indexingProfile.getId());
							addAspenLocationRecordsOwnedStmt.setString(3, ilsCode);
							addAspenLocationRecordsOwnedStmt.executeUpdate();

							//Add records to include for the location
							addAspenLocationRecordsToIncludeStmt.setLong(1, locationId);
							addAspenLocationRecordsToIncludeStmt.setLong(2, indexingProfile.getId());
							addAspenLocationRecordsToIncludeStmt.executeUpdate();
						}

						//Add records owned for the library
						addAspenLibraryRecordsOwnedStmt.setLong(1, libraryId);
						addAspenLibraryRecordsOwnedStmt.setLong(2, indexingProfile.getId());
						addAspenLibraryRecordsOwnedStmt.setString(3, ilsCode);
						addAspenLibraryRecordsOwnedStmt.executeUpdate();

						//Add records to include for the library
						addAspenLibraryRecordsToIncludeStmt.setLong(1, libraryId);
						addAspenLibraryRecordsToIncludeStmt.setLong(2, indexingProfile.getId());
						addAspenLibraryRecordsToIncludeStmt.executeUpdate();
					}

					existingAspenLocationRS = existingAspenLocationStmt.executeQuery();
					if (existingAspenLocationRS.next()){
						existingLocationId = existingAspenLocationRS.getLong("libraryId");
					}
				}else{
					existingLocationId = existingAspenLocationRS.getLong("locationId");
				}
				if (existingLocationId != 0) {
					HashMap<java.sql.Date, Long> existingHolidayDates = new HashMap<>();
					long libraryId = existingAspenLocationRS.getLong("libraryId");
					if (existingAspenLocationRS.getBoolean("isMainBranch")) {
						//Get the existing holidays in case we need to delete any
						existingHolidaysStmt.setLong(1, libraryId);
						existingHolidaysStmt.setDate(2, java.sql.Date.valueOf(currentYear + "-01-01"));
						ResultSet existingHolidaysRS = existingHolidaysStmt.executeQuery();
						existingHolidayDates = new HashMap<>();
						while (existingHolidaysRS.next()) {
							existingHolidayDates.put(existingHolidaysRS.getDate("date"), existingHolidaysRS.getLong("id"));
						}
					}

					//update hours for the branch.  We can only get closed times.
					existingHoursStmt.setLong(1, existingLocationId);
					ResultSet existingHoursRS = existingHoursStmt.executeQuery();
					if (existingHoursRS.next()) {
						long numHours = existingHoursRS.getLong(1);
						if (numHours == 0) {
							//Create default hours
							for (int i = 0; i < 7; i++) {
								addHoursStmt.setLong(1, existingLocationId);
								addHoursStmt.setLong(2, i);
								addHoursStmt.executeUpdate();
							}
						}
					}

					kohaRepeatableHolidaysStmt.setString(1, ilsCode);
					ResultSet kohaRepeatableHolidaysRS = kohaRepeatableHolidaysStmt.executeQuery();
					while (kohaRepeatableHolidaysRS.next()) {
						int weekday = kohaRepeatableHolidaysRS.getInt("weekday");
						if (!kohaRepeatableHolidaysRS.wasNull()) {
							//The library is closed on this date
							markLibraryClosed.setLong(1, existingLocationId);
							markLibraryClosed.setInt(2, weekday);
							markLibraryClosed.executeUpdate();
						} else {
							//Add the holiday for this year
							String holidayDate = currentYear + "-" + kohaRepeatableHolidaysRS.getString("month") + "-" + kohaRepeatableHolidaysRS.getString("day");
							java.sql.Date holidayDateAsDate = java.sql.Date.valueOf(holidayDate);
							String title = kohaRepeatableHolidaysRS.getString("title");
							addHolidayStmt.setLong(1, libraryId);
							addHolidayStmt.setDate(2, holidayDateAsDate);
							addHolidayStmt.setString(3, title);
							addHolidayStmt.executeUpdate();
							existingHolidayDates.remove(holidayDateAsDate);

							//Add the holiday for next year
							holidayDate = nextYear + "-" + kohaRepeatableHolidaysRS.getString("month") + "-" + kohaRepeatableHolidaysRS.getString("day");
							holidayDateAsDate = java.sql.Date.valueOf(holidayDate);
							addHolidayStmt.setLong(1, libraryId);
							addHolidayStmt.setDate(2, holidayDateAsDate);
							addHolidayStmt.setString(3, title);
							addHolidayStmt.executeUpdate();
							existingHolidayDates.remove(holidayDateAsDate);
						}
					}

					//update holidays for the library
					//Koha stores holidays per branch rather than per library system for now, we will just assign based on the main branch
					if (existingAspenLocationRS.getBoolean("isMainBranch")) {
						kohaSpecialHolidaysStmt.setInt(1, Integer.parseInt(currentYear));
						kohaSpecialHolidaysStmt.setInt(2, Integer.parseInt(nextYear));
						kohaSpecialHolidaysStmt.setString(3, ilsCode);
						ResultSet kohaSpecialHolidaysRS = kohaSpecialHolidaysStmt.executeQuery();
						while (kohaSpecialHolidaysRS.next()) {
							String holidayDate = kohaSpecialHolidaysRS.getString("year") + "-" + kohaSpecialHolidaysRS.getString("month") + "-" + kohaSpecialHolidaysRS.getString("day");
							java.sql.Date holidayDateAsDate = java.sql.Date.valueOf(holidayDate);
							String title = kohaSpecialHolidaysRS.getString("title");
							addHolidayStmt.setLong(1, libraryId);
							addHolidayStmt.setDate(2, holidayDateAsDate);
							addHolidayStmt.setString(3, title);
							addHolidayStmt.executeUpdate();
							existingHolidayDates.remove(holidayDateAsDate);
						}
					}

					//Remove anything leftover
					for (Long holidayToBeDeleted : existingHolidayDates.values()) {
						removeHolidayStmt.setLong(1, holidayToBeDeleted);
						removeHolidayStmt.executeUpdate();
					}
				} else {
					logger.error("Aspen does not currently have a location defined for code " + ilsCode + " please create the location in Aspen first so the library information is properly defined.");
				}
			}
		} catch (Exception e) {
			logger.error("Error updating branch information from Koha", e);
		}
	}

	private static void exportHolds(Connection dbConn, Connection kohaConn) {
		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = dbConn.setSavepoint("hold_update_start");
			dbConn.setAutoCommit(false);
			dbConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeUpdate();

			PreparedStatement addIlsHoldSummary = dbConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

			HashMap<String, Long> numHoldsByBib = new HashMap<>();
			//Export bib level holds
			PreparedStatement bibHoldsStmt = kohaConn.prepareStatement("select count(*) as numHolds, biblionumber from reserves group by biblionumber", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldsRS = bibHoldsStmt.executeQuery();
			while (bibHoldsRS.next()) {
				String bibId = bibHoldsRS.getString("biblionumber");
				Long numHolds = bibHoldsRS.getLong("numHolds");
				numHoldsByBib.put(bibId, numHolds);
			}
			bibHoldsRS.close();

			for (String bibId : numHoldsByBib.keySet()) {
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

			try {
				dbConn.commit();
				dbConn.setAutoCommit(true);
			} catch (Exception e) {
				logger.warn("error committing hold updates rolling back", e);
				dbConn.rollback(startOfHolds);
			}

		} catch (Exception e) {
			logger.error("Unable to export holds from Koha", e);
			if (startOfHolds != null) {
				try {
					dbConn.rollback(startOfHolds);
				} catch (Exception e1) {
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logger.info("Finished exporting holds");
	}

	private static int updateRecords(Connection dbConn, Connection kohaConn, String singleWorkId) {
		int totalChanges = 0;

		try {
			//Get the time the last extract was done
			logger.info("Starting to load changed records from Koha using the Database connection");
			long lastKohaExtractTime = indexingProfile.getLastUpdateOfChangedRecords();
			if (lastKohaExtractTime == 0) {
				lastKohaExtractTime = new Date().getTime() / 1000 - 24 * 60 * 60;
			}

			Timestamp lastExtractTimestamp = new Timestamp(lastKohaExtractTime * 1000);

			HashSet<String> changedBibIds = new HashSet<>();

			if (singleWorkId != null){
				changedBibIds.add(singleWorkId);
			}else {
				//Get a list of bibs that have changed
				PreparedStatement getChangedBibsFromKohaStmt;
				if (indexingProfile.isRunFullUpdate()) {
					getChangedBibsFromKohaStmt = kohaConn.prepareStatement("select biblionumber from biblio");
					logEntry.addNote("Getting all records from Koha");
				} else {
					getChangedBibsFromKohaStmt = kohaConn.prepareStatement("select biblionumber from biblio where timestamp >= ?");
					logEntry.addNote("Getting changes to records since " + lastExtractTimestamp.toString() + " UTC");

					getChangedBibsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);
				}

				ResultSet getChangedBibsFromKohaRS = getChangedBibsFromKohaStmt.executeQuery();
				while (getChangedBibsFromKohaRS.next()) {
					changedBibIds.add(getChangedBibsFromKohaRS.getString("biblionumber"));
				}

				//Get a list of changed bibs by biblio_metadata timestamp as well
				if (!indexingProfile.isRunFullUpdate()){
					PreparedStatement getChangedBibMetadataFromKohaStmt = kohaConn.prepareStatement("select biblionumber from biblio_metadata where timestamp >= ?");
					logEntry.addNote("Getting changes to record metadata since " + lastExtractTimestamp.toString() + " UTC");

					getChangedBibMetadataFromKohaStmt.setTimestamp(1, lastExtractTimestamp);

					ResultSet getChangedBibMetadataFromKohaRS = getChangedBibMetadataFromKohaStmt.executeQuery();
					int numRecordsWithChangedMetadata = 0;
					while (getChangedBibMetadataFromKohaRS.next()) {
						if (changedBibIds.add(getChangedBibMetadataFromKohaRS.getString("biblionumber"))){
							numRecordsWithChangedMetadata++;
						}
					}
					logEntry.addNote(numRecordsWithChangedMetadata + " records had changes to the metadata, but not the bib.");
				}
			}

			if (singleWorkId == null && !indexingProfile.isRunFullUpdate()) {
				//Get a list of items that have changed
				PreparedStatement getChangedItemsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from items where timestamp >= ?");
				getChangedItemsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);

				ResultSet itemChangeRS = getChangedItemsFromKohaStmt.executeQuery();
				while (itemChangeRS.next()) {
					changedBibIds.add(itemChangeRS.getString("biblionumber"));
				}

				//Items that have been deleted do not update the bib as changed so get that list as well
				PreparedStatement getDeletedItemsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from deleteditems where timestamp >= ?");
				getDeletedItemsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);

				ResultSet itemDeletedRS = getDeletedItemsFromKohaStmt.executeQuery();
				while (itemDeletedRS.next()) {
					changedBibIds.add(itemDeletedRS.getString("biblionumber"));
				}
			}

			logger.info("A total of " + changedBibIds.size() + " bibs were updated since the last export");
			logEntry.setNumProducts(changedBibIds.size());
			logEntry.saveResults();
			int numProcessed = 0;
			for (String curBibId : changedBibIds) {
				//Update the marc record
				updateBibRecord(curBibId);

				numProcessed++;
				if (numProcessed % 250 == 0) {
					logEntry.saveResults();
				}
			}
			logger.info("Updated " + changedBibIds.size() + " records");

			//Process any bibs that have been deleted
			int numRecordsDeleted = 0;
			if (singleWorkId == null) {
				PreparedStatement getDeletedBibsFromKohaStmt;
				if (indexingProfile.isRunFullUpdate()) {
					getDeletedBibsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from deletedbiblio");
				} else {
					getDeletedBibsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from deletedbiblio where timestamp >= ?");
					getDeletedBibsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);
				}

				ResultSet bibDeletedRS = getDeletedBibsFromKohaStmt.executeQuery();
				while (bibDeletedRS.next()) {
					String bibId = bibDeletedRS.getString("biblionumber");
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibId);
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					numRecordsDeleted++;
					logEntry.incDeleted();
				}
				logEntry.saveResults();
				logger.info("Deleted " + numRecordsDeleted + " records");
			}

			processRecordsToReload(indexingProfile, logEntry);

			totalChanges = changedBibIds.size() + numRecordsDeleted;

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				recordGroupingProcessorSingleton = null;
				groupedWorkIndexer = null;
			}

			//Update the last extract time for the indexing profile
			if (singleWorkId == null) {
				if (indexingProfile.isRunFullUpdate()) {
					PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfAllRecords = ?, runFullUpdate = 0 WHERE id = ?");
					updateVariableStmt.setLong(1, startTimeForLogging);
					updateVariableStmt.setLong(2, indexingProfile.getId());
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
				} else {
					if (!logEntry.hasErrors()) {
						PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfChangedRecords = ? WHERE id = ?");
						updateVariableStmt.setLong(1, startTimeForLogging);
						updateVariableStmt.setLong(2, indexingProfile.getId());
						updateVariableStmt.executeUpdate();
						updateVariableStmt.close();
					}
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error loading changed records from Koha database", e);
			//Don't quit since that keeps the exporter from running continuously
		}
		logger.info("Finished loading changed records from Koha database");

		return totalChanges;
	}

	private static void processRecordsToReload(IndexingProfile indexingProfile, IlsExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='" + indexingProfile.getName() + "'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordIdentifier = getRecordsToReloadRS.getString("identifier");
				File marcFile = indexingProfile.getFileForIlsRecord(recordIdentifier);
				if (!marcFile.exists()) {
					logEntry.incErrors("Could not find marc for record to reload " + recordIdentifier);
				} else {
					FileInputStream marcFileStream = new FileInputStream(marcFile);
					MarcPermissiveStreamReader streamReader = new MarcPermissiveStreamReader(marcFileStream, true, true);
					if (streamReader.hasNext()) {
						Record marcRecord = streamReader.next();
						//Regroup the record
						String groupedWorkId = groupKohaRecord(marcRecord);
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

	private static void updateBibRecord(String curBibId) throws FileNotFoundException, SQLException {
		//Load the existing marc record from file
		try {
			File marcFile = indexingProfile.getFileForIlsRecord(curBibId);
			if (!marcFile.getParentFile().exists()) {
				//noinspection ResultOfMethodCallIgnored
				marcFile.getParentFile().mkdirs();
			}

			//Create a new record from data in the database (faster and more reliable than using ILSDI or OAI export)
			getBaseMarcRecordStmt.setString(1, curBibId);
			ResultSet baseMarcRecordRS = getBaseMarcRecordStmt.executeQuery();
			while (baseMarcRecordRS.next()) {
				String marcXML = baseMarcRecordRS.getString("metadata");
				marcXML = StringUtils.stripNonValidXMLCharacters(marcXML);
				MarcXmlReader marcXmlReader = new MarcXmlReader(new ByteArrayInputStream(marcXML.getBytes(Charset.forName("UTF-8"))));

				//This record has all the basic bib data
				Record marcRecord = marcXmlReader.next();

				//Add the item information
				getBibItemsStmt.setString(1, curBibId);
				ResultSet bibItemsRS = getBibItemsStmt.executeQuery();
				while (bibItemsRS.next()) {
					DataField itemField = marcFactory.newDataField("952", ' ', ' ');

					addSubfield(itemField, 'p', bibItemsRS.getString("barcode"));
					addSubfield(itemField, 'e', bibItemsRS.getString("booksellerid"));
					addSubfield(itemField, '8', bibItemsRS.getString("ccode"));
					addSubfield(itemField, '6', bibItemsRS.getString("cn_sort"));
					addSubfield(itemField, '2', bibItemsRS.getString("cn_source"));
					addSubfield(itemField, 'f', bibItemsRS.getString("coded_location_qualifier"));
					addSubfield(itemField, 't', bibItemsRS.getString("copynumber"));
					addSubfield(itemField, '4', bibItemsRS.getString("damaged"));
					addSubfield(itemField, 'd', bibItemsRS.getString("dateaccessioned"));
					addSubfield(itemField, 's', bibItemsRS.getString("datelastborrowed"));
					addSubfield(itemField, 'r', bibItemsRS.getString("datelastseen"));
					addSubfield(itemField, 'h', bibItemsRS.getString("enumchron"));
					addSubfield(itemField, 'b', bibItemsRS.getString("holdingbranch"));
					addSubfield(itemField, 'a', bibItemsRS.getString("homebranch"));
					addSubfield(itemField, 'l', bibItemsRS.getString("issues"));
					addSubfield(itemField, 'o', bibItemsRS.getString("itemcallnumber"));
					addSubfield(itemField, '1', bibItemsRS.getString("itemlost"));
					addSubfield(itemField, 'z', bibItemsRS.getString("itemnotes"));
					//addSubfield(itemField, 'x', bibItemsRS.getString("itemnotes_nonpublic"));
					addSubfield(itemField, '9', bibItemsRS.getString("itemnumber"));
					addSubfield(itemField, 'y', bibItemsRS.getString("itype"));
					addSubfield(itemField, 'c', bibItemsRS.getString("location"));
					addSubfield(itemField, '3', bibItemsRS.getString("materials"));
					addSubfield(itemField, '7', bibItemsRS.getString("notforloan"));
					addSubfield(itemField, 'q', bibItemsRS.getString("onloan"));
					addSubfield(itemField, 'g', bibItemsRS.getString("price"));
					addSubfield(itemField, 'm', bibItemsRS.getString("renewals"));
					addSubfield(itemField, 'v', bibItemsRS.getString("replacementprice"));
					addSubfield(itemField, 'w', bibItemsRS.getString("replacementpricedate"));
					addSubfield(itemField, 'n', bibItemsRS.getString("renewals"));
					addSubfield(itemField, '5', bibItemsRS.getString("restricted"));
					addSubfield(itemField, 'j', bibItemsRS.getString("stack"));
					addSubfield(itemField, 'i', bibItemsRS.getString("stocknumber"));
					addSubfield(itemField, 'u', bibItemsRS.getString("uri"));
					addSubfield(itemField, '0', bibItemsRS.getString("withdrawn"));
					marcRecord.addVariableField(itemField);
				}

				if (marcFile.exists()) {
					logEntry.incUpdated();
				} else {
					logEntry.incAdded();
				}
				MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile), "UTF-8", true);
				writer.write(marcRecord);
				writer.close();

				//Regroup the record
				String groupedWorkId = groupKohaRecord(marcRecord);
				if (groupedWorkId != null) {
					//Reindex the record
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
				}
			}

		} catch (Exception e) {
			if (e instanceof com.mysql.cj.jdbc.exceptions.CommunicationsException) {
				throw e;
			} else if (e instanceof com.mysql.cj.exceptions.StatementIsClosedException) {
				throw e;
			} else if (e instanceof SQLException && ((SQLException) e).getSQLState().equals("S1009")) {
				throw e;
			} else {
				logEntry.incErrors("Error updating marc record for bib " + curBibId, e);
			}
		}
	}

	private static void addSubfield(DataField itemField, char code, String data) {
		if (data != null) {
			itemField.addSubfield(marcFactory.newSubfield(code, data));
		}
	}

	private static String groupKohaRecord(Record marcRecord) {
		return getRecordGroupingProcessor().processMarcRecord(marcRecord, true);
	}

	private static MarcRecordGrouper getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new MarcRecordGrouper(serverName, dbConn, indexingProfile, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}
}
