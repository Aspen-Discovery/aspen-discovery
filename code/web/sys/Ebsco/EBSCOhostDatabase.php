<?php

class EBSCOhostDatabase extends DataObject
{
	public $__table = 'ebscohost_database';
	public $id;
	public $searchSettingId;
	public $shortName;
	public $displayName;
	public $allowSearching;
	public $searchByDefault;
	public $showInExploreMore;
	public $showInCombinedResults;

	public function getNumericColumnNames(): array
	{
		return ['searchSettingId', 'allowSearching', 'searchByDefault', 'showInExploreMore', 'showInCombinedResults'];
	}

	public static function getObjectStructure() {
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'shortName' => array('property' => 'shortName', 'type' => 'text', 'label' => 'Short Name', 'maxLength' => 50, 'description' => 'The Short Name from EBSCO', 'required' => true, 'readOnly' => true),
			'displayName' => array('property'=>'displayName', 'type'=>'text', 'label'=>'Display Name', 'description'=>'The Display Name from EBSCO', 'readOnly' => true),
			'allowSearching' => array('property' => 'allowSearching', 'type' => 'checkbox', 'label' => 'Allow Searching', 'description' => 'If the database can be searched'),
			'searchByDefault' => array('property' => 'searchByDefault', 'type' => 'checkbox', 'label' => 'Search By Default', 'description' => 'If the database is searched by default when searching everything'),
			'showInExploreMore' => array('property' => 'showInExploreMore', 'type' => 'checkbox', 'label' => 'Show in Explore More', 'description' => 'If the database is shown in Explore More'),
			'showInCombinedResults' => array('property' => 'showInCombinedResults', 'type' => 'checkbox', 'label' => 'Show in Combined Results', 'description' => 'If the database is shown in Combined Results'),
		];
	}
}