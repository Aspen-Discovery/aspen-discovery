package com.turning_leaf_technologies.cron;

import java.io.File;
import java.sql.Connection;
import java.util.Date;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

@SuppressWarnings("unused")
public class BookcoverCleanup implements IProcessHandler {
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Bookcover Cleanup");
		processLog.saveToDatabase(dbConn, logger);

		String coverPath = configIni.get("Site", "coverPath");
		String[] coverPaths = new String[] { "/small", "/medium", "/large" };
		long currentTime = new Date().getTime();

		for (String path : coverPaths) {
			int numFilesDeleted = 0;

			String fullPath = coverPath + path;
			File coverDirectoryFile = new File(fullPath);
			if (!coverDirectoryFile.exists()) {
				processLog.incErrors();
				processLog.addNote("Directory " + coverDirectoryFile.getAbsolutePath() + " does not exist.  Please check configuration file.");
				processLog.saveToDatabase(dbConn, logger);
			} else {
				processLog.addNote("Cleaning up covers in " + coverDirectoryFile.getAbsolutePath());
				processLog.saveToDatabase(dbConn, logger);
				File[] filesToCheck = coverDirectoryFile.listFiles((dir, name) -> name.toLowerCase().endsWith("jpg") || name.toLowerCase().endsWith("png"));
				if (filesToCheck != null) {
					for (File curFile : filesToCheck) {
						//Remove any files created more than 2 weeks ago.
						if (curFile.lastModified() < (currentTime - 2 * 7 * 24 * 3600 * 1000)) {
							if (curFile.delete()) {
								numFilesDeleted++;
								processLog.incUpdated();
							} else {
								processLog.incErrors();
								processLog.addNote("Unable to delete file " + curFile.toString());
							}
						}
					}
				}
				if (numFilesDeleted > 0) {
					processLog.addNote("\tRemoved " + numFilesDeleted + " files from " + fullPath + ".");
				}
			}
		}
		processLog.setFinished();
		processLog.saveToDatabase(dbConn, logger);
	}
}
