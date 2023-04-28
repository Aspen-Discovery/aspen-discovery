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

	public $currentVersion;

	public static function getObjectStructure($context = ''): array {
		global $interface;
		$currentRelease = $interface->getVariable('gitBranch');
		$updateTypes = [
			'patch' => 'Patch',
			'complete' => 'Complete',
		];

		$statuses = [
			'pending' => 'Pending',
			'canceled' => 'Canceled',
		];

		$releases = [];
		$eligibleReleases = [];
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
			if ($result = file_get_contents($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=getReleaseInformation')) {
				$data = json_decode($result, true);
				$releases = $data['releases'];
			}
		} else {
			global $configArray;
			if ($result = file_get_contents($configArray['Site']['url'] . '/API/GreenhouseAPI?method=getReleaseInformation')) {
				$data = json_decode($result, true);
				$releases = $data['releases'];
			}
		}

		foreach($releases as $release) {
			if(version_compare($release['version'], $currentRelease, '>=')) {
				$eligibleReleases[$release['version']] = $release;
			} else {
				unset($eligibleReleases[$release['version']]);
			}
		}

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
			'currentVersion' => [
				'property' => 'currentVersion',
				'type' => 'text',
				'label' => 'Current Version',
				'default' => $currentRelease,
				'readOnly' => true,
			],
			'updateToVersion' => [
				'property' => 'updateToVersion',
				'type' => 'enum',
				'label' => 'Update to Version',
				'values' => $eligibleReleases,
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