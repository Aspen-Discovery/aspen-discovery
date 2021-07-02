package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.VolumeInfo;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.TreeMap;

public class PolarisRecordProcessor extends IlsRecordProcessor{
	PreparedStatement getExistingVolumesStmt;
	PreparedStatement addVolumeStmt;
	PreparedStatement deleteAllVolumesStmt;
	PreparedStatement deleteVolumeStmt;
	PreparedStatement updateVolumeStmt;
	PolarisRecordProcessor(GroupedWorkIndexer indexer, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, dbConn, indexingProfileRS, logger, fullReindex);
		try {
			getExistingVolumesStmt = dbConn.prepareStatement("SELECT id, volumeId from ils_volume_info where recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addVolumeStmt = dbConn.prepareStatement("INSERT INTO ils_volume_info (recordId, volumeId, displayLabel, relatedItems, displayOrder) VALUES (?,?,?,?, ?) ON DUPLICATE KEY update recordId = VALUES(recordId), displayLabel = VALUES(displayLabel), relatedItems = VALUES(relatedItems), displayOrder = VALUES(displayOrder)");
			updateVolumeStmt = dbConn.prepareStatement("UPDATE ils_volume_info SET displayLabel = ?, relatedItems = ?, displayOrder = ? WHERE id = ?");
			deleteAllVolumesStmt = dbConn.prepareStatement("DELETE from ils_volume_info where recordId = ?");
			deleteVolumeStmt = dbConn.prepareStatement("DELETE from ils_volume_info where id = ?");
		} catch (SQLException e) {
			logger.error("Could not create statements to update volumes", e);
		}

	}

	//Override to Check for volume information
	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		super.loadUnsuppressedPrintItems(groupedWork, recordInfo, identifier, record);
		TreeMap<String, VolumeInfo> volumesForRecord = new TreeMap<>();
		for (ItemInfo curItem : recordInfo.getRelatedItems()){
			String volume = curItem.getVolumeField();
			if (volume != null && volume.trim().length() > 0){
				volume = volume.trim();
				VolumeInfo volumeInfo = volumesForRecord.get(volume);
				if (volumeInfo == null){
					volumeInfo = new VolumeInfo();
					volumeInfo.bibNumber = recordInfo.getFullIdentifier();
					volumeInfo.volume = volume;
					volumeInfo.volumeIdentifier = volume;
					volumesForRecord.put(volume, volumeInfo);
				}
				volumeInfo.relatedItems.add(curItem.getItemIdentifier());
			}
		}
		//Save the volumes to the database
		indexer.disableAutoCommit();
		try {

			if (volumesForRecord.size() == 0){
				deleteAllVolumesStmt.setString(1, recordInfo.getFullIdentifier());
				deleteAllVolumesStmt.executeUpdate();
			}else {
				HashMap<String, Long> existingVolumes = new HashMap<>();
				getExistingVolumesStmt.setString(1, recordInfo.getFullIdentifier());
				ResultSet existingVolumesRS = getExistingVolumesStmt.executeQuery();
				while (existingVolumesRS.next()) {
					existingVolumes.put(existingVolumesRS.getString("volumeId"), existingVolumesRS.getLong("id"));
				}
				int numVolumes = 0;
				for (String volume : volumesForRecord.keySet()) {
					VolumeInfo volumeInfo = volumesForRecord.get(volume);
					try {
						if (existingVolumes.containsKey(volume)) {
							updateVolumeStmt.setString(1, volumeInfo.volumeIdentifier);
							updateVolumeStmt.setString(2, volumeInfo.getRelatedItemsAsString());
							updateVolumeStmt.setLong(3, ++numVolumes);
							updateVolumeStmt.setLong(4, existingVolumes.get(volume));
							existingVolumes.remove(volume);
						} else {
							addVolumeStmt.setString(1, recordInfo.getFullIdentifier());
							addVolumeStmt.setString(2, volumeInfo.volume);
							addVolumeStmt.setString(3, volumeInfo.volumeIdentifier);
							addVolumeStmt.setString(4, volumeInfo.getRelatedItemsAsString());
							addVolumeStmt.setLong(5, ++numVolumes);
							addVolumeStmt.executeUpdate();
						}
					}catch (Exception e){
						logger.error("Error updating volume for record " + recordInfo.getFullIdentifier() + " (" + volume.length() + ") " + volume , e);
					}
				}
				for (String volume : existingVolumes.keySet()) {
					deleteVolumeStmt.setLong(1, existingVolumes.get(volume));
					deleteVolumeStmt.executeUpdate();
				}
			}
		}catch (Exception e){
			logger.error("Error updating volumes for record ", e);
		}
		indexer.enableAutoCommit();
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
