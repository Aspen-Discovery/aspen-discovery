<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class LoadPreferredPickupLocations extends Admin_Admin {
	function launch() {
		global $interface;

		if (isset($_REQUEST['submit'])) {
			$results = $this->loadPickupLocations();
			$interface->assign('results', $results);
		}

		$this->display('loadPreferredPickupLocations.tpl', 'Load Preferred Pickup Locations', false);
	}

	function loadPickupLocations() : array {
		set_time_limit(0);
		ini_set('memory_limit', '4G');
		$result = [
			'success' => false,
			'message' => 'Unknown error loading preferred pickup locations.',
		];
		if (isset($_FILES['preferredPickupLocationFile'])) {
			$preferredPickupLocationFile = $_FILES['preferredPickupLocationFile'];
			if (isset($preferredPickupLocationFile["error"]) && $preferredPickupLocationFile["error"] == 4) {
				$result['message'] = translate([
					'text' => "No Preferred Pickup Location file was uploaded",
					'isAdminFacing' => true,
				]);
			} elseif (isset($preferredPickupLocationFile["error"]) && $preferredPickupLocationFile["error"] > 0) {
				$result['message'] = translate([
					'text' => "Error in file upload for preferred pickup location file %1%",
					1 => $preferredPickupLocationFile["error"],
					"isAdminFacing" => true,
				]);
			} else {
				$location = new Location();
				$locationCodeToIdMap = $location->fetchAll('code', 'locationId');
				$preferredPickupLocationFileHnd = fopen($preferredPickupLocationFile['tmp_name'], 'r');
				$numUpdated = 0;
				$numUsersNotFound = 0;
				$numSkipped = 0;
				$numInvalidPickupLocations = 0;
				//Skip the first line
				fgetcsv($preferredPickupLocationFileHnd);
				while ($preferredPickupLocationRow = fgetcsv($preferredPickupLocationFileHnd)) {
					if (count($preferredPickupLocationRow) >= 4) {
						$userBarcode = $preferredPickupLocationRow[1];
						$preferredPickupLocationCode = $preferredPickupLocationRow[2];
						$homeLibraryCode = $preferredPickupLocationRow[3];
						if ($homeLibraryCode != $preferredPickupLocationCode) {
							$user = new User();
							$user->cat_username = $userBarcode;
							if (!$user->find(true)) {
								$user = UserAccount::findNewUser($userBarcode);
								if ($user == false) {
									//Could not find a user for this barcode
									$numUsersNotFound++;
									continue;
								}
							}
							if (array_key_exists($preferredPickupLocationCode, $locationCodeToIdMap) && array_key_exists($preferredPickupLocationCode, $homeLibraryCode)) {
								$user->homeLocationId = $locationCodeToIdMap[$homeLibraryCode];
								$user->pickupLocationId = $locationCodeToIdMap[$preferredPickupLocationCode];
								$user->update();
								$numUpdated++;
							} else {
								$numInvalidPickupLocations++;
							}

						} else {
							//Skipping because the home location matches the preferred pickup location
							$numSkipped++;
						}
					}
				}
				fclose($preferredPickupLocationFileHnd);
				unlink($preferredPickupLocationFile['tmp_name']);
				$result['success'] = true;
				$result['message'] = "Loaded preferred pickup locations for $numUpdated users.";
				$result['message'] .= "<br/>Could not find $numUsersNotFound users";
				$result['message'] .= "<br/>Skipped $numSkipped users because the home location was the same as the preferred pickup location";
				$result['message'] .= "<br/>$numInvalidPickupLocations users had invalid pickup locations";
			}
		}
		return $result;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Load PreferredPickupLocations');

		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}
}