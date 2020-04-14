package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import java.io.*;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Date;

public class GroupedReindexMain {

	private static Logger logger;

	//General configuration
	private static String serverName;
	@SuppressWarnings("FieldCanBeLocal")
	private static String processName = "grouped_reindex";
	private static boolean fullReindex = false;
	private static boolean clearIndex = false;
	private static String individualWorkToProcess;
	private static Ini configIni;
	private static String baseLogPath;

	//Reporting information
	private static long reindexLogId;
	private static long startTime;
	private static long endTime;
	private static PreparedStatement addNoteToReindexLogStmt;

	//Database connections and prepared statements
	private static Connection dbConn = null;

	/**
	 * Starts the re-indexing process
	 * 
	 * @param args String[] The server name to index with optional parameter for properties of indexing
	 */
	public static void main(String[] args) {
		startTime = new Date().getTime();
		// Get the configuration filename
		if (args.length == 0) {
			System.out.println("Please enter the server to index as the first parameter");
			System.exit(1);
		}
		serverName = args[0];
		System.setProperty("reindex.process.serverName", serverName);
		
		if (args.length >= 2 && args[1].equalsIgnoreCase("full")) {
			fullReindex = true;
			clearIndex = true;
		}else if (args.length >= 2 && args[1].equalsIgnoreCase("fullNoClear")){
			fullReindex = true;
			clearIndex = false;
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
		
		addNoteToReindexLog("Initialized Reindex ");
		if (fullReindex){
			addNoteToReindexLog("Performing full reindex");
		}
		
		//Process grouped works
		long numWorksProcessed = 0;
		try {
			GroupedWorkIndexer groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, fullReindex, clearIndex, individualWorkToProcess != null, logger);
			if (groupedWorkIndexer.isOkToIndex()) {
				if (individualWorkToProcess != null) {
					//Get more information about the work
					try {
						PreparedStatement getInfoAboutWorkStmt = dbConn.prepareStatement("SELECT * from grouped_work where permanent_id = ?");
						getInfoAboutWorkStmt.setString(1, individualWorkToProcess);
						ResultSet infoAboutWork = getInfoAboutWorkStmt.executeQuery();
						if (infoAboutWork.next()) {
							groupedWorkIndexer.processGroupedWork(infoAboutWork.getLong("id"), individualWorkToProcess, infoAboutWork.getString("grouping_category"));
						} else {
							logger.error("Could not find a work with id " + individualWorkToProcess);
						}
						getInfoAboutWorkStmt.close();
					} catch (Exception e) {
						logger.error("Unable to process individual work " + individualWorkToProcess, e);
					}
				} else {
					logger.info("Running Reindex");
					numWorksProcessed = groupedWorkIndexer.processGroupedWorks();
				}
				groupedWorkIndexer.finishIndexing();

			}
		} catch (Error e) {
			logger.error("Error processing reindex ", e);
			addNoteToReindexLog("Error processing reindex " + e.toString());
		} catch (Exception e) {
			logger.error("Exception processing reindex ", e);
			addNoteToReindexLog("Exception processing reindex " + e.toString());
		}

		// Send completion information
		endTime = new Date().getTime();
		sendCompletionMessage(numWorksProcessed);
		
		addNoteToReindexLog("Finished Reindex for " + serverName);
		logger.info("Finished Reindex for " + serverName);
		long endTime = new Date().getTime();
		long elapsedTime = endTime - startTime;
		logger.info("Elapsed Minutes " + (elapsedTime / 60000));
	}

	private static StringBuffer reindexNotes = new StringBuffer();
	private static SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	static void addNoteToReindexLog(String note) {
		if (addNoteToReindexLogStmt == null){
			//This happens when called from another system (i.e. from Sierra Export)
			return;
		}
		try {
			Date date = new Date();
			reindexNotes.append("<br>").append(dateFormat.format(date)).append(note);
			addNoteToReindexLogStmt.setString(1, StringUtils.trimTo(65535, reindexNotes.toString()));
			addNoteToReindexLogStmt.setLong(2, new Date().getTime() / 1000);
			addNoteToReindexLogStmt.setLong(3, reindexLogId);
			addNoteToReindexLogStmt.executeUpdate();
			logger.info(note);
		} catch (SQLException e) {
			logger.error("Error adding note to Reindex Log", e);
		}
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
		} catch (SQLException e) {
			logger.error("Could not connect to aspen database", e);
			System.exit(1);
		}

		//Start a reindex log entry
		try {
			logger.info("Creating log entry for index");
			PreparedStatement createLogEntryStatement = dbConn.prepareStatement("INSERT INTO reindex_log (startTime, lastUpdate, notes) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			createLogEntryStatement.setLong(1, new Date().getTime() / 1000);
			createLogEntryStatement.setLong(2, new Date().getTime() / 1000);
			createLogEntryStatement.setString(3, "Initialization complete");
			createLogEntryStatement.executeUpdate();
			ResultSet generatedKeys = createLogEntryStatement.getGeneratedKeys();
			if (generatedKeys.next()){
				reindexLogId = generatedKeys.getLong(1);
			}
			
			addNoteToReindexLogStmt = dbConn.prepareStatement("UPDATE reindex_log SET notes = ?, lastUpdate = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Unable to create log entry for reindex process", e);
			System.exit(0);
		}
		
	}
	
	private static void sendCompletionMessage(Long numWorksProcessed){
		long elapsedTime = endTime - startTime;
		float elapsedMinutes = (float)elapsedTime / (float)(60000); 
		logger.info("Time elapsed: " + elapsedMinutes + " minutes");
		
		try {
			PreparedStatement finishedStatement = dbConn.prepareStatement("UPDATE reindex_log SET endTime = ?, numWorksProcessed = ? WHERE id = ?");
			finishedStatement.setLong(1, new Date().getTime() / 1000);
			finishedStatement.setLong(2, numWorksProcessed);
			finishedStatement.setLong(3, reindexLogId);
			finishedStatement.executeUpdate();
		} catch (SQLException e) {
			logger.error("Unable to update reindex log with completion time.", e);
		}

		//Update variables table to mark the index as complete
		if (individualWorkToProcess == null){
			try {
				PreparedStatement finishedStatement = dbConn.prepareStatement("INSERT INTO variables (name, value) VALUES(?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
				if (fullReindex){
					finishedStatement.setString(1, "lastFullReindexFinish");
				} else{
					finishedStatement.setString(1, "lastPartialReindexFinish");
				}
				finishedStatement.setLong(2, new Date().getTime() / 1000);
				finishedStatement.executeUpdate();
			} catch (SQLException e) {
				logger.error("Unable to update variables with completion time.", e);
			}
		}

	}

	private static PreparedStatement updateNumWorksStatement;
	static void updateNumWorksProcessed(long numWorksProcessed){
		try {
			if (updateNumWorksStatement == null){
				updateNumWorksStatement = dbConn.prepareStatement("UPDATE reindex_log SET lastUpdate = ?, numWorksProcessed = ? WHERE id = ?");
			}
			updateNumWorksStatement.setLong(1, new Date().getTime() / 1000);
			updateNumWorksStatement.setLong(2, numWorksProcessed);
			updateNumWorksStatement.setLong(3, reindexLogId);
			updateNumWorksStatement.executeUpdate();
		} catch (SQLException e) {
			logger.error("Unable to update reindex log with number of works processed.", e);
		}
	}
}
