package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

class RbdigitalProcessor {
    private GroupedWorkIndexer indexer;
    private Logger logger;

    private PreparedStatement getProductInfoStmt;
    private PreparedStatement getAvailabilityStmt;

    RbdigitalProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection dbConn, Logger logger) {
        this.indexer = groupedWorkIndexer;
        this.logger = logger;

        try {
            getProductInfoStmt = dbConn.prepareStatement("SELECT * from rbdigital_title where rbdigitalId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
            getAvailabilityStmt = dbConn.prepareStatement("SELECT * from rbdigital_availability where rbdigitalId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
        } catch (SQLException e) {
            logger.error("Error setting up overdrive processor", e);
        }
    }

    void processRecord(GroupedWorkSolr groupedWork, String identifier) {
        try {
            getProductInfoStmt.setString(1, identifier);
            ResultSet productRS = getProductInfoStmt.executeQuery();
            if (productRS.next()) {
                //Make sure the record isn't deleted
                if (productRS.getBoolean("deleted")){
                    logger.info("Rbdigital product " + identifier + " was deleted, skipping");
                    return;
                }

                RecordInfo rbdigitalRecord = groupedWork.addRelatedRecord("rbdigital", identifier);
                rbdigitalRecord.setRecordIdentifier("rbdigital", identifier);

                String title = productRS.getString("title");
                String mediaType = productRS.getString("mediaType");
                String formatCategory;
                String primaryFormat;
                switch (mediaType) {
                    case "eAudio":
                        formatCategory = "Audio Books";
                        rbdigitalRecord.addFormatCategory("eBook");
                        primaryFormat = "eAudiobook";
                        break;
                    case "eBook":
                        formatCategory = "eBook";
                        primaryFormat = "eBook";
                        break;
                    case "emagazine":
                        formatCategory = "eBook";
                        primaryFormat = "eMagazine";
                        break;
                    default:
                        logger.warn("Unhandled rbdigital mediaType " + mediaType);
                        formatCategory = mediaType;
                        primaryFormat = mediaType;
                        break;
                }
                rbdigitalRecord.addFormat(primaryFormat);
                rbdigitalRecord.addFormatCategory(formatCategory);

                JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));

                groupedWork.setTitle(title, title, title, primaryFormat);
                if (rawResponse.getBoolean("hasSubtitle")) {
                    String subtitle = rawResponse.getString("subtitle");
                    groupedWork.setSubTitle(subtitle);
                }
                String primaryAuthor = swapFirstLastNames(productRS.getString("primaryAuthor"));
                groupedWork.setAuthor(primaryAuthor);
                groupedWork.setAuthAuthor(primaryAuthor);
                groupedWork.setAuthorDisplay(primaryAuthor);

                int seriesPosition = rawResponse.getInt("seriesPosition");
                if (seriesPosition > 0) {
                    if (rawResponse.has("series")){
                        String series = rawResponse.getJSONObject("series").getString("text");
                        groupedWork.addSeries(series);
                        groupedWork.addSeriesWithVolume(series + "|" + seriesPosition);
                    }else{
                        logger.debug("Record should have series, but does not");
                    }
                }

                String targetAudience = productRS.getString("audience");
                if (targetAudience.contains("Childrens")){
                    groupedWork.addTargetAudience("Juvenile");
                    groupedWork.addTargetAudienceFull("Juvenile");
                }else if (targetAudience.contains("Young Adult")){
                    groupedWork.addTargetAudience("Young Adult");
                    groupedWork.addTargetAudienceFull("Adolescent (14-17)");
                }else if (targetAudience.contains("Beginning Reader")){
                    groupedWork.addTargetAudience("Juvenile");
                    groupedWork.addTargetAudienceFull("Primary (6-8)");
                }else {
                    groupedWork.addTargetAudience("Adult");
                    groupedWork.addTargetAudienceFull("Adult");
                }

                rbdigitalRecord.setPrimaryLanguage(productRS.getString("language"));
                long formatBoost = 1;
                try {
                    formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_rbdigital", primaryFormat, identifier));
                } catch (Exception e) {
                    logger.warn("Could not translate format boost for " + primaryFormat + " create transaltion map format_boost_rbdigital");
                }
                rbdigitalRecord.setFormatBoost(formatBoost);
                if (rawResponse.has("narrators")) {
                    JSONArray narrators = rawResponse.getJSONArray("narrators");
                    HashSet<String> narratorsToAdd = new HashSet<>();
                    HashSet<String> narratorsWithRoleToAdd = new HashSet<>();
                    for (int i = 0; i < narrators.length(); i++) {
                        JSONObject curNarrator = narrators.getJSONObject(i);
                        String narratorName = swapFirstLastNames(curNarrator.getString("text"));
                        narratorsToAdd.add(narratorName);
                        narratorsWithRoleToAdd.add(narratorName + "|" + curNarrator.getString("facet"));
                    }
                    groupedWork.addAuthor2(narratorsToAdd);
                    groupedWork.addAuthor2Role(narratorsWithRoleToAdd);
                }
                if (rawResponse.has("authors")) {
                    JSONArray authors = rawResponse.getJSONArray("authors");
                    HashSet<String> authorsToAdd = new HashSet<>();
                    //Skip the first author since that is the primary author
                    for (int i = 1; i < authors.length(); i++) {
                        JSONObject curNarrator = authors.getJSONObject(i);
                        authorsToAdd.add(swapFirstLastNames(curNarrator.getString("text")));
                    }
                    groupedWork.addAuthor2(authorsToAdd);
                }
                JSONArray genres = rawResponse.getJSONArray("genres");
                HashSet<String> genresToAdd = new HashSet<>();
                HashMap<String, Integer> literaryForm = new HashMap<>();
                HashMap<String, Integer> literaryFormFull = new HashMap<>();
                HashSet<String> topicsToAdd = new HashSet<>();
                for (int i = 0; i < genres.length(); i++) {
                    JSONObject curGenre = genres.getJSONObject(0);
                    String genre = curGenre.getString("text");

                    genresToAdd.add(genre);
                    topicsToAdd.add(genre);
                }
                groupedWork.addGenre(genresToAdd);
                groupedWork.addGenreFacet(genresToAdd);
                groupedWork.addTopicFacet(topicsToAdd);
                groupedWork.addTopic(topicsToAdd);

                boolean isFiction = productRS.getBoolean("isFiction");
                if (!isFiction){
                    Util.addToMapWithCount(literaryForm, "Non Fiction");
                    Util.addToMapWithCount(literaryFormFull, "Non Fiction");
                }else{
                    Util.addToMapWithCount(literaryForm, "Fiction");
                    Util.addToMapWithCount(literaryFormFull, "Fiction");
                }
                if (literaryForm.size() > 0){
                    groupedWork.addLiteraryForms(literaryForm);
                }
                if (literaryFormFull.size() > 0){
                    groupedWork.addLiteraryFormsFull(literaryFormFull);
                }
                String publisher = rawResponse.getJSONObject("publisher").getString("text");
                groupedWork.addPublisher(publisher);
                //publication date
                String releaseDate = rawResponse.getString("releasedDate");
                String releaseYear = releaseDate.substring(0, 4);
                groupedWork.addPublicationDate(releaseYear);
                //physical description?
                String description = rawResponse.getString("shortDescription");
                groupedWork.addDescription(description, primaryFormat);

                String isbn = rawResponse.getString("isbn");
                groupedWork.addIsbn(isbn, primaryFormat);

                ItemInfo itemInfo = new ItemInfo();
                itemInfo.seteContentSource("Rbdigital");
                itemInfo.setIsEContent(true);
                itemInfo.setShelfLocation("Online Rbdigital Collection");
                itemInfo.setCallNumber("Online Rbdigital");
                itemInfo.setSortableCallNumber("Online Rbdigital");
                //We don't currently have a way to determine how many copies are owned
                itemInfo.setNumCopies(1);

                Date dateAdded = new Date(productRS.getLong("dateFirstDetected") * 1000);
                itemInfo.setDateAdded(dateAdded);

                getAvailabilityStmt.setString(1, identifier);
                ResultSet availabilityRS = getAvailabilityStmt.executeQuery();
                if (availabilityRS.next()) {
                    boolean available = availabilityRS.getBoolean("isAvailable");
                    if (available) {
                        itemInfo.setDetailedStatus("Available Online");
                    } else {
                        itemInfo.setDetailedStatus("Checked Out");
                    }
                    for (Scope scope : indexer.getScopes()) {
                        ScopingInfo scopingInfo = itemInfo.addScope(scope);
                        scopingInfo.setAvailable(available);
                        if (available) {
                            scopingInfo.setStatus("Available Online");
                            scopingInfo.setGroupedStatus("Available Online");
                        } else {
                            scopingInfo.setStatus("Checked Out");
                            scopingInfo.setGroupedStatus("Checked Out");
                        }
                        scopingInfo.setHoldable(true);
                        scopingInfo.setLibraryOwned(true);
                        scopingInfo.setLocallyOwned(true);
                        scopingInfo.setInLibraryUseOnly(false);
                    }
                }
                rbdigitalRecord.addItem(itemInfo);

            }
            productRS.close();
        }catch (NullPointerException e) {
            logger.error("Null pointer exception processing rbdigital record ", e);
        } catch (JSONException e) {
            logger.error("Error parsing raw data for rbdigital", e);
        } catch (SQLException e) {
            logger.error("Error loading information from Database for overdrive title", e);
        }
    }

    private String swapFirstLastNames(String author) {
        //Need to swap the first and last names
        if (author.contains(" ")){
            String[] authorParts = author.split("\\s+");
            StringBuilder tmpAuthor = new StringBuilder();
            for (int i = 0; i < authorParts.length -1; i++){
                tmpAuthor.append(authorParts[i]).append(" ");
            }
            author = authorParts[authorParts.length -1] + ", " + tmpAuthor.toString();
        }
        return author;
    }
}
