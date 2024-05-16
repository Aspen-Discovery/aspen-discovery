package org.aspen_discovery.grouping;

import com.turning_leaf_technologies.indexing.IndexingProfile;
import org.aspen_discovery.AbstractIndexingTest;
import org.junit.jupiter.api.Assertions;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.CsvFileSource;
import org.marc4j.marc.Record;

class MarcRecordGrouperTest extends AbstractIndexingTest {

	@ParameterizedTest
	@CsvFileSource(resources = "/format_group_tests.csv")
	void testGroupingCategory(String marcFileName, String profileName, String grouperName, String expectedGroup, String description, String ticket) {
		IndexingProfile profile = indexingProfiles.get(profileName);

		MarcRecordGrouper testGrouper = groupingProcessors.get(grouperName);

		Record marcRecord = getMarcRecord("../../tests/junit/sample_marcs/" + marcFileName, profile);
		Assertions.assertNotNull(marcRecord, "Could not load sample marc record");

		GroupedWork testGroupedWork = new GroupedWork(testGrouper);
		String groupingCategory = testGrouper.setGroupingCategoryForWork(marcRecord, testGroupedWork);
		//noinspection TestFailedLine
		Assertions.assertEquals(expectedGroup, groupingCategory);
	}

}