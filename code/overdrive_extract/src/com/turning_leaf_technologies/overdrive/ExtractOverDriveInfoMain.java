package com.turning_leaf_technologies.overdrive;

import java.sql.*;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.HashSet;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

public class ExtractOverDriveInfoMain {
	private static Connection dbConn;
	private static Logger logger;
	private static String serverName;

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
			if (args.length > 1){
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord")){
					extractSingleWork = true;
					if (args.length > 2) {
						singleWorkId = args[2];
					}
				}
			}
		}
		if (extractSingleWork && singleWorkId == null) {
			singleWorkId = StringUtils.getInputFromCommandLine("Enter the id of the title to extract");
		}
		String processName = "overdrive_extract";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		//Start an infinite loop to do continual indexing.  We will just kill the process as needed to restart, but
		//otherwise it should always run
		while (true) {

			Date startTime = new Date();
			logger.info(startTime + ": Starting OverDrive Extract");

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

			HashSet<OverDriveSetting> settings = loadSettings(extractSingleWork);
			final int[] numChanges = {0};

			try {
				if (dbConn.isClosed()) {
					dbConn = DriverManager.getConnection(databaseConnectionInfo);
				}
			} catch (SQLException e) {
				logger.error("Could not connect to database", e);
				System.exit(1);
			}

			ExecutorService es = Executors.newCachedThreadPool();
			for(OverDriveSetting setting : settings) {
				boolean finalExtractSingleWork = extractSingleWork;
				String finalSingleWorkId = singleWorkId;
				es.execute(() -> {
					Connection localDBConnection;
					try {
						localDBConnection = DriverManager.getConnection(databaseConnectionInfo);

						OverDriveExtractLogEntry logEntry = new OverDriveExtractLogEntry(localDBConnection, setting, logger);
						if (!logEntry.saveResults()) {
							logger.error("Could not save log entry to database, quitting");
							return;
						}

						ExtractOverDriveInfo extractor = new ExtractOverDriveInfo(setting);
						if (finalExtractSingleWork) {
							numChanges[0] += extractor.processSingleWork(finalSingleWorkId, configIni, serverName, localDBConnection, logEntry);
						} else {
							numChanges[0] += extractor.extractOverDriveInfo(configIni, serverName, localDBConnection, logEntry);
						}

						logEntry.setFinished();
						logger.info("Finished OverDrive extraction");
						Date endTime = new Date();
						long elapsedTime = (endTime.getTime() - startTime.getTime()) / 1000;
						logger.info("Elapsed time " + String.format("%f2", ((float) elapsedTime / 60f)) + " minutes");

						extractor.close();

						localDBConnection.close();
					} catch (SQLException e) {
						logger.error("Could not connect to database", e);
						System.exit(1);
					}
				});
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
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
				IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
				break;
			}
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.info("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				break;
			}
			//Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)){
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

			//Check to see if nightly indexing is running and if so, wait until it is done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				//Quit and we will restart after if finishes
				System.exit(0);
			}else {
				//Based on number of changes, pause for a little while and then continue such that we are running continuously
				try {
					System.gc();
					int maxChanges = 0;
					for (int numChange : numChanges) {
						if (numChange > maxChanges) {
							maxChanges = numChange;
						}
					}
					if (maxChanges == 0) {
						Thread.sleep(1000 * 60 * 5);
					} else {
						Thread.sleep(1000 * 60);
					}
				} catch (InterruptedException e) {
					logger.info("Thread was interrupted");
				}
			}
		}
		try {
			if (dbConn != null && !dbConn.isClosed()){
				dbConn.close();
			}
		} catch (SQLException e) {
			logger.error("Error closing database connection", e);
		}
	}

	private static HashSet<OverDriveSetting> loadSettings(boolean extractSingleWork) {
		HashSet<OverDriveSetting> settings = new HashSet<>();
		try {
			PreparedStatement getSettingsStmt = dbConn.prepareStatement("SELECT * from overdrive_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			while (getSettingsRS.next()) {
				OverDriveSetting setting = new OverDriveSetting(getSettingsRS, serverName);
				settings.add(setting);
			}
			//Clear works to update
			if (!extractSingleWork) {
				dbConn.prepareStatement("UPDATE overdrive_settings SET productsToUpdate = ''").executeUpdate();
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database", e);
		}
		if (settings.size() == 0) {
			logger.error("Unable to find settings for Axis 360, please add settings to the database");
		}
		return settings;
	}


}
