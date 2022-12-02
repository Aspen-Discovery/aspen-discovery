<?php

class Variable extends DataObject {
	public $__table = 'variables'; // table name
	public $id;
	public $name;
	public $value;

	public function getNumericColumnNames(): array {
		return ['id'];
	}

	static function getObjectStructure(): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'hidden',
				'label' => 'Id',
				'description' => 'The unique id of the variable.',
				'primaryKey' => true,
				'storeDb' => true,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the variable.',
				'maxLength' => 255,
				'size' => 100,
				'storeDb' => true,
			],
			'value' => [
				'property' => 'value',
				'type' => 'text',
				'label' => 'Value',
				'description' => 'The value of the variable',
				'storeDb' => true,
				'maxLength' => 255,
				'size' => 100,
			],
		];
	}
} 