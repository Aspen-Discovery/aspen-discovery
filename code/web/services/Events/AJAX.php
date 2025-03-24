<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Events_AJAX extends JSON_Action {

	/** @noinspection PhpUnused */
	public function getEventTypesAndSubLocationsForLocation() {
		require_once ROOT_DIR . '/sys/Events/EventType.php';
		$result = [
			'success' => false,
			'title' => translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]),
			'message' =>  translate([
				'text' => 'Unknown location',
				'isAdminFacing' => true,
			])
		];
		if (!empty($_REQUEST['locationId'])) {
			$eventTypeIds = EventType::getEventTypeIdsForLocation($_REQUEST['locationId']);
			$eventTypes = [];
			foreach ($eventTypeIds as $eventTypeId) {
				$eventTypes[$eventTypeId] = EventType::getTypeName($eventTypeId);
			}
			$sublocations = Location::getEventSublocations($_REQUEST['locationId']);
			if (!empty($eventTypeIds)) {
				$result = [
					'success' => true,
					'eventTypes' => json_encode($eventTypes),
					'sublocations' => json_encode($sublocations),
				];
			} else {
				$result = [
					'success' => true,
					'eventTypeIds' => '',
					'title' => translate([
						'text' => "No available event types",
						'isAdminFacing' => true,
					]),
					'message' => translate([
						'text' => 'No event types are available for this location.',
						'isAdminFacing' => true,
					])
				];
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function getEventTypeFields() {
		require_once ROOT_DIR . '/sys/Events/EventType.php';
		$result = [
			'success' => false,
			'title' => translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]),
			'message' =>  translate([
				'text' => 'Unknown event type.',
				'isAdminFacing' => true,
			])
		];
		if (!empty($_REQUEST['eventTypeId'])) {
			$eventType = new EventType();
			$eventType->id = $_REQUEST['eventTypeId'];
			if ($eventType->find(true)) {
				$fieldStructure = $eventType->getFieldSetFields();
				global $interface;
				$fieldHTML = [];
				foreach ($fieldStructure as $property) {
					$interface->assign('property', $property);
					$fieldHTML[] = $interface->fetch('DataObjectUtil/property.tpl');
				}
				$locations = $eventType->getLocations();
				$result = [
					'success' => true,
					'eventType' => $eventType->jsonSerialize(),
					'typeFields' => $fieldHTML,
					'locationIds' => json_encode(array_keys($locations)),
				];
			}
		} else {
			// Event type value is probably the placeholder prompt
			$result = [
				'success' => true,
				'status' => 'resetForm',
			];
		}
		return $result;
	}

	public function exportUsageData() {
		require_once ROOT_DIR . '/services/Events/EventGraphs.php';
		$aspenUsageGraph = new Events_EventGraphs();
		$aspenUsageGraph->buildCSV();
	}

	public function iCalendarExport() {
			$result = [
				'success' => false,
				'title' => translate([
					'text' => "Error",
					'isAdminFacing' => false,
				]),
				'message' => translate([
					'text' => 'Could not export event.',
					'isAdminFacing' => false,
				])
			];
			$eventId = $_REQUEST['eventId'] ?? '';
			$wholeSeries = $_REQUEST['wholeSeries'] ?? '';
			if (!empty($eventId)) {
				global $interface;
				global $configArray;
				$interface->assign('timezone', $configArray['Site']['timezone']);
				$eventIdParts = explode("_", $eventId, 3);
				if (isset($eventIdParts[2])) {
					switch ($_REQUEST['source']) {
						case 'event_assabet':
							require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
							$driver = new AssabetEventRecordDriver($eventId);
							break;
						case 'event_communico':
							require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
							$driver = new CommunicoEventRecordDriver($eventId);
							break;
						case 'event_libcal':
							require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
							$driver = new SpringshareLibCalEventRecordDriver($eventId);
							break;
						case 'library_calendar_event':
							require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
							$driver = new LibraryCalendarEventRecordDriver($eventId);
							break;
						case 'event_aspenEvent':
							require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
							$driver = new AspenEventRecordDriver($eventId);
							break;
						default:
							$result = [
								'success' => false,
								'title' => translate([
									'text' => "Error",
									'isAdminFacing' => false,
								]),
								'message' => translate([
									'text' => 'Could not find record driver for ' . $_REQUEST['source'],
									'isAdminFacing' => false,
								])
							];
							return $result;
					}
					$interface->assign('title', $driver->getTitle());
					$description = $driver->getDescription() ?? '';
					$description = str_replace("<p>", " <p>", $description);
					$interface->assign('htmlDescription', $description);
					$description = preg_replace("/(\r\n)/", "\\n\\n", $description);
					$description = str_replace("&nbsp;", "", $description);
					$description = preg_replace("/(<br\s?\/?>)|(<\/p>)/", "\\n\\n", $description);
					$description = strip_tags($description);
					$description = preg_replace("(;|,)", '\\\\$0', $description);
					$interface->assign('description', $description);
					$interface->assign('location', $driver->getBranch());
					$interface->assign('sublocation', $driver->getRoom());
					$event = new stdClass();
					$startDate = $driver->getStartDate();
					$endDate = $driver->getEndDate();
					$event->date = $startDate->format("Ymd\THis");
					$interval = $startDate->diff($endDate);
					$interface->assign('hours', $interval->h);
					$interface->assign('minutes', $interval->i);
					$event->uid = $eventId;
					$event->sublocation = $driver->getRoom() ?? '';
					$event->status = $driver->getStatus() == 'Cancelled' ? 'Cancelled' : '';
					$instances[] = $event;
					if ($_REQUEST['source'] == 'event_aspenEvent' && $wholeSeries) {
						$eventInstance = new EventInstance();
						$eventInstance->id = $eventIdParts[2];
						$eventInstance->find(true);
						$series = $eventInstance->getSeries(true);
						foreach ($series as $instance) {
							$event = new stdClass();
							$date = $instance->date . "T" . $instance->time;
							$event->date = preg_replace('/([-:])/', '', $date);
							$event->uid = join("_", [
								$eventIdParts[0],
								$eventIdParts[0],
								$instance->id
							]);
							$event->sublocation = $instance->getSublocation() ?? '';
							$event->status = $instance->status ? '' : 'Cancelled';
							$instances[] = $event;
						}
					}
					$interface->assign('instances', $instances);
					$icsFile = $interface->fetch('Events/ics-export.tpl');
					$result = [
						'success' => true,
						'icsFile' => $icsFile,
					];
				}
			}
			return $result;
	}

	function getCopyEventsForm() : array {
		if (!empty($_REQUEST['eventId'])) {
			global $interface;
			require_once ROOT_DIR . '/sys/Events/Event.php';
			require_once ROOT_DIR . '/sys/Events/EventType.php';
			$event = new Event();
			$event->id = $_REQUEST['eventId'];
			if ($event->find(true)) {
				$eventId = $event->id;
				$eventLabel = $event->title;
				$interface->assign('eventId', $eventId);
				$interface->assign('eventLabel', $eventLabel);

				$eventType = new EventType();
				$eventType->id = $event->eventTypeId;
				if ($eventType->find(true)) {
					if (!$eventType->titleCustomizable) {
						$interface->assign('eventTitle', $eventType->title);
					}
				}

				$locationsForType = EventType::getLocationIdsForEventType($event->eventTypeId);
				if (UserAccount::userHasPermission('Administer Events for All Locations')) {
					$locationList = Location::getLocationList(false);
				} else if (UserAccount::userHasPermission('Administer Events for Home Library Locations')) {
					$locationList = Location::getLocationList(true);
				} else {
					$user = UserAccount::getLoggedInUser();
					$locationList[$user->homeLocationId] = $user->getHomeLocation()->displayName;
					$locationList = $locationList + $user->getAdditionalAdministrationLocations();
				}
				$locationList = array_intersect(array_flip($locationList), $locationsForType);
				if (count($locationList) == 0) {
					$locationList[0] = translate(['text' => "No locations available for this event type", 'isAdminFacing' => true]);
				}
				$sublocationList = Location::getEventSublocations($event->locationId);
				$interface->assign('locationList', $locationList);
				$interface->assign('sublocationList', $sublocationList);
				$modalBody = $interface->fetch('Events/copyEventsForm.tpl');

				return [
					'success' => true,
					'title' => translate([
						'text' => "Copy $eventLabel",
						'isAdminFacing' => true,
					]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Events.processCopyEventsForm();\" class=\"modal-buttons btn btn-primary\">" . translate([
							'text' => 'Copy',
							'isAdminFacing' => true,
						]) . "</button>",
				];
			}else{
				return[
					'success' => false,
					'message' => translate([
						'text' => "Event to copy could not be found.",
						'isAdminFacing' => true,
					])
				];
			}
		}else{
			return[
				'success' => false,
				'message' => translate([
					'text' => "Event to copy was not provided.",
					'isAdminFacing' => true,
				])
			];
		}
	}
	function doCopyEvent() : array {

		if (!empty($_REQUEST['name']) && !empty($_REQUEST['locationId']) && !empty($_REQUEST['date'])) {
			$eventInstancesCreated = 0;
			$id = $_REQUEST['id'];
			$name = $_REQUEST['name'];
			$locationId = $_REQUEST['locationId'];
			$sublocationId = $_REQUEST['sublocationId'];
			$startDate = $_REQUEST['date'];

			require_once ROOT_DIR . '/sys/Events/Event.php';
			$curObj = new Event();
			$curObj->id = $id;
			if ($curObj->find(true)) {
				$newEvent = clone $curObj;
				$newEvent->id = null;
				$newEvent->title = $name;
				foreach($curObj->getAllTypeFields() as $key => $value) {
					$newEvent->_typeFields[$key] = $value;
				}
				if (!empty($locationId)) {
					$newEvent->locationId = $locationId;
				}
				if (!empty($sublocationId)) {
					$newEvent->sublocationId = $sublocationId;
				}
				if (!empty($startDate)) {
					$newEvent->startDate = $startDate;
				}
				if ($newEvent->insert()) {
					$eventInstancesCreated = $newEvent->getInstanceCount();
					if ($eventInstancesCreated == 1) {
						return [
							'success' => true,
							'title' => 'Success',
							'message' => "Created $name and scheduled for $startDate."
						];
					} else if ($eventInstancesCreated > 1) {
						return [
							'success' => true,
							'title' => 'Success',
							'message' => "Created $eventInstancesCreated future dates for $name. Please verify that they are correct."
						];
					} else {
						return [
							'success' => true,
							'title' => 'Success',
							'message' => "Copied $name but did not create future dates for this event.  Please edit your event to set dates."
						];
					}

				} else {
					return [
						'success' => false,
						'title' => 'Error',
						'message' => "Unable to create new event",
					];
				}
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => "You must include a name, location, and date for the new event.",
			];
		}
		return [
			'success' => false,
			'title' => 'Error',
			'message' => "Unable to create new event",
		];
	}

}