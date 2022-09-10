package org.aspendiscovery.evolve_export;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.commons.lang3.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.marc4j.MarcException;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.*;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

public class EvolveExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;
	private static String baseUrl;
	private static String integrationToken;
	private static String staffUsername;
	private static String staffPassword;

	private static Long startTimeForLogging;
	private static IlsExtractLogEntry logEntry;

	public static void main(String[] args) {
		boolean extractSingleWork = false;

		String singleWorkId = null;

		if (args.length == 0)
		//noinspection CommentedOutCode
		{
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			/*String extractSingleWorkResponse = AspenStringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleWork = true;
			}*/

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
		String profileToLoad;

		String processName = "evolve_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			java.util.Date startTime = new Date();
			logger.info(startTime + ": Starting Evolve Extract");
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

				PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = 'evolve'");
				ResultSet accountProfileRS = accountProfileStmt.executeQuery();
				if (accountProfileRS.next()){
					baseUrl = accountProfileRS.getString("patronApiUrl");
					profileToLoad = accountProfileRS.getString("recordSource");
					integrationToken = accountProfileRS.getString("oAuthClientSecret");
					staffUsername = accountProfileRS.getString("staffUsername");
					staffPassword = accountProfileRS.getString("staffPassword");
				}else{
					logEntry.incErrors("Could not load Evolve account profile");
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

				indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);
				if (indexingProfile == null){
					logEntry.incErrors("Could not load indexing profile for " + profileToLoad);
				}else {
					logEntry.setIsFullUpdate(indexingProfile.isRunFullUpdate());

					if (!extractSingleWork) {
						//Update works that have changed since the last index
						numChanges = updateRecords();
					}else{
						//TODO: API to update an individual record?
						numChanges = updateBibFromEvolve(singleWorkId);
					}
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
				logger.info(currentTime + ": Finished Evolve Extract");
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
	}

	private static int updateBibFromEvolve(@SuppressWarnings("unused") String singleWorkId) {
		logEntry.incErrors("Cannot extract Single Works from Evolve.");
		return 0;
	}

	private static int updateLastChangedBibsFromEvolve(MarcFactory marcFactory) {
		@SuppressWarnings("SpellCheckingInspection")
		SimpleDateFormat lastExtractTimeFormatter = new SimpleDateFormat("MMddyyyyHHmmss");
		long lastExtractTime;
		lastExtractTime = indexingProfile.getLastUpdateOfChangedRecords() * 1000;
		if (lastExtractTime == 0 || (indexingProfile.getLastUpdateOfAllRecords() > indexingProfile.getLastUpdateOfChangedRecords())) {
			//Give a small buffer (5 minute to account for server time differences)
			lastExtractTime = indexingProfile.getLastUpdateOfAllRecords() - 5 * 60 * 1000 ;
		}
		long sixtyDaysAgo = new Date().getTime() - (60 * 24 * 60 * 60 * 1000L);
		if (lastExtractTime < sixtyDaysAgo){
			lastExtractTime = sixtyDaysAgo;
		}
		String formattedExtractTime = lastExtractTimeFormatter.format(new Date(lastExtractTime));

		//The integration token does not allow catalog search - so we need to log in with a patron.
		String patronLoginUrl = baseUrl + "/Authenticate";
		@SuppressWarnings("SpellCheckingInspection")
		String patronLoginBody = "{\"APPTYPE\":\"CATALOG\",\"Token\":\"" + integrationToken + "\",\"Login\":\"" + staffUsername  + "\",\"Pwd\":\"" + staffPassword +"\"}";
		WebServiceResponse loginResponse = NetworkUtils.postToURL(patronLoginUrl, patronLoginBody, "application/json", null, logger);
		int numProcessed = 0;
		if (loginResponse.isSuccess()){
			JSONArray loginResponseData = loginResponse.getJSONResponseAsArray();
			JSONObject firstResponse = loginResponseData.getJSONObject(0);
			String accessToken = firstResponse.getString("LoginToken");

			//Get a list of holdings that have changed from the last update time
			String getChangedHoldingsUrl = baseUrl + "/Holding/Token=" + accessToken + "|ModifiedFromDTM=" + formattedExtractTime;
			WebServiceResponse changedHoldingsResponse = NetworkUtils.getURL(getChangedHoldingsUrl, logger);
			if (changedHoldingsResponse.isSuccess()) {
				String rawMessage = changedHoldingsResponse.getMessage();

				try {
					JSONArray responseAsArray = new JSONArray(rawMessage);
					for (int i = 0; i < responseAsArray.length(); i++) {
						JSONObject curItem = responseAsArray.getJSONObject(i);
						if (curItem.has("Status") && curItem.has("Message")) {
							//This really should be an error, but that would prevent incrementing the export date which will only make things worse.
							logEntry.addNote(curItem.getString("Message"));
							break;
						}

						try{
							if (curItem.isNull("ID")){
								//The item is not attached to an bib?
								continue;
							}
							String bibId = curItem.getString("ID");
							//We can't get the Marc record for an individual MARC, so we will load what we have and edit the correct item or insert it if we can't find it.
							Record marcRecord = getGroupedWorkIndexer().loadMarcRecordFromDatabase(indexingProfile.getName(), bibId, logEntry);
							if (marcRecord != null) {
								String itemBarcode = curItem.getString("Barcode");
								List<DataField> existingItemFields = marcRecord.getDataFields(indexingProfile.getItemTagInt());
								boolean isExistingItem = false;
								try{
									for (DataField existingItemField : existingItemFields){
										if (StringUtils.equals(existingItemField.getSubfield(indexingProfile.getBarcodeSubfield()).getData(), itemBarcode)){
											isExistingItem = true;
											MarcUtil.setSubFieldData(existingItemField, indexingProfile.getItemStatusSubfield(), curItem.getString("Status"), marcFactory);
											MarcUtil.setSubFieldData(existingItemField, indexingProfile.getCallNumberSubfield(), curItem.getString("CallNumber"), marcFactory);
											MarcUtil.setSubFieldData(existingItemField, indexingProfile.getBarcodeSubfield(), curItem.getString("Barcode"), marcFactory);
											if (curItem.isNull("DueDate")) {
												MarcUtil.setSubFieldData(existingItemField, indexingProfile.getDueDateSubfield(), "", marcFactory);
											}else{
												MarcUtil.setSubFieldData(existingItemField, indexingProfile.getDueDateSubfield(), curItem.getString("DueDate"), marcFactory);
											}
											MarcUtil.setSubFieldData(existingItemField, indexingProfile.getLocationSubfield(), curItem.getString("Location"), marcFactory);
											break;
										}
									}
									if (!isExistingItem){
										//Add a new field for the item
										DataField newItemField = marcFactory.newDataField(indexingProfile.getItemTag(), ' ', ' ');
										@SuppressWarnings("WrapperTypeMayBePrimitive")
										Double holdingId = curItem.getDouble("HoldingID");
										MarcUtil.setSubFieldData(newItemField, indexingProfile.getItemRecordNumberSubfield(), Integer.toString(holdingId.intValue()), marcFactory);
										MarcUtil.setSubFieldData(newItemField, indexingProfile.getItemStatusSubfield(), curItem.getString("Status"), marcFactory);
										MarcUtil.setSubFieldData(newItemField, indexingProfile.getCallNumberSubfield(), curItem.getString("CallNumber"), marcFactory);
										MarcUtil.setSubFieldData(newItemField, indexingProfile.getBarcodeSubfield(), curItem.getString("Barcode"), marcFactory);
										if (curItem.isNull("DueDate")) {
											MarcUtil.setSubFieldData(newItemField, indexingProfile.getDueDateSubfield(), "", marcFactory);
										}else{
											MarcUtil.setSubFieldData(newItemField, indexingProfile.getDueDateSubfield(), curItem.getString("DueDate"), marcFactory);
										}
										MarcUtil.setSubFieldData(newItemField, indexingProfile.getLocationSubfield(), curItem.getString("Location"), marcFactory);
										marcRecord.addVariableField(newItemField);
									}
								}catch (Exception e){
									logEntry.incErrors("Error updating item field", e);
								}
								logEntry.incUpdated();
								//Regroup the record
								String groupedWorkId = getRecordGroupingProcessor().processMarcRecord(marcRecord, true, null);
								//Reindex the record
								getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
							}
						}catch (JSONException e){
							logEntry.incErrors("Error processing item", e);
						}
					}
				}catch (JSONException e){
					logEntry.incErrors("Unable to parse JSON for loading changed holdings", e);
				}
			}

			String getBibUrl = baseUrl + "/CatalogSearch/Token=" + accessToken + "|ModifiedFromDTM=" + formattedExtractTime + "|Marc=Yes";
			//ProcessBibRequestResponse response = processGetBibsRequest(getBibUrl, marcFactory, true);

			WebServiceResponse getBibsResponse = NetworkUtils.getURL(getBibUrl, logger);
			if (getBibsResponse.isSuccess()) {
				String rawMessage = getBibsResponse.getMessage();

				try {
					JSONArray responseAsArray = new JSONArray(rawMessage);
					for (int i = 0; i < responseAsArray.length(); i++) {
						numProcessed++;
						JSONObject curRow = responseAsArray.getJSONObject(i);
						String rawMarc = curRow.getString("MARC");
						try {
							MarcReader reader = new MarcPermissiveStreamReader(new ByteArrayInputStream(rawMarc.getBytes(StandardCharsets.UTF_8)), true, false, "UTF-8");
							if (reader.hasNext()) //noinspection GrazieInspection
							{
								String bibId = curRow.getString("ID");
								Record marcRecord = reader.next();

								List<ControlField> controlFields = marcRecord.getControlFields();
								ArrayList<ControlField> controlFieldsCopy = new ArrayList<>(controlFields);
								for (ControlField controlField : controlFieldsCopy) {
									if (controlField.getData().startsWith("\u001f")) {
										marcRecord.removeVariableField(controlField);
										controlField.setData(controlField.getData().replaceAll("\u001f", ""));
										marcRecord.addVariableField(controlField);
									}
								}
								//The MARC data we get from the Evolve API does not include the bib number. Add that as the 950.
								DataField field950 = marcFactory.newDataField("950", ' ', ' ');
								field950.addSubfield(marcFactory.newSubfield('a', bibId));
								field950.addSubfield(marcFactory.newSubfield('b', "Evolve"));
								marcRecord.addVariableField(field950);
								//Also load holdings from the API
								String holdingsUrl = baseUrl + "/Holding/Token=" + accessToken + "|CatalogItem=" + bibId;
								WebServiceResponse getHoldingsResponse = NetworkUtils.getURL(holdingsUrl, logger);
								if (getHoldingsResponse.isSuccess()) {
									JSONArray holdingsData = getHoldingsResponse.getJSONResponseAsArray();
									if (holdingsData != null) {
										for (int j = 0; j < holdingsData.length(); j++) {
											JSONObject holding = holdingsData.getJSONObject(j);
											DataField field852 = marcFactory.newDataField("852", ' ', ' ');
											double holdingId = holding.getDouble("HoldingID");
											String formattedHoldingId = Double.toString(holdingId);
											if (formattedHoldingId.indexOf('.') > 0) {
												formattedHoldingId = formattedHoldingId.substring(0, formattedHoldingId.indexOf('.'));
											}
											field852.addSubfield(marcFactory.newSubfield('c', formattedHoldingId));
											field852.addSubfield(marcFactory.newSubfield('h', holding.getString("CallNumber")));
											field852.addSubfield(marcFactory.newSubfield('a', holding.getString("Location")));
											field852.addSubfield(marcFactory.newSubfield('k', holding.getString("Collection")));
											if (!holding.isNull("DueDate")) {
												field852.addSubfield(marcFactory.newSubfield('e', holding.getString("DueDate")));
											}
											field852.addSubfield(marcFactory.newSubfield('y', holding.getString("Form")));
											field852.addSubfield(marcFactory.newSubfield('d', holding.getString("Status")));

											//TODO: Add these once the API returns them
											//p - barcode
											//q - Cost
											//f - Created date time
											//g - Last scan date

											marcRecord.addVariableField(field852);
										}
									}
								}

								//Save the MARC record
								RecordIdentifier bibliographicRecordId = getRecordGroupingProcessor().getPrimaryIdentifierFromMarcRecord(marcRecord, indexingProfile);
								if (bibliographicRecordId != null) {
									GroupedWorkIndexer.MarcStatus saveMarcResult = getGroupedWorkIndexer().saveMarcRecordToDatabase(indexingProfile, bibliographicRecordId.getIdentifier(), marcRecord);
									if (saveMarcResult == GroupedWorkIndexer.MarcStatus.NEW) {
										logEntry.incAdded();
									} else {
										logEntry.incUpdated();
									}

									//Regroup the record
									String groupedWorkId = getRecordGroupingProcessor().processMarcRecord(marcRecord, true, null);
									if (groupedWorkId != null) {
										//Reindex the record
										getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
									}
								}
								if (logEntry.getNumProducts() > 0 && logEntry.getNumProducts() % 250 == 0) {
									getGroupedWorkIndexer().commitChanges();
									logEntry.saveResults();
								}
							}
						} catch (Exception e) {
							logEntry.incErrors("Error parsing marc record", e);
						}
					}
				}catch (JSONException e){
					logEntry.incErrors("Error parsing JSON from getting changed bib data");
				}
			}else{
				logEntry.incErrors("Error searching catalog for recently changed titles " + getBibsResponse.getResponseCode() + " " + getBibsResponse.getMessage());
			}

			//Also ask for holdings modified from a specific date

		}else{
			logEntry.incErrors("Could not connect to APIs with integration token " + loginResponse.getResponseCode() + " " + loginResponse.getMessage());
		}

		try {
			if (!logEntry.hasErrors()) {
				PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfChangedRecords = ? WHERE id = ?");
				updateVariableStmt.setLong(1, startTimeForLogging);
				updateVariableStmt.setLong(2, indexingProfile.getId());
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			}
		}catch (SQLException e){
			logEntry.incErrors("Error updating when the records were last indexed", e);
		}

		return numProcessed;

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

	private synchronized static String groupEvolveRecord(Record marcRecord) {
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
					String groupedWorkId = groupEvolveRecord(marcRecord);
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

		int totalChanges;

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
		}else{
			MarcFactory marcFactory = MarcFactory.newInstance();
			//Update records based on the last change date
			totalChanges = updateLastChangedBibsFromEvolve(marcFactory);
		}

		return totalChanges;
	}

	/**
	 * Updates Aspen using the MARC export or exports provided.
	 * To see which records are deleted it needs to get a list of all records that are already in the database, so it can detect what has been deleted.
	 *
	 * @param exportedMarcFiles - An array of files to process
	 * @param hasFullExportFile - Whether we are including a full export.  We will only delete records if we have a full export.
	 * @param fullExportFile    - The file containing the full export
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
						ArrayList<ControlField> updatedControlFields = new ArrayList<>();
						for (ControlField controlField : curBib.getControlFields()){
							//We're getting some extraneous separators that need to be removed, trim them off.
							controlField.setData(controlField.getData().replaceAll("\u001F", ""));
							updatedControlFields.add(controlField);
						}
						for (ControlField updatedControlField : updatedControlFields){
							curBib.addVariableField(updatedControlField);
						}
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
				logEntry.incErrors("Error loading Evolve bibs on record " + numRecordsRead + " in profile " + indexingProfile.getName() + " the last record processed was " + lastRecordProcessed + " file " + fullExportFile.getAbsolutePath(), e);
				logEntry.addNote("Not processing MARC export due to error reading MARC files.");
				return totalChanges;
			}
			if (((float) numRecordsWithErrors / (float) numRecordsRead) > 0.00025) {
				logEntry.incErrors("More than .025% of records had errors, skipping due to the volume of errors in " + indexingProfile.getName() + " file " + fullExportFile.getAbsolutePath());
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
				while (catalogReader.hasNext()) {
					logEntry.incProducts();
					try{
						Record curBib = catalogReader.next();
						ArrayList<ControlField> updatedControlFields = new ArrayList<>();
						for (ControlField controlField : curBib.getControlFields()){
							//We're getting some extraneous separators that need to be removed, trim them off.
							controlField.setData(controlField.getData().replaceAll("\u001F", ""));
							updatedControlFields.add(controlField);
						}
						for (ControlField updatedControlField : updatedControlFields){
							curBib.addVariableField(updatedControlField);
						}
						numRecordsRead++;
						RecordIdentifier recordIdentifier = recordGroupingProcessor.getPrimaryIdentifierFromMarcRecord(curBib, indexingProfile);
						if (hasFullExportFile && curBibFile.equals(fullExportFile) && (numRecordsRead < indexingProfile.getLastChangeProcessed())) {
							if (recordIdentifier != null) {
								recordGroupingProcessor.removeExistingRecord(recordIdentifier.getIdentifier());
							}
							logEntry.incSkipped();
						}else {
							boolean deleteRecord = false;
							if (recordIdentifier!= null && !recordIdentifier.isSuppressed()) {
								String recordNumber = recordIdentifier.getIdentifier();
								GroupedWorkIndexer.MarcStatus marcStatus;
								marcStatus = indexer.saveMarcRecordToDatabase(indexingProfile, recordNumber, curBib);

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
							if (deleteRecord && recordIdentifier != null) {
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
				if (!curBibFile.renameTo(new File(curBibFile + ".err"))){
					logEntry.incErrors("Could not rename file to error file "+ curBibFile+ ".err");
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
}
