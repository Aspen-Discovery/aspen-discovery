package com.turning_leaf_technologies.cron;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.TreeSet;
import java.util.concurrent.Executors;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;

@SuppressWarnings("unused")
public class BackupAspen implements IProcessHandler {
	private static final String[] VALID_EXTENSIONS = {".sql", ".tar", ".tar.gz", ".sql.gz"};

	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Backup Aspen", dbConn, logger);
		processLog.saveResults();

		String curDateTime = new SimpleDateFormat("yyyyMMddHHmmss").format(new Date());

		String backupDirName = "/data/aspen-discovery/" + servername + "/sql_backup";
		boolean debug = false;

		try {
			File backupDir = createBackupDirIfNotExists(backupDirName);
			cleanupOldBackups(backupDir);
			String backupFilename = createTarBackup(servername, configIni, backupDirName, curDateTime, debug);

			//Get a list of tables to export
			TreeSet<String> tableNames = getTableNames(dbConn);

			//Use multiple threads to export tables, through some experimentation it looks like 5 threads is the quickest  overall
			ThreadPoolExecutor es = (ThreadPoolExecutor) Executors.newFixedThreadPool(5);
			for (String tableName : tableNames) {
				es.execute(() -> dumpTable(tableName, backupDir, backupFilename, servername, curDateTime, debug, configIni, processLog));
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

			// zip the tar
			gzipBackup(backupDir, backupFilename, debug, processLog, configIni);
			
		} catch (IOException e) {
			processLog.incErrors("IO Exception backing up Aspen", e);
		} catch (InterruptedException e) {
			processLog.incErrors("Interrupted Exception backing up Aspen", e);
		} catch (SQLException e) {
			processLog.incErrors("SQL Exception backing up Aspen", e);
		}

		processLog.setFinished();
		processLog.saveResults();
	}

	private void dumpTable(String tableName, File backupDir, String backupFile, String serverName, String exportDateTime, boolean debug, Ini configIni, CronProcessLogEntry processLog) {
		boolean exportData = true;
		//noinspection RedundantIfStatement
		if (tableName.equals("session") || tableName.equals("cached_values")) {
			exportData = false;
		}

		String dbUser = configIni.get("Database", "database_user");
		String dbPassword = configIni.get("Database", "database_password");
		String dbName = configIni.get("Database", "database_aspen_dbname");
		String dbHost = configIni.get("Database", "database_aspen_host");
		String dbPort = configIni.get("Database", "database_aspen_dbport");

		String operatingSystem = configIni.get("System", "operatingSystem");
		String dumpExecutable;
		if (operatingSystem.equals("windows")) {
			dumpExecutable = "cmd /c mysqldump";
		}else{
			dumpExecutable = "mariadb-dump";
		}

		String exportFile = serverName + "." + exportDateTime + "." + tableName + ".sql";
		String fullExportFilePath = backupDir + "/" + exportFile;
		String dumpCommand;
		if (exportData) {
			dumpCommand = dumpExecutable + " -u" + dbUser + " -p" + dbPassword + " -h" + dbHost + " -P" + dbPort + " " + dbName + " " + tableName + " -r " + fullExportFilePath;
		}else{
			dumpCommand = dumpExecutable + " -u" + dbUser + " -p" + dbPassword + " -h" + dbHost + " -P" + dbPort + " --no-data " + dbName + " " + tableName + " -r " + fullExportFilePath;
		}

		try {
			executeCommand(dumpCommand, debug, null);
			addFileToTar(backupDir, backupFile, debug, processLog, fullExportFilePath, exportFile, configIni);
			processLog.incUpdated();
			processLog.saveResults();
		} catch (IOException | InterruptedException e) {
			processLog.incErrors("Could not dump table " + tableName, e);
		}
	}

	private synchronized void addFileToTar(File backupDir, String backupFile, boolean debug, CronProcessLogEntry processLog, String fullExportFilePath, String exportFile, Ini configIni) throws IOException, InterruptedException {
		File fullExportFile = new File(fullExportFilePath);
		if (fullExportFile.exists()) {
			String operatingSystem = configIni.get("System", "operatingSystem");
			String tarExecutable;
			if (operatingSystem.equals("windows")) {
				tarExecutable = "C:\\cygwin64\\bin\\tar.exe";
				executeCommand(tarExecutable + " -rf " + new File(backupFile).getName() + " " + exportFile, debug, backupDir.getAbsoluteFile());
			}else{
				tarExecutable = "gzip";
				executeCommand(tarExecutable + " -rf " + backupFile + " " + exportFile, debug, backupDir);
			}

			if (!fullExportFile.delete()) {
				processLog.incErrors("Could not delete " + exportFile);
			}
		}
	}

	private void gzipBackup(File backupDir, String backupFile, boolean debug, CronProcessLogEntry processLog, Ini configIni) throws IOException, InterruptedException {
		String operatingSystem = configIni.get("System", "operatingSystem");
		String gzipExecutable;
		if (operatingSystem.equals("windows")) {
			gzipExecutable = "C:\\cygwin64\\bin\\gzip.exe";
			executeCommand(gzipExecutable + " " + new File(backupFile).getName(), debug, backupDir.getAbsoluteFile());
		}else{
			gzipExecutable = "gzip";
			executeCommand(gzipExecutable + " " + backupFile, debug, backupDir);
		}
	}

	private TreeSet<String> getTableNames(Connection dbConn) throws SQLException {
		PreparedStatement getTableNamesStmt = dbConn.prepareStatement("SHOW TABLES", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet getTableNamesRs = getTableNamesStmt.executeQuery();
		TreeSet<String> tableNames = new TreeSet<>();
		while (getTableNamesRs.next()){
			tableNames.add(getTableNamesRs.getString(1));
		}
		getTableNamesRs.close();
		getTableNamesStmt.close();

		return tableNames;
	}

	private File createBackupDirIfNotExists(String backupDirName) throws IOException {
		File backupDir = new File(backupDirName);
		if (!backupDir.exists()) {
			if (!backupDir.mkdirs()) {
				throw new IOException("Failed to create backup directory: " + backupDirName);
			}
		}
		return backupDir;
	}

	private void cleanupOldBackups(File backupDir) throws IOException {
		long twoDaysInMillis = 2L * 24 * 60 * 60 * 1000;
		long earliestTimeToKeep = System.currentTimeMillis() - twoDaysInMillis;

		File[] backupFiles = backupDir.listFiles();
		if (backupFiles != null) {
			for (File file : backupFiles) {
				if (isValidBackupFile(file) && shouldBeDeleted(file, earliestTimeToKeep)) {
					Files.delete(Paths.get(file.getAbsolutePath()));
				}
			}
		}
	}

	private boolean isValidBackupFile(File file) {
		if (file.isDirectory()) {
			return false;
		}

		String fileName = file.getName();
		for (String extension : VALID_EXTENSIONS) {
			if (fileName.endsWith(extension)) {
				return true;
			}
		}
		return false;
	}

	private boolean shouldBeDeleted(File file, long earliestTimeToKeep) {
		long lastModified = file.lastModified();
		return lastModified != 0 && lastModified < earliestTimeToKeep;
	}

	public void executeCommand(String command, boolean log, File activeDirectory) throws IOException, InterruptedException {
		if (log) {
			System.out.println("RUNNING: " + command);
		}

		Process process;
		if (activeDirectory == null) {
			process = Runtime.getRuntime().exec(command);
		}else{
			String[] args = command.split(" ");
			ProcessBuilder pb = new ProcessBuilder(args);
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

	public String createTarBackup(String serverName, Ini configIni, String backupDirName, String curDateTime, boolean debug) throws IOException, InterruptedException {
		String backupFile = backupDirName + "/aspen." + serverName + "." + curDateTime + ".tar";

		// Create the archive
		Path archivePath = Paths.get(backupFile);
		Files.createFile(archivePath);

		return backupFile;
	}
}
