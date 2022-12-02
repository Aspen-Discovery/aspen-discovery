<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class MaterialsRequestFieldsToDisplay extends DataObject {
	public $__table = 'materials_request_fields_to_display';
	public $id;
	public $libraryId;
	public $columnNameToDisplay;
	public $labelForColumnToDisplay;
	public $weight;

	static function getObjectStructure(): array {
		$materialsRequest = new MaterialsRequest();
		$columnNames = array_keys($materialsRequest->table());
		$columnToChooseFrom = array_combine($columnNames, $columnNames);

		//specialFormat Fields get handled specially
		unset($columnToChooseFrom['abridged'], $columnToChooseFrom['magazineDate'], $columnToChooseFrom['magazineNumber'], $columnToChooseFrom['magazinePageNumbers'], $columnToChooseFrom['magazineTitle'], $columnToChooseFrom['magazineVolume'], $columnToChooseFrom['season']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'columnNameToDisplay' => [
				'property' => 'columnNameToDisplay',
				'type' => 'enum',
				'label' => 'Name of Column to Display',
				'values' => $columnToChooseFrom,
				'description' => 'Name of the database column to list in the main table of the Manage Requests Page',
			],
			'labelForColumnToDisplay' => [
				'property' => 'labelForColumnToDisplay',
				'type' => 'text',
				'label' => 'Display Label',
				'description' => 'Label to put in the table header of the Manage Requests page.',
			],
		];
		return $structure;
	}
}