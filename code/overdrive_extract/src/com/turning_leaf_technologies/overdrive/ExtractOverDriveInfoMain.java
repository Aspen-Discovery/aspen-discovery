package com.turning_leaf_technologies.overdrive;

import java.sql.*;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.HashSet;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicInteger;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

public class ExtractOverDriveInfoMain {
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
			singleWorkId = AspenStringUtils.getInputFromCommandLine("Enter the id of the title to extract");
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
			logger.info("{}: Starting OverDrive Extract", startTime);

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			if (databaseConnectionInfo == null || databaseConnectionInfo.isEmpty()) {
				logger.error("Database connection information not found in Database Section.  Please specify connection information in database_aspen_jdbc.");
				break;
			}

			HashSet<OverDriveSetting> settings;
			try (Connection dbConn = DriverManager.getConnection(databaseConnectionInfo)) {
				//Remove log entries older than 45 days
				long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
				try (PreparedStatement deleteOldLogEntries = dbConn.prepareStatement("DELETE from overdrive_extract_log WHERE startTime < " + earliestLogToKeep)) {
					int numDeletions = deleteOldLogEntries.executeUpdate();
					logger.info("Deleted {} old log entries", numDeletions);
				} catch (SQLException e) {
					logger.error("Error deleting old log entries", e);
				}

				settings = loadSettings(dbConn, extractSingleWork);

				//Check to see if the jar has changes before processing records, and if so quit
				if (checkForUpdatedJars(myChecksumAtStart, processName, dbConn, reindexerChecksumAtStart)) {
					break;
				}
			} catch (SQLException e) {
				logger.error("Error with database connection", e);
				break;
			} // End connecting to database

			//noinspection resource in Java 17 The cached thread pool does not have an auto close
			ExecutorService es = Executors.newCachedThreadPool();
			AtomicInteger numChanges = new AtomicInteger(0);
			AtomicBoolean errorOccurred = new AtomicBoolean();
			for(OverDriveSetting setting : settings) {
				boolean finalExtractSingleWork = extractSingleWork;
				String finalSingleWorkId = singleWorkId;
				es.execute(() -> {
					//Get a local database connection, so we don't have issues with conflicts between the different threads
					try (Connection localDBConnection = DriverManager.getConnection(databaseConnectionInfo)) {
						try (OverDriveExtractLogEntry logEntry = new OverDriveExtractLogEntry(localDBConnection, setting, logger)) {
							if (!logEntry.saveResults()) {
								logger.error("Could not save log entry to database, quitting");
								return;
							}

							try (ExtractOverDriveInfo extractor = new ExtractOverDriveInfo(setting)) {
								if (finalExtractSingleWork) {
									numChanges.addAndGet(extractor.processSingleWork(finalSingleWorkId, configIni, serverName, localDBConnection, logEntry));
								} else {
									numChanges.addAndGet(extractor.extractOverDriveInfo(configIni, serverName, localDBConnection, logEntry));
								}

								logEntry.setFinished();
								logger.info("Finished OverDrive extraction");
								Date endTime = new Date();
								long elapsedTime = (endTime.getTime() - startTime.getTime()) / 1000;
								logger.info("Elapsed time {} minutes", String.format("%f2", ((float) elapsedTime / 60f)));
							}
						} catch (Exception e) {
							logger.error("Could not setup OverDrive log entry", e);
							errorOccurred.set(true);
						}
					} catch (SQLException e) {
						logger.error("Could not connect to database while setting up local OverDrive indexer", e);
						errorOccurred.set(true);
					}
				});
			}
			es.shutdown();

			try {
				if (!es.awaitTermination(48, TimeUnit.HOURS)){
					logger.error("Took more than 2 days to run OverDrive extract, halting");
					es.shutdownNow();
				}
			} catch (InterruptedException e) {
				logger.error("Error waiting for all extracts to finish");
				es.shutdownNow();
				Thread.currentThread().interrupt();
			}

			if (errorOccurred.get()) {
				//Something happened during execution, return
				break;
			}

			//Reconnect to the db after the main extract runs just for updating if nightly indexing is needed
			try (Connection dbConn = DriverManager.getConnection(databaseConnectionInfo)) {
				//Check to see if the jar has changes, and if so quit
				if (checkForUpdatedJars(myChecksumAtStart, processName, dbConn, reindexerChecksumAtStart)) {
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

				//Check to see if nightly indexing is running and if so, wait until it is done.
				if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
					//Quit and we will restart after if finishes
					break;
				}else {
					//Based on number of changes, pause for a little while and then continue such that we are running continuously
					try {
						System.gc();
						if (numChanges.get() == 0) {
							Thread.sleep(1000 * 60 * 5);
						} else {
							Thread.sleep(1000 * 60);
						}
					} catch (InterruptedException e) {
						logger.info("Thread was interrupted");
					}
				}
			} catch (SQLException e) {
				logger.error("Error with database connection", e);
			} // End connecting to database
		} //end infinite loop for near real-time indexing
		logger = null;
		//System.exit(0);
	}

	private static boolean checkForUpdatedJars(long myChecksumAtStart, String processName, Connection dbConn, long reindexerChecksumAtStart) {
		if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
			IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
			return true;
		}
		if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar")){
			IndexingUtils.markNightlyIndexNeeded(dbConn, logger);
			return true;
		}
		return false;
	}

	private static HashSet<OverDriveSetting> loadSettings(Connection dbConn, boolean extractSingleWork) {
		HashSet<OverDriveSetting> settings = new HashSet<>();
		try {
			try (PreparedStatement getSettingsStmt = dbConn.prepareStatement("SELECT * from overdrive_settings")) {
				try (ResultSet getSettingsRS = getSettingsStmt.executeQuery()) {
					while (getSettingsRS.next()) {
						OverDriveSetting setting = new OverDriveSetting(getSettingsRS, serverName);
						settings.add(setting);
					}
				}
			}
			//Clear works to update
			if (!extractSingleWork) {
				try (PreparedStatement clearProductsToUpdateStmt = dbConn.prepareStatement("UPDATE overdrive_settings SET productsToUpdate = ''")) {
					clearProductsToUpdateStmt.executeUpdate();
				}
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database", e);
		}
		if (settings.isEmpty()) {
			logger.error("Unable to find settings for OverDrive, please add settings to the database");
		}
		return settings;
	}
}
