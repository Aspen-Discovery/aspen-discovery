package org.vufind;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.sql.*;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Date;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

public class Cron {

	private static Logger logger = Logger.getLogger(Cron.class);
	private static String serverName;
	
	private static Connection vufindConn;
	private static Connection econtentConn;

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		if (args.length == 0){
			System.out.println("The name of the server to run cron for must be provided as the first parameter.");
			System.exit(1);
		}
		serverName = args[0];
		args = Arrays.copyOfRange(args, 1, args.length);
		
		Date currentTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.cron.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(currentTime.toString() + ": Starting Cron");
		// Setup the MySQL driver
		try {
			// The newInstance() call is a work around for some
			// broken Java implementations
			Class.forName("com.mysql.jdbc.Driver").newInstance();

			logger.info("Loaded driver for MySQL");
		} catch (Exception ex) {
			logger.info("Could not load driver for MySQL, exiting.");
			return;
		}

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");
		
		//Connect to the database
		String databaseConnectionInfo = Util.cleanIniValue(ini.get("Database","database_vufind_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("VuFind Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}
		String econtentConnectionInfo = Util.cleanIniValue(ini.get("Database","database_econtent_jdbc"));
		if (econtentConnectionInfo == null || econtentConnectionInfo.length() == 0) {
			logger.error("eContent Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}
		
		try {
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + databaseConnectionInfo, ex);
			return;
		}
		try {
			econtentConn = DriverManager.getConnection(econtentConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + econtentConnectionInfo, ex);
			return;
		}
		
		//Create a log entry for the cron process
		CronLogEntry cronEntry = new CronLogEntry();
		if (!cronEntry.saveToDatabase(vufindConn, logger)){
			logger.error("Could not save log entry to database, quitting");
			return;
		}
		
		// Read the cron INI file to get information about the processes to run
		Ini cronIni = loadConfigFile("config.cron.ini");
		File cronConfigFile = new File("../../sites/" + serverName + "/conf/config.cron.ini");
		
		//Check to see if a specific task has been specified to be run
		ArrayList<ProcessToRun> processesToRun = new ArrayList<ProcessToRun>();
		// INI file has a main section for processes to be run
		// The processes are in the format:
		// name = handler class
		Section processes = cronIni.get("Processes");
		if (args.length >= 1){
			logger.info("Found " + args.length + " arguments ");
			String processName = args[0];
			String processHandler = cronIni.get("Processes", processName);
			if (processHandler == null){
				processHandler = processName;
			}
			ProcessToRun process = new ProcessToRun(processName, processHandler);
			args = Arrays.copyOfRange(args, 1, args.length);
			if (args.length > 0){
				process.setArguments(args);
			}
			loadLastRunTimeForProcess(process);
			processesToRun.add(process);
		}else{
			//Load processes to run
			processesToRun = loadProcessesToRun(cronIni, processes);
		}
		
		for (ProcessToRun processToRun: processesToRun){
			Section processSettings;
			if (processToRun.getArguments() != null){
				//Add arguments into the section
				for (String argument : processToRun.getArguments() ){
					String[] argumentOptions = argument.split("=");
					logger.info("Adding section setting " + argumentOptions[0] + " = " + argumentOptions[1]);
					cronIni.put("runtimeArguments", argumentOptions[0], argumentOptions[1]);
				}
				processSettings = cronIni.get("runtimeArguments");
			}else{
				processSettings = cronIni.get(processToRun.getProcessName());
			}
		
			currentTime = new Date();
			logger.info(currentTime.toString() + ": Running Process " + processToRun.getProcessName());
			if (processToRun.getProcessClass() == null){
				logger.error("Could not run process " + processToRun.getProcessName() + " because there is not a class for the process.");
				cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because there is not a class for the process.");
				continue;
			}
			// Load the class for the process using reflection
			try {
				@SuppressWarnings("rawtypes")
				Class processHandlerClass = Class.forName(processToRun.getProcessClass());
				Object processHandlerClassObject;
				try {
					processHandlerClassObject = processHandlerClass.newInstance();
					IProcessHandler processHandlerInstance = (IProcessHandler) processHandlerClassObject;
					cronEntry.addNote("Starting cron process " + processToRun.getProcessName());
					
					//Mark the time the run was started rather than finished so really long running processes
					//can go on while faster processes execute multiple times in other threads.
					markProcessStarted(processToRun);
					processHandlerInstance.doCronProcess(serverName, ini, processSettings, vufindConn, econtentConn, cronEntry, logger);
					//Log how long the process took
					Date endTime = new Date();
					long elapsedMillis = endTime.getTime() - currentTime.getTime();
					float elapsedMinutes = (elapsedMillis) / 60000;
					logger.info("Finished process " + processToRun.getProcessName() + " in " + elapsedMinutes + " minutes (" + elapsedMillis + " milliseconds)");
					cronEntry.addNote("Finished process " + processToRun.getProcessName() + " in " + elapsedMinutes + " minutes (" + elapsedMillis + " milliseconds)");

				} catch (InstantiationException e) {
					logger.error("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be instantiated.");
					cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be instantiated.");
				} catch (IllegalAccessException e) {
					logger.error("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " generated an Illegal Access Exception.");
					cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " generated an Illegal Access Exception.");
				}

			} catch (ClassNotFoundException e) {
				logger.error("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be found.");
				cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be found.");
			}
		}

		cronEntry.setFinished();
		cronEntry.addNote("Cron run finished");
		cronEntry.saveToDatabase(vufindConn, logger);
	}

	private static void markProcessStarted(ProcessToRun processToRun) {
		try{
			Long finishTime = new Date().getTime() / 1000;
			if (processToRun.getLastRunVariableId() != null) {
				PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, processToRun.getLastRunVariableId());
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else {
				String processVariableId = "last_" + processToRun.getProcessName().toLowerCase().replace(' ', '_') + "_time";
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
				insertVariableStmt.setString(1, processVariableId);
				insertVariableStmt.setString(2, Long.toString(finishTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error updating last run time", e);
			System.exit(1);
		}

	}

	private static ArrayList<ProcessToRun> loadProcessesToRun(Ini cronIni, Section processes) {
		ArrayList<ProcessToRun> processesToRun = new ArrayList<ProcessToRun>();
		Date currentTime = new Date();
		for (String processName : processes.keySet()) {
			String processHandler = cronIni.get("Processes", processName);
			// Each process has its own configuration section which can include:
			// - time last run
			// - interval to run the process
			// - additional configuration information for the process
			// Check to see when the process was last run
			boolean runProcess = false;
			String frequencyHours = cronIni.get(processName, "frequencyHours");
			ProcessToRun newProcess = new ProcessToRun(processName, processHandler);
			if (frequencyHours == null || frequencyHours.length() == 0){
				//If the frequency isn't set, automatically run the process 
				runProcess = true;
			}else if (frequencyHours.trim().compareTo("-1") == 0) {
				// Process has to be run manually
				runProcess = false;
				logger.info("Skipping Process " + processName + " because it must be run manually.");
			}else{
				loadLastRunTimeForProcess(newProcess);
				String lastRun = cronIni.get(processName, "lastRun");

				//Frequency is a number of hours.  See if we should run based on the last run.
				if (newProcess.getLastRunTime() == null) {
					runProcess = true;
				} else {
					// Check the interval to see if the process should be run
					try {
						if (frequencyHours.trim().compareTo("0") == 0) {
							// There should not be a delay between cron runs
							runProcess = true;
						} else {
							int frequencyHoursInt = Integer.parseInt(frequencyHours);
							if ((double) (currentTime.getTime() / 1000 - newProcess.getLastRunTime()) / (double) (60 * 60) >= frequencyHoursInt) {
								// The elapsed time is greater than the frequency to run
								runProcess = true;
							}else{
								logger.info("Skipping Process " + processName + " because it has already run in the specified interval.");
							}
	
						}
					} catch (NumberFormatException e) {
						logger.warn("Warning: the lastRun setting for " + processName + " was invalid. " + e.toString());
					}
				}
			}
			if (runProcess) {
				logger.info("Running process " + processName);
				processesToRun.add(newProcess);
			}
		}
		return processesToRun;
	}

	private static void loadLastRunTimeForProcess(ProcessToRun newProcess) {
		try{
			String processVariableId = "last_" + newProcess.getProcessName().toLowerCase().replace(' ', '_') + "_time";
			PreparedStatement loadLastRunTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = '" + processVariableId + "'");
			ResultSet lastRunTimeRS = loadLastRunTimeStmt.executeQuery();
			if (lastRunTimeRS.next()){
				newProcess.setLastRunTime(lastRunTimeRS.getLong("value"));
				newProcess.setLastRunVariableId(lastRunTimeRS.getLong("id"));
			}
		}catch (Exception e){
			logger.error("Error loading last run time for " + newProcess, e);
			System.exit(1);
		}
	}

	private static Ini loadConfigFile(String filename){
		//First load the default config file 
		String configName = "../../sites/default/conf/" + filename;
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find configuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		Ini ini = new Ini();
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}
		
		//Now override with the site specific configuration
		String siteSpecificFilename = "../../sites/" + serverName + "/conf/" + filename;
		logger.info("Loading site specific config from " + siteSpecificFilename);
		File siteSpecificFile = new File(siteSpecificFilename);
		if (!siteSpecificFile.exists()) {
			logger.error("Could not find server specific config file");
			System.exit(1);
		}
		try {
			Ini siteSpecificIni = new Ini();
			siteSpecificIni.load(new FileReader(siteSpecificFile));
			for (Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}

		//Now override with the site specific configuration
		String passwordFilename = "../../sites/" + serverName + "/conf/config.pwd.ini";
		logger.info("Loading site specific config from " + siteSpecificFilename);
		File siteSpecificPasswordFile = new File(passwordFilename);
		if (!siteSpecificPasswordFile.exists()) {
			logger.error("Could not find server specific config password file");
			System.exit(1);
		}
		try {
			Ini siteSpecificIni = new Ini();
			siteSpecificIni.load(new FileReader(siteSpecificPasswordFile));
			for (Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}
		return ini;
	}

}
