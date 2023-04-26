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

	public static function getObjectStructure($context = ''): array {
		$updateTypes = [
			'patch' => 'Patch',
			'complete' => 'Complete',
		];

		$statuses = [
			'pending' => 'Pending',
			'canceled' => 'Canceled',
		];

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'siteId' => [
				'property' => 'siteId',
				'type' => 'hidden',
				'label' => 'Aspen Site Id',
				'description' => 'The unique Aspen Site Id',
				'default' => '',
				'hideInLists' => true,
			],
			'dateScheduled' => [
				'property' => 'dateScheduled',
				'type' => 'timestamp',
				'label' => 'Date Scheduled',
				'description' => 'When the update was scheduled to run',
				'required' => true,
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'label' => 'Status',
				'values' => $statuses,
				'description' => 'The status of the update',
				'default' => 'pending'
			],
			'updateToVersion' => [
				'property' => 'updateToVersion',
				'type' => 'text',
				'label' => 'Update to Version',
				'description' => 'The version the update will upgrade to',
				'required' => true,
			],
			'updateType' => [
				'property' => 'updateType',
				'type' => 'enum',
				'label' => 'Update Type',
				'values' => $updateTypes,
				'description' => 'The type of update (patch or complete)',
			],
			'dateRun' => [
				'property' => 'dateRun',
				'type' => 'label',
				'label' => 'Date Ran',
				'description' => 'When the update actually ran',
				'default' => null
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