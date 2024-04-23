package com.turning_leaf_technologies.cloud_library;

import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.apache.logging.log4j.Logger;
import org.xml.sax.helpers.DefaultHandler;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.zip.CRC32;

class CloudLibraryEventHandler extends DefaultHandler {
	private final CloudLibraryExporter exporter;
	private PreparedStatement updateCloudLibraryAvailabilityStmt;
	private PreparedStatement getExistingCloudLibraryAvailabilityStmt;

	private final boolean doFullReload;
	private final RecordGroupingProcessor recordGroupingProcessor;
	private final GroupedWorkIndexer indexer;
	private final Logger logger;
	private final CloudLibraryExtractLogEntry logEntry;
	private final long startTimeForLogging;
	private String nodeContents = "";

	private static final CRC32 checksumCalculator = new CRC32();

	CloudLibraryEventHandler(CloudLibraryExporter exporter, boolean doFullReload, Long startTimeForLogging, Connection aspenConn, RecordGroupingProcessor recordGroupingProcessor, GroupedWorkIndexer groupedWorkIndexer, CloudLibraryExtractLogEntry logEntry, Logger logger) {
		this.exporter = exporter;
		this.recordGroupingProcessor = recordGroupingProcessor;
		this.indexer = groupedWorkIndexer;
		this.logEntry = logEntry;
		this.logger = logger;
		this.doFullReload = doFullReload;
		this.startTimeForLogging = startTimeForLogging;

		try {
			getExistingCloudLibraryAvailabilityStmt = aspenConn.prepareStatement("SELECT id, rawChecksum from cloud_library_availability WHERE cloudLibraryId = ? and settingId = " + exporter.getSettingsId());
			updateCloudLibraryAvailabilityStmt = aspenConn.prepareStatement(
					"INSERT INTO cloud_library_availability " +
							"(cloudLibraryId, settingId, totalCopies, sharedCopies, totalLoanCopies, totalHoldCopies, sharedLoanCopies, rawChecksum, rawResponse, lastChange) " +
							"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
							"ON DUPLICATE KEY UPDATE totalCopies = VALUES(totalCopies), sharedCopies = VALUES(sharedCopies), " +
							"totalLoanCopies = VALUES(totalLoanCopies), totalHoldCopies = VALUES(totalHoldCopies), sharedLoanCopies = VALUES(sharedLoanCopies), " +
							"rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange)");
		} catch (Exception e) {
			logger.error("Error connecting to aspen database", e);
			System.exit(1);
		}
	}

	public void characters(char[] ch, int start, int length) {
		nodeContents += new String(ch, start, length);
	}

	public void endElement(String uri, String localName, String qName) {
		if (qName.equals("ItemId")){
			updateAvailabilityForTitle(nodeContents.trim());
		}
		nodeContents = "";
	}

	private void updateAvailabilityForTitle(String cloudLibraryId) {
		//Get availability for the title
		CloudLibraryAvailability availability = exporter.loadAvailabilityForRecord(cloudLibraryId);
		if (availability == null) {
			logEntry.addNote("Did not load availability for id " + cloudLibraryId);
			return;
		}

		checksumCalculator.reset();
		String rawAvailabilityResponse = availability.getRawResponse();
		if (rawAvailabilityResponse == null) {
			rawAvailabilityResponse = "";
		}
		checksumCalculator.update(rawAvailabilityResponse.getBytes());
		long availabilityChecksum = checksumCalculator.getValue();
		boolean availabilityChanged = false;
		try {
			getExistingCloudLibraryAvailabilityStmt.setString(1, cloudLibraryId);
			ResultSet getExistingAvailabilityRS = getExistingCloudLibraryAvailabilityStmt.executeQuery();
			if (getExistingAvailabilityRS.next()) {
				long existingChecksum = getExistingAvailabilityRS.getLong("rawChecksum");
				logger.debug("Availability already exists");
				if (existingChecksum != availabilityChecksum) {
					logger.debug("Updating availability details");
					availabilityChanged = true;
				}
			} else {
				logger.debug("Adding availability for " + cloudLibraryId);
				availabilityChanged = true;
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading availability", e);
		}

		if (availabilityChanged || doFullReload) {
			try {
				logEntry.incAvailabilityChanges();
				updateCloudLibraryAvailabilityStmt.setString(1, cloudLibraryId);
				updateCloudLibraryAvailabilityStmt.setLong(2, exporter.getSettingsId());
				updateCloudLibraryAvailabilityStmt.setLong(3, availability.getTotalCopies());
				updateCloudLibraryAvailabilityStmt.setLong(4, availability.getSharedCopies());
				updateCloudLibraryAvailabilityStmt.setLong(5, availability.getTotalLoanCopies());
				updateCloudLibraryAvailabilityStmt.setLong(6, availability.getTotalHoldCopies());
				updateCloudLibraryAvailabilityStmt.setLong(7, availability.getSharedLoanCopies());
				updateCloudLibraryAvailabilityStmt.setLong(8, availabilityChecksum);
				updateCloudLibraryAvailabilityStmt.setString(9, rawAvailabilityResponse);
				updateCloudLibraryAvailabilityStmt.setLong(10, startTimeForLogging);
				updateCloudLibraryAvailabilityStmt.executeUpdate();
			} catch (SQLException e) {
				logEntry.incErrors("Error saving availability", e);
			}
		}

		if (availabilityChanged || doFullReload) {
			logEntry.incUpdated();
			String groupedWorkId = recordGroupingProcessor.getPermanentIdForRecord("cloud_library", cloudLibraryId);
			indexer.processGroupedWork(groupedWorkId);
		}
	}
}
