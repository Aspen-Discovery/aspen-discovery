package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.HashMap;

/**
 * A base class for setting title, author, and format for a MARC record
 * allows us to override certain information (especially format determination)
 * by library.
 *
 * Pika
 * User: Mark Noble
 * Date: 7/1/2015
 * Time: 2:05 PM
 */
class MarcRecordGrouper extends RecordGroupingProcessor{

	private IndexingProfile profile;
	/**
	 * Creates a record grouping processor that saves results to the database.
	 *
	 * @param dbConnection   - The Connection to the Pika database
	 * @param profile        - The profile that we are grouping records for
	 * @param logger         - A logger to store debug and error messages to.
	 * @param fullRegrouping - Whether or not we are doing full regrouping or if we are only grouping changes.
	 *                         Determines if old works are loaded at the beginning.
	 */
	MarcRecordGrouper(Connection dbConnection, IndexingProfile profile, Logger logger, boolean fullRegrouping) {
		super(logger, fullRegrouping);
		this.profile = profile;

		recordNumberTag = profile.recordNumberTag;
		recordNumberPrefix = profile.recordNumberPrefix;
		itemTag = profile.itemTag;
		eContentDescriptor = profile.eContentDescriptor;
		useEContentSubfield = profile.eContentDescriptor != ' ';


		super.setupDatabaseStatements(dbConnection);

		loadTranslationMaps(dbConnection);

	}

	private void loadTranslationMaps(Connection dbConnection) {
		try {
			PreparedStatement loadMapsStmt = dbConnection.prepareStatement("SELECT * FROM translation_maps where indexingProfileId = ?");
			PreparedStatement loadMapValuesStmt = dbConnection.prepareStatement("SELECT * FROM translation_map_values where translationMapId = ?");
			loadMapsStmt.setLong(1, profile.id);
			ResultSet translationMapsRS = loadMapsStmt.executeQuery();
			while (translationMapsRS.next()){
				HashMap<String, String> translationMap = new HashMap<>();
				String mapName = translationMapsRS.getString("name");
				Long translationMapId = translationMapsRS.getLong("id");

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
			logger.error("Error loading translation maps", e);
		}

	}

	boolean processMarcRecord(Record marcRecord, boolean primaryDataChanged) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, profile.name, profile.doAutomaticEcontentSuppression);

		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWorkBase workForTitle = setupBasicWorkForIlsRecord(marcRecord, profile.formatSource, profile.format, profile.specifiedFormatCategory);

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle, primaryDataChanged);
			return true;
		}else{
			//The record is suppressed
			return false;
		}
	}
}
