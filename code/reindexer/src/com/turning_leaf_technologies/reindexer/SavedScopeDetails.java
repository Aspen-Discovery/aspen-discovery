package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.strings.StringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;

class SavedScopeDetails {
	public long id;
	public long groupedStatusId;
	public long statusId;
	public boolean available;
	public boolean holdable;
	public boolean inLibraryUseOnly;
	public String localUrl;
	public boolean locallyOwned;
	public boolean libraryOwned;

	SavedScopeDetails(ResultSet getExistingScopesForItemRS) throws SQLException {
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

	SavedScopeDetails(long groupedStatusId, long statusId, boolean available, boolean holdable, boolean inLibraryUseOnly, String localUrl, boolean locallyOwned, boolean libraryOwned) {
		this.groupedStatusId = groupedStatusId;
		this.statusId = statusId;
		this.available = available;
		this.holdable = holdable;
		this.inLibraryUseOnly = inLibraryUseOnly;
		this.localUrl = localUrl;
		this.locallyOwned = locallyOwned;
		this.libraryOwned = libraryOwned;
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

	@Override
	public int hashCode() {
		return toString().hashCode();
	}

	private String myString = null;
	public String toString(){
		if (myString == null){
			myString = groupedStatusId + ":" + statusId + ":" + available + ":" + holdable + ":" + inLibraryUseOnly + ":";
			if (localUrl != null) {
				myString += localUrl;
			}
			myString += ":" + locallyOwned + libraryOwned;
		}
		return myString;
	}

	@Override
	public boolean equals(Object obj) {
		if (obj instanceof SavedScopeDetails){
			SavedScopeDetails tmpObj = (SavedScopeDetails)obj;
			return (this.groupedStatusId == tmpObj.groupedStatusId &&
					this.statusId == tmpObj.statusId &&
					this.available == tmpObj.available &&
					this.holdable == tmpObj.holdable &&
					this.inLibraryUseOnly == tmpObj.inLibraryUseOnly &&
					this.locallyOwned == tmpObj.locallyOwned &&
					this.libraryOwned == tmpObj.libraryOwned &&
					StringUtils.compareStrings(this.localUrl, tmpObj.localUrl)
					);
		}else{
			return false;
		}
	}
}
