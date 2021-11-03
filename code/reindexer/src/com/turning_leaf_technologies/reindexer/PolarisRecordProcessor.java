package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;

import java.sql.Connection;
import java.sql.ResultSet;

public class PolarisRecordProcessor extends IlsRecordProcessor{

	PolarisRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		return itemInfo.getStatusCode().equalsIgnoreCase("in") || this.getDisplayGroupedStatus(itemInfo, itemInfo.getFullRecordIdentifier()).equals("On Shelf");
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String location;
		String subLocationCode = getItemSubfieldData(subLocationSubfield, itemField);
		String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
		String collectionCode = getItemSubfieldData(collectionSubfield, itemField);
		if (includeLocationNameInDetailedLocation) {
			location = translateValue("location", locationCode, identifier);
		}else{
			location = "";
		}
		if (subLocationCode != null && subLocationCode.length() > 0){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier);
			if (translatedSubLocation != null && translatedSubLocation.length() > 0) {
				if (location.length() > 0) {
					location += " - ";
				}
				location += translatedSubLocation;
			}
		}
		if (collectionCode != null && collectionCode.length() > 0 && !collectionCode.equals(subLocationCode)){
			String translatedCollection = translateValue("collection", collectionCode, identifier);
			if (translatedCollection != null && translatedCollection.length() > 0) {
				if (location.length() > 0) {
					location += " - ";
				}
				location += translatedCollection;
			}
		}
		String shelvingLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelvingLocation != null && shelvingLocation.length() > 0){
			if (location.length() > 0){
				location += " - ";
			}
			location += translateValue("shelf_location", shelvingLocation, identifier);
		}
		return location;
	}
}
