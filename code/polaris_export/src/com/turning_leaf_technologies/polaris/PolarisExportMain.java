package com.turning_leaf_technologies.polaris;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import org.aspen_discovery.grouping.MarcRecordGrouper;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.VolumeInfo;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.commons.net.util.Base64;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.marc4j.MarcXmlReader;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.Date;
import java.util.*;
import java.util.concurrent.Executors;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

//TODO: Load dates closed

public class PolarisExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;

	private static IlsExtractLogEntry logEntry;
	private static long accountProfileId;
	private static String webServiceUrl;
	private static String clientId;
	private static String clientSecret;
	private static String domain;
	private static String staffUsername;
	private static String staffPassword;
	private static String accessToken;
	private static String accessSecret;
	private static PreparedStatement addIlsHoldSummary;
	private static PreparedStatement getExistingVolumesStmt;
	private static PreparedStatement addVolumeStmt;
	private static PreparedStatement deleteAllVolumesStmt;
	private static PreparedStatement deleteVolumeStmt;
	private static PreparedStatement updateVolumeStmt;
	private static PreparedStatement getBibIdForItemIdStmt;
	private static PreparedStatement getRecordIdForItemIdStmt;
	private static PreparedStatement sourceForPolarisStmt;

	private static Set<String> bibIdsUpdatedDuringContinuous;
	private static Set<Long> itemIdsUpdatedDuringContinuous;

	private static String singleWorkId = null;

	private static int polarisMajorVersion;
	private static int polarisMinorVersion;

	public static void main(String[] args) {
		boolean extractSingleWork = false;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String extractSingleWorkResponse = AspenStringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleWork = true;
			}
		} else {
			serverName = args[0];
			if (args.length > 1) {
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord")) {
					extractSingleWork = true;
					if (args.length > 2) {
						singleWorkId = args[2];
					}
				}
			}
		}
		if (extractSingleWork && singleWorkId == null) {
			singleWorkId = AspenStringUtils.getInputFromCommandLine("Enter the id of the title to extract");
		}
		String profileToLoad = "ils";

		String processName = "polaris_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			java.util.Date startTime = new Date();
			logger.info(startTime + ": Starting Polaris Extract");
			long startTimeForLogging = startTime.getTime() / 1000;

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
				PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = 'polaris'");
				ResultSet accountProfileRS = accountProfileStmt.executeQuery();
				if (accountProfileRS.next()){
					domain = accountProfileRS.getString("domain");
					staffUsername = accountProfileRS.getString( "staffUsername");
					staffPassword = accountProfileRS.getString( "staffPassword");
					profileToLoad = accountProfileRS.getString("recordSource");
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
				SystemUtils.quitIfOffline(dbConn, logger, logEntry);

				if (loadAccountProfile(dbConn)){
					indexingProfile = IndexingProfile.loadIndexingProfile(serverName, dbConn, profileToLoad, logger, logEntry);
					logEntry.setIsFullUpdate(indexingProfile.isRunFullUpdate());

					WebServiceResponse authenticationResponse = authenticateStaffUser();
					if (authenticationResponse.isSuccess()) {
						loadPolarisVersion();
						if (!extractSingleWork) {
							importLibraryBranchData(dbConn);
							updateSublocationInfo(dbConn);
							updatePatronCodes(dbConn);
							updateTranslationMaps(dbConn);

							//Get a list of all active bibs, so we can see what needs to be deleted.
							//This is an expensive process, so we will only do it
							SimpleDateFormat f = new SimpleDateFormat("HH");
							Date now = new Date();
							String curHour = f.format(now);
							if (curHour.equals("21")) {
								checkForDeletedBibsInPolaris();
							}
						}

						//Update works that have changed since the last index
						numChanges = updateRecords(singleWorkId);
					}else{
						logEntry.incErrors("Could not authenticate " + authenticationResponse.getMessage());
					}
				}else{
					logEntry.incErrors("Could not load Account Profile");
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
				logger.info(currentTime + ": Finished Polaris Extract");
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
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase();
				break;
			}
			//Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)){
				logger.info("Ending because we have low memory available");
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

		System.exit(0);
	}

	private static void loadPolarisVersion() {
		String getApiInfoUrl = "/PAPIService/REST/public/v1/1033/100/1/api";
		WebServiceResponse apiInfoResponse = callPolarisAPI(getApiInfoUrl, null, "GET", "application/json", null);
		if (apiInfoResponse.isSuccess()) {
			JSONObject apiInfo = apiInfoResponse.getJSONResponse();
			polarisMajorVersion = apiInfo.getInt("Major");
			polarisMinorVersion = apiInfo.getInt("Minor");

			logEntry.addNote("Polaris version is " + polarisMajorVersion + "." + polarisMinorVersion);
		}
	}

	private static boolean use7_4DateFormatFunctionality() {
		if (polarisMajorVersion >= 8) {
			return true;
		} else if (polarisMajorVersion == 7) {
			//noinspection RedundantIfStatement
			if (polarisMinorVersion >= 4) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private static void updateSublocationInfo(Connection dbConn) {
		try {
			//In Polaris, Sub locations can be connected to multiple locations so need to account for that when loading and saving
			logEntry.addNote("Loading Pickup Areas into Sub-locations");
			PreparedStatement existingAspenSublocationStmt = dbConn.prepareStatement("SELECT id, name, isValidHoldPickupAreaILS, weight from sublocation where locationId = ? AND ilsId = ?");
			PreparedStatement updateAspenSublocationStmt = dbConn.prepareStatement("UPDATE sublocation SET name = ?, isValidHoldPickupAreaILS = ?, weight = ? where id = ?");
			PreparedStatement addAspenSublocationStmt = dbConn.prepareStatement("INSERT INTO sublocation (locationId, name, ilsId, isValidHoldPickupAreaILS, isValidHoldPickupAreaAspen, weight) VALUES (?, ?, ?, ?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement activateSublocationForAllPTypesStmt = dbConn.prepareStatement("INSERT INTO sublocation_ptype (sublocationId, patronTypeId) SELECT ?, id from ptype");
			PreparedStatement getExistingAspenLocationsStmt = dbConn.prepareStatement("SELECT locationId, displayName, code from location", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet allExistingLocationsRS = getExistingAspenLocationsStmt.executeQuery();
			HashMap<String, ExistingLocation> existingLocations = new HashMap<>();
			while (allExistingLocationsRS.next()) {
				ExistingLocation existingLocation = new ExistingLocation();

				existingLocation.setLocationId(allExistingLocationsRS.getLong("locationId"));
				existingLocation.setName(allExistingLocationsRS.getString("displayName"));
				existingLocation.setCode(allExistingLocationsRS.getString("code"));
				existingLocations.put(existingLocation.getCode(), existingLocation);
			}

			//Lookup the sub-locations in Polaris. It does not look like filtering by orgID works in the API so we will just grab them all
			String getPickupAreasUrl = "/PAPIService/REST/public/v1/1033/100/1/pickupareas";
			WebServiceResponse pickupAreasResponse = callPolarisAPI(getPickupAreasUrl, null, "GET", "application/json", null);
			if (pickupAreasResponse.isSuccess()){
				JSONObject pickupAreas = pickupAreasResponse.getJSONResponse();
				JSONArray pickupAreaRows = pickupAreas.getJSONArray("PickupAreasRows");
				for (int i = 0; i < pickupAreaRows.length(); i++) {
					JSONObject pickupAreaInfo = pickupAreaRows.getJSONObject(i);
					String parentOrganizationId = Long.toString(pickupAreaInfo.getLong("OrganizationID"));
					long pickupAreaId = pickupAreaInfo.getLong("PickupAreaID");
					String name = pickupAreaInfo.getString("Description");
					long sequence = pickupAreaInfo.getLong("SequenceID");
					boolean selected = pickupAreaInfo.getBoolean("Selected");

					//Get the parent location for the sublocation
					ExistingLocation parentLocation = existingLocations.get(parentOrganizationId);
					if (parentLocation != null) {
						//See if we have an existing sublocation in Aspen
						existingAspenSublocationStmt.setLong(1, parentLocation.getLocationId());
						existingAspenSublocationStmt.setLong(2, pickupAreaId);
						ResultSet existingAspenSublocationRS = existingAspenSublocationStmt.executeQuery();
						if (existingAspenSublocationRS.next()) {
							//We have an existing sublocation, make sure the name, sequence, and selection has not changed
							long existingId = existingAspenSublocationRS.getLong("id");
							String existingName = existingAspenSublocationRS.getString("name");
							boolean existingSelected = existingAspenSublocationRS.getBoolean("isValidHoldPickupAreaILS");
							long existingWeight = existingAspenSublocationRS.getLong("weight");
							if (!existingName.equals(name)
								|| existingSelected != selected
								|| existingWeight != sequence){
								updateAspenSublocationStmt.setString(1, name);
								updateAspenSublocationStmt.setInt(2, selected ? 1 : 0);
								updateAspenSublocationStmt.setLong(3, sequence);
								updateAspenSublocationStmt.setLong(4, existingId);
								updateAspenSublocationStmt.executeUpdate();
							}
						}else{
							//This is a new sublocation
							addAspenSublocationStmt.setLong(1, parentLocation.getLocationId());
							addAspenSublocationStmt.setString(2, name);
							addAspenSublocationStmt.setLong(3, pickupAreaId);
							addAspenSublocationStmt.setInt(4, selected ? 1 : 0);
							addAspenSublocationStmt.setInt(5, selected ? 1 : 0);
							addAspenSublocationStmt.setLong(6, sequence);
							addAspenSublocationStmt.executeUpdate();

							ResultSet addAspenSublocationRS = addAspenSublocationStmt.getGeneratedKeys();
							if (addAspenSublocationRS.next()){
								if (selected) {
									long sublocationId = addAspenSublocationRS.getLong(1);
									activateSublocationForAllPTypesStmt.setLong(1, sublocationId);
									activateSublocationForAllPTypesStmt.executeUpdate();
								}
							}
						}
						existingAspenSublocationRS.close();
					}
				}
			}
			allExistingLocationsRS.close();
			logEntry.addNote("Finished Loading Pickup Areas into Sub-locations");

		} catch (Exception e) {
			logEntry.incErrors("Error updating sublocation information from Polaris", e);
		}
	}

	private static void importLibraryBranchData(Connection dbConn) {
		try{
			PreparedStatement existingAspenLocationStmt = dbConn.prepareStatement("SELECT libraryId, locationId, isMainBranch from location where code = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement existingAspenLibraryStmt = dbConn.prepareStatement("SELECT libraryId from library where ilsCode = ? and accountProfileId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement existingAspenLibraryBySubdomainStmt = dbConn.prepareStatement("SELECT libraryId from library where subdomain = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement addAspenLibraryStmt = dbConn.prepareStatement("INSERT INTO library (accountProfileId, subdomain, displayName, ilsCode, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, ?, 1, 1)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement addAspenLocationStmt = dbConn.prepareStatement("INSERT INTO location (libraryId, displayName, code, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, -1, -1)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement addAspenLocationRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO location_records_to_include (locationId, indexingProfileId, location, subLocation, markRecordsAsOwned) VALUES (?, ?, ?, '', 1)");
			PreparedStatement addAspenLocationRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO location_records_to_include (locationId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
			PreparedStatement addAspenLibraryRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO library_records_to_include (libraryId, indexingProfileId, location, subLocation, markRecordsAsOwned) VALUES (?, ?, ?, '', 1) ON DUPLICATE KEY UPDATE location = CONCAT(location, '|', VALUES(location))");
			PreparedStatement addAspenLibraryRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO library_records_to_include (libraryId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
			PreparedStatement createTranslationMapStmt = dbConn.prepareStatement("INSERT INTO translation_maps (name, indexingProfileId) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement getTranslationMapStmt = dbConn.prepareStatement("SELECT id from translation_maps WHERE name = ? and indexingProfileId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getExistingValuesForMapStmt = dbConn.prepareStatement("SELECT * from translation_map_values where translationMapId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement insertTranslationStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");

			Long locationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "location");
			HashMap<String, String> existingLocationMapValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, locationMapId);

			//Get a list of all libraries
			String getOrganizationsUrl = "/PAPIService/REST/public/v1/1033/100/1/organizations/all";
			WebServiceResponse organizationsResponse = callPolarisAPI(getOrganizationsUrl, null, "GET", "application/json", null);
			if (organizationsResponse.isSuccess()) {
				JSONObject organizations = organizationsResponse.getJSONResponse();
				JSONArray organizationRows = organizations.getJSONArray("OrganizationsGetRows");
				for (int i = 0; i < organizationRows.length(); i++) {
					JSONObject organizationInfo = organizationRows.getJSONObject(i);
					long ilsId = organizationInfo.getLong("OrganizationID");
					String libraryDisplayName = organizationInfo.getString("DisplayName");
					String abbreviation = organizationInfo.getString("Abbreviation");
					int organizationCodeId = organizationInfo.getInt("OrganizationCodeID");
					if (organizationCodeId == 2) {
						existingAspenLibraryStmt.setLong(1, ilsId);
						existingAspenLibraryStmt.setLong(2, accountProfileId);
						ResultSet existingLibraryRS = existingAspenLibraryStmt.executeQuery();
						long libraryId = 0;
						if (!existingLibraryRS.next()) {
							// Check if subdomain already exists.
							existingAspenLibraryBySubdomainStmt.setString(1, abbreviation);
							ResultSet existingSubdomainRS = existingAspenLibraryBySubdomainStmt.executeQuery();
							if (existingSubdomainRS.next()) {
								logEntry.addNote("Skipping library " + libraryDisplayName + " with ID " + ilsId + " because subdomain '" + abbreviation + "' is already in use.");
							} else {
								addAspenLibraryStmt.setLong(1, accountProfileId);
								addAspenLibraryStmt.setString(2, abbreviation);
								addAspenLibraryStmt.setString(3, libraryDisplayName);
								addAspenLibraryStmt.setLong(4, ilsId);
								addAspenLibraryStmt.executeUpdate();
								ResultSet addAspenLibraryRS = addAspenLibraryStmt.getGeneratedKeys();
								if (addAspenLibraryRS.next()){
									libraryId = addAspenLibraryRS.getLong(1);
								}

								//Add records to include for the library.
								addAspenLibraryRecordsToIncludeStmt.setLong(1, libraryId);
								addAspenLibraryRecordsToIncludeStmt.setLong(2, indexingProfile.getId());
								addAspenLibraryRecordsToIncludeStmt.executeUpdate();
							}
						}
					} else if (organizationCodeId == 3) {
						long parentOrganizationId = organizationInfo.getLong("ParentOrganizationID");
						existingAspenLocationStmt.setLong(1, ilsId);
						ResultSet existingLocationRS = existingAspenLocationStmt.executeQuery();
						if (!existingLocationRS.next()){
							//This location has not been added before
							//Get the library id for the parent
							existingAspenLibraryStmt.setLong(1, parentOrganizationId);
							ResultSet existingLibraryRS = existingAspenLibraryStmt.executeQuery();
							if (existingLibraryRS.next()){
								//We found a valid library for the location
								long libraryId = existingLibraryRS.getLong("libraryId");

								addAspenLocationStmt.setLong(1, libraryId);
								addAspenLocationStmt.setString(2, AspenStringUtils.trimTo(60, libraryDisplayName));
								addAspenLocationStmt.setLong(3, ilsId);

								addAspenLocationStmt.executeUpdate();
								ResultSet addAspenLocationRS = addAspenLocationStmt.getGeneratedKeys();
								if (addAspenLocationRS.next()){
									long locationId = addAspenLocationRS.getLong(1);
									//Add records owned for the location
									addAspenLocationRecordsOwnedStmt.setLong(1, locationId);
									addAspenLocationRecordsOwnedStmt.setLong(2, indexingProfile.getId());
									addAspenLocationRecordsOwnedStmt.setLong(3, ilsId);
									addAspenLocationRecordsOwnedStmt.executeUpdate();

									//Add records owned for the library, since we have multiple locations defined by ID, we will add separate rows for each.
									addAspenLibraryRecordsOwnedStmt.setLong(1, libraryId);
									addAspenLibraryRecordsOwnedStmt.setLong(2, indexingProfile.getId());
									addAspenLibraryRecordsOwnedStmt.setLong(3, ilsId);
									addAspenLibraryRecordsOwnedStmt.executeUpdate();

									//Add records to include for the location
									addAspenLocationRecordsToIncludeStmt.setLong(1, locationId);
									addAspenLocationRecordsToIncludeStmt.setLong(2, indexingProfile.getId());
									addAspenLocationRecordsToIncludeStmt.executeUpdate();
								}
							}
						}

						//Add to the location map
						if (!existingLocationMapValues.containsKey(Long.toString(ilsId))){
							if (!libraryDisplayName.isEmpty()){
								try {
									insertTranslationStmt.setLong(1, locationMapId);
									insertTranslationStmt.setLong(2, ilsId);
									insertTranslationStmt.setString(3, libraryDisplayName);
									insertTranslationStmt.executeUpdate();
								}catch (SQLException e){
									logEntry.addNote("Error adding location value " + ilsId + " with a translation of " + libraryDisplayName + " e");
								}
							}
						}
					}
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error updating branch information from Polaris", e);
		}
	}

	private static void updatePatronCodes(Connection dbConn){
		try{
			PreparedStatement existingPTypeStmt = dbConn.prepareStatement("SELECT * from ptype where accountProfileId = ?");
			existingPTypeStmt.setLong(1, accountProfileId);
			ResultSet existingPTypesRS = existingPTypeStmt.executeQuery();
			HashSet<Long> existingPTypes = new HashSet<>();
			while (existingPTypesRS.next()){
				existingPTypes.add(existingPTypesRS.getLong("pType"));
			}
			existingPTypesRS.close();
			existingPTypeStmt.close();
			PreparedStatement addPTypeStmt = dbConn.prepareStatement("INSERT INTO ptype (accountProfileId, pType, description) VALUES (?, ?, ?)");
			//Get a list of all libraries
			String getPatronCodesUrl = "/PAPIService/REST/public/v1/1033/100/1/patroncodes";
			WebServiceResponse patronCodesResponse = callPolarisAPI(getPatronCodesUrl, null, "GET", "application/json", null);
			if (patronCodesResponse.isSuccess()){
				JSONObject patronCodes = patronCodesResponse.getJSONResponse();
				JSONArray patronCodeRows = patronCodes.getJSONArray("PatronCodesRows");
				for (int i = 0; i < patronCodeRows.length(); i++){
					JSONObject curPatronType = patronCodeRows.getJSONObject(i);
					long patronCodeId = curPatronType.getLong("PatronCodeID");
					if (!existingPTypes.contains(patronCodeId)){
						addPTypeStmt.setLong(1, accountProfileId);
						addPTypeStmt.setLong(2, patronCodeId);
						addPTypeStmt.setString(3, curPatronType.getString("Description"));
						addPTypeStmt.executeUpdate();
					}
				}
			}
			addPTypeStmt.close();
		} catch (Exception e) {
			logEntry.incErrors("Error updating patron codes from Polaris", e);
		}
	}

	private static void updateTranslationMaps(Connection dbConn){
		try {
			PreparedStatement createTranslationMapStmt = dbConn.prepareStatement("INSERT INTO translation_maps (name, indexingProfileId) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement getTranslationMapStmt = dbConn.prepareStatement("SELECT id from translation_maps WHERE name = ? and indexingProfileId = ?");
			PreparedStatement getExistingValuesForMapStmt = dbConn.prepareStatement("SELECT * from translation_map_values where translationMapId = ?");
			PreparedStatement insertTranslationStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");

			//Get a list of all collections
			Long collectionMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "collection");
			HashMap<String, String> existingCollections = getExistingTranslationMapValues(getExistingValuesForMapStmt, collectionMapId);
			String getCollectionsUrl = "/PAPIService/REST/public/v1/1033/100/1/collections";
			WebServiceResponse collectionsResponse = callPolarisAPI(getCollectionsUrl, null, "GET", "application/json", null);
			if (collectionsResponse.isSuccess()){
				JSONObject collections = collectionsResponse.getJSONResponse();
				JSONArray collectionRows = collections.getJSONArray("CollectionsRows");
				for (int i = 0; i < collectionRows.length(); i++){
					JSONObject curCollection = collectionRows.getJSONObject(i);
					long collectionId = curCollection.getLong("ID");
					String collectionName = curCollection.getString("Name");
					if (!existingCollections.containsKey(Long.toString(collectionId))){
						if (!collectionName.isEmpty()){
							try {
								insertTranslationStmt.setLong(1, collectionMapId);
								insertTranslationStmt.setLong(2, collectionId);
								insertTranslationStmt.setString(3, collectionName);
								insertTranslationStmt.executeUpdate();
							}catch (SQLException e){
								logEntry.addNote("Error adding collection value " + collectionId + " with a translation of " + collectionName + " e");
							}
						}
					}
				}
			}

			Long shelfLocationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "shelf_location");
			HashMap<String, String> existingShelfLocations = getExistingTranslationMapValues(getExistingValuesForMapStmt, shelfLocationMapId);
			//noinspection SpellCheckingInspection
			String getShelfLocationsUrl = "/PAPIService/REST/public/v1/1033/100/1/shelflocations";
			WebServiceResponse shelfLocationsResponse = callPolarisAPI(getShelfLocationsUrl, null, "GET", "application/json", null);
			if (shelfLocationsResponse.isSuccess()){
				JSONObject shelfLocations = shelfLocationsResponse.getJSONResponse();
				JSONArray shelfLocationRows = shelfLocations.getJSONArray("ShelfLocationsRows");
				for (int i = 0; i < shelfLocationRows.length(); i++){
					JSONObject curShelfLocation = shelfLocationRows.getJSONObject(i);
					long shelfLocationId = curShelfLocation.getLong("ID");
					String shelfLocationName = curShelfLocation.getString("Description").trim();
					if (!existingShelfLocations.containsKey(Long.toString(shelfLocationId))){
						if (!shelfLocationName.isEmpty()){
							try {
								insertTranslationStmt.setLong(1, shelfLocationMapId);
								insertTranslationStmt.setLong(2, shelfLocationId);
								insertTranslationStmt.setString(3, shelfLocationName);
								insertTranslationStmt.executeUpdate();
								existingShelfLocations.put(Long.toString(shelfLocationId), shelfLocationName);
							}catch (SQLException e){
								logEntry.addNote("Error adding shelf location value " + shelfLocationId + " with a translation of " + shelfLocationName + " " + e);
							}
						}
					}
					//For shelf locations, we also get the text version so pull that too
					if (!shelfLocationName.isEmpty()){
						if (!existingShelfLocations.containsKey(shelfLocationName.toLowerCase())){
							try {
								insertTranslationStmt.setLong(1, shelfLocationMapId);
								insertTranslationStmt.setString(2, shelfLocationName.toLowerCase());
								insertTranslationStmt.setString(3, shelfLocationName);
								insertTranslationStmt.executeUpdate();
								existingShelfLocations.put(shelfLocationName, shelfLocationName);
							}catch (SQLException e){
								logEntry.addNote("Error adding shelf location value '" + shelfLocationName + "' with a translation of " + shelfLocationName + " "  + e);
							}
						}
					}
				}
			}

			Long iTypeMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "itype");
			HashMap<String, String> existingITypes = getExistingTranslationMapValues(getExistingValuesForMapStmt, iTypeMapId);
			String getMaterialTypesUrl = "/PAPIService/REST/public/v1/1033/100/1/materialtypes";
			WebServiceResponse materialTypesResponse = callPolarisAPI(getMaterialTypesUrl, null, "GET", "application/json", null);
			if (materialTypesResponse.isSuccess()){
				JSONObject materialTypes = materialTypesResponse.getJSONResponse();
				JSONArray materialTypeRows = materialTypes.getJSONArray("MaterialTypesRows");
				for (int i = 0; i < materialTypeRows.length(); i++){
					JSONObject curMaterialType = materialTypeRows.getJSONObject(i);
					long materialTypeId = curMaterialType.getLong("MaterialTypeID");
					String materialTypeName = curMaterialType.getString("Description").trim();
					if (!existingITypes.containsKey(Long.toString(materialTypeId))){
						if (!materialTypeName.isEmpty()){
							try {
								insertTranslationStmt.setLong(1, iTypeMapId);
								insertTranslationStmt.setLong(2, materialTypeId);
								insertTranslationStmt.setString(3, materialTypeName);
								insertTranslationStmt.executeUpdate();
								existingITypes.put(Long.toString(materialTypeId), materialTypeName);
							}catch (SQLException e){
								logEntry.addNote("Error adding iType value " + materialTypeId + " with a translation of " + materialTypeName + " e");
							}
						}
					}

					//For material types, we also get the text version so pull that too
					if (!materialTypeName.isEmpty()){
						if (!existingITypes.containsKey(materialTypeName.toLowerCase())){
							try {
								insertTranslationStmt.setLong(1, iTypeMapId);
								insertTranslationStmt.setString(2, materialTypeName.toLowerCase());
								insertTranslationStmt.setString(3, materialTypeName);
								insertTranslationStmt.executeUpdate();
								existingITypes.put(materialTypeName, materialTypeName);
							}catch (SQLException e){
								logEntry.addNote("Error adding iType value " + materialTypeName + " with a translation of " + materialTypeName + " e");
							}
						}
					}
				}
			}
		} catch (SQLException e) {
			logger.error("Error updating translation map information", e);
		}
	}

	private static HashMap<String, String> getExistingTranslationMapValues(PreparedStatement getExistingValuesForMapStmt, Long translationMapId) throws SQLException {
		HashMap<String, String> existingValues = new HashMap<>();
		getExistingValuesForMapStmt.setLong(1, translationMapId);
		ResultSet getExistingValuesForMapRS = getExistingValuesForMapStmt.executeQuery();
		while (getExistingValuesForMapRS.next()) {
			existingValues.put(getExistingValuesForMapRS.getString("value").toLowerCase().trim(), getExistingValuesForMapRS.getString("translation"));
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

	private static void processRecordsToReload(IndexingProfile indexingProfile, IlsExtractLogEntry logEntry) {
		try {
			PreparedStatement getNumRecordsToReloadStmt = dbConn.prepareStatement("SELECT count(*) from record_identifiers_to_reload WHERE processed = 0 and type='" + indexingProfile.getName() + "'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getNumRecordsToReloadRS = getNumRecordsToReloadStmt.executeQuery();
			if (getNumRecordsToReloadRS.next()){
				logEntry.addNote("Processing " + getNumRecordsToReloadRS.getLong(1) + " records to reload");
				logEntry.saveResults();
			}
			PreparedStatement getRecordsToReloadStmt = dbConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='" + indexingProfile.getName() + "'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = dbConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String recordIdentifier = getRecordsToReloadRS.getString("identifier");
				updateBibFromPolaris(recordIdentifier, null, 0, true);

				markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
				markRecordToReloadAsProcessedStmt.executeUpdate();
				numRecordsToReloadProcessed++;
			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Processed " + numRecordsToReloadProcessed + " records marked for reprocessing");
				logEntry.saveResults();
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload ", e);
		}
	}

	private static boolean loadAccountProfile(Connection dbConn) {
		//Get information about the account profile for Polaris
		try {
			PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = ?");
			accountProfileStmt.setString(1, "polaris");
			ResultSet accountProfileRS = accountProfileStmt.executeQuery();
			if (accountProfileRS.next()) {
				webServiceUrl = accountProfileRS.getString("patronApiUrl");
				if (webServiceUrl.endsWith("/")){
					webServiceUrl = webServiceUrl.substring(0, webServiceUrl.length() -1);
				}
				clientId = accountProfileRS.getString("oAuthClientId");
				clientSecret = accountProfileRS.getString("oAuthClientSecret");
				accountProfileId = accountProfileRS.getLong("id");
			} else {
				logger.error("Could not find an account profile for Polaris stopping");
				System.exit(1);
			}
			return true;
		} catch (Exception e){
			logEntry.incErrors("Could not load account profile " + "polaris" + e);
			return false;
		}
	}

	private static int updateRecords(String singleWorkId) {
		int totalChanges = 0;

		try {
			//Get the time the last extract was done
			logger.info("Starting to load changed records from Polaris using the APIs");

			addIlsHoldSummary = dbConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?) ON DUPLICATE KEY UPDATE numHolds = VALUES(numHolds)");
			getExistingVolumesStmt = dbConn.prepareStatement("SELECT id, volumeId from ils_volume_info where recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addVolumeStmt = dbConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems, displayOrder) VALUES (?,?,?,?,?)");
			updateVolumeStmt = dbConn.prepareStatement("UPDATE ils_volume_info SET displayLabel = ?, relatedItems = ?, displayOrder = ? WHERE id = ?");
			deleteAllVolumesStmt = dbConn.prepareStatement("DELETE from ils_volume_info where recordId = ?");
			deleteVolumeStmt = dbConn.prepareStatement("DELETE from ils_volume_info where id = ?");
			sourceForPolarisStmt = dbConn.prepareStatement("select id from indexed_record_source where source = ?");
			getBibIdForItemIdStmt = dbConn.prepareStatement("SELECT recordIdentifier from grouped_work_records WHERE grouped_work_records.id = ? and sourceId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getRecordIdForItemIdStmt = dbConn.prepareStatement("SELECT groupedWorkRecordId FROM grouped_work_record_items WHERE itemId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			if (singleWorkId != null){
				bibIdsUpdatedDuringContinuous = Collections.synchronizedSet(new HashSet<>());
				itemIdsUpdatedDuringContinuous = Collections.synchronizedSet(new HashSet<>());
				updateBibFromPolaris(singleWorkId, null, 0, true);
			}else {
				long lastExtractTime = 0;
				if (!indexingProfile.isRunFullUpdate()) {
					lastExtractTime = indexingProfile.getLastUpdateOfChangedRecords();
					if (lastExtractTime == 0 || (indexingProfile.getLastUpdateOfAllRecords() > indexingProfile.getLastUpdateOfChangedRecords())) {
						lastExtractTime = indexingProfile.getLastUpdateOfAllRecords();
					}
					//Give a buffer of 15 minutes to account for server time differences and allow Polaris to record changes
					//With one minute all changes were not captured.
					lastExtractTime = lastExtractTime - 15 * 60;
				} else {
					getRecordGroupingProcessor().loadExistingTitles(logEntry);
				}

				//Check to see if we should regroup all records
				if (indexingProfile.isRegroupAllRecords()){
					//Regrouping takes a long time, and we don't need koha DB connection so close it while we regroup
					MarcRecordGrouper recordGrouper = getRecordGroupingProcessor();
					recordGrouper.regroupAllRecords(dbConn, indexingProfile, getGroupedWorkIndexer(), logEntry);
				}

				//Update records
				boolean allowDeletingExistingRecords = indexingProfile.getLastChangeProcessed() == 0;
				if (!indexingProfile.isRunFullUpdate() && lastExtractTime != 0) {
					//Process incremental for updated records and deleted records
					totalChanges += updateBibsFromPolarisIncremental(lastExtractTime);
					totalChanges += extractDeletedBibs(lastExtractTime);
				} else {
					//Process full update for all records
					totalChanges += updateBibsFromPolarisFull(lastExtractTime);
					//Loop through remaining records and delete them
					if (allowDeletingExistingRecords) {
						GroupedWorkIndexer groupedWorkIndexer = getGroupedWorkIndexer();
						MarcRecordGrouper recordGroupingProcessor = getRecordGroupingProcessor();
						logEntry.addNote("Starting to delete " + recordGroupingProcessor.getExistingRecords().size() + " records that no longer exist");
						for (String ilsId : recordGroupingProcessor.getExistingRecords().keySet()) {
							RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), ilsId);
							if (result.permanentId != null) {
								if (result.reindexWork) {
									groupedWorkIndexer.processGroupedWork(result.permanentId);
								} else if (result.deleteWork) {
									//Delete the work from solr and the database
									groupedWorkIndexer.deleteRecord(result.permanentId, result.groupedWorkId);
								}
								logEntry.incDeleted();
								if (logEntry.getNumDeleted() % 250 == 0) {
									logEntry.saveResults();
								}
							}
						}
						logEntry.addNote("Finished deleting records that no longer exist");
						logEntry.saveResults();
					}else{
						logEntry.addNote("Skipping deleting records that no longer exist because we skipped some records at the start");
					}
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error loading changed records from Polaris APIs", e);
			//Don't quit since that keeps the exporter from running continuously
		}
		logger.info("Finished loading changed records from Polaris APIs");

		return totalChanges;
	}

	private static int extractDeletedBibs(long lastExtractTime) {
		int numChanges = 0;
		String lastId = "0";
		DateTimeFormatter dateFormatter;
		if (use7_4DateFormatFunctionality()) {
			dateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		} else {
			dateFormatter = DateTimeFormatter.ofPattern("MM/dd/yyyy HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		}
		String deleteDate = dateFormatter.format(Instant.ofEpochSecond(lastExtractTime));
		logEntry.addNote("Checking for deleted records since " + deleteDate);
		boolean doneLoading = false;
		while (!doneLoading) {

			//noinspection SpellCheckingInspection
			String getBibsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/deleted/paged?lastID=" + lastId + "&deletedate=" + URLEncoder.encode(deleteDate, StandardCharsets.UTF_8) + "&nrecs=100";
			int numTries = 0;
			boolean successfulResponse = false;
			while (numTries < 3 && !successfulResponse){
				numTries++;
				WebServiceResponse pagedBibs = callPolarisAPI(getBibsUrl, null, "GET", "text/xml", accessSecret);
				if (pagedBibs.isSuccess()) {
					try {
						successfulResponse = true;
						Document pagedDeletesDocument = createXMLDocumentForWebServiceResponse(pagedBibs);
						Element getBibsDeletesResult = (Element) pagedDeletesDocument.getFirstChild();
						Element getDeletedBibsPagedRows = (Element) getBibsDeletesResult.getElementsByTagName("BibIDListRows").item(0);
						NodeList deletedBibsPagedRows = getDeletedBibsPagedRows.getElementsByTagName("BibIDListRow");
						if (deletedBibsPagedRows.getLength() == 0){
							//Stop looping looking for more records
							doneLoading = true;
						}
						for (int i = 0; i < deletedBibsPagedRows.getLength(); i++) {
							Element bibPagedRow = (Element) deletedBibsPagedRows.item(i);
							String bibliographicRecordId = bibPagedRow.getElementsByTagName("BibliographicRecordID").item(0).getTextContent();
							//This record has no items, suppress it
							RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibliographicRecordId);
							if (result.reindexWork){
								getGroupedWorkIndexer().processGroupedWork(result.permanentId);
							}else if (result.deleteWork){
								//Delete the work from solr and the database
								getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
							}
							logEntry.incDeleted();
							lastId = bibliographicRecordId;
							numChanges++;
						}
					} catch (Exception e) {
						if (numTries == 3) {
							logEntry.incErrors("Unable to parse document for paged deleted bibs response", e);
							doneLoading = true;
						}
					}
				} else {
					if (numTries == 3) {
						logEntry.incErrors("Could not get deleted bibs from " + getBibsUrl + " " + pagedBibs.getResponseCode() + " " + pagedBibs.getMessage());
						doneLoading = true;
					}
				}
			}
		}

		return numChanges;
	}

	private static int updateBibsFromPolarisFull(long lastExtractTime) {
		int numChanges = 0;

		//Get a paged list of all bibs
		String lastId = "0";
		MarcFactory marcFactory = MarcFactory.newInstance();
		DateTimeFormatter dateFormatter;
		if (use7_4DateFormatFunctionality()) {
			dateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		} else {
			dateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		}
		if (indexingProfile.isRunFullUpdate() && indexingProfile.getLastChangeProcessed() > 0){
			lastId = Long.toString(indexingProfile.getLastChangeProcessed());
			logEntry.incSkipped(indexingProfile.getLastChangeProcessed());
			logEntry.addNote("Starting processing at bib " + lastId);
		}

		//Get the highest bib from Polaris
		//noinspection SpellCheckingInspection
		WebServiceResponse maxBibResponse = callPolarisAPI("/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/maxid", null, "GET", "application/json", accessSecret);
		long maxBibId = -1;
		if (maxBibResponse.isSuccess()){
			maxBibId = maxBibResponse.getJSONResponse().getJSONArray("BibIDListRows").getJSONObject(0).getLong("BibliographicRecordID");
			logEntry.addNote("The maximum bib id in the Polaris Database is " + maxBibId);
		}

		boolean doneLoading = false;
		long highestIdProcessed = 0;
		while (!doneLoading) {
			//Polaris has an "include items" field, but it does not seem to contain all information we need for Aspen.
			long lastIdForThisBatch = Long.parseLong(lastId);
			if (lastIdForThisBatch > highestIdProcessed){
				highestIdProcessed = lastIdForThisBatch;
			}
			//noinspection SpellCheckingInspection
			String getBibsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/MARCXML/paged?nrecs=100&lastID=" + lastId;
			ProcessBibRequestResponse response = processGetBibsRequest(getBibsUrl, marcFactory, lastExtractTime, true);
			numChanges += response.numChanges;
			//Polaris has an issue where if there are more than 100 suppressed titles, it will return 0 as the lastId.  We need to account for that
			long lastIdLong = Long.parseLong(response.lastId);
			logEntry.setCurrentId(response.lastId);
			//MDN this seems to be normal if nothing has changed since the last extract.
			if (lastIdLong == 0) {
				highestIdProcessed = maxBibId;
			}else  if (lastIdLong > highestIdProcessed){
				highestIdProcessed = lastIdLong;
			}
			lastId = Long.toString(highestIdProcessed);
			if (indexingProfile.isRunFullUpdate()) {
				indexingProfile.setLastChangeProcessed(highestIdProcessed);
				indexingProfile.updateLastChangeProcessed(dbConn, logEntry);
			}
			if (highestIdProcessed >= maxBibId){
				doneLoading = true;
			}
		}
		indexingProfile.setLastChangeProcessed(0);
		indexingProfile.updateLastChangeProcessed(dbConn, logEntry);

		logEntry.addNote("Finished updating bibs");
		logEntry.saveResults();
		
		return numChanges;
	}

	private static int updateBibsFromPolarisIncremental(long lastExtractTime) {
		int numChanges = 0;

		bibIdsUpdatedDuringContinuous = Collections.synchronizedSet(new HashSet<>());
		itemIdsUpdatedDuringContinuous = Collections.synchronizedSet(new HashSet<>());

		//Get a paged list of all bibs
		MarcFactory marcFactory = MarcFactory.newInstance();
		DateTimeFormatter dateFormatter;
		if (use7_4DateFormatFunctionality()) {
			dateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		} else {
			dateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		}
		String formattedLastExtractTime = "";
		formattedLastExtractTime = dateFormatter.format(Instant.ofEpochSecond(lastExtractTime));
		logEntry.addNote("Looking for changed records since " + formattedLastExtractTime);
		formattedLastExtractTime = URLEncoder.encode(formattedLastExtractTime, StandardCharsets.UTF_8);
		String formattedTimeNow = dateFormatter.format(Instant.now());

		//Get the highest bib from Polaris
		//noinspection SpellCheckingInspection
		WebServiceResponse maxBibResponse = callPolarisAPI("/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/maxid", null, "GET", "application/json", accessSecret);
		long maxBibId = -1;
		if (maxBibResponse.isSuccess()){
			maxBibId = maxBibResponse.getJSONResponse().getJSONArray("BibIDListRows").getJSONObject(0).getLong("BibliographicRecordID");
			logEntry.addNote("The maximum bib id in the Polaris Database is " + maxBibId);
		}

		//If we are doing a continuous index, get a list of any items that have been updated or changed or bib ids that have been replaced
		HashSet<String> bibsToUpdate = new HashSet<>();

		// Get a list of all the bibs that have been updated since the last extract 
		String getUpdatedBibsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/updated?updatedate=" + formattedLastExtractTime;	
		WebServiceResponse updatedBibs = callPolarisAPI(getUpdatedBibsUrl, null, "GET", "application/json", accessSecret);
		if (updatedBibs.isSuccess()){
			try {
				JSONObject response = updatedBibs.getJSONResponse();
				int errorCode = response.getInt("PAPIErrorCode");
				if (errorCode != 0) {
					String errorMessage = response.getString("ErrorMessage");
					logEntry.incErrors("Polaris API error " + errorCode + ": " + errorMessage);
					return numChanges;
				}
				JSONArray allBibs = response.getJSONArray("BibIDListRows");
				for (int i = 0; i < allBibs.length(); i++) {
					JSONObject bibObj = allBibs.getJSONObject(i);
					String bibId = Long.toString(bibObj.getLong("BibliographicRecordID"));
					bibsToUpdate.add(bibId);
				}
			} catch (Exception e) {
				logEntry.incErrors("error getting updated bibs list from Polaris", e);
			}
		}

		//Get a list of any bibs that have been replaced.
		DateTimeFormatter dateReplacedFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		String formattedLastItemExtractDate = URLEncoder.encode(dateReplacedFormatter.format(Instant.ofEpochSecond(lastExtractTime)), StandardCharsets.UTF_8);
		//noinspection SpellCheckingInspection
		String getBibReplacedUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/replacementids?startdate=" + formattedLastItemExtractDate;
		WebServiceResponse bibsReplaced = callPolarisAPI(getBibReplacedUrl, null, "GET", "application/json", accessSecret);
		if (bibsReplaced.isSuccess()){
			try {
				JSONObject response = bibsReplaced.getJSONResponse();
				JSONArray allBibs = response.getJSONArray("BibReplacementIDRows");
				logEntry.addNote("There were " + allBibs.length() + "bibs where the id has been replaced");
				for (int i = 0; i < allBibs.length(); i++) {
					JSONObject curBibReplacement = allBibs.getJSONObject(i);
					String originalId = Long.toString(curBibReplacement.getLong("OriginalBibRecordID"));
					String newId = Long.toString(curBibReplacement.getLong("NewBibliographicRecordID"));
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), originalId);
					if (result.reindexWork){
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					}else if (result.deleteWork){
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					logEntry.incDeleted();
					bibsToUpdate.add(newId);
				}
				logEntry.saveResults();
			} catch (Exception e) {
				logEntry.incErrors("Unable to parse document for replaced bibs response", e);
			}
		}

		DateTimeFormatter itemDateFormatter;
		if (use7_4DateFormatFunctionality()) {
			itemDateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		} else {
			itemDateFormatter = DateTimeFormatter.ofPattern("MM/dd/yyyy HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
		}
		String formattedLastItemExtractTime = URLEncoder.encode(itemDateFormatter.format(Instant.ofEpochSecond(lastExtractTime)), StandardCharsets.UTF_8);
		logEntry.addNote("Getting a list of all items that have been updated");
		logEntry.saveResults();

		long sourceId = -1;
		try {
			sourceForPolarisStmt.setString(1, indexingProfile.getName());
			ResultSet sourceForPolarisRS = sourceForPolarisStmt.executeQuery();
			if (sourceForPolarisRS.next()){
				sourceId = sourceForPolarisRS.getLong(1);
			}else{
				//Nothing has been indexed yet, leave source ID set to -1
			}
		} catch (Exception e) {
			logEntry.incErrors("Error getting source id for " + indexingProfile.getName(), e);
		}

		// Get a list of items that have been deleted and update those MARC records too
		if (sourceId != -1) {
			DateTimeFormatter itemDeleteDateFormatter;
			if (use7_4DateFormatFunctionality()) {
				itemDeleteDateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd", Locale.ENGLISH).withZone(ZoneId.systemDefault());
			}else{
				itemDeleteDateFormatter = DateTimeFormatter.ofPattern("MM/dd/yyyy HH:mm:ss", Locale.ENGLISH).withZone(ZoneId.systemDefault());
			}
			String formattedItemDeleteDate = URLEncoder.encode(itemDeleteDateFormatter.format(Instant.ofEpochSecond(lastExtractTime)), StandardCharsets.UTF_8);

			//noinspection SpellCheckingInspection
			String getDeletedItemsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/items/deleted?deletedate=" + formattedItemDeleteDate;
			WebServiceResponse pagedDeletedItems = callPolarisAPI(getDeletedItemsUrl, null, "GET", "application/json", accessSecret);
			int bibsToUpdateBasedOnDeletedItems = 0;
			if (pagedDeletedItems.isSuccess()) {
				try {
					JSONObject response = pagedDeletedItems.getJSONResponse();
					JSONArray allItems = response.getJSONArray("ItemIDListRows");
					logEntry.addNote("There were " + allItems.length() + " items that have been deleted");
					logEntry.saveResults();
					for (int i = 0; i < allItems.length(); i++) {
						JSONObject curItem = allItems.getJSONObject(i);
						long itemId = curItem.getLong("ItemRecordID");
						//Figure out the bib record based on the item id.
						String bibForItem = getBibIdForItemIdFromAspen(itemId, sourceId);
						if (bibForItem == null) {
							bibForItem = getBibIdForItemId(itemId);
						}
						if (bibForItem != null) {
							if (!bibsToUpdate.contains(bibForItem)) {
								logEntry.incProducts();
								bibsToUpdate.add(bibForItem);
								bibsToUpdateBasedOnDeletedItems++;
								if (logEntry.getNumProducts() % 250 == 0) {
									logEntry.saveResults();
								}
							}
						}
						if (i > 0 && (i % 1000 == 0)) {
							logEntry.addNote("Processed " + i + " items looking for the bib that was deleted");
							logEntry.saveResults();
						}
					}
				} catch (Exception e) {
					logEntry.incErrors("Unable to parse document for deleted items response", e);
				}
			}
			logEntry.addNote("There are " + bibsToUpdateBasedOnDeletedItems + " records to be updated based on deleted items.");
			logEntry.saveResults();

			//Get the highest item id from Polaris
			//noinspection SpellCheckingInspection
			WebServiceResponse maxItemResponse = callPolarisAPI("/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/items/maxid", null, "GET", "application/json", accessSecret);
			long maxItemId = -1;
			if (maxItemResponse.isSuccess()){
				maxItemId = maxItemResponse.getJSONResponse().getJSONArray("ItemIDListRows").getJSONObject(0).getLong("ItemRecordID");
				logEntry.addNote("The maximum item id in the Polaris Database is " + maxItemId);
			}

			boolean doneLoading = false;
			long highestItemIdProcessed = 0;
			int bibsToUpdateBasedOnChangedItems = 0;
			while (!doneLoading) {
				//noinspection SpellCheckingInspection
				String getItemsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/items/updated/paged?updatedate=" + formattedLastItemExtractTime + "&nrecs=100&lastId=" + highestItemIdProcessed;
				WebServiceResponse pagedItems = callPolarisAPI(getItemsUrl, null, "GET", "application/json", accessSecret);
				if (pagedItems.isSuccess()) {
					try {
						JSONObject response = pagedItems.getJSONResponse();
						JSONArray allItems = response.getJSONArray("ItemIDListRows");

						if (allItems.isEmpty()) {
							doneLoading = true;
						}
						for (int i = 0; i < allItems.length(); i++) {
							JSONObject curItem = allItems.getJSONObject(i);
							long itemId = curItem.getLong("ItemRecordID");
							if (!itemIdsUpdatedDuringContinuous.contains(itemId)) {
								//Figure out the bib record based on the item id.
								//Getting from Aspen is faster if we can get it.
								HashSet<String> bibsForItem = getBibIdsForItemIdFromAspen(itemId, sourceId);
								for (String bibForItem : bibsForItem) {
									//check we've already updated this bib, if so it's ok to skip
									if (!bibIdsUpdatedDuringContinuous.contains(bibForItem)) {
										logEntry.incProducts();
										bibsToUpdate.add(bibForItem);
										bibsToUpdateBasedOnChangedItems++;
										if (logEntry.getNumProducts() % 250 == 0) {
											logEntry.saveResults();
										}
									}
								}
							} else {
								logger.info("Not updating item " + itemId + "because it was already processed when updating bibs");
							}
							if (itemId > highestItemIdProcessed) {
								highestItemIdProcessed = itemId;
							}
							if (i > 0 && (i % 500 == 0)) {
								logEntry.addNote("Processed " + i + " items to load bib id for the item");
							}
						}

						if (highestItemIdProcessed >= maxItemId){
							doneLoading = true;
						}
					} catch (Exception e) {
						logEntry.incErrors("Unable to parse document for paged items response", e);
					}

				}else{
					logEntry.incErrors("Could not get paged items");
				}
			}
			logEntry.addNote("There are " + bibsToUpdateBasedOnChangedItems + " records to be updated based on changes to the items.");
			logEntry.saveResults();
		}


		//Now that we have a list of all bibs that need to be updated based on item changes, reindex the bib
		List<String> bibsNumber = new ArrayList<>(bibsToUpdate);
		int batchSize = 50;

		for (int i = 0; i < bibsNumber.size(); i += batchSize) {
			List<String> batch = bibsNumber.subList(i, Math.min(i + batchSize, bibsNumber.size()));
			String bibIds = String.join(",", batch);
			numChanges += updateBibFromPolaris(bibIds, marcFactory, lastExtractTime, false);
			logEntry.setCurrentId(batch.get(batch.size() - 1));
		}

		logEntry.addNote("Finished updating bibs");
		logEntry.saveResults();

		return numChanges;
	}

	private static String getBibIdForItemIdFromAspen(long itemId, long sourceId) {
		if (sourceId == -1){
			//No records have been saved yet
			return null;
		}
		try {
			String itemIdString = Long.toString(itemId);
			getRecordIdForItemIdStmt.setString(1, itemIdString);
			ResultSet getRecordIdForItemIdRS = getRecordIdForItemIdStmt.executeQuery();
			while (getRecordIdForItemIdRS.next()){
				long recordId = getRecordIdForItemIdRS.getLong("groupedWorkRecordId");
				//If records are merged, we can have more than one record for the item in that case, don't return anything
				if (getRecordIdForItemIdRS.next()) {
					return null;
				}

				getBibIdForItemIdStmt.setLong(1, recordId);
				getBibIdForItemIdStmt.setLong(2, sourceId);
				ResultSet getBibIdForItemIdRS = getBibIdForItemIdStmt.executeQuery();
				if (getBibIdForItemIdRS.next()){
					return getBibIdForItemIdRS.getString("recordIdentifier");
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error getting bib for item id from Aspen", e);
		}
		return null;
	}

	private static HashSet<String> getBibIdsForItemIdFromAspen(long itemId, long sourceId) {
		HashSet<String> bibIds = new HashSet<>();
		if (sourceId == -1){
			return bibIds;
		}
		try {
			String itemIdString = Long.toString(itemId);
			getRecordIdForItemIdStmt.setString(1, itemIdString);
			ResultSet getRecordIdForItemIdRS = getRecordIdForItemIdStmt.executeQuery();
			//If records are merged, we can have more than one record for the item in that case, don't return anything
			boolean bibFound = false;
			while (getRecordIdForItemIdRS.next()){
				long recordId = getRecordIdForItemIdRS.getLong("groupedWorkRecordId");
				getBibIdForItemIdStmt.setLong(1, recordId);
				getBibIdForItemIdStmt.setLong(2, sourceId);
				ResultSet getBibIdForItemIdRS = getBibIdForItemIdStmt.executeQuery();
				if (getBibIdForItemIdRS.next()){
					bibIds.add(getBibIdForItemIdRS.getString("recordIdentifier"));
					bibFound = true;
				}
			}
			if (!bibFound) {
				//No records have been saved yet, get them from Polaris
				String bibForItem = getBibIdForItemId(itemId);
				bibIds.add(bibForItem);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error getting bib for item id from Aspen", e);
		}
		return bibIds;
	}

	private static int updateBibFromPolaris(String bibNumber, MarcFactory marcFactory, long lastExtractTime, boolean incrementProductsInLog) {
		//Get the bib record
		//noinspection SpellCheckingInspection
		String getBibUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/MARCXML?bibids=" + bibNumber;
		ProcessBibRequestResponse response = processGetBibsRequest(getBibUrl, marcFactory, lastExtractTime, incrementProductsInLog);
		return response.numChanges;
	}

	static Pattern polarisDatePattern = Pattern.compile("/Date\\((-?\\d+)(-\\d{4})\\)/");
	static Pattern lastCheckInPattern = Pattern.compile("\\w{3}\\s+\\d{1,2}\\s+\\d{4}");
	private static ProcessBibRequestResponse processGetBibsRequest(String getBibsRequestUrl, MarcFactory marcFactory, long lastExtractTime, boolean incrementProductsInLog){
		ProcessBibRequestResponse response = new ProcessBibRequestResponse();
		if (marcFactory == null){
			marcFactory = MarcFactory.newInstance();
		}
		int numTries = 0;
		boolean successfulResponse = false;
		while (numTries < 3 && !successfulResponse) {
			numTries++;
			WebServiceResponse getBibsResponse = callPolarisAPI(getBibsRequestUrl, null, "GET", "text/xml", accessSecret);
			if (getBibsResponse.isSuccess()) {
				try {
					successfulResponse = true;
					Document getBibsDocument = createXMLDocumentForWebServiceResponse(getBibsResponse);
					Element getBibsPagedResult = (Element) getBibsDocument.getFirstChild();
					NodeList lastIdNodes = getBibsPagedResult.getElementsByTagName("LastID");
					if (lastIdNodes.getLength() > 0) {
						Node lastIdNode = getBibsPagedResult.getElementsByTagName("LastID").item(0);
						response.lastId = lastIdNode.getTextContent();
					}else{
						response.doneLoading = true;
					}
					Element getBibsPagedRows = (Element) getBibsPagedResult.getElementsByTagName("GetBibsPagedRows").item(0);
					NodeList bibsPagedRows;
					if (getBibsPagedRows != null) {
						bibsPagedRows = getBibsPagedRows.getElementsByTagName("GetBibsPagedRow");
					}else{
						getBibsPagedRows = (Element) getBibsPagedResult.getElementsByTagName("GetBibsByIDRows").item(0);
						bibsPagedRows = getBibsPagedRows.getElementsByTagName("GetBibsByIDRow");
					}
					if (bibsPagedRows.getLength() == 0){
						//Stop looping looking for more records
						response.doneLoading = true;
					}

					//Use multiple threads to update each bib record, so we can make multiple calls to Polaris to get items
					ThreadPoolExecutor es = (ThreadPoolExecutor) Executors.newFixedThreadPool(10);
					MarcFactory finalMarcFactory = marcFactory;
					for (int i = 0; i < bibsPagedRows.getLength(); i++) {
						int finalI = i;
						es.execute(() -> processPolarisBibAndReindex(finalMarcFactory, lastExtractTime, incrementProductsInLog, response, bibsPagedRows, finalI));
					}
					es.shutdown();
					while (true) {
						try {
							boolean terminated = es.awaitTermination(1, TimeUnit.MINUTES);
							if (terminated){
								break;
							}
						} catch (InterruptedException e) {
							logger.error("Error waiting for all extracts to finish");
						}
					}
					logEntry.saveResults();
				} catch (Exception e) {
					logEntry.incErrors("Unable to parse document for paged bibs response", e);
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

	private static void processPolarisBibAndReindex(MarcFactory marcFactory, long lastExtractTime, boolean incrementProductsInLog, ProcessBibRequestResponse response, NodeList bibsPagedRows, int i) {
		if (incrementProductsInLog) {
			logEntry.incProducts();
		}
		SimpleDateFormat polarisDateParser = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");

		Element bibPagedRow = (Element) bibsPagedRows.item(i);
		String bibliographicRecordId = bibPagedRow.getElementsByTagName("BibliographicRecordID").item(0).getTextContent();
		getRecordGroupingProcessor().removeExistingRecord(bibliographicRecordId);
		String displayInPAC = bibPagedRow.getElementsByTagName("IsDisplayInPAC").item(0).getTextContent();
		if (displayInPAC.equals("true")) {
			//If we are not running a full update, check to be sure the title was actually updated since we last ran.
			if (!indexingProfile.isRunFullUpdate()){
				Date creationDate = null;
				Date modificationDate = null;
				String creationDateString = bibPagedRow.getElementsByTagName("CreationDate").item(0).getTextContent();
				String modificationDateString = bibPagedRow.getElementsByTagName("ModificationDate").item(0).getTextContent();
				try {
					creationDate = polarisDateParser.parse(creationDateString);
				}catch (ParseException | NumberFormatException e){
					logEntry.incErrors("Could not parse creation date", e);
				}
				try {
					modificationDate = polarisDateParser.parse(modificationDateString);
				}catch (ParseException | NumberFormatException e){
					logEntry.incErrors("Could not parse modification date", e);
				}
				if (creationDate != null && creationDate.getTime() < lastExtractTime && modificationDate != null && modificationDate.getTime() < lastExtractTime) {
					//Skip this record
					return;
				}
			}
			bibIdsUpdatedDuringContinuous.add(bibliographicRecordId);

			//Get a count of the holds for the record
			String getBibUrl = "/PAPIService/REST/public/v1/1033/100/1/bib/" + bibliographicRecordId;
			WebServiceResponse getBibResponse = callPolarisAPI(getBibUrl, null, "GET", "application/json", null);
			if (getBibResponse.isSuccess()){
				JSONObject bibInfo = getBibResponse.getJSONResponse();
				if (bibInfo.has("BibGetRows") && bibInfo.get("BibGetRows") instanceof JSONArray) {
					JSONArray bibRows = bibInfo.getJSONArray("BibGetRows");
					for (int j = 0; j < bibRows.length(); j++) {
						JSONObject bibRow = bibRows.getJSONObject(j);
						if (bibRow.getInt("ElementID") == 8) {
							int numHolds = Integer.parseInt(bibRow.getString("Value"));
							try {
								addIlsHoldSummary.setString(1, bibliographicRecordId);
								addIlsHoldSummary.setInt(2, numHolds);
								addIlsHoldSummary.executeUpdate();
							} catch (SQLException e) {
								logEntry.incErrors("Unable to update hold summary", e);
							}
						}
					}
				}else{
					logEntry.addNote("Did not get a bib record for " + bibliographicRecordId + " skipping");
					return;
				}
			}
			try {
				String bibRecordXML = bibPagedRow.getElementsByTagName("BibliographicRecordXML").item(0).getTextContent();
				//bibRecordXML = StringEscapeUtils.unescapeXml(bibRecordXML);
				MarcXmlReader marcXmlReader = new MarcXmlReader(new ByteArrayInputStream(bibRecordXML.getBytes(StandardCharsets.UTF_8)));
				org.marc4j.marc.Record marcRecord = marcXmlReader.next();

				if (marcRecord != null) {
					//Check to see if any of the fields we got in the bib will conflict with item records
					List<DataField> itemFields = marcRecord.getDataFields(indexingProfile.getItemTag());
					for (DataField itemField : itemFields) {
						marcRecord.removeVariableField(itemField);
					}
					//Get Items from the API
					boolean gotItems = getItemsForBibFromPolaris(marcFactory, bibliographicRecordId, marcRecord);
					if (gotItems){
						GroupedWorkIndexer.MarcStatus saveMarcResult = getGroupedWorkIndexer().saveMarcRecordToDatabase(indexingProfile, bibliographicRecordId, marcRecord);
						if (saveMarcResult == GroupedWorkIndexer.MarcStatus.NEW){
							logEntry.incAdded();
						}else {
							logEntry.incUpdated();
						}

						updateVolumeInfoForIdentifier(marcRecord, bibliographicRecordId);

						//Regroup the record
						String groupedWorkId = groupPolarisRecord(marcRecord);
						if (groupedWorkId != null) {
							//Reindex the record
							getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
						}
						response.numChanges++;
					}
				} else {
					logEntry.incErrors("Could not read marc record for " + bibliographicRecordId);
				}
			}catch (Exception e){
				logEntry.incErrors("Error loading marc record for bib " + bibliographicRecordId, e);
			}
		} else {
			RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibliographicRecordId);
			if (result.reindexWork){
				getGroupedWorkIndexer().processGroupedWork(result.permanentId);
			}else if (result.deleteWork){
				//Delete the work from solr and the database
				getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
			}
			logEntry.incDeleted();
		}
	}

	private static synchronized void updateVolumeInfoForIdentifier(Record marcRecord, String bibliographicRecordId) {
		String fullIdentifier = indexingProfile.getName() + ":" + bibliographicRecordId;
		TreeMap<String, VolumeInfo> volumesForRecord = new TreeMap<>();
		List<DataField> itemFields = marcRecord.getDataFields(indexingProfile.getItemTagInt());
		for (DataField curItem : itemFields){
			Subfield volumeSubfield = curItem.getSubfield(indexingProfile.getVolume());
			if (volumeSubfield != null) {
				String volume = volumeSubfield.getData();
				if (volume != null && !volume.trim().isEmpty()) {
					volume = volume.trim();
					VolumeInfo volumeInfo = volumesForRecord.get(volume);
					if (volumeInfo == null) {
						volumeInfo = new VolumeInfo();
						volumeInfo.bibNumber = fullIdentifier;
						volumeInfo.volume = volume;
						volumeInfo.volumeIdentifier = volume;
						volumesForRecord.put(volume, volumeInfo);
					}
					String itemNumber;
					Subfield itemNumberSubfield = curItem.getSubfield(indexingProfile.getItemRecordNumberSubfield());
					if (itemNumberSubfield != null){
						itemNumber = itemNumberSubfield.getData();
					}else{
						Subfield barcodeSubfield = curItem.getSubfield(indexingProfile.getBarcodeSubfield());
						if (barcodeSubfield == null) {
							continue;
						}else{
							itemNumber = barcodeSubfield.getData();
						}
					}
					volumeInfo.relatedItems.add(itemNumber);
				}
			}
		}
		//Save the volumes to the database
		try {

			if (volumesForRecord.isEmpty()){
				deleteAllVolumesStmt.setString(1, fullIdentifier);
				deleteAllVolumesStmt.executeUpdate();
			}else {
//				logger.info(fullIdentifier + " has volumes " + volumesForRecord.size());
				HashMap<String, Long> existingVolumes = new HashMap<>();
				getExistingVolumesStmt.setString(1, fullIdentifier);
				ResultSet existingVolumesRS = getExistingVolumesStmt.executeQuery();
				while (existingVolumesRS.next()) {
					existingVolumes.put(existingVolumesRS.getString("volumeId"), existingVolumesRS.getLong("id"));
				}
//				logger.info(" -- existing volume count " + existingVolumes.size());
				int numVolumes = 0;
				for (String volume : volumesForRecord.keySet()) {
					VolumeInfo volumeInfo = volumesForRecord.get(volume);
					try {
						if (existingVolumes.containsKey(volume)) {
							logger.info(" -- updating " + volume);
							updateVolumeStmt.setString(1, volumeInfo.volumeIdentifier);
							updateVolumeStmt.setString(2, volumeInfo.getRelatedItemsAsString());
							updateVolumeStmt.setLong(3, ++numVolumes);
							updateVolumeStmt.setLong(4, existingVolumes.get(volume));
							updateVolumeStmt.executeUpdate();
							existingVolumes.remove(volume);
						} else {
							logger.info(" -- adding " + volume);
							addVolumeStmt.setString(1, fullIdentifier);
							addVolumeStmt.setString(2, volumeInfo.volume);
							addVolumeStmt.setString(3, volumeInfo.volumeIdentifier);
							addVolumeStmt.setString(4, volumeInfo.getRelatedItemsAsString());
							addVolumeStmt.setLong(5, ++numVolumes);
							addVolumeStmt.executeUpdate();
						}
					}catch (Exception e){
						logEntry.incErrors("Error updating volume for record " + fullIdentifier + " (" + volume.length() + ") " + volume , e);
					}
				}
				for (String volume : existingVolumes.keySet()) {
					deleteVolumeStmt.setLong(1, existingVolumes.get(volume));
					deleteVolumeStmt.executeUpdate();
				}
			}
		}catch (Exception e){
			logEntry.incErrors("Error updating volumes for record ", e);
		}
	}

	private static boolean getItemsForBibFromPolaris(MarcFactory marcFactory, String bibliographicRecordId, Record marcRecord) {
		SimpleDateFormat dateCreatedFormatter = new SimpleDateFormat("yyyy-MM-dd");

		int getItemsTries = 0;
		boolean gotItems = false;
		String getItemsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/items/bibid/" + bibliographicRecordId;
		while (!gotItems && getItemsTries < 3) {
			getItemsTries++;
			WebServiceResponse bibItemsResponse = callPolarisAPI(getItemsUrl, null, "GET", "application/json", accessSecret);
			try {
				if (bibItemsResponse.isSuccess()) {
					//Add Items to the MARC record
					JSONObject bibItemsResponseJSON = bibItemsResponse.getJSONResponse();
					JSONArray allItems = bibItemsResponseJSON.getJSONArray("ItemGetRows");

					JSONArray allHoldings = null;
					if (indexingProfile.getNoteSubfield() != ' ') {
						String getHoldingsUrl = "/PAPIService/REST/public/v1/1033/100/1/bib/" + bibliographicRecordId + "/holdings";
						WebServiceResponse bibHoldingsResponse = callPolarisAPI(getHoldingsUrl, null, "GET", "application/json", null);
						if (bibHoldingsResponse.isSuccess()) {
							try {
								JSONObject bibHoldingsResponseJSON = bibHoldingsResponse.getJSONResponse();
								allHoldings = bibHoldingsResponseJSON.getJSONArray("BibHoldingsGetRows");
							}catch (Exception e){
								//Just add a note for this
								logEntry.addNote("Error loading bib holdings " + e);
							}
						}
					}
					for (int j = 0; j < allItems.length(); j++) {
						JSONObject curItem = allItems.getJSONObject(j);
						if (curItem.getBoolean("IsDisplayInPAC")) {
							DataField itemField = marcFactory.newDataField(indexingProfile.getItemTag(), ' ', ' ');
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getItemRecordNumberSubfield(), "ItemRecordID");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getBarcodeSubfield(), "Barcode");
							String callNumber = getItemFieldData(curItem, "CallNumber").trim();
							String designation = getItemFieldData(curItem, "Designation").trim();
							if (!designation.isEmpty()){
								callNumber = callNumber + " " + designation;
							}
							itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getCallNumberSubfield(), callNumber));
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getLocationSubfield(), "LocationID");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getCollectionSubfield(), "CollectionName");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getShelvingLocationSubfield(), "ShelfLocation");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getVolume(), "VolumeNumber");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getITypeSubfield(), "MaterialType");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getItemStatusSubfield(), "CircStatus");
							updateItemField(marcFactory, curItem, itemField, indexingProfile.getDueDateSubfield(), "DueDate");
							String circDate = getItemFieldData(curItem, "LastCircDate").trim();
							if (!circDate.isEmpty() && lastCheckInPattern.matcher(circDate).matches()){
								SimpleDateFormat lastCheckInParser = new SimpleDateFormat("MMM dd yyy");
								Date lastCheckInDate = lastCheckInParser.parse(circDate);
								itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getLastCheckinDateSubfield(), indexingProfile.getLastCheckinFormatter().format(lastCheckInDate)));
							}else {
								updateItemField(marcFactory, curItem, itemField, indexingProfile.getLastCheckinDateSubfield(), "LastCircDate");
							}

							//Public note is only exported as part of holdings so get from there
							if (indexingProfile.getNoteSubfield() != ' ' && allHoldings != null) {
								//Get the holding based on the barcode
								if (!curItem.isNull("Barcode")) {
									String itemBarcode = curItem.getString("Barcode");
									if (itemBarcode != null) {
										for (int k = 0; k < allHoldings.length(); k++) {
											JSONObject curHolding = allHoldings.getJSONObject(k);
											if (!curHolding.isNull("Barcode") && itemBarcode.equals(curHolding.getString("Barcode"))) {
												updateItemField(marcFactory, curHolding, itemField, indexingProfile.getNoteSubfield(), "PublicNote");
												break;
											}
										}
									}
								}
							}
							if (indexingProfile.getDateCreatedSubfield() != ' ') {
								String dateCreated = getItemFieldData(curItem, "FirstAvailableDate");
								if (!dateCreated.isEmpty()) {
									Matcher dateCreatedMatcher = polarisDatePattern.matcher(dateCreated);
									if (dateCreatedMatcher.matches()){
										Date dateCreatedTime = new Date(Long.parseLong(dateCreatedMatcher.group(1)));
										itemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDateCreatedSubfield(), dateCreatedFormatter.format(dateCreatedTime)));
									}
								}
							}

							marcRecord.addVariableField(itemField);

							itemIdsUpdatedDuringContinuous.add(curItem.getLong("ItemRecordID"));
						}
					}
					gotItems = true;
				} else {
					//This record has no items, suppress it
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibliographicRecordId);
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					logEntry.incDeleted();
				}
			} catch (Exception e) {
				if (getItemsTries == 3) {
					logEntry.incErrors("Error loading items for bib " + bibliographicRecordId, e);
				}
			}
		}
		return gotItems;
	}

	private static String getBibIdForItemId(long itemId) {
		String bibForItem = null;
		String getItemUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/item/" + itemId;
		int numTries = 0;
		boolean successfulResponse = false;
		while (numTries < 3 && !successfulResponse) {
			numTries++;
			WebServiceResponse getItemResponse = callPolarisAPI(getItemUrl, null, "GET", "application/json", accessSecret);
			if (getItemResponse.isSuccess()) {
				successfulResponse = true;
				JSONArray itemInfoRows = getItemResponse.getJSONResponse().getJSONArray("ItemGetRows");
				if (!itemInfoRows.isEmpty()) {
					JSONObject itemInfo = itemInfoRows.getJSONObject(0);
					bibForItem = Long.toString(itemInfo.getLong("BibliographicRecordID"));
				} else {
					//This does not look like an error, just return a null bib.
					logger.info("Failed to get bib id for item id " + itemId + ", could not find the item.");
					logger.info(getItemResponse.getMessage());
				}
			} else {
				if (numTries == 3) {
					logEntry.incErrors("Failed to get bib id for item id " + itemId + ", response was not successful.");
				}
			}
		}
		return bibForItem;
	}

	private static void updateItemField(MarcFactory marcFactory, JSONObject curItem, DataField itemField, char subfieldIndicator, String polarisFieldName) {
		if (subfieldIndicator != ' ') {
			itemField.addSubfield(marcFactory.newSubfield(subfieldIndicator, getItemFieldData(curItem, polarisFieldName)));
		}
	}

	private static String getItemFieldData(JSONObject curItem, String fieldName) {
		if (curItem.isNull(fieldName)){
			return "";
		}else{
			Object itemValue = curItem.get(fieldName);
			if (itemValue instanceof Integer){
				return Integer.toString((int)itemValue);
			}else if (itemValue instanceof String) {
				return (String)itemValue;
			}else{
				return itemValue.toString();
			}
		}
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
	private static void disconnectDatabase() {
		try {
			//Close the connection
			if (dbConn != null) {
				dbConn.close();
				dbConn = null;
			}
		} catch (Exception e) {
			System.out.println("Error closing aspen connection: " + e);
		}
	}

	private static final String HMAC_SHA1_ALGORITHM = "HmacSHA1";
	private static final DateTimeFormatter dateFormatter = DateTimeFormatter.ofPattern("EEE, dd MMM yyyy HH:mm:ss z", Locale.ENGLISH).withZone(ZoneId.of("GMT"));
	private static SecretKeySpec signingKey;
	private static WebServiceResponse callPolarisAPI(String url, String postData, String method, String contentType, String accessSecret){
		if (signingKey == null){
			signingKey = new SecretKeySpec(clientSecret.getBytes(), HMAC_SHA1_ALGORITHM);
		}
		String fullUrl = webServiceUrl + url;

		String authorization = "PWS " + clientId + ":";
		String curTime = dateFormatter.format(Instant.now());
		String signatureUnencoded = method + fullUrl + curTime;
		if (accessSecret != null){
			signatureUnencoded += accessSecret;
		}
		try {
			Mac mac;
			mac = Mac.getInstance(HMAC_SHA1_ALGORITHM);
			mac.init(signingKey);
			byte[] rawHmac = mac.doFinal(signatureUnencoded.getBytes());
			authorization += Base64.encodeBase64String(rawHmac, false);
		}catch (Exception e){
			logEntry.incErrors("Could not call Polaris API", e);
			return new WebServiceResponse(false, 500, "Could not connect to API");
		}

		HashMap<String, String> headers = new HashMap<>();
		headers.put("Content-type", contentType);
		headers.put("Accept", contentType);
		headers.put("PolarisDate", curTime);
		headers.put("Authorization", authorization);

		if (method.equals("GET")) {
			return NetworkUtils.getURL(fullUrl, logger, headers);
		}else{
			return NetworkUtils.postToURL(fullUrl, postData, contentType, null, logger, null, 10000, 60000, StandardCharsets.UTF_8, headers, !serverName.contains(".localhost"));
		}
	}

	private static WebServiceResponse authenticateStaffUser(){
		String url = "/PAPIService/REST/protected/v1/1033/100/1/authenticator/staff";
		JSONObject authenticationData = new JSONObject();
		authenticationData.put("Domain", domain);
		authenticationData.put("Username", staffUsername);
		authenticationData.put("Password", staffPassword);
		String body = authenticationData.toString();

		WebServiceResponse authenticationResponse = callPolarisAPI(url, body, "POST", "application/json", null);
		if (!authenticationResponse.isSuccess()){
			logger.info("Authentication failed");
		}else{
			JSONObject authentication = authenticationResponse.getJSONResponse();
			try {
				accessToken = authentication.getString("AccessToken");
				accessSecret = authentication.getString("AccessSecret");
			}catch (JSONException e) {
				logEntry.incErrors("Could not authenticate the staff user.");
				authenticationResponse.setSuccess(false);
				logger.error(e);
				logger.error(authenticationResponse.getResponseCode() + " " + authenticationResponse.getMessage());
			}
		}
		return authenticationResponse;
	}

	private synchronized static String groupPolarisRecord(Record marcRecord) {
		return getRecordGroupingProcessor().processMarcRecord(marcRecord, true, null, getGroupedWorkIndexer());
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

	private static class ProcessBibRequestResponse{
		String lastId;
		boolean doneLoading = false;
		int numChanges = 0;
	}

	private static void checkForDeletedBibsInPolaris() {
		//Get the highest bib from Polaris
		logEntry.addNote("Starting to check for bibs that have been deleted in Polaris.");
		logEntry.saveResults();

		//noinspection SpellCheckingInspection
		WebServiceResponse maxBibResponse = callPolarisAPI("/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/maxid", null, "GET", "application/json", accessSecret);
		long maxBibId = -1;
		if (maxBibResponse.isSuccess()){
			maxBibId = maxBibResponse.getJSONResponse().getJSONArray("BibIDListRows").getJSONObject(0).getLong("BibliographicRecordID");
		}else{
			logEntry.addNote("Did not get a successful response when getting max bib in polaris, not deleting bibs");
			logEntry.saveResults();
			return;
		}

		//Get a list of all bib ids in Polaris
		HashSet<String> allBibsInPolaris = new HashSet<>();
		int startId = 0;
		int numberOfRecords = 1000;
		while (startId < maxBibId) {
			//noinspection SpellCheckingInspection
			WebServiceResponse getBibIdList = callPolarisAPI("/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/idlist?startId=" + startId + "&nrecs=" + numberOfRecords, null, "GET", "application/json", accessSecret);
			if (maxBibResponse.isSuccess()){
				JSONArray bibIdListRows = getBibIdList.getJSONResponse().getJSONArray("BibIDListRows");
				for (int i = 0; i < bibIdListRows.length(); i ++){
					JSONObject bibInfo = bibIdListRows.getJSONObject(i);
					long bibId = bibInfo.getLong("BibliographicRecordID");
					allBibsInPolaris.add(Long.toString(bibId));
				}
			}else{
				logEntry.addNote("Did not get a successful response when getting a list of all bibs in polaris, not deleting bibs");
				logEntry.saveResults();
				return;
			}

			startId += numberOfRecords;
		}
		logEntry.addNote("Loaded list of all bibs in Polaris.");
		logEntry.saveResults();

		HashSet<String> bibsToDelete = new HashSet<>();
		HashSet<String> allBibsInAspen = getRecordGroupingProcessor().loadExistingActiveIds(logEntry);
		logEntry.addNote("Loaded list of all bibs in Aspen.");
		logEntry.saveResults();

		if (allBibsInAspen != null) {
			if (allBibsInAspen.size() * .9 > allBibsInPolaris.size()){
				logEntry.addNote("The number of bibs in Polaris is less than 90% of the number of bibs in Aspen, not deleting bibs.");
				logEntry.saveResults();
				return;
			}

			for (String bibId : allBibsInAspen) {
				if (!allBibsInPolaris.contains(bibId)) {
					//This bib has been deleted
					bibsToDelete.add(bibId);
				} else {
					//Remove it faster to lookup information in the future
					allBibsInPolaris.remove(bibId);
				}
			}

			logEntry.addNote("Deleting " + bibsToDelete.size() + " bibs that no longer exist in Polaris.");
			if (bibsToDelete.size() > .1 * allBibsInAspen.size()) {
				logEntry.addNote("More than 10% of bibs were marked for deletion, not deleting.");
				logEntry.saveResults();
				return;
			}
			for (String bibToDelete : bibsToDelete) {
				getGroupedWorkIndexer().markIlsRecordAsDeleted(indexingProfile.getName(), bibToDelete);
				RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibToDelete);
				if (result.reindexWork) {
					getGroupedWorkIndexer().processGroupedWork(result.permanentId);
				} else if (result.deleteWork) {
					//Delete the work from solr and the database
					getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
				}
				logEntry.incDeleted();
			}
		}

		logEntry.saveResults();
	}
}