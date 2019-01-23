package org.marmot;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Arrays;
import java.util.Date;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

public class ExtractOverDriveInfoMain {
	private static Logger logger;
	private static String serverName;
	private static Connection vufindConn;

	public static void main(String[] args) {
		if (args.length == 0){
			System.out.println("The name of the server to extract OverDrive data for must be provided as the first parameter.");
			System.exit(1);
		}
		//System.out.println("Starting overdrive extract");

		serverName = args[0];
		args = Arrays.copyOfRange(args, 1, args.length);
		boolean doFullReload = false;
		String individualIdToProcess = null;
		if (args.length == 1){
			//Check to see if we got a full reload parameter
			String firstArg = args[0].replaceAll("\\s", "");
			if (firstArg.matches("^fullReload(=true|1)?$")){
				doFullReload = true;
			}else if (firstArg.equals("singleWork")){
				//Process a specific work
				//Prompt for the work to process
				System.out.print("Enter the id of the record to update from OverDrive: ");

				//  open up standard input
				BufferedReader br = new BufferedReader(new InputStreamReader(System.in));

				//  read the work from the command-line; need to use try/catch with the
				//  readLine() method
				try {
					individualIdToProcess = br.readLine().trim();
				} catch (IOException ioe) {
					System.out.println("IO error trying to read the work to process!");
					System.exit(1);
				}
			}
		}

		
		Date currentTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.overdrive_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger = Logger.getLogger(ExtractOverDriveInfoMain.class);
		logger.info(currentTime.toString() + ": Starting OverDrive Extract");
		
		// Setup the MySQL driver
		try {
			// The newInstance() call is a work around for some
			// broken Java implementations
			Class.forName("com.mysql.jdbc.Driver").newInstance();

			logger.debug("Loaded driver for MySQL");
		} catch (Exception ex) {
			logger.info("Could not load driver for MySQL, exiting.", ex);
			return;
		}
		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini configIni = loadConfigFile("config.ini");
		
		String databaseConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_vufind_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("VuFind Database connection information not found in Database Section.  Please specify connection information in database_vufind_jdbc.");
			System.exit(1);
		}
		try {
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException e) {
			logger.error("Could not connect to vufind database", e);
			System.exit(1);
		}
		
		
		//Connect to the database
		String econtentConnectionInfo = Util.cleanIniValue(configIni.get("Database","database_econtent_jdbc"));
		if (econtentConnectionInfo == null || econtentConnectionInfo.length() == 0) {
			logger.error("eContent Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}

		Connection econtentConn;
		try {
			econtentConn = DriverManager.getConnection(econtentConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + econtentConnectionInfo, ex);
			return;
		}
		
		OverDriveExtractLogEntry logEntry = new OverDriveExtractLogEntry(econtentConn, logger);
		if (!logEntry.saveResults()){
			logger.error("Could not save log entry to database, quitting");
			return;
		}
		
		ExtractOverDriveInfo extractor = new ExtractOverDriveInfo();
		extractor.extractOverDriveInfo(configIni, vufindConn, econtentConn, logEntry, doFullReload, individualIdToProcess);

		logEntry.setFinished();
		logEntry.addNote("Finished OverDrive extraction");
		logEntry.saveResults();
		logger.info("Finished OverDrive extraction");
		Date endTime = new Date();
		long elapsedTime = (endTime.getTime() - currentTime.getTime()) / 1000;
		logger.info("Elapsed time " + String.format("%f2", ((float)elapsedTime / 60f)) + " minutes");
	}
	
	private static Ini loadConfigFile(String filename){
		//First load the default config file 
		String configName = "../../sites/default/conf/" + filename;
		logger.debug("Loading configuration from " + configName);
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
		logger.debug("Loading site specific config from " + siteSpecificFilename);
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
		//Also load password files if they exist
		String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
		logger.debug("Loading password config from " + siteSpecificPassword);
		File siteSpecificPasswordFile = new File(siteSpecificPassword);
		if (siteSpecificPasswordFile.exists()) {
			try {
				Ini siteSpecificPwdIni = new Ini();
				siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
				for (Section curSection : siteSpecificPwdIni.values()){
					for (String curKey : curSection.keySet()){
						ini.put(curSection.getName(), curKey, curSection.get(curKey));
					}
				}
			} catch (InvalidFileFormatException e) {
				logger.error("Site Specific password config file is not valid.  Please check the syntax of the file.", e);
			} catch (IOException e) {
				logger.error("Site Specific password config file could not be read.", e);
			}
		}
		return ini;
	}
}
