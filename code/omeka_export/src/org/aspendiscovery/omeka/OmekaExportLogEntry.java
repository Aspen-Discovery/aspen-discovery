package org.aspendiscovery.omeka;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

public class OmekaExportLogEntry extends BaseIndexingLogEntry {
	private Long logEntryId = null;
	private final long settingId;
	private int numProducts = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;
	private int numSkipped = 0;

	private PreparedStatement insertLogEntry;
	private PreparedStatement updateLogEntry;

	OmekaExportLogEntry(Long settingId, Connection dbConn, Logger logger) {
		super(logger);
		this.settingId = settingId;
		try {
			//noinspection SqlResolve
			insertLogEntry = dbConn.prepareStatement("INSERT into omeka_export_log (startTime, settingId) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE omeka_export_log SET lastUpdate = ?, endTime = ?, notes = ?, numProducts = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numDeleted = ?, numSkipped = ?, numInvalidRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
		saveResults();
	}

	@Override
	public boolean saveResults() {
		try {
			if (logEntryId == null) {
				insertLogEntry.setLong(1, startTime.getTime() / 1000);
				insertLogEntry.setLong(2, settingId);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()) {
					logEntryId = generatedKeys.getLong(1);
				}
			} else {
				int curCol = 0;
				updateLogEntry.setLong(++curCol, new Date().getTime() / 1000);
				if (endTime == null) {
					updateLogEntry.setNull(++curCol, java.sql.Types.INTEGER);
				} else {
					updateLogEntry.setLong(++curCol, endTime.getTime() / 1000);
				}
				updateLogEntry.setString(++curCol, getNotesHtml());
				updateLogEntry.setInt(++curCol, numProducts);
				updateLogEntry.setInt(++curCol, numErrors);
				updateLogEntry.setInt(++curCol, numAdded);
				updateLogEntry.setInt(++curCol, numUpdated);
				updateLogEntry.setInt(++curCol, numDeleted);
				updateLogEntry.setInt(++curCol, numSkipped);
				updateLogEntry.setInt(++curCol, numInvalidRecords);
				updateLogEntry.setLong(++curCol, logEntryId);
				updateLogEntry.executeUpdate();
			}
			return true;
		} catch (SQLException e) {
			logger.error("Error creating updating log", e);
			return false;
		}
	}

	@Override
	public void setFinished() {
		this.endTime = new Date();
		this.addNote("Finished Omeka extraction");
		this.saveResults();
	}

	void incAdded() {
		numAdded++;
	}

	void incDeleted() {
		numDeleted++;
	}

	void incUpdated() {
		numUpdated++;
	}

	void incNumProducts(int size) {
		numProducts += size;
	}

	void incSkipped() {
		numSkipped++;
	}

	int getNumChanges() {
		return numUpdated + numDeleted + numAdded;
	}
}
