package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.io.File;
import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Set;

public abstract class BaseMarcRecordGrouper extends RecordGroupingProcessor {
	private final String recordNumberTag;
	private final int recordNumberTagInt;
	private final char recordNumberSubfield;
	private final String recordNumberPrefix;
	private final BaseIndexingSettings baseSettings;
	private final String treatUnknownLanguageAs;

	private final Connection dbConn;

	//Existing records
	private HashMap<String, IlsTitle> existingRecords = new HashMap<>();

	private boolean isValid = true;

	BaseMarcRecordGrouper(String serverName, BaseIndexingSettings settings, Connection dbConn, BaseLogEntry logEntry, Logger logger) {
		super(dbConn, serverName, logEntry, logger);
		this.dbConn = dbConn;
		recordNumberTag = settings.getRecordNumberTag();
		recordNumberTagInt = Integer.parseInt(recordNumberTag);
		recordNumberSubfield = settings.getRecordNumberSubfield();
		recordNumberPrefix = settings.getRecordNumberPrefix();
		treatUnknownLanguageAs = settings.getTreatUnknownLanguageAs();

		baseSettings = settings;
	}

	public abstract String processMarcRecord(Record marcRecord, boolean primaryDataChanged, String originalGroupedWorkId);

	public RecordIdentifier getPrimaryIdentifierFromMarcRecord(Record marcRecord, BaseIndexingSettings indexingProfile) {
		RecordIdentifier identifier = null;
		VariableField recordNumberField = marcRecord.getVariableField(recordNumberTagInt);
		//Make sure we only get one ils identifier
		if (recordNumberField != null) {
			if (recordNumberField instanceof DataField) {
				DataField curRecordNumberField = (DataField) recordNumberField;
				Subfield subfieldA = curRecordNumberField.getSubfield(recordNumberSubfield);
				if (subfieldA != null && (recordNumberPrefix.length() == 0 || subfieldA.getData().length() > recordNumberPrefix.length())) {
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
			return identifier;
		} else {
			return null;
		}
	}

	protected String getFormatFromBib(Record record) {
		String leader = record.getLeader().toString();
		char leaderBit;
		ControlField fixedField = (ControlField) record.getVariableField(8);
		char formatCode;

		// check for music recordings quickly so we can figure out if it is music
		// for category (need to do here since checking what is on the Compact
		// Disc/Phonograph, etc is difficult).
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			if (Character.toUpperCase(leaderBit) == 'J') {
				return "MusicRecording";
			}
		}

		// check for playaway in 260|b
		DataField sysDetailsNote = record.getDataField(260);
		if (sysDetailsNote != null) {
			if (sysDetailsNote.getSubfield('b') != null) {
				String sysDetailsValue = sysDetailsNote.getSubfield('b').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					return "Playaway";
				}
			}
		}

		// Check for formats in the 538 field
		DataField sysDetailsNote2 = record.getDataField(538);
		if (sysDetailsNote2 != null) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					return "Playaway";
				} else if (sysDetailsValue.contains("bluray")
						|| sysDetailsValue.contains("blu-ray")) {
					return "Blu-ray";
				} else if (sysDetailsValue.contains("dvd")) {
					return "DVD";
				} else if (sysDetailsValue.contains("vertical file")) {
					return "VerticalFile";
				}
			}
		}

		// Check for formats in the 500 tag
		DataField noteField = record.getDataField(500);
		if (noteField != null) {
			if (noteField.getSubfield('a') != null) {
				String noteValue = noteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("vertical file")) {
					return "VerticalFile";
				}
			}
		}

		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		DataField edition = record.getDataField(250);
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				if (edition.getSubfield('a').getData().toLowerCase().contains("large type")) {
					return "LargePrint";
				}
			}
		}

		List<DataField> physicalDescription = getDataFields(record, 300);
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIterator = physicalDescription.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				List<Subfield> subFields = field.getSubfields();
				for (Subfield subfield : subFields) {
					if (subfield.getData().toLowerCase().contains("large type")) {
						return "LargePrint";
					} else if (subfield.getData().toLowerCase().contains("bluray")
							|| subfield.getData().toLowerCase().contains("blu-ray")) {
						return "Blu-ray";
					}
				}
			}
		}
		List<DataField> topicalTerm = getDataFields(record, 650);
		if (topicalTerm != null) {
			Iterator<DataField> fieldsIterator = topicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getData().toLowerCase().contains("large type")) {
						return "LargePrint";
					}
				}
			}
		}

		List<DataField> localTopicalTerm = getDataFields(record, 690);
		if (localTopicalTerm != null) {
			Iterator<DataField> fieldsIterator = localTopicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null) {
					if (subfieldA.getData().toLowerCase().contains("seed library")) {
						return "SeedPacket";
					}
				}
			}
		}

		// check the 007 - this is a repeating field
		List<DataField> fields = getDataFields(record, 7);
		if (fields != null) {
			Iterator<DataField> fieldsIterator = fields.iterator();
			ControlField formatField;
			while (fieldsIterator.hasNext()) {
				formatField = (ControlField) fieldsIterator.next();
				if (formatField.getData() == null || formatField.getData().length() < 2) {
					continue;
				}
				// Check for blu-ray (s in position 4)
				// This logic does not appear correct.
				/*
				 * if (formatField.getData() != null && formatField.getData().length()
				 * >= 4){ if (formatField.getData().toUpperCase().charAt(4) == 'S'){
				 * result.add("Blu-ray"); break; } }
				 */
				formatCode = formatField.getData().toUpperCase().charAt(0);
				switch (formatCode) {
					case 'A':
						if (formatField.getData().toUpperCase().charAt(1) == 'D') {
							return "Atlas";
						}
						return "Map";
					case 'C':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								return "TapeCartridge";
							case 'B':
								return "ChipCartridge";
							case 'C':
								return "DiscCartridge";
							case 'F':
								return "TapeCassette";
							case 'H':
								return "TapeReel";
							case 'J':
								return "FloppyDisk";
							case 'M':
							case 'O':
								return "CDROM";
							case 'R':
								// Do not return - this will cause anything with an
								// 856 field to be labeled as "Electronic"
								break;
							default:
								return "Software";
						}
						break;
					case 'D':
						return "Globe";
					case 'F':
						return "Braille";
					case 'G':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
							case 'D':
								return "Filmstrip";
							case 'T':
								return "Transparency";
							default:
								return "Slide";
						}
					case 'H':
						return "Microfilm";
					case 'K':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								return "Collage";
							case 'D':
							case 'L':
								return "Drawing";
							case 'E':
								return "Painting";
							case 'F':
							case 'J':
								return "Print";
							case 'G':
								return "Photonegative";
							case 'O':
								return "FlashCard";
							case 'N':
								return "Chart";
							default:
								return "Photo";
						}
					case 'M':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'F':
								return "VideoCassette";
							case 'R':
								return "Filmstrip";
							default:
								return "MotionPicture";
						}
					case 'O':
						return "Kit";
					case 'Q':
						return "MusicalScore";
					case 'R':
						return "SensorImage";
					case 'S':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								if (formatField.getData().length() >= 4) {
									char speed = formatField.getData().toUpperCase().charAt(3);
									if (speed >= 'A' && speed <= 'E') {
										return "Phonograph";
									} else if (speed == 'F') {
										return "CompactDisc";
									} else if (speed >= 'K' && speed <= 'R') {
										return "TapeRecording";
									} else {
										return "SoundDisc";
									}
								} else {
									return "SoundDisc";
								}
							case 'S':
								return "SoundCassette";
							default:
								return "SoundRecording";
						}
					case 'T':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								return "Book";
							case 'B':
								return "LargePrint";
						}
					case 'V':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								return "VideoCartridge";
							case 'D':
								return "VideoDisc";
							case 'F':
								return "VideoCassette";
							case 'R':
								return "VideoReel";
							default:
								return "Video";
						}
				}
			}
		}

		// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'C':
				case 'D':
					return "MusicalScore";
				case 'E':
				case 'F':
					return "Map";
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// return "Slide");
					return "Video";
				case 'I':
					return "SoundRecording";
				case 'J':
					return "MusicRecording";
				case 'K':
					return "Photo";
				case 'M':
					return "Electronic";
				case 'O':
				case 'P':
					return "Kit";
				case 'R':
					return "PhysicalObject";
				case 'T':
					return "Manuscript";
			}
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
				// Monograph
				case 'M':
					return "Book";
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					if (fixedField != null && fixedField.getData().length() >= 22) {
						formatCode = fixedField.getData().toUpperCase().charAt(21);
						switch (formatCode) {
							case 'N':
								return "Newspaper";
							case 'P':
								return "Journal";
							default:
								return "Serial";
						}
					}
			}
		}
		// Nothing worked!
		return "Unknown";
	}

	String setGroupingCategoryForWork(Record marcRecord, GroupedWork workForTitle) {
		//Format
		String groupingFormat;
		switch (baseSettings.getFormatSource()) {
			case "bib":
				String format = getFormatFromBib(marcRecord);
				groupingFormat = categoryMap.get(formatsToFormatCategory.get(format.toLowerCase()));
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
				groupingFormat = "Other";
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
		/*DataField field240 = marcRecord.getDataField(240);
		if (field240 != null && field240.getSubfield('a') != null){
			if (field240.getSubfield('l') != null) {
				if (!field240.getSubfield('l').getData().equalsIgnoreCase("English")) {
					assignTitleInfoFromMarcField(workForTitle, field240);
					return field240;
				}
			}
		}*/

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

		boolean isUniformTitle = false;
		if (titleField.getTag().equals("130")){
			isUniformTitle = true;
		}

		//Add in subtitle (subfield b as well to avoid problems with gov docs, etc)
		StringBuilder groupingSubtitle = new StringBuilder();
		if (titleField.getSubfield('b') != null) {
			groupingSubtitle.append(titleField.getSubfield('b').getData());
		}

		//Group volumes, seasons, etc. independently
		StringBuilder partInfo = new StringBuilder();
		if (isUniformTitle && titleField.getSubfield('m') != null){
			if (partInfo.length() > 0) partInfo.append(" ");
			partInfo.append(titleField.getSubfield('m').getData());
		}
		if (titleField.getSubfield('n') != null) {
			if (partInfo.length() > 0) partInfo.append(" ");
			partInfo.append(titleField.getSubfield('n').getData());
		}
		if (isUniformTitle && titleField.getSubfield('o') != null){
			if (partInfo.length() > 0) partInfo.append(" ");
			partInfo.append(titleField.getSubfield('o').getData());
		}
		if (titleField.getSubfield('p') != null) {
			if (partInfo.length() > 0) partInfo.append(" ");
			partInfo.append(titleField.getSubfield('p').getData());
		}
		if (isUniformTitle && titleField.getSubfield('s') != null){
			if (partInfo.length() > 0) partInfo.append(" ");
			partInfo.append(titleField.getSubfield('s').getData());
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
			if (activeLanguage == null){
				activeLanguage = language;
			}else{
				if (!activeLanguage.equals(language)){
					activeLanguage = "mul";
					break;
				}
			}
		}
		if (activeLanguage == null){
			if (treatUnknownLanguageAs.length() > 0){
				activeLanguage = translateValue("language_to_three_letter_code", treatUnknownLanguageAs);
				if (activeLanguage.length() != 3){
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


	private Long getExistingChecksum(String recordNumber) {
		IlsTitle curTitle = existingRecords.get(recordNumber);
		if (curTitle != null) {
			return curTitle.getChecksum();
		}
		return null;
	}

	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	public boolean loadExistingTitles(BaseLogEntry logEntry) {
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
			logEntry.addNote("Error loading existing titles" + e.toString());
			return false;
		}
	}

	public boolean isValid() {
		return isValid;
	}
}
