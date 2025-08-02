package com.turning_leaf_technologies.oai;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.commons.lang3.StringUtils;
import org.apache.logging.log4j.Logger;

import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

class OpenArchivesExtractLogEntry implements BaseLogEntry {
	private Long logEntryId = null;
	private final Date startTime;
	private Date endTime;
	private final ArrayList<String> notes = new ArrayList<>();
	private final String collectionName;
	private int numRecords = 0;
	private int numErrors = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;
	private int numSkipped = 0;
	private final Logger logger;
	private int saveCounter = 0;
	private static final int SAVE_FREQUENCY = 500;

    OpenArchivesExtractLogEntry(String collectionName, Connection dbConn, Logger logger){
		this.logger = logger;
		this.startTime = new Date();
		this.collectionName = collectionName;
		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into open_archives_export_log (collectionName, startTime) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE open_archives_export_log SET lastUpdate = ?, endTime = ?, notes = ?, numRecords = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numDeleted = ?, numSkipped = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log: ", e);
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
		for (String curNote : notes){
			String cleanedNote = curNote;
			cleanedNote = StringUtils.replace(cleanedNote,"<pre>", "<code>");
			cleanedNote = StringUtils.replace(cleanedNote,"</pre>", "</code>");
			//Replace multiple line breaks
			cleanedNote = cleanedNote.replaceAll("(?:<br?>\\s*)+", "<br/>");
			cleanedNote = cleanedNote.replaceAll("<meta.*?>", "");
			cleanedNote = cleanedNote.replaceAll("<title>.*?</title>", "");
			notesText.append("<li>").append(cleanedNote).append("</li>");
		}
		notesText.append("</ol>");
		String returnText = notesText.toString();
		if (returnText.length() > 25000){
			returnText = returnText.substring(0, 25000) + " more data was truncated";
		}
		return returnText;
	}

	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;
	public boolean saveResults() {
		try {
			if (logEntryId == null){
				insertLogEntry.setString(1, collectionName);
				insertLogEntry.setLong(2, startTime.getTime() / 1000);
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
				updateLogEntry.setInt(++curCol, numRecords);
				updateLogEntry.setInt(++curCol, numErrors);
				updateLogEntry.setInt(++curCol, numAdded);
				updateLogEntry.setInt(++curCol, numUpdated);
				updateLogEntry.setInt(++curCol, numDeleted);
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

	private void saveResultsPeriodically() {
		saveCounter++;
		if (saveCounter >= SAVE_FREQUENCY) {
			saveResults();
			saveCounter = 0;
		}
	}

	public void setFinished() {
		this.endTime = new Date();
		this.addNote("Finished Open Archives extraction");
		this.saveResults();
	}
	public void incErrors(String note){
		numErrors++;
		this.addNote("ERROR: " + note);
		this.saveResults();
		logger.error(note);
	}
	public void incErrors(String note, Exception e){
		this.addNote("ERROR: " + note + " " + e.toString());
		numErrors++;
		this.saveResults();
		logger.error(note, e);
	}
	void incAdded(){
		numAdded++;
		saveResultsPeriodically();
	}
	void incDeleted(){
		numDeleted++;
		saveResultsPeriodically();
	}
	void incSkipped(){
		numSkipped++;
		saveResultsPeriodically();
	}
	void incUpdated(){
		numUpdated++;
		saveResultsPeriodically();
	}
	@SuppressWarnings("unused")
	void setNumRecords(int size) {
		numRecords = size;
	}

	boolean hasErrors() {
		return numErrors > 0;
	}

	void incNumRecords() {
		this.numRecords++;
	}

}
