package org.vufind;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.firefox.FirefoxDriver;

import java.io.File;
import java.io.IOException;
import java.lang.reflect.InvocationTargetException;

public class AllTests  {
	private static Logger logger	= Logger.getLogger(AllTests.class);

	public static void main(String[] args) {
		// Initialize the logger
		File log4jFile = new File("conf/log4j.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.getAbsolutePath());
			System.exit(1);
		}
		Ini configIni = readConfig();

		TestResults results = new TestResults();
		// Create a new instance of the Firefox driver
		// Notice that the remainder of the code relies on the interface,
		// not the implementation.
		WebDriver driver = new FirefoxDriver();

		for (String className : configIni.get("tests").keySet()){
			if (configIni.get("tests", className).equals("true")){
				try {
					Class testClass = Class.forName(className);
					Object test = testClass.newInstance();
					if (test instanceof VuFindTest){
						VuFindTest testInstance = (VuFindTest)test;
						try{
							testInstance.runTests(driver, configIni, results, logger);
						}catch (Exception e){
							logger.error("Unexpected error running test " + className, e);
							results.addError(className, "Unexpected error running test " + e.toString());
						}
					}
				} catch (Exception e) {
					logger.error("Could not run class " + className, e);
					results.addError("Setup", "Could not run class " + className);
				}
			}
		}

		//Close the browser
		driver.quit();

		String resultsPath = configIni.get("general", "resultsPath");
		results.writeResults(resultsPath, logger);
	}

	private static Ini readConfig(){
		String configFilePath = "conf/config.ini";
		File configFile = new File(configFilePath);
		try {
			return new Ini(configFile);
		} catch (IOException e) {
			logger.debug("Unable to read config file " + configFile.getAbsolutePath());
			return null;
		}
	}
}