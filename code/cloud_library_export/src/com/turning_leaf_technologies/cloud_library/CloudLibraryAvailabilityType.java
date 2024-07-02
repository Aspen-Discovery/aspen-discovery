package com.turning_leaf_technologies.cloud_library;

class CloudLibraryAvailabilityType {
	private int availabilityType = 1;

	private String rawResponse = "";

	int getAvailabilityType() {
		return availabilityType;
	}

	void setAvailabilityType(int availabilityType) {
		this.availabilityType = availabilityType;
	}

	void setRawResponse(String message) {
		this.rawResponse = message;
	}

	String getRawResponse() {
		return this.rawResponse;
	}
}
