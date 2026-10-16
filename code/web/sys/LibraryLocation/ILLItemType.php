<?php /** @noinspection PhpMissingFieldTypeInspection */


class ILLItemType extends DataObject {
	public $__table = 'library_ill_item_type';
	public $id;
	public $libraryId;
	public $code;


	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(false);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the ILL Item Type within the database',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library',
			],
			'code' => [
				'property' => 'code',
				'type' => 'text',
				'label' => 'ILS Item Type Code',
				'description' => 'The item type code in the ILS',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}