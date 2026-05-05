<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/Drivers/Sierra.php';

class SierraSelfRegistrationMunicipalityValues extends DataObject {
	public $__table = 'self_reg_municipality_values_sierra';
	public $id;
	public $selfRegistrationFormId;
	public $municipality;
	public $municipalityType;
	public $selfRegAllowed;
	public $sierraPType;
	public $sierraPTypeApproved;
	public $sierraPCode1;
	public $sierraPCode2;
	public $sierraPCode3;
	public $sierraPCode4;
	public $expirationLength;
	public $expirationPeriod;
	public $extendExpirationToMonthEnd;

	public function getNumericColumnNames(): array {
		return [
			'expirationLength',
			'selfRegAllowed'
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$base[''] = "None";
		$metadataOptions = self::getMetadataOptions('patronType,pcode1,pcode2,pcode3,pcode4');
		if (!empty($metadataOptions['patronType'])) {
			$sierraPTypes = $base + $metadataOptions['patronType'];
		}else{
			$sierraPTypes = $base;
		}
		if (!empty($metadataOptions['pcode1'])) {
			$pCode1Options = $base + $metadataOptions['pcode1'];
		}else{
			$pCode1Options = $base;
		}
		if (!empty($metadataOptions['pcode2'])) {
			$pCode2Options = $base + $metadataOptions['pcode2'];
		}else{
			$pCode2Options = $base;
		}
		if (!empty($metadataOptions['pcode3'])) {
			$pCode3Options = $base + $metadataOptions['pcode3'];
		}else{
			$pCode3Options = $base;
		}
		if (!empty($metadataOptions['pcode4'])) {
			$pCode4Options = $base + $metadataOptions['pcode4'];
		}else{
			$pCode4Options = $base;
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
				'description' => 'The name of a city, county, or state',
				'required' => true,
			],
			'municipalityType' => [
				'property' => 'municipalityType',
				'type' => 'enum',
				'label' => 'Municipality Type',
				'values' => [
					'city' => 'City',
					'county' => 'County',
					'state' => 'State',
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
			],
			'sierraPType' => [
				'property' => 'sierraPType',
				'type' => 'enum',
				'label' => 'Sierra PType (Initial)',
				'values' => $sierraPTypes,
				'description' => 'The temporary PType to automatically apply before approval',
				'default' => '',
			],
			'sierraPTypeApproved' => [
				'property' => 'sierraPTypeApproved',
				'type' => 'enum',
				'label' => 'Sierra PType (After Approval)',
				'values' => $sierraPTypes,
				'description' => 'The PType to apply after approval',
				'default' => '',
			],
			'sierraPCode1' => [
				'property' => 'sierraPCode1',
				'type' => 'enum',
				'label' => 'Sierra PCode1',
				'values' => $pCode1Options,
				'description' => 'The PCode 1 to automatically apply',
				'default' => '',
			],
			'sierraPCode2' => [
				'property' => 'sierraPCode2',
				'type' => 'enum',
				'label' => 'Sierra PCode2',
				'values' => $pCode2Options,
				'description' => 'The PCode 2 to automatically apply',
				'default' => '',
			],
			'sierraPCode3' => [
				'property' => 'sierraPCode3',
				'type' => 'enum',
				'label' => 'Sierra PCode3',
				'values' => $pCode3Options,
				'description' => 'The PCode 3 to automatically apply',
				'default' => '',
			],
			'sierraPCode4' => [
				'property' => 'sierraPCode4',
				'type' => 'enum',
				'label' => 'Sierra PCode4',
				'values' => $pCode4Options,
				'description' => 'The PCode 4 to automatically apply',
				'default' => '',
			],
			'expirationLength' => [
				'property' => 'expirationLength',
				'type' => 'integer',
				'label' => 'Expiration Length',
				'description' => 'How many days, months, or years before expiration',
				'default' => 0,
			],
			'expirationPeriod' => [
				'property' => 'expirationPeriod',
				'type' => 'enum',
				'label' => 'Expiration Period',
				'values' => [
					'D' => 'Days',
					'M' => 'Months',
					'Y' => 'Years',
				],
				'description' => 'The type of municipality',
				'default' => '0',
			],
			'extendExpirationToMonthEnd' => [
				'property' => 'extendExpirationToMonthEnd',
				'type' => 'checkbox',
				'label' => 'Extend Expiration To Month End?',
				'description' => 'Whether expiration dates will be extended to the end of the month automatically (based on calculated expiration)',
				'default' => '0',
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __set($name, $value) {
		if ($name == "sierraPType" && $value == '') {
			$value = -1;
		}
		else if ($name == "sierraPCode3" && $value == '') {
			$value = -1;
		}
		else if ($name == "sierraPCode4" && $value == '') {
			$value = -1;
		}
		parent::__set($name, $value);
	}

	public function update(string $context = '') : int|bool {
		if ($this->sierraPType == '') {
			$this->sierraPType = -1;
		}
		if ($this->sierraPTypeApproved == '') {
			$this->sierraPTypeApproved = -1;
		}
		if ($this->sierraPCode3 == '') {
			$this->sierraPCode3 = -1;
		}
		if ($this->sierraPCode4 == '') {
			$this->sierraPCode4 = -1;
		}
		return parent::update($context);
	}

	public function insert(string $context = '') : int|bool {
		if ($this->sierraPType == '') {
			$this->sierraPType = -1;
		}
		if ($this->sierraPTypeApproved == '') {
			$this->sierraPTypeApproved = -1;
		}
		if ($this->sierraPCode3 == '') {
			$this->sierraPCode3 = -1;
		}
		if ($this->sierraPCode4 == '') {
			$this->sierraPCode4 = -1;
		}
		return parent::insert($context);
	}

	public static function getMetadataOptions($field) {
		global $library;
		$accountProfile = $library->getAccountProfile();
		$catalogDriverName = trim($accountProfile->driver);
		$catalogDriver = null;
		if (!empty($catalogDriverName)) {
			$catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriverName, $accountProfile);
		}
		if ($catalogDriver->driver instanceof Sierra) {
			return $catalogDriver->driver->getPatronMetadataOptions($field);
		} else {
			return [];
		}
	}

}