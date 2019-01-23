package org.vufind;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Timer;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

import au.com.bytecode.opencsv.CSVReader;

public class LoadTestMain {
	private static Logger logger = Logger.getLogger(LoadTestMain.class);
	
	private static String baseUrlToLoad = "";
	private static ArrayList<TestTask> testTasks = new ArrayList<TestTask>();
	private static ArrayList<TestUser> testUsers;
	private static ArrayList<TestResource> testResources;
	private static Ini ini;
	
	public static void main(String[] args) {
		//Setup logging
		setupLogging();
		//Run the load test
		if (loadConfig()){
			startLoadTest();
			//wait for all tests to finish
			waitForTestsToFinish();
			//Print the results
			printResults();
		}
		
		System.exit(0);
	}

	private static void setupLogging() {
		File log4jFile = new File("./log4j.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info("Starting Cron");
	}

	private static void startLoadTest() {
		Section tests = ini.get("Tests");
		if (tests == null){
			logger.error("Unable to load tests to run.  Please provide a Tests section in the config file");
		}else{
			for (String testName : tests.keySet()) {
				String testClass = tests.get(testName);
				Section testSettings = ini.get(testName);
				if (testSettings == null){
					logger.error("No test settings were provided.  Please add a section called " + testName + " with configuration for this test.");
				}else{
					Integer numTestsToRun = Integer.parseInt(testSettings.get("numTestsToRun"));
					Integer testInterval = Integer.parseInt(testSettings.get("testInterval"));
					Integer numThreads = Integer.parseInt(testSettings.get("numThreads"));
					Integer maxLoadTime = Integer.parseInt(testSettings.get("maxLoadTime"));
					for (int i = 0; i < numThreads; i++){
						startTest(testName + " - " + i, testClass, testInterval, numTestsToRun, maxLoadTime);
					}
				}
				
			}
		}
	}


	private static void waitForTestsToFinish() {
		boolean oneOrMoreTasksRunning = true;
		while (oneOrMoreTasksRunning){
			//logger.debug("Waiting for tests to finish");
			try {
				Thread.sleep(500);
			} catch (InterruptedException e) {
				logger.error("The thread was interrupted", e);
			}
			oneOrMoreTasksRunning = false;
			for (TestTask curTask : testTasks){
				if (!curTask.isFinished()){
					oneOrMoreTasksRunning = true;
				}
			}
		}
	}

	private static void printResults() {
		logger.info("Test Name, Iterations, Passed, Failed, Total Time(s), Average Time(s), Shortest Time(ms), Longest Time(ms)");
		for (TestTask curTask : testTasks){
			logger.info(curTask.getResultsCsv());
		}
	}

	private static void startTest(String testName, String className, int testInterval, int numTestsToRun, int maxLoadTime) {
		try {
			Timer timer = new Timer();
			Class testClass = Class.forName(className);
			Object testClassObject = testClass.newInstance();
			TestTask testClassInstance = (TestTask) testClassObject;
			testClassInstance.initialize(testName, baseUrlToLoad, logger, numTestsToRun, maxLoadTime, testUsers, testResources);
			timer.schedule(testClassInstance, testInterval / 5, testInterval);
			testTasks.add(testClassInstance);
			logger.info("Scheduled test " + testName);
		} catch (Exception e) {
			logger.error("Error setting up test " + className, e);
		}
		
	}

	private static boolean loadConfig() {
		// Read the INI file to detemine what processes should be run.
		// INI File is in the conf directory (current directory/cron/config.ini)
		ini = new Ini();
		File configFile = new File("conf/config.ini");
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.");
			return false;
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.");
			return false;
		} catch (IOException e) {
			logger.error("Configuration file could not be read.");
			return false;
		}
		
		Section general = ini.get("General");
		baseUrlToLoad  = general.get("baseUrl");
		if (baseUrlToLoad == null){
			logger.error("Could not load the base url to test.");
			return false;
		}
		
		//Load test users 
		loadTestUsers();
		
		//Load test resources
		loadTestResources();
		
		return true;
	}

	private static void loadTestResources() {
		try {
			File testResourceFile = new File ("conf/test_resource.csv");
			CSVReader resourceReader = new CSVReader(new FileReader(testResourceFile));
			String[] headers = resourceReader.readNext();
			
			testResources = new ArrayList<TestResource>();
			String[] curResource = resourceReader.readNext(); 
			while (curResource != null){
				TestResource resource = new TestResource(Integer.parseInt(curResource[1]), curResource[3], curResource[2], curResource[4], curResource[6], curResource[7], curResource[8], curResource[9]);
				testResources.add(resource);
				curResource = resourceReader.readNext();
			}
		} catch (Exception e) {
			logger.error("Error loading test resources", e);
		}
	}

	private static void loadTestUsers() {
		try {
			File testUserFile = new File ("conf/test_user.csv");
			CSVReader userReader = new CSVReader(new FileReader(testUserFile));
			String[] headers = userReader.readNext();
			
			testUsers = new ArrayList<TestUser>();
			String[] curFields = userReader.readNext(); 
			while (curFields != null){
				TestUser testUser = new TestUser(curFields[0], curFields[1], Boolean.parseBoolean(curFields[2]));
				testUsers.add(testUser);
				curFields = userReader.readNext();
			}
		} catch (Exception e) {
			logger.error("Error loading test users", e);
		}
	}

}
