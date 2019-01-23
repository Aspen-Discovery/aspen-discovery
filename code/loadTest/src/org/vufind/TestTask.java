package org.vufind;

import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.Date;
import java.util.TimerTask;

import org.apache.log4j.Logger;

public abstract class TestTask extends TimerTask{
	protected String baseUrl;
	protected Logger logger;
	private int numIterationsToRun;
	private int numIterationsRun = 0;
	private int maxLoadTime = 0;
	private int numFailed = 0;
	private int numPassed = 0;
	private long totalElapsedTime = 0;
	private long longestTest = 0;
	private long shortestTest = Long.MAX_VALUE;
	private String testName;
	private boolean finished = false;
	private ArrayList<TestUser> testUsers;
	private ArrayList<TestResource> testResources;
	
	public TestTask() {
	} 
	
	public void initialize(String testName, String baseUrl, Logger logger, int numIterationsToRun, int maxLoadTime, ArrayList<TestUser> testUsers, ArrayList<TestResource> testResources){
		this.testName = testName;
		this.baseUrl = baseUrl;
		this.logger = logger;
		this.numIterationsToRun = numIterationsToRun;
		this.maxLoadTime = maxLoadTime;
		this.testUsers = testUsers;
		this.testResources = testResources;
	} 
	
	public void run(){
		
		long startTime = new Date().getTime();
		numIterationsRun++;
		logger.debug("Running " + testName + " " + numIterationsRun + " - " + numIterationsToRun);
		if (numIterationsRun >= numIterationsToRun){
			this.cancel();
		}
		try {
			URL url = new URL(this.getTestUrl());
			logger.debug("Loading url " + url);
			HttpURLConnection conn = (HttpURLConnection)url.openConnection();
			Object rawData = conn.getContent();
			if (conn.getResponseCode() != 200){
				logger.info("Did not get a response of 200 from the server. " + conn.getResponseCode());
				numFailed++;
			}else{
				//Check to make sure that we haven't exceded the load time.
				long endTime = new Date().getTime();
				long testTime = endTime - startTime;
				if (testTime > maxLoadTime){
					logger.info("Test took longer to run " + testTime + " than allowed " + maxLoadTime);
					numFailed++;
				}else{
					//Validate the result
					if (expectHTML() && rawData instanceof InputStream) {
						String urlResults = Util.convertStreamToString((InputStream) rawData);
						//Validate the result
						if (validateTest(urlResults)){
							numPassed++;
						}else{
							logger.info("Test failed validation");
							numFailed++;
						}
					}else if (expectImage() && rawData.getClass().getName().equals("sun.awt.image.URLImageSource")) {
						numPassed++;
					}else{
						logger.info("Did not get expected type of content, received " + rawData.getClass().toString());
						numFailed++;
					}
				}
				
			}
		} catch (Exception e) {
			logger.error("Exception running test " + this.getClass().getName(), e);
		}
		long endTime = new Date().getTime();
		long testTime = endTime - startTime;
		if (testTime > longestTest){
			longestTest = testTime;
		}
		if (testTime < shortestTest){
			shortestTest = testTime;
		}
		totalElapsedTime += testTime;
		if (numIterationsRun >= numIterationsToRun){
			this.finished = true;
		}
	}
	
	public abstract String getTestUrl();
	public abstract boolean validateTest(String pageContents);
	public abstract boolean expectHTML();
	public abstract boolean expectImage();
	
	public String getResultsCsv(){
		return testName + ", " + numIterationsRun + ", " + numPassed + ", " + numFailed + ", " + (totalElapsedTime / 1000) + ", " + ((float)totalElapsedTime / (float)(numIterationsRun * 1000)) + ", " + shortestTest + ", " + longestTest ;
	}

	public boolean isFinished() {
		return finished;
	}
	
	protected TestUser getRandomTestUser() {
		int testUserIndex = (int)Math.floor(Math.random() * testUsers.size());
		
		return  testUsers.get(testUserIndex);
	}
	
	protected TestResource getRandomTestResource(){
		int testResourceIndex = (int)Math.floor(Math.random() * testResources.size());
		
		return  testResources.get(testResourceIndex);
	}
	
}
