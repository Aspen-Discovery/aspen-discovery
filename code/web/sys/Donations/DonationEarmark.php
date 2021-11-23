<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class DonationEarmark extends DataObject
{
	public $__table = 'donations_earmark';
	public $id;
	public $donationSettingId;
	public $weight;
	public $label;

	static function getObjectStructure() : array {
		$structure = array(
			'id'           => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'label'        => array('property' => 'label', 'type' => 'text', 'label' => 'Label', 'description' => 'The label for the earmark'),
			'weight'       => array('property' => 'weight', 'type' => 'integer', 'label' => 'Weight', 'description' => 'The sort order'),
		);
		return $structure;
	}

	static function getDefaults($donationSettingId) {
		$defaultEarmarksToDisplay = array();

		$defaultEarmark = new DonationEarmark();
		$defaultEarmark->donationSettingId = $donationSettingId;
		$defaultEarmark->label = "Where it's needed most";
		$defaultEarmark->weight = count($defaultEarmarksToDisplay)+1;
		$defaultEarmark->insert();
		$defaultEarmarksToDisplay[] = $defaultEarmark;

		return $defaultEarmarksToDisplay;
	}
}