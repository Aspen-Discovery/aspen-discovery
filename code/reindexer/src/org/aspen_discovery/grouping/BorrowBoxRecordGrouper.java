package org.aspen_discovery.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Locale;

public class BorrowBoxRecordGrouper extends RecordGroupingProcessor {
	private PreparedStatement getBorrowBoxProductInfoStmt;
	private PreparedStatement getProductMetadataStmt;

	public BorrowBoxRecordGrouper(Connection dbConnection, String serverName, BaseIndexingLogEntry logEntry, Logger logger) {
		super(dbConnection, serverName, logEntry, logger);

		try {
			getBorrowBoxProductInfoStmt = dbConnection.prepareStatement("SELECT id, mediaType, title, subtitle, series, primaryCreatorName from borrowbox_api_products WHERE borrowboxId = ?");
			getProductMetadataStmt = dbConnection.prepareStatement("SELECT UNCOMPRESS(rawData) as rawData from borrowbox_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logEntry.incErrors("Unable to setup BorrowBox statements", e);
		}
	}

	public String processBorrowBoxRecord(String borrowboxId) {
		try {
			getBorrowBoxProductInfoStmt.setString(1, borrowboxId);
			ResultSet borrowBoxRecordRS = getBorrowBoxProductInfoStmt.executeQuery();
			if (!borrowBoxRecordRS.next()) {
				borrowBoxRecordRS.close();
				return null;
			}
			long id = borrowBoxRecordRS.getLong("id");
			String mediaType = borrowBoxRecordRS.getString("mediaType");
			String title = borrowBoxRecordRS.getString("title");
			String subtitle = borrowBoxRecordRS.getString("subtitle");
			String series = borrowBoxRecordRS.getString("series");
			String author = borrowBoxRecordRS.getString("primaryCreatorName");
			borrowBoxRecordRS.close();

			String languageFromMetadata = loadLanguageFromMetadata(id, borrowboxId);
			String primaryLanguage = languageFromMetadata != null ? languageFromMetadata : "eng";
			return processBorrowBoxRecord(borrowboxId, title, subtitle, series, author, mediaType, primaryLanguage);
		} catch (SQLException e) {
			logEntry.incErrors("Error getting information about BorrowBox record for grouping", e);
		}
		return null;
	}

	private String loadLanguageFromMetadata(long productId, String borrowboxId) throws SQLException {
		getProductMetadataStmt.setLong(1, productId);
		ResultSet metadataRS = getProductMetadataStmt.executeQuery();
		String rawDataStr = metadataRS.next() ? metadataRS.getString("rawData") : null;
		metadataRS.close();
		boolean hasRawData = rawDataStr != null && !rawDataStr.isEmpty();
		if (!hasRawData) {
			return null;
		}
		try {
			JSONObject productMetadata = new JSONObject(rawDataStr);
			if (!productMetadata.has("languageDetails")) {
				return null;
			}
			JSONArray languagesFromMetadata = productMetadata.getJSONArray("languageDetails");
			if (languagesFromMetadata.length() > 1) {
				return "mul";
			}
			if (languagesFromMetadata.length() == 0) {
				return null;
			}
			String languageCode = languagesFromMetadata.getJSONObject(0).getString("code");
			return translateValue("two_to_three_character_language_codes", languageCode.toLowerCase());
		} catch (JSONException e) {
			logEntry.incErrors("Error loading raw data for BorrowBox MetaData for record " + borrowboxId, e);
			return null;
		}
	}

	private String processBorrowBoxRecord(String borrowboxId, String title, String subtitle, String series, String author, String mediaType, String primaryLanguage) {
		RecordIdentifier primaryIdentifier = new RecordIdentifier("borrowbox", borrowboxId);

		boolean hasSubtitle = subtitle != null && !subtitle.isEmpty();
		boolean hasSeries = series != null && !series.isEmpty();
		boolean subtitleRepeatsSeries = hasSubtitle && hasSeries && subtitle.toLowerCase(Locale.ROOT).contains(series.toLowerCase());
		if (subtitleRepeatsSeries) {
			subtitle = "";
		}

		return processRecord(primaryIdentifier, title, subtitle, author, mediaType, primaryLanguage, true);
	}
}
