package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.strings.StringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;

class SavedScopingInfo {
	public long id;
	public long groupedStatusId;
	public long statusId;
	public boolean available;
	public boolean holdable;
	public boolean inLibraryUseOnly;
	public String localUrl;
	public boolean locallyOwned;
	public boolean libraryOwned;

	SavedScopingInfo(ResultSet getExistingScopesForItemRS) throws SQLException {
		id = getExistingScopesForItemRS.getLong("id");
		groupedStatusId = getExistingScopesForItemRS.getLong("groupedStatusId");
		statusId = getExistingScopesForItemRS.getLong("statusId");
		available = getExistingScopesForItemRS.getBoolean("available");
		holdable = getExistingScopesForItemRS.getBoolean("holdable");
		inLibraryUseOnly = getExistingScopesForItemRS.getBoolean("inLibraryUseOnly");
		localUrl = getExistingScopesForItemRS.getString("localUrl");
		locallyOwned = getExistingScopesForItemRS.getBoolean("locallyOwned");
		libraryOwned = getExistingScopesForItemRS.getBoolean("libraryOwned");
	}

	boolean hasChanged(long groupedStatusId, long statusId, ScopingInfo scopingInfo) {
		if (groupedStatusId != this.groupedStatusId){
			return true;
		}
		if (statusId != this.statusId){
			return true;
		}
		if (scopingInfo.isAvailable() != available){
			return true;
		}
		if (scopingInfo.isHoldable() != holdable){
			return true;
		}
		if (scopingInfo.isInLibraryUseOnly() != inLibraryUseOnly){
			return true;
		}
		if (!StringUtils.compareStrings(scopingInfo.getLocalUrl(), localUrl)) {
			return true;
		}
		if (scopingInfo.isLocallyOwned() != locallyOwned){
			return true;
		}
		//noinspection RedundantIfStatement
		if (scopingInfo.isLibraryOwned() != libraryOwned) {
			return true;
		}
		return false;
	}
}
