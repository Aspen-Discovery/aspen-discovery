package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import java.sql.*;
import java.util.Date;

public class CourseReservesIndexerMain {
	private static Logger logger;

	private static boolean fullReindex = false;

	private static long startTime;
	private static long endTime;
	private static long lastReindexTime;

	private static CourseReservesIndexer courseReservesProcessor;

	private static Connection dbConn;

	/**
	 * Starts the re-indexing process
	 *
	 * @param args String[] The server name to index with optional parameter for properties of indexing
	 */
	public static void main(String[] args) {
		//General configuration
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

		String processName = "course_reserves_indexer";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");

		while (true) {
			startTime = new Date().getTime();

			CourseReservesIndexingLogEntry logEntry = initializeIndexer(serverName);

			//Process lists
			long numReservesProcessed = 0;
			try {
				logger.info("Reindexing course reserves");
				numReservesProcessed += courseReservesProcessor.processCourseReserves(fullReindex, lastReindexTime, logEntry);
			} catch (Error e) {
				logEntry.incErrors("Error processing reindex ", e);
			} catch (Exception e) {
				logEntry.incErrors("Exception processing reindex ", e);
			}

			// Send completion information
			endTime = new Date().getTime();
			logEntry.setFinished();
			finishIndexing(logEntry);

			logger.info("Finished Reindex for " + serverName + " processed " + numReservesProcessed);
			long endTime = new Date().getTime();
			long elapsedTime = endTime - startTime;
			logger.info("Elapsed Minutes " + (elapsedTime / 60000));

			//Disconnect from the database
			disconnectDatabase(dbConn);

			courseReservesProcessor.close();
			courseReservesProcessor = null;

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				break;
			}
			//Pause before running the next export (longer if we didn't get any actual changes)
			System.gc();
			try {
				if (numReservesProcessed == 0) {
					Thread.sleep(1000 * 60 * 5);
				} else {
					Thread.sleep(1000 * 60);
				}
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void finishIndexing(CourseReservesIndexingLogEntry logEntry) {
		long elapsedTime = endTime - startTime;
		float elapsedMinutes = (float) elapsedTime / (float) (60000);
		logger.info("Time elapsed: " + elapsedMinutes + " minutes");

		try {
			String columnToUpdate = "lastUpdateOfChangedCourseReserves";
			if (fullReindex){
				columnToUpdate = "lastUpdateOfAllCourseReserves";
			}
			PreparedStatement finishedStatement = dbConn.prepareStatement("UPDATE  course_reserves_indexing_settings set runFullUpdate = 0, " + columnToUpdate + " = ?");
			finishedStatement.setLong(1, startTime / 1000);
			finishedStatement.executeUpdate();
			finishedStatement.close();
		} catch (SQLException e) {
			logEntry.incErrors("Unable to update settings with completion time.", e);
		}
	}

	private static CourseReservesIndexingLogEntry initializeIndexer(String serverName) {
		logger.info("Starting Reindex for " + serverName);

		// Parse the configuration file
		Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

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

		CourseReservesIndexingLogEntry logEntry = createDbLogEntry(dbConn);

		courseReservesProcessor = new CourseReservesIndexer(configIni, dbConn, logger);

		//Load the last Index time
		try {
			PreparedStatement loadSettingsStmt = dbConn.prepareStatement("SELECT * from course_reserves_indexing_settings", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet loadSettingsRS = loadSettingsStmt.executeQuery();
			if (loadSettingsRS.next()){
				fullReindex = loadSettingsRS.getBoolean("runFullUpdate");
				lastReindexTime = loadSettingsRS.getLong("lastUpdateOfChangedCourseReserves");

				//Get library translations
				PreparedStatement loadLibraryMapStmt = dbConn.prepareStatement("SELECT * FROM course_reserves_library_map where settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				loadLibraryMapStmt.setLong(1, loadSettingsRS.getLong("id"));
				ResultSet loadLibraryMapRS = loadLibraryMapStmt.executeQuery();
				while (loadLibraryMapRS.next()){
					courseReservesProcessor.addLibraryMap(loadLibraryMapRS.getString("value"), loadLibraryMapRS.getString("translation"));
				}
				loadLibraryMapRS.close();
				loadLibraryMapStmt.close();
			}else{
				logEntry.incErrors("No Settings were found for course reserve indexing");
			}
			loadSettingsRS.close();
			loadSettingsStmt.close();
		} catch (Exception e) {
			logEntry.incErrors("Could not load last index time from settings table ", e);
		}

		return logEntry;
	}

	private static CourseReservesIndexingLogEntry createDbLogEntry(Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from course_reserves_indexing_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		CourseReservesIndexingLogEntry logEntry = new CourseReservesIndexingLogEntry(aspenConn, logger);
		logEntry.saveResults();
		return logEntry;
	}
}
