package org.aspendiscovery.omeka;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import java.sql.*;
import java.util.*;
import java.util.Date;

public class OmekaExportMain {
	private static Logger logger;
	private static String serverName;

	private static Ini configIni;

	private static Connection aspenConn;

	public static void main(String[] args) {
		int settingToProcess = -1;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String settingToProcessStr = AspenStringUtils.getInputFromCommandLine("Enter the Setting ID to process (blank to process all)");
			if (!settingToProcessStr.isEmpty() && AspenStringUtils.isInteger(settingToProcessStr)) {
				settingToProcess = Integer.parseInt(settingToProcessStr);
			}
		} else {
			serverName = args[0];
		}

		String processName = "omeka_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			Date startTime = new Date();
			logger.info(startTime + ": Starting Omeka Export");

			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			aspenConn = connectToDatabase();

			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}

			HashSet<OmekaSetting> settings = loadSettings();

			int numSettingsUpdated = 0;
			for (OmekaSetting setting : settings) {
				if (settingToProcess != -1 && settingToProcess != setting.getId()) {
					continue;
				}
				OmekaExportLogEntry logEntry = createDbLogEntry(startTime, setting.getId(), aspenConn);
				if (!logEntry.saveResults()) {
					logger.error("Could not save log entry to database, quitting");
					break;
				}

				OmekaExtractor extractor = new OmekaExtractor(serverName, aspenConn, setting, configIni, logEntry, logger);
				if (extractor.exportOmekaData()) {
					numSettingsUpdated++;
				}

				if (logEntry.hasErrors()) {
					logger.error("There were errors during the export for setting " + setting.getId());
				}

				logEntry.setFinished();
			}

			if (settingToProcess != -1) {
				disconnectDatabase(aspenConn);
				break;
			}

			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <= 1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000) {
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase(aspenConn);
				break;
			}
			if (SystemUtils.hasLowMemory(configIni, logger)) {
				logger.info("Ending because we have low memory available");
				disconnectDatabase(aspenConn);
				break;
			}

			disconnectDatabase(aspenConn);

			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				System.exit(0);
			} else {
				try {
					System.gc();
					if (numSettingsUpdated > 0) {
						Thread.sleep(1000 * 60);
					} else {
						Thread.sleep(1000 * 60 * 5);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		}

		System.exit(0);
	}

	private static Connection connectToDatabase() {
		String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
		if (databaseConnectionInfo == null) {
			logger.error("Aspen database connection information was not provided");
			System.exit(1);
		}
		try {
			return DriverManager.getConnection(databaseConnectionInfo);
		} catch (Exception e) {
			logger.error("Error connecting to Aspen database " + e);
			System.exit(1);
			return null;
		}
	}

	private static HashSet<OmekaSetting> loadSettings() {
		HashSet<OmekaSetting> settings = new HashSet<>();
		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from omeka_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			while (getSettingsRS.next()) {
				OmekaSetting setting = new OmekaSetting(getSettingsRS);
				settings.add(setting);
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database");
		}
		if (settings.isEmpty()) {
			logger.error("Unable to find settings for Omeka, please add settings to the database");
		}
		return settings;
	}

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
			//noinspection UnusedAssignment
			aspenConn = null;
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static OmekaExportLogEntry createDbLogEntry(Date startTime, Long settingId, Connection aspenConn) {
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from omeka_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		return new OmekaExportLogEntry(settingId, aspenConn, logger);
	}
}
