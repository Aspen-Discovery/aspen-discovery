package com.turning_leaf_technologies.reindexer;

import com.opencsv.CSVReader;
import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

public class EvergreenRecordProcessor extends IlsRecordProcessor {
	HashMap<String, String> barcodeCreatedByDates = new HashMap<>();

	private PreparedStatement getVolumesForBibStmt;

	EvergreenRecordProcessor(GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, curType, dbConn, indexingProfileRS, logger, fullReindex);

		loadSupplementalFiles();
	}

	private void loadSupplementalFiles() {
		File supplementalDirectory = new File(marcPath + "/../supplemental");
		if (supplementalDirectory.exists()){
			try {
				CSVReader barcodeActiveDatesReader = new CSVReader(new FileReader(marcPath + "/../supplemental/barcode_active_dates.csv"));
				String[] curValues = barcodeActiveDatesReader.readNext();
				while (curValues != null){
					String barcode = curValues[0];
					if (curValues.length >= 2){
						String date = curValues[1].trim();
						if (date.length() > 0){
							barcodeCreatedByDates.put(barcode, date);
						}
					}
					curValues = barcodeActiveDatesReader.readNext();
				}
				barcodeActiveDatesReader.close();
			}catch (IOException e){
				indexer.getLogEntry().incErrors("Error reading barcode active dates", e);
			}
		}else{
			indexer.getLogEntry().addNote("Supplemental directory did not exist");
		}
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		String groupedStatus = getDisplayGroupedStatus(itemInfo, itemInfo.getFullRecordIdentifier());
		return itemInfo.getStatusCode().equals("Available") || groupedStatus.equals("On Shelf") || groupedStatus.equals("Library Use Only");
	}

	private SimpleDateFormat createdByFormatter = new SimpleDateFormat("yyyy-MM-dd");
	protected void loadDateAdded(String recordIdentifier, DataField itemField, ItemInfo itemInfo) {
		Subfield itemBarcodeSubfield = itemField.getSubfield(barcodeSubfield);
		if (itemBarcodeSubfield != null){
			String barcode = itemBarcodeSubfield.getData();
			if (barcodeCreatedByDates.containsKey(barcode)){
				String createdBy = barcodeCreatedByDates.get(barcode);
				if (createdBy.contains(" ")){
					createdBy = createdBy.substring(0, createdBy.indexOf(' '));
				}
				try {
					Date createdByDate = createdByFormatter.parse(createdBy);
					itemInfo.setDateAdded(createdByDate);
				}catch (ParseException e2){
					indexer.getLogEntry().addNote("Error processing date added for record identifier " + recordIdentifier + " profile " + profileType + " " + e2);
				}
			}
		}
	}
}
