package com.turning_leaf_technologies.cron;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

@SuppressWarnings("unused")
public class MergeHorizonUsers implements IProcessHandler {
	private PreparedStatement mergeUserLinksStmt;
	private PreparedStatement mergeUserLinks2Stmt;
	private PreparedStatement mergeUserLinks3Stmt;
	private PreparedStatement mergeUserLinks4Stmt;
	private PreparedStatement mergeUserListStmt;
	private PreparedStatement mergeNotInterestedStmt;
	private PreparedStatement mergeUserReadingHistoryStmt;
	private PreparedStatement mergeUserRolesStmt;
	private PreparedStatement mergeUserTagsStmt;
	private PreparedStatement mergeSearchesStmt;
	private PreparedStatement mergeBrowseCategoriesStmt;
	private PreparedStatement mergeMaterialsRequestsStmt;
	private PreparedStatement mergeUserReviewsStmt;

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Merge Horizon Users");
		processLog.saveToDatabase(dbConn, logger);

		//Get a list of users that are in the database twice
		try {
			PreparedStatement duplicateUsersStmt = dbConn.prepareStatement("SELECT cat_username, COUNT(id) as numDuplicates FROM user GROUP BY cat_username HAVING numDuplicates > 1");
			PreparedStatement getDuplicateUserInfoStmt = dbConn.prepareStatement("SELECT id, username, cat_username from user where cat_username = ?");
			mergeUserLinksStmt = dbConn.prepareStatement("UPDATE user_link SET primaryAccountId = ? WHERE primaryAccountId = ?");
			mergeUserLinks2Stmt = dbConn.prepareStatement("UPDATE user_link SET linkedAccountId = ? WHERE linkedAccountId = ?");
			mergeUserLinks3Stmt = dbConn.prepareStatement("UPDATE user_link_blocks SET primaryAccountId = ? WHERE primaryAccountId = ?");
			mergeUserLinks4Stmt = dbConn.prepareStatement("UPDATE user_link_blocks SET blockedLinkAccountId = ? WHERE blockedLinkAccountId = ?");
			mergeUserListStmt = dbConn.prepareStatement("UPDATE user_list SET user_id = ? WHERE user_id = ?");
			mergeNotInterestedStmt = dbConn.prepareStatement("UPDATE user_not_interested SET userId = ? WHERE userId = ?");
			mergeUserReadingHistoryStmt = dbConn.prepareStatement("UPDATE user_reading_history_work SET userId = ? WHERE userId = ?");
			mergeUserRolesStmt = dbConn.prepareStatement("UPDATE user_roles SET userId = ? WHERE userId = ?");
			mergeSearchesStmt = dbConn.prepareStatement("UPDATE search SET user_id = ? WHERE user_id = ?");
			mergeBrowseCategoriesStmt = dbConn.prepareStatement("UPDATE browse_category SET userId = ? WHERE userId = ?");
			mergeMaterialsRequestsStmt = dbConn.prepareStatement("UPDATE materials_request SET createdBy = ? WHERE createdBy = ?");
			mergeUserReviewsStmt = dbConn.prepareStatement("UPDATE user_work_review SET userId = ? WHERE userId = ?");
			PreparedStatement removeDuplicateUserStmt = dbConn.prepareStatement("DELETE FROM user where id = ?");
			ResultSet duplicateUsersRS = duplicateUsersStmt.executeQuery();
			int numDuplicateUsers = 0;
			while (duplicateUsersRS.next()){
				numDuplicateUsers++;
				String barcode = duplicateUsersRS.getString("cat_username");
				try {
					getDuplicateUserInfoStmt.setString(1, barcode);
					ResultSet duplicateUserInfo = getDuplicateUserInfoStmt.executeQuery();

					long preferredUsername = -1L;
					long duplicateUsername = -1L;
					Long preferredUserId = -1L;
					Long duplicateUserId = -1L;

					while (duplicateUserInfo.next()) {
						String userId = duplicateUserInfo.getString("username");
						if (userId.equals(barcode)) {
							duplicateUsername = duplicateUserInfo.getLong("username");
							duplicateUserId = duplicateUserInfo.getLong("id");
						}else{
							preferredUsername = duplicateUserInfo.getLong("username");
							preferredUserId = duplicateUserInfo.getLong("id");
						}
					}
					if (preferredUsername == -1L || duplicateUsername == -1L){
						logger.error("Could not determine preferred and duplicate id for barcode " + barcode);
					}else{
						//Merge enrichment for the users
						int numChanges = 0;
						numChanges += mergeUserLinks(preferredUserId, duplicateUserId);
						numChanges += mergeUserLists(preferredUserId, duplicateUserId);
						numChanges += mergeUserNotInterested(preferredUserId, duplicateUserId);
						numChanges += mergeUserReadingHistory(preferredUserId, duplicateUserId);
						numChanges += mergeUserRoles(preferredUserId, duplicateUserId);
						numChanges += mergeUserTags(preferredUserId, duplicateUserId);
						numChanges += mergeSearches(preferredUserId, duplicateUserId);
						numChanges += mergeBrowseCategories(preferredUserId, duplicateUserId);
						numChanges += mergeMaterialsRequests(preferredUserId, duplicateUserId);
						numChanges += mergeUserReviews(preferredUserId, duplicateUserId);

						logger.debug("Made " + numChanges + " changes for user barcode ");

						//Remove the duplicate user
						removeDuplicateUserStmt.setLong(1, duplicateUserId);
						int userDeleted = removeDuplicateUserStmt.executeUpdate();
						if (userDeleted > 0){
							processLog.incUpdated();
						}
					}
				}catch (SQLException e) {
					processLog.incErrors();
					processLog.addNote("Error processing barcode " + barcode + ". " + e.toString());
					logger.error("Error processing barcode " + barcode , e);
					processLog.saveToDatabase(dbConn, logger);
				}
			}
			logger.debug("Processed " + numDuplicateUsers + " users with more than one instance in the system.");
		}catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Error loading duplicate users. " + e.toString());
			logger.error("Error loading duplicate users", e);
			processLog.saveToDatabase(dbConn, logger);
		}


		processLog.setFinished();
		processLog.saveToDatabase(dbConn, logger);
	}

	private int mergeUserReviews(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeUserReviewsStmt.setLong(1, preferredUserId);
		mergeUserReviewsStmt.setLong(2, duplicateUserId);
		return mergeUserReviewsStmt.executeUpdate();
	}

	private int mergeMaterialsRequests(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeMaterialsRequestsStmt.setLong(1, preferredUserId);
		mergeMaterialsRequestsStmt.setLong(2, duplicateUserId);
		return mergeMaterialsRequestsStmt.executeUpdate();
	}

	private int mergeBrowseCategories(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeBrowseCategoriesStmt.setLong(1, preferredUserId);
		mergeBrowseCategoriesStmt.setLong(2, duplicateUserId);
		return mergeBrowseCategoriesStmt.executeUpdate();
	}

	private int mergeSearches(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeSearchesStmt.setLong(1, preferredUserId);
		mergeSearchesStmt.setLong(2, duplicateUserId);
		return mergeSearchesStmt.executeUpdate();
	}

	private int mergeUserTags(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeUserTagsStmt.setLong(1, preferredUserId);
		mergeUserTagsStmt.setLong(2, duplicateUserId);
		return mergeUserTagsStmt.executeUpdate();
	}

	private int mergeUserRoles(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeUserRolesStmt.setLong(1, preferredUserId);
		mergeUserRolesStmt.setLong(2, duplicateUserId);
		return mergeUserRolesStmt.executeUpdate();
	}

	private int mergeUserReadingHistory(Long preferredUserId, Long duplicateUserId) throws SQLException{
		mergeUserReadingHistoryStmt.setLong(1, preferredUserId);
		mergeUserReadingHistoryStmt.setLong(2, duplicateUserId);
		return mergeUserReadingHistoryStmt.executeUpdate();
	}

	private int mergeUserNotInterested(Long preferredId, Long duplicateId) throws SQLException{
		mergeNotInterestedStmt.setLong(1, preferredId);
		mergeNotInterestedStmt.setLong(2, duplicateId);
		return mergeNotInterestedStmt.executeUpdate();
	}

	private int mergeUserLists(Long preferredId, Long duplicateId) throws SQLException {
		mergeUserListStmt.setLong(1, preferredId);
		mergeUserListStmt.setLong(2, duplicateId);
		return mergeUserListStmt.executeUpdate();
	}

	private int mergeUserLinks(Long preferredId, Long duplicateId) throws SQLException {
		int numChanges;
		mergeUserLinksStmt.setLong(1, preferredId);
		mergeUserLinksStmt.setLong(2, duplicateId);
		numChanges = mergeUserLinksStmt.executeUpdate();

		mergeUserLinks2Stmt.setLong(1, preferredId);
		mergeUserLinks2Stmt.setLong(2, duplicateId);
		numChanges += mergeUserLinks2Stmt.executeUpdate();

		mergeUserLinks3Stmt.setLong(1, preferredId);
		mergeUserLinks3Stmt.setLong(2, duplicateId);
		numChanges += mergeUserLinks3Stmt.executeUpdate();

		mergeUserLinks4Stmt.setLong(1, preferredId);
		mergeUserLinks4Stmt.setLong(2, duplicateId);
		numChanges += mergeUserLinks4Stmt.executeUpdate();
		return numChanges;
	}
}
