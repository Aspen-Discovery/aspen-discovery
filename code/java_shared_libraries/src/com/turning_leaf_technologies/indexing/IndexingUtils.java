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
            HashMap<Long, HooplaScope> hooplaScopes = loadHooplaScopes(dbConn, logger);

            loadLibraryScopes(scopes, hooplaScopes, dbConn);

            loadLocationScopes(scopes, hooplaScopes, dbConn);
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

            while (hooplaScopesRS.next()){
                HooplaScope hooplaScope = new HooplaScope();
                hooplaScope.setId(hooplaScopesRS.getLong("id"));
                hooplaScope.setName(hooplaScopesRS.getString("name"));
                hooplaScope.setIncludeEBooks(hooplaScopesRS.getBoolean("includeEBooks"));
                hooplaScope.setMaxCostPerCheckoutEBooks(hooplaScopesRS.getLong("id"));
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

        }catch (SQLException e) {
            logger.error("Error loading hoopla scopes", e);
        }
        return hooplaScopes;
    }

    private static void loadLocationScopes(TreeSet<Scope> scopes, HashMap<Long, HooplaScope> hooplaScopes, Connection dbConn) throws SQLException {
        PreparedStatement locationInformationStmt = dbConn.prepareStatement("SELECT library.libraryId, locationId, code, subLocation, ilsCode, " +
                        "library.subdomain, location.facetLabel, location.displayName, library.pTypes, library.restrictOwningBranchesAndSystems, location.publicListsToInclude, " +
                        "library.enableOverdriveCollection as enableOverdriveCollectionLibrary, " +
                        "location.enableOverdriveCollection as enableOverdriveCollectionLocation, " +
                        "library.includeOverdriveAdult as includeOverdriveAdultLibrary, location.includeOverdriveAdult as includeOverdriveAdultLocation, " +
                        "library.includeOverdriveTeen as includeOverdriveTeenLibrary, location.includeOverdriveTeen as includeOverdriveTeenLocation, " +
                        "library.includeOverdriveKids as includeOverdriveKidsLibrary, location.includeOverdriveKids as includeOverdriveKidsLocation, " +
                        "location.additionalLocationsToShowAvailabilityFor, includeAllLibraryBranchesInFacets, " +
                        "location.includeAllRecordsInShelvingFacets, location.includeAllRecordsInDateAddedFacets, location.baseAvailabilityToggleOnLocalHoldingsOnly, " +
                        "location.includeOnlineMaterialsInAvailableToggle, location.includeLibraryRecordsToInclude, " +
                        "library.hooplaScopeId as hooplaScopeLibrary, location.hooplaScopeId as hooplaScopeLocation " +
                        "FROM location INNER JOIN library on library.libraryId = location.libraryId ORDER BY code ASC",
                ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
        PreparedStatement locationOwnedRecordRulesStmt = dbConn.prepareStatement("SELECT location_records_owned.*, indexing_profiles.name FROM location_records_owned INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE locationId = ?",
                ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
        PreparedStatement locationRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT location_records_to_include.*, indexing_profiles.name FROM location_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE locationId = ?",
                ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);

        ResultSet locationInformationRS = locationInformationStmt.executeQuery();
        while (locationInformationRS.next()){
            String code = locationInformationRS.getString("code").toLowerCase();
            String subLocation = locationInformationRS.getString("subLocation");
            String facetLabel = locationInformationRS.getString("facetLabel");
            String displayName = locationInformationRS.getString("displayName");
            if (facetLabel.length() == 0){
                facetLabel = displayName;
            }

            //Determine if we need to build a scope for this location
            long libraryId = locationInformationRS.getLong("libraryId");
            long locationId = locationInformationRS.getLong("locationId");
            String pTypes = locationInformationRS.getString("pTypes");
            if (pTypes == null) pTypes = "";
            boolean includeOverDriveCollectionLibrary = locationInformationRS.getBoolean("enableOverdriveCollectionLibrary");
            boolean includeOverDriveCollectionLocation = locationInformationRS.getBoolean("enableOverdriveCollectionLocation");

            Scope locationScopeInfo = new Scope();
            locationScopeInfo.setIsLibraryScope(false);
            locationScopeInfo.setIsLocationScope(true);
            String scopeName = code;
            if (subLocation != null && subLocation.length() > 0){
                scopeName = subLocation.toLowerCase();
            }
            locationScopeInfo.setScopeName(scopeName);
            locationScopeInfo.setLibraryId(libraryId);
            locationScopeInfo.setRelatedPTypes(pTypes.split(","));
            locationScopeInfo.setFacetLabel(facetLabel);
            locationScopeInfo.setIncludeOverDriveCollection(includeOverDriveCollectionLibrary && includeOverDriveCollectionLocation);
            boolean includeOverdriveAdult = locationInformationRS.getBoolean("includeOverdriveAdultLibrary") && locationInformationRS.getBoolean("includeOverdriveAdultLocation");
            boolean includeOverdriveTeen = locationInformationRS.getBoolean("includeOverdriveTeenLibrary") && locationInformationRS.getBoolean("includeOverdriveTeenLocation");
            boolean includeOverdriveKids = locationInformationRS.getBoolean("includeOverdriveKidsLibrary") && locationInformationRS.getBoolean("includeOverdriveKidsLocation");
            locationScopeInfo.setIncludeOverDriveAdultCollection(includeOverdriveAdult);
            locationScopeInfo.setIncludeOverDriveTeenCollection(includeOverdriveTeen);
            locationScopeInfo.setIncludeOverDriveKidsCollection(includeOverdriveKids);
            locationScopeInfo.setRestrictOwningLibraryAndLocationFacets(locationInformationRS.getBoolean("restrictOwningBranchesAndSystems"));
            locationScopeInfo.setIlsCode(code);
            locationScopeInfo.setPublicListsToInclude(locationInformationRS.getInt("publicListsToInclude"));
            locationScopeInfo.setAdditionalLocationsToShowAvailabilityFor(locationInformationRS.getString("additionalLocationsToShowAvailabilityFor"));
            locationScopeInfo.setIncludeAllLibraryBranchesInFacets(locationInformationRS.getBoolean("includeAllLibraryBranchesInFacets"));
            locationScopeInfo.setIncludeAllRecordsInShelvingFacets(locationInformationRS.getBoolean("includeAllRecordsInShelvingFacets"));
            locationScopeInfo.setIncludeAllRecordsInDateAddedFacets(locationInformationRS.getBoolean("includeAllRecordsInDateAddedFacets"));
            locationScopeInfo.setBaseAvailabilityToggleOnLocalHoldingsOnly(locationInformationRS.getBoolean("baseAvailabilityToggleOnLocalHoldingsOnly"));
            locationScopeInfo.setIncludeOnlineMaterialsInAvailableToggle(locationInformationRS.getBoolean("includeOnlineMaterialsInAvailableToggle"));

            long hooplaScopeLocation = locationInformationRS.getLong("hooplaScopeLocation");
            long hooplaScopeLibrary = locationInformationRS.getLong("hooplaScopeLibrary");
            if (hooplaScopeLocation == -1 ){
                if (hooplaScopeLibrary != -1) {
                    locationScopeInfo.setHooplaScope(hooplaScopes.get(hooplaScopeLibrary));
                }
            }else{
                locationScopeInfo.setHooplaScope(hooplaScopes.get(hooplaScopeLocation));
            }

            //Load information about what should be included in the scope
            locationOwnedRecordRulesStmt.setLong(1, locationId);
            ResultSet locationOwnedRecordRulesRS = locationOwnedRecordRulesStmt.executeQuery();
            while (locationOwnedRecordRulesRS.next()){
                locationScopeInfo.addOwnershipRule(new OwnershipRule(locationOwnedRecordRulesRS.getString("name"), locationOwnedRecordRulesRS.getString("location"), locationOwnedRecordRulesRS.getString("subLocation")));
            }

            locationRecordInclusionRulesStmt.setLong(1, locationId);
            ResultSet locationRecordInclusionRulesRS = locationRecordInclusionRulesStmt.executeQuery();
            while (locationRecordInclusionRulesRS.next()){
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
            if (includeLibraryRecordsToInclude){
                libraryRecordInclusionRulesStmt.setLong(1, libraryId);
                ResultSet libraryRecordInclusionRulesRS = libraryRecordInclusionRulesStmt.executeQuery();
                while (libraryRecordInclusionRulesRS.next()){
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
                locationScopeInfo.setScopeName(locationScopeInfo.getScopeName()+"loc");
            }
            //Connect this scope to the library scopes
            for (Scope curScope : scopes){
                if (curScope.isLibraryScope() && Objects.equals(curScope.getLibraryId(), libraryId)){
                    curScope.addLocationScope(locationScopeInfo);
                    locationScopeInfo.setLibraryScope(curScope);
                    break;
                }
            }
            scopes.add(locationScopeInfo);

        }
    }

    private static PreparedStatement libraryRecordInclusionRulesStmt;
    private static void loadLibraryScopes(TreeSet<Scope> scopes, HashMap<Long, HooplaScope> hooplaScopes, Connection dbConn) throws SQLException {
        PreparedStatement libraryInformationStmt = dbConn.prepareStatement("SELECT libraryId, ilsCode, subdomain, " +
                        "displayName, facetLabel, pTypes, enableOverdriveCollection, restrictOwningBranchesAndSystems, publicListsToInclude, " +
                        "additionalLocationsToShowAvailabilityFor, includeOverdriveAdult, includeOverdriveTeen, includeOverdriveKids, " +
                        "includeAllRecordsInShelvingFacets, includeAllRecordsInDateAddedFacets, includeOnlineMaterialsInAvailableToggle, hooplaScopeId " +
                        "FROM library ORDER BY ilsCode ASC",
                ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
        PreparedStatement libraryOwnedRecordRulesStmt = dbConn.prepareStatement("SELECT library_records_owned.*, indexing_profiles.name from library_records_owned INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
        libraryRecordInclusionRulesStmt = dbConn.prepareStatement("SELECT library_records_to_include.*, indexing_profiles.name from library_records_to_include INNER JOIN indexing_profiles ON indexingProfileId = indexing_profiles.id WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
        ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
        while (libraryInformationRS.next()){
            String facetLabel = libraryInformationRS.getString("facetLabel");
            String subdomain = libraryInformationRS.getString("subdomain");
            String displayName = libraryInformationRS.getString("displayName");
            if (facetLabel.length() == 0){
                facetLabel = displayName;
            }
            //These options determine how scoping is done
            long libraryId = libraryInformationRS.getLong("libraryId");
            String pTypes = libraryInformationRS.getString("pTypes");
            if (pTypes == null) {pTypes = "";}
            boolean includeOverdrive = libraryInformationRS.getBoolean("enableOverdriveCollection");
            boolean includeOverdriveAdult = libraryInformationRS.getBoolean("includeOverdriveAdult");
            boolean includeOverdriveTeen = libraryInformationRS.getBoolean("includeOverdriveTeen");
            boolean includeOverdriveKids = libraryInformationRS.getBoolean("includeOverdriveKids");

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
            newScope.setIncludeOverDriveCollection(includeOverdrive);
            newScope.setPublicListsToInclude(libraryInformationRS.getInt("publicListsToInclude"));
            newScope.setAdditionalLocationsToShowAvailabilityFor(libraryInformationRS.getString("additionalLocationsToShowAvailabilityFor"));
            newScope.setIncludeAllRecordsInShelvingFacets(libraryInformationRS.getBoolean("includeAllRecordsInShelvingFacets"));
            newScope.setIncludeAllRecordsInDateAddedFacets(libraryInformationRS.getBoolean("includeAllRecordsInDateAddedFacets"));

            newScope.setIncludeOnlineMaterialsInAvailableToggle(libraryInformationRS.getBoolean("includeOnlineMaterialsInAvailableToggle"));

            newScope.setIncludeOverDriveAdultCollection(includeOverdriveAdult);
            newScope.setIncludeOverDriveTeenCollection(includeOverdriveTeen);
            newScope.setIncludeOverDriveKidsCollection(includeOverdriveKids);

            long hooplaScopeLibrary = libraryInformationRS.getLong("hooplaScopeId");
            if (hooplaScopeLibrary != -1) {
                newScope.setHooplaScope(hooplaScopes.get(hooplaScopeLibrary));
            }

            newScope.setRestrictOwningLibraryAndLocationFacets(libraryInformationRS.getBoolean("restrictOwningBranchesAndSystems"));
            newScope.setIlsCode(libraryInformationRS.getString("ilsCode"));

            //Load information about what should be included in the scope
            libraryOwnedRecordRulesStmt.setLong(1, libraryId);
            ResultSet libraryOwnedRecordRulesRS = libraryOwnedRecordRulesStmt.executeQuery();
            while (libraryOwnedRecordRulesRS.next()){
                newScope.addOwnershipRule(new OwnershipRule(libraryOwnedRecordRulesRS.getString("name"), libraryOwnedRecordRulesRS.getString("location"), libraryOwnedRecordRulesRS.getString("subLocation")));
            }

            libraryRecordInclusionRulesStmt.setLong(1, libraryId);
            ResultSet libraryRecordInclusionRulesRS = libraryRecordInclusionRulesStmt.executeQuery();
            while (libraryRecordInclusionRulesRS.next()){
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
