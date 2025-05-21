package com.turning_leaf_technologies.events;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.http.HttpEntity;
import org.apache.http.NameValuePair;
import org.apache.http.StatusLine;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.methods.HttpRequestBase;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.message.BasicNameValuePair;
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
import java.util.Date;
import java.util.*;
import java.util.zip.CRC32;

import static java.util.Calendar.YEAR;

class AssabetIndexer {
	private final long settingsId;
	private final String name;
	private final String baseUrl;
	private final int numberOfDaysToIndex;
	private final Connection aspenConn;
	private final EventsIndexerLogEntry logEntry;
	private final HashMap<String, AssabetEvent> existingEvents = new HashMap<>();
	private final HashSet<String> librariesToShowFor = new HashSet<>();
	private final static CRC32 checksumCalculator = new CRC32();
	private final ConcurrentUpdateHttp2SolrClient solrUpdateServer;

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	AssabetIndexer(long settingsId, String name, String baseUrl, int numberOfDaysToIndex, ConcurrentUpdateHttp2SolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;
		this.numberOfDaysToIndex = numberOfDaysToIndex;

		logEntry = new EventsIndexerLogEntry("Assabet Interactive " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO assabet_events SET settingsId = ?, externalId = ?, title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 ON DUPLICATE KEY UPDATE title = VALUES(title), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), deleted = 0", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE assabet_events SET deleted = 1 where id = ?");

			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'assabet' AND settingId = ?");
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
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * from assabet_events WHERE settingsId = ? and deleted = 0");
			eventsStmt.setLong(1, this.settingsId);
			ResultSet existingEventsRS = eventsStmt.executeQuery();
			while (existingEventsRS.next()) {
				AssabetEvent event = new AssabetEvent(existingEventsRS);
				existingEvents.put(event.getExternalId(), event);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing events for Assabet Interactive " + name, e);
		}
	}

	private final SimpleDateFormat dateParser = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
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

					String eventId = curEvent.getString("id");

					boolean eventExists = existingEvents.containsKey(eventId);

					//Add the event to solr
					try {
						SolrInputDocument solrDocument = new SolrInputDocument();
						solrDocument.addField("id", "assabet_" + settingsId + "_" + eventId);
						solrDocument.addField("identifier", eventId);
						solrDocument.addField("type", "event_assabet");
						solrDocument.addField("source", settingsId);
						solrDocument.addField("url", getStringForKey(curEvent, "url"));

						int boost = 1;
						solrDocument.addField("last_indexed", new Date());
						solrDocument.addField("last_change", null);
						//Make sure the start date is within the range of dates we are indexing
						Date startDate = getDateForKey(curEvent,"start_date");

						solrDocument.addField("start_date", startDate);
						if (startDate == null || startDate.after(lastDateToIndex)) {
							continue;
						}

						solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
						Date endDate = getDateForKey(curEvent,"end_date");
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

						if (curEvent.get("locations") instanceof JSONArray) {
							JSONArray locationsArray = curEvent.getJSONArray("locations");
							JSONObject locationsForCurEvent = locationsArray.getJSONObject(0);
							String branch = getStringForKey(locationsForCurEvent, "branch_name");
							String location = getStringForKey(locationsForCurEvent, "location_name");

							//get event type with location_virtual value
							String eventType = getStringForKey(locationsForCurEvent, "location_virtual");
							if (eventType != null && eventType.equals("0")){
								eventType = "In Person";
							}else {
								eventType = "Online";
							}

							solrDocument.addField("branch", AspenStringUtils.trimTrailingPunctuation(branch));
							solrDocument.addField("room", AspenStringUtils.trimTrailingPunctuation(location));
							solrDocument.addField("event_type", AspenStringUtils.trimTrailingPunctuation(eventType));
						}

						if (curEvent.get("categories") instanceof JSONArray) {
							JSONArray categoriesArray = curEvent.getJSONArray("categories");
							HashSet<String> ageGroups = new HashSet<>();
							for (int j = 0; j < categoriesArray.length(); j++) {
								JSONObject categoryObj = categoriesArray.getJSONObject(j);
								if (!categoryObj.isEmpty()) {
									String categoryName = getStringForKey(categoryObj, "category_name");
									if (categoryName != null) {
										ageGroups.add(AspenStringUtils.trimTrailingPunctuation(categoryName));
									}
								}
							}
							if (!ageGroups.isEmpty()) {
								solrDocument.addField("age_group", ageGroups);
							}
						}

						solrDocument.addField("program_type", getStringsForKey(curEvent, "program_type"));

						if (curEvent.get("image") instanceof JSONObject) {
							JSONObject imageForCurEvent = curEvent.getJSONObject("image");
							if (!imageForCurEvent.isEmpty()) {
								solrDocument.addField("image_url", getStringForKey(imageForCurEvent, "url"));
							}
						}

						if (curEvent.get("registration") instanceof JSONObject) {
							JSONObject registrationForCurEvent = curEvent.getJSONObject("registration");
							if (!registrationForCurEvent.isEmpty()) {
								String registrationRequired = getStringForKey(registrationForCurEvent, "registration_required");
								if (registrationRequired != null && registrationRequired.equals("1")){
									solrDocument.addField("registration_required", "Yes");
									solrDocument.addField("registration_start_date", getDateForKey(registrationForCurEvent, "registration_starts"));
									solrDocument.addField("registration_end_date", getDateForKey(registrationForCurEvent, "registration_ends"));
								} else {
									solrDocument.addField("registration_required", "No");
								}
							}
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

			for(AssabetEvent eventInfo : existingEvents.values()){
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

	private Date getDateForKey(JSONObject curEvent, String keyName) {
		if (curEvent.isNull(keyName) || curEvent.getString(keyName).isEmpty()) {
			return null;
		} else {
			String date = curEvent.getString(keyName);
			String fullDate = date;
			if (keyName.equals("start_date")){
				String time = curEvent.getString("start_time");
				fullDate = date + " " + time;
			}else if(keyName.equals("end_date")){
				String time = curEvent.getString("end_time");
				fullDate = date + " " + time;
			}
			try {
				return dateParser.parse(fullDate);
			} catch (ParseException e) {
				logEntry.incErrors("Error parsing date " + fullDate, e);
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
		String rssURL = baseUrl + ".json";
		try {
			try (CloseableHttpClient httpclient = HttpClients.createDefault()) {
				HttpRequestBase rssRequest;
				rssRequest = new HttpGet(rssURL);
				try (CloseableHttpResponse response1 = httpclient.execute(rssRequest)) {
					StatusLine status = response1.getStatusLine();
					HttpEntity entity1 = response1.getEntity();
					if (status.getStatusCode() == 200) {
						String response = EntityUtils.toString(entity1);
						JSONArray responseArray = new JSONArray(response);
						for (int i = 0; i < responseArray.length(); i++) {
							JSONArray eventArray = responseArray.getJSONArray(i);
							for (int j = 0; j < eventArray.length(); j++) {
								JSONObject event1 = eventArray.getJSONObject(j);
								if ((event1.getInt("canceled") != 1)) {
									events.put(eventArray.get(j));
								}
							}
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
