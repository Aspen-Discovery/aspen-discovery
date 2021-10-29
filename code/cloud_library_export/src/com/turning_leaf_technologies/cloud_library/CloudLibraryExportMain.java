package com.turning_leaf_technologies.cloud_library;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;

import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;

import org.ini4j.Ini;

import java.sql.*;
import java.util.Date;
import java.util.concurrent.Executors;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;

public class CloudLibraryExportMain {
	private static Logger logger;

	private static Ini configIni;

	private static Connection aspenConn;

	private static final String processName = "cloud_library_export";
	private static String serverName;

	public static void main(String[] args) {
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}

		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");

		while (true) {
			Date startTime = new Date();
			logger.info("Starting " + processName + ": " + startTime);

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			aspenConn = connectToDatabase();

			//Do the actual work here
			int numChanges = extractCloudLibraryData();

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
					if (numChanges == 0) {
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

	private static int extractCloudLibraryData() {
		final int[] numChanges = {0};
		//Process each setting in order.
		ThreadPoolExecutor es = (ThreadPoolExecutor) Executors.newFixedThreadPool(1);
		try {
			PreparedStatement getSettingsStmt = aspenConn.prepareStatement("SELECT * from cloud_library_settings");
			ResultSet getSettingsRS = getSettingsStmt.executeQuery();
			int numSettings = 0;
			while (getSettingsRS.next()) {
				CloudLibrarySettings settings = new CloudLibrarySettings(getSettingsRS);
				numSettings++;
				CloudLibraryExporter exporter = new CloudLibraryExporter(serverName, configIni, settings, logger, aspenConn);
				es.execute(() -> {
					try {
						numChanges[0] += exporter.extractRecords();
					}catch (Exception e){
						logger.error("Error setting up cloud library exporter", e);
					}
				});
			}
			if (numSettings == 0) {
				logger.error("Unable to find settings for CloudLibrary, please add settings to the database");
			}
		} catch (SQLException e) {
			logger.error("Error loading settings from the database", e);
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

		return numChanges[0];
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
}
