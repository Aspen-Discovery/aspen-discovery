package org.aspen_discovery.reindexer;

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
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Locale;
import java.util.function.BinaryOperator;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class OmekaProcessor {
	private static final Pattern YEAR_PATTERN = Pattern.compile("(\\d{4})");

	private final GroupedWorkIndexer indexer;
	private final Logger logger;

	private PreparedStatement getProductInfoStmt;
	private final HashMap<Long, OmekaSettingInfo> allSettings = new HashMap<>();

	private static class OmekaSettingInfo {
		long id;
		String name;
		String baseUrl;
		String siteSlug;
	}

	OmekaProcessor(GroupedWorkIndexer indexer, Connection dbConn, Logger logger) {
		this.indexer = indexer;
		this.logger = logger;

		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT id, settingId, omekaId, title, mediaType, thumbnailUrl, itemSetIds, dateFirstDetected, deleted, UNCOMPRESS(rawResponse) as rawResponse from omeka_title where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getSettingsStmt = dbConn.prepareStatement("SELECT id, name, baseUrl, siteSlug from omeka_settings", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet settingsRS = getSettingsStmt.executeQuery();
			while (settingsRS.next()) {
				OmekaSettingInfo settingInfo = new OmekaSettingInfo();
				settingInfo.id = settingsRS.getLong("id");
				settingInfo.name = settingsRS.getString("name");
				settingInfo.baseUrl = settingsRS.getString("baseUrl");
				settingInfo.siteSlug = settingsRS.getString("siteSlug");
				if (settingInfo.baseUrl != null && settingInfo.baseUrl.endsWith("/")) {
					settingInfo.baseUrl = settingInfo.baseUrl.substring(0, settingInfo.baseUrl.length() - 1);
				}
				allSettings.put(settingInfo.id, settingInfo);
			}
		} catch (SQLException e) {
			logger.error("Error setting up Omeka processor", e);
		}
	}

	public static String getFormatForMediaType(String mediaType) {
		if (mediaType == null || mediaType.isEmpty()) {
			return "Web Content";
		}
		if (mediaType.startsWith("image/")) {
			return "Photo";
		}
		if (mediaType.equals("application/pdf")) {
			return "PDF";
		}
		if (mediaType.startsWith("audio/")) {
			return "eAudio";
		}
		if (mediaType.startsWith("video/")) {
			return "eVideo";
		}
		return "Web Content";
	}

	private static String getFormatCategoryForFormat(String format) {
		switch (format) {
			case "PDF":
				return "eBook";
			case "eAudio":
				return "Audio Books";
			case "eVideo":
				return "Movies";
			default:
				return "Other";
		}
	}

	public static String getFirstLiteralValue(JSONObject itemDetails, String property) {
		JSONArray values = itemDetails.optJSONArray(property);
		if (values == null) {
			return null;
		}
		for (int i = 0; i < values.length(); i++) {
			JSONObject value = values.optJSONObject(i);
			if (value == null) {
				continue;
			}
			String literalValue = value.optString("@value", null);
			if (literalValue != null && !literalValue.isEmpty()) {
				return literalValue;
			}
		}
		return null;
	}

	private static HashSet<String> getAllLiteralValues(JSONObject itemDetails, String property) {
		HashSet<String> literalValues = new HashSet<>();
		JSONArray values = itemDetails.optJSONArray(property);
		if (values == null) {
			return literalValues;
		}
		for (int i = 0; i < values.length(); i++) {
			JSONObject value = values.optJSONObject(i);
			if (value == null) {
				continue;
			}
			String literalValue = value.optString("@value", null);
			if (literalValue != null && !literalValue.isEmpty()) {
				literalValues.add(literalValue);
			}
		}
		return literalValues;
	}

	public static String formatAuthorName(String author) {
		boolean alreadyLastNameFirst = author.contains(",");
		if (alreadyLastNameFirst) {
			return author;
		}
		return AspenStringUtils.swapFirstLastNames(author);
	}

	public static String getThreeLetterLanguageCode(String languageValue, BinaryOperator<String> translator) {
		String languageCode = languageValue.toLowerCase(Locale.ROOT);
		if (languageCode.length() == 2) {
			return translator.apply("two_to_three_character_language_codes", languageCode);
		}
		if (languageCode.length() == 3) {
			return languageCode;
		}
		return translator.apply("language_to_three_letter_code", languageCode);
	}

	private String getLanguageForItem(JSONObject itemDetails, String identifier) {
		String languageValue = getFirstLiteralValue(itemDetails, "dcterms:language");
		if (languageValue == null) {
			return "English";
		}
		String threeLetterLanguage = getThreeLetterLanguageCode(languageValue, (mapName, value) -> indexer.translateSystemValue(mapName, value, identifier));
		return indexer.translateSystemValue("language", threeLetterLanguage, identifier);
	}

	private static HashSet<String> getItemSetIdsForTitle(String itemSetIds) {
		HashSet<String> titleItemSets = new HashSet<>();
		if (itemSetIds == null || itemSetIds.isEmpty()) {
			return titleItemSets;
		}
		for (String itemSetId : itemSetIds.split(",")) {
			String trimmedId = itemSetId.trim();
			if (!trimmedId.isEmpty()) {
				titleItemSets.add(trimmedId);
			}
		}
		return titleItemSets;
	}

	void processRecord(AbstractGroupedWorkSolr groupedWork, String identifier, BaseIndexingLogEntry logEntry) {
		try {
			if (!AspenStringUtils.isNumeric(identifier)) {
				logEntry.incErrors("Invalid Omeka identifier " + identifier);
				return;
			}
			getProductInfoStmt.setLong(1, Long.parseLong(identifier));
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (!productRS.next()) {
				productRS.close();
				return;
			}

			boolean deleted = productRS.getBoolean("deleted");
			if (deleted) {
				productRS.close();
				return;
			}

			byte[] rawResponseBytes = productRS.getBytes("rawResponse");
			if (rawResponseBytes == null) {
				logEntry.incInvalidRecords(identifier);
				productRS.close();
				return;
			}

			long settingId = productRS.getLong("settingId");
			OmekaSettingInfo settingInfo = allSettings.get(settingId);
			if (settingInfo == null) {
				logEntry.incErrors("Could not find Omeka setting " + settingId + " for record " + identifier);
				productRS.close();
				return;
			}

			JSONObject itemDetails = new JSONObject(new String(rawResponseBytes, StandardCharsets.UTF_8));

			RecordInfo omekaRecord = groupedWork.addRelatedRecord("omeka", identifier);
			omekaRecord.setRecordIdentifier("omeka", identifier);

			String format = getFormatForMediaType(productRS.getString("mediaType"));
			String formatCategory = getFormatCategoryForFormat(format);
			omekaRecord.addFormat(format);
			omekaRecord.addFormatCategory(formatCategory);

			long formatBoost = 1;
			try {
				formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_omeka", format, identifier));
			} catch (Exception e) {
				logger.warn("Could not translate format boost for " + format + " create translation map format_boost_omeka");
			}
			omekaRecord.setFormatBoost(formatBoost);

			String title = getFirstLiteralValue(itemDetails, "dcterms:title");
			if (title == null) {
				title = productRS.getString("title");
			}
			groupedWork.setTitle(title, "", title, formatCategory, false, omekaRecord);
			groupedWork.addFullTitle(title);

			HashSet<String> creators = getAllLiteralValues(itemDetails, "dcterms:creator");
			String primaryAuthor = "";
			String firstCreator = getFirstLiteralValue(itemDetails, "dcterms:creator");
			if (firstCreator != null) {
				primaryAuthor = formatAuthorName(firstCreator);
			}
			groupedWork.setAuthor(primaryAuthor);
			groupedWork.setAuthAuthor(primaryAuthor);
			groupedWork.setAuthorDisplay(primaryAuthor, formatCategory, omekaRecord);

			HashSet<String> additionalAuthors = new HashSet<>();
			for (String creator : creators) {
				String formattedCreator = formatAuthorName(creator);
				if (!formattedCreator.equals(primaryAuthor)) {
					additionalAuthors.add(formattedCreator);
				}
			}
			HashSet<String> contributors = getAllLiteralValues(itemDetails, "dcterms:contributor");
			for (String contributor : contributors) {
				additionalAuthors.add(formatAuthorName(contributor));
			}
			if (!additionalAuthors.isEmpty()) {
				groupedWork.addAuthor2(additionalAuthors);
				groupedWork.addKeywords(additionalAuthors);
			}

			String language = getLanguageForItem(itemDetails, identifier);
			omekaRecord.setPrimaryLanguage(language);
			groupedWork.addLanguage(language, omekaRecord);

			String publisher = getFirstLiteralValue(itemDetails, "dcterms:publisher");
			if (publisher != null) {
				groupedWork.addPublisher(publisher);
				omekaRecord.setPublisher(publisher);
			}

			String dateValue = getFirstLiteralValue(itemDetails, "dcterms:date");
			if (dateValue != null) {
				Matcher yearMatcher = YEAR_PATTERN.matcher(dateValue);
				if (yearMatcher.find()) {
					String publicationYear = yearMatcher.group(1);
					groupedWork.addPublicationDate(publicationYear);
					omekaRecord.setPublicationDate(publicationYear);
				}
			}

			String description = getFirstLiteralValue(itemDetails, "dcterms:description");
			if (description != null) {
				groupedWork.addDescription(description, formatCategory);
			}

			HashSet<String> subjects = getAllLiteralValues(itemDetails, "dcterms:subject");
			if (!subjects.isEmpty()) {
				groupedWork.addTopic(subjects);
				groupedWork.addTopicFacet(subjects);
				groupedWork.addKeywords(subjects);
			}

			groupedWork.addTargetAudience("Unknown", omekaRecord);
			groupedWork.addTargetAudienceFull("Unknown", omekaRecord);

			long omekaId = productRS.getLong("omekaId");
			String publicUrl = settingInfo.baseUrl + "/s/" + settingInfo.siteSlug + "/item/" + omekaId;

			ItemInfo itemInfo = new ItemInfo();
			itemInfo.setItemIdentifier(identifier + "_" + settingId);
			itemInfo.seteContentSource(settingInfo.name);
			itemInfo.setIsEContent(true);
			itemInfo.seteContentUrl(publicUrl);
			itemInfo.setShelfLocation("Online " + settingInfo.name);
			itemInfo.setDetailedLocation("Online " + settingInfo.name);
			itemInfo.setCallNumber("Online " + settingInfo.name);
			itemInfo.setSortableCallNumber("Online " + settingInfo.name);
			itemInfo.setFormat(format);
			itemInfo.setFormatCategory(formatCategory);
			itemInfo.setNumCopies(1);
			itemInfo.setAvailable(true);
			itemInfo.setDetailedStatus("Available Online");
			itemInfo.setGroupedStatus("Available Online");
			itemInfo.setHoldable(false);
			itemInfo.setInLibraryUseOnly(false);

			Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
			itemInfo.setDateAdded(dateAdded);

			HashSet<String> titleItemSets = getItemSetIdsForTitle(productRS.getString("itemSetIds"));

			for (Scope scope : indexer.getScopes().values()) {
				if (!scope.includesOmekaItem(settingId, titleItemSets)) {
					continue;
				}
				ScopingInfo scopingInfo = itemInfo.addScope(scope);
				groupedWork.addScopingInfo(scope.getScopeName(), scopingInfo);
				scopingInfo.setLibraryOwned(true);
				scopingInfo.setLocallyOwned(true);
			}

			omekaRecord.addItem(itemInfo);

			productRS.close();
		} catch (NullPointerException e) {
			logEntry.incErrors("Null pointer exception processing Omeka record " + identifier + " grouped work " + groupedWork.getId(), e);
		} catch (JSONException e) {
			logEntry.incErrors("Error parsing raw data for Omeka record " + identifier, e);
		} catch (SQLException e) {
			logEntry.incErrors("Error loading information from Database for Omeka title " + identifier, e);
		}
	}
}
