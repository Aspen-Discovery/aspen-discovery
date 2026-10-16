<?php /** @noinspection PhpMissingFieldTypeInspection */


class DonationEarmark extends DataObject {
	public $__table = 'donations_earmark';
	public $id;
	public $donationSettingId;
	public $weight;
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
				'description' => 'The label for the earmark',
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
		$defaultEarmarksToDisplay = [];

		$defaultEarmark = new DonationEarmark();
		$defaultEarmark->donationSettingId = $donationSettingId;
		$defaultEarmark->label = "Where it's needed most";
		$defaultEarmark->weight = 1;
		$defaultEarmark->insert();
		$defaultEarmarksToDisplay[] = $defaultEarmark;

		return $defaultEarmarksToDisplay;
	}
}