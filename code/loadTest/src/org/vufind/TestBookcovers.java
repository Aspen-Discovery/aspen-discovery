package org.vufind;

public class TestBookcovers extends TestTask{

	@Override
	public String getTestUrl() {
		TestResource testResource = getRandomTestResource();
		if (testResource.getSource().equalsIgnoreCase("econtent")){
			return this.baseUrl + "/bookcover.php?econtent=true&id=" + testResource.getRecord_id() + "&size=medium&isn=" + testResource.getIsbn() + "&upc=" + testResource.getUpc() + "&category=" + testResource.getFormat_category();
		}else{
			return this.baseUrl + "/bookcover.php?id=" + testResource.getRecord_id() + "&size=medium&isn=" + testResource.getIsbn() + "&upc=" + testResource.getUpc() + "&category=" + testResource.getFormat_category();
		}
	}

	@Override
	public boolean validateTest(String pageContents) {
		return true;
	}

	@Override
	public boolean expectHTML() {
		return false;
	}

	@Override
	public boolean expectImage() {
		return true;
	}
}
