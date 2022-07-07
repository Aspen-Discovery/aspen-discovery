package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.io.File;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;

abstract class MarcRecordProcessor {
	protected Logger logger;
	protected GroupedWorkIndexer indexer;
	protected BaseIndexingSettings settings;
	protected String profileType;
	private static final Pattern mpaaRatingRegex1 = Pattern.compile("(?:.*?)Rated\\s(G|PG-13|PG|R|NC-17|NR|X)(?:.*)", Pattern.CANON_EQ);
	private static final Pattern mpaaRatingRegex2 = Pattern.compile("(?:.*?)(G|PG-13|PG|R|NC-17|NR|X)\\sRated(?:.*)", Pattern.CANON_EQ);
	private static final Pattern mpaaRatingRegex3 = Pattern.compile("(?:.*?)MPAA rating:\\s(G|PG-13|PG|R|NC-17|NR|X)(?:.*)", Pattern.CANON_EQ);
	private static final Pattern mpaaNotRatedRegex = Pattern.compile("Rated\\sNR\\.?|Not Rated\\.?|NR");
	private static final Pattern dvdBlurayComboRegex = Pattern.compile("(.*blu-ray\\s?\\+\\s?dvd.*)|(.*blu-ray\\s?\\+blu-ray 3d\\s?\\+\\s?dvd.*)|(.*dvd\\s?\\+\\s?blu-ray.*)", Pattern.CASE_INSENSITIVE);
	private final HashSet<String> unknownSubjectForms = new HashSet<>();
	int numCharsToCreateFolderFrom;
	boolean createFolderFromLeadingCharacters;
	String individualMarcPath;
	String formatSource;
	String fallbackFormatField; //Only used for IlsRecordProcessor, but defined here
	String specifiedFormat;
	String specifiedFormatCategory;
	int specifiedFormatBoost;
	String treatUnknownLanguageAs = null;
	String treatUndeterminedLanguageAs = null;
	String customMarcFieldsToIndexAsKeyword = null;

	PreparedStatement addRecordToDBStmt;
	PreparedStatement marcRecordAsSuppressedNoMarcStmt;
	PreparedStatement getRecordSuppressionInformationStmt;

	MarcRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, Logger logger) {
		this.indexer = indexer;
		this.logger = logger;
		this.profileType = profileType;
		try {
			addRecordToDBStmt = dbConn.prepareStatement("INSERT INTO ils_records set ilsId = ?, source = ?, checksum = ?, dateFirstDetected = ?, deleted = 0, suppressed = 0, sourceData = COMPRESS(?), lastModified = ? ON DUPLICATE KEY UPDATE sourceData = VALUES(sourceData), lastModified = VALUES(lastModified)");
			marcRecordAsSuppressedNoMarcStmt = dbConn.prepareStatement("UPDATE ils_records set suppressedNoMarcAvailable = 1 where source = ? and ilsId = ?");
			getRecordSuppressionInformationStmt = dbConn.prepareStatement("SELECT suppressedNoMarcAvailable from ils_records where source = ? and ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		}catch (Exception e){
			indexer.getLogEntry().incErrors("Error setting up prepared statements for loading MARC from the DB", e);
		}
	}

	/**
	 * Load MARC record from disk based on identifier
	 * Then call updateGroupedWorkSolrDataBasedOnMarc to do the actual update of the work
	 *
	 * @param groupedWork the work to be updated
	 * @param identifier the identifier to load information for
	 * @param logEntry the log entry to store any errors
	 */
	public synchronized void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseLogEntry logEntry){
		//Check to be sure the record is not suppressed
		boolean isSuppressed = false;
		try {
			getRecordSuppressionInformationStmt.setString(1, this.profileType);
			getRecordSuppressionInformationStmt.setString(2, identifier);
			ResultSet getRecordSuppressionInformationRS = getRecordSuppressionInformationStmt.executeQuery();
			if (getRecordSuppressionInformationRS.next()){
				if (getRecordSuppressionInformationRS.getBoolean("suppressedNoMarcAvailable")){
					isSuppressed = true;
				}
			}
			getRecordSuppressionInformationRS.close();
		}catch (Exception e){
			logEntry.incErrors("Error loading suppression information for record", e);
		}
		if (!isSuppressed) {
			Record record = indexer.loadMarcRecordFromDatabase(this.profileType, identifier, logEntry);
			if (record == null) {
				record = loadMarcRecordFromDisk(identifier, logEntry);
				if (record != null) {
					indexer.saveMarcRecordToDatabase(settings, identifier, record);
				} else {
					try {
						//We don't have data for this MARC record, mark it as suppressed for not having MARC data
						marcRecordAsSuppressedNoMarcStmt.setString(1, this.profileType);
						marcRecordAsSuppressedNoMarcStmt.setString(2, identifier);
						marcRecordAsSuppressedNoMarcStmt.executeUpdate();
					} catch (SQLException e) {
						logEntry.incErrors("Error marking record as suppressed for not having MARC", e);
					}
				}
			}

			if (record != null) {
				try {
					updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
				} catch (Exception e) {
					logEntry.incErrors("Error updating solr based on marc record", e);
				}
			}
		}
	}

	private Record loadMarcRecordFromDisk(String identifier, BaseLogEntry logEntry) {
		String individualFilename = getFileForIlsRecord(identifier);
		return MarcUtil.readMarcRecordFromFile(new File(individualFilename), logEntry);
	}

	private String getFileForIlsRecord(String recordNumber) {
		StringBuilder shortId = new StringBuilder(recordNumber.replace(".", ""));
		while (shortId.length() < 9) {
			shortId.insert(0, "0");
		}

		String subFolderName;
		if (createFolderFromLeadingCharacters) {
			subFolderName = shortId.substring(0, numCharsToCreateFolderFrom);
		} else {
			subFolderName = shortId.substring(0, shortId.length() - numCharsToCreateFolderFrom);
		}

		String basePath = individualMarcPath + "/" + subFolderName;
		return basePath + "/" + shortId + ".mrc";
	}

	protected void loadSubjects(AbstractGroupedWorkSolr groupedWork, Record record){
		List<DataField> subjectFields = MarcUtil.getDataFields(record, new int[]{600, 610, 611, 630, 648, 650, 651, 655, 690});

		HashSet<String> subjects = new HashSet<>();
		for (DataField curSubjectField : subjectFields){
			switch (curSubjectField.getTag()) {
				case "600": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if ((curSubfield.getCode() >= 'a' && curSubfield.getCode() <= 'h') ||
								(curSubfield.getCode() >= 'j' && curSubfield.getCode() <= 'v') ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());

							groupedWork.addTopic(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'a' || curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'd') {
							groupedWork.addEra(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "610": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if ((curSubfield.getCode() >= 'a' && curSubfield.getCode() <= 'h') ||
								(curSubfield.getCode() >= 'j' && curSubfield.getCode() <= 'v') ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());

							groupedWork.addTopic(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "611": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if (curSubfield.getCode() == 'a' ||
								(curSubfield.getCode() >= 'c' && curSubfield.getCode() <= 'h') ||
								(curSubfield.getCode() >= 'k' && curSubfield.getCode() <= 'l') ||
								curSubfield.getCode() == 'n' ||
								curSubfield.getCode() == 'p' ||
								curSubfield.getCode() == 's' ||
								(curSubfield.getCode() >= 'p' && curSubfield.getCode() <= 'v') ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());

							groupedWork.addTopic(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "630": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if (curSubfield.getCode() == 'a' ||
								curSubfield.getCode() == 'b' ||
								(curSubfield.getCode() >= 'f' && curSubfield.getCode() <= 'h') ||
								(curSubfield.getCode() >= 'k' && curSubfield.getCode() <= 'p') ||
								(curSubfield.getCode() >= 'r' && curSubfield.getCode() <= 't') ||
								curSubfield.getCode() >= 'v' ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());

							groupedWork.addTopic(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "648": {
					String curSubject = "";
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if (curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'a' || curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
						}
					}
					subjects.add(curSubject.replaceAll("[|]", " -- "));
					break;
				}
				case "650": {
					boolean isLCSubject = true;
					boolean isBisacSubject = false;
					if (curSubjectField.getIndicator2() == '0' || curSubjectField.getIndicator2() == '1') {
						if (curSubjectField.getSubfield('2') != null) {
							if (curSubjectField.getSubfield('2').getData().equals("bisacsh") ||
									curSubjectField.getSubfield('2').getData().equals("bisacmt") ||
									curSubjectField.getSubfield('2').getData().equals("bisacrt")) {
								isLCSubject = false;
								isBisacSubject = true;
							}
						}
					} else {
						isLCSubject = false;
						if (curSubjectField.getSubfield('2') != null) {
							if (curSubjectField.getSubfield('2').getData().equals("bisacsh") ||
									curSubjectField.getSubfield('2').getData().equals("bisacmt") ||
									curSubjectField.getSubfield('2').getData().equals("bisacrt")) {
								isBisacSubject = true;
							}
						}
					}
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if ((curSubfield.getCode() >= 'a' && curSubfield.getCode() <= 'e') ||
								curSubfield.getCode() >= 'v' ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());

							groupedWork.addTopic(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'a' || curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
							if (isLCSubject) {
								groupedWork.addLCSubject(curSubfield.getData());
							} else if (isBisacSubject) {
								groupedWork.addBisacSubject(curSubfield.getData());
							}
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
						} else if (curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "651": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if ((curSubfield.getCode() >= 'a' && curSubfield.getCode() <= 'e') ||
								curSubfield.getCode() >= 'v' ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());

							groupedWork.addTopic(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
							groupedWork.addGeographic(curSubfield.getData());
						} else if (curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
							groupedWork.addGeographic(curSubfield.getData());
						} else if (curSubfield.getCode() == 'a' || curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
							groupedWork.addGeographic(curSubfield.getData());
						} else if (curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
							groupedWork.addGeographic(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "655": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if ((curSubfield.getCode() >= 'a' && curSubfield.getCode() <= 'c') ||
								curSubfield.getCode() >= 'v' ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());
						}
						if (curSubfield.getCode() == 'x') {
							groupedWork.addTopicFacet(curSubfield.getData());
							groupedWork.addGenre(curSubfield.getData());
						} else if (curSubfield.getCode() == 'a' || curSubfield.getCode() == 'v') {
							groupedWork.addGenreFacet(curSubfield.getData());
							groupedWork.addGenre(curSubfield.getData());
						} else if (curSubfield.getCode() == 'z') {
							groupedWork.addGeographicFacet(curSubfield.getData());
							groupedWork.addGenre(curSubfield.getData());
						} else if (curSubfield.getCode() == 'y') {
							groupedWork.addEra(curSubfield.getData());
							groupedWork.addGenre(curSubfield.getData());
						} else if (curSubfield.getCode() == 'b' || curSubfield.getCode() == 'x') {
							groupedWork.addGenre(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
				case "690": {
					StringBuilder curSubject = new StringBuilder();
					for (Subfield curSubfield : curSubjectField.getSubfields()) {
						if (curSubfield.getCode() == 'a' ||
								(curSubfield.getCode() >= 'x' && curSubfield.getCode() <= 'z')) {
							if (curSubject.length() > 0) curSubject.append(" -- ");
							curSubject.append(curSubfield.getData());
							groupedWork.addTopic(curSubfield.getData());
						}
					}
					subjects.add(curSubject.toString().replaceAll("[|]", " -- "));
					break;
				}
			}
		}
		groupedWork.addSubjects(subjects);

	}

	void updateGroupedWorkSolrDataBasedOnStandardMarcData(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier, String format, String formatCategory) {
		loadTitles(groupedWork, record, format, formatCategory);
		loadAuthors(groupedWork, record, identifier);
		loadSubjects(groupedWork, record);

		List<DataField> seriesFields = MarcUtil.getDataFields(record, 830);
		for (DataField seriesField : seriesFields){
			String series = StringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "anp"," ")).toString();
			//Remove anything in parenthesis since it's normally just the format
			series = series.replaceAll("\\s+\\(.*?\\)", "");
			//Remove the word series at the end since this gets cataloged inconsistently
			series = series.replaceAll("(?i)\\s+series$", "");
			String volume = "";
			if (seriesField.getSubfield('v') != null){
				//Separate out the volume so we can link specially
				volume = seriesField.getSubfield('v').getData();
			}
			groupedWork.addSeriesWithVolume(series, volume);
		}
		seriesFields = MarcUtil.getDataFields(record, 800);
		for (DataField seriesField : seriesFields){
			String series = StringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "pqt","")).toString();
			//Remove anything in parenthesis since it's normally just the format
			series = series.replaceAll("\\s+\\(.*?\\)", "");
			//Remove the word series at the end since this gets cataloged inconsistently
			series = series.replaceAll("(?i)\\s+series$", "");

			String volume = "";
			if (seriesField.getSubfield('v') != null){
				//Separate out the volume so we can link specially
				volume = seriesField.getSubfield('v').getData();
			}
			groupedWork.addSeriesWithVolume(series, volume);
		}


		groupedWork.addSeries(MarcUtil.getFieldList(record, "830ap:800pqt"));
		groupedWork.addSeries2(MarcUtil.getFieldList(record, "490a"));
		groupedWork.addDateSpan(MarcUtil.getFieldList(record, "362a"));
		groupedWork.addContents(MarcUtil.getFieldList(record, "505a:505t"));
		groupedWork.addIssns(MarcUtil.getFieldList(record, "022a"));
		groupedWork.addOclcNumbers(MarcUtil.getFieldList(record, "035a"));
		groupedWork.addIsbns(MarcUtil.getFieldList(record, "020a"), format);
		List<DataField> upcFields = MarcUtil.getDataFields(record, 24);
		for (DataField upcField : upcFields){
			if (upcField.getIndicator1() == '1' && upcField.getSubfield('a') != null){
				groupedWork.addUpc(upcField.getSubfield('a').getData());
			}
		}

		loadAwards(groupedWork, record);
		loadBibCallNumbers(groupedWork, record, identifier);
		loadLiteraryForms(groupedWork, record, printItems, identifier);
		loadTargetAudiences(groupedWork, record, printItems, identifier);
		loadFountasPinnell(groupedWork, record);
		loadLexileScore(groupedWork, record);
		groupedWork.addMpaaRating(getMpaaRating(record));
		groupedWork.addKeywords(MarcUtil.getAllSearchableFields(record, 100, 900));
		groupedWork.addKeywords(MarcUtil.getCustomSearchableFields(record, customMarcFieldsToIndexAsKeyword));
	}

	private static final Pattern lexileMatchingPattern = Pattern.compile("(AD|NC|HL|IG|GN|BR|NP)(\\d+)");
	private void loadLexileScore(AbstractGroupedWorkSolr groupedWork, Record record) {
		List<DataField> targetAudiences = MarcUtil.getDataFields(record, 521);
		for (DataField targetAudience : targetAudiences){
			Subfield subfieldA = targetAudience.getSubfield('a');
			Subfield subfieldB = targetAudience.getSubfield('b');
			if (subfieldA != null && subfieldB != null){
				if (subfieldB.getData().toLowerCase().startsWith("lexile")){
					String lexileValue = subfieldA.getData();
					if (lexileValue.endsWith("L")){
						lexileValue = lexileValue.substring(0, lexileValue.length() - 1);
					}
					if (StringUtils.isNumeric(lexileValue)) {
						groupedWork.setLexileScore(lexileValue);
					}else{
						Matcher lexileMatcher = lexileMatchingPattern.matcher(lexileValue);
						if (lexileMatcher.find()){
							String lexileCode = lexileMatcher.group(1);
							String lexileScore = lexileMatcher.group(2);
							groupedWork.setLexileScore(lexileScore);
							groupedWork.setLexileCode(lexileCode);
						}
					}
				}
			}
		}
	}

	private void loadFountasPinnell(AbstractGroupedWorkSolr groupedWork, Record record) {
		Set<String> targetAudiences = MarcUtil.getFieldList(record, "521a");
		for (String targetAudience : targetAudiences){
			if (targetAudience.startsWith("Guided reading level: ")){
				String fountasPinnellValue = targetAudience.replace("Guided reading level: ", "");
				fountasPinnellValue = fountasPinnellValue.replace(".", "").toUpperCase();
				groupedWork.setFountasPinnell(fountasPinnellValue);
				break;
			}
		}
	}

	private void loadAwards(AbstractGroupedWorkSolr groupedWork, Record record){
		Set<String> awardFields = MarcUtil.getFieldList(record, "586a");
		HashSet<String> awards = new HashSet<>();
		for (String award : awardFields){
			//Normalize the award name
			if (award.contains("Caldecott")) {
				award = "Caldecott Medal";
			}else if (award.contains("Pulitzer") || award.contains("Puliter")){
				award = "Pulitzer Prize";
			}else if (award.contains("Newbery")){
				award = "Newbery Medal";
			}else {
				if (award.contains(":")) {
					String[] awardParts = award.split(":");
					award = awardParts[0].trim();
				}
				//Remove dates
				award = award.replaceAll("\\d{2,4}", "");
				//Remove punctuation
				award = award.replaceAll("[^\\w\\s]", "");
			}
			awards.add(award.trim());
		}
		groupedWork.addAwards(awards);
	}


	protected abstract void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, Record record, String identifier);

	void loadEditions(AbstractGroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords) {
		Set<String> editions = MarcUtil.getFieldList(record, "250a");
		if (editions.size() > 0) {
			String edition = editions.iterator().next();
			for (RecordInfo ilsRecord : ilsRecords) {
				ilsRecord.setEdition(edition);
			}
		}
		groupedWork.addEditions(editions);
	}

	void loadPhysicalDescription(AbstractGroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords) {
		Set<String> physicalDescriptions = MarcUtil.getFieldList(record, "300abcefg:530abcd");
		if (physicalDescriptions.size() > 0){
			String physicalDescription = physicalDescriptions.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPhysicalDescription(physicalDescription);
			}
		}
		groupedWork.addPhysical(physicalDescriptions);
	}

	private String getCallNumberSubject(Record record) {
		String val = MarcUtil.getFirstFieldVal(record, "090a:050a");

		if (val != null) {
			String[] callNumberSubject = val.toUpperCase().split("[^A-Z]+");
			if (callNumberSubject.length > 0) {
				return callNumberSubject[0];
			}
		}
		return null;
	}

	private String getMpaaRating(Record record) {
		String val = MarcUtil.getFirstFieldVal(record, "521a");

		if (val != null) {
			if (mpaaNotRatedRegex.matcher(val).matches()) {
				return "Not Rated";
			}
			try {
				Matcher mpaaMatcher1 = mpaaRatingRegex1.matcher(val);
				if (mpaaMatcher1.find()) {
					// System.out.println("Matched matcher 1, " + mpaaMatcher1.group(1) +
					// " Rated " + getId());
					return mpaaMatcher1.group(1) + " Rated";
				} else {
					Matcher mpaaMatcher2 = mpaaRatingRegex2.matcher(val);
					if (mpaaMatcher2.find()) {
						// System.out.println("Matched matcher 2, " + mpaaMatcher2.group(1)
						// + " Rated " + getId());
						return mpaaMatcher2.group(1) + " Rated";
					} else {
						Matcher mpaaMatcher3 = mpaaRatingRegex3.matcher(val);
						if (mpaaMatcher3.find()) {
							// System.out.println("Matched matcher 2, " + mpaaMatcher2.group(1)
							// + " Rated " + getId());
							return mpaaMatcher3.group(1) + " Rated";
						} else {
							return null;
						}
					}
				}
			} catch (PatternSyntaxException ex) {
				// Syntax error in the regular expression
				return null;
			}
		} else {
			return null;
		}
	}

	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier) {
		loadTargetAudiences(groupedWork, record, printItems, identifier, "Unknown");
	}

	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier, String unknownAudienceLabel) {
		Set<String> targetAudiences = new LinkedHashSet<>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField(8);
			ControlField ohOhSixField = (ControlField) record.getVariableField(6);

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if ((recordType == 'A' || recordType == 'T')
					&& (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M') /* Books */
					|| (recordType == 'M') /* Computer Files */
					|| (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J') /* Music */
					|| (recordType == 'G' || recordType == 'K' || recordType == 'O' || recordType == 'R') /*
																																																 * Visual
																																																 * Materials
																																																 */
					) {
				char targetAudienceChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() > 5) {
					targetAudienceChar = Character.toUpperCase(ohOhSixField.getData().charAt(5));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
					}
				}
				if (targetAudiences.size() == 0 && ohOhEightField != null && ohOhEightField.getData().length() > 22) {
					targetAudienceChar = Character.toUpperCase(ohOhEightField.getData().charAt(22));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
					}
				} else if (targetAudiences.size() == 0) {
					targetAudiences.add(unknownAudienceLabel);
				}
			} else {
				targetAudiences.add(unknownAudienceLabel);
			}
		} catch (Exception e) {
			// leader not long enough to get target audience
			logger.debug("ERROR in getTargetAudience ", e);
			targetAudiences.add(unknownAudienceLabel);
		}

		if (targetAudiences.size() == 0) {
			targetAudiences.add(unknownAudienceLabel);
		}

		LinkedHashSet<String> translatedAudiences = indexer.translateSystemCollection("target_audience", targetAudiences, identifier);
		if (!unknownAudienceLabel.equals("Unknown") && translatedAudiences.contains("Unknown")){
			translatedAudiences.remove("Unknown");
			translatedAudiences.add(unknownAudienceLabel);
		}
		groupedWork.addTargetAudiences(translatedAudiences);
		LinkedHashSet<String> translatedAudiencesFull = indexer.translateSystemCollection("target_audience_full", targetAudiences, identifier);
		if (!unknownAudienceLabel.equals("Unknown") && translatedAudiencesFull.contains("Unknown")){
			translatedAudiencesFull.remove("Unknown");
			translatedAudiencesFull.add(unknownAudienceLabel);
		}
		groupedWork.addTargetAudiencesFull(translatedAudiencesFull);
	}

	protected void loadLiteraryForms(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier) {
		//First get the literary Forms from the 008.  These need translation
		//Now get literary forms from the subjects, these don't need translation
		LinkedHashSet<String> literaryForms = new LinkedHashSet<>();
		HashMap<String, Integer> literaryFormsWithCount = new HashMap<>();
		HashMap<String, Integer> literaryFormsFull = new HashMap<>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField(8);
			ControlField ohOhSixField = (ControlField) record.getVariableField(6);

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if (((recordType == 'A' || recordType == 'T') && (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M')) /* Books */
					) {
				char literaryFormChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() > 16) {
					literaryFormChar = Character.toUpperCase(ohOhSixField.getData().charAt(16));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
					}
				}
				if (literaryForms.size() == 0 && ohOhEightField != null && ohOhEightField.getData().length() > 33) {
					literaryFormChar = Character.toUpperCase(ohOhEightField.getData().charAt(33));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
					}
				}
				addToMapWithCount(literaryFormsWithCount, indexer.translateSystemCollection("literary_form", literaryForms, identifier), 2);
				addToMapWithCount(literaryFormsFull, indexer.translateSystemCollection("literary_form_full", literaryForms, identifier), 2);
			}else if (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J'){
				//Music / Audio
				if (ohOhEightField != null && ohOhEightField.getData().length() > 31){
					char position30 = Character.toUpperCase(ohOhEightField.getData().charAt(30));
					char position31 = Character.toUpperCase(ohOhEightField.getData().charAt(31));
					if (position30 == 'F' || position31 == 'F'){
						addToMapWithCount(literaryFormsWithCount, "Fiction", 2);
						addToMapWithCount(literaryFormsFull, "Fiction", 2);
					}else{
						addToMapWithCount(literaryFormsWithCount, "Non Fiction", 2);
						addToMapWithCount(literaryFormsFull, "Non Fiction", 2);
					}
				}
			}
		} catch (Exception e) {
			indexer.getLogEntry().incErrors("Unexpected error loading literary forms", e);
		}

		//Check the subjects
		Set<String> subjectFormData = MarcUtil.getFieldList(record, "650v:651v");
		for(String subjectForm : subjectFormData){
			subjectForm = StringUtils.trimTrailingPunctuation(subjectForm);
			if (subjectForm.equalsIgnoreCase("Fiction")
					|| subjectForm.equalsIgnoreCase("Young adult fiction" )
					|| subjectForm.equalsIgnoreCase("Juvenile fiction" )
					|| subjectForm.equalsIgnoreCase("Junior fiction" )
					|| subjectForm.equalsIgnoreCase("Comic books, strips, etc")
					|| subjectForm.equalsIgnoreCase("Comic books,strips, etc")
					|| subjectForm.equalsIgnoreCase("Science fiction comics")
					|| subjectForm.equalsIgnoreCase("Children's fiction" )
					|| subjectForm.equalsIgnoreCase("Fictional Works" )
					|| subjectForm.equalsIgnoreCase("Cartoons and comics" )
					|| subjectForm.equalsIgnoreCase("Folklore" )
					|| subjectForm.equalsIgnoreCase("Legends" )
					|| subjectForm.equalsIgnoreCase("Stories" )
					|| subjectForm.equalsIgnoreCase("Fantasy" )
					|| subjectForm.equalsIgnoreCase("Mystery fiction")
					|| subjectForm.equalsIgnoreCase("Romances")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
			}else if (subjectForm.equalsIgnoreCase("Biography")){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}else if (subjectForm.equalsIgnoreCase("Novela juvenil")
					|| subjectForm.equalsIgnoreCase("Novela")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Novels");
			}else if (subjectForm.equalsIgnoreCase("Drama")
					|| subjectForm.equalsIgnoreCase("Dramas")
					|| subjectForm.equalsIgnoreCase("Juvenile drama")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Dramas");
			}else if (subjectForm.equalsIgnoreCase("Poetry")
					|| subjectForm.equalsIgnoreCase("Juvenile Poetry")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Poetry");
			}else if (subjectForm.equalsIgnoreCase("Humor")
					|| subjectForm.equalsIgnoreCase("Juvenile Humor")
					|| subjectForm.equalsIgnoreCase("Comedy")
					|| subjectForm.equalsIgnoreCase("Wit and humor")
					|| subjectForm.equalsIgnoreCase("Satire")
					|| subjectForm.equalsIgnoreCase("Humor, Juvenile")
					|| subjectForm.equalsIgnoreCase("Humour")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Humor, Satires, etc.");
			}else if (subjectForm.equalsIgnoreCase("Correspondence")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Letters");
			}else if (subjectForm.equalsIgnoreCase("Short stories")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Short stories");
			}else if (subjectForm.equalsIgnoreCase("essays")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Essays");
			}else if (subjectForm.equalsIgnoreCase("Personal narratives, American")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Polish")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Sudanese")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Jewish")
					|| subjectForm.equalsIgnoreCase("Personal narratives")
					|| subjectForm.equalsIgnoreCase("Guidebooks")
					|| subjectForm.equalsIgnoreCase("Guide-books")
					|| subjectForm.equalsIgnoreCase("Handbooks, manuals, etc")
					|| subjectForm.equalsIgnoreCase("Problems, exercises, etc")
					|| subjectForm.equalsIgnoreCase("Case studies")
					|| subjectForm.equalsIgnoreCase("Handbooks")
					|| subjectForm.equalsIgnoreCase("Biographies")
					|| subjectForm.equalsIgnoreCase("Interviews")
					|| subjectForm.equalsIgnoreCase("Autobiography")
					|| subjectForm.equalsIgnoreCase("Cookbooks")
					|| subjectForm.equalsIgnoreCase("Dictionaries")
					|| subjectForm.equalsIgnoreCase("Encyclopedias")
					|| subjectForm.equalsIgnoreCase("Encyclopedias, Juvenile")
					|| subjectForm.equalsIgnoreCase("Dictionaries, Juvenile")
					|| subjectForm.equalsIgnoreCase("Nonfiction")
					|| subjectForm.equalsIgnoreCase("Non-fiction")
					|| subjectForm.equalsIgnoreCase("Juvenile non-fiction")
					|| subjectForm.equalsIgnoreCase("Maps")
					|| subjectForm.equalsIgnoreCase("Catalogs")
					|| subjectForm.equalsIgnoreCase("Recipes")
					|| subjectForm.equalsIgnoreCase("Diaries")
					|| subjectForm.equalsIgnoreCase("Designs and Plans")
					|| subjectForm.equalsIgnoreCase("Reference books")
					|| subjectForm.equalsIgnoreCase("Travel guide")
					|| subjectForm.equalsIgnoreCase("Textbook")
					|| subjectForm.equalsIgnoreCase("Atlas")
					|| subjectForm.equalsIgnoreCase("Atlases")
					|| subjectForm.equalsIgnoreCase("Study guides")
					) {
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}else{
				//noinspection RedundantCollectionOperation
				if (!unknownSubjectForms.contains(subjectForm)){
					//logger.warn("Unknown subject form " + subjectForm);
					unknownSubjectForms.add(subjectForm);
				}
			}
		}

		//Check the subjects
		Set<String> subjectGenreData = MarcUtil.getFieldList(record, "655a");
		for(String subjectForm : subjectGenreData) {
			subjectForm = StringUtils.trimTrailingPunctuation(subjectForm).toLowerCase();
			if (subjectForm.startsWith("instructional film")
					|| subjectForm.startsWith("educational film")
					) {
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}
		}
		groupedWork.addLiteraryForms(literaryFormsWithCount);
		groupedWork.addLiteraryFormsFull(literaryFormsFull);
	}

	private void addToMapWithCount(HashMap<String, Integer> map, HashSet<String> elementsToAdd, int numberToAdd){
		for (String elementToAdd : elementsToAdd) {
			addToMapWithCount(map, elementToAdd, numberToAdd);
		}
	}

	private void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd){
		addToMapWithCount(map, elementToAdd, 1);
	}

	private void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd, int numberToAdd){
		if (map.containsKey(elementToAdd)){
			map.put(elementToAdd, map.get(elementToAdd) + numberToAdd);
		}else{
			map.put(elementToAdd, numberToAdd);
		}
	}

	Pattern closedCaptioningPattern = Pattern.compile("video recordings for the hearing impaired|video recordings for the visually impaired", Pattern.CASE_INSENSITIVE);
	void loadClosedCaptioning(AbstractGroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords){
		//Based on the 655 fields determine if the record is closed captioned
		Set<String> subjectFields = MarcUtil.getFieldList(record, "655a");
		boolean isClosedCaptioned = false;
		for (String subjectField : subjectFields){
			if (closedCaptioningPattern.matcher(subjectField).lookingAt()){
				isClosedCaptioned = true;
				break;
			}
		}
		if (isClosedCaptioned){
			for (RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setClosedCaptioned(true);
			}
		}
	}

	void loadPublicationDetails(AbstractGroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords) {
		//Load publishers
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		if (publishers.size() > 0){
			String publisher = publishers.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPublisher(publisher);
			}
		}

		//Load publication dates
		Set<String> publicationDates = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDates);
		if (publicationDates.size() > 0){
			String publicationDate = publicationDates.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPublicationDate(publicationDate);
			}
		}

	}

	private Set<String> getPublicationDates(Record record) {
		List<DataField> rdaFields = record.getDataFields(264);
		HashSet<String> publicationDates = new HashSet<>();
		String date;
		//Try to get from RDA data
		if (rdaFields.size() > 0){
			for (DataField dataField : rdaFields){
				if (dataField.getIndicator2() == '1'){
					Subfield subFieldC = dataField.getSubfield('c');
					if (subFieldC != null){
						date = subFieldC.getData();
						publicationDates.add(date);
					}
				}
			}
		}
		//Try to get from 260
		if (publicationDates.size() ==0) {
			publicationDates.addAll(StringUtils.trimTrailingPunctuation(MarcUtil.getFieldList(record, "260c")));
		}
		//Try to get from 008, but only need to do if we don't have anything else
		if (publicationDates.size() == 0) {
			publicationDates.add(StringUtils.trimTrailingPunctuation(MarcUtil.getFirstFieldVal(record, "008[7-10]")));
		}

		return publicationDates;
	}

	private Set<String> getPublishers(Record record){
		Set<String> publisher = new LinkedHashSet<>();
		//First check for 264 fields
		List<DataField> rdaFields = MarcUtil.getDataFields(record, 264);
		if (rdaFields.size() > 0){
			for (DataField curField : rdaFields){
				if (curField.getIndicator2() == '1'){
					Subfield subFieldB = curField.getSubfield('b');
					if (subFieldB != null){
						publisher.add(StringUtils.trimTrailingPunctuation(subFieldB.getData()));
					}
				}
			}
		}
		publisher.addAll(StringUtils.trimTrailingPunctuation(MarcUtil.getFieldList(record, "260b")));
		return publisher;
	}

	String languageFields = "008[35-37]";

	void loadLanguageDetails(AbstractGroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords, String identifier) {
		Set <String> languages = MarcUtil.getFieldList(record, languageFields);
		HashSet<String> translatedLanguages = new HashSet<>();
		boolean isFirstLanguage = true;
		for (String language : languages){
			String translatedLanguage = indexer.translateSystemValue("language", language, identifier);
			if (treatUnknownLanguageAs != null && treatUnknownLanguageAs.length() > 0 && translatedLanguage.equals("Unknown")){
				translatedLanguage = treatUnknownLanguageAs;
			}else if (treatUndeterminedLanguageAs != null && treatUndeterminedLanguageAs.length() > 0 && translatedLanguage.equals("Undetermined")){
				translatedLanguage = treatUndeterminedLanguageAs;
			}
			translatedLanguages.add(translatedLanguage);
			if (isFirstLanguage){
				for (RecordInfo ilsRecord : ilsRecords){
					ilsRecord.setPrimaryLanguage(translatedLanguage);
				}
			}
			isFirstLanguage = false;
			String languageBoost = indexer.translateSystemValue("language_boost", language, identifier);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateSystemValue("language_boost_es", language, identifier);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoostEs);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		if (translatedLanguages.size() == 0){
			translatedLanguages.add(treatUnknownLanguageAs);
			for (RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPrimaryLanguage(treatUnknownLanguageAs);
			}
			String languageBoost = indexer.translateSystemValue("language_boost", treatUnknownLanguageAs, identifier);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateSystemValue("language_boost_es", treatUnknownLanguageAs, identifier);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoostEs);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		groupedWork.setLanguages(translatedLanguages);

		String translationFields = "041b:041d:041h:041j";
		Set<String> translations = MarcUtil.getFieldList(record, translationFields);
		translatedLanguages = new HashSet<>();
		for (String translation : translations) {
			String translatedLanguage = indexer.translateSystemValue("language", translation, identifier);
			translatedLanguages.add(translatedLanguage);
		}
		groupedWork.setTranslations(translatedLanguages);
	}

	private void loadAuthors(AbstractGroupedWorkSolr groupedWork, Record record, String identifier) {
		//auth_author = 100abcd, first
		groupedWork.setAuthAuthor(MarcUtil.getFirstFieldVal(record, "100abcd"));
		//author = a, first
		//MDN 2/6/2016 - Do not use 710 because it is not truly the author.  This has the potential
		//of showing some disconnects with how records are grouped, but improves the display of the author
		//710 is still indexed as part of author 2 #ARL-146
		//groupedWork.setAuthor(this.getFirstFieldVal(record, "100abcdq:110ab:710a"));
		groupedWork.setAuthor(MarcUtil.getFirstFieldVal(record, "100abcdq:110ab"));
		//auth_author2 = 700abcd
		groupedWork.addAuthAuthor2(MarcUtil.getFieldList(record, "700abcd"));
		//author2 = 110ab:111ab:700abcd:710ab:711ab:800a
		groupedWork.addAuthor2(MarcUtil.getFieldList(record, "110ab:111ab:700abcd:710ab:711ab:800a"));
		//author_additional = 505r:245c
		groupedWork.addAuthorAdditional(MarcUtil.getFieldList(record, "505r:245c"));
		//Load contributors with role
		List<DataField> contributorFields = MarcUtil.getDataFields(record, new int[]{700,710});
		HashSet<String> contributors = new HashSet<>();
		for (DataField contributorField : contributorFields){
			StringBuilder contributor = MarcUtil.getSpecifiedSubfieldsAsString(contributorField, "abcd", "");
			if (contributor.length() == 0){
				continue;
			}
			if (contributor.substring(contributor.length() - 1, contributor.length()).equals(",")){
				contributor = new StringBuilder(contributor.substring(0, contributor.length() - 1));
			}
			StringBuilder roles = MarcUtil.getSpecifiedSubfieldsAsString(contributorField, "e4", ",");
			if (roles.length() > 0){
				contributor.append("|").append(roles.toString().replaceAll(",,", ","));
			}
			contributors.add(contributor.toString());
		}
		groupedWork.addAuthor2Role(contributors);

		//author_display = 100a:110a:260b:710a:245c, first
		//#ARL-95 Do not show display author from the 710 or from the 245c since neither are truly authors
		//#ARL-200 Do not show display author from the 260b since it is also not the author
		String displayAuthor = MarcUtil.getFirstFieldVal(record, "100a:110ab");
		if (displayAuthor != null && displayAuthor.indexOf(';') > 0){
			displayAuthor = displayAuthor.substring(0, displayAuthor.indexOf(';') -1);
		}
		groupedWork.setAuthorDisplay(displayAuthor);
	}

	private void loadTitles(AbstractGroupedWorkSolr groupedWork, Record record, String format, String formatCategory) {
		//title (full title done by index process by concatenating short and subtitle

		//title short
		DataField titleField = record.getDataField(245);
		String authorInTitleField = null;
		if (titleField != null) {
			String subTitle = titleField.getSubfieldsAsString("bfgnp");
			groupedWork.setTitle(titleField.getSubfieldsAsString("a"), subTitle, titleField.getSubfieldsAsString("abfgnp"), this.getSortableTitle(record), format, formatCategory);
			//title full
			authorInTitleField = titleField.getSubfieldsAsString("c");
		}
		String standardAuthorData = MarcUtil.getFirstFieldVal(record, "100abcdq:110ab");
		if ((authorInTitleField != null && authorInTitleField.length() > 0) || (standardAuthorData == null || standardAuthorData.length() == 0)) {
			groupedWork.addFullTitles(MarcUtil.getAllSubfields(record, "245", " "));
		}else{
			//We didn't get an author from the 245, combine with the 100
			Set<String> titles = MarcUtil.getAllSubfields(record, "245", " ");
			for (String title : titles){
				groupedWork.addFullTitle(title + " " + standardAuthorData);
			}
		}

		//title alt
		groupedWork.addAlternateTitles(MarcUtil.getFieldList(record, "130adfgklnpst:240a:246abfgnp:700tnr:730adfgklnpst:740a"));
		//title old
		groupedWork.addOldTitles(MarcUtil.getFieldList(record, "780ast"));
		//title new
		groupedWork.addNewTitles(MarcUtil.getFieldList(record, "785ast"));
	}

	private void loadBibCallNumbers(AbstractGroupedWorkSolr groupedWork, Record record, String identifier) {
		groupedWork.setCallNumberA(MarcUtil.getFirstFieldVal(record, "099a:090a:050a"));
		String firstCallNumber = MarcUtil.getFirstFieldVal(record, "099a[0]:090a[0]:050a[0]");
		if (firstCallNumber != null){
			groupedWork.setCallNumberFirst(indexer.translateSystemValue("callnumber", firstCallNumber, identifier));
		}
		String callNumberSubject = getCallNumberSubject(record);
		if (callNumberSubject != null){
			groupedWork.setCallNumberSubject(indexer.translateSystemValue("callnumber_subject", callNumberSubject, identifier));
		}
	}

	void loadEContentUrl(Record record, ItemInfo itemInfo) {
		List<DataField> urlFields = MarcUtil.getDataFields(record, 856);
		for (DataField urlField : urlFields){
			//load url into the item
			if (urlField.getSubfield('u') != null){
				//Try to determine if this is a resource or not.
				if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0'){
					if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '4') {
						itemInfo.seteContentUrl(urlField.getSubfield('u').getData().trim());
						break;
					}
				}

			}
		}
	}

	/**
	 * Get the title (245abfgnp) from a record, without non-filing chars as specified
	 * in 245 2nd indicator, and lower cased.
	 *
	 * @return 245a and 245b and 245n and 245p values concatenated, with trailing punctuation removed, and
	 *         with non-filing characters omitted. Null returned if no title can
	 *         be found.
	 */
	private String getSortableTitle(Record record) {
		DataField titleField = record.getDataField(245);
		if (titleField == null || titleField.getSubfield('a') == null)
			return "";

		int nonFilingInt = getInd2AsInt(titleField);

		String title = titleField.getSubfieldsAsString("abfgnp");
		if (title == null){
			return "";
		}
		title = title.toLowerCase();

		// Skip non-filing chars, if possible.
		if (title.length() > nonFilingInt) {
			title = title.substring(nonFilingInt);
		}

		if (title.length() == 0) {
			return "";
		}

		return title;
	}

	/**
	 * @param df
	 *          a DataField
	 * @return the integer (0-9, 0 if blank or other) in the 2nd indicator
	 */
	private int getInd2AsInt(DataField df) {
		char ind2char = df.getIndicator2();
		int result = 0;
		if (Character.isDigit(ind2char))
			result = Integer.parseInt(String.valueOf(ind2char));
		return result;
	}

	LinkedHashSet<String> getFormatsFromBib(Record record, RecordInfo recordInfo){
		LinkedHashSet<String> printFormats = new LinkedHashSet<>();
		String leader = record.getLeader().toString();
		char leaderBit = ' ';
		ControlField fixedField = (ControlField) record.getVariableField(8);

		// check for music recordings quickly so we can figure out if it is music
		// for category (need to do here since checking what is on the Compact
		// Disc/Phonograph, etc is difficult).
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			if (Character.toUpperCase(leaderBit) == 'J') {
				printFormats.add("MusicRecording");
			}
		}
		//Check for braille
		if (fixedField != null && (leaderBit == 'a' || leaderBit == 't' || leaderBit == 'A' || leaderBit == 'T')){
			if (fixedField.getData().length() > 23){
				if (fixedField.getData().charAt(23) == 'f'){
					printFormats.add("Braille");
				}else if (fixedField.getData().charAt(23) == 'd') {
					printFormats.add("LargePrint");
				}
			}
		}

		getFormatFromPublicationInfo(record, printFormats);
		getFormatFromNotes(record, printFormats);
		getFormatFromEdition(record, printFormats);
		getFormatFromPhysicalDescription(record, printFormats);
		getFormatFromSubjects(record, printFormats);
		getFormatFromTitle(record, printFormats);
		getFormatFromDigitalFileCharacteristics(record, printFormats);
		if (printFormats.size() == 0 && fallbackFormatField != null && fallbackFormatField.length() > 0){
			getFormatFromFallbackField(record, printFormats);
		}
		if (printFormats.size() == 0 || printFormats.contains("MusicRecording") || (printFormats.size() == 1 && printFormats.contains("Book"))) {
			if (printFormats.size() == 1 && printFormats.contains("Book")){
				printFormats.clear();
			}
			//Only get from fixed field information if we don't have anything yet since the cataloging of
			//fixed fields is not kept up to date reliably.  #D-87
			getFormatFrom007(record, printFormats);
			if (printFormats.size() > 1){
				logger.info("Found more than 1 format for " + recordInfo.getFullIdentifier() + " looking at just 007");
			}
			if (printFormats.size() == 0 || (printFormats.size() == 1 && printFormats.contains("Book"))) {
				getFormatFromLeader(printFormats, leader, fixedField);
				if (printFormats.size() > 1){
					logger.info("Found more than 1 format for " + recordInfo.getFullIdentifier() + " looking at just the leader");
				}
			}
		}

		if (printFormats.size() == 0) {
			logger.debug("Did not get any formats for print record " + recordInfo.getFullIdentifier() + ", assuming it is a book ");
			printFormats.add("Book");
//		}else if (printFormats.size() > 1){
//			for(String format: printFormats){
//				logger.debug("    found format " + format);
//			}
		}

		if (printFormats.size() > 1) {
			filterPrintFormats(printFormats);
		}

		if (printFormats.size() > 1){
			String formatsString = Util.getCsvSeparatedString(printFormats);
			if (!formatsToFilter.contains(formatsString)){
				formatsToFilter.add(formatsString);
				logger.info("Found more than 1 format for " + recordInfo.getFullIdentifier() + " - " + formatsString);
			}
		}
		return printFormats;
	}

	protected void getFormatFromFallbackField(Record record, LinkedHashSet<String> printFormats) {
		//Do nothing by default, this is overridden in IlsRecordProcessor
	}

	private final HashSet<String> formatsToFilter = new HashSet<>();

	private void getFormatFromDigitalFileCharacteristics(Record record, LinkedHashSet<String> printFormats) {
		Set<String> fields = MarcUtil.getFieldList(record, "347b");
		for (String curField : fields){
			if (curField.equalsIgnoreCase("Blu-Ray")){
				printFormats.add("Blu-ray");
			}else if (curField.equalsIgnoreCase("DVD video")){
				printFormats.add("DVD");
			}
		}
	}

	private void filterPrintFormats(Set<String> printFormats) {
		if (printFormats.contains("Archival Materials")){
			printFormats.clear();
			printFormats.add("Archival Materials");
			return;
		}
		if (printFormats.contains("LibraryOfThings")){
			printFormats.clear();
			printFormats.add("LibraryOfThings");
			return;
		}
		if (printFormats.contains("SoundCassette") && printFormats.contains("MusicRecording")){
			printFormats.clear();
			printFormats.add("MusicCassette");
		}
		if (printFormats.contains("Thesis")){
			printFormats.clear();
			printFormats.add("Thesis");
		}
		if (printFormats.contains("Phonograph")){
			printFormats.clear();
			printFormats.add("Phonograph");
			return;
		}
		if (printFormats.contains("CD+DVD")){
			printFormats.clear();
			printFormats.add("CD+DVD");
			return;
		}
		if (printFormats.contains("MusicRecording") && (printFormats.contains("CD") || printFormats.contains("CompactDisc") || printFormats.contains("SoundDisc"))){
			printFormats.clear();
			printFormats.add("MusicCD");
			return;
		}
		if (printFormats.contains("PlayawayView")){
			printFormats.clear();
			printFormats.add("PlayawayView");
			return;
		}
		if (printFormats.contains("Playaway")){
			printFormats.clear();
			printFormats.add("Playaway");
			return;
		}
		if (printFormats.contains("GoReader")){
			printFormats.clear();
			printFormats.add("GoReader");
			return;
		}
		if (printFormats.contains("VoxBooks")){
			printFormats.clear();
			printFormats.add("VoxBooks");
			return;
		}
		if (printFormats.contains("Kit")){
			printFormats.clear();
			printFormats.add("Kit");
			return;
		}
		if (printFormats.contains("Video") && printFormats.contains("DVD")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("VideoDisc") && printFormats.contains("DVD")){
			printFormats.remove("VideoDisc");
		}
		if (printFormats.contains("Video") && printFormats.contains("VideoDisc")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("Video") && printFormats.contains("VideoCassette")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("DVD")){
			printFormats.remove("VideoCassette");
		}
		if (printFormats.contains("Blu-ray")){
			printFormats.remove("VideoDisc");
			printFormats.remove("DVD");
		}
		if (printFormats.contains("Blu-ray/DVD")){
			printFormats.remove("Blu-ray");
			printFormats.remove("DVD");
		}
		if (printFormats.contains("SoundDisc")){
			printFormats.remove("SoundRecording");
			printFormats.remove("CDROM");
		}
		if (printFormats.contains("MP3Disc")){
			printFormats.remove("SoundDisc");
		}
		if (printFormats.contains("SoundCassette")){
			printFormats.remove("SoundRecording");
			printFormats.remove("CompactDisc");
		}
		if (printFormats.contains("SoundRecording") && printFormats.contains("CDROM")){
			printFormats.clear();
			printFormats.add("SoundDisc");
		}

		if (printFormats.contains("Book") && printFormats.contains("Serial")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("BookClubKit") && printFormats.contains("LargePrint")){
			printFormats.clear();
			printFormats.add("BookClubKitLarge");
		}
		if (printFormats.contains("Book") && printFormats.contains("LargePrint")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("Atlas")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("Manuscript")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("GraphicNovel")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("MusicalScore")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("BookClubKit")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Book") && printFormats.contains("Kit")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("AudioCD") && printFormats.contains("CD")){
			printFormats.remove("AudioCD");
		}

		if (printFormats.contains("CD") && printFormats.contains("SoundDisc")){
			printFormats.remove("CD");
		}
		if (printFormats.contains("CompactDisc") && printFormats.contains("SoundDisc")){
			printFormats.remove("CompactDisc");
		}
		if (printFormats.contains("CompactDisc")){
			printFormats.remove("SoundRecording");
		}
		if (printFormats.contains("GraphicNovel")){
			printFormats.remove("Serial");
		}
		if (printFormats.contains("Atlas") && printFormats.contains("Map")){
			printFormats.remove("Atlas");
		}
		if (printFormats.contains("LargePrint")){
			printFormats.remove("Manuscript");
		}
		if (printFormats.contains("Kinect") || printFormats.contains("XBox360")  || printFormats.contains("Xbox360")
				|| printFormats.contains("XboxOne") || printFormats.contains("XboxSeriesX") || printFormats.contains("PlayStation")
				|| printFormats.contains("PlayStation2") || printFormats.contains("PlayStation3")
				|| printFormats.contains("PlayStation4") || printFormats.contains("PlayStation5") || printFormats.contains("PlayStationVita")
				|| printFormats.contains("Wii") || printFormats.contains("WiiU")
				|| printFormats.contains("3DS") || printFormats.contains("WindowsGame")
				|| printFormats.contains("NintendoSwitch") || printFormats.contains("NintendoDS")){
			printFormats.remove("Software");
			printFormats.remove("Electronic");
			printFormats.remove("CDROM");
			printFormats.remove("Blu-ray");
			printFormats.remove("Blu-ray/DVD");
			printFormats.remove("DVD");
			printFormats.remove("CD+Book");
			printFormats.remove("Book+CD");
			printFormats.remove("Book+DVD");
			printFormats.remove("SoundDisc");
		}
	}

	private void getFormatFromTitle(Record record, Set<String> printFormats) {
		String titleMedium = MarcUtil.getFirstFieldVal(record, "245h");
		if (titleMedium != null){
			titleMedium = titleMedium.toLowerCase();
			if (titleMedium.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titleMedium.contains("large print")){
				printFormats.add("LargePrint");
			}else if (titleMedium.contains("book club kit")){
				printFormats.add("BookClubKit");
			}else if (titleMedium.contains("ebook")){
				printFormats.add("eBook");
			}else if (titleMedium.contains("eaudio")){
				printFormats.add("eAudiobook");
			}else if (titleMedium.contains("emusic")){
				printFormats.add("eMusic");
			}else if (titleMedium.contains("evideo")){
				printFormats.add("eVideo");
			}else if (titleMedium.contains("ejournal")){
				printFormats.add("eJournal");
			}else if (titleMedium.contains("playaway")){
				printFormats.add("Playaway");
			}else if (titleMedium.contains("periodical")){
				printFormats.add("Serial");
			}else if (titleMedium.contains("vhs")){
				printFormats.add("VideoCassette");
			}else if (titleMedium.contains("blu-ray")){
				printFormats.add("Blu-ray");
			}else if (titleMedium.contains("dvd")){
				printFormats.add("DVD");
			}

		}
		String titleForm = MarcUtil.getFirstFieldVal(record, "245k");
		if (titleForm != null){
			titleForm = titleForm.toLowerCase();
			if (titleForm.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titleForm.contains("large print")){
				printFormats.add("LargePrint");
			}else if (titleForm.contains("book club kit")){
				printFormats.add("BookClubKit");
			}
		}
		String titlePart = MarcUtil.getFirstFieldVal(record, "245p");
		if (titlePart != null){
			titlePart = titlePart.toLowerCase();
			if (titlePart.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titlePart.contains("large print")){
				printFormats.add("LargePrint");
			}
		}
		String title = MarcUtil.getFirstFieldVal(record, "245a");
		if (title != null){
			title = title.toLowerCase();
			if (title.contains("book club kit")){
				printFormats.add("BookClubKit");
			}
		}
	}

	private void getFormatFromPublicationInfo(Record record, Set<String> result) {
		// check for playaway in 260|b
		List<DataField> publicationFields = record.getDataFields(new int[]{260, 264});
		for (DataField publicationInfo : publicationFields) {
			for (Subfield publisherSubField : publicationInfo.getSubfields('b')){
				String sysDetailsValue = publisherSubField.getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				} else if (sysDetailsValue.contains("go reader")) {
					result.add("GoReader");
				}
			}
		}
	}

	private void getFormatFromEdition(Record record, Set<String> result) {
		DataField edition = record.getDataField(250);
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				String editionData = edition.getSubfield('a').getData().toLowerCase();
				if (editionData.contains("large type") || editionData.contains("large print")) {
					result.add("LargePrint");
				}else if (dvdBlurayComboRegex.matcher(editionData).matches()) {
					result.add("Blu-ray/DVD");
				}else if (editionData.contains("go reader")) {
					result.add("GoReader");
				}else {
					String gameFormat = getGameFormatFromValue(editionData);
					if (gameFormat != null) {
						result.add(gameFormat);
					}
				}
			}
		}
	}

	Pattern audioDiscPattern = Pattern.compile(".*\\b(cd|cds|(sound|audio|compact) discs?)\\b.*");
	Pattern pagesPattern = Pattern.compile("^.*?\\d+\\s+(p\\.|pages).*$");
	Pattern pagesPattern2 = Pattern.compile("^.*?\\b\\d+\\s+(p\\.|pages)[\\s\\W]*$");
	Pattern kitPattern = Pattern.compile(".*\\bkit\\b.*");
	private void getFormatFromPhysicalDescription(Record record, Set<String> result) {
		List<DataField> physicalDescriptions = MarcUtil.getDataFields(record, 300);
		for (DataField field : physicalDescriptions) {
			List<Subfield> subFields = field.getSubfields();
			for (Subfield subfield : subFields) {
				if (subfield.getCode() != 'e') {
					String physicalDescriptionData = subfield.getData().toLowerCase();
					if (physicalDescriptionData.contains("atlas")) {
						result.add("Atlas");
					} else if (physicalDescriptionData.contains("large type") || physicalDescriptionData.contains("large print")) {
						result.add("LargePrint");
					} else if (physicalDescriptionData.contains("bluray") || physicalDescriptionData.contains("blu-ray")) {
						//Check to see if this is a combo pack.
						Subfield subfieldE = field.getSubfield('e');
						if (subfieldE != null && subfieldE.getData().toLowerCase().contains("dvd")){
							result.add("Blu-ray/DVD");
						}else {
							result.add("Blu-ray");
						}
					} else if (physicalDescriptionData.contains("computer optical disc")) {
						if (!pagesPattern.matcher(physicalDescriptionData).matches()){
							result.add("Software");
						}
					} else if (physicalDescriptionData.contains("sound cassettes")) {
						result.add("SoundCassette");
					} else if (physicalDescriptionData.contains("mp3")) {
						result.add("MP3Disc");
					} else if (kitPattern.matcher(physicalDescriptionData).matches()) {
						result.add("Kit");
					} else if (audioDiscPattern.matcher(physicalDescriptionData).matches() && !physicalDescriptionData.contains("cd player")) {
						//Check to see if there is a subfield e.  If so, this could be a combined format
						Subfield subfieldE = field.getSubfield('e');
						if (subfieldE != null && subfieldE.getData().toLowerCase().contains("book")){
							result.add("CD+Book");
						}else{
							result.add("SoundDisc");
						}
					} else if (subfield.getCode() == 'a' && pagesPattern2.matcher(physicalDescriptionData).matches()){
						Subfield subfieldE = field.getSubfield('e');
						if (subfieldE != null && subfieldE.getData().toLowerCase().contains("dvd")){
							result.add("Book+DVD");
						}else if (subfieldE != null && subfieldE.getData().toLowerCase().contains("cd")){
							result.add("Book+CD");
						}else{
							result.add("Book");
						}
					}
					//Since this is fairly generic, only use it if we have no other formats yet
					if (result.size() == 0 && subfield.getCode() == 'f' && pagesPattern.matcher(physicalDescriptionData).matches()) {
						result.add("Book");
					}
				}
			}
		}
	}

	private final Pattern voxPattern = Pattern.compile(".*(vox books|vox reader|vox audio).*");
	private void getFormatFromNotes(Record record, Set<String> result) {
		// Check for formats in the 538 field
		List<DataField> sysDetailsNotes2 = record.getDataFields(538);
		for (DataField sysDetailsNote2 : sysDetailsNotes2) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				String gameFormat = getGameFormatFromValue(sysDetailsValue);
				if (gameFormat != null) {
					result.add(gameFormat);
				} else {
					if (sysDetailsValue.contains("playaway")) {
						result.add("Playaway");
					} else if (dvdBlurayComboRegex.matcher(sysDetailsValue).matches()) {
						result.add("Blu-ray/DVD");
					} else if (sysDetailsValue.contains("bluray") || sysDetailsValue.contains("blu-ray")) {
						result.add("Blu-ray");
					} else if (sysDetailsValue.contains("dvd") && !sysDetailsValue.contains("dvd-rom")) {
						result.add("DVD");
					} else if (sysDetailsValue.contains("vertical file")) {
						result.add("VerticalFile");
					}
				}
			}
		}

		// Check for formats in the 500 tag
		List<DataField> noteFields = record.getDataFields(500);
		for (DataField noteField : noteFields) {
			if (noteField != null) {
				if (noteField.getSubfield('a') != null) {
					String noteValue = noteField.getSubfield('a').getData().toLowerCase();
					if (noteValue.contains("vertical file")) {
						result.add("VerticalFile");
						break;
					} else if (voxPattern.matcher(noteValue).matches()) {
						result.add("VoxBooks");
						break;
					} else if (dvdBlurayComboRegex.matcher(noteValue).matches()) {
						result.add("Blu-ray/DVD");
						break;
					}
				}
			}
		}

		// Check for formats in the 502 tag
		DataField dissertationNoteField = record.getDataField(502);
		if (dissertationNoteField != null) {
			if (dissertationNoteField.getSubfield('a') != null) {
				String noteValue = dissertationNoteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("thesis (m.a.)")) {
					result.add("Thesis");
				}
			}
		}

		// Check for formats in the 590 tag
		DataField localNoteField = record.getDataField(590);
		if (localNoteField != null) {
			if (localNoteField.getSubfield('a') != null) {
				String noteValue = localNoteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("archival materials")) {
					result.add("Archival Materials");
				}
			}
		}
	}

	Pattern playStation5Pattern = Pattern.compile(".*(playstation\\s?5|ps\\s?5).*");
	Pattern playStation4Pattern = Pattern.compile(".*(playstation\\s?4|ps\\s?4).*");
	Pattern playStation3Pattern = Pattern.compile(".*(playstation\\s?3|ps\\s?3).*");
	Pattern playStation2Pattern = Pattern.compile(".*(playstation\\s?2|ps\\s?2).*");
	Pattern playStationVitaPattern = Pattern.compile(".*(playstation\\s?vita|ps\\s?vita).*");
	private String getGameFormatFromValue(String value) {
		if (value.contains("kinect sensor")) {
			return "Kinect";
		} else if (value.contains("wii u")) {
			return "WiiU";
		} else if (value.contains("nintendo wii") || value.contains("wii")) {
			return "Wii";
		} else if (value.contains("nintendo 3ds")) {
			return "3DS";
		} else if (value.contains("nintendo switch")) {
			return "NintendoSwitch";
		} else if (value.contains("nintendo ds")) {
			return "NintendoDS";
		} else if (value.contains("directx")) {
			return "WindowsGame";
		} else if (!value.contains("compatible")) {
			if (value.contains("xbox one")) {
				return "XboxOne";
			} else if ((value.contains("xbox series x") || value.contains("xbox x"))) {
				return "XBoxSeriesX";
			} else if (value.contains("xbox")) { //Make sure this is the last XBox listing
				return "Xbox360";
			} else if (playStation5Pattern.matcher(value).matches()) {
				return "PlayStation5";
			} else if (playStationVitaPattern.matcher(value).matches()) {
				return "PlayStationVita";
			} else if (playStation4Pattern.matcher(value).matches()) {
				return "PlayStation4";
			} else if (playStation3Pattern.matcher(value).matches()) {
				return "PlayStation3";
			} else if (playStation2Pattern.matcher(value).matches()) {
				return "PlayStation2";
			} else if (value.contains("playstation")) {
				return "PlayStation";
			}else{
				return null;
			}
		}else{
			return null;
		}
	}

	private void getFormatFromSubjects(Record record, Set<String> result) {
		List<DataField> topicalTerm = MarcUtil.getDataFields(record, 650);
		if (topicalTerm != null) {
			Iterator<DataField> fieldIterator = topicalTerm.iterator();
			DataField field;
			while (fieldIterator.hasNext()) {
				field = fieldIterator.next();
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type") || subfieldData.contains("large print")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("television adaptation")){
									okToAdd = true;
									//}else{
									//System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		List<DataField> genreFormTerm = MarcUtil.getDataFields(record, 655);
		if (genreFormTerm != null) {
			Iterator<DataField> fieldIterator = genreFormTerm.iterator();
			DataField field;
			while (fieldIterator.hasNext()) {
				field = fieldIterator.next();
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("library of things")){
							result.add("LibraryOfThings");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("television adaptation")){
									okToAdd = true;
									//}else{
									//System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		List<DataField> localTopicalTerm = MarcUtil.getDataFields(record, 690);
		if (localTopicalTerm != null) {
			Iterator<DataField> fieldsIterator = localTopicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null) {
					if (subfieldA.getData().toLowerCase().contains("seed library")) {
						result.add("SeedPacket");
					}
				}
			}
		}

		List<DataField> addedEntryFields = MarcUtil.getDataFields(record, 710);
		if (localTopicalTerm != null) {
			Iterator<DataField> addedEntryFieldIterator = addedEntryFields.iterator();
			DataField field;
			while (addedEntryFieldIterator.hasNext()) {
				field = addedEntryFieldIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null && subfieldA.getData() != null) {
					String fieldData = subfieldA.getData().toLowerCase();
					if (fieldData.contains("playaway view")) {
						result.add("PlayawayView");
					}else if (fieldData.contains("playaway digital audio") || fieldData.contains("findaway world")) {
						result.add("Playaway");
					}
				}
			}
		}
	}

	private void getFormatFrom007(Record record, Set<String> result) {
		Set<String> resultsFrom007 = new HashSet<>();
		char formatCode;// check the 007 - this is a repeating field
		List<ControlField> formatFields = record.getControlFields(7);
		for (ControlField formatField : formatFields) {
			if (formatField != null) {
				if (formatField.getData() == null || formatField.getData().length() < 2) {
					return;
				}
				formatCode = formatField.getData().toUpperCase().charAt(0);
				switch (formatCode) {
					case 'A':
						if (formatField.getData().toUpperCase().charAt(1) == 'D') {
							resultsFrom007.add("Atlas");
						} else {
							resultsFrom007.add("Map");
						}
						break;
					case 'C':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								resultsFrom007.add("TapeCartridge");
								break;
							case 'B':
								resultsFrom007.add("ChipCartridge");
								break;
							case 'C':
								resultsFrom007.add("DiscCartridge");
								break;
							case 'F':
								resultsFrom007.add("TapeCassette");
								break;
							case 'H':
								resultsFrom007.add("TapeReel");
								break;
							case 'J':
								resultsFrom007.add("FloppyDisk");
								break;
							case 'M':
							case 'O':
								resultsFrom007.add("CDROM");
								break;
							case 'R':
								// Do not return - this will cause anything with an
								// 856 field to be labeled as "Electronic"
								break;
							default:
								resultsFrom007.add("Software");
								break;
						}
						break;
					case 'D':
						resultsFrom007.add("Globe");
						break;
					case 'F':
						resultsFrom007.add("Braille");
						break;
					case 'G':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
							case 'D':
								resultsFrom007.add("Filmstrip");
								break;
							case 'T':
								resultsFrom007.add("Transparency");
								break;
							default:
								resultsFrom007.add("Slide");
								break;
						}
						break;
					case 'H':
						resultsFrom007.add("Microfilm");
						break;
					case 'K':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								resultsFrom007.add("Collage");
								break;
							case 'D':
							case 'L':
								resultsFrom007.add("Drawing");
								break;
							case 'E':
								resultsFrom007.add("Painting");
								break;
							case 'F':
							case 'J':
								resultsFrom007.add("Print");
								break;
							case 'G':
								resultsFrom007.add("Photonegative");
								break;
							case 'O':
								resultsFrom007.add("FlashCard");
								break;
							case 'N':
								resultsFrom007.add("Chart");
								break;
							default:
								resultsFrom007.add("Photo");
								break;
						}
						break;
					case 'M':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'F':
								resultsFrom007.add("VideoCassette");
								break;
							case 'R':
								resultsFrom007.add("Filmstrip");
								break;
							default:
								resultsFrom007.add("MotionPicture");
								break;
						}
						break;
					case 'O':
						resultsFrom007.add("Kit");
						break;
					case 'Q':
						resultsFrom007.add("MusicalScore");
						break;
					case 'R':
						resultsFrom007.add("SensorImage");
						break;
					case 'S':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								if (formatField.getData().length() >= 4) {
									char speed = formatField.getData().toUpperCase().charAt(3);
									if (speed >= 'A' && speed <= 'E') {
										resultsFrom007.add("Phonograph");
									} else if (speed == 'F') {
										resultsFrom007.add("CompactDisc");
									} else if (speed >= 'K' && speed <= 'R') {
										resultsFrom007.add("TapeRecording");
									} else {
										resultsFrom007.add("SoundDisc");
									}
								} else {
									resultsFrom007.add("SoundDisc");
								}
								break;
							case 'S':
								resultsFrom007.add("SoundCassette");
								break;
							default:
								resultsFrom007.add("SoundRecording");
								break;
						}
						break;
					case 'T':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								resultsFrom007.add("Book");
								break;
							case 'B':
								resultsFrom007.add("LargePrint");
								break;
						}
						break;
					case 'V':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								resultsFrom007.add("VideoCartridge");
								break;
							case 'D':
								resultsFrom007.add("VideoDisc");
								break;
							case 'F':
								resultsFrom007.add("VideoCassette");
								break;
							case 'R':
								resultsFrom007.add("VideoReel");
								break;
							default:
								resultsFrom007.add("Video");
								break;
						}
						break;
				}
			}
		}
		if (resultsFrom007.size() > 1){
			//We received more than one 007 field.  We potentially need to combine these.
			if (resultsFrom007.contains("CompactDisc")){
				resultsFrom007.remove("CDROM");
			}
			if (resultsFrom007.contains("CompactDisc") && resultsFrom007.contains("VideoDisc")){
				resultsFrom007.clear();
				resultsFrom007.add("CD+DVD");
			}
			if (resultsFrom007.size() > 1){
				logger.info("record had more than one format identified by 007");
			}
		}
		result.addAll(resultsFrom007);
	}


	private void getFormatFromLeader(Set<String> result, String leader, ControlField fixedField) {
		char leaderBit;
		char formatCode;// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'C':
				case 'D':
					result.add("MusicalScore");
					break;
				case 'E':
				case 'F':
					result.add("Map");
					break;
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// result.add("Slide");
					result.add("Video");
					break;
				case 'I':
					result.add("SoundRecording");
					break;
				case 'J':
					result.add("MusicRecording");
					break;
				case 'K':
					result.add("Photo");
					break;
				case 'M':
					result.add("Electronic");
					break;
				case 'O':
				case 'P':
					result.add("Kit");
					break;
				case 'R':
					result.add("PhysicalObject");
					break;
				case 'T':
					result.add("Manuscript");
					break;
			}
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
				// Monograph
				case 'M':
					if (result.isEmpty()) {
						result.add("Book");
					}
					break;
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					if (fixedField != null && fixedField.getData().length() >= 22) {
						formatCode = fixedField.getData().toUpperCase().charAt(21);
						switch (formatCode) {
							case 'N':
								result.add("Newspaper");
								break;
							case 'P':
								result.add("Journal");
								break;
							default:
								result.add("Serial");
								break;
						}
					}
			}
		}
	}
}
