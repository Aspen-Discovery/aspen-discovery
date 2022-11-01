package com.turning_leaf_technologies.reindexer;

import com.jcraft.jsch.*;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.UnzipUtility;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import javax.xml.parsers.SAXParser;
import javax.xml.parsers.SAXParserFactory;
import java.io.*;
import java.sql.*;
import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.ZoneOffset;
import java.time.temporal.TemporalField;
import java.util.Date;
import java.util.TimeZone;

public class GroupedReindexMain {
	private static BaseIndexingLogEntry logEntry;
	private static Logger logger;

	//General configuration
	private static String serverName;
	@SuppressWarnings("FieldCanBeLocal")
	private static final String processName = "grouped_reindex";
	private static boolean fullReindex = false;
	private static boolean clearIndex = false;
	private static boolean isNightlyReindex = false;
	private static String individualWorkToProcess;
	private static Ini configIni;
	private static String baseLogPath;

	//Database connections and prepared statements
	private static Connection dbConn = null;

	/**
	 * Starts the re-indexing process
	 * 
	 * @param args String[] The server name to index with optional parameter for properties of indexing
	 */
	public static void main(String[] args) {
		// Get the configuration filename
		if (args.length == 0) {
			System.out.println("Please enter the server to index as the first parameter");
			System.exit(1);
		}
		serverName = args[0];

		boolean checkNightlyIndexRunning = false;
		if (args.length >= 2 && args[1].equalsIgnoreCase("full")) {
			fullReindex = true;
			clearIndex = true;
		}else if (args.length >= 2 && (args[1].equalsIgnoreCase("fullNoClear") || args[1].equalsIgnoreCase("nightly"))){
			fullReindex = true;
			clearIndex = false;
			isNightlyReindex = args[1].equalsIgnoreCase("nightly");
		}else if (args.length >= 2 && args[1].equalsIgnoreCase("isNightlyIndexRunning")){
			checkNightlyIndexRunning = true;
		}else if (args.length >= 2 && args[1].equalsIgnoreCase("singleWork")){
			//Process a specific work
			//Prompt for the work to process
			System.out.print("Enter the id of the work to process: ");

			//  open up standard input
			BufferedReader br = new BufferedReader(new InputStreamReader(System.in));

			//  read the work from the command-line; need to use try/catch with the
			//  readLine() method
			try {
				individualWorkToProcess = br.readLine().trim();
			} catch (IOException ioe) {
				System.out.println("IO error trying to read the work to process!");
				System.exit(1);
			}
		}
		
		initializeReindex();
		
		logEntry.addNote("Initialized Reindex ");
		if (checkNightlyIndexRunning) {
			boolean isNightlyIndexRunning = IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger);
			logEntry.addNote("Checked if nightly index is running: " + isNightlyIndexRunning);
			System.out.println("Is Nightly Index Running: " + isNightlyIndexRunning);
			logEntry.setFinished();
			System.exit(0);
		}
		if (fullReindex){
			logEntry.addNote("Performing full reindex");
		}

		//Process grouped works
		try {
			boolean regroupAllRecords = false;
			if (fullReindex){
				//Check to see if we should regroup all records
				try {
					PreparedStatement getRegroupAllRecordsStmt = dbConn.prepareStatement("SELECT regroupAllRecordsDuringNightlyIndex from system_variables");
					ResultSet regroupAllRecordsRS = getRegroupAllRecordsStmt.executeQuery();
					if (regroupAllRecordsRS.next()) {
						regroupAllRecords = regroupAllRecordsRS.getBoolean("regroupAllRecordsDuringNightlyIndex");
					}
					getRegroupAllRecordsStmt.close();
				} catch (Exception e) {
					logger.error("Unable to determine if we should regroup all records", e);
				}
			}

			GroupedWorkIndexer groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, fullReindex, clearIndex, regroupAllRecords, logEntry, logger);
			if (groupedWorkIndexer.isOkToIndex()) {
				if (individualWorkToProcess != null) {
					//Get more information about the work
					try {
						PreparedStatement getInfoAboutWorkStmt = dbConn.prepareStatement("SELECT * from grouped_work where permanent_id = ?");
						getInfoAboutWorkStmt.setString(1, individualWorkToProcess);
						ResultSet infoAboutWork = getInfoAboutWorkStmt.executeQuery();
						if (infoAboutWork.next()) {
							groupedWorkIndexer.setRegroupAllRecords(true);
							groupedWorkIndexer.processGroupedWork(infoAboutWork.getLong("id"), individualWorkToProcess, infoAboutWork.getString("grouping_category"));
							groupedWorkIndexer.setRegroupAllRecords(regroupAllRecords);
						} else {
							logger.error("Could not find a work with id " + individualWorkToProcess);
						}
						getInfoAboutWorkStmt.close();
					} catch (Exception e) {
						logger.error("Unable to process individual work " + individualWorkToProcess, e);
					}
				} else {
					logger.info("Running Reindex");
					groupedWorkIndexer.processGroupedWorks();
				}
				groupedWorkIndexer.finishIndexing();

			}
		} catch (Error e) {
			logEntry.incErrors("Error processing reindex " + e.toString());
		} catch (Exception e) {
			logEntry.incErrors("Exception processing reindex ", e);
		}

		logEntry.addNote("Finished Reindex for " + serverName);
		logEntry.setFinished();
	}

	private static void initializeReindex() {
		// Delete the existing reindex.log file
		File solrMarcLog = new File(baseLogPath + "/" + serverName + "/logs/grouped_reindex.log");
		if (solrMarcLog.exists()){
			if (!solrMarcLog.delete()){
				logger.warn("Could not remove " + solrMarcLog.toString());
			}
		}
		for (int i = 1; i <= 10; i++){
			solrMarcLog = new File(baseLogPath + "/" + serverName + "/logs/grouped_reindex.log." + i);
			if (solrMarcLog.exists()){
				if (!solrMarcLog.delete()){
					logger.warn("Could not remove " + solrMarcLog.toString());
				}
			}
		}
		solrMarcLog = new File("org.solrmarc.log");
		if (solrMarcLog.exists()){
			if (!solrMarcLog.delete()){
				logger.warn("Could not remove " + solrMarcLog.toString());
			}
		}
		for (int i = 1; i <= 4; i++){
			solrMarcLog = new File("org.solrmarc.log." + i);
			if (solrMarcLog.exists()){
				if (!solrMarcLog.delete()){
					logger.warn("Could not remove " + solrMarcLog.toString());
				}
			}
		}

		logger = LoggingUtil.setupLogging(serverName, processName);

		logger.info("Starting Reindex for " + serverName);

		// Parse the configuration file
		configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		baseLogPath = configIni.get("Site", "baseLogPath");
		String solrPort = configIni.get("Reindex", "solrPort");
		if (solrPort == null || solrPort.length() == 0) {
			logger.error("You must provide the port where the solr index is loaded in the import configuration file");
			System.exit(1);
		}

		logger.info("Setting up database connections");
		String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in Database Section.  Please specify connection information in database_aspen_jdbc.");
			System.exit(1);
		}
		try {
			dbConn = DriverManager.getConnection(databaseConnectionInfo);
			dbConn.prepareCall("SET collation_connection = utf8mb4_general_ci").execute();
			dbConn.prepareCall("SET NAMES utf8mb4").execute();
		} catch (SQLException e) {
			logger.error("Could not connect to aspen database", e);
			System.exit(1);
		}

		logEntry = new NightlyIndexLogEntry(dbConn, logger);

		//If this is the nightly index, check to see if we need to run
		if (isNightlyReindex) {
			boolean arDataReloaded = loadAcceleratedReaderData();
			if (!arDataReloaded) { //Force nightly update to run if AR data was reloaded
				try {
					logger.info("Checking to see if nightly index should run");
					PreparedStatement getRunNightlyIndexStmt = dbConn.prepareStatement("SELECT runNightlyFullIndex FROM system_variables");
					ResultSet getRunNightlyIndexRS = getRunNightlyIndexStmt.executeQuery();
					if (getRunNightlyIndexRS.next()){
						boolean runNightlyFullIndex = getRunNightlyIndexRS.getBoolean("runNightlyFullIndex");
						if (!runNightlyFullIndex){
							logEntry.addNote("Nightly index does not need to be run");
							logEntry.setFinished();
							System.exit(0);
						}
					}
					getRunNightlyIndexStmt.close();
				}catch (SQLException e) {
					logger.error("Unable to determine if the nightly index should run, running it", e);
				}
			}

			try {
				//Mark that nightly index does not need to run since we are currently running it.
				dbConn.prepareStatement("UPDATE system_variables set runNightlyFullIndex = 0").executeUpdate();
			}catch (SQLException e) {
				logger.error("Unable to determine if the nightly index should run, running it", e);
			}
		}
	}

	private static boolean loadAcceleratedReaderData(){
		boolean infoReloaded = false;
		try{
			PreparedStatement arSettingsStmt = dbConn.prepareStatement("SELECT * FROM accelerated_reading_settings");
			ResultSet arSettingsRS = arSettingsStmt.executeQuery();
			if (arSettingsRS.next()){
				long lastFetched = arSettingsRS.getLong("lastFetched");

				String arExportPath = arSettingsRS.getString("arExportPath");
				File localFile = new File(arExportPath + "/RLI-ARDATA-XML.ZIP");

				boolean reloadArData = false;
				if (lastFetched == 0){
					reloadArData = true;
				}else{
					int updateOn = arSettingsRS.getInt("updateOn");
					int updateFrequency = arSettingsRS.getInt("updateFrequency");

					//There is some variation in when the nightly index starts, we will only update if it is after 8pm or before 6am on the specified day.
					LocalDateTime today = LocalDateTime.now();
					if (updateOn == 0){ //Friday night, saturday morning
						if (today.getDayOfWeek() == DayOfWeek.FRIDAY && today.getHour() >= 12 || today.getDayOfWeek() == DayOfWeek.SATURDAY && today.getHour() < 6){
							reloadArData = true;
						}
					}else if (updateOn == 1){ //Saturday night, Sunday morning
						if (today.getDayOfWeek() == DayOfWeek.SATURDAY && today.getHour() >= 20 || today.getDayOfWeek() == DayOfWeek.SUNDAY && today.getHour() < 6){
							reloadArData = true;
						}
					}
					if (reloadArData){
						reloadArData = false;
						//It's the correct day to run, check the frequency.
						long todayInSecs = new Date().getTime() / 1000;
						long elapasedTime = todayInSecs - lastFetched;
						int daysElapsed = (int)Math.ceil((double)elapasedTime / (double)(24 * 60 * 60));
						logEntry.addNote("Correct day to run AR updates, checking if enough time has elapsed, " + daysElapsed + " have elapsed.");
						if (updateFrequency == 0) { //Weekly
							if (daysElapsed >= 7){
								reloadArData = true;
							}
						}else if (updateFrequency == 1) { //Bi-Weekly
							if (daysElapsed >= 14){
								reloadArData = true;
							}
						}else if (updateFrequency == 2) { //Monthly (technically every 4 weeks)
							if (daysElapsed >= 28){
								reloadArData = true;
							}
						}
					}
				}

				//Fetch the file if we have never updated or if we last updated more than a week ago
				boolean updateDB = false;
				//Use 23 hours rather than 24 hours to avoid the day accelerated reader loads doesn't drift.
				if (reloadArData){
					updateDB = true;
					logEntry.addNote("Fetching new Accelerated Reader Data");
					logEntry.saveResults();

					//Fetch the latest file from the SFTP server
					String ftpServer = arSettingsRS.getString("ftpServer");
					String ftpUser = arSettingsRS.getString("ftpUser");
					String ftpPassword = arSettingsRS.getString("ftpPassword");

					String remoteFile = "/RLI-ARDATA-XML.ZIP";

					JSch jsch = new JSch();
					Session session;
					try {
						session = jsch.getSession(ftpUser, ftpServer, 22);
						session.setConfig("StrictHostKeyChecking", "no");
						session.setPassword(ftpPassword);
						session.connect();

						Channel channel = session.openChannel("sftp");
						channel.connect();
						ChannelSftp sftpChannel = (ChannelSftp) channel;
						sftpChannel.get(remoteFile, new FileOutputStream(localFile));
						sftpChannel.exit();
						session.disconnect();

						logEntry.addNote("Retrieved new file from FTP server");
						logEntry.saveResults();

						if (localFile.exists()) {
							UnzipUtility.unzip(localFile.getPath(), arExportPath);
						}
					} catch (JSchException e) {
						logEntry.incErrors("JSch Error retrieving accelerated reader file from server", e);
					} catch (SftpException e) {
						logEntry.incErrors("Sftp Error retrieving accelerated reader file from server", e);
					}
				}else if (localFile.exists()){
					//If the last modification time is greater than now, update (to deal with multiple instances on the same server).
					if (localFile.lastModified() / 1000 > lastFetched){
						updateDB = true;
						logEntry.addNote("Updating AR Data because the file was last modified on the server since we last updated. ");
					}
				}

				if (localFile.exists() && updateDB) {
					PreparedStatement updateSettingsStmt = dbConn.prepareStatement("UPDATE accelerated_reading_settings SET lastFetched = ?");
					updateSettingsStmt.setLong(1, (new Date().getTime() / 1000));
					updateSettingsStmt.executeUpdate();

					logEntry.addNote("Updating Accelerated Reader Data");
					logEntry.saveResults();

					//Update the database
					//Load the ar_titles xml file
					File arTitles = new File(arExportPath + "/ar_titles.xml");
					loadAcceleratedReaderTitlesXMLFile(arTitles);

					//Load the ar_titles_isbn xml file
					File arTitlesIsbn = new File(arExportPath + "/ar_titles_isbn.xml");
					loadAcceleratedReaderTitlesIsbnXMLFile(arTitlesIsbn);

					logEntry.addNote("Done updating Accelerated Reader Data");
					logEntry.saveResults();
					infoReloaded = true;
				}

			}
		}catch (Exception e){
			logEntry.incErrors("Error loading accelerated reader data", e);
		}
		return infoReloaded;
	}

	private static void loadAcceleratedReaderTitlesIsbnXMLFile(File arTitlesIsbn) {
		try {
			logEntry.addNote("Loading ar isbns from " + arTitlesIsbn);
			logEntry.saveResults();

			SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
			SAXParser saxParser = saxParserFactory.newSAXParser();
			ArTitleIsbnsHandler handler = new ArTitleIsbnsHandler(dbConn, logger);
			saxParser.parse(arTitlesIsbn, handler);
		} catch (Exception e) {
			logEntry.incErrors("Error parsing Accelerated Reader Title data ", e);
		}
	}

	private static void loadAcceleratedReaderTitlesXMLFile(File arTitles) {
		try {
			logEntry.addNote("Loading ar titles from " + arTitles);
			logEntry.saveResults();

			SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
			SAXParser saxParser = saxParserFactory.newSAXParser();
			ArTitlesHandler handler = new ArTitlesHandler(dbConn, logger);
			saxParser.parse(arTitles, handler);
		} catch (Exception e) {
			logEntry.incErrors("Error parsing Accelerated Reader Title data ", e);
		}
	}
}
