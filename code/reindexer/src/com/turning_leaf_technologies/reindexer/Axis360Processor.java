package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Axis360Scope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
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
				if ("XPS".equals(formatType)) {
					formatCategory = "eBook";
					primaryFormat = "eBook";
				} else {
					logEntry.addNote("Unhandled Axis 360 mediaType " + formatType);
					formatCategory = formatType;
					primaryFormat = formatType;
				}
				axis360Record.addFormat(primaryFormat);
				axis360Record.addFormatCategory(formatCategory);

				JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));

				groupedWork.setTitle(title, title, title, primaryFormat);
				if (rawResponse.has("subTitle")) {
					String subtitle = rawResponse.getString("subTitle");
					groupedWork.setSubTitle(subtitle);
				}
				String primaryAuthor = StringUtils.swapFirstLastNames(productRS.getString("primaryAuthor"));
				groupedWork.setAuthor(primaryAuthor);
				groupedWork.setAuthAuthor(primaryAuthor);
				groupedWork.setAuthorDisplay(primaryAuthor);

				String series = rawResponse.getString("series");
				if (series.length() > 0){
					groupedWork.addSeries(series);
				}

				String targetAudience = loadAxis360Subjects(groupedWork, rawResponse);
				if (targetAudience.contains("Childrens")) {
					groupedWork.addTargetAudience("Juvenile");
					groupedWork.addTargetAudienceFull("Juvenile");
				} else if (targetAudience.contains("Young Adult")) {
					groupedWork.addTargetAudience("Young Adult");
					groupedWork.addTargetAudienceFull("Adolescent (14-17)");
				} else if (targetAudience.contains("Beginning Reader")) {
					groupedWork.addTargetAudience("Juvenile");
					groupedWork.addTargetAudienceFull("Primary (6-8)");
				} else {
					groupedWork.addTargetAudience("Adult");
					groupedWork.addTargetAudienceFull("Adult");
				}

				//Believe these are all
				axis360Record.setPrimaryLanguage("English");
				long formatBoost = 1;
				try {
					formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_axis360", primaryFormat, identifier));
				} catch (Exception e) {
					logEntry.addNote("Could not translate format boost for " + primaryFormat + " create translation map format_boost_axis360");
				}
				axis360Record.setFormatBoost(formatBoost);
				if (rawResponse.has("narrators")) {
					HashSet<String> narratorsToAdd = new HashSet<>();
					HashSet<String> narratorsWithRoleToAdd = new HashSet<>();
					if (rawResponse.get("narrators") instanceof JSONArray){
						JSONArray narrators = rawResponse.getJSONArray("narrators");
						for (int i = 0; i < narrators.length(); i++) {
							String narratorName = narrators.getString(i);
							narratorsToAdd.add(narratorName);
							narratorsWithRoleToAdd.add(narratorName + "|Narrator");
						}
					}else if (rawResponse.get("narrators") instanceof JSONObject){
						JSONObject narrators = rawResponse.getJSONObject("narrators");
						if (narrators.get("narrator") instanceof String) {
							narratorsToAdd.add(narrators.getString("narrator"));
							narratorsWithRoleToAdd.add(narrators.getString("narrator") + "|Narrator");
						}else{
							JSONArray narratorsArray = narrators.getJSONArray("narrator");
							for (int i = 0; i < narratorsArray.length(); i++){
								narratorsToAdd.add(narratorsArray.getString(i));
								narratorsWithRoleToAdd.add(narratorsArray.getString(i) + "|Narrator");
							}
						}
						String narratorName = narrators.getString("narrator");
						narratorsToAdd.add(narratorName);
						narratorsWithRoleToAdd.add(narratorName + "|Narrator");
					}else if (rawResponse.get("narrators") instanceof String){
						String narratorName = rawResponse.getString("narrators");
						if (narratorName.length() > 0){
							narratorsToAdd.add(narratorName);
							narratorsWithRoleToAdd.add(narratorName + "|Narrator");
						}
					}

					groupedWork.addAuthor2(narratorsToAdd);
					groupedWork.addAuthor2Role(narratorsWithRoleToAdd);
				}
				if (rawResponse.has("authors")) {
					HashSet<String> authorsToAdd = new HashSet<>();
					if (rawResponse.get("authors") instanceof JSONArray){
						JSONArray authors = rawResponse.getJSONArray("authors");
						for (int i = 0; i < authors.length(); i++) {
							authorsToAdd.add(authors.getString(i));
						}
					}else if (rawResponse.get("authors") instanceof JSONObject){
						JSONObject authors = rawResponse.getJSONObject("authors");
						if (authors.get("author") instanceof String) {
							authorsToAdd.add(authors.getString("author"));
						}else{
							JSONArray authorsArray = authors.getJSONArray("author");
							for (int i = 0; i < authorsArray.length(); i++){
								authorsToAdd.add(authorsArray.getString(i));
							}
						}

					}else if (rawResponse.get("authors") instanceof String){
						String authorName = rawResponse.getString("authors");
						if (authorName.length() > 0){
							authorsToAdd.add(authorName);
						}
					}

					groupedWork.addAuthor2(authorsToAdd);
				}

				//Axis 360 does not include publisher information or descriptions

				String isbn = Long.toString(rawResponse.getLong("isbn"));
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
				//We don't currently have a way to determine how many copies are owned
				itemInfo.setNumCopies(0);

				Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
				itemInfo.setDateAdded(dateAdded);

				getAvailabilityStmt.setLong(1, aspenId);
				ResultSet availabilityRS = getAvailabilityStmt.executeQuery();
				while (availabilityRS.next()) {
					int availableQty = availabilityRS.getInt("availableQty");
					int ownedQty = availabilityRS.getInt("ownedQty");
					itemInfo.setNumCopies(ownedQty);
					long settingId = availabilityRS.getLong("settingId");
					if (availableQty > 0) {
						itemInfo.setDetailedStatus("Available Online");
					} else {
						itemInfo.setDetailedStatus("Checked Out");
					}
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
							scopingInfo.setAvailable(availableQty > 0);
							if (availableQty > 0) {
								scopingInfo.setStatus("Available Online");
								scopingInfo.setGroupedStatus("Available Online");
							} else {
								scopingInfo.setStatus("Checked Out");
								scopingInfo.setGroupedStatus("Checked Out");
							}
							scopingInfo.setHoldable(true);
							scopingInfo.setLibraryOwned(true);
							scopingInfo.setLocallyOwned(true);
							scopingInfo.setInLibraryUseOnly(false);
						}
					}
				}
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
	 * @return The target audience for use later in scoping
	 * @throws JSONException Exception if something goes horribly wrong
	 */
	private String loadAxis360Subjects(GroupedWorkSolr groupedWork, JSONObject titleData) throws JSONException {
		//Load subject data

		HashSet<String> topics = new HashSet<>();
		HashSet<String> genres = new HashSet<>();
		HashMap<String, Integer> literaryForm = new HashMap<>();
		HashMap<String, Integer> literaryFormFull = new HashMap<>();
		String targetAudience = "Adult";
		String targetAudienceFull = "Adult";
		if (titleData.has("subjects")) {
			String[] subjects = titleData.getString("subjects").split("#\\s");
			for (String curSubject : subjects) {
				curSubject = curSubject.replaceAll("/", " -- ");
				String curSubjectLower = curSubject.toLowerCase();
				if (curSubjectLower.contains("nonfiction")) {
					Util.addToMapWithCount(literaryForm, "Non Fiction");
					Util.addToMapWithCount(literaryFormFull, "Non Fiction");
					genres.add("Non Fiction");
				} else if (curSubjectLower.contains("fiction")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, "Fiction");
					genres.add("Fiction");
				}

				if (curSubjectLower.contains("poetry")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, "Poetry");
				} else if (curSubjectLower.contains("essays")) {
					Util.addToMapWithCount(literaryForm, "Non Fiction");
					Util.addToMapWithCount(literaryFormFull, "Essays");
				} else if (curSubjectLower.contains("short stories")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, "Short Stories");
				} else if (curSubjectLower.contains("drama")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, "Drama");
				}

				if (curSubjectLower.contains("juvenile")) {
					targetAudience = "Juvenile";
					targetAudienceFull = "Juvenile";
				} else if (curSubjectLower.contains("young adult")) {
					targetAudience = "Young Adult";
					targetAudienceFull = "Adolescent (14-17)";
				} else if (curSubjectLower.contains("picture book")) {
					targetAudience = "Juvenile";
					targetAudienceFull = "Preschool (0-5)";
				} else if (curSubjectLower.contains("beginning reader")) {
					targetAudience = "Juvenile";
					targetAudienceFull = "Primary (6-8)";
				}

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

		return targetAudience;
	}
}
