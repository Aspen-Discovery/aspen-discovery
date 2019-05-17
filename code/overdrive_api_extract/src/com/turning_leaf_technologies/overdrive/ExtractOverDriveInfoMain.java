package com.turning_leaf_technologies.overdrive;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
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

		String serverName = args[0];
		Logger logger = LoggingUtil.setupLogging(serverName, "overdrive_extract");

		//Start an infinite loop to do continual indexing.  We will just kill the process as needed to restart, but
		//otherwise it should always run
		while (true) {

			Date startTime = new Date();
			logger.info(startTime.toString() + ": Starting OverDrive Extract");

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

			//Remove log entries older than 60 days
			long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 60);
			try {
				int numDeletions = dbConn.prepareStatement("DELETE from overdrive_extract_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
				logger.info("Deleted " + numDeletions + " old log entries");
			} catch (SQLException e) {
				logger.error("Error deleting old log entries", e);
			}

			OverDriveExtractLogEntry logEntry = new OverDriveExtractLogEntry(dbConn, logger);
			if (!logEntry.saveResults()){
				logger.error("Could not save log entry to database, quitting");
				return;
			}

			ExtractOverDriveInfo extractor = new ExtractOverDriveInfo();
			int numChanges = extractor.extractOverDriveInfo(configIni, serverName, dbConn, logEntry);

			logEntry.setFinished();
			logger.info("Finished OverDrive extraction");
			Date endTime = new Date();
			long elapsedTime = (endTime.getTime() - startTime.getTime()) / 1000;
			logger.info("Elapsed time " + String.format("%f2", ((float)elapsedTime / 60f)) + " minutes");

			//Clean up resources
			extractor.close();

			//Based on number of changes, pause for a little while and then continue on so we are running continuously
			try {
				if (numChanges == 0) {
					Thread.sleep(1000 * 60 * 5);
				}else {
					Thread.sleep(1000 * 60);
				}
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}
	}
}
