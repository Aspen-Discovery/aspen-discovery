<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class QuickSearch extends DataObject {
	public $__table = 'aspen_lida_quick_searches';
	public $id;
	public $weight;
	public $searchTerm;
	public $label;
	public $quickSearchSettingId;

	static function getObjectStructure(): array {
		return [
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
				'description' => 'The label for quick search',
				'required' => true,
			],
			'searchTerm' => [
				'property' => 'searchTerm',
				'type' => 'text',
				'label' => 'Search Term',
				'description' => 'The term to use for the quick search',
				'required' => true,
			],
		];
	}
}