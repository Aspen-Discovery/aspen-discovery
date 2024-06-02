package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.FormatMapValue;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.AbstractGroupedWorkSolr;
import org.marc4j.marc.DataField;

public class KohaRecordFormatClassifier extends IlsRecordFormatClassifier{
	public KohaRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	@Override
	public FormatInfo getFormatInfoForItem(AbstractGroupedWorkSolr groupedWork, String itemIdentifier, DataField itemField, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		IndexingProfile profile = (IndexingProfile)settings;

		boolean foundFormatFromShelfLocation = false;
		FormatInfo formatInfo = new FormatInfo();

		String shelfLocationCode = MarcUtil.getItemSubfieldData(profile.getShelvingLocationSubfield(), itemField, logEntry, logger);
		if (shelfLocationCode != null) {
			String shelfLocation = shelfLocationCode.toLowerCase().trim();
			FormatMapValue translatedLocation = profile.getFormatMapValue(shelfLocation, BaseIndexingSettings.FORMAT_TYPE_ITEM_SHELVING_LOCATION);
			if (translatedLocation != null) {
				foundFormatFromShelfLocation = true;
				formatInfo.setFormatFromMap(translatedLocation, BaseIndexingSettings.FORMAT_TYPE_ITEM_SHELVING_LOCATION);
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format for item " + itemIdentifier + " is " + formatInfo.format + " based on shelf location", 2);}
			}
		}

		boolean foundFormatFromSublocation = false;
		String subLocationCode = MarcUtil.getItemSubfieldData(profile.getSubLocationSubfield(), itemField, logEntry, logger);
		if (!foundFormatFromShelfLocation && subLocationCode != null && !subLocationCode.isEmpty()) {
			String subLocation = subLocationCode.toLowerCase().trim();
			FormatMapValue translatedLocation = profile.getFormatMapValue(subLocation, BaseIndexingSettings.FORMAT_TYPE_ITEM_SUBLOCATION);
			if (translatedLocation != null) {
				foundFormatFromSublocation = true;
				formatInfo.setFormatFromMap(translatedLocation, BaseIndexingSettings.FORMAT_TYPE_ITEM_SUBLOCATION);
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format for item " + itemIdentifier + " is " + formatInfo.format + " based on sub location", 2);}
			}
		}

		boolean foundFormatFromCollection = false;
		String collectionCode = MarcUtil.getItemSubfieldData(profile.getCollectionSubfield(), itemField, logEntry, logger);
		if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && collectionCode != null) {
			collectionCode = collectionCode.toLowerCase().trim();
			FormatMapValue translatedFormat = profile.getFormatMapValue(collectionCode, BaseIndexingSettings.FORMAT_TYPE_ITEM_COLLECTION);
			if (translatedFormat != null) {
				foundFormatFromCollection = true;
				formatInfo.setFormatFromMap(translatedFormat, BaseIndexingSettings.FORMAT_TYPE_ITEM_COLLECTION);
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format for item " + itemIdentifier + " is " + formatInfo.format + " based on collection code", 2);}
			}else{
				//Check to see if the translated collection code is used
				if (profile.hasTranslation("collection", collectionCode)) {
					String translatedCollection = profile.translateValue("collection", collectionCode);
					translatedFormat = profile.getFormatMapValue(translatedCollection, BaseIndexingSettings.FORMAT_TYPE_ITEM_COLLECTION);
					if (translatedFormat != null) {
						foundFormatFromCollection = true;
						formatInfo.setFormatFromMap(translatedFormat, BaseIndexingSettings.FORMAT_TYPE_ITEM_COLLECTION);
						if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format for item " + itemIdentifier + " is " + formatInfo.format + " based on translated collection", 2);}
					}
				}
			}
		}

		boolean foundFormatFromIType = false;
		if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && !foundFormatFromCollection) {
			String iTypeCode = MarcUtil.getItemSubfieldData(profile.getITypeSubfield(), itemField, logEntry, logger);
			if (iTypeCode != null) {
				String iType = iTypeCode.toLowerCase().trim();
				//Translate the iType to see what formats we get.  Some item types do not have a format by default and use the default translation
				FormatMapValue translatedFormat = profile.getFormatMapValue(iType, BaseIndexingSettings.FORMAT_TYPE_ITEM_TYPE);
				if (translatedFormat != null) {
					foundFormatFromIType = true;
					formatInfo.setFormatFromMap(translatedFormat, BaseIndexingSettings.FORMAT_TYPE_ITEM_TYPE);
					if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format for item " + itemIdentifier + " is " + formatInfo.format + " based on item type", 2);}
				}
			}
		}

		if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && !foundFormatFromCollection && !foundFormatFromIType) {
			String format = MarcUtil.getItemSubfieldData(profile.getFormatSubfield(), itemField, logEntry, logger);
			FormatMapValue translatedFormat = profile.getFormatMapValue(format, BaseIndexingSettings.FORMAT_TYPE_ITEM_FORMAT);
			if (translatedFormat != null) {
				formatInfo.setFormatFromMap(translatedFormat, BaseIndexingSettings.FORMAT_TYPE_ITEM_FORMAT);
				if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format for item " + itemIdentifier + " is " + formatInfo.format + " based on item format field", 2);}
			}
		}

		if (formatInfo.format == null) {
			return null;
		}else {
			return formatInfo;
		}
	}
}
