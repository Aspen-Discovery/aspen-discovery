package com.peakc.marmot;

import java.io.*;
import java.nio.channels.FileChannel;
import java.security.MessageDigest;

public class Util {
	

	private static void copyFile(File sourceFile, File destFile) throws IOException {
		if (!destFile.exists()) {
			destFile.createNewFile();
		}

		FileChannel source = null;
		FileChannel destination = null;

		try {
			source = new FileInputStream(sourceFile).getChannel();
			destination = new FileOutputStream(destFile).getChannel();
			destination.transferFrom(source, 0, source.size());
		} finally {
			if (source != null) {
				source.close();
			}
			if (destination != null) {
				destination.close();
			}
		}
	}

	/**
	 * Copys the file to another directory. If there is already a file with that
	 * name, it will not overwrite the existing file. Instead, it will modify the
	 * name by appending a numeric number until it finds a unique name. i.e.
	 * File_1.pdf File_2.pdf etc Up to 99 attempts will be made.
	 * 
	 * @param fileToCopy
	 * @param directoryToCopyTo
	 * @return
	 */
	static CopyNoOverwriteResult copyFileNoOverwrite(File fileToCopy, File directoryToCopyTo) throws IOException {
		CopyNoOverwriteResult result = new CopyNoOverwriteResult();
		if (!directoryToCopyTo.exists()) {
			throw new IOException("Directory to copy to does not exist.");
		}
		int numTries = 0;
		String newFilename = fileToCopy.getName();
		String baseFilename = newFilename.substring(0, newFilename.indexOf("."));
		String extension = newFilename.substring(newFilename.indexOf(".") + 1, newFilename.length());
		File newFile = new File(directoryToCopyTo + File.separator + newFilename);
		while (newFile.exists() && numTries < 100) {
			// Check to see if the checksums of the file are the same and if so,
			// return this name
			// without copying.
			if (newFile.length() == fileToCopy.length()) {
				try {
					String newFileChecksum = getMD5Checksum(newFile);
					String fileToCopyChecksum = getMD5Checksum(newFile);
					if (newFileChecksum.equals(fileToCopyChecksum)) {
						result.setCopyResult(CopyNoOverwriteResult.CopyResult.FILE_ALREADY_EXISTS);
						result.setNewFilename(newFilename);
						return result;
					}
				} catch (Exception e) {
					throw new IOException("Error getting checksums for files", e);
				}
			}

			numTries++;
			// Get a new name
			newFilename = baseFilename + "_" + numTries + "." + extension;
			newFile = new File(directoryToCopyTo + File.separator + newFilename);

		}

		if (newFile.exists()) {
			// We ran out of tries
			throw new IOException("Unable to copy file due to not finding unique name.");
		}
		// We found a name that hasn't been used, copy it
		Util.copyFile(fileToCopy, newFile);
		result.setCopyResult(CopyNoOverwriteResult.CopyResult.FILE_COPIED);
		result.setNewFilename(newFilename);
		return result;
	}
	
	private static byte[] createChecksum(File filename) throws Exception {
		InputStream fis = new FileInputStream(filename);

		byte[] buffer = new byte[1024];
		MessageDigest complete = MessageDigest.getInstance("MD5");
		int numRead;

		do {
			numRead = fis.read(buffer);
			if (numRead > 0) {
				complete.update(buffer, 0, numRead);
			}
		} while (numRead != -1);

		fis.close();
		return complete.digest();
	}

	// see this How-to for a faster way to convert
	// a byte array to a HEX string
	private static String getMD5Checksum(File filename) throws Exception {
		byte[] b = createChecksum(filename);
		String result = "";

		for (int i = 0; i < b.length; i++) {
			result += Integer.toString((b[i] & 0xff) + 0x100, 16).substring(1);
		}
		return result;
	}
}
