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
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;

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
	
	public static boolean doSolrUpdate(String baseIndexUrl, String body){
		try {
			HttpURLConnection conn = null;
			OutputStreamWriter wr = null;
			URL url = new URL(baseIndexUrl + "/update/");
			conn = (HttpURLConnection)url.openConnection();
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
			System.out.println("Invalid url optimizing genealogy index " + e.toString());
			return false;
		} catch (IOException e) {
			System.out.println("IO Exception optimizing genealogy index " + e.toString());
			e.printStackTrace();
			return false;
		}
	}
	
	public static String getCRSeparatedString(List<String> values){
		StringBuffer crSeparatedString = new StringBuffer();
		for(String curValue : values){
			if (crSeparatedString.length() > 0){
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}
	
	public static String getCRSeparatedString(HashSet<String> values){
		StringBuffer crSeparatedString = new StringBuffer();
		for(String curValue : values){
			if (crSeparatedString.length() > 0){
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
}
