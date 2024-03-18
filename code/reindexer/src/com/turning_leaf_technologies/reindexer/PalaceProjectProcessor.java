package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.PalaceProjectScope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashSet;

public class PalaceProjectProcessor {
	private final GroupedWorkIndexer indexer;
	private final Logger logger;

	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getProductIdForPalaceProjectIdStmt;

	PalaceProjectProcessor(GroupedWorkIndexer indexer, Connection dbConn, Logger logger) {
		this.indexer = indexer;
		this.logger = logger;

		try {
			getProductIdForPalaceProjectIdStmt = dbConn.prepareStatement("SELECT id from palace_project_title where palaceProjectId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getProductInfoStmt = dbConn.prepareStatement("SELECT id, palaceProjectId, collectionName, title, rawChecksum, UNCOMPRESS(rawResponse) as rawResponse, dateFirstDetected from palace_project_title where id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up hoopla processor", e);
		}
	}

	void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry) {
		try {
			long palaceProjectId;
			if (!AspenStringUtils.isNumeric(identifier)) {
				//Get the ID of the record based on the palace project id
				getProductIdForPalaceProjectIdStmt.setString(1, identifier);
				ResultSet getProductIdForPalaceProjectIdRS = getProductIdForPalaceProjectIdStmt.executeQuery();
				if (getProductIdForPalaceProjectIdRS.next()) {
					palaceProjectId = getProductIdForPalaceProjectIdRS.getLong("id");
				}else{
					logEntry.incErrors("Could not find palace project identifier " + identifier + " in the database");
					return;
				}
				getProductIdForPalaceProjectIdRS.close();
			}else{
				palaceProjectId = Long.parseLong(identifier);
			}
			getProductInfoStmt.setLong(1, palaceProjectId);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				byte[] rawResponseBytes = productRS.getBytes("rawResponse");
				if (rawResponseBytes == null){
					logEntry.incInvalidRecords(identifier);
					return;
				}
				String rawResponseString = new String(rawResponseBytes, StandardCharsets.UTF_8);
				JSONObject rawResponse = new JSONObject(rawResponseString);
				JSONObject metadata = rawResponse.getJSONObject("metadata");
				String collectionName = productRS.getString("collectionName");

				RecordInfo palaceProjectRecord = groupedWork.addRelatedRecord("palace_project", identifier);
				palaceProjectRecord.setRecordIdentifier("palace_project", identifier);

				String type = metadata.getString("@type");
				String formatCategory;
				String primaryFormat;
				switch (type) {
					case "http://bib.schema.org/Audiobook":
						formatCategory = "Audio Books";
						primaryFormat = "eAudiobook";
						break;
					case "http://schema.org/EBook":
						//TODO: May need to check the subjects to determine if this is a comic/graphic novel
						formatCategory = "eBook";
						primaryFormat = "eBook";
						break;
					default:
						logger.error("Unhandled Palace Project type " + type);
						formatCategory = "Other";
						primaryFormat = type;
						break;
				}

				palaceProjectRecord.addFormat(primaryFormat);
				palaceProjectRecord.addFormatCategory(formatCategory);

				long formatBoost = 1;
				try {
					formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_palace_project", primaryFormat, identifier));
				} catch (Exception e) {
					logger.warn("Could not translate format boost for " + primaryFormat + " create translation map format_boost_palace_project");
				}
				palaceProjectRecord.setFormatBoost(formatBoost);

				String title = productRS.getString("title");
				String subTitle = "";

				if (metadata.has("title")) {
					title = metadata.getString("title");
				}
				if (metadata.has("subtitle")){
					subTitle = metadata.getString("subtitle");
				}
				String fullTitle = title + " " + subTitle;
				fullTitle = fullTitle.trim();
				String sortableTitle = title;
				if (metadata.has("sortAs")){
					sortableTitle = metadata.getString("sortAs");
				}
				groupedWork.setTitle(title, subTitle, title, sortableTitle, primaryFormat, formatCategory);
				groupedWork.addFullTitle(fullTitle);

				String primaryAuthor = "";
				if (metadata.has("author")){
					JSONObject authorObject = metadata.getJSONObject("author");
					primaryAuthor = authorObject.getString("name");
					primaryAuthor = AspenStringUtils.swapFirstLastNames(primaryAuthor);
				}else if (metadata.has("publisher")){
					JSONObject publisherObject = metadata.getJSONObject("publisher");
					primaryAuthor = publisherObject.getString("name");
				}
				groupedWork.setAuthor(primaryAuthor);
				groupedWork.setAuthAuthor(primaryAuthor);
				groupedWork.setAuthorDisplay(primaryAuthor);

				//Note: Palace Project does not provide series information

				if (metadata.has("language")) {
					String languageCode = metadata.getString("language");
					String threeLetterLanguage = indexer.translateSystemValue("two_to_three_character_language_codes", languageCode, identifier);
					String language = indexer.translateSystemValue("language", threeLetterLanguage, identifier);
					palaceProjectRecord.setPrimaryLanguage(language);
					if (language.equalsIgnoreCase("English")){
						groupedWork.setLanguageBoost(10L);
					}else if (language.equalsIgnoreCase("Spanish")){
						groupedWork.setLanguageBoostSpanish(10L);
					}
				}

				if (metadata.has("publisher")) {
					String publisher;
					if (metadata.get("publisher") instanceof String) {
						publisher = metadata.getString("publisher");
						groupedWork.addPublisher(publisher);
					}else{
						publisher = metadata.getJSONObject("publisher").getString("name");
						groupedWork.addPublisher(publisher);
					}
					if (publisher != null) {
						palaceProjectRecord.setPublisher(publisher);
					}
				}

				if (metadata.has("published")) {
					String published = metadata.getString("published");
					String publicationYear = published.substring(0, 4);
					groupedWork.addPublicationDate(publicationYear);
					palaceProjectRecord.setPublicationDate(publicationYear);
				}

				if (metadata.has("description")) {
					groupedWork.addDescription(metadata.getString("description"), primaryFormat, formatCategory);
				}

				String fictionNonFiction = null;
				HashSet<String> generes = new HashSet<>();
				String audience = null;
				if (metadata.has("subject")) {
					JSONArray subjects = metadata.getJSONArray("subject");
					for (int i = 0; i < subjects.length(); i++) {
						JSONObject subjectObject = subjects.getJSONObject(i);
						String scheme = subjectObject.getString("scheme");
						switch (scheme) {
							case "http://librarysimplified.org/terms/fiction/":
								fictionNonFiction = subjectObject.getString("name");
								if (fictionNonFiction.equals("Nonfiction")) {
									fictionNonFiction = "Non Fiction";
								}
								break;
							case "http://librarysimplified.org/terms/genres/Simplified/":
								generes.add(subjectObject.getString("name"));
								break;
							case "http://schema.org/audience":
								audience = subjectObject.getString("name");
								if (audience.equalsIgnoreCase("Children")) {
									audience = "Juvenile";
								}else if (audience.equalsIgnoreCase("Adults Only")) {
									audience = "Adults";
								}
								break;
							case "http://schema.org/typicalAgeRange":
								//TODO: Do we want to apply this anywhere?
								break;
							default:
								logEntry.addNote("Unknown subject scheme " + scheme);
						}
					}
				}
				if (fictionNonFiction != null){
					groupedWork.addLiteraryForm(fictionNonFiction);
					groupedWork.addLiteraryFormFull(fictionNonFiction);
				}
				if (!generes.isEmpty()) {
					groupedWork.addGenre(generes);
					groupedWork.addGenreFacet(generes);
					groupedWork.addTopicFacet(generes);
					groupedWork.addTopic(generes);
				}
				if (audience == null) {
					groupedWork.addTargetAudience("Unknown");
					groupedWork.addTargetAudienceFull("Unknown");
				}else {
					groupedWork.addTargetAudience(audience);
					groupedWork.addTargetAudienceFull(audience);
				}

				if (metadata.has("narrator")) {
					String narrator;
					if (metadata.get("narrator") instanceof String) {
						narrator = metadata.getString("narrator");
					}else{
						narrator = metadata.getJSONObject("narrator").getString("name");
					}
					HashSet<String> artistsToAdd = new HashSet<>();
					HashSet<String> artistsWithRoleToAdd = new HashSet<>();
					artistsToAdd.add(narrator);
					artistsWithRoleToAdd.add(narrator +"|Narrator");
					groupedWork.addAuthor2(artistsToAdd);
					groupedWork.addAuthor2Role(artistsWithRoleToAdd);
					groupedWork.addKeywords(artistsToAdd);
				}

				String contentUrl = "";
				boolean available = true;
				if (rawResponse.has("links")) {
					JSONArray links = rawResponse.getJSONArray("links");
					for (int i = 0; i < links.length(); i++) {
						JSONObject linkObject = links.getJSONObject(i);
						if (linkObject.has("rel") && linkObject.get("rel").equals("http://opds-spec.org/acquisition/borrow")){
							contentUrl = linkObject.getString("href");

							if (linkObject.has("properties")) {
								JSONObject properties = linkObject.getJSONObject("properties");
								if (properties.has("availability")){
									JSONObject availability = properties.getJSONObject("availability");
									available = availability.getString("state").equals("available");
								}
							}

						}
					}
				}

				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setItemIdentifier(identifier);
				itemInfo.seteContentSource(collectionName);
				itemInfo.setIsEContent(true);
				itemInfo.seteContentUrl(contentUrl);
				itemInfo.setShelfLocation("Online " + collectionName);
				itemInfo.setDetailedLocation("Online " + collectionName);
				itemInfo.setCallNumber("Online " + collectionName);
				itemInfo.setSortableCallNumber("Online " + collectionName);
				itemInfo.setFormat(primaryFormat);
				itemInfo.setFormatCategory(formatCategory);
				//TODO: Check availability and update.  For now, assume 1 copy with unlimited use
				itemInfo.setNumCopies(1);
				itemInfo.setAvailable(available);
				itemInfo.setDetailedStatus("Available Online");
				itemInfo.setGroupedStatus("Available Online");
				itemInfo.setHoldable(false);
				itemInfo.setInLibraryUseOnly(false);

				Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
				itemInfo.setDateAdded(dateAdded);

				for (Scope scope : indexer.getScopes()) {
					boolean okToAdd = false;
					PalaceProjectScope palaceProjectScope = scope.getPalaceProjectScope();
					if (palaceProjectScope != null) {
						okToAdd = true;
					}
					if (okToAdd) {
						ScopingInfo scopingInfo = itemInfo.addScope(scope);
						groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);
						scopingInfo.setLibraryOwned(true);
						scopingInfo.setLocallyOwned(true);
					}
				}

				palaceProjectRecord.addItem(itemInfo);
			}
			productRS.close();
		}catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing Palace Project record " + identifier + " grouped work " + groupedWork.getId(), e);
		} catch (JSONException e) {
			logEntry.incErrors("Error parsing raw data for Palace Project record " + identifier, e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for Palace Project title " + identifier, e);
		}
	}
}
