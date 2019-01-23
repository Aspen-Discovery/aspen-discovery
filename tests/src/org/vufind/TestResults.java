package org.vufind;

import org.apache.log4j.Logger;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/21/13
 * Time: 10:04 AM
 */
public class TestResults {
	private int testsRun;
	private int numErrors;
	private ArrayList<String> errors = new ArrayList<String>();
	public void addError(String testName, String error){
		numErrors++;
		errors.add(testName + ":\t " + error);
	}
	public void incTests(){
		testsRun++;
	}

	public void writeResults(String resultsPath, Logger logger) {
		File results = new File(resultsPath);
		try {
			FileWriter writer = new FileWriter(results, false);
			writer.write("Ran " + testsRun + " with " + numErrors + " errors\r\n");
			if (numErrors > 0){
				writer.write("\r\n");
				writer.write("Errors\r\n");
				for (String error : errors){
					writer.write(error + "\r\n");
				}
			}
			writer.flush();
			writer.close();
		} catch (IOException e) {
			logger.error("Unable to write results", e);
		}
	}
}
