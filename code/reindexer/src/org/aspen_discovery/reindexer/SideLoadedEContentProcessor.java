package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.*;

class SideLoadedEContentProcessor extends MarcRecordProcessor{
	private long sideLoadId;
	protected boolean fullReindex;
	private PreparedStatement getDateAddedStmt;

	SideLoadedEContentProcessor(String serverName, GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet sideLoadSettingsRS, Logger logger, boolean fullReindex) {
		super(indexer, profileType, dbConn, logger);
		this.fullReindex = fullReindex;

		try{
			settings = new SideLoadSettings(serverName, sideLoadSettingsRS, indexer.getLogEntry());
			sideLoadId = sideLoadSettingsRS.getLong("id");

			getDateAddedStmt = dbConn.prepareStatement("SELECT dateFirstDetected FROM ils_records WHERE source = ? and ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		}catch (Exception e){
			logger.error("Error setting up side load processor");
		}
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String identifier) {
		try{
			HashSet<RecordInfo> allRelatedRecords = new HashSet<>();
			RecordInfo recordInfo = loadEContentRecord(groupedWork, identifier, record);
			if (recordInfo == null) {
				//This record was not valid (probably due to not having a valid URL).
				return;
			}

			allRelatedRecords.add(recordInfo);

			//Updates based on the overall bib (shared regardless of scoping)
			String primaryFormat = recordInfo.getPrimaryFormat();
			if (primaryFormat == null) primaryFormat = "Unknown";
			String primaryFormatCategory = recordInfo.getPrimaryFormatCategory();
			if (primaryFormatCategory == null) primaryFormatCategory = "Unknown";
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, recordInfo.getRelatedItems(), identifier, primaryFormat, primaryFormatCategory, false);

			String fullDescription = Util.getCRSeparatedString(MarcUtil.getFieldList(record, "520a"));
			groupedWork.addDescription(fullDescription, primaryFormatCategory);

			loadEditions(groupedWork, record, allRelatedRecords);
			loadPhysicalDescription(groupedWork, record, allRelatedRecords);
			loadLanguageDetails(groupedWork, record, allRelatedRecords, identifier);
			loadPublicationDetails(groupedWork, record, allRelatedRecords);

			if (record.getControlNumber() != null){
				groupedWork.addKeywords(record.getControlNumber());
			}

			//Updates based on items
			loadPopularity(groupedWork, identifier);

			groupedWork.addHoldings(1);

			scopeItems(groupedWork, recordInfo, record);
		}catch (Exception e){
			logger.error("Error updating grouped work for side loaded eContent MARC record with identifier " + identifier, e);
		}
	}

	private void scopeItems(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, org.marc4j.marc.Record record){
		boolean isTeen = groupedWork.getTargetAudiences().contains("Young Adult");
		boolean isKids = groupedWork.getTargetAudiences().contains("Juvenile");
		//Account for cases where audience is Unknown, General, etc
		boolean isAdult = !isKids && !isTeen;
		for (ItemInfo itemInfo : recordInfo.getRelatedItems()){
			loadScopeInfoForEContentItem(groupedWork, itemInfo, record, isAdult, isTeen, isKids);
		}
	}

	private void loadScopeInfoForEContentItem(AbstractGroupedWorkSolr groupedWork, ItemInfo itemInfo, org.marc4j.marc.Record record, boolean isAdult, boolean isTeen, boolean isKids) {
		String originalUrl = itemInfo.geteContentUrl();
		for (Scope curScope : indexer.getScopes()){
			SideLoadScope sideLoadScope = curScope.getSideLoadScope(sideLoadId);
			if (sideLoadScope != null) {
				boolean itemPartOfScope = sideLoadScope.isItemPartOfScope(record, isAdult, isTeen, isKids);
				if (itemPartOfScope) {
					ScopingInfo scopingInfo = itemInfo.addScope(curScope);
					groupedWork.addScopingInfo(curScope.getScopeName(), scopingInfo);

					scopingInfo.setLibraryOwned(true);
					scopingInfo.setLocallyOwned(true);

					//Check to see if we need to do url rewriting
					if (originalUrl != null) {
						String newUrl = sideLoadScope.getLocalUrl(originalUrl);
						scopingInfo.setLocalUrl(newUrl);
					}
				}
			}
		}
	}

	private void loadPopularity(AbstractGroupedWorkSolr groupedWork, @SuppressWarnings("unused") String identifier) {
		//TODO: Load popularity based on usage in the database
		groupedWork.addPopularity(0);
	}

	private RecordInfo loadEContentRecord(AbstractGroupedWorkSolr groupedWork, String identifier, org.marc4j.marc.Record record){
		//We will always have a single record
		return getEContentIlsRecord(groupedWork, record, identifier);
	}

	private RecordInfo getEContentIlsRecord(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, String identifier) {
		List<DataField> urlFields = MarcUtil.getDataFields(record, 856);
		RecordInfo relatedRecord = null;
		int urlIndex = 0;
		SideLoadSettings sideLoadSettings = (SideLoadSettings) settings;
		for (DataField urlField : urlFields){
			//load url into the item
			if (urlField.getSubfield('u') != null){
				String linkText = urlField.getSubfield('u').getData().trim();
				if (!linkText.isEmpty()) {
					//Try to determine if this is a resource or not.
					if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0') {
						if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '4') {
							urlIndex++;

							ItemInfo itemInfo = new ItemInfo();
							if (sideLoadSettings.isConvertFormatToEContent()) {
								itemInfo.setIsEContent(true);
							}

							loadDateAdded(identifier, itemInfo);
							itemInfo.setLocationCode(settings.getName());
							itemInfo.setCallNumber("Online " + settings.getName());
							itemInfo.setItemIdentifier(identifier + "_" + urlIndex);
							itemInfo.setShelfLocation(settings.getName());
							itemInfo.setDetailedLocation(settings.getName());

							//No Collection for Side loaded eContent
							//itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField), identifier));
							itemInfo.setAvailable(true);
							if (sideLoadSettings.isConvertFormatToEContent()) {
								itemInfo.setDetailedStatus("Available Online");
								itemInfo.setGroupedStatus("Available Online");
							}else{
								itemInfo.setDetailedStatus("On Shelf");
								itemInfo.setGroupedStatus("On Shelf");
							}
							itemInfo.setHoldable(false);
							itemInfo.setInLibraryUseOnly(false);

							itemInfo.seteContentSource(settings.getName());

							if (relatedRecord == null) {
								relatedRecord = groupedWork.addRelatedRecord(settings.getName(), identifier);
							}
							itemInfo.seteContentUrl(urlField.getSubfield('u').getData().trim());
							Subfield linkTextSubfield = urlField.getSubfield('y');
							if (linkTextSubfield != null) {
								itemInfo.setShelfLocation(linkTextSubfield.getData());
								itemInfo.setDetailedLocation(linkTextSubfield.getData());
							} else {
								linkTextSubfield = urlField.getSubfield('z');
								if (linkTextSubfield != null) {
									itemInfo.setShelfLocation(linkTextSubfield.getData());
									itemInfo.setDetailedLocation(linkTextSubfield.getData());
								}
							}
							relatedRecord.addItem(itemInfo);

							loadEContentFormatInformation(groupedWork, record, relatedRecord, itemInfo);
						}
					}
				}
			}
		}

		return relatedRecord;
	}

	private void loadEContentFormatInformation(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {
		if (settings.getFormatSource().equals("specified")){
			HashSet<String> translatedFormats = new HashSet<>();
			translatedFormats.add(settings.getSpecifiedFormat());
			HashSet<String> translatedFormatCategories = new HashSet<>();
			translatedFormatCategories.add(settings.getSpecifiedFormatCategory());
			econtentRecord.addFormats(translatedFormats);
			econtentRecord.addFormatCategories(translatedFormatCategories);
			econtentRecord.setFormatBoost(settings.getSpecifiedFormatBoost());
		} else {
			LinkedHashSet<String> printFormats = formatClassifier.getUntranslatedFormatsFromBib(groupedWork, record, settings);
			SideLoadSettings sideLoadSettings = (SideLoadSettings) settings;
			if (sideLoadSettings.isConvertFormatToEContent()) {
				//Convert formats from print to eContent version
				for (String format : printFormats) {
					if (format.equalsIgnoreCase("eBook") || format.equalsIgnoreCase("Book") || format.equalsIgnoreCase("LargePrint") || format.equalsIgnoreCase("Manuscript") || format.equalsIgnoreCase("Thesis") || format.equalsIgnoreCase("Print") || format.equalsIgnoreCase("Microfilm") || format.equalsIgnoreCase("Kit")) {
						econtentItem.setFormat("eBook");
						econtentItem.setFormatCategory("eBook");
						econtentRecord.setFormatBoost(10);
					} else if (format.equalsIgnoreCase("Journal") || format.equalsIgnoreCase("Serial")) {
						econtentItem.setFormat("eMagazine");
						econtentItem.setFormatCategory("eBook");
						econtentRecord.setFormatBoost(3);
					} else if (format.equalsIgnoreCase("SoundRecording") || format.equalsIgnoreCase("SoundDisc") || format.equalsIgnoreCase("Playaway") || format.equalsIgnoreCase("CDROM") || format.equalsIgnoreCase("SoundCassette") || format.equalsIgnoreCase("CompactDisc") || format.equalsIgnoreCase("eAudio") || format.equalsIgnoreCase("eAudiobook")) {
						econtentItem.setFormat("eAudiobook");
						econtentItem.setFormatCategory("Audio Books");
						econtentRecord.setFormatBoost(8);
					} else if (format.equalsIgnoreCase("MusicRecording")) {
						econtentItem.setFormat("eMusic");
						econtentItem.setFormatCategory("Music");
						econtentRecord.setFormatBoost(5);
					} else if (format.equalsIgnoreCase("MusicalScore")) {
						econtentItem.setFormat("MusicalScore");
						econtentItem.setFormatCategory("eBook");
						econtentRecord.setFormatBoost(5);
					} else if (format.equalsIgnoreCase("Movies") || format.equalsIgnoreCase("Video") || format.equalsIgnoreCase("DVD") || format.equalsIgnoreCase("VideoDisc")) {
						econtentItem.setFormat("eVideo");
						econtentItem.setFormatCategory("Movies");
						econtentRecord.setFormatBoost(10);
					} else if (format.equalsIgnoreCase("Electronic") || format.equalsIgnoreCase("Software")) {
						econtentItem.setFormat("Online Materials");
						econtentItem.setFormatCategory("Other");
						econtentRecord.setFormatBoost(2);
					} else if (format.equalsIgnoreCase("Photo")) {
						econtentItem.setFormat("Photo");
						econtentItem.setFormatCategory("Other");
						econtentRecord.setFormatBoost(2);
					} else if (format.equalsIgnoreCase("Map")) {
						econtentItem.setFormat("Map");
						econtentItem.setFormatCategory("Other");
						econtentRecord.setFormatBoost(2);
					} else if (format.equalsIgnoreCase("Newspaper")) {
						econtentItem.setFormat("Newspaper");
						econtentItem.setFormatCategory("eBook");
						econtentRecord.setFormatBoost(2);
					} else if (format.equalsIgnoreCase("GraphicNovel") || format.equalsIgnoreCase("Manga")) {
						econtentItem.setFormat("eComic");
						econtentItem.setFormatCategory("eBook");
						econtentRecord.setFormatBoost(8);
					} else {
						if (econtentItem.getFormat() == null || econtentItem.getFormat().equals("")) {
							logger.warn("Could not find appropriate eContent format for " + format + " while side loading eContent " + econtentRecord.getFullIdentifier() + " defaulting to Online Materials");
							econtentItem.setFormat("Online Materials");
							econtentItem.setFormatCategory("Other");
							econtentRecord.setFormatBoost(2);
						}
					}
				}
			}else{
				for (String format : printFormats) {
					FormatMapValue formatMapValue = settings.getFormatMapValue(format, BaseIndexingSettings.FORMAT_TYPE_BIB_LEVEL);
					if (formatMapValue != null) {
						econtentItem.setFormat(formatMapValue.getFormat());
						econtentItem.setFormatCategory(formatMapValue.getFormatCategory());
						econtentRecord.setFormatBoost(formatMapValue.getFormatBoost());
					}
					break;
				}
			}
		}
	}

	private void loadDateAdded(String identifier, ItemInfo itemInfo) {
		try {
			getDateAddedStmt.setString(1, profileType);
			getDateAddedStmt.setString(2, identifier);
			ResultSet getDateAddedRS = getDateAddedStmt.executeQuery();
			if (getDateAddedRS.next()) {
				long timeAdded = getDateAddedRS.getLong(1);
				Date curDate = new Date(timeAdded * 1000);
				itemInfo.setDateAdded(curDate);
				getDateAddedRS.close();
			}else{
				logger.debug("Could not determine date added for " + identifier);
			}
		}catch (Exception e){
			logger.error("Unable to load date added for " + identifier);
		}
	}

	public SideLoadSettings getSettings() {
		if (settings instanceof SideLoadSettings) {
			return (SideLoadSettings)this.settings;
		}else{
			return null;
		}
	}
}
