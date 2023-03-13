package com.turning_leaf_technologies.events;

import org.apache.commons.codec.binary.Base64;
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

import javax.net.ssl.HttpsURLConnection;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.nio.charset.StandardCharsets;
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

class CommunicoIndexer {
	private long settingsId;
	private String name;
	private String baseUrl;
	private String clientId;
	private String clientSecret;
	private Connection aspenConn;
	private EventsIndexerLogEntry logEntry;
	private HashMap<String, CommunicoEvent> existingEvents = new HashMap<>();
	private HashSet<String> librariesToShowFor = new HashSet<>();
	private static CRC32 checksumCalculator = new CRC32();

	//Communico API Info
	private String communicoAPIToken;
	private String communicoAPITokenType;
	private long communicoAPIExpiration;

	private PreparedStatement addEventStmt;
	private PreparedStatement deleteEventStmt;

	private ConcurrentUpdateSolrClient solrUpdateServer;
	private boolean doFullReload = true;

	CommunicoIndexer(long settingsId, String name, String baseUrl, String clientId, String clientSecret, ConcurrentUpdateSolrClient solrUpdateServer, Connection aspenConn, Logger logger) {
		this.settingsId = settingsId;
		this.name = name;
		this.baseUrl = baseUrl;
		if (this.baseUrl.endsWith("/")) {
			this.baseUrl = this.baseUrl.substring(0, this.baseUrl.length() - 1);
		}
		this.clientId = clientId;
		this.clientSecret = clientSecret;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;

		logEntry = new EventsIndexerLogEntry("Communico " + name, aspenConn, logger);

		try {
			addEventStmt = aspenConn.prepareStatement("INSERT INTO communico_events SET settingsId = ?, externalId = ?, title = ?, rawChecksum =?, rawResponse = ?, deleted = 0 ON DUPLICATE KEY UPDATE title = VALUES(title), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), deleted = 0", Statement.RETURN_GENERATED_KEYS);
			deleteEventStmt = aspenConn.prepareStatement("UPDATE communico_events SET deleted = 1 where id = ?");

			PreparedStatement getLibraryScopesStmt = aspenConn.prepareStatement("SELECT subdomain from library inner join library_events_setting on library.libraryId = library_events_setting.libraryId WHERE settingSource = 'communico' AND settingId = ?");
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
			PreparedStatement eventsStmt = aspenConn.prepareStatement("SELECT * from communico_events WHERE settingsId = ? and deleted = 0");
			eventsStmt.setLong(1, this.settingsId);
			ResultSet existingEventsRS = eventsStmt.executeQuery();
			while (existingEventsRS.next()) {
				CommunicoEvent event = new CommunicoEvent(existingEventsRS);
				existingEvents.put(event.getExternalId(), event);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing events for Communico " + name, e);
		}
	}

	private SimpleDateFormat dateParser = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	private SimpleDateFormat eventDayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private SimpleDateFormat eventMonthFormatter = new SimpleDateFormat("yyyy-MM");
	private SimpleDateFormat eventYearFormatter = new SimpleDateFormat("yyyy");

	void indexEvents() {
		GregorianCalendar nextYear = new GregorianCalendar();
		nextYear.setTime(new Date());
		nextYear.add(YEAR, 1);
		JSONArray communicoEvents = getCommunicoEvents();
		if (doFullReload) {
			try {
				solrUpdateServer.deleteByQuery("type:event_communico AND source:" + this.settingsId);
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (HttpSolrClient.RemoteSolrException rse) {
				logEntry.incErrors("Solr is not running properly, try restarting " + rse.toString());
				System.exit(-1);
			} catch (Exception e) {
				logEntry.incErrors("Error deleting from index ", e);
			}
		}

		for (int i = 0; i < communicoEvents.length(); i++){
			try {
				JSONObject curEvent = communicoEvents.getJSONObject(i);
				checksumCalculator.reset();
				String rawResponse = curEvent.toString();
				checksumCalculator.update(rawResponse.getBytes());
				long checksum = checksumCalculator.getValue();

				Integer eventIdRaw = curEvent.getInt("eventId");
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
						SolrInputDocument solrDocument = new SolrInputDocument();
						solrDocument.addField("id", "communico_" + settingsId + "_" + eventId);
						solrDocument.addField("identifier", eventId);
						solrDocument.addField("type", "event_communico");
						solrDocument.addField("source", settingsId);
						solrDocument.addField("url", baseUrl + "/" + eventId);
						int boost = 1;

						solrDocument.addField("last_indexed", new Date());

						Date startDate = getDateForKey(curEvent,"eventStart");
						solrDocument.addField("start_date", startDate);
						solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
						Date endDate = getDateForKey(curEvent,"eventEnd");
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

						//Important info is kept in subtitle, concat main title and subtitle to keep the important info
						String fullTitle = curEvent.getString("title") + ": " + curEvent.getString("subTitle");
						solrDocument.addField("title", fullTitle);

						solrDocument.addField("branch", curEvent.getString("locationName"));

						if (curEvent.isNull("eventType")) {
							solrDocument.addField("event_type", "Undefined");
						} else {
							solrDocument.addField("event_type", curEvent.getString("eventType"));
						}

						//roomName returns null instead of empty string, need to check if null
						if (curEvent.isNull("roomName")){
							solrDocument.addField("room", "");
						}else{
							solrDocument.addField("room", curEvent.getString("roomName"));
						}

						solrDocument.addField("age_group", getNameStringsForKeyCommunico(curEvent, "ages"));
						solrDocument.addField("program_type", getNameStringsForKeyCommunico(curEvent, "types"));
						//may need this down the road: solrDocument.addField("internal_category", getNameStringsForKeyCommunico(curEvent, "searchTags"));

						solrDocument.addField("registration_required", curEvent.getBoolean("registration") ? "Yes" : "No");

						solrDocument.addField("description", curEvent.getString("shortDescription"));

						//eventImage returns null instead of empty string, need to check if null
						if (curEvent.isNull("eventImage")){
							solrDocument.addField("image_url", "");
						}else {
							solrDocument.addField("image_url", curEvent.getString("eventImage"));
						}

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

		for(CommunicoEvent eventInfo : existingEvents.values()){
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

	private HashSet<String> getNameStringsForKeyCommunico(JSONObject curEvent, String keyName) {
		HashSet<String> values = new HashSet<>();
		if (!curEvent.isNull(keyName)){
			JSONArray keyArray = curEvent.getJSONArray(keyName);
			for (int i = 0; i < keyArray.length(); i++){
				values.add(keyArray.getString(i));
			}
		}
		return values;
	}

	private boolean connectToCommunico() throws SocketTimeoutException {
		//Authentication documentation: http://communicocollege.com/1137

		//Check to see if we already have a valid token
		if (communicoAPIToken != null){
			if (communicoAPIExpiration - new Date().getTime() > 0){
				logEntry.incErrors("token is still valid");
				return true;
			}else{
				logEntry.incErrors("Token has expired");
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn;
		try {
			URL emptyIndexURL = new URL("https://api.communico.co/v3/token");
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection) {
				HttpsURLConnection sslConn = (HttpsURLConnection) conn;
				sslConn.setHostnameVerifier((hostname, session) -> {
					//Do not verify host names
					return true;
				});
			}
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/json;charset=UTF-8");
			String encoded = Base64.encodeBase64String((clientId + ":" + clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic " + encoded);
			conn.setReadTimeout(30000);
			conn.setConnectTimeout(30000);
			conn.setDoOutput(true);

			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), StandardCharsets.UTF_8);
			wr.write("{\n" + "\"grant_type\": \"client_credentials\"\n" + "}");
			wr.flush();
			wr.close();

			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				communicoAPIToken = parser.getString("access_token");
				communicoAPITokenType = parser.getString("token_type");
				communicoAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
			} else {
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				return false;
			}
		} catch (SocketTimeoutException toe){
			throw toe;
		} catch (Exception e) {
			logEntry.incErrors("Error connecting to Communico API", e );
			return false;
		}
		return true;
	}

	public JSONArray getCommunicoEvents() {
		try {
			if (connectToCommunico()){
				JSONArray events = new JSONArray();
				CloseableHttpClient httpclient = HttpClients.createDefault();
				LocalDate today = LocalDate.now();
				for (int m = 0; m < 13; m++) {
					LocalDate firstOfMonth;
					if (m == 0) {
						firstOfMonth = today;
					} else {
						firstOfMonth = today.plusMonths(m).with(TemporalAdjusters.firstDayOfMonth());
					}
					LocalDate endOfMonth = today.plusMonths(m).with(TemporalAdjusters.lastDayOfMonth());
					//a limit of 2000 should return all results for the month
					String apiEventsURL = "https://api.communico.co/v3/attend/events";
					apiEventsURL += "?limit=2000";
					apiEventsURL += "&startDate=" + firstOfMonth;
					apiEventsURL += "&endDate=" + endOfMonth;
					//Need to request the fields we want as many are "optional" and aren't returned unless asked for
					apiEventsURL += "&fields=ages,searchTags,registration,eventImage,eventType,registrationOpens,registrationCloses,eventRegistrationUrl,thirdPartyRegistration,waitlist,maxAttendees,totalRegistrants,totalWaitlist,maxWaitlist,types";
					HttpGet apiRequest = new HttpGet(apiEventsURL);
					apiRequest.addHeader("Authorization", communicoAPITokenType + " " + communicoAPIToken);
					try (CloseableHttpResponse response1 = httpclient.execute(apiRequest)) {
						StatusLine status = response1.getStatusLine();
						HttpEntity entity1 = response1.getEntity();
						if (status.getStatusCode() == 200) {
							String response = EntityUtils.toString(entity1);
							JSONObject response2 = new JSONObject(response);
							JSONObject data = response2.getJSONObject("data");
							JSONArray events1 = data.getJSONArray("entries");
							for (int i = 0; i < events1.length(); i++) {
								events.put(events1.get(i));
							}
						}
					} catch (Exception e) {
						logEntry.incErrors("Error getting events from " + apiEventsURL, e);
					}
				}
				return events;
			}
		} catch (Exception e) {
			logEntry.incErrors("Error getting events", e);
		}
		return null;
	}
}
