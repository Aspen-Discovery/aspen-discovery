package com.turning_leaf_technologies.cron;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

@SuppressWarnings("unused")
public class DatabaseCleanup implements IProcessHandler {

	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Database Cleanup", dbConn, logger);
		processLog.saveResults();

		removeOldSearches(dbConn, logger, processLog);
		removeSpammySearches(dbConn, logger, processLog);
		removeLongSearches(dbConn, logger, processLog);
		removeOldMaterialsRequests(dbConn, logger, processLog);
		removeOldPaymentHistory(dbConn, logger, processLog);
		removeUserDataForDeletedUsers(dbConn, logger, processLog);
		removeOldCachedObjects(dbConn, logger, processLog);
		removeOldIndexingData(dbConn, logger, processLog);
		removeOldExternalRequests(dbConn, logger, processLog);
		optimizeSearchTable(dbConn, logger, processLog);
		optimizeSessionsTable(dbConn, logger, processLog);

		cleanupReadingHistory(dbConn, logger, processLog);
		removeOldIndexingDiagnostics(dbConn, logger, processLog);

		removeOldObjectHistory(dbConn, logger, processLog);

		processLog.setFinished();
		processLog.saveResults();
	}

	private void removeOldIndexingDiagnostics(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		try {
			PreparedStatement removeOldIndexingDiagnosticsStmt = dbConn.prepareStatement("DELETE from grouped_work_debug_info where processed = 1 and debugTime < ?");
			long now = new Date().getTime() / 1000;
			//Remove anything more than 24 hours old
			long removalTime = now - 24 * 60 * 60;
			removeOldIndexingDiagnosticsStmt.setLong(1, removalTime);

			int rowsRemoved = removeOldIndexingDiagnosticsStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " indexing diagnostics");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete indexing diagnostics. ", e);
		}
	}

	private void optimizeSearchTable(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Optimize search table
		try {
			PreparedStatement optimizeStmt = dbConn.prepareStatement("OPTIMIZE TABLE search");

			optimizeStmt.execute();

			processLog.addNote("Optimized search table.");
			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to optimize search table. ", e);
		}
	}

	private void optimizeSessionsTable(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Optimize search table
		try {
			PreparedStatement optimizeStmt = dbConn.prepareStatement("OPTIMIZE TABLE session");

			optimizeStmt.execute();

			processLog.addNote("Optimized sessions table.");
			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to optimize search table. ", e);
		}
	}

	private void cleanupReadingHistory(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove long searches
		try {
			long now = new Date().getTime() / 1000;

			//Look for anything where the source is part of the sourceId
			PreparedStatement updateSourceStmt = dbConn.prepareStatement("UPDATE user_reading_history_work set sourceId = ? where id = ?");
			PreparedStatement sourceInIdStmt = dbConn.prepareStatement("SELECT * from user_reading_history_work WHERE sourceId like '%:%'");
			ResultSet sourceInIdRS = sourceInIdStmt.executeQuery();
			int numUpdates = 0;
			while (sourceInIdRS.next()){
				String sourceId = sourceInIdRS.getString("sourceId");
				long readingHistoryId = sourceInIdRS.getLong("id");
				String newId = sourceId.substring(sourceId.indexOf(":") + 1);
				updateSourceStmt.setString(1, newId);
				updateSourceStmt.setLong(2, readingHistoryId);
				updateSourceStmt.executeUpdate();
				numUpdates++;
			}
			if (numUpdates > 0){
				processLog.addNote("Updated " + numUpdates + " records where the sourceId had the source in it");
			}

			//Remove records with a sourceId of ?
			PreparedStatement deleteQuestionIdsStmt = dbConn.prepareStatement("DELETE from user_reading_history_work WHERE sourceId = '?'");
			int numDeletions = deleteQuestionIdsStmt.executeUpdate();
			if (numDeletions > 0){
				processLog.addNote("Deleted " + numDeletions + " records where the sourceId was a ?");
			}


			processLog.addNote("Finished cleaning up reading history");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to cleanup reading history. ", e);
		}
	}

	private void removeOldExternalRequests(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove long searches
		try {
			long now = new Date().getTime() / 1000;
			//Remove anything more than 24 hours old
			long removalTime = now - 24 * 60 * 60;
			PreparedStatement removeOldExternalRequestsStmt = dbConn.prepareStatement("DELETE from external_request_log where requestTime <= ?");
			removeOldExternalRequestsStmt.setLong(1, removalTime);

			int rowsRemoved = removeOldExternalRequestsStmt.executeUpdate();

			PreparedStatement optimizeStmt = dbConn.prepareStatement("OPTIMIZE TABLE external_request_log");
			optimizeStmt.execute();

			processLog.addNote("Removed " + rowsRemoved + " external request log entries");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete old external requests. ", e);
		}
	}

	private void removeOldIndexingData(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove long searches
		try {
			PreparedStatement removeRecordsToReloadStmt = dbConn.prepareStatement("DELETE from record_identifiers_to_reload where processed = 1");

			int rowsRemoved = removeRecordsToReloadStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " records to reload that have already been processed");
			processLog.incUpdated();

			PreparedStatement removeWorksScheduledForIndexingStmt = dbConn.prepareStatement("DELETE from grouped_work_scheduled_index where processed = 1");

			rowsRemoved = removeWorksScheduledForIndexingStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " works that were scheduled for reindexing that have already been processed");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete old cached objects. ", e);
		}
	}

	private void removeOldCachedObjects(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove long searches
		try {
			long now = new Date().getTime() / 1000;
			PreparedStatement removeOldCachedObjectsStmt = dbConn.prepareStatement("DELETE from cached_values where expirationTime <= ?");
			removeOldCachedObjectsStmt.setLong(1, now);

			int rowsRemoved = removeOldCachedObjectsStmt.executeUpdate();

			PreparedStatement optimizeStmt = dbConn.prepareStatement("OPTIMIZE TABLE cached_values");
			optimizeStmt.execute();

			processLog.addNote("Removed " + rowsRemoved + " long searches");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete old cached objects. ", e);
		}
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
			processLog.saveResults();
		}catch (Exception e){
			processLog.incErrors("Unable to cleanup user data for deleted users. ", e);
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
			processLog.incErrors("Unable to remove old materials requests. ", e);
		}
	}

	private void removeOldPaymentHistory(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		try{
			//Get a list of a libraries
			PreparedStatement librariesListStmt = dbConn.prepareStatement("SELECT libraryId, deletePaymentHistoryOlderThan from library where deletePaymentHistoryOlderThan > 0");
			PreparedStatement libraryLocationsStmt = dbConn.prepareStatement("SELECT locationId from location where libraryId = ?");
			PreparedStatement deletePaymentStmt = dbConn.prepareStatement("DELETE from user_payments where id = ?");
			PreparedStatement deletePaymentLinesStmt = dbConn.prepareStatement("DELETE from user_payment_lines where paymentId = ?");

			ResultSet librariesListRS = librariesListStmt.executeQuery();

			long numDeletions = 0;
			//Loop through libraries
			while (librariesListRS.next()){
				//Get the number of days to preserve from the variables table
				long libraryId = librariesListRS.getLong("libraryId");
				long daysToPreserve = librariesListRS.getLong("deletePaymentHistoryOlderThan");

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
					PreparedStatement paymentsToDeleteStmt = dbConn.prepareStatement("SELECT user_payments.id from user_payments INNER JOIN user on userId = user.id where user.homeLocationId IN (" + libraryLocations + ") AND transactionDate < ?");

					long now = new Date().getTime() / 1000;
					long earliestDateToPreserve = now - (daysToPreserve * 24 * 60 * 60);
					paymentsToDeleteStmt.setLong(1, earliestDateToPreserve);

					ResultSet paymentsToDeleteRS = paymentsToDeleteStmt.executeQuery();
					while (paymentsToDeleteRS.next()) {
						deletePaymentLinesStmt.setLong(1, paymentsToDeleteRS.getLong(1));
						int numLinesDeleted = deletePaymentLinesStmt.executeUpdate();
						deletePaymentStmt.setLong(1, paymentsToDeleteRS.getLong(1));
						int numUpdates = deletePaymentStmt.executeUpdate();
						processLog.addUpdates(numUpdates);
						numDeletions += numUpdates;
					}
					paymentsToDeleteRS.close();
					paymentsToDeleteStmt.close();
				}
			}
			librariesListRS.close();
			librariesListStmt.close();
			processLog.addNote("Removed " + numDeletions + " old payments.");
		}catch (SQLException e) {
			processLog.incErrors("Unable to remove old materials requests. ", e);
		}
	}

	private void removeLongSearches(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove long searches
		try {
			PreparedStatement removeSearchStmt = dbConn.prepareStatement("DELETE from search_stats_new where length(phrase) > 256");

			int rowsRemoved = removeSearchStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " long searches");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete long searches. ", e);
		}
	}

	private void removeSpammySearches(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove spammy searches
		try {
			PreparedStatement removeSearchStmt = dbConn.prepareStatement("DELETE from search_stats_new where phrase like '%http:%' or phrase like '%https:%' or phrase like '%mailto:%'");

			int rowsRemoved = removeSearchStmt.executeUpdate();

			processLog.addNote("Removed " + rowsRemoved + " spammy searches");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete spammy searches. ", e);
		}
	}

	private void removeOldObjectHistory(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		try {
			long now = new Date().getTime() / 1000;
			//Remove anything more than 90 days hours old
			long removalTime = now - 90 * 24 * 60 * 60;
			PreparedStatement removeOldObjectHistoryStmt = dbConn.prepareStatement("DELETE from object_history where changeDate <= ?");
			removeOldObjectHistoryStmt.setLong(1, removalTime);

			int rowsRemoved = removeOldObjectHistoryStmt.executeUpdate();

			PreparedStatement optimizeStmt = dbConn.prepareStatement("OPTIMIZE TABLE object_history");
			optimizeStmt.execute();

			processLog.addNote("Removed " + rowsRemoved + " rows from object history");
			processLog.incUpdated();

			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete old object history. ", e);
		}
	}

	private void removeOldSearches(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		//Remove old searches
		try {
			int rowsRemoved = 0;
			ResultSet numSearchesRS = dbConn.prepareStatement("SELECT count(id) from search where saved = 0").executeQuery();
			numSearchesRS.next();
			long numSearches = numSearchesRS.getLong(1);
			long batchSize = 100000;
			long numBatches = (numSearches / batchSize) + 1;
			processLog.addNote("Found " + numSearches + " expired searches that need to be removed.  Will process in " + numBatches + " batches");
			processLog.saveResults();
			for (int i = 0; i < numBatches; i++){
				PreparedStatement searchesToRemove = dbConn.prepareStatement("SELECT id from search where saved = 0 LIMIT 0, " + batchSize, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement removeSearchStmt = dbConn.prepareStatement("DELETE from search where id = ?");

				ResultSet searchesToRemoveRs = searchesToRemove.executeQuery();
				while (searchesToRemoveRs.next()){
					long curId = searchesToRemoveRs.getLong("id");
					removeSearchStmt.setLong(1, curId);
					rowsRemoved += removeSearchStmt.executeUpdate();
				}
				processLog.incUpdated();
				processLog.saveResults();
			}
			processLog.addNote("Removed " + rowsRemoved + " expired searches");
			processLog.saveResults();
		} catch (SQLException e) {
			processLog.incErrors("Unable to delete expired searches. ", e);
		}
	}

	private void removeExternalSearchTracking(Connection dbConn, Logger logger, CronProcessLogEntry processLog) {
		try {
			//Get userIDs that have cookie consent for external search sources set to 0
			PreparedStatement getNoExternalTrackingUserIdsStmt = dbConn.prepareStatement("SELECT id FROM user WHERE userCookiePreferenceExternalSearchServices = 0");
			ResultSet userIdsRS = getNoExternalTrackingUserIdsStmt.executeQuery();

			PreparedStatement removeAxis360UsageStmt = dbConn.prepareStatement("DELETE FROM user_axis360_usage WHERE id = ?");
			PreparedStatement removeCloudLibraryUsageStmt = dbConn.prepareStatement("DELETE FROM user_cloud_library_usage WHERE id = ?");
			PreparedStatement removeEbscoHostUsageStmt = dbConn.prepareStatement("DELETE FROM user_ebscohost_usage WHERE id = ?");
			PreparedStatement removeEbscoEdsUsageStmt = dbConn.prepareStatement("DELETE FROM user_ebsco_eds_usage WHERE id = ?");
			PreparedStatement removeHooplaUsageStmt = dbConn.prepareStatement("DELETE FROM user_hoopla_usage WHERE id = ?");
			PreparedStatement removeOverdriveUsageStmt = dbConn.prepareStatement("DELETE FROM user_overdrive_usage WHERE id = ?");
			PreparedStatement removePalaceProjectUsageStmt = dbConn.prepareStatement("DELETE FROM user_palace_project_usage WHERE id = ?");
			PreparedStatement removeSideloadUsageStmt = dbConn.prepareStatement("DELETE FROM user_sideload_usage WHERE id = ?");
			PreparedStatement removeSummonUsageStmt = dbConn.prepareStatement("DELETE FROM user_summon_usage WHERE id = ?");

			int totalRowsRemovedAXIS360 = 0;
			int totalRowsRemovedCloudLibrary = 0;
			int totalRowsRemovedEbscoHost = 0;
			int totalRowsRemovedEbscoEds = 0;
			int totalRowsRemovedHoopla = 0;
			int totalRowsRemovedOverdrive = 0;
			int totalRowsRemovedPalaceProject = 0;
			int totalRowsRemovedSideload = 0;
			int totalRowsRemovedSummon = 0;

			while (userIdsRS.next()) {
				int userId = userIdsRS.getInt("id");

				removeAxis360UsageStmt.setInt(1, userId);
				totalRowsRemovedAXIS360 += removeAxis360UsageStmt.executeUpdate();

				removeCloudLibraryUsageStmt.setInt(1, userId);
				totalRowsRemovedCloudLibrary += removeCloudLibraryUsageStmt.executeUpdate();

				removeEbscoHostUsageStmt.setInt(1, userId);
				totalRowsRemovedEbscoHost += removeEbscoHostUsageStmt.executeUpdate();

				removeEbscoEdsUsageStmt.setInt(1, userId);
				totalRowsRemovedEbscoEds += removeEbscoEdsUsageStmt.executeUpdate();

				removeHooplaUsageStmt.setInt(1, userId);
				totalRowsRemovedHoopla += removeHooplaUsageStmt.executeUpdate();

				removeOverdriveUsageStmt.setInt(1, userId);
				totalRowsRemovedOverdrive += removeOverdriveUsageStmt.executeUpdate();

				removePalaceProjectUsageStmt.setInt(1, userId);
				totalRowsRemovedPalaceProject += removePalaceProjectUsageStmt.executeUpdate();

				removeSideloadUsageStmt.setInt(1, userId);
				totalRowsRemovedSideload += removeSideloadUsageStmt.executeUpdate();

				removeSummonUsageStmt.setInt(1, userId);
				totalRowsRemovedSummon += removeSummonUsageStmt.executeUpdate();
			}


			//Log results
			processLog.addNote("Removed " + totalRowsRemovedAXIS360 + " AXIS 360 usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedCloudLibrary + " Cloud Library usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedEbscoHost + " Ebsco Host usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedEbscoEds + " Ebsco EDS usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedHoopla + " Hoopla usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedOverdrive + " Overdrive usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedPalaceProject + " Palace Project usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedSideload + " Sideload usage records for users with externalSearchSources set to 0");
			processLog.addNote("Removed " + totalRowsRemovedSummon + " Summon usage records for users with externalSearchSources set to 0");
			processLog.incUpdated();
			processLog.saveResults();

			//Close statements and results sets
			getNoExternalTrackingUserIdsStmt.close();
			userIdsRS.close();
			removeAxis360UsageStmt.close();
			removeCloudLibraryUsageStmt.close();
			removeEbscoHostUsageStmt.close();
			removeEbscoEdsUsageStmt.close();
			removeHooplaUsageStmt.close();
			removeOverdriveUsageStmt.close();
			removePalaceProjectUsageStmt.close();
			removeSideloadUsageStmt.close();
			removeSummonUsageStmt.close();
		} catch (SQLException e) {
			processLog.incErrors("Unable to remove external search tracking. ", e);
		}
	}
}
