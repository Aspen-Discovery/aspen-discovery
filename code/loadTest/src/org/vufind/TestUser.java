package org.vufind;

public class TestUser {
	private String username;
	private String password;
	private boolean updateable;
	public TestUser(String username, String password, boolean updateable){
		this.username = username;
		this.password = password;
		this.updateable = updateable;
	}
	public String getUsername() {
		return username;
	}
	public String getPassword() {
		return password;
	}
}
