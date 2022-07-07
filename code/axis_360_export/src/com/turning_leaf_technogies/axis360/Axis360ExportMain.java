package com.turning_leaf_technogies.axis360;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import java.sql.*;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.HashSet;
import java.util.concurrent.*;

public class Axis360ExportMain {
	private static Logger logger;

	private static Ini configIni;

	private static Connection aspenConn;

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

		String processName = "axis_360_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {

			Date startTime = new Date();
			logger.info("Starting " + processName + ": " + startTime);

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			aspenConn = connectToDatabase();

			HashSet<Axis360Setting> settings = loadSettings();

			final int[] numChanges = {0};
			//Process each setting in order.
			ThreadPoolExecutor es = (ThreadPoolExecutor)Executors.newFixedThreadPool(10);
			int numSettingsAdded = 0;
			for(Axis360Setting setting : settings) {
				try {
					es.execute(() -> {
						Axis360ExtractLogEntry logEntry = createDbLogEntry(startTime, setting.getId(), aspenConn);
						if (!logEntry.saveResults()) {
							logger.error("Could not save log entry to database, quitting");
							return;
						}

						Axis360Extractor extractor = new Axis360Extractor(serverName, aspenConn, setting, configIni, logEntry, logger);
						numChanges[0] += extractor.extractAxis360Data();

						if (logEntry.hasErrors()) {
							logger.error("There were errors during the export for setting " + setting.getId());
						}

						logger.info("Finished " + new Date());
						long endTime = new Date().getTime();
						long elapsedTime = endTime - startTime.getTime();
						logger.info("Elapsed Minutes " + (elapsedTime / 60000));

						logEntry.setFinished();
					});
					numSettingsAdded++;
				}catch (Exception e){
					logger.error("Error adding setting for processing " + setting.getId() + " - " + numSettingsAdded + " added so far");
				}
			}
			es.shutdown();
			while (true) {
				try {
					boolean terminated = es.awaitTermination(1, TimeUnit.MINUTES);
					if (terminated){
						break;
					}
				} catch (InterruptedException e) {
					logger.error("Error waiting for all extracts to finish");
				}
			}

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
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase(aspenConn);
				break;
			}
			//Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)){
				logger.info("Ending because we have low memory available");
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
					if (numChanges[0] == 0) {
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



	private static HashSet<Axis360Setting> loadSettings(){
		HashSet<Axis360Setting> settings = new HashSet<>();
		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from axis360_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			while (getSettingsRS.next()) {
				Axis360Setting setting = new Axis360Setting(getSettingsRS);
				settings.add(setting);
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database");
		}
		if (settings.size() == 0) {
			logger.error("Unable to find settings for Axis 360, please add settings to the database");
		}
		return settings;
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

	private static Axis360ExtractLogEntry createDbLogEntry(Date startTime, Long settingId, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from axis360_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		return new Axis360ExtractLogEntry(settingId, aspenConn, logger);
	}
}
