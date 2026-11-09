package com.turning_leaf_technologies.borrowbox;

import java.sql.*;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.HashSet;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

public class ExtractBorrowBoxInfoMain {
	private static Connection dbConn;
	private static Logger logger;
	private static String serverName;

	public static void main(String[] args) {
		boolean extractSingleWork = false;
		String singleWorkId = null;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String extractSingleWorkResponse = AspenStringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleWork = true;
			}
		} else {
			serverName = args[0];
			boolean singleExtractRequested = args.length > 1 && (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord"));
			if (singleExtractRequested) {
				extractSingleWork = true;
				if (args.length > 2) {
					singleWorkId = args[2];
				}
			}
		}
		boolean needsSingleWorkId = extractSingleWork && singleWorkId == null;
		if (needsSingleWorkId) {
			singleWorkId = AspenStringUtils.getInputFromCommandLine("Enter the id of the title to extract");
		}
		String processName = "borrowbox_extract";
		logger = LoggingUtil.setupLogging(serverName, processName);

		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {

			Date startTime = new Date();
			logger.info(startTime + ": Starting BorrowBox Extract");

			Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			boolean connectionInfoMissing = databaseConnectionInfo == null || databaseConnectionInfo.isEmpty();
			if (connectionInfoMissing) {
				logger.error("Database connection information not found in Database Section. Please specify connection information in database_aspen_jdbc.");
				System.exit(1);
			}
			try {
				dbConn = DriverManager.getConnection(databaseConnectionInfo);
			} catch (SQLException e) {
				logger.error("Could not connect to database", e);
				System.exit(1);
			}

			long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
			try {
				int numDeletions = dbConn.prepareStatement("DELETE from borrowbox_extract_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
				logger.info("Deleted " + numDeletions + " old log entries");
			} catch (SQLException e) {
				logger.error("Error deleting old log entries", e);
			}

			HashSet<BorrowBoxSetting> settings = loadSettings(extractSingleWork);
			int numChanges = 0;

			try {
				if (dbConn.isClosed()) {
					dbConn = DriverManager.getConnection(databaseConnectionInfo);
				}
			} catch (SQLException e) {
				logger.error("Could not connect to database", e);
				System.exit(1);
			}

			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")) {
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				return;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")) {
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				return;
			}

			for (BorrowBoxSetting setting : settings) {
				Connection localDBConnection;
				try {
					localDBConnection = DriverManager.getConnection(databaseConnectionInfo);

					BorrowBoxExtractLogEntry logEntry = new BorrowBoxExtractLogEntry(localDBConnection, setting, logger);
					if (!logEntry.saveResults()) {
						logger.error("Could not save log entry to database, quitting");
						continue;
					}

					ExtractBorrowBoxInfo extractor = new ExtractBorrowBoxInfo(setting);
					if (extractSingleWork) {
						numChanges += extractor.processSingleWork(singleWorkId, configIni, serverName, localDBConnection, logEntry);
					} else {
						numChanges += extractor.extractBorrowBoxInfo(configIni, serverName, localDBConnection, logEntry);
					}

					logEntry.setFinished();
					logger.info("Finished BorrowBox extraction for setting " + setting.getName());
					Date endTime = new Date();
					long elapsedTime = (endTime.getTime() - startTime.getTime()) / 1000;
					logger.info("Elapsed time " + String.format("%f2", ((float) elapsedTime / 60f)) + " minutes");

					extractor.close();

					localDBConnection.close();
				} catch (SQLException e) {
					logger.error("Could not connect to database", e);
					System.exit(1);
				}
			}

			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")) {
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")) {
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				break;
			}
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			boolean inNightlyWindow = nowAsCalendar.get(Calendar.HOUR_OF_DAY) <= 1;
			boolean ranMoreThan15Hours = (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000;
			if (inNightlyWindow && ranMoreThan15Hours) {
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				break;
			}
			if (SystemUtils.hasLowMemory(configIni, logger)) {
				logger.info("Ending because we have low memory available");
				break;
			}

			if (extractSingleWork) {
				break;
			}

			try {
				dbConn.close();
			} catch (SQLException e) {
				logger.error("Error closing database connection", e);
			}

			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				System.exit(0);
			}
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
		try {
			boolean connectionOpen = dbConn != null && !dbConn.isClosed();
			if (connectionOpen) {
				dbConn.close();
			}
		} catch (SQLException e) {
			logger.error("Error closing database connection", e);
		}

		System.exit(0);
	}

	private static HashSet<BorrowBoxSetting> loadSettings(boolean extractSingleWork) {
		HashSet<BorrowBoxSetting> settings = new HashSet<>();
		try {
			PreparedStatement getSettingsStmt = dbConn.prepareStatement("SELECT * from borrowbox_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			while (getSettingsRS.next()) {
				BorrowBoxSetting setting = new BorrowBoxSetting(getSettingsRS, serverName);
				settings.add(setting);
			}
			if (!extractSingleWork) {
				dbConn.prepareStatement("UPDATE borrowbox_settings SET productsToUpdate = ''").executeUpdate();
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database", e);
		}
		if (settings.isEmpty()) {
			logger.error("Unable to find settings for BorrowBox, please add settings to the database");
		}
		return settings;
	}
}
