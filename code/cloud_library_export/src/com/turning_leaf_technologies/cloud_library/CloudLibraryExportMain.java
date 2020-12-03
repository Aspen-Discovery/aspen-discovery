package com.turning_leaf_technologies.cloud_library;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;

import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;

import org.ini4j.Ini;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.grouping.RecordGroupingProcessor;
import org.xml.sax.SAXException;

import javax.xml.parsers.*;
import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.security.InvalidKeyException;
import java.security.NoSuchAlgorithmException;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Base64;
import java.util.Date;
import java.util.HashMap;
import java.util.TimeZone;

public class CloudLibraryExportMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Long startTimeForLogging;
	private static CloudLibraryExtractLogEntry logEntry;

	//SQL Statements
	private static PreparedStatement deleteCloudLibraryItemStmt;
	private static PreparedStatement getAllExistingCloudLibraryItemsStmt;

	//Record grouper
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//Existing records
	private static HashMap<String, CloudLibraryTitle> existingRecords = new HashMap<>();

	private static Connection aspenConn;

	private static String baseUrl;
	private static String accountId;
	private static String accountKey;
	private static String libraryId;

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

		String processName = "cloud_library_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long recordGroupingChecksumAtStart = JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar");

		while (true) {

			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info("Starting " + processName + ": " + startTime.toString());

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			aspenConn = connectToDatabase();

			createDbLogEntry(startTime, aspenConn);

			//Get a list of all existing records in the database
			loadExistingTitles();

			//Do the actual work here
			int numChanges = extractCloudLibraryData();

			//For any records that have been marked to reload, regroup and reindex the records
			processRecordsToReload(logEntry);

			if (recordGroupingProcessorSingleton != null) {
				recordGroupingProcessorSingleton.close();
				recordGroupingProcessorSingleton = null;
			}

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract(logEntry);
				groupedWorkIndexer.close();
				groupedWorkIndexer = null;
				existingRecords = null;
			}

			if (logEntry.hasErrors()) {
				logger.error("There were errors during the export!");
			}

			logger.info("Finished " + new Date().toString());
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime.getTime();
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			logEntry.setFinished();

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}

			//Disconnect from the database
			disconnectDatabase(aspenConn);

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				//Quit and we will restart after if finishes
				System.exit(0);
			}else {
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
		}
	}

	private static void processRecordsToReload(CloudLibraryExtractLogEntry logEntry) {
		try {
			PreparedStatement getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type='cloud_library'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			PreparedStatement getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT title, subTitle, author, format from cloud_library_title where cloudLibraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()){
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String cloudLibraryId = getRecordsToReloadRS.getString("identifier");
				//Regroup the record
				getItemDetailsForRecordStmt.setString(1, cloudLibraryId);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String title = getItemDetailsForRecordRS.getString("title");
					String subTitle = getItemDetailsForRecordRS.getString("subTitle");
					String author = getItemDetailsForRecordRS.getString("author");
					String format = getItemDetailsForRecordRS.getString("format");
					RecordIdentifier primaryIdentifier = new RecordIdentifier("cloud_library", cloudLibraryId);


					String groupedWorkId = getRecordGroupingProcessor().processRecord(primaryIdentifier, title, subTitle, author, format, true);
					//Reindex the record
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

					markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
					markRecordToReloadAsProcessedStmt.executeUpdate();
					numRecordsToReloadProcessed++;
				}else{
					logEntry.incErrors("Could not get details for record to reload " + cloudLibraryId);
				}
				getItemDetailsForRecordRS.close();
			}
			if (numRecordsToReloadProcessed > 0){
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " records marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error processing records to reload " + e.toString());
		}
	}

	private static int deleteItems() {
		int numDeleted = 0;
		try {
			for (CloudLibraryTitle cloudLibraryTitle : existingRecords.values()) {
				if (!cloudLibraryTitle.isDeleted()) {
					deleteCloudLibraryItemStmt.setLong(1, cloudLibraryTitle.getId());
					deleteCloudLibraryItemStmt.executeUpdate();
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("cloud_library", cloudLibraryTitle.getCloudLibraryId());
					if (result.reindexWork) {
						getGroupedWorkIndexer().processGroupedWork(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
					}
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old titles");
			}
		} catch (SQLException e) {
			logger.error("Error deleting items", e);
			logEntry.addNote("Error deleting items " + e.toString());
		}
		return numDeleted;
	}

	private static void loadExistingTitles() {
		try {
			if (existingRecords == null) existingRecords = new HashMap<>();
			ResultSet allRecordsRS = getAllExistingCloudLibraryItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				String cloudLibraryId = allRecordsRS.getString("cloudLibraryId");
				CloudLibraryTitle newTitle = new CloudLibraryTitle(
						allRecordsRS.getLong("id"),
						cloudLibraryId,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("deleted")
				);
				existingRecords.put(cloudLibraryId, newTitle);
			}
		} catch (SQLException e) {
			logger.error("Error loading existing titles", e);
			logEntry.addNote("Error loading existing titles" + e.toString());
			System.exit(-1);
		}
	}

	private static int extractCloudLibraryData() {
		int numChanges = 0;

		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from cloud_library_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				numSettings++;
				baseUrl = getSettingsRS.getString("apiUrl");
				accountId = getSettingsRS.getString("accountId");
				accountKey = getSettingsRS.getString("accountKey");
				libraryId = getSettingsRS.getString("libraryId");
				boolean doFullReload = getSettingsRS.getBoolean("runFullUpdate");
				long lastExtractTime = getSettingsRS.getLong("lastUpdateOfChangedRecords");
				long lastExtractTimeAll = getSettingsRS.getLong("lastUpdateOfAllRecords");

				long settingsId = getSettingsRS.getLong("id");
				String startDate = "2000-01-01";
				if (!doFullReload) {
					lastExtractTime = Math.max(lastExtractTime, lastExtractTimeAll);

					SimpleDateFormat dateFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
					dateFormatter.setTimeZone(TimeZone.getTimeZone("GMT"));
					startDate = dateFormatter.format(new Date(lastExtractTime * 1000));
				}

				CloudLibraryMarcHandler handler = new CloudLibraryMarcHandler(existingRecords, doFullReload, startTimeForLogging, aspenConn, getRecordGroupingProcessor(), getGroupedWorkIndexer(), logEntry, logger);

				int curOffset = 1;
				boolean moreRecords = true;
				while (moreRecords) {
					moreRecords = false;
					//Get a list of eBooks and eAudiobooks to process
					String apiPath = "/cirrus/library/" + libraryId + "/data/marc?offset=" + curOffset + "&limit=50&startdate=" + startDate;

					//noinspection ConstantConditions
					for (int curTry = 1; curTry <= 4; curTry++) {
						WebServiceResponse response = callCloudLibrary(apiPath);
						if (response == null) {
							//Something really bad happened, we're done.
							return numChanges;
						} else if (!response.isSuccess()) {
							if (response.getResponseCode() != 502) {
								logEntry.incErrors("Error " + response.getResponseCode() + " calling " + apiPath + ": " + response.getMessage());
								break;
							} else {
								if (curTry == 4) {
									logEntry.incErrors("Error " + response.getResponseCode() + " calling " + apiPath + ": " + response.getMessage());
									logEntry.addNote(response.getMessage());
									break;
								} else {
									try {
										Thread.sleep(1000);
									} catch (InterruptedException e) {
										logger.error("Thread was interrupted while waiting to retry for cloud library");
									}
								}
							}
						} else {
							try {
								SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
								SAXParser saxParser = saxParserFactory.newSAXParser();
								saxParser.parse(new ByteArrayInputStream(response.getMessage().getBytes(StandardCharsets.UTF_8)), handler);

								if (handler.getNumDocuments() > 0) {
									curOffset += handler.getNumDocuments();
									numChanges += handler.getNumDocuments();
									moreRecords = true;
								}
								logEntry.saveResults();
							} catch (SAXException | ParserConfigurationException | IOException e) {
								logger.error("Error parsing response", e);
								logEntry.addNote("Error parsing response: " + e.toString());
							}
							break;
						}
					}
				}

				//Handle events to determine status changes when the bibs don't change.
				if (!doFullReload) {
					String eventsApiPath = "/cirrus/library/" + libraryId + "/data/cloudevents?startdate=" + startDate;
					CloudLibraryEventHandler eventHandler = new CloudLibraryEventHandler(doFullReload, startTimeForLogging, aspenConn, getRecordGroupingProcessor(), getGroupedWorkIndexer(), logEntry, logger);
					//noinspection ConstantConditions
					for (int curTry = 1; curTry <= 4; curTry++) {
						WebServiceResponse response = callCloudLibrary(eventsApiPath);
						if (response == null) {
							//Something really bad happened, we're done.
							return numChanges;
						} else if (!response.isSuccess()) {
							if (response.getResponseCode() != 502) {
								logEntry.incErrors("Error " + response.getResponseCode() + " calling " + eventsApiPath + ": " + response.getMessage());
								break;
							} else {
								if (curTry == 4) {
									logEntry.incErrors("Error " + response.getResponseCode() + " calling " + eventsApiPath + ": " + response.getMessage());
									break;
								} else {
									try {
										Thread.sleep(1000);
									} catch (InterruptedException e) {
										logger.error("Thread was interrupted while waiting to retry for cloud library");
									}
								}
							}
						} else {
							try {
								SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
								SAXParser saxParser = saxParserFactory.newSAXParser();
								saxParser.parse(new ByteArrayInputStream(response.getMessage().getBytes(StandardCharsets.UTF_8)), eventHandler);

								if (handler.getNumDocuments() > 0) {
									numChanges += handler.getNumDocuments();
								}
								logEntry.saveResults();
							} catch (SAXException | ParserConfigurationException | IOException e) {
								logger.error("Error parsing response", e);
								logEntry.addNote("Error parsing response: " + e.toString());
							}
							break;
						}
					}
				}

				if (doFullReload && !logEntry.hasErrors()) {
					//Un mark that a full update needs to be done
					PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE cloud_library_settings set runFullUpdate = 0 where id = ?");
					updateSettingsStmt.setLong(1, settingsId);
					updateSettingsStmt.executeUpdate();

					//Mark any records that no longer exist in search results as deleted, but only if we are doing a full update
					numChanges += deleteItems();
				}

				//Update the last time we ran the update in settings.  This is always done since Cloud Library has some expected errors.
				PreparedStatement updateExtractTime;
				String columnToUpdate = "lastUpdateOfChangedRecords";
				if (doFullReload) {
					columnToUpdate = "lastUpdateOfAllRecords";
				}
				updateExtractTime = aspenConn.prepareStatement("UPDATE cloud_library_settings set " + columnToUpdate + " = ? WHERE id = ?");
				updateExtractTime.setLong(1, startTimeForLogging);
				updateExtractTime.setLong(2, settingsId);
				updateExtractTime.executeUpdate();

				logger.info("Updated or added " + numChanges + " records");
			}
			if (numSettings == 0) {
				logger.error("Unable to find settings for CloudLibrary, please add settings to the database");
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database");
		}
		return numChanges;
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private static RecordGroupingProcessor getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static Connection connectToDatabase() {
		Connection aspenConn = null;
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo != null) {
				aspenConn = DriverManager.getConnection(databaseConnectionInfo);
				deleteCloudLibraryItemStmt = aspenConn.prepareStatement("UPDATE cloud_library_title SET deleted = 1 where id = ?");
				getAllExistingCloudLibraryItemsStmt = aspenConn.prepareStatement("SELECT id, cloudLibraryId, rawChecksum, deleted from cloud_library_title");
			} else {
				logger.error("Aspen database connection information was not provided");
				System.exit(1);
			}

		} catch (Exception e) {
			logger.error("Error connecting to aspen database", e);
			System.exit(1);
		}
		return aspenConn;
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from cloud_library_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		logEntry = new CloudLibraryExtractLogEntry(aspenConn, logger);
	}

	static CloudLibraryAvailability loadAvailabilityForRecord(String cloudLibraryId) {
		CloudLibraryAvailability availability = new CloudLibraryAvailability();
		String apiPath = "/cirrus/library/" + libraryId + "/item/summary/" + cloudLibraryId;

		WebServiceResponse response = callCloudLibrary(apiPath);
		if (response == null) {
			//Something really bad happened, we're done.
			return null;
		} else if (!response.isSuccess()) {
			if (response.getResponseCode() != 500) {
				logEntry.incErrors("Error " + response.getResponseCode() + " calling " + apiPath + ": " + response.getMessage());
			}
			logEntry.addNote("Error getting availability from " + apiPath + ": " + response.getResponseCode() + " " + response.getMessage());
			return null;
		} else {
			availability.setRawResponse(response.getMessage());
			CloudLibraryAvailabilityHandler handler = new CloudLibraryAvailabilityHandler(availability);

			try {
				SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
				SAXParser saxParser = saxParserFactory.newSAXParser();
				saxParser.parse(new ByteArrayInputStream(response.getMessage().getBytes(StandardCharsets.UTF_8)), handler);
			} catch (SAXException | ParserConfigurationException | IOException e) {
				logger.error("Error parsing response", e);
				logEntry.addNote("Error parsing response: " + e.toString());
			}
		}

		return availability;
	}

	private static WebServiceResponse callCloudLibrary(String apiPath) {
		String bookUrl = baseUrl + apiPath;
		HashMap<String, String> headers = new HashMap<>();
		SimpleDateFormat dateFormatter = new SimpleDateFormat("EEE, dd MMM yyyy HH:mm:ss z");
		dateFormatter.setTimeZone(TimeZone.getTimeZone("GMT"));
		String formattedDate = dateFormatter.format(new Date());

		String dataToSign = formattedDate + "\nGET\n" + apiPath;
		String signature;
		try {
			javax.crypto.Mac mac = javax.crypto.Mac.getInstance("hmacSHA256");
			mac.init(new javax.crypto.spec.SecretKeySpec(accountKey.getBytes(), "HmacSHA1"));
			mac.update(dataToSign.getBytes());
			signature = Base64.getEncoder().encodeToString(mac.doFinal());
		} catch (NoSuchAlgorithmException noSuchAlgorithmException) {
			logger.error("No algorithm found when creating signature", noSuchAlgorithmException);
			return null;
		} catch (InvalidKeyException e) {
			logger.error("Invalid Key", e);
			return null;
		}

		headers.put("3mcl-Datetime", formattedDate);
		headers.put("3mcl-Authorization", "3MCLAUTH " + accountId + ":" + signature);
		headers.put("3mcl-APIVersion", "3.0");
		return NetworkUtils.getURL(bookUrl, logger, headers);
	}
}
