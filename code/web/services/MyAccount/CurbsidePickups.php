<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';

class MyAccount_CurbsidePickups extends MyAccount {
	function launch() {
		global $interface;
		global $library;
		$user = UserAccount::getActiveUserObj();
		$interface->assign('patronId', $user->id);
		$interface->assign('patron', $user->username);

		$curbsidePickupSetting = new CurbsidePickupSetting();
		$curbsidePickupSetting->id = $library->curbsidePickupSettingId;
		if ($curbsidePickupSetting->find(true)) {
			$interface->assign('instructionSchedule', $curbsidePickupSetting->instructionSchedule);
			$interface->assign('useNote', $curbsidePickupSetting->useNote);
			$interface->assign('noteLabel', $curbsidePickupSetting->noteLabel);

			$catalog = CatalogFactory::getCatalogConnectionInstance();
			$hasPickups = $catalog->hasCurbsidePickups($user);
			$interface->assign('hasPickups', false);
			if (isset($hasPickups['hasPickups'])) {
				if ($hasPickups['hasPickups'] == true) {
					$interface->assign('hasPickups', true);
					$currentPickups = $catalog->getPatronCurbsidePickups($user);
					$interface->assign('currentCurbsidePickups', $currentPickups);
					$pickupsByLocation = [];
					foreach ($currentPickups['pickups'] as $pickup) {
						if (!isset($pickupsByLocation)) {
							$pickupsByLocation[$pickup->branchcode]['code'] = $pickup->branchcode;
							$pickupsByLocation[$pickup->branchcode]['count'] += 1;
						} elseif (!in_array($pickup->branchcode, array_column($pickupsByLocation, 'code'))) {
							$pickupsByLocation[$pickup->branchcode]['code'] = $pickup->branchcode;
							$pickupsByLocation[$pickup->branchcode]['count'] = 1;

							$location = new Location();
							$location->code = $pickup->branchcode;
							if ($location->find(true)) {
								if ($location->curbsidePickupInstructions) {
									$interface->assign('pickupInstructions', $location->curbsidePickupInstructions);
								} else {
									$interface->assign('pickupInstructions', $curbsidePickupSetting->curbsidePickupInstructions);
								}
							}

							$interface->assign('withinTime', false);
							$allowedTime = $curbsidePickupSetting->timeAllowedBeforeCheckIn;
							$pickupTime = $pickup->scheduled_pickup_datetime;
							$scheduledTime = date_create($pickupTime);
							$now = date_create();
							$difference = date_diff($now, $scheduledTime);
							$minutes = $difference->days * 24 * 60;
							$minutes += $difference->h * 60;
							$minutes += $difference->i;
							$timeUntil = $minutes;
							if ($timeUntil <= $allowedTime) {
								$interface->assign('withinTime', true);
							}
						}
					}
				}
			}

			$interface->assign('allowCheckIn', $curbsidePickupSetting->allowCheckIn);
			$interface->assign('showScheduleButton', true);

			$ilsSummary = $user->getCatalogDriver()->getAccountSummary($user);
			$availableHolds = $ilsSummary->numAvailableHolds;
			$interface->assign('availableHolds', $availableHolds);
			$interface->assign('hasHolds', false);
			$alwaysAllowPickups = $curbsidePickupSetting->alwaysAllowPickups;
			if ($alwaysAllowPickups == 0 && $availableHolds == 0) {
				$interface->assign('showScheduleButton', false);
			}

			if ($availableHolds > 0) {
				$interface->assign('hasHolds', true);
				$allHolds = $user->getHolds(false, '', '', 'ils');
				$holdsByLocation = [];
				foreach ($allHolds['available'] as $hold) {
					$locationCode = null;
					require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
					$pickupLocation = [];
					$location = new Location();
					$location->locationId = $hold->pickupLocationId;
					if ($location->find(true)) {
						$locationCode = $location->code;
					}
					$isScheduled = false;
					if (isset($pickupsByLocation[$locationCode])) {
						$isScheduled = true;
					}
					if (!isset($holdsByLocation)) {
						$holdsByLocation[$hold->pickupLocationName]['id'] = $hold->pickupLocationId;
						$holdsByLocation[$hold->pickupLocationName]['name'] = $hold->pickupLocationName;
						$holdsByLocation[$hold->pickupLocationName]['code'] = $locationCode;
						$holdsByLocation[$hold->pickupLocationName]['pickupScheduled'] = $isScheduled;
						$holdsByLocation[$hold->pickupLocationName]['holds'][] = $hold;
					} elseif (!in_array($hold->pickupLocationId, array_column($holdsByLocation, 'code'))) {
						$holdsByLocation[$hold->pickupLocationName]['id'] = $hold->pickupLocationId;
						$holdsByLocation[$hold->pickupLocationName]['name'] = $hold->pickupLocationName;
						$holdsByLocation[$hold->pickupLocationName]['code'] = $locationCode;
						$holdsByLocation[$hold->pickupLocationName]['pickupScheduled'] = $isScheduled;
						$holdsByLocation[$hold->pickupLocationName]['holds'][] = $hold;
					}
				}
				$interface->assign('holdsReadyForPickup', $holdsByLocation);
			}

		} else {
			// setting not found
		}

		$this->display('curbsidePickups.tpl', 'Curbside Pickups');

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Curbside Pickups');
		return $breadcrumbs;
	}
}