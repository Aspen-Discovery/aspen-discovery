<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

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
	public $greenhouseId;
	public $currentVersion;
	public $remoteUpdate;

	public static function getObjectStructure($context = ''): array {
		global $interface;
		$currentRelease = $interface->getVariable('aspenVersion');
		if (str_contains($currentRelease, ' ')) {
			$currentRelease = substr($currentRelease, 0, strpos($currentRelease, ' '));
		}
		$updateTypes = [
			'patch' => 'Patch',
			'complete' => 'Complete',
		];

		$statuses = [
			'pending' => 'Pending',
			'started' => 'Started',
			'canceled' => 'Canceled',
			'failed' => 'Failed',
			'complete' => 'Complete',
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
				$eligibleReleases[$release['version']] = $release['version'];
			}
		}

		$structure = [
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
			'siteName' => [
				'property' => 'siteName',
				'type' => 'label',
				'label' => 'Site',
				'description' => '',
			],
			'greenhouseId' => [
				'property' => 'greenhouseId',
				'type' => 'text',
				'label' => 'Greenhouse Update Id',
				'description' => 'The unique update id from Greenhouse',
				'readOnly' => true,
				'hideInLists' => true,
			],
			'updateType' => [
				'property' => 'updateType',
				'type' => 'enum',
				'label' => 'Update Type',
				'values' => $updateTypes,
				'description' => 'The type of update (patch or complete)',
			],
			'remoteUpdate' => [
				'property' => 'remoteUpdate',
				'type' => 'checkbox',
				'label' => 'Remote Update',
				'description' => 'Whether this update is scheduled on a remote server or this server',
				'default' => 0,
				'readOnly' => true
			],
			'updateToVersion' => [
				'property' => 'updateToVersion',
				'type' => 'enum',
				'label' => 'Update to Version',
				'values' => $eligibleReleases,
				'description' => 'The version the update will upgrade to',
				'required' => true,
			],
			'dateScheduled' => [
				'property' => 'dateScheduled',
				'type' => 'timestamp',
				'label' => 'Date Scheduled',
				'description' => 'When the update was scheduled to run',
				'required' => true,
				'default' => time()
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'label' => 'Status',
				'values' => $statuses,
				'description' => 'The status of the update',
				'default' => 'pending',
				'readOnly' => true,
			],
//			'currentVersion' => [
//				'property' => 'currentVersion',
//				'type' => 'text',
//				'label' => 'Current Version',
//				'default' => $currentRelease,
//				'readOnly' => true,
//			],
			'dateRun' => [
				'property' => 'dateRun',
				'type' => 'timestamp',
				'label' => 'Date Run',
				'description' => 'When the update actually ran',
				'default' => null,
				'readOnly' => true,
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'textarea',
				'label' => 'Notes',
				'description' => 'Notes from when the update ran',
				'hideInLists' => true,
				'readOnly' => true,
			],
		];

		if ($context == 'addNew') {
			unset($structure['greenhouseId']);
			$structure['status']['type'] = 'hidden';
			unset($structure['remoteUpdate']);
			unset($structure['dateRun']);
			unset($structure['notes']);
		}

		if($context != 'addNew') {
			// only force dropdown if trying to add new update
			$structure['updateToVersion']['type'] = 'text';
		}
		return $structure;
	}

	function __get($name) {
		if($name == 'siteName') {
			if (empty($this->_data['siteName'])) {
				$aspenSite = new AspenSite();
				$aspenSite->id = $this->siteId;
				if($aspenSite->find(true)) {
					$this->_data['siteName'] = $aspenSite->name;
				} else {
					$this->_data['siteName'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}
			}
		}
		return $this->_data[$name] ?? null;
	}

	public function getNumericColumnNames(): array {
		return [
			'id',
			'siteId',
			'greenhouseId',
			'remoteUpdate'
		];
	}
}