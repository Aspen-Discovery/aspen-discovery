<?php

require_once ROOT_DIR . '/JSON_Action.php';

class CurbsidePickups_AJAX extends JSON_Action {

	/** @noinspection PhpUnused */
	function getCurbsidePickupScheduler(): array {
		$this->requireLoggedInUser();
		global $interface;
		global $library;

		$result = [
			'success' => false,
			'message' => 'Error loading curbside pickup scheduler.',
		];

		$user = UserAccount::getActiveUserObj();
		$interface->assign('patronId', $user->id);

		if (isset($_REQUEST['pickupLocation'])) {
			require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
			$pickupLocation = [];
			$location = new Location();
			$location->locationId = $_REQUEST['pickupLocation'];
			if ($location->find(true)) {
				$pickupLocation['id'] = $location->locationId;
				$pickupLocation['code'] = $location->code;
				$pickupLocation['name'] = $location->displayName;
			}
		} else {
			$pickupLocation = "any";
		}
		$interface->assign('pickupLocation', $pickupLocation);

		require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';
		$curbsidePickupSetting = new CurbsidePickupSetting();
		$curbsidePickupSetting->id = $library->curbsidePickupSettingId;
		$curbsidePickupSetting->find();
		if ($curbsidePickupSetting->find(true)) {
			$interface->assign('instructionNewPickup', $curbsidePickupSetting->instructionNewPickup);
			$interface->assign('useNote', $curbsidePickupSetting->useNote);
			$interface->assign('noteLabel', $curbsidePickupSetting->noteLabel);
			$interface->assign('noteInstruction', $curbsidePickupSetting->noteInstruction);

			// Return only a loading message initially; the full content will be loaded via AJAX.
			$result = [
				'success' => true,
				'title' => translate([
					'text' => 'Schedule Your Pickup at ' . htmlentities($pickupLocation["name"]),
					'isPublicFacing' => true,
				]),
				'body' => "<div id='curbsidePickupLoading' class='text-center'><i class='fas fa-spinner fa-spin fa-2x'></i><br>" . translate([
						'text' => "Loading calendar...",
						'isPublicFacing' => true,
					]) . "</div><div id='curbsidePickupContent' style='display:none'>" . $interface->fetch('MyAccount/curbsidePickupsNew.tpl') . "</div>",
				'buttons' => "<button type='submit' id='createCurbsidePickupSubmit' name='submit' style='display:none' class='btn btn-primary' onclick='return AspenDiscovery.CurbsidePickup.createCurbsidePickup()'>" . translate([
						'text' => "Schedule Pickup",
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} else {
			$result['message'] = "Curbside pickup settings not found.";
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function createCurbsidePickup(): array {
		$this->requireLoggedInUser();
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error Scheduling Curbside Pickup',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Failed to schedule your curbside pickup.',
				'isPublicFacing' => true,
			]),
		];
		if (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if (!$patronOwningHold) {
				$result['message'] = translate([
					'text' => 'Sorry, you do not have access to schedule a curbside pickup for this patron.',
					'isPublicFacing' => true,
				]);
			} else {
				if (empty($_REQUEST['location']) || empty($_REQUEST['date']) || empty($_REQUEST['time'])) {
					global $logger;
					$logger->log('New Curbside Pickup: Pickup library or pickup date/time was not passed in the AJAX call.', Logger::LOG_ERROR);
					$result['message'] = translate([
						'text' => 'Schedule information about the curbside pickup was not provided.',
						'isPublicFacing' => true,
					]);
				} else {
					$pickupLocation = $_REQUEST['location'];
					$pickupDate = $_REQUEST['date'];
					$pickupTime = $_REQUEST['time'];
					$pickupNote = null;
					if (isset($_REQUEST['note'])) {
						$pickupNote = $_REQUEST['note'];
						if ($pickupNote == 'undefined') {
							$pickupNote = null;
						}
						// Koha's API endpoint for creating a curbside pickup does not enforce a maximum
						// character limit, so Aspen should enforce one, albeit somewhat arbitrarily.
						if (!empty($pickupNote) && strlen($pickupNote) > 255) {
							$pickupNote = substr($pickupNote, 0, 255);
							global $logger;
							$logger->log("Curbside pickup note truncated to 255 characters.", Logger::LOG_NOTICE);
						}
					}

					$date = $pickupDate . " " . $pickupTime;
					$pickupDateTime = strtotime($date);
					$pickupDateTime = date('Y-m-d H:i:s', $pickupDateTime);

					require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';
					$curbsidePickupSetting = new CurbsidePickupSetting();
					$curbsidePickupSetting->id = $library->curbsidePickupSettingId;
					if ($curbsidePickupSetting->find(true)) {
						$interface->assign('contentSuccess', $curbsidePickupSetting->contentSuccess);
					}

					$result = $patronOwningHold->newCurbsidePickup($pickupLocation, $pickupDateTime, $pickupNote);
					$interface->assign('scheduleResultMessage', $result['message']);
					if ($result['success']) {
						return [
							'success' => true,
							'title' => translate([
								'text' => 'Pickup scheduled',
								'isPublicFacing' => true,
							]),
							'body' => $interface->fetch('MyAccount/curbsidePickupsNewSuccess.tpl'),
						];
					} else {
						return [
							'title' => translate([
								'text' => 'Error Scheduling Curbside Pickup',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => $result['message'],
								'isPublicFacing' => true,
							]),
						];
					}
				}
			}
		} else {
			global $logger;
			$logger->log('New Curbside Pickup: No patron ID was passed in the AJAX call.', Logger::LOG_ERROR);
			$result['message'] = translate([
				'text' => 'No patron was specified.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getCancelCurbsidePickup(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['patronId', 'pickupId']);
		$patronId = $_REQUEST['patronId'];
		$pickupId = $_REQUEST['pickupId'];
		return [
			'success' => true,
			'title' => translate([
				'text' => 'Cancel Curbside Pickup',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Are you sure you want to cancel this curbside pickup?',
				'isPublicFacing' => true,
			]),
			'buttons' => "<button type='button' class='btn btn-primary' onclick='AspenDiscovery.CurbsidePickup.cancelCurbsidePickup(\"$patronId\", \"$pickupId\")'>" . translate([
					'text' => 'Yes, cancel pickup',
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function checkInCurbsidePickup(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['patronId', 'pickupId']);
		global $interface;
		global $library;
		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Checking in Curbside Pickup',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Error checking in for curbside pickup',
				'isPublicFacing' => true,
			]),
		];

		$patronId = $_REQUEST['patronId'];
		$pickupId = $_REQUEST['pickupId'];

		require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';
		$curbsidePickupSetting = new CurbsidePickupSetting();
		$curbsidePickupSetting->id = $library->curbsidePickupSettingId;
		if ($curbsidePickupSetting->find(true)) {
			$interface->assign('contentCheckedIn', $curbsidePickupSetting->contentCheckedIn);
		}

		$user = UserAccount::getActiveUserObj();
		$patron = $user->getUserReferredTo($patronId);
		if ($patron === false) {
			$results['message'] = translate([
				'text' => 'Invalid patron specified.',
				'isPublicFacing' => true,
			]);
			return $results;
		}
		$result = $user->getCatalogDriver()->checkInCurbsidePickup($patron, $pickupId);

		if ($result['success']) {
			$interface->assign('scheduleResultMessage', $result['message']);
			$interface->assign('contentSuccess', $curbsidePickupSetting->contentCheckedIn);
			$results = [
				'success' => true,
				'title' => translate([
					'text' => 'Checked In Curbside Pickup',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/curbsidePickupsNewSuccess.tpl'),
			];
		} else {
			$results = [
				'success' => false,
				'title' => translate([
					'text' => 'Failed to Check In for Curbside Pickup',
					'isPublicFacing' => true,
				]),
				'body' => translate([
					'text' => $result['message'],
					'isPublicFacing' => true,
				]),
			];
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function cancelCurbsidePickup(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['patronId', 'pickupId']);
		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Cancel Curbside Pickup',
				'isPublicFacing' => true,
			]),
		];

		$patronId = $_REQUEST['patronId'];
		$pickupId = $_REQUEST['pickupId'];

		$user = UserAccount::getActiveUserObj();
		$patron = $user->getUserReferredTo($patronId);
		if ($patron === false) {
			$results['message'] = translate([
				'text' => 'Invalid patron specified.',
				'isPublicFacing' => true,
			]);
			return $results;
		}
		$result = $user->getCatalogDriver()->cancelCurbsidePickup($patron, $pickupId);

		if ($result['success']) {
			$results = [
				'success' => true,
				'title' => translate([
					'text' => 'Cancel Curbside Pickup',
					'isPublicFacing' => true,
				]),
				'body' => translate([
					'text' => 'Your pickup was cancelled successfully.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			$results = [
				'success' => false,
				'title' => translate([
					'text' => 'Cancel Curbside Pickup',
					'isPublicFacing' => true,
				]),
				'body' => translate([
					'text' => $result['message'],
					'isPublicFacing' => true,
				]),
			];
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function getCurbsidePickupUnavailableDays(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['locationCode']);

		$pickupLocation = $_REQUEST['locationCode'];

		$user = UserAccount::getActiveUserObj();
		$pickupSettings = $user->getCatalogDriver()->getCurbsidePickupSettings($pickupLocation);
		if (!empty($pickupSettings['disabledDays']) && is_array($pickupSettings['disabledDays'])) {
			return [
				'success' => true,
				'days' => array_values($pickupSettings['disabledDays']),
			];
		}

		return [
			'success' => true,
			'days' => [],
		];
	}

	/** @noinspection PhpUnused */
	function getCurbsidePickupAvailableTimes(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['locationCode', 'date']);

		$pickupLocation = $_REQUEST['locationCode'];
		$pickupDate = $_REQUEST['date'];

		$user = UserAccount::getActiveUserObj();
		$pickupSettings = $user->getCatalogDriver()->getCurbsidePickupSettings($pickupLocation);

		if ($pickupSettings['success'] && $pickupSettings['enabled'] == 1) {

			$date = strtotime($pickupDate);
			$dayOfWeek = date('D', $date);
			$todayDay = date('D');
			$now = date('H:i');
			$allPossibleTimes = $pickupSettings['pickupTimes'][$dayOfWeek];

			// check if max number of patrons are signed up for timeWindow
			$maxPatrons = $pickupSettings['maxPickupsPerInterval'];
			$allScheduledPickups = $user->getCatalogDriver()->getAllCurbsidePickups();

			if ($allPossibleTimes && $allPossibleTimes['available']) {
				$range = range(strtotime($allPossibleTimes['startTime']), strtotime($allPossibleTimes['endTime']), $pickupSettings['interval'] * 60);
				$timeWindow = [];
				// Check if this is today's date.
				$isToday = date('Y-m-d', $date) === date('Y-m-d');

				foreach ($range as $time) {
					$numPickups = 0;
					$formattedTime = strtotime(date('H:i', $time));

					// Only filter times by current time if this is today's date.
					if ($isToday && $formattedTime <= strtotime($now)) {
						// Skip times that are in the past for today.
						continue;
					}

					if (!empty($allScheduledPickups['pickups'])) {
						foreach ($allScheduledPickups['pickups'] as $pickup) {
							if ($pickupLocation == $pickup['branchcode']) {
								$scheduledDate = strtotime($pickup['scheduled_pickup_datetime']);
								$scheduledPickupDate = date('Y-m-d', $scheduledDate);
								$pickupSelectedDate = date('Y-m-d', $date);
								$scheduledTime = date('H:i', $scheduledDate);

								// Only count pickups for the exact date selected, not just day of week.
								if ($scheduledPickupDate == $pickupSelectedDate && $formattedTime == strtotime($scheduledTime)) {
									$numPickups += 1;
								}
							}
						}
						if ($numPickups < $maxPatrons) {
							$timeWindow[] = date("H:i", $time);
						}
					} else {
						$timeWindow[] = date("H:i", $time);
					}
				}
				global $logger;
				$logger->log("Time window: " . print_r($timeWindow, true), Logger::LOG_ERROR);
				return [
					'success' => true,
					'times' => $timeWindow,
				];
			}
		}
		return [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => "There was an error loading curbside pickup availability.",
				'isPublicFacing' => true,
			]),
		];
	}
}