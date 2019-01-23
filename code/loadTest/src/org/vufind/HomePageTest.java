package org.vufind;

public class HomePageTest extends TestTask{

	@Override
	public String getTestUrl() {
		return this.baseUrl;
	}

	@Override
	public boolean validateTest(String pageContents) {
		if (pageContents.matches("(?si).*Catalog\\sHome.*")){
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
