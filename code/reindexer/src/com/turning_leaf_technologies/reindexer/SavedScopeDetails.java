package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.strings.StringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;

class SavedScopeDetails {
	public long id;
	public String localUrl;
	public boolean locallyOwned;
	public boolean libraryOwned;

	SavedScopeDetails(ResultSet getExistingScopesForItemRS) throws SQLException {
		id = getExistingScopesForItemRS.getLong("id");
		localUrl = getExistingScopesForItemRS.getString("localUrl");
		locallyOwned = getExistingScopesForItemRS.getBoolean("locallyOwned");
		libraryOwned = getExistingScopesForItemRS.getBoolean("libraryOwned");
	}

	SavedScopeDetails(String localUrl, boolean locallyOwned, boolean libraryOwned) {
		this.localUrl = localUrl;
		this.locallyOwned = locallyOwned;
		this.libraryOwned = libraryOwned;
	}

	boolean hasChanged(long groupedStatusId, long statusId, ScopingInfo scopingInfo) {
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
			myString = locallyOwned + ":" + libraryOwned;
			if (localUrl != null) {
				myString += localUrl;
			}

		}
		return myString;
	}

	@Override
	public boolean equals(Object obj) {
		if (obj instanceof SavedScopeDetails){
			SavedScopeDetails tmpObj = (SavedScopeDetails)obj;
			return (this.locallyOwned == tmpObj.locallyOwned &&
					this.libraryOwned == tmpObj.libraryOwned &&
					StringUtils.compareStrings(this.localUrl, tmpObj.localUrl)
					);
		}else{
			return false;
		}
	}
}
