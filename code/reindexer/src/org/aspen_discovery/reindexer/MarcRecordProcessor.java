package org.aspen_discovery.reindexer;

import org.aspen_discovery.format_classification.MarcRecordFormatClassifier;
import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

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
	protected MarcRecordFormatClassifier formatClassifier;
	protected GroupedWorkIndexer indexer;
	protected BaseIndexingSettings settings;
	protected String profileType;
	private static final Pattern mpaaRatingRegex1 = Pattern.compile(".*?Rated\\s(G|PG-13|PG|R|NC-17|NR|X).*", Pattern.CANON_EQ);
	private static final Pattern mpaaRatingRegex2 = Pattern.compile(".*?(G|PG-13|PG|R|NC-17|NR|X)\\sRated.*", Pattern.CANON_EQ);
	private static final Pattern mpaaRatingRegex3 = Pattern.compile(".*?MPAA rating:\\s(G|PG-13|PG|R|NC-17|NR|X).*", Pattern.CANON_EQ);
	private static final Pattern mpaaNotRatedRegex = Pattern.compile("Rated\\sNR\\.?|Not Rated\\.?|NR");
	private final HashSet<String> unknownSubjectForms = new HashSet<>();

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
		formatClassifier = new MarcRecordFormatClassifier(logger);
	}

	/**
	 * Load MARC record from disk based on identifier
	 * Then call updateGroupedWorkSolrDataBasedOnMarc to do the actual update of the work
	 *
	 * @param groupedWork the work to be updated
	 * @param identifier the identifier to load information for
	 * @param logEntry the log entry to store any errors
	 */
	public synchronized void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry){
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
			org.marc4j.marc.Record record = indexer.loadMarcRecordFromDatabase(this.profileType, identifier, logEntry);
			if (record == null) {
				try {
					//We don't have data for this MARC record, mark it as suppressed for not having MARC data
					marcRecordAsSuppressedNoMarcStmt.setString(1, this.profileType);
					marcRecordAsSuppressedNoMarcStmt.setString(2, identifier);
					marcRecordAsSuppressedNoMarcStmt.executeUpdate();
				} catch (SQLException e) {
					logEntry.incErrors("Error marking record as suppressed for not having MARC", e);
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

	protected void loadSubjects(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record){
		List<DataField> subjectFields = MarcUtil.getDataFields(record, new int[]{600, 610, 611, 630, 648, 650, 651, 655, 690, 691});

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
							if (settings.isIncludePersonalAndCorporateNamesInTopics()) {
								groupedWork.addTopic(curSubfield.getData());
							}
						}
						if (curSubfield.getCode() == 'a' || curSubfield.getCode() == 'x') {
							if (settings.isIncludePersonalAndCorporateNamesInTopics()) {
								groupedWork.addTopicFacet(curSubfield.getData());
							}
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
							if (settings.isIncludePersonalAndCorporateNamesInTopics()) {
								groupedWork.addTopic(curSubfield.getData());
							}
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
				case "690":
				case "691":
				{
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

	void updateGroupedWorkSolrDataBasedOnStandardMarcData(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, ArrayList<ItemInfo> printItems, String identifier, String format, String formatCategory, boolean hasParentRecord) {
		loadTitles(groupedWork, record, format, formatCategory, hasParentRecord);
		loadAuthors(groupedWork, record, identifier, formatCategory);
		loadSubjects(groupedWork, record);

		List<DataField> personalNameFields = MarcUtil.getDataFields(record, 600);
		for (DataField nameField : personalNameFields) {
			String name = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(nameField, "abd", "")).toString();
			groupedWork.addPersonalNameSubject(name);
		}
		List<DataField> corporateNameFields = MarcUtil.getDataFields(record, 610);
		for (DataField nameField : corporateNameFields) {
			String name = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(nameField, "abd", "")).toString();
			groupedWork.addCorporateNameSubject(name);
		}

		boolean foundSeriesIn800or830 = false;
		List<DataField> seriesFields = MarcUtil.getDataFields(record, 830);
		for (DataField seriesField : seriesFields){
			String series = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "anp"," ")).toString();
			//Remove anything in parentheses since it's normally just the format
			//series = series.replaceAll("\\s+\\(.*?\\)", "");
			//Remove the word series at the end since this gets cataloged inconsistently
			series = series.replaceAll("(?i)\\s+series$", "");
			String seriesLanguage = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "l","")).toString();
			if (!seriesLanguage.isEmpty()) {
				series = series + " (" + seriesLanguage + ")";
			}
			String volume = "";
			if (seriesField.getSubfield('v') != null){
				//Separate out the volume so we can link specially
				volume = seriesField.getSubfield('v').getData();
			}
			groupedWork.addSeriesWithVolume(series, volume);
			foundSeriesIn800or830 = true;
		}
		seriesFields = MarcUtil.getDataFields(record, 800);
		for (DataField seriesField : seriesFields){
			String series = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "pqt","")).toString();
			String seriesLanguage = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "l","")).toString();
			if (!seriesLanguage.isEmpty()) {
				series = series + " (" + seriesLanguage + ")";
			}
			//Remove anything in parentheses since it's normally just the format
			//No longer remove parentheses to better differentiate series
			//series = series.replaceAll("\\s+\\(.*?\\)", "");
			//Remove the word series at the end since this gets cataloged inconsistently
			series = series.replaceAll("(?i)\\s+series$", "");

			String volume = "";
			if (seriesField.getSubfield('v') != null){
				//Separate out the volume so we can link specially
				volume = seriesField.getSubfield('v').getData();
			}
			groupedWork.addSeriesWithVolume(series, volume);
			foundSeriesIn800or830 = true;
		}
		if (!foundSeriesIn800or830){
			seriesFields = MarcUtil.getDataFields(record, 490);
			for (DataField seriesField : seriesFields){
				String series = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getSpecifiedSubfieldsAsString(seriesField, "a","")).toString();
				//Remove anything in parentheses since it's normally just the format
				//series = series.replaceAll("\\s+\\(.*?\\)", "");
				//Remove the word series at the end since this gets cataloged inconsistently
				series = series.replaceAll("(?i)\\s+series$", "");

				String volume = "";
				if (seriesField.getSubfield('v') != null){
					//Separate out the volume so we can link specially
					volume = seriesField.getSubfield('v').getData();
				}
				groupedWork.addSeriesWithVolume(series, volume);
			}
		}

		if (foundSeriesIn800or830) {
			groupedWork.addSeries(MarcUtil.getFieldList(record, "830ap:800pqt"));
			groupedWork.addSeries2(MarcUtil.getFieldList(record, "490a"));
		}else{
			groupedWork.addSeries(MarcUtil.getFieldList(record, "490a"));
		}
		groupedWork.addDateSpan(MarcUtil.getFieldList(record, "362a"));
		groupedWork.addContents(MarcUtil.getFieldList(record, "505a:505t"));
		//Check to see if we have any child records and if so add them as well
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
		//Settings are nullable for eContent that is in MARC format (i.e. cloudLibrary)
		if (settings != null && settings.getCustomMarcFieldsToIndexAsKeyword() != null && !settings.getCustomMarcFieldsToIndexAsKeyword().isEmpty()) {
			try {
				groupedWork.addKeywords(MarcUtil.getCustomSearchableFields(record, settings.getCustomMarcFieldsToIndexAsKeyword()));
			}catch (Exception e){
				if (!loggedCustomMarcError) {
					indexer.getLogEntry().incErrors("Error processing custom marc fields to index as keyword", e);
					loggedCustomMarcError = true;
				}
			}
		}
	}
	private static boolean loggedCustomMarcError = false;

	private static final Pattern lexileMatchingPattern = Pattern.compile("(AD|NC|HL|IG|GN|BR|NP)(\\d+)");
	private void loadLexileScore(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record) {
		List<DataField> targetAudiences = MarcUtil.getDataFields(record, 521);
		for (DataField targetAudience : targetAudiences){
			Subfield subfieldA = targetAudience.getSubfield('a');
			Subfield subfieldB = targetAudience.getSubfield('b');
			if (subfieldA != null && subfieldB != null){
				if (subfieldB.getData().toLowerCase().startsWith("lexile")){
					String lexileValue = subfieldA.getData();
					if (lexileValue.endsWith("L")){
						lexileValue = lexileValue.substring(0, lexileValue.length() - 1).trim();
					}
					if (AspenStringUtils.isNumeric(lexileValue)) {
						AspenStringUtils.trimTrailingPunctuation(lexileValue);
						if (lexileValue.contains(".")) {
							//We expect the number to be an integer so trim anything that looks like a decimal
							lexileValue = lexileValue.substring(0, lexileValue.indexOf('.')).trim();
						}
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

	private void loadFountasPinnell(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record) {
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

	private void loadAwards(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record){
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


	protected abstract void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String identifier);

	void loadEditions(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, HashSet<RecordInfo> ilsRecords) {
		Set<String> editions = MarcUtil.getFieldList(record, "250a");
		if (!editions.isEmpty()) {
			String edition = editions.iterator().next();
			for (RecordInfo ilsRecord : ilsRecords) {
				ilsRecord.setEdition(edition);
			}
		}
		groupedWork.addEditions(editions);
	}

	void loadPhysicalDescription(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, HashSet<RecordInfo> ilsRecords) {
		Set<String> physicalDescriptions = MarcUtil.getFieldList(record, "300abcefg:530abcd");
		if (!physicalDescriptions.isEmpty()){
			String physicalDescription = physicalDescriptions.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPhysicalDescription(physicalDescription);
			}
		}
		groupedWork.addPhysical(physicalDescriptions);
	}

	private String getCallNumberSubject(org.marc4j.marc.Record record) {
		String val = MarcUtil.getFirstFieldVal(record, "090a:050a");

		if (val != null) {
			String[] callNumberSubject = val.toUpperCase().split("[^A-Z]+");
			if (callNumberSubject.length > 0) {
				return callNumberSubject[0];
			}
		}
		return null;
	}

	private String getMpaaRating(org.marc4j.marc.Record record) {
		String val = MarcUtil.getFirstFieldVal(record, "521a");

		if (val != null) {
			if (mpaaNotRatedRegex.matcher(val).matches()) {
				return "Not Rated";
			}
			try {
				Matcher mpaaMatcher1 = mpaaRatingRegex1.matcher(val);
				if (mpaaMatcher1.find()) {
					return mpaaMatcher1.group(1) + " Rated";
				} else {
					Matcher mpaaMatcher2 = mpaaRatingRegex2.matcher(val);
					if (mpaaMatcher2.find()) {
						return mpaaMatcher2.group(1) + " Rated";
					} else {
						Matcher mpaaMatcher3 = mpaaRatingRegex3.matcher(val);
						if (mpaaMatcher3.find()) {
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

	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, ArrayList<ItemInfo> printItems, String identifier) {
		loadTargetAudiences(groupedWork, record, printItems, identifier, "Unknown");
	}

	@SuppressWarnings("unused")
	protected void loadTargetAudiences(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, ArrayList<ItemInfo> printItems, String identifier, String unknownAudienceLabel) {
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
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience determined by 006/5", 2);}
					}
				}
				if (targetAudiences.isEmpty() && ohOhEightField != null && ohOhEightField.getData().length() > 22) {
					targetAudienceChar = Character.toUpperCase(ohOhEightField.getData().charAt(22));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience determined by 008/22", 2);}
					}
				} else if (targetAudiences.isEmpty()) {
					targetAudiences.add(unknownAudienceLabel);
					if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience " + unknownAudienceLabel + " because 006/5 and 008/22 did not exist", 2);}
				}
			} else {
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience unknown based on Leader 6/7", 2);}
				targetAudiences.add(unknownAudienceLabel);
			}
		} catch (Exception e) {
			// leader not long enough to get target audience
			logger.debug("ERROR in getTargetAudience ", e);
			targetAudiences.add(unknownAudienceLabel);
		}

		if (targetAudiences.isEmpty()) {
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is " + unknownAudienceLabel + " because 006/5 and 008/22 were both blank", 2);}
			targetAudiences.add(unknownAudienceLabel);
		}

		LinkedHashSet<String> translatedAudiences;
		if (settings == null) {
			translatedAudiences = indexer.translateSystemCollection("target_audience", targetAudiences, identifier);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is " + translatedAudiences + " based on system target_audience translation map", 2);}
		}else{
			translatedAudiences = settings.translateCollection("target_audience", targetAudiences, identifier, indexer.getLogEntry(), logger, true);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is " + translatedAudiences + " based on target_audience translation map in settings", 2);}
		}
		if (!unknownAudienceLabel.equals("Unknown") && translatedAudiences.contains("Unknown")){
			translatedAudiences.remove("Unknown");
			translatedAudiences.add(unknownAudienceLabel);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Updating unknown target audience to " + unknownAudienceLabel, 2);}
		}
		groupedWork.addTargetAudiences(translatedAudiences);
		LinkedHashSet<String> translatedAudiencesFull;
		if (settings == null) {
			translatedAudiencesFull = indexer.translateSystemCollection("target_audience_full", targetAudiences, identifier);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is " + translatedAudiencesFull + " based on system target_audience translation map", 2);}
		}else {
			translatedAudiencesFull = settings.translateCollection("target_audience_full", targetAudiences, identifier, indexer.getLogEntry(), logger, true);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is " + translatedAudiencesFull + " based on target_audience translation map in settings", 2);}
		}
		if (!unknownAudienceLabel.equals("Unknown") && translatedAudiencesFull.contains("Unknown")){
			translatedAudiencesFull.remove("Unknown");
			translatedAudiencesFull.add(unknownAudienceLabel);
			if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Updating unknown full target audience to " + unknownAudienceLabel, 2);}
		}
		groupedWork.addTargetAudiencesFull(translatedAudiencesFull);
	}

	protected void loadLiteraryForms(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, ArrayList<ItemInfo> printItems, String identifier) {
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
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record is a book based on Leader and Literary Form is " + indexer.translateSystemCollection("literary_form", literaryForms, identifier).toString() + " based on Leader and 006", 2);}
					}
				}
				if (literaryForms.isEmpty() && ohOhEightField != null && ohOhEightField.getData().length() > 33) {
					literaryFormChar = Character.toUpperCase(ohOhEightField.getData().charAt(33));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record is a book based on Leader 6/7 and Literary Form is  " + indexer.translateSystemCollection("literary_form", literaryForms, identifier).toString() + " based on Leader and 008 (counts for 2 points)", 2);}
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
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record is audio based on based on Leader 6/7 and Literary Form is fiction based on 008 position 30, 31 (counts for 2 points)", 2);}
					}else if ((position30 == '|' || position30 == ' ') && (position31 == '|' || position31 == ' ')){
						addToMapWithCount(literaryFormsWithCount, "Not Coded", 1);
						addToMapWithCount(literaryFormsFull, "Not Coded", 1);
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record is audio based on based on Leader 6/7 and Literary Form is not coded based on 008 position 30, 31", 2);}
					}else{
						addToMapWithCount(literaryFormsWithCount, "Non Fiction", 2);
						addToMapWithCount(literaryFormsFull, "Non Fiction", 2);
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record is audio based on Leader 6/7 and Literary Form is non fiction based on 008 position 30, 31 (counts for 2 points)", 2);}
					}
				}
			} else {
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Could not determine literary form based on Leader 6/7", 2);}
			}
		} catch (Exception e) {
			indexer.getLogEntry().incErrors("Unexpected error loading literary forms", e);
		}

		//Check the subjects
		Set<String> subjectFormData = MarcUtil.getFieldList(record, "650v:651v");
		for(String subjectForm : subjectFormData){
			subjectForm = AspenStringUtils.trimTrailingPunctuation(subjectForm);
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
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction based on 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("Biography")){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction based on 'biography' in 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("Novela juvenil")
					|| subjectForm.equalsIgnoreCase("Novela")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Novels");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction/novel based on 'novela' in 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("Drama")
					|| subjectForm.equalsIgnoreCase("Dramas")
					|| subjectForm.equalsIgnoreCase("Juvenile drama")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Dramas");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction/drama based on 'drama' in 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("Poetry")
					|| subjectForm.equalsIgnoreCase("Juvenile Poetry")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Poetry");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction/poetry based on 'poetry' in 650v, 651v", 2);}
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
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction/humor based on 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("Correspondence")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Letters");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction/letters based on 'correspondence' in 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("Short Stories")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Short Stories");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction/short stories based on 'short stories' in 650v, 651v", 2);}
			}else if (subjectForm.equalsIgnoreCase("essays")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Essays");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction/essays based on 'essays' in 650v, 651v", 2);}
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
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction based on 650v, 651v", 2);}
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
			subjectForm = AspenStringUtils.trimTrailingPunctuation(subjectForm).toLowerCase();
			if (subjectForm.startsWith("instructional film")
					|| subjectForm.startsWith("educational film")
					) {
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction based on 655a", 2);}
			}
			if (subjectForm.startsWith("fiction")) {
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction based on 655a", 2);}
			}
		}
		groupedWork.addLiteraryForms(literaryFormsWithCount);
		groupedWork.addLiteraryFormsFull(literaryFormsFull);
	}

	private void addToMapWithCount(HashMap<String, Integer> map, HashSet<String> elementsToAdd, @SuppressWarnings("SameParameterValue") int numberToAdd){
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

	void loadClosedCaptioning(org.marc4j.marc.Record record, HashSet<RecordInfo> ilsRecords){
		//Based on the 546 fields determine if the record is closed captioned
		Pattern closedCaptionPattern = Pattern.compile("\\b(closed?[- ]caption|hearing impaired)", Pattern.CASE_INSENSITIVE);
		Set<String> languageNoteFields = MarcUtil.getFieldList(record, "546a");
		boolean isClosedCaptioned = false;
		for (String languageNoteField: languageNoteFields){
			if (closedCaptionPattern.matcher(languageNoteField).matches()) {
				isClosedCaptioned = true;
				break;
			}
		}
		if (!isClosedCaptioned) {
			//Based on the 650/655 fields determine if the record is closed captioned
			Set<String> subjectFields = MarcUtil.getFieldList(record, "655a:650a");
			for (String subjectField : subjectFields) {
				if (subjectField.toLowerCase(Locale.ROOT).startsWith("video recordings for the hearing impaired")) {
					isClosedCaptioned = true;
					break;
				} else if (subjectField.toLowerCase(Locale.ROOT).startsWith("closed caption")) {
					isClosedCaptioned = true;
					break;
				}
			}
		}
		if (isClosedCaptioned){
			for (RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setClosedCaptioned(true);
			}
		}
	}

	void loadPublicationDetails(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, HashSet<RecordInfo> ilsRecords) {
		//Load publishers
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		if (!publishers.isEmpty()){
			String publisher = publishers.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPublisher(publisher);
			}
		}

		//Load publication dates
		Set<String> publicationDates = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDates);
		if (!publicationDates.isEmpty()){
			String publicationDate = publicationDates.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPublicationDate(publicationDate);
			}
		}

		//load places of publication
		Set<String> placesOfPublication = this.getPlacesOfPublication(record);
		groupedWork.addPlacesOfPublication(placesOfPublication);
		if (!placesOfPublication.isEmpty()){
			String placeOfPublication = placesOfPublication.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords) {
				ilsRecord.setPlaceOfPublication(placeOfPublication);
			}
		}
	}

	private Set<String> getPublicationDates(org.marc4j.marc.Record record) {
		List<DataField> rdaFields = record.getDataFields(264);
		HashSet<String> publicationDates = new HashSet<>();
		String date;
		//Try to get from RDA data
		if (!rdaFields.isEmpty()){
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
		if (publicationDates.isEmpty()) {
			publicationDates.addAll(AspenStringUtils.trimTrailingPunctuation(MarcUtil.getFieldList(record, "260c")));
		}
		//Try to get from 008, but only need to do if we don't have anything else
		if (publicationDates.isEmpty()) {
			publicationDates.add(AspenStringUtils.trimTrailingPunctuation(MarcUtil.getFirstFieldVal(record, "008[7-10]")));
		}

		return publicationDates;
	}

	private Set<String> getPlacesOfPublication(org.marc4j.marc.Record record) {
		List<DataField> rdaFields = record.getDataFields(264);
		HashSet<String> placesOfPublication = new HashSet<>();
		String place;
		if(!rdaFields.isEmpty()) {
			for(DataField dataField : rdaFields){
				if (dataField.getIndicator2() == '1') {
					Subfield subFieldA = dataField.getSubfield('a');
					if (subFieldA != null) {
						place = subFieldA.getData();
						placesOfPublication.add(AspenStringUtils.trimTrailingPunctuation(place));
					}
				}
			}
		}
		//Try field 260
		if (placesOfPublication.isEmpty()) {
			placesOfPublication.addAll(AspenStringUtils.trimTrailingPunctuation(MarcUtil.getFieldList(record, "260a")));
		}
		//Try 008
		if(placesOfPublication.isEmpty()) {
			// placesOfPublication.add(AspenStringUtils.trimTrailingPunctuation(MarcUtil.getFirstFieldVal(record, "008[15-17]")));
			String countryCode = AspenStringUtils.trimTrailingPunctuation(MarcUtil.getFirstFieldVal(record, "008[15-17]"));
			if (!countryCode.isEmpty()) {
				String country = codeToCountry(countryCode);
				if (country != null) {
					placesOfPublication.add(country);
				}
			}
		}
		return placesOfPublication;
	}

	private static final HashMap<String, String> countryList = new HashMap<>();
	public static String codeToCountry(String code) {
		code = code.toUpperCase().trim();

		if (countryList.isEmpty()) {
			countryList.put("AF", "Afghanistan");
			countryList.put("AX", "Aland Islands");
			countryList.put("AL", "Albania");
			countryList.put("DZ", "Algeria");
			countryList.put("AS", "American Samoa");
			countryList.put("AD", "Andorra");
			countryList.put("AO", "Angola");
			countryList.put("AI", "Anguilla");
			countryList.put("AQ", "Antigua and Barbuda");
			countryList.put("AR", "Argentina");
			countryList.put("AM", "Armenia");
			countryList.put("AW", "Aruba");
			countryList.put("AU", "Australia");
			countryList.put("AT", "Austria");
			countryList.put("AZ", "Azerbaijan");
			countryList.put("BS", "Bahamas the");
			countryList.put("BH", "Bahrain");
			countryList.put("BD", "Bangladesh");
			countryList.put("BB", "Barbados");
			countryList.put("BY", "Belarus");
			countryList.put("BE", "Belgium");
			countryList.put("BZ", "Belize");
			countryList.put("BJ", "Benin");
			countryList.put("BM", "Bermuda");
			countryList.put("BT", "Bhutan");
			countryList.put("BO", "Bolivia");
			countryList.put("BA", "Bosnia and Herzegovina");
			countryList.put("BW", "Botswana");
			//noinspection SpellCheckingInspection
			countryList.put("BV", "Bouvet Island (Bouvetoya)");
			countryList.put("BR", "Brazil");
			//noinspection SpellCheckingInspection
			countryList.put("IO", "British Indian Ocean Territory (Chagos Archipelago)");
			countryList.put("VG", "British Virgin Islands");
			//noinspection SpellCheckingInspection
			countryList.put("BN", "Brunei Darussalam");
			countryList.put("BG", "Bulgaria");
			countryList.put("BF", "Burkina Faso");
			countryList.put("BI", "Burundi");
			countryList.put("KH", "Cambodia");
			countryList.put("CM", "Cameroon");
			countryList.put("CA", "Canada");
			countryList.put("CV", "Cape Verde");
			countryList.put("KY", "Cayman Islands");
			countryList.put("CF", "Central African Republic");
			countryList.put("TD", "Chad");
			countryList.put("CL", "Chile");
			countryList.put("CN", "China");
			countryList.put("CX", "Christmas Island");
			countryList.put("CC", "Cocos (Keeling) Islands");
			countryList.put("CO", "Colombia");
			countryList.put("KM", "Comoros the");
			countryList.put("CD", "Congo");
			countryList.put("CG", "Congo the");
			countryList.put("CK", "Cook Islands");
			//noinspection SpellCheckingInspection
			countryList.put("CR", "Costa Rica");
			//noinspection SpellCheckingInspection
			countryList.put("CI", "Cote d'Ivoire");
			countryList.put("HR", "Croatia");
			countryList.put("CU", "Cuba");
			countryList.put("CY", "Cyprus");
			countryList.put("CZ", "Czech Republic");
			countryList.put("DK", "Denmark");
			countryList.put("DJ", "Djibouti");
			countryList.put("DM", "Dominica");
			countryList.put("DO", "Dominican Republic");
			countryList.put("EC", "Ecuador");
			countryList.put("EG", "Egypt");
			countryList.put("SV", "El Salvador");
			countryList.put("GQ", "Equatorial Guinea");
			countryList.put("ER", "Eritrea");
			countryList.put("EE", "Estonia");
			countryList.put("ET", "Ethiopia");
			//noinspection SpellCheckingInspection
			countryList.put("FO", "Faroe Islands");
			//noinspection SpellCheckingInspection
			countryList.put("FK", "Falkland Islands (Malvinas)");
			countryList.put("FJ", "Fiji the Fiji Islands");
			countryList.put("FI", "Finland");
			countryList.put("FR", "France, French Republic");
			countryList.put("GF", "French Guiana");
			countryList.put("PF", "French Polynesia");
			countryList.put("TF", "French Southern Territories");
			countryList.put("GA", "Gabon");
			countryList.put("GM", "Gambia the");
			countryList.put("GE", "Georgia");
			countryList.put("DE", "Germany");
			countryList.put("GH", "Ghana");
			countryList.put("GI", "Gibraltar");
			countryList.put("GR", "Greece");
			countryList.put("GL", "Greenland");
			countryList.put("GD", "Grenada");
			countryList.put("GP", "Guadeloupe");
			countryList.put("GU", "Guam");
			countryList.put("GT", "Guatemala");
			countryList.put("GG", "Guernsey");
			countryList.put("GN", "Guinea");
			countryList.put("GW", "Guinea-Bissau");
			countryList.put("GY", "Guyana");
			countryList.put("HT", "Haiti");
			countryList.put("HM", "Heard Island and McDonald Islands");
			countryList.put("VA", "Holy See (Vatican City State)");
			countryList.put("HN", "Honduras");
			countryList.put("HK", "Hong Kong");
			countryList.put("HU", "Hungary");
			countryList.put("IS", "Iceland");
			countryList.put("IN", "India");
			countryList.put("ID", "Indonesia");
			countryList.put("IR", "Iran");
			countryList.put("IQ", "Iraq");
			countryList.put("IE", "Ireland");
			countryList.put("IM", "Isle of Man");
			countryList.put("IL", "Israel");
			countryList.put("IT", "Italy");
			countryList.put("JM", "Jamaica");
			countryList.put("JA", "Japan");
			countryList.put("JP", "Japan");
			countryList.put("JE", "Jersey");
			countryList.put("JO", "Jordan");
			countryList.put("KZ", "Kazakhstan");
			countryList.put("KE", "Kenya");
			countryList.put("KI", "Kiribati");
			countryList.put("KP", "Korea");
			countryList.put("KR", "Korea");
			countryList.put("KW", "Kuwait");
			//noinspection SpellCheckingInspection
			countryList.put("KG", "Kyrgyz Republic");
			countryList.put("LA", "Lao");
			countryList.put("LV", "Latvia");
			countryList.put("LB", "Lebanon");
			countryList.put("LS", "Lesotho");
			countryList.put("LR", "Liberia");
			//noinspection SpellCheckingInspection
			countryList.put("LY", "Libyan Arab Jamahiriya");
			countryList.put("LI", "Liechtenstein");
			countryList.put("LT", "Lithuania");
			countryList.put("LU", "Luxembourg");
			countryList.put("MO", "Macao");
			countryList.put("MK", "Macedonia");
			countryList.put("MG", "Madagascar");
			countryList.put("MW", "Malawi");
			countryList.put("MY", "Malaysia");
			countryList.put("MV", "Maldives");
			countryList.put("ML", "Mali");
			countryList.put("MT", "Malta");
			countryList.put("MH", "Marshall Islands");
			countryList.put("MQ", "Martinique");
			countryList.put("MR", "Mauritania");
			countryList.put("MU", "Mauritius");
			//noinspection SpellCheckingInspection
			countryList.put("YT", "Mayotte");
			countryList.put("MX", "Mexico");
			countryList.put("FM", "Micronesia");
			countryList.put("MD", "Moldova");
			countryList.put("MC", "Monaco");
			countryList.put("MN", "Mongolia");
			countryList.put("ME", "Montenegro");
			countryList.put("MS", "Montserrat");
			countryList.put("MA", "Morocco");
			countryList.put("MZ", "Mozambique");
			countryList.put("MM", "Myanmar");
			countryList.put("NA", "Namibia");
			countryList.put("NR", "Nauru");
			countryList.put("NP", "Nepal");
			countryList.put("AN", "Netherlands Antilles");
			countryList.put("NL", "Netherlands the");
			countryList.put("NC", "New Caledonia");
			countryList.put("NZ", "New Zealand");
			countryList.put("NI", "Nicaragua");
			countryList.put("NE", "Niger");
			countryList.put("NG", "Nigeria");
			//noinspection SpellCheckingInspection
			countryList.put("NU", "Niue");
			countryList.put("NF", "Norfolk Island");
			countryList.put("MP", "Northern Mariana Islands");
			countryList.put("NO", "Norway");
			countryList.put("OM", "Oman");
			countryList.put("PK", "Pakistan");
			//noinspection SpellCheckingInspection
			countryList.put("PW", "Palau");
			countryList.put("PS", "Palestinian Territory");
			countryList.put("PA", "Panama");
			countryList.put("PG", "Papua New Guinea");
			countryList.put("PY", "Paraguay");
			countryList.put("PE", "Peru");
			countryList.put("PH", "Philippines");
			countryList.put("PN", "Pitcairn Islands");
			countryList.put("PL", "Poland");
			countryList.put("PT", "Portugal, Portuguese Republic");
			countryList.put("PR", "Puerto Rico");
			countryList.put("QA", "Qatar");
			countryList.put("RE", "Reunion");
			countryList.put("RO", "Romania");
			countryList.put("RU", "Russian Federation");
			countryList.put("RW", "Rwanda");
			//noinspection SpellCheckingInspection
			countryList.put("BL", "Saint Barthelemy");
			countryList.put("SH", "Saint Helena");
			//noinspection SpellCheckingInspection
			countryList.put("KN", "Saint Kitts and Nevis");
			countryList.put("LC", "Saint Lucia");
			countryList.put("MF", "Saint Martin");
			countryList.put("PM", "Saint Pierre and Miquelon");
			countryList.put("VC", "Saint Vincent and the Grenadines");
			countryList.put("WS", "Samoa");
			countryList.put("SM", "San Marino");
			countryList.put("ST", "Sao Tome and Principe");
			countryList.put("SA", "Saudi Arabia");
			countryList.put("SN", "Senegal");
			countryList.put("RS", "Serbia");
			countryList.put("SC", "Seychelles");
			countryList.put("SL", "Sierra Leone");
			countryList.put("SG", "Singapore");
			countryList.put("SK", "Slovakia (Slovak Republic)");
			countryList.put("SI", "Slovenia");
			countryList.put("SB", "Solomon Islands");
			countryList.put("SO", "Somalia, Somali Republic");
			countryList.put("ZA", "South Africa");
			countryList.put("GS", "South Georgia and the South Sandwich Islands");
			countryList.put("ES", "Spain");
			countryList.put("LK", "Sri Lanka");
			countryList.put("SD", "Sudan");
			countryList.put("SR", "Suriname");
			//noinspection SpellCheckingInspection
			countryList.put("SJ", "Svalbard & Jan Mayen Islands");
			countryList.put("SZ", "Swaziland");
			countryList.put("SE", "Sweden");
			countryList.put("CH", "Switzerland, Swiss Confederation");
			countryList.put("SY", "Syrian Arab Republic");
			countryList.put("TW", "Taiwan");
			countryList.put("TJ", "Tajikistan");
			countryList.put("TZ", "Tanzania");
			countryList.put("TH", "Thailand");
			countryList.put("TL", "Timor-Leste");
			countryList.put("TG", "Togo");
			//noinspection SpellCheckingInspection
			countryList.put("TK", "Tokelau");
			countryList.put("TO", "Tonga");
			countryList.put("TT", "Trinidad and Tobago");
			countryList.put("TN", "Tunisia");
			countryList.put("TR", "Turkey");
			countryList.put("TM", "Turkmenistan");
			//noinspection SpellCheckingInspection
			countryList.put("TC", "Turks and Caicos Islands");
			countryList.put("TV", "Tuvalu");
			countryList.put("UG", "Uganda");
			countryList.put("UA", "Ukraine");
			countryList.put("AE", "United Arab Emirates");
			countryList.put("GB", "United Kingdom");
			countryList.put("US", "United States of America");
			countryList.put("UM", "United States Minor Outlying Islands");
			countryList.put("VI", "United States Virgin Islands");
			countryList.put("UY", "Uruguay, Eastern Republic of");
			countryList.put("UZ", "Uzbekistan");
			countryList.put("VU", "Vanuatu");
			countryList.put("VE", "Venezuela");
			countryList.put("VN", "Vietnam");
			//noinspection SpellCheckingInspection
			countryList.put("WF", "Wallis and Futuna");
			countryList.put("EH", "Western Sahara");
			countryList.put("YE", "Yemen");
			countryList.put("ZM", "Zambia");
			countryList.put("ZW", "Zimbabwe");
			countryList.put("XXC","Canada");
			countryList.put("ABC","Alberta");
			countryList.put("BCC","British Columbia");
			countryList.put("MBC","Manitoba");
			countryList.put("NKC","New Brunswick");
			countryList.put("NFC","Newfoundland and Labrador");
			countryList.put("NTC","Northwest Territories");
			countryList.put("NSC","Nova Scotia");
			countryList.put("NUC","Nunavut");
			countryList.put("ONC","Ontario");
			countryList.put("PIC","Prince Edward Island");
			countryList.put("QUC","Québec (Province)");
			countryList.put("SNC","Saskatchewan");
			countryList.put("YKC","Yukon Territory");
			countryList.put("XL","Saint Pierre and Miquelon");
			countryList.put("XXU","United States");
			countryList.put("ALU","Alabama");
			countryList.put("AKU","Alaska");
			countryList.put("AZU","Arizona");
			countryList.put("ARU","Arkansas");
			countryList.put("CAU","California");
			countryList.put("COU","Colorado");
			countryList.put("CTU","Connecticut");
			countryList.put("DEU","Delaware");
			countryList.put("DCU","District of Columbia");
			countryList.put("FLU","Florida");
			countryList.put("GAU","Georgia");
			countryList.put("HIU","Hawaii");
			countryList.put("IDU","Idaho");
			countryList.put("ILU","Illinois");
			countryList.put("INU","Indiana");
			countryList.put("IAU","Iowa");
			countryList.put("KSU","Kansas");
			countryList.put("KYU","Kentucky");
			countryList.put("LAU","Louisiana");
			countryList.put("MEU","Maine");
			countryList.put("MDU","Maryland");
			countryList.put("MAU","Massachusetts");
			countryList.put("MIU","Michigan");
			countryList.put("MNU","Minnesota");
			countryList.put("MSU","Mississippi");
			countryList.put("MOU","Missouri");
			countryList.put("MTU","Montana");
			countryList.put("NBU","Nebraska");
			countryList.put("NVU","Nevada");
			countryList.put("NHU","New Hampshire");
			countryList.put("NJU","New Jersey");
			countryList.put("NMU","New Mexico");
			countryList.put("NYU","New York (State)");
			countryList.put("NCU","North Carolina");
			countryList.put("NDU","North Dakota");
			countryList.put("OHU","Ohio");
			countryList.put("OKU","Oklahoma");
			countryList.put("ORU","Oregon");
			countryList.put("PAU","Pennsylvania");
			countryList.put("RIU","Rhode Island");
			countryList.put("SCU","South Carolina");
			countryList.put("SDU","South Dakota");
			countryList.put("TNU","Tennessee");
			countryList.put("TXU","Texas");
			countryList.put("UTU","Utah");
			countryList.put("VTU","Vermont");
			countryList.put("VAU","Virginia");
			countryList.put("WAU","Washington (State)");
			countryList.put("WVU","West Virginia");
			countryList.put("WIU","Wisconsin");
			countryList.put("WYU","Wyoming");
			countryList.put("XXK","United Kingdom");
			countryList.put("ENK","England");
			countryList.put("NIK","Northern Ireland");
			countryList.put("STK","Scotland");
			countryList.put("UIK","United Kingdom Misc. Islands");
			countryList.put("WLK","Wales");
			countryList.put("ACA","Australian Capital Territory");
			countryList.put("QEA","Queensland");
			countryList.put("TMA","Tasmania");
			countryList.put("VRA","Victoria");
			countryList.put("WEA","Western Australia");
			countryList.put("XGA","Coral Sea Islands Territory");
			countryList.put("XNA","New South Wales");
			countryList.put("XOA","Northern Territory");
			countryList.put("XRA","South Australia");
		}

		return countryList.get(code);

		// return countryList.getOrDefault(code, code);
	}


	private Set<String> getPublishers(org.marc4j.marc.Record record){
		Set<String> publisher = new LinkedHashSet<>();
		//First check for 264 fields
		List<DataField> rdaFields = MarcUtil.getDataFields(record, 264);
		if (!rdaFields.isEmpty()){
			for (DataField curField : rdaFields){
				if (curField.getIndicator2() == '1'){
					Subfield subFieldB = curField.getSubfield('b');
					if (subFieldB != null){
						publisher.add(AspenStringUtils.trimTrailingPunctuation(subFieldB.getData()));
					}
				}
			}
		}
		publisher.addAll(AspenStringUtils.trimTrailingPunctuation(MarcUtil.getFieldList(record, "260b")));
		return publisher;
	}

	String languageFields = "008[35-37]";

	void loadLanguageDetails(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, HashSet<RecordInfo> ilsRecords, String identifier) {
		Set <String> languages = MarcUtil.getFieldList(record, languageFields);
		HashSet<String> translatedLanguages = new HashSet<>();
		boolean isFirstLanguage = true;
		for (String language : languages){
			String translatedLanguage = indexer.translateSystemValue("language", language, identifier);
			if (settings != null) {
				if (settings.getTreatUnknownLanguageAs() != null && !settings.getTreatUnknownLanguageAs().isEmpty() && translatedLanguage.equals("Unknown")) {
					translatedLanguage = settings.getTreatUnknownLanguageAs();
				} else if (settings.getTreatUndeterminedLanguageAs() != null && !settings.getTreatUndeterminedLanguageAs().isEmpty() && translatedLanguage.equals("Undetermined")) {
					translatedLanguage = settings.getTreatUndeterminedLanguageAs();
				}
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
		if (translatedLanguages.isEmpty()){
			translatedLanguages.add(settings.getTreatUnknownLanguageAs());
			for (RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPrimaryLanguage(settings.getTreatUnknownLanguageAs());
			}
			String languageBoost = indexer.translateSystemValue("language_boost", settings.getTreatUnknownLanguageAs(), identifier);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateSystemValue("language_boost_es", settings.getTreatUnknownLanguageAs(), identifier);
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

	@SuppressWarnings("unused")
	private void loadAuthors(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String identifier, String formatCategory) {
		//auth_author = 100abcd, first
		groupedWork.setAuthAuthor(MarcUtil.getFirstFieldVal(record, "100abcd"));
		//MDN 2/6/2016 - Do not use 710 because it is not truly the author.  This has the potential
		//of showing some disconnects with how records are grouped, but improves the display of the author
		//710 is still indexed as part of author 2 #ARL-146
		//groupedWork.setAuthor(this.getFirstFieldVal(record, "100abcdq:110ab:710a"));
		groupedWork.setAuthor(MarcUtil.getFirstFieldVal(record, "100abcdq:110ab"));
		//auth_author2 = 700abcd
		groupedWork.addAuthAuthor2(MarcUtil.getFieldList(record, "700abcd"));
		//author2 = 110ab:111ab:700abcd:710ab:711ab:800a
		groupedWork.addAuthor2(MarcUtil.getFieldList(record, "110ab:111ab:700abcd:710ab:711ab:800ac"));
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

		//author_display = 100acq:110a:260b:710a:245c, first
		//#ARL-95 Do not show display author from the 710 or from the 245c since neither are truly authors
		//#ARL-200 Do not show display author from the 260b since it is also not the author
		//DIS-413 Add subfield q to the display
		String displayAuthor = MarcUtil.getFirstFieldVal(record, "100acq:110ab");
		if (displayAuthor != null && displayAuthor.indexOf(';') > 0){
			displayAuthor = displayAuthor.substring(0, displayAuthor.indexOf(';') -1);
		}
		groupedWork.setAuthorDisplay(displayAuthor, formatCategory);
	}

	private void loadTitles(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String format, String formatCategory, boolean hasParentRecord) {
		//title (full title done by index process by concatenating short and subtitle

		//title short
		DataField titleField = record.getDataField(245);
		String authorInTitleField = null;
		if (titleField != null) {
			//noinspection SpellCheckingInspection
			String subTitle = titleField.getSubfieldsAsString("bfgnp");
			if (!hasParentRecord) {
				//noinspection SpellCheckingInspection
				groupedWork.setTitle(titleField.getSubfieldsAsString("a"), subTitle, titleField.getSubfieldsAsString("abfgnp"), this.getSortableTitle(record), format, formatCategory);
			}
			//title full
			authorInTitleField = titleField.getSubfieldsAsString("c");
		}
		String standardAuthorData = MarcUtil.getFirstFieldVal(record, "100abcdq:110ab");
		if ((authorInTitleField != null && !authorInTitleField.isEmpty()) || (standardAuthorData == null || standardAuthorData.isEmpty())) {
			groupedWork.addFullTitles(MarcUtil.getAllSubfields(record, "245", " "));
		} else {
			//We didn't get an author from the 245, combine with the 100
			Set<String> titles = MarcUtil.getAllSubfields(record, "245", " ");
			for (String title : titles) {
				groupedWork.addFullTitle(title + " " + standardAuthorData);
			}
		}

		//title alt
		//noinspection SpellCheckingInspection
		groupedWork.addAlternateTitles(MarcUtil.getFieldList(record, "130adfgklnpst:240a:246abfgnp:700tnr:730adfgklnpst:740a:505t"));
		//title old
		groupedWork.addOldTitles(MarcUtil.getFieldList(record, "780ast"));
		//title new
		groupedWork.addNewTitles(MarcUtil.getFieldList(record, "785ast"));
	}

	private void loadBibCallNumbers(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String identifier) {
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

	void loadEContentUrl(org.marc4j.marc.Record record, ItemInfo itemInfo) {
		List<DataField> urlFields = MarcUtil.getDataFields(record, 856);
		for (DataField urlField : urlFields){
			//load url into the item
			if (urlField.getSubfield('u') != null){
				String linkText = urlField.getSubfield('u').getData().trim();
				if (!linkText.isEmpty()) {
					//Try to determine if this is a resource or not.
					if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0') {
						if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '4') {
							itemInfo.seteContentUrl(urlField.getSubfield('u').getData().trim());
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Get the title from a record, without non-filing chars as specified
	 * in 245 2nd indicator, and lower cased.
	 *
	 * @return 245a and 245b and 245n and 245p values concatenated, with trailing punctuation removed, and
	 *         with non-filing characters omitted. Null returned if no title can
	 *         be found.
	 */
	private String getSortableTitle(org.marc4j.marc.Record record) {
		DataField titleField = record.getDataField(245);
		if (titleField == null || titleField.getSubfield('a') == null)
			return "";

		int nonFilingInt = getInd2AsInt(titleField);

		//noinspection SpellCheckingInspection
		String title = titleField.getSubfieldsAsString("abfgnp");
		if (title == null){
			return "";
		}
		title = title.toLowerCase();

		// Skip non-filing chars, if possible.
		if (title.length() > nonFilingInt) {
			title = title.substring(nonFilingInt);
		}

		if (title.isEmpty()) {
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

}
