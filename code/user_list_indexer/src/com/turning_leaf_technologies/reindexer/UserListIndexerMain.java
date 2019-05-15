package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import java.sql.*;
import java.util.Date;

public class UserListIndexerMain {
    private static Logger logger;

    private static boolean fullReindex = false;

    private static long startTime;
    private static long endTime;
    private static long lastReindexTime;

    private static UserListIndexer listProcessor;

    //General configuration
    private static String serverName;
    private static Connection dbConn;

    /**
     * Starts the re-indexing process
     *
     * @param args String[] The server name to index with optional parameter for properties of indexing
     */
    public static void main(String[] args) {
        startTime = new Date().getTime();
        // Get the configuration filename
        if (args.length == 0) {
            System.out.println("Please enter the server to index as the first parameter");
            System.exit(1);
        }
        boolean runContinuously = true;
        serverName = args[0];
        System.setProperty("reindex.process.serverName", serverName);

        while (runContinuously) {
            runContinuously = false;
            if (args.length >= 2) {
                String firstArg = args[1].replaceAll("\\s", "");
                if (firstArg.equalsIgnoreCase("full")) {
                    fullReindex = true;
                } else if (firstArg.equalsIgnoreCase("continuous")) {
                    runContinuously = true;
                }
            }

            initializeIndexer();

            //Process lists
            long numListsProcessed = 0;
            try {
                logger.info("Reindexing lists");
                numListsProcessed = listProcessor.processPublicUserLists(fullReindex, lastReindexTime);
            } catch (Error e) {
                logger.error("Error processing reindex ", e);
            } catch (Exception e) {
                logger.error("Exception processing reindex ", e);
            }

            // Send completion information
            endTime = new Date().getTime();
            finishIndexing();

            logger.info("Finished Reindex for " + serverName + " processed " + numListsProcessed);
            long endTime = new Date().getTime();
            long elapsedTime = endTime - startTime;
            logger.info("Elapsed Minutes " + (elapsedTime / 60000));

            //Disconnect from the database
            disconnectDatabase(dbConn);

            //Pause before running the next export (longer if we didn't get any actual changes)
            if (runContinuously) {
                try {
                    if (numListsProcessed == 0) {
                        Thread.sleep(1000 * 60 * 5);
                    }else {
                        Thread.sleep(1000 * 60);
                    }
                } catch (InterruptedException e) {
                    logger.info("Thread was interrupted");
                }
            }
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

    private static void finishIndexing(){
        long elapsedTime = endTime - startTime;
        float elapsedMinutes = (float)elapsedTime / (float)(60000);
        logger.info("Time elapsed: " + elapsedMinutes + " minutes");

        try {
            PreparedStatement finishedStatement = dbConn.prepareStatement("INSERT INTO variables (name, value) VALUES(?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
            finishedStatement.setString(1, "last_user_list_index_time");
            finishedStatement.setLong(2, startTime / 1000);
            finishedStatement.executeUpdate();
        } catch (SQLException e) {
            logger.error("Unable to update variables with completion time.", e);
        }
    }

    private static void initializeIndexer() {
        String processName = "user_list_reindex";
        logger = LoggingUtil.setupLogging(serverName, processName);

        logger.info("Starting Reindex for " + serverName);

        // Parse the configuration file
        Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

        logger.info("Setting up database connections");
        String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
        if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
            logger.error("Database connection information not found in Database Section.  Please specify connection information in database_vufind_jdbc.");
            System.exit(1);
        }
        try {
            dbConn = DriverManager.getConnection(databaseConnectionInfo);
        } catch (SQLException e) {
            logger.error("Could not connect to aspen database", e);
            System.exit(1);
        }

        //Load the last Index time
        try{
            PreparedStatement loadLastIndexTimeStmt = dbConn.prepareStatement("SELECT * from variables WHERE name = 'last_user_list_index_time'");
            ResultSet lastIndexTimeRS = loadLastIndexTimeStmt.executeQuery();
            if (lastIndexTimeRS.next()){
                lastReindexTime = lastIndexTimeRS.getLong("value");
            }
            lastIndexTimeRS.close();
            loadLastIndexTimeStmt.close();
        } catch (Exception e){
            logger.error("Could not load last index time from variables table ", e);
        }

        listProcessor = new UserListIndexer(configIni, dbConn, logger);
    }

}
