package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.SideLoadSettings;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.Record;

import java.sql.Connection;

public class SideLoadedRecordGrouper extends BaseMarcRecordGrouper {
	private SideLoadSettings settings;
	/**
	 * Creates a record grouping processor that saves results to the database.
	 *
	 * @param dbConnection   - The Connection to the database
	 * @param settings        - The profile that we are grouping records for
	 * @param logger         - A logger to store debug and error messages to.
	 * @param fullRegrouping - Whether or not we are doing full regrouping or if we are only grouping changes.
	 */
	public SideLoadedRecordGrouper(String serverName, Connection dbConnection, SideLoadSettings settings, Logger logger, boolean fullRegrouping) {
		super(serverName, settings, dbConnection, fullRegrouping, logger);
		this.settings = settings;
	}

	public String processMarcRecord(Record marcRecord, boolean primaryDataChanged) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, settings.getName());

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
}
