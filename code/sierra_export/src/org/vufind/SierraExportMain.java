package org.vufind;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

import au.com.bytecode.opencsv.CSVWriter;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONObject;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;
import org.apache.commons.codec.binary.Base64;
import org.marc4j.*;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.SubfieldImpl;

/**
 * Export data to
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/15/13
 * Time: 8:59 AM
 */
public class SierraExportMain{
	private static Logger logger = Logger.getLogger(SierraExportMain.class);
	private static String serverName;

	private static IndexingProfile indexingProfile;

	private static boolean exportItemHolds = true;
	private static boolean suppressOrderRecordsThatAreReceivedAndCatalogged = false;
	private static boolean suppressOrderRecordsThatAreCatalogged = false;
	private static String orderStatusesToExport;

	public static void main(String[] args){
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.sierra_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Sierra Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");
		String exportPath = ini.get("Reindex", "marcPath");
		if (exportPath.startsWith("\"")){
			exportPath = exportPath.substring(1, exportPath.length() - 1);
		}
		String exportItemHoldsStr = ini.get("Catalog", "exportItemHolds");
		if (exportItemHoldsStr != null){
			exportItemHolds = exportItemHoldsStr.equalsIgnoreCase("true");
		}
		String suppressOrderRecordsThatAreReceivedAndCataloggedStr = ini.get("Catalog", "suppressOrderRecordsThatAreReceivedAndCatalogged");
		if (suppressOrderRecordsThatAreReceivedAndCataloggedStr != null){
			suppressOrderRecordsThatAreReceivedAndCatalogged = suppressOrderRecordsThatAreReceivedAndCataloggedStr.equalsIgnoreCase("true");
		}
		String suppressOrderRecordsThatAreCataloggedStr = ini.get("Catalog", "suppressOrderRecordsThatAreCatalogged");
		if (suppressOrderRecordsThatAreCataloggedStr != null){
			suppressOrderRecordsThatAreCatalogged = suppressOrderRecordsThatAreCataloggedStr.equalsIgnoreCase("true");
		}

		//Connect to the vufind database
		Connection vufindConn = null;
		try{
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to vufind database " + e.toString());
			System.exit(1);
		}

		String profileToLoad = "ils";
		if (args.length > 1){
			profileToLoad = args[1];
		}
		indexingProfile = IndexingProfile.loadIndexingProfile(vufindConn, profileToLoad, logger);

		//Get a list of works that have changed since the last index
		getChangedRecordsFromApi(ini, vufindConn, exportPath);

		//Connect to the sierra database
		String url = ini.get("Catalog", "sierra_db");
		if (url.startsWith("\"")){
			url = url.substring(1, url.length() - 1);
		}
		Connection conn = null;
		try{
			//Open the connection to the database
			conn = DriverManager.getConnection(url);
			orderStatusesToExport = cleanIniValue(ini.get("Reindex", "orderStatusesToExport"));
			if (orderStatusesToExport == null){
				orderStatusesToExport = "o|1";
			}
			exportActiveOrders(exportPath, conn);
			exportDueDates(exportPath, conn);

			exportHolds(conn, vufindConn);

		}catch(Exception e){
			System.out.println("Error: " + e.toString());
			e.printStackTrace();
		}

		if (conn != null){
			try{
				//Close the connection
				conn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}

		if (vufindConn != null){
			try{
				//Close the connection
				vufindConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}
		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished Sierra Extract");
	}

	private static void exportHolds(Connection sierraConn, Connection vufindConn) {
		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = vufindConn.setSavepoint();
			vufindConn.setAutoCommit(false);
			vufindConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeQuery();

			PreparedStatement addIlsHoldSummary = vufindConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

			HashMap<String, Long> numHoldsByBib = new HashMap<>();
			HashMap<String, Long> numHoldsByVolume = new HashMap<>();
			//Export bib level holds
			PreparedStatement bibHoldsStmt = sierraConn.prepareStatement("select count(hold.id) as numHolds, record_type_code, record_num from sierra_view.hold left join sierra_view.record_metadata on hold.record_id = record_metadata.id where record_type_code = 'b' and (status = '0' OR status = 't') GROUP BY record_type_code, record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldsRS = bibHoldsStmt.executeQuery();
			while (bibHoldsRS.next()){
				String bibId = bibHoldsRS.getString("record_num");
				bibId = ".b" + bibId + getCheckDigit(bibId);
				Long numHolds = bibHoldsRS.getLong("numHolds");
				numHoldsByBib.put(bibId, numHolds);
			}
			bibHoldsRS.close();

			if (exportItemHolds) {
				//Export item level holds
				PreparedStatement itemHoldsStmt = sierraConn.prepareStatement("select count(hold.id) as numHolds, record_num\n" +
						"from sierra_view.hold \n" +
						"inner join sierra_view.bib_record_item_record_link ON hold.record_id = item_record_id \n" +
						"inner join sierra_view.record_metadata on bib_record_item_record_link.bib_record_id = record_metadata.id \n" +
						"WHERE status = '0' OR status = 't' " +
						"group by record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet itemHoldsRS = itemHoldsStmt.executeQuery();
				while (itemHoldsRS.next()) {
					String bibId = itemHoldsRS.getString("record_num");
					bibId = ".b" + bibId + getCheckDigit(bibId);
					Long numHolds = itemHoldsRS.getLong("numHolds");
					if (numHoldsByBib.containsKey(bibId)) {
						numHoldsByBib.put(bibId, numHolds + numHoldsByBib.get(bibId));
					} else {
						numHoldsByBib.put(bibId, numHolds);
					}
				}
				itemHoldsRS.close();
			}

			//Export volume level holds
			PreparedStatement volumeHoldsStmt = sierraConn.prepareStatement("select count(hold.id) as numHolds, bib_metadata.record_num as bib_num, volume_metadata.record_num as volume_num\n" +
					"from sierra_view.hold \n" +
					"inner join sierra_view.bib_record_volume_record_link ON hold.record_id = volume_record_id \n" +
					"inner join sierra_view.record_metadata as volume_metadata on bib_record_volume_record_link.volume_record_id = volume_metadata.id \n" +
					"inner join sierra_view.record_metadata as bib_metadata on bib_record_volume_record_link.bib_record_id = bib_metadata.id \n" +
					"WHERE status = '0' OR status = 't'\n" +
					"GROUP BY bib_metadata.record_num, volume_metadata.record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet volumeHoldsRS = volumeHoldsStmt.executeQuery();
			while (volumeHoldsRS.next()) {
				String bibId = volumeHoldsRS.getString("bib_num");
				bibId = ".b" + bibId + getCheckDigit(bibId);
				String volumeId = volumeHoldsRS.getString("volume_num");
				volumeId = ".j" + volumeId + getCheckDigit(volumeId);
				Long numHolds = volumeHoldsRS.getLong("numHolds");
				//Do not count these in
				if (numHoldsByBib.containsKey(bibId)) {
					numHoldsByBib.put(bibId, numHolds + numHoldsByBib.get(bibId));
				} else {
					numHoldsByBib.put(bibId, numHolds);
				}
				if (numHoldsByVolume.containsKey(volumeId)) {
					numHoldsByVolume.put(volumeId, numHolds + numHoldsByVolume.get(bibId));
				} else {
					numHoldsByVolume.put(volumeId, numHolds);
				}
			}
			volumeHoldsRS.close();


			for (String bibId : numHoldsByBib.keySet()){
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

			for (String volumeId : numHoldsByVolume.keySet()){
				addIlsHoldSummary.setString(1, volumeId);
				addIlsHoldSummary.setLong(2, numHoldsByVolume.get(volumeId));
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
			logger.error("Unable to export holds from Sierra", e);
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

	private static void getChangedRecordsFromApi(Ini ini, Connection vufindConn, String exportPath) {
		//Get the time the last extract was done
		try{
			logger.info("Starting to load changed records from Sierra using the API");
			Long lastSierraExtractTime = null;
			Long lastSierraExtractTimeVariableId = null;

			Long exportStartTime = new Date().getTime() / 1000;

			HashSet<String> itemsThatNeedToBeProcessed = new HashSet<>();
			File changedItemsFile = new File(exportPath + "/changed_items_to_process.csv");
			if (changedItemsFile.exists()){
				BufferedReader changedItemsReader = new BufferedReader(new FileReader(changedItemsFile));
				String curLine = changedItemsReader.readLine();
				while (curLine != null){
					itemsThatNeedToBeProcessed.add(curLine);
					curLine = changedItemsReader.readLine();
				}
				changedItemsReader.close();
			}

			PreparedStatement loadLastSierraExtractTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_sierra_extract_time'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet lastSierraExtractTimeRS = loadLastSierraExtractTimeStmt.executeQuery();
			if (lastSierraExtractTimeRS.next()){
				lastSierraExtractTime = lastSierraExtractTimeRS.getLong("value");
				lastSierraExtractTimeVariableId = lastSierraExtractTimeRS.getLong("id");
			}

			/*String maxRecordsToUpdateDuringExtractStr = ini.get("Sierra", "maxRecordsToUpdateDuringExtract");
			int maxRecordsToUpdateDuringExtract = 5000;
			if (maxRecordsToUpdateDuringExtractStr != null){
				maxRecordsToUpdateDuringExtract = Integer.parseInt(maxRecordsToUpdateDuringExtractStr);
				logger.info("Extracting a maximum of " + maxRecordsToUpdateDuringExtract + " records");
			}*/

			//Only mark records as changed
			boolean errorUpdatingDatabase = false;
			if (lastSierraExtractTime != null){
				String apiVersion = cleanIniValue(ini.get("Catalog", "api_version"));
				if (apiVersion == null || apiVersion.length() == 0){
					return;
				}
				String apiBaseUrl = ini.get("Catalog", "url") + "/iii/sierra-api/v" + apiVersion;

				//Last Update in UTC
				//Add a small buffer to be
				Date lastExtractDate = new Date((lastSierraExtractTime - 120) * 1000);

				Date now = new Date();
				Date yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);

				if (lastExtractDate.before(yesterday)){
					logger.warn("Last Extract date was more than 24 hours ago.  Just getting the last 24 hours since we should have a full extract.");
					lastExtractDate = yesterday;
				}

				SimpleDateFormat dateFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
				dateFormatter.setTimeZone(TimeZone.getTimeZone("UTC"));
				String dateUpdated = dateFormatter.format(lastExtractDate);
				long updateTime = new Date().getTime() / 1000;
				logger.info("Loading records changed since " + dateUpdated);

				SimpleDateFormat marcDateFormat = new SimpleDateFormat(indexingProfile.dueDateFormat);
				SimpleDateFormat marcCheckInFormat = new SimpleDateFormat(indexingProfile.lastCheckinFormat);

				//Extract the ids of all records that have changed.  That will allow us to mark
				//That the grouped record has changed which will force the work to be indexed
				//In reality, this will only update availability unless we pull the full marc record
				//from the API since we only have updated availability, not location data or metadata
				long firstRecordIdToLoad = 1;
				boolean moreToRead = true;
				PreparedStatement markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;
				HashMap<String, ArrayList<ItemChangeInfo>> changedBibs = new HashMap<>();
				int bufferSize = 1000;
				/*String recordsToExtractBatchSizeStr = ini.get("Sierra", "recordsToExtractBatchSize");
				if (recordsToExtractBatchSizeStr != null){
					bufferSize = Integer.parseInt(recordsToExtractBatchSizeStr);
					logger.info("Loading records in batches of " + bufferSize + " records");
				}*/

				//Get a list of everything that has changed, loading a minimum of data so we can get the ids as quickly as possible
				int recordOffset = 50000;
				while (moreToRead){
					//long lastRecord = firstRecordIdToLoad + recordOffset;
					logger.info("Loading items with changes from " + firstRecordIdToLoad);
					JSONObject changedRecords = null;
					int numTries = 0;
					while ((numTries == 0 || lastCallTimedOut) && numTries < 5){
						numTries++;
						//Try loading again
						if (lastCallTimedOut) {
							logger.info(" - timed out, retrying");
							Thread.sleep(2500);
						}
						//changedRecords = callSierraApiURL(ini, apiBaseUrl, apiBaseUrl + "/items/?updatedDate=[" + dateUpdated + ",]&limit=" + bufferSize + "&fields=id,bibIds,location,status,fixedFields&deleted=false&suppressed=false&id=[" + firstRecordIdToLoad + "," + (lastRecord > 999999999 ? "" : lastRecord) + "]", false);
						changedRecords = callSierraApiURL(ini, apiBaseUrl, apiBaseUrl + "/items/?updatedDate=[" + dateUpdated + ",]&limit=" + bufferSize + "&fields=id,bibIds&deleted=false&suppressed=false&id=[" + firstRecordIdToLoad + ",]", false);
					}
					if (lastCallTimedOut){
						logger.error(" - call " + numTries + " timed out, data will be lost!");
					}
					int numChangedIds = 0;
					if (changedRecords != null && changedRecords.has("entries")){
						//int numUpdates = changedRecords.getInt("total");
						JSONArray changedIds = changedRecords.getJSONArray("entries");
						numChangedIds = changedIds.length();
						logger.info(" - Found " + numChangedIds + " changes");
						int lastId = 0;
						for(int i = 0; i < numChangedIds; i++){
							JSONObject curItem = changedIds.getJSONObject(i);
							String itemId = curItem.getString("id");
							itemsThatNeedToBeProcessed.add(itemId);
							lastId = Integer.parseInt(itemId) + 1;
							logger.debug("   item " + itemId + " changed");
						}
						if (numChangedIds >= bufferSize){
							firstRecordIdToLoad = lastId + 1;
						}else{
							firstRecordIdToLoad += recordOffset;
						}
					}else{
						logger.info(" - Found no changes");
						firstRecordIdToLoad += recordOffset;
					}
					//If we have the same number of records as the buffer that is ok.  Sierra does not return the correct total anymore
					moreToRead = (numChangedIds >= bufferSize); // || firstRecordIdToLoad <= 999999999;
				}

				//Get details for each change.  This is a bit slower so we will just load for up to 5 minutes and save the rest for later if needed
				int numProcessed = 0;
				HashSet<String> itemsThatNeedToBeProcessed2 = (HashSet<String>)itemsThatNeedToBeProcessed.clone();
				for (String itemId : itemsThatNeedToBeProcessed2){
					JSONObject itemData = callSierraApiURL(ini, apiBaseUrl, apiBaseUrl + "/items/?id=" + itemId + "&fields=id,bibIds,location,status,fixedFields,updatedDate&suppressed=false", false);
					if (itemData == null) {
						//This seems to be a normal issue if items get deleted or suppressed.
						//Manual lookups show that they cannot be found in sierra either.
						logger.debug("Could not load item data (result was null) for " + itemId);
						itemsThatNeedToBeProcessed.remove(itemId);
					}else if (itemData.has("entries")){
						JSONObject curItem = itemData.getJSONArray("entries").getJSONObject(0);

						String location;
						if (curItem.has("location")) {
							location = curItem.getJSONObject("location").getString("code");
						}else{
							location = "";
						}
						String status;
						if (curItem.has("status")){
							status = curItem.getJSONObject("status").getString("code");
						}else{
							status = "";
						}

						String dueDateMarc = null;
						if (curItem.getJSONObject("fixedFields").has("65")){
							String dueDateStr = curItem.getJSONObject("fixedFields").getJSONObject("65").getString("value");
							//The due date is in the format 2014-10-16T10:00:00Z, convert to what the marc record shows which is just yymmdd
							Date dueDate = dateFormatter.parse(dueDateStr);
							dueDateMarc = marcDateFormat.format(dueDate);
						}
						String lastCheckInDateMarc = null;
						if (curItem.getJSONObject("fixedFields").has("68")){
							String lastCheckInDateStr = curItem.getJSONObject("fixedFields").getJSONObject("68").getString("value");
							//The due date is in the format 2014-10-16T10:00:00Z, convert to what the marc record shows which is just yymmdd
							Date lastCheckInDate = dateFormatter.parse(lastCheckInDateStr);
							lastCheckInDateMarc = marcCheckInFormat.format(lastCheckInDate);
						}

						ItemChangeInfo changeInfo = new ItemChangeInfo();
						String itemIdFull = ".i" + itemId + getCheckDigit(itemId);
						logger.debug("Loaded changes for item " + itemIdFull);

						changeInfo.setItemId(itemIdFull);
						changeInfo.setLocation(location);
						changeInfo.setStatus(status);

						changeInfo.setDueDate(dueDateMarc);
						changeInfo.setLastCheckinDate(lastCheckInDateMarc);

						JSONArray bibIds = curItem.getJSONArray("bibIds");
						for (int j = 0; j < bibIds.length(); j++){
							String curId = bibIds.getString(j);
							String fullId = ".b" + curId + getCheckDigit(curId);
							ArrayList<ItemChangeInfo> itemChanges;
							if (changedBibs.containsKey(fullId)) {
								itemChanges = changedBibs.get(fullId);
							}else{
								itemChanges = new ArrayList<>();
								changedBibs.put(fullId, itemChanges);
							}
							itemChanges.add(changeInfo);
						}

						itemsThatNeedToBeProcessed.remove(itemId);
					}else{
						logger.warn("Did not get item information (entries) for " + itemId);
					}

					//Check to see if we've used too much time
					numProcessed++;
					if (numProcessed % 250 == 0){
						if ((new Date().getTime() / 1000) - exportStartTime >= 5 * 60){
							break;
						}
					}
				}

				vufindConn.setAutoCommit(false);
				logger.info("A total of " + changedBibs.size() + " bibs were updated");
				int numUpdates = 0;
				for (String curBibId : changedBibs.keySet()){
					//Update the marc record
					updateMarc(curBibId, changedBibs.get(curBibId));
					logger.debug("Updated Bib " + curBibId);
					//Update the database
					try {
						markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
						markGroupedWorkForBibAsChangedStmt.setString(2, curBibId);
						markGroupedWorkForBibAsChangedStmt.executeUpdate();

						numUpdates++;
						if (numUpdates % 50 == 0){
							vufindConn.commit();
						}
					}catch (SQLException e){
						logger.error("Could not mark that " + curBibId + " was changed due to error ", e);
						errorUpdatingDatabase = true;
					}
				}
				//Turn auto commit back on
				vufindConn.commit();
				vufindConn.setAutoCommit(true);

				//TODO: Process deleted records as well?
			}

			//Write any records that still haven't been processed
			BufferedWriter itemsToProcessWriter = new BufferedWriter(new FileWriter(changedItemsFile, false));
			for (String changedItem : itemsThatNeedToBeProcessed){
				itemsToProcessWriter.write(changedItem + "\r\n");
			}
			itemsToProcessWriter.flush();
			itemsToProcessWriter.close();
			//logger.warn(itemsThatNeedToBeProcessed.size() + " items remain to be processed");

			if (!errorUpdatingDatabase) {
				//Update the last extract time
				if (lastSierraExtractTimeVariableId != null) {
					PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
					updateVariableStmt.setLong(1, exportStartTime);
					updateVariableStmt.setLong(2, lastSierraExtractTimeVariableId);
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
				} else {
					PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_sierra_extract_time', ?)");
					insertVariableStmt.setString(1, Long.toString(exportStartTime));
					insertVariableStmt.executeUpdate();
					insertVariableStmt.close();
				}
				PreparedStatement setRemainingRecordsStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('remaining_sierra_records', ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
				setRemainingRecordsStmt.setString(1, Long.toString(itemsThatNeedToBeProcessed.size()));
				setRemainingRecordsStmt.executeUpdate();
				setRemainingRecordsStmt.close();
			}else{
				logger.error("There was an error updating the database, not setting last extract time.");
			}
		} catch (Exception e){
			logger.error("Error loading changed records from Sierra API", e);
			System.exit(1);
		}
		logger.info("Finished loading changed records from Sierra API");
	}

	private static void updateMarc(String curBibId, ArrayList<ItemChangeInfo> itemChangeInfo) {
		//Load the existing marc record from file
		try {
			File marcFile = indexingProfile.getFileForIlsRecord(curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					inputStream.close();

					//Loop through all item fields to see what has changed
					List<VariableField> itemFields = marcRecord.getVariableFields(indexingProfile.itemTag);
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(indexingProfile.itemRecordNumberSubfield) != null) {
							String itemRecordNumber = itemField.getSubfield(indexingProfile.itemRecordNumberSubfield).getData();
							//Update the items
							for (ItemChangeInfo curItem : itemChangeInfo) {
								//Find the correct item
								if (itemRecordNumber.equals(curItem.getItemId())) {
									itemField.getSubfield(indexingProfile.locationSubfield).setData(curItem.getLocation());
									itemField.getSubfield(indexingProfile.itemStatusSubfield).setData(curItem.getStatus());
									if (curItem.getDueDate() == null) {
										if (itemField.getSubfield(indexingProfile.dueDateSubfield) != null) {
											if (indexingProfile.dueDateFormat.contains("-")){
												itemField.getSubfield(indexingProfile.dueDateSubfield).setData("  -  -  ");
											} else {
												itemField.getSubfield(indexingProfile.dueDateSubfield).setData("      ");
											}
										}
									} else {
										if (itemField.getSubfield(indexingProfile.dueDateSubfield) == null) {
											itemField.addSubfield(new SubfieldImpl(indexingProfile.dueDateSubfield, curItem.getDueDate()));
										} else {
											itemField.getSubfield(indexingProfile.dueDateSubfield).setData(curItem.getDueDate());
										}
									}
									if (indexingProfile.lastCheckinDateSubfield != ' ') {
										if (curItem.getLastCheckinDate() == null) {
											if (itemField.getSubfield(indexingProfile.lastCheckinDateSubfield) != null) {
												if (indexingProfile.lastCheckinFormat.contains("-")) {
													itemField.getSubfield(indexingProfile.lastCheckinDateSubfield).setData("  -  -  ");
												} else {
													itemField.getSubfield(indexingProfile.lastCheckinDateSubfield).setData("      ");
												}
											}
										} else {
											if (itemField.getSubfield(indexingProfile.lastCheckinDateSubfield) == null) {
												itemField.addSubfield(new SubfieldImpl(indexingProfile.lastCheckinDateSubfield, curItem.getLastCheckinDate()));
											} else {
												itemField.getSubfield(indexingProfile.lastCheckinDateSubfield).setData(curItem.getLastCheckinDate());
											}
										}
									}
								}
							}
						}
					}

					//Write the new marc record
					MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile, false), true);
					writer.write(marcRecord);
					writer.close();
				} else {
					logger.info("Could not read marc record for " + curBibId + " the bib was empty");
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + " it is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
	}

	private static void exportDueDates(String exportPath, Connection conn) throws SQLException, IOException {
		logger.info("Starting export of due dates");
		String dueDatesSQL = "select record_num, due_gmt from sierra_view.checkout inner join sierra_view.item_view on item_record_id = item_view.id where due_gmt is not null";
		PreparedStatement getDueDatesStmt = conn.prepareStatement(dueDatesSQL, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet dueDatesRS = null;
		boolean loadError = false;
		try{
			dueDatesRS = getDueDatesStmt.executeQuery();
		} catch (SQLException e1){
			logger.error("Error loading active orders", e1);
			loadError = true;
		}
		if (!loadError){
			File dueDateFile = new File(exportPath + "/due_dates.csv");
			CSVWriter dueDateWriter = new CSVWriter(new FileWriter(dueDateFile));
			while (dueDatesRS.next()){
				try {
					String recordNum = dueDatesRS.getString("record_num");
					if (recordNum != null){
						String dueDateRaw = dueDatesRS.getString("due_gmt");
						String itemId = ".i" + recordNum + getCheckDigit(recordNum);
						Date dueDate = dueDatesRS.getDate("due_gmt");
						dueDateWriter.writeNext(new String[]{itemId, Long.toString(dueDate.getTime()), dueDateRaw});
					}else{
						logger.warn("No record number found while exporting due dates");
					}
				}catch (Exception e){
					logger.error("Error writing due dates", e);
				}
			}
			dueDateWriter.close();
			dueDatesRS.close();
		}
		logger.info("Finished exporting due dates");
	}

	private static void exportActiveOrders(String exportPath, Connection conn) throws SQLException, IOException {
		logger.info("Starting export of active orders");
		String[] orderStatusesToExportVals = orderStatusesToExport.split("\\|");
		String orderStatusCodesSQL = "";
		for (String orderStatusesToExportVal : orderStatusesToExportVals){
			if (orderStatusCodesSQL.length() > 0){
				orderStatusCodesSQL += " or ";
			}
			orderStatusCodesSQL += " order_status_code = '" + orderStatusesToExportVal + "'";
		}
		String activeOrderSQL = "select bib_view.record_num as bib_record_num, order_view.record_num as order_record_num, accounting_unit_code_num, order_status_code, copies, location_code, catalog_date_gmt, received_date_gmt " +
				"from sierra_view.order_view " +
				"inner join sierra_view.bib_record_order_record_link on bib_record_order_record_link.order_record_id = order_view.record_id " +
				"inner join sierra_view.bib_view on sierra_view.bib_view.id = bib_record_order_record_link.bib_record_id " +
				"inner join sierra_view.order_record_cmf on order_record_cmf.order_record_id = order_view.id " +
				"where (" + orderStatusCodesSQL + ") and order_view.is_suppressed = 'f' and location_code != 'multi' and ocode4 != 'n'";
		if (suppressOrderRecordsThatAreReceivedAndCatalogged){
			activeOrderSQL += " and (catalog_date_gmt IS NULL or received_date_gmt IS NULL) ";
		}else if (suppressOrderRecordsThatAreCatalogged){
			activeOrderSQL += " and (catalog_date_gmt IS NULL) ";
		}
		PreparedStatement getActiveOrdersStmt = conn.prepareStatement(activeOrderSQL, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet activeOrdersRS = null;
		boolean loadError = false;
		try{
			activeOrdersRS = getActiveOrdersStmt.executeQuery();
		} catch (SQLException e1){
			logger.error("Error loading active orders", e1);
			loadError = true;
		}
		if (!loadError){
			File orderRecordFile = new File(exportPath + "/active_orders.csv");
			CSVWriter orderRecordWriter = new CSVWriter(new FileWriter(orderRecordFile));
			orderRecordWriter.writeAll(activeOrdersRS, true);
			orderRecordWriter.close();
			activeOrdersRS.close();
		}
		logger.info("Finished exporting active orders");
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
			for (Section curSection : siteSpecificIni.values()){
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
				for (Section curSection : siteSpecificPwdIni.values()){
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

	private static String sierraAPIToken;
	private static String sierraAPITokenType;
	private static long sierraAPIExpiration;
	private static boolean connectToSierraAPI(Ini configIni, String baseUrl){
		//Check to see if we already have a valid token
		if (sierraAPIToken != null){
			if (sierraAPIExpiration - new Date().getTime() > 0){
				//logger.debug("token is still valid");
				return true;
			}else{
				logger.debug("Token has expired");
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn;
		try {
			URL emptyIndexURL = new URL(baseUrl + "/token");
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {

					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			conn.setReadTimeout(30000);
			conn.setConnectTimeout(30000);
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
			String clientKey = cleanIniValue(configIni.get("Catalog", "clientKey"));
			String clientSecret = cleanIniValue(configIni.get("Catalog", "clientSecret"));
			String encoded = Base64.encodeBase64String((clientKey + ":" + clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic "+encoded);
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();

			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				sierraAPIToken = parser.getString("access_token");
				sierraAPITokenType = parser.getString("token_type");
				//logger.debug("Token expires in " + parser.getLong("expires_in") + " seconds");
				sierraAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
				//logger.debug("Sierra token is " + sierraAPIToken);
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to sierra authentication service" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response\r\n" + response);

				rd.close();
				return false;
			}

		} catch (Exception e) {
			logger.error("Error connecting to sierra API", e );
			return false;
		}
		return true;
	}

	private static boolean lastCallTimedOut = false;

	private static JSONObject callSierraApiURL(Ini configIni, String baseUrl, String sierraUrl, boolean logErrors) {
		lastCallTimedOut = false;
		if (connectToSierraAPI(configIni, baseUrl)){
			//Connect to the API to get our token
			HttpURLConnection conn;
			try {
				URL emptyIndexURL = new URL(sierraUrl);
				conn = (HttpURLConnection) emptyIndexURL.openConnection();
				if (conn instanceof HttpsURLConnection){
					HttpsURLConnection sslConn = (HttpsURLConnection)conn;
					sslConn.setHostnameVerifier(new HostnameVerifier() {

						@Override
						public boolean verify(String hostname, SSLSession session) {
							//Do not verify host names
							return true;
						}
					});
				}
				conn.setRequestMethod("GET");
				conn.setRequestProperty("Authorization", sierraAPITokenType + " " + sierraAPIToken);
				conn.setReadTimeout(20000);
				conn.setConnectTimeout(5000);

				StringBuilder response = new StringBuilder();
				if (conn.getResponseCode() == 200) {
					// Get the response
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					//logger.debug("  Finished reading response");
					rd.close();
					return new JSONObject(response.toString());
				} else {
					if (logErrors) {
						logger.error("Received error " + conn.getResponseCode() + " calling sierra API " + sierraUrl);
						// Get any errors
						BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
						String line;
						while ((line = rd.readLine()) != null) {
							response.append(line);
						}
						logger.error("  Finished reading response");
						logger.error(response.toString());

						rd.close();
					}
				}

			} catch (java.net.SocketTimeoutException e) {
				logger.debug("Socket timeout talking to to sierra API " + e.toString() );
				lastCallTimedOut = true;
			} catch (java.net.ConnectException e) {
				logger.debug("Timeout connecting to sierra API " + e.toString() );
				lastCallTimedOut = true;
			} catch (Exception e) {
				logger.debug("Error loading data from sierra API ", e );
			}
		}
		return null;
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	private static String getCheckDigit(String basedId) {
		int sumOfDigits = 0;
		for (int i = 0; i < basedId.length(); i++){
			int multiplier = ((basedId.length() +1 ) - i);
			sumOfDigits += multiplier * Integer.parseInt(basedId.substring(i, i+1));
		}
		int modValue = sumOfDigits % 11;
		if (modValue == 10){
			return "x";
		}else{
			return Integer.toString(modValue);
		}
	}

}