<?php
/**
 * Provides information for mapping fixed bib fields and variable item fields to MARC records when using the Sierra Export.
 *
 * User: mnoble
 * Date: 4/16/2018
 * Time: 12:17 PM
 */

class SierraExportFieldMapping extends DB_DataObject{
	public $__table = 'sierra_export_field_mapping';    // table name
	public $id;
	public $indexingProfileId;
	public $bcode3DestinationField;
	public $bcode3DestinationSubfield;
	public $callNumberExportFieldTag;
	public $callNumberPrestampExportSubfield;
	public $callNumberExportSubfield;
	public $callNumberCutterExportSubfield;
	public $callNumberPoststampExportSubfield;
	public $volumeExportFieldTag;
	public $urlExportFieldTag;
	public $eContentExportFieldTag;

	function getObjectStructure(){
		$indexingProfiles = array();
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()){
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id within the database'),
				'indexingProfileId' => array('property' => 'indexingProfileId', 'type' => 'enum', 'values' => $indexingProfiles, 'label' => 'Indexing Profile Id', 'description' => 'The Indexing Profile this map is associated with'),
				'bcode3DestinationField' => array('property' => 'bcode3DestinationField', 'type' => 'text', 'label' => 'BCode3 Destination Field', 'maxLength' => 3, 'description' => 'The MARC field where BCode3 should be stored'),
				'bcode3DestinationSubfield' => array('property' => 'bcode3DestinationSubfield', 'type' => 'text', 'label' => 'BCode3 Destination Subfield', 'maxLength' => 1, 'description' => 'Subfield for where BCode3 should be stored'),
				'callNumberExportFieldTag' => array('property' => 'callNumberExportFieldTag', 'type' => 'text', 'label' => 'Call Number Export Field Tag', 'maxLength' => 1, 'description' => 'The Item Variable field tag where call number is exported'),
				'callNumberPrestampExportSubfield' => array('property' => 'callNumberPrestampExportSubfield', 'type' => 'text', 'label' => 'Call Number Prestamp Export Subfield', 'maxLength' => 1, 'description' => 'The subfield where the call number prestamp is exported'),
				'callNumberExportSubfield' => array('property' => 'callNumberExportSubfield', 'type' => 'text', 'label' => 'Call Number Export Subfield', 'maxLength' => 1, 'description' => 'The subfield where the call number is exported'),
				'callNumberCutterExportSubfield' => array('property' => 'callNumberCutterExportSubfield', 'type' => 'text', 'label' => 'Call Number Cutter Export Subfield', 'maxLength' => 1, 'description' => 'The subfield where the call number cutter is exported'),
				'callNumberPoststampExportSubfield' => array('property' => 'callNumberPoststampExportSubfield', 'type' => 'text', 'label' => 'Call Number Poststamp Export Subfield', 'maxLength' => 5, 'description' => 'The subfield where the call number poststamp is exported.  Multiple can be specified.  I.e. eS is both e and S'),
				'volumeExportFieldTag' => array('property' => 'volumeExportFieldTag', 'type' => 'text', 'label' => 'Volume Export Field Tag', 'maxLength' => 1, 'description' => 'The Item Variable field tag where volume is exported'),
				'urlExportFieldTag' => array('property' => 'urlExportFieldTag', 'type' => 'text', 'label' => 'URL Export Field Tag', 'maxLength' => 1, 'description' => 'The Item Variable field tag where the url is exported'),
				'eContentExportFieldTag' => array('property' => 'eContentExportFieldTag', 'type' => 'text', 'label' => 'eContent Export Field Tag', 'maxLength' => 1, 'description' => 'The Item Variable field tag where eContent information (Marmot Only) is exported'),
		);
		return $structure;
	}
}