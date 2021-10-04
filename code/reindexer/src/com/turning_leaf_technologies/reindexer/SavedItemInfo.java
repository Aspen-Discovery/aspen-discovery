package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.strings.StringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

public class SavedItemInfo {
	public long id;
	public long recordId;
	public long variationId;
	public String itemId;
	public long shelfLocationId;
	public long callNumberId;
	public long sortableCallNumberId;
	public long numCopies;
	public boolean isOrderItem;
	public long statusId;
	public Long dateAdded;
	public long locationCodeId;
	public long subLocationCodeId;
	public Long lastCheckInDate;
	public long groupedStatusId;
	public boolean available;
	public boolean holdable;
	public boolean inLibraryUseOnly;
	public String locationOwnedScopes;
	public String libraryOwnedScopes;
	public String recordIncludedScopes;


	public SavedItemInfo(ResultSet getExistingItemsForRecordRS) throws SQLException {
		this.id = getExistingItemsForRecordRS.getLong("id");
		this.recordId = getExistingItemsForRecordRS.getLong("groupedWorkRecordId");
		this.variationId = getExistingItemsForRecordRS.getLong("groupedWorkVariationId");
		this.itemId = getExistingItemsForRecordRS.getString("itemId");
		this.shelfLocationId = getExistingItemsForRecordRS.getLong("shelfLocationId");
		this.callNumberId = getExistingItemsForRecordRS.getLong("callNumberId");
		this.sortableCallNumberId = getExistingItemsForRecordRS.getLong("sortableCallNumberId");
		this.numCopies = getExistingItemsForRecordRS.getLong("isOrderItem");
		this.isOrderItem = getExistingItemsForRecordRS.getBoolean("isOrderItem");
		this.statusId = getExistingItemsForRecordRS.getLong("statusId");
		this.dateAdded = getExistingItemsForRecordRS.getLong("dateAdded");
		this.locationCodeId = getExistingItemsForRecordRS.getLong("locationCodeId");
		this.subLocationCodeId = getExistingItemsForRecordRS.getLong("subLocationCodeId");
		this.lastCheckInDate = getExistingItemsForRecordRS.getLong("lastCheckInDate");
		this.groupedStatusId = getExistingItemsForRecordRS.getLong("groupedStatusId");
		this.available = getExistingItemsForRecordRS.getBoolean("available");
		this.holdable = getExistingItemsForRecordRS.getBoolean("holdable");
		this.inLibraryUseOnly = getExistingItemsForRecordRS.getBoolean("inLibraryUseOnly");
		this.locationOwnedScopes = getExistingItemsForRecordRS.getString("locationOwnedScopes");
		this.libraryOwnedScopes = getExistingItemsForRecordRS.getString("libraryOwnedScopes");
		this.recordIncludedScopes = getExistingItemsForRecordRS.getString("recordIncludedScopes");
	}

	public SavedItemInfo(long id, long recordId, long variationId, String itemId, long shelfLocationId, long callNumberId, long sortableCallNumberId,
	                     int numCopies, boolean isOrderItem, long statusId, Date dateAdded, long locationId, long subLocationId, Date lastCheckInDate,
	                     long groupedStatusId, boolean available, boolean holdable, boolean inLibraryUseOnly, String locationOwnedScopes,
	                     String libraryOwnedScopes, String recordIncludedScopes){
		this.id = id;
		this.recordId = recordId;
		this.variationId = variationId;
		this.itemId = itemId;
		this.shelfLocationId = shelfLocationId;
		this.callNumberId = callNumberId;
		this.sortableCallNumberId = sortableCallNumberId;
		this.numCopies = numCopies;
		this.isOrderItem = isOrderItem;
		this.statusId = statusId;
		if (dateAdded == null){
			this.dateAdded = null;
		}else {
			this.dateAdded = dateAdded.getTime() / 1000;
		}
		this.locationCodeId = locationId;
		this.subLocationCodeId = subLocationId;
		if (lastCheckInDate == null){
			this.lastCheckInDate = null;
		}else {
			this.lastCheckInDate = lastCheckInDate.getTime() / 1000;
		}
		this.groupedStatusId = groupedStatusId;
		this.available = available;
		this.holdable = holdable;
		this.inLibraryUseOnly = inLibraryUseOnly;
		this.locationOwnedScopes = locationOwnedScopes;
		this.libraryOwnedScopes = libraryOwnedScopes;
		this.recordIncludedScopes = recordIncludedScopes;
	}

	boolean hasChanged(long recordId, long variationId, String itemId, long shelfLocationId, long callNumberId, long sortableCallNumberId,
	                   int numCopies, boolean isOrderItem, long statusId, Date dateAdded, long locationId, long subLocationId, Date lastCheckInDate,
	                   long groupedStatusId, boolean available, boolean holdable, boolean inLibraryUseOnly, String locationOwnedScopes,
	                   String libraryOwnedScopes, String recordIncludedScopes) {
		if (this.recordId != recordId) {
			return true;
		}
		if (this.variationId != variationId) {
			return true;
		}
		if (!StringUtils.compareStrings(itemId, this.itemId)){
			return true;
		}
		if (this.shelfLocationId != shelfLocationId){
			return true;
		}
		if (this.callNumberId != callNumberId){
			return true;
		}
		if (this.sortableCallNumberId != sortableCallNumberId){
			return true;
		}
		if (this.numCopies != numCopies){
			return true;
		}
		if (this.isOrderItem != isOrderItem){
			return true;
		}
		if (this.statusId != statusId){
			return true;
		}
		if (dateAdded != null || this.dateAdded != null) {
			if (dateAdded == null) {
				return true;
			} else if (this.dateAdded == null) {
				return true;
			} else if (dateAdded.getTime() / 1000 != this.dateAdded) {
				return true;
			}
		}// else both are null
		if (this.locationCodeId != locationId){
			return true;
		}
		if (this.subLocationCodeId != subLocationId){
			return true;
		}
		if (lastCheckInDate != null || this.lastCheckInDate != null) {
			if (lastCheckInDate == null) {
				return true;
			} else if (this.lastCheckInDate == null) {
				return true;
			} else if (lastCheckInDate.getTime() / 1000 != this.lastCheckInDate) {
				return true;
			}
		}// else both are null
		if (this.groupedStatusId != groupedStatusId){
			return true;
		}
		if (this.available != available){
			return true;
		}
		if (this.holdable != holdable){
			return true;
		}
		if (this.inLibraryUseOnly != inLibraryUseOnly){
			return true;
		}
		if (!StringUtils.compareStrings(locationOwnedScopes, this.locationOwnedScopes)){
			return true;
		}
		if (!StringUtils.compareStrings(libraryOwnedScopes, this.libraryOwnedScopes)){
			return true;
		}
		return !StringUtils.compareStrings(recordIncludedScopes, this.recordIncludedScopes);
	}
}
