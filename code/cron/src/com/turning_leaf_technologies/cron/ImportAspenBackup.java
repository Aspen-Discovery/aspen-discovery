package com.turning_leaf_technologies.cron;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.jsoup.internal.StringUtil;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.sql.SQLException;
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
						es.execute(() -> importFile(fileToImport, dbConn, debug, configIni, processLog));
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

	private void importFile(File fileToImport, Connection dbConn, boolean debug, Ini configIni, CronProcessLogEntry processLog) {
		boolean exportData = true;

		BufferedReader reader;

		try {
			if (debug) {
				System.out.println("PROCESSING: " + fileToImport.getName());
			}

			reader = new BufferedReader(new FileReader(fileToImport));
			String line = reader.readLine();

			StringBuilder statementToExecute = new StringBuilder();
			while (line != null) {
				if (line.isEmpty() || line.startsWith("-") || line.startsWith("/")){
					//This is a comment or blank line that we can ignore.
					if (debug) {
						System.out.println("SKIPPING: " + line);
					}
				}else{
					statementToExecute.append(line).append("\n");
					if (line.endsWith(";")){
						try {
							//reset the statement
							boolean result = dbConn.prepareCall(statementToExecute.toString()).execute();
							if (debug) {
								System.out.println("EXECUTED: " + statementToExecute);
							}
							statementToExecute = new StringBuilder();
						} catch (SQLException e) {
							System.out.println("ERROR: " + line);
							System.out.println(e.toString());
						}
					}

				}
				// read next line
				line = reader.readLine();
			}

			reader.close();
		} catch (IOException e) {
			processLog.incErrors("Error loading import file", e);
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
			tarExecutable = "cmd /c tar";
			executeCommand(tarExecutable + " -xzf " + latestBackupFile.getName() , debug, backupDir.getAbsoluteFile());
		}else{
			tarExecutable = "tar";
			executeCommand(tarExecutable + " -xzf " + latestBackupFile.getName(), debug, backupDir);
		}

		File[] filesToCheck = backupDir.listFiles((dir, name) -> name.toLowerCase().endsWith("sql"));
		if (filesToCheck == null) {
			processLog.addNote("No files extracted from tarball");
			return new File[]{};
		}
		return filesToCheck;
	}

	public void executeCommand(String command, boolean log, File activeDirectory) throws IOException, InterruptedException {
		if (log) {
			if (activeDirectory == null) {
				System.out.println("RUNNING: " + command);
			}else{
				System.out.println("RUNNING: " + command + " in " + activeDirectory);
			}
		}

		Process process;
		if (activeDirectory == null) {
			process = Runtime.getRuntime().exec(command);
		}else{
			String[] args = command.split(" ");
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
