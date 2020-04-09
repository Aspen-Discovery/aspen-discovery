<?php

require_once ROOT_DIR . '/Action.php';

global $configArray;

class Record_AJAX extends Action
{

	function launch()
	{
		global $timer;
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$timer->logTime("Starting method $method");
		if (method_exists($this, $method)) {
			// Methods intend to return JSON data
			if (in_array($method, array('getPlaceHoldForm', 'getPlaceHoldEditionsForm', 'getBookMaterialForm', 'placeHold', 'bookMaterial'))) {
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo json_encode($this->$method());
			} else if (in_array($method, array('getBookingCalendar'))) {
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			} else if ($method == 'downloadMarc') {
				echo $this->$method();
			} else {
				header('Content-type: text/xml');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

				$xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
				$xmlResponse .= "<AJAXResponse>\n";
				$xmlResponse .= $this->$_GET['method']();
				$xmlResponse .= '</AJAXResponse>';

				echo $xmlResponse;
			}
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
			echo $output;
		}
	}


	/** @noinspection PhpUnused */
	function downloadMarc()
	{
		$id = $_REQUEST['id'];
		$marcData = MarcLoader::loadMarcRecordByILSId($id);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename={$id}.mrc");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . strlen($marcData->toRaw()));
		ob_clean();
		flush();
		echo($marcData->toRaw());
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldForm()
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			//Get information to show a warning if the user does not have sufficient holds
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$maxHolds = -1;
			//Determine if we should show a warning
			$ptype = new PType();
			$ptype->pType = UserAccount::getUserPType();
			if ($ptype->find(true)) {
				$maxHolds = $ptype->maxHolds;
			}
			$currentHolds = $user->_numHoldsIls;
			//TODO: this check will need to account for linked accounts now
			if ($maxHolds != -1 && ($currentHolds + 1 > $maxHolds)) {
				$interface->assign('showOverHoldLimit', true);
				$interface->assign('maxHolds', $maxHolds);
				$interface->assign('currentHolds', $currentHolds);
			}

			//Check to see if the user has linked users that we can place holds for as well
			//If there are linked users, we will add pickup locations for them as well
			$locations = $user->getValidPickupBranches($recordSource);
			$multipleAccountPickupLocations = false;
			$linkedUsers = $user->getLinkedUsers();
			if (count($linkedUsers)) {
				foreach ($locations as $location) {
					if (count($location->pickupUsers) > 1) {
						$multipleAccountPickupLocations = true;
						break;
					}
				}
			}

			if (!$multipleAccountPickupLocations){
				$rememberHoldPickupLocation = $user->rememberHoldPickupLocation;
				$interface->assign('rememberHoldPickupLocation', $rememberHoldPickupLocation);
			}else{
				$rememberHoldPickupLocation = false;
			}

			$interface->assign('pickupLocations', $locations);
			$interface->assign('multipleUsers', $multipleAccountPickupLocations); // switch for displaying the account drop-down (used for linked accounts)

			global $library;
			$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
			$interface->assign('defaultNotNeededAfterDays', $library->defaultNotNeededAfterDays);
			$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
			$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

			$holdDisclaimers = array();
			$patronLibrary = $user->getHomeLibrary();
			if ($patronLibrary == null){
				$results = array(
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'modalBody' => '<p>This account is not associated with a library, please contact your library.</p>',
					'modalButtons' => ""
				);
				return $results;
			}
			if (strlen($patronLibrary->holdDisclaimer) > 0) {
				$holdDisclaimers[$patronLibrary->displayName] = $patronLibrary->holdDisclaimer;
			}
			foreach ($linkedUsers as $linkedUser) {
				$linkedLibrary = $linkedUser->getHomeLibrary();
				if (strlen($linkedLibrary->holdDisclaimer) > 0) {
					$holdDisclaimers[$linkedLibrary->displayName] = $linkedLibrary->holdDisclaimer;
				}
			}

			$interface->assign('holdDisclaimers', $holdDisclaimers);

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$title = rtrim($marcRecord->getTitle(), ' /');
			$interface->assign('id', $marcRecord->getId());

			//Figure out what types of holds to allow
			$items = $marcRecord->getCopies();
			$format = $marcRecord->getPrimaryFormat();
			/** IndexingProfile[] */
			global $indexingProfiles;
			$indexingProfile = $indexingProfiles[$marcRecord->getRecordType()];
			$formatMap = $indexingProfile->formatMap;
			/** @var FormatMapValue $formatMapValue */
			$holdType = 'bib';
			foreach ($formatMap as $formatMapValue){
				if ($formatMapValue->format == $format){
					$holdType = $formatMapValue->holdType;
				}
			}

			$interface->assign('items', $items);
			$interface->assign('holdType', $holdType);

			//See if we can bypass the holds form
			$bypassHolds = false;
			if ($rememberHoldPickupLocation){
				if ($holdType == 'bib'){
					$bypassHolds = true;
				}elseif ($holdType != 'none' && count($items) == 1 ){
					$bypassHolds = true;
				}
			}

			if ($bypassHolds){
				if ($holdType == 'item' && isset($_REQUEST['selectedItem'])) {
					$results = $user->placeItemHold($id, $_REQUEST['selectedItem'], $user->_homeLocationCode, null);
				} else {
					if (isset($_REQUEST['volume'])){
						$results = $user->placeVolumeHold($id, $_REQUEST['volume'], $user->_homeLocationCode);
					}else{
						$results = $user->placeHold($id, $user->_homeLocationCode, null);
					}
				}
				$results['holdFormBypassed'] = true;
			}else if (count($locations) == 0) {
				$results = array(
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'modalBody' => '<p>Sorry, no copies of this title are available to your account.</p>',
					'modalButtons' => ""
				);
			} else {
				$results = array(
					'holdFormBypassed' => false,
					'title' => empty($title) ? 'Place Hold' : 'Place Hold on ' . $title,
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
				);
				if ($holdType != 'none'){
					$results['modalButtons'] = "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'>" . translate("Submit Hold Request") . "</button>";
				}
			}

		} else {
			$results = array(
				'holdFormBypassed' => false,
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'modalButtons' => ""
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldEditionsForm()
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {

			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$groupedWork = $marcRecord->getGroupedWorkDriver();
			$relatedManifestations = $groupedWork->getRelatedManifestations();
			$format = $marcRecord->getFormat();
			$relatedManifestations = $relatedManifestations[$format[0]];
			$interface->assign('relatedManifestation', $relatedManifestations);
			$results = array(
				'title' => 'Place Hold on Alternate Edition?',
				'modalBody' => $interface->fetch('Record/hold-select-edition-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.showPlaceHold(\'Record\', \'' . $recordSource . '\', \'' . $id . '\');">No, place a hold on this edition</a>'
			);
		} else {
			$results = array(
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'modalButtons' => ''
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getBookMaterialForm($errorMessage = null)
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$title = $marcRecord->getTitle();
			$interface->assign('id', $id);
			if ($errorMessage) $interface->assign('errorMessage', $errorMessage);
			$results = array(
				'title' => 'Schedule ' . $title,
				'modalBody' => $interface->fetch("Record/book-materials-form.tpl"),
				'modalButtons' => '<button class="btn btn-primary" onclick="$(\'#bookMaterialForm\').submit()">Schedule Item</button>'
				// Clicking invokes submit event, which allows the validator to act before calling the ajax handler
			);
		} else {
			$results = array(
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before scheduling this item.",
				'modalButtons' => ""
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getBookingCalendar()
	{
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') !== false) list(, $recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		if (!empty($recordId)) {
			$user = UserAccount::getLoggedInUser();
			$catalog = $user->getCatalogDriver();
			return $catalog->getBookingCalendar($recordId);
		}
		return null;
	}

	/** @noinspection PhpUnused */
	function bookMaterial()
	{
		if (!empty($_REQUEST['id'])) {
			$recordId = $_REQUEST['id'];
			if (strpos($recordId, ':') !== false) list(, $recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		}
		if (empty($recordId)) {
			return array('success' => false, 'message' => 'Item ID is required.');
		}
		if (isset($_REQUEST['startDate'])) {
			$startDate = $_REQUEST['startDate'];
		} else {
			return array('success' => false, 'message' => 'Start Date is required.');
		}

		$startTime = empty($_REQUEST['startTime']) ? null : $_REQUEST['startTime'];
		$endDate = empty($_REQUEST['endDate']) ? null : $_REQUEST['endDate'];
		$endTime = empty($_REQUEST['endTime']) ? null : $_REQUEST['endTime'];

		$user = UserAccount::getLoggedInUser();
		if ($user) { // The user is already logged in
			return $user->bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime);

		} else {
			return array('success' => false, 'message' => 'User not logged in.');
		}
	}

	function placeHold()
	{
		global $interface;
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') > 0) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($source, $shortId) = explode(':', $recordId, 2);
		} else {
			$shortId = $recordId;
		}

		$user = UserAccount::getLoggedInUser();
		if ($user) {
			//The user is already logged in

			if (!empty($_REQUEST['pickupBranch'])) {
				//Check to see what account we should be placing a hold for
				//Rather than asking the user for this explicitly, we do it based on the pickup location
				$pickupBranch = $_REQUEST['pickupBranch'];

				$patron = null;
				if (!empty($_REQUEST['selectedUser'])) {
					$selectedUserId = $_REQUEST['selectedUser'];
					if (is_numeric($selectedUserId)) { // we expect an id
						if ($user->id == $selectedUserId) {
							$patron = $user;
						} else {
							$linkedUsers = $user->getLinkedUsers();
							foreach ($linkedUsers as $tmpUser) {
								if ($tmpUser->id == $selectedUserId) {
									$patron = $tmpUser;
									break;
								}
							}
						}
					}
				} else {
					//block below sets the $patron variable to place the hold through pick-up location. (shouldn't be needed anymore. plb 10-27-2015)
					$location = new Location();
					/** @var Location[] $userPickupLocations */
					$userPickupLocations = $location->getPickupBranches($user);
					foreach ($userPickupLocations as $tmpLocation) {
						if ($tmpLocation->code == $pickupBranch) {
							$patron = $user;
							break;
						}
					}
					if ($patron == null) {
						//Check linked users
						$linkedUsers = $user->getLinkedUsers();
						foreach ($linkedUsers as $tmpUser) {
							$location = new Location();
							/** @var Location[] $userPickupLocations */
							$userPickupLocations = $location->getPickupBranches($tmpUser);
							foreach ($userPickupLocations as $tmpLocation) {
								if ($tmpLocation->code == $pickupBranch) {
									$patron = $tmpUser;
									break;
								}
							}
							if ($patron != null) {
								break;
							}
						}
					}
				}
				if ($patron == null) {
					$results = array(
						'success' => false,
						'message' => 'You must select a valid user to place the hold for.',
						'title' => 'Select valid user',
					);
				} else {
					$homeLibrary = $patron->getHomeLibrary();
					if (isset($_REQUEST['rememberHoldPickupLocation']) && ($_REQUEST['rememberHoldPickupLocation'] == 'true' || $_REQUEST['rememberHoldPickupLocation'] == 'on')){
						if ($user->rememberHoldPickupLocation == false){
							$user->rememberHoldPickupLocation = true;

							//Get the branch id for the hold
							$holdBranch = new Location();
							$holdBranch->code = $pickupBranch;
							if ($holdBranch->find(true)){
								$user->homeLocationId = $holdBranch->locationId;
								$user->_homeLocationCode = $holdBranch->code;
							}
							$user->update();
						}
					}
					$holdType = $_REQUEST['holdType'];

					if (!empty($_REQUEST['cancelDate'])) {
						$cancelDate = $_REQUEST['cancelDate'];
					} else {
						if ($homeLibrary->defaultNotNeededAfterDays <= 0) {
							$cancelDate = null;
						} else {
							//Default to a date 6 months (half a year) in the future.
							$nnaDate = time() + $homeLibrary->defaultNotNeededAfterDays * 24 * 60 * 60;
							$cancelDate = date('m/d/Y', $nnaDate);
						}
					}

					if ($holdType == 'item' && isset($_REQUEST['selectedItem'])) {
						$return = $patron->placeItemHold($shortId, $_REQUEST['selectedItem'], $pickupBranch, $cancelDate);
					} else {
						if (isset($_REQUEST['volume'])) {
							$return = $patron->placeVolumeHold($shortId, $_REQUEST['volume'], $pickupBranch);
						} else {
							$return = $patron->placeHold($shortId, $pickupBranch, $cancelDate);
						}
					}

					if (isset($return['items'])) {
						$interface->assign('pickupBranch', $pickupBranch);
						$items = $return['items'];
						$interface->assign('items', $items);
						$interface->assign('message', $return['message']);
						$interface->assign('id', $shortId);
						$interface->assign('patronId', $patron->id);
						if (!empty($_REQUEST['autologout'])) $interface->assign('autologout', $_REQUEST['autologout']); // carry user selection to Item Hold Form

						$interface->assign('showDetailedHoldNoticeInformation', $homeLibrary->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $homeLibrary->treatPrintNoticesAsPhoneNotices);

						// Need to place item level holds.
						$results = array(
							'success' => true,
							'needsItemLevelHold' => true,
							'message' => $interface->fetch('Record/item-hold-popup.tpl'),
							'title' => isset($return['title']) ? $return['title'] : '',
						);
					} else { // Completed Hold Attempt
						$interface->assign('message', $return['message']);
						$interface->assign('success', $return['success']);

						$canUpdateContactInfo = $homeLibrary->allowProfileUpdates == 1;
						// set update permission based on active library's settings. Or allow by default.
						$canChangeNoticePreference = $homeLibrary->showNoticeTypeInProfile == 1;
						// when user preference isn't set, they will be shown a link to account profile. this link isn't needed if the user can not change notification preference.
						$interface->assign('canUpdate', $canUpdateContactInfo);
						$interface->assign('canChangeNoticePreference', $canChangeNoticePreference);
						$interface->assign('showDetailedHoldNoticeInformation', $homeLibrary->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $homeLibrary->treatPrintNoticesAsPhoneNotices);
						$interface->assign('profile', $patron);

						$results = array(
							'success' => $return['success'],
							'message' => $interface->fetch('Record/hold-success-popup.tpl'),
							'title' => isset($return['title']) ? $return['title'] : '',
						);
						if (isset($_REQUEST['autologout'])) {
							$masqueradeMode = UserAccount::isUserMasquerading();
							if ($masqueradeMode) {
								require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
								MyAccount_Masquerade::endMasquerade();
							} else {
								UserAccount::softLogout();
							}
							$results['autologout'] = true;
							unset($_REQUEST['autologout']); // Prevent entering the second auto log out code-block below.
						}
					}
				}
			} else {
				$results = array(
					'success' => false,
					'message' => 'No pick-up location is set.  Please choose a Location for the title to be picked up at.',
				);
			}

			if (isset($_REQUEST['autologout']) && !(isset($results['needsItemLevelHold']) && $results['needsItemLevelHold'])) {
				// Only go through the auto-logout when the holds process is completed. Item level holds require another round of interaction with the user.
				$masqueradeMode = UserAccount::isUserMasquerading();
				if ($masqueradeMode) {
					require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
					MyAccount_Masquerade::endMasquerade();
				} else {
					UserAccount::softLogout();
				}
				$results['autologout'] = true;
			}
		} else {
			$results = array(
				'success' => false,
				'message' => 'You must be logged in to place a hold.  Please close this dialog and login.',
				'title' => 'Please login',
			);
		}
		return $results;
	}

}
