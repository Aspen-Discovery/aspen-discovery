package com.turning_leaf_technologies.cron;

import java.sql.*;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Date;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public class Cron {

	private static Logger logger;

	private static Connection dbConn;

	/**
	 * @param args command line parameters passed
	 */
	public static void main(String[] args) {
		String serverName;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
		} else {
			serverName = args[0];
			args = Arrays.copyOfRange(args, 1, args.length);
		}
		
		Date currentTime = new Date();
		logger = LoggingUtil.setupLogging(serverName, "cron");
		logger.info(currentTime.toString() + ": Starting Cron");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = ConfigUtil.loadConfigFile("config.ini", serverName, logger);
		
		//Connect to the database
		String databaseConnectionInfo = ConfigUtil.cleanIniValue(ini.get("Database","database_aspen_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}

		try {
			dbConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + databaseConnectionInfo, ex);
			return;
		}

		//Create a log entry for the cron process
		CronLogEntry cronEntry = new CronLogEntry(dbConn, logger);
		if (!cronEntry.saveResults()){
			logger.error("Could not save log entry to database, quitting");
			return;
		}
		
		// Read the cron INI file to get information about the processes to run
		Ini cronIni = ConfigUtil.loadConfigFile("config.cron.ini", serverName, logger);
		
		//Check to see if a specific task has been specified to be run
		ArrayList<ProcessToRun> processesToRun = new ArrayList<>();
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
				cronEntry.saveResults();
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
					cronEntry.saveResults();
					
					//Mark the time the run was started rather than finished so really long running processes
					//can go on while faster processes execute multiple times in other threads.
					markProcessStarted(processToRun);
					processHandlerInstance.doCronProcess(serverName, ini, processSettings, dbConn, cronEntry, logger);
					//Log how long the process took
					Date endTime = new Date();
					long elapsedMillis = endTime.getTime() - currentTime.getTime();
					float elapsedMinutes = elapsedMillis / 60000f;
					logger.info("Finished process " + processToRun.getProcessName() + " in " + elapsedMinutes + " minutes (" + elapsedMillis + " milliseconds)");
					cronEntry.addNote("Finished process " + processToRun.getProcessName() + " in " + elapsedMinutes + " minutes (" + elapsedMillis + " milliseconds)");
					cronEntry.saveResults();

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
		cronEntry.saveResults();
	}

	private static void markProcessStarted(ProcessToRun processToRun) {
		try{
			long finishTime = new Date().getTime() / 1000;
			if (processToRun.getLastRunVariableId() != null) {
				PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, processToRun.getLastRunVariableId());
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else {
				String processVariableId = "last_" + processToRun.getProcessName().toLowerCase().replace(' ', '_') + "_time";
				PreparedStatement insertVariableStmt = dbConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
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
		ArrayList<ProcessToRun> processesToRun = new ArrayList<>();
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
				logger.info("Skipping Process " + processName + " because it must be run manually.");
			}else{
				loadLastRunTimeForProcess(newProcess);

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
			PreparedStatement loadLastRunTimeStmt = dbConn.prepareStatement("SELECT * from variables WHERE name = '" + processVariableId + "'");
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

}
