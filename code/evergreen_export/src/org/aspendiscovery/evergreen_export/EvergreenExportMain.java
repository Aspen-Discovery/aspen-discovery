package org.aspendiscovery.evergreen_export;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RecordGroupingProcessor;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONObject;
import org.marc4j.MarcException;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.*;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import java.io.*;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

public class EvergreenExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;
	private static String baseUrl;

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
			if (args.length > 1) {
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord")) {
					extractSingleWork = true;
				}
			}
		}
		if (extractSingleWork) {
			singleWorkId = StringUtils.getInputFromCommandLine("Enter the id of the title to extract");
		}
		String profileToLoad = "ils";

		String processName = "evergreen_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");

		while (true) {
			java.util.Date startTime = new Date();
			logger.info(startTime + ": Starting Evergreen Extract");
			startTimeForLogging = startTime.getTime() / 1000;

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

				PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = 'evergreen'");
				ResultSet accountProfileRS = accountProfileStmt.executeQuery();
				if (accountProfileRS.next()){
					baseUrl = accountProfileRS.getString("patronApiUrl");
					profileToLoad = accountProfileRS.getString("recordSource");
				}else{
					logEntry.incErrors("Could not load Evergreen account profile");
					accountProfileRS.close();
					continue;
				}
				accountProfileRS.close();

				logEntry = new IlsExtractLogEntry(dbConn, profileToLoad, logger);
				//Remove log entries older than 45 days
				long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
				try {
					int numDeletions = dbConn.prepareStatement("DELETE from ils_extract_log WHERE startTime < " + earliestLogToKeep + " AND indexingProfile = '" + profileToLoad + "'").executeUpdate();
					logger.info("Deleted " + numDeletions + " old log entries");
				} catch (SQLException e) {
					logger.error("Error deleting old log entries", e);
				}

				if (baseUrl != null) {
					indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);
					if (indexingProfile == null){
						logEntry.incErrors("Could not load indexing profile for " + profileToLoad);
					}else {
						logEntry.setIsFullUpdate(indexingProfile.isRunFullUpdate());

						if (!extractSingleWork) {
							updateBranchInfo(dbConn);
							logEntry.addNote("Finished updating branch information");

							exportVolumes(dbConn);

							//Update works that have changed since the last index
							numChanges = updateRecords();
						}else{
							MarcFactory marcFactory = MarcFactory.newInstance();
							numChanges = updateBibFromEvergreen(singleWorkId, marcFactory, true);
						}
					}
				}else{
					logEntry.incErrors("Could not load account profile.");
				}

				if (!extractSingleWork) {
					processRecordsToReload(indexingProfile, logEntry);
				}

				if (recordGroupingProcessorSingleton != null) {
					recordGroupingProcessorSingleton.close();
					recordGroupingProcessorSingleton = null;
				}

				if (groupedWorkIndexer != null) {
					groupedWorkIndexer.finishIndexingFromExtract(logEntry);
					groupedWorkIndexer.close();
					groupedWorkIndexer = null;
				}

				try {
					if (indexingProfile.isRunFullUpdate()) {
						if (!logEntry.hasErrors()) {
							PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfAllRecords = ?, runFullUpdate = 0 WHERE id = ?");
							updateVariableStmt.setLong(1, startTimeForLogging);
							updateVariableStmt.setLong(2, indexingProfile.getId());
							updateVariableStmt.executeUpdate();
							updateVariableStmt.close();
						}
					} else {
						if (!logEntry.hasErrors()) {
							PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfChangedRecords = ? WHERE id = ?");
							updateVariableStmt.setLong(1, startTimeForLogging);
							updateVariableStmt.setLong(2, indexingProfile.getId());
							updateVariableStmt.executeUpdate();
							updateVariableStmt.close();
						}
					}
				}catch (SQLException e){
					logEntry.incErrors("Error updating when the records were last indexed", e);
				}

				logEntry.setFinished();

				Date currentTime = new Date();
				logger.info(currentTime + ": Finished Evergreen Extract");
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
			if (extractSingleWork) {
				disconnectDatabase();
				break;
			}
			disconnectDatabase();

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				//Quit and we will restart after if finishes
				System.exit(0);
			}else{
				//Pause before running the next export (longer if we didn't get any actual changes)
				try {
					System.gc();
					if (numChanges == 0) {
						//noinspection BusyWait
						Thread.sleep(1000 * 60 * 5);
					} else {
						//noinspection BusyWait
						Thread.sleep(1000 * 60);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		} //Infinite loop
	}

	private static void exportVolumes(Connection dbConn) {
		File supplementalDirectory = new File(indexingProfile.getMarcPath() + "/../supplemental");
		if (supplementalDirectory.exists()){
			File partsFile = new File(indexingProfile.getMarcPath() + "/../supplemental/parts.csv");
			if (partsFile.exists()) {
				long lastVolumeTimeStamp = indexingProfile.getLastVolumeExportTimestamp();
				long fileTimeStamp = partsFile.lastModified();
				if ((fileTimeStamp / 1000) > lastVolumeTimeStamp) {
					logEntry.addNote("Checking to see if the volume file is still changing");
					logEntry.saveResults();
					boolean fileChanging = true;
					while (fileChanging) {
						fileChanging = false;
						try {
							Thread.sleep(1000);
						} catch (InterruptedException e) {
							logger.debug("Thread interrupted while checking if volume file is changing");
						}
						if (fileTimeStamp != partsFile.lastModified()) {
							fileTimeStamp = partsFile.lastModified();
							fileChanging = true;
						}
					}
					try {
						PreparedStatement getExistingVolumes = dbConn.prepareStatement("SELECT id, volumeId from ils_volume_info", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
						HashMap<String, Long> existingVolumes = new HashMap<>();
						ResultSet existingVolumesRS = getExistingVolumes.executeQuery();
						while (existingVolumesRS.next()) {
							existingVolumes.put(existingVolumesRS.getString("volumeId"), existingVolumesRS.getLong("id"));
						}
						existingVolumesRS.close();

						PreparedStatement updateVolumeStmt = dbConn.prepareStatement("UPDATE ils_volume_info SET recordId =?, displayLabel = ?, relatedItems = ?, displayOrder = ? WHERE id = ?");
						PreparedStatement addVolumeStmt = dbConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems, displayOrder) VALUES (?,?,?,?, ?) ON DUPLICATE KEY update recordId = VALUES(recordId), displayLabel = VALUES(displayLabel), relatedItems = VALUES(relatedItems), displayOrder = VALUES(displayOrder)");
						PreparedStatement deleteVolumeStmt = dbConn.prepareStatement("DELETE from ils_volume_info where id = ?");

						TreeMap<String, VolumeInfo> volumes = new TreeMap<>();

						BufferedReader partsReader = new BufferedReader(new FileReader(partsFile));
						String curValuesStr = partsReader.readLine();
						while (curValuesStr != null) {
							String[] curValues = curValuesStr.split("\\|");
							if (curValues.length >= 4) {
								String bibID = indexingProfile.getName() + ":" + curValues[0];
								String partLabel = curValues[1];
								String internalPartId = curValues[2];
								String itemBarcode = curValues[3];

								String volumeIdentifier = bibID + ":" + partLabel.toLowerCase() + ":" + internalPartId;

								VolumeInfo volumeInfo = volumes.get(volumeIdentifier);
								if (volumeInfo == null) {
									volumeInfo = new VolumeInfo();
									volumeInfo.bibNumber = bibID;
									volumeInfo.volume = partLabel;
									volumeInfo.volumeIdentifier = internalPartId;
									volumes.put(volumeIdentifier, volumeInfo);
								}
								volumeInfo.relatedItems.add(itemBarcode);
							}
							curValuesStr = partsReader.readLine();
						}

						partsReader.close();

						logEntry.addNote("Saving " + volumes.size() + " volumes");
						logEntry.saveResults();

						//Save volumes
						int numUpdated = 0;
						int numAdded = 0;
						int numVolumes = 0;
						for (String volumeId : volumes.keySet()) {
							VolumeInfo volumeInfo = volumes.get(volumeId);
							if (existingVolumes.containsKey(volumeInfo.volumeIdentifier)) {
								long existingVolumeId = existingVolumes.get(volumeInfo.volumeIdentifier);
								//Update the volume information
								updateVolumeStmt.setString(1, volumeInfo.bibNumber);
								updateVolumeStmt.setString(2, volumeInfo.volume);
								updateVolumeStmt.setString(3, volumeInfo.getRelatedItemsAsString());
								updateVolumeStmt.setLong(4, ++numVolumes);
								updateVolumeStmt.setLong(5, existingVolumeId);
								updateVolumeStmt.executeUpdate();
								existingVolumes.remove(volumeInfo.volumeIdentifier);
								numUpdated++;
							} else {
								//Add the volume
								addVolumeStmt.setString(1, volumeInfo.bibNumber);
								addVolumeStmt.setString(2, volumeInfo.volumeIdentifier);
								addVolumeStmt.setString(3, volumeInfo.volume);
								addVolumeStmt.setString(4, volumeInfo.getRelatedItemsAsString());
								addVolumeStmt.setLong(5, ++numVolumes);
								addVolumeStmt.executeUpdate();
								numAdded++;
							}
						}
						//Remove any leftover volumes
						long numVolumesDeleted = 0;
						for (Long existingVolume : existingVolumes.values()) {
							deleteVolumeStmt.setLong(1, existingVolume);
							deleteVolumeStmt.executeUpdate();
							numVolumesDeleted++;
						}

						logEntry.addNote("Added " + numAdded + ", updated " + numUpdated + ", and deleted " + numVolumesDeleted + " volumes");
						logEntry.saveResults();

						deleteVolumeStmt.close();
						addVolumeStmt.close();
						updateVolumeStmt.close();

						//Update the indexing profile to store the last volume time change
						PreparedStatement updateLastVolumeExportTimeStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastVolumeExportTimestamp = ? where id = ?");
						updateLastVolumeExportTimeStmt.setLong(1, fileTimeStamp / 1000);
						updateLastVolumeExportTimeStmt.setLong(2, indexingProfile.getId());
						updateLastVolumeExportTimeStmt.executeUpdate();
					} catch (IOException e) {
						logEntry.incErrors("Error reading part information", e);
					} catch (SQLException e2) {
						logEntry.incErrors("SQL Error reading parts and storing as volumes", e2);
					}
				}
			}
		}else{
			logEntry.addNote("Supplemental directory did not exist");
		}
	}

	private static PreparedStatement existingAspenLocationStmt;
	private static PreparedStatement existingAspenLibraryStmt;
	private static PreparedStatement addAspenLibraryStmt;
	private static PreparedStatement addAspenLocationStmt;
	private static PreparedStatement addAspenLocationRecordsOwnedStmt;
	private static PreparedStatement addAspenLocationRecordsToIncludeStmt;
	private static PreparedStatement addAspenLibraryRecordsOwnedStmt;
	private static PreparedStatement addAspenLibraryRecordsToIncludeStmt;
	private static PreparedStatement createTranslationMapStmt;
	private static PreparedStatement getTranslationMapStmt;
	private static PreparedStatement getExistingValuesForMapStmt;
	private static PreparedStatement insertTranslationStmt;
	private static void updateBranchInfo(Connection dbConn) {
		//Setup our prepared statements
		try {
			existingAspenLocationStmt = dbConn.prepareStatement("SELECT libraryId, locationId, isMainBranch from location where code = ?");
			existingAspenLibraryStmt = dbConn.prepareStatement("SELECT libraryId from library where ilsCode = ?");
			addAspenLibraryStmt = dbConn.prepareStatement("INSERT INTO library (subdomain, displayName, ilsCode, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, 1, 1)", Statement.RETURN_GENERATED_KEYS);
			addAspenLocationStmt = dbConn.prepareStatement("INSERT INTO location (libraryId, displayName, code, historicCode, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, ?, -1, -1)", Statement.RETURN_GENERATED_KEYS);
			addAspenLocationRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO location_records_owned (locationId, indexingProfileId, location, subLocation) VALUES (?, ?, ?, '')");
			addAspenLocationRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO location_records_to_include (locationId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
			addAspenLibraryRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO library_records_owned (libraryId, indexingProfileId, location, subLocation) VALUES (?, ?, ?, '') ON DUPLICATE KEY UPDATE location = CONCAT(location, '|', VALUES(location))");
			addAspenLibraryRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO library_records_to_include (libraryId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");

			createTranslationMapStmt = dbConn.prepareStatement("INSERT INTO translation_maps (name, indexingProfileId) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			getTranslationMapStmt = dbConn.prepareStatement("SELECT id from translation_maps WHERE name = ? and indexingProfileId = ?");
			getExistingValuesForMapStmt = dbConn.prepareStatement("SELECT * from translation_map_values where translationMapId = ?");
			insertTranslationStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");
		}catch (Exception e){
			logEntry.incErrors("Could not setup database statements to update branch information", e);
			return;
		}

		Long locationMapId = -1L;
		HashMap<String, String> existingLocations =new HashMap<>();

		try {
			locationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "location");
			existingLocations = getExistingTranslationMapValues(getExistingValuesForMapStmt, locationMapId);
		}catch (SQLException e) {
			logEntry.incErrors("Could not load translation maps ", e);
		}

		//Get the organization tree
		String apiUrl = baseUrl +  "/osrf-gateway-v1";
		String params = "service=open-ils.actor&method=open-ils.actor.org_tree.retrieve";
		HashMap<String, String> headers = new HashMap<>();

		WebServiceResponse response = NetworkUtils.postToURL(apiUrl, params, "application/x-www-form-urlencoded", null, logger);
		if (response.isSuccess()){
			JSONObject orgData = response.getJSONResponse();
			if (orgData.has("payload")){
				JSONArray mainPayload = orgData.getJSONArray("payload");
				for (int i = 0; i < mainPayload.length(); i++){
					JSONObject mainPayloadObject = mainPayload.getJSONObject(i);
					if (mainPayloadObject.has("__p")){
						JSONArray subPayload = mainPayloadObject.getJSONArray("__p");
						loadOrganizationalUnit(subPayload, 0, 0, locationMapId, existingLocations);
					}
				}
			}
		}

		try {
			existingAspenLocationStmt.close();
			existingAspenLibraryStmt.close();
			addAspenLibraryStmt.close();
			addAspenLocationStmt.close();
			addAspenLocationRecordsOwnedStmt.close();
			addAspenLocationRecordsToIncludeStmt.close();
			addAspenLibraryRecordsOwnedStmt.close();
			addAspenLibraryRecordsToIncludeStmt.close();
			createTranslationMapStmt.close();
			getTranslationMapStmt.close();
			getExistingValuesForMapStmt.close();
			insertTranslationStmt.close();
		}catch (Exception e){
			logEntry.incErrors("Error closing statements while updating branch info", e);
		}

	}

	static String[] orgUnitFields = new String[]{"children",
		"billing_address",
		"holds_address",
		"id",
		"ill_address",
		"mailing_address",
		"name",
		"ou_type",
		"parent_ou",
		"shortname",
		"email",
		"phone",
		"opac_visible",
		"fiscal_calendar",
		"users",
		"closed_dates",
		"circulations",
		"settings",
		"addresses",
		"checkins",
		"workstations",
		"fund_alloc_pcts",
		"copy_location_orders",
		"atc_prev_dests",
		"resv_requests",
		"resv_pickups",
		"rsrc_types",
		"resources",
		"rsrc_attrs",
		"attr_vals",
		"hours_of_operation"
	};
	private static void loadOrganizationalUnit(JSONArray orgUnitPayload, int level, long parentId, long locationMapId, HashMap<String, String> existingLocations) {
		HashMap<String, Object> mappedOrgUnitField = mapFields(orgUnitPayload, orgUnitFields);
		if (level == 0){
			//This is the top level unit, it is not written to Aspen, just process all the children
			JSONArray children = (JSONArray) mappedOrgUnitField.get("children");
			for (int i = 0; i < children.length(); i++){
				JSONObject curObject = children.getJSONObject(i);
				if (curObject.has("__p")){
					JSONArray libraryUnitPayload = curObject.getJSONArray("__p");
					loadOrganizationalUnit(libraryUnitPayload, level +1, 0, locationMapId, existingLocations);
				}
			}
		}else if(level == 1){
			//This is a library, add it into the system.
			long libraryId = 0;
			try {
				String shortName = (String) mappedOrgUnitField.get("shortname");
				existingAspenLibraryStmt.setString(1, shortName);
				ResultSet existingLibraryRS = existingAspenLibraryStmt.executeQuery();
				if (!existingLibraryRS.next()) {
					addAspenLibraryStmt.setString(1, shortName);
					addAspenLibraryStmt.setString(2, (String)mappedOrgUnitField.get("name"));
					addAspenLibraryStmt.setString(3, shortName);
					addAspenLibraryStmt.executeUpdate();
					ResultSet addAspenLibraryRS = addAspenLibraryStmt.getGeneratedKeys();
					if (addAspenLibraryRS.next()){
						libraryId = addAspenLibraryRS.getLong(1);
					}

					//Add records to include for the library
					addAspenLibraryRecordsToIncludeStmt.setLong(1, libraryId);
					addAspenLibraryRecordsToIncludeStmt.setLong(2, indexingProfile.getId());
					addAspenLibraryRecordsToIncludeStmt.executeUpdate();
				}else{
					libraryId = existingLibraryRS.getLong("libraryId");
				}
			}catch (Exception e){
				logEntry.incErrors("Error adding library " + mappedOrgUnitField.get("shortname") + " to Aspen", e);
			}

			JSONArray children = (JSONArray) mappedOrgUnitField.get("children");
			for (int i = 0; i < children.length(); i++){
				JSONObject curObject = children.getJSONObject(i);
				if (curObject.has("__p")){
					JSONArray libraryUnitPayload = curObject.getJSONArray("__p");
					loadOrganizationalUnit(libraryUnitPayload, level +1, libraryId, locationMapId, existingLocations);
				}
			}

		}else if(level == 2){
			//This is a branch, add it to the system
			try {
				Integer branchId = (Integer) mappedOrgUnitField.get("id");
				String shortName = (String) mappedOrgUnitField.get("shortname");

				updateTranslationMap(shortName, (String)mappedOrgUnitField.get("name"), insertTranslationStmt, locationMapId, existingLocations);

				existingAspenLocationStmt.setString(1, shortName);
				ResultSet existingLocationRS = existingAspenLocationStmt.executeQuery();
				if (!existingLocationRS.next()){
					addAspenLocationStmt.setLong(1, parentId);
					addAspenLocationStmt.setString(2, StringUtils.trimTo(60, (String)mappedOrgUnitField.get("name")));
					addAspenLocationStmt.setString(3, shortName);
					addAspenLocationStmt.setInt(4, branchId);

					addAspenLocationStmt.executeUpdate();
					ResultSet addAspenLocationRS = addAspenLocationStmt.getGeneratedKeys();
					if (addAspenLocationRS.next()){
						long locationId = addAspenLocationRS.getLong(1);

						//Add records owned for the location
						addAspenLocationRecordsOwnedStmt.setLong(1, locationId);
						addAspenLocationRecordsOwnedStmt.setLong(2, indexingProfile.getId());
						addAspenLocationRecordsOwnedStmt.setString(3, shortName);
						addAspenLocationRecordsOwnedStmt.executeUpdate();

						//Add records owned for the library, since we have multiple locations defined by ID, we will add separate rows for each.
						addAspenLibraryRecordsOwnedStmt.setLong(1, parentId);
						addAspenLibraryRecordsOwnedStmt.setLong(2, indexingProfile.getId());
						addAspenLibraryRecordsOwnedStmt.setString(3, shortName);
						addAspenLibraryRecordsOwnedStmt.executeUpdate();

						//Add records to include for the location
						addAspenLocationRecordsToIncludeStmt.setLong(1, locationId);
						addAspenLocationRecordsToIncludeStmt.setLong(2, indexingProfile.getId());
						addAspenLocationRecordsToIncludeStmt.executeUpdate();
					}
				}
			}catch (Exception e){
				logEntry.incErrors("Error adding branch " + mappedOrgUnitField.get("shortname") + " to Aspen", e);
			}
		}
	}

	private static HashMap<String, Object> mapFields(JSONArray orgUnitPayload, String[] orgUnitFields) {
		HashMap<String, Object> mappedFields = new HashMap<>();
		for (int i = 0; i < orgUnitPayload.length(); i++){
			mappedFields.put(orgUnitFields[i], orgUnitPayload.get(i));
		}
		return mappedFields;
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

	private synchronized static String groupEvergreenRecord(Record marcRecord) {
		return getRecordGroupingProcessor().processMarcRecord(marcRecord, true, null);
	}

	private synchronized static MarcRecordGrouper getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new MarcRecordGrouper(serverName, dbConn, indexingProfile, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private synchronized static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
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
				Record marcRecord = getGroupedWorkIndexer().loadMarcRecordFromDatabase(indexingProfile.getName(), recordIdentifier, logEntry);
				if (marcRecord != null){
					logEntry.incRecordsRegrouped();
					//Regroup the record
					String groupedWorkId = groupEvergreenRecord(marcRecord);
					//Reindex the record
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
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

	private static int updateRecords() {
		//Check to see if we should regroup all existing records
		try {
			if (indexingProfile.isRegroupAllRecords()) {
				MarcRecordGrouper recordGrouper = getRecordGroupingProcessor();
				recordGrouper.regroupAllRecords(dbConn, indexingProfile, getGroupedWorkIndexer(), logEntry);
			}
		}catch (Exception e){
			logEntry.incErrors("Error regrouping all records", e);
		}

		int totalChanges = 0;

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

		//Get a list of marc deltas since the last marc record, we will actually process all of these since the full export takes so long
		File marcDeltaPath = new File(marcExportPath.getParentFile() + "/marc_delta");
		File[] exportedMarcDeltaFiles = marcDeltaPath.listFiles((dir, name) -> name.endsWith("mrc") || name.endsWith("marc"));
		if (exportedMarcDeltaFiles != null && exportedMarcDeltaFiles.length > 0){
			//Sort from oldest to newest
			Arrays.sort(exportedMarcDeltaFiles, Comparator.comparingLong(File::lastModified));
			filesToProcess.addAll(Arrays.asList(exportedMarcDeltaFiles));
		}

		if (filesToProcess.size() > 0){
			//Update all records based on the MARC export
			logEntry.addNote("Updating based on MARC extract");
			totalChanges = updateRecordsUsingMarcExtract(filesToProcess, hasFullExportFile, fullExportFile, dbConn);
		}

		//Process CSV Files
		File[] exportedCsvFiles = marcDeltaPath.listFiles((dir, name) -> name.endsWith("csv"));
		if (exportedCsvFiles != null && exportedCsvFiles.length > 0) {
			//Sort from oldest to newest
			Arrays.sort(exportedCsvFiles, Comparator.comparingLong(File::lastModified));
			totalChanges += updateItemsUsingCsvFile(exportedCsvFiles, lastUpdateFromMarc, dbConn);
		}


		//Process ID Files
		File[] exportedIdFiles = marcDeltaPath.listFiles((dir, name) -> (name.endsWith("ids") && name.startsWith("all")));
		if (exportedIdFiles != null && exportedIdFiles.length > 0){
			//Sort from newest to oldest
			Arrays.sort(exportedIdFiles, Comparator.comparingLong(File::lastModified).reversed());
			//Just process the newest 1 file.
			totalChanges += updateItemsBasedOnIds(exportedIdFiles[0], lastUpdateFromMarc, dbConn);
			for (int i = 1; i < exportedIdFiles.length; i++) {
				if (!exportedIdFiles[i].delete()) {
					logEntry.incErrors("Could not delete old ids file " + exportedIdFiles[i]);
				}
			}
		}

		File[] exportedDeletedIdFiles = marcDeltaPath.listFiles((dir, name) -> (name.endsWith("ids") && name.startsWith("incremental_deleted")));
		if (exportedDeletedIdFiles != null && exportedDeletedIdFiles.length > 0){
			//For now we don't care about these since we process the all ids file, just delete them.
			for (int i = 1; i < exportedDeletedIdFiles.length; i++) {
				if (!exportedDeletedIdFiles[i].delete()) {
					logEntry.incErrors("Could not delete old ids file " + exportedDeletedIdFiles[i]);
				}
			}
		}

		return totalChanges;
	}

	private static int updateItemsBasedOnIds(File idsFile, long lastUpdateFromMarc, Connection dbConn) {
		int numUpdates = 0;
		logEntry.addNote("Processing ids file " + idsFile);
		try {
			//Get all existing ids in the database
			PreparedStatement getAllExistingRecordsStmt = dbConn.prepareStatement("SELECT ilsId, deleted FROM ils_records where source = ?;");
			getAllExistingRecordsStmt.setString(1, indexingProfile.getName());
			ResultSet existingRecordsRS = getAllExistingRecordsStmt.executeQuery();
			HashMap<String, Boolean> existingRecords = new HashMap<>();
			while (existingRecordsRS.next()){
				existingRecords.put(existingRecordsRS.getString("ilsId"), existingRecordsRS.getBoolean("deleted"));
			}

			//Read the file to see what has been added or deleted
			BufferedReader reader = new BufferedReader(new FileReader(idsFile));
			String id = reader.readLine();
			HashSet<String> newIds = new HashSet<>();
			HashSet<String> restoredIds = new HashSet<>();
			HashSet<String> deletedIds = new HashSet<>();
			while (id != null){
				if (existingRecords.containsKey(id)){
					if (existingRecords.get(id) == Boolean.TRUE){
						//This was previously deleted
						restoredIds.add(id);
					}
					existingRecords.remove(id);
				}else{
					newIds.add(id);
				}
				id = reader.readLine();
			}
			//Check the existing records to see what hasn't been deleted already
			for (String existingRecord : existingRecords.keySet()){
				if (existingRecords.get(id) == Boolean.FALSE){
					deletedIds.add(id);
				}
			}
			logEntry.addNote("There are " + newIds.size() + " new and " + restoredIds.size() + " restored ids and " + deletedIds.size() + " deleted ids");

			logEntry.addNote("Restoring previously deleted ids");
			logEntry.saveResults();
			GroupedWorkIndexer indexer = getGroupedWorkIndexer();
			RecordGroupingProcessor recordGroupingProcessor = getRecordGroupingProcessor();
			int numRestored = 0;
			for (String restoredRecordId : restoredIds) {
				indexer.markIlsRecordAsRestored(indexingProfile.getName(), restoredRecordId);
				Record currentMarcRecord = indexer.loadMarcRecordFromDatabase(indexingProfile.getName(), restoredRecordId, logEntry);
				if (currentMarcRecord != null) {
					String groupedWorkId = groupEvergreenRecord(currentMarcRecord);
					if (groupedWorkId != null) {
						//Reindex the record
						indexer.processGroupedWork(groupedWorkId);
						logEntry.incAdded();
						numRestored++;
						if (numRestored > 0 && numRestored % 250 == 0) {
							indexer.commitChanges();
							logEntry.saveResults();
						}
					}
				}
			}
			logEntry.addNote("Restored " + restoredIds.size() + " records");
			logEntry.saveResults();

			logEntry.addNote("Processing new ids");
			logEntry.saveResults();
			MarcFactory marcFactory = MarcFactory.newInstance();
			int numAdded = 0;
			for (String idToProcess : newIds) {
				updateBibFromEvergreen(idToProcess, marcFactory, true);
				numAdded++;
				if (numAdded >= 1000){
					logEntry.addNote("Only processing the first 1000 new ids to ensure performance");
					break;
				}
			}

			for (String deletedRecordId : deletedIds){
				RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), deletedRecordId);
				indexer.markIlsRecordAsDeleted(indexingProfile.getName(), deletedRecordId);
				if (result.reindexWork){
					indexer.processGroupedWork(result.permanentId);
				}else if (result.deleteWork){
					//Delete the work from solr and the database
					indexer.deleteRecord(result.permanentId);
				}
				logEntry.incDeleted();
			}
			reader.close();

			//After the file has been processed, delete it
			if (!idsFile.delete()){
				logEntry.incErrors("Could not delete ids file " + idsFile + " after processing.");
			}
		}catch (Exception e){
			logEntry.incErrors("Error reading IDs file " + idsFile, e);
		}
		return numUpdates;
	}

	private static int updateItemsUsingCsvFile(File[] exportedCsvFiles, long lastUpdateFromMarc, Connection dbConn) {
		int numUpdates = 0;
		HashSet<String> bibsToUpdateViaAPI = new HashSet<>();
		MarcFactory marcFactory = MarcFactory.newInstance();
		GroupedWorkIndexer indexer = getGroupedWorkIndexer();
		for(File csvFile : exportedCsvFiles){
			try {
				@SuppressWarnings("deprecation")
				CSVReader reader = new CSVReader(new FileReader(csvFile), '|');
				String[] rowData = reader.readNext();
				while (rowData != null){
					//Currently, columns are: copy id, status, bib id, copy location/current location, deleted, barcode
					//We will pull the full bib from super cat to get current status.
					if (rowData.length >= 6) {
						String bibNumber = rowData[2];
						Record currentMarcRecord = indexer.loadMarcRecordFromDatabase(indexingProfile.getName(), bibNumber, logEntry);

						boolean updateWithAPI = true;
						if (currentMarcRecord != null) {
							//Find the proper item record for this
							List<DataField> itemFields = MarcUtil.getDataFields(currentMarcRecord, indexingProfile.getItemTagInt());
							for (DataField itemField : itemFields) {
								Subfield itemBarcode = itemField.getSubfield(indexingProfile.getBarcodeSubfield());
								if (itemBarcode != null) {
									String barcodeForRow = rowData[5];
									if (barcodeForRow.equals(itemBarcode.getData())) {
										updateWithAPI = false;
										if (rowData[3].equals("t")) {
											//This item has been deleted
											currentMarcRecord.removeVariableField(itemField);
										} else {
											//Update values
											//We ignore copy id since it does not normally export
											String status = rowData[1];
											Subfield statusSubfield = itemField.getSubfield(indexingProfile.getItemStatusSubfield());
											if (status == null) {
												if (statusSubfield != null) {
													itemField.removeSubfield(statusSubfield);
												}
											} else {
												if (statusSubfield == null) {
													statusSubfield = marcFactory.newSubfield(indexingProfile.getItemStatusSubfield(), status);
													itemField.addSubfield(statusSubfield);
												} else {
													statusSubfield.setData(status);
												}
											}

											String shelvingLocation = rowData[3];
											Subfield shelvingLocationSubfield = itemField.getSubfield(indexingProfile.getShelvingLocationSubfield());
											if (shelvingLocation == null) {
												if (shelvingLocationSubfield != null) {
													itemField.removeSubfield(shelvingLocationSubfield);
												}
											} else {
												if (shelvingLocationSubfield == null) {
													shelvingLocationSubfield = marcFactory.newSubfield(indexingProfile.getShelvingLocationSubfield(), shelvingLocation);
													itemField.addSubfield(shelvingLocationSubfield);
												} else {
													shelvingLocationSubfield.setData(shelvingLocation);
												}
											}
										}
										//Once we process, we can skip the rest of the items
										break;
									}
								}
							}
						}

						if (updateWithAPI) {
							bibsToUpdateViaAPI.add(bibNumber);
						}else{
							//mark the item for reindexing
							GroupedWorkIndexer.MarcStatus saveMarcResult = indexer.saveMarcRecordToDatabase(indexingProfile, bibNumber, currentMarcRecord);
							if (saveMarcResult == GroupedWorkIndexer.MarcStatus.CHANGED) {
								logEntry.incUpdated();
							} else if (saveMarcResult == GroupedWorkIndexer.MarcStatus.NEW) {
								logEntry.incAdded();
							} else {
								//No change has been made, we could skip this
								if (!indexingProfile.isRunFullUpdate()) {
									//TODO: Actually skip re-processing the record?
									rowData = reader.readNext();
									logEntry.incSkipped();
									continue;
								}
							}

							//Regroup the record
							String groupedWorkId = groupEvergreenRecord(currentMarcRecord);
							if (groupedWorkId != null) {
								//Reindex the record
								indexer.processGroupedWork(groupedWorkId);
							}
						}
					}
					rowData = reader.readNext();
				}
				reader.close();
				//delete the file after it has been processed.
				csvFile.delete();
			}catch (Exception e){
				logEntry.incErrors("Error reading CSV file " + csvFile, e);
			}
		}
		logEntry.addNote("Processing " + bibsToUpdateViaAPI.size() + " bibs that were marked as changed in the CSV files");
		logEntry.saveResults();
		for (String bibToUpdate : bibsToUpdateViaAPI){
			numUpdates += updateBibFromEvergreen(bibToUpdate, marcFactory, true);
		}
		logEntry.addNote("Finished processing bibs from CSV");
		logEntry.saveResults();
		return numUpdates;
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
		MarcRecordGrouper recordGroupingProcessor = getRecordGroupingProcessor();
		if (!recordGroupingProcessor.isValid()) {
			logEntry.incErrors("Record Grouping Processor was not valid");
			return totalChanges;
		} else if (!recordGroupingProcessor.loadExistingTitles(logEntry)) {
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
		if (hasFullExportFile) {
			logEntry.addNote("Validating that full export is the correct size");
			logEntry.saveResults();

			int numRecordsRead = 0;
			int numRecordsWithErrors = 0;
			String lastRecordProcessed = "";
			try {
				FileInputStream marcFileStream = new FileInputStream(fullExportFile);
				MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, indexingProfile.getMarcEncoding());
				while (catalogReader.hasNext()) {
					numRecordsRead++;
					Record curBib = null;
					try {
						curBib = catalogReader.next();
					} catch (Exception e) {
						numRecordsWithErrors++;
					}
					if (curBib != null) {
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
				}
			} catch (Exception e) {
				logEntry.incErrors("Error loading Evergreen bibs on record " + numRecordsRead + " in profile " + indexingProfile.getName() + " the last record processed was " + lastRecordProcessed + " file " + fullExportFile.getAbsolutePath(), e);
				logEntry.addNote("Not processing MARC export due to error reading MARC files.");
				return totalChanges;
			}
			if (((float) numRecordsWithErrors / (float) numRecordsRead) > 0.0001) {
				logEntry.incErrors("More than .1% of records had errors, skipping due to the volume of errors in " + indexingProfile.getName() + " file " + fullExportFile.getAbsolutePath());
				return totalChanges;
			} else if (numRecordsWithErrors > 0) {
				logEntry.addNote("There were " + numRecordsWithErrors + " in " + fullExportFile.getAbsolutePath() + " but still processing");
				logEntry.saveResults();
			}
			logEntry.addNote("Full export " + fullExportFile + " contains " + numRecordsRead + " records.");
			logEntry.saveResults();

			if (maxIdInExport < indexingProfile.getFullMarcExportRecordIdThreshold()) {
				logEntry.incErrors("Full MARC export appears to be truncated, MAX Record ID in the export was " + maxIdInExport + " expected to be greater than or equal to " + indexingProfile.getFullMarcExportRecordIdThreshold());
				logEntry.addNote("Not processing the full export");
				exportedMarcFiles.remove(fullExportFile);
				hasFullExportFile = false;
			} else {
				logEntry.addNote("The full export is the correct size.");
				logEntry.saveResults();
			}
		}

		GroupedWorkIndexer indexer = getGroupedWorkIndexer();
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
				//Evergreen handles bib records with a large number of items by breaking the MARC export into multiple records. The records are always sequential.
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
							lastRecordProcessed = recordIdentifier.getIdentifier();
						}else {
							RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, indexingProfile);
							lastRecordProcessed = recordIdentifier.getIdentifier();
							boolean deleteRecord = false;
							if (recordIdentifier == null) {
								//logger.debug("Record with control number " + curBib.getControlNumber() + " was suppressed or is eContent");
								String controlNumber = curBib.getControlNumber();
								if (controlNumber == null) {
									logger.warn("Bib did not have control number or identifier");
								}
							} else if (!recordIdentifier.isSuppressed()) {
								lastRecordProcessed = recordIdentifier.getIdentifier();
								String recordNumber = recordIdentifier.getIdentifier();
								GroupedWorkIndexer.MarcStatus marcStatus;
								if (lastIdentifier != null && lastIdentifier.equals(recordIdentifier)) {
									marcStatus = indexer.appendItemsToExistingRecord(indexingProfile, curBib, recordNumber);
								} else {
									marcStatus = indexer.saveMarcRecordToDatabase(indexingProfile, recordNumber, curBib);
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
										indexer.processGroupedWork(permanentId);
										totalChanges++;
									}
								} else {
									logEntry.incSkipped();
									lastRecordProcessed = recordIdentifier.getIdentifier();
								}
								if (totalChanges > 0 && totalChanges % 5000 == 0) {
									indexer.commitChanges();
								}
								//Mark that the record was processed
								recordGroupingProcessor.removeExistingRecord(recordIdentifier.getIdentifier());
							} else {
								//Delete the record since it is suppressed
								deleteRecord = true;
							}
							indexingProfile.setLastChangeProcessed(numRecordsRead);
							if (deleteRecord) {
								RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), recordIdentifier.getIdentifier());
								if (result.reindexWork) {
									indexer.processGroupedWork(result.permanentId);
								} else if (result.deleteWork) {
									//Delete the work from solr and the database
									indexer.deleteRecord(result.permanentId);
								}
								logEntry.incDeleted();
								totalChanges++;
							}
						}
					}catch (MarcException me){
						logEntry.incRecordsWithInvalidMarc("Error processing record index " + numRecordsRead + " of " + curBibFile.getAbsolutePath() + " the last record processed was " + lastRecordProcessed + " trying to continue" + me);
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
				//After the file has been processed, delete it
				if (!curBibFile.delete()){
					logEntry.incErrors("Could not delete " + curBibFile);
				}
			} catch (Exception e) {
				logEntry.incErrors("Error loading Evergreen bibs on record " + numRecordsRead + " in profile " + indexingProfile.getName() + " the last record processed was " + lastRecordProcessed + " file " + curBibFile.getAbsolutePath(), e);
				//Since we had errors, rename it with a .err extension
				if (!curBibFile.renameTo(new File(curBibFile.toString() + ".err"))){
					logEntry.incErrors("Could not rename file to error file "+ curBibFile.toString() + ".err");
				}
			}


		}

		//Loop through remaining records and delete them
		if (hasFullExportFile) {
			logEntry.addNote("Deleting " + recordGroupingProcessor.getExistingRecords().size() + " records that were not contained in the export");
			for (String identifier : recordGroupingProcessor.getExistingRecords().keySet()) {
				RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), identifier);
				if (result.reindexWork){
					indexer.processGroupedWork(result.permanentId);
				}else if (result.deleteWork){
					//Delete the work from solr and the database
					indexer.deleteRecord(result.permanentId);
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
			updateMarcExportStmt.setLong(1, startTimeForLogging);
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

	private static int updateBibFromEvergreen(String bibNumber, MarcFactory marcFactory, boolean incrementProductsInLog) {
		//Get the bib record
		//noinspection SpellCheckingInspection
		String getBibUrl = baseUrl + "/opac/extras/supercat/retrieve/marcxml-full/record/" + bibNumber;
		ProcessBibRequestResponse response = processGetBibsRequest(getBibUrl, marcFactory, incrementProductsInLog);
		return response.numChanges;
	}

	private static int updateBibsFromEvergreen(long lastExtractTime, boolean incrementProductsInLog) throws UnsupportedEncodingException {
		int numChanges = 0;

		MarcFactory marcFactory = MarcFactory.newInstance();

		String getBibUrl = baseUrl + "/opac/extras/feed/freshmeat/marcxml-full/biblio/import/50";
		ProcessBibRequestResponse response = processGetBibsRequest(getBibUrl, marcFactory, true);

		return numChanges;
	}

	private static ProcessBibRequestResponse processGetBibsRequest(String getBibsRequestUrl, MarcFactory marcFactory, boolean incrementProductsInLog) {
		if (incrementProductsInLog) {
			logEntry.incProducts();
		}

		ProcessBibRequestResponse response = new ProcessBibRequestResponse();
		if (marcFactory == null) {
			marcFactory = MarcFactory.newInstance();
		}
		SimpleDateFormat evergreenDateParser = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");

		int numTries = 0;
		boolean successfulResponse = false;
		while (numTries < 3 && !successfulResponse) {
			numTries++;
			WebServiceResponse getBibsResponse = callEvergreenAPI(getBibsRequestUrl, null, "GET", "text/xml");
			if (getBibsResponse.isSuccess()) {
				try {
					successfulResponse = true;

					Document getBibsDocument = createXMLDocumentForWebServiceResponse(getBibsResponse);
					Element collectionsResult = (Element) getBibsDocument.getFirstChild();

					NodeList recordNotes = collectionsResult.getElementsByTagName("record");
					for (int i = 0; i < recordNotes.getLength(); i++){
						Record marcRecord = marcFactory.newRecord();

						Node curRecordNode = recordNotes.item(i);
						for (int j = 0; j < curRecordNode.getChildNodes().getLength(); j++){
							Node curChild = curRecordNode.getChildNodes().item(j);
							if (curChild instanceof Element){
								Element curElement = (Element)curChild;
								switch (curElement.getTagName()) {
									case "leader":
										String leader = curElement.getTextContent();
										marcRecord.setLeader(marcFactory.newLeader(leader));
										break;
									case "controlfield": {
										String tag = curElement.getAttribute("tag");
										String value = curElement.getTextContent();
										marcRecord.addVariableField(marcFactory.newControlField(tag, value));
										break;
									}
									case "datafield": {
										String tag = curElement.getAttribute("tag");
										String ind1 = curElement.getAttribute("ind1");
										String ind2 = curElement.getAttribute("ind2");
										DataField curField = marcFactory.newDataField(tag, ind1.charAt(0), ind2.charAt(0));
										for (int k = 0; k < curElement.getChildNodes().getLength(); k++) {
											Node curChild2 = curElement.getChildNodes().item(k);
											if (curChild2 instanceof Element) {
												Element curElement2 = (Element) curChild2;
												if (curElement2.getTagName().equals("subfield")) {
													String code = curElement2.getAttribute("code");
													String data = curElement2.getTextContent();
													Subfield curSubField = marcFactory.newSubfield(code.charAt(0), data);
													curField.addSubfield(curSubField);
												}
											}
										}
										marcRecord.addVariableField(curField);
										break;
									}
									case "holdings":
										NodeList volumesList = curElement.getElementsByTagName("volumes");
										for (int n = 0; n < volumesList.getLength(); n++) {
											Element curVolumeListElement = (Element)volumesList.item(n);
											//Get all the volumes
											NodeList volumes = curVolumeListElement.getElementsByTagName("volume");
											for (int k = 0; k < volumes.getLength(); k++) {
												Element curVolume = (Element) volumes.item(k);
												//Get the call number
												String callNumber = curVolume.getAttribute("label");

												//Get all the copies within each volume
												NodeList copies = curVolume.getElementsByTagName("copies");
												if (copies.getLength() > 0) {
													Element copiesElement = (Element) copies.item(0);
													NodeList copyList = copiesElement.getElementsByTagName("copy");
													for (int l = 0; l < copyList.getLength(); l++) {
														Element curCopy = (Element) copyList.item(l);
														String deleted = curCopy.getAttribute("deleted");
														if (deleted.equals("t")) {
															continue;
														}
														DataField curItemField = marcFactory.newDataField(indexingProfile.getItemTag(), ' ', ' ');

														//item id is not part of the regular MARC export, ignore for now.
														//String itemId = curCopy.getAttribute("id");
														//itemId = itemId.substring(itemId.lastIndexOf('/') + 1, itemId.length());
														//curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemRecordNumberSubfield(), itemId));

														//Created date is not part of regular mar export, ignore for now
														//String createDate = curCopy.getAttribute("create_date");
														//createDate = createDate.substring(0, createDate.indexOf("T"));
														//curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDateCreatedSubfield(), createDate));
														//String holdable = curCopy.getAttribute("holdable");
														//TODO: Figure out where the holdable flag should go
														//TODO: Do we need to load circulate, ref, or deposit flags?
														String barcode = curCopy.getAttribute("barcode");
														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getBarcodeSubfield(), barcode));
														String itemType = curCopy.getAttribute("circ_modifier");
														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getITypeSubfield(), itemType));

														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getCallNumberSubfield(), callNumber));

														String price = curCopy.getAttribute("price");
														curItemField.addSubfield(marcFactory.newSubfield('y', price));

														String copyNumber = curCopy.getAttribute("copy_number");
														curItemField.addSubfield(marcFactory.newSubfield('t', copyNumber));

														for (int m = 0; m < curCopy.getChildNodes().getLength(); m++) {
															Node curCopySubNode = curCopy.getChildNodes().item(m);
															if (curCopySubNode instanceof Element) {
																Element curCopySubElement = (Element) curCopySubNode;
																switch (curCopySubElement.getTagName()) {
																	case "status":
																		String statusCode = curCopySubElement.getTextContent();
																		curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemStatusSubfield(), statusCode));
																		break;
																	case "location":
																		String shelfLocation = curCopySubElement.getTextContent();
																		curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getShelvingLocationSubfield(), shelfLocation));
																		break;
																	case "circ_lib":
																		String locationCode = curCopySubElement.getAttribute("shortname");
																		curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getLocationSubfield(), locationCode));
																		break;
																}
															}
														}

														marcRecord.addVariableField(curItemField);
													}
												}
											}
										}
										break;
									case "subscriptions":
										//TODO: This may not be needed
										break;
								}
							}
						}

						//Save the MARC record
						RecordIdentifier bibliographicRecordId = getRecordGroupingProcessor().getPrimaryIdentifierFromMarcRecord(marcRecord, indexingProfile);
						if (bibliographicRecordId != null) {

							GroupedWorkIndexer.MarcStatus saveMarcResult = getGroupedWorkIndexer().saveMarcRecordToDatabase(indexingProfile, bibliographicRecordId.getIdentifier(), marcRecord);
							if (saveMarcResult == GroupedWorkIndexer.MarcStatus.CHANGED) {
								logEntry.incUpdated();
							} else if (saveMarcResult == GroupedWorkIndexer.MarcStatus.NEW) {
								logEntry.incAdded();
							} else {
								//No change has been made, we could skip this
								if (!indexingProfile.isRunFullUpdate()) {
									//TODO: Actually skip re-processing the record?
									logEntry.incSkipped();
								}
							}

							//Regroup the record
							String groupedWorkId = groupEvergreenRecord(marcRecord);
							if (groupedWorkId != null) {
								//Reindex the record
								getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
							}
						}
						if (logEntry.getNumProducts() > 0 && logEntry.getNumProducts() % 250 == 0) {
							getGroupedWorkIndexer().commitChanges();
							logEntry.saveResults();
						}
						response.numChanges++;
					}
				} catch (Exception e) {
					logEntry.incErrors("Unable to parse document for get bibs response " + getBibsRequestUrl, e);
					response.doneLoading = true;
				}
			} else {
				if (numTries == 3) {
					logEntry.incErrors("Could not get bibs from " + getBibsRequestUrl + " " + getBibsResponse.getResponseCode() + " " + getBibsResponse.getMessage());
					response.doneLoading = true;
				}
			}
		}

		return response;
	}

	private static WebServiceResponse callEvergreenAPI(String url, String postData, String method, String contentType){
		HashMap<String, String> headers = new HashMap<>();
		headers.put("Content-type", contentType);
		headers.put("Accept", contentType);

		if (method.equals("GET")) {
			return NetworkUtils.getURL(url, logger, headers);
		}else{
			return NetworkUtils.postToURL(url, postData, contentType, null, logger, null, 10000, 60000, StandardCharsets.UTF_8, headers);
		}
	}

	private static class ProcessBibRequestResponse{
		String lastId;
		boolean doneLoading = false;
		int numChanges = 0;
	}

	private static Document createXMLDocumentForWebServiceResponse(WebServiceResponse response) throws ParserConfigurationException, IOException, SAXException {
		DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
		dbFactory.setValidating(false);
		dbFactory.setIgnoringElementContentWhitespace(true);

		DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();

		byte[] soapResponseByteArray = response.getMessage().getBytes(StandardCharsets.UTF_8);
		ByteArrayInputStream soapResponseByteArrayInputStream = new ByteArrayInputStream(soapResponseByteArray);
		InputSource soapResponseInputSource = new InputSource(soapResponseByteArrayInputStream);

		Document doc = dBuilder.parse(soapResponseInputSource);
		doc.getDocumentElement().normalize();

		return doc;
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

	private static HashMap<String, String> getExistingTranslationMapValues(PreparedStatement getExistingValuesForMapStmt, Long translationMapId) throws SQLException {
		HashMap<String, String> existingValues = new HashMap<>();
		getExistingValuesForMapStmt.setLong(1, translationMapId);
		ResultSet getExistingValuesForMapRS = getExistingValuesForMapStmt.executeQuery();
		while (getExistingValuesForMapRS.next()) {
			existingValues.put(getExistingValuesForMapRS.getString("value").toLowerCase(), getExistingValuesForMapRS.getString("translation"));
		}
		return existingValues;
	}

	private static void updateTranslationMap(String value, String translation, PreparedStatement insertTranslationStmt, Long translationMapId, HashMap<String, String> existingValues) throws SQLException {
		if (existingValues.containsKey(value.toLowerCase())) {
			if (translation == null){
				translation = "";
			}else {
				translation = translation.trim();
			}
			if (!existingValues.get(value.toLowerCase()).equals(translation)) {
				logger.warn("Translation for " + value + " has changed from " + existingValues.get(value) + " to " + translation);
			}
		} else {
			if (translation == null) {
				translation = value;
			}
			if (value.length() > 0) {
				try {
					insertTranslationStmt.setLong(1, translationMapId);
					insertTranslationStmt.setString(2, value);
					insertTranslationStmt.setString(3, translation);
					insertTranslationStmt.executeUpdate();
				}catch (SQLException e){
					logEntry.addNote("Error adding translation map value " + value + " with a translation of " + translation + " e");
				}
			}
		}
	}
}
