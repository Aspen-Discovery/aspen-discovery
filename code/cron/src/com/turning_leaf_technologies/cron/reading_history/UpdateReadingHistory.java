package com.turning_leaf_technologies.cron.reading_history;

import com.turning_leaf_technologies.cron.CronLogEntry;
import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.cron.IProcessHandler;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.concurrent.ArrayBlockingQueue;
import java.util.concurrent.BlockingQueue;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;

@SuppressWarnings("unused")
public class UpdateReadingHistory implements IProcessHandler {

	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Update Reading History", dbConn, logger);
		processLog.saveResults();

		logger.info("Updating Reading History");
		processLog.addNote("Updating Reading History");

		String aspenUrl = configIni.get("Site", "url");
		if (aspenUrl == null || aspenUrl.length() == 0) {
			processLog.incErrors("Unable to get URL for Aspen in General settings.  Please add a url key to Site section.");
			return;
		}

		// Connect to the MySQL database
		int numSkipped = 0;
		try {
			//Get the number of patrons to update
			PreparedStatement getNumUsersStmt = dbConn.prepareStatement("SELECT count(*) as numUsers FROM user where trackReadingHistory=1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet numUsersResults = getNumUsersStmt.executeQuery();
			int numUsersToUpdate = 0;
			if (numUsersResults.next()) {
				numUsersToUpdate = numUsersResults.getInt("numUsers");
				processLog.addNote("Preparing to process " + numUsersToUpdate + " users");
			}

			// Get a list of all patrons that have reading history turned on.
			PreparedStatement getUsersStmt = dbConn.prepareStatement("SELECT id, cat_username, cat_password FROM user where trackReadingHistory=1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			if (numUsersToUpdate > 0) {
				BlockingQueue<Runnable> blockingQueue = new ArrayBlockingQueue<>(numUsersToUpdate);

				//Process all the threads, we will allow up to 20 concurrent threads to start
				ThreadPoolExecutor executor = new ThreadPoolExecutor(4, 8, 5000, TimeUnit.MILLISECONDS, blockingQueue);

				//Setup the ThreadGroup
				ResultSet userResults = getUsersStmt.executeQuery();
				while (userResults.next()) {

					// For each patron
					String cat_username = userResults.getString("cat_username");
					String cat_password = userResults.getString("cat_password");

					if (cat_password == null || cat_password.length() == 0) {
						numSkipped++;
						processLog.incSkipped();
						continue;
					}

					UpdateReadingHistoryTask newTask = new UpdateReadingHistoryTask(aspenUrl, cat_username, cat_password, processLog, logger);
					executor.execute(newTask);

					processLog.saveResults();
				}
				userResults.close();

				while ((executor.getCompletedTaskCount() + numSkipped) < numUsersToUpdate) {
					processLog.saveResults();
					logger.debug("Num Users To Update = " + numUsersToUpdate + " Completed Task Count = " + executor.getCompletedTaskCount() + " Num Skipped = " + numSkipped);
					try {
						Thread.sleep(1000);
					} catch (InterruptedException e) {
						logger.error("Sleep was interrupted", e);
					}
				}
				logger.debug("Finished processing all threads");

				executor.shutdownNow();

				processLog.addNote("Skipped " + numSkipped + " records because the password was null");
			}
		} catch (SQLException e) {
			processLog.incErrors("Unable get a list of users that need to have their reading list updated ", e);
		}
		
		processLog.setFinished();
		processLog.saveResults();
	}
}
