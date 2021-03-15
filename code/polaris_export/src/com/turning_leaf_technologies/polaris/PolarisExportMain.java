package com.turning_leaf_technologies.polaris;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.commons.net.util.Base64;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONObject;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.MarcXmlReader;
import org.marc4j.marc.Record;
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
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.net.URLDecoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.Date;
import java.util.HashMap;
import java.util.Locale;

public class PolarisExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;

	private static Long startTimeForLogging;
	private static IlsExtractLogEntry logEntry;
	private static String webServiceUrl;
	private static String clientId;
	private static String clientSecret;
	private static String domain;
	private static String staffUsername;
	private static String staffPassword;
	private static String staffPAPIAccessID;
	private static String staffPAPIAccessKey;
	private static String accessToken;
	private static String accessSecret;

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

		String processName = "polaris_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");

		while (true) {
			java.util.Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info(startTime.toString() + ": Starting Polaris Extract");

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
				domain = ConfigUtil.cleanIniValue(configIni.get("Catalog", "domain"));
				staffUsername = ConfigUtil.cleanIniValue(configIni.get("Catalog", "staffUsername"));
				staffPassword = ConfigUtil.cleanIniValue(configIni.get("Catalog", "staffPassword"));
				staffPAPIAccessID = ConfigUtil.cleanIniValue(configIni.get("Catalog", "staffPAPIAccessId"));
				staffPAPIAccessKey = ConfigUtil.cleanIniValue(configIni.get("Catalog", "staffPAPIAccessKey"));
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

				if (loadAccountProfile(dbConn, "polaris")){
					indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);

					if (!extractSingleWork) {
						//updateBranchInfo(dbConn, kohaConn);
					}

					//Update works that have changed since the last index
					numChanges = updateRecords(dbConn, singleWorkId);
				}else{
					logEntry.incErrors("Could not load Account Profile");
				}

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

				logEntry.setFinished();

				Date currentTime = new Date();
				logger.info(currentTime.toString() + ": Finished Polaris Extract");
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
				Record marcRecord = MarcUtil.readIndividualRecord(marcFile, logEntry);
				if (marcRecord != null){
					logEntry.incRecordsRegrouped();
					//Regroup the record
					String groupedWorkId = groupPolarisRecord(marcRecord);
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

	private static boolean loadAccountProfile(Connection dbConn, String profileName) {
		//Get information about the account profile for Polaris
		try {
			PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE ils = ?");
			accountProfileStmt.setString(1, profileName);
			ResultSet accountProfileRS = accountProfileStmt.executeQuery();
			if (accountProfileRS.next()) {
				webServiceUrl = accountProfileRS.getString("patronApiUrl");
				if (webServiceUrl.endsWith("/")){
					webServiceUrl = webServiceUrl.substring(0, webServiceUrl.length() -1);
				}
				clientId = accountProfileRS.getString("oAuthClientId");
				clientSecret = accountProfileRS.getString("oAuthClientSecret");
			} else {
				logger.error("Could not find an account profile for Polaris stopping");
				System.exit(1);
			}
			return true;
		} catch (Exception e){
			logEntry.incErrors("Could not load account profile " + profileName + e);
			return false;
		}
	}

	private static int updateRecords(Connection dbConn, String singleWorkId) {
		int totalChanges = 0;

		try {
			//Get the time the last extract was done
			logger.info("Starting to load changed records from Polaris using the APIs");
			long lastExtractTime = indexingProfile.getLastUpdateOfChangedRecords();
			if (lastExtractTime == 0) {
				lastExtractTime = new Date().getTime() / 1000 - 24 * 60 * 60;
			}

			if (true || indexingProfile.isRunFullUpdate()){
				//Get all bibs
				totalChanges += extractAllBibs();
			}else{
				//Get updated bibs
				//Get deleted bibs
			}

			//Get a list of
		} catch (Exception e) {
			logEntry.incErrors("Error loading changed records from Polaris APIs", e);
			//Don't quit since that keeps the exporter from running continuously
		}
		logger.info("Finished loading changed records from Polaris APIs");

		return totalChanges;
	}

	private static int extractAllBibs() {
		int numChanges = 0;
		WebServiceResponse authenticationResponse = authenticateStaffUser();
		if (authenticationResponse.isSuccess()){
			//Get a paged list of all bibs
			String getBibsUrl = "/PAPIService/REST/protected/v1/1033/100/1/" + accessToken + "/synch/bibs/MARCXML/paged";
			WebServiceResponse pagedBibs = callPolarisAPI(getBibsUrl, null, "GET", "text/xml", accessSecret);
			if (pagedBibs.isSuccess()){
				try {
					Document pagedBibsDocument = createXMLDocumentForWebServiceResponse(pagedBibs);
					Element getBibsPagedResult = (Element)pagedBibsDocument.getFirstChild();
					Node lastIdNode = getBibsPagedResult.getElementsByTagName("LastID").item(0);
					String lastId = lastIdNode.getTextContent();
					Element getBibsPagedRows = (Element)getBibsPagedResult.getElementsByTagName("GetBibsPagedRows").item(0);
					NodeList bibsPagedRows = getBibsPagedRows.getElementsByTagName("GetBibsPagedRow");
					for (int i = 0; i < bibsPagedRows.getLength(); i++){
						Element bibPagedRow = (Element)bibsPagedRows.item(i);
						String bibliographicRecordId = bibPagedRow.getElementsByTagName("BibliographicRecordID").item(0).getTextContent();
						String displayInPAC = bibPagedRow.getElementsByTagName("IsDisplayInPAC").item(0).getTextContent();
						if (displayInPAC.equals("true")){
							String bibRecordXML = bibPagedRow.getElementsByTagName("BibliographicRecordXML").item(0).getTextContent();
							bibRecordXML = URLDecoder.decode(bibRecordXML, "UTF-8");
							MarcXmlReader marcXmlReader = new MarcXmlReader(new ByteArrayInputStream(bibRecordXML.getBytes(StandardCharsets.UTF_8)));
							Record marcRecord = marcXmlReader.next();

							if (marcRecord != null){
								//Save the file
								File marcFile = indexingProfile.getFileForIlsRecord(bibliographicRecordId);
								if (!marcFile.getParentFile().exists()) {
									//noinspection ResultOfMethodCallIgnored
									marcFile.getParentFile().mkdirs();
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
								String groupedWorkId = groupPolarisRecord(marcRecord);
								if (groupedWorkId != null) {
									//Reindex the record
									getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
								}
								numChanges++;
							}else{
								logEntry.incErrors("Could not read marc record for " + bibliographicRecordId);
							}
						}else{
							//Delete the record from SOLR
						}
					}
				} catch (Exception e) {
					logEntry.incErrors("Unable to parse document for paged bibs response", e);
				}
			}else{
				logEntry.incErrors("Could not get bibs from " + getBibsUrl + " " + pagedBibs.getMessage());
			}
		}else{
			logEntry.incErrors("Could not authenticate " + authenticationResponse.getMessage());
		}
		return numChanges;
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
			System.out.println("Error closing aspen connection: " + e.toString());
			e.printStackTrace();
		}
	}

	private static final String HMAC_SHA1_ALGORITHM = "HmacSHA1";
	private static DateTimeFormatter dateFormatter = DateTimeFormatter.ofPattern("EEE, dd MMM yyyy HH:mm:ss z", Locale.ENGLISH).withZone(ZoneId.of("GMT"));
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
			return NetworkUtils.postToURL(fullUrl, postData, contentType, null, logger, null, 10000, 300000, StandardCharsets.UTF_8, headers);
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
			accessToken = authentication.getString("AccessToken");
			accessSecret = authentication.getString("AccessSecret");
		}
		return authenticationResponse;
	}

	private static String groupPolarisRecord(Record marcRecord) {
		return getRecordGroupingProcessor().processMarcRecord(marcRecord, true, null);
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
