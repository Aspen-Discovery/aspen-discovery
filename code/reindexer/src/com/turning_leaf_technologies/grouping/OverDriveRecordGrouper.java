package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.regex.Pattern;

public class OverDriveRecordGrouper extends RecordGroupingProcessor {
	private PreparedStatement getOverDriveProductInfoStmt;

	public OverDriveRecordGrouper(Connection dbConnection, String serverName, BaseLogEntry logEntry, Logger logger) {
		super(dbConnection, serverName, logEntry, logger);

		try {
			getOverDriveProductInfoStmt = dbConnection.prepareStatement("SELECT mediaType, title, subtitle, series, primaryCreatorName from overdrive_api_products WHERE overdriveId = ?");
		} catch (SQLException e) {
			logEntry.incErrors("Unable to setup overdrive statements", e);
		}
	}

	Pattern wordsInParensPattern = Pattern.compile("\\(.*?\\)", Pattern.CASE_INSENSITIVE);
	public String processOverDriveRecord(String overdriveId) {
		try {
			getOverDriveProductInfoStmt.setString(1, overdriveId);
			ResultSet overDriveRecordRS = getOverDriveProductInfoStmt.executeQuery();
			if (overDriveRecordRS.next()) {
				String mediaType = overDriveRecordRS.getString("mediaType");
				String title = overDriveRecordRS.getString("title");
				title = wordsInParensPattern.matcher(title).replaceAll("");
				String subtitle = overDriveRecordRS.getString("subtitle");
				String series = overDriveRecordRS.getString("series");
				String author = overDriveRecordRS.getString("primaryCreatorName");

				return processOverDriveRecord(overdriveId, title, subtitle, series, author, mediaType);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error getting information about overdrive record for grouping", e);
		}
		return null;
	}

	private String processOverDriveRecord(String overdriveId, String title, String subtitle, String series, String author, String mediaType) {
		RecordIdentifier primaryIdentifier = new RecordIdentifier("overdrive", overdriveId);
		//Overdrive typically makes the subtitle the series and volume which we don't want for grouping
		if (subtitle != null && series != null && series.length() > 0 && subtitle.toLowerCase().contains(series.toLowerCase())) {
			subtitle = "";
		}
		//Overdrive typically makes the subtitle the series and volume which we don't want for grouping
		if (title != null && series != null && title.toLowerCase().endsWith("--" + series.toLowerCase())) {
			title = title.substring(0, title.length() - (series.length() + 2));
		}
		return processRecord(primaryIdentifier, title, subtitle, author, mediaType, true);
	}
}
