<?php

class LocationMoreDetails extends DataObject{
	public $__table = 'location_more_details';
	public $id;
	public $locationId;
	public $source;
	public $collapseByDefault;
	public $weight;

	static function getObjectStructure() : array {
		//Load Libraries for lookup values
		require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
		$validSources = RecordInterface::getValidMoreDetailsSources();
		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
				'source' => array('property'=>'source', 'type'=>'enum', 'label'=>'Source', 'values' => $validSources, 'description'=>'The source of the data to display'),
				'collapseByDefault' => array('property'=>'collapseByDefault', 'type'=>'checkbox', 'label'=>'Collapse By Default', 'description'=>'Whether or not the section should be collapsed by default', 'default' => true),
				'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true),
		);
		return $structure;
	}

	function getEditLink($context) : string{
		return '';
	}
}