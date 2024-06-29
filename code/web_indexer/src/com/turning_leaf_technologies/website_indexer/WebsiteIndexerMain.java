package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.SystemUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.client.solrj.impl.Http2SolrClient;
import org.apache.solr.client.solrj.response.UpdateResponse;
import org.ini4j.Ini;

import java.sql.*;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.HashSet;

public class WebsiteIndexerMain {
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

		String processName = "web_indexer";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started, so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");
		long timeAtStart = new Date().getTime();

		while (true) {
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

				PreparedStatement getSitesToIndexStmt = aspenConn.prepareStatement("SELECT * from website_indexing_settings where deleted = 0");
				PreparedStatement getLibrariesForSettingsStmt = aspenConn.prepareStatement("SELECT library.subdomain From library_website_indexing inner join library on library.libraryId = library_website_indexing.libraryId where settingId = ?");
				PreparedStatement getLocationsForSettingsStmt = aspenConn.prepareStatement("SELECT code, subLocation from location_website_indexing inner join location on location.locationId = location_website_indexing.locationId where settingId = ?");
				PreparedStatement updateLastIndexedStmt = aspenConn.prepareStatement("UPDATE website_indexing_settings set lastIndexed = ? WHERE id = ?");
				ResultSet sitesToIndexRS = getSitesToIndexStmt.executeQuery();
				while (sitesToIndexRS.next()) {
					long websiteId = sitesToIndexRS.getLong("id");
					String websiteName = sitesToIndexRS.getString("name");
					String siteUrl = sitesToIndexRS.getString("siteUrl");
					String pageTitleExpression = sitesToIndexRS.getString("pageTitleExpression");
					String descriptionExpression = sitesToIndexRS.getString("descriptionExpression");
					String searchCategory = sitesToIndexRS.getString("searchCategory");
					String fetchFrequency = sitesToIndexRS.getString("indexFrequency");
					String pathsToExclude = sitesToIndexRS.getString("pathsToExclude");
					long lastFetched = sitesToIndexRS.getLong("lastIndexed");
					long maxPagesToIndex = sitesToIndexRS.getLong("maxPagesToIndex");
					long crawlDelay = sitesToIndexRS.getLong("crawlDelay");
					boolean fullReload = false;
					boolean needsIndexing = false;
					long currentTime = new Date().getTime() / 1000;
					if (sitesToIndexRS.wasNull() || lastFetched == 0) {
						needsIndexing = true;
						fullReload = true;
					} else {
						//'daily', 'weekly', 'monthly', 'yearly', 'once'
						switch (fetchFrequency) {
							case "hourly":
								needsIndexing = lastFetched < (currentTime - 60 * 60);
								break;
							case "daily":
								needsIndexing = lastFetched < (currentTime - 24 * 60 * 60);
								break;
							case "weekly":
								needsIndexing = lastFetched < (currentTime - 7 * 24 * 60 * 60);
								break;
							case "monthly":
								needsIndexing = lastFetched < (currentTime - 30 * 24 * 60 * 60);
								break;
							case "yearly":
								needsIndexing = lastFetched < (currentTime - 3655 * 24 * 60 * 60);
								break;
						}
					}
					if (needsIndexing) {
						HashSet<String> scopesToInclude = new HashSet<>();

						//Get a list of libraries and locations that the setting applies to
						getLibrariesForSettingsStmt.setLong(1, websiteId);
						ResultSet librariesForSettingsRS = getLibrariesForSettingsStmt.executeQuery();
						while (librariesForSettingsRS.next()){
							String subdomain = librariesForSettingsRS.getString("subdomain");
							subdomain = subdomain.replaceAll("[^a-zA-Z0-9_-]", "");
							scopesToInclude.add(subdomain.toLowerCase());
						}

						getLocationsForSettingsStmt.setLong(1, websiteId);
						ResultSet locationsForSettingsRS = getLocationsForSettingsStmt.executeQuery();
						while (locationsForSettingsRS.next()){
							String subLocation = locationsForSettingsRS.getString("subLocation");
							String scopeName;
							if (!locationsForSettingsRS.wasNull() && !subLocation.isEmpty()){
								scopeName = subLocation.replaceAll("[^a-zA-Z0-9_-]", "").toLowerCase();
							}else {
								String code = locationsForSettingsRS.getString("code");
								scopeName = code.replaceAll("[^a-zA-Z0-9_-]", "").toLowerCase();
							}
							if (scopesToInclude.contains(scopeName)){
								scopeName += "loc";
							}
							scopesToInclude.add(scopeName);
						}

						WebsiteIndexLogEntry logEntry = createDbLogEntry(websiteName, startTime, aspenConn);
						WebsiteIndexer indexer = new WebsiteIndexer(websiteId, websiteName, searchCategory, siteUrl, pageTitleExpression, descriptionExpression, pathsToExclude, maxPagesToIndex, crawlDelay, scopesToInclude, fullReload, logEntry, aspenConn, solrUpdateServer, logger);
						indexer.spiderWebsite();

						updateLastIndexedStmt.setLong(1, currentTime);
						updateLastIndexedStmt.setLong(2, websiteId);
						updateLastIndexedStmt.executeUpdate();

						logEntry.setFinished();
					}
				}

				//Check for settings that have been deleted
				PreparedStatement deletedSitesStmt = aspenConn.prepareStatement("SELECT * from website_indexing_settings where deleted = 1");
				ResultSet deletedSitesRS = deletedSitesStmt.executeQuery();
				while (deletedSitesRS.next()) {
					//Check to see if the jar has changes before processing records, and if so quit
					if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
						break;
					}

					WebsiteIndexLogEntry logEntry = null;
					long websiteId = deletedSitesRS.getLong("id");
					//Get a list of any pages that exist for the site.
					String websiteName = deletedSitesRS.getString("name");
					try {
						PreparedStatement websitePagesStmt = aspenConn.prepareStatement("SELECT * from website_pages WHERE websiteId = ? and deleted = 0");
						PreparedStatement deletePageStmt = aspenConn.prepareStatement("UPDATE website_pages SET deleted = 1 where id = ?");
						websitePagesStmt.setLong(1, websiteId);
						ResultSet websitePagesRS = websitePagesStmt.executeQuery();
						while (websitePagesRS.next()) {
							if (logEntry == null){
								logEntry = createDbLogEntry(websiteName, startTime, aspenConn);
							}
							try {
								WebPage page = new WebPage(websitePagesRS);
								//noinspection unused
								UpdateResponse deleteResponse = solrUpdateServer.deleteByQuery("id:\"WebPage:" + page.getId() + "\" AND settingId:" + websiteId);
								deletePageStmt.setLong(1, page.getId());
								deletePageStmt.executeUpdate();
								logEntry.incDeleted();
							}catch (Exception e){
								logEntry.incErrors("Error deleting page for website " + websiteName, e );
							}
						}
						if (logEntry != null) {
							try {
								solrUpdateServer.commit(true, true, false);
							}catch (Exception e){
								logEntry.incErrors("Error updating solr after deleting pages for " + websiteName, e );
							}
							logEntry.setFinished();
						}
					} catch (SQLException e) {
						if (logEntry == null){
							logEntry = createDbLogEntry(websiteName, startTime, aspenConn);
						}
						logEntry.incErrors("Error loading pages to delete for website " + websiteName, e);
						logEntry.setFinished();
					}
				}
				deletedSitesRS.close();

				//Index all content entered within Aspen (pages, resources, etc)
				PreparedStatement getBasicPagesStmt = aspenConn.prepareStatement("SELECT count(*) as numBasicPages from web_builder_basic_page");
				ResultSet getBasicPagesRS = getBasicPagesStmt.executeQuery();
				int numBasicPages = 0;
				if (getBasicPagesRS.next()){
					numBasicPages = getBasicPagesRS.getInt("numBasicPages");
				}
				getBasicPagesRS.close();
				getBasicPagesStmt.close();
				PreparedStatement getResourcesStmt = aspenConn.prepareStatement("SELECT count(*) as numResources from web_builder_resource");
				ResultSet getResourcesRS = getResourcesStmt.executeQuery();
				int numResources = 0;
				if (getResourcesRS.next()){
					numResources = getResourcesRS.getInt("numResources");
				}
				getResourcesRS.close();
				getResourcesStmt.close();
				PreparedStatement getPortalPagesStmt = aspenConn.prepareStatement("SELECT count(*) as numPortalPages from web_builder_portal_page");
				ResultSet getPortalPagesRS = getPortalPagesStmt.executeQuery();
				int numPortalPages = 0;
				if (getPortalPagesRS.next()){
					numPortalPages = getPortalPagesRS.getInt("numPortalPages");
				}
				getPortalPagesRS.close();
				getPortalPagesStmt.close();
				PreparedStatement getGrapesPagesStmt = aspenConn.prepareStatement("SELECT count(*) as numGrapesPages from grapes_web_builder");
				ResultSet getGrapesPagesRS = getGrapesPagesStmt.executeQuery();
				int numGrapesPages = 0;
				if (getGrapesPagesRS.next()) {
					numGrapesPages = getGrapesPagesRS.getInt("numGrapesPages");
				}
				getGrapesPagesRS.close();
				getGrapesPagesStmt.close();
				if ((numBasicPages > 0) || (numResources > 0) || (numPortalPages > 0) || (numGrapesPages > 0)){
					WebsiteIndexLogEntry logEntry = createDbLogEntry("Web Builder Content", startTime, aspenConn);
					WebBuilderIndexer indexer = new WebBuilderIndexer(configIni, logEntry, aspenConn, solrUpdateServer);
					indexer.indexContent();
					logEntry.setFinished();
				}

				//Clean up anything that does not have a setting ID
				try {
					//noinspection unused
					UpdateResponse deleteResponse = solrUpdateServer.deleteByQuery("-settingId:[* TO *]");
					solrUpdateServer.commit(true, true, false);
				}catch (Exception e){
					logger.error("Error deleting all content without a settingId", e );
				}

				try {
					solrUpdateServer.close();
				}catch (Exception e) {
					logger.error("Error closing update server ", e);
					System.exit(-5);
				}

			} catch (SQLException e) {
				logger.error("Error processing websites to index", e);
			}

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
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

			//Pause 15 minutes before running the next export
			try {
				Thread.sleep(1000 * 60 * 15);
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}

		System.exit(0);
	}

	private static ConcurrentUpdateHttp2SolrClient setupSolrClient(String solrHost, String solrPort) {
		Http2SolrClient http2Client = new Http2SolrClient.Builder().build();
		try {
			return new ConcurrentUpdateHttp2SolrClient.Builder("http://" + solrHost + ":" + solrPort + "/solr/website_pages", http2Client)
					.withThreadCount(1)
					.withQueueSize(25)
					.build();
		}catch (OutOfMemoryError e) {
			logger.error("Unable to create solr client, out of memory", e);
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

	private static WebsiteIndexLogEntry createDbLogEntry(String websiteName, Date startTime, Connection aspenConn) {
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from website_index_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		WebsiteIndexLogEntry logEntry = new WebsiteIndexLogEntry(websiteName, aspenConn, logger);
		logEntry.saveResults();
		return logEntry;
	}
}
