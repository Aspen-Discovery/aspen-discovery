<?php


class AspenSite extends DataObject
{
	public $__table = 'aspen_sites';
	public $id;
	public $name;
	public $baseUrl;
	public $internalServerName;
	public $siteType;
	public $libraryType;
	public $libraryServes;
	public $implementationStatus;
	public $hosting;
	public $appAccess;
	public $operatingSystem;
	public $ils;
	public $notes;
	public $version;
	public $sendErrorNotificationsTo;
	public $lastNotificationTime;

	public static $_implementationStatuses = [0 => 'Installing', 1 => 'Implementing', 2 => 'Soft Launch', 3 => 'Production', 4 => 'Retired'];
	public static $_appAccess = [0 => 'None', 1 => 'LiDA Only', 2 => 'Whitelabel Only', 3 => 'LiDA + Whitelabel'];

	public static function getObjectStructure() : array {
		return [
			'id' => ['property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'],
			'name' => ['property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The name of the website to index', 'maxLength'=>50, 'required' => true],
			'internalServerName' => ['property'=>'internalServerName', 'type'=>'text', 'label'=>'Internal Server Name', 'description'=>'The internal server name', 'maxLength'=>50, 'required' => false],
			'siteType' => ['property'=>'siteType', 'type'=>'enum', 'values' => [0 => 'Library Partner', 1 => 'Library Partner Test', 2 => 'Demo', 3 => 'Test'], 'label'=>'Type of Server', 'description'=>'The type of server', 'required' => true, 'default' => 0],
			'libraryType' => ['property'=>'libraryType', 'type'=>'enum', 'values' => [0 => 'Single branch library', 1 => 'Multi-branch library', 2 => 'Consortia - Central Admin', 3 => 'Consortia - Member Admin', 4 => 'Consortia - Hybrid Admin'], 'label'=>'Type of Library', 'description'=>'The type of server', 'required' => true, 'default' => 0],
			'libraryServes' => ['property'=>'libraryServes', 'type'=>'enum', 'values' => [0 => 'Public', 1 => 'Academic', 2 => 'Schools', 3 => 'Special', 4 => 'Mixed'], 'label'=>'Library Serves...', 'description'=>'Who the library primarily serves', 'required' => true, 'default' => 0],
			'implementationStatus' => ['property'=>'implementationStatus', 'type'=>'enum', 'values' => AspenSite::$_implementationStatuses, 'label'=>'Implementation Status', 'description'=>'The status of implementation', 'required' => true, 'default' => 0],
			'baseUrl' => ['property'=>'baseUrl', 'type'=>'url', 'label'=>'Site URL', 'description'=>'The URL to the Website', 'maxLength'=>255, 'required' => false],
			'hosting' => ['property'=>'hosting', 'type'=>'text', 'label'=>'Hosting', 'description'=>'What hosting the site is on', 'maxLength'=>75, 'required' => false],
			'appAccess' => ['property'=>'appAccess', 'type'=>'enum', 'values' => AspenSite::$_appAccess, 'label'=>'App Access Level', 'description'=>'The level of access to the Aspen app that the library has', 'required' => true, 'default' => 0],
			'operatingSystem' => ['property'=>'operatingSystem', 'type'=>'text', 'label'=>'Operating System', 'description'=>'What operating system the site is on', 'maxLength'=>75, 'required' => false],
			'notes' => ['property' => 'notes', 'type'=>'textarea', 'label'=>'Notes', 'description'=>'Notes on the site.', 'hideInLists' => true],
			'lastNotificationTime' => ['property' => 'lastNotificationTime', 'type'=>'timestamp', 'label'=>'Last Notification Time', 'description'=>'When the last alert was sent.', 'hideInLists' => false],
		];
	}

	public function updateStatus() {
		$status = $this->toArray();
		if (!empty($this->baseUrl)){
			$statusUrl = $this->baseUrl . '/API/SearchAPI?method=getIndexStatus';
			try {
				$statusRaw = file_get_contents($statusUrl);
				if ($statusRaw) {
					$statusJson = json_decode($statusRaw, true);
					$status['alive'] = true;
					$status = array_merge($status, $statusJson['result']);
				}else {
					$status['alive'] = false;
					$status['checks'] = [];
				}
			}catch (Exception $e) {
				$status['alive'] = false;
				$status['checks'] = [];
			}
		}else{
			$status['alive'] = false;
			$status['checks'] = [];
		}

		return $status;
	}

	public function getCachedStatus() {
		$status = $this->toArray();
		if (!empty($this->baseUrl)){
			$status['checks'] = [];
			require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCheck.php';
			$statusChecks = new AspenSiteCheck();
			$statusChecks->siteId = $this->id;
			$statusChecks->orderBy('checkName');
			$statusChecks->find();
			$hasCriticalErrors = false;
			$hasWarnings = false;
			while ($statusChecks->fetch()){
				$note = $statusChecks->currentNote;

				$statusValue = 'okay';
				if ($statusChecks->currentStatus == 2){
					$hasCriticalErrors = true;
					$statusValue = 'critical';
					$note .= ' for ' . $this->getElapsedTime($statusChecks->lastErrorTime);
				}else if ($statusChecks->currentStatus == 1){
					$hasWarnings = true;
					$statusValue = 'warning';
					$note .= ' for ' . $this->getElapsedTime($statusChecks->lastWarningTime);
				}
				$checkName = str_replace(' ', '_', strtolower($statusChecks->checkName));

				$status['checks'][$checkName] = [
					'name' => $statusChecks->checkName,
					'status' => $statusValue,
					'note' => $note
				];
			}
			if ($hasCriticalErrors){
				$status['aspen_health_status'] = 'critical';
			}elseif ($hasWarnings){
				$status['aspen_health_status'] = 'warning';
			}else{
				$status['aspen_health_status'] = 'okay';
			}
		}else{
			$status['checks'] = [];
		}

		return $status;
	}

	function getElapsedTime($time)
	{
		$elapsedTimeMin = ceil((time() - $time) / 60);
		if ($elapsedTimeMin < 60) {
			return $elapsedTimeMin . " min";
		} else {
			$hours = floor($elapsedTimeMin / 60);
			$minutes = $elapsedTimeMin - (60 * $hours);
			return "$hours hours, $minutes min";
		}
	}

	public function getCurrentVersion() {
		$version = translate(['text'=>'Unknown','isAdminFacing'=>true]);
		if (!empty($this->baseUrl)){
			$versionUrl = $this->baseUrl . '/API/SystemAPI?method=getCurrentVersion';
			try {
				$versionRaw = @file_get_contents($versionUrl);
				if ($versionRaw) {
					$versionJson = json_decode($versionRaw, true);
					if ($versionJson && isset($versionJson['result'])) {
						$version = $versionJson['result']['version'];
						if ($version != $this->version){
							$this->version = $version;
							$this->update();
						}
					}
				}
			}catch (Exception $e){
				//Ignore for now
			}
		}
		return $version;
	}

	public function toArray(): array
	{
		$return = parent::toArray();
		$return['implementationStatus'] = AspenSite::$_implementationStatuses[$this->implementationStatus];
		return $return;
	}

	public function getImplementationStatusName(){
		return AspenSite::$_implementationStatuses[$this->implementationStatus];
	}
}