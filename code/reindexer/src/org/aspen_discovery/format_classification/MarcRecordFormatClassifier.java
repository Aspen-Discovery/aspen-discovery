package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.ItemInfo;
import org.aspen_discovery.reindexer.RecordInfo;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.util.*;
import java.util.regex.Pattern;

/**
 * Generic format classification of a MARC record including side loads, and records from the ILS
 *
 * Bib level classification not taking items into account.
 */
public class MarcRecordFormatClassifier {
	protected Logger logger;

	private static final Pattern dvdBlurayComboRegex = Pattern.compile("(.*blu-ray\\s?[+\\\\/]\\s?dvd.*)|(blu-ray 3d\\s?[+\\\\/]\\s?dvd.*)|(.*dvd\\s?[+\\\\/]\\s?blu-ray.*)", Pattern.CASE_INSENSITIVE);
	private static final Pattern bluray4kComboRegex = Pattern.compile("(.*4k ultra hd\\s?\\+\\s?blu-ray.*)|(.*blu-ray\\s?\\+\\s?.*4k.*)|(.*4k ultra hd blu-ray disc\\s?\\+\\s?.*blu-ray.*)", Pattern.CASE_INSENSITIVE);

	public MarcRecordFormatClassifier(Logger logger) {
		this.logger = logger;
	}

	/**
	 * Returns full format information for the record. These are fully translated for indexing.
	 *
	 * @param record The MARC record to load from
	 * @param settings Settings for how to process the record
	 * @param logEntry The log entry for writing errors etc.
	 * @param logger  Raw log for writing debugging information etc.
	 * @return Full information including, format, format category, and format boost for the record.
	 */
	public LinkedHashSet<FormatInfo> getFormatsForRecord(org.marc4j.marc.Record record, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger){
		LinkedHashSet<String> formatsFromBib = this.getUntranslatedFormatsFromBib(record, settings);
		LinkedHashSet<FormatInfo> formatInfoFromBib = new LinkedHashSet<>();
		for (String format : formatsFromBib) {
			FormatInfo formatInfo = new FormatInfo();
			formatInfo.format = format;
			if (settings instanceof IndexingProfile) {
				IndexingProfile profile = (IndexingProfile) settings;
				String formatLower = format.toLowerCase();
				if (profile.hasTranslation("format", formatLower)) {
					formatInfo.format = profile.translateValue("format", formatLower);
				}
				if (profile.hasTranslation("format_category", formatLower)) {
					formatInfo.formatCategory = profile.translateValue("format_category", formatLower);
				}else{
					formatInfo.formatCategory = "other";
				}
				if (profile.hasTranslation("format_boost", formatLower)) {
					String formatBoostString = profile.translateValue("format_boost", formatLower);
					int formatBoost = 1;
					if (!formatBoostString.isEmpty()) {
						try {
							formatBoost = Integer.parseInt(formatBoostString);
						} catch (Exception e) {
							//Ignore this
						}
					}
					formatInfo.formatBoost = formatBoost;
				}else{
					formatInfo.formatBoost = 1;
				}
			}else{
				//TODO: set format category and format boost
			}

			formatInfoFromBib.add(formatInfo);
		}

		return formatInfoFromBib;
	}

	public FormatInfo getFirstFormatForRecord(org.marc4j.marc.Record record, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		LinkedHashSet<FormatInfo> printFormats = getFormatsForRecord(record, settings, logEntry, logger);
		if (!printFormats.isEmpty()) {
			return printFormats.iterator().next();
		}
		// Nothing worked!
		FormatInfo unknownFormat = new FormatInfo();
		unknownFormat.format = "Unknown";
		unknownFormat.formatCategory = "Other";
		unknownFormat.formatBoost = 1;
		return unknownFormat;
	}

	public LinkedHashSet<String> getTranslatedFormatsFromBib(org.marc4j.marc.Record record, BaseIndexingSettings settings){
		LinkedHashSet<String> untranslatedFormats = this.getUntranslatedFormatsFromBib(record, settings);
		LinkedHashSet<String> translatedFormats = new LinkedHashSet<>();
		for (String format : untranslatedFormats) {
			if (settings.hasTranslation("format", format)) {
				translatedFormats.add(settings.translateValue("format", format));
			}else{
				translatedFormats.add(format);
			}
		}
		return translatedFormats;
	}

	/**
	 * Get formats from a bib, these are untranslated, so they can be translated later for category, boosting, etc.
	 *
	 * @param record The record to load formats from
	 * @param settings The settings to use while loading formats
	 * @return The list of formats for the records
	 */
	public LinkedHashSet<String> getUntranslatedFormatsFromBib(org.marc4j.marc.Record record, BaseIndexingSettings settings){
		LinkedHashSet<String> printFormats = new LinkedHashSet<>();

		String leader = record.getLeader().toString();
		char leaderBit = ' ';
		ControlField fixedField = (ControlField) record.getVariableField(8);

		// check for music recordings quickly, so we can figure out if it is music
		// for category (need to do here since checking what is on the Compact
		// Disc/Phonograph, etc. is difficult).
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
		getFormatFromFormField(record, printFormats);
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile) settings;
			if (printFormats.isEmpty() && indexingProfile.getFallbackFormatField() != null && !indexingProfile.getFallbackFormatField().isEmpty()) {
				getFormatFromFallbackField(record, printFormats, settings);
			}
		}
		if (printFormats.isEmpty() || printFormats.contains("MusicRecording") || (printFormats.size() == 1 && printFormats.contains("Book"))) {
			if (printFormats.size() == 1 && printFormats.contains("Book")){
				printFormats.clear();
			}
			//Only get from fixed field information if we don't have anything yet since the cataloging of
			//fixed fields is not kept up to date reliably.  #D-87
			getFormatFrom007(record, printFormats);
			if (printFormats.isEmpty() || (printFormats.size() == 1 && printFormats.contains("Book"))) {
				getFormatFromLeader(printFormats, leader, fixedField);
			}
		}

		if (printFormats.isEmpty()) {
			//logger.debug("Did not get any formats for print record " + recordInfo.getFullIdentifier() + ", assuming it is a book ");
			printFormats.add("Book");
		}

		if (printFormats.size() > 1) {
			filterPrintFormats(printFormats);
		}

		return printFormats;
	}

	public void getFormatFromDigitalFileCharacteristics(org.marc4j.marc.Record record, HashSet<String> printFormats) {
		Set<String> fields = MarcUtil.getFieldList(record, "347b");
		for (String curField : fields){
			if (curField.equalsIgnoreCase("Blu-Ray")){
				printFormats.add("Blu-ray");
			}else if (curField.equalsIgnoreCase("DVD video")){
				printFormats.add("DVD");
			}
		}
	}

	public void getFormatFromFormField(org.marc4j.marc.Record record, HashSet<String> printFormats) {
		Set<String> fields = MarcUtil.getFieldList(record, "380a");
		for (String curField : fields){
			if (curField.equalsIgnoreCase("Graphic Novel")){
				printFormats.add("GraphicNovel");
			}else if (curField.equalsIgnoreCase("Comic")){
				printFormats.add("Comic");
			}
		}
	}

	Pattern audioDiscPattern = Pattern.compile(".*\\b(cd|cds|(sound|audio|compact) discs?)\\b.*");
	Pattern pagesPattern = Pattern.compile("^.*?\\d+\\s+(p\\.|pages|v\\.|volume|volumes).*$");
	Pattern pagesPattern2 = Pattern.compile("^.*?\\b\\d+\\s+(p\\.|pages|v\\.|volume|volumes)\\b.*");
	Pattern kitPattern = Pattern.compile(".*\\bkit\\b.*");
	public void getFormatFromPhysicalDescription(org.marc4j.marc.Record record, Set<String> result) {
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
					} else if (subfield.getCode() == 'a' && (physicalDescriptionData.contains("launchpad"))) {
						result.add("PlayawayLaunchpad");
					}else if (physicalDescriptionData.contains("4k") && (physicalDescriptionData.contains("bluray") || physicalDescriptionData.contains("blu-ray"))) {
						result.add("4KBlu-ray");
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
					} else if (audioDiscPattern.matcher(physicalDescriptionData).matches() && !(physicalDescriptionData.contains("cd player") || physicalDescriptionData.contains("cd boombox") || physicalDescriptionData.contains("cd boom box") || physicalDescriptionData.contains("cd/mp3 player"))) {
						//Check to see if there is a subfield e.  If so, this could be a combined format
						Subfield subfieldE = field.getSubfield('e');
						if (subfieldE != null && subfieldE.getData().toLowerCase().contains("book")){
							result.add("CD+Book");
						}else{
							if (!physicalDescriptionData.contains("cd-rom")) {
								result.add("SoundDisc");
							}
						}
					} else if (subfield.getCode() == 'a' && (pagesPattern2.matcher(physicalDescriptionData).matches())){
						Subfield subfieldE = field.getSubfield('e');
						if (subfieldE != null && subfieldE.getData().toLowerCase().contains("dvd")){
							result.add("Book+DVD");
						}else if (subfieldE != null && subfieldE.getData().toLowerCase().contains("cd-rom")){
							result.add("Book+CD-ROM");
						}else if (subfieldE != null && (subfieldE.getData().toLowerCase().contains("cd") || subfieldE.getData().toLowerCase().contains("audio disc"))){
							result.add("Book+CD");
						}else{
							result.add("Book");
						}
					}
					//Since this is fairly generic, only use it if we have no other formats yet
					if (result.isEmpty() && subfield.getCode() == 'f' && (pagesPattern.matcher(physicalDescriptionData).matches())) {
						result.add("Book");
					}
				} else {
					String physicalDescriptionData = subfield.getData().toLowerCase();
					if (kitPattern.matcher(physicalDescriptionData).matches()) {
						result.add("Kit");
					}
				}
			}
		}
	}

	public void getFormatFromSubjects(org.marc4j.marc.Record record, Set<String> result) {
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
						}else if (subfieldData.contains("board books") || subfieldData.contains("board book")) {
							result.add("BoardBook");
						}else if (subfieldData.contains("pop-up")) {
							result.add("Pop-UpBook");
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
						}else if (subfieldData.contains("board books") || subfieldData.contains("board book")) {
							result.add("BoardBook");
						}else if (subfieldData.contains("pop-up")) {
							result.add("Pop-UpBook");
						}else if (subfieldData.startsWith("manga graphic novel")) {
							result.add("Manga");
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
					}else if (fieldData.contains("playaway launchpad")) {
						result.add("PlayawayLaunchpad");
					}else if (fieldData.contains("playaway bookpack")) {
						result.add("PlayawayBookpack");
					}else if (fieldData.contains("playaway wonderbook")) {
						result.add("Wonderbook");
					}else if (fieldData.contains("playaway digital audio") || fieldData.contains("findaway world")) {
						result.add("Playaway");
					}
				}
			}
		}
	}

	public void getFormatFromTitle(org.marc4j.marc.Record record, Set<String> printFormats) {
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

	public void getFormatFromPublicationInfo(org.marc4j.marc.Record record, Set<String> result) {
		// check for playaway in 260|b
		List<DataField> publicationFields = record.getDataFields(new int[]{260, 264});
		for (DataField publicationInfo : publicationFields) {
			for (Subfield publisherSubField : publicationInfo.getSubfields('b')){
				String sysDetailsValue = publisherSubField.getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				} else if (sysDetailsValue.contains("go reader") || sysDetailsValue.contains("goreader")) {
					result.add("GoReader");
				}
			}
		}
	}

	public void getFormatFrom007(org.marc4j.marc.Record record, Set<String> result) {
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
							case 'Z':
								//Don't add anything, this is identified as Other
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

	public void getFormatFromLeader(Set<String> result, String leader, ControlField fixedField008) {
		char leaderBit;
		char formatCode;// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'A':
					//Books, look for graphic novels in positions (24-27)
					if (fixedField008 != null && fixedField008.getData().length() >= 25) {
						formatCode = fixedField008.getData().toUpperCase().charAt(24);
						if (formatCode == '6') {
							result.add("GraphicNovel");
						}else if (fixedField008.getData().length() >= 26) {
							formatCode = fixedField008.getData().toUpperCase().charAt(25);
							if (formatCode == '6') {
								result.add("GraphicNovel");
							}else if (fixedField008.getData().length() >= 27) {
								formatCode = fixedField008.getData().toUpperCase().charAt(26);
								if (formatCode == '6') {
									result.add("GraphicNovel");
								}else if (fixedField008.getData().length() >= 28) {
									formatCode = fixedField008.getData().toUpperCase().charAt(27);
									if (formatCode == '6') {
										result.add("GraphicNovel");
									}
								}
							}
						}
					}
					break;
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
					// Look in 008 to determine what type of Continuing Resource
					if (fixedField008 != null && fixedField008.getData().length() >= 27) {
						formatCode = fixedField008.getData().toUpperCase().charAt(26);
						switch (formatCode) {
							case 'A':
								result.add("Numeric Data");
								break;
							case 'B':
								result.add("Computer Program");
								break;
							case 'G':
								result.add("Video Game");
								break;
							default:
								result.add("Electronic");
								break;
						}
					}
					break;
				case 'O':
				case 'P':
					result.add("Kit");
					break;
				case 'R':
					if (fixedField008 != null && fixedField008.getData().length() >= 34) {
						formatCode = fixedField008.getData().toUpperCase().charAt(33);
						switch (formatCode) {
							case 'A':
								result.add("Art Original");
								break;
							case 'B':
								result.add("Kit");
								break;
							case 'C':
								result.add("Journal");
								break;
							case 'D':
								result.add("Diorama");
								break;
							case 'F':
								result.add("Filmstrip");
								break;
							case 'G':
								result.add("Game");
								break;
							case 'I':
								result.add("Picture");
								break;
							case 'K':
								result.add("Graphic");
								break;
							case 'L':
								result.add("Technical Drawing");
								break;
							case 'N':
								result.add("Chart");
								break;
							case 'O':
								result.add("Flash card");
								break;
							case 'P':
								result.add("Microscope Slide");
								break;
							case 'Q':
								result.add("Model");
								break;
							case 'R':
								result.add("Realia");
								break;
							case 'S':
								result.add("Slide");
								break;
							case 'T':
								result.add("Transparency");
								break;
							case 'W':
								result.add("Toy");
								break;
							default:
								result.add("PhysicalObject");
								break;
						}
					}
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
					if (fixedField008 != null && fixedField008.getData().length() >= 22) {
						formatCode = fixedField008.getData().toUpperCase().charAt(21);
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

	public void getFormatFromEdition(org.marc4j.marc.Record record, Set<String> result) {
		List<DataField> allEditions = record.getDataFields(250);
		for (DataField edition : allEditions) {
			if (edition.getSubfield('a') != null) {
				String editionData = edition.getSubfield('a').getData().toLowerCase();
				if (editionData.contains("large type") || editionData.contains("large print")) {
					result.add("LargePrint");
				} else if (bluray4kComboRegex.matcher(editionData).matches()) {
					result.add("4K/Blu-ray");
				} else if (dvdBlurayComboRegex.matcher(editionData).matches()) {
					result.add("Blu-ray/DVD");
				} else if (editionData.contains("go reader") || editionData.contains("goreader")) {
					result.add("GoReader");
				} else if (editionData.contains("playaway view")) {
					result.add("PlayawayView");
				} else if (editionData.contains("playaway")) {
					result.add("Playaway");
				} else if (editionData.contains("wonderbook")) {
					result.add("Wonderbook");
				} else if (editionData.contains("gamecube")) {
					result.add("GameCube");
				} else if (editionData.contains("nintendo switch")) {
					result.add("NintendoSwitch");
				} else if (editionData.contains("book club kit")) {
					result.add("BookClubKit");
				} else if (editionData.contains("vox")) {
					result.add("VoxBooks");
				} else if (editionData.contains("pop-up") || (editionData.contains("mini-pop-up"))) {
					result.add("Pop-UpBook");
				} else {
					String gameFormat = getGameFormatFromValue(editionData);
					if (gameFormat != null) {
						result.add(gameFormat);
					}
				}
			}
		}
	}

	public void getFormatFromNotes(org.marc4j.marc.Record record, Set<String> result) {
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
					} else if (bluray4kComboRegex.matcher(sysDetailsValue).matches()) {
						result.add("4K/Blu-ray");
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
					}else if (voxPattern.matcher(noteValue).matches()) {
						result.add("VoxBooks");
						break;
//					}else if (bluray4kComboRegex.matcher(noteValue).matches()) {
//						result.add("4K/Blu-ray");
//						break;
//					} else if (dvdBlurayComboRegex.matcher(noteValue).matches()) {
//						result.add("Blu-ray/DVD");
//						break;
					} else if (noteValue.contains("wonderbook")) {
						result.add("Wonderbook");
						break;
					} else if (noteValue.contains("playaway view")) {
						result.add("PlayawayView");
						break;
					}  else if (noteValue.contains("playaway bookpack") || noteValue.contains("playaway bookpacks")) {
						result.add("PlayawayBookpack");
						break;
					}else if (noteValue.contains("playaway launchpad")) {
						result.add("PlayawayLaunchpad");
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

	private final Pattern voxPattern = Pattern.compile(".*(vox books|vox reader|vox audio).*");
	Pattern playStation5Pattern = Pattern.compile(".*(playstation\\s?5|ps\\s?5).*");
	Pattern playStation4Pattern = Pattern.compile(".*(playstation\\s?4|ps\\s?4).*");
	Pattern playStation3Pattern = Pattern.compile(".*(playstation\\s?3|ps\\s?3).*");
	Pattern playStation2Pattern = Pattern.compile(".*(playstation\\s?2|ps\\s?2).*");
	Pattern playStationVitaPattern = Pattern.compile(".*(playstation\\s?vita|ps\\s?vita).*");
	private String getGameFormatFromValue(String value) {
		if (value.contains("kinect sensor")) {
			return "Kinect";
		} else if (value.contains("wii u") || value.contains("wiiu")) {
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

	public void filterPrintFormats(Set<String> printFormats) {
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
		if (printFormats.contains("PlayawayLaunchpad")){
			printFormats.clear();
			printFormats.add("PlayawayLaunchpad");
			return;
		}
		if (printFormats.contains("PlayawayBookpack")){
			printFormats.clear();
			printFormats.add("PlayawayBookpack");
			return;
		}
		if (printFormats.contains("Wonderbook")){
			printFormats.clear();
			printFormats.add("Wonderbook");
			return;
		}
		if (printFormats.contains("Playaway")){
			printFormats.clear();
			printFormats.add("Playaway");
			return;
		}
		if (printFormats.contains("Kit")){
			printFormats.clear();
			printFormats.add("Kit");
			return;
		}
		if (printFormats.contains("DVD")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("DVD")){
			printFormats.remove("VideoDisc");
		}
		if (printFormats.contains("VideoDisc")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("VideoCassette")){
			printFormats.remove("Video");
		}
		if (printFormats.contains("DVD")){
			printFormats.remove("VideoCassette");
		}
		if (printFormats.contains("4KBlu-ray")){
			printFormats.remove("VideoDisc");
			printFormats.remove("DVD");
			printFormats.remove("Blu-ray");
		}
		if (printFormats.contains("Blu-ray")){
			printFormats.remove("VideoDisc");
			printFormats.remove("DVD");
		}
		if (printFormats.contains("4K/Blu-ray")){
			printFormats.remove("Blu-ray");
			printFormats.remove("4KBlu-ray");
		}
		if (printFormats.contains("Blu-ray/DVD")){
			printFormats.remove("Blu-ray");
			printFormats.remove("DVD");
		}
		if (printFormats.contains("Book+DVD")){
			printFormats.remove("Book");
			printFormats.remove("DVD");
		}
		if (printFormats.contains("Book+CD")){
			printFormats.remove("Book");
			printFormats.remove("CD");
			printFormats.remove("CompactDisc");
		}
		if (printFormats.contains("Book+CD-ROM")){
			printFormats.remove("Book");
			printFormats.remove("CDROM");
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

		if (printFormats.contains("Serial")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("BookClubKit") && printFormats.contains("LargePrint")){
			printFormats.clear();
			printFormats.add("BookClubKitLarge");
		}
		if (printFormats.contains("BookClubKit") && printFormats.contains("Kit")){
			printFormats.clear();
			printFormats.add("BookClubKit");
		}
		if (printFormats.contains("LargePrint")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Atlas")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Manuscript")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("GraphicNovel")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("MusicalScore")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("BookClubKit")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Kit")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Pop-UpBook")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("BoardBook")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Journal")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Serial")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("Braille")){
			printFormats.remove("Book");
		}
		if (printFormats.contains("CD")){
			printFormats.remove("AudioCD");
		}
		if (printFormats.contains("SoundDisc")){
			printFormats.remove("Software");
			printFormats.remove("CompactDisc");
		}
		if (printFormats.contains("SoundDisc")){
			printFormats.remove("CD");
		}
		if (printFormats.contains("CompactDisc")){
			printFormats.remove("SoundRecording");
		}
		if (printFormats.contains("GraphicNovel")){
			printFormats.remove("Serial");
		}
		if (printFormats.contains("Map")){
			printFormats.remove("Atlas");
		}
		if (printFormats.contains("LargePrint")){
			printFormats.remove("Manuscript");
		}
		if (printFormats.contains("XboxOne")){
			printFormats.remove("XBoxSeriesX");
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
			if (printFormats.contains("PlayStation") || printFormats.contains("PlayStation2")
					|| printFormats.contains("PlayStation3") || printFormats.contains("PlayStation4")
					|| printFormats.contains("PlayStation5")) {
				printFormats.remove("PlayStation");
			}
		}
	}

	@SuppressWarnings("unused")
	protected void getFormatFromFallbackField(org.marc4j.marc.Record record, LinkedHashSet<String> printFormats, BaseIndexingSettings settings) {
		//Do nothing by default, this is overridden in IlsRecordProcessor
	}

	public void loadItemFormat(RecordInfo recordInfo, DataField itemField, ItemInfo itemInfo, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
	}
}
