package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.SideLoadSettings;
import com.turning_leaf_technologies.logging.BaseLogEntry;
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
	 */
	public SideLoadedRecordGrouper(String serverName, Connection dbConnection, SideLoadSettings settings, BaseLogEntry logEntry, Logger logger) {
		super(serverName, settings, dbConnection, logEntry, logger);
		this.settings = settings;
	}

	public String processMarcRecord(Record marcRecord, boolean primaryDataChanged, String originalGroupedWorkId) {
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord, settings);

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
}
