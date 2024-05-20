package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.ItemInfo;
import org.aspen_discovery.reindexer.RecordInfo;
import org.marc4j.marc.DataField;

public class KohaRecordFormatClassifier extends IlsRecordFormatClassifier{
	public KohaRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	@Override
	public FormatInfo getFormatInfoForItem(DataField itemField, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		IndexingProfile profile = (IndexingProfile)settings;

		boolean foundFormatFromShelfLocation = false;
		FormatInfo formatInfo = new FormatInfo();

		String formatBoost = null;
		String shelfLocationCode = MarcUtil.getItemSubfieldData(profile.getShelvingLocationSubfield(), itemField, logEntry, logger);
		if (shelfLocationCode != null) {
			String shelfLocation = shelfLocationCode.toLowerCase().trim();
			if (profile.hasTranslation("format", shelfLocation)) {
				String translatedLocation = profile.translateValue("format", shelfLocation);
				if (translatedLocation != null && !translatedLocation.isEmpty()) {
					foundFormatFromShelfLocation = true;
					formatInfo.format = translatedLocation;
					formatInfo.formatCategory = profile.translateValue("format_category", shelfLocation);
					if (profile.hasTranslation("format_boost", shelfLocation)) {
						formatBoost = profile.translateValue("format_boost", shelfLocation);
					}
				}
			}
		}

		boolean foundFormatFromSublocation = false;
		String subLocationCode = MarcUtil.getItemSubfieldData(profile.getSubLocationSubfield(), itemField, logEntry, logger);
		if (!foundFormatFromShelfLocation && subLocationCode != null && !subLocationCode.isEmpty()) {
			String subLocation = subLocationCode.toLowerCase().trim();
			if (profile.hasTranslation("format", subLocation)) {
				String translatedLocation = profile.translateValue("format", subLocation);
				if (translatedLocation != null && !translatedLocation.isEmpty()) {
					foundFormatFromSublocation = true;
					formatInfo.format = translatedLocation;
					formatInfo.formatCategory = profile.translateValue("format_category", subLocation);
					if (profile.hasTranslation("format_boost", subLocation)) {
						formatBoost = profile.translateValue("format_boost", subLocation);
					}
				}
			}
		}

		boolean foundFormatFromCollection = false;
		String collectionCode = MarcUtil.getItemSubfieldData(profile.getCollectionSubfield(), itemField, logEntry, logger);
		if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && collectionCode != null) {
			collectionCode = collectionCode.toLowerCase().trim();
			if (profile.hasTranslation("format", collectionCode)) {
				String translatedFormat = profile.translateValue("format", collectionCode);
				if (translatedFormat != null && !translatedFormat.isEmpty()) {
					foundFormatFromCollection = true;
					formatInfo.format = translatedFormat;
					formatInfo.formatCategory = profile.translateValue("format_category", collectionCode);
					if (profile.hasTranslation("format_boost", collectionCode)) {
						formatBoost = profile.translateValue("format_boost", collectionCode);
					}
				}
			}else{
				//Check to see if the translated collection code is used
				if (profile.hasTranslation("collection", collectionCode)) {
					String translatedCollection = profile.translateValue("collection", collectionCode);
					if (profile.hasTranslation("format", translatedCollection)) {
						String translatedFormat = profile.translateValue("format", translatedCollection);
						if (translatedFormat != null && !translatedFormat.isEmpty()) {
							foundFormatFromCollection = true;
							formatInfo.format = translatedFormat;
							formatInfo.formatCategory = profile.translateValue("format_category", translatedCollection);
							if (profile.hasTranslation("format_boost", translatedCollection)) {
								formatBoost = profile.translateValue("format_boost", translatedCollection);
							}
						}
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
				String translatedFormat = profile.translateValue("format", iType);
				if (translatedFormat != null && !translatedFormat.isEmpty()) {
					foundFormatFromIType = true;
					formatInfo.format = translatedFormat;
					formatInfo.formatCategory = profile.translateValue("format_category", iType);
					if (profile.hasTranslation("format_boost", iType)) {
						formatBoost = profile.translateValue("format_boost", iType);
					}
				}
			}
		}

		if (!foundFormatFromShelfLocation && !foundFormatFromSublocation && !foundFormatFromCollection && !foundFormatFromIType) {
			String format = MarcUtil.getItemSubfieldData(profile.getFormatSubfield(), itemField, logEntry, logger);
			if (format != null && profile.hasTranslation("format", format)) {
				String translatedFormat = profile.translateValue("format", format);
				if (translatedFormat != null && !translatedFormat.isEmpty()) {
					formatInfo.format = translatedFormat;
					formatInfo.formatCategory = profile.translateValue("format_category", format);
					if (profile.hasTranslation("format_boost", format)) {
						formatBoost = profile.translateValue("format_boost", format);
					}
				}
			}
		}

		try {
			if (formatBoost != null && !formatBoost.isEmpty()) {
				formatInfo.formatBoost = Integer.parseInt(formatBoost);
			}
		} catch (Exception e) {
			if (!unhandledFormatBoosts.contains(formatInfo.format)){
				unhandledFormatBoosts.add(formatInfo.format);
				logger.warn("Could not get boost for format " + formatInfo.format);
			}
		}

		if (formatInfo.format == null) {
			return null;
		}else {
			return formatInfo;
		}
	}
}
