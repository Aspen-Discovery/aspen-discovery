package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import org.apache.logging.log4j.Logger;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

public class OverDriveRecordGrouper extends RecordGroupingProcessor {
	private PreparedStatement getOverDriveProductInfoStmt;

	public OverDriveRecordGrouper(Connection dbConnection, String serverName, Logger logger, boolean fullRegrouping) {
		super(dbConnection, serverName, logger);

		try {
			getOverDriveProductInfoStmt = dbConnection.prepareStatement("SELECT mediaType, title, subtitle, series, primaryCreatorName from overdrive_api_products WHERE overdriveId = ?");
		} catch (SQLException e) {
			logger.error("Unable to setup overdrive statments", e);
		}
	}

	public String processOverDriveRecord(String overdriveId) {
		try {
			getOverDriveProductInfoStmt.setString(1, overdriveId);
			ResultSet overDriveRecordRS = getOverDriveProductInfoStmt.executeQuery();
			if (overDriveRecordRS.next()) {
				String mediaType = overDriveRecordRS.getString("mediaType");
				String title = overDriveRecordRS.getString("title");
				String subtitle = overDriveRecordRS.getString("subtitle");
				String series = overDriveRecordRS.getString("series");
				String author = overDriveRecordRS.getString("primaryCreatorName");

				return processOverDriveRecord(overdriveId, title, subtitle, series, author, mediaType, true);
			}
		} catch (SQLException e) {
			logger.error("Error getting information about overdrive record for grouping", e);
		}
		return null;
	}

	String processOverDriveRecord(String overdriveId, String title, String subtitle, String series, String author, String mediaType, boolean primaryDataChanged) {
		RecordIdentifier primaryIdentifier = new RecordIdentifier("overdrive", overdriveId);
		//Overdrive typically makes the subtitle the series and volume which we don't want for grouping
		if (subtitle != null && series != null && subtitle.toLowerCase().contains(series.toLowerCase())) {
			subtitle = "";
		}
		//Overdrive typically makes the subtitle the series and volume which we don't want for grouping
		if (title != null && series != null && title.toLowerCase().endsWith("--" + series.toLowerCase())) {
			title = title.substring(0, title.length() - (series.length() + 2));
		}
		return processRecord(primaryIdentifier, title, subtitle, author, mediaType, primaryDataChanged);
	}
}
