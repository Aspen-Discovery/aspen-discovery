package com.turning_leaf_technologies.overdrive;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Arrays;
import java.util.Date;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

public class ExtractOverDriveInfoMain {
	private static Connection dbConn;

	public static void main(String[] args) {
		if (args.length == 0){
			System.out.println("The name of the server to extract OverDrive data for must be provided as the first parameter.");
			System.exit(1);
		}
		//System.out.println("Starting overdrive extract");

		String serverName = args[0];
		args = Arrays.copyOfRange(args, 1, args.length);
		boolean doFullReload = false;
		String individualIdToProcess = null;
		if (args.length == 1){
			//Check to see if we got a full reload parameter
			String firstArg = args[0].replaceAll("\\s", "");
			if (firstArg.matches("^fullReload(=true|1)?$")){
				doFullReload = true;
			}else if (firstArg.equals("singleWork")){
				//Process a specific work
				//Prompt for the work to process
				System.out.print("Enter the id of the record to update from OverDrive: ");

				//  open up standard input
				BufferedReader br = new BufferedReader(new InputStreamReader(System.in));

				//  read the work from the command-line; need to use try/catch with the
				//  readLine() method
				try {
					individualIdToProcess = br.readLine().trim();
				} catch (IOException ioe) {
					System.out.println("IO error trying to read the work to process!");
					System.exit(1);
				}
			}
		}

		
		Date currentTime = new Date();
		Logger logger = LoggingUtil.setupLogging(serverName, "overdrive_extract");
		logger.info(currentTime.toString() + ": Starting OverDrive Extract");
		
		// Setup the MySQL driver
		try {
			// The newInstance() call is a work around for some
			// broken Java implementations
			Class.forName("com.mysql.jdbc.Driver").newInstance();

			logger.debug("Loaded driver for MySQL");
		} catch (Exception ex) {
			logger.info("Could not load driver for MySQL, exiting.", ex);
			return;
		}
		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);
		
		String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in Database Section.  Please specify connection information in database_aspen_jdbc.");
			System.exit(1);
		}
		try {
			dbConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException e) {
			logger.error("Could not connect to database", e);
			System.exit(1);
		}
		
		OverDriveExtractLogEntry logEntry = new OverDriveExtractLogEntry(dbConn, logger);
		if (!logEntry.saveResults()){
			logger.error("Could not save log entry to database, quitting");
			return;
		}
		
		ExtractOverDriveInfo extractor = new ExtractOverDriveInfo();
		extractor.extractOverDriveInfo(configIni, dbConn, logEntry, doFullReload, individualIdToProcess);

		logEntry.setFinished();
		logEntry.addNote("Finished OverDrive extraction");
		logEntry.saveResults();
		logger.info("Finished OverDrive extraction");
		Date endTime = new Date();
		long elapsedTime = (endTime.getTime() - currentTime.getTime()) / 1000;
		logger.info("Elapsed time " + String.format("%f2", ((float)elapsedTime / 60f)) + " minutes");
	}
}
