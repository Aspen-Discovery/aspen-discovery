package com.turning_leaf_technologies.events;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.http.HttpEntity;
import org.apache.http.StatusLine;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpRequestBase;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BaseHttpSolrClient;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import static java.util.Calendar.YEAR;

class LocalHopIndexer {
	private final long settingsId;
	private final String name;
	private final String baseUrl;
	private final int numberOfDaysToIndex;
	private final Connection aspenConn;
	private final EventsIndexerLogEntry logEntry;
	private final HashMap<String, LocalHopEvent> existingEvents = new HashMap<>();
	private final HashSet<String> librariesToShowFor = new HashSet<>();
	private final static CRC32 checksumCalculator = new CRC32();
	private final ConcurrentUpdateHttp2SolrClient solrUpdateServer;

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	LocalHopIndexer(long settingsId, String name, String baseUrl, int numberOfDaysToIndex, ConcurrentUpdateHttp2SolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;
		this.numberOfDaysToIndex = numberOfDaysToIndex;

		logEntry = new EventsIndexerLogEntry("LocalHop Interactive " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO localhop_events SET settingsId = ?, externalId = ?, title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 ON DUPLICATE KEY UPDATE title = VALUES(title), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), deleted = 0", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE localhop_events SET deleted = 1 where id = ?");

			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'localhop' AND settingId = ?");
			getLibraryScopesStmt.setLong(1, settingsId);
			ResultSet getLibraryScopesRS = getLibraryScopesStmt.executeQuery();
			while (getLibraryScopesRS.next()){
				librariesToShowFor.add(getLibraryScopesRS.getString("subdomain").toLowerCase());
			}

		} catch (Exception e) {
			logEntry.incErrors("Error setting up statements ", e);
		}

		loadExistingEvents();
	}

	private void loadExistingEvents() {
		try {
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * from localhop_events WHERE settingsId = ? and deleted = 0");
			eventsStmt.setLong(1, this.settingsId);
			ResultSet existingEventsRS = eventsStmt.executeQuery();
			while (existingEventsRS.next()) {
				LocalHopEvent event = new LocalHopEvent(existingEventsRS);
				existingEvents.put(event.getExternalId(), event);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing events for LocalHop Interactive " + name, e);
		}
	}

	private final SimpleDateFormat dateParser = new SimpleDateFormat("MMM dd yyyy HH:mm:ss", Locale.ENGLISH);
	private final SimpleDateFormat eventDayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private final SimpleDateFormat eventWeekFormatter = new SimpleDateFormat("yyyy-ww");
	private final SimpleDateFormat eventMonthFormatter = new SimpleDateFormat("yyyy-MM");
	private final SimpleDateFormat eventYearFormatter = new SimpleDateFormat("yyyy");
	void indexEvents() {
		//Load the RSS feed
		GregorianCalendar nextYear = new GregorianCalendar();
		nextYear.setTime(new Date());
		nextYear.add(YEAR, 1);
		JSONArray rssFeed = getRSSFeed();
		if (rssFeed != null){

			try {
				solrUpdateServer.deleteByQuery("type:event AND source:" + this.settingsId);
			} catch (BaseHttpSolrClient.RemoteSolrException rse) {
				logEntry.incErrors("Solr is not running properly, try restarting " + rse);
				System.exit(-1);
			} catch (Exception e) {
				logEntry.incErrors("Error deleting from index ", e);
			}

			Date lastDateToIndex = new Date();
			long numberOfDays = numberOfDaysToIndex * 24L;
			lastDateToIndex.setTime(lastDateToIndex.getTime() + (numberOfDays * 60 * 60 * 1000));

			for (int i = 0; i < rssFeed.length(); i++){
				try {
					JSONObject curEvent = rssFeed.getJSONObject(i);
					checksumCalculator.reset();
					String rawResponse = curEvent.toString();
					checksumCalculator.update(rawResponse.getBytes());
					long checksum = checksumCalculator.getValue();

					JSONArray customElements = curEvent.getJSONArray("custom_elements");

					String eventId = getCustomElement(customElements, "LHEvent:eventId");

					boolean eventExists = existingEvents.containsKey(eventId);

					//Add the event to solr
					try {
						SolrInputDocument solrDocument = new SolrInputDocument();
						solrDocument.addField("id", "localhop_" + settingsId + "_" + eventId);
						solrDocument.addField("identifier", eventId);
						solrDocument.addField("type", "event_localhop");
						solrDocument.addField("source", settingsId);
						solrDocument.addField("url", getStringForKey(curEvent, "url"));

						int boost = 1;
						solrDocument.addField("last_indexed", new Date());
						solrDocument.addField("last_change", null);
						//Make sure the start date is within the range of dates we are indexing
						Date startDate = getDate(curEvent, getCustomElement(customElements, "LHEvent:sd"));

						solrDocument.addField("start_date", startDate);
						if (startDate == null || startDate.after(lastDateToIndex)) {
							continue;
						}

						solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
						Date endDate = getDate(curEvent, getCustomElement(customElements, "LHEvent:ed"));
						solrDocument.addField("end_date", endDate);

						//Only add events for the next year
						if (startDate.after(nextYear.getTime())){
							continue;
						}
						HashSet<String> eventDays = new HashSet<>();
						HashSet<String> eventWeeks = new HashSet<>();
						HashSet<String> eventMonths = new HashSet<>();
						HashSet<String> eventYears = new HashSet<>();
						Date tmpDate = (Date)startDate.clone();

						if (tmpDate.equals(endDate) || tmpDate.after(endDate)){
							eventDays.add(eventDayFormatter.format(tmpDate));
							eventWeeks.add(eventWeekFormatter.format(tmpDate));
							eventMonths.add(eventMonthFormatter.format(tmpDate));
							eventYears.add(eventYearFormatter.format(tmpDate));
						}else {
							while (tmpDate.before(endDate)) {
								eventDays.add(eventDayFormatter.format(tmpDate));
								eventWeeks.add(eventWeekFormatter.format(tmpDate));
								eventMonths.add(eventMonthFormatter.format(tmpDate));
								eventYears.add(eventYearFormatter.format(tmpDate));
								tmpDate.setTime(tmpDate.getTime() + 24 * 60 * 60 * 1000);
							}
						}
						//Boost based on start date, we will give preference to anything in the next 30 days
						Date today = new Date();
						if (startDate.before(today) || startDate.equals(today)){
							boost += 30;
						}else{
							long daysInFuture = (startDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24);
							if (daysInFuture > 30){
								daysInFuture = 30;
							}
							boost += (int) (30 - daysInFuture);
						}
						solrDocument.addField("event_day", eventDays);
						solrDocument.addField("event_week", eventWeeks);
						solrDocument.addField("event_month", eventMonths);
						solrDocument.addField("event_year", eventYears);
						solrDocument.addField("title", curEvent.getString("title"));

						solrDocument.addField("branch", getCustomElement(customElements, "LHEvent:organizationName"));
						solrDocument.addField("room", getCustomElement(customElements, "LHEvent:bookingRoomName"));

						//get event type with location_virtual value
						String eventType = getCustomElement(customElements, "LHEvent:virtual");
						if (eventType != null && eventType.equals("false")){
							eventType = "In Person";
						} else {
							eventType = "Online";
						}
						solrDocument.addField("event_type", AspenStringUtils.trimTrailingPunctuation(eventType));


						solrDocument.addField("image_url", getCustomElement(customElements, "LHEvent:photo"));

						solrDocument.addField("age_group", getCustomElementAsSet(customElements, "LHEvent:ageGroup"));

						if (!getCustomElement(customElements, "LHEvent:bookingStartDate").isEmpty()){
							solrDocument.addField("registration_required", "Yes");
							solrDocument.addField("registration_start_date", getDate(curEvent, getCustomElement(customElements, "LHEvent:bookingStartDate")));
							solrDocument.addField("registration_end_date", getDate(curEvent, getCustomElement(customElements, "LHEvent:bookingEndDate")));
						} else {
							solrDocument.addField("registration_required", "No");

						}

						solrDocument.addField("description", getStringForKey(curEvent,"description"));

						solrDocument.addField("library_scopes", librariesToShowFor);
						if (boost < 1){
							boost = 1;
						}
						solrDocument.addField("boost", boost);
						solrUpdateServer.add(solrDocument);
					} catch (SolrServerException | IOException e) {
						logEntry.incErrors("Error adding event to solr ", e);
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
						logEntry.incErrors("Error adding event to database " , e);
					}

					if (eventExists){
						existingEvents.remove(eventId);
						logEntry.incUpdated();
					}else{
						logEntry.incAdded();
					}

				} catch (JSONException e) {
					logEntry.incErrors("Error getting JSON information from the RSS Feed ", e);
				}
			}

			for(LocalHopEvent eventInfo : existingEvents.values()){
				try {
					deleteEventStmt.setLong(1, eventInfo.getId());
					deleteEventStmt.executeUpdate();
				} catch (SQLException e) {
					logEntry.incErrors("Error deleting event ", e);
				}
				try {
					solrUpdateServer.deleteById("lc_" + settingsId + "_" + eventInfo.getExternalId());
				} catch (Exception e) {
					logEntry.incErrors("Error deleting event by id ", e);
				}
				logEntry.incDeleted();
			}

			try {
				solrUpdateServer.commit(false, false, true);
			} catch (Exception e) {
				logEntry.incErrors("Error in final commit while finishing extract, shutting down", e);
				logEntry.setFinished();
				logEntry.saveResults();
				System.exit(-3);
			}
		}

		// Close prepared statements.
		try {
			if (addEventStmt != null) {
				addEventStmt.close();
			}
			if (deleteEventStmt != null) {
				deleteEventStmt.close();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error closing database statements: ", e);
		}

		logEntry.setFinished();
	}

	private String getCustomElement(JSONArray customElements, String key) {
		for (int i = 0; i < customElements.length(); i++) {
			try {
				JSONObject element = customElements.getJSONObject(i);
				if (element.has(key)) {
					return element.getString(key);
				}
			} catch (JSONException e) {
				// skip
			}
		}
		return null;
	}

	private Set<String> getCustomElementAsSet(JSONArray customElements, String key) {
		for (int i = 0; i < customElements.length(); i++) {
			try {
				JSONObject element = customElements.getJSONObject(i);
				if (element.has(key)) {
					JSONArray valueArray = element.getJSONArray(key);
					Set<String> names = new HashSet<>();
					for (int j = 0; j < valueArray.length(); j++) {
						JSONObject obj = valueArray.getJSONObject(j);
						if (obj.has("LHEvent:AgeGroupName")) {
							names.add(obj.getString("LHEvent:AgeGroupName"));
						}
					}
					return names.isEmpty() ? null : names;
				}
			} catch (JSONException e) {
				// skip
			}
		}
		return null;
	}

	private Date getDate(JSONObject curEvent, String date) {
		if (date.isEmpty()) {
			return null;
		} else {
			// Strip the parenthetical timezone label, day of week, and timezone
			String trimmedDate = date.replaceAll("^\\w+\\s|\\s*GMT[+-]\\d{4}|\\s*\\(.*\\)", "").trim();

			try {
				return dateParser.parse(trimmedDate);
			} catch (ParseException e) {
				logEntry.incErrors("Error parsing date " + date, e);
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
						for (String objKey: keyObj.keySet()){
							if (keyObj.isNull(objKey)) {
								return null;
							}else {
								return keyObj.getString(objKey);
							}
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
					values.add(AspenStringUtils.trimTrailingPunctuation(keyObj.getString(keyValue)));
				}
			}else{
				JSONArray keyArray = curEvent.getJSONArray(keyName);
				for (int i = 0; i < keyArray.length(); i++){
					String value = AspenStringUtils.trimTrailingPunctuation(keyArray.getString(i));
					values.add(value);
				}
			}
		}
		return values;
	}

	private JSONArray getRSSFeed() {
		JSONArray events = new JSONArray();
		String rssURL = baseUrl;
		try {
			try (CloseableHttpClient httpclient = HttpClients.createDefault()) {
				HttpRequestBase rssRequest;
				rssRequest = new HttpGet(rssURL);
				try (CloseableHttpResponse response1 = httpclient.execute(rssRequest)) {
					StatusLine status = response1.getStatusLine();
					HttpEntity entity1 = response1.getEntity();
					if (status.getStatusCode() == 200) {
						String response = EntityUtils.toString(entity1);
						JSONObject eventsObject = new JSONObject(response);
						JSONArray itemsArray = eventsObject.getJSONArray("items");

						for (int i = 0; i < itemsArray.length(); i++) {
							JSONObject event = itemsArray.getJSONObject(i);
							events.put(event);
						}
						return events;
					}
				}
			} catch (Exception e) {
				logEntry.incErrors("Could not create HTTP client", e);
			}
		} catch (Exception e) {
			logEntry.incErrors("Error getting RSS feed from " + rssURL, e);
		}
		return null;
	}
}
