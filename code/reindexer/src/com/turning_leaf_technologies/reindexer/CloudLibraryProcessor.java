package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.CloudLibraryScope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.ByteArrayInputStream;
import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashSet;

class CloudLibraryProcessor extends MarcRecordProcessor {

	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getAvailabilityStmt;

	CloudLibraryProcessor(GroupedWorkIndexer groupedWorkIndexer, String curType, Connection dbConn, Logger logger) {
		super(groupedWorkIndexer, "cloud_library", dbConn, logger);

		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT * from cloud_library_title where cloudLibraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getAvailabilityStmt = dbConn.prepareStatement("SELECT * from cloud_library_availability where cloudLibraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up cloudLibrary processor", e);
		}
	}

	public void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				if (productRS.getBoolean("deleted")) {
					logger.debug("cloudLibrary product " + identifier + " was deleted, skipping");
					return;
				}

				RecordInfo cloudLibraryRecord = groupedWork.addRelatedRecord("cloud_library", identifier);
				cloudLibraryRecord.setRecordIdentifier("cloud_library", identifier);

				String format = productRS.getString("format");
				String formatCategory;
				String primaryFormat;
				switch (format) {
					case "MP3":
						formatCategory = "Audio Books";
						cloudLibraryRecord.addFormatCategory("eBook");
						primaryFormat = "eAudiobook";
						break;
					case "EPUB":
					case "PDF":
						formatCategory = "eBook";
						primaryFormat = "eBook";
						break;
					default:
						logEntry.addNote("Unhandled cloud_library format " + format);
						formatCategory = format;
						primaryFormat = format;
						break;
				}
				cloudLibraryRecord.addFormat(primaryFormat);
				cloudLibraryRecord.addFormatCategory(formatCategory);

				String rawMarc = productRS.getString("rawResponse");
				MarcReader reader = new MarcPermissiveStreamReader(new ByteArrayInputStream(rawMarc.getBytes(StandardCharsets.UTF_8)), true, false, "UTF-8");
				if (reader.hasNext()) {
					Record marcRecord = reader.next();
					updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, marcRecord, new ArrayList<>(), identifier, primaryFormat, formatCategory);

					//Special processing for ILS Records
					String fullDescription = Util.getCRSeparatedString(MarcUtil.getFieldList(marcRecord, "520a"));
					groupedWork.addDescription(fullDescription, format, formatCategory);
					HashSet<RecordInfo> allRelatedRecords = new HashSet<>();
					allRelatedRecords.add(cloudLibraryRecord);
					loadEditions(groupedWork, marcRecord, allRelatedRecords);
					loadPhysicalDescription(groupedWork, marcRecord, allRelatedRecords);
					loadLanguageDetails(groupedWork, marcRecord, allRelatedRecords, identifier);
					loadPublicationDetails(groupedWork, marcRecord, allRelatedRecords);

					//TODO: cloudLibrary does not code target audience.  Load from subjects
				} else {
					logEntry.incErrors("Error getting MARC record for cloudLibrary record from database");
				}

				//Update to create one item per settings so we can have uniform availability at the item level
				getAvailabilityStmt.setString(1, identifier);
				ResultSet availabilityRS = getAvailabilityStmt.executeQuery();
				while (availabilityRS.next()) {
					long settingId = availabilityRS.getLong("settingId");
					//Ignore any settings that are null
					if (availabilityRS.wasNull()){
						continue;
					}

					ItemInfo itemInfo = new ItemInfo();
					itemInfo.setItemIdentifier(identifier + ":" + settingId); //Make sure we have an item identifier
					itemInfo.setFormat(primaryFormat);
					itemInfo.setFormatCategory(formatCategory);
					itemInfo.seteContentSource("cloudLibrary");
					itemInfo.setIsEContent(true);
					itemInfo.setShelfLocation("Online cloudLibrary Collection");
					itemInfo.setDetailedLocation("Online cloudLibrary Collection");
					itemInfo.setCallNumber("Online cloudLibrary");
					itemInfo.setSortableCallNumber("Online cloudLibrary");
					itemInfo.setHoldable(true);
					itemInfo.setInLibraryUseOnly(false);

					Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
					itemInfo.setDateAdded(dateAdded);

					itemInfo.setDetailedStatus("Available Online");

					boolean isChildrens = groupedWork.getTargetAudiences().contains("Juvenile");

					int totalCopies = availabilityRS.getInt("totalCopies");
					itemInfo.setNumCopies(totalCopies);
					int totalLoanCopies = availabilityRS.getInt("totalLoanCopies");
					boolean available = totalCopies > totalLoanCopies;
					itemInfo.setAvailable(available);
					if (available) {
						itemInfo.setDetailedStatus("Available Online");
						itemInfo.setGroupedStatus("Available Online");
					} else {
						itemInfo.setDetailedStatus("Checked Out");
						itemInfo.setGroupedStatus("Checked Out");
					}
					for (Scope scope : indexer.getScopes()) {
						boolean okToAdd = false;
						CloudLibraryScope cloudLibraryScope = scope.getCloudLibraryScope(settingId);
						if (cloudLibraryScope != null) {
							if (cloudLibraryScope.isIncludeEBooks() && formatCategory.equals("eBook")) {
								okToAdd = true;
							} else if (cloudLibraryScope.isIncludeEAudiobook() && primaryFormat.equals("eAudiobook")) {
								okToAdd = true;
							}
							if (cloudLibraryScope.isRestrictToChildrensMaterial() && !isChildrens) {
								okToAdd = false;
							}
						}
						if (okToAdd) {
							ScopingInfo scopingInfo = itemInfo.addScope(scope);
							groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);

							scopingInfo.setLibraryOwned(true);
							scopingInfo.setLocallyOwned(true);

						}
					}
					cloudLibraryRecord.addItem(itemInfo);
				}
			}
			productRS.close();
		} catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing cloudLibrary record ", e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for cloudLibrary title", e);
		}
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, Record record, String identifier) {
		//Unused, just calls updateGroupedWorkSolrDataBasedOnStandardMarcData
	}

}
