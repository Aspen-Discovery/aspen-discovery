package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.marc.MarcUtil;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;

class ArlingtonKohaRecordProcessor extends KohaRecordProcessor {
	ArlingtonKohaRecordProcessor(GroupedWorkIndexer indexer, String profileType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, profileType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected void loadLiteraryForms(AbstractGroupedWorkSolr groupedWork, Record record, ArrayList<ItemInfo> printItems, String identifier) {
		//For Arlington we can load the literary forms based off of the location code:
		// ??f?? = Fiction
		// ??n?? = Non-Fiction
		// ??x?? = Other
		String literaryForm = null;
		for (ItemInfo printItem : printItems){
			String locationCode = printItem.getShelfLocationCode();
			if (locationCode != null) {
				literaryForm = getLiteraryFormForLocation(locationCode);
				if (literaryForm != null){
					break;
				}
			}
		}
		if (literaryForm == null){
			literaryForm = "Other";
		}
		groupedWork.addLiteraryForm(literaryForm);
		groupedWork.addLiteraryFormFull(literaryForm);
	}

	private String getLiteraryFormForLocation(String locationCode) {
		String literaryForm = null;
		if (locationCode.length() >= 2) {
			if (locationCode.charAt(1) == 'F') {
				literaryForm = "Fiction";
			} else if (locationCode.charAt(1) == 'N') {
				literaryForm = "Non Fiction";
			}
		}
		return literaryForm;
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(AbstractGroupedWorkSolr groupedWork, String identifier, Record record){
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		//For arlington, eContent will always have no items on the bib record.
		List<DataField> items = MarcUtil.getDataFields(record, itemTagInt);
		if (items.size() > 0){
			return unsuppressedEcontentRecords;
		}else{
			//Get the url
			String url = MarcUtil.getFirstFieldVal(record, "856u");

			if (url != null && !url.toLowerCase().contains("lib.overdrive.com")){
				//Get the econtent source
				String urlLower = url.toLowerCase();
				String econtentSource;
				String specifiedSource = MarcUtil.getFirstFieldVal(record, "856x");
				if (specifiedSource != null){
					econtentSource = specifiedSource;
				}else {
					String urlText = MarcUtil.getFirstFieldVal(record, "856z");
					if (urlText != null) {
						urlText = urlText.toLowerCase();
						if (urlText.contains("gale virtual reference library")) {
							econtentSource = "Gale Virtual Reference Library";
						} else if (urlText.contains("gale directory library")) {
							econtentSource = "Gale Directory Library";
						} else if (urlText.contains("hoopla")) {
							econtentSource = "Hoopla";
						} else if (urlText.contains("national geographic virtual library")) {
							econtentSource = "National Geographic Virtual Library";
						} else if ((urlText.contains("ebscohost") || urlLower.contains("netlibrary") || urlLower.contains("ebsco"))) {
							econtentSource = "EbscoHost";
						} else {
							econtentSource = "Premium Sites";
						}
					} else {
						econtentSource = "Premium Sites";
					}
				}

				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setIsEContent(true);
				itemInfo.setLocationCode("Online");
				itemInfo.setCallNumber("Online");
				itemInfo.seteContentSource(econtentSource);
				itemInfo.setShelfLocation("Online");
				itemInfo.setDetailedLocation(econtentSource);
				itemInfo.setIType("eCollection");
				RecordInfo relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				itemInfo.seteContentUrl(url);

				//Set the format based on the material type
				String formatFrom856 = MarcUtil.getFirstFieldVal(record, "856z");
				if (formatFrom856 != null) {
					itemInfo.setFormat(formatFrom856);
				}else {
					itemInfo.setFormat("Online Content");
				}
				itemInfo.setFormatCategory("eBook");
				relatedRecord.setFormatBoost(10);

				itemInfo.setDetailedStatus("Available Online");

				logger.debug("Found eContent record from " + econtentSource);
				unsuppressedEcontentRecords.add(relatedRecord);
			}
		}
		return unsuppressedEcontentRecords;
	}

	/**
	 * For Arlington do not load Bisac Subjects and load full stings with subfields for topics
	 */
	protected void loadSubjects(AbstractGroupedWorkSolr groupedWork, Record record){
		HashSet<String> validSubjects = new HashSet<>();
		getSubjectValues(MarcUtil.getDataFields(record, 600), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, 610), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, 611), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, 630), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, 650), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, 651), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, 690), validSubjects);

		groupedWork.addSubjects(validSubjects);
		//Add lc subjects
		//groupedWork.addLCSubjects(getLCSubjects(record));
		//Add bisac subjects
		groupedWork.addGenre(MarcUtil.getAllSubfields(record, "655abcvxyz", " -- "));
		groupedWork.addGenreFacet(MarcUtil.getAllSubfields(record, "655av", " -- "));
	}

	private void getSubjectValues(List<DataField> subjectFields, HashSet<String> validSubjects) {
		for (DataField curSubject : subjectFields){
			boolean okToInclude = true;
			Subfield subfield2 = curSubject.getSubfield('2');
			if (subfield2 != null){
				if (subfield2.getData().equalsIgnoreCase("bisac") || subfield2.getData().equalsIgnoreCase("fast")){
					okToInclude = false;
				}
			}
			if (okToInclude){
				StringBuilder subjectValue = new StringBuilder();
				for (Subfield curSubfield : curSubject.getSubfields()){
					if (curSubfield.getCode() != '2' && curSubfield.getCode() != '0' && curSubfield.getCode() != '9'){
						if (subjectValue.length() > 0){
							subjectValue.append(" -- ");
						}
						subjectValue.append(curSubfield.getData());
					}
				}
				validSubjects.add(subjectValue.toString());
			}
		}
	}

	protected boolean use099forBibLevelCallNumbers() {
		return false;
	}
}
