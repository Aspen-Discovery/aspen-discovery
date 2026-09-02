<?php /** @noinspection PhpMissingFieldTypeInspection */


class DonationValue extends DataObject {
	public $__table = 'donations_value';
	public $id;
	public $weight;
	public $donationSettingId;
	public $value;
	public $isDefault;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'value' => [
				'property' => 'value',
				'type' => 'integer',
				'label' => 'Value',
				'description' => 'The value to display',
				'default' => 0,
				'required' => true,
			],
			'isDefault' => [
				'property' => 'isDefault',
				'type' => 'checkbox',
				'label' => 'Selected by Default',
				'description' => 'Whether or not this value is selected by default',
				'default' => 0,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	static function getDefaults($donationSettingId) : array {
		$defaultDonationValuesToDisplay = [];

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 5;
		$defaultDonationValue->weight = 1;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 15;
		$defaultDonationValue->isDefault = 1;
		$defaultDonationValue->weight = 2;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 25;
		$defaultDonationValue->weight = 3;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		$defaultDonationValue = new DonationValue();
		$defaultDonationValue->value = 75;
		$defaultDonationValue->weight = 4;
		$defaultDonationValue->donationSettingId = $donationSettingId;
		$defaultDonationValue->insert();
		$defaultDonationValuesToDisplay[] = $defaultDonationValue;

		return $defaultDonationValuesToDisplay;
	}
}