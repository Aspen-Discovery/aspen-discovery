package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;

import java.sql.Connection;
import java.sql.ResultSet;

public class PolarisRecordProcessor extends IlsRecordProcessor{

	PolarisRecordProcessor(String serverName, GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(serverName, indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		return itemInfo.getStatusCode().equalsIgnoreCase("in") || groupedStatus.equals("On Shelf") || (settings.getTreatLibraryUseOnlyGroupedStatusesAsAvailable() && groupedStatus.equals("Library Use Only"));
	}

	protected String getDetailedLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String location;
		String subLocationCode = MarcUtil.getItemSubfieldData(settings.getSubLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		String locationCode = MarcUtil.getItemSubfieldData(settings.getLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		String collectionCode = MarcUtil.getItemSubfieldData(settings.getCollectionSubfield(), itemField, indexer.getLogEntry(), logger);
		if (settings.isIncludeLocationNameInDetailedLocation()) {
			location = translateValue("location", locationCode, identifier, true);
		}else{
			location = "";
		}
		if (subLocationCode != null && !subLocationCode.isEmpty()){
			String translatedSubLocation = translateValue("sub_location", subLocationCode, identifier, true);
			if (translatedSubLocation != null && !translatedSubLocation.isEmpty()) {
				if (!location.isEmpty()) {
					location += " - ";
				}
				location += translatedSubLocation;
			}
		}
		if (collectionCode != null && !collectionCode.isEmpty() && !collectionCode.equals(subLocationCode)){
			String translatedCollection = translateValue("collection", collectionCode, identifier, true);
			if (translatedCollection != null && !translatedCollection.isEmpty()) {
				if (!location.isEmpty()) {
					location += " - ";
				}
				location += translatedCollection;
			}
		}
		String shelvingLocation = MarcUtil.getItemSubfieldData(settings.getShelvingLocationSubfield(), itemField, indexer.getLogEntry(), logger);
		if (shelvingLocation != null && !shelvingLocation.isEmpty()){
			if (!location.isEmpty()){
				location += " - ";
			}
			location += translateValue("shelf_location", shelvingLocation, identifier, true);
		}
		return location;
	}
}
