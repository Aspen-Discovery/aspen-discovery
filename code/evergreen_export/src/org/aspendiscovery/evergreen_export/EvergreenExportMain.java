package org.aspendiscovery.evergreen_export;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.commons.net.util.Base64;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONObject;
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
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
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


						}

						//Update works that have changed since the last index
						numChanges = updateRecords(singleWorkId);
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

	private static PreparedStatement existingAspenLocationStmt;
	private static PreparedStatement existingAspenLibraryStmt;
	private static PreparedStatement addAspenLibraryStmt;
	private static PreparedStatement addAspenLocationStmt;
	private static PreparedStatement addAspenLocationRecordsOwnedStmt;
	private static PreparedStatement addAspenLocationRecordsToIncludeStmt;
	private static PreparedStatement addAspenLibraryRecordsOwnedStmt;
	private static PreparedStatement addAspenLibraryRecordsToIncludeStmt;
	private static void updateBranchInfo(Connection dbConn) {
		//Setup our prepared statements
		try {
			existingAspenLocationStmt = dbConn.prepareStatement("SELECT libraryId, locationId, isMainBranch from location where code = ?");
			existingAspenLibraryStmt = dbConn.prepareStatement("SELECT libraryId from library where ilsCode = ?");
			addAspenLibraryStmt = dbConn.prepareStatement("INSERT INTO library (subdomain, displayName, ilsCode, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, 1, 1)", Statement.RETURN_GENERATED_KEYS);
			addAspenLocationStmt = dbConn.prepareStatement("INSERT INTO location (libraryId, displayName, code, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (?, ?, ?, -1, -1)", Statement.RETURN_GENERATED_KEYS);
			addAspenLocationRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO location_records_owned (locationId, indexingProfileId, location, subLocation) VALUES (?, ?, ?, '')");
			addAspenLocationRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO location_records_to_include (locationId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
			addAspenLibraryRecordsOwnedStmt = dbConn.prepareStatement("INSERT INTO library_records_owned (libraryId, indexingProfileId, location, subLocation) VALUES (?, ?, ?, '') ON DUPLICATE KEY UPDATE location = CONCAT(location, '|', VALUES(location))");
			addAspenLibraryRecordsToIncludeStmt = dbConn.prepareStatement("INSERT INTO library_records_to_include (libraryId, indexingProfileId, location, subLocation, weight) VALUES (?, ?, '.*', '', 1)");
		}catch (Exception e){
			logEntry.incErrors("Could not setup database statements to update branch information", e);
			return;
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
						loadOrganizationalUnit(subPayload, 0, 0);
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
	private static void loadOrganizationalUnit(JSONArray orgUnitPayload, int level, long parentId) {
		HashMap<String, Object> mappedOrgUnitField = mapFields(orgUnitPayload, orgUnitFields);
		if (level == 0){
			//This is the top level unit, it is not written to Aspen, just process all the children
			JSONArray children = (JSONArray) mappedOrgUnitField.get("children");
			for (int i = 0; i < children.length(); i++){
				JSONObject curObject = children.getJSONObject(i);
				if (curObject.has("__p")){
					JSONArray libraryUnitPayload = curObject.getJSONArray("__p");
					loadOrganizationalUnit(libraryUnitPayload, level +1, 0);
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
					existingLibraryRS.getLong("libraryId");
				}
			}catch (Exception e){
				logEntry.incErrors("Error adding library " + mappedOrgUnitField.get("shortname") + " to Aspen", e);
			}

			JSONArray children = (JSONArray) mappedOrgUnitField.get("children");
			for (int i = 0; i < children.length(); i++){
				JSONObject curObject = children.getJSONObject(i);
				if (curObject.has("__p")){
					JSONArray libraryUnitPayload = curObject.getJSONArray("__p");
					loadOrganizationalUnit(libraryUnitPayload, level +1, libraryId);
				}
			}

		}else if(level == 2){
			//This is a branch, add it to the system
			try {
				String shortName = (String) mappedOrgUnitField.get("shortname");
				existingAspenLocationStmt.setString(1, shortName);
				ResultSet existingLocationRS = existingAspenLocationStmt.executeQuery();
				if (!existingLocationRS.next()){
					addAspenLocationStmt.setLong(1, parentId);
					addAspenLocationStmt.setString(2, StringUtils.trimTo(60, (String)mappedOrgUnitField.get("name")));
					addAspenLocationStmt.setString(3, shortName);

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

	private static int updateRecords(String singleWorkId) {
		int totalChanges = 0;

		try {
			//Get the time the last extract was done
			logger.info("Starting to load changed records from Evergreen using the APIs");

			if (singleWorkId != null){
				//TODO: Single work indexing
				updateBibFromEvergreen(singleWorkId, null, 0, true);
			}else {
				long lastExtractTime = 0;
				if (!indexingProfile.isRunFullUpdate()) {
					lastExtractTime = indexingProfile.getLastUpdateOfChangedRecords();
					if (lastExtractTime == 0 || (indexingProfile.getLastUpdateOfAllRecords() > indexingProfile.getLastUpdateOfChangedRecords())) {
						//Give a small buffer (1 minute to account for server time differences)
						lastExtractTime = indexingProfile.getLastUpdateOfAllRecords() - 60 * 1000 ;
					}
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
				totalChanges += updateBibsFromEvergreen(lastExtractTime, true);
				if (!indexingProfile.isRunFullUpdate()) {
					//Process deleted bibs
					//TODO: extract deleted bibs
					//totalChanges += extractDeletedBibs(lastExtractTime);
				} else {
					//Loop through remaining records and delete them
					if (allowDeletingExistingRecords) {
						logEntry.addNote("Starting to delete records that no longer exist");
						GroupedWorkIndexer groupedWorkIndexer = getGroupedWorkIndexer();
						MarcRecordGrouper recordGroupingProcessor = getRecordGroupingProcessor();
						for (String ilsId : recordGroupingProcessor.getExistingRecords().keySet()) {
							RemoveRecordFromWorkResult result = recordGroupingProcessor.removeRecordFromGroupedWork(indexingProfile.getName(), ilsId);
							if (result.permanentId != null) {
								if (result.reindexWork) {
									groupedWorkIndexer.processGroupedWork(result.permanentId);
								} else if (result.deleteWork) {
									//Delete the work from solr and the database
									groupedWorkIndexer.deleteRecord(result.permanentId);
								}
								logEntry.incDeleted();
								if (logEntry.getNumDeleted() % 250 == 0) {
									logEntry.saveResults();
								}
							}
						}
						logEntry.addNote("Finished deleting records that no longer exist");
					}else{
						logEntry.addNote("Skipping deleting records that no longer exist because we skipped some records at the start");
					}
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error loading changed records from Evergreen APIs", e);
			//Don't quit since that keeps the exporter from running continuously
		}
		logger.info("Finished loading changed records from Evergreen APIs");

		return totalChanges;
	}

	private static int updateBibFromEvergreen(String bibNumber, MarcFactory marcFactory, long lastExtractTime, boolean incrementProductsInLog) {
		//Get the bib record
		//noinspection SpellCheckingInspection
		String getBibUrl = baseUrl + "/opac/extras/supercat/retrieve/marcxml-full/" + bibNumber;
		ProcessBibRequestResponse response = processGetBibsRequest(getBibUrl, marcFactory, lastExtractTime, incrementProductsInLog);
		return response.numChanges;
	}

	private static int updateBibsFromEvergreen(long lastExtractTime, boolean incrementProductsInLog) throws UnsupportedEncodingException {
		int numChanges = 0;

		MarcFactory marcFactory = MarcFactory.newInstance();

		String getBibUrl = baseUrl + "/opac/extras/feed/freshmeat/marcxml-full/biblio/import/50";
		ProcessBibRequestResponse response = processGetBibsRequest(getBibUrl, marcFactory, lastExtractTime, true);



		return numChanges;
	}

	private static void processEvergreenBibAndReindex(MarcFactory marcFactory, long lastExtractTime, boolean incrementProductsInLog, ProcessBibRequestResponse response, NodeList bibsPagedRows, int i) {
		if (incrementProductsInLog) {
			logEntry.incProducts();
		}
	}

	private static ProcessBibRequestResponse processGetBibsRequest(String getBibsRequestUrl, MarcFactory marcFactory, long lastExtractTime, boolean incrementProductsInLog) {
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
										String leader = curElement.getTextContent().trim();
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
														String itemId = curCopy.getAttribute("id");
														itemId = itemId.substring(itemId.lastIndexOf('/') + 1, itemId.length());
														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getItemRecordNumberSubfield(), itemId));
														String createDate = curCopy.getAttribute("create_date");
														createDate = createDate.substring(0, createDate.indexOf("T"));
														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getDateCreatedSubfield(), createDate));
														//String holdable = curCopy.getAttribute("holdable");
														//TODO: Figure out where the holdable flag should go
														//TODO: Do we need to load circulate, ref, or deposit flags?
														String barcode = curCopy.getAttribute("barcode");
														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getBarcodeSubfield(), barcode));
														String itemType = curCopy.getAttribute("circ_modifier");
														curItemField.addSubfield(marcFactory.newSubfield(indexingProfile.getITypeSubfield(), itemType));

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
						response.numChanges++;
					}
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
}
