package org.innovative;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 8/6/13
 * Time: 4:45 PM
 */
public class OfflineCirculationResult {
	private boolean success;
	private String note;

	public boolean isSuccess() {
		return success;
	}

	public void setSuccess(boolean success) {
		this.success = success;
	}

	public String getNote() {
		return note;
	}

	public void setNote(String note) {
		this.note = note;
	}
}
