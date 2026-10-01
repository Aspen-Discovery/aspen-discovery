<?php /** @noinspection PhpMissingFieldTypeInspection */


class FieldLock extends DataObject {
	public $__table = 'administration_field_lock';// table name
	public $id;
	public $module;
	public $toolName;
	public $field;

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
				'description' => 'The unique id of the permission within the database',
			],
			'module' => [
				'property' => 'module',
				'type' => 'text',
				'label' => 'Module',
				'maxLength' => 30,
				'description' => 'The module where the locked field occurs.',
			],
			'toolName' => [
				'property' => 'toolName',
				'type' => 'text',
				'label' => 'Tool Name',
				'maxLength' => 100,
				'description' => 'The tool where the locked field occurs.',
			],
			'field' => [
				'property' => 'field',
				'type' => 'text',
				'label' => 'Field',
				'maxLength' => 100,
				'description' => 'The field to be locked.',
			],

		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}