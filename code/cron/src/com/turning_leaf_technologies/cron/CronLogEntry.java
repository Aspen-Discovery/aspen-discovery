package com.turning_leaf_technologies.cron;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;

import org.apache.logging.log4j.Logger;

public class CronLogEntry {
	private Long logEntryId = null;
	private Date startTime;
	private Date endTime;
	private ArrayList<String> notes = new ArrayList<>();
	public CronLogEntry(){
		this.startTime = new Date();
	}
	private Date getLastUpdate() {
		//The last time the log entry was updated so we can tell if a process is stuck
		return new Date();
	}
	public Long getLogEntryId() {
		return logEntryId;
	}
	
	public void addNote(String note) {
		this.notes.add(note);
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

	private static boolean statementsPrepared = false;
	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;
	public boolean saveToDatabase(Connection dbConn, Logger logger) {
		try {
			if (!statementsPrepared){
				insertLogEntry = dbConn.prepareStatement("INSERT into cron_log (startTime) VALUES (?)", PreparedStatement.RETURN_GENERATED_KEYS);
				updateLogEntry = dbConn.prepareStatement("UPDATE cron_log SET lastUpdate = ?, endTime = ?, notes = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
				statementsPrepared = true;
			}
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
				updateLogEntry.setString(3, getNotesHtml());
				updateLogEntry.setLong(4, logEntryId);
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
	
}
