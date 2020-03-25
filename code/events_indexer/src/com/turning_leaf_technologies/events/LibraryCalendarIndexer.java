package com.turning_leaf_technologies.events;

import org.apache.http.HttpEntity;
import org.apache.http.StatusLine;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.json.JSONString;

import java.io.IOException;
import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.HashMap;
import java.util.HashSet;
import java.util.zip.CRC32;
import java.util.Date;

class LibraryCalendarIndexer {
	private long settingsId;
	private String name;
	private String baseUrl;
	private Connection aspenConn;
	private EventsIndexerLogEntry logEntry;
	private HashMap<String, LibraryCalendarEvent> existingEvents = new HashMap<>();
	private HashSet<String> librariesToShowFor = new HashSet<>();
	private static CRC32 checksumCalculator = new CRC32();

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	private ConcurrentUpdateSolrClient solrUpdateServer;
	//TODO: Update full reload based on settings
	private boolean doFullReload = true;

	LibraryCalendarIndexer(long settingsId, String name, String baseUrl, ConcurrentUpdateSolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;

		logEntry = new EventsIndexerLogEntry("Library Calendar " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO lm_library_calendar_events SET settingsId = ?, externalId = ?, title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 ON DUPLICATE KEY UPDATE title = VALUES(title), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), deleted = 0", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE lm_library_calendar_events SET deleted = 1 where id = ?");

			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'library_market' AND settingId = ?");
			getLibraryScopesStmt.setLong(1, settingsId);
			ResultSet getLibraryScopesRS = getLibraryScopesStmt.executeQuery();
			while (getLibraryScopesRS.next()){
				librariesToShowFor.add(getLibraryScopesRS.getString("subdomain"));
			}

		} catch (Exception e) {
			logEntry.incErrors();
			logEntry.addNote("Error setting up statements " + e.toString());
		}

		loadExistingEvents();
	}

	private void loadExistingEvents() {
		try {
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * from lm_library_calendar_events WHERE settingsId = ? and deleted = 0");
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

	private SimpleDateFormat dateParser = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	void indexEvents() {
		//Load the RSS feed
		JSONArray rssFeed = getRSSFeed();
		if (rssFeed != null){
			if (doFullReload) {
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
			}

			for (int i = 0; i < rssFeed.length(); i++){
				try {
					JSONObject curEvent = rssFeed.getJSONObject(i);
					checksumCalculator.reset();
					String rawResponse = curEvent.toString();
					checksumCalculator.update(rawResponse.getBytes());
					long checksum = checksumCalculator.getValue();

					String eventId = curEvent.getString("uuid");
					boolean eventExists = false;
					boolean eventChanged = false;
					if (existingEvents.containsKey(eventId)){
						eventExists = true;
						if (checksum != existingEvents.get(eventId).getRawChecksum()){
							eventChanged = true;
						}
					}

					if (doFullReload || !eventExists || eventChanged){
						//Add the event to solr
						try {
							SolrInputDocument solrDocument = new SolrInputDocument();
							solrDocument.addField("id", "lc_" + settingsId + "_" + eventId);
							solrDocument.addField("identifier", eventId);
							solrDocument.addField("type", "library_calendar");
							solrDocument.addField("source", settingsId);
							solrDocument.addField("url", getStringForKey(curEvent, "url"));
							solrDocument.addField("last_indexed", new Date());
							solrDocument.addField("last_change", getDateForKey(curEvent,"changed"));
							Date startDate = getDateForKey(curEvent,"start_date");
							solrDocument.addField("start_date", startDate);
							solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
							solrDocument.addField("end_date", getDateForKey(curEvent,"end_date"));
							solrDocument.addField("title", curEvent.getString("title"));
							solrDocument.addField("branch", getStringsForKey(curEvent, "branch"));
							solrDocument.addField("room", getStringsForKey(curEvent, "room"));
							solrDocument.addField("offsite_address", getStringForKey(curEvent, "offsite_address"));
							solrDocument.addField("online_address", getStringForKey(curEvent, "online_address"));
							solrDocument.addField("age_group", getStringsForKey(curEvent, "age_group"));
							solrDocument.addField("program_type", getStringsForKey(curEvent, "program_type"));
							solrDocument.addField("internal_category", getStringsForKey(curEvent, "internal_category"));
							solrDocument.addField("registration_required", curEvent.getBoolean("registration_enabled"));
							solrDocument.addField("registration_start_date", getDateForKey(curEvent, "registration_start"));
							solrDocument.addField("registration_end_date",getDateForKey(curEvent,"registration_end"));

							if (curEvent.get("program_description") instanceof JSONArray) {
								JSONArray programDescriptions = curEvent.getJSONArray("program_description");
								if (programDescriptions.length() > 0) {
									solrDocument.addField("teaser", programDescriptions.toString());
								}
							}else{
								solrDocument.addField("teaser", getStringForKey(curEvent, "program_description"));
							}

							solrDocument.addField("description", getStringForKey(curEvent,"description"));

							solrDocument.addField("image_url", getStringForKey(curEvent, "image"));

							solrDocument.addField("library_scopes", librariesToShowFor);

							solrUpdateServer.add(solrDocument);
						} catch (SolrServerException | IOException e) {
							logEntry.addNote("Error adding event to solr " + e.toString());
							logEntry.incErrors();
						}

						//Add the event to the database
						try {
							addEventStmt.setLong(1, settingsId);
							addEventStmt.setString(2, eventId);
							addEventStmt.setString(3, curEvent.getString("title"));
							addEventStmt.setLong(4, checksum);
							addEventStmt.setString(5, rawResponse);
							addEventStmt.executeUpdate();
						} catch (SQLException e) {
							logEntry.addNote("Error adding event to database " + e.toString());
							logEntry.incErrors();
						}

						if (eventExists){
							existingEvents.remove(eventId);
						}
					}

				} catch (JSONException e) {
					logEntry.addNote("Error getting JSON information from the RSS Feed " + e.toString());
					logEntry.incErrors();
				}
			}

			for(LibraryCalendarEvent eventInfo : existingEvents.values()){
				try {
					deleteEventStmt.setLong(1, eventInfo.getId());
				} catch (SQLException e) {
					logEntry.addNote("Error deleting event " + e.toString());
					logEntry.incErrors();
				}
				try {
					solrUpdateServer.deleteById("lc_" + settingsId + "_" + eventInfo.getExternalId());
				} catch (Exception e) {
					logEntry.addNote("Error deleting event by id " + e.toString());
					logEntry.incErrors();
				}
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

	private Date getDateForKey(JSONObject curEvent, String keyName) {
		if (curEvent.isNull(keyName)) {
			return null;
		} else {
			String date = curEvent.getString(keyName);
			try {
				return dateParser.parse(date);
			} catch (ParseException e) {
				logEntry.addNote("Error parsing date " + date);
				logEntry.incErrors();
				return null;
			}
		}
	}

	private String getStringForKey(JSONObject curEvent, String keyName) {
		if (curEvent.has(keyName)){
			if (curEvent.isNull(keyName)){
				return null;
			}else {
				if (curEvent.get(keyName) instanceof JSONObject){
					JSONObject keyObj = curEvent.getJSONObject(keyName);
					if (keyObj.has(keyName)) {
						return keyObj.getString(keyName);
					}else{
						//noinspection LoopStatementThatDoesntLoop
						for (String objKey: keyObj.keySet()){
							return keyObj.getString(objKey);
						}
						return null;
					}
				}else{
					return curEvent.get(keyName).toString();
				}
			}
		}else{
			return null;
		}
	}

	private HashSet<String> getStringsForKey(JSONObject curEvent, String keyName) {
		HashSet<String> values = new HashSet<>();
		if (!curEvent.isNull(keyName)){
			if (curEvent.get(keyName) instanceof JSONObject) {
				JSONObject keyObj = curEvent.getJSONObject(keyName);
				for (String keyValue : keyObj.keySet()) {
					values.add(keyObj.getString(keyValue));
				}
			}else{
				JSONArray keyArray = curEvent.getJSONArray(keyName);
				for (int i = 0; i < keyArray.length(); i++){
					values.add(keyArray.getString(i));
				}
			}
		}
		return values;
	}

	private JSONArray getRSSFeed() {
		String rssURL = baseUrl + "/events/feed/json";
		try {
			CloseableHttpClient httpclient = HttpClients.createDefault();
			HttpGet httpGet = new HttpGet(rssURL);
			try (CloseableHttpResponse response1 = httpclient.execute(httpGet)) {
				StatusLine status = response1.getStatusLine();
				HttpEntity entity1 = response1.getEntity();
				if (status.getStatusCode() == 200) {
					String response = EntityUtils.toString(entity1);
					JSONArray rssData = new JSONArray(response);
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
