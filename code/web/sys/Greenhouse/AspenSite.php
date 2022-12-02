<?php


class AspenSite extends DataObject {
	public $__table = 'aspen_sites';
	public $id;
	public $name;
	public $baseUrl;
	public $internalServerName;
	public $siteType;
	public $libraryType;
	public $libraryServes;
	public $timezone;
	public $implementationStatus;
	public $hosting;
	public $appAccess;
	public $operatingSystem;
	public $ils;
	public $notes;
	public $version;
	public $sendErrorNotificationsTo;
	public $lastNotificationTime;
	public $contractSigningDate;
	public $goLiveDate;
	public $contactFrequency;
	public $lastContacted;
	public $nextMeetingDate;
	public $nextMeetingPerson;
	public $activeTicketFeed;
	public $lastOfflineTime;
	public $lastOnlineTime;
	public $lastOfflineNote;
	public $isOnline;
	//public $jointAspenKohaImplementation;
	//public $ilsMigration;

	//public $implementationSpecialist;

	public static $_siteTypes = [
		0 => 'Library Partner',
		1 => 'Library Partner Test',
		2 => 'Demo',
		3 => 'Test',
	];
	public static $_implementationStatuses = [
		0 => 'Installing',
		1 => 'Implementing',
		2 => 'Soft Launch',
		3 => 'Production',
		4 => 'Retired',
	];
	public static $_appAccess = [
		0 => 'None',
		1 => 'LiDA Only',
		2 => 'Whitelabel Only',
		3 => 'LiDA + Whitelabel',
	];
	public static $_validIls = [
		0 => 'Not Set',
		1 => 'Koha',
		2 => 'CARL.X',
		3 => 'Evergreen',
		8 => 'Evolve',
		4 => 'Millennium',
		5 => 'Polaris',
		6 => 'Sierra',
		7 => 'Symphony',
	];
	public static $_contactFrequency = [
		0 => 'Weekly',
		1 => 'Bi-Monthly',
		2 => 'Monthly',
		3 => 'Quarterly',
		4 => 'Every 6 Months',
		5 => 'Yearly',
	];
	public static $_timezones = [
		0 => 'Unknown',
		10 => 'Eastern',
		12 => 'Central',
		14 => 'Mountain',
		16 => 'Arizona',
		18 => 'Pacific',
	];

	public function getNumericColumnNames(): array {
		return [
			'siteType',
			'libraryType',
			'libraryServes',
			'implementationStatus',
			'appAccess',
			'ils',
		];
	}

	public static function getObjectStructure(): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the website to index',
				'maxLength' => 50,
				'required' => true,
			],
			'internalServerName' => [
				'property' => 'internalServerName',
				'type' => 'text',
				'label' => 'Internal Server Name',
				'description' => 'The internal server name',
				'maxLength' => 50,
				'required' => false,
			],
			'siteType' => [
				'property' => 'siteType',
				'type' => 'enum',
				'values' => AspenSite::$_siteTypes,
				'label' => 'Type of Server',
				'description' => 'The type of server',
				'required' => true,
				'default' => 0,
			],
			'timezone' => [
				'property' => 'timezone',
				'type' => 'enum',
				'values' => AspenSite::$_timezones,
				'label' => 'Timezone',
				'description' => 'The timezone of the library',
				'required' => true,
				'default' => 0,
			],
			'libraryType' => [
				'property' => 'libraryType',
				'type' => 'enum',
				'values' => [
					0 => 'Single branch library',
					1 => 'Multi-branch library',
					2 => 'Consortia - Central Admin',
					3 => 'Consortia - Member Admin',
					4 => 'Consortia - Hybrid Admin',
				],
				'label' => 'Type of Library',
				'description' => 'The type of server',
				'required' => true,
				'default' => 0,
			],
			'libraryServes' => [
				'property' => 'libraryServes',
				'type' => 'enum',
				'values' => [
					0 => 'Public',
					1 => 'Academic',
					2 => 'Schools',
					3 => 'Special',
					4 => 'Mixed',
				],
				'label' => 'Library Serves...',
				'description' => 'Who the library primarily serves',
				'required' => true,
				'default' => 0,
			],
			'implementationStatus' => [
				'property' => 'implementationStatus',
				'type' => 'enum',
				'values' => AspenSite::$_implementationStatuses,
				'label' => 'Implementation Status',
				'description' => 'The status of implementation',
				'required' => true,
				'default' => 0,
			],
			'contractSigningDate' => [
				'property' => 'contractSigningDate',
				'type' => 'date',
				'label' => 'Contract Signing Date',
				'description' => 'When the library initially signed their contract.',
				'hideInLists' => false,
			],
			'goLiveDate' => [
				'property' => 'goLiveDate',
				'type' => 'date',
				'label' => 'Go Live Date',
				'description' => 'When the library went live (or projects to go live).',
				'hideInLists' => false,
			],
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'url',
				'label' => 'Site URL',
				'description' => 'The URL to the Website',
				'maxLength' => 255,
				'required' => false,
			],
			'hosting' => [
				'property' => 'hosting',
				'type' => 'text',
				'label' => 'Hosting',
				'description' => 'What hosting the site is on',
				'maxLength' => 75,
				'required' => false,
			],
			'appAccess' => [
				'property' => 'appAccess',
				'type' => 'enum',
				'values' => AspenSite::$_appAccess,
				'label' => 'App Access Level',
				'description' => 'The level of access to the Aspen app that the library has',
				'required' => true,
				'default' => 0,
			],
			'ils' => [
				'property' => 'ils',
				'type' => 'enum',
				'values' => AspenSite::$_validIls,
				'label' => 'ILS',
				'description' => 'The ils used by the library',
				'required' => true,
				'default' => 0,
			],
			'operatingSystem' => [
				'property' => 'operatingSystem',
				'type' => 'text',
				'label' => 'Operating System',
				'description' => 'What operating system the site is on',
				'maxLength' => 75,
				'required' => false,
			],
			'activeTicketFeed' => [
				'property' => 'activeTicketFeed',
				'type' => 'url',
				'label' => 'Active Ticket Feed',
				'description' => 'The URL to get a list of all active tickets for an instance',
				'maxLength' => 1000,
				'required' => false,
				'hideInLists' => true,
			],
			'contactFrequency' => [
				'property' => 'contactFrequency',
				'type' => 'enum',
				'values' => AspenSite::$_contactFrequency,
				'label' => 'Contact Frequency',
				'description' => 'How often we want to contact the library',
				'required' => true,
				'default' => 3,
			],
			'lastContacted' => [
				'property' => 'lastContacted',
				'type' => 'date',
				'label' => 'Last Contacted',
				'description' => 'When the library was last contacted.',
				'hideInLists' => false,
			],
			'nextMeetingDate' => [
				'property' => 'nextMeetingDate',
				'type' => 'date',
				'label' => 'Next Meeting Date',
				'description' => 'When we want to talk to the library next.',
				'hideInLists' => false,
			],
			'nextMeetingPerson' => [
				'property' => 'nextMeetingPerson',
				'type' => 'text',
				'label' => 'Next meeting person',
				'description' => 'Who will meet with the library next.',
				'hideInLists' => false,
			],
			'notes' => [
				'property' => 'notes',
				'type' => 'textarea',
				'label' => 'Notes',
				'description' => 'Notes on the site.',
				'hideInLists' => true,
			],
			'lastNotificationTime' => [
				'property' => 'lastNotificationTime',
				'type' => 'timestamp',
				'label' => 'Last Notification Time',
				'description' => 'When the last alert was sent.',
				'hideInLists' => false,
			],
			'isOnline' => [
				'property' => 'isOnline',
				'type' => 'label',
				'label' => 'Server is online',
				'description' => 'Whether or not the server is online.',
				'hideInLists' => false,
			],
			'lastOfflineTime' => [
				'property' => 'lastOfflineTime',
				'type' => 'timestamp',
				'label' => 'Last Offline Time',
				'description' => 'When the last time the site was offline.',
			],
			'lastOfflineNote' => [
				'property' => 'lastOfflineNote',
				'type' => 'textarea',
				'label' => 'Last Offline Note',
				'description' => 'Note for when the site was last offline.',
			],
			'lastOnlineTime' => [
				'property' => 'lastOnlineTime',
				'type' => 'timestamp',
				'label' => 'Last Online Time',
				'description' => 'When the last time the site was online.',
			],
		];
	}

	public function updateStatus() {
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$status = $this->toArray();

		$curlWrapper = new CurlWrapper();
		$curlWrapper->setTimeout(5);
		$this->lastOfflineNote = '';
		if (!empty($this->baseUrl)) {
			$statusUrl = $this->baseUrl . '/API/SearchAPI?method=getIndexStatus';
			$retry = true;
			$numTries = 0;
			while ($retry == true) {
				$retry = false;
				$numTries++;
				try {
					$statusRaw = $curlWrapper->curlGetPage($statusUrl);
					$responseCode = $curlWrapper->getResponseCode();
					if ($responseCode != 200) {
						//We might get a better response if we retry.
						$canRetry = true;
						if ($responseCode == 403) {
							$this->lastOfflineNote = "Got a response code of " . $curlWrapper->getResponseCode() . " can't monitor status for this server.";
							$canRetry = false;
						} elseif ($responseCode == 0) {
							$this->lastOfflineNote = "Got a response code of " . $curlWrapper->getResponseCode() . " could not connect to the server.";
						} else {
							$this->lastOfflineNote = "Got a response code of " . $curlWrapper->getResponseCode() . ".";
						}
						$retry = $canRetry && ($numTries <= 2);
						if (!$retry) {
							$status['alive'] = false;
							$status['checks'] = [];
							$status['wasOffline'] = false;
							$this->isOnline = 0;

							if ((time() - $this->lastOfflineTime) > 4 * 60 * 60) {
								$this->lastOfflineTime = time();
							}
						}
					} else {
						$statusJson = json_decode($statusRaw, true);
						if (empty($statusJson)) {
							$retry = ($numTries <= 2);
							if (!$retry) {
								$status['alive'] = false;
								$status['checks'] = [];
								$status['wasOffline'] = false;
								$this->isOnline = 0;

								if ((time() - $this->lastOfflineTime) > 4 * 60 * 60) {
									$this->lastOfflineTime = time();
								}
								$this->lastOfflineNote = "JSON data is not available";
							}
						} else {
							$status['alive'] = true;
							$status = array_merge($status, $statusJson['result']);

							//Update logging for CPU usage, memory usage, and general site stats
							$now = time();
							$twoWeeksAgo = $now - 2 * 7 * 24 * 60 * 60;
							require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCpuUsage.php';
							$cpuUsage = new AspenSiteCpuUsage();
							//delete anything more than 2 weeks old
							$cpuUsage->whereAdd();
							$cpuUsage->aspenSiteId = $this->id;
							$cpuUsage->whereAdd('timestamp < ' . $twoWeeksAgo);
							$cpuUsage->delete(true);
							$cpuUsage = new AspenSiteCpuUsage();
							$cpuUsage->aspenSiteId = $this->id;
							$cpuUsage->timestamp = $now;
							$loadPerCpu = (float)$status['serverStats']['load_per_cpu']['value'];
							$cpuUsage->loadPerCpu = $loadPerCpu;
							$cpuUsage->insert();

							require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteMemoryUsage.php';
							$memoryUsage = new AspenSiteMemoryUsage();
							//delete anything more than 2 weeks old
							$memoryUsage->whereAdd();
							$memoryUsage->aspenSiteId = $this->id;
							$memoryUsage->whereAdd('timestamp < ' . $twoWeeksAgo);
							$memoryUsage->delete(true);
							$memoryUsage = new AspenSiteMemoryUsage();
							$memoryUsage->aspenSiteId = $this->id;
							$memoryUsage->timestamp = $now;
							$memoryUsage->percentMemoryUsage = $status['serverStats']['percent_memory_in_use']['value'];
							$totalMemory = StringUtils::unformatBytes($status['serverStats']['total_memory']['value']) / (1024 * 1024 * 1024);
							$memoryUsage->totalMemory = $totalMemory;
							$availableMemory = StringUtils::unformatBytes($status['serverStats']['available_memory']['value']) / (1024 * 1024 * 1024);
							$memoryUsage->availableMemory = $availableMemory;
							$memoryUsage->insert();

							require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteWaitTime.php';
							$waitTime = new AspenSiteWaitTime();
							//delete anything more than 2 weeks old
							$waitTime->whereAdd();
							$waitTime->aspenSiteId = $this->id;
							$waitTime->whereAdd('timestamp < ' . $twoWeeksAgo);
							$waitTime->delete(true);
							$waitTime = new AspenSiteWaitTime();
							$waitTime->aspenSiteId = $this->id;
							$waitTime->timestamp = $now;
							$waitTimeVal = (float)$status['serverStats']['wait_time']['value'];
							$waitTime->waitTime = $waitTimeVal;
							$waitTime->insert();

							//Update daily stats
							require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteStat.php';
							$aspenSiteStat = new AspenSiteStat();
							$aspenSiteStat->year = date('Y');
							$aspenSiteStat->month = date('n');
							$aspenSiteStat->day = date('j');
							$aspenSiteStat->aspenSiteId = $this->id;
							if ($aspenSiteStat->find(true)) {
								$foundStats = true;
							} else {
								$foundStats = false;
							}
							$statsChanged = false;
							$dataDiskSpace = StringUtils::unformatBytes($status['serverStats']['data_disk_space']['value']) / (1024 * 1024 * 1024);
							if (!$foundStats || $dataDiskSpace < $aspenSiteStat->minDataDiskSpace) {
								$aspenSiteStat->minDataDiskSpace = $dataDiskSpace;
								$statsChanged = true;
							}
							$usrDiskSpace = StringUtils::unformatBytes($status['serverStats']['usr_disk_space']['value']) / (1024 * 1024 * 1024);
							if (!$foundStats || $usrDiskSpace < $aspenSiteStat->minUsrDiskSpace) {
								$aspenSiteStat->minUsrDiskSpace = $usrDiskSpace;
								$statsChanged = true;
							}
							if (!$foundStats || $availableMemory < $aspenSiteStat->minAvailableMemory) {
								$aspenSiteStat->minAvailableMemory = $availableMemory;
								$statsChanged = true;
							}
							if (!$foundStats || $availableMemory > $aspenSiteStat->maxAvailableMemory) {
								$aspenSiteStat->maxAvailableMemory = $availableMemory;
								$statsChanged = true;
							}
							if (!$foundStats || $loadPerCpu < $aspenSiteStat->minLoadPerCPU) {
								$aspenSiteStat->minLoadPerCPU = $loadPerCpu;
								$statsChanged = true;
							}
							if (!$foundStats || $loadPerCpu > $aspenSiteStat->maxLoadPerCPU) {
								$aspenSiteStat->maxLoadPerCPU = $loadPerCpu;
								$statsChanged = true;
							}
							$waitTime = (float)$status['serverStats']['wait_time']['value'];
							if (!$foundStats || $waitTime > $aspenSiteStat->maxWaitTime) {
								$aspenSiteStat->maxWaitTime = $waitTime;
								$statsChanged = true;
							}

							if (!$foundStats) {
								$aspenSiteStat->insert();
							} elseif ($statsChanged) {
								$aspenSiteStat->update();
							}

							if ($this->isOnline == 0) {
								$status['wasOffline'] = true;
							} else {
								$status['wasOffline'] = false;
							}

							$this->isOnline = 1;
							$this->lastOnlineTime = time();
						}
						$this->update();
					}
				} catch (Exception $e) {
					$retry = ($numTries <= 2);
					if (!$retry) {
						$status['alive'] = false;
						$status['checks'] = [];
						$status['wasOffline'] = false;
						$this->isOnline = 0;
						$this->lastOfflineNote = "Unable to connect to server";
						if ((time() - $this->lastOfflineTime) > 4 * 60 * 60) {
							$this->lastOfflineTime = time();
						}
						$this->update();
					}
				}
				if ($retry) {
					sleep(5);
				}
			}
		} else {
			$status['alive'] = false;
			$status['checks'] = [];
			$status['wasOffline'] = false;
			$this->isOnline = 0;
			$this->lastOfflineNote = "Base URL not set";
			if ((time() - $this->lastOfflineTime) > 4 * 60 * 60) {
				$this->lastOfflineTime = time();
			}
			$this->update();
		}

		return $status;
	}

	public function getCachedStatus() {
		$status = $this->toArray();
		if (!empty($this->baseUrl)) {
			$status['checks'] = [];
			require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCheck.php';
			$statusChecks = new AspenSiteCheck();
			$statusChecks->siteId = $this->id;
			$statusChecks->orderBy('checkName');
			$statusChecks->find();
			$hasCriticalErrors = false;
			$hasWarnings = false;
			while ($statusChecks->fetch()) {
				$note = $statusChecks->currentNote;

				$statusValue = 'okay';
				if ($statusChecks->currentStatus == 2) {
					$hasCriticalErrors = true;
					$statusValue = 'critical';
					$note .= ' for ' . $this->getElapsedTime($statusChecks->lastErrorTime);
				} elseif ($statusChecks->currentStatus == 1) {
					$hasWarnings = true;
					$statusValue = 'warning';
					$note .= ' for ' . $this->getElapsedTime($statusChecks->lastWarningTime);
				}
				$checkName = str_replace(' ', '_', strtolower($statusChecks->checkName));

				$status['checks'][$checkName] = [
					'name' => $statusChecks->checkName,
					'status' => $statusValue,
					'note' => $note,
					'url' => $statusChecks->getUrl($this),
				];
			}
			if ($hasCriticalErrors) {
				$status['aspen_health_status'] = 'critical';
			} elseif ($hasWarnings) {
				$status['aspen_health_status'] = 'warning';
			} else {
				$status['aspen_health_status'] = 'okay';
			}
		} else {
			$status['checks'] = [];
		}

		return $status;
	}

	function getElapsedTime($time) {
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
		$version = translate([
			'text' => 'Unknown',
			'isAdminFacing' => true,
		]);
		if (!empty($this->baseUrl)) {
			$versionUrl = $this->baseUrl . '/API/SystemAPI?method=getCurrentVersion';
			try {
				$versionRaw = @file_get_contents($versionUrl);
				if ($versionRaw) {
					$versionJson = json_decode($versionRaw, true);
					if ($versionJson && isset($versionJson['result'])) {
						$version = $versionJson['result']['version'];
						if ($version != $this->version) {
							$this->version = $version;
							$this->update();
						}
					}
				}
			} catch (Exception $e) {
				//Ignore for now
			}
		}
		return $version;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		$return['implementationStatus'] = AspenSite::$_implementationStatuses[$this->implementationStatus];
		$return['timezone'] = AspenSite::$_timezones[$this->timezone];
		return $return;
	}

	public function getImplementationStatusName() {
		return AspenSite::$_implementationStatuses[$this->implementationStatus];
	}

	public function getSiteTypeName() {
		return AspenSite::$_siteTypes[$this->siteType];
	}

	public function getTimezoneName() {
		return AspenSite::$_timezones[$this->timezone];
	}
}