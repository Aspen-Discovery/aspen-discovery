package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;

import java.sql.*;
import java.util.Date;

class WebsiteIndexLogEntry extends BaseLogEntry {
	private Long logEntryId = null;
	private final String websiteName;
	private int numPages = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;
	private int numSkipped = 0;
	private int numInvalidPages = 0;

	WebsiteIndexLogEntry(String websiteName, Connection dbConn, Logger logger){
		super(logger);
		this.websiteName = websiteName;
		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into website_index_log (startTime, websiteName) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE website_index_log SET lastUpdate = ?, endTime = ?, notes = ?, numPages = ?, numAdded = ?, numUpdated = ?, numDeleted = ?, numErrors = ?, numInvalidPages = ?, numSkipped = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
		saveResults();
	}

	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;
	public boolean saveResults() {
		try {
			if (logEntryId == null){
				insertLogEntry.setLong(1, startTime.getTime() / 1000);
				insertLogEntry.setString(2, websiteName);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()){
					logEntryId = generatedKeys.getLong(1);
				}
			}else{
				int curCol = 0;
				updateLogEntry.setLong(++curCol, new Date().getTime() / 1000);
				if (endTime == null){
					updateLogEntry.setNull(++curCol, Types.INTEGER);
				}else{
					updateLogEntry.setLong(++curCol, endTime.getTime() / 1000);
				}
				updateLogEntry.setString(++curCol, getNotesHtml());
				updateLogEntry.setInt(++curCol, numPages);
				updateLogEntry.setInt(++curCol, numAdded);
				updateLogEntry.setInt(++curCol, numUpdated);
				updateLogEntry.setInt(++curCol, numDeleted);
				updateLogEntry.setInt(++curCol, numErrors);
				updateLogEntry.setInt(++curCol, numInvalidPages);
				updateLogEntry.setInt(++curCol, numSkipped);
				updateLogEntry.setLong(++curCol, logEntryId);
				updateLogEntry.executeUpdate();
			}
			return true;
		} catch (SQLException e) {
			logger.error("Error creating updating log", e);
			return false;
		}
	}
	public void setFinished() {
		this.endTime = new Date();
		this.addNote("Finished Website extraction for " + websiteName);
		this.saveResults();
	}
	void incAdded(){
		numAdded++;
		if ((numAdded + numUpdated + numSkipped) % 50 == 0){
			this.saveResults();
		}
	}
	void incDeleted(){
		numDeleted++;
		if ((numDeleted) % 50 == 0){
			this.saveResults();
		}
	}
	void incUpdated(){
		numUpdated++;
		if ((numAdded + numUpdated + numSkipped) % 50 == 0){
			this.saveResults();
		}
	}
	void incSkipped() {
		numSkipped++;
		if ((numAdded + numUpdated + numSkipped) % 50 == 0){
			this.saveResults();
		}
	}
	void incNumPages() {
		numPages++;
		if (numPages % 50 == 0){
			this.saveResults();
		}
	}

	public void incInvalidPages(String note) {
		this.addNote("Invalid Page: " + note);
		numInvalidPages++;
		this.saveResults();
	}
}
