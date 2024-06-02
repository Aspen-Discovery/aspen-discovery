package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.SierraExportFieldMapping;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.reindexer.AbstractGroupedWorkSolr;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.util.LinkedHashSet;
import java.util.List;

public class IIIRecordFormatClassifier extends IlsRecordFormatClassifier {
	public IIIRecordFormatClassifier(Logger logger) {
		super(logger);
	}

	public LinkedHashSet<String> getUntranslatedFormatsFromBib(AbstractGroupedWorkSolr groupedWork, org.marc4j.marc.Record record, BaseIndexingSettings settings){
		LinkedHashSet<String> formats = new LinkedHashSet<>();
		if (settings instanceof IndexingProfile) {
			IndexingProfile indexingProfile = (IndexingProfile)settings;
			SierraExportFieldMapping exportFieldMapping = indexingProfile.getSierraExportFieldMappings();
			if (exportFieldMapping != null) {
				if (indexingProfile.isCheckSierraMatTypeForFormat()) {
					List<DataField> sierraFixedFields = record.getDataFields(exportFieldMapping.getFixedFieldDestinationFieldInt());
					for (DataField sierraFixedField : sierraFixedFields) {
						Subfield matTypeSubfield = sierraFixedField.getSubfield(exportFieldMapping.getMaterialTypeSubfield());
						if (matTypeSubfield != null) {
							String formatValue = matTypeSubfield.getData().trim();
							if (indexingProfile.hasFormat(formatValue, BaseIndexingSettings.FORMAT_TYPE_MAT_TYPE)) {
								if (groupedWork != null && groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format is " + formatValue + " based on Mat Type " + formatValue, 2);}
								formats.add(formatValue);
								break;
							}
						}
					}
				}
			}
		}
		if (formats.isEmpty()) {
			return super.getUntranslatedFormatsFromBib(groupedWork, record, settings);
		}else{
			return formats;
		}
	}
}
