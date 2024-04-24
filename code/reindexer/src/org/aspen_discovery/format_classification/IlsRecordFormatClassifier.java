package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;

import java.util.LinkedHashSet;
import java.util.Set;

/**
 * Performs classification of a record from the ILS for use when grouping and indexing
 *
 * May take
 */
public class IlsRecordFormatClassifier extends MarcRecordFormatClassifier {
	public IlsRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	protected void getFormatFromFallbackField(org.marc4j.marc.Record record, LinkedHashSet<String> printFormats, BaseIndexingSettings settings) {
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile)settings;
			Set<String> fields = MarcUtil.getFieldList(record, indexingProfile.getFallbackFormatField());
			for (String curField : fields) {
				if (indexingProfile.hasTranslation("format", curField.toLowerCase())) {
					printFormats.add(indexingProfile.translateValue("format", curField));
				}
			}
		}
	}
}
