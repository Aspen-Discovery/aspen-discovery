package org.vufind;

public class LoadOverdriveCheckedOut extends TestTask{
	@Override
	public String getTestUrl() {
		//Get test user
		TestUser testUser = getRandomTestUser();
		
		return this.baseUrl + "/API/UserAPI?method=getPatronCheckedOutItemsOverDrive&username=" + testUser.getUsername() + "&password=" + testUser.getPassword();
	}

	@Override
	public boolean validateTest(String pageContents) {
		if (pageContents.matches("(?si)\\{\"result\":\\{\"success\":true,\"items\":\\[.*?\\]\\}\\}")){
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
