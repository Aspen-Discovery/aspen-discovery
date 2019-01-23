package org.vufind;

import org.apache.log4j.Logger;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.*;

/**
 * Creates Sitemaps for publication to Google and other search engines.
 * This class contains information to build all of the sitemaps for all scopes.
 *
 * Created by jabedo on 9/23/2016.
 */
class SiteMap {

	private Logger logger;
	private int maxPopularTitles;
	private int maxUniqueTitles;
	private Connection vufindConn;
	private HashMap<Long, ArrayList<Long>> librariesByHomeLocation;
	private ArrayList<SiteMapEntry> uniqueItemsToWrite;
	private int fileID;
	private int countTracker;
	private int currentSiteMapCount;
	private Long libraryIdToWrite;
	private String scopeName;
	private String filePath;
	private final int maxGoogleSiteMapCount = 50000;

	SiteMap(Logger log, Connection connection, int maxUnique, int maxPopular) {
		this.logger = log;
		this.vufindConn = connection;
		this.maxPopularTitles = maxPopular;
		this.maxUniqueTitles = maxUnique;
		librariesByHomeLocation = new HashMap<>();
		prepareLocationIds();
	}

	private void prepareLocationIds() {
		try {
			PreparedStatement getLibraryForHomeLocation = vufindConn.prepareStatement("SELECT libraryId, locationId from location");
			ResultSet librariesByHomeLocationRS = getLibraryForHomeLocation.executeQuery();
			while (librariesByHomeLocationRS.next()) {
				Long locationId = librariesByHomeLocationRS.getLong("locationId");
				Long libraryId = librariesByHomeLocationRS.getLong("libraryId");
				if (!librariesByHomeLocation.containsKey(libraryId)) {
					librariesByHomeLocation.put(libraryId, new ArrayList<Long>());
				}
				librariesByHomeLocation.get(libraryId).add(locationId);
			}
			librariesByHomeLocationRS.close();
		} catch (Exception ex) {
			logger.error("Unable to get location Ids");
		}
	}

	private void resetSiteMapDefaults() {
		fileID = 1;
		countTracker = 0;
		currentSiteMapCount = 0;
	}

	void createSiteMaps(String baseUrl, File dataDir, HashMap<Scope, ArrayList<SiteMapEntry>> siteMapsByScope,
	                           HashSet<Long> uniqueGroupedWorks) throws IOException {

		//create a site maps directory if it doesn't exist
		if (!dataDir.exists()) {
			if (!dataDir.mkdirs()) {
				logger.error("Could not create site map directory");
				throw new IOException("Could not create site map directory");
			}
		}
		//update the variables table
		updateVariablesTable();
		//create site map index file
		filePath = dataDir.getPath();
		Date date = new Date();
		boolean isSSL = baseUrl.startsWith("https");
		String urlWithoutProtocol = baseUrl.replace(isSSL ? "https://" : "http://", "");

		for (Scope scope : siteMapsByScope.keySet()) {
			SiteMapIndex siteMapIndex = new SiteMapIndex(logger);
			scopeName = scope.getScopeName();


			String scopedUrl = isSSL ? "https://" : "http://";
			if (siteMapsByScope.size() > 1){
				scopedUrl += scopeName;
				String[] urlParts = urlWithoutProtocol.split("\\.");
				for (int i = (urlParts.length == 2 ? 0 : 1); i < urlParts.length; i++){
					scopedUrl += "." + urlParts[i];
				}
			}else{
				scopedUrl = baseUrl;
			}

			libraryIdToWrite = scope.getLibraryId();

			ArrayList<SiteMapEntry> siteMapEntries = siteMapsByScope.get(scope);

			//separate the site maps into unique and popular
			ArrayList<SiteMapEntry> unique = new ArrayList<>();
			SortedSet<SiteMapEntry> popular = new TreeSet<>();
			regroupSiteMapGroups(unique, popular, siteMapEntries, uniqueGroupedWorks);

			uniqueItemsToWrite = unique;
			resetSiteMapDefaults();
			String fileName = buildSiteMapFileName("_unique_", fileID);
			writeToFile(fileName, "_unique_", true, maxUniqueTitles, scopedUrl);
			SimpleDateFormat simpleDateFormat = new SimpleDateFormat("yyyy-MM-dd");
			siteMapIndex.addSiteMapLocation(buildLocationURL(siteMapFileName("_unique_", 1), scopedUrl), simpleDateFormat.format(date));

			uniqueItemsToWrite = new ArrayList<>(popular);
			resetSiteMapDefaults();
			fileName = buildSiteMapFileName("_popular_", fileID);
			writeToFile(fileName, "_popular_", false, maxPopularTitles, scopedUrl);
			siteMapIndex.addSiteMapLocation(buildLocationURL(siteMapFileName("_popular_", 1), scopedUrl), simpleDateFormat.format(date));

			File siteMapindexFile = getSiteMapIndexFile();
			siteMapIndex.saveFile(siteMapindexFile);
		}
	}


	private void writeToFile(String fileName, String fileType, Boolean writeLibraryAndBranches, int maxTitles, String scopedUrl) {

		BufferedWriter writer = null;
		try {
			File outputFile = new File(fileName);
			logger.info("creating .." + fileName);
			FileWriter fw = new FileWriter(outputFile.getAbsoluteFile(), false);
			writer = new BufferedWriter(fw);
			//add system
			countTracker++;

			if (writeLibraryAndBranches) {

				String librarySystemUrl = buildBranchUrl("System", Long.toString(libraryIdToWrite), scopedUrl);
				writer.write(librarySystemUrl);
				writer.newLine();

				//add library branches?
				ArrayList<Long> branches = librariesByHomeLocation.get(libraryIdToWrite);
				if (branches != null) {
					for (Long libId : branches) {
						String branchUrl = buildBranchUrl("Branch", Long.toString(libId), scopedUrl);
						writer.write(branchUrl);
						writer.newLine();
						countTracker++;
					}
				}
			}

			for (int i = currentSiteMapCount; i < uniqueItemsToWrite.size(); i++) {
				SiteMapEntry siteMapEntry = uniqueItemsToWrite.get(i);

				if (i >= maxTitles)
					break;

				if (countTracker <= maxGoogleSiteMapCount) {
					writer.write(buildGroupedWorkSiteMap(siteMapEntry.getPermanentId(), scopedUrl));
					writer.newLine();
					countTracker++;
				} else {
					fileID++;
					currentSiteMapCount = i + 1;
					countTracker = 0;
					fileName = buildSiteMapFileName(fileType, fileID);
					writeToFile(fileName, fileType, false, maxTitles, scopedUrl);
				}
			}
			logger.info("created: " + fileName);
		} catch (IOException ex) {
			logger.error("Could not create unique works file");
			logger.error("Error creating: " + fileName);
		} finally {
			try {
				if (writer != null) {
					writer.close();
				}
			} catch (Exception ex) {
				logger.error("Could not close writer for : " + fileName, ex);
			}
		}
	}


	///regroups the works into unique and sorted popular works
	private void regroupSiteMapGroups(ArrayList<SiteMapEntry> unique, SortedSet<SiteMapEntry> popular, ArrayList<SiteMapEntry> siteMapEntries, HashSet<Long> uniqueGroupedWorks) {

		for (SiteMapEntry siteMapEntry : siteMapEntries) {
			if (uniqueGroupedWorks.contains(siteMapEntry.getId())) {
				unique.add(siteMapEntry);
			} else {
				popular.add(siteMapEntry);
			}
		}
	}

	private String buildLocationURL(String fileName, String scopedUrl) {
		return scopedUrl +
				"/" +
				"sitemaps" +
				"/" +
				fileName;
	}


	private File getSiteMapIndexFile() {
		return new File(buildSiteMapIndexFile());
	}

	private String buildSiteMapIndexFile() {

		return filePath +
				"/" +
				scopeName +
				".xml";
	}

	private String buildSiteMapFileName(String fileTypeName, int fileID) {
		return filePath + "/" + siteMapFileName(fileTypeName, fileID);
	}

	private String siteMapFileName(String fileTypeName, int fileID) {
		return scopeName +
				fileTypeName +
				String.format("%1$03d", fileID) +
				".txt";
	}


	private String buildBranchUrl(String branch, String branchID, String scopedUrl) {
		//https://adams.marmot.org/Library/1/Branch
		//https://adams.marmot.org/Library/1/System
		return scopedUrl +
				"/" +
				"Library" +
				"/" +
				branchID +
				"/" +
				branch;
	}

	private String buildGroupedWorkSiteMap(String id, String scopedUrl) {
		//https://adams.marmot.org/GroupedWork/24d6b52f-05de-a6d5-fc01-89ccefd7356e/Home -- example
		return scopedUrl +
				"/GroupedWork/" +
				id +
				"/" +
				"Home";
	}

	private void updateVariablesTable() {

		try {

			maxUniqueTitles = getVariableValue("num_title_in_unique_sitemap", maxUniqueTitles);
			maxPopularTitles = getVariableValue("num_titles_in_most_popular_sitemap", maxPopularTitles);

		} catch (SQLException ignored) {

		}
	}

	private int getVariableValue(String variableName, int defaultValue) throws SQLException {
		ResultSet rs = null;
		try {
			PreparedStatement st = vufindConn.prepareStatement("SELECT value from variables WHERE name = ?");
			st.setString(1, variableName);
			rs = st.executeQuery();

			if (rs != null && rs.next()) {
				return Integer.parseInt(rs.getString("value"));
			}

			PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('" + variableName + "', ?)");
			insertVariableStmt.setString(1, Long.toString(defaultValue));
			insertVariableStmt.executeUpdate();
			insertVariableStmt.close();

			return defaultValue;

		} catch (Exception ex) {
            /*ignore*/
		} finally {
			/*ignore*/
			if (rs != null){
				rs.close();
			}
		}
		return 0;
	}

}

