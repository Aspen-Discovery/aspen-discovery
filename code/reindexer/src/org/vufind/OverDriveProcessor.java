package org.vufind;

import org.apache.log4j.Logger;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 12/9/13
 * Time: 9:14 AM
 */
public class OverDriveProcessor {
	private GroupedWorkIndexer indexer;
	private Logger logger;
	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getNumCopiesStmt;
	private PreparedStatement getProductMetadataStmt;
	private PreparedStatement getProductAvailabilityStmt;
	private PreparedStatement getProductFormatsStmt;
	private PreparedStatement getProductLanguagesStmt;
	private PreparedStatement getProductSubjectsStmt;
	private PreparedStatement getProductIdentifiersStmt;

	public OverDriveProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection econtentConn, Logger logger) {
		this.indexer = groupedWorkIndexer;
		this.logger = logger;
		try {
			getProductInfoStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_products where overdriveId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getNumCopiesStmt = econtentConn.prepareStatement("SELECT sum(copiesOwned) as totalOwned FROM overdrive_api_product_availability WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getProductMetadataStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getProductAvailabilityStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			//getProductCreatorsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_creators where productId = ?");
			getProductFormatsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_formats where productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getProductLanguagesStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_languages inner join overdrive_api_product_languages_ref on overdrive_api_product_languages.id = languageId where productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getProductSubjectsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_subjects inner join overdrive_api_product_subjects_ref on overdrive_api_product_subjects.id = subjectId where productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getProductIdentifiersStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_identifiers where productId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up overdrive processor", e);
		}
	}

	private SimpleDateFormat dateAddedParser = new SimpleDateFormat("yyyy-MM-dd");
	public void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		try {
			indexer.overDriveRecordsIndexed.add(identifier);
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				Long productId = productRS.getLong("id");
				String title = productRS.getString("title");

				if (productRS.getInt("deleted") == 1) {
					logger.info("Not processing deleted overdrive product " + title + " - " + identifier);
					indexer.overDriveRecordsSkipped.add(identifier);

				} else {
					getNumCopiesStmt.setLong(1, productId);
					ResultSet numCopiesRS = getNumCopiesStmt.executeQuery();
					numCopiesRS.next();
					if (numCopiesRS.getInt("totalOwned") == 0) {
						logger.debug("Not processing overdrive product with no copies owned" + title + " - " + identifier);
						indexer.overDriveRecordsSkipped.add(identifier);
						return;
					} else {

						RecordInfo overDriveRecord = groupedWork.addRelatedRecord("overdrive", identifier);
						overDriveRecord.setRecordIdentifier("overdrive", identifier);

						String subtitle = productRS.getString("subtitle");
						String series = productRS.getString("series");
						if (subtitle == null) {
							subtitle = "";
						}
						String mediaType = productRS.getString("mediaType");
						String formatCategory;
						String primaryFormat;
						switch (mediaType) {
							case "Audiobook":
								formatCategory = "Audio Books";
								primaryFormat = "eAudiobook";
								break;
							case "Video":
								formatCategory = "Movies";
								primaryFormat = "eVideo";
								break;
							default:
								formatCategory = mediaType;
								primaryFormat = mediaType;
								break;
						}

						HashMap<String, String> metadata = loadOverDriveMetadata(groupedWork, productId, primaryFormat);

						String fullTitle = title + " " + subtitle;
						fullTitle = fullTitle.trim();
						groupedWork.setTitle(title, title, metadata.get("sortTitle"), primaryFormat);
						groupedWork.setSubTitle(subtitle);
						groupedWork.addFullTitle(fullTitle);

						groupedWork.addSeries(series);
						groupedWork.addSeriesWithVolume(series);
						groupedWork.setAuthor(productRS.getString("primaryCreatorName"));
						groupedWork.setAuthAuthor(productRS.getString("primaryCreatorName"));
						groupedWork.setAuthorDisplay(productRS.getString("primaryCreatorName"));

						Date dateAdded = null;
						try {
							String productDataRaw = productRS.getString("rawData");
							if (productDataRaw != null) {
								JSONObject productDataJSON = new JSONObject(productDataRaw);
								if (productDataJSON.has("dateAdded")) {
									String dateAddedString = productDataJSON.getString("dateAdded");
									if (dateAddedString.length() > 10) {
										dateAddedString = dateAddedString.substring(0, 10);
									}

									dateAdded = dateAddedParser.parse(dateAddedString);
								}
							}
						} catch (ParseException e) {
							logger.warn("Error parsing date added for Overdrive " + productId, e);
						} catch (JSONException e) {
							logger.warn("Error loading date added for Overdrive " + productId, e);
						}
						if (dateAdded == null) {
							dateAdded = new Date(productRS.getLong("dateAdded") * 1000);
						}

						productRS.close();

						String primaryLanguage = loadOverDriveLanguages(groupedWork, productId, identifier);
						String targetAudience = loadOverDriveSubjects(groupedWork, productId);

						//Load the formats for the record.  For OverDrive, we will create a separate item for each format.
						HashSet<String> validFormats = loadOverDriveFormats(groupedWork, productId, identifier);
						String detailedFormats = Util.getCsvSeparatedString(validFormats);
						//overDriveRecord.addFormats(validFormats);

						loadOverDriveIdentifiers(groupedWork, productId, primaryFormat);

						long maxFormatBoost = 1;
						for (String curFormat : validFormats) {
							long formatBoost = 1;
							try {
								formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_overdrive", curFormat.replace(' ', '_'), identifier));
							} catch (Exception e) {
								logger.warn("Could not translate format boost for " + primaryFormat);
							}
							if (formatBoost > maxFormatBoost) {
								maxFormatBoost = formatBoost;
							}
						}
						overDriveRecord.setFormatBoost(maxFormatBoost);

						//Load availability & determine which scopes are valid for the record
						getProductAvailabilityStmt.setLong(1, productId);
						ResultSet availabilityRS = getProductAvailabilityStmt.executeQuery();

						overDriveRecord.setEdition("");
						overDriveRecord.setPrimaryLanguage(primaryLanguage);
						overDriveRecord.setPublisher(metadata.get("publisher"));
						overDriveRecord.setPublicationDate(metadata.get("publicationDate"));
						overDriveRecord.setPhysicalDescription("");

						int totalCopiesOwned = 0;
						while (availabilityRS.next()) {
							//Just create one item for each with a list of sub formats.
							ItemInfo itemInfo = new ItemInfo();
							itemInfo.seteContentSource("OverDrive");
							itemInfo.seteContentProtectionType("Limited Access");
							itemInfo.setIsEContent(true);
							itemInfo.setShelfLocation("Online OverDrive Collection");
							itemInfo.setCallNumber("Online OverDrive");
							itemInfo.setSortableCallNumber("Online OverDrive");
							itemInfo.setDateAdded(dateAdded);

							overDriveRecord.addItem(itemInfo);

							long libraryId = availabilityRS.getLong("libraryId");
							boolean available = availabilityRS.getBoolean("available");

							itemInfo.setFormat(primaryFormat);
							itemInfo.setSubFormats(detailedFormats);
							itemInfo.setFormatCategory(formatCategory);

							//Need to set an identifier based on the scope so we can filter later.
							itemInfo.setItemIdentifier(Long.toString(libraryId));

							//TODO: Check to see if this is a pre-release title.  If not, suppress if the record has 0 copies owned
							int copiesOwned = availabilityRS.getInt("copiesOwned");
							itemInfo.setNumCopies(copiesOwned);
							totalCopiesOwned = Math.max(copiesOwned, totalCopiesOwned);

							if (available) {
								itemInfo.setDetailedStatus("Available Online");
							} else {
								itemInfo.setDetailedStatus("Checked Out");
							}

							boolean isAdult = targetAudience.equals("Adult");
							boolean isTeen = targetAudience.equals("Young Adult");
							boolean isKids = targetAudience.equals("Juvenile");
							if (libraryId == -1) {
								for (Scope scope : indexer.getScopes()) {
									if (scope.isIncludeOverDriveCollection()) {
										//Check based on the audience as well
										boolean okToInclude = false;
										if (isAdult && scope.isIncludeOverDriveAdultCollection()) {
											okToInclude = true;
										}
										if (isTeen && scope.isIncludeOverDriveTeenCollection()) {
											okToInclude = true;
										}
										if (isKids && scope.isIncludeOverDriveKidsCollection()) {
											okToInclude = true;
										}
										if (okToInclude) {
											ScopingInfo scopingInfo = itemInfo.addScope(scope);
											scopingInfo.setAvailable(available);
											scopingInfo.setHoldable(true);

											if (available) {
												scopingInfo.setStatus("Available Online");
												scopingInfo.setGroupedStatus("Available Online");
											} else {
												scopingInfo.setStatus("Checked Out");
												scopingInfo.setGroupedStatus("Checked Out");
											}
										}
									}
								}
							} else {
								for (Scope curScope : indexer.getScopes()) {
									if (curScope.isIncludeOverDriveCollection() && curScope.getLibraryId().equals(libraryId)) {
										boolean okToInclude = false;
										if (isAdult && curScope.isIncludeOverDriveAdultCollection()) {
											okToInclude = true;
										}
										if (isTeen && curScope.isIncludeOverDriveTeenCollection()) {
											okToInclude = true;
										}
										if (isKids && curScope.isIncludeOverDriveKidsCollection()) {
											okToInclude = true;
										}
										if (okToInclude) {
											ScopingInfo scopingInfo = itemInfo.addScope(curScope);
											scopingInfo.setAvailable(available);
											scopingInfo.setHoldable(true);
											if (curScope.isLocationScope()) {
												scopingInfo.setLocallyOwned(true);
												scopingInfo.setLibraryOwned(true);
											}
											if (curScope.isLibraryScope()) {
												scopingInfo.setLibraryOwned(true);
											}
											if (available) {
												scopingInfo.setStatus("Available Online");
												scopingInfo.setGroupedStatus("Available Online");
											} else {
												scopingInfo.setStatus("Checked Out");
												scopingInfo.setGroupedStatus("Checked Out");
											}
										}
									}
								}

							}//End processing availability
						}
						groupedWork.addHoldings(totalCopiesOwned);
					}
					numCopiesRS.close();
				}
			}
			productRS.close();
		} catch (SQLException e) {
			logger.error("Error loading information from Database for overdrive title", e);
		}

	}

	private void loadOverDriveIdentifiers(GroupedWorkSolr groupedWork, Long productId, String primaryFormat) throws SQLException {
		getProductIdentifiersStmt.setLong(1, productId);
		ResultSet identifiersRS = getProductIdentifiersStmt.executeQuery();
		while (identifiersRS.next()){
			String type = identifiersRS.getString("type");
			String value = identifiersRS.getString("value");
			//For now, ignore anything that isn't an ISBN
			if (type.equals("ISBN")){
				groupedWork.addIsbn(value, primaryFormat);
			}
		}
	}

	/**
	 * Load information based on subjects for the record
	 *
	 * @param groupedWork
	 * @param productId
	 * @return The target audience for use later in scoping
	 * @throws SQLException
	 */
	private String loadOverDriveSubjects(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		//Load subject data
		getProductSubjectsStmt.setLong(1, productId);
		ResultSet subjectsRS = getProductSubjectsStmt.executeQuery();
		HashSet<String> topics = new HashSet<>();
		HashSet<String> genres = new HashSet<>();
		HashMap<String, Integer> literaryForm = new HashMap<>();
		HashMap<String, Integer> literaryFormFull = new HashMap<>();
		String targetAudience = "Adult";
		String targetAudienceFull = "Adult";
		while (subjectsRS.next()){
			String curSubject = subjectsRS.getString("name");
			if (curSubject.contains("Nonfiction")){
				addToMapWithCount(literaryForm, "Non Fiction");
				addToMapWithCount(literaryFormFull, "Non Fiction");
				genres.add("Non Fiction");
			}else	if (curSubject.contains("Fiction")){
				addToMapWithCount(literaryForm, "Fiction");
				addToMapWithCount(literaryFormFull, "Fiction");
				genres.add("Fiction");
			}

			if (curSubject.contains("Poetry")){
				addToMapWithCount(literaryForm, "Fiction");
				addToMapWithCount(literaryFormFull, "Poetry");
			}else if (curSubject.contains("Essays")){
				addToMapWithCount(literaryForm, "Non Fiction");
				addToMapWithCount(literaryFormFull, curSubject);
			}else if (curSubject.contains("Short Stories") || curSubject.contains("Drama")){
				addToMapWithCount(literaryForm, "Fiction");
				addToMapWithCount(literaryFormFull, curSubject);
			}

			if (curSubject.contains("Juvenile")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Juvenile";
			}else if (curSubject.contains("Young Adult")){
				targetAudience = "Young Adult";
				targetAudienceFull = "Adolescent (14-17)";
			}else if (curSubject.contains("Picture Book")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Preschool (0-5)";
			}else if (curSubject.contains("Beginning Reader")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Primary (6-8)";
			}

			topics.add(curSubject);
		}
		groupedWork.addTopic(topics);
		groupedWork.addTopicFacet(topics);
		groupedWork.addGenre(genres);
		groupedWork.addGenreFacet(genres);
		if (literaryForm.size() > 0){
			groupedWork.addLiteraryForms(literaryForm);
		}
		if (literaryFormFull.size() > 0){
			groupedWork.addLiteraryFormsFull(literaryFormFull);
		}
		groupedWork.addTargetAudience(targetAudience);
		groupedWork.addTargetAudienceFull(targetAudienceFull);
		subjectsRS.close();

		return targetAudience;
	}

	private void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd){
		if (map.containsKey(elementToAdd)){
			map.put(elementToAdd, map.get(elementToAdd) + 1);
		}else{
			map.put(elementToAdd, 1);
		}
	}

	private String loadOverDriveLanguages(GroupedWorkSolr groupedWork, Long productId, String identifier) throws SQLException {
		String primaryLanguage = null;
		//Load languages
		getProductLanguagesStmt.setLong(1, productId);
		ResultSet languagesRS = getProductLanguagesStmt.executeQuery();
		HashSet<String> languages = new HashSet<>();
		while (languagesRS.next()){
			String language = languagesRS.getString("name");
			languages.add(language);
			if (primaryLanguage == null){
				primaryLanguage = language;
			}
			String languageCode = languagesRS.getString("code");
			String languageBoost = indexer.translateSystemValue("language_boost", languageCode, identifier);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateSystemValue("language_boost_es", languageCode, identifier);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoostEs);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		groupedWork.setLanguages(languages);
		languagesRS.close();
		if (primaryLanguage == null){
			primaryLanguage = "English";
		}
		return primaryLanguage;
	}

	private HashSet<String> loadOverDriveFormats(GroupedWorkSolr groupedWork, Long productId, String identifier) throws SQLException {
		//Load formats
		getProductFormatsStmt.setLong(1, productId);
		ResultSet formatsRS = getProductFormatsStmt.executeQuery();
		HashSet<String> formats = new HashSet<>();
		HashSet<String> eContentDevices = new HashSet<>();
		Long formatBoost = 1L;
		while (formatsRS.next()){
			String format = formatsRS.getString("name");
			formats.add(format);
			String deviceString = indexer.translateSystemValue("device_compatibility", format.replace(' ', '_'), identifier);
			String[] devices = deviceString.split("\\|");
			for (String device : devices){
				eContentDevices.add(device.trim());
			}
			String formatBoostStr = indexer.translateSystemValue("format_boost_overdrive", format.replace(' ', '_'), identifier);
			try{
				Long curFormatBoost = Long.parseLong(formatBoostStr);
				if (curFormatBoost > formatBoost){
					formatBoost = curFormatBoost;
				}
			}catch (NumberFormatException e){
				logger.warn("Could not parse format_boost " + formatBoostStr);
			}
		}
		//By default, formats are good for all locations
		groupedWork.addEContentDevices(eContentDevices);

		formatsRS.close();

		return formats;
	}

	private HashMap<String, String> loadOverDriveMetadata(GroupedWorkSolr groupedWork, Long productId, String format) throws SQLException {
		HashMap<String, String> returnMetadata = new HashMap<>();
		//Load metadata
		getProductMetadataStmt.setLong(1, productId);
		ResultSet metadataRS = getProductMetadataStmt.executeQuery();
		if (metadataRS.next()){
			returnMetadata.put("sortTitle", metadataRS.getString("sortTitle"));
			String publisher = metadataRS.getString("publisher");
			groupedWork.addPublisher(publisher);
			returnMetadata.put("publisher", publisher);
			String publicationDate = metadataRS.getString("publishDate");
			groupedWork.addPublicationDate(publicationDate);
			returnMetadata.put("publicationDate", publicationDate);
			//Need to divide this because it seems to be all time checkouts for all libraries, not just our libraries
			//Hopefully OverDrive will give us better stats in the near future that we can use.
			groupedWork.addPopularity(metadataRS.getFloat("popularity") / 500f);
			String shortDescription = metadataRS.getString("shortDescription");
			groupedWork.addDescription(shortDescription, format);
			String fullDescription = metadataRS.getString("fullDescription");
			groupedWork.addDescription(fullDescription, format);

			//Decode JSON data to get a little more information
			/*try {
				String rawMetadata = metadataRS.getString("rawData");
				if (rawMetadata != null) {
					JSONObject jsonData = new JSONObject(rawMetadata);
					if (jsonData.has("ATOS")) {
						groupedWork.setAcceleratedReaderReadingLevel(jsonData.getString("ATOS"));
					}
				}else{
					logger.error("Overdrive product " + productId + " did not have raw metadata");
				}
			} catch (JSONException e) {
				logger.error("Error loading raw data for OverDrive MetaData");
			}*/
		}
		metadataRS.close();
		return returnMetadata;
	}
}
