package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.BorrowBoxScope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;

class BorrowBoxProcessor {
	private final GroupedWorkIndexer indexer;
	private final Logger logger;
	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getProductMetadataStmt;
	private PreparedStatement getProductAvailabilityStmt;

	BorrowBoxProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection dbConn, Logger logger) {
		this.indexer = groupedWorkIndexer;
		this.logger = logger;
		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT * from borrowbox_api_products where borrowboxId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getProductMetadataStmt = dbConn.prepareStatement("SELECT id, productId, checksum, publisher, releaseDate, summary, cover, UNCOMPRESS(rawData) as rawData from borrowbox_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getProductAvailabilityStmt = dbConn.prepareStatement("SELECT * from borrowbox_api_product_availability where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up BorrowBox processor", e);
		}
	}

	void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (!productRS.next()) {
				productRS.close();
				return;
			}
			if (productRS.getInt("deleted") == 1) {
				logger.info("Not processing deleted BorrowBox product {} - {}", productRS.getString("title"), identifier);
				indexer.borrowBoxRecordsSkipped.add(identifier);
				productRS.close();
				return;
			}
			indexProduct(groupedWork, identifier, productRS, logEntry);
			productRS.close();
		} catch (JSONException e) {
			logEntry.incErrors("Error loading information from JSON for BorrowBox title", e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for BorrowBox title", e);
		}
	}

	private void indexProduct(AbstractGroupedWorkSolr groupedWork, String identifier, ResultSet productRS, BaseIndexingLogEntry logEntry) throws SQLException {
		long productId = productRS.getLong("id");
		String title = productRS.getString("title");
		RecordInfo borrowBoxRecord = groupedWork.addRelatedRecord("borrowbox", identifier);
		borrowBoxRecord.setRecordIdentifier("borrowbox", identifier);

		String subtitle = productRS.getString("subtitle");
		String series = productRS.getString("series");
		int seriesNumber = productRS.getInt("seriesNumber");
		if (subtitle == null) {
			subtitle = "";
		}
		String mediaType = productRS.getString("mediaType");
		String formatCategory;
		String primaryFormat;
		switch (mediaType) {
			case "eAudiobook":
				formatCategory = "Audio Books";
				primaryFormat = "eAudiobook";
				break;
			case "eMagazine":
				formatCategory = "eBook";
				primaryFormat = "eMagazine";
				break;
			case "eBook":
			default:
				formatCategory = "eBook";
				primaryFormat = "eBook";
				break;
		}
		if (groupedWork.isDebugEnabled()) {
			groupedWork.addDebugMessage("Format is " + primaryFormat + " based on a mediaType of " + mediaType, 2);
		}

		String fullTitle = title + " " + subtitle;
		fullTitle = fullTitle.trim();
		groupedWork.setTitle(title, subtitle, title, formatCategory, false, borrowBoxRecord);
		groupedWork.addFullTitle(fullTitle);

		String authorName = productRS.getString("primaryCreatorName");
		boolean hasSeries = series != null && !series.isEmpty();
		if (hasSeries) {
			String volume = seriesNumber > 0 ? String.valueOf(seriesNumber) : "";
			groupedWork.addSeriesWithVolume(series, authorName, volume, 2, false);
		}

		groupedWork.setAuthor(authorName);
		groupedWork.setAuthAuthor(authorName);
		groupedWork.setAuthorDisplay(authorName, formatCategory, borrowBoxRecord);

		Date dateAdded = new Date(productRS.getLong("dateAdded") * 1000);
		String isbn13 = productRS.getString("isbn13");

		BorrowBoxMetadataDetails metadata = loadMetadata(groupedWork, borrowBoxRecord, productId, identifier, logEntry);

		boolean hasIsbn = isbn13 != null && !isbn13.isEmpty();
		if (hasIsbn) {
			groupedWork.addIsbn(isbn13, primaryFormat);
		}

		groupedWork.addDescription(metadata.summary, formatCategory);
		groupedWork.addPublisher(metadata.publisher);
		borrowBoxRecord.setPublisher(AspenStringUtils.trimTrailingPunctuation(metadata.publisher));
		borrowBoxRecord.setPrimaryLanguage(metadata.primaryLanguage);
		borrowBoxRecord.setPhysicalDescription("");

		groupedWork.addTargetAudience(metadata.targetAudience, borrowBoxRecord);
		groupedWork.addTargetAudienceFull(metadata.targetAudience, borrowBoxRecord);

		HashMap<String, BorrowBoxAvailabilityInfo> availabilityBySettingSite = loadAvailability(productId);
		setFormatBoost(borrowBoxRecord, primaryFormat, identifier);

		int totalCopiesOwned = addItems(groupedWork, borrowBoxRecord, identifier, primaryFormat, formatCategory, dateAdded, metadata.targetAudience, availabilityBySettingSite);
		groupedWork.addHoldings(totalCopiesOwned);
	}

	private BorrowBoxMetadataDetails loadMetadata(AbstractGroupedWorkSolr groupedWork, RecordInfo borrowBoxRecord, long productId, String identifier, BaseIndexingLogEntry logEntry) throws SQLException {
		BorrowBoxMetadataDetails details = new BorrowBoxMetadataDetails();
		getProductMetadataStmt.setLong(1, productId);
		ResultSet metadataRS = getProductMetadataStmt.executeQuery();
		if (!metadataRS.next()) {
			metadataRS.close();
			return details;
		}
		details.publisher = metadataRS.getString("publisher");
		details.summary = metadataRS.getString("summary");
		long releaseDate = metadataRS.getLong("releaseDate");
		String rawDataString = metadataRS.getString("rawData");
		metadataRS.close();

		boolean hasRawData = rawDataString != null && !rawDataString.isEmpty();
		if (!hasRawData) {
			return details;
		}
		try {
			JSONObject rawData = new JSONObject(rawDataString);
			addAuthors(groupedWork, rawData);
			addLanguages(groupedWork, borrowBoxRecord, rawData, details);
			addGenres(groupedWork, rawData, details);
			addPublicationDate(groupedWork, borrowBoxRecord, releaseDate);
		} catch (JSONException e) {
			logEntry.addNote("Error parsing raw metadata for BorrowBox record " + identifier);
		}
		return details;
	}

	private void addAuthors(AbstractGroupedWorkSolr groupedWork, JSONObject rawData) {
		if (!rawData.has("authors")) {
			return;
		}
		JSONArray authors = rawData.getJSONArray("authors");
		HashSet<String> authorNames = new HashSet<>();
		for (int i = 0; i < authors.length(); i++) {
			authorNames.add(authors.getJSONObject(i).optString("fullName", ""));
		}
		groupedWork.addAuthor2(authorNames);
	}

	private void addLanguages(AbstractGroupedWorkSolr groupedWork, RecordInfo borrowBoxRecord, JSONObject rawData, BorrowBoxMetadataDetails details) {
		if (!rawData.has("languageDetails")) {
			return;
		}
		JSONArray languages = rawData.getJSONArray("languageDetails");
		HashSet<String> languageSet = new HashSet<>();
		for (int i = 0; i < languages.length(); i++) {
			String langName = languages.getJSONObject(i).optString("name", "English");
			languageSet.add(langName);
			if (i == 0) {
				details.primaryLanguage = langName;
			}
		}
		groupedWork.setLanguages(languageSet, borrowBoxRecord);
	}

	private void addGenres(AbstractGroupedWorkSolr groupedWork, JSONObject rawData, BorrowBoxMetadataDetails details) {
		if (!rawData.has("genres")) {
			return;
		}
		JSONArray genres = rawData.getJSONArray("genres");
		HashSet<String> genreSet = new HashSet<>();
		for (int i = 0; i < genres.length(); i++) {
			String genreName = genres.getJSONObject(i).optString("name", "");
			if (genreName.isEmpty()) {
				continue;
			}
			genreSet.add(genreName);
			boolean isJuvenileGenre = genreName.contains("Children") || genreName.contains("Juvenile");
			boolean isYoungAdultGenre = genreName.contains("Young Adult") || genreName.contains("Teen");
			if (isJuvenileGenre) {
				details.targetAudience = "Juvenile";
			} else if (isYoungAdultGenre) {
				details.targetAudience = "Young Adult";
			}
		}
		groupedWork.addGenre(genreSet);
		groupedWork.addGenreFacet(genreSet);
		groupedWork.addTopicFacet(genreSet);
	}

	private void addPublicationDate(AbstractGroupedWorkSolr groupedWork, RecordInfo borrowBoxRecord, long releaseDate) {
		if (releaseDate <= 0) {
			return;
		}
		Calendar cal = Calendar.getInstance();
		cal.setTimeInMillis(releaseDate);
		String publicationDate = String.valueOf(cal.get(Calendar.YEAR));
		groupedWork.addPublicationDate(publicationDate);
		borrowBoxRecord.setPublicationDate(publicationDate);
	}

	private HashMap<String, BorrowBoxAvailabilityInfo> loadAvailability(long productId) throws SQLException {
		getProductAvailabilityStmt.setLong(1, productId);
		ResultSet availabilityRS = getProductAvailabilityStmt.executeQuery();
		HashMap<String, BorrowBoxAvailabilityInfo> availabilityBySettingSite = new HashMap<>();
		while (availabilityRS.next()) {
			long settingId = availabilityRS.getLong("settingId");
			String siteId = availabilityRS.getString("siteId");
			String availStatus = availabilityRS.getString("availabilityStatus");
			Long nextAvailDate = availabilityRS.getLong("nextAvailableDate");
			if (availabilityRS.wasNull()) {
				nextAvailDate = null;
			}
			availabilityBySettingSite.put(settingId + ":" + siteId, new BorrowBoxAvailabilityInfo(siteId, availStatus, nextAvailDate, settingId));
		}
		availabilityRS.close();
		return availabilityBySettingSite;
	}

	private void setFormatBoost(RecordInfo borrowBoxRecord, String primaryFormat, String identifier) {
		long maxFormatBoost = 1;
		try {
			String formatBoostStr = indexer.translateSystemValue("format_boost_borrowbox", primaryFormat.replace(' ', '_'), identifier);
			if (formatBoostStr != null) {
				maxFormatBoost = Long.parseLong(formatBoostStr);
			}
		} catch (Exception e) {
		}
		borrowBoxRecord.setFormatBoost(maxFormatBoost);
	}

	private int addItems(AbstractGroupedWorkSolr groupedWork, RecordInfo borrowBoxRecord, String identifier, String primaryFormat, String formatCategory, Date dateAdded, String targetAudience, HashMap<String, BorrowBoxAvailabilityInfo> availabilityBySettingSite) {
		HashSet<Long> uniqueSettings = new HashSet<>();
		for (BorrowBoxAvailabilityInfo availability : availabilityBySettingSite.values()) {
			uniqueSettings.add(availability.settingId);
		}

		int totalCopiesOwned = 0;
		for (Long settingId : uniqueSettings) {
			ItemInfo itemInfo = new ItemInfo();
			itemInfo.setIsEContent(true);
			itemInfo.setDateAdded(dateAdded);
			itemInfo.setFormat(primaryFormat);
			itemInfo.setFormatCategory(formatCategory);
			itemInfo.setItemIdentifier(identifier + ":" + settingId + ":" + primaryFormat);

			boolean anyAvailable = isAvailableForSetting(availabilityBySettingSite, settingId);
			itemInfo.setAvailable(anyAvailable);
			itemInfo.setHoldable(true);
			itemInfo.setNumCopies(1);
			totalCopiesOwned = Math.max(totalCopiesOwned, 1);

			String status = anyAvailable ? "Available Online" : "Checked Out";
			itemInfo.setDetailedStatus(status);
			itemInfo.setGroupedStatus(status);

			addScopes(groupedWork, itemInfo, settingId, targetAudience);
			borrowBoxRecord.addItem(itemInfo);
		}
		return totalCopiesOwned;
	}

	private boolean isAvailableForSetting(HashMap<String, BorrowBoxAvailabilityInfo> availabilityBySettingSite, long settingId) {
		for (BorrowBoxAvailabilityInfo availability : availabilityBySettingSite.values()) {
			boolean availableForSetting = availability.settingId == settingId && availability.isAvailable();
			if (availableForSetting) {
				return true;
			}
		}
		return false;
	}

	private void addScopes(AbstractGroupedWorkSolr groupedWork, ItemInfo itemInfo, long settingId, String targetAudience) {
		boolean isAdult = targetAudience.equals("Adult");
		boolean isTeen = targetAudience.equals("Young Adult");
		boolean isKids = targetAudience.equals("Juvenile");
		for (Scope scope : indexer.getScopes().values()) {
			if (!scope.isIncludeBorrowBoxCollection(settingId)) {
				continue;
			}
			BorrowBoxScope borrowBoxScope = scope.getBorrowBoxScope(settingId);

			itemInfo.seteContentSource("BorrowBox");
			itemInfo.setShelfLocation(borrowBoxScope.getSettingName());
			itemInfo.setDetailedLocation("BorrowBox " + borrowBoxScope.getSettingName());
			itemInfo.setCallNumber("BorrowBox");
			itemInfo.setSortableCallNumber("BorrowBox");

			boolean includeAsAdult = isAdult && borrowBoxScope.isIncludeAdult();
			boolean includeAsTeen = isTeen && borrowBoxScope.isIncludeTeen();
			boolean includeAsKids = isKids && borrowBoxScope.isIncludeKids();
			boolean okToInclude = includeAsAdult || includeAsTeen || includeAsKids;
			if (!okToInclude) {
				continue;
			}
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

	private static class BorrowBoxMetadataDetails {
		String publisher = "";
		String summary = "";
		String primaryLanguage = "English";
		String targetAudience = "Adult";
	}
}
