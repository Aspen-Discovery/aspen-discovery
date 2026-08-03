package com.turning_leaf_technologies.cron.reading_history;

import com.turning_leaf_technologies.cron.CronLogEntry;
import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.cron.IProcessHandler;
import com.turning_leaf_technologies.encryption.EncryptionUtils;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.concurrent.*;

@SuppressWarnings("unused")
public class UpdateReadingHistory implements IProcessHandler {

	private static final Logger log = LogManager.getLogger(UpdateReadingHistory.class);

	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Update Reading History", dbConn, logger);
		processLog.saveResults();
		processLog.addNote("Starting nightly reading history updates...");

		String aspenUrl = configIni.get("Site", "url");
		if (aspenUrl == null || aspenUrl.isEmpty()) {
			org.ini4j.Profile.Section siteSection = configIni.get("Site");
			String siteConfig = (siteSection != null) ? siteSection.toString() : "<Site section missing>";
			processLog.incErrors("Unable to get URL for Aspen in the site's config file. Please add a URL key to the Site section. Site config: " + siteConfig + ".");
			return;
		}

		// Get the thread settings from the configuration, if any.
		int minThreads = 4;
		int maxThreads = 8;
		try {
			String minThreadsStr = processSettings.get("minThreads");
			if (minThreadsStr != null && !minThreadsStr.isEmpty()) {
				minThreads = Integer.parseInt(minThreadsStr);
			}

			String maxThreadsStr = processSettings.get("maxThreads");
			if (maxThreadsStr != null && !maxThreadsStr.isEmpty()) {
				maxThreads = Integer.parseInt(maxThreadsStr);
			}
		} catch (Exception e) {
			logger.error("Error parsing thread configuration, using defaults: ", e);
		}

		// Get the batch size from configuration.
		int batchSize = 1000;
		try {
			String batchSizeStr = processSettings.get("batchSize");
			if (batchSizeStr != null && !batchSizeStr.isEmpty()) {
				batchSize = Integer.parseInt(batchSizeStr);
			}
		} catch (Exception e) {
			logger.error("Error parsing batch size configuration, using default: ", e);
		}

		//Determine how we should process base URLs
		int readingHistoryBaseUrlType = 0; //0 = Use Localhost, 1 = Use Server URL
		try {
			PreparedStatement getReadingHistoryBaseUrlStmt = dbConn.prepareStatement("SELECT readingHistoryBaseUrl from system_variables", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getReadingHistoryBaseUrlRS = getReadingHistoryBaseUrlStmt.executeQuery();
			if (getReadingHistoryBaseUrlRS.next()) {
				readingHistoryBaseUrlType = getReadingHistoryBaseUrlRS.getInt("readingHistoryBaseUrl");
			}
		} catch (Exception e) {
			processLog.addNote("Unable to determine how base urls should be constructed");
			processLog.addNote(e.toString());
		}

		int numSkipped = 0;
		int numAlreadyUpToDate = 0;
		long startTime = new Date().getTime() / 1000;
		try {
			// Get the number of patrons to update.
			PreparedStatement getNumUsersStmt = dbConn.prepareStatement("SELECT COUNT(*) AS numUsers FROM user WHERE trackReadingHistory=1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet numUsersResults = getNumUsersStmt.executeQuery();
			int numUsersToUpdate = 0;
			if (numUsersResults.next()) {
				numUsersToUpdate = numUsersResults.getInt("numUsers");
				processLog.addNote("Preparing to process " + numUsersToUpdate + " users.");
			}

			numUsersResults.close();
			getNumUsersStmt.close();

			if (numUsersToUpdate > 0) {
				BlockingQueue<Runnable> blockingQueue = new ArrayBlockingQueue<>(Math.min(batchSize * 2, numUsersToUpdate));
				ThreadPoolExecutor executor = new ThreadPoolExecutor(minThreads, maxThreads, 5000, TimeUnit.MILLISECONDS, blockingQueue, new BlockWhenFullPolicy());

				logger.info("Created thread pool: corePoolSize={}, maximumPoolSize={}.", minThreads, maxThreads);
				processLog.addNote("Created thread pool: corePoolSize=" + minThreads + ", maximumPoolSize=" + maxThreads + ".");
				processLog.saveResults();

				// Process the users in batches to avoid memory issues.
				int offset = 0;
				boolean moreUsersToProcess = true;

				while (moreUsersToProcess) {
					PreparedStatement getUsersStmt = dbConn.prepareStatement(
							"SELECT id, ils_barcode, ils_password, lastReadingHistoryUpdate FROM user WHERE trackReadingHistory=1 ORDER BY id LIMIT ?, ?",
							ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY
					);
					getUsersStmt.setInt(1, offset);
					getUsersStmt.setInt(2, batchSize);
					ResultSet userResults = getUsersStmt.executeQuery();

					int usersInBatch = 0;
					while (userResults.next()) {
						usersInBatch++;
						String ilsBarcode = userResults.getString("ils_barcode");
						String ilsPassword = null;
						try {
							ilsPassword = EncryptionUtils.decryptString(userResults.getString("ils_password"), servername, processLog);
						} catch (Exception e) {
							processLog.addNote("Could not decrypt password for " + ilsBarcode + ": " + e);
						}

						if (ilsPassword == null || ilsPassword.isEmpty()) {
							numSkipped++;
							processLog.incSkipped();
							continue;
						}

						if (startTime - userResults.getLong("lastReadingHistoryUpdate") < (23 * 60 * 60)){
							// Update a user's reading history every 23 hours as a buffer and to make sure the user's reading history stays updated.
							// If the user has viewed his or her reading history within 23 hours, then no update is needed.
							numAlreadyUpToDate++;
							processLog.incSkipped();
							continue;
						}

						UpdateReadingHistoryTask newTask = new UpdateReadingHistoryTask(aspenUrl, readingHistoryBaseUrlType, ilsBarcode, ilsPassword, processLog, logger);
						executor.execute(newTask);
					}

					userResults.close();
					getUsersStmt.close();

					processLog.addNote("Submitted batch #" + (offset/batchSize) + " (offset=" + offset + "), queued " + usersInBatch + " tasks" + ".");
					processLog.saveResults();

					// If fewer users than the batch size, the process has completed.
					moreUsersToProcess = (usersInBatch == batchSize);
					offset += batchSize;
				}

				long lastThreadsCompleted = 0;
				int numTimesCompletedThreadsHasNotChanged = 0;
				while ((executor.getCompletedTaskCount() + numSkipped + numAlreadyUpToDate) < numUsersToUpdate) {
					long completed = executor.getCompletedTaskCount();
					if (lastThreadsCompleted != (completed + numSkipped)) {
						numTimesCompletedThreadsHasNotChanged = 0;
						lastThreadsCompleted = (completed + numSkipped);
					} else {
						numTimesCompletedThreadsHasNotChanged++;
					}
					if (numTimesCompletedThreadsHasNotChanged == 10) {
						processLog.incErrors("Number of threads completed has not changed for 10 minutes and looks stuck.");
						break;
					}
					processLog.addNote("Waiting on task completion: completed=" + completed + "/" + numUsersToUpdate + ", skipped=" + numSkipped + ".");
					processLog.saveResults();
					logger.debug("Num Users To Update = {}; Completed Task Count = {}; Num Skipped = {}; Num already up to date = {}.", numUsersToUpdate, completed, numSkipped, numAlreadyUpToDate);
					try {
						Thread.sleep(60000);
					} catch (InterruptedException e) {
						logger.error("Sleep was interrupted:", e);
					}
				}
				logger.debug("Finished processing all threads.");

				executor.shutdownNow();

				int largestPool = executor.getLargestPoolSize();
				logger.info("Largest thread pool size reached: {}.", largestPool);
				processLog.addNote("Largest thread pool size reached: " + largestPool + ".");
				processLog.addNote("Skipped due to NULL password: " + numSkipped + ".");
				processLog.addNote("Skipped due to login unsuccessful: " + UpdateReadingHistoryTask.getLoginUnsuccessfulCount() + ".");
				processLog.addNote("Skipped due to user not found: " + UpdateReadingHistoryTask.getUserNotFoundCount() + ".");
				processLog.saveResults();
			}
		} catch (SQLException e) {
			processLog.incErrors("Unable get a list of users that need to have their reading history updated: ", e);
		}

		processLog.setFinished();
		processLog.saveResults();
	}

	/**
	 * A RejectedExecutionHandler that blocks the submitting thread when the pool and queue are full,
	 * rather than rejecting the task or running it on the calling thread.
	 */
	private static class BlockWhenFullPolicy implements RejectedExecutionHandler {
		/**
		 * Called when the ThreadPoolExecutor cannot accept a task via execute().
		 * This implementation blocks until the queue has space, then enqueues the task.
		 *
		 * @param r The runnable task requested to be executed.
		 * @param executor The executor attempting to execute this task.
		 * @throws RejectedExecutionException Thrown exception if interrupted while waiting to enqueue.
		 */
		@Override
		public void rejectedExecution(Runnable r, ThreadPoolExecutor executor) {
			try {
				executor.getQueue().put(r);
			} catch (InterruptedException e) {
				Thread.currentThread().interrupt();
				throw new RejectedExecutionException("Interrupted while enqueuing task:", e);
			}
		}
	}
}
