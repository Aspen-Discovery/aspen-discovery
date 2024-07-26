package com.turning_leaf_technologies.cron;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;

import java.io.File;
import java.io.IOException;
import java.sql.Connection;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.concurrent.Executors;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;

@SuppressWarnings("unused")
public class ImportAspenBackup implements IProcessHandler {

	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Backup Aspen", dbConn, logger);
		processLog.saveResults();

		String curDateTime = new SimpleDateFormat("yyyyMMddHHmmss").format(new Date());

		String backupDirName = "/data/aspen-discovery/" + servername + "/sql_backup";
		boolean debug = true;

		try {
			File backupDir = new File(backupDirName);
			if (backupDir.exists()) {
				//Check to see if we need to extract files from a tarball or if we already have sql files
				File[] filesToImport = backupDir.listFiles((dir, name) -> name.toLowerCase().endsWith("sql"));
				if (filesToImport == null || filesToImport.length == 0) {
					// We do need to extract from the tarball
					filesToImport = extractSqlFromLatestBackup(backupDir, debug, configIni, processLog);
				}

				if (filesToImport.length > 0) {
					//Import each sql file using multiple threads.
					ThreadPoolExecutor es = (ThreadPoolExecutor) Executors.newFixedThreadPool(5);
					for (File fileToImport : filesToImport) {
						//Since we are actively updating cron_log and cron_process log we can't import those
						if (fileToImport.getName().endsWith("cron_log.sql") || fileToImport.getName().endsWith("cron_process_log.sql")) {
							//noinspection ConstantValue
							if (debug) {
								System.out.println("Skipping cron log table");
							}
							if (!fileToImport.delete()) {
								processLog.incErrors("Unable to delete " + fileToImport.getName());
							}
						}else{
							es.execute(() -> importFile(fileToImport, backupDir, debug, configIni, processLog));
						}
					}
					es.shutdown();
					while (true) {
						try {
							boolean terminated = es.awaitTermination(1, TimeUnit.MINUTES);
							if (terminated){
								break;
							}
						} catch (InterruptedException e) {
							logger.error("Error waiting for all extracts to finish");
						}
					}
				}
			}else{
				processLog.incErrors("Backup directory did not exist");
			}

		} catch (IOException e) {
			processLog.incErrors("IO Exception importing Aspen backup", e);
		} catch (InterruptedException e) {
			processLog.incErrors("Interrupted Exception  importing Aspen backup", e);
		}

		processLog.setFinished();
		processLog.saveResults();
	}

	private void importFile(File fileToImport, File backupDir, boolean debug, Ini configIni, CronProcessLogEntry processLog) {
		if (debug) {
			System.out.println("PROCESSING: " + fileToImport.getName());
		}

		String dbUser = configIni.get("Database", "database_user");
		String dbPassword = configIni.get("Database", "database_password");
		String dbName = configIni.get("Database", "database_aspen_dbname");
		String dbHost = configIni.get("Database", "database_aspen_host");
		String dbPort = configIni.get("Database", "database_aspen_dbport");

		String[] importCommand;
		String operatingSystem = configIni.get("System", "operatingSystem");
		if (operatingSystem.equals("windows")) {
			importCommand = new String[]{"cmd", "/c", "\"mysql --force" + "-u" + dbUser + " -p" + dbPassword + " -h" + dbHost + " -P" + dbPort + " -D" + dbName + " < " + fileToImport.getName() + "\""};
		}else{
			importCommand = new String[]{"mysql", "--force", "-u" + dbUser, " -p" + dbPassword, " -h" + dbHost, " -P" + dbPort, dbName, "< " + fileToImport.getName()};
		}

		try {
			executeCommand(importCommand, debug, backupDir);
		} catch (IOException | InterruptedException e) {
			processLog.incErrors("Exception loading " + fileToImport.getName(), e);
		}

		if (!fileToImport.delete()) {
			processLog.incErrors("Unable to delete " + fileToImport.getName());
		}
	}

	private File[] extractSqlFromLatestBackup(File backupDir, boolean debug, Ini configIni, CronProcessLogEntry processLog) throws IOException, InterruptedException {
		File latestBackupFile = null;
		long latestBackupTime = 0;
		File[] backupFiles = backupDir.listFiles((dir, name) -> name.toLowerCase().endsWith("tar.gz"));
		if (backupFiles == null || backupFiles.length == 0) {
			processLog.addNote("No backup found");
			// We do need to extract from the tarball
			return new File[]{};
		}else{
			for (File backupFile : backupFiles) {
				if (backupFile.lastModified() > latestBackupTime) {
					latestBackupFile = backupFile;
					latestBackupTime = backupFile.lastModified();
				}
			}
		}

		if (latestBackupFile == null) {
			processLog.addNote("No backup found");
			return new File[]{};
		}

		String operatingSystem = configIni.get("System", "operatingSystem");
		String tarExecutable;
		String cdExecutable;
		if (operatingSystem.equals("windows")) {
			executeCommand(new String[]{"cmd", "/c", "tar", "-xzf", latestBackupFile.getName()}, debug, backupDir.getAbsoluteFile());
		}else{
			executeCommand(new String[]{"tar","-xzf", latestBackupFile.getName()}, debug, backupDir);
		}

		File[] filesToCheck = backupDir.listFiles((dir, name) -> name.toLowerCase().endsWith("sql"));
		if (filesToCheck == null) {
			processLog.addNote("No files extracted from tarball");
			return new File[]{};
		}
		return filesToCheck;
	}

	public void executeCommand(String[] command, boolean log, File activeDirectory) throws IOException, InterruptedException {
		if (log) {
			if (activeDirectory == null) {
				System.out.println("RUNNING: " + String.join(" ", command));
			}else{
				System.out.println("RUNNING: " + String.join(" ", command) + " in " + activeDirectory);
			}
		}

		Process process;
		if (activeDirectory == null) {
			process = Runtime.getRuntime().exec(command);
		}else{
			ProcessBuilder pb = new ProcessBuilder(command);
			pb.directory(activeDirectory);
			process = pb.start();
		}
		int exitCode = process.waitFor();
		if (log) {
			StringBuilder output = new StringBuilder();
			byte[] buffer = new byte[1024];
			while (process.getInputStream().read(buffer) != -1) {
				output.append(new String(buffer));
			}
			System.out.println("RESULT: " + exitCode + "\n" + output);
		}
	}

}
