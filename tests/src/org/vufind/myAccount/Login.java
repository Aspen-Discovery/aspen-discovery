package org.vufind.myAccount;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.support.ui.ExpectedCondition;
import org.openqa.selenium.support.ui.WebDriverWait;
import org.vufind.TestResults;
import org.vufind.VuFindTest;

/**
 * Tests for logging into the
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/21/13
 * Time: 2:59 PM
 */
public class Login extends VuFindTest {

	@Override
	public void runTests(WebDriver driver, Ini configIni, TestResults results, Logger logger) {
		String testUrl = configIni.get("general", "testUrl");
		driver.get(testUrl);
		testValidLogin(driver, configIni, results);
		testInvalidLogin(driver, configIni, results);


	}

	private void testInvalidLogin(WebDriver driver, Ini configIni, TestResults results) {
		//Test invalid login
		results.incTests();
		String invalidLogin = configIni.get("users", "invalid");
		String[] invalidValues = invalidLogin.split(":");
		driver.findElement(By.cssSelector("#headerLoginLink")).click();
		waitForModalDialogOpen(driver);

		//Make sure the modal dialog opens
		if (driver.findElement(By.cssSelector("#modalDialog")).isDisplayed()){
			driver.findElement(By.cssSelector("#showPwd")).click();
			driver.findElement(By.cssSelector("#username")).sendKeys(invalidValues[0]);
			driver.findElement(By.cssSelector("#password")).sendKeys(invalidValues[1]);
			driver.findElement(By.cssSelector("#loginFormSubmit")).click();

			waitForElementVisible(driver, "#loginError");

			//Make sure the error message shows properly
			if (!driver.findElement(By.cssSelector("#loginError")).isDisplayed()){
				results.addError(this.getClass().getCanonicalName() + ":testInvalidLogin", "Error message was not shown correctly");
			}
			String errorText =  driver.findElement(By.cssSelector("#loginError")).getText();
			if (!errorText.equals("Sorry that login information was not recognized, please try again.")){
				results.addError(this.getClass().getCanonicalName() + ":testInvalidLogin", "Did not get valid error message, received " + errorText);
			}
			//Check login and out options
			if (!driver.findElement(By.cssSelector("#headerLoginLink")).isDisplayed()){
				results.addError(this.getClass().getCanonicalName() + ":testInvalidLogin", "Login link was incorrectly hidden");
			}
			if (driver.findElement(By.cssSelector(".logoutOptions")).isDisplayed()){
				results.addError(this.getClass().getCanonicalName() + ":testInvalidLogin", "Logout link was incorrectly shown");
			}
		}else{
			results.addError(this.getClass().getCanonicalName() + ":testInvalidLogin", "Modal Dialog did not appear after clicking login link");
		}
		//Close the modal dialog
		driver.findElement(By.cssSelector("#modalClose")).click();
		waitForModalDialogClose(driver);
	}

	private void testValidLogin(WebDriver driver, Ini configIni, TestResults results) {
		//Test valid login
		results.incTests();
		String basicLogin = configIni.get("users", "basic");
		String[] basicValues = basicLogin.split(":");
		driver.findElement(By.cssSelector("#headerLoginLink")).click();
		(new WebDriverWait(driver, 10)).until(new ExpectedCondition<Boolean>() {
			@Override
			public Boolean apply(WebDriver webDriver) {
				return webDriver.findElement(By.cssSelector("#modalDialog")).isDisplayed();
			}
		});
		//Make sure the modal dialog opens
		if (driver.findElement(By.cssSelector("#modalDialog")).isDisplayed()){
			driver.findElement(By.cssSelector("#username")).sendKeys(basicValues[0]);
			driver.findElement(By.cssSelector("#password")).sendKeys(basicValues[1]);
			driver.findElement(By.cssSelector("#loginFormSubmit")).click();
			waitForModalDialogClose(driver);

			//Check login and out options
			if (driver.findElement(By.cssSelector("#headerLoginLink")).isDisplayed()){
				results.addError(this.getClass().getCanonicalName()+ ":testValidLogin", "Login link was not hidden");
			}
			if (!driver.findElement(By.cssSelector(".logoutOptions")).isDisplayed()){
				results.addError(this.getClass().getCanonicalName()+ ":testValidLogin", "Logout options were not shown");
			}
		}else{
			results.addError(this.getClass().getCanonicalName()+ ":testValidLogin", "Modal Dialog did not appear after clicking login link");
		}
		//Logout
		driver.findElement(By.cssSelector("#logoutLink")).click();
	}
}
