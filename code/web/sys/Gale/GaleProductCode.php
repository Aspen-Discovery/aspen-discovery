<?php /** @noinspection PhpMissingFieldTypeInspection */

class GaleProductCode extends DataObject {
	public $__table = 'gale_product_codes';
	public $id;
	public $settingId;
	public $productCode;
	public $displayName;

	public function getNumericColumnNames(): array {
		return ['settingId'];
	}

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
			'productCode' => [
				'property' => 'productCode',
				'type' => 'text',
				'label' => 'Product Code',
				'description' => 'The Gale product code to use in requests.',
				'maxLength' => 50,
				'required' => true,
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Facet Display Name',
				'description' => 'Label shown in the facet list.',
				'maxLength' => 255,
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}
