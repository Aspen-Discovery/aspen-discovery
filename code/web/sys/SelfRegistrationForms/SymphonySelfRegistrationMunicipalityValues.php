<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/Drivers/SirsiDynixROA.php';

class SymphonySelfRegistrationMunicipalityValues extends DataObject {
	public $__table = 'self_reg_municipality_values_symphony';
	public $id;
	public $selfRegistrationFormId;
	public $municipality;
	public $ilsMunicipality;
	public $municipalityType;
	public $selfRegAllowed;


	public function getNumericColumnNames(): array
	{
		return [
			'selfRegAllowed'
		];
	}

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
			'municipality' => [
				'property' => 'municipality',
				'type' => 'text',
				'label' => 'Municipality Name',
				'description' => 'The name of a city, town, or village',
				'required' => true,
			],
			'ilsMunicipality' => [
				'property' => 'ilsMunicipality',
				'type' => 'text',
				'label' => 'Municipality Name in ILS',
				'description' => 'The name of municipality in ILS',
				'required' => true,
			],
			'municipalityType' => [
				'property' => 'municipalityType',
				'type' => 'enum',
				'label' => 'Municipality Type',
				'values' => [
					'city' => 'City',
					'town' => 'Town',
					'village' => 'Village',
				],
				'description' => 'The type of municipality',
				'default' => '0',
			],
			'selfRegAllowed' => [
				'property' => 'selfRegAllowed',
				'type' => 'checkbox',
				'label' => 'Self Registration Allowed?',
				'description' => 'Whether or not the municipality allows self registration',
				'default' => '1',
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}