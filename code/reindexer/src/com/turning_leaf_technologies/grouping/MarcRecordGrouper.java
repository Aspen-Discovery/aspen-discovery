package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.IlsExtractLogEntry;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.TranslationMap;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Set;
import java.util.regex.Pattern;

/**
 * A base class for setting title, author, and format for a MARC record
 * allows us to override certain information (especially format determination)
 * by library.
 */
public class MarcRecordGrouper extends BaseMarcRecordGrouper {
	private final IndexingProfile profile;
	private final String itemTag;
	private final int itemTagInt;
	private final boolean useEContentSubfield;
	private final char eContentDescriptor;
	/**
	 * Creates a record grouping processor that saves results to the database.
	 *
	 * @param dbConnection   - The Connection to the database
	 * @param profile        - The profile that we are grouping records for
	 * @param logger         - A logger to store debug and error messages to.
	 */
	public MarcRecordGrouper(String serverName, Connection dbConnection, IndexingProfile profile, BaseLogEntry logEntry, Logger logger) {
		super(serverName, profile, dbConnection, logEntry, logger);
		this.profile = profile;

		itemTag = profile.getItemTag();
		itemTagInt = profile.getItemTagInt();
		eContentDescriptor = profile.getEContentDescriptor();
		useEContentSubfield = profile.getEContentDescriptor() != ' ';

		super.setupDatabaseStatements(dbConnection);

		super.loadAuthorities(dbConnection);

		loadTranslationMaps(dbConnection);

	}

	private void loadTranslationMaps(Connection dbConnection) {
		try {
			PreparedStatement loadMapsStmt = dbConnection.prepareStatement("SELECT * FROM translation_maps where indexingProfileId = ?");
			PreparedStatement loadMapValuesStmt = dbConnection.prepareStatement("SELECT * FROM translation_map_values where translationMapId = ?");
			loadMapsStmt.setLong(1, profile.getId());
			ResultSet translationMapsRS = loadMapsStmt.executeQuery();
			while (translationMapsRS.next()){
				HashMap<String, String> translationMap = new HashMap<>();
				String mapName = translationMapsRS.getString("name");
				long translationMapId = translationMapsRS.getLong("id");

				loadMapValuesStmt.setLong(1, translationMapId);
				ResultSet mapValuesRS = loadMapValuesStmt.executeQuery();
				while (mapValuesRS.next()){
					String value = mapValuesRS.getString("value");
					String translation = mapValuesRS.getString("translation");

					translationMap.put(value, translation);
				}
				mapValuesRS.close();
				translationMaps.put(mapName, translationMap);
			}
			translationMapsRS.close();

			PreparedStatement getFormatMapStmt = dbConnection.prepareStatement("SELECT * from format_map_values WHERE indexingProfileId = ?");
			getFormatMapStmt.setLong(1, profile.getId());
			ResultSet formatMapRS = getFormatMapStmt.executeQuery();
			HashMap <String, String> formatMap = new HashMap<>();
			translationMaps.put("format", formatMap);
			HashMap <String, String> formatCategoryMap = new HashMap<>();
			translationMaps.put("formatCategory", formatCategoryMap);
			while (formatMapRS.next()){
				String format = formatMapRS.getString("value");
				formatMap.put(format.toLowerCase(), formatMapRS.getString("format"));
				formatCategoryMap.put(format.toLowerCase(), formatMapRS.getString("formatCategory"));
			}
			formatMapRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error loading translation maps", e);
		}

	}

	private static final Pattern overdrivePattern = Pattern.compile("(?i)^http://.*?lib\\.overdrive\\.com/ContentDetails\\.htm\\?id=[\\da-f]{8}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{12}$");

	private String getFormatFromItems(Record record, char formatSubfield) {
		List<DataField> itemFields = getDataFields(record, itemTagInt);
		for (DataField itemField : itemFields) {
			if (itemField.getSubfield(formatSubfield) != null) {
				String originalFormat = itemField.getSubfield(formatSubfield).getData().toLowerCase();
				if (translationMaps.get("formatCategory").containsKey(originalFormat)){
					String format = translateValue("formatCategory", originalFormat);
					String formatCategory = categoryMap.get(format.toLowerCase());
					if (formatCategory != null){
						return formatCategory;
					}else{
						logger.warn("Did not find a grouping category for format " + format.toLowerCase());
					}
				}else{
					logger.warn("Did not find a format category for format " + originalFormat);
				}
			}
		}
		return null;
	}

	public String processMarcRecord(Record marcRecord, boolean primaryDataChanged, String originalGroupedWorkId) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, profile);

		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWork workForTitle = setupBasicWorkForIlsRecord(marcRecord);

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle, primaryDataChanged, originalGroupedWorkId);
			return workForTitle.getPermanentId();
		}else{
			//The record is suppressed
			return null;
		}
	}

	protected String setGroupingCategoryForWork(Record marcRecord, GroupedWork workForTitle) {
		String groupingFormat;
		if (profile.getFormatSource().equals("item")){
			//get format from item
			groupingFormat = getFormatFromItems(marcRecord, profile.getFormat());
			if (groupingFormat == null || groupingFormat.length() == 0){
				//Do a bib level determination
				String format = getFormatFromBib(marcRecord);
				groupingFormat = categoryMap.get(formatsToFormatCategory.get(format.toLowerCase()));
				workForTitle.setGroupingCategory(groupingFormat);
			}else {
				workForTitle.setGroupingCategory(groupingFormat);
			}
		}else{
			groupingFormat = super.setGroupingCategoryForWork(marcRecord, workForTitle);
		}
		return groupingFormat;
	}

	public RecordIdentifier getPrimaryIdentifierFromMarcRecord(Record marcRecord, IndexingProfile indexingProfile){
		RecordIdentifier identifier = super.getPrimaryIdentifierFromMarcRecord(marcRecord, indexingProfile);

		if (indexingProfile.isDoAutomaticEcontentSuppression()) {
			//Check to see if the record is an overdrive record
			if (useEContentSubfield) {
				boolean allItemsSuppressed = true;

				List<DataField> itemFields = getDataFields(marcRecord, itemTagInt);
				int numItems = itemFields.size();
				for (DataField itemField : itemFields) {
					if (itemField.getSubfield(eContentDescriptor) != null) {
						//Check the protection types and sources
						String eContentData = itemField.getSubfield(eContentDescriptor).getData();
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
					//Don't return a primary identifier for this record (we will suppress the bib and just use OverDrive APIs)
					identifier.setSuppressed();
				}
			} else {
				//Check the 856 for an overdrive url
				if (identifier != null) {
					List<DataField> linkFields = getDataFields(marcRecord, 856);
					for (DataField linkField : linkFields) {
						if (linkField.getSubfield('u') != null) {
							//Check the url to see if it is from OverDrive
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

	protected String getFormatFromBib(Record record) {
		//Check to see if the title is eContent based on the 989 field
		if (useEContentSubfield) {
			List<DataField> itemFields = getDataFields(record, itemTag);
			for (DataField itemField : itemFields) {
				if (itemField.getSubfield(eContentDescriptor) != null) {
					//The record is some type of eContent.  For this purpose, we don't care what type.
					return "eContent";
				}
			}
		}
		return super.getFormatFromBib(record);
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
			Record marcRecord = indexer.loadMarcRecordFromDatabase(indexingProfile.getName(), recordIdentifier, logEntry);
			if (marcRecord != null) {
				//Pass null to processMarcRecord.  It will do the lookup to see if there is an existing id there.
				String groupedWorkId = processMarcRecord(marcRecord, false, null);
				if (originalGroupedWorkId == null || !originalGroupedWorkId.equals(groupedWorkId)) {
					logEntry.incChangedAfterGrouping();
					//process records to regroup after every 1000 changes so we keep up with the changes.
					if (logEntry.getNumChangedAfterGrouping() % 1000 == 0){
						indexer.processScheduledWorks(logEntry, false);
					}
				}
			}
		}

		//Finish reindexing anything that just changed
		if (logEntry.getNumChangedAfterGrouping() > 0){
			indexer.processScheduledWorks(logEntry, false);
		}

		indexingProfile.clearRegroupAllRecords(dbConn, logEntry);
		logEntry.addNote("Finished regrouping all records");
		logEntry.saveResults();
	}
}
