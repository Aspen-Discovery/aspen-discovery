package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.FormatMapValue;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.AbstractGroupedWorkSolr;
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
	public LinkedHashSet<FormatInfo> getFormatsForRecord(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger){
		IndexingProfile indexingProfile = (IndexingProfile)settings;
		LinkedHashSet<FormatInfo> formats = new LinkedHashSet<>();
		if (settings.getFormatSource().equals("item")) {
			List<DataField> itemFields = MarcUtil.getDataFields(record, indexingProfile.getItemTagInt());
			for (DataField itemField : itemFields) {
				String itemIdentifier = itemField.getSubfield(((IndexingProfile) settings).getItemRecordNumberSubfield()).getData();
				FormatInfo itemFormatInfo = this.getFormatInfoForItem(groupedWork, itemIdentifier, itemField, settings, logEntry, logger);
				if (itemFormatInfo != null) {
					formats.add(itemFormatInfo);
				}
			}
		}
		if (!formats.isEmpty()) {
			return formats;
		}else {
			return super.getFormatsForRecord(groupedWork, record, settings, logEntry, logger);
		}
	}

	/**
	 * Retrieve a format from the specified fallback field.  This is untranslated because it gets translated later.
	 *
	 * @param record - The record being processed
	 * @param printFormats - a list of formats that apply
	 * @param settings - Settings used when determining the format
	 */
	protected void getFormatFromFallbackField(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, LinkedHashSet<String> printFormats, BaseIndexingSettings settings) {
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile)settings;
			Set<String> fields = MarcUtil.getFieldList(record, indexingProfile.getFallbackFormatField());
			for (String curField : fields) {
				if (indexingProfile.hasFormat(curField.toLowerCase(), BaseIndexingSettings.FORMAT_TYPE_FALLBACK_FORMAT)) {
					if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Adding format " + curField + " based on fallback format", 2);}
					printFormats.add( curField);
				}
			}
		}
	}

	@Override
	public void loadItemFormat(AbstractGroupedWorkSolr groupedWork, RecordInfo recordInfo, DataField itemField, ItemInfo itemInfo, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		if (itemInfo.isEContent()) {return;}

		FormatInfo formatInfo = getFormatInfoForItem(groupedWork, itemInfo.getItemIdentifier(), itemField, settings, logEntry, logger);
		if (formatInfo != null) {
			itemInfo.setFormat(formatInfo.format);
			itemInfo.setFormatCategory(formatInfo.formatCategory);
			if (formatInfo.formatBoost != 0) {
				recordInfo.setFormatBoost(formatInfo.formatBoost);
			}
		}
	}

	public FormatInfo getFormatInfoForItem(AbstractGroupedWorkSolr groupedWork, String itemIdentifier, DataField itemField, BaseIndexingSettings settings, BaseIndexingLogEntry logEntry, Logger logger) {
		IndexingProfile profile = (IndexingProfile)settings;
		if (settings.getFormatSource().equals("item") && profile.getFormatSubfield() != ' '){
			String format = MarcUtil.getItemSubfieldData(profile.getFormatSubfield(), itemField, logEntry, logger);
			if (format != null) {
				FormatMapValue formatMapValue = profile.getFormatMapValue(format, BaseIndexingSettings.FORMAT_TYPE_ITEM_FORMAT);
				if (formatMapValue != null) {
					if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format is " + formatMapValue.getFormat() + " based on Item Format of " + format, 2);}
					return new FormatInfo(formatMapValue, BaseIndexingSettings.FORMAT_TYPE_ITEM_FORMAT);
				}
			}
		}
		return null;
	}
}
