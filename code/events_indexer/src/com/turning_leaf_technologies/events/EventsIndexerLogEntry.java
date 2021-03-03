package com.turning_leaf_technologies.events;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;

import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

public class EventsIndexerLogEntry implements BaseLogEntry {
	private Long id;
	private Date startTime;
	private Date endTime;
	private ArrayList<String> notes = new ArrayList<>();
	private int numEvents = 0;
	private int numErrors = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numUpdated = 0;

	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;

	private Logger logger;
	private String name;

	EventsIndexerLogEntry(String name, Connection dbConn, Logger logger) {
		this.name = name;
		this.logger = logger;
		this.startTime = new Date();
		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into events_indexing_log (name, startTime) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE events_indexing_log SET lastUpdate = ?, endTime = ?, notes = ?, numEvents = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numDeleted = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
		saveResults();
	}

	private SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
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

	public boolean saveResults() {
		try {
			if (id == null) {
				insertLogEntry.setString(1, name);
				insertLogEntry.setLong(2, startTime.getTime() / 1000);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()) {
					id = generatedKeys.getLong(1);
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
				updateLogEntry.setInt(++curCol, numEvents);
				updateLogEntry.setInt(++curCol, numErrors);
				updateLogEntry.setInt(++curCol, numAdded);
				updateLogEntry.setInt(++curCol, numUpdated);
				updateLogEntry.setInt(++curCol, numDeleted);
				updateLogEntry.setLong(++curCol, id);
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
		this.addNote("Finished Events Indexing");
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

	boolean hasErrors() {
		return numErrors > 0;
	}

	void incNumEvents(int numResults) {
		this.numEvents += numResults;
	}

}
