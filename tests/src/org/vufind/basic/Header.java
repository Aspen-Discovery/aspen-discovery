package org.vufind.basic;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.openqa.selenium.By;
import org.openqa.selenium.Dimension;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.vufind.TestResults;
import org.vufind.VuFindTest;

import java.util.List;

/**
 * Tests of the VuFind Header
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/21/13
 * Time: 10:10 AM
 */
public class Header extends VuFindTest {

	@Override
	public void runTests(WebDriver driver, Ini configIni, TestResults results, Logger logger) {
		String testUrl = configIni.get("general", "testUrl");
		driver.get(testUrl);
		results.incTests();

		//Start with a relatively large screen
		resizeToLandscapeTablet(driver);

		//Make sure login options are displayed properly (user is logged out)
		if (!driver.findElement(By.cssSelector(".loginOptions")).isDisplayed()){
			results.addError(this.getClass().getCanonicalName(), "Login options were not visible");
		}
		List<WebElement> logoutOptions = driver.findElements(By.cssSelector(".logoutOptions"));
		for (WebElement logoutOption : logoutOptions){
			if (logoutOption.isDisplayed()){
				results.addError(this.getClass().getCanonicalName(), "Logout option was displayed.  Should be hidden.");
			}
		}

		//Make sure the logo is present in top left
		if (driver.findElements(By.cssSelector("#header_logo")).size() == 0){
			results.addError(this.getClass().getCanonicalName(), "Image in nav bar is not present");
		}

		//Make sure the language toggle is available
		if (driver.findElements(By.cssSelector("#language_toggle")).size() == 0){
			results.addError(this.getClass().getCanonicalName(), "Language toggle is not present");
		}

		loginUser(driver, configIni, "basic", results);

		//Make sure the user name is shown

		resizeToLandscapePhone(driver);

		//Logout
		driver.findElement(By.cssSelector("#logoutLink")).click();
	}
}
