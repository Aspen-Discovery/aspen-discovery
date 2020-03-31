package com.turning_leaf_technologies.cloud_library;

class CloudLibraryAvailability {
	private int totalCopies;
	private int sharedCopies;
	private int totalLoanCopies;
	private int totalHoldCopies;
	private int sharedLoanCopies;
	private String rawResponse = "";

	int getTotalCopies() {
		return totalCopies;
	}

	void setTotalCopies(int totalCopies) {
		this.totalCopies = totalCopies;
	}

	int getSharedCopies() {
		return sharedCopies;
	}

	void setSharedCopies(int sharedCopies) {
		this.sharedCopies = sharedCopies;
	}

	int getTotalLoanCopies() {
		return totalLoanCopies;
	}

	void setTotalLoanCopies(int totalLoanCopies) {
		this.totalLoanCopies = totalLoanCopies;
	}

	int getTotalHoldCopies() {
		return totalHoldCopies;
	}

	void setTotalHoldCopies(int totalHoldCopies) {
		this.totalHoldCopies = totalHoldCopies;
	}

	int getSharedLoanCopies() {
		return sharedLoanCopies;
	}

	void setSharedLoanCopies(int sharedLoanCopies) {
		this.sharedLoanCopies = sharedLoanCopies;
	}

	void setRawResponse(String message) {
		this.rawResponse = message;
	}

	String getRawResponse() {
		return this.rawResponse;
	}
}
