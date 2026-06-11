<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class Events_AttendanceManagement extends Admin_Admin {

	function launch() {
		global $interface;

		$eventInstanceId = $_REQUEST['eventInstanceId'] ?? null;

		if (empty($eventInstanceId)) {
			$this->displayEventSelector();
			return;
		}

		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		if (!$eventInstance->find(true)) {
			$interface->assign('error', translate(['text' => 'Event not found.', 'isAdminFacing' => true]));
			$this->display('eventManagement.tpl', 'Attendance Management');
			return;
		}

		require_once ROOT_DIR . '/services/EventRegistrationService.php';
		$parentEvent = $eventInstance->getParentEvent();
		if (!EventRegistrationService::canStaffManagePatronEventAttendance($parentEvent->locationId)) {
			$interface->assign('error', translate(['text' => 'You do not have permission to manage patron attendance for this event.', 'isAdminFacing' => true]));
			$this->display('eventManagement.tpl', 'Attendance Management');
			return;
		}

		if (isset($_GET['download_list']) && $_GET['download_list'] === 'true') {
			$this->downloadRegistrationsList($eventInstanceId);
			return;
		}

		if (isset($_GET['download_csv']) && $_GET['download_csv'] === 'true') {
			$this->downloadRegistrationsCSV($eventInstanceId);
			return;
		}

		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		global $library;

		$interface->assign('eventInstanceId', $eventInstanceId);
		$interface->assign('eventTitle', $parentEvent->title);
		$interface->assign('eventDate', $eventInstance->date);
		$interface->assign('eventTime', $eventInstance->time);
		$interface->assign('eventLocation', $eventInstance->getLocation());
		$interface->assign('numberOfSeats', $eventInstance->getEffectiveNumberOfSeats());
		$interface->assign('availableSeats', EventRegistrationService::getAvailableSeats($eventInstance));
		$interface->assign('registrationCount', EventRegistrationService::getRegistrationCount((int)$eventInstance->id));
		$interface->assign('canManageEventRegistration', $library->allowEventRegistration && $library->allowStaffToRegisterUsersForEvents && EventRegistrationService::canStaffRegisterUsersForLocation($parentEvent->locationId));

		$registrations = UserAspenEventInstanceRegistration::getRegistrationsForEvent((int)$eventInstanceId);
		$registrationData = [];
		foreach ($registrations as $registration) {
			$user = $registration->getUser();
			$staffUser = $registration->getStaffUser();
			$registrationData[] = [
				'id' => $registration->id,
				'userId' => $registration->userId,
				'userName' => $user ? $user->getDisplayName() : 'Unknown',
				'userBarcode' => $user ? $user->ils_barcode : '',
				'userEmail' => $user ? $user->email : '',
				'attended' => (bool)$registration->attended,
				'registeredByStaff' => $registration->wasRegisteredByStaff(),
				'staffName' => $staffUser ? $staffUser->getDisplayName() : null,
				'dateRegistered' => $registration->createdAt ? date('Y-m-d H:i', strtotime($registration->createdAt)) : null,
			];
		}
		$interface->assign('registrations', $registrationData);

		$this->display('eventManagement.tpl', 'Attendance Management - ' . $parentEvent->title);
	}

	private function displayEventSelector(): void {
		global $interface;
		$eventInstances = $this->getActiveEventsInstances();
		$interface->assign('eventInstances', $eventInstances);
		$interface->assign('showEventSelector', true);

		$this->display('eventManagement.tpl', 'Attendance Management');
	}

	/*
	* Excludes deleted events.
	*/
	private function getActiveEventsInstances(): array {
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		require_once ROOT_DIR . '/services/EventRegistrationService.php';

		$events = [];

		$eventInstance = new EventInstance();
		$eventInstance->whereAdd("(deleted IS NULL OR deleted = 0)");
		$eventInstance->orderBy('date ASC, time ASC');
		$eventInstance->limit(0, 100);

		$eventInstance->find();
		while ($eventInstance->fetch()) {
			$parentEvent = $eventInstance->getParentEvent();
			if ($parentEvent && !$parentEvent->deleted && $parentEvent->registrationRequired) {
				// Only show events with registration enabled that staff has location access to
				if (EventRegistrationService::canStaffManagePatronEventAttendance($parentEvent->locationId)) {
					$events[] = [
						'instanceId' => $eventInstance->id,
						'title' => $parentEvent->title,
						'date' => $eventInstance->date,
						'time' => $eventInstance->time,
						'location' => $eventInstance->getLocation(),
						'registrationCount' => EventRegistrationService::getRegistrationCount((int)$eventInstance->id),
						'availableSeats' => EventRegistrationService::getAvailableSeats($eventInstance),
						'numberOfSeats' => $eventInstance->getEffectiveNumberOfSeats(),
						'registrationRequired' => (bool)$parentEvent->registrationRequired,
						'attendeeCategoryBreakdown' => EventRegistrationService::getAttendeeCategoryBreakdown((int)$eventInstance->id),
					];
				}
			}
		}

		return $events;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/AttendanceManagement', 'Attendance Management');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function canView(): bool {
		require_once ROOT_DIR . '/services/EventRegistrationService.php';
		return EventRegistrationService::canStaffManagePatronEventAttendance();
	}

	private function buildBaseRegistrationRow(UserAspenEventInstanceRegistration $registration, User $user): array {
		$user->loadContactInformation(); // so that we get the postcode and dob
		$staffUser = $registration->getStaffUser();
		return [
			$user->getDisplayName(),
			$user->ils_barcode,
			$user->email,
			$user->_zip,
			$user->_dateOfBirth,
			$registration->status,
			$registration->wasRegisteredByStaff() ? ($staffUser ? $staffUser->getDisplayName() : 'Staff') : 'Self',
			$registration->createdAt ? date('Y-m-d H:i', strtotime($registration->createdAt)) : '-',
		];
	}

	private function downloadRegistrationsList(int $eventInstanceId): void {
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		if (!$eventInstance->find(true)) {
			return;
		}

		$parentEvent = $eventInstance->getParentEvent();
		$registrations = UserAspenEventInstanceRegistration::getRegistrationsForEvent($eventInstanceId);

		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="registrations_list_' . $eventInstanceId . '.txt"');

		echo "Event: " . $parentEvent->title . "\n";
		echo "Date: " . $eventInstance->date . " " . $eventInstance->time . "\n";
		echo "Location: " . $eventInstance->getLocation() . "\n";
		echo "Total Registrations: " . count($registrations) . "\n";
		echo str_repeat("=", 80) . "\n\n";

		echo str_pad("Patron Name", 30)
		 . str_pad("ILS Barcode", 15)
		 . str_pad("Email", 40)
		 . str_pad("Post Code", 12)
		 . str_pad("Date of Birth", 15)
		 . str_pad("Registered By", 20)
		 . str_pad("Date Registered", 20)
		 . str_pad("Status", 15)
		 . "Attended\n";
		echo str_repeat("-", 200) . "\n";

		foreach ($registrations as $registration) {
			if (!$user = $registration->getUser()) {
				continue;
			}
			[$patronName, $barcode, $email, $postCode, $dateOfBirth, $status, $registeredBy, $dateRegistered] = $this->buildBaseRegistrationRow($registration, $user);
			echo str_pad($patronName, 30)
			 . str_pad($barcode, 15)
			 . str_pad($email, 40)
			 . str_pad($postCode, 12)
			 . str_pad($dateOfBirth, 15)
			 . str_pad($registeredBy, 20)
			 . str_pad($dateRegistered, 20)
			 . str_pad($status, 15)
			 . "[ ]\n";
		}
		exit;
	}

	private function downloadRegistrationsCSV(int $eventInstanceId): void {
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		if (!$eventInstance->find(true)) {
			return;
		}

		$registrations = UserAspenEventInstanceRegistration::getRegistrationsForEvent($eventInstanceId);

		if (!$parentEvent = $eventInstance->getParentEvent()) {
			return;
		}
		
		if (!$eventType = $eventInstance->getEventType()) {
			return;
		}

		$customFields = $eventType->getFieldSetFieldsByUse(2);
		$attendeeCategories = $eventType->getEventTypeAttendeeCategories();

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="registrations_' . $eventInstanceId . '.csv"');

		$output = fopen('php://output', 'w');

		$headers = [
			'Event date',
			'Event title',
			'Event start time',
			'Library location',
			'Event Type',
			'Patron Name',
			'ILS Barcode',
			'Email',
			'Post Code',
			'Date of Birth',
			'Status',
			'Registered By',
			'Date Registered',
			'Attended'
		];

		foreach ($customFields as $fieldId => $field) {
			$headers[] = $field['label'];
		}

		foreach ($attendeeCategories as $eventTypeCategory) {
			$category = $eventTypeCategory->getCategory();
			if ($category !== null) {
				$headers[] = $category->name . ' (attendees)';
			}
		}

		fputcsv($output, $headers);

		foreach ($registrations as $registration) {
			if (!$user = $registration->getUser()) {
				continue;
			}
			$row = array_merge([
					$eventInstance->date,
					$parentEvent->title,
					$eventInstance->time,
					$eventInstance->getLocation(),
					$eventType->title,
				],
				[...$this->buildBaseRegistrationRow($registration, $user), $registration->attended ? 'Yes' : 'No']
			);

			$customFieldValues = $registration->getCustomFieldValues();
			foreach ($customFields as $fieldId => $field) {
				$raw = $customFieldValues[(int)$fieldId] ?? '';
				if (($field['type'] ?? '') === 'enum' && isset($field['values'][$raw])) {
					$row[] = $field['values'][$raw];
				} else {
					$row[] = $raw;
				}
			}

			$attendeeCounts = $registration->getAttendeeCounts();
			foreach ($attendeeCategories as $eventTypeCategory) {
				$category = $eventTypeCategory->getCategory();
				if ($category !== null) {
					$row[] = $attendeeCounts[(int)$eventTypeCategory->attendeeCategoryId] ?? 0;
				}
			}

			fputcsv($output, $row);
		}

		fclose($output);
		exit;
	}

}
