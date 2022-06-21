<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class DonationValue extends DataObject
{
	public $__table = 'donations_value';
	public $id;
	public $donationSettingId;
	public $value;
	public $isDefault;

	static function getObjectStructure() : array {
		$structure = array(
			'id'           => array('property' => 'id', 'type'=> 'label', 'label'=> 'Id', 'description'=> 'The unique id'),
			'value'        => array('property' => 'value', 'type'=> 'integer', 'label'=> 'Value', 'description'=> 'The value to display', 'default' => 0, 'required' => true),
			'isDefault'    => array('property' => 'isDefault', 'type' => 'checkbox', 'label' => 'Selected by Default', 'description' => 'Whether or not this value is selected by default', 'default' => 0),
		);
		return $structure;
	}

	static function getDefaults($donationSettingId) {
		$defaultDonationValuesToDisplay = array();

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 5;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 15;
		$defaultDonationValue->isDefault = 1;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 25;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 75;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		return $defaultDonationValuesToDisplay;
	}

	public static function getValues() : array {
		return [];
	}
}