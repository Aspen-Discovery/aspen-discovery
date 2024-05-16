package org.aspen_discovery.grouping;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import org.aspen_discovery.format_classification.*;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;
import java.util.regex.Pattern;

/**
 * A base class for setting title, author, and format for a MARC record
 * allows us to override certain information (especially format determination)
 * by library.
 */
public class MarcRecordGrouper extends BaseMarcRecordGrouper {
	private final IndexingProfile profile;
	private PreparedStatement getExistingParentRecordsStmt;
	private PreparedStatement addParentRecordStmt;
	private PreparedStatement deleteParentRecordStmt;
	private PreparedStatement updateChildTitleStmt;

	/**
	 * Creates a record grouping processor that saves results to the database.
	 *
	 * @param dbConnection   - The Connection to the database
	 * @param profile        - The profile that we are grouping records for
	 * @param logger         - A logger to store debug and error messages to.
	 */
	public MarcRecordGrouper(String serverName, Connection dbConnection, IndexingProfile profile, BaseIndexingLogEntry logEntry, Logger logger) {
		super(serverName, profile, dbConnection, logEntry, logger);
		this.profile = profile;

		switch (profile.getIndexingClass()) {
			case "III":
				formatClassifier = new IIIRecordFormatClassifier(logger);
				break;
			case "Koha":
				formatClassifier = new KohaRecordFormatClassifier(logger);
				break;
			case "NashvilleCarlX":
				formatClassifier = new NashvilleRecordFormatClassifier(logger);
				break;
			default:
				formatClassifier = new IlsRecordFormatClassifier(logger);
				break;
		}

		super.setupDatabaseStatements(dbConnection);

		super.loadAuthorities(dbConnection);

		try {
			getExistingParentRecordsStmt = dbConnection.prepareStatement("SELECT * FROM record_parents where childRecordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addParentRecordStmt = dbConnection.prepareStatement("INSERT INTO record_parents (childRecordId, parentRecordId, childTitle) VALUES (?, ?, ?)");
			deleteParentRecordStmt = dbConnection.prepareStatement("DELETE FROM record_parents WHERE childRecordId = ? AND parentRecordId = ?");
			updateChildTitleStmt = dbConnection.prepareStatement("UPDATE record_parents set childTitle = ? where childRecordId = ? and parentRecordId = ?");
		}catch (SQLException e) {
			logEntry.incErrors("Error loading prepared statements for loading parent records", e);
		}

	}



	private static final Pattern overdrivePattern = Pattern.compile("(?i)^http://.*?lib\\.overdrive\\.com/ContentDetails\\.htm\\?id=[\\da-f]{8}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{12}$");

	public String processMarcRecord(org.marc4j.marc.Record marcRecord, boolean primaryDataChanged, String originalGroupedWorkId, GroupedWorkIndexer indexer) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, profile);

		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWork workForTitle = setupBasicWorkForIlsRecord(marcRecord);

			if (profile.isProcessRecordLinking()){
				//Check to see if we have any 773 fields which identify the
				HashSet<String> parentRecords = getParentRecordIds(marcRecord);
				if (!parentRecords.isEmpty()){
					String firstParentRecordId = null;
					//Add the parent records to the database
					try {
						getExistingParentRecordsStmt.setString(1, primaryIdentifier.getIdentifier());
						ResultSet existingParentsRS = getExistingParentRecordsStmt.executeQuery();
						HashMap<String, String> existingParentRecords = new HashMap<>();
						while (existingParentsRS.next()){
							existingParentRecords.put(existingParentsRS.getString("parentRecordId"), existingParentsRS.getString("childTitle"));
						}
						DataField titleField = marcRecord.getDataField(245);
						String title;
						if (titleField == null) {
							title = "";
						}else{
							//noinspection SpellCheckingInspection
							title = titleField.getSubfieldsAsString("abfgnp", " ");
						}

						//Loop through the records to see if they need to be added
						for (String parentRecordId : parentRecords){
							if (firstParentRecordId == null) {
								firstParentRecordId = parentRecordId;
							}
							if (existingParentRecords.containsKey(parentRecordId)){
								try{
									if (!existingParentRecords.get(parentRecordId).equals(title)){
										updateChildTitleStmt.setString(1, AspenStringUtils.trimTo(750, title));
										updateChildTitleStmt.setString(2, primaryIdentifier.getIdentifier());
										updateChildTitleStmt.setString(3, parentRecordId);
										updateChildTitleStmt.executeUpdate();
									}
									existingParentRecords.remove(parentRecordId);
								}catch (Exception e){
									logEntry.incErrors("Error updating parent record for " + primaryIdentifier.getIdentifier() + " in the database", e);
								}
							}else{
								try{
									addParentRecordStmt.setString(1, primaryIdentifier.getIdentifier());
									addParentRecordStmt.setString(2, parentRecordId);
									addParentRecordStmt.setString(3, AspenStringUtils.trimTo(750, title));
									addParentRecordStmt.executeUpdate();
								}catch (Exception e){
									logEntry.incErrors("Error adding parent record for " + primaryIdentifier.getIdentifier() + " in the database", e);
								}
							}
						}
						for (String oldParentRecordId : existingParentRecords.keySet()){
							try{
								deleteParentRecordStmt.setString(1, primaryIdentifier.getIdentifier());
								deleteParentRecordStmt.setString(2,oldParentRecordId);
								deleteParentRecordStmt.executeUpdate();
							}catch (Exception e){
								logEntry.incErrors("Error deleting parent record for " + primaryIdentifier.getIdentifier() + ", " + oldParentRecordId, e);
							}
						}
					}catch (Exception e){
						logEntry.incErrors("Error adding parent records to the database", e);
					}
					//MDN 9/24/22 even if the record has parents, we want to group it, so we have information about
					//the record, and it's items in the database.
					//return null;

					//if the record does have a parent, we're going to cheat a bit and use the info for the parent record when grouping
					if (firstParentRecordId != null) {
						org.marc4j.marc.Record parentMarcRecord = indexer.loadMarcRecordFromDatabase(profile.getName(), firstParentRecordId, logEntry);
						if (parentMarcRecord == null) {
							indexer.forceRecordReindex(primaryIdentifier.getType(), primaryIdentifier.getIdentifier());
						} else {
							workForTitle = setupBasicWorkForIlsRecord(parentMarcRecord);
						}
					}
				}
			}

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle, primaryDataChanged, originalGroupedWorkId);
			return workForTitle.getPermanentId();
		}else{
			//The record is suppressed
			return null;
		}
	}

	protected String setGroupingCategoryForWork(org.marc4j.marc.Record marcRecord, GroupedWork workForTitle) {
		String groupingFormat;
		if (profile.getFormatSource().equals("item")){
			//get format from item
			FormatInfo formatInfo = formatClassifier.getFirstFormatForRecord(marcRecord, profile, logEntry, logger);
			groupingFormat = formatInfo.getGroupingFormat(profile);
			workForTitle.setGroupingCategory(groupingFormat);
		}else{
			groupingFormat = super.setGroupingCategoryForWork(marcRecord, workForTitle);
		}
		return groupingFormat;
	}

	public RecordIdentifier getPrimaryIdentifierFromMarcRecord(org.marc4j.marc.Record marcRecord, IndexingProfile indexingProfile){
		RecordIdentifier identifier = super.getPrimaryIdentifierFromMarcRecord(marcRecord, indexingProfile);

		if (indexingProfile.isDoAutomaticEcontentSuppression()) {
			//Check to see if the record is an overdrive record
			if (profile.useEContentSubfield()) {
				boolean allItemsSuppressed = true;

				List<DataField> itemFields = getDataFields(marcRecord, profile.getItemTagInt());
				int numItems = itemFields.size();
				for (DataField itemField : itemFields) {
					if (itemField.getSubfield(profile.getEContentDescriptor()) != null) {
						//Check the protection types and sources
						String eContentData = itemField.getSubfield(profile.getEContentDescriptor()).getData();
						if (eContentData.indexOf(':') >= 0) {
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (!sourceType.equals("overdrive") && !sourceType.equals("hoopla")) {
								allItemsSuppressed = false;
							}
						} else {
							allItemsSuppressed = false;
						}
					} else {
						allItemsSuppressed = false;
					}
				}
				if (numItems == 0) {
					allItemsSuppressed = false;
				}
				if (allItemsSuppressed && identifier != null) {
					//Don't return a primary identifier for this record (we will suppress the bib and just use Libby APIs)
					identifier.setSuppressed();
				}
			} else {
				//Check the 856 for an overdrive url
				if (identifier != null) {
					List<DataField> linkFields = getDataFields(marcRecord, 856);
					for (DataField linkField : linkFields) {
						if (linkField.getSubfield('u') != null) {
							//Check the url to see if it is from Libby
							//TODO: Suppress other eContent records as well?
							String linkData = linkField.getSubfield('u').getData().trim();
							if (MarcRecordGrouper.overdrivePattern.matcher(linkData).matches()) {
								identifier.setSuppressed();
							}
						}
					}
				}
			}
		}

		if (identifier != null) {
			if (indexingProfile.getSuppressRecordsWithUrlsMatching() != null) {
				Set<String> linkFields = MarcUtil.getFieldList(marcRecord, "856u");
				for (String linkData : linkFields) {
					if (indexingProfile.getSuppressRecordsWithUrlsMatching().matcher(linkData).matches()) {
						identifier.setSuppressed();
					}
				}
			}
		}

		if (identifier != null && identifier.isValid()){
			return identifier;
		}else{
			return null;
		}
	}

	protected FormatInfo getFirstFormatFromBib(org.marc4j.marc.Record record, BaseIndexingSettings settings) {
		//Check to see if the title is eContent based on the 989 field
		if (profile.useEContentSubfield()) {
			List<DataField> itemFields = getDataFields(record, profile.getItemTagInt());
			for (DataField itemField : itemFields) {
				if (itemField.getSubfield(profile.getEContentDescriptor()) != null) {
					//The record is some type of eContent.  For this purpose, we don't care what type.
					FormatInfo eContentFormatInfo = new FormatInfo();
					eContentFormatInfo.format = "eContent";
					eContentFormatInfo.formatCategory = "eBook";
					return eContentFormatInfo;
				}
			}
		}
		return formatClassifier.getFirstFormatForRecord(record, settings, logEntry, logger);
	}

	public void regroupAllRecords(Connection dbConn, IndexingProfile indexingProfile, GroupedWorkIndexer indexer, IlsExtractLogEntry logEntry)  throws SQLException {
		logEntry.addNote("Starting to regroup all records");
		PreparedStatement getAllRecordsToRegroupStmt = dbConn.prepareStatement("SELECT ilsId from ils_records where source = ? and deleted = 0", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement getOriginalPermanentIdForRecordStmt = dbConn.prepareStatement("SELECT permanent_id from grouped_work_primary_identifiers join grouped_work on grouped_work_id = grouped_work.id WHERE type = ? and identifier = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		getAllRecordsToRegroupStmt.setString(1, indexingProfile.getName());
		ResultSet allRecordsToRegroupRS = getAllRecordsToRegroupStmt.executeQuery();
		while (allRecordsToRegroupRS.next()) {
			logEntry.incRecordsRegrouped();
			String recordIdentifier = allRecordsToRegroupRS.getString("ilsId");
			String originalGroupedWorkId;
			getOriginalPermanentIdForRecordStmt.setString(1, indexingProfile.getName());
			getOriginalPermanentIdForRecordStmt.setString(2, recordIdentifier);
			ResultSet getOriginalPermanentIdForRecordRS = getOriginalPermanentIdForRecordStmt.executeQuery();
			if (getOriginalPermanentIdForRecordRS.next()){
				originalGroupedWorkId = getOriginalPermanentIdForRecordRS.getString("permanent_id");
			}else{
				originalGroupedWorkId = "false";
			}
			org.marc4j.marc.Record marcRecord = indexer.loadMarcRecordFromDatabase(indexingProfile.getName(), recordIdentifier, logEntry);
			if (marcRecord != null) {
				//Pass null to processMarcRecord.  It will do the lookup to see if there is an existing id there.
				String groupedWorkId = processMarcRecord(marcRecord, false, null, indexer);
				if (originalGroupedWorkId == null || !originalGroupedWorkId.equals(groupedWorkId)) {
					logEntry.incChangedAfterGrouping();
					//process records to regroup after every 1000 changes, so we keep up with the changes.
					if (logEntry.getNumChangedAfterGrouping() % 1000 == 0){
						indexer.processScheduledWorks(logEntry, false, -1);
					}
				}
			}
		}

		//Finish reindexing anything that just changed
		if (logEntry.getNumChangedAfterGrouping() > 0){
			indexer.processScheduledWorks(logEntry, false, -1);
		}

		indexingProfile.clearRegroupAllRecords(dbConn, logEntry);
		logEntry.addNote("Finished regrouping all records");
		logEntry.saveResults();
	}

	public HashSet<String> getParentRecordIds(org.marc4j.marc.Record record) {
		List<DataField> analyticFields = record.getDataFields(773);
		HashSet<String> parentRecords = new HashSet<>();
		for (DataField analyticField : analyticFields){
			Subfield linkingSubfield = analyticField.getSubfield('w');
			if (linkingSubfield != null){
				//Establish a link and suppress this record
				String parentRecordId = linkingSubfield.getData();
				//Remove anything in parentheses
				parentRecordId = parentRecordId.replaceAll("\\(.*?\\)", "").trim();
				parentRecords.add(parentRecordId);
			}
		}
		return parentRecords;
	}


}
