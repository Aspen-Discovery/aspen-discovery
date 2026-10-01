<?php /** @noinspection PhpMissingFieldTypeInspection */


class DonationDedicationType extends DataObject {
	public $__table = 'donations_dedicate_type';
	public $id;
	public $donationSettingId;
	public $label;

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
			'label' => [
				'property' => 'label',
				'type' => 'text',
				'label' => 'Label',
				'description' => 'The label for the dedication type',
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	static function getDefaults($donationSettingId) : array {
		$defaultDedicationTypesToDisplay = [];

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