package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.openqa.selenium.By;
import org.openqa.selenium.Dimension;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.support.ui.ExpectedCondition;
import org.openqa.selenium.support.ui.WebDriverWait;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/21/13
 * Time: 10:06 AM
 */
public abstract class VuFindTest {
	public abstract void runTests(WebDriver driver, Ini configIni, TestResults results, Logger logger);

	protected void resizeToPhone(WebDriver driver){
		driver.manage().window().setSize(new Dimension(320, 480));
	}
	protected void resizeToLandscapePhone(WebDriver driver){
		driver.manage().window().setSize(new Dimension(480, 320));
	}
	protected void resizeToSmallTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(600, 800));
	}
	protected void resizeToLandscapeSmallTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(800, 600));
	}
	protected void resizeToTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(768, 1024));
	}
	protected void resizeToLandscapeTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(1024, 768));
	}
	protected void resizeToFullDesktop(WebDriver driver){
		driver.manage().window().maximize();
	}
	protected void waitForModalDialogOpen(WebDriver driver){
		(new WebDriverWait(driver, 10)).until(new ExpectedCondition<Boolean>() {
			@Override
			public Boolean apply(org.openqa.selenium.WebDriver webDriver) {
				return webDriver.findElement(By.cssSelector("#modalDialog")).isDisplayed();
			}
		});
	}
	protected void waitForModalDialogClose(WebDriver driver){
		(new WebDriverWait(driver, 10)).until(new ExpectedCondition<Boolean>() {
			@Override
			public Boolean apply(org.openqa.selenium.WebDriver webDriver) {
				return !webDriver.findElement(By.cssSelector("#modalDialog")).isDisplayed();
			}
		});
	}
	protected void waitForElementVisible(WebDriver driver, final String cssSelector){
		(new WebDriverWait(driver, 10)).until(new ExpectedCondition<Boolean>() {
			@Override
			public Boolean apply(org.openqa.selenium.WebDriver webDriver) {
				return webDriver.findElement(By.cssSelector(cssSelector)).isDisplayed();
			}
		});
	}
	protected void loginUser(WebDriver driver, Ini configIni, String userKey, TestResults results) {
		//Test valid login
		results.incTests();
		String basicLogin = configIni.get("users", userKey);
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
		}else{
			results.addError(this.getClass().getCanonicalName()+ ":testValidLogin", "Modal Dialog did not appear after clicking login link");
		}
		//Logout
		driver.findElement(By.cssSelector("#logoutLink")).click();
	}
}
