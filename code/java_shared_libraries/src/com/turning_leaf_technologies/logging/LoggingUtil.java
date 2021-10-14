package com.turning_leaf_technologies.logging;

import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.io.*;

public class LoggingUtil {
	public static Logger setupLogging(String serverName, String processName) {
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j." + processName + ".properties");
		if (!log4jFile.exists()) {
			//Copy the default log file and update based on the serverName
			File defaultFile = new File("../../sites/default/conf/log4j.properties");
			try {
				BufferedReader reader = new BufferedReader(new FileReader(defaultFile));
				BufferedWriter writer = new BufferedWriter(new FileWriter(log4jFile));
				String curLine = reader.readLine();
				while (curLine != null) {
					curLine = curLine.replaceAll("<<processname>>", processName);
					curLine = curLine.replaceAll("<<sitename>>", serverName);
					writer.write(curLine);
					writer.newLine();
					curLine = reader.readLine();
				}
				reader.close();
				writer.flush();
				writer.close();
			} catch (FileNotFoundException fne) {
				System.out.println("Could not find default log file at " + defaultFile.getAbsolutePath() + " " + fne.toString());
				System.exit(1);
			} catch (IOException ioe) {
				System.out.println("Error setting up log file " + ioe.toString());
				System.exit(1);
			}
		}
		System.setProperty("log4j.configurationFile", log4jFile.getAbsolutePath());

		return LogManager.getLogger();
	}
}
