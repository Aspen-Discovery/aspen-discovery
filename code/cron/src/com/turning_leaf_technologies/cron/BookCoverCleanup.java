package com.turning_leaf_technologies.cron;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.LinkOption;
import java.nio.file.attribute.BasicFileAttributes;
import java.nio.file.attribute.FileTime;
import java.sql.Connection;
import java.util.Date;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

@SuppressWarnings("unused")
public class BookCoverCleanup implements IProcessHandler {
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Bookcover Cleanup", dbConn, logger);
		processLog.saveResults();

		String coverPath = configIni.get("Site", "coverPath");
		String[] coverPaths = new String[] { "/small", "/medium", "/large" };
		long currentTime = new Date().getTime();
		long oneWeekAgo = currentTime - (7L * 24 * 3600 * 1000);
		long twoWeeksAgo = currentTime - (2L * 7 * 24 * 3600 * 1000);
		long fourWeeksAgo = currentTime - (4L * 7 * 24 * 3600 * 1000);

		//TODO: Get a smarter list of covers to remove
		//Any default covers created more than a week ago
		//Any non default covers accessed more than 2 weeks ago
		//Any non default covers accessed created more than 4 weeks ago

		for (String path : coverPaths) {
			int numFilesDeleted = 0;

			String fullPath = coverPath + path;
			File coverDirectoryFile = new File(fullPath);
			if (!coverDirectoryFile.exists()) {
				processLog.incErrors("Directory " + coverDirectoryFile.getAbsolutePath() + " does not exist.  Please check configuration file.");
			} else {
				processLog.addNote("Cleaning up covers in " + coverDirectoryFile.getAbsolutePath());
				processLog.saveResults();
				File[] filesToCheck = coverDirectoryFile.listFiles((dir, name) -> name.toLowerCase().endsWith("jpg") || name.toLowerCase().endsWith("png"));
				if (filesToCheck != null) {
					for (File curFile : filesToCheck) {
						//Remove any files created more than 2 weeks ago.
						try {
							BasicFileAttributes fileAttributes = Files.readAttributes(curFile.toPath(), BasicFileAttributes.class, LinkOption.NOFOLLOW_LINKS);
							FileTime dateCreated = fileAttributes.creationTime();
							FileTime lastAccessed = fileAttributes.lastAccessTime();
							if (lastAccessed.toMillis() < twoWeeksAgo) {
								if (curFile.delete()) {
									numFilesDeleted++;
									processLog.incUpdated();
								} else {
									processLog.incErrors("Unable to delete file " + curFile);
								}
							}else if (dateCreated.toMillis() < fourWeeksAgo) {
								if (curFile.delete()) {
									numFilesDeleted++;
									processLog.incUpdated();
								} else {
									processLog.incErrors("Unable to delete file " + curFile);
								}
							}
						} catch (IOException e) {
							throw new RuntimeException(e);
						}

					}
				}
				if (numFilesDeleted > 0) {
					processLog.addNote("\tRemoved " + numFilesDeleted + " files from " + fullPath + ".");
				}
			}
		}
		processLog.setFinished();
		processLog.saveResults();
	}
}
