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
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import java.io.IOException;
import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.time.temporal.TemporalAdjusters;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import static java.util.Calendar.YEAR;

class SpringshareLibCalIndexer {
	private final long settingsId;
	private final String name;
	private String baseUrl;
	private final String calId;
	private final String clientId;
	private final String clientSecret;
	private final int numberOfDaysToIndex;

	private final Connection aspenConn;
	private final EventsIndexerLogEntry logEntry;
	private final HashMap<String, SpringshareLibCalEvent> existingEvents = new HashMap<>();
	private final HashSet<String> librariesToShowFor = new HashSet<>();
	private static final CRC32 checksumCalculator = new CRC32();

	private PreparedStatement addEventStmt;
	private PreparedStatement updateEventStmt;
	private PreparedStatement deleteEventStmt;
	private PreparedStatement addRegistrantStmt;
	private PreparedStatement deleteRegistrantStmt;

	private final ConcurrentUpdateHttp2SolrClient solrUpdateServer;

	private String oAuthTokenType;
	private String oAuthAccessToken;

	SpringshareLibCalIndexer(long settingsId, String name, String baseUrl, String calId, String clientId, String clientSecret, int numberOfDaysToIndex, ConcurrentUpdateHttp2SolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		if (this.baseUrl.endsWith("/")) {
			this.baseUrl = this.baseUrl.substring(0, this.baseUrl.length() - 1);
		}
		this.calId = calId;
		this.clientId = clientId;
		this.clientSecret = clientSecret;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;
		this.numberOfDaysToIndex = numberOfDaysToIndex;

		logEntry = new EventsIndexerLogEntry("Springshare LibCal " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO springshare_libcal_events (settingsId, externalId, title, rawChecksum, rawResponse, deleted) VALUES (?, ?, ?, ?, ?, 0)", Statement.RETURN_GENERATED_KEYS);
			updateEventStmt = aspenConn.prepareStatement("UPDATE springshare_libcal_events SET title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 where settingsId = ? AND externalId = ?", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE springshare_libcal_events SET deleted = 1 where id = ?");

			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'springshare' AND settingId = ?");
			getLibraryScopesStmt.setLong(1, settingsId);
			ResultSet getLibraryScopesRS = getLibraryScopesStmt.executeQuery();
			while (getLibraryScopesRS.next()){
				librariesToShowFor.add(getLibraryScopesRS.getString("subdomain").toLowerCase());
			}

		} catch (Exception e) {
			logEntry.incErrors("Error setting up event indexing statements ", e);
		}

		try {
			addRegistrantStmt = aspenConn.prepareStatement("INSERT INTO user_events_registrations SET userId = ?, userBarcode = ?, sourceId = ?, waitlist = 0", Statement.RETURN_GENERATED_KEYS);
			deleteRegistrantStmt = aspenConn.prepareStatement("DELETE FROM user_events_registrations WHERE userId = ? AND sourceId = ?");
		} catch (Exception e) {
			logEntry.incErrors("Error setting up registration statements ", e);
		}

		loadExistingEvents();

		// Count active (non-deleted) events for reporting.
		int activeEvents = 0;
		for (SpringshareLibCalEvent event : existingEvents.values()) {
			if (!event.isDeleted()) {
				activeEvents++;
			}
		}
		logEntry.addNote("There are " + activeEvents + " active events for setting " + settingsId + ".");
	}

	private void loadExistingEvents() {
		try {
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * FROM springshare_libcal_events WHERE settingsId = ?");
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

	private HashMap<Long, EventRegistrations> loadExistingRegistrations(String sourceId) {
		HashMap<Long, EventRegistrations> existingRegistrations = new HashMap<>();
		try {
			PreparedStatement regStmt = aspenConn.prepareStatement("SELECT * from user_events_registrations WHERE sourceId = ?");
			regStmt.setString(1, sourceId);
			ResultSet existingRegistrationsRS = regStmt.executeQuery();
			while (existingRegistrationsRS.next()) {
				EventRegistrations libcalRegistrations = new EventRegistrations(existingRegistrationsRS);
				existingRegistrations.put(libcalRegistrations.getUserId(), libcalRegistrations);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing registrations for LibCal " + name, e);
		}
		return existingRegistrations;
	}

	private final SimpleDateFormat dateParser = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX");
	private final SimpleDateFormat eventDayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private final SimpleDateFormat eventWeekFormatter = new SimpleDateFormat("yyyy-ww");
	private final SimpleDateFormat eventMonthFormatter = new SimpleDateFormat("yyyy-MM");
	private final SimpleDateFormat eventYearFormatter = new SimpleDateFormat("yyyy");

	void indexEvents() {
		// LibCal API request
		GregorianCalendar nextYear = new GregorianCalendar();
		nextYear.setTime(new Date());
		nextYear.add(YEAR, 1);
		JSONArray libCalEvents = getLibCalEvents();

		if (libCalEvents == null) {
			return;
		}

		Date lastDateToIndex = new Date();
		long numberOfDays = numberOfDaysToIndex * 24L;
		lastDateToIndex.setTime(lastDateToIndex.getTime() + (numberOfDays * 60 * 60 * 1000));

		for (int i = 0; i < libCalEvents.length(); i++){
			try {
				JSONObject curEvent = libCalEvents.getJSONObject(i);
				checksumCalculator.reset();
				String rawResponse = curEvent.toString();
				checksumCalculator.update(rawResponse.getBytes());
				long checksum = checksumCalculator.getValue();

				int eventIdRaw = curEvent.getInt("id");
				String eventId = Integer.toString(eventIdRaw);

				boolean eventExists = existingEvents.containsKey(eventId);

				String sourceId = "libcal_" + settingsId + "_" + eventId;

				//Add the event to solr
				try {
					if (curEvent.has("public")){
						if (!curEvent.getBoolean("public")){
							continue;
						}
					}
					SolrInputDocument solrDocument = new SolrInputDocument();
					solrDocument.addField("id", sourceId);
					solrDocument.addField("identifier", eventId);
					solrDocument.addField("type", "event_libcal");
					solrDocument.addField("source", settingsId);
					solrDocument.addField("url", curEvent.getJSONObject("url").getString("public"));
					int boost = 1;
					// "type" does not exist in LibCal as such

					String eventType = "Undefined";
					if (curEvent.has("physical_seats") && curEvent.getInt("physical_seats") > 0) {
						if (curEvent.has("online_seats") && curEvent.getInt("online_seats") > 0) {
							eventType = "Hybrid In-person and Online";
						} else {
							eventType = "In-person";
						}
					} else if (curEvent.has("online_seats") && curEvent.getInt("online_seats") > 0) {
						eventType = "Online";
					}
					solrDocument.addField("event_type", eventType);

					//Don't index reservations since they are restricted to staff and
					// LibCal has space bookings, see https://bywater.libcal.com/admin/api/1.1/endpoint/space_post

					solrDocument.addField("last_indexed", new Date());

					// LibCal events to not have a changed field

					Date startDate = getDateForKey(curEvent,"start");
					//Make sure the start date is within the range of dates we are indexing

					solrDocument.addField("start_date", startDate);
					if (startDate == null || startDate.after(lastDateToIndex)) {
						continue;
					}
					solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
					Date endDate = getDateForKey(curEvent,"end");
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
					solrDocument.addField("branch", AspenStringUtils.trimTrailingPunctuation(getNameStringForKeyLibCal(curEvent, "campus")));
					solrDocument.addField("room", AspenStringUtils.trimTrailingPunctuation(getNameStringForKeyLibCal(curEvent, "location")));
					// LibCal events do not have a field for offsite_address

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
					// TODO: How does LibCal declare a cancellation?

					solrDocument.addField("reservation_state", getNameStringsForKeyLibCal(curEvent, "reservation_state"));
					solrDocument.addField("registration_required", curEvent.getBoolean("registration") ? "Yes" : "No");

					// TODO : request Springshare build registration start and end date like Library Market Library Calendar
					// solrDocument.addField("registration_start_date", getDateForKey(curEvent, "registration_start"));
					// solrDocument.addField("registration_end_date",getDateForKey(curEvent,"registration_end"));

					// Springshare LibCal does not have teaser / program_description equivalent 2022 03 06

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
				if (eventExists){
					existingEvents.remove(eventId);
					try {
						updateEventStmt.setString(1, curEvent.getString("title"));
						updateEventStmt.setLong(2, checksum);
						updateEventStmt.setString(3, rawResponse);
						updateEventStmt.setLong(4, settingsId);
						updateEventStmt.setString(5, eventId);
						updateEventStmt.executeUpdate();
					} catch (SQLException e) {
						logEntry.incErrors("Error updating event in database " , e);
					}
					logEntry.incUpdated();
				}else{
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

					logEntry.incAdded();
				}

				//Fetch registrations here and add to DB - for events that require registration ONLY
				if (curEvent.getBoolean("registration")){
					JSONArray libCalEvent = getRegistrations(Integer.valueOf(eventId));
					HashMap<Long, EventRegistrations> registrationsForEvent = loadExistingRegistrations(sourceId);

					HashSet<String> uniqueBarcodesRegistered = new HashSet<>();
					if (libCalEvent != null) {
						JSONArray libCalEventRegistrants = libCalEvent.getJSONArray(0);
						for (int j = 0; j < libCalEventRegistrants.length(); j++) {
							try {
								JSONObject curRegistrant = libCalEventRegistrants.getJSONObject(j);

								if (!curRegistrant.getString("barcode").isEmpty()){
									uniqueBarcodesRegistered.add(curRegistrant.getString("barcode"));
								}
							} catch (JSONException e) {
								logEntry.incErrors("Error getting JSON information ", e);
							}
						}
					}

					for (String uniqueBarcodeRegistered : uniqueBarcodesRegistered){
						try {
							PreparedStatement getUserIdStmt = aspenConn.prepareStatement("SELECT id FROM user WHERE cat_username = ?");
							getUserIdStmt.setString(1, uniqueBarcodeRegistered);
							ResultSet getUserIdRS = getUserIdStmt.executeQuery();
							while (getUserIdRS.next()){
								long userId = getUserIdRS.getLong("id");
								if (registrationsForEvent.containsKey(userId)){
									registrationsForEvent.remove(userId);
								}else{
									addRegistrantStmt.setLong(1, userId);
									addRegistrantStmt.setString(2, uniqueBarcodeRegistered);
									addRegistrantStmt.setString(3, sourceId);
									addRegistrantStmt.executeUpdate();
								}
							}
						} catch (SQLException e) {
							logEntry.incErrors("Error adding registrant info to database " , e);
						}
					}

					for(EventRegistrations registrantInfo : registrationsForEvent.values()){
						try {
							deleteRegistrantStmt.setLong(1, registrantInfo.getUserId());
							deleteRegistrantStmt.setString(2, registrantInfo.getSourceId());
							deleteRegistrantStmt.executeUpdate();
						}catch (SQLException e) {
							logEntry.incErrors("Error deleting registration info ", e);
						}
					}
				}
			} catch (JSONException e) {
				logEntry.incErrors("Error getting JSON information ", e);
			}
		}

		for (SpringshareLibCalEvent eventInfo : existingEvents.values()){
			try {
				// Only mark for deletion if it's not already deleted.
				if (!eventInfo.isDeleted()) {
					deleteEventStmt.setLong(1, eventInfo.getId());
					deleteEventStmt.executeUpdate();

					try {
						solrUpdateServer.deleteById("libcal_" + settingsId + "_" + eventInfo.getExternalId());
					} catch (Exception e) {
						logEntry.incErrors("Error deleting event by id ", e);
					}
					logEntry.incDeleted();
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error deleting event ", e);
			}
		}

		try {
			solrUpdateServer.commit(false, false, true);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit while finishing extract, shutting down", e);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-3);
		}

		// Close prepared statements.
		try {
			if (addEventStmt != null) {
				addEventStmt.close();
			}
			if (updateEventStmt != null) {
				updateEventStmt.close();
			}
			if (deleteEventStmt != null) {
				deleteEventStmt.close();
			}
			if (addRegistrantStmt != null) {
				addRegistrantStmt.close();
			}
			if (deleteRegistrantStmt != null) {
				deleteRegistrantStmt.close();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error closing prepared statements: ", e);
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
						return AspenStringUtils.trimTrailingPunctuation(keyObj.getString("name"));
					}else{
						for (String objKey: keyObj.keySet()){
							return AspenStringUtils.trimTrailingPunctuation(keyObj.getString(objKey));
						}
						return null;
					}
				}else{
					return AspenStringUtils.trimTrailingPunctuation(curEvent.get(keyName).toString());
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
					values.add(AspenStringUtils.trimTrailingPunctuation(keyObj.getString(keyValue)));
				}
			}else{
				JSONArray keyArray = curEvent.getJSONArray(keyName);
				for (int i = 0; i < keyArray.length(); i++){
					if (keyArray.get(i) instanceof JSONObject && keyArray.getJSONObject(i).has("name")) {
						values.add(AspenStringUtils.trimTrailingPunctuation(keyArray.getJSONObject(i).getString("name")));
					}
				}
			}
		}
		return values;
	}

	private JSONArray getLibCalEvents() {
		try {
			CloseableHttpClient httpclient = HttpClients.createDefault();
			HttpRequestBase apiRequest;
			String authTokenUrl = baseUrl + "/1.1/oauth/token";
			ArrayList<NameValuePair> params = new ArrayList<>();
			params.add(new BasicNameValuePair("grant_type", "client_credentials"));
			params.add(new BasicNameValuePair("client_id", clientId));
			params.add(new BasicNameValuePair("client_secret", clientSecret));
			HttpPost authTokenRequest = new HttpPost(authTokenUrl);
			authTokenRequest.setEntity(new UrlEncodedFormEntity(params, "UTF-8"));
			try (CloseableHttpResponse response1 = httpclient.execute(authTokenRequest)) {
				StatusLine status = response1.getStatusLine();
				HttpEntity entity1 = response1.getEntity();
				if (status.getStatusCode() == 200) {
					String response = EntityUtils.toString(entity1);
					JSONObject authData = new JSONObject(response);
					oAuthTokenType = authData.getString("token_type");
					oAuthAccessToken = authData.getString("access_token");
				}
			}
			ArrayList <Integer> calIds = getLibCalCalIds(httpclient);
			JSONArray events = new JSONArray();
			for (Integer calId : calIds) {
				// Springshare LibCal API currently allows max 500 events returned per call. We will attempt to retrieve 500 events for each month
				LocalDate today = LocalDate.now();
				for (int m = 0; m < 13; m++) {
					LocalDate firstOfMonth;
					if (m == 0) {
						firstOfMonth = today;
					} else {
						firstOfMonth = today.plusMonths(m).with(TemporalAdjusters.firstDayOfMonth());
					}
					LocalDate endOfMonth = today.plusMonths(m).with(TemporalAdjusters.lastDayOfMonth());
					long daysThisMonth = ChronoUnit.DAYS.between(firstOfMonth, endOfMonth);
					String apiEventsURL = baseUrl + "/1.1/events?cal_id=" + calId;
					apiEventsURL += "&date=" + firstOfMonth;
					apiEventsURL += "&days=" + daysThisMonth;
					apiEventsURL += "&limit=500";
					apiRequest = new HttpGet(apiEventsURL);
					apiRequest.addHeader("Authorization", oAuthTokenType + " " + oAuthAccessToken);
					try (CloseableHttpResponse response1 = httpclient.execute(apiRequest)) {
						StatusLine status = response1.getStatusLine();
						HttpEntity entity1 = response1.getEntity();
						if (status.getStatusCode() == 200) {
							String response = EntityUtils.toString(entity1);
							JSONObject response2 = new JSONObject(response);
							JSONArray events1 = response2.getJSONArray("events");
							for (int i = 0; i < events1.length(); i++) {
								events.put(events1.get(i));
							}
						}
					} catch (Exception e) {
						logEntry.incErrors("Error getting events from " + apiEventsURL, e);
					}
				}
			}
			return events;
		} catch (Exception e) {
			logEntry.incErrors("Error getting events", e);
		}
		return null;
	}

	private JSONArray getRegistrations(Integer eventId) {
		try {
			JSONArray eventRegistrations;
			try (CloseableHttpClient httpclient = HttpClients.createDefault()) {
				HttpRequestBase apiRequest;

				eventRegistrations = new JSONArray();
				String apiRegistrationsURL = baseUrl + "/1.1/events/" + eventId + "/registrations?waitlist=1";
				apiRequest = new HttpGet(apiRegistrationsURL);
				apiRequest.addHeader("Authorization", oAuthTokenType + " " + oAuthAccessToken);
				try (CloseableHttpResponse response1 = httpclient.execute(apiRequest)) {
					StatusLine status = response1.getStatusLine();
					HttpEntity entity1 = response1.getEntity();
					if (status.getStatusCode() == 200) {
						//LibCal returns an array of objects that have an array of registrants because they allow checking multiple eventIds at once
						String response = EntityUtils.toString(entity1);
						JSONArray eventRegArray = new JSONArray(response);
						JSONObject response2 = eventRegArray.getJSONObject(0); //only checking one event at a time so only need first index
						JSONArray registrants = response2.getJSONArray("registrants");
						eventRegistrations.put(registrants);
					}
				} catch (Exception e) {
					logEntry.incErrors("Error getting event registrations from " + apiRegistrationsURL, e);
					return null;
				}
				return eventRegistrations;
			} catch (Exception e) {
				logEntry.incErrors("Unable to create HTTP connection", e);
			}
		} catch (Exception e) {
			logEntry.incErrors("Error getting event registrations", e);
		}
		return null;
	}

	private ArrayList <Integer> getLibCalCalIds(CloseableHttpClient httpclient) {
		ArrayList <Integer> values = new ArrayList<>();
		if (calId.isEmpty()) {
			String apiCalendarsURL = baseUrl + "/1.1/calendars";
			HttpRequestBase apiRequest = new HttpGet(apiCalendarsURL);
			apiRequest.addHeader("Authorization", oAuthTokenType + " " + oAuthAccessToken);
			try (CloseableHttpResponse response1 = httpclient.execute(apiRequest)) {
				StatusLine status = response1.getStatusLine();
				HttpEntity entity1 = response1.getEntity();
				if (status.getStatusCode() == 200) {
					String response = EntityUtils.toString(entity1);
					JSONObject responseCalendars = new JSONObject(response);
					JSONArray calendars = responseCalendars.getJSONArray("calendars");
					for (int i = 0; i < calendars.length(); i++){
						// visibility must be Public
						if (calendars.get(i) instanceof JSONObject && Objects.equals(calendars.getJSONObject(i).getString("visibility"), "Public")) {
							//noinspection SpellCheckingInspection
							Integer calId = calendars.getJSONObject(i).getInt("calid");
							values.add(calId);
						}
					}
				}
			} catch (Exception e) {
				logEntry.incErrors("Error getting calendars from " + apiCalendarsURL, e);
			}
		} else {
			// Transform comma-delimited calId into ArrayList
			String[] calendars = calId.split(",");
			for (String id : calendars) {
				values.add(Integer.parseInt(id.trim()));
			}
		}
		return values;
	}

	public static void cleanOrphanEvents(ConcurrentUpdateHttp2SolrClient solrUpdateServer, Connection aspenConn, Logger logger)
	{
		EventsIndexerLogEntry logEntry = new EventsIndexerLogEntry("Springshare LibCal Orphan Events", aspenConn, logger);
		//get settingsIds with orphans
		//create orphan indexers for them
		//run index events
		logEntry.addNote("Checking for orphaned events...");
		try {
			PreparedStatement getEventsSitesToIndexStmt = aspenConn.prepareStatement("SELECT unique(settingsId) from springshare_libcal_events where settingsId not in (select id from springshare_libcal_settings) and deleted = 0");
			PreparedStatement deleteOrphans = aspenConn.prepareStatement("UPDATE springshare_libcal_events SET deleted = 1 where settingsId = ?");
			ResultSet eventsSitesRS = getEventsSitesToIndexStmt.executeQuery();
			while (eventsSitesRS.next()) {
				long settingsId = eventsSitesRS.getLong("settingsId");
				deleteOrphans.setLong(1, settingsId);
				int deletedEvents = deleteOrphans.executeUpdate();
				logEntry.incDeletedByNum(deletedEvents);
				solrUpdateServer.deleteByQuery("type:event_libcal AND source:" + settingsId);
				solrUpdateServer.commit(false, false, true);
				logEntry.addNote("Deleted orphans for settingsId: " + settingsId);
			}
		} catch (SQLException e) {
			logger.error("SQLException cleaning orphan events", e);
			logEntry.incErrors("SQLException cleaning orphan events", e);
		} catch (SolrServerException e) {
			logger.error("Solr Exception cleaning orphan events sql and solr may be out of sync", e);
			logEntry.incErrors("Solr Exception cleaning orphan events sql and solr may be out of sync", e);
		} catch (IOException e)
		{
			//this exception is ex
			logger.error("IOException cleaning orphan events sql and solr may be out of sync", e);
			logEntry.incErrors("IOException cleaning orphan events sql and solr may be out of sync", e);
		}
		logEntry.setFinished();
		logEntry.saveResults();
	}
}
