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

public class CronLogEntry implements BaseLogEntry {
	private Long logEntryId = null;
	private Date startTime;
	private Date endTime;
	private int numErrors;
	private ArrayList<String> notes = new ArrayList<>();

	private Logger logger;
	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;

	public CronLogEntry(Connection dbConn, Logger logger){
		this.logger = logger;
		this.startTime = new Date();

		try {
			insertLogEntry = dbConn.prepareStatement("INSERT into cron_log (startTime) VALUES (?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = dbConn.prepareStatement("UPDATE cron_log SET lastUpdate = ?, endTime = ?, numErrors = ?, notes = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
	}
	private Date getLastUpdate() {
		//The last time the log entry was updated so we can tell if a process is stuck
		return new Date();
	}
	Long getLogEntryId() {
		return logEntryId;
	}

	private SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	@Override
	//Synchronized to prevent concurrent modification of the notes ArrayList
	public synchronized void addNote(String note) {
		Date date = new Date();
		this.notes.add(dateFormat.format(date) + " - " + note);
	}
	
	private String getNotesHtml() {
		StringBuilder notesText = new StringBuilder("<ol class='cronNotes'>");
		for (String curNote : notes){
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
		if (notesText.length() > 64000){
			notesText.substring(0, 64000);
		}
		return notesText.toString();
	}


	public boolean saveResults() {
		try {
			if (logEntryId == null){
				insertLogEntry.setLong(1, startTime.getTime() / 1000);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()){
					logEntryId = generatedKeys.getLong(1);
				}
			}else{
				updateLogEntry.setLong(1, getLastUpdate().getTime() / 1000);
				if (endTime == null){
					updateLogEntry.setNull(2, java.sql.Types.INTEGER);
				}else{
					updateLogEntry.setLong(2, endTime.getTime() / 1000);
				}
				updateLogEntry.setLong(3, numErrors);
				updateLogEntry.setString(4, getNotesHtml());
				updateLogEntry.setLong(5, logEntryId);
				updateLogEntry.executeUpdate();
			}
			return true;
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
			return false;
		}
	}
	public void setFinished() {
		this.endTime = new Date();
	}

	void incErrors(){
		numErrors++;
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
	
}
