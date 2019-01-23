package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;


import java.io.IOException;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.Iterator;

public class UpdateReadingHistory implements IProcessHandler {
	private CronProcessLogEntry processLog;
	private PreparedStatement insertReadingHistoryStmt;
	private String vufindUrl;
	private Logger logger;

	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Update Reading History");
		processLog.saveToDatabase(vufindConn, logger);
		
		this.logger = logger;
		logger.info("Updating Reading History");
		processLog.addNote("Updating Reading History");

		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			processLog.incErrors();
			processLog.addNote("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return;
		}

		// Connect to the VuFind MySQL database
		try {
			// Get a list of all patrons that have reading history turned on.
			PreparedStatement getUsersStmt = vufindConn.prepareStatement("SELECT id, cat_username, cat_password, initialReadingHistoryLoaded FROM user where trackReadingHistory=1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement updateInitialReadingHistoryLoaded = vufindConn.prepareStatement("UPDATE user SET initialReadingHistoryLoaded = 1 WHERE id = ?");

			PreparedStatement getCheckedOutTitlesForUser = vufindConn.prepareStatement("SELECT id, groupedWorkPermanentId, source, sourceId, title FROM user_reading_history_work WHERE userId=? and checkInDate is null", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement updateReadingHistoryStmt = vufindConn.prepareStatement("UPDATE user_reading_history_work SET checkInDate=? WHERE id = ?");
			insertReadingHistoryStmt = vufindConn.prepareStatement("INSERT INTO user_reading_history_work (userId, groupedWorkPermanentId, source, sourceId, title, author, format, checkOutDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			
			ResultSet userResults = getUsersStmt.executeQuery();
			while (userResults.next()) {
				// For each patron
				Long userId = userResults.getLong("id");
				String cat_username = userResults.getString("cat_username");
				String cat_password = userResults.getString("cat_password");

				boolean initialReadingHistoryLoaded = userResults.getBoolean("initialReadingHistoryLoaded");
				boolean errorLoadingInitialReadingHistory = false;
				if (!initialReadingHistoryLoaded){
					//Get the initial reading history from the ILS
					try {
						if (loadInitialReadingHistoryForUser(userId, cat_username, cat_password)) {
							updateInitialReadingHistoryLoaded.setLong(1, userId);
							updateInitialReadingHistoryLoaded.executeUpdate();
						}else{
							errorLoadingInitialReadingHistory = true;
						}
					}catch (SQLException e){
						logger.error("Error loading initial reading history", e);
						errorLoadingInitialReadingHistory = true;
					}
				}

				if (!errorLoadingInitialReadingHistory) {
					//Get a list of titles that are currently checked out
					getCheckedOutTitlesForUser.setLong(1, userId);
					ResultSet checkedOutTitlesRS = getCheckedOutTitlesForUser.executeQuery();
					ArrayList<CheckedOutTitle> checkedOutTitles = new ArrayList<>();
					while (checkedOutTitlesRS.next()) {
						CheckedOutTitle curCheckout = new CheckedOutTitle();
						curCheckout.setId(checkedOutTitlesRS.getLong("id"));
						curCheckout.setGroupedWorkPermanentId(checkedOutTitlesRS.getString("groupedWorkPermanentId"));
						curCheckout.setSource(checkedOutTitlesRS.getString("source"));
						curCheckout.setSourceId(checkedOutTitlesRS.getString("sourceId"));
						curCheckout.setTitle(checkedOutTitlesRS.getString("title"));
						checkedOutTitles.add(curCheckout);
					}

					logger.info("Loading Reading History for patron " + cat_username);
					processTitlesForUser(userId, cat_username, cat_password, checkedOutTitles);

					//Any titles that are left in checkedOutTitles were checked out previously and are no longer checked out.
					Long curTime = new Date().getTime() / 1000;
					for (CheckedOutTitle curTitle : checkedOutTitles) {
						updateReadingHistoryStmt.setLong(1, curTime);
						updateReadingHistoryStmt.setLong(2, curTitle.getId());
						updateReadingHistoryStmt.executeUpdate();
					}
				}

				processLog.incUpdated();
				processLog.saveToDatabase(vufindConn, logger);
				try {
					Thread.sleep(1000);
				}catch (Exception e){
					logger.warn("Sleep was interrupted while processing reading history for user.");
				}
			}
			userResults.close();
		} catch (SQLException e) {
			logger.error("Unable get a list of users that need to have their reading list updated ", e);
			processLog.incErrors();
			processLog.addNote("Unable get a list of users that need to have their reading list updated " + e.toString());
		}
		
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	private boolean loadInitialReadingHistoryForUser(Long userId, String cat_username, String cat_password) throws SQLException {
		boolean hadError = false;
		try {
			// Call the patron API to get their checked out items
			URL patronApiUrl = new URL(vufindUrl + "/API/UserAPI?method=getPatronReadingHistory&username=" + URLEncoder.encode(cat_username) + "&password=" + URLEncoder.encode(cat_password));
			logger.debug("Loading initial reading history from " + patronApiUrl);
			Object patronDataRaw = patronApiUrl.getContent();
			if (patronDataRaw instanceof InputStream) {
				String patronDataJson = Util.convertStreamToString((InputStream) patronDataRaw);
				logger.debug(patronApiUrl.toString());
				logger.debug("Json for patron reading history " + patronDataJson);
				try {
					JSONObject patronData = new JSONObject(patronDataJson);
					JSONObject result = patronData.getJSONObject("result");
					if (result.getBoolean("success") && result.has("readingHistory")) {
						if (result.get("readingHistory").getClass() == JSONObject.class){
							JSONObject readingHistoryItems = result.getJSONObject("readingHistory");
							@SuppressWarnings("unchecked")
							Iterator<String> keys = (Iterator<String>) readingHistoryItems.keys();
							while (keys.hasNext()) {
								String curKey = keys.next();
								JSONObject readingHistoryItem = readingHistoryItems.getJSONObject(curKey);
								processReadingHistoryTitle(readingHistoryItem, userId);

							}
						}else if (result.get("readingHistory").getClass() == JSONArray.class){
							JSONArray readingHistoryItems = result.getJSONArray("readingHistory");
							for (int i = 0; i < readingHistoryItems.length(); i++){
								processReadingHistoryTitle(readingHistoryItems.getJSONObject(i), userId);
							}
						}else{
							processLog.incErrors();
							processLog.addNote("Unexpected JSON for patron reading history " + result.get("readingHistory").getClass());
							hadError = true;
						}
					} else {
						logger.info("Call to getPatronReadingHistory returned a success code of false for " + cat_username);
						hadError = true;
					}
				} catch (JSONException e) {
					logger.error("Unable to load patron information from for " + cat_username + " exception loading response ", e);
					logger.error(patronDataJson);
					processLog.incErrors();
					processLog.addNote("Unable to load patron information from for " + cat_username + " exception loading response " + e.toString());
					hadError = true;
				}
			} else {
				logger.error("Unable to load patron information from for " + cat_username + ": expected to get back an input stream, received a "
						+ patronDataRaw.getClass().getName());
				processLog.incErrors();
				hadError = true;
			}
		} catch (MalformedURLException e) {
			logger.error("Bad url for patron API " + e.toString());
			processLog.incErrors();
			hadError = true;
		} catch (IOException e) {
			logger.error("Unable to retrieve information from patron API for " + cat_username /*+ ": " + e.toString()*/);
			processLog.incErrors();
			hadError = true;
		}
		return !hadError;
	}

	private boolean processReadingHistoryTitle(JSONObject readingHistoryTitle, Long userId) throws JSONException {
		String source = "ILS";
		String sourceId = "";
		if (readingHistoryTitle.has("recordId")) {
			sourceId = readingHistoryTitle.getString("recordId");
		}
		if (sourceId == null || sourceId.length() == 0){
			//Don't try to add records we know nothing about.
			return false;
		}
		SimpleDateFormat checkoutDateFormat = new SimpleDateFormat("MM-dd-yyyy");

		//This is a newly checked out title
		try {
			insertReadingHistoryStmt.setLong(1, userId);
			insertReadingHistoryStmt.setString(2, readingHistoryTitle.getString("permanentId") == null ? "" : readingHistoryTitle.getString("permanentId"));
			insertReadingHistoryStmt.setString(3, source);
			insertReadingHistoryStmt.setString(4, sourceId);
			insertReadingHistoryStmt.setString(5, Util.trimTo(150, readingHistoryTitle.getString("title")));
			insertReadingHistoryStmt.setString(6, Util.trimTo(75, readingHistoryTitle.has("author") ? readingHistoryTitle.getString("author") : ""));
			String format = readingHistoryTitle.getString("format");
			if (format.startsWith("[")){
				//This is an array of formats, just grab one
				format = format.replace("[", "");
				format = format.replace("]", "");
				format = format.replace("\"", "");
				String[] formats = format.split(",");
				format = formats[0];
			}
			insertReadingHistoryStmt.setString(7, Util.trimTo(50, format));
			String checkoutDate = readingHistoryTitle.getString("checkout");
			long checkoutTime = new Date().getTime();
			if (checkoutDate.matches("^\\d+$")){
				checkoutTime = Long.parseLong(checkoutDate);
			}else{
				try {
					checkoutTime = checkoutDateFormat.parse(checkoutDate).getTime() / 1000;
				} catch (ParseException e) {
					logger.error("Error loading checkout date " + checkoutDate + " was not the expected format");
				}
			}

			insertReadingHistoryStmt.setLong(8, checkoutTime);
			insertReadingHistoryStmt.executeUpdate();
			processLog.incUpdated();
			return true;
		}catch (SQLException e){
			logger.error("Error adding title for user " + userId + " " + readingHistoryTitle.getString("title"), e);
			processLog.incErrors();
			return false;
		}
	}

	private void processTitlesForUser(Long userId, String cat_username, String cat_password, ArrayList<CheckedOutTitle> checkedOutTitles) throws SQLException{
		try {
			// Call the patron API to get their checked out items
			URL patronApiUrl = new URL(vufindUrl + "/API/UserAPI?method=getPatronCheckedOutItems&username=" + URLEncoder.encode(cat_username) + "&password=" + URLEncoder.encode(cat_password));
			Object patronDataRaw = patronApiUrl.getContent();
			if (patronDataRaw instanceof InputStream) {
				String patronDataJson = Util.convertStreamToString((InputStream) patronDataRaw);
				logger.debug(patronApiUrl.toString());
				logger.debug("Json for patron checked out items " + patronDataJson);
				try {
					JSONObject patronData = new JSONObject(patronDataJson);
					JSONObject result = patronData.getJSONObject("result");
					if (result.getBoolean("success") && result.has("checkedOutItems")) {
						if (result.get("checkedOutItems").getClass() == JSONObject.class){
							JSONObject checkedOutItems = result.getJSONObject("checkedOutItems");
							@SuppressWarnings("unchecked")
							Iterator<String> keys = (Iterator<String>) checkedOutItems.keys();
							while (keys.hasNext()) {
								String curKey = keys.next();
								JSONObject checkedOutItem = checkedOutItems.getJSONObject(curKey);
								processCheckedOutTitle(checkedOutItem, userId, checkedOutTitles);
								
							}
						}else if (result.get("checkedOutItems").getClass() == JSONArray.class){
							JSONArray checkedOutItems = result.getJSONArray("checkedOutItems");
							for (int i = 0; i < checkedOutItems.length(); i++){
								processCheckedOutTitle(checkedOutItems.getJSONObject(i), userId, checkedOutTitles);
							}
						}else{
							processLog.incErrors();
							processLog.addNote("Unexpected JSON for patron checked out items received " + result.get("checkedOutItems").getClass());
						}
					} else {
						logger.info("Call to getPatronCheckedOutItems returned a success code of false for " + cat_username);
					}
				} catch (JSONException e) {
					logger.error("Unable to load patron information from for " + cat_username + " exception loading response ", e);
					logger.error(patronDataJson);
					processLog.incErrors();
					processLog.addNote("Unable to load patron information from for " + cat_username + " exception loading response " + e.toString());
				}
			} else {
				logger.error("Unable to load patron information from for " + cat_username + ": expected to get back an input stream, received a "
						+ patronDataRaw.getClass().getName());
				processLog.incErrors();
			}
		} catch (MalformedURLException e) {
			logger.error("Bad url for patron API " + e.toString());
			processLog.incErrors();
		} catch (IOException e) {
			logger.error("Unable to retrieve information from patron API for " + cat_username /*+ ": " + e.toString()*/);
			processLog.incErrors();
		}
	}
	
	private boolean processCheckedOutTitle(JSONObject checkedOutItem, long userId, ArrayList<CheckedOutTitle> checkedOutTitles) throws JSONException, SQLException, IOException{
		try {
			// System.out.println(checkedOutItem.toString());
			String source = checkedOutItem.getString("checkoutSource");
			String sourceId = "?";
			switch (source) {
				case "OverDrive":
					sourceId = checkedOutItem.getString("overDriveId");
					break;
				case "ILS":
					sourceId = checkedOutItem.getString("id");
					break;
				case "Hoopla":
					sourceId = checkedOutItem.getString("hooplaId");
					break;
				case "eContent":
					source = checkedOutItem.getString("recordType");
					sourceId = checkedOutItem.getString("id");
					break;
				default:
					logger.error("Unknown source updating reading history: " + source);
			}

			//Check to see if this is an existing checkout.  If it is, skip inserting
			if (checkedOutTitles != null) {
				for (CheckedOutTitle curTitle : checkedOutTitles) {
					boolean sourceMatches = Util.compareStrings(curTitle.getSource(), source);
					boolean sourceIdMatches = Util.compareStrings(curTitle.getSourceId(), sourceId);
					boolean titleMatches = Util.compareStrings(curTitle.getTitle(), checkedOutItem.getString("title"));
					if (
							(sourceMatches && sourceIdMatches) ||
							titleMatches
						 ) {
						checkedOutTitles.remove(curTitle);
						return true;
					}
				}
			}

		//This is a newly checked out title
			insertReadingHistoryStmt.setLong(1, userId);
			String groupedWorkId = checkedOutItem.has("groupedWorkId") ? checkedOutItem.getString("groupedWorkId") : "";
			if (groupedWorkId == null){
				groupedWorkId = "";
			}
			insertReadingHistoryStmt.setString(2, groupedWorkId);
			insertReadingHistoryStmt.setString(3, source);
			insertReadingHistoryStmt.setString(4, sourceId);
			insertReadingHistoryStmt.setString(5, Util.trimTo(150, checkedOutItem.getString("title")));
			insertReadingHistoryStmt.setString(6, checkedOutItem.has("author") ? Util.trimTo(75, checkedOutItem.getString("author")) : "");
			insertReadingHistoryStmt.setString(7, checkedOutItem.has("format") ? Util.trimTo(50, checkedOutItem.getString("format")) : "");
			long checkoutTime = new Date().getTime() / 1000;
			insertReadingHistoryStmt.setLong(8, checkoutTime);
			insertReadingHistoryStmt.executeUpdate();
			processLog.incUpdated();
			return true;
		}catch (Exception e){
			if (checkedOutItem.has("title")) {
				logger.error("Error adding title for user " + userId + " " + checkedOutItem.getString("title"), e);
			}else{
				logger.error("Error adding title for user " + userId + " " + checkedOutItem.toString(), e);
			}
			processLog.incErrors();
			return false;
		}
	}
}
