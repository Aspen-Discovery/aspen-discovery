package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.JarUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.response.UpdateResponse;
import org.ini4j.Ini;

import java.sql.*;
import java.util.Date;
import java.util.HashSet;

public class WebsiteIndexerMain {
	private static Logger logger;

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

		String processName = "web_indexer";
		logger = LoggingUtil.setupLogging(serverName, processName);

		//Get the checksum of the JAR when it was started so we can stop if it has changed.
		long myChecksumAtStart = JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar");

		while (true) {
			Date startTime = new Date();
			logger.info("Starting " + processName + ": " + startTime.toString());

			// Read the base INI file to get information about the server (current directory/cron/config.ini)
			Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the aspen database
			Connection aspenConn = connectToDatabase(configIni);

			try {
				String solrPort = configIni.get("Reindex", "solrPort");
				ConcurrentUpdateSolrClient solrUpdateServer = setupSolrClient(solrPort);

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
							subdomain = subdomain.replaceAll("[^a-zA-Z0-9_]", "");
							scopesToInclude.add(subdomain.toLowerCase());
						}

						getLocationsForSettingsStmt.setLong(1, websiteId);
						ResultSet locationsForSettingsRS = getLocationsForSettingsStmt.executeQuery();
						while (locationsForSettingsRS.next()){
							String subLocation = locationsForSettingsRS.getString("subLocation");
							if (!locationsForSettingsRS.wasNull() && subLocation.length() > 0){
								scopesToInclude.add(subLocation.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase());
							}else {
								String code = locationsForSettingsRS.getString("code");
								scopesToInclude.add(code.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase());
							}
						}

						WebsiteIndexLogEntry logEntry = createDbLogEntry(websiteName, startTime, aspenConn);
						WebsiteIndexer indexer = new WebsiteIndexer(websiteId, websiteName, searchCategory, siteUrl, pageTitleExpression, descriptionExpression, pathsToExclude, maxPagesToIndex, scopesToInclude, fullReload, logEntry, aspenConn, solrUpdateServer);
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
								UpdateResponse deleteResponse = solrUpdateServer.deleteByQuery("id:" + page.getId() + " AND website_name:\"" + websiteName + "\"");
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
				if ((numBasicPages > 0) || (numResources > 0)){
					boolean fullReload = true;
					WebsiteIndexLogEntry logEntry = createDbLogEntry("Web Builder Content", startTime, aspenConn);
					WebBuilderIndexer indexer = new WebBuilderIndexer(fullReload, logEntry, aspenConn, solrUpdateServer);
					indexer.indexContent();
					logEntry.setFinished();
				}

			} catch (SQLException e) {
				logger.error("Error processing websites to index", e);
			}

			//Check to see if the jar has changes, and if so quit
			if (myChecksumAtStart != JarUtil.getChecksumForJar(logger, processName, "./" + processName + ".jar")){
				break;
			}
			//Pause 15 minutes before running the next export
			try {
				Thread.sleep(1000 * 60 * 15);
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}
	}

	private static ConcurrentUpdateSolrClient setupSolrClient(String solrPort) {
		ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/website_pages");
		solrBuilder.withThreadCount(1);
		solrBuilder.withQueueSize(25);
		ConcurrentUpdateSolrClient updateServer = solrBuilder.build();
		updateServer.setRequestWriter(new BinaryRequestWriter());

		return updateServer;
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
