package org.aspen_discovery;
import com.opencsv.CSVReader;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.SierraExportFieldMapping;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.aspen_discovery.grouping.MarcRecordGrouper;
import org.aspen_discovery.reindexer.NightlyIndexLogEntry;
import org.ini4j.Ini;
import org.junit.jupiter.api.Assertions;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.HashMap;

public abstract class AbstractIndexingTest  {
	protected static Logger logger;
	protected static final String serverName = "unit_tests.localhost";
	protected static final String processName = "unit_tests";
	protected static Connection dbConn;
	protected static final HashMap<String, IndexingProfile> indexingProfiles = new HashMap<>();
	protected static final HashMap<String, MarcRecordGrouper> groupingProcessors = new HashMap<>();

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

	protected static Record getMarcRecord(String fileToProcess, IndexingProfile profile) {
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
					IndexingProfile profile = new IndexingProfile(serverName, null);
					int curCol = 0;
					profile.setName(indexingProfileData[curCol++]);
					profile.setMarcEncoding("UTF8");
					profile.setGroupingClass(indexingProfileData[curCol++]);
					profile.setIndexingClass(indexingProfileData[curCol++]);
					profile.setFormatSource(indexingProfileData[curCol++]);
					profile.setRecordNumberTag(indexingProfileData[curCol++]);
					profile.setRecordNumberSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setItemTag(indexingProfileData[curCol++]);
					profile.setItemRecordNumberSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setLocationSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setSubLocationSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setShelvingLocationSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setBarcodeSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setITypeSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setCollectionSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setFormatSubfield(AspenStringUtils.convertStringToChar(indexingProfileData[curCol++]));
					profile.setFallbackFormatField(indexingProfileData[curCol++]);
					profile.setCheckSierraMatTypeForFormat(indexingProfileData[curCol++].equals("1"));

					if (profile.getIndexingClass().equals("III")) {
						SierraExportFieldMapping fieldMapping = new SierraExportFieldMapping();
						profile.setSierraExportFieldMappings(fieldMapping);
					}
					indexingProfiles.put(profile.getName(), profile);
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
					//Make sure the line is actually filled out
					if (formatData.length >= 2) {
						IndexingProfile profile = indexingProfiles.get(formatData[0]);
						Assertions.assertNotNull(profile, "No record grouper found for " + formatData[0]);

						profile.addTranslationMapValue("format_category", formatData[1], formatData[2]);
						profile.addTranslationMapValue("format", formatData[1], formatData[3]);
					}
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
	}
}
