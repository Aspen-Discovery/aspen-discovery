package org.vufind;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.Reader;
import java.io.StringWriter;
import java.io.Writer;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.nio.channels.FileChannel;
import java.security.MessageDigest;
import java.util.HashSet;
import java.util.List;

import org.apache.log4j.Logger;
import org.vufind.CopyNoOverwriteResult.CopyResult;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;

public class Util {
	

	public static String convertStreamToString(InputStream is) throws IOException {
		/*
		 * To convert the InputStream to String we use the Reader.read(char[]
		 * buffer) method. We iterate until the Reader return -1 which means there's
		 * no more data to read. We use the StringWriter class to produce the
		 * string.
		 */
		if (is != null) {
			Writer writer = new StringWriter();

			char[] buffer = new char[1024];
			try {
				Reader reader = new BufferedReader(new InputStreamReader(is, "UTF-8"));
				int n;
				while ((n = reader.read(buffer)) != -1) {
					writer.write(buffer, 0, n);
				}
			} finally {
				is.close();
			}
			return writer.toString();
		} else {
			return "";
		}
	}

	public static boolean doSolrUpdate(String baseIndexUrl, String body) {
		try {
			HttpURLConnection conn = null;
			OutputStreamWriter wr = null;
			URL url = new URL(baseIndexUrl + "/update/");
			conn = (HttpURLConnection) url.openConnection();
			conn.setDoOutput(true);
			conn.addRequestProperty("Content-Type", "text/xml");
			wr = new OutputStreamWriter(conn.getOutputStream());
			wr.write(body);
			wr.flush();

			// Get the response
			InputStream _is;
			boolean doOuptut = false;
			if (conn.getResponseCode() == 200) {
				_is = conn.getInputStream();
			} else {
				System.out.println("Error in update");
				System.out.println("  " + body);
				/* error from server */
				_is = conn.getErrorStream();
				doOuptut = true;
			}
			BufferedReader rd = new BufferedReader(new InputStreamReader(_is));
			String line;
			while ((line = rd.readLine()) != null) {
				if (doOuptut) System.out.println(line);
			}
			wr.close();
			rd.close();
			conn.disconnect();

			return true;
		} catch (MalformedURLException e) {
			System.out.println("Invalid url updating index " + e.toString());
			return false;
		} catch (IOException e) {
			System.out.println("IO Exception updating index " + e.toString());
			e.printStackTrace();
			return false;
		}
	}

	public static String getCRSeparatedString(List<String> values) {
		StringBuffer crSeparatedString = new StringBuffer();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	public static String getCRSeparatedString(HashSet<String> values) {
		StringBuffer crSeparatedString = new StringBuffer();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	public static void copyFile(File sourceFile, File destFile) throws IOException {
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
	public static CopyNoOverwriteResult copyFileNoOverwrite(File fileToCopy, File directoryToCopyTo) throws IOException {
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
						result.setCopyResult(CopyResult.FILE_ALREADY_EXISTS);
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
		result.setCopyResult(CopyResult.FILE_COPIED);
		result.setNewFilename(newFilename);
		return result;
	}
	
	

	public static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		if (value.contains(";")){
			value = value.substring(0, value.indexOf(";"));
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}

	public static byte[] createChecksum(File filename) throws Exception {
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
	public static String getMD5Checksum(File filename) throws Exception {
		byte[] b = createChecksum(filename);
		String result = "";

		for (int i = 0; i < b.length; i++) {
			result += Integer.toString((b[i] & 0xff) + 0x100, 16).substring(1);
		}
		return result;
	}

	public static URLPostResponse getURL(String url, Logger logger) {
		URLPostResponse retVal;
		try {
			URL emptyIndexURL = new URL(url);
			HttpURLConnection conn = (HttpURLConnection) emptyIndexURL.openConnection();
			logger.debug("Getting From URL " + url);
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {

					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			
			StringBuffer response = new StringBuffer();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line + "\r\n");
				}

				rd.close();
				retVal = new URLPostResponse(true, 200, response.toString());
			} else {
				logger.error("Received error " + conn.getResponseCode() + " getting " + url);
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}

				rd.close();
				retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		}
		return retVal;
	}

	public static URLPostResponse postToURL(String url, String postData, String contentType, String referer, Logger logger) {
		URLPostResponse retVal;
		HttpURLConnection conn = null;
		try {
			URL emptyIndexURL = new URL(url);
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			conn.setConnectTimeout(1000);
			conn.setReadTimeout(300000);
			logger.debug("Posting To URL " + url + (postData != null && postData.length() > 0 ? "?" + postData : ""));

			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {

					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			conn.setDoInput(true);
			if (referer != null){
				conn.setRequestProperty("Referer", referer);
			}
			conn.setRequestMethod("POST");
			if (postData != null && postData.length() > 0) {
				conn.setRequestProperty("Content-Type", contentType + "; charset=utf-8");
				conn.setRequestProperty("Content-Language", "en-US");
				conn.setRequestProperty("Connection", "keep-alive");

				conn.setDoOutput(true);
				OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
				wr.write(postData);
				wr.flush();
				wr.close();
			}

			StringBuffer response = new StringBuffer();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}

				rd.close();
				retVal = new URLPostResponse(true, 200, response.toString());
			} else {
				logger.error("Received error " + conn.getResponseCode() + " posting to " + url);
				logger.info(postData);
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}

				rd.close();
				
				if (response.length() == 0){
					//Try to load the regular body as well
					// Get the response
					BufferedReader rd2 = new BufferedReader(new InputStreamReader(conn.getInputStream()));
					while ((line = rd2.readLine()) != null) {
						response.append(line);
					}

					rd.close();
				}
				retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		}finally{
			if (conn != null) conn.disconnect();
		}
		return retVal;
	}

	public static boolean isNumeric(String str)
	{
		for (char c : str.toCharArray())
		{
			if (!Character.isDigit(c)) return false;
		}
		return true;
	}

	public static String trimTo(int maxCharacters, String stringToTrim) {
		if (stringToTrim == null) {
			return null;
		}
		if (stringToTrim.length() > maxCharacters) {
			stringToTrim = stringToTrim.substring(0, maxCharacters);
		}
		return stringToTrim.trim();
	}

	public static boolean compareStrings(String curLine1, String curLine2) {
		return curLine1 == null && curLine2 == null || !(curLine1 == null || curLine2 == null) && curLine1.equals(curLine2);
	}
}
