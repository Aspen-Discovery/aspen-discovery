package com.turning_leaf_technologies.indexing;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Objects;
import java.util.TreeSet;

import org.apache.logging.log4j.Logger;

public class IndexingUtils {

	public static TreeSet<Scope> loadScopes(Connection dbConn, Logger logger) {
		TreeSet<Scope> scopes = new TreeSet<>();
		//Setup translation maps for system and location
		try {
			HashMap<Long, OverDriveScope> overDriveScopes = loadOverDriveScopes(dbConn, logger);
			HashMap<Long, HooplaScope> hooplaScopes = loadHooplaScopes(dbConn, logger);
			HashMap<Long, RbdigitalScope> rbdigitalScopes = loadRbdigitalScopes(dbConn, logger);
			HashMap<Long, CloudLibraryScope> cloudLibraryScopes = loadCloudLibraryScopes(dbConn, logger);
			HashMap<Long, SideLoadScope> sideLoadScopes = loadSideLoadScopes(dbConn, logger);

			loadLibraryScopes(scopes, overDriveScopes, hooplaScopes, rbdigitalScopes, cloudLibraryScopes, sideLoadScopes, dbConn);

			loadLocationScopes(scopes, overDriveScopes, hooplaScopes, rbdigitalScopes, cloudLibraryScopes, sideLoadScopes, dbConn);
		} catch (SQLException e) {
			logger.error("Error setting up scopes", e);
		}

		return scopes;
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
				hooplaScope.setRestrictToChildrensMaterial(hooplaScopesRS.getBoolean("restrictToChildrensMaterial"));
				hooplaScope.setRatingsToExclude(hooplaScopesRS.getString("ratingsToExclude"));
				hooplaScope.setExcludeAbridged(hooplaScopesRS.getBoolean("excludeAbridged"));
				hooplaScope.setExcludeParentalAdvisory(hooplaScopesRS.getBoolean("excludeParentalAdvisory"));
				hooplaScope.setExcludeProfanity(hooplaScopesRS.getBoolean("excludeProfanity"));

				hooplaScopes.put(hooplaScope.getId(), hooplaScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading hoopla scopes", e);
		}
		return hooplaScopes;
	}

	private static HashMap<Long, RbdigitalScope> loadRbdigitalScopes(Connection dbConn, Logger logger) {
		HashMap<Long, RbdigitalScope> rbdigitalScopes = new HashMap<>();
		try {
			PreparedStatement rbdigitalScopeStmt = dbConn.prepareStatement("SELECT * from rbdigital_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet rbdigitalScopesRS = rbdigitalScopeStmt.executeQuery();

			while (rbdigitalScopesRS.next()) {
				RbdigitalScope rbdigitalScope = new RbdigitalScope();
				rbdigitalScope.setId(rbdigitalScopesRS.getLong("id"));
				rbdigitalScope.setName(rbdigitalScopesRS.getString("name"));
				rbdigitalScope.setIncludeEBooks(rbdigitalScopesRS.getBoolean("includeEBooks"));
				rbdigitalScope.setIncludeEMagazines(rbdigitalScopesRS.getBoolean("includeEMagazines"));
				rbdigitalScope.setIncludeEAudiobook(rbdigitalScopesRS.getBoolean("includeEAudiobook"));
				rbdigitalScope.setRestrictToChildrensMaterial(rbdigitalScopesRS.getBoolean("restrictToChildrensMaterial"));

				rbdigitalScopes.put(rbdigitalScope.getId(), rbdigitalScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading RBdigital scopes", e);
		}
		return rbdigitalScopes;
	}

	private static HashMap<Long, OverDriveScope> loadOverDriveScopes(Connection dbConn, Logger logger) {
		HashMap<Long, OverDriveScope> overDriveScopes = new HashMap<>();
		try {
			PreparedStatement overDriveScopeStmt = dbConn.prepareStatement("SELECT * from overdrive_scopes", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet overDriveScopesRS = overDriveScopeStmt.executeQuery();

			while (overDriveScopesRS.next()) {
				OverDriveScope overDriveScope = new OverDriveScope();
				overDriveScope.setId(overDriveScopesRS.getLong("id"));
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
				cloudLibraryScope.setName(cloudLibraryScopesRS.getString("name"));
				cloudLibraryScope.setIncludeEBooks(cloudLibraryScopesRS.getBoolean("includeEBooks"));
				cloudLibraryScope.setIncludeEAudiobook(cloudLibraryScopesRS.getBoolean("includeEAudiobook"));
				cloudLibraryScope.setRestrictToChildrensMaterial(cloudLibraryScopesRS.getBoolean("restrictToChildrensMaterial"));

				cloudLibraryScopes.put(cloudLibraryScope.getId(), cloudLibraryScope);
			}

		} catch (SQLException e) {
			logger.error("Error loading Cloud Library scopes", e);
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

	private static void loadLocationScopes(TreeSet<Scope> scopes, HashMap<Long, OverDriveScope> overDriveScopes, HashMap<Long, HooplaScope> hooplaScopes, HashMap<Long, RbdigitalScope> rbdigitalScopes, HashMap<Long, CloudLibraryScope> cloudLibraryScopes, HashMap<Long, SideLoadScope> sideLoadScopes, Connection dbConn) throws SQLException {
		PreparedStatement locationInformationStmt = dbConn.prepareStatement("SELECT library.libraryId, locationId, code, subLocation, ilsCode, " +
						"library.subdomain, location.facetLabel, location.displayName, library.pTypes, library.restrictOwningBranchesAndSystems, location.publicListsToInclude, " +
						"location.additionalLocationsToShowAvailabilityFor, includeAllLibraryBranchesInFacets, " +
						"location.includeAllRecordsInShelvingFacets, location.includeAllRecordsInDateAddedFacets, location.baseAvailabilityToggleOnLocalHoldingsOnly, " +
						"location.includeOnlineMaterialsInAvailableToggle, location.includeLibraryRecordsToInclude, " +
						"library.overDriveScopeId as overDriveScopeIdLibrary, location.overDriveScopeId as overDriveScopeIdLocation, " +
						"library.hooplaScopeId as hooplaScopeLibrary, location.hooplaScopeId as hooplaScopeLocation, " +
						"library.rbdigitalScopeId as rbdigitalScopeLibrary, location.rbdigitalScopeId as rbdigitalScopeLocation, " +
						"library.cloudLibraryScopeId as cloudLibraryScopeLibrary, location.cloudLibraryScopeId as cloudLibraryScopeLocation " +
						"FROM location INNER JOIN library on library.libraryId = location.libraryId ORDER BY code ASC",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationOwnedRecordRulesStmt = dbConn.prepareStatement("SELECT location_records_owned.*, indexing_profiles.name FROM location_records_owned INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE locationId = ?",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT location_records_to_include.*, indexing_profiles.name FROM location_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE locationId = ?",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement librarySideLoadScopesStmt = dbConn.prepareStatement("SELECT * from library_sideload_scopes WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement locationSideLoadScopesStmt = dbConn.prepareStatement("SELECT * from location_sideload_scopes WHERE locationId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

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
			String pTypes = locationInformationRS.getString("pTypes");
			if (pTypes == null) pTypes = "";

			Scope locationScopeInfo = new Scope();
			locationScopeInfo.setIsLibraryScope(false);
			locationScopeInfo.setIsLocationScope(true);
			String scopeName = code;
			if (subLocation != null && subLocation.length() > 0) {
				scopeName = subLocation.toLowerCase();
			}
			locationScopeInfo.setScopeName(scopeName);
			locationScopeInfo.setLibraryId(libraryId);
			locationScopeInfo.setRelatedPTypes(pTypes.split(","));
			locationScopeInfo.setFacetLabel(facetLabel);
			locationScopeInfo.setRestrictOwningLibraryAndLocationFacets(locationInformationRS.getBoolean("restrictOwningBranchesAndSystems"));
			locationScopeInfo.setIlsCode(code);
			locationScopeInfo.setPublicListsToInclude(locationInformationRS.getInt("publicListsToInclude"));
			locationScopeInfo.setAdditionalLocationsToShowAvailabilityFor(locationInformationRS.getString("additionalLocationsToShowAvailabilityFor"));
			locationScopeInfo.setIncludeAllLibraryBranchesInFacets(locationInformationRS.getBoolean("includeAllLibraryBranchesInFacets"));
			locationScopeInfo.setIncludeAllRecordsInShelvingFacets(locationInformationRS.getBoolean("includeAllRecordsInShelvingFacets"));
			locationScopeInfo.setIncludeAllRecordsInDateAddedFacets(locationInformationRS.getBoolean("includeAllRecordsInDateAddedFacets"));
			locationScopeInfo.setBaseAvailabilityToggleOnLocalHoldingsOnly(locationInformationRS.getBoolean("baseAvailabilityToggleOnLocalHoldingsOnly"));
			locationScopeInfo.setIncludeOnlineMaterialsInAvailableToggle(locationInformationRS.getBoolean("includeOnlineMaterialsInAvailableToggle"));

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

			long rbdigitalScopeLocation = locationInformationRS.getLong("rbdigitalScopeLocation");
			long rbdigitalScopeLibrary = locationInformationRS.getLong("rbdigitalScopeLibrary");
			if (rbdigitalScopeLocation == -1) {
				if (rbdigitalScopeLibrary != -1) {
					locationScopeInfo.setRbdigitalScope(rbdigitalScopes.get(rbdigitalScopeLibrary));
				}
			} else if (rbdigitalScopeLocation == -2) {
				locationScopeInfo.setRbdigitalScope(rbdigitalScopes.get(rbdigitalScopeLocation));
			}

			long cloudLibraryScopeLocation = locationInformationRS.getLong("cloudLibraryScopeLocation");
			long cloudLibraryScopeLibrary = locationInformationRS.getLong("cloudLibraryScopeLibrary");
			if (cloudLibraryScopeLocation == -1) {
				if (cloudLibraryScopeLibrary != -1) {
					locationScopeInfo.setCloudLibraryScope(cloudLibraryScopes.get(cloudLibraryScopeLibrary));
				}
			} else if (rbdigitalScopeLocation == -2) {
				locationScopeInfo.setCloudLibraryScope(cloudLibraryScopes.get(cloudLibraryScopeLocation));
			}

			locationSideLoadScopesStmt.setLong(1, locationId);
			ResultSet locationSideLoadScopesRS = locationSideLoadScopesStmt.executeQuery();
			while (locationSideLoadScopesRS.next()) {
				long scopeId = locationSideLoadScopesRS.getLong("sideLoadScopeId");
				if (scopeId == -1) {
					librarySideLoadScopesStmt.setLong(1, libraryId);
					ResultSet librarySideLoadScopesRS = librarySideLoadScopesStmt.executeQuery();
					while (librarySideLoadScopesRS.next()) {
						locationScopeInfo.addSideLoadScope(sideLoadScopes.get(librarySideLoadScopesRS.getLong("sideLoadScopeId")));
					}
				} else {
					locationScopeInfo.addSideLoadScope(sideLoadScopes.get(scopeId));
				}
			}

			//Load information about what should be included in the scope
			locationOwnedRecordRulesStmt.setLong(1, locationId);
			ResultSet locationOwnedRecordRulesRS = locationOwnedRecordRulesStmt.executeQuery();
			while (locationOwnedRecordRulesRS.next()) {
				locationScopeInfo.addOwnershipRule(new OwnershipRule(locationOwnedRecordRulesRS.getString("name"), locationOwnedRecordRulesRS.getString("location"), locationOwnedRecordRulesRS.getString("subLocation")));
			}

			locationRecordInclusionRulesStmt.setLong(1, locationId);
			ResultSet locationRecordInclusionRulesRS = locationRecordInclusionRulesStmt.executeQuery();
			while (locationRecordInclusionRulesRS.next()) {
				locationScopeInfo.addInclusionRule(new InclusionRule(locationRecordInclusionRulesRS.getString("name"),
						locationRecordInclusionRulesRS.getString("location"),
						locationRecordInclusionRulesRS.getString("subLocation"),
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
				));
			}

			boolean includeLibraryRecordsToInclude = locationInformationRS.getBoolean("includeLibraryRecordsToInclude");
			if (includeLibraryRecordsToInclude) {
				libraryRecordInclusionRulesStmt.setLong(1, libraryId);
				ResultSet libraryRecordInclusionRulesRS = libraryRecordInclusionRulesStmt.executeQuery();
				while (libraryRecordInclusionRulesRS.next()) {
					locationScopeInfo.addInclusionRule(new InclusionRule(libraryRecordInclusionRulesRS.getString("name"),
							libraryRecordInclusionRulesRS.getString("location"),
							libraryRecordInclusionRulesRS.getString("subLocation"),
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
					));
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

	private static PreparedStatement libraryRecordInclusionRulesStmt;

	private static void loadLibraryScopes(TreeSet<Scope> scopes, HashMap<Long, OverDriveScope> overDriveScopes, HashMap<Long, HooplaScope> hooplaScopes, HashMap<Long, RbdigitalScope> rbdigitalScopes, HashMap<Long, CloudLibraryScope> cloudLibraryScopes, HashMap<Long, SideLoadScope> sideLoadScopes, Connection dbConn) throws SQLException {
		PreparedStatement libraryInformationStmt = dbConn.prepareStatement("SELECT libraryId, ilsCode, subdomain, " +
						"displayName, facetLabel, pTypes, restrictOwningBranchesAndSystems, publicListsToInclude, " +
						"additionalLocationsToShowAvailabilityFor, overDriveScopeId, " +
						"includeAllRecordsInShelvingFacets, includeAllRecordsInDateAddedFacets, includeOnlineMaterialsInAvailableToggle, " +
						"hooplaScopeId, rbdigitalScopeId, cloudLibraryScopeId " +
						"FROM library ORDER BY ilsCode ASC",
				ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		PreparedStatement libraryOwnedRecordRulesStmt = dbConn.prepareStatement("SELECT library_records_owned.*, indexing_profiles.name from library_records_owned INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		libraryRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT library_records_to_include.*, indexing_profiles.name from library_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
		PreparedStatement librarySideLoadScopesStmt = dbConn.prepareStatement("SELECT * from library_sideload_scopes WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

		while (libraryInformationRS.next()) {
			String facetLabel = libraryInformationRS.getString("facetLabel");
			String subdomain = libraryInformationRS.getString("subdomain");
			String displayName = libraryInformationRS.getString("displayName");
			if (facetLabel.length() == 0) {
				facetLabel = displayName;
			}
			//These options determine how scoping is done
			long libraryId = libraryInformationRS.getLong("libraryId");
			String pTypes = libraryInformationRS.getString("pTypes");
			if (pTypes == null) {
				pTypes = "";
			}

			//Determine if we need to build a scope for this library
			//MDN 10/1/2014 always build scopes because it makes coding more consistent elsewhere.
			//We need to build a scope
			Scope newScope = new Scope();
			newScope.setIsLibraryScope(true);
			newScope.setIsLocationScope(false);
			newScope.setScopeName(subdomain);
			newScope.setLibraryId(libraryId);
			newScope.setFacetLabel(facetLabel);
			newScope.setRelatedPTypes(pTypes.split(","));
			newScope.setPublicListsToInclude(libraryInformationRS.getInt("publicListsToInclude"));
			newScope.setAdditionalLocationsToShowAvailabilityFor(libraryInformationRS.getString("additionalLocationsToShowAvailabilityFor"));
			newScope.setIncludeAllRecordsInShelvingFacets(libraryInformationRS.getBoolean("includeAllRecordsInShelvingFacets"));
			newScope.setIncludeAllRecordsInDateAddedFacets(libraryInformationRS.getBoolean("includeAllRecordsInDateAddedFacets"));

			newScope.setIncludeOnlineMaterialsInAvailableToggle(libraryInformationRS.getBoolean("includeOnlineMaterialsInAvailableToggle"));

			long overDriveScopeLibrary = libraryInformationRS.getLong("overDriveScopeId");
			if (overDriveScopeLibrary != -1) {
				newScope.setOverDriveScope(overDriveScopes.get(overDriveScopeLibrary));
			}

			long hooplaScopeLibrary = libraryInformationRS.getLong("hooplaScopeId");
			if (hooplaScopeLibrary != -1) {
				newScope.setHooplaScope(hooplaScopes.get(hooplaScopeLibrary));
			}

			long rbdigitalScopeLibrary = libraryInformationRS.getLong("rbdigitalScopeId");
			if (rbdigitalScopeLibrary != -1) {
				newScope.setRbdigitalScope(rbdigitalScopes.get(rbdigitalScopeLibrary));
			}

			long cloudLibraryScopeLibrary = libraryInformationRS.getLong("cloudLibraryScopeId");
			if (cloudLibraryScopeLibrary != -1) {
				newScope.setCloudLibraryScope(cloudLibraryScopes.get(cloudLibraryScopeLibrary));
			}

			librarySideLoadScopesStmt.setLong(1, libraryId);
			ResultSet librarySideLoadScopesRS = librarySideLoadScopesStmt.executeQuery();
			while (librarySideLoadScopesRS.next()) {
				newScope.addSideLoadScope(sideLoadScopes.get(librarySideLoadScopesRS.getLong("sideLoadScopeId")));
			}

			newScope.setRestrictOwningLibraryAndLocationFacets(libraryInformationRS.getBoolean("restrictOwningBranchesAndSystems"));
			newScope.setIlsCode(libraryInformationRS.getString("ilsCode"));

			//Load information about what should be included in the scope
			libraryOwnedRecordRulesStmt.setLong(1, libraryId);
			ResultSet libraryOwnedRecordRulesRS = libraryOwnedRecordRulesStmt.executeQuery();
			while (libraryOwnedRecordRulesRS.next()) {
				newScope.addOwnershipRule(new OwnershipRule(libraryOwnedRecordRulesRS.getString("name"), libraryOwnedRecordRulesRS.getString("location"), libraryOwnedRecordRulesRS.getString("subLocation")));
			}

			libraryRecordInclusionRulesStmt.setLong(1, libraryId);
			ResultSet libraryRecordInclusionRulesRS = libraryRecordInclusionRulesStmt.executeQuery();
			while (libraryRecordInclusionRulesRS.next()) {
				newScope.addInclusionRule(new InclusionRule(libraryRecordInclusionRulesRS.getString("name"),
						libraryRecordInclusionRulesRS.getString("location"),
						libraryRecordInclusionRulesRS.getString("subLocation"),
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
				));
			}

			scopes.add(newScope);
		}
	}

}
