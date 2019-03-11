package com.turning_leaf_technologies.overdrive;

import org.json.JSONObject;

/**
 * Represents a call to the webservice encapsulating the response code as well as the JSON response.
 * Pika
 * User: Mark Noble
 * Date: 4/7/2015
 * Time: 9:13 AM
 */
public class WebServiceResponse {
	private JSONObject response;
	private int responseCode;
	private String error;

	public JSONObject getResponse() {
		return response;
	}

	public void setResponse(JSONObject response) {
		this.response = response;
	}

	public int getResponseCode() {
		return responseCode;
	}

	public void setResponseCode(int responseCode) {
		this.responseCode = responseCode;
	}

	public void setError(String error) {
		this.error = error;
	}

	public String getError() {
		return error;
	}
}
