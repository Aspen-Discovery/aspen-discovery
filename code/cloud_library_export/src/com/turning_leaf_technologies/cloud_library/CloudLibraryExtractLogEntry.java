package com.turning_leaf_technologies.cloud_library;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;

import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

class CloudLibraryExtractLogEntry implements BaseIndexingLogEntry {
	private Long logEntryId = null;
	private final long settingId;
	private final Date startTime;
	private Date endTime;
	private final ArrayList<String> notes = new ArrayList<>();
	private int numProducts = 0;
	private int numErrors = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;
	private int numAvailabilityChanges = 0;
	private int numMetadataChanges = 0;
	private int numRegrouped = 0;
	private int numInvalidRecords = 0;
	private final Logger logger;

	CloudLibraryExtractLogEntry(Connection dbConn, long settingsId, Logger logger) {
		this.logger = logger;
		this.startTime = new Date();
		this.settingId = settingsId;
		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into cloud_library_export_log (settingId, startTime) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE cloud_library_export_log SET lastUpdate = ?, endTime = ?, notes = ?, numProducts = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numDeleted = ?, numAvailabilityChanges = ?, numMetadataChanges = ?, numRegrouped = ?, numInvalidRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
		saveResults();
	}

	private final SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	//Synchronized to prevent concurrent modification of the notes ArrayList
	public synchronized void addNote(String note) {
		Date date = new Date();
		this.notes.add(dateFormat.format(date) + " - " + note);
		saveResults();
	}

	private String getNotesHtml() {
		StringBuilder notesText = new StringBuilder("<ol class='cronNotes'>");
		for (String curNote : notes) {
			String cleanedNote = curNote;
			cleanedNote = cleanedNote.replaceAll("<pre>", "<code>");
			cleanedNote = cleanedNote.replaceAll("</pre>", "</code>");
			//Replace multiple line breaks
			cleanedNote = cleanedNote.replaceAll("(?:<br?>\\s*)+", "<br/>");
			cleanedNote = cleanedNote.replaceAll("<meta.*?>", "");
			cleanedNote = cleanedNote.replaceAll("<title>.*?</title>", "");
			notesText.append("<li>").append(cleanedNote).append("</li>");
		}
		notesText.append("</ol>");
		String returnText = notesText.toString();
		if (returnText.length() > 25000) {
			returnText = returnText.substring(0, 25000) + " more data was truncated";
		}
		return returnText;
	}

	private PreparedStatement insertLogEntry;
	private PreparedStatement updateLogEntry;

	public boolean saveResults() {
		try {
			if (logEntryId == null) {
				insertLogEntry.setLong(1, settingId);
				insertLogEntry.setLong(2, startTime.getTime() / 1000);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()) {
					logEntryId = generatedKeys.getLong(1);
				}
			} else {
				int curCol = 0;
				updateLogEntry.setLong(++curCol, new Date().getTime() / 1000);
				if (endTime == null) {
					updateLogEntry.setNull(++curCol, Types.INTEGER);
				} else {
					updateLogEntry.setLong(++curCol, endTime.getTime() / 1000);
				}
				updateLogEntry.setString(++curCol, getNotesHtml());
				updateLogEntry.setInt(++curCol, numProducts);
				updateLogEntry.setInt(++curCol, numErrors);
				updateLogEntry.setInt(++curCol, numAdded);
				updateLogEntry.setInt(++curCol, numUpdated);
				updateLogEntry.setInt(++curCol, numDeleted);
				updateLogEntry.setInt(++curCol, numAvailabilityChanges);
				updateLogEntry.setInt(++curCol, numMetadataChanges);
				updateLogEntry.setInt(++curCol, numRegrouped);
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

	public void setFinished() {
		this.endTime = new Date();
		this.addNote("Finished cloudLibrary extraction");
		this.saveResults();
	}

	public void incErrors(String note) {
		this.addNote("ERROR: " + note);
		numErrors++;
		this.saveResults();
		logger.error(note);
	}

	public void incErrors(String note, Exception e){
		this.addNote("ERROR: " + note + " " + e.toString());
		numErrors++;
		this.saveResults();
		logger.error(note, e);
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

	void incAvailabilityChanges() {
		numAvailabilityChanges++;
	}

	void incMetadataChanges() {
		numMetadataChanges++;
	}

	@SuppressWarnings("unused")
	void setNumProducts(int size) {
		numProducts = size;
	}

	boolean hasErrors() {
		return numErrors > 0;
	}

	@SuppressWarnings("SameParameterValue")
	void incNumProducts(int numResults) {
		this.numProducts += numResults;
	}

	public void incInvalidRecords(String invalidRecordId){
		this.numInvalidRecords++;
		this.addNote("Invalid Record found: " + invalidRecordId);
	}

	public void incRecordsRegrouped() {
		numRegrouped++;
		if (numRegrouped % 1000 == 0){
			this.saveResults();
		}
	}
}
