<?php
require_once ROOT_DIR . '/services/API/AbstractAPI.php';

class EventAPI extends AbstractAPI {
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
					'saveEvent',
					'removeSavedEvent',
					'getSavedEvents'
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

	/**
	 * Returns specific data about a given event. If user credentials are provided, specific data on if they've saved or registered for the event will be provided (validated with getUserForApiCall).
	 *
	 * Parameters:
	 * <ul>
	 * <li>id - The full id of the event to search for</li>
	 * <li>source - The event vendor/source tied to the specific event (i.e. communico, springshare, or library_calendar)</li>
	 * </ul>
	 *
	 */

	/* @noinspection PhpUnused */
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
		} else if ($source == 'assabet') {
			return $this->getAssabetEventDetails();
		} else if ($source == 'aspenEvents') {
			return $this->getAspenEventDetails();
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
			$registrationInformation = null;
			if($libraryCalendarDriver->getRegistrationModalBodyForAPI()) {
				$registrationInformation = strip_tags($libraryCalendarDriver->getRegistrationModalBodyForAPI());
			} elseif ($libraryCalendarDriver->getRegistrationModalBody()) { // use the regular registration modal as a backup if needed
				$registrationInformation = strip_tags($libraryCalendarDriver->getRegistrationModalBody());
			}

			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $libraryCalendarDriver->getTitle();
			$itemData['isAllDay'] = (bool)$libraryCalendarDriver->isAllDayEvent();
			$itemData['startDate'] = $libraryCalendarDriver->getStartDate();
			$itemData['endDate'] = $libraryCalendarDriver->getEndDate();
			$itemData['description'] = strip_tags($libraryCalendarDriver->getDescription());
			$itemData['registrationRequired'] = $libraryCalendarDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['inUserEvents'] = false;
			$itemData['registrationBody'] = $registrationInformation;
			$itemData['bypass'] = (bool)$libraryCalendarDriver->getBypassSetting();
			$itemData['cover'] = $libraryCalendarDriver->getEventCoverUrl();
			$itemData['url'] = $libraryCalendarDriver->getExternalUrl();
			$itemData['audiences'] = $libraryCalendarDriver->getAudiences();
			$itemData['categories'] = null;
			$itemData['programTypes'] = null;
			$itemData['room'] = null;

			// check if event has passed
			$today = new DateTime('now');
			$eventDay = $libraryCalendarDriver->getStartDate();
			$itemData['pastEvent'] = $today >= $eventDay;

			$itemData['location'] = $this->getDiscoveryBranchDetails($libraryCalendarDriver->getBranch());
			$itemData['canAddToList'] = false;

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
				$itemData['inUserEvents'] = $user->inUserEvents($_REQUEST['id']);
				$itemData['canAddToList'] = $user->isAllowedToAddEventsToList($libraryCalendarDriver->getSource());
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
			$registrationInformation = null;
			if($communicoDriver->getRegistrationModalBodyForAPI()) {
				$registrationInformation = strip_tags($communicoDriver->getRegistrationModalBodyForAPI());
			} elseif ($communicoDriver->getRegistrationModalBody()) { // use the regular registration modal as a backup if needed
				$registrationInformation = strip_tags($communicoDriver->getRegistrationModalBody());
			}

			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $communicoDriver->getTitle();
			$itemData['isAllDay'] = (bool)$communicoDriver->isAllDayEvent();
			$itemData['startDate'] = $communicoDriver->getStartDate();
			$itemData['endDate'] = $communicoDriver->getEndDate();
			$itemData['description'] = strip_tags($communicoDriver->getDescription());
			$itemData['registrationRequired'] = $communicoDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['inUserEvents'] = false;
			$itemData['registrationBody'] = $registrationInformation;
			$itemData['bypass'] = (bool)$communicoDriver->getBypassSetting();
			$itemData['cover'] = $communicoDriver->getEventCoverUrl();
			$itemData['url'] = $communicoDriver->getExternalUrl();
			$itemData['audiences'] = $communicoDriver->getAudiences();
			$itemData['categories'] = null;
			$itemData['programTypes'] = $communicoDriver->getProgramTypes();
			$itemData['room'] = $communicoDriver->getRoom();
			$itemData['location'] = $this->getDiscoveryBranchDetails($communicoDriver->getBranch());
			$itemData['canAddToList'] = false;

			// check if event has passed
			$difference = $communicoDriver->getStartDate()->diff(new DateTime());;
			$difference = (int)$difference->format('%a');
			$itemData['pastEvent'] = $difference < 0;

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
				$itemData['inUserEvents'] = $user->inUserEvents($_REQUEST['id']);
				$itemData['canAddToList'] = $user->isAllowedToAddEventsToList($communicoDriver->getSource());
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
			$registrationInformation = null;
			if($springshareDriver->getRegistrationModalBodyForAPI()) {
				$registrationInformation = strip_tags($springshareDriver->getRegistrationModalBodyForAPI());
			} elseif ($springshareDriver->getRegistrationModalBody()) { // use the regular registration modal as a backup if needed
				$registrationInformation = strip_tags($springshareDriver->getRegistrationModalBody());
			}
			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $springshareDriver->getTitle();
			$itemData['isAllDay'] = (bool)$springshareDriver->isAllDayEvent();
			$itemData['startDate'] = $springshareDriver->getStartDate();
			$itemData['endDate'] = $springshareDriver->getEndDate();
			$itemData['description'] = strip_tags($springshareDriver->getDescription());
			$itemData['registrationRequired'] = $springshareDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['inUserEvents'] = false;
			$itemData['registrationBody'] = $registrationInformation;
			$itemData['bypass'] = (bool)$springshareDriver->getBypassSetting();
			$itemData['cover'] = $springshareDriver->getEventCoverUrl();
			$itemData['url'] = $springshareDriver->getExternalUrl();
			$itemData['audiences'] = $springshareDriver->getAudiences();
			$itemData['categories'] = $springshareDriver->getCategories();
			$itemData['programTypes'] = null;
			$itemData['room'] = $springshareDriver->getRoom();
			$itemData['location'] = $this->getDiscoveryBranchDetails($springshareDriver->getBranch());
			$itemData['canAddToList'] = false;

			// check if event has passed
			$today = new DateTime('now');
			$eventDay = $springshareDriver->getStartDate();
			$itemData['pastEvent'] = $today >= $eventDay;

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
				$itemData['inUserEvents'] = $user->inUserEvents($_REQUEST['id']);
				$itemData['canAddToList'] = $user->isAllowedToAddEventsToList($springshareDriver->getSource());
			}

			return $itemData;
		}
		return [
			'success' => false,
			'message' => 'Event id not valid',
		];
	}

	function getAssabetEventDetails(): array {
		require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
		$assabetDriver = new AssabetEventRecordDriver($_REQUEST['id']);
		if($assabetDriver->isValid()) {
			$registrationInformation = null;
			if($assabetDriver->getRegistrationModalBodyForAPI()) {
				$registrationInformation = strip_tags($assabetDriver->getRegistrationModalBodyForAPI());
			} elseif ($assabetDriver->getRegistrationModalBody()) { // use the regular registration modal as a backup if needed
				$registrationInformation = strip_tags($assabetDriver->getRegistrationModalBody());
			}

			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $assabetDriver->getTitle();
			$itemData['isAllDay'] = (bool)$assabetDriver->isAllDayEvent();
			$itemData['startDate'] = $assabetDriver->getStartDate();
			$itemData['endDate'] = $assabetDriver->getEndDate();
			$itemData['description'] = strip_tags($assabetDriver->getDescription());
			$itemData['registrationRequired'] = $assabetDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['inUserEvents'] = false;
			$itemData['registrationBody'] = $registrationInformation;
			$itemData['bypass'] = (bool)$assabetDriver->getBypassSetting();
			$itemData['cover'] = $assabetDriver->getEventCoverUrl();
			$itemData['url'] = $assabetDriver->getExternalUrl();
			$itemData['audiences'] = $assabetDriver->getAudiences();
			$itemData['categories'] = null;
			$itemData['programTypes'] = null;
			$itemData['room'] = null;

			// check if event has passed
			$today = new DateTime('now');
			$eventDay = $assabetDriver->getStartDate();
			$itemData['pastEvent'] = $today >= $eventDay;

			$itemData['location'] = $this->getDiscoveryBranchDetails($assabetDriver->getBranch());
			$itemData['canAddToList'] = false;

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = $user->isRegistered($_REQUEST['id']);
				$itemData['inUserEvents'] = $user->inUserEvents($_REQUEST['id']);
				$itemData['canAddToList'] = $user->isAllowedToAddEventsToList($assabetDriver->getSource());
			}


			return $itemData;
		}
		return [
			'success' => false,
			'message' => 'Event id not valid',
		];
	}

	function getAspenEventDetails() {
		require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
		$aspenEventDriver = new AspenEventRecordDriver($_REQUEST['id']);
		if($aspenEventDriver->isValid()) {
			$registrationInformation = null;

			$itemData['success'] = true;
			$itemData['id'] = $_REQUEST['id'];
			$itemData['title'] = $aspenEventDriver->getTitle();
			$itemData['isAllDay'] = (bool)$aspenEventDriver->isAllDayEvent();
			$itemData['startDate'] = $aspenEventDriver->getStartDate();
			$itemData['endDate'] = $aspenEventDriver->getEndDate();
			$itemData['description'] = strip_tags($aspenEventDriver->getDescription());
			$itemData['registrationRequired'] = $aspenEventDriver->isRegistrationRequired();
			$itemData['userIsRegistered'] = false;
			$itemData['inUserEvents'] = false;
			$itemData['registrationBody'] = $registrationInformation;
			$itemData['bypass'] = (bool)$aspenEventDriver->getBypassSetting();
			$itemData['cover'] = $aspenEventDriver->getEventCoverUrl();
			$itemData['url'] = $aspenEventDriver->getExternalUrl();
			$itemData['audiences'] = $aspenEventDriver->getAudiences();
			$itemData['categories'] = null;
			$itemData['programTypes'] = $aspenEventDriver->getProgramTypes();
			$itemData['room'] = $aspenEventDriver->getRoom();

			// check if event has passed
			$today = new DateTime('now');
			$eventDay = $aspenEventDriver->getStartDate();
			$itemData['pastEvent'] = $today >= $eventDay;

			$itemData['location'] = $this->getDiscoveryBranchDetails($aspenEventDriver->getBranch());
			$itemData['canAddToList'] = false;

			$user = $this->getUserForApiCall();
			if ($user && !($user instanceof AspenError)) {
				$itemData['userIsRegistered'] = false;
				$itemData['inUserEvents'] = $user->inUserEvents($_REQUEST['id']);
				$itemData['canAddToList'] = $user->isAllowedToAddEventsToList($aspenEventDriver->getSource());
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
	 * Save an event for the user.
	 *
	 * Parameters:
	 * <ul>
	 * <li>id - The full id of the event to save</li>
	 * </ul>
	 *
	 */

	/* @noinspection PhpUnused */
	function saveEvent() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$id = $_REQUEST['id'];
			if (empty($id)) {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
					'message' => 'You must provide an event id, source, and vendor to save this event.'
				];
			}

			$success = false;
			$title = translate([
				'text' => 'Unable to save',
				'isPublicFacing' => true,
			]);
			$message = translate(['text' => 'Unable to save this event to your events.',
				'isPublicFacing' => true,]);
			
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$userEventsEntry = new UserEventsEntry();
			$userEventsEntry->userId = $user->id;
			$userEventsEntry->sourceId = $id;

			$regRequired = 0;
			$regModal = null;
			$externalUrl = null;
			if(str_starts_with($id, 'communico')) {
				require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
				$recordDriver = new CommunicoEventRecordDriver($id);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()){
						$regRequired = 1;
						$regModal = $recordDriver->getRegistrationModalBodyForAPI();
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif(str_starts_with($id, 'libcal')) {
				require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
				$recordDriver = new SpringshareLibCalEventRecordDriver($id);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()){
						$regRequired = 1;
						$regModal = $recordDriver->getRegistrationModalBodyForAPI();
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif(str_starts_with($id, 'lc')) {
				require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
				$recordDriver = new LibraryCalendarEventRecordDriver($id);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()){
						$regRequired = 1;
						$regModal = $recordDriver->getRegistrationModalBodyForAPI();
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif(str_starts_with($id, 'assabet')) {
				require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
				$recordDriver = new AssabetEventRecordDriver($id);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()){
						$regRequired = 1;
						$regModal = $recordDriver->getRegistrationModalBodyForAPI();
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} else {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
					'message' => 'Invalid source id',
				];
			}

			$userEventsEntry->dateAdded = time();

			if($userEventsEntry->find(true)) {
				if($userEventsEntry->update()) {
					$success = true;
				}
 			} else {
				if($userEventsEntry->insert()) {
					$success = true;
				}
			}

			if($success) {
				$message = translate(['text' => 'This event was saved to your events successfully.',
					'isPublicFacing' => true,]);
				$title = translate([
					'text' => 'Added Successfully',
					'isPublicFacing' => true,
				]);
				if($regRequired) {
					$message = translate([
						'text' => 'This event was saved to your events successfully. Saving an event to your events is not the same as registering.',
						'isPublicFacing' => true,
					]);
				}
			}

			return [
				'success' => $success,
				'title' => $title,
				'message' => $message,
				'registrationRequired' => $regRequired,
				'regBody' => $regModal,
				'url' => $externalUrl,
			];
		} else {
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Remove a previously saved event for the user.
	 *
	 * Parameters:
	 * <ul>
	 * <li>id - The full id of the event to search for</li>
	 * </ul>
	 *
	 */

	/* @noinspection PhpUnused */
	function removeSavedEvent() {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			if(isset($_REQUEST['id'])) {
				require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
				$userEventsEntry = new UserEventsEntry();
				$userEventsEntry->sourceId = $_REQUEST['id'];
				if($userEventsEntry->find(true)) {
					$userEventsEntry->delete();
					return [
						'success' => true,
						'title' => translate(['text' => 'Success', 'isPublicFacing' => true]),
						'message' => translate([
							'text' => 'Event successfully removed from your events.',
							'isPublicFacing' => true
						])
					];
				} else {
					return [
						'success' => false,
						'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
						'message' => 'Sorry, we could not find that event in the system.',
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
					'message' => 'You must provide a saved event id to remove it.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Returns a list of events that a user has saved.
	 *
	 * Parameters:
	 * <ul>
	 * <li>filter - Filter to apply to the results (all, upcoming, past). Set to 'all' if not included.</li>
	 * <li>page - Used for pagination. Default is 1.</li>
	 * <li>pageSize - Used for pagination. Default is 20.</li>
	 * </ul>
	 *
	 */

	/* @noinspection PhpUnused */
	function getSavedEvents() {
		$curTime = time();
		global $configArray;
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			$filter = $_REQUEST['filter'] ?? 'all';
			$page = $_REQUEST['page'] ?? 1;
			$pageSize = $_REQUEST['pageSize'] ?? 20;

			$savedEvents = [];
			$events = [];

			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$savedEvent = new UserEventsEntry();
			$savedEvent->userId = $user->id;

			if($filter == 'past') {
				$savedEvent->whereAdd("eventDate < $curTime");
				$savedEvent->orderBy('eventDate DESC');
			} elseif ($filter == 'upcoming') {
				$savedEvent->whereAdd("eventDate >= $curTime");
				$savedEvent->orderBy('eventDate ASC');
			} else {
				$savedEvent->orderBy('eventDate DESC');
			}
			$savedEvent->limit(($page - 1) * $pageSize, $pageSize);
			$savedEvent->find();

			while ($savedEvent->fetch()) {
				if (!array_key_exists($savedEvent->sourceId, $events)) {
					$savedEvents[$savedEvent->sourceId] = clone $savedEvent;
				}
			}

			/** @var SearchObject_EventsSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Events');
			$eventRecords = $searchObject->getRecords(array_keys($savedEvents));

			foreach($savedEvents as $eventId => $event) {
				$_REQUEST['id'] = $eventId;
				$registration = $user->isRegistered($event->sourceId);
				$source = 'unknown';
				$sourceFull = 'unknown';

				$hasPassed = false;
				$today = new DateTime();
				$today->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$eventDate = date('c', $event->eventDate);
				$eventDate = new DateTime($eventDate);
				$eventDate->setTimezone(new DateTimeZone(date_default_timezone_get()));

				if($today > $eventDate) {
					$hasPassed = true;
				}

				if(str_starts_with($eventId, 'lc')) {
					$sourceFull = 'library_calendar';
					$source = 'lc';
				} else if(str_starts_with($eventId, 'communico')) {
					$sourceFull = 'communico';
					$source = 'communico';
				} else if(str_starts_with($eventId, 'libcal')) {
					$sourceFull = 'springshare_libcal';
					$source = 'libcal';
				} else if(str_starts_with($eventId, 'assabet')) {
					$sourceFull = 'assabet';
					$source = 'assabet';
				} else if(str_starts_with($eventId, 'aspenEvent')) {
					$sourceFull = 'aspenEvent';
					$source = 'aspenEvent';
				} else {
					// something went wrong
				}

				if (array_key_exists($eventId, $eventRecords)) {
					$details = [];
					if(str_starts_with($eventId, 'lc')) {
						$details = $this->getLMEventDetails();
					} else if(str_starts_with($eventId, 'communico')) {
						$details = $this->getCommunicoEventDetails();
					} else if(str_starts_with($eventId, 'libcal')) {
						$details = $this->getSpringshareEventDetails();
					} else if(str_starts_with($eventId, 'assabet')) {
						$details = $this->getAssabetEventDetails();
					} else if(str_starts_with($eventId, 'aspenEvent')) {
						$details = $this->getAspenEventDetails();
					} else {
						// something went wrong
					}

					if($details['success'] === true) {
						$events[$event->sourceId]['id'] = $event->id;
						$events[$event->sourceId]['sourceId'] = $event->sourceId;
						$events[$event->sourceId]['title'] = $event->title;
						$events[$event->sourceId]['startDate'] = $details['startDate'];
						$events[$event->sourceId]['endDate'] = $details['endDate'];
						$events[$event->sourceId]['url'] = $details['url'];
						$events[$event->sourceId]['bypass'] = $details['bypass'];
						$events[$event->sourceId]['cover'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $event->sourceId . '&size=medium&type=' . $sourceFull . '_event' . '&isPast=' . $hasPassed;
						$events[$event->sourceId]['registrationRequired'] = $details['registrationRequired'];
						$events[$event->sourceId]['userIsRegistered'] = $details['userIsRegistered'];
						$events[$event->sourceId]['location'] = $details['location'];
						$events[$event->sourceId]['pastEvent'] = $hasPassed;
						$events[$event->sourceId]['source'] = $source;
					}
				} else {
					$events[$event->sourceId]['id'] = $event->id;
					$events[$event->sourceId]['sourceId'] = $event->sourceId;
					$events[$event->sourceId]['title'] = $event->title;
					$events[$event->sourceId]['startDate'] = $eventDate;
					$events[$event->sourceId]['endDate'] = null;
					$events[$event->sourceId]['bypass'] = 0;
					$events[$event->sourceId]['url'] = null;
					$events[$event->sourceId]['cover'] = $configArray['Site']['url'] . '/bookcover.php?id=' . $event->sourceId . '&size=medium&type=' . $sourceFull . '_event' . '&isPast=' . $hasPassed;
					$events[$event->sourceId]['registrationRequired'] = null;
					$events[$event->sourceId]['userIsRegistered'] = $registration;
					$events[$event->sourceId]['pastEvent'] = $hasPassed;
					$events[$event->sourceId]['source'] = $source;
				}
			}

			$options = [
				'totalItems' => count($savedEvents),
				'perPage' => $pageSize,
				'append' => false,
			];

			$pager = new Pager($options);

			return [
				'success' => true,
				'totalResults' => $pager->getTotalItems(),
				'page_current' => (int)$pager->getCurrentPage(),
				'page_total' => (int)$pager->getTotalPages(),
				'filter' => $filter,
				'events' => $events,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}