<?php
require_once ROOT_DIR . '/Action.php';

class EventAPI extends Action {
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getEventDetails',
				])) {
					header('Cache-Control: max-age=10800');
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('EventAPI', $method);
					$output = json_encode($this->$method());
				} else {
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('EventAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if (method_exists($this, $method)) {
				$output = json_encode(['result' => $this->$method()]);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('EventAPI', $method);
			} else {
				$output = json_encode(['error' => "invalid_method '$method'"]);
			}

			echo $output;
		} else {
			$this->forbidAPIAccess();
		}
	}

	/** @noinspection PhpUnused */
	function getEventDetails(): array {
		if (!isset($_REQUEST['id']) || !isset($_REQUEST['source'])) {
			return [
				'success' => false,
				'message' => 'Event id or source not provided'
			];
		}

		$source = $_REQUEST['source'];
		if($source == 'communico') {
			return $this->getCommunicoEventDetails();
		} else if ($source == 'library_calendar') {
			return $this->getLMEventDetails();
		} else if ($source == 'springshare') {
			return $this->getSpringshareEventDetails();
		} else {
			return [
				'success' => false,
				'message' => 'This event source is not supported.',
			];
		}
	}

	function getLMEventDetails(): array {
		require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
		$libraryCalendarDriver = new LibraryCalendarEventRecordDriver($_REQUEST['id']);
		if($libraryCalendarDriver->isValid()) {
			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $libraryCalendarDriver->getTitle();
			$itemData['isAllDay'] = (bool)$libraryCalendarDriver->isAllDayEvent();
			$itemData['startDate'] = $libraryCalendarDriver->getStartDate();
			$itemData['endDate'] = $libraryCalendarDriver->getEndDate();
			$itemData['description'] = strip_tags($libraryCalendarDriver->getDescription());
			$itemData['registrationRequired'] = $libraryCalendarDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['registrationBody'] = strip_tags($libraryCalendarDriver->getRegistrationModalBody());
			$itemData['bypass'] = (bool)$libraryCalendarDriver->getBypassSetting();
			$itemData['cover'] = $libraryCalendarDriver->getEventCoverUrl();
			$itemData['url'] = $libraryCalendarDriver->getExternalUrl();
			$itemData['audiences'] = $libraryCalendarDriver->getAudiences();
			$itemData['categories'] = null;
			$itemData['programTypes'] = null;

			$itemData['location'] = $this->getDiscoveryBranchDetails($libraryCalendarDriver->getBranch());

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
			}

			return $itemData;
		}
		return [
			'success' => false,
			'message' => 'Event id not valid',
		];
	}

	function getCommunicoEventDetails(): array {
		require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
		$communicoDriver = new CommunicoEventRecordDriver($_REQUEST['id']);
		if($communicoDriver->isValid()) {
			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $communicoDriver->getTitle();
			$itemData['isAllDay'] = (bool)$communicoDriver->isAllDayEvent();
			$itemData['startDate'] = $communicoDriver->getStartDate();
			$itemData['endDate'] = $communicoDriver->getEndDate();
			$itemData['description'] = strip_tags($communicoDriver->getDescription());
			$itemData['registrationRequired'] = $communicoDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['registrationBody'] = strip_tags($communicoDriver->getRegistrationModalBody());
			$itemData['bypass'] = (bool)$communicoDriver->getBypassSetting();
			$itemData['cover'] = $communicoDriver->getEventCoverUrl();
			$itemData['url'] = $communicoDriver->getExternalUrl();
			$itemData['audiences'] = $communicoDriver->getAudiences();
			$itemData['categories'] = null;
			$itemData['programTypes'] = $communicoDriver->getProgramTypes();
			$itemData['location'] = $this->getDiscoveryBranchDetails($communicoDriver->getBranch());

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
			}

			return $itemData;
		}
		return [
			'success' => false,
			'message' => 'Event id not valid',
		];
	}

	function getSpringshareEventDetails(): array {
		require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
		$springshareDriver = new SpringshareLibCalEventRecordDriver($_REQUEST['id']);
		if($springshareDriver->isValid()) {
			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $springshareDriver->getTitle();
			$itemData['isAllDay'] = (bool)$springshareDriver->isAllDayEvent();
			$itemData['startDate'] = $springshareDriver->getStartDate();
			$itemData['endDate'] = $springshareDriver->getEndDateString();
			$itemData['description'] = strip_tags($springshareDriver->getDescription());
			$itemData['registrationRequired'] = $springshareDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['registrationBody'] = strip_tags($springshareDriver->getRegistrationModalBody());
			$itemData['bypass'] = (bool)$springshareDriver->getBypassSetting();
			$itemData['cover'] = $springshareDriver->getEventCoverUrl();
			$itemData['url'] = $springshareDriver->getExternalUrl();
			$itemData['audiences'] = $springshareDriver->getAudiences();
			$itemData['categories'] = $springshareDriver->getCategories();
			$itemData['programTypes'] = null;

			$itemData['location'] = $this->getDiscoveryBranchDetails($springshareDriver->getBranch());

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
			}

			return $itemData;
		}
		return [
			'success' => false,
			'message' => 'Event id not valid',
		];
	}

	function getDiscoveryBranchDetails($locationName = '') {
		if ($locationName) {
			$eventLocation = new Location();
			$eventLocation->displayName = $locationName;
			if($eventLocation->find(true)) {
				return [
					'name' => $eventLocation->displayName,
					'address' => $eventLocation->address,
					'phone' => $eventLocation->phone,
					'coordinates' => [
						'latitude' => $eventLocation->latitude,
						'longitude' => $eventLocation->longitude,
					]
				];
			}

			require_once ROOT_DIR . '/sys/Events/EventsBranchMapping.php';
			$locationMap = new EventsBranchMapping();
			$locationMap->eventsLocation = $locationName;
			if($locationMap->find(true)) {
				$eventLocation = new Location();
				$eventLocation->locationId = $locationMap->locationId;
				if($eventLocation->find(true)) {
					return [
						'name' => $eventLocation->displayName,
						'address' => $eventLocation->address,
						'phone' => $eventLocation->phone,
						'coordinates' => [
							'latitude' => $eventLocation->latitude,
							'longitude' => $eventLocation->longitude,
						]
					];
				}
			}
		}
		return [
			'name' => $locationName,
			'address' => '',
			'phone' => '',
			'coordinates' => [
				'latitude' => 0,
				'longitude' => 0,
			]
		];
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	private function loadUsernameAndPassword() {
		$username = $_REQUEST['username'] ?? '';
		$password = $_REQUEST['password'] ?? '';

		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = $_POST['password'];
		}

		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return [$username, $password];
	}

	/**
	 * @return bool|User
	 */
	protected function getUserForApiCall() {
		$user = false;
		[$username, $password] = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user !== false && $user->source == 'admin') {
			return false;
		}
		return $user;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}