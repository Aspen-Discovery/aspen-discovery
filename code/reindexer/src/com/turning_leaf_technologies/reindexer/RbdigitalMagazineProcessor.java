package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.RbdigitalScope;
import com.turning_leaf_technologies.indexing.Scope;
import org.apache.logging.log4j.Logger;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashSet;

class RbdigitalMagazineProcessor {
    private GroupedWorkIndexer indexer;
    private Logger logger;

    private PreparedStatement getProductInfoStmt;

    RbdigitalMagazineProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection dbConn, Logger logger) {
        this.indexer = groupedWorkIndexer;
        this.logger = logger;

        try {
            getProductInfoStmt = dbConn.prepareStatement("SELECT * from rbdigital_magazine where magazineId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
        } catch (SQLException e) {
            logger.error("Error setting up rbdigital magazine processor", e);
        }
    }

    void processRecord(GroupedWorkSolr groupedWork, String identifier) {
        try {
            getProductInfoStmt.setString(1, identifier);
            ResultSet productRS = getProductInfoStmt.executeQuery();
            if (productRS.next()) {
                //Make sure the record isn't deleted
                if (productRS.getBoolean("deleted")){
                    logger.debug("Rbdigital magazine " + identifier + " was deleted, skipping");
                    return;
                }

                RecordInfo rbdigitalRecord = groupedWork.addRelatedRecord("rbdigital_magazine", identifier);
                rbdigitalRecord.setRecordIdentifier("rbdigital_magazine", identifier);

                String title = productRS.getString("title");
                String formatCategory = "eBook";
                String primaryFormat = "eMagazine";

                rbdigitalRecord.addFormat(primaryFormat);
                rbdigitalRecord.addFormatCategory(formatCategory);

                JSONObject rawResponse = new JSONObject(productRS.getString("rawResponse"));

                groupedWork.setTitle(title, title, title, primaryFormat);

                String targetAudience = rawResponse.getString("audience");
                boolean isChildrens = false;
                if (targetAudience.equals("G")){
                    groupedWork.addTargetAudience("Juvenile");
                    groupedWork.addTargetAudienceFull("Juvenile");
                    isChildrens = true;
                }else if (targetAudience.contains("PG")){
                    groupedWork.addTargetAudience("Young Adult");
                    groupedWork.addTargetAudienceFull("Adolescent (14-17)");
                    groupedWork.addTargetAudience("Adult");
                    groupedWork.addTargetAudienceFull("Adult");
                }else {
                    groupedWork.addTargetAudience("Adult");
                    groupedWork.addTargetAudienceFull("Adult");
                }

                rbdigitalRecord.setPrimaryLanguage(productRS.getString("language"));
                long formatBoost = 1;
                try {
                    formatBoost = Long.parseLong(indexer.translateSystemValue("format_boost_rbdigital", primaryFormat, identifier));
                } catch (Exception e) {
                    logger.warn("Could not translate format boost for " + primaryFormat + " create translation map format_boost_rbdigital");
                }
                rbdigitalRecord.setFormatBoost(formatBoost);

                String genre = rawResponse.getString("genre");
                HashSet<String> genresToAdd = new HashSet<>();
                HashSet<String> topicsToAdd = new HashSet<>();
                genresToAdd.add(genre);
                topicsToAdd.add(genre);
                groupedWork.addGenre(genresToAdd);
                groupedWork.addGenreFacet(genresToAdd);
                groupedWork.addTopicFacet(topicsToAdd);
                groupedWork.addTopic(topicsToAdd);

                groupedWork.addSubjects(topicsToAdd);

                groupedWork.addLiteraryForm("Non Fiction");
                groupedWork.addLiteraryFormFull("Non Fiction");

                String publisher = rawResponse.getString("publisher");
                groupedWork.addPublisher(publisher);
                //publication date
                String releaseDate = rawResponse.getString("coverDate");
                String releaseYear = releaseDate.substring(releaseDate.lastIndexOf("/") + 1);
                groupedWork.addPublicationDate(releaseYear);
                //physical description?
                String description = rawResponse.getString("description");
                groupedWork.addDescription(description, primaryFormat);

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


                boolean available = !rawResponse.getBoolean("isCheckedOut");
                if (available) {
                    itemInfo.setDetailedStatus("Available Online");
                } else {
                    itemInfo.setDetailedStatus("Checked Out");
                }
                for (Scope scope : indexer.getScopes()) {
                    boolean okToAdd = false;
                    RbdigitalScope rbdigitalScope = scope.getRbdigitalScope();
                    if (rbdigitalScope != null) {
                        if (rbdigitalScope.isIncludeEMagazines()) {
                            okToAdd = true;
                        }
                        if (rbdigitalScope.isRestrictToChildrensMaterial() && !isChildrens){
                            okToAdd = false;
                        }
                    }
                    if (okToAdd) {
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
            logger.error("Null pointer exception processing rbdigital magazine ", e);
        } catch (JSONException e) {
            logger.error("Error parsing raw data for rbdigital magazine", e);
        } catch (SQLException e) {
            logger.error("Error loading information from Database for rbdigital magazine", e);
        }
    }

}
