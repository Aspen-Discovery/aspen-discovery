package com.turning_leaf_technologies.cron;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;

import org.apache.logging.log4j.Logger;

public class CronProcessLogEntry {
	private Long cronLogId;
	private Long logProcessId;
	private String processName;
	private Date startTime;
	private Date endTime;
	private int numErrors;
	private int numUpdates; 
	private ArrayList<String> notes = new ArrayList<>();
	
	public CronProcessLogEntry(Long cronLogId, String processName){
		this.cronLogId = cronLogId;
		this.processName = processName;
		this.startTime = new Date();
	}
	private Date getLastUpdate() {
		//The last time the log entry was updated so we can tell if a process is stuck
		return new Date();
	}
	
	public void incErrors() {
		numErrors++;
	}
	public void incUpdated() {
		numUpdates++;
	}
	public void addUpdates(int updates) {
		numUpdates += updates;
	}

	public void addNote(String note) {
		if (this.notes.size() < 5000){
			this.notes.add(note);
		}
	}
	
	private String getNotesHtml() {
		StringBuilder notesText = new StringBuilder("<ol class='cronProcessNotes'>");
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
				insertLogEntry = dbConn.prepareStatement("INSERT into cron_process_log (cronId, processName, startTime) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
				updateLogEntry = dbConn.prepareStatement("UPDATE cron_process_log SET lastUpdate = ?, endTime = ?, numErrors = ?, numUpdates = ?, notes = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
				statementsPrepared = true;
			}
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
			return false;
		}
		try{
			if (logProcessId == null){
				insertLogEntry.setLong(1, cronLogId);
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
				updateLogEntry.setString(5, getNotesHtml());
				updateLogEntry.setLong(6, logProcessId);
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
