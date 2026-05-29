package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.HooplaScope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.commons.lang3.StringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

class HooplaProcessor2 {
	private final GroupedWorkIndexer indexer;
	private final Logger logger;

	private PreparedStatement getProductInfoStmt;
	private PreparedStatement doubleDecodeRawResponseStmt;
	private PreparedStatement updateRawResponseStmt;
	private PreparedStatement getFlexAvailabilityStmt;
	private PreparedStatement getEntitlementsByHooplaIdStmt;

	HooplaProcessor2(GroupedWorkIndexer indexer, Connection dbConn, Logger logger) {
		this.indexer = indexer;
		this.logger = logger;

		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT id, hooplaId, title, format, pa, demo, profanity, rating, abridged, children, ppuPrice, rawChecksum, UNCOMPRESS(rawResponse) as rawResponse, dateFirstDetected from hoopla_export where hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			doubleDecodeRawResponseStmt = dbConn.prepareStatement("SELECT UNCOMPRESS(UNCOMPRESS(rawResponse)) as rawResponse from hoopla_export where id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			updateRawResponseStmt = dbConn.prepareStatement("UPDATE hoopla_export SET rawResponse = COMPRESS(?) where id = ?");
			getFlexAvailabilityStmt = dbConn.prepareStatement("SELECT * from hoopla_flex_availability where hooplaId = ? and scopeLibraryId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getEntitlementsByHooplaIdStmt = dbConn.prepareStatement("SELECT he.hooplaType, hes.scopeLibraryId FROM hoopla_entitlements he INNER JOIN hoopla_entitlement_scopes hes ON hes.entitlementId = he.id WHERE he.hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up hoopla processor", e);
		}
	}

	void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				// Check if the record has any entitlements
				long hooplaId = productRS.getLong("hooplaId");
				HashMap<Long, String> entitlementsByScope = loadEntitlementsForTitle(hooplaId);
				if (entitlementsByScope.isEmpty()) {
					logger.warn("Hoopla title " + identifier + ", hooplaId " + hooplaId + " has no entitlements, skipping");
					return;
				}

				byte[] rawResponseBytes = productRS.getBytes("rawResponse");
				if (rawResponseBytes == null){
					logEntry.incErrors("rawResponse for Hoopla title " + identifier + " was null skipping");
					return;
				}
				String format = productRS.getString("format");
				float price = productRS.getFloat("ppuPrice");

				RecordInfo hooplaRecord = groupedWork.addRelatedRecord("hoopla", identifier);
				hooplaRecord.setRecordIdentifier("hoopla", identifier);

				String title = productRS.getString("title");
				String subTitle = "";

				String formatCategory;
				String primaryFormat;
				switch (format) {
					case "MOVIE":
					case "TELEVISION":
						formatCategory = "Movies";
						primaryFormat = "eVideo";
						break;
					case "AUDIOBOOK":
						formatCategory = "Audio Books";
						hooplaRecord.addFormatCategory("eBook");
						primaryFormat = "eAudiobook";
						break;
					case "EBOOK":
						formatCategory = "eBook";
						primaryFormat = "eBook";
						break;
					case "COMIC":
						formatCategory = "eBook";
						primaryFormat = "eComic";
						break;
					case "MUSIC":
						formatCategory = "Music";
						primaryFormat = "eMusic";
						break;
					case "BINGEPASS":
						formatCategory = "Other";
						primaryFormat = "Binge Pass";
						break;
					default:
						logger.error("Unhandled hoopla format " + format);
						formatCategory = format;
						primaryFormat = format;
						break;
				}
				if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Format is " + primaryFormat + " based on format of " + format, 2);}

				hooplaRecord.addFormat(primaryFormat);
				hooplaRecord.addFormatCategory(formatCategory);

				String rawResponseString = new String(rawResponseBytes, StandardCharsets.UTF_8);
				if (rawResponseString.charAt(0) != '{' || rawResponseString.charAt(rawResponseString.length() -1) != '}'){
					//If the first char is not { check to see if it has been double encoded
					rawResponseString = fixHooplaData(productRS.getLong("id"));
					if (rawResponseString == null){
						logEntry.incErrors("Could not read or correct Hoopla raw response for " + identifier);
					}
				}
				JSONObject rawResponse = new JSONObject(rawResponseString);

				if (rawResponse.has("seasonNumber")) {
					if (rawResponse.has("seriesName")){
						title = rawResponse.getString("seriesName");
					}
					title += " - Season " + rawResponse.get("seasonNumber").toString();
					if (rawResponse.has("episodeNumber")) {
						title += " Episode " + rawResponse.get("episodeNumber").toString();
					}
				}else {
					if (rawResponse.has("title")) {
						title = rawResponse.getString("title");
					}
					if (rawResponse.has("subtitle")) {
						subTitle = rawResponse.getString("subtitle");
					}
				}

				String fullTitle = title + " " + subTitle;
				fullTitle = fullTitle.trim();
				String sortableTitle = AspenStringUtils.makeValueSortable(title);
				groupedWork.setTitle(title, subTitle, sortableTitle, formatCategory, false, hooplaRecord);
				groupedWork.addFullTitle(fullTitle);


				String primaryAuthor = "";
				if (rawResponse.has("artist")){
					primaryAuthor = rawResponse.getString("artist");
					//Don't swap artist names for music since these are typically group names.
					if (!format.equals("MUSIC")) {
						primaryAuthor = AspenStringUtils.swapFirstLastNames(primaryAuthor);
					}
				}else if (rawResponse.has("publisher")){
					primaryAuthor = rawResponse.getString("publisher");
				}
				groupedWork.setAuthor(primaryAuthor);
				groupedWork.setAuthAuthor(primaryAuthor);
				groupedWork.setAuthorDisplay(primaryAuthor, formatCategory, hooplaRecord);

				String series = rawResponse.optString("seriesName", rawResponse.optString("series", ""));

				if (!series.isEmpty()){
					groupedWork.addSeries(series);
					if (rawResponse.has("seriesNumber")) {
						String volume = rawResponse.optString("seriesNumber", rawResponse.optString("volume", ""));
						if (rawResponse.has("episodeNumber") || rawResponse.has("episode")) {
							volume += " Episode " + rawResponse.optString("episodeNumber", rawResponse.optString("episode", ""));
						}
						groupedWork.addSeriesWithVolume(series, volume, 2, false);
					}
				}

				if (rawResponse.has("atosBookLevel")) {
					groupedWork.setAcceleratedReaderReadingLevel(rawResponse.getString("atosBookLevel"));
				}
				if (rawResponse.has("interestLevel")){
					groupedWork.setAcceleratedReaderInterestLevel(rawResponse.getString("interestLevel"));
				}
				if (rawResponse.has("lexile")) {
					groupedWork.setLexileScore(rawResponse.getString("lexile"));
				}

				boolean children = productRS.getBoolean("children");
				boolean isAdult = false;
				boolean isTeen = false;
				boolean isKids = false;
				if (children){
					isKids = true;
					groupedWork.addTargetAudience("Juvenile");
					groupedWork.addTargetAudienceFull("Juvenile");
					if ( groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Juvenile based on Hoopla record", 2);}
				} else {
					boolean foundAudience = false;
					if (rawResponse.has("audience")) {
						if (rawResponse.get("audience") instanceof JSONArray){
							JSONArray audiences = rawResponse.getJSONArray("audience");
							for (int i = 0; i < audiences.length(); i++) {
								if (audiences.getString(i).equals("Juvenile")) {
									isKids = true;
									groupedWork.addTargetAudience("Juvenile");
									groupedWork.addTargetAudienceFull("Juvenile");
								} else if (audiences.getString(i).equals("Young Adult")) {
									isTeen = true;
									groupedWork.addTargetAudience("Young Adult");
									groupedWork.addTargetAudienceFull("Young Adult");
								} else if (audiences.getString(i).equals("General Adult") || audiences.getString(i).equals("Mature")) {
									isAdult = true;
									groupedWork.addTargetAudience("Adult");
									groupedWork.addTargetAudienceFull("Adult");
								}
								foundAudience = true;
							}
						} else {
							if (rawResponse.getString("audience").equals("Juvenile")) {
								isKids = true;
								groupedWork.addTargetAudience("Juvenile");
								groupedWork.addTargetAudienceFull("Juvenile");
							} else if (rawResponse.getString("audience").equals("Young Adult")) {
								isTeen = true;
								groupedWork.addTargetAudience("Young Adult");
								groupedWork.addTargetAudienceFull("Young Adult");
							} else if (rawResponse.getString("audience").equals("General Adult") || rawResponse.getString("audience").equals("Mature")) {
								isAdult = true;
								groupedWork.addTargetAudience("Adult");
								groupedWork.addTargetAudienceFull("Adult");
							}
							foundAudience = true;
						}
					} else if (rawResponse.has("genres")) {
						JSONArray genres = rawResponse.getJSONArray("genres");
						for (int i = 0; i < genres.length(); i++) {
							if (genres.getString(i).equals("Teen") || genres.getString(i).startsWith("Young Adult")) {
								isTeen = true;
								groupedWork.addTargetAudience("Young Adult");
								groupedWork.addTargetAudienceFull("Adolescent (14-17)");
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is Young Adult based on Hoopla genre", 2);}
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is Adolescent (14-17) based on Hoopla genre", 2);}
								foundAudience = true;
							} else if (genres.getString(i).equals("Children's")) {
								isKids = true;
								groupedWork.addTargetAudience("Juvenile");
								groupedWork.addTargetAudienceFull("Juvenile");
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Juvenile based on Hoopla genre", 2);}
								foundAudience = true;
							} else if (genres.getString(i).equals("Adult")) {
								isAdult = true;
								groupedWork.addTargetAudience("Adult");
								groupedWork.addTargetAudienceFull("Adult");
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla genre", 2);}
								foundAudience = true;
							}
						}
					}

					if (!foundAudience && rawResponse.has("ratings")) {
						String rating = productRS.getString("rating");
						if (rating.equals("TVMA") || rating.equals("M") || rating.equals("NC17")) {
							isAdult = true;
							groupedWork.addTargetAudience("Adult");
							groupedWork.addTargetAudienceFull("Adult");
							if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla rating", 2);}
						} else {
							if (format.equals("MOVIE") || format.equals("TELEVISION")) {
								switch (rating) {
									case "R":
									case "NR":
									case "NRA":
									case "NRM":
									case "NC-17":
										isAdult = true;
										groupedWork.addTargetAudience("Adult");
										groupedWork.addTargetAudienceFull("Adult");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla rating " + rating, 2);}
										break;
									case "PG-13":
									case "PG13":
									case "PG":
									case "TVPG":
									case "TV14":
									case "NRT":
										isAdult = true;
										isTeen = true;
										groupedWork.addTargetAudience("Young Adult");
										groupedWork.addTargetAudienceFull("Adolescent (14-17)");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is Young Adult based on Hoopla rating " + rating, 2);}
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is Adolescent (14-17) based on Hoopla rating " + rating, 2);}
										groupedWork.addTargetAudience("Adult");
										groupedWork.addTargetAudienceFull("Adult");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla rating " + rating, 2);}
										break;
									case "TVY":
									case "TVY7":
									case "NRC":
										isKids = true;
										groupedWork.addTargetAudience("Juvenile");
										groupedWork.addTargetAudienceFull("Juvenile");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Juvenile based on Hoopla rating " + rating, 2);}
										break;
									case "TVG":
									case "G":
										isKids = true;
										isTeen = true;
										isAdult = true;
										groupedWork.addTargetAudience("General");
										groupedWork.addTargetAudienceFull("General");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is General based on Hoopla rating " + rating, 2);}
										break;
									default:
										//todo, do we want to add additional ratings here?
										logger.debug("rating " + rating);
										break;
								}
							} else if (format.equals("COMIC")) {
								switch (rating) {
									case "E":
										isKids = true;
										groupedWork.addTargetAudience("Juvenile");
										groupedWork.addTargetAudienceFull("Juvenile");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Juvenile based on Hoopla rating " + rating, 2);}
										break;
									case "PA":
									case "EX":
										isAdult = true;
										groupedWork.addTargetAudience("Adult");
										groupedWork.addTargetAudienceFull("Adult");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla rating " + rating, 2);}
										break;
									case "T":
										isTeen = true;
										groupedWork.addTargetAudience("Young Adult");
										groupedWork.addTargetAudienceFull("Adolescent (14-17)");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is Young Adult based on Hoopla rating " + rating, 2);}
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is Adolescent (14-17) based on Hoopla rating " + rating, 2);}
										break;
									case "T+":
									default:
										isAdult = true;
										isTeen = true;
										groupedWork.addTargetAudience("Young Adult");
										groupedWork.addTargetAudienceFull("Adolescent (14-17)");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is Young Adult based on Hoopla rating " + rating, 2);}
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is Adolescent (14-17) based on Hoopla rating " + rating, 2);}
										groupedWork.addTargetAudience("Adult");
										groupedWork.addTargetAudienceFull("Adult");
										if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla rating " + rating, 2);}
								}

							} else {
								isAdult = true;
								isTeen = true;
								groupedWork.addTargetAudience("Young Adult");
								groupedWork.addTargetAudienceFull("Adolescent (14-17)");
								groupedWork.addTargetAudience("Adult");
								groupedWork.addTargetAudienceFull("Adult");
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target audience is Young Adult based on Hoopla rating " + rating, 2);}
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Full target audience is Adolescent (14-17) based on Hoopla rating " + rating, 2);}
								if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla rating " + rating, 2);}
							}
						}
					} else if (!foundAudience) {
						isAdult = true;
						groupedWork.addTargetAudience("Adult");
						groupedWork.addTargetAudienceFull("Adult");
						if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Target/full target audience is Adult based on Hoopla record", 2);}
					}
				}

				String language = rawResponse.getString("language");
				language = StringUtils.capitalize(language.toLowerCase());
				hooplaRecord.setPrimaryLanguage(language);
				groupedWork.addLanguage(language);
				if (language.equalsIgnoreCase("English")){
					groupedWork.setLanguageBoost(10L);
				}else if (language.equalsIgnoreCase("Spanish")){
					groupedWork.setLanguageBoostSpanish(10L);
				}
				long formatBoost = 1;
				try {
					formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_hoopla", primaryFormat, identifier));
				} catch (Exception e) {
					logger.warn("Could not translate format boost for " + primaryFormat + " create translation map format_boost_hoopla");
				}
				hooplaRecord.setFormatBoost(formatBoost);
				if (rawResponse.optBoolean("isAbridged") || rawResponse.optBoolean("abridged")) {
					hooplaRecord.setEdition("Abridged");
				} else {
					hooplaRecord.setEdition("Unabridged");
				}
				if (rawResponse.has("artists")) {
					JSONArray artists = rawResponse.getJSONArray("artists");
					HashSet<String> artistsToAdd = new HashSet<>();
					HashSet<String> artistsWithRoleToAdd = new HashSet<>();
					for (int i = 0; i < artists.length(); i++) {
						JSONObject curArtist = artists.getJSONObject(i);
						String artistName = AspenStringUtils.swapFirstLastNames(curArtist.getString("name"));
						artistsToAdd.add(artistName);
						artistsWithRoleToAdd.add(artistName + "|" + StringUtils.capitalize(curArtist.getString("relationship").toLowerCase()));
					}
					groupedWork.addAuthor2(artistsToAdd);
					groupedWork.addAuthor2Role(artistsWithRoleToAdd);
					groupedWork.addKeywords(artistsToAdd);
				}
				JSONArray genres = rawResponse.has("genres") ? rawResponse.getJSONArray("genres") : new JSONArray();
				HashSet<String> genresToAdd = new HashSet<>();
				HashSet<String> topicsToAdd = new HashSet<>();
				for (int i = 0; i < genres.length(); i++) {
					String genre = genres.getString(i);

					genresToAdd.add(genre);
					topicsToAdd.add(genre);
				}
				groupedWork.addGenre(genresToAdd);
				groupedWork.addGenreFacet(genresToAdd);
				groupedWork.addTopicFacet(topicsToAdd);
				groupedWork.addTopic(topicsToAdd);
				HashMap<String, Integer> literaryForm = new HashMap<>();
				HashMap<String, Integer> literaryFormFull = new HashMap<>();
				if (rawResponse.has("isFiction") || rawResponse.has("fiction")){
					if (rawResponse.optBoolean("isFiction", rawResponse.optBoolean("fiction", false))){
						Util.addToMapWithCount(literaryForm, "Fiction");
						Util.addToMapWithCount(literaryFormFull, "Fiction");
						if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is fiction based on Hoopla record", 2);}
					}else{
						Util.addToMapWithCount(literaryForm, "Non Fiction");
						Util.addToMapWithCount(literaryFormFull, "Non Fiction");
						if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Literary Form is non fiction based on Hoopla record", 2);}
					}
				}
				if (!literaryForm.isEmpty()){
					groupedWork.addLiteraryForms(literaryForm);
				}
				if (!literaryFormFull.isEmpty()){
					groupedWork.addLiteraryFormsFull(literaryFormFull);
				}

				String publisher = rawResponse.getString("publisher");
				groupedWork.addPublisher(publisher);
				//publication date
				String releaseYear = rawResponse.optString("releaseYear", rawResponse.optString("year", ""));

				groupedWork.addPublicationDate(releaseYear);
				//physical description
				if (primaryFormat.equals("eAudiobook") && rawResponse.has("duration")) {
					int duration = AspenStringUtils.extractTotalMinutes(rawResponse.getString("duration"));
					hooplaRecord.setDuration(duration);
					Set<Integer> durationSet = new HashSet<>();
					durationSet.add(duration);
					groupedWork.addDuration(durationSet);
				}

				//Description
				if (rawResponse.has("synopsis")) {
					String description = rawResponse.getString("synopsis");
					groupedWork.addDescription(description, formatCategory);
				}

				String isbn = rawResponse.getString("isbn");
				groupedWork.addIsbn(isbn, primaryFormat);

				String upc = rawResponse.getString("upc");
				groupedWork.addUpc(upc);

				boolean abridged = productRS.getBoolean("abridged");
				boolean pa = productRS.getBoolean("pa");
				boolean profanity = productRS.getBoolean("profanity");
				String rating = productRS.getString("rating");

				ItemInfo baseItemInfo = new ItemInfo();
				baseItemInfo.setIsEContent(true);
				baseItemInfo.seteContentUrl(rawResponse.getString("url"));
				baseItemInfo.setShelfLocation("Online Hoopla Collection");
				baseItemInfo.setDetailedLocation("Online Hoopla Collection");
				baseItemInfo.setCallNumber("Online Hoopla");
				baseItemInfo.setSortableCallNumber("Online Hoopla");
				baseItemInfo.setFormat(primaryFormat);
				baseItemInfo.setFormatCategory(formatCategory);
				baseItemInfo.setInLibraryUseOnly(false);
				Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
				baseItemInfo.setDateAdded(dateAdded);

				ItemInfo instantItemInfo = null;
				boolean instantItemHasScopes = false;
				ItemInfo itemInfo;
				for (Long scopeLibraryId : entitlementsByScope.keySet()) {
					String hooplaType = entitlementsByScope.get(scopeLibraryId);
					if (hooplaType == null){
						continue;
					}
					if (hooplaType.equalsIgnoreCase("Flex")){
						itemInfo = new ItemInfo();
						itemInfo.copyFrom(baseItemInfo);
						itemInfo.seteContentSource("Hoopla");
						itemInfo.setItemIdentifier(identifier + ":" + scopeLibraryId);
						itemInfo.seteContentSubSource("Flex");
						ResultSet flexAvailabilityRS = null;
						try {
							getFlexAvailabilityStmt.setLong(1, hooplaId);
							getFlexAvailabilityStmt.setLong(2, scopeLibraryId);
							flexAvailabilityRS = getFlexAvailabilityStmt.executeQuery();
							if (flexAvailabilityRS.next()){
								int totalCopies = flexAvailabilityRS.getInt("totalCopies");
								int availableCopies = flexAvailabilityRS.getInt("availableCopies");
								int holdsQueueSize = flexAvailabilityRS.getInt("holdsQueueSize");
								itemInfo.setNumCopies(totalCopies);
								itemInfo.setAvailable(availableCopies > 0);

								if (availableCopies > 0){
									itemInfo.setDetailedStatus("Available Online");
									itemInfo.setGroupedStatus("Available Online");
									itemInfo.setHoldable(false);
								}else{
									itemInfo.setDetailedStatus("Checked Out");
									itemInfo.setGroupedStatus("Checked Out");
									itemInfo.setHoldable(true);
								}
							}
						} catch (SQLException e) {
							logger.error("Error getting Flex availability for title " + hooplaId + " for library " + scopeLibraryId + ")", e);
						} finally {
							if (flexAvailabilityRS != null) {
								flexAvailabilityRS.close();
							}
						}
					}else{
						if (instantItemInfo == null){
							instantItemInfo = new ItemInfo();
							instantItemInfo.copyFrom(baseItemInfo);
							instantItemInfo.seteContentSource("Hoopla");
							instantItemInfo.setItemIdentifier(identifier);
							//Hoopla instant is always 1 copy unlimited use
							instantItemInfo.seteContentSubSource("Instant");
							instantItemInfo.setNumCopies(1);
							instantItemInfo.setAvailable(true);
							instantItemInfo.setDetailedStatus("Available Online");
							instantItemInfo.setGroupedStatus("Available Online");
							instantItemInfo.setHoldable(false);
						}
						itemInfo = instantItemInfo;
					}

					boolean scopeAddedForLibrary = false;
					for (Scope scope : indexer.getScopes()) {
						boolean okToAdd;
						Long curScopeLibraryId = scope.getLibraryId();
						if (curScopeLibraryId == null || !curScopeLibraryId.equals(scopeLibraryId)){
							continue;
						}
						HooplaScope hooplaScope = scope.getHooplaScope();
						if (hooplaScope != null){
							okToAdd = hooplaScope.isOkToAdd2(identifier, format, price, abridged, pa, profanity, isAdult, isTeen, isKids, rating, genresToAdd, logger);
						} else {
							okToAdd = false;
						}
						if (okToAdd) {
							ScopingInfo scopingInfo = itemInfo.addScope(scope);
							groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);
							scopingInfo.setLibraryOwned(true);
							scopingInfo.setLocallyOwned(true);
							scopeAddedForLibrary = true;
						}
					}
					if (hooplaType.equalsIgnoreCase("Flex")) {
						if (scopeAddedForLibrary) {
							hooplaRecord.addItem(itemInfo);
						}
					}else{
						if (scopeAddedForLibrary) {
							instantItemHasScopes = true;
						}
					}
				}
				if (instantItemInfo != null && instantItemHasScopes) {
					hooplaRecord.addItem(instantItemInfo);
				}
			}
			productRS.close();
		}catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing Hoopla record " + identifier + " grouped work " + groupedWork.getId(), e);
		} catch (JSONException e) {
			logEntry.incErrors("Error parsing raw data for Hoopla record " + identifier, e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for Hoopla title " + identifier, e);
		}
	}

	private String fixHooplaData(long id) throws SQLException{
		doubleDecodeRawResponseStmt.setLong(1, id);
		ResultSet doubleDecodeRawResponseRS = doubleDecodeRawResponseStmt.executeQuery();
		if (doubleDecodeRawResponseRS.next()){
			String rawResponseString = doubleDecodeRawResponseRS.getString("rawResponse");
			if (rawResponseString.charAt(0) == '{' && rawResponseString.charAt(rawResponseString.length() -1) == '}'){
				updateRawResponseStmt.setString(1, rawResponseString);
				updateRawResponseStmt.setLong(2, id);
				updateRawResponseStmt.executeUpdate();
				return rawResponseString;
			}
		}
		doubleDecodeRawResponseRS.close();

		return null;
	}

	private HashMap<Long, String> loadEntitlementsForTitle(long hooplaId) throws SQLException {
		HashMap<Long, String> entitlementsByScope = new HashMap<>();
		getEntitlementsByHooplaIdStmt.setLong(1, hooplaId);
		try (ResultSet entitlementsByHooplaIdRS = getEntitlementsByHooplaIdStmt.executeQuery()) {
			while (entitlementsByHooplaIdRS.next()) {
				long scopeLibraryId = entitlementsByHooplaIdRS.getLong("scopeLibraryId");
				String hooplaType = entitlementsByHooplaIdRS.getString("hooplaType");
				entitlementsByScope.put(scopeLibraryId, hooplaType);
			}
		}
		return entitlementsByScope;
	}

}
