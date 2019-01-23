package org.nashville;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.vufind.*;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

/**
 * Migrate data for Nashville's conversion from a split Millennium and LSS system to a single CARL.X system
 * Created by mnoble on 6/7/2017.
 */
public class CarlXMigration implements IProcessHandler{
	private CronProcessLogEntry processLog;
	private String lssExportLocation;
	private String carlxExportLocation;
	private HashMap<String, CarlXPatronMap> patronMap = new HashMap<>();
	private HashMap<String, HashSet<String>> millenniumBarcodesByPType = new HashMap<>();
	private boolean deleteMissingUsers;

	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "CarlX Migration");
		if (!loadConfig(configIni, processSettings)){
			processLog.addNote("Unable to load configuration");
			processLog.incErrors();
			processLog.saveToDatabase(vufindConn, logger);
			return;
		}else{
			processLog.addNote("Loaded configuration");
			processLog.saveToDatabase(vufindConn, logger);
		}
		if (!loadPatronMappingFile(logger)){
			processLog.addNote("Unable to load patron mapping information");
			processLog.incErrors();
			processLog.saveToDatabase(vufindConn, logger);
			return;
		}else{
			processLog.addNote("Loaded patron mapping information");
			processLog.saveToDatabase(vufindConn, logger);
		}

		if (!loadMillenniumPatronFile(logger)){
			processLog.addNote("Unable to load millennium patron barcodes");
			processLog.incErrors();
			processLog.saveToDatabase(vufindConn, logger);
			return;
		}else{
			processLog.addNote("Loaded millennium patron barcodes");
			processLog.saveToDatabase(vufindConn, logger);
		}

		if (!setupUserMigrationStatements(vufindConn, logger)){
			processLog.addNote("Unable to setup user migration statements");
			processLog.incErrors();
			processLog.saveToDatabase(vufindConn, logger);
			return;
		}else{
			processLog.addNote("Setup user migration statements");
			processLog.saveToDatabase(vufindConn, logger);
		}

		updateLssUsers(processLog, vufindConn, logger);
		updateMillenniumUsers(processLog, vufindConn, logger);

		fixBibLinks(processLog, vufindConn, logger);

		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	private boolean loadMillenniumPatronFile(Logger logger) {
		File millenniumBarcodesFile = new File("/data/pika/nashville.production/migration_files/PATRON_PIKA.txt");
		if (!millenniumBarcodesFile.exists()){
			return false;
		}else{
			try {
				CSVReader reader = new CSVReader(new FileReader(millenniumBarcodesFile), '|');
				String[] csvValues = reader.readNext();
				//Skip the header
				csvValues = reader.readNext();
				while (csvValues != null){
					//This only has the map from the system the patron was created within.
					HashSet<String> barcodesByPNumber;
					String pNumber = csvValues[1];
					pNumber = pNumber.replace("p", "");
					pNumber = pNumber.substring(0, pNumber.length() - 1);
					if (millenniumBarcodesByPType.containsKey(pNumber)){
						barcodesByPNumber = millenniumBarcodesByPType.get(pNumber);
					}else{
						barcodesByPNumber = new HashSet<>();
						millenniumBarcodesByPType.put(pNumber, barcodesByPNumber);
					}
					barcodesByPNumber.add(csvValues[0]);
					csvValues = reader.readNext();
				}
				return true;
			}catch (Exception e){
				logger.error("Error reading millennium patron file", e);
				return false;
			}
		}
	}

	private boolean loadPatronMappingFile(Logger logger) {
		File patronMappingFile = new File("/data/pika/nashville.production/migration_files/PIKA Patron Cross Reference_full.csv");
		if (!patronMappingFile.exists()){
			return false;
		}else{
			try {
				CSVReader reader = new CSVReader(new FileReader(patronMappingFile));
				String[] csvValues = reader.readNext();
				//Skip the header
				csvValues = reader.readNext();
				while (csvValues != null){
					//This only has the map from the system the patron was created within.
					CarlXPatronMap newMap = new CarlXPatronMap(csvValues[0], csvValues[1], csvValues[2], csvValues[3]);
					patronMap.put(newMap.getKey(), newMap);
					csvValues = reader.readNext();
				}
				return true;
			}catch (Exception e){
				logger.error("Error reading patron mapping file", e);
				return false;
			}
		}
	}

	private void updateMillenniumUsers(CronProcessLogEntry processLog, Connection vufindConn, Logger logger){
		//Get a list of all Millennium users
		try {
			processLog.addNote("Starting to update millennium users");
			processLog.saveToDatabase(vufindConn, logger);
			PreparedStatement millenniumUsersStmt = vufindConn.prepareStatement("SELECT id, username, cat_username FROM user where source = 'ils'");
			PreparedStatement updateMillenniumUserStmt = vufindConn.prepareStatement("UPDATE user SET username = ?, cat_username = ?, source = 'carlx' where id = ? and source = 'ils'");

			ResultSet millenniumUsersRS = millenniumUsersStmt.executeQuery();
			int numUpdates = 0;
			int numMerges = 0;
			int numDeletes = 0;
			while (millenniumUsersRS.next()){
				String userBarcode = millenniumUsersRS.getString("cat_username");
				String oldUniqueId = millenniumUsersRS.getString("username");
				Long userId = millenniumUsersRS.getLong("id");

				//Get the new unique id (username) for the user in CARL.X  For LSS this is based on the barcode rather than the old patron id.
				CarlXPatronMap migratedUserInformation = patronMap.get("ils-" + oldUniqueId);
				//First try to merge the patrons
				if (migratedUserInformation == null){
					//Check to see if the user was migrated from lss by checking the barcode
					migratedUserInformation = patronMap.get("lss-" + userBarcode);
					if (migratedUserInformation != null){
						logger.debug("Found migration information from lss");
					}else{
						HashSet<String> alternateBarcodes = millenniumBarcodesByPType.get(oldUniqueId);
						if (alternateBarcodes != null) {
							for (String alternateBarcode : alternateBarcodes) {
								migratedUserInformation = patronMap.get("lss-" + alternateBarcode);
								if (migratedUserInformation != null) {
									logger.debug("Found migration information from lss based on alternate barcode");
									break;
								}
							}
						//}else{
							//logger.warn("Did not find alternate barcodes for " + oldUniqueId);
						}
					}
				}
				if (migratedUserInformation == null){
					logger.warn("Did not find migration information for millennium patron with barcode " + userBarcode + " patron id " + oldUniqueId);
					if (deleteMissingUsers){
						deletePatronStmt.setLong(1, userId);
						deletePatronStmt.executeUpdate();
						numDeletes++;
					}
				}else {
					try {
						if (!patronsMerged(vufindConn, userId, migratedUserInformation.getPatronGuid(), logger)) {
							//Patrons don't need to be merged, just update
							updateMillenniumUserStmt.setString(1, migratedUserInformation.getPatronGuid());
							updateMillenniumUserStmt.setString(2, migratedUserInformation.getPatronId());
							updateMillenniumUserStmt.setLong(3, userId);
							updateMillenniumUserStmt.executeUpdate();
							numUpdates++;
						}else{
							numMerges++;
						}
					}catch (Exception e){
						logger.error("Error updating millennium users with barcode " + userBarcode + " patron id " + oldUniqueId, e);
					}
				}
			}
			processLog.addUpdates(numUpdates + numMerges + numDeletes);
			processLog.addNote("Finished processing millennium users.  " + numUpdates + " updated, " + numMerges + " merged, " + numDeletes + " deleted");
			processLog.saveToDatabase(vufindConn, logger);
		}catch (Exception e){
			logger.error("Error updating millennium users", e);
			processLog.addNote("Error updating millennium users" +  e.toString());
			processLog.saveToDatabase(vufindConn, logger);
		}
	}

	private void updateLssUsers(CronProcessLogEntry processLog, Connection vufindConn, Logger logger) {
		//Get a list of all LSS users
		try {
			processLog.addNote("Starting to update LSS users");
			processLog.saveToDatabase(vufindConn, logger);
			PreparedStatement lssUsersStmt = vufindConn.prepareStatement("SELECT id, username, cat_username FROM user where source = 'lss'");
			PreparedStatement updateLssUserStmt = vufindConn.prepareStatement("UPDATE user SET username = ?, cat_username = ?, source = 'carlx' where id = ? and source = 'lss'");

			ResultSet lssUsersRS = lssUsersStmt.executeQuery();
			int numUpdates = 0;
			while (lssUsersRS.next()){
				String userBarcode = lssUsersRS.getString("cat_username");
				//String oldUniqueId = lssUsersRS.getString("username");
				Long userId = lssUsersRS.getLong("id");

				//Get the new unique id (username) for the user in CARL.X  For LSS this is based on the barcode rather than the old patron id.
				CarlXPatronMap migratedUserInformation = patronMap.get("lss-" + userBarcode);
				if (migratedUserInformation == null){
					logger.warn("Did not find migration information for LSS patron with barcode " + userBarcode);
					if (deleteMissingUsers){
						deletePatronStmt.setLong(1, userId);
						deletePatronStmt.executeUpdate();
					}
				}else {
					//First try to merge the patrons
					if (!patronsMerged(vufindConn, userId, migratedUserInformation.getPatronGuid(), logger)) {
						//Patrons don't need to be merged, just update
						updateLssUserStmt.setString(1, migratedUserInformation.getPatronGuid());
						updateLssUserStmt.setString(2, migratedUserInformation.getPatronId());
						updateLssUserStmt.setLong(3, userId);
						updateLssUserStmt.executeUpdate();
						numUpdates++;
					}
				}
			}
			processLog.addUpdates(numUpdates);
			processLog.addNote("Finished updating LSS users");
			processLog.saveToDatabase(vufindConn, logger);
		}catch (Exception e){
			logger.error("Error updating lss users", e);
			processLog.addNote("Error updating lss users" + e.toString());
			processLog.saveToDatabase(vufindConn, logger);
		}
	}

	private PreparedStatement checkForExistingPatronStmt = null;
	private PreparedStatement getExistingLinkStmt = null;
	private PreparedStatement mergeUserLinkStmt1 = null;
	private PreparedStatement mergeUserLinkStmt2 = null;
	private PreparedStatement mergeUserLinkBlocksStmt1 = null;
	private PreparedStatement mergeUserLinkBlocksStmt2 = null;
	private PreparedStatement mergeReadingHistoryStmt = null;
	private PreparedStatement mergeTagsStmt = null;
	private PreparedStatement mergeSavedSearchesStmt = null;
	private PreparedStatement mergeRolesStmt = null;
	private PreparedStatement mergeRatingsStmt = null;
	private PreparedStatement mergeNotInterestedStmt = null;
	private PreparedStatement mergeStaffSettingsStmt = null;
	private PreparedStatement mergeListsStmt = null;
	private PreparedStatement mergeBrowseCategoryStmt = null;
	private PreparedStatement mergeMaterialsRequestStmt1 = null;
	private PreparedStatement mergeMaterialsRequestStmt2 = null;
	private PreparedStatement deletePatronStmt = null;
	private PreparedStatement deleteLinkStmt = null;

	private boolean setupUserMigrationStatements(Connection vufindConn, Logger logger){
		try {
			checkForExistingPatronStmt = vufindConn.prepareStatement("SELECT id, cat_username FROM user where username = ? AND source = 'carlx'");
			mergeBrowseCategoryStmt = vufindConn.prepareStatement("UPDATE browse_category set userId = ? where userId = ?");
			getExistingLinkStmt = vufindConn.prepareStatement("SELECT * from user_link where primaryAccountId = ? or linkedAccountId = ? or primaryAccountId = ? or linkedAccountId = ?");
			mergeUserLinkStmt1 = vufindConn.prepareStatement("UPDATE user_link set primaryAccountId = ? where primaryAccountId = ?");
			mergeUserLinkStmt2 = vufindConn.prepareStatement("UPDATE user_link set linkedAccountId = ? where linkedAccountId = ?");
			mergeUserLinkBlocksStmt1 = vufindConn.prepareStatement("UPDATE user_link_blocks set primaryAccountId = ? where primaryAccountId = ?");
			mergeUserLinkBlocksStmt2 = vufindConn.prepareStatement("UPDATE user_link_blocks set blockedLinkAccountId = ? where blockedLinkAccountId = ?");
			mergeListsStmt = vufindConn.prepareStatement("UPDATE user_list set user_id = ? where user_id = ?");
			mergeNotInterestedStmt = vufindConn.prepareStatement("UPDATE user_not_interested set userId = ? where userId = ?");
			mergeReadingHistoryStmt = vufindConn.prepareStatement("UPDATE user_reading_history_work set userId = ? where userId = ?");
			mergeRolesStmt = vufindConn.prepareStatement("UPDATE user_roles set userId = ? where userId = ?");
			mergeSavedSearchesStmt = vufindConn.prepareStatement("UPDATE search set user_id = ? where user_id = ?");
			mergeStaffSettingsStmt = vufindConn.prepareStatement("UPDATE user_staff_settings set userId = ? where userId = ?");
			mergeTagsStmt = vufindConn.prepareStatement("UPDATE user_tags set userId = ? where userId = ?");
			mergeRatingsStmt = vufindConn.prepareStatement("UPDATE user_work_review set userId = ? where userId = ?");
			mergeMaterialsRequestStmt1 = vufindConn.prepareStatement("UPDATE materials_request set createdBy = ? where createdBy = ?");
			mergeMaterialsRequestStmt2 = vufindConn.prepareStatement("UPDATE materials_request set assignedTo = ? where assignedTo = ?");
			deleteLinkStmt = vufindConn.prepareStatement("DELETE from user_link where id = ?");
			deletePatronStmt = vufindConn.prepareStatement("DELETE from user where id = ?");
			return true;
		} catch (SQLException e) {
			logger.error("Error creating statement", e);
			return false;
		}
	}
	private boolean patronsMerged(Connection vufindConn, Long userId, String patronGuid, Logger logger) throws SQLException {
		checkForExistingPatronStmt.setString(1, patronGuid);
		ResultSet checkForExistingPatronRS = checkForExistingPatronStmt.executeQuery();
		if (checkForExistingPatronRS.next()){
			Long newUserId = checkForExistingPatronRS.getLong("id");
			String newBarcode = checkForExistingPatronRS.getString("cat_username");
			//We have an existing patron with this information, merge enrichment data for the user and then delete the user.
			mergeBrowseCategoryStmt.setLong(2, userId);
			mergeBrowseCategoryStmt.setLong(1, newUserId);
			int numChanges = mergeBrowseCategoryStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged browse categories for patron " + newUserId + " " + newBarcode);
			}

			getExistingLinkStmt.setLong(1, userId);
			getExistingLinkStmt.setLong(2, userId);
			getExistingLinkStmt.setLong(3, newUserId);
			getExistingLinkStmt.setLong(4, newUserId);
			ResultSet existingLinkRS = getExistingLinkStmt.executeQuery();
			while (existingLinkRS.next()){
				Long primaryId = existingLinkRS.getLong("primaryAccountId");
				Long linkedId = existingLinkRS.getLong("linkedAccountId");
				Long linkId = existingLinkRS.getLong("id");
				//Check to see if this will be self referential.  If so, delete it.
				if ((primaryId.equals(userId) || primaryId.equals(newUserId)) && (linkedId.equals(userId) || linkedId.equals(newUserId))){
					deleteLinkStmt.setLong(1, linkId);
					deleteLinkStmt.executeUpdate();
				}else if (primaryId.equals(linkedId)){
					deleteLinkStmt.setLong(1, linkId);
					deleteLinkStmt.executeUpdate();
				}else{
					logger.debug("Found existing link that is not self referential for patron " + newUserId + " " + newBarcode);
				}
			}
			mergeUserLinkStmt1.setLong(2, userId);
			mergeUserLinkStmt1.setLong(1, newUserId);
			numChanges = mergeUserLinkStmt1.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged user link primary account id for patron " + newUserId + " " + newBarcode);
			}

			mergeUserLinkStmt2.setLong(2, userId);
			mergeUserLinkStmt2.setLong(1, newUserId);
			try {
				numChanges = mergeUserLinkStmt2.executeUpdate();
				if (numChanges > 0) {
					logger.debug("Merged user link secondary account id for patron " + newUserId + " " + newBarcode);
				}
			}catch (Exception e){
				logger.error("Error merging user link", e);
			}

			mergeUserLinkBlocksStmt1.setLong(2, userId);
			mergeUserLinkBlocksStmt1.setLong(1, newUserId);
			numChanges = mergeUserLinkBlocksStmt1.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged user link block primary account id for patron " + newUserId + " " + newBarcode);
			}

			mergeUserLinkBlocksStmt2.setLong(2, userId);
			mergeUserLinkBlocksStmt2.setLong(1, newUserId);
			numChanges = mergeUserLinkBlocksStmt2.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged user link block - blocked account id for patron " + newUserId + " " + newBarcode);
			}

			mergeListsStmt.setLong(2, userId);
			mergeListsStmt.setLong(1, newUserId);
			numChanges = mergeListsStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged lists for patron " + newUserId + " " + newBarcode);
			}

			mergeNotInterestedStmt.setLong(2, userId);
			mergeNotInterestedStmt.setLong(1, newUserId);
			numChanges = mergeNotInterestedStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged not interested titles for patron " + newUserId + " " + newBarcode);
			}

			mergeReadingHistoryStmt.setLong(2, userId);
			mergeReadingHistoryStmt.setLong(1, newUserId);
			numChanges = mergeReadingHistoryStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged reading history for patron " + newUserId + " " + newBarcode);
			}

			mergeRolesStmt.setLong(2, userId);
			mergeRolesStmt.setLong(1, newUserId);
			numChanges = mergeRolesStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged roles for patron " + newUserId + " " + newBarcode);
			}

			mergeSavedSearchesStmt.setLong(2, userId);
			mergeSavedSearchesStmt.setLong(1, newUserId);
			numChanges = mergeSavedSearchesStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged saved searches for patron " + newUserId + " " + newBarcode);
			}

			mergeStaffSettingsStmt.setLong(2, userId);
			mergeStaffSettingsStmt.setLong(1, newUserId);
			numChanges = mergeStaffSettingsStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged staff settings for patron " + newUserId + " " + newBarcode);
			}

			mergeTagsStmt.setLong(2, userId);
			mergeTagsStmt.setLong(1, newUserId);
			numChanges = mergeTagsStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged tags for patron " + newUserId + " " + newBarcode);
			}

			mergeRatingsStmt.setLong(2, userId);
			mergeRatingsStmt.setLong(1, newUserId);
			numChanges = mergeRatingsStmt.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged ratings/reviews for patron " + newUserId + " " + newBarcode);
			}

			mergeMaterialsRequestStmt1.setLong(2, userId);
			mergeMaterialsRequestStmt1.setLong(1, newUserId);
			numChanges = mergeMaterialsRequestStmt1.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged materials request created by for patron " + newUserId + " " + newBarcode);
			}

			mergeMaterialsRequestStmt2.setLong(2, userId);
			mergeMaterialsRequestStmt2.setLong(1, newUserId);
			numChanges = mergeMaterialsRequestStmt2.executeUpdate();
			if (numChanges > 0){
				logger.debug("Merged materials request assigned to for patron " + newUserId + " " + newBarcode);
			}

			deletePatronStmt.setLong(1, userId);
			numChanges = deletePatronStmt.executeUpdate();

			return true;
		}
		return false;
	}

	/**
	 * Correct records not to merge and reading history data so the newly merged records in CARL.X are referenced
	 *
	 * @param processLog
	 * @param vufindConn The connection to the Pika database
	 * @param logger     The logger for reporting errors
	 */
	private void fixBibLinks(CronProcessLogEntry processLog, Connection vufindConn, Logger logger) {
		//Get the export file from CARL.X
		processLog.addNote("Starting to fix bib record links");
		processLog.saveToDatabase(vufindConn, logger);
		File carlXExport = new File(carlxExportLocation);
		if (!carlXExport.exists()){
			logger.warn("Could not find carlx export in " + carlxExportLocation);
			this.processLog.addNote("Could not find carlx export in " + carlxExportLocation);
			this.processLog.incErrors();
			return;
		}
		//Get the old LSS export
		File lssExport = new File(lssExportLocation);
		if (!lssExport.exists()){
			logger.warn("Could not find LSS export in " + lssExportLocation);
			this.processLog.addNote("Could not find LSS export in " + lssExportLocation);
			this.processLog.incErrors();
			return;
		}

		//Make a map for LSS records to map 001 to the 039
		HashMap<String, String> lssControlNumberToUniqueId = new HashMap<>();
		try {
			MarcPermissiveStreamReader lssReader = new MarcPermissiveStreamReader(new FileInputStream(lssExport), true, true);
			while (lssReader.hasNext()){
				Record lssRecord = lssReader.next();
				String controlNumber = ((ControlField)lssRecord.getVariableField("001")).getData().trim();
				VariableField lssNumberField = lssRecord.getVariableField("039");
				if (lssNumberField != null){
					DataField lssNumberDataField = (DataField)lssNumberField;
					String lssNumber = lssNumberDataField.getSubfield('a').getData();
					if (lssControlNumberToUniqueId.containsKey(controlNumber)){
						logger.warn("Warning control number " + controlNumber + " was not unique");
					}else{
						lssControlNumberToUniqueId.put(controlNumber, lssNumber);
					}
				}else{
					logger.warn("Did not find the lss number for record with control number " + controlNumber);
				}
			}

		}catch (Exception e){
			logger.error("Error in fixBibLinks" ,  e);
			this.processLog.addNote("Error in fixBibLinks - " +  e.toString());
			this.processLog.incErrors();
		}

		//Loop through all records
		try {
			PreparedStatement updateRecordNotToGroupStmt = vufindConn.prepareStatement("UPDATE nongrouped_records SET source='ils', recordId = ? where source = ? and recordId = ? ");
			PreparedStatement updateReadingHistoryStmt = vufindConn.prepareStatement("UPDATE user_reading_history_work SET sourceId = ? WHERE sourceId = ?");

			MarcPermissiveStreamReader carlxReader = new MarcPermissiveStreamReader(new FileInputStream(carlXExport), true, true);
			//Check the 907 (millennium) and 908 (LSS)
			//Update the old within the records not to group based on the 910
			logger.warn("Starting to process records from carlx");
			int numProcessed = 0;
			while (carlxReader.hasNext()){
				Record carlxRecord = carlxReader.next();
				VariableField carlXIdentifierField = carlxRecord.getVariableField("910");
				String carlxIdentifier = ((DataField)carlXIdentifierField).getSubfield('a').getData();
				List<VariableField> millenniumIdentifierFields = carlxRecord.getVariableFields("907");
				for (VariableField millenniumIdentifierField : millenniumIdentifierFields){
					String millenniumIdentifier = ((DataField)millenniumIdentifierField).getSubfield('a').getData();

					if (millenniumIdentifier.matches("\\.b.*")) {
						updateRecordNotToGroupStmt.setString(1, carlxIdentifier);
						updateRecordNotToGroupStmt.setString(2, "millennium");
						updateRecordNotToGroupStmt.setString(3, millenniumIdentifier);
						int numUpdated = updateRecordNotToGroupStmt.executeUpdate();
						if (numUpdated == 1) {
							this.processLog.incUpdated();
							this.processLog.addNote("Updated Millennium identifier " + millenniumIdentifier + " to " + carlxIdentifier + " in records not to group");
							logger.warn("Updated Millennium identifier " + millenniumIdentifier + " to " + carlxIdentifier + " in records not to group");
						}

						updateReadingHistoryStmt.setString(1, "carlx:" + carlxIdentifier);
						updateReadingHistoryStmt.setString(2, "millennium:" + millenniumIdentifier);
						numUpdated = updateReadingHistoryStmt.executeUpdate();
						if (numUpdated == 1) {
							this.processLog.incUpdated();
							this.processLog.addNote("Updated Millennium identifier " + millenniumIdentifier + " to " + carlxIdentifier + " in reading history " + numUpdated);
							logger.warn("Updated Millennium identifier " + millenniumIdentifier + " to " + carlxIdentifier + " in reading history " + numUpdated);
						}
					}else{
						logger.debug("Invalid millennium identifer");
					}
				}
				List<VariableField> lssControlNumberFields = carlxRecord.getVariableFields("908");
				for (VariableField lssControlNumberField : lssControlNumberFields){
					String lssControlNumber = ((DataField)lssControlNumberField).getSubfield('a').getData();
					String lssIdentifier = lssControlNumberToUniqueId.get(lssControlNumber);
					if (lssIdentifier != null) {
						updateRecordNotToGroupStmt.setString(1, carlxIdentifier);
						updateRecordNotToGroupStmt.setString(2, "lss");
						updateRecordNotToGroupStmt.setString(3, lssIdentifier);
						int numUpdated = updateRecordNotToGroupStmt.executeUpdate();
						if (numUpdated == 1) {
							this.processLog.incUpdated();
							this.processLog.addNote("Updated LSS identifier " + lssIdentifier + " to " + carlxIdentifier + " in records not to group");
							logger.warn("Updated LSS identifier " + lssIdentifier + " to " + carlxIdentifier + " in records not to group");
						}

						updateReadingHistoryStmt.setString(1, "carlx:" + carlxIdentifier);
						updateReadingHistoryStmt.setString(2, "lss:" + lssIdentifier);
						numUpdated = updateReadingHistoryStmt.executeUpdate();
						if (numUpdated == 1) {
							this.processLog.incUpdated();
							this.processLog.addNote("Updated Millennium identifier " + lssIdentifier + " to " + carlxIdentifier + " in reading history " + numUpdated);
							logger.warn("Updated Millennium identifier " + lssIdentifier + " to " + carlxIdentifier + " in reading history " + numUpdated);
						}
					}else{
						//It looks like there is more than just control numbers from LSS here so this is normal.
						logger.debug("Did not find an identifier for lss control number " + lssControlNumber);
					}
				}
				numProcessed++;
				if (numProcessed % 10000 == 0){
					logger.warn("Processed " + numProcessed + " records");
				}
			}
		}catch (Exception e){
			logger.error("Error in fixBibLinks", e);
			this.processLog.addNote("Error in fixBibLinks - " +  e.toString());
			this.processLog.incErrors();
		}

		processLog.addNote("Finished fixing bib record links");
		processLog.saveToDatabase(vufindConn, logger);
	}

	private boolean loadConfig(Ini configIni, Profile.Section processSettings) {
		lssExportLocation = processSettings.get("lssExportLocation");
		carlxExportLocation = processSettings.get("carlxExportLocation");
		deleteMissingUsers = Boolean.parseBoolean(processSettings.get("deleteMissingUsers"));
		return true;
	}
}
