package com.turning_leaf_technologies.file;

import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;
import java.util.zip.GZIPInputStream;

/**
 * This utility extracts files and directories of a standard zip file to
 * a destination directory.
 *
 * @author www.codejava.net
 */
public class UnzipUtility {
	/**
	 * Size of the buffer to read/write data
	 */
	private static final int BUFFER_SIZE = 4096;

	/**
	 * Extracts a zip file specified by the zipFilePath to a directory specified by
	 * destinationDirectory (will be created if it does not exist)
	 *
	 * @param zipFilePath The path to the zip file
	 * @param destinationDirectory The destination where the zip should be extracted
	 * @throws IOException an exception if the file cannot be read
	 */
	public static void unzip(String zipFilePath, String destinationDirectory) throws IOException {
		File destinationDir = new File(destinationDirectory);
		if (!destinationDir.exists()) {
			if (!destinationDir.mkdir()) {
				return;
			}
		}
		ZipInputStream zipIn = new ZipInputStream(new FileInputStream(zipFilePath));
		ZipEntry entry = zipIn.getNextEntry();
		// iterates over entries in the zip file
		while (entry != null) {
			String filePath = destinationDirectory + File.separator + entry.getName();
			if (!entry.isDirectory()) {
				// if the entry is a file, extracts it
				extractFile(zipIn, filePath);
			} else {
				// if the entry is a directory, make the directory
				File dir = new File(filePath);
				if (!dir.mkdir()){
					return;
				}
			}
			zipIn.closeEntry();
			entry = zipIn.getNextEntry();
		}
		zipIn.close();
	}

	/**
	 * Extracts a zip entry (file entry)
	 *
	 * @param zipIn The zip input stream being processed
	 * @param filePath The path to extract to
	 * @throws IOException An exception if the file cannot be processed
	 */
	private static void extractFile(ZipInputStream zipIn, String filePath) throws IOException {
		BufferedOutputStream bos = new BufferedOutputStream(new FileOutputStream(filePath));
		byte[] bytesIn = new byte[BUFFER_SIZE];
		int read;
		while ((read = zipIn.read(bytesIn)) != -1) {
			bos.write(bytesIn, 0, read);
		}
		bos.close();
	}

	//gUnzip is currently not utilized and needs testing when applicable
	public static void gUnzip(File source, File target) throws IOException {
		try (GZIPInputStream gis = new GZIPInputStream(new FileInputStream(source));
		     FileOutputStream fos = new FileOutputStream(target)) {
			// copy GZIPInputStream to FileOutputStream
			byte[] buffer = new byte[BUFFER_SIZE];
			int read;
			while ((read = gis.read(buffer)) != -1) {
				fos.write(buffer, 0, read);
			}
		}
	}
}