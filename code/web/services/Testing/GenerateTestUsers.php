<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Testing_GenerateTestUsers extends Admin_Admin {
	function launch() : void {
		global $interface;
		global $library;

		$accountProfile = $library->getAccountProfile();
		if (isset($_REQUEST['generateTestUsers'])) {
			set_time_limit(0);
			$results = [
				'success' => false,
			];
			$startingBarcode = $_REQUEST['startingBarcode'] ?? null;
			$defaultPassword = $_REQUEST['defaultPassword'] ?? null;
			$numberOfUsersToGenerate = $_REQUEST['numberOfUsersToGenerate'] ?? 1;
			if (empty($startingBarcode)) {
				$results['message'] = 'No staring barcode was supplied';
			}elseif (!is_numeric($startingBarcode)) {
				$results['message'] = 'Invalid starting barcode';
			}elseif (!is_numeric($numberOfUsersToGenerate)) {
				$results['message'] = 'Invalid number of users to generate';
			}elseif (empty($defaultPassword)) {
				$results['message'] = 'No default password was supplied';
			}else{
				//Get a list of all patron types
				require_once ROOT_DIR . '/sys/Account/PType.php';
				$ptype = new PType();
				//Get a list of all patron types without a key, so we can get a random one by index
				$allPatronTypes = array_values($ptype->fetchAll('pType'));
				//Get a list of all home location ids, so we can get a random one by index
				$location = new Location();
				$allLocationIds = array_values($location->fetchAll('locationId'));

				//Load the list of first names and last names
				$commonForenamesFile = file_get_contents(__DIR__ . '/common-forenames-by-country.json');
				$commonSurnamesFile = file_get_contents(__DIR__ . '/common-surnames-by-country.json');
				if ($commonForenamesFile === false) {
					$results['message'] = 'Could not load common forenames file';
				}elseif ($commonSurnamesFile === false) {
					$results['message'] = 'Could not load common surnames file';
				}else{
					$commonSurnames = json_decode($commonSurnamesFile, true);
					$commonForenames = json_decode($commonForenamesFile, true);

					$numSurnameLanguages = count($commonSurnames);
					$surnameLanguages = array_keys($commonSurnames);

					$numUsersAdded = 0;
					$numConflicts = 0;
					$numErrors = 0;
					$errorMessage = '';
					for ($barcodeToAdd = $startingBarcode; $barcodeToAdd < $startingBarcode + $numberOfUsersToGenerate; $barcodeToAdd++) {
						$user = new User();
						$user->ils_barcode = $barcodeToAdd;
						if ($user->find(true)) {
							$numConflicts++;
						}else{
							//Get the first and last names
							$surnameLanguageIndex = rand(0, $numSurnameLanguages - 1);
							$surnameLanguage = $surnameLanguages[$surnameLanguageIndex];
							$forename = null;
							$surname = null;
							$surnames = $commonSurnames[$surnameLanguage];
							$numNames = count($surnames);
							$surnameToUse = $surnames[rand(0, $numNames - 1)];
							$surname = $surnameToUse['romanized'][0];
							$forenameRegions = $commonForenames[$surnameLanguage];
							$numRegions = count($forenameRegions);
							$regionToUse = $forenameRegions[rand(0, $numRegions - 1)];
							$numNames = count($regionToUse['names']);
							$forenameToUse = $regionToUse['names'][rand(0, $numNames - 1)];
							$forename = $forenameToUse['romanized'][0];

							if (!empty($surname) && !empty($forename)) {
								$newUser = new User();
								$newUser->isLocalTestUser = 1;
								$newUser->source = $accountProfile->name;
								$newUser->ils_barcode = $barcodeToAdd;
								$newUser->ils_password = $defaultPassword;
								$newUser->unique_ils_id = $barcodeToAdd;
								$newUser->username = $barcodeToAdd;

								$newUser->firstname = $forename;
								$newUser->lastname = $surname;

								//Assign a random patron type
								$newUser->patronType = $allPatronTypes[rand(0, count($allPatronTypes) - 1)];

								//Assign a random location id
								$newUser->homeLocationId = $allLocationIds[rand(0, count($allLocationIds) - 1)];

								$newUser->created = time();
								$newUser->interfaceLanguage = 'en';
								$newUser->searchPreferenceLanguage = -1;
								$newUser->rememberHoldPickupLocation = 0;

								//insert the new user
								if ($newUser->insert()) {
									$numUsersAdded++;
									//Generate the display name for the patron
									$newUser->getDisplayName();
								}else{
									$errorMessage .= $newUser->getLastError();
									$numErrors++;
									break;
								}
							}elseif (empty($surname)){
								$errorMessage .= "Could not load surname for $surnameLanguage<br/>";
								$numErrors++;
							}elseif (empty($forename)){
								$errorMessage .= "Could not load forename for $surnameLanguage<br/>";
								$numErrors++;
							}
						}
					}
					$results['message'] = "Generated $numUsersAdded, there were $numConflicts conflicts with existing users in the database. There were $numErrors errors.";
					if (!empty($errorMessage)) {
						$results['message'] .= '<br/>' . $errorMessage;
					}
					if ($numErrors == 0) {
						$results['success'] = true;
					}
				}
			}
			$interface->assign('results', $results);
		}

		//Figure out the suggested starting barcode
		$user = new User();
		$user->whereAdd("ils_barcode REGEXP '^[0-9]+$'");
		$user->selectAdd();
		$user->selectAdd('MAX(ils_barcode) as max_barcode');
		if ($user->find(true)) {
			/** @noinspection PhpUndefinedFieldInspection */
			$interface->assign('suggestedStartingBarcode', $user->max_barcode + 1);
		}else{
			//Suggest a 14 digit barcode
			$interface->assign('suggestedStartingBarcode', 7357000000001);
		}
		$interface->assign('defaultPassword', $defaultPassword ?? 1234);


		$this->display('generateTestUsers.tpl', 'Generate Test Users', 'Greenhouse/greenhouse-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home#greenhouse-testing-tools', 'Testing Tools');
		$breadcrumbs[] = new Breadcrumb('/Testing/GenerateTestUsers', 'Generate Test Users', true);
		return $breadcrumbs;
	}

	function canView() : bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}
}