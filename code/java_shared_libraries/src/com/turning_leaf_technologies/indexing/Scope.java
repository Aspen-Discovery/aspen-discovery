package com.turning_leaf_technologies.indexing;

import com.sun.istack.internal.NotNull;
import org.marc4j.marc.Record;

import java.util.HashMap;
import java.util.HashSet;
import java.util.TreeSet;
import java.util.regex.Pattern;

public class Scope implements Comparable<Scope>{
	private long id;

	private String scopeName;
	private String facetLabel;

	private Long libraryId;

	//Determine if this is a library scope or location scope and store related information
	private boolean isLibraryScope;
	//If this is a library scope, we want to store pointers to the individual location scopes
	private final HashSet<Scope> locationScopes = new HashSet<>();

	private boolean isLocationScope;
	private Scope libraryScope;

	//Called restrictOwningBranchesAndSystems in PHP admin interface
	private boolean restrictOwningLibraryAndLocationFacets;
	private boolean isConsortialCatalog;
	//Ownership rules indicate direct ownership of a record
	private final HashSet<OwnershipRule> ownershipRules = new HashSet<>();
	//Inclusion rules indicate records owned by someone else that should be shown within the scope
	private final HashSet<InclusionRule> inclusionRules = new HashSet<>();
	private String ilsCode;

	private int publicListsToInclude;
	private String additionalLocationsToShowAvailabilityFor;
	private Pattern additionalLocationsToShowAvailabilityForPattern;
	private boolean includeAllLibraryBranchesInFacets; //Only applies to location scopes

	private GroupedWorkDisplaySettings groupedWorkDisplaySettings;
	private OverDriveScope overDriveScope;
	private HooplaScope hooplaScope;
	private final HashMap<Long, CloudLibraryScope> cloudLibraryScopes = new HashMap<>();
	private Axis360Scope axis360Scope;

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

	private final HashMap<String, Boolean> ownershipResults = new HashMap<>();
	/**
	 * Determine if the item is part of the current scope based on location code and other information
	 *
	 *
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether or not the item is included within the scope
	 */
	public InclusionResult isItemPartOfScope(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode, String iType, TreeSet<String> audiences, String audiencesAsString, String format, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord, String econtentUrl){
		String fullKey = recordType + locationCode + subLocationCode;
		Boolean isOwned = ownershipResults.get(fullKey);
		if (isOwned == null) {
			for (OwnershipRule curRule : ownershipRules) {
				if (curRule.isItemOwned(fullKey, recordType, locationCode, subLocationCode)) {
					ownershipResults.put(fullKey, true);
					return new InclusionResult(true, true, econtentUrl);
				}
			}
			ownershipResults.put(fullKey, false);
		}else if (isOwned){
			return new InclusionResult(true, true, econtentUrl);
		}

		for(InclusionRule curRule: inclusionRules){
			if (curRule.isItemIncluded(recordType, locationCode, subLocationCode, iType, audiences, audiencesAsString, format, isHoldable, isOnOrder, isEContent, marcRecord)){
				if (econtentUrl != null) {
					econtentUrl = curRule.getLocalUrl(econtentUrl);
				}
				return new InclusionResult(true, false, econtentUrl);
			}
		}

		//If we got this far, it isn't included
		return new InclusionResult(false, false, econtentUrl);
	}

	/**
	 * Determine if the item is part of the current scope based on location code and other information
	 *
	 *
	 *
	 * @param fullKey
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether or not the item is included within the scope
	 */
	public boolean isItemOwnedByScope(String fullKey, @NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		for(OwnershipRule curRule: ownershipRules){
			if (curRule.isItemOwned(fullKey, recordType, locationCode, subLocationCode)){
				return true;
			}
		}

		//If we got this far, it isn't owned
		return false;
	}

	public String getFacetLabel() {
		return facetLabel;
	}


	public boolean isIncludeOverDriveCollection() {
		return overDriveScope != null;
	}

	void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}

	public Long getLibraryId() {
		return libraryId;
	}


	@Override
	public int compareTo(@NotNull Scope o) {
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

	void addOwnershipRule(OwnershipRule ownershipRule) {
		ownershipRules.add(ownershipRule);
	}

	void addInclusionRule(InclusionRule inclusionRule) {
		inclusionRules.add(inclusionRule);
	}

	void addLocationScope(Scope locationScope) {
		this.locationScopes.add(locationScope);
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

	public HashSet<Scope> getLocationScopes() {
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
		if (additionalLocationsToShowAvailabilityFor.length() > 0){
			additionalLocationsToShowAvailabilityForPattern = Pattern.compile(additionalLocationsToShowAvailabilityFor);
		}
	}

	public String getAdditionalLocationsToShowAvailabilityFor() {
		return additionalLocationsToShowAvailabilityFor;
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

	public OverDriveScope getOverDriveScope() {
		return overDriveScope;
	}

	void setOverDriveScope(OverDriveScope overDriveScope) {
		this.overDriveScope = overDriveScope;
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

	public boolean isConsortialCatalog() {
		return isConsortialCatalog;
	}

	public void setConsortialCatalog(boolean consortialCatalog) {
		this.isConsortialCatalog = consortialCatalog;
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
