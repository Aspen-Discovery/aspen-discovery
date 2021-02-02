package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.List;
import java.util.regex.Pattern;

/**
 * A base class for setting title, author, and format for a MARC record
 * allows us to override certain information (especially format determination)
 * by library.
 */
public class MarcRecordGrouper extends BaseMarcRecordGrouper {
	private IndexingProfile profile;
	private String itemTag;
	private boolean useEContentSubfield;
	private char eContentDescriptor;
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
		}catch (Exception e){
			logEntry.incErrors("Error loading translation maps", e);
		}

	}

	private static Pattern overdrivePattern = Pattern.compile("(?i)^http://.*?lib\\.overdrive\\.com/ContentDetails\\.htm\\?id=[\\da-f]{8}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{12}$");

	private String getFormatFromItems(Record record, char formatSubfield) {
		List<DataField> itemFields = getDataFields(record, itemTag);
		for (DataField itemField : itemFields) {
			if (itemField.getSubfield(formatSubfield) != null) {
				String originalFormat = itemField.getSubfield(formatSubfield).getData().toLowerCase();
				String format = translateValue("item_format", originalFormat);
				if (format != null && !format.equals(originalFormat)){
					return format;
				}
			}
		}
		//We didn't get a format from the items, check the bib as backup
		String format = getFormatFromBib(record);
		format = categoryMap.get(formatsToFormatCategory.get(format.toLowerCase()));
		return format;
	}

	public String processMarcRecord(Record marcRecord, boolean primaryDataChanged) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, profile.getName(), profile.isDoAutomaticEcontentSuppression());

		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWorkBase workForTitle = setupBasicWorkForIlsRecord(marcRecord);

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle, primaryDataChanged);
			return workForTitle.getPermanentId();
		}else{
			//The record is suppressed
			return null;
		}
	}

	protected String setGroupingCategoryForWork(Record marcRecord, GroupedWorkBase workForTitle) {
		String groupingFormat;
		if (profile.getFormatSource().equals("item")){
			//get format from item
			groupingFormat = getFormatFromItems(marcRecord, profile.getFormat());
		}else{
			groupingFormat = super.setGroupingCategoryForWork(marcRecord, workForTitle);
		}
		return groupingFormat;
	}

	public RecordIdentifier getPrimaryIdentifierFromMarcRecord(Record marcRecord, String recordType, boolean doAutomaticEcontentSuppression){
		RecordIdentifier identifier = super.getPrimaryIdentifierFromMarcRecord(marcRecord, recordType);

		if (doAutomaticEcontentSuppression) {
			//Check to see if the record is an overdrive record
			if (useEContentSubfield) {
				boolean allItemsSuppressed = true;

				List<DataField> itemFields = getDataFields(marcRecord, itemTag);
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
					List<DataField> linkFields = getDataFields(marcRecord, "856");
					for (DataField linkField : linkFields) {
						if (linkField.getSubfield('u') != null) {
							//Check the url to see if it is from OverDrive
							//TODO: Suppress Rbdigital and Hoopla records as well?
							String linkData = linkField.getSubfield('u').getData().trim();
							if (MarcRecordGrouper.overdrivePattern.matcher(linkData).matches()) {
								identifier.setSuppressed();
							}
						}
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
}
