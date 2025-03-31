package com.turning_leaf_technologies.events;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.client.solrj.impl.Http2SolrClient;
import org.ini4j.Ini;

import java.sql.*;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;

public class EventsIndexerMain {
	private static Logger logger;
	public static void main(String[] args) {
		String serverName;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
		}

		String processName = "events_indexer";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long timeAtStart = new Date().getTime();

		while (true) {
			//Check to see if the jar has changes before processing records, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				logger.warn("Ending because the checksum for the jar changed");
				break;
			}

			Date startTime = new Date();
			logger.info("Starting " + processName + ": " + startTime);

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			Connection aspenConn = connectToDatabase(configIni);

			try {
				String solrPort = configIni.get("Index", "solrPort");
				if (solrPort == null || solrPort.isEmpty()) {
					solrPort = configIni.get("Reindex", "solrPort");
					if (solrPort == null || solrPort.isEmpty()) {
						solrPort = "8080";
					}
				}
				String solrHost = configIni.get("Index", "solrHost");
				if (solrHost == null || solrHost.isEmpty()) {
					solrHost = configIni.get("Reindex", "solrHost");
					if (solrHost == null || solrHost.isEmpty()) {
						solrHost = "localhost";
					}
				}

				ConcurrentUpdateHttp2SolrClient solrUpdateServer = setupSolrClient(solrHost, solrPort);

				// LibraryMarket LibraryCalendar
				PreparedStatement getEventsSitesToIndexStmt = aspenConn.prepareStatement("SELECT * from lm_library_calendar_settings");
				ResultSet eventsSitesRS = getEventsSitesToIndexStmt.executeQuery();
				while (eventsSitesRS.next()) {
					LibraryMarketLibraryCalendarIndexer indexer = new LibraryMarketLibraryCalendarIndexer(
							eventsSitesRS.getLong("id"),
							eventsSitesRS.getString("name"),
							eventsSitesRS.getString("baseUrl"),
							eventsSitesRS.getString("clientId"),
							eventsSitesRS.getString("clientSecret"),
							eventsSitesRS.getString("username"),
							eventsSitesRS.getString("password"),
							eventsSitesRS.getInt("numberOfDaysToIndex"),
							solrUpdateServer, aspenConn, logger);
					indexer.indexEvents();
				}

				// Springshare LibCal
				getEventsSitesToIndexStmt = aspenConn.prepareStatement("SELECT * from springshare_libcal_settings");
				eventsSitesRS = getEventsSitesToIndexStmt.executeQuery();
				while (eventsSitesRS.next()) {
					SpringshareLibCalIndexer indexer = new SpringshareLibCalIndexer(
							eventsSitesRS.getLong("id"),
							eventsSitesRS.getString("name"),
							eventsSitesRS.getString("baseUrl"),
							eventsSitesRS.getString("calId"),
							eventsSitesRS.getString("clientId"),
							eventsSitesRS.getString("clientSecret"),
							eventsSitesRS.getInt("numberOfDaysToIndex"),
							solrUpdateServer, aspenConn, logger);
					indexer.indexEvents();
				}
				SpringshareLibCalIndexer.cleanOrphanEvents(solrUpdateServer, aspenConn, logger);

				// Communico
				getEventsSitesToIndexStmt = aspenConn.prepareStatement("SELECT * from communico_settings");
				eventsSitesRS = getEventsSitesToIndexStmt.executeQuery();
				while (eventsSitesRS.next()) {
					CommunicoIndexer indexer = new CommunicoIndexer(
							eventsSitesRS.getLong("id"),
							eventsSitesRS.getString("name"),
							eventsSitesRS.getString("baseUrl"),
							eventsSitesRS.getString("clientId"),
							eventsSitesRS.getString("clientSecret"),
							eventsSitesRS.getInt("numberOfDaysToIndex"),
							eventsSitesRS.getLong("lastUpdateOfAllEvents"),
							solrUpdateServer, aspenConn, logger);
					indexer.indexEvents();
				}

				// Assabet
				getEventsSitesToIndexStmt = aspenConn.prepareStatement("SELECT * from assabet_settings");
				eventsSitesRS = getEventsSitesToIndexStmt.executeQuery();
				while (eventsSitesRS.next()) {
					AssabetIndexer indexer = new AssabetIndexer(
							eventsSitesRS.getLong("id"),
							eventsSitesRS.getString("name"),
							eventsSitesRS.getString("baseUrl"),
							eventsSitesRS.getInt("numberOfDaysToIndex"),
							solrUpdateServer, aspenConn, logger);
					indexer.indexEvents();
				}

				// Aspen events
				getEventsSitesToIndexStmt = aspenConn.prepareStatement("SELECT * from events_indexing_settings");
				eventsSitesRS = getEventsSitesToIndexStmt.executeQuery();
				while (eventsSitesRS.next()) {
					AspenEventsIndexer indexer = new AspenEventsIndexer(
						eventsSitesRS.getLong("id"),
						eventsSitesRS.getString("name"),
						eventsSitesRS.getInt("numberOfDaysToIndex"),
						eventsSitesRS.getBoolean("runFullUpdate"),
						eventsSitesRS.getLong("lastUpdateOfAllEvents"),
						eventsSitesRS.getLong("lastUpdateOfChangedEvents"),
						solrUpdateServer, aspenConn, logger, serverName);
					indexer.indexEvents();
				}

				//Index events from other source here
				try {
					solrUpdateServer.close();
				}catch (Exception e) {
					logger.error("Error closing update server ", e);
					System.exit(-5);
				}
			} catch (Exception e) {
				logger.error("Exception indexing events", e);
			} catch (Error e) {
				logger.error("Error indexing events", e);
			}

			disconnectDatabase(aspenConn);

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				logger.warn("Ending because the checksum for the jar changed");
				break;
			}
			//Check to see if it's between midnight and 1 am and the jar has been running more than 15 hours.  If so, restart just to clean up memory.
			GregorianCalendar nowAsCalendar = new GregorianCalendar();
			Date now = new Date();
			nowAsCalendar.setTime(now);
			if (nowAsCalendar.get(Calendar.HOUR_OF_DAY) <=1 && (now.getTime() - timeAtStart) > 15 * 60 * 60 * 1000 ){
				logger.warn("Ending because we have been running for more than 15 hours and it's between midnight and one AM");
				break;
			}

			//Pause 5 minutes before running the next export
			try {
				Thread.sleep(1000 * 60 * 5);
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}

		System.exit(0);
	}

	private static ConcurrentUpdateHttp2SolrClient setupSolrClient(String solrHost, String solrPort) {
		try {
			Http2SolrClient http2Client = new Http2SolrClient.Builder().build();
			return new ConcurrentUpdateHttp2SolrClient.Builder("http://" + solrHost + ":" + solrPort + "/solr/events", http2Client)
					.withThreadCount(1)
					.withQueueSize(25)
					.build();
		}catch (OutOfMemoryError e) {
			logger.error("Could not create solr client, out of memory", e);
			System.exit(-7);
		}
		return null;
	}

	private static Connection connectToDatabase(Ini configIni) {
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
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}
}
