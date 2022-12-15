<?php

class GroupedWorkMoreDetails extends DataObject {
	public $__table = 'grouped_work_more_details';
	public $__displayNameColumn = 'source';
	public $id;
	public $groupedWorkSettingsId;
	public $source;
	public $collapseByDefault;
	public $weight;

	function getNumericColumnNames(): array {
		return [
			'collapseByDefault',
			'weight',
		];
	}

	static function getObjectStructure($context = ''): array {
		require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
		$validSources = RecordInterface::getValidMoreDetailsSources();
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'groupedWorkSettingsId' => [
				'property' => 'groupedWorkSettingsId;',
				'type' => 'hidden',
				'label' => 'Grouped Work Display Settings',
				'description' => 'A link to the settings which the details belongs to',
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'label' => 'Source',
				'values' => $validSources,
				'description' => 'The source of the data to display',
			],
			'collapseByDefault' => [
				'property' => 'collapseByDefault',
				'type' => 'checkbox',
				'label' => 'Collapse By Default',
				'description' => 'Whether or not the section should be collapsed by default',
				'default' => true,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
			],
		];
	}
}