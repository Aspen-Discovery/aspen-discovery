<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';

global $configArray;

class Record_AJAX extends Action {

	function launch() {
		global $timer;
		global $analytics;
		$analytics->disableTracking();
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$timer->logTime("Starting method $method");
		if (method_exists($this, $method)) {
			// Methods intend to return JSON data
			if (in_array($method, array('getPlaceHoldForm', 'getPlaceHoldEditionsForm', 'getBookMaterialForm', 'placeHold', 'reloadCover', 'bookMaterial'))){
				header('Content-type: text/plain');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->json_utf8_encode($this->$method());
			}else if (in_array($method, array('getBookingCalendar'))){
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			}else if ($method == 'downloadMarc'){
				echo $this->$method();
			}else{
				header ('Content-type: text/xml');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

				$xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
				$xmlResponse .= "<AJAXResponse>\n";
				$xmlResponse .= $this->$_GET['method']();
				$xmlResponse .= '</AJAXResponse>';

				echo $xmlResponse;
			}
		} else {
			$output = json_encode(array('error'=>'invalid_method'));
			echo $output;
		}
	}

	function downloadMarc(){
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

	function IsLoggedIn()
	{
		return "<result>" .
		(UserAccount::isLoggedIn() ? "True" : "False") . "</result>";
	}

	function GetProspectorInfo(){
		require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
		global $configArray;
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();
		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var SearchObject_Solr $db */
		$db = new $class($url);

		// Retrieve Full record from Solr
		if (!($record = $db->getRecord($id))) {
			PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
		}

		$prospector = new Prospector();

		$searchTerms = array(
			array(
				'lookfor' => $record['title'],
				'index' => 'Title'
			),
		);
		if (isset($record['author'])){
			$searchTerms[] = array(
				'lookfor' => $record['author'],
				'index' => 'Author'
			);
		}
		$prospectorResults = $prospector->getTopSearchResults($searchTerms, 10);
		$interface->assign('prospectorResults', $prospectorResults['records']);
		return $interface->fetch('Record/ajax-prospector.tpl');
	}

	function getPlaceHoldForm(){
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])){
				$interface->assign('volume', $_REQUEST['volume']);
			}

			//Get information to show a warning if the user does not have sufficient holds
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$maxHolds = -1;
			//Determine if we should show a warning
			$ptype = new PType();
			$ptype->pType = UserAccount::getUserPType();
			if ($ptype->find(true)){
				$maxHolds = $ptype->maxHolds;
			}
			$currentHolds = $user->numHoldsIls;
			//TODO: this check will need to account for linked accounts now
			if ($maxHolds != -1 && ($currentHolds + 1 > $maxHolds)){
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

			$interface->assign('pickupLocations', $locations);
			$interface->assign('multipleUsers', $multipleAccountPickupLocations); // switch for displaying the account drop-down (used for linked accounts)

			global $library;
			$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
			$interface->assign('defaultNotNeededAfterDays', $library->defaultNotNeededAfterDays);
			$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
			$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

			$holdDisclaimers = array();
			$patronLibrary = $user->getHomeLibrary();
			if (strlen($patronLibrary->holdDisclaimer) > 0){
				$holdDisclaimers[$patronLibrary->displayName] = $patronLibrary->holdDisclaimer;
			}
			foreach ($linkedUsers as $linkedUser){
				$linkedLibrary = $linkedUser->getHomeLibrary();
				if (strlen($linkedLibrary->holdDisclaimer) > 0){
					$holdDisclaimers[$linkedLibrary->displayName] = $linkedLibrary->holdDisclaimer;
				}
			}

			$interface->assign('holdDisclaimers', $holdDisclaimers);

			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$marcRecord = new MarcRecord($id);
			$title = rtrim($marcRecord->getTitle(), ' /');
			$interface->assign('id', $marcRecord->getId());
			if (count($locations) == 0){
				$results = array(
					'title' => 'Unable to place hold',
					'modalBody' => '<p>Sorry, no copies of this title are available to your account.</p>',
					'modalButtons' => ""
				);
			}else{
				$results = array(
					'title' => empty($title) ? 'Place Hold' : 'Place Hold on ' . $title,
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
					'modalButtons' => "<input type='submit' name='submit' id='requestTitleButton' value='Submit Hold Request' class='btn btn-primary' onclick='return VuFind.Record.submitHoldForm();'>"
				);
			}

		}else{
			$results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
					'modalButtons' => ""
			);
		}
		return $results;
	}

	function getPlaceHoldEditionsForm() {
		global $interface;
		if (UserAccount::isLoggedIn()) {

			$id           = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$marcRecord = new MarcRecord($id);
			$groupedWork = $marcRecord->getGroupedWorkDriver();
			$relatedManifestations = $groupedWork->getRelatedManifestations();
			$format = $marcRecord->getFormat();
			$relatedManifestations = $relatedManifestations[$format[0]];
			$interface->assign('relatedManifestation', $relatedManifestations);
			$results = array(
				'title' => 'Place Hold on Alternate Edition?',
				'modalBody' => $interface->fetch('Record/hold-select-edition-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return VuFind.Record.showPlaceHold(\'Record\', \'' . $id . '\', false);">No, place a hold on this edition</a>'
			);
		}else{
			$results = array(
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'modalButtons' => ''
			);
		}
		return $results;
		}

	function getBookMaterialForm($errorMessage = null){
		global $interface;
		if (UserAccount::isLoggedIn()){
			$id = $_REQUEST['id'];

			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$marcRecord = new MarcRecord($id);
			$title = $marcRecord->getTitle();
			$interface->assign('id', $id);
			if ($errorMessage) $interface->assign('errorMessage', $errorMessage);
			$results = array(
					'title' => 'Schedule ' . $title,
					'modalBody' => $interface->fetch("Record/book-materials-form.tpl"),
					'modalButtons' => '<button class="btn btn-primary" onclick="$(\'#bookMaterialForm\').submit()">Schedule Item</button>'
			    // Clicking invokes submit event, which allows the validator to act before calling the ajax handler
			);
		}else{
			$results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login before scheduling this item.",
					'modalButtons' => ""
			);
		}
		return $results;
	}

	function getBookingCalendar(){
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') !== false) list(,$recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		if (!empty($recordId)) {
			$user = UserAccount::getLoggedInUser();
			$catalog = $user->getCatalogDriver();
//			$catalog = CatalogFactory::getCatalogConnectionInstance();
			return $catalog->getBookingCalendar($recordId);
		}
	}

	function bookMaterial(){
		if (!empty($_REQUEST['id'])){
			$recordId = $_REQUEST['id'];
			if (strpos($recordId, ':') !== false) list(,$recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
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
		$endDate   = empty($_REQUEST['endDate'])   ? null : $_REQUEST['endDate'];
		$endTime   = empty($_REQUEST['endTime'])   ? null : $_REQUEST['endTime'];

		$user = UserAccount::getLoggedInUser();
		if ($user) { // The user is already logged in
			return $user->bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime);

		} else {
			return array('success' => false, 'message' => 'User not logged in.');
		}
	}

	function json_utf8_encode($result) { // TODO: add to other ajax.php or make part of a ajax base class
		try {
			require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
			$utf8EncodedValue = ArrayUtils::utf8EncodeArray($result);
			$output           = json_encode($utf8EncodedValue);
			$error            = json_last_error();
			if ($error != JSON_ERROR_NONE || $output === FALSE) {
				if (function_exists('json_last_error_msg')) {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => json_last_error_msg()));
				} else {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => json_last_error()));
				}
				global $configArray;
				if ($configArray['System']['debug']) {
					print_r($utf8EncodedValue);
				}
			}
		}
		catch (Exception $e){
			$output = json_encode(array('error'=>'error_encoding_data', 'message' => $e));
			global $logger;
			$logger->log("Error encoding json data $e", PEAR_LOG_ERR);
		}
		return $output;
	}

	function placeHold(){
		global $interface;
		global $analytics;
		$analytics->enableTracking();
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') > 0){
			list($source, $shortId) = explode(':', $recordId, 2);
		}else{
			$shortId = $recordId;
		}

		$user = UserAccount::getLoggedInUser();
		if ($user){
			//The user is already logged in

			if (!empty($_REQUEST['campus'])) {
			 //Check to see what account we should be placing a hold for
				//Rather than asking the user for this explicitly, we do it based on the pickup location
				$campus = $_REQUEST['campus'];

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
				}
				else {
					//block below sets the $patron variable to place the hold through pick-up location. (shouldn't be needed anymore. plb 10-27-2015)
					$location = new Location();
					/** @var Location[] $userPickupLocations */
					$userPickupLocations = $location->getPickupBranches($user);
					foreach ($userPickupLocations as $tmpLocation) {
						if ($tmpLocation->code == $campus) {
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
								if ($tmpLocation->code == $campus) {
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
						'title'   => 'Select valid user',
					);
				} else {
					$homeLibrary = $patron->getHomeLibrary();

					if (isset($_REQUEST['selectedItem'])) {
						$return = $patron->placeItemHold($shortId, $_REQUEST['selectedItem'], $campus);
					} else {
						if (isset($_REQUEST['volume'])){
							$return = $patron->placeVolumeHold($shortId, $_REQUEST['volume'], $campus);
						}else{

							if (!empty($_REQUEST['canceldate'])){
								$cancelDate = $_REQUEST['canceldate'];
							}else{
								if ($homeLibrary->defaultNotNeededAfterDays == 0){
									//Default to a date 6 months (half a year) in the future.
									$sixMonthsFromNow = time() + 182.5 * 24 * 60 * 60;
									$cancelDate = date('m/d/Y', $sixMonthsFromNow);
								}else{
									//Default to a date 6 months (half a year) in the future.
									$nnaDate = time() + $homeLibrary->defaultNotNeededAfterDays * 24 * 60 * 60;
									$cancelDate = date('m/d/Y', $nnaDate);
								}
							}

							$return = $patron->placeHold($shortId, $campus, $cancelDate);
						}
					}

					if (isset($return['items'])) {
						$interface->assign('campus', $campus);
						$items = $return['items'];
						$interface->assign('items', $items);
						$interface->assign('message', $return['message']);
						$interface->assign('id', $shortId);
						$interface->assign('patronId', $patron->id);
						if (!empty($_REQUEST['autologout'])) $interface->assign('autologout', $_REQUEST['autologout']); // carry user selection to Item Hold Form

//						global $library;
//						$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
//						$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);
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

						//Get library based on patron home library since that is what controls their notifications rather than the active interface.
						//$library = Library::getPatronHomeLibrary();

//						global $library;
//						$canUpdateContactInfo = $library->allowProfileUpdates == 1;
//						// set update permission based on active library's settings. Or allow by default.
//						$canChangeNoticePreference = $library->showNoticeTypeInProfile == 1;
//						// when user preference isn't set, they will be shown a link to account profile. this link isn't needed if the user can not change notification preference.
//						$interface->assign('canUpdate', $canUpdateContactInfo);
//						$interface->assign('canChangeNoticePreference', $canChangeNoticePreference);
//						$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
//						$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

						$canUpdateContactInfo = $homeLibrary->allowProfileUpdates == 1;
						// set update permission based on active library's settings. Or allow by default.
						$canChangeNoticePreference = $homeLibrary->showNoticeTypeInProfile == 1;
						// when user preference isn't set, they will be shown a link to account profile. this link isn't needed if the user can not change notification preference.
						$interface->assign('canUpdate', $canUpdateContactInfo);
						$interface->assign('canChangeNoticePreference', $canChangeNoticePreference);
						$interface->assign('showDetailedHoldNoticeInformation', $homeLibrary->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $homeLibrary->treatPrintNoticesAsPhoneNotices);
						$interface->assign('profile', $patron); // Use the account the hold was placed with for the success message.

						$results = array(
							'success' => $return['success'],
							'message' => $interface->fetch('Record/hold-success-popup.tpl'),
							'title'   => isset($return['title']) ? $return['title'] : '',
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

	function reloadCover(){
		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
		$id = $_REQUEST['id'];
		$recordDriver = new MarcRecord($id);

		//Reload small cover
		$smallCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('small')) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('medium')) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = str_replace('&amp;', '&', $recordDriver->getBookcoverUrl('large')) . '&reload';
		file_get_contents($largeCoverUrl);

		//Also reload covers for the grouped work
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($recordDriver->getGroupedWorkId());
		global $configArray;
		//Reload small cover
		$smallCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('small')) . '&reload';
		file_get_contents($smallCoverUrl);

		//Reload medium cover
		$mediumCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('medium')) . '&reload';
		file_get_contents($mediumCoverUrl);

		//Reload large cover
		$largeCoverUrl = $configArray['Site']['coverUrl'] . str_replace('&amp;', '&', $groupedWorkDriver->getBookcoverUrl('large')) . '&reload';
		file_get_contents($largeCoverUrl);

		return array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.');
	}

}
