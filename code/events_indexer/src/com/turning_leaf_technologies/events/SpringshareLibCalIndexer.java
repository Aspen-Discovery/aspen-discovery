package com.turning_leaf_technologies.events;

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
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
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

class SpringshareLibCalIndexer {
	private long settingsId;
	private String name;
	private String baseUrl;
	private String calId;
	private String clientId;
	private String clientSecret;
	private String username;
	private String password;
	private Connection aspenConn;
	private EventsIndexerLogEntry logEntry;
	private HashMap<String, SpringshareLibCalEvent> existingEvents = new HashMap<>();
	private HashSet<String> librariesToShowFor = new HashSet<>();
	private static CRC32 checksumCalculator = new CRC32();

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	private ConcurrentUpdateSolrClient solrUpdateServer;
	//TODO: Update full reload based on settings
	private boolean doFullReload = true;

	SpringshareLibCalIndexer(long settingsId, String name, String baseUrl, String calId, String clientId, String clientSecret, String username, String password, ConcurrentUpdateSolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		this.calId = calId;
		this.clientId = clientId;
		this.clientSecret = clientSecret;
		this.username = username;
		this.password = password;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;

		logEntry = new EventsIndexerLogEntry("Springshare LibCal " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO springshare_libcal_events SET settingsId = ?, externalId = ?, title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 ON DUPLICATE KEY UPDATE title = VALUES(title), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), deleted = 0", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE springshare_libcal_events SET deleted = 1 where id = ?");

			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'springshare' AND settingId = ?");
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
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * from springshare_libcal_events WHERE settingsId = ? and deleted = 0");
			eventsStmt.setLong(1, this.settingsId);
			ResultSet existingEventsRS = eventsStmt.executeQuery();
			while (existingEventsRS.next()) {
				SpringshareLibCalEvent event = new SpringshareLibCalEvent(existingEventsRS);
				existingEvents.put(event.getExternalId(), event);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing events for Springshare LibCal " + name, e);
		}
	}

	private SimpleDateFormat dateParser = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX");
	private SimpleDateFormat eventDayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private SimpleDateFormat eventMonthFormatter = new SimpleDateFormat("yyyy-MM");
	private SimpleDateFormat eventYearFormatter = new SimpleDateFormat("yyyy");
	void indexEvents() {
		// LibCal API request
		GregorianCalendar nextYear = new GregorianCalendar();
		nextYear.setTime(new Date());
		nextYear.add(YEAR, 1);
		JSONObject libCalEventsResponse = getLibCalEvents();
		if (libCalEventsResponse != null){
			JSONArray libCalEvents = libCalEventsResponse.getJSONArray("events");
			if (doFullReload) {
				try {
					solrUpdateServer.deleteByQuery("type:event_libcal AND source:" + this.settingsId);
					//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
				} catch (HttpSolrClient.RemoteSolrException rse) {
					logEntry.incErrors("Solr is not running properly, try restarting " + rse.toString());
					System.exit(-1);
				} catch (Exception e) {
					logEntry.incErrors("Error deleting from index ", e);
				}
			}

			for (int i = 0; i < libCalEvents.length(); i++){
				try {
					JSONObject curEvent = libCalEvents.getJSONObject(i);
					checksumCalculator.reset();
					String rawResponse = curEvent.toString();
					checksumCalculator.update(rawResponse.getBytes());
					long checksum = checksumCalculator.getValue();

					Integer eventIdRaw = curEvent.getInt("id");
					String eventId = Integer.toString(eventIdRaw);
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
							if (curEvent.has("public")){
								if (!curEvent.getBoolean("public")){
									continue;
								}
							}
							SolrInputDocument solrDocument = new SolrInputDocument();
							solrDocument.addField("id", "libcal_" + settingsId + "_" + eventId);
							solrDocument.addField("identifier", eventId);
							solrDocument.addField("type", "event_libcal");
							solrDocument.addField("source", settingsId);
							solrDocument.addField("url", curEvent.getJSONObject("url").getString("public"));
							int boost = 1;
							/* // "type" does not exist in LibCal as such
							String eventType = getStringForKeyLibCal(curEvent, "type");
							//Translate the Event Type
							int boost = 1;
							if (eventType == null ){
								eventType = "Unknown";
							}else if (eventType.equals("libcal_closing")) {
								eventType = "Library Closure";
							}else if (eventType.equals("libcal_event")) {
								eventType = "Event";
								boost = 5;
							}else if (eventType.equals("libcal_reservation")) {
								eventType = "Reservation";
								boost = 2;
							}
							solrDocument.addField("event_type", eventType);
							*/

							//Don't index reservations since they are restricted to staff and
							/* // LibCal has space bookings, see https://bywater.libcal.com/admin/api/1.1/endpoint/space_post
							if (eventType != null && eventType.equals("libcal_reservation")) {
								continue;
							}*/

							solrDocument.addField("last_indexed", new Date());

							/* // LibCal events to not have a changed field
							solrDocument.addField("last_change", getDateForKey(curEvent,"changed"));
							*/

							Date startDate = getDateForKey(curEvent,"start");
							solrDocument.addField("start_date", startDate);
							solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
							Date endDate = getDateForKey(curEvent,"end");
							solrDocument.addField("end_date", endDate);

							//Only add events for the next year
							if (startDate.after(nextYear.getTime())){
								continue;
							}
							HashSet<String> eventDays = new HashSet<>();
							HashSet<String> eventMonths = new HashSet<>();
							HashSet<String> eventYears = new HashSet<>();
							Date tmpDate = (Date)startDate.clone();

							if (tmpDate.equals(endDate) || tmpDate.after(endDate)){
								eventDays.add(eventDayFormatter.format(tmpDate));
								eventMonths.add(eventMonthFormatter.format(tmpDate));
								eventYears.add(eventYearFormatter.format(tmpDate));
							}else {
								while (tmpDate.before(endDate)) {
									eventDays.add(eventDayFormatter.format(tmpDate));
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
								boost += (30 - daysInFuture);
							}
							solrDocument.addField("event_day", eventDays);
							solrDocument.addField("event_month", eventMonths);
							solrDocument.addField("event_year", eventYears);
							solrDocument.addField("title", curEvent.getString("title"));
							solrDocument.addField("branch", getNameStringForKeyLibCal(curEvent, "campus"));
							solrDocument.addField("room", getNameStringForKeyLibCal(curEvent, "location"));
							/* // LibCal events do not have a field for offsite_address
							solrDocument.addField("offsite_address", getStringForKeyLibCal(curEvent, "offsite_address"));
							*/
							solrDocument.addField("online_address", getNameStringForKeyLibCal(curEvent, "online_address"));
							solrDocument.addField("age_group", getNameStringsForKeyLibCal(curEvent, "audience"));
							solrDocument.addField("program_type", getNameStringsForKeyLibCal(curEvent, "category"));
							// TODO: James is not sure that internal_tags is indexing in a useful way 2022 03 16
							HashSet<String> internalCategories =  getNameStringsForKeyLibCal(curEvent, "internal_tags");
							if (internalCategories.contains("Featured")){
								boost += 10;
							}
							solrDocument.addField("internal_category", internalCategories);
							solrDocument.addField("event_state", getNameStringsForKeyLibCal(curEvent, "event_state"));
							/* // TODO: How does LibCal declare a cancellation?
							HashSet<String> reservationStates = getNameStringsForKeyLibCal(curEvent, "reservation_state");
							if (reservationStates.contains("Cancelled")){
								boost -= 10;
							}
							*/
							solrDocument.addField("reservation_state", getNameStringsForKeyLibCal(curEvent, "reservation_state"));
							solrDocument.addField("registration_required", curEvent.getBoolean("registration") ? "Yes" : "No");
							// TODO : request Springshare build registration start and end date like Library Market Library Calendar
							// solrDocument.addField("registration_start_date", getDateForKey(curEvent, "registration_start"));
							// solrDocument.addField("registration_end_date",getDateForKey(curEvent,"registration_end"));

							// Springshare LibCal does not have teaser / program_description equivalent 2022 03 06
							//if (curEvent.get("program_description") instanceof JSONArray) {
							//	JSONArray programDescriptions = curEvent.getJSONArray("program_description");
							//	if (programDescriptions.length() > 0) {
							//		solrDocument.addField("teaser", programDescriptions.toString());
							//	}
							//}else{
							//	solrDocument.addField("teaser", getStringForKeyLibCal(curEvent, "program_description"));
							//}

							solrDocument.addField("description", getNameStringForKeyLibCal(curEvent,"description"));

							// TODO: how does Aspen use image_url? It does not look like it actually uses the image...
							solrDocument.addField("image_url", getNameStringForKeyLibCal(curEvent, "featured_image"));

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
					}

				} catch (JSONException e) {
					logEntry.incErrors("Error getting JSON information ", e);
				}
			}

			for(SpringshareLibCalEvent eventInfo : existingEvents.values()){
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
				solrUpdateServer.commit(true, true, false);
			} catch (Exception e) {
				logEntry.incErrors("Error in final commit ", e);
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
				logEntry.incErrors("Error parsing date " + date, e);
				return null;
			}
		}
	}

	// Springshare LibCal has many key/value pairs in which the value is a JSON object
	// James has not found documentation that defines which key values are expected to be objects
	// if a key value is empty, it will appear as an empty string, e.g., "campus" : ""
	// if a key value is not empty, it will often appear as an object consisting of an id integer and name string, e.g., "campus" : { "id" : 777, "name" : "West" }
	private String getNameStringForKeyLibCal(JSONObject curEvent, String keyName) {
		if (curEvent.has(keyName)){
			if (curEvent.isNull(keyName)){
				return null;
			}else {
				if (curEvent.get(keyName) instanceof JSONObject){
					JSONObject keyObj = curEvent.getJSONObject(keyName);
					if (keyObj.has("name")) {
						return keyObj.getString("name");
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

	private HashSet<String> getNameStringsForKeyLibCal(JSONObject curEvent, String keyName) {
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
					if (keyArray.get(i) instanceof JSONObject && keyArray.getJSONObject(i).has("name")) {
						values.add(keyArray.getJSONObject(i).getString("name"));
					}
				}
			}
		}
		return values;
	}

	private JSONObject getLibCalEvents() {
		String apiEventsURL = baseUrl + "1.1/events?cal_id=" + calId;
		try {
			CloseableHttpClient httpclient = HttpClients.createDefault();
			HttpRequestBase apiRequest;
			String authTokenUrl = baseUrl + "/1.1/oauth/token";
			ArrayList<NameValuePair> params = new ArrayList<>();
			params.add(new BasicNameValuePair("grant_type", "client_credentials"));
			params.add(new BasicNameValuePair("client_id", clientId));
			params.add(new BasicNameValuePair("client_secret", clientSecret));
			HttpPost authTokenRequest  = new HttpPost(authTokenUrl);
			authTokenRequest.setEntity(new UrlEncodedFormEntity(params, "UTF-8"));
			String accessToken = "";
			String tokenType = "";
			try (CloseableHttpResponse response1 = httpclient.execute(authTokenRequest)) {
				StatusLine status = response1.getStatusLine();
				HttpEntity entity1 = response1.getEntity();
				if (status.getStatusCode() == 200) {
					String response = EntityUtils.toString(entity1);
					JSONObject authData = new JSONObject(response);
					tokenType = authData.getString("token_type");
					accessToken = authData.getString("access_token");
				}
			}

			apiRequest = new HttpGet(apiEventsURL);
			apiRequest.addHeader("Authorization", tokenType + " " + accessToken);

			try (CloseableHttpResponse response1 = httpclient.execute(apiRequest)) {
				StatusLine status = response1.getStatusLine();
				HttpEntity entity1 = response1.getEntity();
				if (status.getStatusCode() == 200) {
					String response = EntityUtils.toString(entity1);
					return new JSONObject(response);
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error getting events from " + apiEventsURL, e);
		}
		return null;
	}
}
