package com.turning_leaf_technogies.axis360;

import com.turning_leaf_technologies.net.WebServiceResponse;
import org.json.JSONObject;

public class AvailabilityResponse {
	public boolean callSucceeded = false;
	public WebServiceResponse response = null;
	public JSONObject titleInformation = null;
	public boolean titleIsUnavailable = false;
}
