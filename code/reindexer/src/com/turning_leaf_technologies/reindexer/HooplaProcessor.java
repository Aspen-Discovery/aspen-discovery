package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.HooplaScope;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashSet;

class HooplaProcessor {
	private GroupedWorkIndexer indexer;
	private Logger logger;

	private PreparedStatement getProductInfoStmt;

	HooplaProcessor(GroupedWorkIndexer indexer, Connection dbConn, Logger logger) {
		this.indexer = indexer;
		this.logger = logger;

		try {
			getProductInfoStmt = dbConn.prepareStatement("SELECT * from hoopla_export where hooplaId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Error setting up hoopla processor", e);
		}
	}

	void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()) {
				//Make sure the record isn't deleted
				if (!productRS.getBoolean("active")){
					logger.debug("Hoopla product " + identifier + " is inactive, skipping");
					return;
				}
				String kind = productRS.getString("kind");
				float price = productRS.getFloat("price");

				RecordInfo hooplaRecord = groupedWork.addRelatedRecord("hoopla", identifier);
				hooplaRecord.setRecordIdentifier("hoopla", identifier);

				String title = productRS.getString("title");
				String formatCategory;
				String primaryFormat;
				switch (kind) {
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
					default:
						logger.error("Unhandled hoopla kind " + kind);
						formatCategory = kind;
						primaryFormat = kind;
						break;
				}

				hooplaRecord.addFormat(primaryFormat);
				hooplaRecord.addFormatCategory(formatCategory);

				JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));

				groupedWork.setTitle(title, title, title, primaryFormat);

				String primaryAuthor = "";
				if (rawResponse.has("artist")){
					primaryAuthor = rawResponse.getString("artist");
					primaryAuthor = StringUtils.swapFirstLastNames(primaryAuthor);
				}else if (rawResponse.has("publisher")){
					primaryAuthor = rawResponse.getString("publisher");
				}
				groupedWork.setAuthor(primaryAuthor);
				groupedWork.setAuthAuthor(primaryAuthor);
				groupedWork.setAuthorDisplay(primaryAuthor);

				if (rawResponse.has("series")){
					String series = rawResponse.getString("series");
					groupedWork.addSeries(series);
					String volume = "";
					if (rawResponse.has("episode")){
						volume = rawResponse.getString("episode");
					}
					groupedWork.addSeriesWithVolume(series, volume);
				}

				boolean children = rawResponse.getBoolean("children");
				if (children){
					groupedWork.addTargetAudience("Juvenile");
					groupedWork.addTargetAudienceFull("Juvenile");
				}else{
					if (rawResponse.has("rating")){
						String rating = rawResponse.getString("rating");
						if (rating.equals("TVMA") || rating.equals("M") || rating.equals("NC17")){
							groupedWork.addTargetAudience("Adult");
							groupedWork.addTargetAudienceFull("Adult");
						}else {
							groupedWork.addTargetAudience("Young Adult");
							groupedWork.addTargetAudienceFull("Adolescent (14-17)");
							groupedWork.addTargetAudience("Adult");
							groupedWork.addTargetAudienceFull("Adult");
						}
					}else{
						groupedWork.addTargetAudience("Adult");
						groupedWork.addTargetAudienceFull("Adult");
					}
				}

				String language = rawResponse.getString("language");
				language = org.apache.commons.lang3.StringUtils.capitalize(language.toLowerCase());
				hooplaRecord.setPrimaryLanguage(language);
				long formatBoost = 1;
				try {
					formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_hoopla", primaryFormat, identifier));
				} catch (Exception e) {
					logger.warn("Could not translate format boost for " + primaryFormat + " create translation map format_boost_hoopla");
				}
				hooplaRecord.setFormatBoost(formatBoost);
				if (rawResponse.has("artists")) {
					JSONArray artists = rawResponse.getJSONArray("artists");
					HashSet<String> artistsToAdd = new HashSet<>();
					HashSet<String> artistsWithRoleToAdd = new HashSet<>();
					for (int i = 0; i < artists.length(); i++) {
						JSONObject curArtist = artists.getJSONObject(i);
						String artistName = StringUtils.swapFirstLastNames(curArtist.getString("name"));
						artistsToAdd.add(artistName);
						artistsWithRoleToAdd.add(artistName + "|" + org.apache.commons.lang3.StringUtils.capitalize(curArtist.getString("relationship").toLowerCase()));
					}
					groupedWork.addAuthor2(artistsToAdd);
					groupedWork.addAuthor2Role(artistsWithRoleToAdd);
				}

				JSONArray genres = rawResponse.getJSONArray("genres");
				HashSet<String> genresToAdd = new HashSet<>();
//				HashMap<String, Integer> literaryForm = new HashMap<>();
//				HashMap<String, Integer> literaryFormFull = new HashMap<>();
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

//				boolean isFiction = productRS.getBoolean("isFiction");
//				if (!isFiction){
//					Util.addToMapWithCount(literaryForm, "Non Fiction");
//					Util.addToMapWithCount(literaryFormFull, "Non Fiction");
//				}else{
//					Util.addToMapWithCount(literaryForm, "Fiction");
//					Util.addToMapWithCount(literaryFormFull, "Fiction");
//				}
//				if (literaryForm.size() > 0){
//					groupedWork.addLiteraryForms(literaryForm);
//				}
//				if (literaryFormFull.size() > 0){
//					groupedWork.addLiteraryFormsFull(literaryFormFull);
//				}
				String publisher = rawResponse.getString("publisher");
				groupedWork.addPublisher(publisher);
				//publication date
				String releaseYear = rawResponse.getString("year");
				groupedWork.addPublicationDate(releaseYear);
				//physical description
				if (rawResponse.has("duration")){
					groupedWork.addPhysical(rawResponse.getString("duration"));
				}

				//Description
				if (rawResponse.has("synopsis")) {
					String description = rawResponse.getString("synopsis");
					groupedWork.addDescription(description, primaryFormat);
				}

				String isbn = rawResponse.getString("isbn");
				groupedWork.addIsbn(isbn, primaryFormat);

				String upc = rawResponse.getString("upc");
				groupedWork.addUpc(upc);

				ItemInfo itemInfo = new ItemInfo();
				itemInfo.seteContentSource("Hoopla");
				itemInfo.setIsEContent(true);
				itemInfo.seteContentUrl(rawResponse.getString("url"));
				itemInfo.setShelfLocation("Online Hoopla Collection");
				itemInfo.setCallNumber("Online Hoopla");
				itemInfo.setSortableCallNumber("Online Hoopla");
				itemInfo.setFormat(primaryFormat);
				itemInfo.setFormatCategory(formatCategory);
				//Hoopla is always 1 copy unlimited use
				itemInfo.setNumCopies(1);

				Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
				itemInfo.setDateAdded(dateAdded);

				itemInfo.setDetailedStatus("Available Online");
				boolean abridged = productRS.getBoolean("abridged");
				boolean pa = productRS.getBoolean("pa");
				boolean profanity = productRS.getBoolean("profanity");
				String rating = productRS.getString("rating");

				for (Scope scope : indexer.getScopes()) {
					boolean okToAdd = true;
					HooplaScope hooplaScope = scope.getHooplaScope();
					if (hooplaScope != null){
						//Filter by kind and price
						switch (kind){
							case "MOVIE":
								okToAdd = (hooplaScope.isIncludeMovies() && price <= hooplaScope.getMaxCostPerCheckoutMovies());
								break;
							case "TELEVISION":
								okToAdd = (hooplaScope.isIncludeTelevision() && price <= hooplaScope.getMaxCostPerCheckoutTelevision());
								break;
							case "AUDIOBOOK":
								okToAdd = (hooplaScope.isIncludeEAudiobook() && price <= hooplaScope.getMaxCostPerCheckoutEAudiobook());
								break;
							case "EBOOK":
								okToAdd = (hooplaScope.isIncludeEBooks() && price <= hooplaScope.getMaxCostPerCheckoutEBooks());
								break;
							case "COMIC":
								okToAdd = (hooplaScope.isIncludeEComics() && price <= hooplaScope.getMaxCostPerCheckoutEComics());
								break;
							case "MUSIC":
								okToAdd = (hooplaScope.isIncludeMusic() && price <= hooplaScope.getMaxCostPerCheckoutMusic());
								break;
							default:
								logger.error("Unknown kind " + kind);
						}
						if (okToAdd && hooplaScope.isExcludeAbridged() && abridged){
							okToAdd = false;
						}
						if (okToAdd && hooplaScope.isExcludeParentalAdvisory() && pa){
							okToAdd = false;
						}
						if (okToAdd && hooplaScope.isExcludeProfanity() && profanity){
							okToAdd = false;
						}
						if (okToAdd && hooplaScope.isRestrictToChildrensMaterial() && !children){
							okToAdd = false;
						}
						if (okToAdd && hooplaScope.isRatingExcluded(rating)){
							okToAdd = false;
						}
					}else{
						okToAdd = false;
					}
					if (okToAdd) {
						ScopingInfo scopingInfo = itemInfo.addScope(scope);
						scopingInfo.setAvailable(true);
						scopingInfo.setStatus("Available Online");
						scopingInfo.setGroupedStatus("Available Online");
						scopingInfo.setHoldable(false);
						scopingInfo.setLibraryOwned(true);
						scopingInfo.setLocallyOwned(true);
						scopingInfo.setInLibraryUseOnly(false);
					}
				}

				hooplaRecord.addItem(itemInfo);

			}
			productRS.close();
		}catch (NullPointerException e) {
			logger.error("Null pointer exception processing Hoopla record ", e);
		} catch (JSONException e) {
			logger.error("Error parsing raw data for Hoopla", e);
		} catch (SQLException e) {
			logger.error("Error loading information from Database for Hoopla title", e);
		}
	}

}
