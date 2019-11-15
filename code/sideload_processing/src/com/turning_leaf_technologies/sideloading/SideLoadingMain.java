package com.turning_leaf_technologies.sideloading;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.grouping.BaseMarcRecordGrouper;
import com.turning_leaf_technologies.grouping.RemoveRecordFromWorkResult;
import com.turning_leaf_technologies.grouping.SideLoadedRecordGrouper;
import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.indexing.SideLoadSettings;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.reindexer.GroupedWorkIndexer;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.sql.*;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;

public class SideLoadingMain {
    private static Logger logger;
    private static String serverName;

    private static Ini configIni;

    private static Connection aspenConn;

    private static SideLoadLogEntry logEntry;

    private static long startTimeForLogging;

    //Record grouper
    private static GroupedWorkIndexer groupedWorkIndexer;
    private static HashMap<String, SideLoadedRecordGrouper> recordGroupingProcessors = new HashMap<>();

    public static void main(String[] args){
        if (args.length == 0) {
            System.out.println("You must provide the server name as the first argument.");
            System.exit(1);
        }
        serverName = args[0];

        logger = LoggingUtil.setupLogging(serverName, "side_loading");

        //noinspection InfiniteLoopStatement
        while (true) {
            Date startTime = new Date();
            logger.info(startTime.toString() + ": Starting Hoopla Export");
            startTimeForLogging = startTime.getTime() / 1000;

            // Read the base INI file to get information about the server (current directory/cron/config.ini)
            configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

            //Connect to the Aspen database
            aspenConn = connectToDatabase();

            //Start a log entry
            createDbLogEntry(startTime, aspenConn);
            logEntry.addNote("Starting Update of Side Loaded eContent");
            logEntry.saveResults();

            //Get a list of side loads
            try {
                PreparedStatement getSideloadsStmt = aspenConn.prepareStatement("SELECT * FROM sideloads");
                ResultSet getSideloadsRS = getSideloadsStmt.executeQuery();
                while (getSideloadsRS.next()){
                    SideLoadSettings settings = new SideLoadSettings(getSideloadsRS);
                    processSideLoad(settings);
                }
            }catch (SQLException e){
                logger.error("Error loading sideloads to run", e);
            }

            if (groupedWorkIndexer != null) {
                groupedWorkIndexer.finishIndexingFromExtract();
                recordGroupingProcessors = new HashMap<>();
                groupedWorkIndexer = null;
            }

            logger.info("Finished exporting data " + new Date().toString());
            long endTime = new Date().getTime();
            long elapsedTime = endTime - startTime.getTime();
            logger.info("Elapsed Minutes " + (elapsedTime / 60000));

            //Mark that indexing has finished
            logEntry.setFinished();

            disconnectDatabase(aspenConn);

            //Pause 30 minutes before running the next export
            try {
                System.gc();
                Thread.sleep(1000 * 60 * 30);
            } catch (InterruptedException e) {
                logger.info("Thread was interrupted");
            }
        }
    }

    private static void processSideLoad(SideLoadSettings settings) {
        File marcDirectory = new File(settings.getMarcPath());
        if (!marcDirectory.exists()){
            logEntry.addNote("Marc Directory " + settings.getMarcPath() + " did not exist");
            logEntry.incErrors();
        }else{
            long startTime = Math.max(settings.getLastUpdateOfAllRecords(), settings.getLastUpdateOfChangedRecords()) * 1000;
            File[] marcFiles = marcDirectory.listFiles((dir, name) -> name.matches(settings.getFilenamesToInclude()));
            if (marcFiles != null) {
                ArrayList<File> filesToProcess = new ArrayList<>();
                for (File marcFile : marcFiles) {
                    if (settings.isRunFullUpdate() || (marcFile.lastModified() > startTime)) {
                        filesToProcess.add(marcFile);
                    }
                }
                if (filesToProcess.size() > 0) {
                    logEntry.addUpdatedSideLoad(settings.getName());
                    for (File fileToProcess : filesToProcess) {
                        processSideLoadFile(fileToProcess, settings);
                    }
                }
            }

            try {
                PreparedStatement updateSideloadStmt;
                if (settings.isRunFullUpdate()){
                    updateSideloadStmt = aspenConn.prepareStatement("UPDATE sideloads set lastUpdateOfAllRecords = ?, runFullUpdate = 0 where id = ?");
                }else{
                    updateSideloadStmt = aspenConn.prepareStatement("UPDATE sideloads set lastUpdateOfChangedRecords = ? where id = ?");
                }

                updateSideloadStmt.setLong(1, startTimeForLogging);
                updateSideloadStmt.setLong(2, settings.getId());
                updateSideloadStmt.executeUpdate();
            }catch (Exception e){
                logger.error("Error updating lastUpdateFromMarcExport", e);
                logEntry.addNote("Error updating lastUpdateFromMarcExport");
            }
        }
    }

    private static void processSideLoadFile(File fileToProcess, SideLoadSettings settings) {
        try {
            SideLoadedRecordGrouper recordGrouper = getRecordGroupingProcessor(settings);
            MarcReader marcReader = new MarcPermissiveStreamReader(new FileInputStream(fileToProcess), true, true, settings.getMarcEncoding());
            while (marcReader.hasNext()){
                Record marcRecord = marcReader.next();
                RecordIdentifier recordIdentifier = recordGrouper.getPrimaryIdentifierFromMarcRecord(marcRecord, settings.getName());
                if (recordIdentifier != null) {
                    logEntry.incNumProducts(1);
                    boolean deleteRecord = false;
                    String recordNumber = recordIdentifier.getIdentifier();
                    BaseMarcRecordGrouper.MarcStatus marcStatus = recordGrouper.writeIndividualMarc(settings, marcRecord, recordNumber, logger);
                    if (marcStatus != BaseMarcRecordGrouper.MarcStatus.UNCHANGED || settings.isRunFullUpdate()) {
                        String permanentId = recordGrouper.processMarcRecord(marcRecord, marcStatus != BaseMarcRecordGrouper.MarcStatus.UNCHANGED);
                        if (permanentId == null){
                            //Delete the record since it is suppressed
                            deleteRecord = true;
                        }else {
                            if (marcStatus == BaseMarcRecordGrouper.MarcStatus.NEW){
                                logEntry.incAdded();
                            }else {
                                logEntry.incUpdated();
                            }
                            getGroupedWorkIndexer().processGroupedWork(permanentId);
                        }
                    }else{
                        logEntry.incSkipped();
                    }
                    if (deleteRecord){
                        RemoveRecordFromWorkResult result = recordGrouper.removeRecordFromGroupedWork(settings.getName(), recordIdentifier.getIdentifier());
                        if (result.reindexWork){
                            getGroupedWorkIndexer().processGroupedWork(result.permanentId);
                        }else if (result.deleteWork){
                            //Delete the work from solr and the database
                            getGroupedWorkIndexer().deleteRecord(result.permanentId, result.groupedWorkId);
                        }
                        logEntry.incDeleted();
                    }
                    if (logEntry.getNumProducts() % 250 == 0){
                        logEntry.saveResults();
                    }
                }
            }
            logEntry.saveResults();
        } catch (FileNotFoundException e) {
            logEntry.incErrors();
            logEntry.addNote("Could not find file " + fileToProcess.getAbsolutePath());
        }
    }

    private static Connection connectToDatabase(){
        Connection aspenConn = null;
        try{
            String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
            if (databaseConnectionInfo != null) {
                aspenConn = DriverManager.getConnection(databaseConnectionInfo);
            }else{
                logger.error("Aspen database connection information was not provided");
                System.exit(1);
            }
        }catch (Exception e){
            logger.error("Error connecting to Aspen database " + e.toString());
            System.exit(1);
        }
        return aspenConn;
    }

    private static void disconnectDatabase(Connection aspenConn) {
        try{
            aspenConn.close();
        }catch (Exception e){
            logger.error("Error closing database ", e);
            System.exit(1);
        }
    }

    private static void createDbLogEntry(Date startTime, Connection aspenConn) {
        //Remove log entries older than 45 days
        long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
        try {
            int numDeletions = aspenConn.prepareStatement("DELETE from sideload_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
            logger.info("Deleted " + numDeletions + " old log entries");
        } catch (SQLException e) {
            logger.error("Error deleting old log entries", e);
        }

        logEntry = new SideLoadLogEntry(aspenConn, logger);
    }

    private static GroupedWorkIndexer getGroupedWorkIndexer() {
        if (groupedWorkIndexer == null) {
            groupedWorkIndexer = new GroupedWorkIndexer(serverName, aspenConn, configIni, false, false, false, logger);
        }
        return groupedWorkIndexer;
    }

    private static SideLoadedRecordGrouper getRecordGroupingProcessor(SideLoadSettings settings){
        SideLoadedRecordGrouper recordGroupingProcessor = recordGroupingProcessors.get(settings.getName());
        if (recordGroupingProcessor == null) {
            recordGroupingProcessor = new SideLoadedRecordGrouper(serverName, aspenConn, settings, logger, false);
            recordGroupingProcessors.put(settings.getName(), recordGroupingProcessor);
        }
        return recordGroupingProcessor;
    }
}
