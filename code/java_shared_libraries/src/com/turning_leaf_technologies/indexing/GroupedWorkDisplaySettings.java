package com.turning_leaf_technologies.indexing;

public class GroupedWorkDisplaySettings {
	private long id;
	private String name;

	private boolean includeOnlineMaterialsInAvailableToggle  = true;
	private boolean includeAllRecordsInShelvingFacets;
	private boolean includeAllRecordsInDateAddedFacets;
	private boolean baseAvailabilityToggleOnLocalHoldingsOnly = false;

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public boolean isIncludeAllRecordsInShelvingFacets() {
		return includeAllRecordsInShelvingFacets;
	}

	void setIncludeAllRecordsInShelvingFacets(boolean includeAllRecordsInShelvingFacets) {
		this.includeAllRecordsInShelvingFacets = includeAllRecordsInShelvingFacets;
	}

	public boolean isIncludeAllRecordsInDateAddedFacets() {
		return includeAllRecordsInDateAddedFacets;
	}

	void setIncludeAllRecordsInDateAddedFacets(boolean includeAllRecordsInDateAddedFacets) {
		this.includeAllRecordsInDateAddedFacets = includeAllRecordsInDateAddedFacets;
	}

	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	public boolean isBaseAvailabilityToggleOnLocalHoldingsOnly() {
		return baseAvailabilityToggleOnLocalHoldingsOnly;
	}

	void setBaseAvailabilityToggleOnLocalHoldingsOnly(boolean baseAvailabilityToggleOnLocalHoldingsOnly) {
		this.baseAvailabilityToggleOnLocalHoldingsOnly = baseAvailabilityToggleOnLocalHoldingsOnly;
	}

	public boolean isIncludeOnlineMaterialsInAvailableToggle() {
		return includeOnlineMaterialsInAvailableToggle;
	}

	void setIncludeOnlineMaterialsInAvailableToggle(boolean includeOnlineMaterialsInAvailableToggle) {
		this.includeOnlineMaterialsInAvailableToggle = includeOnlineMaterialsInAvailableToggle;
	}

}

