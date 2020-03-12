package com.turning_leaf_technologies.events;

import org.apache.http.HttpEntity;
import org.apache.http.StatusLine;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.json.JSONArray;
import org.json.JSONObject;

import java.sql.*;
import java.util.HashMap;
import java.util.zip.CRC32;

class LibraryCalendarIndexer {
	private long settingsId;
	private String name;
	private String baseUrl;
	private Connection aspenConn;
	private Logger logger;
	private EventsIndexerLogEntry logEntry;
	private HashMap<String, LibraryCalendarEvent> existingEvents;
	private static CRC32 checksumCalculator = new CRC32();

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	private ConcurrentUpdateSolrClient solrUpdateServer;

	LibraryCalendarIndexer(long settingsId, String name, String baseUrl, ConcurrentUpdateSolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		this.aspenConn = aspenConn;
		this.logger = logger;

		logEntry = new EventsIndexerLogEntry("Library Calendar " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO lm_library_calendar_events SET settingsId = ?, externalId = ?, title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 ON DUPLICATE KEY UPDATE title = VALUES(title), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse)", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE lm_library_calendar_events SET deleted = 1 where id = ?");
		} catch (Exception e) {
			logEntry.incErrors();
			logEntry.addNote("Error setting up statements " + e.toString());
		}


	}

	private void loadExistingEvents() {
		try {
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * from lm_library_calendar_events WHERE settingsId = ?");
			eventsStmt.setLong(1, this.settingsId);
			ResultSet existingEventsRS = eventsStmt.executeQuery();
			while (existingEventsRS.next()) {
				LibraryCalendarEvent event = new LibraryCalendarEvent(existingEventsRS);
				existingEvents.put(event.getExternalId(), event);
			}
		} catch (SQLException e) {
			logEntry.addNote("Error loading existing events for Library Calendar " + name + " " + e.toString());
			logEntry.incErrors();
		}
	}

	void indexEvents() {
		//Load the RSS feed
		JSONObject rssFeed = getRSSFeed();
		if (rssFeed != null){
			try {
				solrUpdateServer.deleteByQuery("type:library_calendar AND source:" + this.settingsId);
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (HttpSolrClient.RemoteSolrException rse) {
				logEntry.addNote("Solr is not running properly, try restarting " + rse.toString());
				System.exit(-1);
			} catch (Exception e) {
				logEntry.addNote("Error deleting from index " + e.toString());
				logEntry.incErrors();
			}



			try {
				solrUpdateServer.commit(false, false, true);
			} catch (Exception e) {
				logEntry.addNote("Error in final commit " + e.toString());
				logEntry.incErrors();
			}
		}

		logEntry.setFinished();
	}

	private JSONObject getRSSFeed() {
		String rssURL = baseUrl + "/events/feed/json";
		try {
			CloseableHttpClient httpclient = HttpClients.createDefault();
			HttpGet httpGet = new HttpGet(rssURL);
			try (CloseableHttpResponse response1 = httpclient.execute(httpGet)) {
				StatusLine status = response1.getStatusLine();
				HttpEntity entity1 = response1.getEntity();
				if (status.getStatusCode() == 200) {
					String response = EntityUtils.toString(entity1);
					JSONObject rssData = new JSONObject(response);
					return rssData;
				}
			}
		} catch (Exception e) {
			logEntry.addNote("Error getting RSS feed from " + rssURL + " " + e.toString());
			logEntry.incErrors();
		}
		return null;
	}
}
