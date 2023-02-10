package com.turning_leaf_technologies.grouping;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.marc4j.marc.*;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.*;
import java.util.Date;

public class RecordGroupingProcessor {
	protected BaseIndexingLogEntry logEntry;
	protected Logger logger;

	private PreparedStatement insertGroupedWorkStmt;
	private PreparedStatement groupedWorkForIdentifierStmt;
	private PreparedStatement updateDateUpdatedForGroupedWorkStmt;
	private PreparedStatement addPrimaryIdentifierForWorkStmt;

	private PreparedStatement getWorkForPrimaryIdentifierStmt;
	private PreparedStatement getAdditionalPrimaryIdentifierForWorkStmt;
	private PreparedStatement deletePrimaryIdentifierStmt;
	private PreparedStatement getPermanentIdByWorkIdStmt;
	private PreparedStatement getGroupedWorkIdByPermanentIdStmt;

	private PreparedStatement updateRatingsStmt;
	private PreparedStatement updateReadingHistoryStmt;
	private PreparedStatement updateNotInterestedStmt;
	private PreparedStatement updateUserListEntriesStmt;
	private PreparedStatement updateNovelistStmt;
	private PreparedStatement updateDisplayInfoStmt;

	private PreparedStatement getAuthoritativeAuthorStmt;
	private PreparedStatement getTitleAuthorityStmt;
	private boolean lookupAuthorAuthoritiesInDB = true;
	private boolean lookupTitleAuthoritiesInDB = true;
	private HashMap<String, String> titleAuthorities = new HashMap<>();

	private PreparedStatement markWorkAsNeedingReindexStmt;

	private PreparedStatement getWorkByAlternateTitleAuthorStmt;

	private PreparedStatement getAxis360DetailsForRecordStmt;
	private PreparedStatement getCloudLibraryDetailsForRecordStmt;
	private PreparedStatement getHooplaRecordStmt;


	HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();

	//A list of grouped works that have been manually merged.
	private final HashSet<String> recordsToNotGroup = new HashSet<>();
	private final Long updateTime = new Date().getTime() / 1000;

	/**
	 * Creates a record grouping processor that saves results to the database.  For use from external extractors
	 *
	 * @param dbConnection - The Connection to the database
	 * @param serverName   - The server we are grouping data for
	 * @param logger       - A logger to store debug and error messages to.
	 */
	public RecordGroupingProcessor(Connection dbConnection, String serverName, BaseIndexingLogEntry logEntry, Logger logger) {
		this.logger = logger;
		this.logEntry = logEntry;

		setupDatabaseStatements(dbConnection);

		loadTranslationMaps(serverName);

		loadAuthorities(dbConnection);
	}

	public void close(){
		translationMaps.clear();
		recordsToNotGroup.clear();
		updatedAndInsertedWorksThisRun.clear();
		formatsWarned.clear();
		try {
			insertGroupedWorkStmt.close();
			updateDateUpdatedForGroupedWorkStmt.close();
			addPrimaryIdentifierForWorkStmt.close();
			groupedWorkForIdentifierStmt.close();

			getWorkForPrimaryIdentifierStmt.close();
			deletePrimaryIdentifierStmt.close();
			getAdditionalPrimaryIdentifierForWorkStmt.close();
			getPermanentIdByWorkIdStmt.close();

			getAuthoritativeAuthorStmt.close();
			getTitleAuthorityStmt.close();

			getGroupedWorkIdByPermanentIdStmt.close();

			updateRatingsStmt.close();
			updateReadingHistoryStmt.close();
			updateNotInterestedStmt.close();
			updateUserListEntriesStmt.close();
			updateNovelistStmt.close();
			updateDisplayInfoStmt.close();

			markWorkAsNeedingReindexStmt.close();

			getWorkByAlternateTitleAuthorStmt.close();

			getAxis360DetailsForRecordStmt.close();
			getCloudLibraryDetailsForRecordStmt.close();
			getHooplaRecordStmt.close();

		} catch (Exception e) {
			logEntry.incErrors("Error closing prepared statements in record grouping processor", e);
		}
	}

	/**
	 * Removes a record from a grouped work and returns if the grouped work no longer has
	 * any records attached to it (in which case it should be removed from the index after calling this)
	 *
	 * @param source - The source of the record being removed
	 * @param id     - The id of the record being removed
	 */
	public RemoveRecordFromWorkResult removeRecordFromGroupedWork(String source, String id) {
		RemoveRecordFromWorkResult result = new RemoveRecordFromWorkResult();
		try {
			//Check to see if the identifier is in the grouped work primary identifiers table
			getWorkForPrimaryIdentifierStmt.setString(1, source);
			getWorkForPrimaryIdentifierStmt.setString(2, id);
			ResultSet getWorkForPrimaryIdentifierRS = getWorkForPrimaryIdentifierStmt.executeQuery();
			if (getWorkForPrimaryIdentifierRS.next()) {
				long groupedWorkId = getWorkForPrimaryIdentifierRS.getLong("grouped_work_id");
				long primaryIdentifierId = getWorkForPrimaryIdentifierRS.getLong("id");
				String permanentId = getWorkForPrimaryIdentifierRS.getString("permanent_id");
				result.groupedWorkId = groupedWorkId;
				result.permanentId = permanentId;
				//Delete the primary identifier
				deletePrimaryIdentifierStmt.setLong(1, primaryIdentifierId);
				deletePrimaryIdentifierStmt.executeUpdate();
				//Check to see if there are other identifiers for this work
				getAdditionalPrimaryIdentifierForWorkStmt.setLong(1, groupedWorkId);
				ResultSet getAdditionalPrimaryIdentifierForWorkRS = getAdditionalPrimaryIdentifierForWorkStmt.executeQuery();
				if (getAdditionalPrimaryIdentifierForWorkRS.next()) {
					//There are additional records for this work, just need to mark that it needs indexing again
					result.reindexWork = true;
				} else {
					result.deleteWork = true;
				}
			}//If not true, already deleted skip this
		} catch (Exception e) {
			logEntry.incErrors("Error processing deleted bibs", e);
		}
		return result;
	}

	public String getPermanentIdForRecord(String source, String id) {
		String permanentId = null;
		try {
			getWorkForPrimaryIdentifierStmt.setString(1, source);
			getWorkForPrimaryIdentifierStmt.setString(2, id);
			ResultSet getWorkForPrimaryIdentifierRS = getWorkForPrimaryIdentifierStmt.executeQuery();
			if (getWorkForPrimaryIdentifierRS.next()) {
				long groupedWorkId = getWorkForPrimaryIdentifierRS.getLong("grouped_work_id");
				getPermanentIdByWorkIdStmt.setLong(1, groupedWorkId);
				ResultSet getPermanentIdByWorkIdRS = getPermanentIdByWorkIdStmt.executeQuery();
				if (getPermanentIdByWorkIdRS.next()) {
					permanentId = getPermanentIdByWorkIdRS.getString("permanent_id");
				}
			}
		} catch (Exception e) {
			logEntry.incErrors("Error getting permanent id for record " + source + " " + id, e);
		}
		return permanentId;
	}

	void setupDatabaseStatements(Connection dbConnection) {
		try {
			insertGroupedWorkStmt = dbConnection.prepareStatement("INSERT INTO grouped_work (full_title, author, grouping_category, permanent_id, date_updated) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE date_updated = VALUES(date_updated), id=LAST_INSERT_ID(id) ", Statement.RETURN_GENERATED_KEYS);
			updateDateUpdatedForGroupedWorkStmt = dbConnection.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = ?");
			addPrimaryIdentifierForWorkStmt = dbConnection.prepareStatement("INSERT INTO grouped_work_primary_identifiers (grouped_work_id, type, identifier) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), grouped_work_id = VALUES(grouped_work_id)", Statement.RETURN_GENERATED_KEYS);
			groupedWorkForIdentifierStmt = dbConnection.prepareStatement("SELECT grouped_work.id, grouped_work.permanent_id FROM grouped_work inner join grouped_work_primary_identifiers on grouped_work_primary_identifiers.grouped_work_id = grouped_work.id where type = ? and identifier = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			getWorkForPrimaryIdentifierStmt = dbConnection.prepareStatement("SELECT grouped_work_primary_identifiers.id, grouped_work_primary_identifiers.grouped_work_id, permanent_id from grouped_work_primary_identifiers inner join grouped_work on grouped_work_id = grouped_work.id where type = ? and identifier = ?");
			deletePrimaryIdentifierStmt = dbConnection.prepareStatement("DELETE from grouped_work_primary_identifiers where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getAdditionalPrimaryIdentifierForWorkStmt = dbConnection.prepareStatement("SELECT * from grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getPermanentIdByWorkIdStmt = dbConnection.prepareStatement("SELECT permanent_id from grouped_work WHERE id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			PreparedStatement getNumAuthorAuthoritiesStmt = dbConnection.prepareStatement("SELECT count(*) as numAuthorities from author_authority", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet numAuthorAuthoritiesRS = getNumAuthorAuthoritiesStmt.executeQuery();
			if (numAuthorAuthoritiesRS.next()) {
				if (numAuthorAuthoritiesRS.getLong("numAuthorities") == 0) {
					lookupAuthorAuthoritiesInDB = false;
				}
			}
			getAuthoritativeAuthorStmt = dbConnection.prepareStatement("SELECT author_authority.normalized from author_authority inner join author_authority_alternative on author_authority.id = authorId WHERE author_authority_alternative.normalized = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getNumTitleAuthoritiesStmt = dbConnection.prepareStatement("SELECT count(*) as numAuthorities from title_authorities", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet numTitleAuthoritiesRS = getNumTitleAuthoritiesStmt.executeQuery();
			if (numTitleAuthoritiesRS.next()){
				if (numTitleAuthoritiesRS.getLong("numAuthorities") <= 100){
					lookupTitleAuthoritiesInDB = false;
					//Just get all the authorities up front
					PreparedStatement getAllTitleAuthoritiesStmt = dbConnection.prepareStatement("SELECT * from title_authorities", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
					ResultSet getAllTitleAuthoritiesRS = getAllTitleAuthoritiesStmt.executeQuery();
					while (getAllTitleAuthoritiesRS.next()){
						titleAuthorities.put(getAllTitleAuthoritiesRS.getString("originalName"), getAllTitleAuthoritiesRS.getString("authoritativeName"));
					}
				}
			}
			getTitleAuthorityStmt = dbConnection.prepareStatement("SELECT * from title_authorities where originalName = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			getGroupedWorkIdByPermanentIdStmt = dbConnection.prepareStatement("SELECT id from grouped_work WHERE permanent_id = ?");

			updateRatingsStmt = dbConnection.prepareStatement("UPDATE user_work_review SET groupedRecordPermanentId = ? where groupedRecordPermanentId = ?");
			updateReadingHistoryStmt = dbConnection.prepareStatement("UPDATE user_reading_history_work SET groupedWorkPermanentId = ? where groupedWorkPermanentId = ?");
			updateNotInterestedStmt = dbConnection.prepareStatement("UPDATE user_not_interested SET groupedRecordPermanentId = ? where groupedRecordPermanentId = ?");
			updateUserListEntriesStmt = dbConnection.prepareStatement("UPDATE user_list_entry SET sourceId = ? where sourceId = ? and source = 'GroupedWork'");
			updateNovelistStmt = dbConnection.prepareStatement("UPDATE novelist_data SET groupedRecordPermanentId = ? where groupedRecordPermanentId = ?");
			updateDisplayInfoStmt = dbConnection.prepareStatement("UPDATE grouped_work_display_info SET permanent_id = ? where permanent_id = ?");

			markWorkAsNeedingReindexStmt = dbConnection.prepareStatement("INSERT into grouped_work_scheduled_index (permanent_id, indexAfter) VALUES (?, ?)");

			getAxis360DetailsForRecordStmt = dbConnection.prepareStatement("SELECT title, subtitle, primaryAuthor, formatType, rawResponse from axis360_title where axis360Id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getCloudLibraryDetailsForRecordStmt =  dbConnection.prepareStatement("SELECT title, subTitle, author, format from cloud_library_title where cloudLibraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getHooplaRecordStmt = dbConnection.prepareStatement("SELECT UNCOMPRESS(rawResponse) as rawResponse from hoopla_export where hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			PreparedStatement recordsToNotGroupStmt = dbConnection.prepareStatement("SELECT * from nongrouped_records");
			ResultSet nonGroupedRecordsRS = recordsToNotGroupStmt.executeQuery();
			while (nonGroupedRecordsRS.next()) {
				String identifier = nonGroupedRecordsRS.getString("source") + ":" + nonGroupedRecordsRS.getString("recordId");
				recordsToNotGroup.add(identifier.toLowerCase());
			}
			nonGroupedRecordsRS.close();
			recordsToNotGroupStmt.close();

			getWorkByAlternateTitleAuthorStmt = dbConnection.prepareStatement("SELECT permanent_id from grouped_work_alternate_titles where alternateTitle = ? and alternateAuthor = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);


		} catch (Exception e) {
			logEntry.incErrors("Error setting up prepared statements", e);
		}
	}


	List<DataField> getDataFields(Record marcRecord, String tag) {
		return marcRecord.getDataFields(tag);
	}

	List<DataField> getDataFields(Record marcRecord, int tag) {
		return marcRecord.getDataFields(tag);
	}

	/**
	 * Add a work to the database
	 *
	 * @param primaryIdentifier The primary identifier we are updating the work for
	 * @param groupedWork       Information about the work itself
	 */
	void addGroupedWorkToDatabase(RecordIdentifier primaryIdentifier, GroupedWork groupedWork, boolean primaryDataChanged, String originalGroupedWorkId) {
		String groupedWorkPermanentId = groupedWork.getPermanentId();

		//Check to see if we need to ungroup the record.
		String primaryIdentifierString = primaryIdentifier.toString();
		if (recordsToNotGroup.contains(primaryIdentifierString.toLowerCase())) {
			groupedWork.makeUnique(primaryIdentifierString);
			groupedWorkPermanentId = groupedWork.getPermanentId();
		}else{
			String alternateGroupedWorkPermanentId = checkForAlternateTitleAuthor(groupedWork, groupedWorkPermanentId);
			if (alternateGroupedWorkPermanentId != null) {
				if (alternateGroupedWorkPermanentId.length() > 36) {
					alternateGroupedWorkPermanentId = alternateGroupedWorkPermanentId.substring(0, 36);
				}
				alternateGroupedWorkPermanentId += "-" + groupedWork.getLanguage();
				groupedWorkPermanentId = alternateGroupedWorkPermanentId;
				groupedWork.overridePermanentId(groupedWorkPermanentId);
 			}
		}

		//Check to see if the record is already on an existing work.  If so, remove from the old work.
		boolean addPrimaryIdentifierToWork = true;

		if (originalGroupedWorkId == null){
			//Try to look up the original id
			try {
				groupedWorkForIdentifierStmt.setString(1, primaryIdentifier.getType());
				groupedWorkForIdentifierStmt.setString(2, primaryIdentifier.getIdentifier());

				ResultSet groupedWorkForIdentifierRS = groupedWorkForIdentifierStmt.executeQuery();
				if (groupedWorkForIdentifierRS.next()) {

					//We have an existing grouped work
					originalGroupedWorkId = groupedWorkForIdentifierRS.getString("permanent_id");
				}
				groupedWorkForIdentifierRS.close();
			} catch (SQLException e) {
				logEntry.incErrors("Error determining existing grouped work for identifier", e);
			}
		}else if (originalGroupedWorkId.equals("false")) {
			//A value of false means we prevalidated that there was not an existing id
			originalGroupedWorkId = null;
		}

		if (originalGroupedWorkId != null && !originalGroupedWorkId.equals(groupedWorkPermanentId)) {
			try {
				//move enrichment from the old id to the new if the new old no longer has any records
				moveGroupedWorkEnrichment(originalGroupedWorkId, groupedWorkPermanentId);

				//For realtime indexing we will want to trigger a reindex of the old record as well
				markWorkAsNeedingReindexStmt.setString(1, originalGroupedWorkId);
				markWorkAsNeedingReindexStmt.setLong(2, new Date().getTime() / 1000);
				markWorkAsNeedingReindexStmt.executeUpdate();

				//Also trigger a reindex of the new record.
				markWorkAsNeedingReindexStmt.setString(1, groupedWorkPermanentId);
				markWorkAsNeedingReindexStmt.setLong(2, new Date().getTime() / 1000);
				markWorkAsNeedingReindexStmt.executeUpdate();
			} catch (SQLException e) {
				logEntry.incErrors("Error marking record for reindexing", e);
			}
		}else if (originalGroupedWorkId != null){
			//We don't need to add the primary identifier since it is already on the correct work
			addPrimaryIdentifierToWork = false;
		}

		//Add the work to the database
		long groupedWorkId = -1;
		try {
			getGroupedWorkIdByPermanentIdStmt.setString(1, groupedWorkPermanentId);
			ResultSet existingIdRS = getGroupedWorkIdByPermanentIdStmt.executeQuery();

			if (existingIdRS.next()) {
				//There is an existing grouped work
				groupedWorkId = existingIdRS.getLong("id");

				//Mark that the work has been updated
				//Only mark it as updated if the data for the primary identifier has changed
				if (primaryDataChanged) {
					markWorkUpdated(groupedWorkId);
				}

			} else {
				//Need to insert a new grouped record
				String title = groupedWork.getTitle();
				if (title.length() > 750){
					title = title.substring(0, 750);
					logEntry.addNote("Title for " + primaryIdentifierString + " was truncated");
				}
				insertGroupedWorkStmt.setString(1, title);
				insertGroupedWorkStmt.setString(2, groupedWork.getAuthor());
				insertGroupedWorkStmt.setString(3, groupedWork.getGroupingCategory());
				insertGroupedWorkStmt.setString(4, groupedWorkPermanentId);
				insertGroupedWorkStmt.setLong(5, updateTime);

				insertGroupedWorkStmt.executeUpdate();
				ResultSet generatedKeysRS = insertGroupedWorkStmt.getGeneratedKeys();
				if (generatedKeysRS.next()) {
					groupedWorkId = generatedKeysRS.getLong(1);
				}
				generatedKeysRS.close();

				updatedAndInsertedWorksThisRun.add(groupedWorkId);
			}

			//Update identifiers
			if (addPrimaryIdentifierToWork) {
				addPrimaryIdentifierForWorkToDB(groupedWorkId, primaryIdentifier);
			}
		} catch (Exception e) {
			logEntry.incErrors("Error adding grouped record " + primaryIdentifierString + " to grouped work " + groupedWorkPermanentId, e);
		}

	}

	private String checkForAlternateTitleAuthor(GroupedWork groupedWork, String groupedWorkPermanentId) {
		try {
			//Check to see if we know the work based on the title and author through the merge process
			getWorkByAlternateTitleAuthorStmt.setString(1, groupedWork.getTitle());
			getWorkByAlternateTitleAuthorStmt.setString(2, groupedWork.getAuthor());
			ResultSet getWorkByAlternateTitleAuthorRS = getWorkByAlternateTitleAuthorStmt.executeQuery();
			if (getWorkByAlternateTitleAuthorRS.next()){
				return getWorkByAlternateTitleAuthorRS.getString("permanent_id");
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error looking for grouped work by alternate title title = " + groupedWork.getTitle() + " author = " + groupedWork.getAuthor(), e);
		}
		return null;
	}

	private void moveGroupedWorkEnrichment(String oldPermanentId, String newPermanentId) {
		try{
			//First make sure the old record does not have items attached to it still
			getGroupedWorkIdByPermanentIdStmt.setString(1, oldPermanentId);
			ResultSet getWorkIdByPermanentIdRS = getGroupedWorkIdByPermanentIdStmt.executeQuery();
			if (getWorkIdByPermanentIdRS.next()){
				long workId = getWorkIdByPermanentIdRS.getLong("id");
				getAdditionalPrimaryIdentifierForWorkStmt.setLong(1, workId);
				ResultSet getAdditionalPrimaryIdentifierForWorkRS = getAdditionalPrimaryIdentifierForWorkStmt.executeQuery();
				int numPrimaryIdentifiers = 0;
				while (getAdditionalPrimaryIdentifierForWorkRS.next()){
					numPrimaryIdentifiers++;
				}
				//At the point this is called, we have not removed the record from the work so count should be 1
				if (numPrimaryIdentifiers <= 1) {
					//If there are no items attached to the old record
					//Move ratings
					int numUpdatedRatings = 0;
					try{
						updateRatingsStmt.setString(1, newPermanentId);
						updateRatingsStmt.setString(2, oldPermanentId);
						numUpdatedRatings = updateRatingsStmt.executeUpdate();
					}catch (SQLException e){
						logEntry.incErrors("Error moving ratings", e);
					}

					//Move reading history
					int numUpdatedReadingHistory = 0;
					try {
						updateReadingHistoryStmt.setString(1, newPermanentId);
						updateReadingHistoryStmt.setString(2, oldPermanentId);
						numUpdatedReadingHistory = updateReadingHistoryStmt.executeUpdate();
					}catch (SQLException e){
						logEntry.incErrors("Error moving reading history from " + oldPermanentId + " to " + newPermanentId, e);
					}

					//Update list entries
					int numUpdatedListEntries = 0;
					try {
						updateUserListEntriesStmt.setString(1, newPermanentId);
						updateUserListEntriesStmt.setString(2, oldPermanentId);
						numUpdatedListEntries = updateUserListEntriesStmt.executeUpdate();
					}catch (SQLException e){
						logEntry.incErrors("Error moving list entries", e);
					}

					//User Not Interested
					int numUpdatedNotInterested = 0;
					try{
						updateNotInterestedStmt.setString(1, newPermanentId);
						updateNotInterestedStmt.setString(2, oldPermanentId);
						numUpdatedNotInterested = updateNotInterestedStmt.executeUpdate();
					}catch (SQLException e){
						logEntry.incErrors("Error moving not interested info", e);
					}

					//Novelist
					int numUpdatedNovelist = 0;
					try{
						updateNovelistStmt.setString(1, newPermanentId);
						updateNovelistStmt.setString(2, oldPermanentId);
						numUpdatedNovelist = updateNovelistStmt.executeUpdate();
					}catch (SQLException e){
						logEntry.incErrors("Error moving novelist info", e);
					}

					//Display info
					int numUpdatedDisplayInfo = 0;
					try{
						updateDisplayInfoStmt.setString(1, newPermanentId);
						updateDisplayInfoStmt.setString(2, oldPermanentId);
						numUpdatedDisplayInfo = updateDisplayInfoStmt.executeUpdate();
					}catch (SQLException e){
						logEntry.incErrors("Error moving display info", e);
					}

					logger.debug("Updated " + numUpdatedRatings + " ratings, " + numUpdatedListEntries + " list entries, " + numUpdatedReadingHistory + " reading history entries, " + numUpdatedNotInterested + " not interested entries, " + numUpdatedNovelist + " novelist entries, " + numUpdatedDisplayInfo + " display info entries");
				}
			}else{
				logEntry.incErrors("Could not find the id of the work when merging enrichment " + oldPermanentId);
			}
		}catch (Exception e){
			logEntry.incErrors("Error moving enrichment", e);
		}
	}

	private final HashSet<Long> updatedAndInsertedWorksThisRun = new HashSet<>();

	private void markWorkUpdated(long groupedWorkId) {
		//Optimize to not continually mark the same works as updated
		if (!updatedAndInsertedWorksThisRun.contains(groupedWorkId)) {
			try {
				updateDateUpdatedForGroupedWorkStmt.setLong(1, updateTime);
				updateDateUpdatedForGroupedWorkStmt.setLong(2, groupedWorkId);
				updateDateUpdatedForGroupedWorkStmt.executeUpdate();
				updatedAndInsertedWorksThisRun.add(groupedWorkId);
			} catch (Exception e) {
				logEntry.incErrors("Error updating date updated for grouped work ", e);
			}
		}
	}

	private void addPrimaryIdentifierForWorkToDB(long groupedWorkId, RecordIdentifier primaryIdentifier) {
		//Optimized to not delete and remove the primary identifier if it hasn't changed.  Just updates the grouped_work_id.
		try {
			//This statement will either add the primary key or update the work id if it already exists
			//Note, we can not lower case this because we depend on the actual identifier later
			addPrimaryIdentifierForWorkStmt.setLong(1, groupedWorkId);
			addPrimaryIdentifierForWorkStmt.setString(2, primaryIdentifier.getType());
			addPrimaryIdentifierForWorkStmt.setString(3, primaryIdentifier.getIdentifier());
			addPrimaryIdentifierForWorkStmt.executeUpdate();
		} catch (SQLException e) {
			logEntry.incErrors("Error adding primary identifier to grouped work " + groupedWorkId + " " + primaryIdentifier.toString(), e);
		}
	}

	/**
	 * Processes the record and returns the permanent id of the grouped work
	 *
	 * @param primaryIdentifier  The primary identifier of the record
	 * @param title              The title of the record
	 * @param subtitle           The subtitle of the record
	 * @param author             The author of the record
	 * @param format             The format of the record
	 * @param primaryDataChanged Whether or not the primary data has been changed
	 * @return The permanent id of the grouped work
	 */
	public String processRecord(RecordIdentifier primaryIdentifier, String title, String subtitle, String author, String format, String language, boolean primaryDataChanged) {
		GroupedWork groupedWork = new GroupedWork(this);

		//Replace & with and for better matching
		groupedWork.setTitle(title, 0, subtitle, "");

		if (author != null) {
			groupedWork.setAuthor(author);
		}

		if (formatsToFormatCategory.containsKey(format.toLowerCase())) {
			String formatCategory = formatsToFormatCategory.get(format.toLowerCase());
			String groupingCategory = categoryMap.get(formatCategory);
			groupedWork.setGroupingCategory(groupingCategory);
		} else {
			if (!formatsWarned.contains(format)) {
				logEntry.incErrors("Could not find format category for format " + format + " setting to book");
				groupedWork.setGroupingCategory("book");
				formatsWarned.add(format);
			}
		}

		groupedWork.setLanguage(language);

		addGroupedWorkToDatabase(primaryIdentifier, groupedWork, primaryDataChanged, null);
		return groupedWork.getPermanentId();
	}


	private static final HashSet<String> formatsWarned = new HashSet<>();
	static HashMap<String, String> formatsToFormatCategory = new HashMap<>();

	static {
		formatsToFormatCategory.put("emagazine", "book");
		formatsToFormatCategory.put("emusic", "music");
		formatsToFormatCategory.put("music", "music");
		formatsToFormatCategory.put("video", "movie");
		formatsToFormatCategory.put("evideo", "movie");
		formatsToFormatCategory.put("eaudio", "book");
		formatsToFormatCategory.put("eaudiobook", "book");
		formatsToFormatCategory.put("ecomic", "book");
		formatsToFormatCategory.put("audiobook", "book");
		formatsToFormatCategory.put("atlas", "other");
		formatsToFormatCategory.put("map", "other");
		formatsToFormatCategory.put("tapecartridge", "other");
		formatsToFormatCategory.put("chipcartridge", "other");
		formatsToFormatCategory.put("disccartridge", "other");
		formatsToFormatCategory.put("tapecassette", "other");
		formatsToFormatCategory.put("tapereel", "other");
		formatsToFormatCategory.put("floppydisk", "other");
		formatsToFormatCategory.put("cdrom", "other");
		formatsToFormatCategory.put("software", "other");
		formatsToFormatCategory.put("globe", "other");
		formatsToFormatCategory.put("braille", "book");
		formatsToFormatCategory.put("filmstrip", "movie");
		formatsToFormatCategory.put("transparency", "other");
		formatsToFormatCategory.put("slide", "other");
		formatsToFormatCategory.put("microfilm", "other");
		formatsToFormatCategory.put("collage", "other");
		formatsToFormatCategory.put("drawing", "other");
		formatsToFormatCategory.put("painting", "other");
		formatsToFormatCategory.put("print", "other");
		formatsToFormatCategory.put("photonegative", "other");
		formatsToFormatCategory.put("flashcard", "other");
		formatsToFormatCategory.put("chart", "other");
		formatsToFormatCategory.put("photo", "other");
		formatsToFormatCategory.put("motionpicture", "movie");
		formatsToFormatCategory.put("kit", "other");
		formatsToFormatCategory.put("musicalscore", "book");
		formatsToFormatCategory.put("sensorimage", "other");
		formatsToFormatCategory.put("sounddisc", "audio");
		formatsToFormatCategory.put("soundcassette", "audio");
		formatsToFormatCategory.put("soundrecording", "audio");
		formatsToFormatCategory.put("videocartridge", "movie");
		formatsToFormatCategory.put("videosisc", "movie");
		formatsToFormatCategory.put("videocassette", "movie");
		formatsToFormatCategory.put("videoreel", "movie");
		formatsToFormatCategory.put("musicrecording", "music");
		formatsToFormatCategory.put("electronic", "other");
		formatsToFormatCategory.put("physicalobject", "other");
		formatsToFormatCategory.put("manuscript", "book");
		formatsToFormatCategory.put("ebook", "ebook");
		formatsToFormatCategory.put("book", "book");
		formatsToFormatCategory.put("newspaper", "book");
		formatsToFormatCategory.put("journal", "book");
		formatsToFormatCategory.put("serial", "book");
		formatsToFormatCategory.put("unknown", "other");
		formatsToFormatCategory.put("playaway", "audio");
		formatsToFormatCategory.put("largeprint", "book");
		formatsToFormatCategory.put("blu-ray", "movie");
		formatsToFormatCategory.put("dvd", "movie");
		formatsToFormatCategory.put("verticalfile", "other");
		formatsToFormatCategory.put("compactdisc", "audio");
		formatsToFormatCategory.put("taperecording", "audio");
		formatsToFormatCategory.put("phonograph", "audio");
		formatsToFormatCategory.put("pdf", "ebook");
		formatsToFormatCategory.put("epub", "ebook");
		formatsToFormatCategory.put("jpg", "other");
		formatsToFormatCategory.put("gif", "other");
		formatsToFormatCategory.put("mp3", "audio");
		formatsToFormatCategory.put("plucker", "ebook");
		formatsToFormatCategory.put("kindle", "ebook");
		formatsToFormatCategory.put("externallink", "ebook");
		formatsToFormatCategory.put("externalmp3", "audio");
		formatsToFormatCategory.put("interactivebook", "ebook");
		formatsToFormatCategory.put("overdrive", "ebook");
		formatsToFormatCategory.put("external_web", "ebook");
		formatsToFormatCategory.put("external_ebook", "ebook");
		formatsToFormatCategory.put("external_eaudio", "audio");
		formatsToFormatCategory.put("external_emusic", "music");
		formatsToFormatCategory.put("external_evideo", "movie");
		formatsToFormatCategory.put("text", "ebook");
		formatsToFormatCategory.put("gifs", "other");
		formatsToFormatCategory.put("itunes", "audio");
		formatsToFormatCategory.put("adobe_epub_ebook", "ebook");
		formatsToFormatCategory.put("kindle_book", "ebook");
		formatsToFormatCategory.put("microsoft_ebook", "ebook");
		formatsToFormatCategory.put("overdrive_wma_audiobook", "audio");
		formatsToFormatCategory.put("overdrive_mp3_audiobook", "audio");
		formatsToFormatCategory.put("overdrive_music", "music");
		formatsToFormatCategory.put("overdrive_video", "movie");
		formatsToFormatCategory.put("overdrive_read", "ebook");
		formatsToFormatCategory.put("overdrive_listen", "audio");
		formatsToFormatCategory.put("adobe_pdf_ebook", "ebook");
		formatsToFormatCategory.put("palm", "ebook");
		formatsToFormatCategory.put("mobipocket_ebook", "ebook");
		formatsToFormatCategory.put("disney_online_book", "ebook");
		formatsToFormatCategory.put("open_pdf_ebook", "ebook");
		formatsToFormatCategory.put("open_epub_ebook", "ebook");
		formatsToFormatCategory.put("nook_periodicals", "ebook");
		formatsToFormatCategory.put("econtent", "ebook");
		formatsToFormatCategory.put("seedpacket", "other");
		formatsToFormatCategory.put("magazine-overdrive", "ebook");
		formatsToFormatCategory.put("magazine", "book");
		formatsToFormatCategory.put("xps", "ebook");
	}

	static HashMap<String, String> categoryMap = new HashMap<>();

	static {
		categoryMap.put("other", "book");
		categoryMap.put("book", "book");
		categoryMap.put("books", "book");
		categoryMap.put("ebook", "book");
		categoryMap.put("audio", "book");
		categoryMap.put("audio books", "book");
		categoryMap.put("music", "music");
		categoryMap.put("movie", "movie");
		categoryMap.put("movies", "movie");
	}

	private void loadTranslationMaps(String serverName) {
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		if (defaultTranslationMapFiles != null) {
			for (File curFile : defaultTranslationMapFiles) {
				String mapName = curFile.getName().replace(".properties", "");
				mapName = mapName.replace("_map", "");
				translationMaps.put(mapName, loadTranslationMap(curFile));
			}
			if (serverTranslationMapFiles != null) {
				for (File curFile : serverTranslationMapFiles) {
					String mapName = curFile.getName().replace(".properties", "");
					mapName = mapName.replace("_map", "");
					translationMaps.put(mapName, loadTranslationMap(curFile));
				}
			}
		}
	}

	private HashMap<String, String> loadTranslationMap(File translationMapFile) {
		Properties props = new Properties();
		try {
			FileReader translationMapReader = new FileReader(translationMapFile);
			props.load(translationMapReader);
			translationMapReader.close();
		} catch (IOException e) {
			logEntry.incErrors("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
		}
		HashMap<String, String> translationMap = new HashMap<>();
		for (Object keyObj : props.keySet()) {
			String key = (String) keyObj;
			translationMap.put(key.toLowerCase(), props.getProperty(key));
		}
		return translationMap;
	}

	private final HashSet<String> unableToTranslateWarnings = new HashSet<>();

	public String translateValue(@SuppressWarnings("SameParameterValue") String mapName, String value) {
		value = value.toLowerCase();
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null) {
			if (!unableToTranslateWarnings.contains("unable_to_find_" + mapName)) {
				logEntry.incErrors("Unable to find translation map for " + mapName);
				unableToTranslateWarnings.add("unable_to_find_" + mapName);
			}

			translatedValue = value;
		} else {
			if (translationMap.containsKey(value)) {
				translatedValue = translationMap.get(value);
			} else {
				if (translationMap.containsKey("*")) {
					translatedValue = translationMap.get("*");
				} else {
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)) {
						logger.warn("Could not translate '" + concatenatedValue + "'");
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue != null) {
			translatedValue = translatedValue.trim();
			if (translatedValue.length() == 0) {
				translatedValue = null;
			}
		}
		return translatedValue;
	}

	void loadAuthorities(Connection dbConn) {
		logger.info("Loading authorities");
		try {
			//Get the count of authorities in the database
			boolean reloadAuthorAuthorities = true;
			PreparedStatement authorityStmt = dbConn.prepareStatement("SELECT count(*) as numAuthorities from author_authorities");
			ResultSet numAuthorAuthorities = authorityStmt.executeQuery();
			if (numAuthorAuthorities.next()) {
				if (numAuthorAuthorities.getInt("numAuthorities") > 0) {
					reloadAuthorAuthorities = false;
				}
			}
			if (reloadAuthorAuthorities) {
				PreparedStatement addAuthorAuthorityStmt = dbConn.prepareStatement("INSERT into author_authorities (originalName, authoritativeName) VALUES (?, ?)");
				try {
					CSVReader csvReader = new CSVReader(new FileReader(new File("../reindexer/author_authorities.properties")));
					String[] curLine = csvReader.readNext();
					while (curLine != null) {
						try {
							addAuthorAuthorityStmt.setString(1, curLine[0]);
							addAuthorAuthorityStmt.setString(2, curLine[1]);
							addAuthorAuthorityStmt.executeUpdate();
						} catch (SQLException e) {
							logEntry.incErrors("Error adding authority " + curLine[0]);
						}
						curLine = csvReader.readNext();
					}
					csvReader.close();
				} catch (IOException e) {
					logEntry.incErrors("Unable to load author authorities", e);
				}
			}

			//Get the count of authorities in the database
			boolean reloadTitleAuthorities = true;
			authorityStmt = dbConn.prepareStatement("SELECT count(*) as numAuthorities from title_authorities");
			ResultSet numTitleAuthorities = authorityStmt.executeQuery();
			if (numTitleAuthorities.next()) {
				if (numTitleAuthorities.getInt("numAuthorities") > 0) {
					reloadTitleAuthorities = false;
				}
			}
			if (reloadTitleAuthorities) {
				PreparedStatement addTitleAuthorityStmt = dbConn.prepareStatement("INSERT into title_authorities (originalName, authoritativeName) VALUES (?, ?)");
				try {
					CSVReader csvReader = new CSVReader(new FileReader(new File("../reindexer/title_authorities.properties")));
					String[] curLine = csvReader.readNext();
					while (curLine != null) {
						try {
							addTitleAuthorityStmt.setString(1, curLine[0]);
							addTitleAuthorityStmt.setString(2, curLine[1]);
							addTitleAuthorityStmt.executeUpdate();
						} catch (SQLException e) {
							logEntry.incErrors("Error adding authority " + curLine[0]);
						}
						curLine = csvReader.readNext();
					}
					csvReader.close();
				} catch (IOException e) {
					logEntry.incErrors("Unable to load title authorities", e);
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading authorities", e);
		}

		//Normalize any authorities that have not been normalized yet.
		try{
			PreparedStatement getNonNormalizedAuthorsStmt = dbConn.prepareStatement("SELECT id, author FROM author_authority where normalized IS NULL or normalized = ''", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement setNormalizedAuthorStmt = dbConn.prepareStatement("UPDATE author_authority set normalized = ? where id = ?");
			ResultSet getNonNormalizedAuthorsRS = getNonNormalizedAuthorsStmt.executeQuery();
			while (getNonNormalizedAuthorsRS.next()){
				String author = getNonNormalizedAuthorsRS.getString("author");
				String normalizedAuthor = AuthorNormalizer.getNormalizedName(author);
				setNormalizedAuthorStmt.setString(1, normalizedAuthor);
				setNormalizedAuthorStmt.setLong(2, getNonNormalizedAuthorsRS.getLong("id"));
				setNormalizedAuthorStmt.executeUpdate();
			}
			PreparedStatement getNonNormalizedAuthorAlternativesStmt = dbConn.prepareStatement("SELECT id, alternativeAuthor FROM author_authority_alternative where normalized IS NULL or normalized = ''", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement setNormalizedAlternativeAuthorStmt = dbConn.prepareStatement("UPDATE author_authority_alternative set normalized = ? where id = ?");
			ResultSet getNonNormalizedAuthorAlternativesRS = getNonNormalizedAuthorAlternativesStmt.executeQuery();
			while (getNonNormalizedAuthorAlternativesRS.next()){
				String alternativeAuthor = getNonNormalizedAuthorAlternativesRS.getString("alternativeAuthor");
				String normalizedAuthor = AuthorNormalizer.getNormalizedName(alternativeAuthor);
				setNormalizedAlternativeAuthorStmt.setString(1, normalizedAuthor);
				setNormalizedAlternativeAuthorStmt.setLong(2, getNonNormalizedAuthorAlternativesRS.getLong("id"));
				setNormalizedAlternativeAuthorStmt.executeUpdate();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error normalizing authorities", e);
		}
		logger.info("Done loading authorities");
	}

	String getAuthoritativeAuthor(String originalAuthor) {
		if (lookupAuthorAuthoritiesInDB && originalAuthor.length() > 0) {
			try {
				getAuthoritativeAuthorStmt.setString(1, originalAuthor);
				ResultSet authoritativeAuthorRS = getAuthoritativeAuthorStmt.executeQuery();
				if (authoritativeAuthorRS.next()) {
					return authoritativeAuthorRS.getString("normalized");
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting authoritative author", e);
			}
		}
		return originalAuthor;
	}

	String getAuthoritativeTitle(String originalTitle) {
		if (originalTitle.length() > 0) {
			if (lookupTitleAuthoritiesInDB) {
				try {
					getTitleAuthorityStmt.setString(1, originalTitle);
					ResultSet authorityRS = getTitleAuthorityStmt.executeQuery();
					if (authorityRS.next()) {
						return authorityRS.getString("authoritativeName");
					}
				} catch (SQLException e) {
					logEntry.incErrors("Error getting authoritative title", e);
				}
			} else {
				String authority = titleAuthorities.get(originalTitle);
				if (authority != null) {
					return authority;
				}
			}
		}
		return originalTitle;
	}

	BaseIndexingLogEntry getLogEntry(){
		return this.logEntry;
	}

	String languageFields = "008[35-37]";
	public String getLanguageBasedOnMarcRecord(Record marcRecord) {
		String activeLanguage = null;
		Set<String> languages = MarcUtil.getFieldList(marcRecord, languageFields);
		for (String language : languages){
			if (activeLanguage == null){
				activeLanguage = language;
			}else{
				if (!activeLanguage.equals(language)){
					activeLanguage = "mul";
					break;
				}
			}
		}
		if (activeLanguage == null || activeLanguage.equals("|||") || activeLanguage.equals("   ") || activeLanguage.contains(" ")){
			activeLanguage = "unk";
		}
		return activeLanguage;
	}

	public String groupAxis360Record(String axis360Id) throws JSONException {
		try {
			getAxis360DetailsForRecordStmt.setString(1, axis360Id);
			ResultSet getItemDetailsForRecordRS = getAxis360DetailsForRecordStmt.executeQuery();
			if (getItemDetailsForRecordRS.next()){
				String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
				try {
					JSONObject itemDetails = new JSONObject(rawResponse);
					String primaryAuthor = getItemDetailsForRecordRS.getString("primaryAuthor");
					return groupAxis360Record(itemDetails, axis360Id, primaryAuthor);
				}catch (JSONException e){
					logEntry.incErrors("Could not parse item details for record to reload " + axis360Id);
				}
			}else{
				logEntry.incErrors("Could not get details for record to reload " + axis360Id);
			}
			getItemDetailsForRecordRS.close();
		}catch (SQLException e){
			logEntry.incErrors("Error Grouping Axis 360 Record", e);
		}
		return null;
	}

	public String groupAxis360Record(JSONObject itemDetails, String axis360Id, String primaryAuthor) throws JSONException {
		//Perform record grouping on the record
		String title = getAxis360FieldValue(itemDetails, "title");
		String formatType = itemDetails.getString("formatType");
		String language = getAxis360FieldValue(itemDetails, "language");

		RecordIdentifier primaryIdentifier = new RecordIdentifier("axis360", axis360Id);

		String subtitle = getAxis360FieldValue(itemDetails, "subtitle");
		return processRecord(primaryIdentifier, title, subtitle, primaryAuthor, formatType, language, true);
	}

	public String groupCloudLibraryRecord(String cloudLibraryId, Record cloudLibraryRecord){
		try{
			getCloudLibraryDetailsForRecordStmt.setString(1, cloudLibraryId);
			ResultSet getItemDetailsForRecordRS = getCloudLibraryDetailsForRecordStmt.executeQuery();
			if (getItemDetailsForRecordRS.next()){
				String title = getItemDetailsForRecordRS.getString("title");
				String subTitle = getItemDetailsForRecordRS.getString("subTitle");
				String author = getItemDetailsForRecordRS.getString("author");
				String format = getItemDetailsForRecordRS.getString("format");
				RecordIdentifier primaryIdentifier = new RecordIdentifier("cloud_library", cloudLibraryId);

				String primaryLanguage = getLanguageBasedOnMarcRecord(cloudLibraryRecord);

				return processRecord(primaryIdentifier, title, subTitle, author, format, primaryLanguage, true);
			}else{
				logEntry.incErrors("Could not get details for record to reload " + cloudLibraryId);
			}
			getItemDetailsForRecordRS.close();
		}catch(SQLException e){
			logEntry.incErrors("Error Grouping Cloud Library Record", e);
		}
		return null;
	}

	private String getAxis360FieldValue(JSONObject itemDetails, String fieldName) {
		JSONArray fields = itemDetails.getJSONArray("fields");
		for (int i = 0; i < fields.length(); i++){
			JSONObject field = fields.getJSONObject(i);
			if (field.getString("name").equals(fieldName)){
				JSONArray fieldValues = field.getJSONArray("values");
				if (fieldValues.length() == 0) {
					return "";
				}else if (fieldValues.length() == 1) {
					return fieldValues.getString(0).trim();
				}else{
					ArrayList<String> values = new ArrayList<>();
					for (int j = 0; j < fieldValues.length(); j++){
						values.add(fieldValues.getString(j));
					}
					return values.get(0).trim();
				}
			}
		}
		return "";
	}

	public String groupHooplaRecord(String hooplaId) throws JSONException {
		try {
			getHooplaRecordStmt.setString(1, hooplaId);
			ResultSet getHooplaRecordRS = getHooplaRecordStmt.executeQuery();
			if (getHooplaRecordRS.next()){
				String rawResponseString = new String(getHooplaRecordRS.getBytes("rawResponse"), StandardCharsets.UTF_8);
				JSONObject rawResponse = new JSONObject(rawResponseString);
				//Pass null to processMarcRecord.  It will do the lookup to see if there is an existing id there.
				return groupHooplaRecord(rawResponse, Long.parseLong(hooplaId));
			}
		}catch (Exception e){
			logEntry.incErrors("Error grouping hoopla record " + hooplaId, e);
		}
		return null;
	}

	public String groupHooplaRecord(JSONObject itemDetails, long hooplaId) throws JSONException {
		//Perform record grouping on the record
		String title;
		String subTitle;
		if (itemDetails.has("titleTitle")){
			title = itemDetails.getString("titleTitle");
			subTitle = itemDetails.getString("title");
		}else {
			title = itemDetails.getString("title");
			if (itemDetails.has("subtitle")){
				subTitle = itemDetails.getString("subtitle");
			}else{
				subTitle = "";
			}
		}
		String mediaType = itemDetails.getString("kind");
		String primaryFormat;
		switch (mediaType) {
			case "MOVIE":
			case "TELEVISION":
				primaryFormat = "eVideo";
				break;
			case "AUDIOBOOK":
				primaryFormat = "eAudiobook";
				break;
			case "EBOOK":
				primaryFormat = "eBook";
				break;
			case "COMIC":
				primaryFormat = "eComic";
				break;
			case "MUSIC":
				primaryFormat = "eMusic";
				break;
			default:
				logger.error("Unhandled hoopla mediaType " + mediaType);
				primaryFormat = mediaType;
				break;
		}
		String author = "";
		if (itemDetails.has("artist")) {
			author = itemDetails.getString("artist");
			author = AspenStringUtils.swapFirstLastNames(author);
		} else if (itemDetails.has("publisher")) {
			author = itemDetails.getString("publisher");
		}

		RecordIdentifier primaryIdentifier = new RecordIdentifier("hoopla", Long.toString(hooplaId));

		String language = itemDetails.getString("language");
		String languageCode = translateValue("language_to_three_letter_code", language);

		return processRecord(primaryIdentifier, title, subTitle, author, primaryFormat, languageCode, true);
	}
}
