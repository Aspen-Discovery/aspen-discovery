package org.aspen_discovery.grouping;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.aspen_discovery.AbstractIndexingTest;
import org.aspen_discovery.reindexer.NightlyIndexLogEntry;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.junit.jupiter.api.Assertions;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.CsvFileSource;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.HashMap;

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
		Assertions.assertEquals(expectedGroup, groupingCategory);
	}

}