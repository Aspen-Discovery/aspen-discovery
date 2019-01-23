package org.vufind;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;

import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;
import java.util.HashMap;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 7/8/2015
 * Time: 4:43 PM
 */
class NashvilleSchoolsRecordProcessor extends IlsRecordProcessor {

	private HashMap<String, LSSItemInformation> allItemInformation = new HashMap<>();
	NashvilleSchoolsRecordProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(groupedWorkIndexer, vufindConn, indexingProfileRS, logger, fullReindex);

		//Load item information
		String itemInfoPath = "";
		SimpleDateFormat itemDateAddedFormatter = new SimpleDateFormat("yyyyMMddHH:mm:ss");
		try {
			String fullExportPath = indexingProfileRS.getString("marcPath");
			itemInfoPath = fullExportPath + "/schoolsitemupdatedaily.txt";
			File itemInfoFile = new File(itemInfoPath);
			CSVReader itemInfoReader = new CSVReader(new FileReader(itemInfoFile));
			//read the header
			itemInfoReader.readNext();
			String[] itemInfoRow = itemInfoReader.readNext();
			while (itemInfoRow != null){
				LSSItemInformation itemInformation = new LSSItemInformation();
				itemInformation.setResourceId(itemInfoRow[0]);
				itemInformation.setItemBarcode(itemInfoRow[1]);
				itemInformation.setHoldingsCode(itemInfoRow[2]);
				itemInformation.setItemStatus(itemInfoRow[3]);
				itemInformation.setControlNumber(itemInfoRow[4]);
				itemInformation.setTotalCirculations(Integer.parseInt(itemInfoRow[5]));
				itemInformation.setCheckoutsThisYear(Integer.parseInt(itemInfoRow[8]));
				itemInformation.setDateAddedToSystem(itemDateAddedFormatter.parse(itemInfoRow[11]));
				itemInfoRow = itemInfoReader.readNext();

				allItemInformation.put(itemInformation.getItemBarcode(), itemInformation);
			}
		}catch (Exception e){
			logger.error("Error loading item information from " + itemInfoPath, e);
		}
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		//For LSS, the barcode is the item identifier
		LSSItemInformation itemInformation = allItemInformation.get(itemInfo.getItemIdentifier());
		if (itemInformation !=null){
			if (itemInformation.getItemStatus().equals("I")){
				return true;
			}
		}
		return false;
	}

	@Override
	protected String getItemStatus(DataField itemField, String recordIdentifier){
		//Get the barcode
		String itemBarcode = getItemSubfieldData(barcodeSubfield, itemField);
		LSSItemInformation itemInformation = allItemInformation.get(itemBarcode);
		if (itemInformation != null){
			return itemInformation.getItemStatus();
		} else{
			return "Unknown";
		}
	}

	@Override
	protected void loadDateAdded(String recordIdentifier, DataField itemField, ItemInfo itemInfo) {
		LSSItemInformation itemInformation = allItemInformation.get(itemInfo.getItemIdentifier());
		if (itemInformation != null){
			itemInfo.setDateAdded(itemInformation.getDateAddedToSystem());
		}
	}

	@Override
	protected double getItemPopularity(DataField itemField) {
		String itemBarcode = getItemSubfieldData(barcodeSubfield, itemField);
		LSSItemInformation itemInformation = allItemInformation.get(itemBarcode);
		if (itemInformation != null){
			return itemInformation.getCheckoutsThisYear() + .2 * (itemInformation.getTotalCirculations() - itemInformation.getCheckoutsThisYear());
		}else{
			return 0;
		}
	}

	protected boolean isItemValid(String itemStatus, String itemLocation) {
		return itemLocation != null;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		//Suppress if the barcode is null or blank
		String barcode = getItemSubfieldData(barcodeSubfield, curItem);
		return barcode == null || barcode.length() == 0 || super.isItemSuppressed(curItem);
	}
}
