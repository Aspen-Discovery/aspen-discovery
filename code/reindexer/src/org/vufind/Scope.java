package org.vufind;

import com.sun.istack.internal.NotNull;
import org.marc4j.marc.Record;

import java.util.HashSet;
import java.util.TreeSet;
import java.util.regex.Pattern;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 6/2/2014
 * Time: 1:08 PM
 */
public class Scope implements Comparable<Scope>{
	private String scopeName;
	private String facetLabel;

	private HashSet<Long> relatedNumericPTypes = new HashSet<>();
	private boolean includeOverDriveCollection;
	private Long libraryId;

	//Determine if this is a library scope or location scope and store related information
	private boolean isLibraryScope;
	//If this is a library scope, we want to store pointers to the individual location scopes
	private HashSet<Scope> locationScopes = new HashSet<>();

	private boolean isLocationScope;
	private Scope libraryScope;

	private boolean restrictOwningLibraryAndLocationFacets;
	//Ownership rules indicate direct ownership of a record
	private HashSet<OwnershipRule> ownershipRules = new HashSet<>();
	//Inclusion rules indicate records owned by someone else that should be shown within the scope
	private HashSet<InclusionRule> inclusionRules = new HashSet<>();
	private String ilsCode;
	private boolean includeOverDriveAdultCollection;
	private boolean includeOverDriveTeenCollection;
	private boolean includeOverDriveKidsCollection;
	private int publicListsToInclude;
	private String additionalLocationsToShowAvailabilityFor;
	private Pattern additionalLocationsToShowAvailabilityForPattern;
	private boolean includeAllLibraryBranchesInFacets; //Only applies to location scopes
	private boolean includeAllRecordsInShelvingFacets;
	private boolean includeAllRecordsInDateAddedFacets;
	private boolean baseAvailabilityToggleOnLocalHoldingsOnly = false;
	private boolean includeOnlineMaterialsInAvailableToggle  = true;

	String getScopeName() {
		return scopeName;
	}

	void setScopeName(String scopeName) {
		this.scopeName = scopeName;
		this.scopeName = this.scopeName.replaceAll("[^a-zA-Z0-9_]", "");
	}

	void setRelatedPTypes(String[] relatedPTypes) {
		for (String relatedPType : relatedPTypes) {
			relatedPType = relatedPType.trim();
			if (relatedPType.length() > 0) {
				try{
					Long numericPType = Long.parseLong(relatedPType);
					relatedNumericPTypes.add(numericPType);
				} catch (Exception e){
					//No need to do anything here.
				}

			}
		}
	}

	void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel;
	}

	/**
	 * Determine if the item is part of the current scope based on location code and pType
	 *
	 *
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether or not the item is included within the scope
	 */
	InclusionResult isItemPartOfScope(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode, String iType, TreeSet<String> audiences, String format, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord, String econtentUrl){
		if (locationCode == null){
			//No location code, skip this item
			return new InclusionResult(false, econtentUrl);
		}

		for(OwnershipRule curRule: ownershipRules){
			if (curRule.isItemOwned(recordType, locationCode, subLocationCode)){
				return new InclusionResult(true, econtentUrl);
			}
		}

		for(InclusionRule curRule: inclusionRules){
			if (curRule.isItemIncluded(recordType, locationCode, subLocationCode, iType, audiences, format, isHoldable, isOnOrder, isEContent, marcRecord)){
				if (econtentUrl != null) {
					econtentUrl = curRule.getLocalUrl(econtentUrl);
				}
				return new InclusionResult(true, econtentUrl);
			}
		}

		//If we got this far, it isn't included
		return new InclusionResult(false, econtentUrl);
	}

	/**
	 * Determine if the item is part of the current scope based on location code and pType
	 *
	 *
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether or not the item is included within the scope
	 */
	boolean isItemOwnedByScope(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		for(OwnershipRule curRule: ownershipRules){
			if (curRule.isItemOwned(recordType, locationCode, subLocationCode)){
				return true;
			}
		}

		//If we got this far, it isn't owned
		return false;
	}

	String getFacetLabel() {
		return facetLabel;
	}


	boolean isIncludeOverDriveCollection() {
		return includeOverDriveCollection;
	}

	void setIncludeOverDriveCollection(boolean includeOverDriveCollection) {
		this.includeOverDriveCollection = includeOverDriveCollection;
	}

	void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}

	Long getLibraryId() {
		return libraryId;
	}


	@Override
	public int compareTo(@NotNull Scope o) {
		return scopeName.compareTo(o.scopeName);
	}

	void setIsLibraryScope(boolean isLibraryScope) {
		this.isLibraryScope = isLibraryScope;
	}

	boolean isLibraryScope() {
		return isLibraryScope;
	}

	void setIsLocationScope(boolean isLocationScope) {
		this.isLocationScope = isLocationScope;
	}

	boolean isLocationScope() {
		return isLocationScope;
	}

	void addOwnershipRule(OwnershipRule ownershipRule) {
		ownershipRules.add(ownershipRule);
	}

	void addInclusionRule(InclusionRule inclusionRule) {
		inclusionRules.add(inclusionRule);
	}

	HashSet<Long> getRelatedNumericPTypes() {
		return relatedNumericPTypes;
	}

	void addLocationScope(Scope locationScope) {
		this.locationScopes.add(locationScope);
	}

	void setLibraryScope(Scope libraryScope) {
		this.libraryScope = libraryScope;
	}

	Scope getLibraryScope() {
		return libraryScope;
	}

	boolean isRestrictOwningLibraryAndLocationFacets() {
		return restrictOwningLibraryAndLocationFacets;
	}

	void setRestrictOwningLibraryAndLocationFacets(boolean restrictOwningLibraryAndLocationFacets) {
		this.restrictOwningLibraryAndLocationFacets = restrictOwningLibraryAndLocationFacets;
	}

	HashSet<Scope> getLocationScopes() {
		return locationScopes;
	}

	String getIlsCode() {
		return ilsCode;
	}

	void setIlsCode(String ilsCode) {
		this.ilsCode = ilsCode;
	}

	void setIncludeOverDriveAdultCollection(boolean includeOverDriveAdultCollection) {
		this.includeOverDriveAdultCollection = includeOverDriveAdultCollection;
	}

	boolean isIncludeOverDriveAdultCollection() {
		return includeOverDriveAdultCollection;
	}

	void setIncludeOverDriveTeenCollection(boolean includeOverDriveTeenCollection) {
		this.includeOverDriveTeenCollection = includeOverDriveTeenCollection;
	}

	boolean isIncludeOverDriveTeenCollection() {
		return includeOverDriveTeenCollection;
	}

	void setIncludeOverDriveKidsCollection(boolean includeOverDriveKidsCollection) {
		this.includeOverDriveKidsCollection = includeOverDriveKidsCollection;
	}

	boolean isIncludeOverDriveKidsCollection() {
		return includeOverDriveKidsCollection;
	}

	void setPublicListsToInclude(int publicListsToInclude) {
		this.publicListsToInclude = publicListsToInclude;
	}

	int getPublicListsToInclude() {
		return publicListsToInclude;
	}

	void setAdditionalLocationsToShowAvailabilityFor(String additionalLocationsToShowAvailabilityFor) {
		this.additionalLocationsToShowAvailabilityFor = additionalLocationsToShowAvailabilityFor;
		if (additionalLocationsToShowAvailabilityFor.length() > 0){
			additionalLocationsToShowAvailabilityForPattern = Pattern.compile(additionalLocationsToShowAvailabilityFor);
		}
	}

	String getAdditionalLocationsToShowAvailabilityFor() {
		return additionalLocationsToShowAvailabilityFor;
	}

	boolean isIncludeAllLibraryBranchesInFacets() {
		return includeAllLibraryBranchesInFacets;
	}

	void setIncludeAllLibraryBranchesInFacets(boolean includeAllLibraryBranchesInFacets) {
		this.includeAllLibraryBranchesInFacets = includeAllLibraryBranchesInFacets;
	}

	Pattern getAdditionalLocationsToShowAvailabilityForPattern() {
		return additionalLocationsToShowAvailabilityForPattern;
	}

	boolean isIncludeAllRecordsInShelvingFacets() {
		return includeAllRecordsInShelvingFacets;
	}

	void setIncludeAllRecordsInShelvingFacets(boolean includeAllRecordsInShelvingFacets) {
		this.includeAllRecordsInShelvingFacets = includeAllRecordsInShelvingFacets;
	}

	boolean isIncludeAllRecordsInDateAddedFacets() {
		return includeAllRecordsInDateAddedFacets;
	}

	void setIncludeAllRecordsInDateAddedFacets(boolean includeAllRecordsInDateAddedFacets) {
		this.includeAllRecordsInDateAddedFacets = includeAllRecordsInDateAddedFacets;
	}

	boolean isBaseAvailabilityToggleOnLocalHoldingsOnly() {
		return baseAvailabilityToggleOnLocalHoldingsOnly;
	}

	void setBaseAvailabilityToggleOnLocalHoldingsOnly(boolean baseAvailabilityToggleOnLocalHoldingsOnly) {
		this.baseAvailabilityToggleOnLocalHoldingsOnly = baseAvailabilityToggleOnLocalHoldingsOnly;
	}

	boolean isIncludeOnlineMaterialsInAvailableToggle() {
		return includeOnlineMaterialsInAvailableToggle;
	}

	void setIncludeOnlineMaterialsInAvailableToggle(boolean includeOnlineMaterialsInAvailableToggle) {
		this.includeOnlineMaterialsInAvailableToggle = includeOnlineMaterialsInAvailableToggle;
	}

	private Boolean isUnscoped = null;
	boolean isUnscoped() {
		if (isUnscoped == null){
			isUnscoped = relatedNumericPTypes.contains(-1L);
		}
		return isUnscoped;
	}

	class InclusionResult{
		boolean isIncluded;
		String localUrl;

		InclusionResult(boolean isIncluded, String localUrl) {
			this.isIncluded = isIncluded;
			this.localUrl = localUrl;
		}
	}
}
