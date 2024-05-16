package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.SierraExportFieldMapping;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.util.LinkedHashSet;

public class IIIRecordFormatClassifier extends IlsRecordFormatClassifier {
	public IIIRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	public LinkedHashSet<String> getUntranslatedFormatsFromBib(org.marc4j.marc.Record record, BaseIndexingSettings settings){
		LinkedHashSet<String> formats = new LinkedHashSet<>();
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile)settings;
			SierraExportFieldMapping exportFieldMapping = indexingProfile.getSierraExportFieldMappings();
			if (exportFieldMapping != null) {
				if (indexingProfile.isCheckSierraMatTypeForFormat()) {
					DataField sierraFixedField = record.getDataField(exportFieldMapping.getFixedFieldDestinationFieldInt());
					if (sierraFixedField != null) {
						Subfield matTypeSubfield = sierraFixedField.getSubfield(exportFieldMapping.getMaterialTypeSubfield());
						if (matTypeSubfield != null) {
							String formatValue = matTypeSubfield.getData().trim();
							if (indexingProfile.hasTranslation("format", formatValue)) {
								formats.add(formatValue);

							}
						}
					}
				}
			}
		}
		if (formats.isEmpty()) {
			return super.getUntranslatedFormatsFromBib(record, settings);
		}else{
			return formats;
		}
	}
}
