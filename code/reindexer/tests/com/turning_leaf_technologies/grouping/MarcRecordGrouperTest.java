package com.turning_leaf_technologies.grouping;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.NightlyIndexLogEntry;
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

class MarcRecordGrouperTest {
	private static Logger logger;
	private static final String serverName = "unit_tests.localhost";
	private static final String processName = "unit_tests";
	private static Connection dbConn;
	private static final HashMap<String, IndexingProfile> indexingProfiles = new HashMap<>();
	private static final HashMap<String, MarcRecordGrouper> groupingProcessors = new HashMap<>();

	@org.junit.jupiter.api.BeforeAll
	static void setUp() {
		logger = LoggingUtil.setupLogging(serverName, processName);
		Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);
		String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.isEmpty()) {
			logger.error("Database connection information not found in Database Section.  Please specify connection information in database_aspen_jdbc.");
			System.exit(1);
		}
		try {
			dbConn = DriverManager.getConnection(databaseConnectionInfo);
			logger.debug("Connected to aspen database");
			dbConn.prepareCall("SET collation_connection = utf8mb4_general_ci").execute();
			dbConn.prepareCall("SET NAMES utf8mb4").execute();
		} catch (SQLException e) {
			logger.error("Could not connect to aspen database", e);
			System.exit(1);
		}

		//Load indexing profiles
		loadIndexingProfiles();

		//Load record groupers
		loadRecordGroupers();
	}

	@org.junit.jupiter.api.AfterAll
	static void tearDown() {
		try {
			dbConn.close();
		} catch (SQLException e) {
			logger.error("Could not close aspen database", e);
			System.exit(1);
		}
	}

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

	private static Record getMarcRecord(String fileToProcess, IndexingProfile profile) {
		Record marcRecord = null;
		try {
			MarcReader marcReader = new MarcPermissiveStreamReader(new FileInputStream(fileToProcess), true, true, profile.getMarcEncoding());

			if (marcReader.hasNext()) {
				marcRecord = marcReader.next();
			}
		} catch (FileNotFoundException e) {
			//Suppress this, but marc record will be null
		}
		return marcRecord;
	}

	static void loadIndexingProfiles() {
		File formatGroupTestsFile = new File("../../tests/junit/test_definitions/indexing_profiles.csv");
		if (formatGroupTestsFile.exists()) {
			try {
				CSVReader reader = new CSVReader(new FileReader(formatGroupTestsFile));
				reader.readNext();
				String[] indexingProfileData;
				while ((indexingProfileData = reader.readNext()) != null){
					IndexingProfile profile = new IndexingProfile();
					profile.setName(indexingProfileData[0]);
					profile.setGroupingClass(indexingProfileData[1]);
					profile.setIndexingClass(indexingProfileData[2]);
					profile.setFormatSource(indexingProfileData[3]);
					profile.setRecordNumberTag(indexingProfileData[4]);
					profile.setRecordNumberSubfield(indexingProfileData[5].charAt(0));
					profile.setItemTag(indexingProfileData[6]);
					profile.setItemRecordNumberSubfield(indexingProfileData[7].charAt(0));
					profile.setLocationSubfield(indexingProfileData[8].charAt(0));
					profile.setShelvingLocationSubfield(indexingProfileData[9].charAt(0));
					profile.setBarcodeSubfield(indexingProfileData[10].charAt(0));
					profile.setITypeSubfield(indexingProfileData[11].charAt(0));
					profile.setFormatSubfield(indexingProfileData[12].charAt(0));
					indexingProfiles.put(profile.getName(), profile);
				}
			} catch (Exception e) {
				Assertions.assertEquals("", e.toString());
			}
		}
	}

	static void loadRecordGroupers() {

		File recordGroupersFile = new File("../../tests/junit/test_definitions/record_groupers.csv");
		if (recordGroupersFile.exists()) {
			try {
				CSVReader reader = new CSVReader(new FileReader(recordGroupersFile));
				reader.readNext();
				String[] recordGroupersData;
				while ((recordGroupersData = reader.readNext()) != null){
					NightlyIndexLogEntry logEntry = new NightlyIndexLogEntry(dbConn, logger);
					MarcRecordGrouper recordGrouper = new MarcRecordGrouper(serverName, dbConn, indexingProfiles.get(recordGroupersData[1]), logEntry, logger);

					groupingProcessors.put(recordGroupersData[0], recordGrouper);
				}
			} catch (Exception e) {
				Assertions.assertEquals("", e.toString());
			}
		}

		File formatMapsFile = new File("../../tests/junit/test_definitions/format_maps.csv");
		if (formatMapsFile.exists()) {
			try {
				CSVReader reader = new CSVReader(new FileReader(formatMapsFile));
				reader.readNext();
				String[] formatData;
				while ((formatData = reader.readNext()) != null){
					MarcRecordGrouper recordGrouper = groupingProcessors.get(formatData[0]);

					recordGrouper.addTranslationMapValue("formatCategory", formatData[1], formatData[2]);
				}
			} catch (Exception e) {
				Assertions.assertEquals("", e.toString());
			}
		}
	}
}