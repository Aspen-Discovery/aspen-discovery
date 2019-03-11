package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.HashMap;

/**
 * A base class for setting title, author, and format for a MARC record
 * allows us to override certain information (especially format determination)
 * by library.
 */
public class MarcRecordGrouper extends RecordGroupingProcessor{

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
	public MarcRecordGrouper(Connection dbConnection, IndexingProfile profile, Logger logger, boolean fullRegrouping) {
		super(logger, fullRegrouping);
		this.profile = profile;

		recordNumberTag = profile.getRecordNumberTag();
		recordNumberPrefix = profile.getRecordNumberPrefix();
		itemTag = profile.getItemTag();
		eContentDescriptor = profile.getEContentDescriptor();
		useEContentSubfield = profile.getEContentDescriptor() != ' ';


		super.setupDatabaseStatements(dbConnection);

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
			logger.error("Error loading translation maps", e);
		}

	}

	public boolean processMarcRecord(Record marcRecord, boolean primaryDataChanged) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, profile.getName(), profile.isDoAutomaticEcontentSuppression());

		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWorkBase workForTitle = setupBasicWorkForIlsRecord(marcRecord, profile.getFormatSource(), profile.getFormat(), profile.getSpecifiedFormatCategory());

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle, primaryDataChanged);
			return true;
		}else{
			//The record is suppressed
			return false;
		}
	}
}
