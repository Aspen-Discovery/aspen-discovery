<?php /** @noinspection PhpMissingFieldTypeInspection */


class ListIndexingSettings extends DataObject {
	public $__table = 'list_indexing_settings';    // table name
	public $id;
	public $runFullUpdate;
	/** @noinspection PhpUnused */
	public $lastUpdateOfChangedLists;
	/** @noinspection PhpUnused */
	public $lastUpdateOfAllLists;

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
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'lastUpdateOfChangedLists' => [
				'property' => 'lastUpdateOfChangedLists',
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Lists',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllLists' => [
				'property' => 'lastUpdateOfAllLists',
				'type' => 'timestamp',
				'label' => 'Last Update of All Lists',
				'description' => 'The timestamp when all lists were loaded',
				'default' => 0,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}