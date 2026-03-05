package com.turning_leaf_technologies.indexing;

import org.marc4j.marc.Record;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.TreeSet;
import java.util.regex.Pattern;

/**
 * A library or location scope with all the relevant information about which records should be included in the scope
 * as well as how the records should be handled during indexing.
 */
public class Scope implements Comparable<Scope>{
	private long id;

	private String scopeName;
	private String facetLabel;

	private Long libraryId;

	//Determine if this is a library scope or location scope and store related information
	private boolean isLibraryScope;
	//If this is a library scope, we want to store pointers to the individual location scopes
	private final ArrayList<Scope> locationScopes = new ArrayList<>();

	private boolean isLocationScope;
	private Scope libraryScope;

	//Called restrictOwningBranchesAndSystems in PHP admin interface
	private boolean restrictOwningLibraryAndLocationFacets;
	private boolean isConsortialCatalog;
	private final ArrayList<InclusionRule> ownershipRules = new ArrayList<>();
	//Inclusion rules indicate records owned by someone else that should be shown within the scope
	private final ArrayList<InclusionRule> inclusionRules = new ArrayList<>();
	private String ilsCode;

	private int publicListsToInclude;
	private String additionalLocationsToShowAvailabilityFor;
	private Pattern additionalLocationsToShowAvailabilityForPattern;
	private String locationsToExcludeAvailabilityFor;
	private Pattern locationsToExcludeAvailabilityForPattern;
	private boolean includeAllLibraryBranchesInFacets; //Only applies to location scopes
	private Pattern courseReserveLibrariesToIncludePattern;

	private GroupedWorkDisplaySettings groupedWorkDisplaySettings;
	private final HashMap<Long, OverDriveScope> overDriveScopes = new HashMap<>();
	private HooplaScope hooplaScope;
	private final HashMap<Long, CloudLibraryScope> cloudLibraryScopes = new HashMap<>();
	private Axis360Scope axis360Scope;
	private PalaceProjectScope palaceProjectScope;

	private final HashMap<Long, SideLoadScope> sideLoadScopes = new HashMap<>();

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getScopeName() {
		return scopeName;
	}

	void setScopeName(String scopeName) {
		this.scopeName = scopeName;
		this.scopeName = this.scopeName.replaceAll("[^a-zA-Z0-9_]", "");
	}

	void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel.trim();
	}

	private static final InclusionResult  includedOwnedResult = new InclusionResult(true, true, null);
	private static final InclusionResult  includedNonOwnedResult = new InclusionResult(true, false, null);
	private static final InclusionResult  nonIncludedNonOwnedResult = new InclusionResult(false, false, null);

	/**
	 * Determine if the item is part of the current scope based on location code and other information
	 *
	 *
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether the item is included within the scope
	 */
	public InclusionResult isItemPartOfScope(String itemIdentifier, String recordType, String locationCode, String subLocationCode, String iType, TreeSet<String> audiences, String audiencesAsString, String format, String shelfLocation, String collectionCode, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord, String econtentUrl, DebugLogger debugLogger){
		if (debugLogger != null && debugLogger.isDebugEnabled()) {
			debugLogger.addDebugMessage("Checking scope '" + facetLabel + "' for item " + itemIdentifier, 2);
		}

		if (isItemOwnedByScope(itemIdentifier, recordType, locationCode, subLocationCode, iType, audiences, audiencesAsString, format, shelfLocation, collectionCode, isHoldable, isOnOrder, isEContent, marcRecord, debugLogger)){
			if (econtentUrl == null){
				return includedOwnedResult;
			}else {
				return new InclusionResult(true, true, econtentUrl);
			}
		}

		for (InclusionRule curRule : inclusionRules) {
			if (curRule.isItemIncluded(itemIdentifier, recordType, locationCode, subLocationCode, iType, audiences, audiencesAsString, format, shelfLocation, collectionCode, isHoldable, isOnOrder, isEContent, marcRecord, debugLogger)) {
				if (econtentUrl == null) {
					return includedNonOwnedResult;
				} else {
					econtentUrl = curRule.getLocalUrl(econtentUrl);
					return new InclusionResult(true, false, econtentUrl);
				}
			}
		}

		if (econtentUrl == null) {
			return nonIncludedNonOwnedResult;
		} else {
			return new InclusionResult(false, false, econtentUrl);
		}
	}

	/**
	 * Determine if the item is part of the current scope based on location code and other information
	 */
	public boolean isItemOwnedByScope(String itemIdentifier, String recordType, String locationCode, String subLocationCode, String iType, TreeSet<String> audiences, String audiencesAsString, String format, String shelfLocation, String collectionCode, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord, DebugLogger debugLogger){
		for (InclusionRule curRule: ownershipRules){
			if (curRule.isItemIncluded(itemIdentifier, recordType, locationCode, subLocationCode, iType, audiences, audiencesAsString, format, shelfLocation, collectionCode, isHoldable, isOnOrder, isEContent, marcRecord, debugLogger)){
				return true;
			}
		}
		return false;
	}

	public String getFacetLabel() {
		return facetLabel;
	}

	void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}

	public Long getLibraryId() {
		return libraryId;
	}


	@Override
	public int compareTo(Scope o) {
		return scopeName.compareTo(o.scopeName);
	}

	void setIsLibraryScope(boolean isLibraryScope) {
		this.isLibraryScope = isLibraryScope;
	}

	public boolean isLibraryScope() {
		return isLibraryScope;
	}

	void setIsLocationScope(boolean isLocationScope) {
		this.isLocationScope = isLocationScope;
	}

	public boolean isLocationScope() {
		return isLocationScope;
	}

	void addOwnershipRule(InclusionRule ownershipRule) {
		if (!ownershipRules.contains( ownershipRule ) ) {
			ownershipRules.add(ownershipRule);
		}
	}

	void addInclusionRule(InclusionRule inclusionRule) {
		if (!inclusionRules.contains( inclusionRule ) ) {
			inclusionRules.add(inclusionRule);
		}
	}

	void addLocationScope(Scope locationScope) {
		if (!locationScopes.contains( locationScope ) ) {
			this.locationScopes.add(locationScope);
		}
	}

	void setLibraryScope(Scope libraryScope) {
		this.libraryScope = libraryScope;
	}

	public Scope getLibraryScope() {
		return libraryScope;
	}

	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	public boolean isRestrictOwningLibraryAndLocationFacets() {
		return restrictOwningLibraryAndLocationFacets;
	}

	void setRestrictOwningLibraryAndLocationFacets(boolean restrictOwningLibraryAndLocationFacets) {
		this.restrictOwningLibraryAndLocationFacets = restrictOwningLibraryAndLocationFacets;
	}

	public ArrayList<Scope> getLocationScopes() {
		return locationScopes;
	}

	public String getIlsCode() {
		return ilsCode;
	}

	void setIlsCode(String ilsCode) {
		this.ilsCode = ilsCode;
	}

	void setPublicListsToInclude(int publicListsToInclude) {
		this.publicListsToInclude = publicListsToInclude;
	}

	public int getPublicListsToInclude() {
		return publicListsToInclude;
	}

	void setAdditionalLocationsToShowAvailabilityFor(String additionalLocationsToShowAvailabilityFor) {
		this.additionalLocationsToShowAvailabilityFor = additionalLocationsToShowAvailabilityFor;
		if (additionalLocationsToShowAvailabilityFor != null && !additionalLocationsToShowAvailabilityFor.isEmpty()){
			additionalLocationsToShowAvailabilityForPattern = Pattern.compile(additionalLocationsToShowAvailabilityFor);
		}
	}

	public String getAdditionalLocationsToShowAvailabilityFor() {
		return additionalLocationsToShowAvailabilityFor;
	}

	void setLocationsToExcludeAvailabilityFor(String locationsToExcludeAvailabilityFor) {
		this.locationsToExcludeAvailabilityFor = locationsToExcludeAvailabilityFor;
		if (locationsToExcludeAvailabilityFor != null && !locationsToExcludeAvailabilityFor.isEmpty()) {
			locationsToExcludeAvailabilityForPattern = Pattern.compile(locationsToExcludeAvailabilityFor);
		}
	}

	public boolean isIncludeAllLibraryBranchesInFacets() {
		return includeAllLibraryBranchesInFacets;
	}

	void setIncludeAllLibraryBranchesInFacets(boolean includeAllLibraryBranchesInFacets) {
		this.includeAllLibraryBranchesInFacets = includeAllLibraryBranchesInFacets;
	}

	public Pattern getAdditionalLocationsToShowAvailabilityForPattern() {
		return additionalLocationsToShowAvailabilityForPattern;
	}

	public Pattern getLocationsToExcludeAvailabilityForPattern() {
		return locationsToExcludeAvailabilityForPattern;
	}

	void addOverDriveScope(OverDriveScope overDriveScope) {
		this.overDriveScopes.put(overDriveScope.getSettingId(), overDriveScope);
	}

	public HashMap<Long, OverDriveScope> getOverDriveScopes() {
		return overDriveScopes;
	}
	public OverDriveScope getOverDriveScope(long settingId) {
		return overDriveScopes.get(settingId);
	}

	public boolean isIncludeOverDriveCollection(long settingId) {
		return overDriveScopes.containsKey(settingId);
	}

	public HooplaScope getHooplaScope() {
		return hooplaScope;
	}

	void setHooplaScope(HooplaScope hooplaScope) {
		this.hooplaScope = hooplaScope;
	}

	void addCloudLibraryScope(CloudLibraryScope cloudLibraryScope) {
		this.cloudLibraryScopes.put(cloudLibraryScope.getSettingId(), cloudLibraryScope);
	}

	public CloudLibraryScope getCloudLibraryScope(long settingId) {
		return cloudLibraryScopes.get(settingId);
	}

	void addSideLoadScope(SideLoadScope scope){
		sideLoadScopes.put(scope.getSideLoadId(), scope);
	}

	public SideLoadScope getSideLoadScope(long sideLoadId){
		return sideLoadScopes.get(sideLoadId);
	}

	public GroupedWorkDisplaySettings getGroupedWorkDisplaySettings() {
		return groupedWorkDisplaySettings;
	}

	void setGroupedWorkDisplaySettings(GroupedWorkDisplaySettings groupedWorkDisplaySettings) {
		this.groupedWorkDisplaySettings = groupedWorkDisplaySettings;
	}

	public Axis360Scope getAxis360Scope() {
		return axis360Scope;
	}

	public void setAxis360Scope(Axis360Scope axis360Scope) {
		this.axis360Scope = axis360Scope;
	}

	public PalaceProjectScope getPalaceProjectScope() {
		return palaceProjectScope;
	}

	public void setPalaceProjectScope(PalaceProjectScope palaceProjectScope) {
		this.palaceProjectScope = palaceProjectScope;
	}

	public boolean isConsortialCatalog() {
		return isConsortialCatalog;
	}

	public void setConsortialCatalog(boolean consortialCatalog) {
		this.isConsortialCatalog = consortialCatalog;
	}

	public void setCourseReserveLibrariesToInclude(String courseReserveLibrariesToInclude) {
		if (courseReserveLibrariesToInclude != null && !courseReserveLibrariesToInclude.isEmpty()){
			courseReserveLibrariesToIncludePattern = Pattern.compile(courseReserveLibrariesToInclude, Pattern.CASE_INSENSITIVE);
		}
	}

	public boolean isCourseReserveLibraryIncluded(String courseLibrary){
		if (courseReserveLibrariesToIncludePattern == null){
			return false;
		}else{
			return courseReserveLibrariesToIncludePattern.matcher(courseLibrary).matches();
		}
	}

	public static class InclusionResult{
		public boolean isIncluded;
		public String localUrl;
		public boolean isOwned;

		InclusionResult(boolean isIncluded, boolean isOwned, String localUrl) {
			this.isIncluded = isIncluded;
			this.localUrl = localUrl;
			this.isOwned = isOwned;
		}
	}
}
