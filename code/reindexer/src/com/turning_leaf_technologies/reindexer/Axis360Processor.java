package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Axis360Scope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

class Axis360Processor {
	private final GroupedWorkIndexer indexer;
	private final Logger logger;

	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getAvailabilityStmt;

	Axis360Processor(GroupedWorkIndexer groupedWorkIndexer, Connection dbConn, Logger logger) {
		this.indexer = groupedWorkIndexer;
		this.logger = logger;

		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT * from axis360_title where axis360Id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getAvailabilityStmt = dbConn.prepareStatement("SELECT * from axis360_title_availability where titleId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up Axis 360 processor", e);
		}
	}

	void processRecord(GroupedWorkSolr groupedWork, String identifier, BaseLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				if (productRS.getBoolean("deleted")) {
					logger.debug("Axis 360 product " + identifier + " was deleted, skipping");
					return;
				}

				RecordInfo axis360Record = groupedWork.addRelatedRecord("axis360", identifier);
				axis360Record.setRecordIdentifier("axis360", identifier);

				long aspenId = productRS.getLong("id");
				String title = productRS.getString("title");
				String formatType = productRS.getString("formatType");
				String formatCategory;
				String primaryFormat;
				if ("eBook".equals(formatType)) {
					formatCategory = "eBook";
					primaryFormat = "eBook";
				} else if ("eAudiobook".equals(formatType)) {
					formatCategory = "Audio Books";
					axis360Record.addFormatCategory("eBook");
					primaryFormat = "eAudiobook";
				} else {
					logEntry.addNote("Unhandled Axis 360 mediaType " + formatType);
					formatCategory = formatType;
					primaryFormat = formatType;
				}
				axis360Record.addFormat(primaryFormat);
				axis360Record.addFormatCategory(formatCategory);

				JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));

				String subTitle = "";
				if (rawResponse.has("subTitle")) {
					subTitle = rawResponse.getString("subTitle");
				}
				groupedWork.setTitle(title, subTitle, title, title, primaryFormat, formatCategory);

				String primaryAuthor = productRS.getString("primaryAuthor");
				groupedWork.setAuthor(primaryAuthor);
				groupedWork.setAuthAuthor(primaryAuthor);
				groupedWork.setAuthorDisplay(primaryAuthor);

				String series = getFieldValue(rawResponse,"series");
				if (series.length() > 0){
					groupedWork.addSeries(series);
				}

				loadAxis360Subjects(groupedWork, rawResponse);

				//Believe these are all
				axis360Record.setPrimaryLanguage("English");
				long formatBoost = 1;
				try {
					formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_axis360", primaryFormat, identifier));
				} catch (Exception e) {
					logEntry.addNote("Could not translate format boost for " + primaryFormat + " create translation map format_boost_axis360");
				}
				axis360Record.setFormatBoost(formatBoost);

				ArrayList<String> narrators = getFieldValues(rawResponse, "narrators");
				if (narrators.size() > 0) {
					HashSet<String> narratorsToAdd = new HashSet<>();
					HashSet<String> narratorsWithRoleToAdd = new HashSet<>();
					for (String narratorName : narrators) {
						narratorsToAdd.add(narratorName);
						narratorsWithRoleToAdd.add(narratorName + "|Narrator");
					}
					groupedWork.addAuthor2(narratorsToAdd);
					groupedWork.addAuthor2Role(narratorsWithRoleToAdd);
				}

				groupedWork.addDescription(getFieldValue(rawResponse, "description"), formatType, formatCategory);

				String language = getFieldValue(rawResponse, "language");
				groupedWork.addLanguage(indexer.translateSystemValue("language", language, identifier));

				groupedWork.addPublisher(getFieldValue(rawResponse, "publisher"));

				ArrayList<String> authors = getFieldValues(rawResponse, "author");
				if (authors.size() > 1){
					HashSet<String> authorsToAdd = new HashSet<>(authors);
					groupedWork.addAuthor2(authorsToAdd);
				}

				//Axis 360 does not include publisher information or descriptions

				String isbn = getFieldValue(rawResponse, "isbn");
				groupedWork.addIsbn(isbn, primaryFormat);

				ItemInfo itemInfo = new ItemInfo();
				itemInfo.seteContentSource("Axis 360");
				itemInfo.setIsEContent(true);
				itemInfo.setShelfLocation("Online Axis 360 Collection");
				itemInfo.setDetailedLocation("Online Axis 360 Collection");
				itemInfo.setCallNumber("Online Axis 360");
				itemInfo.setSortableCallNumber("Online Axis 360");
				itemInfo.setFormat(primaryFormat);
				itemInfo.setFormatCategory(formatCategory);

				Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
				itemInfo.setDateAdded(dateAdded);

				getAvailabilityStmt.setLong(1, aspenId);
				ResultSet availabilityRS = getAvailabilityStmt.executeQuery();
				while (availabilityRS.next()) {
					boolean available = availabilityRS.getBoolean("available");
					int ownedQty = availabilityRS.getInt("ownedQty");
					itemInfo.setNumCopies(ownedQty);
					long settingId = availabilityRS.getLong("settingId");
					itemInfo.setAvailable(available);
					if (available) {
						itemInfo.setDetailedStatus("Available Online");
						itemInfo.setGroupedStatus("Available Online");
					} else {
						itemInfo.setDetailedStatus("Checked Out");
						itemInfo.setGroupedStatus("Checked Out");
					}
					itemInfo.setHoldable(true);
					itemInfo.setInLibraryUseOnly(false);
					for (Scope scope : indexer.getScopes()) {
						boolean okToAdd = false;
						Axis360Scope axis360Scope = scope.getAxis360Scope();
						if (axis360Scope != null) {
							if (axis360Scope.getSettingId() == settingId) {
								okToAdd = true;
							}
						}
						if (okToAdd) {
							ScopingInfo scopingInfo = itemInfo.addScope(scope);
							groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);

							scopingInfo.setLibraryOwned(true);
							scopingInfo.setLocallyOwned(true);
						}
					}
				}
				availabilityRS.close();
				axis360Record.addItem(itemInfo);

			}
			productRS.close();
		} catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing Axis 360 record ", e);
		} catch (JSONException e) {
			logEntry.incErrors("Error parsing raw data for Axis 360", e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for Axis 360 title", e);
		}
	}

	/**
	 * Load information based on subjects for the record
	 *
	 * @param groupedWork     The Grouped Work being updated
	 * @param titleData JSON representing the raw data metadata from OverDrive
	 * @throws JSONException Exception if something goes horribly wrong
	 */
	private void loadAxis360Subjects(GroupedWorkSolr groupedWork, JSONObject titleData) throws JSONException {
		//Load subject data

		HashSet<String> topics = new HashSet<>();
		HashSet<String> genres = new HashSet<>();
		HashMap<String, Integer> literaryForm = new HashMap<>();
		HashMap<String, Integer> literaryFormFull = new HashMap<>();
		String genre = getFieldValue(titleData, "genre");
		genres.add(genre);
		Util.addToMapWithCount(literaryForm, genre);
		Util.addToMapWithCount(literaryFormFull, genre);

		String targetAudience;
		String targetAudienceFull;
		String audience = getFieldValue(titleData, "audience");
		String gradeLevel = getFieldValue(titleData, "grade level");
		if (audience.equals("General Adult")){
			targetAudience = "Adult";
			targetAudienceFull = "Adult";
		}else{
			if (audience.contains("Children")){
				targetAudience = "Juvenile";
			}else if (audience.contains("Teen")) {
				targetAudience = "Young Adult";
			}else{
				targetAudience = audience;
			}

			targetAudienceFull = gradeLevel;
		}

		if (titleData.has("subjects")) {
			String[] subjects = getFieldValue(titleData,"subject").split("#\\s");
			for (String curSubject : subjects) {
				curSubject = curSubject.replaceAll("/", " -- ");
				topics.add(curSubject);
			}
			groupedWork.addTopic(topics);
			groupedWork.addTopicFacet(topics);
			groupedWork.addGenre(genres);
			groupedWork.addGenreFacet(genres);
			groupedWork.addSubjects(topics);
			if (literaryForm.size() > 0) {
				groupedWork.addLiteraryForms(literaryForm);
			}
			if (literaryFormFull.size() > 0) {
				groupedWork.addLiteraryFormsFull(literaryFormFull);
			}
		}

		groupedWork.addTargetAudience(targetAudience);
		groupedWork.addTargetAudienceFull(targetAudienceFull);
	}

	private static String getFieldValue(JSONObject itemDetails, String fieldName) {
		JSONArray fields = itemDetails.getJSONArray("fields");
		for (int i = 0; i < fields.length(); i++){
			JSONObject field = fields.getJSONObject(i);
			if (field.getString("name").equals(fieldName)){
				JSONArray fieldValues = field.getJSONArray("values");
				if (fieldValues.length() == 0) {
					return "";
				}else if (fieldValues.length() == 1) {
					return fieldValues.getString(0);
				}else{
					ArrayList<String> values = new ArrayList<>();
					for (int j = 0; j < fieldValues.length(); j++){
						values.add(fieldValues.getString(j));
					}
					return values.get(0);
				}
			}
		}
		return "";
	}

	private static boolean fieldHasMultipleValues(JSONObject itemDetails, String fieldName) {
		JSONArray fields = itemDetails.getJSONArray("fields");
		for (int i = 0; i < fields.length(); i++){
			JSONObject field = fields.getJSONObject(i);
			if (field.getString("name").equals(fieldName)){
				JSONArray fieldValues = field.getJSONArray("values");
				return fieldValues.length() > 1;
			}
		}
		return false;
	}

	private static ArrayList<String> getFieldValues(JSONObject itemDetails, String fieldName) {
		ArrayList<String> values = new ArrayList<>();
		JSONArray fields = itemDetails.getJSONArray("fields");
		for (int i = 0; i < fields.length(); i++){
			JSONObject field = fields.getJSONObject(i);
			if (field.getString("name").equals(fieldName)){
				JSONArray fieldValues = field.getJSONArray("values");

				for (int j = 0; j < fieldValues.length(); j++){
					values.add(fieldValues.getString(j));
				}
			}
		}
		return values;
	}
}
