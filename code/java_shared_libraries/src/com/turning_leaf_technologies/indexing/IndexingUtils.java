package com.turning_leaf_technologies.indexing;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Objects;
import java.util.TreeSet;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

public class IndexingUtils {
	private static HashMap<String, OwnershipRule> allOwnershipRules = new HashMap<>();
	private static HashMap<String, InclusionRule> allInclusionRules = new HashMap<>();

	public static TreeSet<Scope> loadScopes(Connection dbConn, Logger logger) {
		TreeSet<Scope> scopes = new TreeSet<>();
		//Setup translation maps for system and location
		try {
			HashMap<Long, OverDriveScope> overDriveScopes = loadOverDriveScopes(dbConn, logger);
			HashMap<Long, HooplaScope> hooplaScopes = loadHooplaScopes(dbConn, logger);
			HashMap<Long, Axis360Scope> axis360Scopes = loadAxis360Scopes(dbConn, logger);
			HashMap<Long, CloudLibraryScope> cloudLibraryScopes = loadCloudLibraryScopes(dbConn, logger);
			HashMap<Long, SideLoadScope> sideLoadScopes = loadSideLoadScopes(dbConn, logger);
			HashMap<Long, GroupedWorkDisplaySettings> groupedWorkDisplaySettings = loadGroupedWorkDisplaySettings(dbConn, logger);

			loadLibraryScopes(scopes, groupedWorkDisplaySettings, overDriveScopes, hooplaScopes, cloudLibraryScopes, axis360Scopes, sideLoadScopes, dbConn, logger);

			loadLocationScopes(scopes, groupedWorkDisplaySettings, overDriveScopes, hooplaScopes, cloudLibraryScopes, axis360Scopes, sideLoadScopes, dbConn, logger);
		} catch (SQLException e) {
			logger.error("Error setting up scopes", e);
			return null;
		}

		return scopes;
	}

	private static HashMap<Long, GroupedWorkDisplaySettings> loadGroupedWorkDisplaySettings(Connection dbConn, Logger logger) {
		HashMap<Long, GroupedWorkDisplaySettings> groupedWorkSettings = new HashMap<>();
		try{
			PreparedStatement groupedWorkDisplaySettingsStmt = dbConn.prepareStatement("SELECT * from grouped_work_display_settings", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet groupedWorkDisplaySettingsRS = groupedWorkDisplaySettingsStmt.executeQuery();
			while (groupedWorkDisplaySettingsRS.next()){
				GroupedWorkDisplaySettings setting = new GroupedWorkDisplaySettings();
				setting.setId(groupedWorkDisplaySettingsRS.getLong("id"));
				setting.setName(groupedWorkDisplaySettingsRS.getString("name"));
				setting.setIncludeOnlineMaterialsInAvailableToggle(groupedWorkDisplaySettingsRS.getBoolean("includeOnlineMaterialsInAvailableToggle"));
				setting.setIncludeAllRecordsInShelvingFacets(groupedWorkDisplaySettingsRS.getBoolean("includeAllRecordsInShelvingFacets"));
				setting.setIncludeAllRecordsInDateAddedFacets(groupedWorkDisplaySettingsRS.getBoolean("includeAllRecordsInDateAddedFacets"));
				setting.setBaseAvailabilityToggleOnLocalHoldingsOnly(groupedWorkDisplaySettingsRS.getBoolean("baseAvailabilityToggleOnLocalHoldingsOnly"));

				groupedWorkSettings.put(setting.getId(), setting);
			}
		} catch (SQLException e) {
			logger.error("Error loading grouped work settings", e);
		}
		return groupedWorkSettings;
	}

	private static HashMap<Long, HooplaScope> loadHooplaScopes(Connection dbConn, Logger logger) {
		HashMap<Long, HooplaScope> hooplaScopes = new HashMap<>();
		try {
			PreparedStatement hooplaScopeStmt = dbConn.prepareStatement("SELECT * from hoopla_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet hooplaScopesRS = hooplaScopeStmt.executeQuery();

			while (hooplaScopesRS.next()) {
				HooplaScope hooplaScope = new HooplaScope();
				hooplaScope.setId(hooplaScopesRS.getLong("id"));
				hooplaScope.setName(hooplaScopesRS.getString("name"));
				hooplaScope.setExcludeTitlesWithCopiesFromOtherVendors(hooplaScopesRS.getInt("excludeTitlesWithCopiesFromOtherVendors"));
				hooplaScope.setIncludeEBooks(hooplaScopesRS.getBoolean("includeEBooks"));
				hooplaScope.setMaxCostPerCheckoutEBooks(hooplaScopesRS.getLong("maxCostPerCheckoutEBooks"));
				hooplaScope.setIncludeEComics(hooplaScopesRS.getBoolean("includeEComics"));
				hooplaScope.setMaxCostPerCheckoutEComics(hooplaScopesRS.getFloat("maxCostPerCheckoutEComics"));
				hooplaScope.setIncludeEAudiobook(hooplaScopesRS.getBoolean("includeEAudiobook"));
				hooplaScope.setMaxCostPerCheckoutEAudiobook(hooplaScopesRS.getFloat("maxCostPerCheckoutEAudiobook"));
				hooplaScope.setIncludeMovies(hooplaScopesRS.getBoolean("includeMovies"));
				hooplaScope.setMaxCostPerCheckoutMovies(hooplaScopesRS.getFloat("maxCostPerCheckoutMovies"));
				hooplaScope.setIncludeMusic(hooplaScopesRS.getBoolean("includeMusic"));
				hooplaScope.setMaxCostPerCheckoutMusic(hooplaScopesRS.getFloat("maxCostPerCheckoutMusic"));
				hooplaScope.setIncludeTelevision(hooplaScopesRS.getBoolean("includeTelevision"));
				hooplaScope.setMaxCostPerCheckoutTelevision(hooplaScopesRS.getFloat("maxCostPerCheckoutTelevision"));
				hooplaScope.setIncludeBingePass(hooplaScopesRS.getBoolean("includeBingePass"));
				hooplaScope.setMaxCostPerCheckoutBingePass(hooplaScopesRS.getFloat("maxCostPerCheckoutBingePass"));
				hooplaScope.setRestrictToChildrensMaterial(hooplaScopesRS.getBoolean("restrictToChildrensMaterial"));
				hooplaScope.setRatingsToExclude(hooplaScopesRS.getString("ratingsToExclude"));
				hooplaScope.setExcludeAbridged(hooplaScopesRS.getBoolean("excludeAbridged"));
				hooplaScope.setExcludeParentalAdvisory(hooplaScopesRS.getBoolean("excludeParentalAdvisory"));
				hooplaScope.setExcludeProfanity(hooplaScopesRS.getBoolean("excludeProfanity"));
				hooplaScope.setGenreFilters(hooplaScopesRS.getString("genresToExclude"));

				hooplaScopes.put(hooplaScope.getId(), hooplaScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading hoopla scopes", e);
		}
		return hooplaScopes;
	}

	private static HashMap<Long, Axis360Scope> loadAxis360Scopes(Connection dbConn, Logger logger) {
		HashMap<Long, Axis360Scope> axis360Scopes = new HashMap<>();
		try {
			PreparedStatement axis360ScopeStmt = dbConn.prepareStatement("SELECT * from axis360_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet axis360ScopesRS = axis360ScopeStmt.executeQuery();

			while (axis360ScopesRS.next()) {
				Axis360Scope axis360Scope = new Axis360Scope();
				axis360Scope.setId(axis360ScopesRS.getLong("id"));
				axis360Scope.setName(axis360ScopesRS.getString("name"));
				axis360Scope.setSettingId(axis360ScopesRS.getLong("settingId"));

				axis360Scopes.put(axis360Scope.getId(), axis360Scope);
			}

		} catch (SQLException e) {
			logger.error("Error loading Axis 360 scopes", e);
		}
		return axis360Scopes;
	}

	private static HashMap<Long, OverDriveScope> loadOverDriveScopes(Connection dbConn, Logger logger) {
		HashMap<Long, OverDriveScope> overDriveScopes = new HashMap<>();
		try {
			PreparedStatement overDriveScopeStmt = dbConn.prepareStatement("SELECT * from overdrive_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet overDriveScopesRS = overDriveScopeStmt.executeQuery();

			while (overDriveScopesRS.next()) {
				OverDriveScope overDriveScope = new OverDriveScope();
				overDriveScope.setId(overDriveScopesRS.getLong("id"));
				overDriveScope.setSettingId(overDriveScopesRS.getLong("settingId"));
				overDriveScope.setName(overDriveScopesRS.getString("name"));
				overDriveScope.setIncludeAdult(overDriveScopesRS.getBoolean("includeAdult"));
				overDriveScope.setIncludeTeen(overDriveScopesRS.getBoolean("includeTeen"));
				overDriveScope.setIncludeKids(overDriveScopesRS.getBoolean("includeKids"));

				overDriveScopes.put(overDriveScope.getId(), overDriveScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading OverDrive scopes", e);
		}
		return overDriveScopes;
	}

	private static HashMap<Long, CloudLibraryScope> loadCloudLibraryScopes(Connection dbConn, Logger logger) {
		HashMap<Long, CloudLibraryScope> cloudLibraryScopes = new HashMap<>();
		try {
			PreparedStatement cloudLibraryScopeStmt = dbConn.prepareStatement("SELECT * from cloud_library_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet cloudLibraryScopesRS = cloudLibraryScopeStmt.executeQuery();

			while (cloudLibraryScopesRS.next()) {
				CloudLibraryScope cloudLibraryScope = new CloudLibraryScope();
				cloudLibraryScope.setId(cloudLibraryScopesRS.getLong("id"));
				cloudLibraryScope.setSettingId(cloudLibraryScopesRS.getLong("settingId"));
				cloudLibraryScope.setName(cloudLibraryScopesRS.getString("name"));
				cloudLibraryScope.setIncludeEBooks(cloudLibraryScopesRS.getBoolean("includeEBooks"));
				cloudLibraryScope.setIncludeEAudiobook(cloudLibraryScopesRS.getBoolean("includeEAudiobook"));
				cloudLibraryScope.setRestrictToChildrensMaterial(cloudLibraryScopesRS.getBoolean("restrictToChildrensMaterial"));

				cloudLibraryScopes.put(cloudLibraryScope.getId(), cloudLibraryScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading cloudLibrary scopes", e);
		}
		return cloudLibraryScopes;
	}

	private static HashMap<Long, SideLoadScope> loadSideLoadScopes(Connection dbConn, Logger logger) {
		HashMap<Long, SideLoadScope> sideLoadScopes = new HashMap<>();
		try {
			PreparedStatement sideLoadScopeStmt = dbConn.prepareStatement("SELECT * from sideload_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet sideLoadScopesRS = sideLoadScopeStmt.executeQuery();

			while (sideLoadScopesRS.next()) {
				SideLoadScope sideLoadScope = new SideLoadScope();
				sideLoadScope.setId(sideLoadScopesRS.getLong("id"));
				sideLoadScope.setName(sideLoadScopesRS.getString("name"));
				sideLoadScope.setSideLoadId(sideLoadScopesRS.getLong("sideLoadId"));
				sideLoadScope.setRestrictToChildrensMaterial(sideLoadScopesRS.getBoolean("restrictToChildrensMaterial"));
				sideLoadScope.setMarcTagToMatch(sideLoadScopesRS.getString("marcTagToMatch"));
				sideLoadScope.setMarcValueToMatch(sideLoadScopesRS.getString("marcValueToMatch"));
				sideLoadScope.setIncludeExcludeMatches(sideLoadScopesRS.getBoolean("includeExcludeMatches"));
				sideLoadScope.setUrlToMatch(sideLoadScopesRS.getString("urlToMatch"));
				sideLoadScope.setUrlReplacement(sideLoadScopesRS.getString("urlReplacement"));
				sideLoadScopes.put(sideLoadScope.getId(), sideLoadScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading Side Load scopes", e);
		}
		return sideLoadScopes;
	}

	private static void loadLocationScopes(TreeSet<Scope> scopes, HashMap<Long, GroupedWorkDisplaySettings> groupedWorkDisplaySettings, HashMap<Long, OverDriveScope> overDriveScopes, HashMap<Long, HooplaScope> hooplaScopes, HashMap<Long, CloudLibraryScope> cloudLibraryScopes, HashMap<Long, Axis360Scope> axis360Scopes, HashMap<Long, SideLoadScope> sideLoadScopes, Connection dbConn, Logger logger) throws SQLException {
		//To minimize the amount of data in the index, only load locations that have more than one location within the library.
		PreparedStatement librariesWithMoreThanOneLocationStmt = dbConn.prepareStatement("select libraryId, count(*) as numLocations from location WHERE createSearchInterface = 1 group by libraryId having numLocations > 1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet librariesWithMoreThanOneLocation = librariesWithMoreThanOneLocationStmt.executeQuery();
		String librariesToFetch = new String();
		while (librariesWithMoreThanOneLocation.next()){
			if (librariesToFetch.length() > 0){
				librariesToFetch += ",";
			}
			librariesToFetch += librariesWithMoreThanOneLocation.getString("libraryId");
		}
		librariesWithMoreThanOneLocation.close();
		librariesWithMoreThanOneLocationStmt.close();
		if (librariesToFetch.length() == 0){
			return;
		}

		PreparedStatement locationInformationStmt = dbConn.prepareStatement("SELECT library.libraryId, locationId, code, subLocation, ilsCode, " +
						"library.subdomain, location.facetLabel, location.displayName, library.restrictOwningBranchesAndSystems, location.publicListsToInclude, " +
						"location.additionalLocationsToShowAvailabilityFor, includeAllLibraryBranchesInFacets, library.isConsortialCatalog, " +
						"location.groupedWorkDisplaySettingId as groupedWorkDisplaySettingIdLocation, library.groupedWorkDisplaySettingId as groupedWorkDisplaySettingIdLibrary, " +
						"location.includeLibraryRecordsToInclude, library.courseReserveLibrariesToInclude, " +
						"library.overDriveScopeId as overDriveScopeIdLibrary, location.overDriveScopeId as overDriveScopeIdLocation, " +
						"library.hooplaScopeId as hooplaScopeLibrary, location.hooplaScopeId as hooplaScopeLocation, " +
						"library.axis360ScopeId as axis360ScopeLibrary, location.axis360ScopeId as axis360ScopeLocation " +
						"FROM location INNER JOIN library on library.libraryId = location.libraryId WHERE location.libraryId IN (" + librariesToFetch + ") ORDER BY code ASC",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationOwnedRecordRulesStmt = dbConn.prepareStatement("SELECT location_records_owned.*, indexing_profiles.name FROM location_records_owned INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE locationId = ?",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT location_records_to_include.*, indexing_profiles.name FROM location_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE locationId = ?",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement libraryCloudLibraryScopesStmt = dbConn.prepareStatement("SELECT * from library_cloud_library_scope WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationCloudLibraryScopesStmt = dbConn.prepareStatement("SELECT * from location_cloud_library_scope WHERE locationId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement librarySideLoadScopesStmt = dbConn.prepareStatement("SELECT * from library_sideload_scopes WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationSideLoadScopesStmt = dbConn.prepareStatement("SELECT * from location_sideload_scopes WHERE locationId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement libraryRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT library_records_to_include.*, indexing_profiles.name from library_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

		ResultSet locationInformationRS = locationInformationStmt.executeQuery();
		while (locationInformationRS.next()) {
			String code = locationInformationRS.getString("code").toLowerCase();
			String subLocation = locationInformationRS.getString("subLocation");
			String facetLabel = locationInformationRS.getString("facetLabel");
			String displayName = locationInformationRS.getString("displayName");
			if (facetLabel.length() == 0) {
				facetLabel = displayName;
			}

			//Determine if we need to build a scope for this location
			long libraryId = locationInformationRS.getLong("libraryId");
			long locationId = locationInformationRS.getLong("locationId");

			Scope locationScopeInfo = new Scope();
			locationScopeInfo.setIsLibraryScope(false);
			locationScopeInfo.setIsLocationScope(true);
			String scopeName = code;
			if (subLocation != null && subLocation.length() > 0) {
				scopeName = subLocation.toLowerCase();
			}
			locationScopeInfo.setScopeName(scopeName);
			locationScopeInfo.setLibraryId(libraryId);
			locationScopeInfo.setFacetLabel(facetLabel);
			locationScopeInfo.setRestrictOwningLibraryAndLocationFacets(locationInformationRS.getBoolean("restrictOwningBranchesAndSystems"));
			locationScopeInfo.setIlsCode(code);
			locationScopeInfo.setPublicListsToInclude(locationInformationRS.getInt("publicListsToInclude"));
			locationScopeInfo.setAdditionalLocationsToShowAvailabilityFor(locationInformationRS.getString("additionalLocationsToShowAvailabilityFor"));
			locationScopeInfo.setIncludeAllLibraryBranchesInFacets(locationInformationRS.getBoolean("includeAllLibraryBranchesInFacets"));
			locationScopeInfo.setConsortialCatalog(locationInformationRS.getBoolean("isConsortialCatalog"));
			long groupedWorkDisplaySettingId = locationInformationRS.getLong("groupedWorkDisplaySettingIdLocation");
			if (groupedWorkDisplaySettingId == -1){
				groupedWorkDisplaySettingId = locationInformationRS.getLong("groupedWorkDisplaySettingIdLibrary");
			}
			if (groupedWorkDisplaySettings.containsKey(groupedWorkDisplaySettingId)) {
				locationScopeInfo.setGroupedWorkDisplaySettings(groupedWorkDisplaySettings.get(groupedWorkDisplaySettingId));
			}else{
				logger.error("Invalid groupedWorkDisplaySettingId provided, got " + groupedWorkDisplaySettingId + " not loading location scope " + scopeName);
				continue;
			}
			locationScopeInfo.setGroupedWorkDisplaySettings(groupedWorkDisplaySettings.get(groupedWorkDisplaySettingId));
			boolean includeLibraryRecordsToInclude = locationInformationRS.getBoolean("includeLibraryRecordsToInclude");

			locationScopeInfo.setCourseReserveLibrariesToInclude(locationInformationRS.getString("courseReserveLibrariesToInclude"));

			long overDriveScopeIdLocation = locationInformationRS.getLong("overDriveScopeIdLocation");
			long overDriveScopeIdLibrary = locationInformationRS.getLong("overDriveScopeIdLibrary");

			//No records
			if (overDriveScopeIdLocation == -1) {
				if (overDriveScopeIdLibrary != -1) {
					locationScopeInfo.setOverDriveScope(overDriveScopes.get(overDriveScopeIdLibrary));
				}
			} else if (overDriveScopeIdLocation != -2) {
				locationScopeInfo.setOverDriveScope(overDriveScopes.get(overDriveScopeIdLocation));
			}

			long hooplaScopeLocation = locationInformationRS.getLong("hooplaScopeLocation");
			long hooplaScopeLibrary = locationInformationRS.getLong("hooplaScopeLibrary");

			//No records
			if (hooplaScopeLocation == -1) {
				if (hooplaScopeLibrary != -1) {
					locationScopeInfo.setHooplaScope(hooplaScopes.get(hooplaScopeLibrary));
				}
			} else if (hooplaScopeLocation != -2) {
				locationScopeInfo.setHooplaScope(hooplaScopes.get(hooplaScopeLocation));
			}

			locationCloudLibraryScopesStmt.setLong(1, locationId);
			ResultSet locationCloudLibraryScopesRS = locationCloudLibraryScopesStmt.executeQuery();
			while (locationCloudLibraryScopesRS.next()) {
				long scopeId = locationCloudLibraryScopesRS.getLong("scopeId");
				if (scopeId == -1) {
					libraryCloudLibraryScopesStmt.setLong(1, libraryId);
					ResultSet libraryCloudLibraryScopesRS = libraryCloudLibraryScopesStmt.executeQuery();
					while (libraryCloudLibraryScopesRS.next()) {
						long cloudLibraryScopeId = libraryCloudLibraryScopesRS.getLong("scopeId");
						if (cloudLibraryScopes.containsKey(cloudLibraryScopeId)) {
							locationScopeInfo.addCloudLibraryScope(cloudLibraryScopes.get(cloudLibraryScopeId));
						}
					}
				} else {
					if (cloudLibraryScopes.containsKey(scopeId)) {
						locationScopeInfo.addCloudLibraryScope(cloudLibraryScopes.get(scopeId));
					}
				}
			}
			if (includeLibraryRecordsToInclude){
				libraryCloudLibraryScopesStmt.setLong(1, libraryId);
				ResultSet libraryCloudLibraryScopesRS = libraryCloudLibraryScopesStmt.executeQuery();
				while (libraryCloudLibraryScopesRS.next()) {
					long cloudLibraryScopeId = libraryCloudLibraryScopesRS.getLong("scopeId");
					if (cloudLibraryScopes.containsKey(cloudLibraryScopeId)) {
						locationScopeInfo.addCloudLibraryScope(cloudLibraryScopes.get(cloudLibraryScopeId));
					}
				}
			}

			long axis360ScopeLocation = locationInformationRS.getLong("axis360ScopeLocation");
			long axis360ScopeLibrary = locationInformationRS.getLong("axis360ScopeLibrary");
			if (axis360ScopeLocation == -1) {
				if (axis360ScopeLibrary != -1) {
					locationScopeInfo.setAxis360Scope(axis360Scopes.get(axis360ScopeLibrary));
				}
			} else if (axis360ScopeLocation != -2) {
				locationScopeInfo.setAxis360Scope(axis360Scopes.get(axis360ScopeLocation));
			}

			locationSideLoadScopesStmt.setLong(1, locationId);
			ResultSet locationSideLoadScopesRS = locationSideLoadScopesStmt.executeQuery();
			while (locationSideLoadScopesRS.next()) {
				long scopeId = locationSideLoadScopesRS.getLong("sideLoadScopeId");
				if (scopeId == -1) {
					librarySideLoadScopesStmt.setLong(1, libraryId);
					ResultSet librarySideLoadScopesRS = librarySideLoadScopesStmt.executeQuery();
					while (librarySideLoadScopesRS.next()) {
						long sideLoadScopeId = librarySideLoadScopesRS.getLong("sideLoadScopeId");
						if (sideLoadScopes.containsKey(sideLoadScopeId)) {
							locationScopeInfo.addSideLoadScope(sideLoadScopes.get(sideLoadScopeId));
						}
					}
				} else {
					if (sideLoadScopes.containsKey(scopeId)) {
						locationScopeInfo.addSideLoadScope(sideLoadScopes.get(scopeId));
					}
				}
			}
			if (includeLibraryRecordsToInclude){
				librarySideLoadScopesStmt.setLong(1, libraryId);
				ResultSet librarySideLoadScopesRS = librarySideLoadScopesStmt.executeQuery();
				while (librarySideLoadScopesRS.next()) {
					long sideLoadScopeId = librarySideLoadScopesRS.getLong("sideLoadScopeId");
					if (sideLoadScopes.containsKey(sideLoadScopeId)) {
						locationScopeInfo.addSideLoadScope(sideLoadScopes.get(sideLoadScopeId));
					}
				}
			}

			//Load information about what should be included in the scope
			locationOwnedRecordRulesStmt.setLong(1, locationId);
			ResultSet locationOwnedRecordRulesRS = locationOwnedRecordRulesStmt.executeQuery();
			while (locationOwnedRecordRulesRS.next()) {
				String ownershipRuleKey = locationOwnedRecordRulesRS.getString("name") + "~" + locationOwnedRecordRulesRS.getString("location") + "~" + locationOwnedRecordRulesRS.getString("subLocation") + "~" + locationOwnedRecordRulesRS.getString("locationsToExclude") + "~" + locationOwnedRecordRulesRS.getString("subLocationsToExclude");
				if (allOwnershipRules.containsKey(ownershipRuleKey)){
					locationScopeInfo.addOwnershipRule(allOwnershipRules.get(ownershipRuleKey));
				}else{
					OwnershipRule ownershipRule = new OwnershipRule(locationOwnedRecordRulesRS.getString("name"), locationOwnedRecordRulesRS.getString("location"), locationOwnedRecordRulesRS.getString("subLocation"), locationOwnedRecordRulesRS.getString("locationsToExclude"), locationOwnedRecordRulesRS.getString("subLocationsToExclude"));
					allOwnershipRules.put(ownershipRuleKey, ownershipRule);
					locationScopeInfo.addOwnershipRule(ownershipRule);
				}
			}

			locationRecordInclusionRulesStmt.setLong(1, locationId);
			ResultSet locationRecordInclusionRulesRS = locationRecordInclusionRulesStmt.executeQuery();
			while (locationRecordInclusionRulesRS.next()) {
				String inclusionRuleKey = locationRecordInclusionRulesRS.getString("name") + "~" +
						locationRecordInclusionRulesRS.getString("location") + "~" +
						locationRecordInclusionRulesRS.getString("subLocation") + "~" +
						locationRecordInclusionRulesRS.getString("locationsToExclude") + "~" +
						locationRecordInclusionRulesRS.getString("subLocationsToExclude") + "~" +
						locationRecordInclusionRulesRS.getString("iType") + "~" +
						locationRecordInclusionRulesRS.getString("audience") + "~" +
						locationRecordInclusionRulesRS.getString("format") + "~" +
						locationRecordInclusionRulesRS.getString("includeHoldableOnly") + "~" +
						locationRecordInclusionRulesRS.getString("includeItemsOnOrder") + "~" +
						locationRecordInclusionRulesRS.getString("includeEContent") + "~" +
						locationRecordInclusionRulesRS.getString("marcTagToMatch") + "~" +
						locationRecordInclusionRulesRS.getString("marcValueToMatch") + "~" +
						locationRecordInclusionRulesRS.getString("includeExcludeMatches") + "~" +
						locationRecordInclusionRulesRS.getString("urlToMatch") + "~" +
						locationRecordInclusionRulesRS.getString("urlReplacement");
				if (allInclusionRules.containsKey(inclusionRuleKey)){
					locationScopeInfo.addInclusionRule(allInclusionRules.get(inclusionRuleKey));
				}else{
					InclusionRule inclusionRule = new InclusionRule(locationRecordInclusionRulesRS.getString("name"),
							locationRecordInclusionRulesRS.getString("location"),
							locationRecordInclusionRulesRS.getString("subLocation"),
							locationRecordInclusionRulesRS.getString("locationsToExclude"),
							locationRecordInclusionRulesRS.getString("subLocationsToExclude"),
							locationRecordInclusionRulesRS.getString("iType"),
							locationRecordInclusionRulesRS.getString("audience"),
							locationRecordInclusionRulesRS.getString("format"),
							locationRecordInclusionRulesRS.getBoolean("includeHoldableOnly"),
							locationRecordInclusionRulesRS.getBoolean("includeItemsOnOrder"),
							locationRecordInclusionRulesRS.getBoolean("includeEContent"),
							locationRecordInclusionRulesRS.getString("marcTagToMatch"),
							locationRecordInclusionRulesRS.getString("marcValueToMatch"),
							locationRecordInclusionRulesRS.getBoolean("includeExcludeMatches"),
							locationRecordInclusionRulesRS.getString("urlToMatch"),
							locationRecordInclusionRulesRS.getString("urlReplacement")
					);
					allInclusionRules.put(inclusionRuleKey, inclusionRule);
					locationScopeInfo.addInclusionRule(inclusionRule);
				}
			}

			if (includeLibraryRecordsToInclude) {

				libraryRecordInclusionRulesStmt.setLong(1, libraryId);
				ResultSet libraryRecordInclusionRulesRS = libraryRecordInclusionRulesStmt.executeQuery();
				while (libraryRecordInclusionRulesRS.next()) {
					String inclusionRuleKey = libraryRecordInclusionRulesRS.getString("name") +
							libraryRecordInclusionRulesRS.getString("location") + "~" +
							libraryRecordInclusionRulesRS.getString("subLocation") + "~" +
							libraryRecordInclusionRulesRS.getString("locationsToExclude") + "~" +
							libraryRecordInclusionRulesRS.getString("subLocationsToExclude") + "~" +
							libraryRecordInclusionRulesRS.getString("iType") + "~" +
							libraryRecordInclusionRulesRS.getString("audience") + "~" +
							libraryRecordInclusionRulesRS.getString("format") + "~" +
							libraryRecordInclusionRulesRS.getString("includeHoldableOnly") + "~" +
							libraryRecordInclusionRulesRS.getString("includeItemsOnOrder") + "~" +
							libraryRecordInclusionRulesRS.getString("includeEContent") + "~" +
							libraryRecordInclusionRulesRS.getString("marcTagToMatch") + "~" +
							libraryRecordInclusionRulesRS.getString("marcValueToMatch") + "~" +
							libraryRecordInclusionRulesRS.getString("includeExcludeMatches") + "~" +
							libraryRecordInclusionRulesRS.getString("urlToMatch") + "~" +
							libraryRecordInclusionRulesRS.getString("urlReplacement");
					if (allInclusionRules.containsKey(inclusionRuleKey)){
						locationScopeInfo.addInclusionRule(allInclusionRules.get(inclusionRuleKey));
					}else{
						InclusionRule inclusionRule = new InclusionRule(libraryRecordInclusionRulesRS.getString("name"),
								libraryRecordInclusionRulesRS.getString("location"),
								libraryRecordInclusionRulesRS.getString("subLocation"),
								libraryRecordInclusionRulesRS.getString("locationsToExclude"),
								libraryRecordInclusionRulesRS.getString("subLocationsToExclude"),
								libraryRecordInclusionRulesRS.getString("iType"),
								libraryRecordInclusionRulesRS.getString("audience"),
								libraryRecordInclusionRulesRS.getString("format"),
								libraryRecordInclusionRulesRS.getBoolean("includeHoldableOnly"),
								libraryRecordInclusionRulesRS.getBoolean("includeItemsOnOrder"),
								libraryRecordInclusionRulesRS.getBoolean("includeEContent"),
								libraryRecordInclusionRulesRS.getString("marcTagToMatch"),
								libraryRecordInclusionRulesRS.getString("marcValueToMatch"),
								libraryRecordInclusionRulesRS.getBoolean("includeExcludeMatches"),
								libraryRecordInclusionRulesRS.getString("urlToMatch"),
								libraryRecordInclusionRulesRS.getString("urlReplacement")
						);
						allInclusionRules.put(inclusionRuleKey, inclusionRule);
						locationScopeInfo.addInclusionRule(inclusionRule);
					}
				}
			}

			if (scopes.contains(locationScopeInfo)) {
				locationScopeInfo.setScopeName(locationScopeInfo.getScopeName() + "loc");
			}
			//Connect this scope to the library scopes
			for (Scope curScope : scopes) {
				if (curScope.isLibraryScope() && Objects.equals(curScope.getLibraryId(), libraryId)) {
					curScope.addLocationScope(locationScopeInfo);
					locationScopeInfo.setLibraryScope(curScope);
					break;
				}
			}
			scopes.add(locationScopeInfo);

		}
	}

	private static void loadLibraryScopes(TreeSet<Scope> scopes, HashMap<Long, GroupedWorkDisplaySettings> groupedWorkDisplaySettings, HashMap<Long, OverDriveScope> overDriveScopes, HashMap<Long, HooplaScope> hooplaScopes, HashMap<Long, CloudLibraryScope> cloudLibraryScopes, HashMap<Long, Axis360Scope> axis360Scopes, HashMap<Long, SideLoadScope> sideLoadScopes, Connection dbConn, Logger logger) throws SQLException {
		PreparedStatement libraryInformationStmt = dbConn.prepareStatement("SELECT libraryId, ilsCode, subdomain, " +
						"displayName, facetLabel, restrictOwningBranchesAndSystems, publicListsToInclude, isConsortialCatalog, " +
						"additionalLocationsToShowAvailabilityFor, courseReserveLibrariesToInclude, overDriveScopeId, " +
						"groupedWorkDisplaySettingId, hooplaScopeId, axis360ScopeId " +
						"FROM library WHERE createSearchInterface = 1 ORDER BY ilsCode ASC",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement numLocationsForLibraryStmt = dbConn.prepareStatement("SELECT count(locationId) as numLocations from location where libraryId = ? and createSearchInterface = 1")
;		PreparedStatement libraryOwnedRecordRulesStmt = dbConn.prepareStatement("SELECT library_records_owned.*, indexing_profiles.name from library_records_owned INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement libraryRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT library_records_to_include.*, indexing_profiles.name from library_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
		PreparedStatement librarySideLoadScopesStmt = dbConn.prepareStatement("SELECT * from library_sideload_scopes WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement libraryCloudLibraryScopesStmt = dbConn.prepareStatement("SELECT * from library_cloud_library_scope WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

		while (libraryInformationRS.next()) {
			String facetLabel = libraryInformationRS.getString("facetLabel");
			String subdomain = libraryInformationRS.getString("subdomain");
			String displayName = libraryInformationRS.getString("displayName");
			if (facetLabel.length() == 0) {
				facetLabel = displayName;
			}
			//These options determine how scoping is done
			long libraryId = libraryInformationRS.getLong("libraryId");

			//Get number of locations for the library
			int numLocations = 0;
			numLocationsForLibraryStmt.setLong(1, libraryId);
			ResultSet numLocationsForLibraryRS = numLocationsForLibraryStmt.executeQuery();
			if (numLocationsForLibraryRS.next()){
				numLocations = numLocationsForLibraryRS.getInt("numLocations");
			}
			numLocationsForLibraryRS.close();

			//Determine if we need to build a scope for this library
			//MDN 10/1/2014 always build scopes because it makes coding more consistent elsewhere.
			//We need to build a scope
			Scope newScope = new Scope();
			newScope.setIsLibraryScope(true);
			if (numLocations == 1) {
				//Scopes with only 1 location for the library will be boht library and location
				newScope.setIsLocationScope(true);
			}else{
				newScope.setIsLocationScope(false);
			}
			newScope.setScopeName(subdomain);
			newScope.setLibraryId(libraryId);
			newScope.setFacetLabel(facetLabel);
			newScope.setPublicListsToInclude(libraryInformationRS.getInt("publicListsToInclude"));
			newScope.setAdditionalLocationsToShowAvailabilityFor(libraryInformationRS.getString("additionalLocationsToShowAvailabilityFor"));
			newScope.setConsortialCatalog(libraryInformationRS.getBoolean("isConsortialCatalog"));
			long groupedWorkDisplaySettingId = libraryInformationRS.getLong("groupedWorkDisplaySettingId");
			if (groupedWorkDisplaySettings.containsKey(groupedWorkDisplaySettingId)) {
				newScope.setGroupedWorkDisplaySettings(groupedWorkDisplaySettings.get(groupedWorkDisplaySettingId));
			}else{
				logger.error("Invalid groupedWorkDisplaySettingId provided, got " + groupedWorkDisplaySettingId + " not loading library scope " + subdomain);
				continue;
			}
			newScope.setCourseReserveLibrariesToInclude(libraryInformationRS.getString("courseReserveLibrariesToInclude"));

			long overDriveScopeLibrary = libraryInformationRS.getLong("overDriveScopeId");
			if (overDriveScopeLibrary != -1) {
				newScope.setOverDriveScope(overDriveScopes.get(overDriveScopeLibrary));
			}

			long hooplaScopeLibrary = libraryInformationRS.getLong("hooplaScopeId");
			if (hooplaScopeLibrary != -1) {
				newScope.setHooplaScope(hooplaScopes.get(hooplaScopeLibrary));
			}

			libraryCloudLibraryScopesStmt.setLong(1, libraryId);
			ResultSet libraryCloudLibraryScopesRS = libraryCloudLibraryScopesStmt.executeQuery();
			while (libraryCloudLibraryScopesRS.next()) {
				long cloudLibraryScopeId = libraryCloudLibraryScopesRS.getLong("scopeId");
				if (cloudLibraryScopes.containsKey(cloudLibraryScopeId)) {
					newScope.addCloudLibraryScope(cloudLibraryScopes.get(cloudLibraryScopeId));
				}
			}

			long axis360ScopeLibrary = libraryInformationRS.getLong("axis360ScopeId");
			if (axis360ScopeLibrary != -1) {
				newScope.setAxis360Scope(axis360Scopes.get(axis360ScopeLibrary));
			}

			librarySideLoadScopesStmt.setLong(1, libraryId);
			ResultSet librarySideLoadScopesRS = librarySideLoadScopesStmt.executeQuery();
			while (librarySideLoadScopesRS.next()) {
				long sideLoadScopeId = librarySideLoadScopesRS.getLong("sideLoadScopeId");
				if (sideLoadScopes.containsKey(sideLoadScopeId)) {
					newScope.addSideLoadScope(sideLoadScopes.get(sideLoadScopeId));
				}
			}

			newScope.setRestrictOwningLibraryAndLocationFacets(libraryInformationRS.getBoolean("restrictOwningBranchesAndSystems"));
			newScope.setIlsCode(libraryInformationRS.getString("ilsCode"));

			//Load information about what should be included in the scope
			libraryOwnedRecordRulesStmt.setLong(1, libraryId);
			ResultSet libraryOwnedRecordRulesRS = libraryOwnedRecordRulesStmt.executeQuery();
			while (libraryOwnedRecordRulesRS.next()) {
				String ownershipRuleKey = libraryOwnedRecordRulesRS.getString("name") + "~" + libraryOwnedRecordRulesRS.getString("location") +  "~" + libraryOwnedRecordRulesRS.getString("subLocation") + "~" + libraryOwnedRecordRulesRS.getString("locationsToExclude") + "~" + libraryOwnedRecordRulesRS.getString("subLocationsToExclude");
				if (allOwnershipRules.containsKey(ownershipRuleKey)){
					newScope.addOwnershipRule(allOwnershipRules.get(ownershipRuleKey));
				}else{
					OwnershipRule ownershipRule = new OwnershipRule(libraryOwnedRecordRulesRS.getString("name"), libraryOwnedRecordRulesRS.getString("location"), libraryOwnedRecordRulesRS.getString("subLocation"), libraryOwnedRecordRulesRS.getString("locationsToExclude"), libraryOwnedRecordRulesRS.getString("subLocationsToExclude"));
					allOwnershipRules.put(ownershipRuleKey, ownershipRule);
					newScope.addOwnershipRule(ownershipRule);
				}
			}

			libraryRecordInclusionRulesStmt.setLong(1, libraryId);
			ResultSet libraryRecordInclusionRulesRS = libraryRecordInclusionRulesStmt.executeQuery();
			while (libraryRecordInclusionRulesRS.next()) {
				String inclusionRuleKey = libraryRecordInclusionRulesRS.getString("name") + "~" +
						libraryRecordInclusionRulesRS.getString("location") + "~" +
						libraryRecordInclusionRulesRS.getString("subLocation") + "~" +
						libraryRecordInclusionRulesRS.getString("locationsToExclude") + "~" +
						libraryRecordInclusionRulesRS.getString("subLocationsToExclude") + "~" +
						libraryRecordInclusionRulesRS.getString("iType") + "~" +
						libraryRecordInclusionRulesRS.getString("audience") + "~" +
						libraryRecordInclusionRulesRS.getString("format") + "~" +
						libraryRecordInclusionRulesRS.getString("includeHoldableOnly") + "~" +
						libraryRecordInclusionRulesRS.getString("includeItemsOnOrder") + "~" +
						libraryRecordInclusionRulesRS.getString("includeEContent") + "~" +
						libraryRecordInclusionRulesRS.getString("marcTagToMatch") + "~" +
						libraryRecordInclusionRulesRS.getString("marcValueToMatch") + "~" +
						libraryRecordInclusionRulesRS.getString("includeExcludeMatches") + "~" +
						libraryRecordInclusionRulesRS.getString("urlToMatch") + "~" +
						libraryRecordInclusionRulesRS.getString("urlReplacement");
				if (allInclusionRules.containsKey(inclusionRuleKey)){
					newScope.addInclusionRule(allInclusionRules.get(inclusionRuleKey));
				}else{
					InclusionRule inclusionRule = new InclusionRule(libraryRecordInclusionRulesRS.getString("name"),
							libraryRecordInclusionRulesRS.getString("location"),
							libraryRecordInclusionRulesRS.getString("subLocation"),
							libraryRecordInclusionRulesRS.getString("locationsToExclude"),
							libraryRecordInclusionRulesRS.getString("subLocationsToExclude"),
							libraryRecordInclusionRulesRS.getString("iType"),
							libraryRecordInclusionRulesRS.getString("audience"),
							libraryRecordInclusionRulesRS.getString("format"),
							libraryRecordInclusionRulesRS.getBoolean("includeHoldableOnly"),
							libraryRecordInclusionRulesRS.getBoolean("includeItemsOnOrder"),
							libraryRecordInclusionRulesRS.getBoolean("includeEContent"),
							libraryRecordInclusionRulesRS.getString("marcTagToMatch"),
							libraryRecordInclusionRulesRS.getString("marcValueToMatch"),
							libraryRecordInclusionRulesRS.getBoolean("includeExcludeMatches"),
							libraryRecordInclusionRulesRS.getString("urlToMatch"),
							libraryRecordInclusionRulesRS.getString("urlReplacement")
					);
					allInclusionRules.put(inclusionRuleKey, inclusionRule);
					newScope.addInclusionRule(inclusionRule);
				}
			}

			scopes.add(newScope);
		}
	}

	public static boolean isIndexerRunning(String indexerName, Ini configIni, String serverName, Logger logger) {
		int numInstancesRunning = 0;
		if (configIni.get("System", "operatingSystem").equalsIgnoreCase("windows")){
			try {
				String line;
				Process p = Runtime.getRuntime().exec("tasklist.exe /fo csv /nh /v /fi \"IMAGENAME eq cmd.exe\"");
				BufferedReader input = new BufferedReader(new InputStreamReader(p.getInputStream()));
				while ((line = input.readLine()) != null) {
					//logger.info(line);
					if (line.matches(".*" + indexerName + "\\.jar " + serverName)){
						logger.warn(line);
						numInstancesRunning++;
					}
				}
				input.close();
			} catch (IOException e) {
				logger.error("Error checking to see if the " + indexerName + " reindexer is running", e);
			}

		}else{
			try {
				String line;
				Process p = Runtime.getRuntime().exec("ps -ef");
				BufferedReader input = new BufferedReader(new InputStreamReader(p.getInputStream()));
				while ((line = input.readLine()) != null) {
					//logger.info(line);
					if (line.matches(".*" + indexerName + "\\.jar " + serverName) && !line.contains("/bin/sh -c")){
						logger.warn(line);
						numInstancesRunning++;
					}
				}
				input.close();
			} catch (IOException e) {
				logger.error("Error checking to see if the " + indexerName + " indexer is running", e);
			}
		}
		if (numInstancesRunning > 1) {
			logger.error("Found " + numInstancesRunning + " instances of " + indexerName + " running");
			return true;
		}else{
			return false;
		}
	}

	public static boolean isNightlyIndexRunning(Ini configIni, String serverName, Logger logger) {
		if (configIni.get("System", "operatingSystem").equalsIgnoreCase("windows")){
			try {
				String line;
				Process p = Runtime.getRuntime().exec("tasklist.exe /fo csv /nh /v /fi \"IMAGENAME eq cmd.exe\"");
				BufferedReader input = new BufferedReader(new InputStreamReader(p.getInputStream()));
				while ((line = input.readLine()) != null) {
					//logger.info(line);
					if (line.matches(".*reindexer\\.jar " + serverName + " nightly.*")){
						return true;
					}
				}
				input.close();
			} catch (IOException e) {
				logger.error("Error checking to see if reindexer is running", e);
			}

		}else{
			try {
				String line;
				Process p = Runtime.getRuntime().exec("ps -ef");
				BufferedReader input = new BufferedReader(new InputStreamReader(p.getInputStream()));
				while ((line = input.readLine()) != null) {
					//logger.info(line);
					if (line.matches(".*reindexer\\.jar " + serverName + " nightly.*")){
						return true;
					}
				}
				input.close();
			} catch (IOException e) {
				logger.error("Error checking to see if reindexer is running", e);
			}
		}
		return false;
	}

	public static void markNightlyIndexNeeded(Connection dbConn, Logger logger) {
		try {
			//Mark that nightly index does not need to run since we are currently running it.
			dbConn.prepareStatement("UPDATE system_variables set runNightlyFullIndex = 1").executeUpdate();
		}catch (SQLException e) {
			logger.error("Unable to update that the nightly index should run tonight", e);
		}
	}
}
