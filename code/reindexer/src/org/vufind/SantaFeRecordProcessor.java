package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;

/**
 * Custom Record Processing for Arlington
 *
 * Pika
 * User: Mark Noble
 * Date: 10/15/2015
 * Time: 9:48 PM
 */
class SantaFeRecordProcessor extends IIIRecordProcessor {

	SantaFeRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, indexingProfileRS, logger, fullReindex);
		loadOrderInformationFromExport();
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-o";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		return available;
	}

	@Override
	protected boolean loanRulesAreBasedOnCheckoutLocation() {
		return false;
	}

	protected boolean isBibSuppressed(Record record) {
		DataField field907 = record.getDataField("907");
		if (field907 != null){
			Subfield suppressionSubfield = field907.getSubfield('c');
			if (suppressionSubfield != null){
				String bCode3 = suppressionSubfield.getData().toLowerCase().trim();
				if (bCode3.matches("^[dns]$")){
					logger.debug("Bib record is suppressed due to bcode3 " + bCode3);
					return true;
				}
			}
		}
		return false;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield != null) {
			String icode2 = icode2Subfield.getData().toLowerCase().trim();
			String status = curItem.getSubfield(statusSubfieldIndicator).getData().trim();

			//Suppress based on combination of status and icode2
			if ((icode2.equals("2") || icode2.equals("3")) && status.equals("f")){
				logger.debug("Item record is suppressed due to icode2 / status");
				return true;
			}else if (icode2.equals("d") && (status.equals("$") || status.equals("s") || status.equals("m") || status.equals("r") || status.equals("z"))){
				logger.debug("Item record is suppressed due to icode2 / status");
				return true;
			}else if (icode2.equals("x") && status.equals("n")){
				logger.debug("Item record is suppressed due to icode2 / status");
				return true;
			}else if (icode2.equals("c")){
				logger.debug("Item record is suppressed due to icode2 / status");
				return true;
			}

		}
		return super.isItemSuppressed(curItem);
	}

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1") || status.equals("a");
	}

}
