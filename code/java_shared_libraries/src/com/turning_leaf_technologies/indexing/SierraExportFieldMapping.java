package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class SierraExportFieldMapping {
	private String fixedFieldDestinationField;
	private char bcode3DestinationSubfield;
	private char materialTypeSubfield;
	private char bibLevelLocationsSubfield;
	private String callNumberExportFieldTag;
	private char callNumberPrestampExportSubfield;
	private char callNumberExportSubfield;
	private char callNumberCutterExportSubfield;
	private char callNumberPoststampExportSubfield;
	private String volumeExportFieldTag;
	private String urlExportFieldTag;
	private String eContentExportFieldTag;
	private String itemPublicNoteExportSubfield;

	public String getFixedFieldDestinationField() {
		return fixedFieldDestinationField;
	}

	private void setFixedFieldDestinationField(String bcode3DestinationField) {
		this.fixedFieldDestinationField = bcode3DestinationField;
	}

	public char getBcode3DestinationSubfield() {
		return bcode3DestinationSubfield;
	}

	private void setBcode3DestinationSubfield(char bcode3DestinationSubfield) {
		this.bcode3DestinationSubfield = bcode3DestinationSubfield;
	}

	public String getCallNumberExportFieldTag() {
		return callNumberExportFieldTag;
	}

	private void setCallNumberExportFieldTag(String callNumberExportFieldTag) {
		this.callNumberExportFieldTag = callNumberExportFieldTag;
	}

	public char getCallNumberPrestampExportSubfield() {
		return callNumberPrestampExportSubfield;
	}

	private void setCallNumberPrestampExportSubfield(char callNumberPrestampExportSubfield) {
		this.callNumberPrestampExportSubfield = callNumberPrestampExportSubfield;
	}

	public char getCallNumberExportSubfield() {
		return callNumberExportSubfield;
	}

	private void setCallNumberExportSubfield(char callNumberExportSubfield) {
		this.callNumberExportSubfield = callNumberExportSubfield;
	}

	public char getCallNumberCutterExportSubfield() {
		return callNumberCutterExportSubfield;
	}

	private void setCallNumberCutterExportSubfield(char callNumberCutterExportSubfield) {
		this.callNumberCutterExportSubfield = callNumberCutterExportSubfield;
	}

	public char getCallNumberPoststampExportSubfield() {
		return callNumberPoststampExportSubfield;
	}

	private void setCallNumberPoststampExportSubfield(char callNumberPoststampExportSubfield) {
		this.callNumberPoststampExportSubfield = callNumberPoststampExportSubfield;
	}

	public String getVolumeExportFieldTag() {
		return volumeExportFieldTag;
	}

	private void setVolumeExportFieldTag(String volumeExportFieldTag) {
		this.volumeExportFieldTag = volumeExportFieldTag;
	}

	public String getUrlExportFieldTag() {
		return urlExportFieldTag;
	}

	private void setUrlExportFieldTag(String urlExportFieldTag) {
		this.urlExportFieldTag = urlExportFieldTag;
	}

	public String getEContentExportFieldTag() {
		return eContentExportFieldTag;
	}

	private void setEContentExportFieldTag(String eContentExportFieldTag) {
		this.eContentExportFieldTag = eContentExportFieldTag;
	}

	public static SierraExportFieldMapping loadSierraFieldMappings(Connection dbConn, long profileId, Logger logger) {
		//Get the Indexing Profile from the database
		SierraExportFieldMapping sierraFieldMapping = new SierraExportFieldMapping();
		try {
			PreparedStatement getSierraFieldMappingsStmt = dbConn.prepareStatement("SELECT * FROM sierra_export_field_mapping where indexingProfileId =" + profileId);
			ResultSet getSierraFieldMappingsRS = getSierraFieldMappingsStmt.executeQuery();
			if (getSierraFieldMappingsRS.next()) {
				sierraFieldMapping.setFixedFieldDestinationField(getSierraFieldMappingsRS.getString("fixedFieldDestinationField"));
				sierraFieldMapping.setBcode3DestinationSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("bcode3DestinationSubfield")));
				sierraFieldMapping.setMaterialTypeSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("materialTypeSubfield")));
				sierraFieldMapping.setBibLevelLocationsSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("bibLevelLocationsSubfield")));
				sierraFieldMapping.setCallNumberExportFieldTag(getSierraFieldMappingsRS.getString("callNumberExportFieldTag"));
				sierraFieldMapping.setCallNumberPrestampExportSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("callNumberPrestampExportSubfield")));
				sierraFieldMapping.setCallNumberExportSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("callNumberExportSubfield")));
				sierraFieldMapping.setCallNumberCutterExportSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("callNumberCutterExportSubfield")));
				sierraFieldMapping.setCallNumberPoststampExportSubfield(StringUtils.convertStringToChar(getSierraFieldMappingsRS.getString("callNumberPoststampExportSubfield")));
				sierraFieldMapping.setVolumeExportFieldTag(getSierraFieldMappingsRS.getString("volumeExportFieldTag"));
				sierraFieldMapping.setUrlExportFieldTag(getSierraFieldMappingsRS.getString("urlExportFieldTag"));
				sierraFieldMapping.setEContentExportFieldTag(getSierraFieldMappingsRS.getString("eContentExportFieldTag"));
				sierraFieldMapping.setItemPublicNoteExportSubfield(getSierraFieldMappingsRS.getString("itemPublicNoteExportSubfield"));

				getSierraFieldMappingsRS.close();
			}
			getSierraFieldMappingsStmt.close();

		} catch (Exception e) {
			logger.error("Error reading sierra field mappings", e);
		}
		return sierraFieldMapping;
	}

	private void setItemPublicNoteExportSubfield(String itemPublicNoteExportSubfield) {
		this.itemPublicNoteExportSubfield = itemPublicNoteExportSubfield;
	}

	public String getItemPublicNoteExportSubfield(){
		return itemPublicNoteExportSubfield;
	}

	public char getMaterialTypeSubfield() {
		return materialTypeSubfield;
	}

	public void setMaterialTypeSubfield(char materialTypeSubfield) {
		this.materialTypeSubfield = materialTypeSubfield;
	}

	public char getBibLevelLocationsSubfield() {
		return bibLevelLocationsSubfield;
	}

	public void setBibLevelLocationsSubfield(char bibLevelLocationsSubfield) {
		this.bibLevelLocationsSubfield = bibLevelLocationsSubfield;
	}
}
