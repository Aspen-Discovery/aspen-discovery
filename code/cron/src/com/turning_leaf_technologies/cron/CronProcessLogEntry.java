package com.turning_leaf_technologies.cron;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;

public class CronProcessLogEntry implements BaseLogEntry {
	private Logger logger;
	private CronLogEntry cronLogEntry;
	private Long logProcessId;
	private String processName;
	private Date startTime;
	private Date endTime;
	private int numErrors;
	private int numSkipped;
	private int numUpdates;
	private StringBuilder notesText = new StringBuilder();
	private boolean maxNoteTextLengthReached = false;

	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;

	public CronProcessLogEntry(CronLogEntry cronLogEntry, String processName, Connection dbConn, Logger logger){
		this.cronLogEntry = cronLogEntry;
		this.processName = processName;
		this.startTime = new Date();
		this.logger = logger;

		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into cron_process_log (cronId, processName, startTime) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE cron_process_log SET lastUpdate = ?, endTime = ?, numErrors = ?, numUpdates = ?, numSkipped = ?, notes = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
	}
	private Date getLastUpdate() {
		//The last time the log entry was updated so we can tell if a process is stuck
		return new Date();
	}

	public synchronized void incErrors(String note){
		this.numErrors++;
		this.addNote("ERROR: " + note);
		cronLogEntry.incErrors();
		this.saveResults();
		logger.error(note);
	}
	public synchronized void incErrors(String note, Exception e){
		this.addNote("ERROR: " + note + " " + e.toString());
		this.numErrors++;
		cronLogEntry.incErrors();
		this.saveResults();
		logger.error(note, e);
	}
	public void incUpdated() {
		numUpdates++;
		if (numUpdates + numSkipped % 100 == 0) {
			this.saveResults();
		}
	}
	void addUpdates(int updates) {
		numUpdates += updates;
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
		cleanedNote = cleanedNote.replaceAll("<pre>", "<code>");
		cleanedNote = cleanedNote.replaceAll("</pre>", "</code>");
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
	
	public synchronized boolean saveResults() {
		try{
			if (logProcessId == null){
				insertLogEntry.setLong(1, cronLogEntry.getLogEntryId());
				insertLogEntry.setString(2,processName);
				insertLogEntry.setLong(3, startTime.getTime() / 1000);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()){
					logProcessId = generatedKeys.getLong(1);
				}
			}else{
				updateLogEntry.setLong(1, getLastUpdate().getTime() / 1000);
				if (endTime == null){
					updateLogEntry.setNull(2, java.sql.Types.INTEGER);
				}else{
					updateLogEntry.setLong(2, endTime.getTime() / 1000);
				}
				updateLogEntry.setLong(3, numErrors);
				updateLogEntry.setLong(4, numUpdates);
				updateLogEntry.setLong(5, numSkipped);
				updateLogEntry.setString(6, getNotesHtml());
				updateLogEntry.setLong(7, logProcessId);
				updateLogEntry.executeUpdate();
			}
			return true;
		} catch (SQLException e) {
			logger.error("Error saving cron process log", e);
			return false;
		}
	}
	public void setFinished() {
		this.endTime = new Date();
	}

	public void incSkipped() {
		this.numSkipped++;
		if (numUpdates + numSkipped % 100 == 0) {
			this.saveResults();
		}
	}
}
