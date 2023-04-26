<?php

class ScheduledUpdate extends DataObject {
	public $__table = 'aspen_site_scheduled_update';
	public $id;
	public $dateScheduled;
	public $updateToVersion;
	public $updateType; //patch update, complete update
	public $dateRun;
	public $status;
	public $notes;
	public $siteId;

	public static $_updateTypes = [
		'patch' => 'Patch',
		'complete' => 'Complete',
	];

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'siteId' => [
				'property' => 'siteId',
				'type' => 'label',
				'label' => 'Aspen Site Id',
				'description' => 'The unique Aspen Site Id',
			],
			'dateScheduled' => [
				'property' => 'dateScheduled',
				'type' => 'timestamp',
				'label' => 'Date Scheduled',
				'description' => 'When the update was scheduled to run',
			],
			'status' => [
				'property' => 'status',
				'type' => 'label',
				'label' => 'Status',
				'description' => 'The status of the update',
			],
			'updateToVersion' => [
				'property' => 'updateToVersion',
				'type' => 'label',
				'label' => 'Update to Version',
				'description' => 'The version the update will upgrade to',
			],
			'updateType' => [
				'property' => 'updateType',
				'type' => 'label',
				'label' => 'Update Type',
				'description' => 'The type of update (patch or complete)',
			],
			'dateRun' => [
				'property' => 'dateRun',
				'type' => 'timestamp',
				'label' => 'Date Ran',
				'description' => 'When the update actually ran',
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'textarea',
				'label' => 'Notes',
				'description' => 'Notes from when the update ran',
				'hideInLists' => true,
			],
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'id',
			'siteId'
		];
	}
}