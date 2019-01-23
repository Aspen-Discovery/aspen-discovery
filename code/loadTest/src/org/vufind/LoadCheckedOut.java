package org.vufind;

public class LoadCheckedOut extends TestTask{
	@Override
	public String getTestUrl() {
		//Get test user
		TestUser testUser = getRandomTestUser();
		
		return this.baseUrl + "/API/UserAPI?method=getPatronCheckedOutItems&username=" + testUser.getUsername() + "&password=" + testUser.getPassword();
	}

	@Override
	public boolean validateTest(String pageContents) {
		if (pageContents.matches("(?si)\\{\"result\":\\{\"success\":true,\"checkedOutItems\":.*?\\}\\}")){
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
