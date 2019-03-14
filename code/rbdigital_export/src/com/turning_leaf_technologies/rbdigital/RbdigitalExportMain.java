package com.turning_leaf_technologies.rbdigital;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.URLPostResponse;
import com.turning_leaf_technologies.strings.StringUtils;

import org.apache.logging.log4j.Logger;

import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import com.turning_leaf_technologies.grouping.RecordGroupingProcessor;

import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Date;
import java.util.HashMap;
import java.util.zip.CRC32;

public class RbdigitalExportMain {
    private static Logger logger;
    private static String serverName;
    @SuppressWarnings("FieldCanBeLocal")
    private static String processName = "rbdigital_export";

    private static Ini configIni;

    private static Long startTimeForLogging;
    private static boolean hadErrors = false;

    //Reporting information
    private static long exportLogId;

    //SQL Statements
    private static PreparedStatement addNoteToExportLogStmt;
    private static PreparedStatement updateRbdigitalItemStmt;
    private static PreparedStatement deleteRbdigitalItemStmt;
    private static PreparedStatement getAllExistingRbdigitalItemsStmt;
    private static PreparedStatement updateRbdigitalAvailabilityStmt;
    private static PreparedStatement getExistingRbdigitalAvailabilityStmt;
    private static PreparedStatement getWorkForPrimaryIdentifierStmt;
    private static PreparedStatement getAdditionalPrimaryIdentifierForWorkStmt;
    private static PreparedStatement deletePrimaryIdentifierStmt;
    private static PreparedStatement markGroupedWorkAsChangedStmt;
    private static PreparedStatement deleteGroupedWorkStmt;
    private static PreparedStatement getPermanentIdByWorkIdStmt;

    //Record grouper
    private static GroupedWorkIndexer groupedWorkIndexer;
    private static RecordGroupingProcessor recordGroupingProcessorSingleton = null;

    //Existing records
    private static HashMap<String, RbdigitalTitle> existingRecords = new HashMap<>();

    //For Checksums
    private static CRC32 checksumCalculator = new CRC32();
    private static Connection aspenConn;

    public static void main(String[] args){
        if (args.length == 0) {
            System.out.println("You must provide the servername as the first argument.");
            System.exit(1);
        }
        serverName = args[0];
        args = Arrays.copyOfRange(args, 1, args.length);

        boolean doFullReload = false;
        if (args.length == 1){
            //Check to see if we got a full reload parameter
            String firstArg = args[0].replaceAll("\\s", "");
            if (firstArg.matches("^fullReload(=true|1)?$")){
                doFullReload = true;
            }
        }

        Date startTime = new Date();
        startTimeForLogging = startTime.getTime() / 1000;
        logger = LoggingUtil.setupLogging(serverName, processName);
        logger.info("Starting " + processName + ": " + startTime.toString());

        // Read the base INI file to get information about the server (current directory/cron/config.ini)
        configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

        //Connect to the aspen database
        aspenConn = connectToDatabase();

        //Remove log entries older than 60 days
        long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 60);
        try {
            int numDeletions = aspenConn.prepareStatement("DELETE from rbdigital_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
            logger.info("Deleted " + numDeletions + " old log entries");
        }catch (SQLException e){
            logger.error("Error deleting old log entries", e);
        }
        //Start a log entry
        createDbLogEntry(startTime, aspenConn);

        //Get a list of all existing records in the database
        loadExistingTitles();

        //Do the actual work here
        extractRbdigitalData(doFullReload);

        //Mark any records that no longer exist in search results as deleted
        deleteItems();

        if (hadErrors){
            logger.error("There were errors during the export!");
        }

        logger.info("Finished " + new Date().toString());
        long endTime = new Date().getTime();
        long elapsedTime = endTime - startTime.getTime();
        logger.info("Elapsed Minutes " + (elapsedTime / 60000));

        try {
            PreparedStatement finishedStatement = aspenConn.prepareStatement("UPDATE rbdigital_export_log SET endTime = ? WHERE id = ?");
            finishedStatement.setLong(1, endTime / 1000);
            finishedStatement.setLong(2, exportLogId);
            finishedStatement.executeUpdate();
        } catch (SQLException e) {
            logger.error("Unable to update export log with completion time.", e);
        }

        //Disconnect from the database
        disconnectDatabase(aspenConn);
    }

    private static void deleteItems() {
        try {
            int numDeleted = 0;
            for (RbdigitalTitle rbdigitalTitle : existingRecords.values()) {
                if (!rbdigitalTitle.isDeleted()) {
                    deleteRbdigitalItemStmt.setLong(1, rbdigitalTitle.getId());
                    deleteRbdigitalItemStmt.executeUpdate();
                    removeRecordFromGroupedWork(startTimeForLogging, "rbdigital", rbdigitalTitle.getRbdigitalId());
                    numDeleted++;
                }
            }
            if (numDeleted > 0) {
                logger.warn("Deleted " + numDeleted + " old titles");
            }
        }catch (SQLException e) {
            logger.error("Error deleting items", e);
            addNoteToExportLog("Error deleting items " + e.toString());
        }
    }

    private static void loadExistingTitles() {
        try {
            ResultSet allRecordsRS = getAllExistingRbdigitalItemsStmt.executeQuery();
            while (allRecordsRS.next()) {
                String rbdigitalId = allRecordsRS.getString("rbdigitalId");
                RbdigitalTitle newTitle = new RbdigitalTitle(
                        allRecordsRS.getLong("id"),
                        rbdigitalId,
                        allRecordsRS.getLong("rawChecksum"),
                        allRecordsRS.getBoolean("deleted")
                        );
                existingRecords.put(rbdigitalId, newTitle);
            }
        } catch (SQLException e) {
            logger.error("Error loading existing titles", e);
            addNoteToExportLog("Error loading existing titles" + e.toString());
            System.exit(-1);
        }
    }

    private static void extractRbdigitalData(boolean doFullReload) {
        //TODO: Change to pull data from database rather than INI file
        String baseUrl = configIni.get("Rbdigital", "url");
        String apiToken = configIni.get("Rbdigital", "apiToken");
        String libraryId = configIni.get("Rbdigital", "libraryId");

        //Get a list of ebooks and eaudiobooks to process (would ideally use book-holdings, but that is not currently working)
        //String audioBookUrl = baseUrl + "/v1/libraries/" + libraryId + "/book-holdings/";

        String bookUrl = baseUrl + "/v1/libraries/" + libraryId + "/search?page-size=100";
        HashMap<String, String> headers = new HashMap<>();
        headers.put("Authorization", "basic " + apiToken);
        headers.put("Content-Type", "application/json");
        URLPostResponse response = NetworkUtils.getURL(bookUrl, logger, headers);
        if (!response.isSuccess()){
            logger.error(response.getMessage());
            hadErrors = true;
        }else{
            try {
                JSONObject responseJSON = new JSONObject(response.getMessage());
                int numPages = responseJSON.getInt("pageCount");
                int numResults = responseJSON.getInt("resultSetCount");
                logger.info("Preparing to process " + numPages + " pages of audiobook results, " + numResults + " results");
                //Process the first page of results
                logger.info("Processing page 0 of results");
                processRbdigitalTitles(responseJSON, doFullReload);

                //Process each page of the results
                for (int curPage = 1; curPage < numPages; curPage++) {
                    logger.info("Processing page " + curPage);
                    bookUrl = baseUrl + "/v1/libraries/" + libraryId + "/search?page-size=100&page-index=" + curPage;
                    response = NetworkUtils.getURL(bookUrl, logger, headers);
                    responseJSON = new JSONObject(response.getMessage());
                    processRbdigitalTitles(responseJSON, doFullReload);
                }

                if (groupedWorkIndexer != null) {
                    groupedWorkIndexer.finishIndexingFromExtract();
                }
            } catch (JSONException e) {
                logger.error("Error parsing response", e);
            }
        }

        //TODO: Process magazines
        // Get a list of magazines to process
        String eMagazineUrl = baseUrl + "/v1/libraries/" + libraryId + "/search/emagazine/";
        response = NetworkUtils.getURL(eMagazineUrl, logger, headers);
        if (!response.isSuccess()){
            logger.error(response.getMessage());
            hadErrors = true;
        }else{
            try {
                JSONObject responseJSON = new JSONObject(response.getMessage());
                int numPages = responseJSON.getInt("pageCount");
                int numResults = responseJSON.getInt("resultSetCount");
                logger.info("Preparing to process " + numPages + " pages of emagazine results, " + numResults + " results");
            } catch (JSONException e) {
                logger.error("Error parsing response", e);
            }
        }
    }

    private static void processRbdigitalTitles(JSONObject responseJSON, boolean doFullReload) {

        try {
            JSONArray items = responseJSON.getJSONArray("items");
            for (int i = 0; i < items.length(); i++) {
                JSONObject curItem = items.getJSONObject(i);
                JSONObject itemDetails = curItem.getJSONObject("item");
                checksumCalculator.reset();
                String itemDetailsAsString = itemDetails.toString();
                checksumCalculator.update(itemDetailsAsString.getBytes());
                long itemChecksum = checksumCalculator.getValue();

                String rbdigitalId = itemDetails.getString("id");
                logger.debug("processing " + rbdigitalId);

                //Check to see if the title metadata has changed
                RbdigitalTitle existingTitle = existingRecords.get(rbdigitalId);
                boolean metadataChanged = false;
                if (existingTitle != null){
                    logger.debug("Record already exists");
                    if (existingTitle.getChecksum() != itemChecksum || existingTitle.isDeleted()) {
                        logger.debug("Updating item details");
                        metadataChanged = true;
                    }
                    existingRecords.remove(rbdigitalId);
                } else {
                    logger.debug("Adding record " + rbdigitalId);
                    metadataChanged = true;
                }

                //Check if availability changed
                JSONObject itemAvailability = curItem.getJSONObject("interest");
                checksumCalculator.reset();
                String itemAvailabilityAsString = itemAvailability.toString();
                checksumCalculator.update(itemAvailabilityAsString.getBytes());
                long availabilityChecksum = checksumCalculator.getValue();
                boolean availabilityChanged = false;
                getExistingRbdigitalAvailabilityStmt.setString(1, rbdigitalId);
                ResultSet getExistingAvailabilityRS = getExistingRbdigitalAvailabilityStmt.executeQuery();
                if (getExistingAvailabilityRS.next()){
                    long existingChecksum = getExistingAvailabilityRS.getLong("rawChecksum");
                    logger.debug("Availability already exists");
                    if (existingChecksum != availabilityChecksum) {
                        logger.debug("Updating availability details");
                        availabilityChanged = true;
                    }
                } else {
                    logger.debug("Adding availability for " + itemDetails.getString("id"));
                    availabilityChanged = true;
                }

                String primaryAuthor = null;
                JSONArray authors = itemDetails.getJSONArray("authors");
                if (authors.length() > 0) {
                    primaryAuthor = authors.getJSONObject(0).getString("text");
                }
                if (metadataChanged || doFullReload) {
                    //Update the database
                    updateRbdigitalItemStmt.setString(1, rbdigitalId);
                    updateRbdigitalItemStmt.setString(2, itemDetails.getString("title"));
                    updateRbdigitalItemStmt.setString(3, primaryAuthor);
                    updateRbdigitalItemStmt.setString(4, itemDetails.getString("mediaType"));
                    updateRbdigitalItemStmt.setBoolean(5, itemDetails.getBoolean("isFiction"));
                    updateRbdigitalItemStmt.setString(6, itemDetails.getString("audience"));
                    updateRbdigitalItemStmt.setString(7, itemDetails.getString("language"));
                    updateRbdigitalItemStmt.setLong(8, itemChecksum);
                    updateRbdigitalItemStmt.setString(9, itemDetailsAsString);
                    updateRbdigitalItemStmt.setLong(10, startTimeForLogging);
                    updateRbdigitalItemStmt.setLong(11, startTimeForLogging);
                    updateRbdigitalItemStmt.executeUpdate();
                }

                if (availabilityChanged) {
                    updateRbdigitalAvailabilityStmt.setString(1, rbdigitalId);
                    updateRbdigitalAvailabilityStmt.setBoolean(2, itemAvailability.getBoolean("isAvailable"));
                    updateRbdigitalAvailabilityStmt.setBoolean(3, itemAvailability.getBoolean("isOwned"));
                    updateRbdigitalAvailabilityStmt.setString(4, itemAvailability.getString("name"));
                    updateRbdigitalAvailabilityStmt.setLong(5, availabilityChecksum);
                    updateRbdigitalAvailabilityStmt.setString(6, itemAvailabilityAsString);
                    updateRbdigitalAvailabilityStmt.setLong(7, startTimeForLogging);
                    updateRbdigitalAvailabilityStmt.executeUpdate();
                }

                String groupedWorkId = null;
                if (metadataChanged || doFullReload) {
                    groupedWorkId = groupRbdigitalRecord(itemDetails, rbdigitalId, primaryAuthor);
                }
                if (metadataChanged || availabilityChanged || doFullReload) {
                    if (groupedWorkId == null) {
                        groupedWorkId = getPermanentIdForRecord(rbdigitalId);
                    }
                    indexRbdigitalRecord(groupedWorkId);
                }
            }
        } catch (Exception e) {
            logger.error("Error processing titles", e);
        }
    }

    private static void indexRbdigitalRecord(String permanentId) {
        getGroupedWorkIndexer().processGroupedWork(permanentId);
    }

    private static GroupedWorkIndexer getGroupedWorkIndexer() {
        if (groupedWorkIndexer == null) {
            groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, false, logger);
        }
        return groupedWorkIndexer;
    }

    private static String getPermanentIdForRecord(String rbdigitalId) {
        String permanentId = null;
        try{
            getWorkForPrimaryIdentifierStmt.setString(1, "rbdigital");
            getWorkForPrimaryIdentifierStmt.setString(2, rbdigitalId);
            ResultSet getWorkForPrimaryIdentifierRS = getWorkForPrimaryIdentifierStmt.executeQuery();
            if (getWorkForPrimaryIdentifierRS.next()) {
                long groupedWorkId = getWorkForPrimaryIdentifierRS.getLong("grouped_work_id");
                getPermanentIdByWorkIdStmt.setLong(1, groupedWorkId);
                ResultSet getPermanentIdByWorkIdRS = getPermanentIdByWorkIdStmt.executeQuery();
                if (getPermanentIdByWorkIdRS.next()) {
                    permanentId = getPermanentIdByWorkIdRS.getString("permanent_id");
                }
            }
        } catch (Exception e) {
            logger.error("Error processing deleted bibs", e);
        }
        return permanentId;
    }

    private static String groupRbdigitalRecord(JSONObject itemDetails, String rbdigitalId, String primaryAuthor) throws JSONException {
        //Perform record grouping on the record
        String title = itemDetails.getString("title");
        String author = primaryAuthor;
        author = swapFirstLastNames(author);
        String mediaType = itemDetails.getString("mediaType");

        RecordIdentifier primaryIdentifier = new RecordIdentifier("rbdigital", rbdigitalId);

        String subtitle = "";
        if (itemDetails.getBoolean("hasSubtitle")) {
            subtitle = itemDetails.getString("subtitle");
        }
        return getRecordGroupingProcessor().processRecord(primaryIdentifier, title, subtitle, author, mediaType, true);
    }

    private static RecordGroupingProcessor getRecordGroupingProcessor(){
        if (recordGroupingProcessorSingleton == null) {
            recordGroupingProcessorSingleton = new RecordGroupingProcessor(aspenConn, serverName, logger, false);
        }
        return recordGroupingProcessorSingleton;
    }

    private static String swapFirstLastNames(String author) {
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

    private static void createDbLogEntry(Date startTime, Connection aspenConn) {
        try {
            logger.info("Creating log entry for index");
            PreparedStatement createLogEntryStatement = aspenConn.prepareStatement("INSERT INTO rbdigital_export_log (startTime, lastUpdate, notes) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
            createLogEntryStatement.setLong(1, startTime.getTime() / 1000);
            createLogEntryStatement.setLong(2, startTime.getTime() / 1000);
            createLogEntryStatement.setString(3, "Initialization complete");
            createLogEntryStatement.executeUpdate();
            ResultSet generatedKeys = createLogEntryStatement.getGeneratedKeys();
            if (generatedKeys.next()){
                exportLogId = generatedKeys.getLong(1);
            }

            addNoteToExportLogStmt = aspenConn.prepareStatement("UPDATE rbdigital_export_log SET notes = ?, lastUpdate = ? WHERE id = ?");
        } catch (SQLException e) {
            logger.error("Unable to create log entry for record grouping process", e);
            System.exit(0);
        }
    }

    private static void disconnectDatabase(Connection aspenConn) {
        try{
            aspenConn.close();
        }catch (Exception e){
            logger.error("Error closing database ", e);
            System.exit(1);
        }
    }

    private static Connection connectToDatabase() {
        Connection aspenConn = null;
        try{
            String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
            aspenConn = DriverManager.getConnection(databaseConnectionInfo);
            getAllExistingRbdigitalItemsStmt = aspenConn.prepareStatement("SELECT id, rbdigitalId, rawChecksum, deleted from rbdigital_title");
            updateRbdigitalItemStmt = aspenConn.prepareStatement(
                    "INSERT INTO rbdigital_title " +
                            "(rbdigitalId, title, primaryAuthor, mediaType, isFiction, audience, language, rawChecksum, rawResponse, lastChange, dateFirstDetected) " +
                            "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
                            "ON DUPLICATE KEY UPDATE title = VALUES(title), primaryAuthor = VALUES(primaryAuthor), mediaType = VALUES(mediaType), " +
                            "isFiction = VALUES(isFiction), audience = VALUES(audience), language = VALUES(language), rawChecksum = VALUES(rawChecksum), " +
                            "rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange), deleted = 0");
            deleteRbdigitalItemStmt = aspenConn.prepareStatement("UPDATE rbdigital_title SET deleted = 1 where id = ?");
            getExistingRbdigitalAvailabilityStmt = aspenConn.prepareStatement("SELECT id, rawChecksum from rbdigital_availability WHERE rbdigitalId = ?");
            updateRbdigitalAvailabilityStmt = aspenConn.prepareStatement(
                    "INSERT INTO rbdigital_availability " +
                            "(rbdigitalId, isAvailable, isOwned, name, rawChecksum, rawResponse, lastChange) " +
                            "VALUES (?, ?, ?, ?, ?, ?, ?) " +
                            "ON DUPLICATE KEY UPDATE isAvailable = VALUES(isAvailable), isOwned = VALUES(isOwned), " +
                            "name = VALUES(name), rawChecksum = VALUES(rawChecksum), rawResponse = VALUES(rawResponse), lastChange = VALUES(lastChange)");
            getWorkForPrimaryIdentifierStmt = aspenConn.prepareStatement("SELECT id, grouped_work_id from grouped_work_primary_identifiers where type = ? and identifier = ?");
            deletePrimaryIdentifierStmt = aspenConn.prepareStatement("DELETE from grouped_work_primary_identifiers where id = ?");
            getAdditionalPrimaryIdentifierForWorkStmt = aspenConn.prepareStatement("SELECT * from grouped_work_primary_identifiers where grouped_work_id = ?");
            markGroupedWorkAsChangedStmt = aspenConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = ?");
            deleteGroupedWorkStmt = aspenConn.prepareStatement("DELETE from grouped_work where id = ?");
            getPermanentIdByWorkIdStmt = aspenConn.prepareStatement("SELECT permanent_id from grouped_work WHERE id = ?");
        }catch (Exception e){
            logger.error("Error connecting to aspen database", e);
            System.exit(1);
        }
        return aspenConn;
    }

    private static StringBuffer notes = new StringBuffer();
    private static SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
    private static void addNoteToExportLog(String note) {
        try {
            Date date = new Date();
            notes.append("<br>").append(dateFormat.format(date)).append(": ").append(note);
            addNoteToExportLogStmt.setString(1, StringUtils.trimTo(65535, notes.toString()));
            addNoteToExportLogStmt.setLong(2, new Date().getTime() / 1000);
            addNoteToExportLogStmt.setLong(3, exportLogId);
            addNoteToExportLogStmt.executeUpdate();
            logger.info(note);
        } catch (SQLException e) {
            logger.error("Error adding note to Export Log", e);
        }
    }

    //TODO: Move to record grouping or another shared location
    /**
     * Removes a record from a grouped work and returns if the grouped work no longer has
     * any records attached to it (in which case it should be removed from the index after calling this)
     *
     * @param updateTime - Current indexing time to indicate that the record has changed
     * @param source - The source of the record being removed
     * @param id - The id of the record being removed
     */
    private static void removeRecordFromGroupedWork(long updateTime, @SuppressWarnings("SameParameterValue") String source, String id) {
        try {
            //Check to see if the identifier is in the grouped work primary identifiers table
            getWorkForPrimaryIdentifierStmt.setString(1, source);
            getWorkForPrimaryIdentifierStmt.setString(2, id);
            ResultSet getWorkForPrimaryIdentifierRS = getWorkForPrimaryIdentifierStmt.executeQuery();
            if (getWorkForPrimaryIdentifierRS.next()) {
                long groupedWorkId = getWorkForPrimaryIdentifierRS.getLong("grouped_work_id");
                long primaryIdentifierId = getWorkForPrimaryIdentifierRS.getLong("id");
                //Delete the primary identifier
                deletePrimaryIdentifierStmt.setLong(1, primaryIdentifierId);
                deletePrimaryIdentifierStmt.executeUpdate();
                //Check to see if there are other identifiers for this work
                getAdditionalPrimaryIdentifierForWorkStmt.setLong(1, groupedWorkId);
                ResultSet getAdditionalPrimaryIdentifierForWorkRS = getAdditionalPrimaryIdentifierForWorkStmt.executeQuery();
                if (getAdditionalPrimaryIdentifierForWorkRS.next()) {
                    //There are additional records for this work, just need to mark that it needs indexing again
                    markGroupedWorkAsChangedStmt.setLong(1, updateTime);
                    markGroupedWorkAsChangedStmt.setLong(2, groupedWorkId);
                    markGroupedWorkAsChangedStmt.executeUpdate();
                } else {
                    //The grouped work no longer exists
                    //Get the permanent id
                    getPermanentIdByWorkIdStmt.setLong(1, groupedWorkId);
                    ResultSet getPermanentIdByWorkIdRS = getPermanentIdByWorkIdStmt.executeQuery();
                    if (getPermanentIdByWorkIdRS.next()) {
                        //TODO: Refactor to do the reindex outside of this method
                        String permanentId = getPermanentIdByWorkIdRS.getString("permanent_id");
                        //Delete the work from solr
                        getGroupedWorkIndexer().deleteRecord(permanentId);

                        //Delete the work from the database?
                        //TODO: Should we do this or leave a record if it was linked to lists, reading history, etc?
                        //regular indexer deletes them too
                        deleteGroupedWorkStmt.setLong(1, groupedWorkId);
                        deleteGroupedWorkStmt.executeUpdate();
                    }

                }
            }//If not true, already deleted skip this
        } catch (Exception e) {
            logger.error("Error processing deleted bibs", e);
        }
    }
}
