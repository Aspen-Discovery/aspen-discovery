package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.RbdigitalScope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashSet;

class RbdigitalMagazineProcessor {
	private GroupedWorkIndexer indexer;
	private Logger logger;

	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getMagazineIssuesStmt;
	private PreparedStatement getIssueAvailabilityStmt;

	private SimpleDateFormat dateFormatter = new SimpleDateFormat("M/d/yyyy");
	private SimpleDateFormat sortableDateFormatter = new SimpleDateFormat("yyyy/MM/dd");

	RbdigitalMagazineProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection dbConn, Logger logger) {
		this.indexer = groupedWorkIndexer;
		this.logger = logger;

		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT * from rbdigital_magazine where magazineId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getMagazineIssuesStmt = dbConn.prepareStatement("SELECT * from rbdigital_magazine_issue where magazineId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getIssueAvailabilityStmt = dbConn.prepareStatement("SELECT * FROM rbdigital_magazine_issue_availability where issueId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up rbdigital magazine processor", e);
		}
	}

	void processRecord(GroupedWorkSolr groupedWork, String identifier, BaseLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				if (productRS.getBoolean("deleted")) {
					logger.debug("Rbdigital magazine " + identifier + " was deleted, skipping");
					return;
				}

				//Setup basics of the grouped work
				JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));

				String title = productRS.getString("title");
				String formatCategory = "eBook";
				String primaryFormat = "eMagazine";
				groupedWork.setTitle(title, title, title, primaryFormat);

				String targetAudience = rawResponse.getString("audience");
				boolean isChildrens = false;
				if (targetAudience.equals("G")) {
					groupedWork.addTargetAudience("Juvenile");
					groupedWork.addTargetAudienceFull("Juvenile");
					isChildrens = true;
				} else if (targetAudience.contains("PG")) {
					groupedWork.addTargetAudience("Young Adult");
					groupedWork.addTargetAudienceFull("Adolescent (14-17)");
					groupedWork.addTargetAudience("Adult");
					groupedWork.addTargetAudienceFull("Adult");
				} else {
					groupedWork.addTargetAudience("Adult");
					groupedWork.addTargetAudienceFull("Adult");
				}
				long formatBoost = 1;
				try {
					formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_rbdigital", primaryFormat, identifier));
				} catch (Exception e) {
					logEntry.addNote("Could not translate format boost for " + primaryFormat + " create translation map format_boost_rbdigital");
				}

				String genre = rawResponse.getString("genre");
				HashSet<String> genresToAdd = new HashSet<>();
				HashSet<String> topicsToAdd = new HashSet<>();
				genresToAdd.add(genre);
				topicsToAdd.add(genre);
				groupedWork.addGenre(genresToAdd);
				groupedWork.addGenreFacet(genresToAdd);
				groupedWork.addTopicFacet(topicsToAdd);
				groupedWork.addTopic(topicsToAdd);

				groupedWork.addSubjects(topicsToAdd);

				groupedWork.addLiteraryForm("Non Fiction");
				groupedWork.addLiteraryFormFull("Non Fiction");

				String publisher = rawResponse.getString("publisher");
				groupedWork.addPublisher(publisher);

				//physical description?
				String description = rawResponse.getString("description");
				groupedWork.addDescription(description, primaryFormat);

				//Get issues for the magazine and add one record per issue
				getMagazineIssuesStmt.setString(1, identifier);
				ResultSet getMagazineIssuesRS = getMagazineIssuesStmt.executeQuery();
				while (getMagazineIssuesRS.next()) {
					String issueIdentifier = getMagazineIssuesRS.getString("issueId");

					RecordInfo rbdigitalRecord = groupedWork.addRelatedRecord("rbdigital_magazine", identifier + "_" + issueIdentifier);
					rbdigitalRecord.setRecordIdentifier("rbdigital_magazine", identifier + "_" + issueIdentifier);

					rbdigitalRecord.addFormat(primaryFormat);
					rbdigitalRecord.addFormatCategory(formatCategory);

					rbdigitalRecord.setPrimaryLanguage(productRS.getString("language"));

					rbdigitalRecord.setFormatBoost(formatBoost);

					ItemInfo itemInfo = new ItemInfo();
					itemInfo.setItemIdentifier(getMagazineIssuesRS.getString("issueId"));
					itemInfo.seteContentSource("RBdigital");
					itemInfo.setIsEContent(true);
					String coverDate = getMagazineIssuesRS.getString("coverDate");
					try {
						Date coverDateAsDate = dateFormatter.parse(coverDate);
						itemInfo.setCallNumber(coverDate);
						String sortableDate = sortableDateFormatter.format(coverDateAsDate);
						itemInfo.setShelfLocation("RBdigital");
						itemInfo.setDetailedLocation(sortableDate + " RBdigital");
						itemInfo.setSortableCallNumber(sortableDate);

						rbdigitalRecord.setEdition(coverDate);

						//publication date
						String releaseYear = coverDate.substring(coverDate.lastIndexOf("/") + 1);
						groupedWork.addPublicationDate(releaseYear);
					} catch (ParseException e) {
						logEntry.addNote("Unable to parse cover date " + e.toString());
					}

					itemInfo.setFormat(primaryFormat);
					itemInfo.setFormatCategory(formatCategory);
					//We don't currently have a way to determine how many copies are owned
					itemInfo.setNumCopies(1);

					Date dateAdded = null;
					try {
						dateAdded = dateFormatter.parse(getMagazineIssuesRS.getString("publishedOn"));
					} catch (ParseException e) {
						logEntry.addNote("Error parsing publication date for RBdigital magazine " + e.toString());
					}
					itemInfo.setDateAdded(dateAdded);

					//Get Issues
					long issueId = getMagazineIssuesRS.getLong("id");
					getIssueAvailabilityStmt.setLong(1, issueId);
					ResultSet getIssueAvailabilityRS = getIssueAvailabilityStmt.executeQuery();
					while (getIssueAvailabilityRS.next()){
						long settingId = getIssueAvailabilityRS.getLong("settingId");
						for (Scope scope : indexer.getScopes()) {
							boolean okToAdd = false;
							RbdigitalScope rbdigitalScope = scope.getRbdigitalScope();
							if (rbdigitalScope != null) {
								if (settingId == rbdigitalScope.getSettingId()){
									if (rbdigitalScope.isIncludeEMagazines()) {
										okToAdd = true;
									}
									if (rbdigitalScope.isRestrictToChildrensMaterial() && !isChildrens) {
										okToAdd = false;
									}
								}
							}
							if (okToAdd) {
								ScopingInfo scopingInfo = itemInfo.addScope(scope);
								boolean available = getIssueAvailabilityRS.getBoolean("isAvailable");
								if (available) {
									itemInfo.setDetailedStatus("Available Online");
								} else {
									itemInfo.setDetailedStatus("Checked Out");
								}
								scopingInfo.setAvailable(available);
								if (available) {
									scopingInfo.setStatus("Available Online");
									scopingInfo.setGroupedStatus("Available Online");
								} else {
									scopingInfo.setStatus("Checked Out");
									scopingInfo.setGroupedStatus("Checked Out");
								}
								scopingInfo.setHoldable(true);
								boolean owned = getIssueAvailabilityRS.getBoolean("isOwned");
								scopingInfo.setLibraryOwned(owned);
								scopingInfo.setLocallyOwned(owned);
								scopingInfo.setInLibraryUseOnly(false);
							}
						}
					}
					rbdigitalRecord.addItem(itemInfo);
				}
				getMagazineIssuesRS.close();
			}
			productRS.close();
		} catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing rbdigital magazine ", e);
		} catch (JSONException e) {
			logEntry.incErrors("Error parsing raw data for rbdigital magazine", e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for rbdigital magazine", e);
		}
	}

}
