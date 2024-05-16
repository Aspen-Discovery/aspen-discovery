package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.ItemInfo;
import org.aspen_discovery.reindexer.RecordInfo;
import org.marc4j.marc.DataField;

import java.util.*;

/**
 * Performs classification of a record from the ILS for use when grouping and indexing
 *
 * May take
 */
public class IlsRecordFormatClassifier extends MarcRecordFormatClassifier {
	public IlsRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	@Override
	public LinkedHashSet<FormatInfo> getFormatsForRecord(org.marc4j.marc.Record record, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger){
		IndexingProfile indexingProfile = (IndexingProfile)settings;
		LinkedHashSet<FormatInfo> formats = new LinkedHashSet<>();
		if (settings.getFormatSource().equals("item")) {
			List<DataField> itemFields = MarcUtil.getDataFields(record, indexingProfile.getItemTagInt());
			for (DataField itemField : itemFields) {
				FormatInfo itemFormatInfo = this.getFormatInfoForItem(itemField, settings, logEntry, logger);
				if (itemFormatInfo != null) {
					formats.add(itemFormatInfo);
				}
			}
		}
		if (!formats.isEmpty()) {
			return formats;
		}else {
			return super.getFormatsForRecord(record, settings, logEntry, logger);
		}
	}

	/**
	 * Retrieve a format from the specified fallback field.  This is untranslated because it gets translated later.
	 *
	 * @param record
	 * @param printFormats
	 * @param settings
	 */
	protected void getFormatFromFallbackField(org.marc4j.marc.Record record, LinkedHashSet<String> printFormats, BaseIndexingSettings settings) {
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile)settings;
			Set<String> fields = MarcUtil.getFieldList(record, indexingProfile.getFallbackFormatField());
			for (String curField : fields) {
				if (indexingProfile.hasTranslation("format", curField.toLowerCase())) {
					printFormats.add( curField);
				}
			}
		}
	}

	protected final HashSet<String> unhandledFormatBoosts = new HashSet<>();

	@Override
	public void loadItemFormat(RecordInfo recordInfo, DataField itemField, ItemInfo itemInfo, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		if (itemInfo.isEContent()) {return;}

		FormatInfo formatInfo = getFormatInfoForItem(itemField, settings, logEntry, logger);
		if (formatInfo != null) {
			itemInfo.setFormat(formatInfo.format);
			itemInfo.setFormatCategory(formatInfo.formatCategory);
			if (formatInfo.formatBoost != 0) {
				recordInfo.setFormatBoost(formatInfo.formatBoost);
			}
		}
	}

	public FormatInfo getFormatInfoForItem(DataField itemField, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		IndexingProfile profile = (IndexingProfile)settings;
		if (settings.getFormatSource().equals("item") && profile.getFormatSubfield() != ' '){
			String format = MarcUtil.getItemSubfieldData(profile.getFormatSubfield(), itemField, logEntry, logger);
			if (format != null) {
				format = format.toLowerCase(Locale.ROOT);
				if (profile.hasTranslation("format", format)) {
					String translatedFormat = profile.translateValue("format", format);
					if (translatedFormat != null && !translatedFormat.isEmpty()) {
						FormatInfo formatInfo = new FormatInfo();
						formatInfo.format = translatedFormat;
						if (profile.hasTranslation("format_category", format)) {
							formatInfo.formatCategory = profile.translateValue("format_category", format);
						}
						String formatBoost = null;
						if (profile.hasTranslation("format_boost", format)) {
							formatBoost = profile.translateValue("format_boost", format);
						}
						try {
							if (formatBoost != null && !formatBoost.isEmpty()) {
								formatInfo.formatBoost = Integer.parseInt(formatBoost);
							}
						} catch (Exception e) {
							if (!unhandledFormatBoosts.contains(format)) {
								unhandledFormatBoosts.add(format);
								logger.warn("Could not get boost for format " + format);
							}
						}
						return formatInfo;
					}
				}
			}
		}
		return null;
	}
}
