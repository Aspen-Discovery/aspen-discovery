package com.turning_leaf_technologies.borrowbox;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;

class BorrowBoxExtractLogEntry extends BaseIndexingLogEntry {
	private Long logEntryId = null;
	private final long settingId;
	private int numProducts = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;
	private int numSkipped = 0;
	private int numAvailabilityChanges = 0;
	private int numMetadataChanges = 0;

	private PreparedStatement insertLogEntry;
	private PreparedStatement updateLogEntry;

	BorrowBoxExtractLogEntry(Connection dbConn, BorrowBoxSetting setting, Logger logger) {
		super(logger);
		this.settingId = setting.getId();

		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into borrowbox_extract_log (startTime, settingId) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE borrowbox_extract_log SET lastUpdate = ?, endTime = ?, notes = ?, numProducts = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numSkipped = ?, numDeleted = ?, numAvailabilityChanges = ?, numMetadataChanges = ?, numInvalidRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
		saveResults();
	}

	public synchronized boolean saveResults() {
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
				updateLogEntry.setInt(++curCol, numSkipped);
				updateLogEntry.setInt(++curCol, numDeleted);
				updateLogEntry.setInt(++curCol, numAvailabilityChanges);
				updateLogEntry.setInt(++curCol, numMetadataChanges);
				updateLogEntry.setInt(++curCol, numInvalidRecords);
				updateLogEntry.setLong(++curCol, logEntryId);
				updateLogEntry.executeUpdate();
			}
			return true;
		} catch (SQLException e) {
			if (logEntryId == null) {
				logger.error("Error creating borrowbox log entry", e);
			} else {
				logger.error("Error updating borrowbox log entry", e);
			}
			logger.info(this.toString());
			return false;
		}
	}

	public void setFinished() {
		this.endTime = new Date();
		this.addNote("Finished BorrowBox extraction");
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

	void incSkipped() {
		numSkipped++;
	}

	void incAvailabilityChanges() {
		numAvailabilityChanges++;
	}

	void incMetadataChanges() {
		numMetadataChanges++;
	}

	void setNumProducts(int size) {
		numProducts = size;
	}
}
