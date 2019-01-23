package org.vufind;

public class LoadOverdriveHolds extends TestTask{
	@Override
	public String getTestUrl() {
		//Get test user
		TestUser testUser = getRandomTestUser();
		
		return this.baseUrl + "/API/UserAPI?method=getPatronHoldsOverDrive&username=" + testUser.getUsername() + "&password=" + testUser.getPassword();
	}

	@Override
	public boolean validateTest(String pageContents) {
		if (pageContents.matches("(?si)\\{\"result\":\\{\"success\":true,\"holds\":\\{\"available\":\\[.*?\\],\"unavailable\":\\[.*?\\]\\}\\}\\}")){
			return true;
		}
		return false;
	}
	
	@Override
	public boolean expectHTML() {
		return true;
	}

	@Override
	public boolean expectImage() {
		return false;
	}
}
