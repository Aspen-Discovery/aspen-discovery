package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.Axis360Scope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
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
			logger.error("Error setting up Boundless processor", e);
		}
	}

	void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				if (productRS.getBoolean("deleted")) {
					logger.debug("Boundless product " + identifier + " was deleted, skipping");
					return;
				}

				RecordInfo axis360Record = groupedWork.addRelatedRecord("axis360", identifier);
				axis360Record.setRecordIdentifier("axis360", identifier);

				long aspenId = productRS.getLong("id");
				JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));
				String title = productRS.getString("title");
				String formatType = productRS.getString("formatType");
				String formatCategory;
				String primaryFormat;
				if ("eBook".equals(formatType) || "Adobe PDF".equals(formatType) || "Adobe EPUB".equals(formatType)) {
					formatCategory = "eBook";
					primaryFormat = "eBook";
					//Check subjects to see if this should be a comic book
					String[] subjects = getFieldValue(rawResponse, "subject").split("#\\s");
					for (String curSubject : subjects) {
						if (curSubject.contains("Comics & Graphic Novels") || curSubject.contains("Comic and Graphic Books")) {
							primaryFormat = "eComic";
							break;
						}
					}
				} else if ("eAudiobook".equals(formatType)) {
					formatCategory = "Audio Books";
					axis360Record.addFormatCategory("eBook");
					primaryFormat = "eAudiobook";
				} else {
					logEntry.addNote("Unhandled Boundless mediaType " + formatType);
					formatCategory = formatType;
					primaryFormat = formatType;
				}
				if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format is " + primaryFormat + " based on a formatType of " + formatType, 2);}

				axis360Record.addFormat(primaryFormat);
				axis360Record.addFormatCategory(formatCategory);

				String subTitle = "";
				if (rawResponse.has("subTitle")) {
					subTitle = rawResponse.getString("subTitle");
				}
				groupedWork.setTitle(title, subTitle, title, title, primaryFormat, formatCategory);

				String primaryAuthor = productRS.getString("primaryAuthor");
				groupedWork.setAuthor(primaryAuthor);
				groupedWork.setAuthAuthor(primaryAuthor);
				groupedWork.setAuthorDisplay(primaryAuthor, formatCategory);

				String series = getFieldValue(rawResponse,"series");
				if (!series.isEmpty()){
					groupedWork.addSeries(series);
				}

				String targetAudience = loadAxis360Subjects(groupedWork, rawResponse);

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
				if (!narrators.isEmpty()) {
					HashSet<String> narratorsToAdd = new HashSet<>();
					HashSet<String> narratorsWithRoleToAdd = new HashSet<>();
					for (String narratorName : narrators) {
						narratorsToAdd.add(narratorName);
						narratorsWithRoleToAdd.add(narratorName + "|Narrator");
					}
					groupedWork.addAuthor2(narratorsToAdd);
					groupedWork.addAuthor2Role(narratorsWithRoleToAdd);
				}

				groupedWork.addDescription(getFieldValue(rawResponse, "description"), formatCategory);

				String language = getFieldValue(rawResponse, "language");
				groupedWork.addLanguage(indexer.translateSystemValue("language", language, identifier));

				groupedWork.addPublisher(getFieldValue(rawResponse, "publisher"));

				ArrayList<String> authors = getFieldValues(rawResponse, "author");
				if (authors.size() > 1){
					HashSet<String> authorsToAdd = new HashSet<>(authors);
					groupedWork.addAuthor2(authorsToAdd);
				}

				//Boundless does not include publisher information or descriptions

				String isbn = getFieldValue(rawResponse, "isbn");
				groupedWork.addIsbn(isbn, primaryFormat);

				getAvailabilityStmt.setLong(1, aspenId);
				ResultSet availabilityRS = getAvailabilityStmt.executeQuery();
				while (availabilityRS.next()) {
					ItemInfo itemInfo = new ItemInfo();
					itemInfo.seteContentSource("Boundless");
					itemInfo.setIsEContent(true);
					itemInfo.setShelfLocation("Online Boundless Collection");
					itemInfo.setDetailedLocation("Online Boundless Collection");
					itemInfo.setCallNumber("Online Boundless");
					itemInfo.setSortableCallNumber("Online Boundless");
					itemInfo.setFormat(primaryFormat);
					itemInfo.setFormatCategory(formatCategory);

					Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
					itemInfo.setDateAdded(dateAdded);

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
					boolean isAdult = targetAudience.equals("Adult");
					boolean isTeen = targetAudience.equals("Young Adult");
					boolean isKids = targetAudience.equals("Juvenile");
					for (Scope scope : indexer.getScopes()) {
						boolean okToAdd = false;
						Axis360Scope axis360Scope = scope.getAxis360Scope();
						if (axis360Scope != null) {
							if (axis360Scope.getSettingId() == settingId) {
								okToAdd = true;
							}
						}
						if (okToAdd) {
							//Check based on the audience as well
							okToAdd = false;
							//noinspection RedundantIfStatement
							if (isAdult && axis360Scope.isIncludeAdult()) {
								okToAdd = true;
							}
							if (isTeen && axis360Scope.isIncludeTeen()) {
								okToAdd = true;
							}
							if (isKids && axis360Scope.isIncludeKids()) {
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
					axis360Record.addItem(itemInfo);
				}
				availabilityRS.close();
			}
			productRS.close();
		} catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing Boundless record ", e);
		} catch (JSONException e) {
			logEntry.incErrors("Error parsing raw data for Boundless", e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for Boundless title", e);
		}
	}

	/**
	 * Load information based on subjects for the record
	 *
	 * @param groupedWork     The Grouped Work being updated
	 * @param titleData JSON representing the raw data metadata from Libby
	 * @throws JSONException Exception if something goes horribly wrong
	 *
	 * @return String the targetAudience of the record
	 */
	private String loadAxis360Subjects(AbstractGroupedWorkSolr groupedWork, JSONObject titleData) throws JSONException {
		//Load subject data

		HashSet<String> topics = new HashSet<>();
		HashSet<String> genres = new HashSet<>();
		HashMap<String, Integer> literaryForm = new HashMap<>();
		HashMap<String, Integer> literaryFormFull = new HashMap<>();
		String genre = getFieldValue(titleData, "genre");
		genres.add(genre);
		Util.addToMapWithCount(literaryForm, genre);
		if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is " + genre + " based on Axis360 record", 2);}
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
				targetAudience = "Adult";
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
			if (!literaryForm.isEmpty()) {
				groupedWork.addLiteraryForms(literaryForm);
			}
			if (!literaryFormFull.isEmpty()) {
				groupedWork.addLiteraryFormsFull(literaryFormFull);
			}
		}
		if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is " + targetAudience + " based on Boundless record", 2);}
		if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience list is " + targetAudienceFull + " based on Boundless record", 2);}
		groupedWork.addTargetAudience(targetAudience);
		groupedWork.addTargetAudienceFull(targetAudienceFull);

		return targetAudience;
	}

	private static String getFieldValue(JSONObject itemDetails, String fieldName) {
		JSONArray fields = itemDetails.getJSONArray("fields");
		for (int i = 0; i < fields.length(); i++){
			JSONObject field = fields.getJSONObject(i);
			if (field.getString("name").equals(fieldName)){
				JSONArray fieldValues = field.getJSONArray("values");
				if (fieldValues.isEmpty()) {
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
