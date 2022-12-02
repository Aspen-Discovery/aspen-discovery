<?php


class Permission extends DataObject {
	public $__table = 'permissions';// table name
	public $id;
	public $name;
	public $weight;
	public $sectionName;
	public $requiredModule;
	public $description;

	static function getObjectStructure(): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the permission within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 75,
				'description' => 'The full name of the permission.',
			],
			'sectionName' => [
				'property' => 'sectionName',
				'type' => 'text',
				'label' => 'Section Name',
				'maxLength' => 75,
				'description' => 'The section for the permission.',
			],
			'requiredModule' => [
				'property' => 'requiredModule',
				'type' => 'text',
				'label' => 'Required Module',
				'maxLength' => 50,
				'description' => 'A module required to show the permission.',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'description' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 250,
				'description' => 'A description of the permission.',
			],
		];
	}
}