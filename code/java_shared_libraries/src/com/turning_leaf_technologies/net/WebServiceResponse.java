package com.turning_leaf_technologies.net;

import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class WebServiceResponse {
	private static Logger logger = LogManager.getLogger("WebServiceResponse");
	private boolean success;
	private int responseCode;
	private String message;
	private boolean callTimedOut = false;
	private Map<String, List<String>> responseHeaders = new HashMap();

	public WebServiceResponse(boolean success, int responseCode, String message, Map<String, List<String>> responseHeaders){
		this.success = success;
		this.responseCode = responseCode;
		this.message = message;
		this.responseHeaders = responseHeaders;
	}
	public WebServiceResponse(boolean success, int responseCode, String message){
		this.success = success;
		this.responseCode = responseCode;
		this.message = message;
	}
	public boolean isSuccess() {
		return success;
	}
	public void setSuccess(boolean success) {
		this.success = success;
	}
	public int getResponseCode() {
		return responseCode;
	}
	public void setResponseCode(int responseCode) {
		this.responseCode = responseCode;
	}
	public String getMessage() {
		return message;
	}
	public void setMessage(String message) {
		this.message = message;
	}
	public JSONObject getJSONResponse() {
		try {
			return new JSONObject(message);
		} catch (JSONException e) {
			logger.error("Error parsing json from webservice response", e);
			return null;
		}
	}
	public JSONArray getJSONResponseAsArray() {
		try {
			return new JSONArray(new String(message.getBytes(StandardCharsets.UTF_8)));
		} catch (JSONException e) {
			logger.error("Error parsing json from webservice response", e);
			return null;
		}
	}

	public boolean isCallTimedOut() {
		return callTimedOut;
	}

	void setCallTimedOut(boolean callTimedOut) {
		this.callTimedOut = callTimedOut;
	}

	public String getResponseHeaderValue(String headerName) {
		if (responseHeaders != null) {
			if (responseHeaders.containsKey(headerName)){
				List<String> headerValues = responseHeaders.get(headerName);
				if (headerValues.size() > 0) {
					return headerValues.get(0);
				}
			}
		}
		return null;
	}
}
