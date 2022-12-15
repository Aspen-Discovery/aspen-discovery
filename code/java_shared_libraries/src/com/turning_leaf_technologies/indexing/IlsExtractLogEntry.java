package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.commons.lang3.StringUtils;
import org.apache.logging.log4j.Logger;

import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

public class IlsExtractLogEntry implements BaseIndexingLogEntry {
	private Long logEntryId = null;
	private String indexingProfile;
	private Date startTime;
	private Date endTime;
	private int numRegrouped = 0;
	private int numChangedAfterGrouping = 0;
	private int numProducts = 0;
	private String currentId;
	private boolean isFullUpdate;
	private int numErrors = 0;
	private int numRecordsWithInvalidMarc = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;
	private int numSkipped = 0;
	private int numInvalidRecords = 0;
	private Logger logger;
	private StringBuilder notesText = new StringBuilder();
	private boolean maxNoteTextLengthReached = false;

	public IlsExtractLogEntry(Connection dbConn, String indexingProfile, Logger logger){
		this.logger = logger;
		this.startTime = new Date();
		this.indexingProfile = indexingProfile;
		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into ils_extract_log (startTime, indexingProfile) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE ils_extract_log SET lastUpdate = ?, isFullUpdate = ?, endTime = ?, notes = ?, numRegrouped =?, numChangedAfterGrouping = ?, numProducts = ?, numRecordsWithInvalidMarc = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numDeleted = ?, numSkipped = ?, currentId = ?, numInvalidRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
		notesText.append("<ol class='cronNotes'>");
		this.saveResults();
	}
	private SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	@Override
	//Synchronized to prevent concurrent modification of the notes ArrayList
	public synchronized void addNote(String note) {
		logger.info(note);
		if (maxNoteTextLengthReached){
			return;
		}
		Date date = new Date();
		String cleanedNote = note;
		cleanedNote = StringUtils.replace(cleanedNote, "<pre>", "<code>");
		cleanedNote = StringUtils.replace(cleanedNote, "</pre>", "</code>");
		//Replace multiple line breaks
		cleanedNote = cleanedNote.replaceAll("(?:<br?>\\s*)+", "<br/>");
		cleanedNote = cleanedNote.replaceAll("<meta.*?>", "");
		cleanedNote = cleanedNote.replaceAll("<title>.*?</title>", "");
		cleanedNote = "<li>" + dateFormat.format(date) + " - " + cleanedNote + "</li>";
		if (notesText.length() + cleanedNote.length() < 25000){
			notesText.append(cleanedNote);
		}else{
			cleanedNote = "<li>Additional Notes truncated</li>";
			maxNoteTextLengthReached = true;
		}
	}

	private String getNotesHtml() {
		return notesText.toString() + "</ol>";
	}
	
	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;
	@Override
	@SuppressWarnings("UnusedReturnValue")
	public synchronized boolean saveResults() {
		try {
			if (logEntryId == null){
				insertLogEntry.setLong(1, startTime.getTime() / 1000);
				insertLogEntry.setString(2, indexingProfile);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()){
					logEntryId = generatedKeys.getLong(1);
				}
			}else{
				int curCol = 0;
				updateLogEntry.setLong(++curCol, new Date().getTime() / 1000);
				updateLogEntry.setBoolean(++curCol, isFullUpdate);
				if (endTime == null){
					updateLogEntry.setNull(++curCol, Types.INTEGER);
				}else{
					updateLogEntry.setLong(++curCol, endTime.getTime() / 1000);
				}
				updateLogEntry.setString(++curCol, getNotesHtml());
				updateLogEntry.setInt(++curCol, numRegrouped);
				updateLogEntry.setInt(++curCol, numChangedAfterGrouping);
				updateLogEntry.setInt(++curCol, numProducts);
				updateLogEntry.setInt(++curCol, numRecordsWithInvalidMarc);
				updateLogEntry.setInt(++curCol, numErrors);
				updateLogEntry.setInt(++curCol, numAdded);
				updateLogEntry.setInt(++curCol, numUpdated);
				updateLogEntry.setInt(++curCol, numDeleted);
				updateLogEntry.setInt(++curCol, numSkipped);
				updateLogEntry.setString(++curCol, currentId);
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
		this.addNote("Finished ILS extraction");
		this.saveResults();
	}

	public void incErrors(String note) {
		this.addNote("ERROR: " + note);
		numErrors++;
		this.saveResults();
		logger.error(note);
	}

	public void incRecordsWithInvalidMarc(String note) {
		this.numRecordsWithInvalidMarc++;
		this.addNote(note);
		this.saveResults();
	}

	public void incErrors(String note, Exception e){
		this.addNote("ERROR: " + note + " " + e.toString());
		numErrors++;
		this.saveResults();
		logger.error(note, e);
	}
	public void incAdded(){
		numAdded++;
	}
	public void incDeleted(){
		numDeleted++;
		if (numDeleted % 1000 == 0){
			this.saveResults();
		}
	}
	public void incUpdated(){
		numUpdated++;
		if (numUpdated % 1000 == 0){
			this.saveResults();
		}
	}
	public void incSkipped(){
		numSkipped++;
	}
	public void incSkipped(long numSkipped){
		this.numSkipped += numSkipped;
	}
	public void setNumProducts(int size) {
		numProducts = size;
	}
	public int getNumProducts(){
		return numProducts;
	}
	public void incProducts(){
		numProducts++;
	}
	public void incRecordsRegrouped() {
		numRegrouped++;
		if (numRegrouped % 1000 == 0){
			this.saveResults();
		}
	}
	public void incChangedAfterGrouping(){
		numChangedAfterGrouping++;
	}
	public boolean hasErrors() {
		return numErrors > 0;
	}

	public int getNumDeleted() {
		return numDeleted;
	}

	public void setNumDeleted(int numDeleted) {
		this.numDeleted = numDeleted;
	}

	public int getNumChangedAfterGrouping() {
		return numChangedAfterGrouping;
	}

	public void setCurrentId(String currentId){
		this.currentId = currentId;
	}

	public void setIsFullUpdate(boolean runFullUpdate) {
		this.isFullUpdate = runFullUpdate;
	}

	public void incInvalidRecords(String invalidRecordId){
		this.numInvalidRecords++;
		this.addNote("Invalid Record found: " + invalidRecordId);
	}
}
