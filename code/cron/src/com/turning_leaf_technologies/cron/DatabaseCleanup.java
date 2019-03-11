package com.turning_leaf_technologies.cron;

import java.io.File;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import com.turning_leaf_technologies.config.ConfigUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

@SuppressWarnings("unused")
public class DatabaseCleanup implements IProcessHandler {

	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Database Cleanup");
		processLog.saveToDatabase(dbConn, logger);

		removeExpiredSessions(configIni, dbConn, logger, processLog);
		removeOldSearches(dbConn, logger, processLog);
		removeSpammySearches(dbConn, logger, processLog);
		removeLongSearches(dbConn, logger, processLog);
		cleanupReadingHistory(dbConn, logger, processLog);
		cleanupIndexingReports(configIni, dbConn, logger, processLog);
		removeOldMaterialsRequests(dbConn, logger, processLog);
		removeUserDataForDeletedUsers(dbConn, logger, processLog);

		processLog.setFinished();
		processLog.saveToDatabase(dbConn, logger);
	}

	private void removeUserDataForDeletedUsers(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		try {
			int numUpdates = dbConn.prepareStatement("DELETE FROM user_link where primaryAccountId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user links where the primary account does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_link where linkedAccountId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user links where the linked account does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_link_blocks where primaryAccountId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user link blocks where the primary account does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_link_blocks where blockedLinkAccountId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user link blocks where the blocked account does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_list where public = 0 and user_id NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_list where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_not_interested where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_not_interested where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_reading_history_work where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_reading_history_work where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_roles where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_roles where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM search where user_id NOT IN (select id from user) and user_id != 0").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " search where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_staff_settings where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_staff_settings where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_tags where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_tags where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("DELETE FROM user_work_review where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_work_review where the user does not exist");
			}

			numUpdates = dbConn.prepareStatement("UPDATE browse_category SET userID = null where userId NOT IN (select id from user)").executeUpdate();
			if (numUpdates > 0){
				processLog.incUpdated();
				processLog.addNote("Deleted " + numUpdates + " user_work_review where the user does not exist");
			}
		}catch (Exception e){
			processLog.incErrors();
			processLog.addNote("Unable to cleanup user data for deleted users. " + e.toString());
			logger.error("Error cleaning up user data for deleted users", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void removeOldMaterialsRequests(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		try{
			//Get a list of a libraries
			PreparedStatement librariesListStmt = dbConn.prepareStatement("SELECT libraryId, materialsRequestDaysToPreserve from library where materialsRequestDaysToPreserve > 0");
			PreparedStatement libraryLocationsStmt = dbConn.prepareStatement("SELECT locationId from location where libraryId = ?");
			PreparedStatement requestToDeleteStmt = dbConn.prepareStatement("DELETE from materials_request where id = ?");

			ResultSet librariesListRS = librariesListStmt.executeQuery();

			long numDeletions = 0;
			//Loop through libraries
			while (librariesListRS.next()){
				//Get the number of days to preserve from the variables table
				long libraryId = librariesListRS.getLong("libraryId");
				long daysToPreserve = librariesListRS.getLong("materialsRequestDaysToPreserve");

				if (daysToPreserve < 366){
					daysToPreserve = 366L;
				}

				//Get a list of locations for the library
				libraryLocationsStmt.setLong(1, libraryId);

				ResultSet libraryLocationsRS = libraryLocationsStmt.executeQuery();
				StringBuilder libraryLocations = new StringBuilder();
				while (libraryLocationsRS.next()){
					if (libraryLocations.length() > 0){
						libraryLocations.append(", ");
					}
					libraryLocations.append(libraryLocationsRS.getString("locationId"));
				}

				if (libraryLocations.length() > 0) {
					//Delete records for that library
					PreparedStatement requestsToDeleteStmt = dbConn.prepareStatement("SELECT materials_request.id from materials_request INNER JOIN materials_request_status on materials_request.status = materials_request_status.id INNER JOIN user on createdBy = user.id where isOpen = 0 and user.homeLocationId IN (" + libraryLocations + ") AND dateCreated < ?");

					long now = new Date().getTime() / 1000;
					long earliestDateToPreserve = now - (daysToPreserve * 24 * 60 * 60);
					requestsToDeleteStmt.setLong(1, earliestDateToPreserve);

					ResultSet requestsToDeleteRS = requestsToDeleteStmt.executeQuery();
					while (requestsToDeleteRS.next()) {
						requestToDeleteStmt.setLong(1, requestsToDeleteRS.getLong(1));
						int numUpdates = requestToDeleteStmt.executeUpdate();
						processLog.addUpdates(numUpdates);
						numDeletions += numUpdates;
					}
					requestsToDeleteStmt.close();
				}
			}
			librariesListRS.close();
			librariesListStmt.close();
			processLog.addNote("Removed " + numDeletions + " old materials requests.");
		}catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to remove old materials requests. " + e.toString());
			logger.error("Error deleting long searches", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void cleanupIndexingReports(Ini configIni, Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove indexing reports
		try{
			//Get the data directory where reports are stored
			File dataDir = new File(configIni.get("Reindex", "marcPath"));
			dataDir = dataDir.getParentFile();
			//Get a list of dates that should be kept
			SimpleDateFormat dateFormatter = new SimpleDateFormat("yyyy-MM-dd");
			GregorianCalendar today = new GregorianCalendar();
			ArrayList<String> validDatesToKeep = new ArrayList<>();
			//Keep the last 7 days
			for (int i = 0; i < 7; i++) {
				validDatesToKeep.add(dateFormatter.format(today.getTime()));
				today.add(Calendar.DATE, -1);
			}
			//Keep the last 12 months
			today.setTime(new Date());
			today.set(Calendar.DAY_OF_MONTH, 1);
			for (int i = 0; i < 12; i++) {
				validDatesToKeep.add(dateFormatter.format(today.getTime()));
				today.add(Calendar.MONTH, -1);
			}

			//List all csv files in the directory
			File[] filesToCheck = dataDir.listFiles((dir, name) -> name.matches(".*\\d{4}-\\d{2}-\\d{2}\\.csv"));

			//Check to see if we should keep or delete the file
			Pattern getDatePattern = Pattern.compile("(\\d{4}-\\d{2}-\\d{2})", Pattern.CANON_EQ);
			if (filesToCheck != null) {
				for (File curFile : filesToCheck) {
					//Get the date from the file
					Matcher fileMatcher = getDatePattern.matcher(curFile.getName());
					if (fileMatcher.find()) {
						String date = fileMatcher.group();
						if (!validDatesToKeep.contains(date)) {
							if (!curFile.delete()){
								logger.warn("Could not delete file " + curFile.getAbsolutePath());
							}
						}
					}
				}
			}

		} catch (Exception e){
			processLog.incErrors();
			processLog.addNote("Error removing old indexing reports. " + e.toString());
			logger.error("Error removing old indexing reports", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void removeLongSearches(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove long searches
		try {
			PreparedStatement removeSearchStmt = dbConn.prepareStatement("DELETE from search_stats_new where length(phrase) > 256");

			int rowsRemoved = removeSearchStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " long searches");
			processLog.incUpdated();

			processLog.saveToDatabase(dbConn, logger);
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete long searches. " + e.toString());
			logger.error("Error deleting long searches", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void removeSpammySearches(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove spammy searches
		try {
			PreparedStatement removeSearchStmt = dbConn.prepareStatement("DELETE from search_stats_new where phrase like '%http:%' or phrase like '%https:%' or phrase like '%mailto:%'");

			int rowsRemoved = removeSearchStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " spammy searches");
			processLog.incUpdated();

			processLog.saveToDatabase(dbConn, logger);

			PreparedStatement removeSearchStmt2 = dbConn.prepareStatement("DELETE from analytics_search where lookfor like '%http:%' or lookfor like '%https:%' or lookfor like '%mailto:%' or length(lookfor) > 256");

			int rowsRemoved2 = removeSearchStmt2.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved2 + " spammy searches");
			processLog.incUpdated();

			processLog.saveToDatabase(dbConn, logger);
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete spammy searches. " + e.toString());
			logger.error("Error deleting spammy searches", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void removeOldSearches(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove old searches
		try {
			int rowsRemoved = 0;
			ResultSet numSearchesRS = dbConn.prepareStatement("SELECT count(id) from search where created < (CURDATE() - INTERVAL 2 DAY) and saved = 0").executeQuery();
			numSearchesRS.next();
			long numSearches = numSearchesRS.getLong(1);
			long batchSize = 100000;
			long numBatches = (numSearches / batchSize) + 1;
			processLog.addNote("Found " + numSearches + " expired searches that need to be removed.  Will process in " + numBatches + " batches");
			processLog.saveToDatabase(dbConn, logger);
			for (int i = 0; i < numBatches; i++){
				PreparedStatement searchesToRemove = dbConn.prepareStatement("SELECT id from search where created < (CURDATE() - INTERVAL 2 DAY) and saved = 0 LIMIT 0, " + batchSize, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement removeSearchStmt = dbConn.prepareStatement("DELETE from search where id = ?");

				ResultSet searchesToRemoveRs = searchesToRemove.executeQuery();
				while (searchesToRemoveRs.next()){
					long curId = searchesToRemoveRs.getLong("id");
					removeSearchStmt.setLong(1, curId);
					rowsRemoved += removeSearchStmt.executeUpdate();
				}
				processLog.incUpdated();
				processLog.saveToDatabase(dbConn, logger);
			}
			processLog.addNote("Removed " + rowsRemoved + " expired searches");
			processLog.saveToDatabase(dbConn, logger);
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete expired searches. " + e.toString());
			logger.error("Error deleting expired searches", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void removeExpiredSessions(Ini configIni, Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove expired sessions
		try{
			//Make sure to normalize the time based to be milliseconds, not microseconds
			long now = new Date().getTime() / 1000;
			long defaultTimeout = Long.parseLong(ConfigUtil.cleanIniValue(configIni.get("Session", "lifetime")));
			long earliestDefaultSessionToKeep = now - defaultTimeout;
			long numStandardSessionsDeleted = dbConn.prepareStatement("DELETE FROM session where last_used < " + earliestDefaultSessionToKeep + " and remember_me = 0").executeUpdate();
			processLog.addNote("Deleted " + numStandardSessionsDeleted + " expired Standard Sessions");
			processLog.saveToDatabase(dbConn, logger);
			long rememberMeTimeout = Long.parseLong(ConfigUtil.cleanIniValue(configIni.get("Session", "rememberMeLifetime")));
			long earliestRememberMeSessionToKeep = now - rememberMeTimeout;
			long numRememberMeSessionsDeleted = dbConn.prepareStatement("DELETE FROM session where last_used < " + earliestRememberMeSessionToKeep + " and remember_me = 1").executeUpdate();
			processLog.addNote("Deleted " + numRememberMeSessionsDeleted + " expired Remember Me Sessions");
			processLog.saveToDatabase(dbConn, logger);
		}catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete expired sessions. " + e.toString());
			logger.error("Error deleting expired sessions", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

	private void cleanupReadingHistory(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove reading history entries that are duplicate based on being renewed
		//Get a list of duplicate titles
		try {
			//Add a filter so that we are looking at 1 week resolution rather than exact.
			PreparedStatement duplicateRecordsToPreserveStmt = dbConn.prepareStatement("SELECT COUNT(id) as numRecords, userId, groupedWorkPermanentId, source, sourceId, FLOOR(checkOutDate/604800) as checkoutWeek , MIN(id) as idToPreserve FROM user_reading_history_work where deleted = 0 GROUP BY userId, groupedWorkPermanentId, FLOOR(checkOutDate/604800) having numRecords > 1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement deleteDuplicateRecordStmt = dbConn.prepareStatement("UPDATE user_reading_history_work SET deleted = 1 WHERE userId = ? AND groupedWorkPermanentId = ? AND FLOOR(checkOutDate/604800) = ? AND id != ?");
			ResultSet duplicateRecordsRS = duplicateRecordsToPreserveStmt.executeQuery();
			int numDuplicateRecords = 0;
			while (duplicateRecordsRS.next()){
				deleteDuplicateRecordStmt.setLong(1, duplicateRecordsRS.getLong("userId"));
				deleteDuplicateRecordStmt.setString(2, duplicateRecordsRS.getString("groupedWorkPermanentId"));
				deleteDuplicateRecordStmt.setLong(3, duplicateRecordsRS.getLong("checkoutWeek"));
				deleteDuplicateRecordStmt.setLong(4, duplicateRecordsRS.getLong("idToPreserve"));
				deleteDuplicateRecordStmt.executeUpdate();

				//int numDeletions = deleteDuplicateRecordStmt.executeUpdate();
				/*if (numDeletions == 0){
					//This happens if the items have already been marked as deleted
					logger.debug("Warning did not delete any records for user " + duplicateRecordsRS.getLong("userId"));
				}*/
				numDuplicateRecords++;
			}
			processLog.addNote("Removed " + numDuplicateRecords + " records that were duplicates (check 1)");

			//Now look for additional duplicates where the check in date is within a week
			duplicateRecordsToPreserveStmt = dbConn.prepareStatement("SELECT COUNT(id) as numRecords, userId, groupedWorkPermanentId, source, sourceId, FLOOR(checkInDate/604800) checkInWeek, MIN(id) as idToPreserve FROM user_reading_history_work where deleted = 0 GROUP BY userId, groupedWorkPermanentId, FLOOR(checkInDate/604800) having numRecords > 1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			deleteDuplicateRecordStmt = dbConn.prepareStatement("UPDATE user_reading_history_work SET deleted = 1 WHERE userId = ? AND groupedWorkPermanentId = ? AND FLOOR(checkInDate/604800) = ? AND id != ?");
			duplicateRecordsRS = duplicateRecordsToPreserveStmt.executeQuery();
			numDuplicateRecords = 0;
			while (duplicateRecordsRS.next()){
				deleteDuplicateRecordStmt.setLong(1, duplicateRecordsRS.getLong("userId"));
				deleteDuplicateRecordStmt.setString(2, duplicateRecordsRS.getString("groupedWorkPermanentId"));
				deleteDuplicateRecordStmt.setLong(3, duplicateRecordsRS.getLong("checkInWeek"));
				deleteDuplicateRecordStmt.setLong(4, duplicateRecordsRS.getLong("idToPreserve"));
				deleteDuplicateRecordStmt.executeUpdate();

				//int numDeletions = deleteDuplicateRecordStmt.executeUpdate();
				/*if (numDeletions == 0){
					//This happens if the items have already been marked as deleted
					logger.debug("Warning did not delete any records for user " + duplicateRecordsRS.getLong("userId"));
				}*/
				numDuplicateRecords++;
			}
			processLog.addNote("Removed " + numDuplicateRecords + " records that were duplicates (check 2)");
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete duplicate reading history entries. " + e.toString());
			logger.error("Error deleting duplicate reading history entries", e);
			processLog.saveToDatabase(dbConn, logger);
		}

		//Remove invalid reading history entries
		try{
			PreparedStatement removeInvalidReadingHistoryEntriesStmt = dbConn.prepareStatement("DELETE FROM user_reading_history_work WHERE groupedWorkPermanentId = 'L'");
			int numUpdates = removeInvalidReadingHistoryEntriesStmt.executeUpdate();
			processLog.addNote("Removed " + numUpdates + " invalid reading history entries");
			processLog.incUpdated();

		} catch (Exception e){
			processLog.incErrors();
			processLog.addNote("Error removing invalid reading history entriee. " + e.toString());
			logger.error("Error removing invalid reading history entriee", e);
			processLog.saveToDatabase(dbConn, logger);
		}
	}

}
