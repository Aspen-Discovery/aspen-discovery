package org.aspen_discovery.grouping;

import org.aspen_discovery.format_classification.MarcRecordFormatClassifier;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;

public abstract class BaseMarcRecordGrouper extends RecordGroupingProcessor {
	private final int recordNumberTagInt;
	private final char recordNumberSubfield;
	private final String recordNumberPrefix;
	private final BaseIndexingSettings baseSettings;
	private final String treatUnknownLanguageAs;

	private final Connection dbConn;

	//Existing records
	private HashMap<String, IlsTitle> existingRecords = new HashMap<>();

	private boolean isValid = true;

	protected MarcRecordFormatClassifier formatClassifier;

	BaseMarcRecordGrouper(String serverName, BaseIndexingSettings settings, Connection dbConn, BaseIndexingLogEntry logEntry, Logger logger) {
		super(dbConn, serverName, logEntry, logger);
		this.dbConn = dbConn;
		String recordNumberTag = settings.getRecordNumberTag();
		recordNumberTagInt = Integer.parseInt(recordNumberTag);
		recordNumberSubfield = settings.getRecordNumberSubfield();
		recordNumberPrefix = settings.getRecordNumberPrefix();
		treatUnknownLanguageAs = settings.getTreatUnknownLanguageAs();

		baseSettings = settings;

		formatClassifier = new MarcRecordFormatClassifier(logger);
	}

	public abstract String processMarcRecord(Record marcRecord, boolean primaryDataChanged, String originalGroupedWorkId, GroupedWorkIndexer indexer);

	public RecordIdentifier getPrimaryIdentifierFromMarcRecord(Record marcRecord, BaseIndexingSettings indexingProfile) {
		RecordIdentifier identifier = null;
		if (marcRecord == null) {
			isValid = false;
			return null;
		}
		VariableField recordNumberField = marcRecord.getVariableField(recordNumberTagInt);
		//Make sure we only get one ils identifier
		if (recordNumberField != null) {
			if (recordNumberField instanceof DataField) {
				DataField curRecordNumberField = (DataField) recordNumberField;
				Subfield subfieldA = curRecordNumberField.getSubfield(recordNumberSubfield);
				if (subfieldA != null && (recordNumberPrefix.isEmpty() || subfieldA.getData().length() > recordNumberPrefix.length())) {
					if (subfieldA.getData().startsWith(recordNumberPrefix)) {
						String recordNumber = subfieldA.getData().trim();
						if (recordNumber.indexOf(' ') > 0){
							recordNumber = recordNumber.substring(0, recordNumber.indexOf(' '));
						}
						identifier = new RecordIdentifier(indexingProfile.getName(), recordNumber);
					}
				}
			} else {
				//It's a control field
				ControlField curRecordNumberField = (ControlField) recordNumberField;
				String recordNumber = curRecordNumberField.getData().trim();
				identifier = new RecordIdentifier(indexingProfile.getName(), recordNumber);
			}
		}

		if (identifier != null && identifier.isValid()) {
			isValid = true;
			return identifier;
		} else {
			isValid = false;
			return null;
		}
	}



	String setGroupingCategoryForWork(Record marcRecord, GroupedWork workForTitle) {
		//Format
		String groupingFormat;
		switch (baseSettings.getFormatSource()) {
			case "bib":
				String format = formatClassifier.getFirstFormatFromBib(marcRecord, baseSettings);
				String formatLower = format.toLowerCase();
				if (formatLower.contains("graphic novel") || (formatLower.contains("comic") && !formatLower.contains("ecomic")) || formatLower.contains("manga")) {
					formatLower = "graphic novel";
				}
				if (formatsToFormatCategory.containsKey(formatLower)) {
					groupingFormat = categoryMap.getOrDefault(formatsToFormatCategory.get(formatLower), "other");
				}else{
					groupingFormat = "book";
				}

				break;
			case "specified":
				//Use specified format
				String specifiedFormatCategory = baseSettings.getSpecifiedFormatCategory();
				groupingFormat = categoryMap.get(specifiedFormatCategory.toLowerCase());
				if (groupingFormat == null) {
					groupingFormat = specifiedFormatCategory;
				}
				break;
			default:
				logEntry.incErrors("Unknown setting to load format from");
				groupingFormat = "other";
		}
		workForTitle.setGroupingCategory(groupingFormat);
		return groupingFormat;
	}

	private void setWorkAuthorBasedOnMarcRecord(Record marcRecord, GroupedWork workForTitle, DataField field245, String groupingFormat) {
		String author = null;
		DataField field100 = marcRecord.getDataField(100);
		DataField field110 = marcRecord.getDataField(110);
		DataField field260 = marcRecord.getDataField(260);
		DataField field264 = marcRecord.getDataField(264);
		DataField field710 = marcRecord.getDataField(710);

		//Depending on the format we will promote the use of the 245c
		if (field100 != null && field100.getSubfield('a') != null) {
			author = field100.getSubfield('a').getData();
		} else if (field110 != null && field110.getSubfield('a') != null) {
			author = field110.getSubfield('a').getData();
			if (field110.getSubfield('b') != null) {
				author += " " + field110.getSubfield('b').getData();
			}
		} else if (groupingFormat.equals("book") && field245 != null && field245.getSubfield('c') != null) {
			author = field245.getSubfield('c').getData();
			if (author.indexOf(';') > 0) {
				author = author.substring(0, author.indexOf(';') - 1);
			}
		} else if (field710 != null && field710.getSubfield('a') != null) {
			author = field710.getSubfield('a').getData();
		} else if (field260 != null && field260.getSubfield('b') != null) {
			author = field260.getSubfield('b').getData();
		} else if (field264 != null && field264.getSubfield('b') != null) {
			author = field264.getSubfield('b').getData();
		} else if (!groupingFormat.equals("book") && field245 != null && field245.getSubfield('c') != null) {
			author = field245.getSubfield('c').getData();
			if (author.indexOf(';') > 0) {
				author = author.substring(0, author.indexOf(';') - 1);
			}
		}
		if (author != null) {
			workForTitle.setAuthor(author);
		}
	}

	private DataField setWorkTitleBasedOnMarcRecord(Record marcRecord, GroupedWork workForTitle) {
		//Check for a uniform title field
		//The uniform title is useful for movies (often has the release year)
		DataField field130 = marcRecord.getDataField(130);
		if (field130 != null && field130.getSubfield('a') != null){
			Subfield subfieldK = field130.getSubfield('k');
			if (subfieldK == null || !subfieldK.getData().toLowerCase().contains("selections")) {
				assignTitleInfoFromMarcField(workForTitle, field130, 1);
				return field130;
			}
		}

		//The 240 only gives good information if the language is not English.  If the language isn't English,
		//it generally gives the english translation which could help to group translated versions with the original work.
		// Not implementing this for now until we get additional feedback 2/2021

		DataField field245 = marcRecord.getDataField(245);
		if (field245 != null && field245.getSubfield('a') != null) {
			assignTitleInfoFromMarcField(workForTitle, field245, 2);
			return field245;
		}
		return null;
	}

	private void assignTitleInfoFromMarcField(GroupedWork workForTitle, DataField titleField, int nonFilingCharactersIndicator) {
		String fullTitle = titleField.getSubfield('a').getData();

		char nonFilingCharacters;
		if (nonFilingCharactersIndicator == 1){
			nonFilingCharacters = titleField.getIndicator1();
		}else {
			nonFilingCharacters = titleField.getIndicator2();
		}
		if (nonFilingCharacters == ' ') nonFilingCharacters = '0';
		int numNonFilingCharacters = 0;
		if (nonFilingCharacters >= '0' && nonFilingCharacters <= '9') {
			numNonFilingCharacters = Integer.parseInt(Character.toString(nonFilingCharacters));
		}

		boolean isUniformTitle = titleField.getTag().equals("130");

		//Add in subtitle (subfield b as well to avoid problems with gov docs, etc.)
		StringBuilder groupingSubtitle = new StringBuilder();
		if (titleField.getSubfield('b') != null) {
			groupingSubtitle.append(titleField.getSubfield('b').getData());
		}

		//Group volumes, seasons, etc. independently
		List<Subfield> partSubfields;
		if (isUniformTitle) {
			//noinspection SpellCheckingInspection
			partSubfields = titleField.getSubfields("mnops");
		}else{
			partSubfields = titleField.getSubfields("fnp");
		}
		StringBuilder partInfo = new StringBuilder();
		for (Subfield partSubfield : partSubfields) {
			if (partInfo.length() > 0) partInfo.append(" ");
			partInfo.append(partSubfield.getData());
		}

		workForTitle.setTitle(fullTitle, numNonFilingCharacters, groupingSubtitle.toString(), partInfo.toString());
	}

	GroupedWork setupBasicWorkForIlsRecord(Record marcRecord) {
		GroupedWork workForTitle = new GroupedWork(this);

		//Title
		DataField field245 = setWorkTitleBasedOnMarcRecord(marcRecord, workForTitle);
		String groupingFormat = setGroupingCategoryForWork(marcRecord, workForTitle);

		//Author
		setWorkAuthorBasedOnMarcRecord(marcRecord, workForTitle, field245, groupingFormat);

		//Language
		setLanguageBasedOnMarcRecord(marcRecord, workForTitle);

		return workForTitle;
	}

	String languageFields = "008[35-37]";
	private void setLanguageBasedOnMarcRecord(Record marcRecord, GroupedWork workForTitle) {
		String activeLanguage = null;
		Set<String> languages = MarcUtil.getFieldList(marcRecord, languageFields);
		for (String language : languages){
			String trimmedLanguage = language.trim();
			if (trimmedLanguage.length() == 3 && !trimmedLanguage.equals("|||") && !trimmedLanguage.contains(" ")) {
				if (activeLanguage == null) {
					activeLanguage = trimmedLanguage;
				} else {
					if (!activeLanguage.equals(trimmedLanguage)) {
						activeLanguage = "mul";
						break;
					}
				}
			}
		}
		//Check to see if the language has a space in it.
		if (activeLanguage == null){
			if (!treatUnknownLanguageAs.isEmpty()){
				activeLanguage = translateValue("language_to_three_letter_code", treatUnknownLanguageAs);
				if (activeLanguage.length() != 3 || activeLanguage.contains(" ")){
					activeLanguage = "unk";
				}
			}else {
				activeLanguage = "unk";
			}
		}
		workForTitle.setLanguage(activeLanguage);
	}

	public void removeExistingRecord(String identifier) {
		existingRecords.remove(identifier);
	}

	public HashMap<String, IlsTitle> getExistingRecords() {
		return existingRecords;
	}

	public int getNumRemainingRecordsToDelete() {
		int numRemainingRecordsToDelete = 0;
		for (IlsTitle title : existingRecords.values()){
			if (!title.isDeleted()){
				numRemainingRecordsToDelete++;
			}
		}
		return numRemainingRecordsToDelete;
	}

	public int getNumExistingTitles(BaseIndexingLogEntry logEntry) {
		try {
			//Clear previous records if we load multiple times
			existingRecords = new HashMap<>();
			PreparedStatement getAllExistingRecordsStmt = dbConn.prepareStatement("SELECT count(*) FROM ils_records where source = ? AND deleted = 0;");
			getAllExistingRecordsStmt.setString(1, baseSettings.getName());
			ResultSet allRecordsRS = getAllExistingRecordsStmt.executeQuery();
			int numExistingTitles = 0;
			if (allRecordsRS.next()) {
				numExistingTitles = allRecordsRS.getInt(1);
			}
			allRecordsRS.close();
			getAllExistingRecordsStmt.close();
			return numExistingTitles;
		}catch (SQLException e) {
			logEntry.incErrors("Error getting number of existing titles for " + baseSettings.getName(), e);
			logEntry.addNote("Error getting number of existing titles for " + baseSettings.getName() + " " + e);
			return 0;
		}
	}

	public HashSet<String> loadExistingActiveIds(BaseIndexingLogEntry logEntry) {
		try {
			//Clear previous records if we load multiple times
			HashSet<String> existingRecords = new HashSet<>();
			PreparedStatement getAllExistingRecordsStmt = dbConn.prepareStatement("SELECT ilsId FROM ils_records where source = ? and deleted = 0;");
			getAllExistingRecordsStmt.setString(1, baseSettings.getName());
			ResultSet allRecordsRS = getAllExistingRecordsStmt.executeQuery();
			int numDeletedTitles = 0;
			while (allRecordsRS.next()) {
				existingRecords.add(allRecordsRS.getString("ilsId"));
			}
			allRecordsRS.close();
			getAllExistingRecordsStmt.close();
			logEntry.addNote("There are " + existingRecords.size() + " records that have already been loaded " + numDeletedTitles + " are deleted, and " + (existingRecords.size() - numDeletedTitles) + " are active");
			return existingRecords;
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing titles", e);
			logEntry.addNote("Error loading existing titles" + e);
			return null;
		}
	}

	public boolean loadExistingTitles(BaseIndexingLogEntry logEntry) {
		try {
			//Clear previous records if we load multiple times
			existingRecords = new HashMap<>();
			PreparedStatement getAllExistingRecordsStmt = dbConn.prepareStatement("SELECT ilsId, checksum, dateFirstDetected, deleted FROM ils_records where source = ?;");
			getAllExistingRecordsStmt.setString(1, baseSettings.getName());
			ResultSet allRecordsRS = getAllExistingRecordsStmt.executeQuery();
			int numDeletedTitles = 0;
			while (allRecordsRS.next()) {
				String ilsId = allRecordsRS.getString("ilsId");
				IlsTitle newTitle = new IlsTitle(
						allRecordsRS.getLong("checksum"),
						allRecordsRS.getLong("dateFirstDetected"),
						allRecordsRS.getBoolean("deleted")
				);
				existingRecords.put(ilsId, newTitle);
				if (newTitle.isDeleted()){
					numDeletedTitles++;
				}
			}
			allRecordsRS.close();
			getAllExistingRecordsStmt.close();
			logEntry.addNote("There are " + existingRecords.size() + " records that have already been loaded " + numDeletedTitles + " are deleted, and " + (existingRecords.size() - numDeletedTitles) + " are active");
			return true;
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing titles", e);
			logEntry.addNote("Error loading existing titles" + e);
			return false;
		}
	}

	public boolean isValid() {
		return isValid;
	}

	public MarcRecordFormatClassifier getFormatClassifier() {
		return formatClassifier;
	}
}
