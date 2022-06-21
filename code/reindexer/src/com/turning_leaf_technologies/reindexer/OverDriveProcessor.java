package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

class OverDriveProcessor {
	private final GroupedWorkIndexer indexer;
	private final Logger logger;
	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getNumCopiesStmt;
	private PreparedStatement getProductMetadataStmt;
	private PreparedStatement getProductAvailabilityStmt;
	private PreparedStatement getProductFormatsStmt;
	private PreparedStatement doubleDecodeRawMetadataStmt;
	private PreparedStatement updateRawMetadataStmt;
	private final SimpleDateFormat publishDateFormatter = new SimpleDateFormat("MM/dd/yyyy");
	private final SimpleDateFormat publishDateFormatter2 = new SimpleDateFormat("MM/yyyy");
	private final SimpleDateFormat publishDateFormatter3 = new SimpleDateFormat("yyyy-MM-dd");
	private final Pattern publishDatePattern = Pattern.compile("([a-zA-Z]{3})\\s([\\s\\d]\\d)\\s(\\d{4}).*");
	private final Pattern publishDateFullMonthPattern = Pattern.compile("(january|february|march|april|may|june|july|august|september|october|november|december),?\\s(\\d{4}).*", Pattern.CASE_INSENSITIVE);

	OverDriveProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection dbConn, Logger logger) {
		this.indexer = groupedWorkIndexer;
		this.logger = logger;
		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT * from overdrive_api_products where overdriveId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getNumCopiesStmt = dbConn.prepareStatement("SELECT sum(copiesOwned) as totalOwned FROM overdrive_api_product_availability WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getProductMetadataStmt = dbConn.prepareStatement("SELECT id, productId, checksum, sortTitle, publisher, publishDate, isPublicDomain, isPublicPerformanceAllowed, shortDescription, fullDescription, starRating, popularity, UNCOMPRESS(rawData) as rawData, thumbnail, cover, isOwnedByCollections from overdrive_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getProductAvailabilityStmt = dbConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getProductFormatsStmt = dbConn.prepareStatement("SELECT * from overdrive_api_product_formats where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			doubleDecodeRawMetadataStmt = dbConn.prepareStatement("SELECT UNCOMPRESS(UNCOMPRESS(rawData)) as rawData from overdrive_api_product_metadata where id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			updateRawMetadataStmt = dbConn.prepareStatement("UPDATE overdrive_api_product_metadata SET rawData = COMPRESS(?) where id = ?");
		} catch (SQLException e) {
			logger.error("Error setting up overdrive processor", e);
		}
	}

	void processRecord(GroupedWorkSolr groupedWork, String identifier, BaseLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				long productId = productRS.getLong("id");
				String title = productRS.getString("title");

				if (productRS.getInt("deleted") == 1) {
					logger.info("Not processing deleted overdrive product " + title + " - " + identifier);
					indexer.overDriveRecordsSkipped.add(identifier);

				} else {
					//Check to see if at least one Aspen library owns a copy
					getNumCopiesStmt.setLong(1, productId);
					ResultSet numCopiesRS = getNumCopiesStmt.executeQuery();
					numCopiesRS.next();
					if (numCopiesRS.getInt("totalOwned") == 0) {
						logger.debug("Not processing overdrive product with no copies owned" + title + " - " + identifier);
						indexer.overDriveRecordsSkipped.add(identifier);
						return;
					} else {
						boolean hasKindle = false;

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
							case "Magazine":
								formatCategory = "eBook";
								primaryFormat = "eMagazine";
								break;
							default:
								formatCategory = mediaType;
								primaryFormat = mediaType;
								break;
						}

						HashMap<String, String> metadata = loadOverDriveMetadata(groupedWork, productId, primaryFormat, formatCategory, logEntry);

						if (!metadata.containsKey("rawMetadata") || (metadata.get("rawMetadata") == null)){
							//We didn't get metadata for the title.  This shouldn't happen in normal cases, but if it does,
							//we should just skip processing this record.
							logEntry.addNote("OverDrive record " + identifier + " did not have metadata, skipping");
							productRS.close();
							return;
						}
						String rawMetadataString = metadata.get("rawMetadata");
						if (rawMetadataString.charAt(0) != '{' || rawMetadataString.charAt(rawMetadataString.length() -1) != '}'){
							rawMetadataString = fixOverDriveMetaData(productId);
							if (rawMetadataString == null){
								logEntry.incErrors("Could not read or correct raw OverDrive Metadata for " + identifier);
							}
						}

						if (rawMetadataString == null){
							logEntry.incErrors("Could not load metadata for record " + identifier + " skipping");
							return;
						}
						//Decode JSON data to get a little more information
						JSONObject rawMetadataDecoded = null;
						try {
							rawMetadataDecoded = new JSONObject(rawMetadataString);
						} catch (JSONException e) {
							logEntry.incErrors("Error loading raw data for OverDrive MetaData for record " + identifier, e);
						}

						boolean isOnOrder = false;
						Date publishDate = null;
						if (rawMetadataDecoded != null) {
							if (rawMetadataDecoded.has("onSaleDate")) {
								String onSaleDate = rawMetadataDecoded.getString("onSaleDate");
							} else if (rawMetadataDecoded.has("publishDateText")) {
								String publishDateText = rawMetadataDecoded.getString("publishDateText");
								if (publishDateText.length() == 4 && StringUtils.isNumeric(publishDateText)) {
									GregorianCalendar publishCal = new GregorianCalendar();
									publishCal.set(Integer.parseInt(publishDateText), Calendar.JANUARY, 1);
									publishDate = publishCal.getTime();
									if (publishDate.after(new Date())) {
										isOnOrder = true;
									}
								} else {
									try {
										publishDate = publishDateFormatter.parse(publishDateText);
										if (publishDate.after(new Date())) {
											isOnOrder = true;
										}
									} catch (ParseException e) {
										try {
											publishDate = publishDateFormatter2.parse(publishDateText);
											if (publishDate.after(new Date())) {
												isOnOrder = true;
											}
										} catch (ParseException e2) {
											try{
												publishDate = publishDateFormatter3.parse(publishDateText);
												if (publishDate.after(new Date())) {
													isOnOrder = true;
												}
											} catch (ParseException e3) {
												Matcher publishDateMatcher = publishDatePattern.matcher(publishDateText);
												if (publishDateMatcher.matches()) {
													String month = publishDateMatcher.group(1).toLowerCase();
													String day = publishDateMatcher.group(2).trim();
													String year = publishDateMatcher.group(3);
													GregorianCalendar publishCal = new GregorianCalendar();
													int monthInt;
													switch (month) {
														case "jan":
															monthInt = Calendar.JANUARY;
															break;
														case "feb":
															monthInt = Calendar.FEBRUARY;
															break;
														case "mar":
															monthInt = Calendar.MARCH;
															break;
														case "apr":
															monthInt = Calendar.APRIL;
															break;
														case "may":
															monthInt = Calendar.MAY;
															break;
														case "jun":
															monthInt = Calendar.JUNE;
															break;
														case "jul":
															monthInt = Calendar.JULY;
															break;
														case "aug":
															monthInt = Calendar.AUGUST;
															break;
														case "sep":
															monthInt = Calendar.SEPTEMBER;
															break;
														case "oct":
															monthInt = Calendar.OCTOBER;
															break;
														case "nov":
															monthInt = Calendar.NOVEMBER;
															break;
														case "dec":
															monthInt = Calendar.DECEMBER;
															break;
														default:
															monthInt = Calendar.JANUARY;
															break;
													}
													publishCal.set(Integer.parseInt(year), monthInt, (Integer.parseInt(day)));
													publishDate = publishCal.getTime();
													if (publishDate.after(new Date())) {
														isOnOrder = true;
													}
												} else {
													Matcher publishDateFullMonthMatcher = publishDateFullMonthPattern.matcher(publishDateText);
													if (publishDateFullMonthMatcher.matches()) {
														String month = publishDateFullMonthMatcher.group(1).toLowerCase();
														String year = publishDateFullMonthMatcher.group(2);
														GregorianCalendar publishCal = new GregorianCalendar();
														int monthInt;
														switch (month) {
															case "january":
																monthInt = Calendar.JANUARY;
																break;
															case "february":
																monthInt = Calendar.FEBRUARY;
																break;
															case "march":
																monthInt = Calendar.MARCH;
																break;
															case "april":
																monthInt = Calendar.APRIL;
																break;
															case "may":
																monthInt = Calendar.MAY;
																break;
															case "june":
																monthInt = Calendar.JUNE;
																break;
															case "july":
																monthInt = Calendar.JULY;
																break;
															case "august":
																monthInt = Calendar.AUGUST;
																break;
															case "september":
																monthInt = Calendar.SEPTEMBER;
																break;
															case "october":
																monthInt = Calendar.OCTOBER;
																break;
															case "november":
																monthInt = Calendar.NOVEMBER;
																break;
															case "december":
																monthInt = Calendar.DECEMBER;
																break;
															default:
																monthInt = Calendar.JANUARY;
																break;
														}
														publishCal.set(Integer.parseInt(year), monthInt, 1);
														publishDate = publishCal.getTime();
														if (publishDate.after(new Date())) {
															isOnOrder = true;
														}
													} else {
														logEntry.addNote("Error parsing publication date " + publishDateText);
													}
												}
											}
										}
									}
								}
							}
						}

						String fullTitle = title + " " + subtitle;
						fullTitle = fullTitle.trim();
						groupedWork.setTitle(title, subtitle, title, metadata.get("sortTitle"), primaryFormat, formatCategory);
						groupedWork.addFullTitle(fullTitle);

						if (series != null && series.length() > 0) {
							groupedWork.addSeries(series);
							groupedWork.addSeriesWithVolume(series, "");
						}
						groupedWork.setAuthor(productRS.getString("primaryCreatorName"));
						groupedWork.setAuthAuthor(productRS.getString("primaryCreatorName"));
						groupedWork.setAuthorDisplay(productRS.getString("primaryCreatorName"));
						if (rawMetadataDecoded != null) {
							//Loop through all creators and add them as alternate author names
							JSONArray creators = rawMetadataDecoded.getJSONArray("creators");
							HashSet<String> authors = new HashSet<>();
							HashSet<String> authorsWithRole = new HashSet<>();
							for (int i = 0; i < creators.length(); i++){
								JSONObject creator = creators.getJSONObject(i);
								authors.add(creator.getString("fileAs"));
								authorsWithRole.add(creator.getString("fileAs") + "|" + creator.getString("role"));
							}
							groupedWork.addAuthor2(authors);
							groupedWork.addAuthor2Role(authorsWithRole);
						}

						Date dateAdded = new Date(productRS.getLong("dateAdded") * 1000);

						productRS.close();

						String primaryLanguage = "English";
						String targetAudience = "Adult";
						if (rawMetadataDecoded != null) {
							primaryLanguage = loadOverDriveLanguages(groupedWork, rawMetadataDecoded, identifier);
							targetAudience = loadOverDriveSubjects(groupedWork, rawMetadataDecoded);
						}

						//Load the formats for the record.  For OverDrive, we will create a separate item for each format.
						HashSet<String> validFormats = loadOverDriveFormats(productId, identifier);
						if (validFormats.contains("Kindle Book")){
							hasKindle = true;
						}
						if (rawMetadataDecoded != null) {
							if (rawMetadataDecoded.has("subjects")) {
								JSONArray subjects = rawMetadataDecoded.getJSONArray("subjects");
								for (int i = 0; i < subjects.length(); i++) {
									String curSubject = subjects.getJSONObject(i).getString("value");
									if (curSubject.equals("Comic and Graphic Books") && validFormats.contains("eBook")) {
										validFormats.remove("eBook");
										validFormats.add("eComic");
										primaryFormat = "eComic";
									}
								}
							}
						}
						String detailedFormats = Util.getCsvSeparatedString(validFormats);
						//overDriveRecord.addFormats(validFormats);
						if (rawMetadataDecoded != null) {
							loadOverDriveIdentifiers(groupedWork, rawMetadataDecoded, primaryFormat);
						}

						long maxFormatBoost = 1;
						for (String curFormat : validFormats) {
							long formatBoost = 1;
							try {
								formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_overdrive", curFormat.replace(' ', '_'), identifier));
							} catch (Exception e) {
								logEntry.addNote("Could not translate format boost for " + primaryFormat);
							}
							if (formatBoost > maxFormatBoost) {
								maxFormatBoost = formatBoost;
							}
						}
						overDriveRecord.setFormatBoost(maxFormatBoost);

						if (rawMetadataDecoded != null) {
							if (rawMetadataDecoded.has("edition")) {
								overDriveRecord.setEdition(rawMetadataDecoded.getString("edition"));
							} else {
								overDriveRecord.setEdition("");
							}
						}
						overDriveRecord.setPrimaryLanguage(primaryLanguage);
						overDriveRecord.setPublisher(StringUtils.trimTrailingPunctuation(metadata.get("publisher")));
						overDriveRecord.setPublicationDate(metadata.get("publicationDate"));
						overDriveRecord.setPhysicalDescription("");

						//Loop through all of our scopes and figure out if that scope has records.
						int totalCopiesOwned = 0;
						int numHolds = 0;
						//Just create one item for each with a list of sub formats.
						ItemInfo itemInfo = new ItemInfo();
						itemInfo.seteContentSource("OverDrive");
						itemInfo.setIsEContent(true);
						itemInfo.setShelfLocation("OverDrive");
						itemInfo.setDetailedLocation("OverDrive");
						itemInfo.setCallNumber("OverDrive");
						itemInfo.setSortableCallNumber("OverDrive");
						if (isOnOrder) {
							itemInfo.setIsOrderItem();
							itemInfo.setDateAdded(publishDate);
						} else {
							itemInfo.setDateAdded(dateAdded);
						}

						itemInfo.setFormat(primaryFormat);
						itemInfo.setSubFormats(detailedFormats);
						itemInfo.setFormatCategory(formatCategory);

						//Need to set an identifier based on the scope so we can filter later.
						itemInfo.setItemIdentifier(identifier + ":" + primaryFormat);

						//Get Availability for the product
						getProductAvailabilityStmt.setLong(1, productId);
						ResultSet availabilityRS = getProductAvailabilityStmt.executeQuery();
						HashMap<String, OverDriveAvailabilityInfo> availabilityInfo = new HashMap<>();
						while (availabilityRS.next()) {
							availabilityInfo.put(
									availabilityRS.getString("settingId") + ":" + availabilityRS.getString("libraryId"),
									new OverDriveAvailabilityInfo(availabilityRS.getInt("numberOfHolds"), availabilityRS.getLong("libraryId"), availabilityRS.getBoolean("available"), availabilityRS.getInt("copiesOwned"))
							);
						}
						availabilityRS.close();

						for (Scope scope : indexer.getScopes()) {
							if (scope.isIncludeOverDriveCollection()) {

								//Load availability & determine which scopes are valid for the record
								//This does not include any shared records since those are included in the main collection
								OverDriveAvailabilityInfo availability = availabilityInfo.get(scope.getOverDriveScope().getSettingId() + ":" + scope.getLibraryId());
								if (availability == null){
									availability = availabilityInfo.get(scope.getOverDriveScope().getSettingId() + ":-1");
								}

								if (availability != null) {
									numHolds = Math.max(availability.numberOfHolds, numHolds);

									long libraryId = availability.libraryId;
									boolean available = availability.available;

									//TODO: Check to see if this is a pre-release title.  If not, suppress if the record has 0 copies owned
									int copiesOwned = availability.copiedOwned;
									itemInfo.setNumCopies(copiesOwned);

									//Add copies since non-shared records are distinct from shared collection
									totalCopiesOwned = Math.max(totalCopiesOwned, copiesOwned);

									if (copiesOwned == 0 && libraryId != -1) {
										//Don't add advantage info if the library does not own additional copies (or have additional copies shared with it)
										continue;
									}
									itemInfo.setAvailable(available);
									itemInfo.setHoldable(true);

									if (isOnOrder) {
										itemInfo.setDetailedStatus("On Order");
										itemInfo.setGroupedStatus("On Order");
									} else if (available) {
										itemInfo.setDetailedStatus("Available Online");
										itemInfo.setGroupedStatus("Available Online");
									} else {
										itemInfo.setDetailedStatus("Checked Out");
										itemInfo.setGroupedStatus("Checked Out");
									}

									boolean isAdult = targetAudience.equals("Adult");
									boolean isTeen = targetAudience.equals("Young Adult");
									boolean isKids = targetAudience.equals("Juvenile");
									//Check based on the audience as well
									boolean okToInclude = false;
									//noinspection RedundantIfStatement
									if (isAdult && scope.getOverDriveScope().isIncludeAdult()) {
										okToInclude = true;
									}
									if (isTeen && scope.getOverDriveScope().isIncludeTeen()) {
										okToInclude = true;
									}
									if (isKids && scope.getOverDriveScope().isIncludeKids()) {
										okToInclude = true;
									}
									if (okToInclude) {
										ScopingInfo scopingInfo = itemInfo.addScope(scope);
										if (scope.isLocationScope()) {
											scopingInfo.setLocallyOwned(true);
											scopingInfo.setLibraryOwned(true);
										}
										if (scope.isLibraryScope()) {
											scopingInfo.setLibraryOwned(true);
										}
										groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);
									}
								}
							} // Scope has OverDrive content
						} // End looping through scopes
						overDriveRecord.addItem(itemInfo);

						groupedWork.addHoldings(totalCopiesOwned);
						groupedWork.addHolds(numHolds);

						if (hasKindle){
							RecordInfo kindleRecord = groupedWork.addRelatedRecord("overdrive", "kindle", identifier);
							kindleRecord.copyFrom(overDriveRecord);
							for (ItemInfo tmpItemInfo: kindleRecord.getRelatedItems()){
								tmpItemInfo.setItemIdentifier(tmpItemInfo.getItemIdentifier() + ":kindle");
								tmpItemInfo.setFormat("Kindle");
								tmpItemInfo.setSubFormats("");
							}
						}
					}
					numCopiesRS.close();
				}
			}
			productRS.close();
		} catch (JSONException e) {
			logEntry.incErrors("Error loading information from JSON for overdrive title", e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for overdrive title", e);
		}

	}

	private String fixOverDriveMetaData(long productId) throws SQLException {
		doubleDecodeRawMetadataStmt.setLong(1, productId);
		ResultSet doubleDecodeRawResponseRS = doubleDecodeRawMetadataStmt.executeQuery();
		if (doubleDecodeRawResponseRS.next()){
			String rawResponseString = doubleDecodeRawResponseRS.getString("rawData");
			if (rawResponseString.charAt(0) == '{' && rawResponseString.charAt(rawResponseString.length() -1) == '}'){
				updateRawMetadataStmt.setString(1, rawResponseString);
				updateRawMetadataStmt.setLong(2, productId);
				updateRawMetadataStmt.executeUpdate();
				return rawResponseString;
			}
		}
		doubleDecodeRawResponseRS.close();

		return null;
	}

	private void loadOverDriveIdentifiers(GroupedWorkSolr groupedWork, JSONObject productMetadata, String primaryFormat) throws JSONException {
		if (productMetadata.has("formats")) {
			JSONArray formats = productMetadata.getJSONArray("formats");
			for (int i = 0; i < formats.length(); i++) {
				JSONObject curFormat = formats.getJSONObject(i);
				//Things like videos do not have identifiers so we need to check for the lack here
				if (curFormat.has("identifiers")) {
					JSONArray identifiers = curFormat.getJSONArray("identifiers");
					for (int j = 0; j < identifiers.length(); j++) {
						JSONObject curIdentifier = identifiers.getJSONObject(j);
						String type = curIdentifier.getString("type");
						String value = curIdentifier.getString("value");
						//For now, ignore anything that isn't an ISBN
						if (type.equals("ISBN")) {
							groupedWork.addIsbn(value, primaryFormat);
						} else if (type.equals("UPC")) {
							groupedWork.addUpc(value);
						}
					}
				}
			}
		}else{
			logger.warn("OverDrive product did not have formats");
		}
	}

	/**
	 * Load information based on subjects for the record
	 *
	 * @param groupedWork     The Grouped Work being updated
	 * @param productMetadata JSON representing the raw data metadata from OverDrive
	 * @return The target audience for use later in scoping
	 * @throws JSONException Exception if something goes horribly wrong
	 */
	private String loadOverDriveSubjects(GroupedWorkSolr groupedWork, JSONObject productMetadata) throws JSONException {
		//Load subject data

		HashSet<String> topics = new HashSet<>();
		HashSet<String> genres = new HashSet<>();
		HashMap<String, Integer> literaryForm = new HashMap<>();
		HashMap<String, Integer> literaryFormFull = new HashMap<>();
		String targetAudience = "Adult";
		String targetAudienceFull = "Adult";
		if (productMetadata.has("subjects")) {
			JSONArray subjects = productMetadata.getJSONArray("subjects");
			for (int i = 0; i < subjects.length(); i++) {
				String curSubject = subjects.getJSONObject(i).getString("value");
				if (curSubject.contains("Nonfiction")) {
					Util.addToMapWithCount(literaryForm, "Non Fiction");
					Util.addToMapWithCount(literaryFormFull, "Non Fiction");
					genres.add("Non Fiction");
				} else if (curSubject.contains("Fiction")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, "Fiction");
					genres.add("Fiction");
				}

				if (curSubject.contains("Poetry")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, "Poetry");
				} else if (curSubject.contains("Essays")) {
					Util.addToMapWithCount(literaryForm, "Non Fiction");
					Util.addToMapWithCount(literaryFormFull, curSubject);
				} else if (curSubject.contains("Short Stories") || curSubject.contains("Drama")) {
					Util.addToMapWithCount(literaryForm, "Fiction");
					Util.addToMapWithCount(literaryFormFull, curSubject);
				}

				if (curSubject.contains("Juvenile")) {
					targetAudience = "Juvenile";
					targetAudienceFull = "Juvenile";
				} else if (curSubject.contains("Young Adult")) {
					targetAudience = "Young Adult";
					targetAudienceFull = "Adolescent (14-17)";
				} else if (curSubject.contains("Picture Book")) {
					targetAudience = "Juvenile";
					targetAudienceFull = "Preschool (0-5)";
				} else if (curSubject.contains("Beginning Reader")) {
					targetAudience = "Juvenile";
					targetAudienceFull = "Primary (6-8)";
				}

				topics.add(curSubject);
			}
			groupedWork.addTopic(topics);
			groupedWork.addTopicFacet(topics);
			groupedWork.addGenre(genres);
			groupedWork.addGenreFacet(genres);
			if (literaryForm.size() > 0) {
				groupedWork.addLiteraryForms(literaryForm);
			}
			if (literaryFormFull.size() > 0) {
				groupedWork.addLiteraryFormsFull(literaryFormFull);
			}
		}

		groupedWork.addTargetAudience(targetAudience);
		groupedWork.addTargetAudienceFull(targetAudienceFull);

		return targetAudience;
	}

	private String loadOverDriveLanguages(GroupedWorkSolr groupedWork, JSONObject productMetadata, String identifier) throws JSONException {
		String primaryLanguage = null;
		if (productMetadata.has("languages")) {
			JSONArray languagesFromMetadata = productMetadata.getJSONArray("languages");

			//Load languages
			HashSet<String> languages = new HashSet<>();
			for (int i = 0; i < languagesFromMetadata.length(); i++) {
				JSONObject curLanguageObj = languagesFromMetadata.getJSONObject(i);
				String language = curLanguageObj.getString("name");
				//OverDrive no adds multiple languages separated by commas
				String[] splitLanguages = language.split(";");
				for (String curLanguage : splitLanguages) {
					curLanguage = curLanguage.trim();
					languages.add(curLanguage);
					if (primaryLanguage == null) {
						primaryLanguage = curLanguage;
					}
				}
				String languageCode = curLanguageObj.getString("code");
				String languageBoost = indexer.translateSystemValue("language_boost", languageCode, identifier);
				if (languageBoost != null) {
					Long languageBoostVal = Long.parseLong(languageBoost);
					groupedWork.setLanguageBoost(languageBoostVal);
				}
				String languageBoostEs = indexer.translateSystemValue("language_boost_es", languageCode, identifier);
				if (languageBoostEs != null) {
					Long languageBoostVal = Long.parseLong(languageBoostEs);
					groupedWork.setLanguageBoostSpanish(languageBoostVal);
				}
			}
			groupedWork.setLanguages(languages);
		} else {
			groupedWork.addLanguage("English");
		}

		if (primaryLanguage == null) {
			primaryLanguage = "English";
		}
		return primaryLanguage;
	}

	private HashSet<String> loadOverDriveFormats(Long productId, String identifier) throws SQLException {
		//Load formats
		getProductFormatsStmt.setLong(1, productId);
		ResultSet formatsRS = getProductFormatsStmt.executeQuery();
		HashSet<String> formats = new HashSet<>();
		long formatBoost = 1L;
		while (formatsRS.next()) {
			String format = formatsRS.getString("name");
			formats.add(format);
			String formatBoostStr = indexer.translateSystemValue("format_boost_overdrive", format.replace(' ', '_'), identifier);
			try {
				long curFormatBoost = Long.parseLong(formatBoostStr);
				if (curFormatBoost > formatBoost) {
					formatBoost = curFormatBoost;
				}
			} catch (NumberFormatException e) {
				logger.warn("Could not parse format_boost " + formatBoostStr);
			}
		}
		formatsRS.close();

		return formats;
	}

	private HashMap<String, String> loadOverDriveMetadata(GroupedWorkSolr groupedWork, long productId, String format, String formatCategory, BaseLogEntry logEntry) throws SQLException {
		HashMap<String, String> returnMetadata = new HashMap<>();
		//Load metadata
		getProductMetadataStmt.setLong(1, productId);
		ResultSet metadataRS = getProductMetadataStmt.executeQuery();
		if (metadataRS.next()) {
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
			groupedWork.addDescription(shortDescription, format, formatCategory);
			String fullDescription = metadataRS.getString("fullDescription");
			groupedWork.addDescription(fullDescription, format, formatCategory);

			try {
				byte[] rawDataBytes = metadataRS.getBytes("rawData");
				if (!metadataRS.wasNull()) {
					returnMetadata.put("rawMetadata", new String(rawDataBytes, StandardCharsets.UTF_8));
					rawDataBytes = null;
				}
			}catch (Exception e) {
				logEntry.incErrors("Error loading metadata for record " + productId, e);
			}
		}
		metadataRS.close();
		return returnMetadata;
	}
}
