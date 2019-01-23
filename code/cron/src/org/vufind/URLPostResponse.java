package org.vufind;

public class URLPostResponse {
	private boolean success;
	private int responseCode;
	private String message;
	public URLPostResponse(boolean success, int responseCode, String message){
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
}
