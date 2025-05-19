package com.turning_leaf_technogies.axis360;

import org.aspen_discovery.grouping.RecordGroupingProcessor;
import org.aspen_discovery.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import org.aspen_discovery.reindexer.GroupedWorkIndexer;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.zip.CRC32;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;
import java.io.StringReader;
import javax.xml.parsers.ParserConfigurationException;
import org.xml.sax.SAXException;
import java.io.IOException;

public class Axis360Extractor {
	private final String serverName;
	private final Axis360Setting setting;
	private final Axis360ExtractLogEntry logEntry;
	private final Logger logger;
	private final Connection aspenConn;
	private final Ini configIni;

	private final Long startTimeForLogging;
	private final CRC32 checksumCalculator = new CRC32();

	//Record grouper
	private GroupedWorkIndexer groupedWorkIndexer;
	private RecordGroupingProcessor recordGroupingProcessorSingleton = null;

	//SQL Statements
	private PreparedStatement updateAxis360ItemStmt;
	private PreparedStatement deleteAxis360ItemStmt;
	private PreparedStatement deleteAxis360AvailabilityStmt;
	private PreparedStatement getAllExistingAxis360ItemsStmt;
	private PreparedStatement getAspenIdByAxis360IdStmt;
	private PreparedStatement updateAxis360AvailabilityStmt;
	private PreparedStatement getExistingAxis360AvailabilityStmt;
	private PreparedStatement getExistingSettingsForAxis360TitleStmt;
	private PreparedStatement getRecordsToReloadStmt;
	private PreparedStatement markRecordToReloadAsProcessedStmt;
	private PreparedStatement getItemDetailsForRecordStmt;

	private String accessToken;
	private long accessTokenSettingId;
	private long accessTokenExpiration;

	Axis360Extractor(String serverName, Connection aspenConn, Axis360Setting setting, Ini configIni, Axis360ExtractLogEntry logEntry, Logger logger) {
		this.serverName = serverName;
		this.aspenConn = aspenConn;
		this.setting = setting;
		this.logEntry = logEntry;
		this.logger = logger;
		this.configIni = configIni;

		Date startTime = new Date();
		startTimeForLogging = startTime.getTime() / 1000;
	}

	public int extractAxis360Data() {
		try {
			getAllExistingAxis360ItemsStmt = aspenConn.prepareStatement("SELECT * from axis360_title", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			updateAxis360ItemStmt = aspenConn.prepareStatement(
					"INSERT INTO axis360_title " +
							"(axis360Id, isbn, title, subtitle, primaryAuthor, formatType, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
							"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
							"ON DUPLICATE KEY UPDATE isbn = VALUES(isbn), title = VALUES(title), subtitle = VALUES(subtitle), primaryAuthor = VALUES(primaryAuthor), formatType = VALUES(formatType), " +
							"rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0", PreparedStatement.RETURN_GENERATED_KEYS);
			getAspenIdByAxis360IdStmt = aspenConn.prepareStatement("SELECT * from axis360_title where axis360Id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			deleteAxis360AvailabilityStmt = aspenConn.prepareStatement("DELETE FROM axis360_title_availability where titleId = ? and settingId = ?");
			deleteAxis360ItemStmt = aspenConn.prepareStatement("UPDATE axis360_title SET deleted = 1 where id = ?");
			getExistingSettingsForAxis360TitleStmt = aspenConn.prepareStatement("SELECT count(*) as numSettings from axis360_title_availability WHERE titleId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingAxis360AvailabilityStmt = aspenConn.prepareStatement("SELECT id, rawChecksum from axis360_title_availability WHERE titleId = ? and settingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			updateAxis360AvailabilityStmt = aspenConn.prepareStatement(
					"INSERT INTO axis360_title_availability " +
							"(titleId, settingId, libraryPrefix, available, ownedQty, totalHolds, rawChecksum, rawResponse, lastChange) " +
							"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) " +
							"ON DUPLICATE KEY UPDATE available = VALUES(available), ownedQty = VALUES(ownedQty), totalHolds = VALUES(totalHolds), " +
							"rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange)");
			getRecordsToReloadStmt = aspenConn.prepareStatement("SELECT * from record_identifiers_to_reload WHERE processed = 0 and type=?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			markRecordToReloadAsProcessedStmt = aspenConn.prepareStatement("UPDATE record_identifiers_to_reload SET processed = 1 where id = ?");
			getItemDetailsForRecordStmt = aspenConn.prepareStatement("SELECT title, subtitle, primaryAuthor, formatType, rawResponse from axis360_title where axis360Id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		}catch (SQLException sqe){
			logEntry.incErrors("Could not create statements for Boundless extract", sqe);
		}

		int numChanges = 0;
		//Get a list of all existing records in the database
		HashMap<String, Axis360Title> existingRecords = loadExistingTitles();

		//Do the actual work here
		numChanges += extractAxis360Data(existingRecords);

		//Mark any records that no longer exist in search results as deleted
		if (setting.doFullReload()) {
			numChanges += deleteItems(setting, existingRecords);
		}

		processRecordsToReload(logEntry);

		if (recordGroupingProcessorSingleton != null) {
			recordGroupingProcessorSingleton.close();
			recordGroupingProcessorSingleton = null;
		}

		if (groupedWorkIndexer != null) {
			groupedWorkIndexer.finishIndexingFromExtract(logEntry);
			groupedWorkIndexer.close();
			groupedWorkIndexer = null;
		}

		return numChanges;
	}

	private HashMap<String, Axis360Title> loadExistingTitles() {
		HashMap<String, Axis360Title> existingRecords = new HashMap<>();
		try {
			ResultSet allRecordsRS = getAllExistingAxis360ItemsStmt.executeQuery();
			while (allRecordsRS.next()) {
				String axis360Id = allRecordsRS.getString("axis360Id");
				Axis360Title newTitle = new Axis360Title(
						allRecordsRS.getLong("id"),
						axis360Id,
						allRecordsRS.getLong("rawChecksum"),
						allRecordsRS.getBoolean("deleted")
				);

				existingRecords.put(axis360Id, newTitle);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing titles", e);
			logEntry.saveResults();
			System.exit(-1);
		}
		return existingRecords;
	}

	private int extractAxis360Data(HashMap<String, Axis360Title> existingRecords) {
		int numChanges = 0;
		try {
			numChanges = extractBooks(setting, existingRecords, numChanges);

			if (setting.doFullReload()) {
				//Un-mark that a full update needs to be done
				PreparedStatement updateSettingsStmt = aspenConn.prepareStatement("UPDATE axis360_settings set runFullUpdate = 0 where id = ?");
				updateSettingsStmt.setLong(1, setting.getId());
				updateSettingsStmt.executeUpdate();
			}

			if (!logEntry.hasErrors()) {
				//Update the last time we ran the update in settings
				PreparedStatement updateExtractTime;
				String columnToUpdate = "lastUpdateOfChangedRecords";
				if (setting.doFullReload()) {
					columnToUpdate = "lastUpdateOfAllRecords";
				}
				updateExtractTime = aspenConn.prepareStatement("UPDATE axis360_settings set " + columnToUpdate + " = ? WHERE id = ?");
				updateExtractTime.setLong(1, startTimeForLogging);
				updateExtractTime.setLong(2, setting.getId());
				updateExtractTime.executeUpdate();
			} else {
				logEntry.addNote("Not setting last extract time since there were problems extracting products from the API");
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error extracting Axis360 information ", e);
		}

		return numChanges;
	}

	private int extractBooks(Axis360Setting setting, HashMap<String, Axis360Title> existingRecords, int numChanges) {
		HashMap<String, String> headers = new HashMap<>();
		String accessToken = getAxis360AccessToken(setting);
		if (accessToken == null){
			logEntry.incErrors("Did not get access token");
			return 0;
		}
		headers.put("Authorization", getAxis360AccessToken(setting));
		headers.put("Library", setting.getLibraryPrefix());
		headers.put("Content-Type", "application/json");
		headers.put("Accept", "application/json");
		//Get a list of titles to process
		String baseItemDetailsUrl = setting.getBaseUrl() + "/Services/VendorAPI/titleLicense/v3?modifiedSince=";
		String modificationDate;
		if (!setting.doFullReload() && (setting.getLastUpdateOfChangedRecords() != 0)){
			modificationDate = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'").format(new Date(setting.getLastUpdateOfChangedRecords() * 1000));
		}else{
			modificationDate = "2000-01-01T12:00:00Z";
		}
		logEntry.addNote("Loading records modified since " + modificationDate);
		baseItemDetailsUrl += modificationDate;

		int currentPage = 1;
		int totalPages = 1;

		while (currentPage <= totalPages) {
			String itemDetailsUrl = baseItemDetailsUrl + "&page=" + currentPage;
			int numTries = 1;
			boolean successfullyLoadedPage = false;
			while (numTries <= 3 && !successfullyLoadedPage) {
				WebServiceResponse response = NetworkUtils.getURL(itemDetailsUrl, logger, headers, 240000, false);
				if (!response.isSuccess()) {
					if (numTries == 3) {
						logEntry.incErrors("Error calling " + itemDetailsUrl + ": " + response.getResponseCode() + " " + response.getMessage());
					}
				} else {
					try {
						JSONObject responseJSON = response.getJSONResponse();
						JSONObject itemDetailsResponseStatus = responseJSON.getJSONObject("status");
						if (itemDetailsResponseStatus.getString("Code").equals("0000")) {
							successfullyLoadedPage = true;
							JSONObject pagination = responseJSON.getJSONObject("pagination");
							totalPages = pagination.getInt("totalPage");
							if (currentPage == 1) {
								logEntry.addNote("There are " + totalPages + " pages of results");
							}
							if (responseJSON.has("titles") && !responseJSON.isNull("titles")) {
								JSONArray titleDetails = responseJSON.getJSONArray("titles");
								numChanges += processAxis360Titles(setting, existingRecords, titleDetails);
							}
						} else {
							if (numTries == 3) {
								logEntry.incErrors("Did not get a good status while calling getItemDetails URL: " + itemDetailsUrl + " Status Code: " + itemDetailsResponseStatus.getString("Code") + " Status Message: " + itemDetailsResponseStatus.getString("Message"));
							}
						}

					} catch (JSONException e) {
						if (numTries == 3) {
							logger.error("Error parsing response for " + itemDetailsUrl, e);
							logEntry.addNote("Error parsing response for " + itemDetailsUrl + ": " + e);
						}
					}
				}
				if (!successfullyLoadedPage) {
					if (numTries != 3) {
						try {
							//Wait one minute before try again
							Thread.sleep(60 * 1000);
							logEntry.addNote("Did not get a good response calling " + itemDetailsUrl + " trying again");
						} catch (InterruptedException e) {
							throw new RuntimeException(e);
						}
					}
					numTries++;
				}
			}
			currentPage++;
			//Don't try to load subsequent pages if we got an error
			if (!successfullyLoadedPage) {
				break;
			}
		}

		if (groupedWorkIndexer != null) {
			groupedWorkIndexer.commitChanges();
		}
		return numChanges;
	}

	private int processAxis360Titles(Axis360Setting setting, HashMap<String, Axis360Title> existingRecords, JSONArray titleDetails) {
		int numChanges = 0;
		logEntry.incNumProducts(titleDetails.length());
		for (int i = 0; i < titleDetails.length(); i++) {
			try {
				JSONObject itemDetails = titleDetails.getJSONObject(i);
				checksumCalculator.reset();
				String itemDetailsAsString = itemDetails.toString();
				checksumCalculator.update(itemDetailsAsString.getBytes());
				long itemChecksum = checksumCalculator.getValue();

				String axis360Id = itemDetails.getString("TitleID");
				logger.debug("processing " + axis360Id);

				boolean active = itemDetails.getBoolean("active");
				Axis360Title existingTitle = existingRecords.get(axis360Id);
				if (!active){
					//The record is no longer active, delete it if it still exists
					if (existingTitle != null) {
						HashMap<String, Axis360Title> titleToDelete = new HashMap<>();
						existingTitle.setProcessed(false);
						titleToDelete.put(axis360Id, existingTitle);
						deleteItems(setting, titleToDelete);
					}
				}else {
					//Check to see if the title metadata has changed
					boolean metadataChanged = false;
					if (existingTitle != null) {
						logger.debug("Record already exists");
						if (existingTitle.getChecksum() != itemChecksum || existingTitle.isDeleted()) {
							logger.debug("Updating item details");
							metadataChanged = true;
						}
						existingTitle.setProcessed(true);
					} else {
						logger.debug("Adding record " + axis360Id);
						metadataChanged = true;
					}

					boolean availabilityChanged = false;
					String itemAvailabilityAsString;
					long availabilityChecksum;
					//Check if availability changed
					AvailabilityResponse itemAvailability = getAvailabilityForTitle(axis360Id, setting);
					long aspenId = existingTitle != null ? existingTitle.getId() : -1;
					if (itemAvailability.callSucceeded) {
						if (itemAvailability.titleInformation != null) {
							checksumCalculator.reset();
							itemAvailabilityAsString = itemAvailability.titleInformation.toString();
							checksumCalculator.update(itemAvailabilityAsString.getBytes());
							availabilityChecksum = checksumCalculator.getValue();
							if (aspenId != -1) {
								getExistingAxis360AvailabilityStmt.setLong(1, aspenId);
								getExistingAxis360AvailabilityStmt.setLong(2, setting.getId());
								ResultSet getExistingAvailabilityRS = getExistingAxis360AvailabilityStmt.executeQuery();
								if (getExistingAvailabilityRS.next()) {
									long existingChecksum = getExistingAvailabilityRS.getLong("rawChecksum");
									logger.debug("Availability already exists");
									if (existingChecksum != availabilityChecksum) {
										logger.debug("Updating availability details");
										availabilityChanged = true;
									}
								} else {
									logger.debug("Adding availability for " + axis360Id);
									availabilityChanged = true;
								}
							} else {
								//This happens when we find a new title.  The id is inserted below , the following should never trigger, but it's a safety check
								if (!metadataChanged) {
									metadataChanged = true;
									logEntry.incErrors("Did not find aspen id for axis360 id " + axis360Id + " but we thought it should exist");
								}
								availabilityChanged = true;
							}
						} else {
							//noinspection IfStatementWithIdenticalBranches
							if (itemAvailability.titleIsUnavailable) {
								//The title is no longer available
								if (existingTitle != null) {
									HashMap<String, Axis360Title> titleToDelete = new HashMap<>();
									existingTitle.setProcessed(false);
									titleToDelete.put(axis360Id, existingTitle);
									deleteItems(setting, titleToDelete);
								}
								continue;
							} else {
								//TODO: This is an undefined state, need to decide what to do, we didn't get a good response, but it also isn't unavailable. Reprocess or just skip?
								continue;
							}
						}
					} else {
						//TODO: Call failed, we can try again or we can skip
						continue;
					}

					String primaryAuthor = getFieldValue(itemDetails, "author");

					if (metadataChanged || setting.doFullReload()) {
						logEntry.incMetadataChanges();
						//Update the database
						updateAxis360ItemStmt.setString(1, axis360Id);
						updateAxis360ItemStmt.setString(2, getFieldValue(itemDetails,"isbn"));
						updateAxis360ItemStmt.setString(3, getFieldValue(itemDetails,"title"));
						updateAxis360ItemStmt.setString(4, getFieldValue(itemDetails,"subtitle"));
						updateAxis360ItemStmt.setString(5, primaryAuthor);

						updateAxis360ItemStmt.setString(6, itemDetails.getString("formatType"));
						updateAxis360ItemStmt.setLong(7, itemChecksum);
						updateAxis360ItemStmt.setString(8, itemDetailsAsString);
						updateAxis360ItemStmt.setLong(9, startTimeForLogging);
						updateAxis360ItemStmt.setLong(10, startTimeForLogging);

						int result = updateAxis360ItemStmt.executeUpdate();
						if (result == 1) {
							//A result of 1 indicates a new row was inserted
							logEntry.incAdded();
							ResultSet generatedKeys = updateAxis360ItemStmt.getGeneratedKeys();
							if (generatedKeys.next()) {
								aspenId = generatedKeys.getLong(1);
								existingTitle = new Axis360Title(aspenId, axis360Id, itemChecksum, false);
								existingTitle.setProcessed(true);
							}else{
								getAspenIdByAxis360IdStmt.setString(1, axis360Id);
								ResultSet getAspenIdByAxis360IdRS = getAspenIdByAxis360IdStmt.executeQuery();
								if (getAspenIdByAxis360IdRS.next()){
									aspenId = getAspenIdByAxis360IdRS.getLong("id");
								}else {
									//This happens when a title exists within a different collection, look it up based on ID
									logEntry.incErrors("Did not get a generated key when inserting title " + axis360Id);
								}
								getAspenIdByAxis360IdRS.close();
							}
						}else{
							if (aspenId == -1){
								getAspenIdByAxis360IdStmt.setString(1, axis360Id);
								ResultSet getAspenIdByAxis360IdRS = getAspenIdByAxis360IdStmt.executeQuery();
								if (getAspenIdByAxis360IdRS.next()){
									aspenId = getAspenIdByAxis360IdRS.getLong("id");
								}else {
									//This happens when a title exists within a different collection, look it up based on ID
									logEntry.addNote("The existing title had metadata updated, but we did not find an existing record for it");
								}
								getAspenIdByAxis360IdRS.close();
							}
						}
					}

					if (availabilityChanged || setting.doFullReload()) {
						JSONObject availabilityInfo = itemAvailability.titleInformation.getJSONObject("Availability");
						logEntry.incAvailabilityChanges();
						if (aspenId == -1){
							logEntry.incErrors("Did not get an id for the title " + axis360Id + " prior to updating availability, this implies a new title that failed to insert");
						}else {
							updateAxis360AvailabilityStmt.setLong(1, aspenId);
							updateAxis360AvailabilityStmt.setLong(2, setting.getId());
							updateAxis360AvailabilityStmt.setString(3, setting.getLibraryPrefix());
							updateAxis360AvailabilityStmt.setBoolean(4, availabilityInfo.getBoolean("Available"));
							updateAxis360AvailabilityStmt.setLong(5, availabilityInfo.getLong("TotalCopies"));
							updateAxis360AvailabilityStmt.setLong(6, availabilityInfo.getLong("HoldQueueSize"));
							updateAxis360AvailabilityStmt.setLong(7, availabilityChecksum);
							updateAxis360AvailabilityStmt.setString(8, itemAvailabilityAsString);
							updateAxis360AvailabilityStmt.setLong(9, startTimeForLogging);
							int numAvailabilityUpdates = updateAxis360AvailabilityStmt.executeUpdate();
							logger.info("Availability changes made " + numAvailabilityUpdates);
						}
					}

					String groupedWorkId = null;
					if (metadataChanged || setting.doFullReload()) {
						groupedWorkId = getRecordGroupingProcessor().groupAxis360Record(itemDetails, axis360Id, primaryAuthor);
					}
					if (metadataChanged || availabilityChanged || setting.doFullReload()) {
						logEntry.incUpdated();
						if (groupedWorkId == null) {
							groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("axis360", axis360Id);
						}
						indexAxis360Record(groupedWorkId);
						numChanges++;

						if (numChanges % 500 == 0) {
							getGroupedWorkIndexer().commitChanges();
						}
					}
				}
			} catch (Exception e) {
				logEntry.incErrors("Error processing titles", e);
			}
		}
		logEntry.saveResults();
		return numChanges;
	}

	private int deleteItems(Axis360Setting setting, HashMap<String, Axis360Title> existingRecords) {
		int numDeleted = 0;
		try {
			for (Axis360Title axis360Title : existingRecords.values()) {
				if (!axis360Title.isDeleted() && !axis360Title.isProcessed()) {
					//Remove Axis360 availability
					deleteAxis360AvailabilityStmt.setLong(1, axis360Title.getId());
					deleteAxis360AvailabilityStmt.setLong(2, setting.getId());
					deleteAxis360AvailabilityStmt.executeUpdate();

					getExistingSettingsForAxis360TitleStmt.setLong(1, axis360Title.getId());
					ResultSet getExistingSettingsForAxis360TitleRS = getExistingSettingsForAxis360TitleStmt.executeQuery();
					int numSettings = 0;
					if (getExistingSettingsForAxis360TitleRS.next()){
						numSettings = getExistingSettingsForAxis360TitleRS.getInt("numSettings");
					}
					if (numSettings == 0) {
						deleteAxis360ItemStmt.setLong(1, axis360Title.getId());
						deleteAxis360ItemStmt.executeUpdate();
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork("axis360", axis360Title.getAxis360Id());
						if (result.reindexWork) {
							getGroupedWorkIndexer().processGroupedWork(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
						}
					}else{
						//Reindex the work
						String groupedWorkId = getRecordGroupingProcessor().getPermanentIdForRecord("axis360", axis360Title.getAxis360Id());
						indexAxis360Record(groupedWorkId);
					}
					numDeleted++;
					logEntry.incDeleted();
				}
			}
			if (numDeleted > 0) {
				logEntry.saveResults();
				logger.warn("Deleted " + numDeleted + " old titles");
			}
		} catch (SQLException e) {
			logger.error("Error deleting items", e);
			logEntry.addNote("Error deleting items " + e);
		}
		return numDeleted;
	}

	private String getFieldValue(JSONObject itemDetails, String fieldName) {
		JSONArray fields = itemDetails.getJSONArray("fields");
		for (int i = 0; i < fields.length(); i++){
			JSONObject field = fields.getJSONObject(i);
			if (field.getString("name").equals(fieldName)){
				JSONArray fieldValues = field.getJSONArray("values");
				if (fieldValues.isEmpty()) {
					return "";
				}else if (fieldValues.length() == 1) {
					return fieldValues.getString(0).trim();
				}else{
					ArrayList<String> values = new ArrayList<>();
					for (int j = 0; j < fieldValues.length(); j++){
						values.add(fieldValues.getString(j));
					}
					return values.get(0).trim();
				}
			}
		}
		return "";
	}

	private AvailabilityResponse getAvailabilityForTitle(String axis360Id, Axis360Setting setting) {
		AvailabilityResponse availabilityResponse = new AvailabilityResponse();
		HashMap<String, String> headers = new HashMap<>();
		String accessToken = getAxis360AccessToken(setting);
		if (accessToken == null){
			logEntry.incErrors("Did not get access token when checking availability");
			availabilityResponse.callSucceeded = false;
			return availabilityResponse;
		}
		headers.put("Authorization", getAxis360AccessToken(setting));
		headers.put("Library", setting.getLibraryPrefix());
		headers.put("Content-Type", "application/xml");
		headers.put("Accept", "application/xml");

		String availabilityUrl = setting.getBaseUrl() + "/Services/VendorAPI/availability/v2?titleIds=" + axis360Id;

		int numTries = 1;
		while (!availabilityResponse.callSucceeded && numTries <= 3) {
			if (numTries > 1) {
				try {
					//Sleep a little bit to allow the server to calm down.
					Thread.sleep(10000);
				} catch (InterruptedException e) {
					//Not a big deal if this gets interrupted
				}
			}
			availabilityResponse.response = NetworkUtils.getURL(availabilityUrl, logger, headers, 120000, false);
			if (!availabilityResponse.response.isSuccess()) {
				logEntry.addNote("Error calling " + availabilityUrl + ": " + availabilityResponse.response.getResponseCode() + " " + availabilityResponse.response.getMessage() + " trying again.");
				availabilityResponse.callSucceeded = false;
			} else {
				try {
					DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
					DocumentBuilder builder = factory.newDocumentBuilder();
					Document doc = builder
							.parse(new InputSource(new StringReader(availabilityResponse.response.getMessage())));
					NodeList statusCode = doc.getElementsByTagName("code");
					String code = statusCode.item(0).getTextContent();

					if (code.equals("0000")) {
						availabilityResponse.callSucceeded = true;

						NodeList titleList = doc.getElementsByTagName("title");
						if (titleList.getLength() > 0) {
							Element titleElement = (Element) titleList.item(0);
							NodeList titleAvail = titleElement.getElementsByTagName("availability");
							Element availabilityElement = (Element) titleAvail.item(0);

							int availableQty = Integer.parseInt(getElementTextContent(availabilityElement, "availableCopies"));
							int totalHolds = Integer.parseInt(getElementTextContent(availabilityElement, "holdsQueueSize"));

							JSONObject availability = new JSONObject();
							availability.put("Available", availableQty > 0);
							availability.put("TotalCopies", availableQty);
							availability.put("HoldQueueSize", totalHolds);

							JSONObject titleInfo = new JSONObject();
							titleInfo.put("Availability", availability);
							titleInfo.put("TitleId", getElementTextContent(titleElement, "titleId"));

							availabilityResponse.titleInformation = titleInfo;
						}
					} else {
						logEntry.incErrors("Did not get a good status while calling titleInfo " + code);
					}
				} catch (ParserConfigurationException | SAXException | IOException e) {
					logEntry.incErrors("Error parsing XML response for title " + axis360Id + ": " + e);
					availabilityResponse.callSucceeded = false;
				} catch (JSONException e) {
					logEntry.incErrors("Error parsing availability response for title " + axis360Id + e);
				}
			}
			numTries++;
		}
		if (numTries == 4 && !availabilityResponse.callSucceeded && !availabilityResponse.titleIsUnavailable) {
			logEntry.incErrors("Did not get a successful API response after 3 tries for " + availabilityUrl);
		}
		return availabilityResponse;
	}

	private String getElementTextContent(Element parent, String tagName) {
		NodeList nodeList = parent.getElementsByTagName(tagName);
		if (nodeList.getLength() > 0) {
			return nodeList.item(0).getTextContent();
		}
		return "0";
	}

	private void indexAxis360Record(String permanentId) {
		getGroupedWorkIndexer().processGroupedWork(permanentId);
	}

	private GroupedWorkIndexer getGroupedWorkIndexer() {
		if (groupedWorkIndexer == null) {
			groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, logEntry, logger);
		}
		return groupedWorkIndexer;
	}

	private RecordGroupingProcessor getRecordGroupingProcessor() {
		if (recordGroupingProcessorSingleton == null) {
			recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessorSingleton;
	}

	private String getAxis360AccessToken(Axis360Setting setting) {
		long curTime = new Date().getTime();
		if (accessToken == null || accessTokenExpiration <= curTime || accessTokenSettingId != setting.getId()){
			accessTokenSettingId = setting.getId();
			String authentication = setting.getVendorUsername() + ":" + setting.getVendorPassword() + ":" + setting.getLibraryPrefix();

			//noinspection SpellCheckingInspection
			String authorizationUrl = setting.getBaseUrl() + "/Services/VendorAPI/accesstoken";
			WebServiceResponse response = NetworkUtils.postToURL(authorizationUrl, "", "application/json", null, logger, authentication, 10000, 300000, StandardCharsets.UTF_16LE);
			if (!response.isSuccess()) {
				logEntry.incErrors("Error calling " + authorizationUrl + ": " + response.getResponseCode() + " " + response.getMessage());
			}else{
				JSONObject accessTokenResponse = response.getJSONResponse();
				accessToken = accessTokenResponse.getString("access_token");
				accessTokenExpiration = new Date().getTime() + (accessTokenResponse.getLong("expires_in")  - 5) * 1000;
			}
		}
		return accessToken;
	}

	private void processRecordsToReload(Axis360ExtractLogEntry logEntry) {
		try {
			//First process books and eBooks
			getRecordsToReloadStmt.setString(1, "axis360");
			ResultSet getRecordsToReloadRS = getRecordsToReloadStmt.executeQuery();
			int numRecordsToReloadProcessed = 0;
			while (getRecordsToReloadRS.next()) {
				long recordToReloadId = getRecordsToReloadRS.getLong("id");
				String axis360Id = getRecordsToReloadRS.getString("identifier");
				//Regroup the record
				getItemDetailsForRecordStmt.setString(1, axis360Id);
				ResultSet getItemDetailsForRecordRS = getItemDetailsForRecordStmt.executeQuery();
				if (getItemDetailsForRecordRS.next()){
					String rawResponse = getItemDetailsForRecordRS.getString("rawResponse");
					try {
						JSONObject itemDetails = new JSONObject(rawResponse);
						String primaryAuthor = getItemDetailsForRecordRS.getString("primaryAuthor");
						String groupedWorkId = getRecordGroupingProcessor().groupAxis360Record(itemDetails, axis360Id, primaryAuthor);
						//Reindex the record
						getGroupedWorkIndexer().processGroupedWork(groupedWorkId);

						markRecordToReloadAsProcessedStmt.setLong(1, recordToReloadId);
						markRecordToReloadAsProcessedStmt.executeUpdate();
						numRecordsToReloadProcessed++;
					}catch (JSONException e){
						logEntry.incErrors("Could not parse item details for record to reload " + axis360Id);
					}
				}else{
					logEntry.addNote("Could not get details for Axis360 record to reload " + axis360Id + " it has been deleted.");
				}
				getItemDetailsForRecordRS.close();

			}
			if (numRecordsToReloadProcessed > 0) {
				logEntry.addNote("Regrouped " + numRecordsToReloadProcessed + " eBooks and audiobooks marked for reprocessing");
			}
			getRecordsToReloadRS.close();
		}catch (SQLException e){
			logEntry.incErrors("Error processing records to reload", e);
		}
	}
}
