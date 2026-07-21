<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/Drivers/SirsiDynixROA.php';

class SymphonySelfRegistrationCountyCodeValues extends DataObject {
	public $__table = 'self_reg_county_code_values_symphony';
	public $id;
	public $selfRegistrationFormId;
	public $countyCode;
	public $countyName;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array
	{
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'countyCode' => [
				'property' => 'countyCode',
				'type' => 'text',
				'label' => 'County Code',
				'description' => 'The two letter county code',
				'required' => true,
			],
			'countyName' => [
				'property' => 'countyName',
				'type' => 'text',
				'label' => 'County Name',
				'description' => 'The full name of the county',
				'required' => false,
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}