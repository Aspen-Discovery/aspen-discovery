package com.turning_leaf_technologies.koha_export;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.grouping.MarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.MarcXmlReader;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;

import java.io.*;
import java.net.URLEncoder;
import java.nio.charset.Charset;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

public class KohaExportMain {
	private static Logger logger;

	private static IndexingProfile indexingProfile;
	private static PreparedStatement getBaseMarcRecordStmt;
	private static PreparedStatement getBibItemsStmt;
	private static MarcFactory marcFactory = MarcFactory.newInstance();
	private static MarcRecordGrouper recordGroupingProcessorSingleton;
	private static GroupedWorkIndexer groupedWorkIndexer;
	private static Ini configIni;
	private static Connection dbConn;
	private static String serverName;

	private static Long startTimeForLogging;
	private static IlsExtractLogEntry logEntry;

	public static void main(String[] args) {
		if (args.length == 0) {
			System.out.println("You must provide the server name as the first argument.");
			System.exit(1);
		}
		serverName = args[0];
		String profileToLoad = "ils";

		logger = LoggingUtil.setupLogging(serverName, "koha_export");

		while (true) {
			Date startTime = new Date();
			startTimeForLogging = startTime.getTime() / 1000;
			logger.info(startTime.toString() + ": Starting Koha Extract");

			// Read the base INI file to get information about the server (current directory/conf/config.ini)
			configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

			//Connect to the database
			Connection kohaConn = null;

			try {
				String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
				if (databaseConnectionInfo == null) {
					logger.error("Please provide database_aspen_jdbc within config.ini (or better config.pwd.ini) ");
					System.exit(1);
				}
				dbConn = DriverManager.getConnection(databaseConnectionInfo);

				//Get information about the account profile for koha
				PreparedStatement accountProfileStmt = dbConn.prepareStatement("SELECT * from account_profiles WHERE driver = 'Koha'");
				ResultSet accountProfileRS = accountProfileStmt.executeQuery();
				if (accountProfileRS.next()) {
					try {
						String host = accountProfileRS.getString("databaseHost");
						String port = accountProfileRS.getString("databasePort");
						if (port == null || port.length() == 0) {
							port = "3306";
						}
						String databaseName = accountProfileRS.getString("databaseName");
						String user = accountProfileRS.getString("databaseUser");
						String password = accountProfileRS.getString("databasePassword");
						String timezone = accountProfileRS.getString("databaseTimezone");

						String kohaConnectionJDBC = "jdbc:mysql://" +
								host + ":" + port +
								"/" + databaseName +
								"?user=" + user +
								"&password=" + password +
								"&useUnicode=yes&characterEncoding=UTF-8";
						if (timezone != null && timezone.length() > 0) {
							kohaConnectionJDBC += "&serverTimezone=" + URLEncoder.encode(timezone, "UTF8");

						}
						kohaConn = DriverManager.getConnection(kohaConnectionJDBC);

						getBaseMarcRecordStmt = kohaConn.prepareStatement("SELECT * from biblio_metadata where biblionumber = ?");
						getBibItemsStmt = kohaConn.prepareStatement("SELECT * from items where biblionumber = ?");

						profileToLoad = accountProfileRS.getString("recordSource");
					} catch (Exception e) {
						logger.error("Error connecting to koha database ", e);
						System.exit(1);
					}
				} else {
					logger.error("Could not find an account profile for Koha stopping");
					System.exit(1);
				}
			} catch (Exception e) {
				logger.error("Error connecting to database ", e);
				System.exit(1);
			}

			logEntry = new IlsExtractLogEntry(dbConn, profileToLoad, logger);

			indexingProfile = IndexingProfile.loadIndexingProfile(dbConn, profileToLoad, logger);

			//Remove log entries older than 45 days
			long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
			try {
				int numDeletions = dbConn.prepareStatement("DELETE from ils_extract_log WHERE startTime < " + earliestLogToKeep + " AND indexingProfile = '" + indexingProfile.getName() + "'").executeUpdate();
				logger.info("Deleted " + numDeletions + " old log entries");
			} catch (SQLException e) {
				logger.error("Error deleting old log entries", e);
			}

			updateBranchInfo(dbConn, kohaConn);
			logEntry.addNote("Finished updating branch information");

			updateTranslationMaps(dbConn, kohaConn);
			logEntry.addNote("Finished updating translation maps");

			exportHolds(dbConn, kohaConn);
			logEntry.addNote("Finished loading holds");

			//Update works that have changed since the last index
			int numChanges = updateRecords(dbConn, kohaConn);

			logEntry.setFinished();

			try {
				//Close the connection
				dbConn.close();
			} catch (Exception e) {
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
			try {
				//Close the connection
				kohaConn.close();
			} catch (Exception e) {
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
			Date currentTime = new Date();
			logger.info(currentTime.toString() + ": Finished Koha Extract");

			//Pause before running the next export (longer if we didn't get any actual changes)
			try {
				if (numChanges == 0) {
					Thread.sleep(1000 * 60 * 5);
				}else {
					Thread.sleep(1000 * 60);
				}
			} catch (InterruptedException e) {
				logger.info("Thread was interrupted");
			}
		}
	}

	private static void updateTranslationMaps(Connection dbConn, Connection kohaConn) {
		try {
			PreparedStatement createTranslationMapStmt = dbConn.prepareStatement("INSERT INTO translation_maps (name, indexingProfileId) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			PreparedStatement getTranslationMapStmt = dbConn.prepareStatement("SELECT id from translation_maps WHERE name = ? and indexingProfileId = ?");
			PreparedStatement getExistingValuesForMapStmt = dbConn.prepareStatement("SELECT * from translation_map_values where translationMapId = ?");
			PreparedStatement insertTranslationStmt = dbConn.prepareStatement("INSERT INTO translation_map_values (translationMapId, value, translation) VALUES (?, ?, ?)");

			//Load branches into location
			PreparedStatement kohaBranchesStmt = kohaConn.prepareStatement("SELECT branchcode, branchname from branches");
			Long translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "location");
			HashMap <String, String> existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaBranchesStmt, "branchcode", "branchname", insertTranslationStmt, translationMapId, existingValues);

			//Load LOC into sub location
			PreparedStatement kohaLocStmt = kohaConn.prepareStatement("SELECT * FROM authorised_values where category = 'LOC'");
			translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "sub_location");
			existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaLocStmt, "authorised_value", "lib", insertTranslationStmt, translationMapId, existingValues);

			//Load ccodes into shelf location
			PreparedStatement kohaCCodesStmt = kohaConn.prepareStatement("SELECT * FROM authorised_values where category = 'CCODE'");
			translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "shelf_location");
			existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaCCodesStmt, "authorised_value", "lib", insertTranslationStmt, translationMapId, existingValues);

			//Load itemtypes into formats
			PreparedStatement kohaItemTypesStmt = kohaConn.prepareStatement("SELECT itemtype, description FROM itemtypes");
			translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "format");
			existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaItemTypesStmt, "itemtype", "description", insertTranslationStmt, translationMapId, existingValues);

			//Also load item types into itype
			translationMapId = getTranslationMapId(createTranslationMapStmt, getTranslationMapStmt, "itype");
			existingValues = getExistingTranslationMapValues(getExistingValuesForMapStmt, translationMapId);
			updateTranslationMap(kohaItemTypesStmt, "itemtype", "description", insertTranslationStmt, translationMapId, existingValues);

		}catch (SQLException e) {
			logger.error("Error updating translation maps", e);
		}
	}

	private static void updateTranslationMap(PreparedStatement kohaValuesStmt, String valueColumn, String translationColumn, PreparedStatement insertTranslationStmt, Long translationMapId, HashMap<String, String> existingValues) throws SQLException {
		ResultSet kohaValuesRS = kohaValuesStmt.executeQuery();
		while (kohaValuesRS.next()) {
			String value = kohaValuesRS.getString(valueColumn);
			String translation = kohaValuesRS.getString(translationColumn);
			if (existingValues.containsKey(value)){
				if (!existingValues.get(value).equals(translation)){
					logger.warn("Translation for " + value + " has changed from " + existingValues.get(value) + " to " + translation);
				}
			} else {
				insertTranslationStmt.setLong(1, translationMapId);
				insertTranslationStmt.setString(2, value);
				insertTranslationStmt.setString(3, translation);
				insertTranslationStmt.executeUpdate();
			}
		}
	}

	private static HashMap<String, String> getExistingTranslationMapValues(PreparedStatement getExistingValuesForMapStmt, Long translationMapId) throws SQLException {
		HashMap<String, String> existingValues = new HashMap<>();
		getExistingValuesForMapStmt.setLong(1, translationMapId);
		ResultSet getExistingValuesForMapRS = getExistingValuesForMapStmt.executeQuery();
		while (getExistingValuesForMapRS.next()) {
			existingValues.put(getExistingValuesForMapRS.getString("value"), getExistingValuesForMapRS.getString("translation"));
		}
		return existingValues;
	}

	private static Long getTranslationMapId(PreparedStatement createTranslationMapStmt, PreparedStatement getTranslationMapStmt, String mapName) throws SQLException {
		Long translationMapId = null;
		getTranslationMapStmt.setString(1, mapName);
		getTranslationMapStmt.setLong(2, indexingProfile.getId());
		ResultSet getTranslationMapRS = getTranslationMapStmt.executeQuery();
		if (getTranslationMapRS.next()){
			translationMapId = getTranslationMapRS.getLong("id");
		} else {
			//Map does not exist, create it
			createTranslationMapStmt.setString(1, mapName);
			createTranslationMapStmt.setLong(2, indexingProfile.getId());
			createTranslationMapStmt.executeUpdate();
			ResultSet generatedIds = createTranslationMapStmt.getGeneratedKeys();
			if (generatedIds.next()) {
				translationMapId = generatedIds.getLong(1);
			}
		}
		return translationMapId;
	}

	private static void updateBranchInfo(Connection dbConn, Connection kohaConn) {
        try {
            PreparedStatement kohaBranchesStmt = kohaConn.prepareStatement("SELECT * from branches");
            PreparedStatement existingAspenLocationStmt = dbConn.prepareStatement("SELECT libraryId, locationId, isMainBranch from location where code = ?");
            PreparedStatement updateAspenLocationStmt = dbConn.prepareStatement("UPDATE location SET displayName = ?, address = ?, phone = ? where locationId = ?");
            PreparedStatement kohaRepeatableHolidaysStmt = kohaConn.prepareStatement("SELECT * FROM repeatable_holidays where branchcode = ?");
            PreparedStatement kohaSpecialHolidaysStmt = kohaConn.prepareStatement("SELECT * FROM special_holidays where (year = ? or year = ?) AND branchcode = ? order by  year, month, day");
			PreparedStatement existingHoursStmt = dbConn.prepareStatement("SELECT count(*) FROM location_hours where locationId = ?");
			PreparedStatement addHoursStmt = dbConn.prepareStatement("INSERT INTO location_hours (locationId, day, closed, open, close) VALUES (?, ?, 0, '00:30', '00:30') ");
			PreparedStatement existingHolidaysStmt = dbConn.prepareStatement("SELECT * FROM holiday where libraryId = ? and date >= ?");
            PreparedStatement addHolidayStmt = dbConn.prepareStatement("INSERT INTO holiday (libraryId, date, name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            PreparedStatement removeHolidayStmt = dbConn.prepareStatement("DELETE FROM holiday WHERE id = ?");
			PreparedStatement markLibraryClosed = dbConn.prepareStatement("UPDATE location_hours set closed = 1 where locationId = ? and day = ?");
            ResultSet kohaBranches = kohaBranchesStmt.executeQuery();
            String currentYear = new SimpleDateFormat("yyyy").format(new Date());
            GregorianCalendar nextYearCal = new GregorianCalendar();
            nextYearCal.roll(GregorianCalendar.YEAR, 1);
            String nextYear = new SimpleDateFormat("yyyy").format(nextYearCal.getTime());
            while (kohaBranches.next()){
                String ilsCode = kohaBranches.getString("branchcode");
                existingAspenLocationStmt.setString(1, ilsCode);
                ResultSet existingAspenLocationRS = existingAspenLocationStmt.executeQuery();
                if (existingAspenLocationRS.next()){
                    long existingLocationId = existingAspenLocationRS.getLong("locationId");
                    updateAspenLocationStmt.setString(1, kohaBranches.getString("branchname"));
                    String address = kohaBranches.getString("branchaddress1");
                    String address2 = kohaBranches.getString("branchaddress2");
                    if (address2 != null && address2.length() > 0){
                        address += "\r\n" + address2;
                    }
                    address += "\r\n" + kohaBranches.getString("branchcity") + "," + kohaBranches.getString("branchstate") + " " + kohaBranches.getString("branchzip");
                    updateAspenLocationStmt.setString(2, address);
                    updateAspenLocationStmt.setString(3, kohaBranches.getString("branchphone"));
                    updateAspenLocationStmt.setLong(4, existingLocationId);
                    updateAspenLocationStmt.executeUpdate();

					HashMap<java.sql.Date, Long> existingHolidayDates = new HashMap<>();
					long libraryId = existingAspenLocationRS.getLong("libraryId");
					if (existingAspenLocationRS.getBoolean("isMainBranch")) {
						//Get the existing holidays in case we need to delete any
						existingHolidaysStmt.setLong(1, libraryId);
						existingHolidaysStmt.setDate(2, java.sql.Date.valueOf(currentYear + "-01-01"));
						ResultSet existingHolidaysRS = existingHolidaysStmt.executeQuery();
						existingHolidayDates = new HashMap<>();
						while (existingHolidaysRS.next()) {
							existingHolidayDates.put(existingHolidaysRS.getDate("date"), existingHolidaysRS.getLong("id"));
						}
					}

					//update hours for the branch.  We can only get closed times.
					existingHoursStmt.setLong(1, existingLocationId);
					ResultSet existingHoursRS = existingHoursStmt.executeQuery();
					if (existingHoursRS.next()){
						long numHours = existingHoursRS.getLong(1);
						if (numHours == 0){
							//Create default hours
							for (int i = 0; i < 7; i++) {
								addHoursStmt.setLong(1, existingLocationId);
								addHoursStmt.setLong(2, i);
								addHoursStmt.executeUpdate();
							}
						}
					}

					kohaRepeatableHolidaysStmt.setString(1, ilsCode);
					ResultSet kohaRepeatableHolidaysRS = kohaRepeatableHolidaysStmt.executeQuery();
					while (kohaRepeatableHolidaysRS.next()){
						int weekday = kohaRepeatableHolidaysRS.getInt("weekday");
						if (!kohaRepeatableHolidaysRS.wasNull()){
							//The library is closed on this date
							markLibraryClosed.setLong(1, existingLocationId);
							markLibraryClosed.setInt(2, weekday);
							markLibraryClosed.executeUpdate();
						} else {
							//Add the holiday for this year
							String holidayDate = currentYear + "-" + kohaRepeatableHolidaysRS.getString("month") + "-" + kohaRepeatableHolidaysRS.getString("day");
							java.sql.Date holidayDateAsDate = java.sql.Date.valueOf(holidayDate);
							String title = kohaRepeatableHolidaysRS.getString("title");
							addHolidayStmt.setLong(1, libraryId);
							addHolidayStmt.setDate(2, holidayDateAsDate);
							addHolidayStmt.setString(3, title);
							addHolidayStmt.executeUpdate();
							existingHolidayDates.remove(holidayDateAsDate);

							//Add the holiday for next year
							holidayDate = nextYear + "-" + kohaRepeatableHolidaysRS.getString("month") + "-" + kohaRepeatableHolidaysRS.getString("day");
							holidayDateAsDate = java.sql.Date.valueOf(holidayDate);
							addHolidayStmt.setLong(1, libraryId);
							addHolidayStmt.setDate(2, holidayDateAsDate);
							addHolidayStmt.setString(3, title);
							addHolidayStmt.executeUpdate();
							existingHolidayDates.remove(holidayDateAsDate);
						}
					}

                    //update holidays for the library
                    //Koha stores holidays per branch rather than per library system for now, we will just assign based on the main branch
                    if (existingAspenLocationRS.getBoolean("isMainBranch")){
                        kohaSpecialHolidaysStmt.setInt(1, Integer.parseInt(currentYear));
                        kohaSpecialHolidaysStmt.setInt(2, Integer.parseInt(nextYear));
                        kohaSpecialHolidaysStmt.setString(3, ilsCode);
                        ResultSet kohaSpecialHolidaysRS = kohaSpecialHolidaysStmt.executeQuery();
                        while (kohaSpecialHolidaysRS.next()) {
                            String holidayDate = kohaSpecialHolidaysRS.getString("year") + "-" + kohaSpecialHolidaysRS.getString("month") + "-" + kohaSpecialHolidaysRS.getString("day");
                            java.sql.Date holidayDateAsDate = java.sql.Date.valueOf(holidayDate);
                            String title = kohaSpecialHolidaysRS.getString("title");
                            addHolidayStmt.setLong(1, libraryId);
                            addHolidayStmt.setDate(2, holidayDateAsDate);
                            addHolidayStmt.setString(3, title);
                            addHolidayStmt.executeUpdate();
                            existingHolidayDates.remove(holidayDateAsDate);
                        }
                    }

                    //Remove anything leftover
					for (Long holidayToBeDeleted : existingHolidayDates.values()) {
						removeHolidayStmt.setLong(1, holidayToBeDeleted);
						removeHolidayStmt.executeUpdate();
					}
                } else {
                    logger.error("Aspen does not currently have a location defined for code " + ilsCode + " please create the location in Aspen first so the library information is properly defined.");
                }
            }
        }catch (Exception e) {
            logger.error("Error updating branch information from Koha", e);
        }
    }

    private static void exportHolds(Connection dbConn, Connection kohaConn) {
		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = dbConn.setSavepoint("hold_update_start");
			dbConn.setAutoCommit(false);
			dbConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeUpdate();

			PreparedStatement addIlsHoldSummary = dbConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

			HashMap<String, Long> numHoldsByBib = new HashMap<>();
			//Export bib level holds
			PreparedStatement bibHoldsStmt = kohaConn.prepareStatement("select count(*) as numHolds, biblionumber from reserves group by biblionumber", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldsRS = bibHoldsStmt.executeQuery();
			while (bibHoldsRS.next()){
				String bibId = bibHoldsRS.getString("biblionumber");
				Long numHolds = bibHoldsRS.getLong("numHolds");
				numHoldsByBib.put(bibId, numHolds);
			}
			bibHoldsRS.close();

			for (String bibId : numHoldsByBib.keySet()){
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

			try {
				dbConn.commit();
				dbConn.setAutoCommit(true);
			}catch (Exception e){
				logger.warn("error committing hold updates rolling back", e);
				dbConn.rollback(startOfHolds);
			}

		} catch (Exception e) {
			logger.error("Unable to export holds from Koha", e);
			if (startOfHolds != null) {
				try {
					dbConn.rollback(startOfHolds);
				}catch (Exception e1){
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logger.info("Finished exporting holds");
	}

	private static int updateRecords(Connection dbConn, Connection kohaConn) {
		int totalChanges = 0;

		//Get the time the last extract was done
		try{
			logger.info("Starting to load changed records from Koha using the Database connection");
			long lastKohaExtractTime = indexingProfile.getLastUpdateOfChangedRecords();
			if (lastKohaExtractTime == 0){
				lastKohaExtractTime = new Date().getTime() / 1000 - 24 * 60 * 60;
			}
			Timestamp lastExtractTimestamp = new Timestamp(lastKohaExtractTime * 1000);

			HashSet<String> changedBibIds = new HashSet<>();

			//Get a list of bibs that have changed
			PreparedStatement getChangedBibsFromKohaStmt;
			if (indexingProfile.isRunFullUpdate()){
				getChangedBibsFromKohaStmt = kohaConn.prepareStatement("select biblionumber from biblio");
				logEntry.addNote("Getting all records from Koha");
			}else{
				getChangedBibsFromKohaStmt = kohaConn.prepareStatement("select biblionumber from biblio where timestamp >= ?");
				logEntry.addNote("Getting changes to records since " + lastExtractTimestamp.toString());

				getChangedBibsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);
			}

			ResultSet getChangedBibsFromKohaRS = getChangedBibsFromKohaStmt.executeQuery();
			while (getChangedBibsFromKohaRS.next()) {
				changedBibIds.add(getChangedBibsFromKohaRS.getString("biblionumber"));
			}

			if (!indexingProfile.isRunFullUpdate()){
				//Get a list of items that have changed
				PreparedStatement getChangedItemsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from items where timestamp >= ?");
				getChangedItemsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);

				ResultSet itemChangeRS = getChangedItemsFromKohaStmt.executeQuery();
				while (itemChangeRS.next()) {
					changedBibIds.add(itemChangeRS.getString("biblionumber"));
				}

				//Items that have been deleted do not update the bib as changed so get that list as well
				PreparedStatement getDeletedItemsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from deleteditems where timestamp >= ?");
				getDeletedItemsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);

				ResultSet itemDeletedRS = getDeletedItemsFromKohaStmt.executeQuery();
				while (itemDeletedRS.next()) {
					changedBibIds.add(itemDeletedRS.getString("biblionumber"));
				}
			}

			logger.info("A total of " + changedBibIds.size() + " bibs were updated since the last export");
			logEntry.setNumProducts(changedBibIds.size());
			logEntry.saveResults();
			int numProcessed = 0;
			for (String curBibId : changedBibIds){
				//Update the marc record
				updateBibRecord(curBibId);
				numProcessed++;
				if (numProcessed % 250 == 0){
					logEntry.saveResults();
				}
			}

			//Process any bibs that have been deleted
			PreparedStatement getDeletedBibsFromKohaStmt;
			if (indexingProfile.isRunFullUpdate()) {
				getDeletedBibsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from deletedbiblio");
			}else{
				getDeletedBibsFromKohaStmt = kohaConn.prepareStatement("select DISTINCT biblionumber from deletedbiblio where timestamp >= ?");
				getDeletedBibsFromKohaStmt.setTimestamp(1, lastExtractTimestamp);
			}

			ResultSet bibDeletedRS = getDeletedBibsFromKohaStmt.executeQuery();
			int numRecordsDeleted = 0;
			while (bibDeletedRS.next()) {
				String bibId = bibDeletedRS.getString("biblionumber");
				RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(indexingProfile.getName(), bibId);
				if (result.reindexWork){
					getGroupedWorkIndexer().processGroupedWork(result.permanentId);
				}else if (result.deleteWork){
					//Delete the work from solr and the database
					getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
				}
				numRecordsDeleted++;
				logEntry.incDeleted();
			}
			logEntry.saveResults();

			logger.info("Updated " + changedBibIds.size() + " records");
			logger.info("Deleted " + numRecordsDeleted + " records");

			totalChanges = changedBibIds.size() + numRecordsDeleted;

			if (groupedWorkIndexer != null) {
				groupedWorkIndexer.finishIndexingFromExtract();
				recordGroupingProcessorSingleton = null;
				groupedWorkIndexer = null;
			}

			//Update the last extract time for the indexing profile
			if (indexingProfile.isRunFullUpdate()) {
				PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfAllRecords = ?, runFullUpdate = 0 WHERE id = ?");
				updateVariableStmt.setLong(1, startTimeForLogging);
				updateVariableStmt.setLong(2, indexingProfile.getId());
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else {
				if (!logEntry.hasErrors()) {
					PreparedStatement updateVariableStmt = dbConn.prepareStatement("UPDATE indexing_profiles set lastUpdateOfChangedRecords = ? WHERE id = ?");
					updateVariableStmt.setLong(1, startTimeForLogging);
					updateVariableStmt.setLong(2, indexingProfile.getId());
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
				}
			}
		} catch (Exception e){
			logger.error("Error loading changed records from Koha database", e);
			logEntry.incErrors();
			System.exit(1);
		}
		logger.info("Finished loading changed records from Koha database");

		return totalChanges;
	}

	private static void updateBibRecord(String curBibId) {
		//Load the existing marc record from file
		try {
			File marcFile = indexingProfile.getFileForIlsRecord(curBibId);

			//Create a new record from data in the database (faster and more reliable than using ILSDI or OAI export)
			getBaseMarcRecordStmt.setString(1, curBibId);
			ResultSet baseMarcRecordRS = getBaseMarcRecordStmt.executeQuery();
			while (baseMarcRecordRS.next()) {
				String marcXML = baseMarcRecordRS.getString("metadata");
				marcXML = StringUtils.stripNonValidXMLCharacters(marcXML);
				MarcXmlReader marcXmlReader = new MarcXmlReader(new ByteArrayInputStream(marcXML.getBytes(Charset.forName("UTF-8"))));

				//This record has all the basic bib data
				Record marcRecord = marcXmlReader.next();

				//Add the item information
				getBibItemsStmt.setString(1, curBibId);
				ResultSet bibItemsRS = getBibItemsStmt.executeQuery();
				while (bibItemsRS.next()) {
					DataField itemField = marcFactory.newDataField("952", ' ', ' ');

					addSubfield(itemField, 'e', bibItemsRS.getString("booksellerid"));
					addSubfield(itemField, '8', bibItemsRS.getString("ccode"));
					addSubfield(itemField, '6', bibItemsRS.getString("cn_sort"));
					addSubfield(itemField, '2', bibItemsRS.getString("cn_source"));
					addSubfield(itemField, 'f', bibItemsRS.getString("coded_location_qualifier"));
					addSubfield(itemField, 't', bibItemsRS.getString("copynumber"));
					addSubfield(itemField, '4', bibItemsRS.getString("damaged"));
					addSubfield(itemField, 'd', bibItemsRS.getString("dateaccessioned"));
					addSubfield(itemField, 's', bibItemsRS.getString("datelastborrowed"));
					addSubfield(itemField, 'r', bibItemsRS.getString("datelastseen"));
					addSubfield(itemField, 'h', bibItemsRS.getString("enumchron"));
					addSubfield(itemField, 'b', bibItemsRS.getString("holdingbranch"));
					addSubfield(itemField, 'a', bibItemsRS.getString("homebranch"));
					addSubfield(itemField, 'l', bibItemsRS.getString("issues"));
					addSubfield(itemField, 'o', bibItemsRS.getString("itemcallnumber"));
					addSubfield(itemField, '1', bibItemsRS.getString("itemlost"));
					addSubfield(itemField, 'z', bibItemsRS.getString("itemnotes"));
					//addSubfield(itemField, 'x', bibItemsRS.getString("itemnotes_nonpublic"));
					addSubfield(itemField, '9', bibItemsRS.getString("itemnumber"));
					addSubfield(itemField, 'y', bibItemsRS.getString("itype"));
					addSubfield(itemField, 'c', bibItemsRS.getString("location"));
					addSubfield(itemField, '3', bibItemsRS.getString("materials"));
					addSubfield(itemField, '7', bibItemsRS.getString("notforloan"));
					addSubfield(itemField, 'q', bibItemsRS.getString("onloan"));
					addSubfield(itemField, 'g', bibItemsRS.getString("price"));
					addSubfield(itemField, 'm', bibItemsRS.getString("renewals"));
					addSubfield(itemField, 'v', bibItemsRS.getString("replacementprice"));
					addSubfield(itemField, 'w', bibItemsRS.getString("replacementpricedate"));
					addSubfield(itemField, 'n', bibItemsRS.getString("renewals"));
					addSubfield(itemField, '5', bibItemsRS.getString("restricted"));
					addSubfield(itemField, 'j', bibItemsRS.getString("stack"));
					addSubfield(itemField, 'i', bibItemsRS.getString("stocknumber"));
					addSubfield(itemField, 'u', bibItemsRS.getString("uri"));
					addSubfield(itemField,'0', bibItemsRS.getString("withdrawn"));
					marcRecord.addVariableField(itemField);
				}

				if (marcFile.exists()){
					logEntry.incUpdated();
				}else{
					logEntry.incAdded();
				}
				MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile));
				writer.write(marcRecord);

				//Regroup the record
				String groupedWorkId = groupKohaRecord(marcRecord);
				if (groupedWorkId != null) {
					//Reindex the record
					getGroupedWorkIndexer().processGroupedWork(groupedWorkId);
				}

			}

		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
			logEntry.incErrors();
		}
	}

	private static void addSubfield(DataField itemField, char code, String data) {
		if (data != null) {
			itemField.addSubfield(marcFactory.newSubfield(code, data));
		}
	}

	private static String groupKohaRecord(Record marcRecord) {
		return getRecordGroupingProcessor().processMarcRecord(marcRecord, true);
	}

	private static MarcRecordGrouper getRecordGroupingProcessor(){
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new MarcRecordGrouper(dbConn, indexingProfile, logger, false);
		}
		return recordGroupingProcessorSingleton;
	}

	private static GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, dbConn, configIni, false, false, false, logger);
		}
		return groupedWorkIndexer;
	}
}
