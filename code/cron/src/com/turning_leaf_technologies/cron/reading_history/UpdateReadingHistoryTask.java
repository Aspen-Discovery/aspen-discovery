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
import java.util.concurrent.atomic.AtomicLong;

public class UpdateReadingHistoryTask implements Runnable {
	private static long numTasksRun = 0;
	private static final AtomicLong loginUnsuccessfulCount = new AtomicLong();
	private static final AtomicLong userNotFoundCount = new AtomicLong();
	private final String aspenUrl;
	private final String ilsBarcode;
	private final String ilsPassword;
	private final CronProcessLogEntry processLog;
	private final Logger logger;
	UpdateReadingHistoryTask(String aspenUrl, String ilsBarcode, String ilsPassword, CronProcessLogEntry processLog, Logger logger) {
		this.aspenUrl = aspenUrl;
		this.ilsBarcode = ilsBarcode;
		this.ilsPassword = ilsPassword;
		this.processLog = processLog;
		this.logger = logger;
	}

	/**
	 * @return Number of skipped updates due to an unsuccessful login.
	 */
	public static long getLoginUnsuccessfulCount() {
		return loginUnsuccessfulCount.get();
	}

	/**
	 * @return Number of skipped updates because a user could not be found by barcode.
	 */
	public static long getUserNotFoundCount() {
		return userNotFoundCount.get();
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
					logger.debug("Try {} for {}.", numTries, ilsBarcode);
					try {
						// Wait 30 seconds before retrying.
						Thread.sleep(30000);
					} catch (InterruptedException e) {
						processLog.incErrors("Interrupted sleep when retrying to load.");
					}
				} else {
					logger.debug("{}) Updating reading history for {}.", ++numTasksRun, ilsBarcode);
				}
				retry = false;
				// Call the patron API to get their checked out items.
				// Use localhost to avoid the request leaving the server and being subject to WAF rules.
				// The Host header routes the request to the correct virtual host. Sending it requires
				// allowing restricted headers, which is enabled in Cron.main().
				String hostName = new URL(aspenUrl).getHost();
				URL patronApiUrl = new URL("http://localhost/API/UserAPI?method=updatePatronReadingHistory&username=" + URLEncoder.encode(ilsBarcode, StandardCharsets.UTF_8));
				HttpURLConnection conn = (HttpURLConnection) patronApiUrl.openConnection();
				conn.setRequestProperty("Host", hostName);
				// Give 10 seconds for connection timeout and 10 minutes for read timeout.
				conn.setConnectTimeout(10000);
				conn.setReadTimeout(600000);
				conn.addRequestProperty("User-Agent", "Aspen Discovery");
				conn.addRequestProperty("Accept", "*/*");
				conn.addRequestProperty("Cache-Control", "no-cache");
				if (conn.getResponseCode() == 200) {
					String patronDataJson = AspenStringUtils.convertStreamToString(conn.getInputStream());
					logger.debug("Got results for {}.", ilsBarcode);
					try {
						JSONObject patronData = new JSONObject(patronDataJson);
						JSONObject result;
						if (patronData.has("result")) {
							result = patronData.getJSONObject("result");
						} else {
							result = patronData;
						}
						hadError = !result.getBoolean("success");
						if (hadError) {
							String message = result.getString("message");
							// Treat login failure or missing user as skipped rather than an error.
							// The missing-user case may be an odd race condition, where users are being deleted as the API endpoint is reached.
							if (message.equals("Login unsuccessful")) {
								loginUnsuccessfulCount.incrementAndGet();
								processLog.incSkipped();
								logger.debug("Updating reading history skipped for {}: login unsuccessful.", ilsBarcode);
								wasSkipped = true;
							} else if (message.equals("Could not find a user with that barcode")) {
								userNotFoundCount.incrementAndGet();
								processLog.incSkipped();
								logger.debug("Updating reading history skipped for {}: user not found.", ilsBarcode);
								wasSkipped = true;
							} else {
								processLog.incErrors("Updating reading history failed for " + ilsBarcode + ": " + message + ".");
							}
						} else {
							// Updates may be skipped if the last changed hasn't updated or the patron's account expired.
							if (result.getBoolean("skipped")){
								processLog.incSkipped();
								wasSkipped = true;
							}
						}
					} catch (JSONException e) {
						processLog.incErrors("Unable to load patron information for " + ilsBarcode + " exception loading response: ", e);
						logger.error(patronDataJson);
						hadError = true;
					}
				} else {
					String errorResponse = AspenStringUtils.convertStreamToString(conn.getErrorStream());
					if (numTries < 3){
						retry = true;
					}else{
						processLog.incErrors("Failed " + conn.getResponseCode() + " retrieving information from patron API for " + ilsBarcode + " base url is " + aspenUrl + ": " + errorResponse);
						hadError = true;
					}
				}
			}
		} catch (MalformedURLException e) {
			processLog.incErrors("Bad URL for patron API: " + e);
			hadError = true;
		} catch (IOException e) {
			String errorMessage = e.getMessage();
			errorMessage = errorMessage.replaceAll(ilsPassword, "XXXX");
			processLog.incErrors("Unable to retrieve information from patron API for " + ilsBarcode + "; base URL is " + aspenUrl + ": " + errorMessage);
			hadError = true;
		}
		if (!hadError && !wasSkipped){
			processLog.incUpdated();
		}
	}
}