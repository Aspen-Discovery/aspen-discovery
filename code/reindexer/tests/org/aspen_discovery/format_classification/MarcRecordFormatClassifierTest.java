package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.IndexingProfile;
import org.aspen_discovery.AbstractIndexingTest;
import org.aspen_discovery.grouping.MarcRecordGrouper;
import org.aspen_discovery.reindexer.Util;
import org.junit.jupiter.api.Assertions;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.CsvFileSource;
import org.marc4j.marc.Record;

import java.util.LinkedHashSet;

class MarcRecordFormatClassifierTest extends AbstractIndexingTest{
	@ParameterizedTest
	@CsvFileSource(resources = "/format_tests.csv")
	void testGroupingCategory(String marcFileName, String profileName, String grouperName, String expectedFormats, String description, String ticket) {
		IndexingProfile profile = indexingProfiles.get(profileName);
		MarcRecordGrouper testGrouper = groupingProcessors.get(grouperName);

		Record marcRecord = getMarcRecord("../../tests/junit/sample_marcs/" + marcFileName, profile);
		Assertions.assertNotNull(marcRecord, "Could not load sample marc record");

		MarcRecordFormatClassifier marcRecordFormatClassifier = testGrouper.getFormatClassifier();
		LinkedHashSet<String> formats = marcRecordFormatClassifier.getFormatsFromBib(marcRecord, profile);
		String formatsAsString = Util.getCsvSeparatedString(formats);
		Assertions.assertEquals(expectedFormats, formatsAsString);
	}
}