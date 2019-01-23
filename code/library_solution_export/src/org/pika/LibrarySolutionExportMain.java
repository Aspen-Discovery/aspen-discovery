package org.pika;
import au.com.bytecode.opencsv.CSVReader;
import au.com.bytecode.opencsv.CSVWriter;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;

import java.io.*;
import java.sql.*;
import java.util.Arrays;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

public class LibrarySolutionExportMain {
	private static Logger logger = Logger.getLogger(LibrarySolutionExportMain.class);
	private static String serverName; //Pika instance name

	public static void main(String[] args) {
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.library_solution_export.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Library.Solution Extract");

		// Read the base INI file to get information about the server (current directory/conf/config.ini)
		Ini ini = loadConfigFile("config.ini");

		//Connect to the vufind database
		Connection vufindConn = null;
		try {
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			if (databaseConnectionInfo == null){
				logger.error("Please provide database_vufind_jdbc within config.ini (or better config.pwd.ini) ");
				System.exit(1);
			}
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (Exception e) {
			logger.error("Error connecting to vufind database ", e);
			System.exit(1);
		}

		//Get the export path from the database
		try {
			PreparedStatement getLSSIndexingProfileStmt = vufindConn.prepareStatement("SELECT * FROM indexing_profiles where name ='lss'");
			ResultSet lssIndexingProfileRS = getLSSIndexingProfileStmt.executeQuery();
			if (lssIndexingProfileRS.next()) {
				String lssExportPath = lssIndexingProfileRS.getString("marcPath");
				//Load the full export file
				mergeItemExportFiles(vufindConn, lssExportPath);
			} else {
				logger.error("Unable to find library simplified indexing profile, please create a profile with the name lss.");
			}
		}catch (Exception e){
			logger.error("Error merging extracts for Library.Solution", e);
		}

		if (vufindConn != null){
			try{
				//Close the connection
				vufindConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}
	}

	private static void mergeItemExportFiles(Connection vufindConn, String lssExportPath) {
		long updateTime = new Date().getTime() / 1000;

		//Load existing item information
		HashMap<String, String[]> existingItemInformation = new HashMap<>();
		String itemInfoPath = lssExportPath + "/schoolsitemupdatedaily.txt";
		long dailyUpdateLastModifiedTime = 0;
		try {
			File itemInfoFile = new File(itemInfoPath);
			dailyUpdateLastModifiedTime = itemInfoFile.lastModified();
			CSVReader itemInfoReader = new CSVReader(new FileReader(itemInfoFile));
			//read the header
			itemInfoReader.readNext();
			String[] itemInfoRow = itemInfoReader.readNext();
			while (itemInfoRow != null){
				String barcode = itemInfoRow[1];
				existingItemInformation.put(barcode, itemInfoRow);
				itemInfoRow = itemInfoReader.readNext();
			}
			itemInfoReader.close();
		} catch (IOException e) {
			logger.error("Error loading existing item information", e);
		}

		HashSet<String> updatedControlNumbers = new HashSet<>();
		//Load new item information
		String updatedInfoPath = lssExportPath + "/schoolsitemupdate.txt";
		try {
			File itemInfoFile = new File(updatedInfoPath);
			if (!itemInfoFile.exists() || itemInfoFile.lastModified() < dailyUpdateLastModifiedTime){
				//There is nothing to merge, quite early
				return;
			}
			CSVReader updatedItemInfoReader = new CSVReader(new FileReader(itemInfoFile));
			//read the header
			updatedItemInfoReader.readNext();
			String[] updatedItemInfoRow = updatedItemInfoReader.readNext();
			while (updatedItemInfoRow != null){
				String barcode = updatedItemInfoRow[1];
				String[] existingValues = existingItemInformation.get(barcode);
				if (existingValues == null){
					//This is a new value add to the file, but we won't index it since it didn't exist previously
					existingItemInformation.put(barcode, updatedItemInfoRow);
				}else{
					//Only update if the data is different
					if (!Arrays.equals(existingValues, updatedItemInfoRow)){
						//The information has changed, add to the updates file and mark the work as changed
						existingItemInformation.put(barcode, updatedItemInfoRow);

						String updatedControlNumber = updatedItemInfoRow[0];
						updatedControlNumbers.add(updatedControlNumber);
						logger.debug("Control number " + updatedControlNumber + " changed");
					}
				}
				updatedItemInfoRow = updatedItemInfoReader.readNext();
			}
			updatedItemInfoReader.close();
		} catch (IOException e) {
			logger.error("Error merging item export files", e);
		}

		logger.info("A total of " + updatedControlNumbers.size() + " bibs were updated");
		//Write merged information
		if (updatedControlNumbers.size() > 0) {
			try {
				CSVWriter mergedItemWriter = new CSVWriter(new FileWriter(itemInfoPath));
				for (String[] itemInfo : existingItemInformation.values()) {
					mergedItemWriter.writeNext(itemInfo);
				}
				mergedItemWriter.flush();
				mergedItemWriter.close();
			} catch (Exception e) {
				logger.error("Error saving updated Item", e);
			}

			//Mark the appropriate records as changed in the database
			try {
				PreparedStatement markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'lss' and identifier = ?)");
				vufindConn.setAutoCommit(false);
				int numUpdates = 0;
				for (String curBibId : updatedControlNumbers){
					//Update the database
					try {
						logger.debug("Updating grouped work in database updateTime=" + updateTime + " curBibId=" + curBibId);
						markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
						markGroupedWorkForBibAsChangedStmt.setString(2, curBibId);
						markGroupedWorkForBibAsChangedStmt.executeUpdate();

						numUpdates++;
						if (numUpdates % 50 == 0){
							vufindConn.commit();
						}
					}catch (SQLException e){
						logger.error("Could not mark that " + curBibId + " was changed due to error ", e);
					}
				}
				//Turn auto commit back on
				vufindConn.commit();
				vufindConn.setAutoCommit(true);
			}catch (Exception e){
				logger.error("Error marking work changed", e);
			}
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
			for (Profile.Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
			//Also load password files if they exist
			String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
			logger.info("Loading password config from " + siteSpecificPassword);
			File siteSpecificPasswordFile = new File(siteSpecificPassword);
			if (siteSpecificPasswordFile.exists()) {
				Ini siteSpecificPwdIni = new Ini();
				siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
				for (Profile.Section curSection : siteSpecificPwdIni.values()){
					for (String curKey : curSection.keySet()){
						ini.put(curSection.getName(), curKey, curSection.get(curKey));
					}
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}

		return ini;
	}

	public static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}
}
