<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class DonationDedicationType extends DataObject
{
	public $__table = 'donations_dedicate_type';
	public $id;
	public $donationSettingId;
	public $label;

	static function getObjectStructure() : array {
		$structure = array(
			'id'           => array('property' => 'id', 'type'=> 'label', 'label'=> 'Id', 'description'=> 'The unique id'),
			'label'        => array('property' => 'label', 'type'=> 'text', 'label'=> 'Label', 'description'=> 'The label for the dedication type', 'required' => true),
		);
		return $structure;
	}

	static function getDefaults($donationSettingId) {
		$defaultDedicationTypesToDisplay = array();

		$defaultDedicationType = new DonationDedicationType();
		$defaultDedicationType->label = "In honor of...";
		$defaultDedicationType->donationSettingId = $donationSettingId;
		$defaultDedicationType->insert();
		$defaultDedicationTypesToDisplay[] = $defaultDedicationType;

		$defaultDedicationType = new DonationDedicationType();
		$defaultDedicationType->label = "In memory of...";
		$defaultDedicationType->donationSettingId = $donationSettingId;
		$defaultDedicationType->insert();
		$defaultDedicationTypesToDisplay[] = $defaultDedicationType;

		return $defaultDedicationTypesToDisplay;
	}

}