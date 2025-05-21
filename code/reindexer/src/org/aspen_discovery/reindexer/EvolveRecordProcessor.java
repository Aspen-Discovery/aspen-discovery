package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;

public class EvolveRecordProcessor extends IlsRecordProcessor {
	EvolveRecordProcessor(String serverName, GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(serverName, indexer, curType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		return itemInfo.getStatusCode().equals("Available") || groupedStatus.equals("On Shelf") || (settings.getTreatLibraryUseOnlyGroupedStatusesAsAvailable() && groupedStatus.equals("Library Use Only"));
	}

	protected ResultWithNotes isItemSuppressed(DataField curItem, String itemIdentifier, StringBuilder suppressionNotes) {
		if (settings.getItemStatusSubfield() != ' ') {
			Subfield statusSubfield = curItem.getSubfield(settings.getItemStatusSubfield());
			if (statusSubfield == null) {
				//For evolve this is ok.  It actually means the item is on shelf.
			} else {
				String statusValue = statusSubfield.getData();
				if (settings.getStatusesToSuppressPattern() != null && settings.getStatusesToSuppressPattern().matcher(statusValue).matches()) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - matched status suppression pattern<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}else if (statusesToSuppress.contains(statusValue)){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" - status suppressed in Indexing Profile<br>");
					return new ResultWithNotes(true, suppressionNotes);
				}

			}
		}
		Subfield locationSubfield = curItem.getSubfield(settings.getLocationSubfield());
		if (locationSubfield == null){
			suppressionNotes.append("Item ").append(itemIdentifier).append(" no location<br/>");
			return new ResultWithNotes(true, suppressionNotes);
		}else{
			if (settings.getLocationsToSuppressPattern() != null && settings.getLocationsToSuppressPattern().matcher(locationSubfield.getData().trim()).matches()){
				suppressionNotes.append("Item ").append(itemIdentifier).append(" location matched suppression pattern<br/>");
				return new ResultWithNotes(true, suppressionNotes);
			}
		}
		if (settings.getCollectionSubfield() != ' '){
			Subfield collectionSubfieldValue = curItem.getSubfield(settings.getCollectionSubfield());
			if (collectionSubfieldValue == null){
				if (this.suppressRecordsWithNoCollection) {
					suppressionNotes.append("Item ").append(itemIdentifier).append(" no collection<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}else{
				if (settings.getCollectionsToSuppressPattern() != null && settings.getCollectionsToSuppressPattern().matcher(collectionSubfieldValue.getData().trim()).matches()){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" collection matched suppression pattern<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (settings.getFormatSubfield() != ' '){
			Subfield formatSubfieldValue = curItem.getSubfield(settings.getFormatSubfield());
			if (formatSubfieldValue != null){
				String formatValue = formatSubfieldValue.getData();
				if (formatsToSuppress.contains(formatValue.toUpperCase())){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" format suppressed in formats table<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}
		if (settings.getITypeSubfield() != ' '){
			Subfield iTypeSubfieldValue = curItem.getSubfield(settings.getITypeSubfield());
			if (iTypeSubfieldValue != null){
				String iTypeValue = iTypeSubfieldValue.getData();
				if (iTypesToSuppress != null && iTypesToSuppress.matcher(iTypeValue).matches()){
					suppressionNotes.append("Item ").append(itemIdentifier).append(" iType matched suppression pattern<br/>");
					return new ResultWithNotes(true, suppressionNotes);
				}
			}
		}

		return new ResultWithNotes(false, suppressionNotes);
	}

	protected ItemStatus getItemStatus(DataField itemField, String recordIdentifier){
		String status = MarcUtil.getItemSubfieldData(settings.getItemStatusSubfield(), itemField, indexer.getLogEntry(), logger);
		if (status == null || status.isEmpty()){
			status = "On Shelf";
		} else if (status.startsWith("Due on")) {
			status = "Checked Out";
		}
		return new ItemStatus(status, ItemStatus.FROM_STATUS_FIELD, this, recordIdentifier);
	}
}
