<?php

class StatusMapValue extends DataObject
{
	public $__table = 'status_map_values';    // table name
	public $id;
	public $indexingProfileId;
	public $value;
	public $status;
	public $groupedStatus;
	public $suppress;
	public /** @noinspection PhpUnused */ $inLibraryUseOnly;

	static function getObjectStructure() : array
	{
		$groupedStatuses = array(
			'Currently Unavailable' => 'Currently Unavailable',
			'On Order' => 'On Order',
			'Coming Soon' => 'Coming Soon',
			'In Processing' => 'In Processing',
			'Checked Out' => 'Checked Out',
			'Library Use Only' => 'Library Use Only',
			'Available Online' => 'Available Online',
			'In Transit' => 'In Transit',
			'On Shelf' => 'On Shelf'
		);
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'indexingProfileId' => array('property' => 'indexingProfileId', 'type' => 'foreignKey', 'label' => 'Indexing Profile Id', 'description' => 'The Profile this is associated with'),
			'value' => array('property' => 'value', 'type' => 'text', 'label' => 'Value', 'description' => 'The value to be translated', 'maxLength' => '50', 'required' => true, 'forcesReindex' => true),
			'status' => array('property' => 'status', 'type' => 'text', 'label' => 'Status', 'description' => 'The detailed status', 'maxLength' => '255', 'required' => true, 'forcesReindex' => true),
			'groupedStatus' => array('property' => 'groupedStatus', 'type' => 'enum', 'label' => 'Grouped Status', 'description' => 'The Status Category', 'values' => $groupedStatuses, 'required' => true, 'forcesReindex' => true),
			'suppress' => array('property' => 'suppress', 'type' => 'checkbox', 'label' => 'Suppress?', 'description' => 'Suppress from the catalog', 'default' => 0, 'required' => true, 'forcesReindex' => true),
			'inLibraryUseOnly' => array('property' => 'inLibraryUseOnly', 'type' => 'checkbox', 'label' => 'In Library Use Only?', 'description' => 'Make the item usable within the library only', 'default' => 0, 'required' => true, 'forcesReindex' => true),
		);
	}
}