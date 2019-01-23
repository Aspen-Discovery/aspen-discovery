package org.marmot;

import java.io.FileReader;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.sql.Types;
import java.util.Date;
import java.util.List;

import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.apache.log4j.Logger;

import au.com.bytecode.opencsv.CSVReader;

public class ImportSteamboatGenealogy implements IProcessHandler{
	private CronProcessLogEntry processLog;
	private String steamboatFile;
	private String ruralFile;
	private String vufindUrl;
	private PreparedStatement checkForExistingPerson;
	private PreparedStatement addPersonStmt;
	private PreparedStatement updatePersonStmt;

	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Import Steamboat Genealogy");
		if (!loadConfig(configIni, processSettings)){
			processLog.addNote("Unable to load configuration");
			processLog.incErrors();
			return;
		}
				
		try {
			checkForExistingPerson = vufindConn.prepareStatement("SELECT personId FROM person WHERE cemeteryName = ? AND addition = ? AND block = ? and lot = ? AND grave = ? AND importedFrom = ?");
			addPersonStmt = vufindConn.prepareStatement("INSERT INTO person (firstName, lastName, deathDateDay, deathDateMonth, deathDateYear, birthDateDay, birthDateMonth, birthDateYear, cemeteryName, cemeteryLocation, comments, veteranOf, addition, block, lot, grave, tombstoneInscription, addedBy, dateAdded, privateComments, importedFrom) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
			updatePersonStmt = vufindConn.prepareStatement("UPDATE person SET firstName = ?, lastName = ?, deathDateDay = ?, deathDateMonth = ?, deathDateYear = ?, birthDateDay = ?, birthDateMonth = ?, birthDateYear = ?, cemeteryName = ?, cemeteryLocation = ?, comments = ?, veteranOf = ?, addition = ?, block = ?, lot = ?, grave = ?, tombstoneInscription = ?, addedBy = ?, dateAdded = ?, privateComments = ?, importedFrom =? WHERE personId = ?");
			//Read Steamboat File
			CSVReader reader = new CSVReader(new FileReader(steamboatFile));
			List<String[]> steamboatValues = reader.readAll();
			int curRow = 0;
			for (String[] curRecord : steamboatValues){
				if (curRow == 0){
					//Skip the headers
					curRow++;
					continue;
				}else{
					importRecord(steamboatFile, curRecord, curRow++, false, logger);
				}
				if (curRow % 100 == 0){
					processLog.saveToDatabase(vufindConn, logger);
				}
			}
			
			//Read Rural File
			CSVReader ruralReader = new CSVReader(new FileReader(ruralFile));
			List<String[]> ruralValues = ruralReader.readAll();
			curRow = 0;
			for (String[] curRecord : ruralValues){
				if (curRow == 0){
					//Skip the headers
					curRow++;
					continue;
				}else{
					importRecord(ruralFile, curRecord, curRow++, true, logger);
				}
				if (curRow % 100 == 0){
					processLog.saveToDatabase(vufindConn, logger);
				}
			}
			
		} catch (Exception ex) {
			// handle any errors
			System.out.println("Error importing genealogy information " + ex.toString());
			ex.printStackTrace();
			return;
		}
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	private void importRecord(String fileName, String[] curRecord, int curRow, boolean isRural, Logger logger){
		//Only import filled graves to start
		if ((getFieldValue(curRecord, 7) == null || getFieldValue(curRecord, 7).length() == 0) && (getFieldValue(curRecord, 8) == null || getFieldValue(curRecord, 8).length() == 0)){
			logger.info("Skipping " + fileName + " row " + curRow);
			return;
		}
		try {
			logger.info("Importing " + fileName + " row " + curRow);
			//Check to see if there is an existing record for the user?
			if (!isRural){
				checkForExistingPerson.setString(1, "Steamboat Springs Cemetery");
				checkForExistingPerson.setString(2, getFieldValue(curRecord, 0));
			}else{
				checkForExistingPerson.setString(1, getFieldValue(curRecord, 0));
				checkForExistingPerson.setString(2, "");
			}
			checkForExistingPerson.setString(3, getFieldValue(curRecord, 1));
			checkForExistingPerson.setString(4, getFieldValue(curRecord, 2));
			checkForExistingPerson.setString(5, getFieldValue( curRecord, 3));
			checkForExistingPerson.setString(6, "Steamboat Cemetery Society Data");
			ResultSet results = checkForExistingPerson.executeQuery();
			DateInfo birthDate = new DateInfo(getFieldValue(curRecord, 10));
			DateInfo deathDate = new DateInfo(getFieldValue(curRecord, 11));
			String inscription = "";
			if (getFieldValue(curRecord, 13) != null && getFieldValue(curRecord, 13).length() > 0){
				inscription = getFieldValue(curRecord, 13);
			}
			if (getFieldValue(curRecord, 14) != null && getFieldValue(curRecord, 14).length() > 0){
				if (inscription.length() > 0){
					inscription += "\r\n";
				}
				inscription += getFieldValue(curRecord, 14);
			}
			Long personId = null;
			if (results.next()){
				personId = results.getLong("personId");
				//If there is, update it with cemetery information
				updatePersonStmt.setString(1, getFieldValue(curRecord, 7));
				updatePersonStmt.setString(2, getFieldValue(curRecord, 8));
				updatePersonStmt.setInt(3, deathDate.getDay());
				updatePersonStmt.setInt(4, deathDate.getMonth());
				updatePersonStmt.setInt(5, deathDate.getYear());
				updatePersonStmt.setInt(6, birthDate.getDay());
				updatePersonStmt.setInt(7, birthDate.getMonth());
				updatePersonStmt.setInt(8, birthDate.getYear());
				if (!isRural){
					updatePersonStmt.setString(9, "Steamboat Springs Cemetery");
					updatePersonStmt.setString(10, "Steamboat Springs");
				}else{
					updatePersonStmt.setString(9, getFieldValue(curRecord, 0));
					updatePersonStmt.setString(10, "");
				}
				updatePersonStmt.setString(11, getFieldValue(curRecord, 12));
				updatePersonStmt.setString(12, getFieldValue(curRecord, 9));
				updatePersonStmt.setString(13, getFieldValue(curRecord, 0));
				updatePersonStmt.setString(14, getFieldValue(curRecord, 1));
				try{
					updatePersonStmt.setInt(15, Integer.parseInt(curRecord[2]));
				}catch (NumberFormatException e){
					updatePersonStmt.setNull(15, Types.INTEGER);
				}
				try{
					updatePersonStmt.setInt(16, Integer.parseInt(curRecord[3]));
				}catch (NumberFormatException e){
					updatePersonStmt.setNull(16, Types.INTEGER);
				}
				updatePersonStmt.setString(17, inscription);
				updatePersonStmt.setInt(18, -1);
				updatePersonStmt.setLong(19, new Date().getTime() / 1000);
				updatePersonStmt.setString(20, getFieldValue(curRecord, 15));
				updatePersonStmt.setString(21, "Steamboat Cemetery Society Data");
				updatePersonStmt.setLong(22, personId);
				updatePersonStmt.executeUpdate();
			}else{
				//If not, create a new record
				addPersonStmt.setString(1, getFieldValue(curRecord, 7));
				addPersonStmt.setString(2, getFieldValue(curRecord, 8));
				addPersonStmt.setInt(3, deathDate.getDay());
				addPersonStmt.setInt(4, deathDate.getMonth());
				addPersonStmt.setInt(5, deathDate.getYear());
				addPersonStmt.setInt(6, birthDate.getDay());
				addPersonStmt.setInt(7, birthDate.getMonth());
				addPersonStmt.setInt(8, birthDate.getYear());
				if (!isRural){
					addPersonStmt.setString(9, "Steamboat Springs Cemetery");
					addPersonStmt.setString(10, "Steamboat Springs");
				}else{
					addPersonStmt.setString(9, getFieldValue(curRecord, 0));
					addPersonStmt.setString(10, "");
				}
				addPersonStmt.setString(11, getFieldValue(curRecord, 12));
				addPersonStmt.setString(12, getFieldValue(curRecord, 9));
				addPersonStmt.setString(13, getFieldValue(curRecord, 0));
				addPersonStmt.setString(14, getFieldValue(curRecord, 1));
				try{
					addPersonStmt.setInt(15, Integer.parseInt(curRecord[2]));
				}catch (NumberFormatException e){
					addPersonStmt.setNull(15, Types.INTEGER);
				}
				try{
					addPersonStmt.setInt(16, Integer.parseInt(curRecord[3]));
				}catch (NumberFormatException e){
					addPersonStmt.setNull(16, Types.INTEGER);
				}
				addPersonStmt.setString(17, inscription);
				addPersonStmt.setInt(18, -1);
				addPersonStmt.setLong(19, new Date().getTime() / 1000);
				addPersonStmt.setString(20, getFieldValue(curRecord, 15));
				addPersonStmt.setString(21, "Steamboat Cemetery Society Data");
				addPersonStmt.executeUpdate();
				ResultSet keys = addPersonStmt.getGeneratedKeys();
				if (keys.next()){
					personId = keys.getLong(1);
				}
			}
			
			//Reindex the record
			if (personId != null){
				URL reindexUrl = new URL(vufindUrl + "/Person/" + personId + "/Reindex?quick=true");
				@SuppressWarnings("unused")
				Object content = reindexUrl.getContent();
				processLog.incUpdated();
			}
		} catch (Exception e) {
			logger.error("Error importing row " + curRow, e);
			processLog.addNote("Error importing row " + curRow + " - " +  e.toString());
			processLog.incErrors();
		}
	}
	
	private boolean loadConfig(Ini configIni, Section processSettings) {
		vufindUrl = configIni.get("Site", "url");
		steamboatFile = processSettings.get("steamboatFile");
		if (steamboatFile == null || steamboatFile.length() == 0) {
			processLog.addNote("Unable to get steamboat file in Process section.  Please specify steamboatFile key.");
			return false;
		}
		ruralFile = processSettings.get("ruralFile");
		if (ruralFile == null || ruralFile.length() == 0) {
			processLog.addNote("Unable to get rural file in Process section.  Please specify steamboatFile key.");
			return false;
		}
		return true;
	}
	
	private String getFieldValue(String[] fields, int index){
		if (fields.length -1 >= index){
			return fields[index];
		}else{
			return null;
		}
	}

}
