package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.AbstractGroupedWorkSolr;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.util.HashMap;
import java.util.LinkedHashSet;
import java.util.List;

public class NashvilleRecordFormatClassifier extends IlsRecordFormatClassifier{
	public NashvilleRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	public LinkedHashSet<String> getUntranslatedFormatsFromBib(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, BaseIndexingSettings settings){
		//Although Nashville is set to load format from the bib, it actually checks items first.
		LinkedHashSet<String> formats = new LinkedHashSet<>();
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile) settings;
			List<DataField> items = MarcUtil.getDataFields(record, indexingProfile.getItemTagInt());
			boolean allItemsAreOrderRecords = true;
			HashMap<String, Integer> printFormats = new HashMap<>();
			for (DataField curItem : items) {
				Subfield shelfLocationField = curItem.getSubfield(indexingProfile.getShelvingLocationSubfield());
				boolean hasLocationBasedFormat = false;
				if (shelfLocationField != null) {
					String shelfLocation = shelfLocationField.getData().toLowerCase();
					//noinspection SpellCheckingInspection
					if (!shelfLocation.equals("xord")) {
						allItemsAreOrderRecords = false;
					}
					String printFormatLower = null;
					if (shelfLocation.endsWith("ltp")) {
						printFormatLower = "largeprint";
						hasLocationBasedFormat = true;
					} else if (shelfLocation.endsWith("board")) {
						printFormatLower = "board";
						hasLocationBasedFormat = true;
					}
					if (hasLocationBasedFormat) {
						if (!printFormats.containsKey(printFormatLower)) {
							printFormats.put(printFormatLower, 1);
						} else {
							printFormats.put(printFormatLower, printFormats.get(printFormatLower) + 1);
						}
					}
				} else {
					allItemsAreOrderRecords = false;
				}
				if (!hasLocationBasedFormat) {
					Subfield formatField = curItem.getSubfield(indexingProfile.getFormatSubfield());
					if (formatField != null) {
						String curFormat = formatField.getData();
						String printFormatLower = curFormat.toLowerCase();
						if (!printFormats.containsKey(printFormatLower)) {
							printFormats.put(printFormatLower, 1);
						} else {
							printFormats.put(printFormatLower, printFormats.get(printFormatLower) + 1);
						}
						if (!printFormatLower.equals("bk") && !printFormatLower.equals("oth") && !printFormatLower.equals("ord")) {
							allItemsAreOrderRecords = false;
						}
					} else {
						allItemsAreOrderRecords = false;
					}
				}
			}

			if (allItemsAreOrderRecords) {
				return super.getUntranslatedFormatsFromBib(groupedWork, record, settings);
			}

			int maxPrintFormats = 0;
			String selectedFormat = "";
			if (printFormats.size() > 1) {
				for (String printFormat : printFormats.keySet()) {
					int numUsages = printFormats.get(printFormat);
					logger.info("  " + printFormat + " used " + numUsages + " times");
					if (numUsages > maxPrintFormats) {
						selectedFormat = printFormat;
						maxPrintFormats = numUsages;
					}
				}
				logger.info("  Selected Format is " + selectedFormat);
			} else if (printFormats.size() == 1) {
				selectedFormat = printFormats.keySet().iterator().next();
			} else {
				//format not found based on item records.
				//TODO Fall back to default method?
				selectedFormat = "On Order";
			}
			//Do not translate formats since they will be translated later
			formats.add(selectedFormat);
		}

		if (formats.isEmpty()) {
			return super.getUntranslatedFormatsFromBib(groupedWork, record, settings);
		}else{
			return formats;
		}
	}
}
