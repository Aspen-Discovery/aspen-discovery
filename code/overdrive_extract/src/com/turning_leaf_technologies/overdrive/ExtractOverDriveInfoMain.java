package com.turning_leaf_technologies.overdrive;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Date;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

public class ExtractOverDriveInfoMain {
	private static Connection dbConn;

	public static void main(String[] args) {
		String serverName;
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}
		String processName = "overdrive_extract";
		Logger logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long recordGroupingChecksumAtStart = JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar");

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

			//Remove log entries older than 45 days
			long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
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

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				break;
			}
			if (recordGroupingChecksumAtStart != JarUtil.getChecksumForJar(logger, "record_grouping", "../record_grouping/record_grouping.jar")){
				break;
			}
			//Based on number of changes, pause for a little while and then continue on so we are running continuously
			try {
				System.gc();
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
