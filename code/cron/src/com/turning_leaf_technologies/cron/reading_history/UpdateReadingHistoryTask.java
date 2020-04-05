package com.turning_leaf_technologies.cron.reading_history;

import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;

public class UpdateReadingHistoryTask implements Runnable {
	private String aspenUrl;
	private long userId;
	private String cat_username;
	private String cat_password;
	private CronProcessLogEntry processLog;
	private Logger logger;
	UpdateReadingHistoryTask(String aspenUrl, String cat_username, String cat_password, CronProcessLogEntry processLog, Logger logger) {
		this.aspenUrl = aspenUrl;
		this.cat_username = cat_username;
		this.cat_password = cat_password;
		this.processLog = processLog;
		this.logger = logger;
	}

	@Override
	public void run() {
		boolean hadError;
		try {
			// Call the patron API to get their checked out items
			URL patronApiUrl = new URL(aspenUrl + "/API/UserAPI?method=updatePatronReadingHistory&username=" + URLEncoder.encode(cat_username, "UTF-8") + "&password=" + URLEncoder.encode(cat_password, "UTF-8"));
			//logger.error("Updating reading history for " + cat_username);
			Object responseRaw = patronApiUrl.getContent();
			if (responseRaw instanceof InputStream) {
				String patronDataJson = StringUtils.convertStreamToString((InputStream) responseRaw);
				logger.debug(patronApiUrl.toString());
				logger.debug("Json for patron reading history " + patronDataJson);
				//logger.error("Got results for " + cat_username);
				try {
					JSONObject patronData = new JSONObject(patronDataJson);
					JSONObject result = patronData.getJSONObject("result");
					hadError = !result.getBoolean("success");
					if (hadError){
						String message = result.getString("message");
						if (!message.equals("Login unsuccessful")) {
							processLog.incErrors("Updating reading history failed for " + cat_username + " " + message);
						}else{
							//This happens if the patron has changed their login or no longer exists.
							processLog.incSkipped();
							processLog.addNote("Updating reading history failed for " + cat_username + " " + message);
						}
					}
				} catch (JSONException e) {
					processLog.incErrors("Unable to load patron information from for " + cat_username + " exception loading response ", e);
					logger.error(patronDataJson);
					hadError = true;
				}
			} else {
				processLog.incErrors("Unable to load patron information from for " + cat_username + ": expected to get back an input stream, received a "
						+ responseRaw.getClass().getName());
				hadError = true;
			}
		} catch (MalformedURLException e) {
			processLog.incErrors("Bad url for patron API " + e.toString());
			hadError = true;
		} catch (IOException e) {
			processLog.incErrors("Unable to retrieve information from patron API for " + cat_username /*+ ": " + e.toString()*/);
			hadError = true;
		}
		if (!hadError){
			processLog.incUpdated();
		}
	}
}
