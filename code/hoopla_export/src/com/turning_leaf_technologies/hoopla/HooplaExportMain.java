package com.turning_leaf_technologies.hoopla;

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

public class HooplaExportMain {
	private static String serverName;
	private static Logger logger;
	private static Ini configIni;
	private static Connection aspenConn;

	public static void main(String[] args) {
		boolean extractSingleWork = false;
		String singleWorkId = null;
		String singleWorkType = null;
		String hooplaType;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String extractSingleWorkResponse = AspenStringUtils.getInputFromCommandLine("Process a single work? (y/N)");
			if (extractSingleWorkResponse.equalsIgnoreCase("y")) {
				extractSingleWork = true;
				String extractSingleWorkType = AspenStringUtils
					.getInputFromCommandLine("For version 1, enter the type of work to extract (INSTANT/Flex); for version 2, enter Instant to continue");
				if (extractSingleWorkType.equalsIgnoreCase("Instant")) {
					singleWorkType = "Instant";
				} else if (extractSingleWorkType.equalsIgnoreCase("Flex")) {
					singleWorkType = "Flex";
				} else {
					singleWorkType = "Instant";
				}

			}

		} else {
			serverName = args[0];
			if (args.length > 1) {
				if (args[1].equalsIgnoreCase("singleWork") || args[1].equalsIgnoreCase("singleRecord")) {
					extractSingleWork = true;
					if (args.length > 2) {
						hooplaType = args[2];
						if (hooplaType.equalsIgnoreCase("Instant")) {
							singleWorkType = "Instant";
						} else if (hooplaType.equalsIgnoreCase("Flex")) {
							singleWorkType = "Flex";
						} else {
							System.out.println("Invalid work type. Please enter Instant or Flex.");
							System.exit(1);
						}
						if (args.length > 3) {
							singleWorkId = args[3];
						}
					} else {
						String extractSingleWorkType = AspenStringUtils
							.getInputFromCommandLine("For version 1, enter the type of work to extract (INSTANT/Flex); for version 2, enter Instant to continue");
						if (extractSingleWorkType.equalsIgnoreCase("Instant")) {
							singleWorkType = "Instant";
						} else if (extractSingleWorkType.equalsIgnoreCase("Flex")) {
							singleWorkType = "Flex";
						} else {
							singleWorkType = "Instant";
						}
					}
				}
			}
		}

		if (extractSingleWork && singleWorkId == null) {
			singleWorkId = AspenStringUtils.getInputFromCommandLine("Enter the id of the title to extract");
		}

		String processName = "hoopla_export";
		logger = LoggingUtil.setupLogging(serverName, processName);

		// Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long reindexerChecksumAtStart = JarUtil.getChecksumForJar(logger, "reindexer", "../reindexer/reindexer.jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			// Hoopla only needs to run once a day, so run it in cron
			Date startTime = new Date();
			logger.info(startTime + ": Starting Hoopla Export");

			// Read the base INI file to get information about the server (current
			// directory/cron/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			// Connect to the Aspen database
			aspenConn = connectToDatabase();

			// Check to see if the jar has changes before processing records, and if so,
			// quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer",
					"../reindexer/reindexer.jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}

			int hooplaVersion = 1;
			boolean updatesRun = false;
			int numChanges = 0;

			try {
				// Get the Hoopla version from the system variables
				PreparedStatement getHooplaVersionStmt = aspenConn.prepareStatement("SELECT hooplaVersion FROM system_variables");
				ResultSet hooplaVersionRS = getHooplaVersionStmt.executeQuery();
				if (hooplaVersionRS.next()) {
					hooplaVersion = hooplaVersionRS.getInt("hooplaVersion");
				}
				hooplaVersionRS.close();
				getHooplaVersionStmt.close();

				// Start a log entry
				createDbLogEntry(startTime, aspenConn);

				// Run different exporters for different versions
				if (hooplaVersion == 1) {
					HooplaExtractLogEntry logEntry = new HooplaExtractLogEntry(aspenConn, logger);
					logEntry.addNote("Starting extract with Hoopla Exporter Version 1");
					logEntry.saveResults();

					HooplaExporter exporter = new HooplaExporter(serverName, aspenConn, configIni, logEntry, logger);
					if (singleWorkId == null) {
						updatesRun = exporter.exportHooplaData();
					} else {
						updatesRun = HooplaExporter.exportSingleHooplaTitle(singleWorkId, singleWorkType);
					}
					exporter.exporterCleanUp();

					numChanges = logEntry.getNumChanges();

					if (logEntry.hasErrors()) {
						logger.error("There were errors during the export!");
					}

					logger.info("Finished exporting data " + new Date());
					long endTime = new Date().getTime();
					long elapsedTime = endTime - startTime.getTime();
					logger.info("Elapsed Minutes " + (elapsedTime / 60000));

					// Mark that indexing has finished
					logEntry.setFinished();

					if (!updatesRun && !logEntry.hasErrors()) {
						// delete the log entry
						try {
							PreparedStatement deleteLogEntryStmt = aspenConn
								.prepareStatement("DELETE from hoopla_export_log WHERE id = " + logEntry.getLogEntryId());
							deleteLogEntryStmt.executeUpdate();
						} catch (SQLException e) {
							logger.error("Could not delete log export ", e);
						}

					}
				} else if (hooplaVersion == 2) {
					HooplaExtractLogEntry2 logEntry2 = new HooplaExtractLogEntry2(aspenConn, logger);
					logEntry2.addNote("Starting extract with Hoopla Exporter Version 2");
					logEntry2.saveResults();

					HooplaExporter2 exporter2 = new HooplaExporter2(serverName, aspenConn, configIni, logEntry2, logger);
					if (singleWorkId == null) {
						updatesRun = exporter2.exportHooplaData();
					} else {
						updatesRun = exporter2.exportSingleHooplaTitle(singleWorkId, true);
					}
					exporter2.exporter2CleanUp();
					numChanges = logEntry2.getNumChanges();

					if (logEntry2.hasErrors()) {
						logger.error("There were errors during the export!");
					}

					logger.info("Finished exporting data " + new Date());
					long endTime = new Date().getTime();
					long elapsedTime = endTime - startTime.getTime();
					logger.info("Elapsed Minutes " + (elapsedTime / 60000));

					// Mark that indexing has finished
					logEntry2.setFinished();

					if (!updatesRun && !logEntry2.hasErrors()) {
						// delete the log entry
						try {
							PreparedStatement deleteLogEntryStmt = aspenConn
								.prepareStatement("DELETE from hoopla_export_log WHERE id = " + logEntry2.getLogEntryId());
							deleteLogEntryStmt.executeUpdate();
						} catch (SQLException e) {
							logger.error("Could not delete log export ", e);
						}

					}

				}
			} catch (SQLException e) {
				logger.error("Error getting Hoopla version", e);
			}

			if (extractSingleWork) {
				disconnectDatabase(aspenConn);
				break;
			}

			// Check to see if the jar has changes, and if so, quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			if (reindexerChecksumAtStart != JarUtil.getChecksumForJar(logger, "reindexer",
				"../reindexer/reindexer.jar")) {
				IndexingUtils.markNightlyIndexNeeded(aspenConn, logger);
				disconnectDatabase(aspenConn);
				break;
			}
			// Check to see if it's between midnight and 1 am, and the jar has been running
			// more than 15 hours. If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <= 1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000) {
				logger.info(
						"Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				disconnectDatabase(aspenConn);
				break;
			}
			// Check memory to see if we should close
			if (SystemUtils.hasLowMemory(configIni, logger)) {
				logger.info("Ending because we have low memory available");
				disconnectDatabase(aspenConn);
				break;
			}

			disconnectDatabase(aspenConn);

			// Check to see if nightly indexing is running, and if so, wait until it is
			// done.
			if (IndexingUtils.isNightlyIndexRunning(configIni, serverName, logger)) {
				// Quit and we will restart after if finishes
				System.exit(0);
			} else {
				// Pause before running the next export (longer if we didn't get any actual
				// changes)
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

		System.exit(0);
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		// Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn
					.prepareStatement("DELETE from hoopla_export_log WHERE startTime < " + earliestLogToKeep)
					.executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
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

	private static void disconnectDatabase(Connection aspenConn) {
		try {
			aspenConn.close();
			aspenConn = null;
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

}
