package com.turning_leaf_technologies.indexing;

import java.util.HashSet;

public class OmekaScope {
	private long id;
	private String name;
	private long settingId;
	private boolean includeAllItemSets;
	private final HashSet<String> itemSetIds = new HashSet<>();

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

	public long getSettingId() {
		return settingId;
	}

	public void setSettingId(long settingId) {
		this.settingId = settingId;
	}

	void setItemSetIds(String itemSetIds) {
		this.itemSetIds.clear();
		if (itemSetIds == null || itemSetIds.isBlank()) {
			return;
		}
		for (String itemSetId : itemSetIds.split(",")) {
			String trimmedId = itemSetId.trim();
			if (!trimmedId.isEmpty()) {
				this.itemSetIds.add(trimmedId);
			}
		}
	}

	void setIncludeAllItemSets(boolean includeAllItemSets) {
		this.includeAllItemSets = includeAllItemSets;
	}

	public boolean includesItemWithItemSets(HashSet<String> titleItemSetIds) {
		if (includeAllItemSets) {
			return true;
		}
		for (String titleItemSetId : titleItemSetIds) {
			if (itemSetIds.contains(titleItemSetId)) {
				return true;
			}
		}
		return false;
	}
}
