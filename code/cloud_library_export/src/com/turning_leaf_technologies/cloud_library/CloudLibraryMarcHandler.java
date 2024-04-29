package com.turning_leaf_technologies.cloud_library;

import org.aspen_discovery.grouping.RecordGroupingProcessor;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;

import java.io.ByteArrayOutputStream;
import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import java.util.regex.Pattern;
import java.util.zip.CRC32;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Subfield;
import org.xml.sax.Attributes;
import org.xml.sax.helpers.DefaultHandler;

class CloudLibraryMarcHandler extends DefaultHandler {
	private final CloudLibraryExporter exporter;
	private PreparedStatement updateCloudLibraryItemStmt;
	private PreparedStatement updateCloudLibraryAvailabilityStmt;
	private PreparedStatement getExistingCloudLibraryAvailabilityStmt;

	private final MarcFactory marcFactory;
	private final boolean doFullReload;
	private final RecordGroupingProcessor recordGroupingProcessor;
	private final GroupedWorkIndexer indexer;
	private final Logger logger;
	private final CloudLibraryExtractLogEntry logEntry;
	private final HashMap<String, CloudLibraryTitle> existingRecords;
	private final long startTimeForLogging;
	private final long settingId;

	private int numDocuments = 0;
	private org.marc4j.marc.Record marcRecord;
	private String nodeContents = "";
	private String tag = "";
	private DataField dataField;
	private char subfieldCode;

	private static final CRC32 checksumCalculator = new CRC32();

	CloudLibraryMarcHandler(CloudLibraryExporter exporter, long settingId, HashMap<String, CloudLibraryTitle> existingRecords, boolean doFullReload, long startTimeForLogging, Connection dbConn, RecordGroupingProcessor recordGroupingProcessor, GroupedWorkIndexer indexer, CloudLibraryExtractLogEntry logEntry, Logger logger) {
		this.exporter = exporter;
		this.settingId = settingId;
		this.recordGroupingProcessor = recordGroupingProcessor;
		this.indexer = indexer;
		this.logEntry = logEntry;
		this.logger = logger;
		this.marcFactory = MarcFactory.newInstance();
		this.existingRecords = existingRecords;
		this.doFullReload = doFullReload;
		this.startTimeForLogging = startTimeForLogging;

		try {
			updateCloudLibraryItemStmt = dbConn.prepareStatement(
					"INSERT INTO cloud_library_title " +
							"(cloudLibraryId, title, subTitle, author, format, targetAudience, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
							"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
							"ON DUPLICATE KEY UPDATE title = VALUES(title), subTitle = VALUES(subTitle), author = VALUES(author), format = VALUES(format), " +
							"targetAudience = VALUES(targetAudience), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
			getExistingCloudLibraryAvailabilityStmt = dbConn.prepareStatement("SELECT id, rawChecksum, typeRawChecksum from cloud_library_availability WHERE cloudLibraryId = ?");
			updateCloudLibraryAvailabilityStmt = dbConn.prepareStatement(
					"INSERT INTO cloud_library_availability " +
							"(cloudLibraryId, settingId, totalCopies, sharedCopies, totalLoanCopies, totalHoldCopies, sharedLoanCopies, rawChecksum, rawResponse, lastChange, availabilityType, typeRawChecksum, typeRawResponse) " +
							"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
							"ON DUPLICATE KEY UPDATE totalCopies = VALUES(totalCopies), sharedCopies = VALUES(sharedCopies), " +
							"totalLoanCopies = VALUES(totalLoanCopies), totalHoldCopies = VALUES(totalHoldCopies), sharedLoanCopies = VALUES(sharedLoanCopies), " +
							"rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), " +
							"availabilityType = VALUES(availabilityType), typeRawChecksum = VALUES(typeRawChecksum), typeRawResponse = VALUES(typeRawResponse)");
		} catch (Exception e) {
			logger.error("Error connecting to aspen database", e);
			System.exit(1);
		}
	}

	public void startElement(String uri, String localName, String qName, Attributes attributes) {
		switch (qName) {
			case "marc:record":
				logEntry.incNumProducts(1);
				numDocuments++;

				marcRecord = marcFactory.newRecord();
				break;
			case "marc:controlfield":
				tag = attributes.getValue("tag");
				break;
			case "marc:datafield":
				tag = attributes.getValue("tag");
				String indicator1 = attributes.getValue("ind1");
				char ind1 = ' ';
				if (!indicator1.isEmpty()) {
					ind1 = indicator1.charAt(0);
				}
				String indicator2 = attributes.getValue("ind2");
				char ind2 = ' ';
				if (!indicator2.isEmpty()) {
					ind2 = indicator2.charAt(0);
				}
				dataField = marcFactory.newDataField(tag, ind1, ind2);
				break;
			case "marc:subfield":
				String subfieldCodeStr = attributes.getValue("code");
				subfieldCode = ' ';
				if (!subfieldCodeStr.isEmpty()) {
					subfieldCode = subfieldCodeStr.charAt(0);
				}
				break;
		}
	}

	public void characters(char[] ch, int start, int length) {
		nodeContents += new String(ch, start, length);
	}

	public void endElement(String uri, String localName, String qName) {
		switch (qName) {
			case "marc:record":
				processMarcRecord();
				break;
			case "marc:leader":
				marcRecord.setLeader(marcFactory.newLeader(nodeContents.trim()));
				break;
			case "marc:controlfield":
				marcRecord.addVariableField(marcFactory.newControlField(tag, nodeContents.trim()));
				break;
			case "marc:datafield":
				marcRecord.addVariableField(dataField);
				break;
			case "marc:subfield":
				dataField.addSubfield(marcFactory.newSubfield(subfieldCode, nodeContents.trim()));
				break;
		}
		nodeContents = "";
	}

	private void processMarcRecord() {
		ByteArrayOutputStream stream = new ByteArrayOutputStream();

		MarcWriter writer = new MarcStreamWriter(stream, "UTF-8", true);
		writer.write(marcRecord);
		String marcAsString;
		marcAsString = stream.toString(StandardCharsets.UTF_8);
		checksumCalculator.reset();
		checksumCalculator.update(marcAsString.getBytes());
		long itemChecksum = checksumCalculator.getValue();
		String cloudLibraryId = ((ControlField) marcRecord.getVariableField(1)).getData();
		logger.debug("processing " + cloudLibraryId);

		CloudLibraryTitle existingTitle = existingRecords.get(cloudLibraryId);
		boolean metadataChanged = false;
		if (existingTitle != null) {
			logger.debug("Record already exists");
			if (existingTitle.getChecksum() != itemChecksum || existingTitle.isDeleted()) {
				logger.debug("Updating item details");
				metadataChanged = true;
			}
			existingRecords.remove(cloudLibraryId);
		} else {
			logger.debug("Adding record " + cloudLibraryId);
			metadataChanged = true;
		}

		String title = MarcUtil.getFirstFieldVal(marcRecord, "245a");
		String subtitle = MarcUtil.getFirstFieldVal(marcRecord, "245b");
		String author = MarcUtil.getFirstFieldVal(marcRecord, "100a");

		String primaryLanguage = recordGroupingProcessor.getLanguageBasedOnMarcRecord(marcRecord);

		//Get availability for the title
		CloudLibraryAvailability availability = exporter.loadAvailabilityForRecord(cloudLibraryId);
		CloudLibraryAvailabilityType availabilityType = exporter.loadAvailabilityTypeForRecord(cloudLibraryId);
		if (availability == null) {
			logEntry.addNote("Did not load availability for " + title + " by " + author + " id " + cloudLibraryId);
			return;
		}
		if (availabilityType == null) {
			logEntry.addNote("Did not load availability type for " + title + " by " + author + " id " + cloudLibraryId);
			return;
		}

		boolean availabilityChanged = false;
		//Check availability checksum
		checksumCalculator.reset();
		String rawAvailabilityResponse = availability.getRawResponse();
		if (rawAvailabilityResponse == null) {
			rawAvailabilityResponse = "";
		}
		checksumCalculator.update(rawAvailabilityResponse.getBytes());
		long availabilityChecksum = checksumCalculator.getValue();
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
		//Same thing for availability type checksum
		checksumCalculator.reset();
		String rawAvailabilityTypeResponse = null;
		long availabilityTypeChecksum = 0;
		rawAvailabilityTypeResponse = availabilityType.getRawResponse();
		if (rawAvailabilityTypeResponse == null) {
			rawAvailabilityTypeResponse = "";
		}
		checksumCalculator.update(rawAvailabilityTypeResponse.getBytes());
		availabilityTypeChecksum = checksumCalculator.getValue();
		try {
			getExistingCloudLibraryAvailabilityStmt.setString(1, cloudLibraryId);
			ResultSet getExistingAvailabilityRS = getExistingCloudLibraryAvailabilityStmt.executeQuery();
			if (getExistingAvailabilityRS.next()) {
				long existingTypeChecksum = getExistingAvailabilityRS.getLong("typeRawChecksum");
				logger.debug("Availability type already exists");
				if (existingTypeChecksum != availabilityTypeChecksum) {
					logger.debug("Updating availability type details");
					availabilityChanged = true;
				}
			} else {
				logger.debug("Adding availability type for " + cloudLibraryId);
				availabilityChanged = true;
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading availability type", e);
		}


		Set<String> subjects = MarcUtil.getFieldList(marcRecord, "650a");
		String targetAudience = "Adult";
		if (subjects != null) {
			for (String subject : subjects) {
				if (subject.startsWith("JUVENILE")) {
					targetAudience = "Juvenile";
					break;
				}else if (subject.startsWith("YOUNG ADULT")) {
					targetAudience = "Young Adult";
					break;
				}
			}
		}

		Set<String> formatFields = MarcUtil.getFieldList(marcRecord, "538a");
		String format = null;
		for (String formatField : formatFields) {
			switch (formatField) {
				case "Format: MP3":
					format = "MP3";
					break;
				case "Format: Adobe EPUB":
				case "Format: Adobe EPUB3":
					format = "EPUB";
					break;
				case "Format: Adobe PDF":
					format = "PDF";
					break;
			}
			if (format != null) {
				break;
			}
		}
		if (format == null) {
			logger.error("Format was not found");
		}else if (format.equals("EPUB") || format.equals("PDF")) {
			ControlField fixedField008 = (ControlField) marcRecord.getVariableField(8);
			if (fixedField008 != null && fixedField008.getData().length() >= 25) {
				char formatCode;
				formatCode = fixedField008.getData().toUpperCase().charAt(24);
				if (formatCode == '6') {
					format = "eComic";
				}else if (fixedField008.getData().length() >= 26) {
					formatCode = fixedField008.getData().toUpperCase().charAt(25);
					if (formatCode == '6') {
						format = "eComic";
					}else if (fixedField008.getData().length() >= 27) {
						formatCode = fixedField008.getData().toUpperCase().charAt(26);
						if (formatCode == '6') {
							format = "eComic";
						}else if (fixedField008.getData().length() >= 28) {
							formatCode = fixedField008.getData().toUpperCase().charAt(27);
							if (formatCode == '6') {
								format = "eComic";
							}
						}
					}
				}
			}
			if (!format.equals("eComic")) {
				List<DataField> genreFormTerm = MarcUtil.getDataFields(marcRecord, 650);
				Iterator<DataField> fieldIterator = genreFormTerm.iterator();
				DataField field;
				while (fieldIterator.hasNext()) {
					field = fieldIterator.next();
					List<Subfield> subfields = field.getSubfields();
					for (Subfield subfield : subfields) {
						if (subfield.getCode() == 'a') {
							String subfieldData = subfield.getData().toLowerCase();
							if (subfieldData.contains("graphic novel")) {
								format = "eComic";
								if (existingTitle == null || existingTitle.getFormat() == null || !existingTitle.getFormat().equals(format)) {
									metadataChanged = true;
								}
								break;
							}
						}
					}
					if (format.equals("eComic")) {
						break;
					}
				}
			}
		}
		if (metadataChanged || doFullReload) {
			logEntry.incMetadataChanges();
			try {
				//Update the database
				updateCloudLibraryItemStmt.setString(1, cloudLibraryId);
				updateCloudLibraryItemStmt.setString(2, AspenStringUtils.trimTo(255, title));
				updateCloudLibraryItemStmt.setString(3, AspenStringUtils.trimTo(255, subtitle));
				updateCloudLibraryItemStmt.setString(4, AspenStringUtils.trimTo(255, author));
				updateCloudLibraryItemStmt.setString(5, format);
				updateCloudLibraryItemStmt.setString(6, targetAudience);
				updateCloudLibraryItemStmt.setLong(7, itemChecksum);
				updateCloudLibraryItemStmt.setString(8, marcAsString);
				updateCloudLibraryItemStmt.setLong(9, startTimeForLogging);
				updateCloudLibraryItemStmt.setLong(10, startTimeForLogging);
				int result = updateCloudLibraryItemStmt.executeUpdate();
				if (result == 1) {
					//A result of 1 indicates a new row was inserted
					logEntry.incAdded();
				}
			} catch (Exception e) {
				logger.error("Error processing titles", e);
			}
		}

		if (availabilityChanged || doFullReload) {
			try {
				logEntry.incAvailabilityChanges();
				updateCloudLibraryAvailabilityStmt.setString(1, cloudLibraryId);
				updateCloudLibraryAvailabilityStmt.setLong(2, settingId);
				updateCloudLibraryAvailabilityStmt.setLong(3, availability.getTotalCopies());
				updateCloudLibraryAvailabilityStmt.setLong(4, availability.getSharedCopies());
				updateCloudLibraryAvailabilityStmt.setLong(5, availability.getTotalLoanCopies());
				updateCloudLibraryAvailabilityStmt.setLong(6, availability.getTotalHoldCopies());
				updateCloudLibraryAvailabilityStmt.setLong(7, availability.getSharedLoanCopies());
				updateCloudLibraryAvailabilityStmt.setLong(8, availabilityChecksum);
				updateCloudLibraryAvailabilityStmt.setString(9, rawAvailabilityResponse);
				updateCloudLibraryAvailabilityStmt.setLong(10, startTimeForLogging);
				updateCloudLibraryAvailabilityStmt.setLong(11, availabilityType.getAvailabilityType());
				updateCloudLibraryAvailabilityStmt.setLong(12, availabilityTypeChecksum);
				updateCloudLibraryAvailabilityStmt.setString(13, rawAvailabilityTypeResponse);
				updateCloudLibraryAvailabilityStmt.executeUpdate();
			} catch (SQLException e) {
				logEntry.incErrors("Error saving availability", e);
			}
		}

		String groupedWorkId = null;
		if (metadataChanged || doFullReload) {
			groupedWorkId = groupCloudLibraryRecord(title, subtitle, author, format, primaryLanguage, cloudLibraryId);
		}
		if (metadataChanged || availabilityChanged || doFullReload) {
			logEntry.incUpdated();
			if (groupedWorkId == null) {
				groupedWorkId = recordGroupingProcessor.getPermanentIdForRecord("cloud_library", cloudLibraryId);
			}
			indexer.processGroupedWork(groupedWorkId);
		}
	}

	Pattern wordsInParensPattern = Pattern.compile("\\(.*?\\)", Pattern.CASE_INSENSITIVE);
	private String groupCloudLibraryRecord(String title, String subtitle, String author, String format, String primaryLanguage, String cloudLibraryId) {
		RecordIdentifier primaryIdentifier = new RecordIdentifier("cloud_library", cloudLibraryId);
		//cloudLibrary puts awards within parentheses, we need to remove all of those.
		title = wordsInParensPattern.matcher(title).replaceAll("");
		return recordGroupingProcessor.processRecord(primaryIdentifier, title, subtitle, author, format, primaryLanguage, true);
	}

	int getNumDocuments() {
		return numDocuments;
	}

	public void startDocument() {
		numDocuments = 0;
	}
}
