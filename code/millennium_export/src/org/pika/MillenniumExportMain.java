package org.pika;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.SubfieldImpl;

import java.io.*;
import java.sql.*;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

public class MillenniumExportMain{
	private static Logger logger = Logger.getLogger(MillenniumExportMain.class);
	private static String serverName; //Pika instance name
	private static Connection vufindConn;

	private static IndexingProfile indexingProfile;

	private static String defaultDueDate;

	public static void main(String[] args){
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.millennium_export.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Millennium Extract");

		// Read the base INI file to get information about the server (current directory/conf/config.ini)
		Ini ini = loadConfigFile("config.ini");
		String exportPath = ini.get("Reindex", "marcPath");
		if (exportPath.startsWith("\"")){
			exportPath = exportPath.substring(1, exportPath.length() - 1);
		}

		//Connect to the vufind database
		vufindConn = null;
		try{
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			logger.error("Error connecting to vufind database ", e);
			System.exit(1);
		}

		String profileToLoad = "ils";
		if (args.length > 1){
			profileToLoad = args[1];
		}
		indexingProfile = IndexingProfile.loadIndexingProfile(vufindConn, profileToLoad, logger);

		//We assume that before this process runs, you have already called
		//ITEM_UPDATE_EXTRACT_PIKA.exp to create the export.

		//Information in the export is item based and includes:
		//Bib record, item record, item status, item due date, item location, item barcode
		//All information is tab delimited with no text qualifier.
		//Repeated field values are separated with |
		File[] potentialFiles = new File(exportPath).listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				if (name.matches("ITEM_UPDATE_EXTRACT_PIKA-\\d+-UPDATES")){
					return true;
				}
				return false;
			}
		});

		if (potentialFiles == null || potentialFiles.length == 0){
			logger.error("Could not find updates file to process");
		}else{
			//Sort the potential files alphabetically
			Arrays.sort(potentialFiles);
			//Grab the last file
			File itemUpdateDataFile = potentialFiles[potentialFiles.length - 1];
			if (itemUpdateDataFile.exists()){
				//Yay, we got a file, process it.
				processItemUpdates(itemUpdateDataFile);
			}else{
				logger.error("That's really weird, the update file was deleted while we were looking at it.");
			}
		}

		//Load holds into the database from BIB_HOLDS _EXTRACT
		File holdsExport = new File(exportPath + "/BIB_HOLDS_EXTRACT_PIKA.TXT");
		if (holdsExport.exists()){
			loadHolds(holdsExport);
		}

		//Cleanup
		if (vufindConn != null) {
			try {
				vufindConn.close();
			}catch (Exception e){
				logger.error("error closing connection", e);
			}
		}
	}

	private static long getChecksum(Record marcRecord) {
		CRC32 crc32 = new CRC32();
		crc32.update(marcRecord.toString().getBytes());
		return crc32.getValue();
	}

	private static void loadHolds(File holdsExport) {
		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = vufindConn.setSavepoint();
			vufindConn.setAutoCommit(false);
			vufindConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeQuery();

			PreparedStatement addIlsHoldSummary = vufindConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");
			HashMap<String, Long> numHoldsByBib = new HashMap<>();

			CSVReader holdsReader = new CSVReader(new FileReader(holdsExport), '\t');
			//Read the header
			holdsReader.readNext();
			String[] holdsRow = holdsReader.readNext();
			while (holdsRow != null){
				String bibId = holdsRow[0];
				bibId = "." + bibId;
				//Get the number of holds for this record
				String[] holdDetails = holdsRow[1].split("\\|");

				if (numHoldsByBib.containsKey(bibId)){
					numHoldsByBib.put(bibId, (holdDetails.length) + numHoldsByBib.get(bibId));
				}else{
					numHoldsByBib.put(bibId, (long)holdDetails.length);
				}
				holdsRow = holdsReader.readNext();
			}

			for (String bibId : numHoldsByBib.keySet()){
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

			try {
				vufindConn.commit();
				vufindConn.setAutoCommit(true);
			}catch (Exception e){
				logger.warn("error committing hold updates rolling back", e);
				vufindConn.rollback(startOfHolds);
			}
		} catch (Exception e) {
			logger.error("Unable to export holds from Millennium", e);
			if (startOfHolds != null) {
				try {
					vufindConn.rollback(startOfHolds);
				}catch (Exception e1){
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logger.info("Finished exporting holds");
	}

	private static void processItemUpdates(File itemUpdateDataFile) {
		defaultDueDate = indexingProfile.dueDateFormat.replaceAll("[Mdy]", " ");

		//Last Update in UTC
		long updateTime = new Date().getTime() / 1000;

		SimpleDateFormat csvDateFormat = new SimpleDateFormat("MM-dd-yyyy");
		SimpleDateFormat marcDateFormat = new SimpleDateFormat(indexingProfile.dueDateFormat);

		//Merge item changes with the individual marc records and
		HashMap<String, ArrayList<ItemChangeInfo>> changedItemsByBib = new HashMap<>();
		try {
			CSVReader updateReader = new CSVReader(new FileReader(itemUpdateDataFile), '\t');
			//Read each line in the file
			String[] curItem = updateReader.readNext();
			while (curItem != null){
				if (curItem.length >= 5) {
					ItemChangeInfo changeInfo = new ItemChangeInfo();
					//First record id
					String curId = curItem[0];
					changeInfo.setItemId("." + curItem[1]);
					changeInfo.setStatus(curItem[2]);
					//Convert 4 digit year to 2 digit year
					if (curItem[3].matches("\\d{2}-\\d{2}-\\d{4}")) {
						try {
							changeInfo.setDueDate(marcDateFormat.format(csvDateFormat.parse(curItem[3])));
						} catch (ParseException e) {
							logger.error("Error parsing date " + curItem[3], e);
							changeInfo.setDueDate(curItem[3]);
						}
					}else if (curItem[3].equals("  -  -    ")) {
						changeInfo.setDueDate(defaultDueDate);
					} else {
						changeInfo.setDueDate(curItem[3]);
					}
					changeInfo.setLocation(curItem[4]);

					String fullId = "." + curId;
					ArrayList<ItemChangeInfo> itemChanges;
					if (changedItemsByBib.containsKey(fullId)) {
						itemChanges = changedItemsByBib.get(fullId);
					}else{
						itemChanges = new ArrayList<>();
						changedItemsByBib.put(fullId, itemChanges);
					}
					itemChanges.add(changeInfo);
				}else{
					logger.debug("Invalid row read");
				}

				//Don't forget to read the next line
				curItem = updateReader.readNext();
			}
			updateReader.close();
		} catch (IOException e) {
			logger.error("Unable to read from " + itemUpdateDataFile.getAbsolutePath(), e);
		}

		//indicate that the work needs to be reindexed
		try {
			vufindConn.setAutoCommit(false);
			PreparedStatement markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;

			logger.info("A total of " + changedItemsByBib.size() + " bibs were updated");
			int numUpdates = 0;
			for (String curBibId : changedItemsByBib.keySet()) {
				//Update the marc record
				long newChecksum = updateMarc(curBibId, changedItemsByBib.get(curBibId));
				//Mark that the checksum has changed within database
				//Update the database
				try {
					markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
					markGroupedWorkForBibAsChangedStmt.setString(2, curBibId);
					markGroupedWorkForBibAsChangedStmt.executeUpdate();

					numUpdates++;
					if (numUpdates % 50 == 0) {
						vufindConn.commit();
					}
				} catch (SQLException e) {
					logger.error("Could not mark that " + curBibId + " was changed due to error ", e);
				}
			}
			//Turn auto commit back on
			vufindConn.commit();
			vufindConn.setAutoCommit(true);
		}catch (SQLException e){
			logger.error("Error updating the database ", e);
		}

	}

	private static Long updateMarc(String curBibId, ArrayList<ItemChangeInfo> itemChanges) {
		//Load the existing marc record from file
		long newChecksum = -1L;
		try {
			File marcFile = indexingProfile.getFileForIlsRecord(curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					Long oldChecksum = getChecksum(marcRecord);
					inputStream.close();

					//Loop through all item fields to see what has changed
					List<VariableField> itemFields = marcRecord.getVariableFields(indexingProfile.itemTag);
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(indexingProfile.itemRecordNumberSubfield) != null) {
							String itemRecordNumber = itemField.getSubfield(indexingProfile.itemRecordNumberSubfield).getData();
							//Update the items
							for (ItemChangeInfo curItem : itemChanges) {
								//Find the correct item
								if (itemRecordNumber.equals(curItem.getItemId())) {
									setSubfield(itemField, indexingProfile.locationSubfield, curItem.getLocation());
									setSubfield(itemField, indexingProfile.itemStatusSubfield, curItem.getStatus());

									if (curItem.getDueDate() == null) {
										if (itemField.getSubfield(indexingProfile.dueDateSubfield) != null) {
											itemField.getSubfield(indexingProfile.dueDateSubfield).setData(defaultDueDate);
										}
									} else {
										if (itemField.getSubfield(indexingProfile.dueDateSubfield) == null) {
											itemField.addSubfield(new SubfieldImpl(indexingProfile.dueDateSubfield, curItem.getDueDate()));
										} else {
											itemField.getSubfield(indexingProfile.dueDateSubfield).setData(curItem.getDueDate());
										}
									}
									itemChanges.remove(curItem);
									break;
								}
							}
						}
					}

					for (ItemChangeInfo itemChange : itemChanges){
						//Reduce logging level to debug since this happens frequently with new items.
						logger.debug("Could not find item within bib " + curBibId + " for item " + itemChange.getItemId());
					}

					//Write the new marc record
					MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile, false));
					writer.write(marcRecord);
					writer.close();

					newChecksum = getChecksum(marcRecord);


				} else {
					logger.warn("Could not read marc record for " + curBibId);
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + " it is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
		return newChecksum;
	}

	private static void setSubfield(DataField itemField, char subfield, String value) {
		if (value == null){
			value = "";
		}
		if (itemField.getSubfield(subfield) == null){
			if (value.length() > 0){
				itemField.addSubfield(new SubfieldImpl(subfield, value));
			}
		}else{
			itemField.getSubfield(subfield).setData(value);
		}
	}

	private static String getFileIdForRecordNumber(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}
		return shortId;
	}

	private static Ini loadConfigFile(String filename){
		//First load the default config file
		String configName = "../../sites/default/conf/" + filename;
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find configuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		Ini ini = new Ini();
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}

		//Now override with the site specific configuration
		String siteSpecificFilename = "../../sites/" + serverName + "/conf/" + filename;
		logger.info("Loading site specific config from " + siteSpecificFilename);
		File siteSpecificFile = new File(siteSpecificFilename);
		if (!siteSpecificFile.exists()) {
			logger.error("Could not find server specific config file");
			System.exit(1);
		}
		try {
			Ini siteSpecificIni = new Ini();
			siteSpecificIni.load(new FileReader(siteSpecificFile));
			for (Profile.Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
			//Also load password files if they exist
			String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
			logger.info("Loading password config from " + siteSpecificPassword);
			File siteSpecificPasswordFile = new File(siteSpecificPassword);
			if (siteSpecificPasswordFile.exists()) {
				Ini siteSpecificPwdIni = new Ini();
				siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
				for (Profile.Section curSection : siteSpecificPwdIni.values()){
					for (String curKey : curSection.keySet()){
						ini.put(curSection.getName(), curKey, curSection.get(curKey));
					}
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}

		return ini;
	}

	private static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}
}