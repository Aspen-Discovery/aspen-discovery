package com.turning_leaf_technologies.cron.reading_history;

import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

public class UpdateReadingHistoryTask implements Runnable {
	private static long numTasksRun = 0;
	private final String aspenUrl;
	private final String cat_username;
	private final String cat_password;
	private final CronProcessLogEntry processLog;
	private final Logger logger;
	UpdateReadingHistoryTask(String aspenUrl, String cat_username, String cat_password, CronProcessLogEntry processLog, Logger logger) {
		this.aspenUrl = aspenUrl;
		this.cat_username = cat_username;
		this.cat_password = cat_password;
		this.processLog = processLog;
		this.logger = logger;
	}

	@Override
	public void run() {
		boolean hadError = false;
		boolean wasSkipped = false;
		try {
			int numTries = 0;
			boolean retry = true;
			while (retry) {
				numTries++;
				if (numTries > 1){
					logger.debug("Try " + numTries + " for " + cat_username);
					//Wait 2 minutes to give solr a chance to restart if needed
					try {
						Thread.sleep(120000);
					} catch (InterruptedException e) {
						processLog.incErrors("Interrupted sleep when retrying to load");
					}
				}else{
					logger.debug(++numTasksRun + ") Updating reading history for " + cat_username);
				}
				retry = false;
				// Call the patron API to get their checked out items
				URL patronApiUrl = new URL(aspenUrl + "/API/UserAPI?method=updatePatronReadingHistory&username=" + URLEncoder.encode(cat_username, StandardCharsets.UTF_8));
				//logger.error("Updating reading history for " + cat_username);
				HttpURLConnection conn = (HttpURLConnection) patronApiUrl.openConnection();
				//Give 10 seconds for connection timeout and 10 minutes for read timeout
				conn.setConnectTimeout(10000);
				conn.setReadTimeout(600000);
				conn.addRequestProperty("User-Agent", "Aspen Discovery");
				conn.addRequestProperty("Accept", "*/*");
				conn.addRequestProperty("Cache-Control", "no-cache");
				if (conn.getResponseCode() == 200) {
					String patronDataJson = AspenStringUtils.convertStreamToString(conn.getInputStream());
					logger.debug("Got results for " + cat_username);
					try {
						JSONObject patronData = new JSONObject(patronDataJson);
						JSONObject result = patronData.getJSONObject("result");
						hadError = !result.getBoolean("success");
						if (hadError) {
							String message = result.getString("message");
							if (!message.equals("Login unsuccessful")) {
								processLog.incErrors("Updating reading history failed for " + cat_username + " " + message);
							} else {
								//This happens if the patron has changed their login or no longer exists.
								processLog.incSkipped();
								//Don't log that we couldn't update them, the skipped is enough
								logger.debug("Updating reading history failed for " + cat_username + " " + message);
								wasSkipped = true;
								//processLog.addNote("Updating reading history failed for " + cat_username + " " + message);
							}
						}else{
							//We can also have things skipped if the last changed hasn't updated or the patron expires
							if (result.getBoolean("skipped")){
								processLog.incSkipped();
								wasSkipped = true;
							}
						}
					} catch (JSONException e) {
						processLog.incErrors("Unable to load patron information for " + cat_username + " exception loading response ", e);
						logger.error(patronDataJson);
						hadError = true;
					}
				} else {
					//Received an error
					String errorResponse = AspenStringUtils.convertStreamToString(conn.getErrorStream());
					if (numTries < 3){
						retry = true;
					}else{
						processLog.incErrors("Error " + conn.getResponseCode() + " retrieving information from patron API for " + cat_username + " base url is " + aspenUrl + " " + errorResponse);
						hadError = true;
					}
				}
			}
		} catch (MalformedURLException e) {
			processLog.incErrors("Bad url for patron API " + e);
			hadError = true;
		} catch (IOException e) {
			String errorMessage = e.getMessage();
			//noinspection SpellCheckingInspection
			errorMessage = errorMessage.replaceAll(cat_password, "XXXX");
			processLog.incErrors("Unable to retrieve information from patron API for " + cat_username + " base url is " + aspenUrl + " " + errorMessage);
			hadError = true;
		}
		if (!hadError && !wasSkipped){
			processLog.incUpdated();
		}
	}
}
