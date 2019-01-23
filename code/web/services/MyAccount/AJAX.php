<?php
/**
 * Asynchronous functionality for MyAccount module
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/25/14
 * Time: 4:26 PM
 */

class MyAccount_AJAX
{
	function launch()
	{
		// not checked below refer to testing returning results through utf8 encoding. plb 2-6-2015
		$valid_json_methods = array(
			'GetSuggestions', // not checked
			'GetListTitles', // only used by MyAccount/ImportListsFromClassic.php && ajax.js //not checked
			'getOverDriveSummary', //called by getOverDriveSummary() is scripts.js // not checked
			'GetPreferredBranches', //not checked
//			'clearUserRating', //no function found.
			'requestPinReset', //not checked
			'getCreateListForm', 'getBulkAddToListForm', 'AddList',
			'getEmailMyListForm', 'sendMyListEmail', 'setListEntryPositions',
			'removeTag',
			'saveSearch', 'deleteSavedSearch', // deleteSavedSearch not checked
			'confirmCancelHold', 'cancelHold', 'cancelHolds', 'freezeHold', 'thawHold', 'getChangeHoldLocationForm', 'changeHoldLocation',
			'getReactivationDateForm', //not checked
			'renewItem', 'renewAll', 'renewSelectedItems', 'getPinResetForm',
			'getAddAccountLinkForm', 'addAccountLink', 'removeAccountLink',
			'cancelBooking', 'getCitationFormatsForm', 'getAddBrowseCategoryFromListForm'
		  ,'getMasqueradeAsForm', 'initiateMasquerade', 'endMasquerade', 'getMenuData'
		);
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			if (in_array($method, $valid_json_methods)) {
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$result = $this->$method();
				try {
					require_once ROOT_DIR . '/sys/Utils/ArrayUtils.php';
					$utf8EncodedValue = ArrayUtils::utf8EncodeArray($result);
					$output = json_encode($utf8EncodedValue);
					$error = json_last_error();
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
				} catch (Exception $e) {
					$output = json_encode(array('error' => 'error_encoding_data', 'message' => $e));
					global $logger;
					$logger->log("Error encoding json data $e", PEAR_LOG_ERR);
				}
				echo $output;

			} elseif (in_array($method, array('LoginForm', 'getBulkAddToListForm', 'getPinUpdateForm'))) {
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			} else {
				header('Content-type: text/xml');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
						"<AJAXResponse>\n";
				$xml .= $this->$_GET['method']();
				$xml .= '</AJAXResponse>';

				echo $xml;
			}
		}else {
			echo json_encode(array('error'=>'invalid_method'));
		}
	}

	function getAddBrowseCategoryFromListForm(){
		global $interface;

		// Select List Creation using Object Editor functions
		require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
		$temp = SubBrowseCategories::getObjectStructure();
		$temp['subCategoryId']['values'] = array(0 => 'Select One') + $temp['subCategoryId']['values'];
		// add default option that denotes nothing has been selected to the options list
		// (this preserves the keys' numeric values (which is essential as they are the Id values) as well as the array's order)
		// btw addition of arrays is kinda a cool trick.
		$interface->assign('propName', 'addAsSubCategoryOf');
		$interface->assign('property', $temp['subCategoryId']);

		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		$results = array(
				'title' => 'Add as Browse Category to Home Page',
				'modalBody' => $interface->fetch('Browse/addBrowseCategoryForm.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>Create Category</button>"
		);
		return $results;
	}

	function addAccountLink(){
		if (!UserAccount::isLoggedIn()){
			$result = array(
				'result' => false,
				'message' => 'Sorry, you must be logged in to manage accounts.'
			);
		}else{
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];

			$accountToLink = UserAccount::validateAccount($username, $password);

			if ($accountToLink){
				$user = UserAccount::getLoggedInUser();
				$addResult = $user->addLinkedUser($accountToLink);
				if ($addResult === true) {
					$result = array(
						'result' => true,
						'message' => 'Successfully linked accounts.'
					);
				}else { // insert failure or user is blocked from linking account or account & account to link are the same account
					$result = array(
						'result' => false,
						'message' => 'Sorry, we could not link to that account.  Accounts cannot be linked if all libraries do not allow account linking.  Please contact your local library if you have questions.'
					);
				}
			}else{
				$result = array(
					'result' => false,
					'message' => 'Sorry, we could not find a user with that information to link to.'
				);
			}
		}

		return $result;
	}

	function removeAccountLink(){
		if (!UserAccount::isLoggedIn()){
			$result = array(
				'result' => false,
				'message' => 'Sorry, you must be logged in to manage accounts.'
			);
		}else{
			$accountToRemove = $_REQUEST['idToRemove'];
			$user = UserAccount::getLoggedInUser();
			if ($user->removeLinkedUser($accountToRemove)){
				$result = array(
					'result' => true,
					'message' => 'Successfully removed linked account.'
				);
			}else{
				$result = array(
					'result' => false,
					'message' => 'Sorry, we could remove that account.'
				);
			}
		}
		return $result;
	}

	function getAddAccountLinkForm(){
		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', 0);
		if (isset($library)){
			$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name'));
			$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));
		}else{
			$interface->assign('usernameLabel', 'Name');
			$interface->assign('passwordLabel', 'Library Card Number');
		}
		// Display Page
		$formDefinition = array(
			'title' => 'Account to Manage',
			'modalBody' => $interface->fetch('MyAccount/addAccountLink.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Account.processAddLinkedUser(); return false;'>Add Account</span>"
		);
		return $formDefinition;
	}

	function getBulkAddToListForm()	{
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		$interface->assign('popupTitle', 'Add titles to list');
		$formDefinition = array(
			'title' => 'Add titles to list',
			'modalBody' => $interface->fetch('MyAccount/bulkAddToListPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Lists.processBulkAddForm(); return false;'>Add To List</span>"
		);
		return $formDefinition;
	}

	// TODO: Clean-up: No Calls to this method were found. plb 2-1-2016
	function getPinResetForm(){
		global $interface;
		$interface->assign('popupTitle', 'Reset PIN Request');

		$formDefinition = array(
			'title' => 'Reset PIN',
			'modalBody' => $interface->fetch('MyAccount/resetPinPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Account.resetPinReset(); return false;'>Add To List</span>"
		);
		return $formDefinition;
//		$pageContent = $interface->fetch('MyResearch/resetPinPopup.tpl');
//		$interface->assign('popupContent', $pageContent);
//		return $interface->fetch('popup-wrapper.tpl');
	}

	function removeTag()
	{
		if (UserAccount::isLoggedIn()) {
			$tagToRemove = $_REQUEST['tag'];

			require_once ROOT_DIR . '/sys/LocalEnrichment/UserTag.php';
			$userTag = new UserTag();
			$userTag->tag = $tagToRemove;
			$userTag->userId = UserAccount::getActiveUserId();
			$numDeleted = $userTag->delete();
			$result = array(
					'result' => true,
					'message' => "Removed tag '{$tagToRemove}' from $numDeleted titles."
			);
		}else{
			$result = array(
					'result' => false,
					'message' => "Please login to remove a tag."
			);
		}
		return $result;
	}

	function saveSearch()
	{
		$searchId = $_REQUEST['searchId'];
		$search = new SearchEntry();
		$search->id = $searchId;
		$saveOk = false;
		if ($search->find(true)) {
			// Found, make sure this is a search from this user
			if ($search->session_id == session_id() || $search->user_id == UserAccount::getActiveUserId()) {
				if ($search->saved != 1) {
					$search->user_id = UserAccount::getActiveUserId();
					$search->saved = 1;
					$saveOk = ($search->update() !== FALSE);
					$message = $saveOk ? 'Your search was saved successfully.  You can view the saved search by clicking on <a href="/Search/History?require_login">Search History</a> within '.translate('My Account').'.' : "Sorry, we could not save that search for you.  It may have expired.";
				} else {
					$saveOk = true;
					$message = "That search was already saved.";
				}
			} else {
				$message = "Sorry, it looks like that search does not belong to you.";
			}
		} else {
			$message = "Sorry, it looks like that search has expired.";
		}
		$result = array(
			'result' => $saveOk,
			'message' => $message,
		);
		return $result;
	}

	function deleteSavedSearch()
	{
		$searchId = $_REQUEST['searchId'];
		$search = new SearchEntry();
		$search->id = $searchId;
		$saveOk = false;
		if ($search->find(true)) {
			// Found, make sure this is a search from this user
			if ($search->session_id == session_id() || $search->user_id == UserAccount::getActiveUserId()) {
				if ($search->saved != 0) {
					$search->saved = 0;
					$saveOk = ($search->update() !== FALSE);
					$message = $saveOk ? "Your saved search was deleted successfully." : "Sorry, we could not delete that search for you.  It may have already been deleted.";
				} else {
					$saveOk = true;
					$message = "That search is not saved.";
				}
			} else {
				$message = "Sorry, it looks like that search does not belong to you.";
			}
		} else {
			$message = "Sorry, it looks like that search has expired.";
		}
		$result = array(
			'result' => $saveOk,
			'message' => $message,
		);
		return $result;
	}

	function confirmCancelHold(){
		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'];
		$cancelId = $_REQUEST['cancelId'];
		$cancelButtonLabel = translate('Confirm Cancel Hold');
		return array(
				'title' => translate('Cancel Hold'),
				'body' => translate("Are you sure you want to cancel this hold?"),
				'buttons' => "<span class='tool btn btn-primary' onclick='VuFind.Account.cancelHold(\"$patronId\", \"$recordId\", \"$cancelId\")'>$cancelButtonLabel</span>",
		);
	}

	function cancelHold() {
		$result = array(
			'success' => false,
			'message' => 'Error cancelling hold.'
		);

		if (!UserAccount::isLoggedIn()){
			$result['message'] = 'You must be logged in to cancel a hold.  Please close this dialog and login again.';
		}else{
			//Determine which user the hold is on so we can cancel it.
			$patronId = $_REQUEST['patronId'];
			$user = UserAccount::getLoggedInUser();
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false){
				$result['message'] = 'Sorry, you do not have access to cancel holds for the supplied user.';
			}else{
				//MDN 9/20/2015 The recordId can be empty for Prospector holds
				if (empty($_REQUEST['cancelId']) && empty($_REQUEST['recordId'])) {
					$result['message'] = 'Information about the hold to be cancelled was not provided.';
				}else{
					$cancelId = $_REQUEST['cancelId'];
					$recordId = $_REQUEST['recordId'];
					$result = $patronOwningHold->cancelHold($recordId, $cancelId);
				}
			}
		}

		global $interface;
		// if title come back a single item array, set as the title instead. likewise for message
		if (isset($result['title'])){
			if (is_array($result['title']) && count($result['title']) == 1) $result['title'] = current($result['title']);
		}
		if (is_array($result['message']) && count($result['message']) == 1) $result['message'] = current($result['message']);

		$interface->assign('cancelResults', $result);

		$cancelResult = array(
			'title' => 'Cancel Hold',
			'body' => $interface->fetch('MyAccount/cancelhold.tpl'),
			'success' => $result['success']
		);
		return $cancelResult;
	}

	function cancelBooking() {
		try {
			$user = UserAccount::getLoggedInUser();

			if (!empty($_REQUEST['cancelAll']) && $_REQUEST['cancelAll'] == 1) {
				$result = $user->cancelAllBookedMaterial();
				$totalCancelled = $numCancelled = null;
			} else {
				$cancelIds = !empty($_REQUEST['cancelId']) ? $_REQUEST['cancelId'] : array();

				$totalCancelled = 0;
				$numCancelled = 0;
				$result = array(
					'success' => true,
					'message' => 'Your scheduled items were successfully canceled.'
				);
				foreach ($cancelIds as $userId => $cancelId) {
					$patron = $user->getUserReferredTo($userId);
					$userResult      = $patron->cancelBookedMaterial($cancelId);
					$numCancelled   += $userResult['success'] ? count($cancelId) : count($cancelId) - count($userResult['message']);
					$totalCancelled += count($cancelId);
					// either all were canceled or total canceled minus the number of errors (1 error per failure)

					if (!$userResult['success']) {
						if ($result['success']) { // the first failure
							$result = $userResult;
						} else { // additional failures
							$result['message'] = array_merge($result['message'], $userResult['message']);
						}
					}
				}
			}
		} catch (PDOException $e) {
			/** @var Logger $logger */
			global $logger;
			$logger->log('Booking : '.$e->getMessage(), PEAR_LOG_ERR);

			$result = array(
				'success' => false,
				'message' => 'We could not connect to the circulation system, please try again later.'
			);
		}
		$failed = (!$result['success'] && is_array($result['message']) && !empty($result['message'])) ? array_keys($result['message']) : null; //returns failed id for javascript function

		global $interface;
		$interface->assign('cancelResults', $result);
		$interface->assign('numCancelled', $numCancelled);
		$interface->assign('totalCancelled', $totalCancelled);

		$cancelResult = array(
			'title' => 'Cancel Booking',
			'modalBody' => $interface->fetch('MyAccount/cancelBooking.tpl'),
			'success' => $result['success'],
			'failed' => $failed
		);
		return $cancelResult;
	}

	function cancelHolds() { // for cancelling multiple holds
		try {
			global $configArray;
			$user = UserAccount::getLoggedInUser();
			$catalog = CatalogFactory::getCatalogConnectionInstance();

			// ids grabbed in MillenniumHolds.php in $_REQUEST['waitingholdselected'] & $_REQUEST['availableholdselected']
			// but we will pass ids here instead.
			$cancelId = array();
			if (!empty($_REQUEST['holdselected'])) {
				$cancelId = $_REQUEST['holdselected'];
			}
//			$locationId = isset($_REQUEST['location']) ? $_REQUEST['location'] : null; //not passed via ajax. don't think it's needed
			$result = $catalog->driver->updateHoldDetailed($user->password, 'cancel', $cancelId, null);

		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
			$result = array(
				'result' => false,
				'message' => 'We could not connect to the circulation system, please try again later.'
			);
		}
		if (is_array($result['title'])) { // avoid some naming confusion
			$result['titles'] = $result['title'];
			unset($result['title']);
		}
		global $interface;
		$result['success'] = $result['success']; // makes template easier to understand
		$failed = (is_array($result['message']) && !empty($result['message'])) ? array_keys($result['message']) : null; //returns failed id for javascript function
		if (isset($result['titles'])) {
			$result['numCancelled'] = count($result['titles']) - count($failed);
		}
		$interface->assign('cancelResults', $result);

		$cancelResult = array(
			'title' => 'Cancel Hold',
			'modalBody' => $interface->fetch('MyAccount/cancelhold.tpl'),
			'success' => $result['success'],
		  'failed' => $failed
		);
		return $cancelResult;
	}

	function freezeHold() {
		$user = UserAccount::getLoggedInUser();
		$result = array(
			'success' => false,
			'message' => 'Error '.translate('freezing').' hold.'
		);
		if (!$user){
			$result['message'] = 'You must be logged in to '. translate('freeze') .' a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false){
				$result['message'] = 'Sorry, you do not have access to '. translate('freeze') .' holds for the supplied user.';
			}else{
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					global $logger;
					$logger->log('Freeze Hold, no record or hold Id was passed in AJAX call.', PEAR_LOG_ERR);
					$result['message'] = 'Information about the hold to be '. translate('frozen') .' was not provided.';
				}else{
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					$reactivationDate = isset($_REQUEST['reactivationDate']) ? $_REQUEST['reactivationDate'] : null;
					$result = $patronOwningHold->freezeHold($recordId, $holdId, $reactivationDate);
					if ($result['success']) {
						$notice = translate('freeze_info_notice');
						if (translate('frozen') != 'frozen') {
							$notice = str_replace('frozen', translate('frozen'), $notice);  // Translate the phrase frozen from the notice.
						}
						$message = '<div class="alert alert-success">'.$result['message'] .'</div>'. ($notice ? '<div class="alert alert-info">'.$notice .'</div>' : '');
						$result['message'] = $message;
					}

					if (!$result['success'] && is_array($result['message'])) {
						$result['message'] = implode('; ', $result['message']);
						// Millennium Holds assumes there can be more than one item processed. Here we know only one got processed,
						// but do implode as a fallback
					}
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Freeze Hold, no patron Id was passed in AJAX call.', PEAR_LOG_ERR);
			$result['message'] = 'No Patron was specified.';
		}

		return $result;
	}

	function thawHold() {
		$user = UserAccount::getLoggedInUser();
		$result = array( // set default response
			'success' => false,
			'message' => 'Error thawing hold.'
		);

		if (!$user){
			$result['message'] = 'You must be logged in to '. translate('thaw') .' a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false){
				$result['message'] = 'Sorry, you do not have access to '. translate('thaw') .' holds for the supplied user.';
			}else{
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					$result['message'] = 'Information about the hold to be '. translate('thawed') .' was not provided.';
				}else{
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					$result = $patronOwningHold->thawHold($recordId, $holdId);
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Thaw Hold, no patron Id was passed in AJAX call.', PEAR_LOG_ERR);
			$result['message'] = 'No Patron was specified.';
		}

		return $result;
	}

	//TODO: Review these methods to see what can be deleted
	// Create new list
	function AddList()
	{
		$return = array();
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			$title = (isset($_REQUEST['title']) && !is_array($_REQUEST['title'])) ? urldecode($_REQUEST['title']) : '';
			if (strlen(trim($title)) == 0) {
				$return['success'] = "false";
				$return['message'] = "You must provide a title for the list";
			} else {
				//If the record is not valid, skip the whole thing since the title could be bad too
				if (isset($_REQUEST['recordId']) && !is_array($_REQUEST['recordId'])) {
					$recordToAdd = urldecode($_REQUEST['recordId']);
					if (!preg_match("/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}|[A-Z0-9_-]+:[A-Z0-9_-]+$/i", $recordToAdd)) {
						$return['success'] = false;
						$return['message'] = 'The title provided is not valid';
						return $return;
					}
				}

				$list = new UserList();
				$list->title = strip_tags($title);
				$list->user_id = $user->id;
				//Check to see if there is already a list with this id
				$existingList = false;
				if ($list->find(true)) {
					$existingList = true;
				}
				if (isset($_REQUEST['desc'])){
					$desc = $_REQUEST['desc'];
					if (is_array($desc)){
						$desc = reset($desc);
					}
				}else{
					$desc = "";
				}

				$list->description = strip_tags(urldecode($desc));
				$list->public = isset($_REQUEST['public']) && $_REQUEST['public'] == 'true';
				if ($existingList) {
					$list->update();
				} else {
					$list->insert();
				}

				if (isset($_REQUEST['recordId']) && !is_array($_REQUEST['recordId'])) {
					$recordToAdd = urldecode($_REQUEST['recordId']);
					require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
					//Check to see if the user has already added the title to the list.
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
					$userListEntry->groupedWorkPermanentId = $recordToAdd;
					if (!$userListEntry->find(true)) {
						$userListEntry->dateAdded = time();
						$userListEntry->insert();
					}
				}

				$return['success'] = 'true';
				$return['newId'] = $list->id;
				if ($existingList) {
					$return['message'] = "Updated list {$title} successfully";
				} else {
					$return['message'] = "Created list {$title} successfully";
				}
			}
		} else {
			$return['success'] = "false";
			$return['message'] = "You must be logged in to create a list";
		}

		return $return;
	}

	function getCreateListForm()
	{
		global $interface;

		if (isset($_REQUEST['recordId'])){
			$id = $_REQUEST['recordId'];
			$interface->assign('recordId', $id);
		}else{
			$id = '';
		}

		$results = array(
			'title' => 'Create new List',
			'modalBody' => $interface->fetch("MyResearch/list-form.tpl"),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='VuFind.Account.addList(\"{$id}\"); return false;'>Create List</span>"
		);
		return $results;
	}

	/**
	 * Get a list of preferred hold pickup branches for a user.
	 *
	 * @return string XML representing the pickup branches.
	 */
	function GetPreferredBranches()
	{
		require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';
		global $configArray;

		try {
			$catalog = CatalogFactory::getCatalogConnectionInstance();
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		$username = $_REQUEST['username'];
		$password = $_REQUEST['barcode'];

		//Get the list of pickup branch locations for display in the user interface.
		$patron = UserAccount::validateAccount($username, $password);
		if ($patron == null) {
			$result = array(
				'PickupLocations' => array(),
				'loginFailed' => true
			);
		} else {
			$location = new Location();
			$locationList = $location->getPickupBranches($patron, $patron->homeLocationId);
			$pickupLocations = array();
			foreach ($locationList as $curLocation) {
				$pickupLocations[] = array(
					'id' => $curLocation->locationId,
					'displayName' => $curLocation->displayName,
					'selected' => $curLocation->selected,
				);
			}
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$maxHolds = -1;
			//Determine if we should show a warning
			$ptype = new PType();
			$ptype->pType = $patron->patronType;
			if ($ptype->find(true)) {
				$maxHolds = $ptype->maxHolds;
			}
			$currentHolds = $patron->getNumHoldsTotal(false);
			$holdCount = $_REQUEST['holdCount'];
			$showOverHoldLimit = false;
			if ($maxHolds != -1 && ($currentHolds + $holdCount > $maxHolds)) {
				$showOverHoldLimit = true;
			}

			//Also determine if the hold can be cancelled.
			/* var Library $librarySingleton */
			global $librarySingleton;
			$patronHomeBranch = $librarySingleton->getPatronHomeLibrary();
			$showHoldCancelDate = 0;
			if ($patronHomeBranch != null) {
				$showHoldCancelDate = $patronHomeBranch->showHoldCancelDate;
			}
			$result = array(
				'PickupLocations' => $pickupLocations,
				'loginFailed' => false,
				'AllowHoldCancellation' => $showHoldCancelDate,
				'showOverHoldLimit' => $showOverHoldLimit,
				'maxHolds' => $maxHolds,
				'currentHolds' => $currentHolds
			);
		}
		return $result;
	}

	function GetSuggestions()
	{
		global $interface;
		global $library;
		global $configArray;

		//Make sure to initialize solr
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init();

		//Get suggestions for the user
		$suggestions = Suggestions::getSuggestions();
		$interface->assign('suggestions', $suggestions);
		if (isset($library)) {
			$interface->assign('showRatings', $library->showRatings);
		} else {
			$interface->assign('showRatings', 1);
		}

		//return suggestions as json for display in the title scroller
		$titles = array();
		foreach ($suggestions as $suggestion) {
			$titles[] = array(
				'id' => $suggestion['titleInfo']['id'],
				'image' => $configArray['Site']['coverUrl'] . "/bookcover.php?id=" . $suggestion['titleInfo']['id'] . "&issn=" . $suggestion['titleInfo']['issn'] . "&isn=" . $suggestion['titleInfo']['isbn10'] . "&size=medium&upc=" . $suggestion['titleInfo']['upc'] . "&category=" . $suggestion['titleInfo']['format_category'][0],
				'title' => $suggestion['titleInfo']['title'],
				'author' => $suggestion['titleInfo']['author'],
				'basedOn' => $suggestion['basedOn']
			);
		}

		foreach ($titles as $key => $rawData) {
			$formattedTitle = "<div id=\"scrollerTitleSuggestion{$key}\" class=\"scrollerTitle\">" .
				'<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . '" id="descriptionTrigger' . $rawData['id'] . '">' .
				"<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
				"</a></div>" .
				"<div id='descriptionPlaceholder{$rawData['id']}' style='display:none'></div>";
			$rawData['formattedTitle'] = $formattedTitle;
			$titles[$key] = $rawData;
		}

		$return = array('titles' => $titles, 'currentIndex' => 0);
		return $return;
	}

	function GetListTitles()
	{
		/** @var MemCache $memCache */
		global $memCache;
		global $configArray;
		global $timer;

		$listId = $_REQUEST['listId'];
		$_REQUEST['id'] = 'list:' . $listId;
		$listName = strip_tags(isset($_GET['scrollerName']) ? $_GET['scrollerName'] : 'List' . $listId);
		$scrollerName = isset($_GET['scrollerName']) ? strip_tags($_GET['scrollerName']) : $listName;

		//Determine the caching parameters
		require_once(ROOT_DIR . '/services/API/ListAPI.php');
		$listAPI = new ListAPI();
		$cacheInfo = $listAPI->getCacheInfoForList();

		$listData = $memCache->get($cacheInfo['cacheName']);

		$return = false; // default response
		if (!$listData || isset($_REQUEST['reload']) || (isset($listData['titles']) && count($listData['titles']) == 0)) {
			global $interface;

			$titles = $listAPI->getListTitles();
			$timer->logTime("getListTitles");
			$addStrandsTracking = false;
			if ($titles['success'] == true) {
				if (isset($titles['strands'])) {
					$addStrandsTracking = true;
					$strandsInfo = $titles['strands'];
				}
				$titles = $titles['titles'];
				if (is_array($titles)) {
					foreach ($titles as $key => $rawData) {

						$interface->assign('title', $rawData['title']);
//						$interface->assign('description', $rawData['description'] . 'w00t!');
						$interface->assign('description', $rawData['description']); // Looks like not in use currently
						$interface->assign('length', $rawData['length']);
						$interface->assign('publisher', $rawData['publisher']);
						$descriptionInfo = $interface->fetch('Record/ajax-description-popup.tpl');

						$formattedTitle = "<div id=\"scrollerTitle{$scrollerName}{$key}\" class=\"scrollerTitle\">";
						$shortId = $rawData['id'];
						$shortId = str_replace('.b', 'b', $shortId);
						$formattedTitle .= '<a href="' . $configArray['Site']['path'] . "/Record/" . $rawData['id'] . ($addStrandsTracking ? "?strandsReqId={$strandsInfo['reqId']}&strandsTpl={$strandsInfo['tpl']}" : '') . '" id="descriptionTrigger' . $shortId . '">';
						$formattedTitle .= "<img src=\"{$rawData['image']}\" class=\"scrollerTitleCover\" alt=\"{$rawData['title']} Cover\"/>" .
							"</a></div>" .
							"<div id='descriptionPlaceholder{$shortId}' style='display:none' class='loaded'>" .
							$descriptionInfo .
							"</div>";
						$rawData['formattedTitle'] = $formattedTitle;
						$titles[$key] = $rawData;
					}
				}
				$currentIndex = count($titles) > 5 ? floor(count($titles) / 2) : 0;

				$return = array('titles' => $titles, 'currentIndex' => $currentIndex);
				$listData = json_encode($return);
			} else {
				$return = array('titles' => array(), 'currentIndex' => 0);
				$listData = json_encode($return);
			}

			$memCache->set($cacheInfo['cacheName'], $listData, 0, $cacheInfo['cacheLength']);

		}

		return $return;
	}

	function getOverDriveSummary()
	{
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
			$overDriveDriver = OverDriveDriverFactory::getDriver();
			$summary = $overDriveDriver->getOverDriveSummary($user);
			return $summary;
		} else {
			return array('error' => 'There is no user currently logged in.');
		}
	}

	function LoginForm()
	{
		global $interface;
		global $library;
		global $configArray;

		if (isset($library)){
			$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
			$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
			$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');
		}else{
			$interface->assign('enableSelfRegistration', 0);
			$interface->assign('usernameLabel', 'Your Name');
			$interface->assign('passwordLabel', 'Library Card Number');
		}
		if ($configArray['Catalog']['ils'] == 'Horizon' || $configArray['Catalog']['ils'] == 'Symphony'){
			$interface->assign('showForgotPinLink', true);
			$catalog = CatalogFactory::getCatalogConnectionInstance();
			$useEmailResetPin = $catalog->checkFunction('emailResetPin');
			$interface->assign('useEmailResetPin', $useEmailResetPin);
		}
		if (isset($_REQUEST['multistep'])) {
			$interface->assign('multistep', true);
		}
		return $interface->fetch('MyAccount/ajax-login.tpl');
	}

	function getMasqueradeAsForm(){
		global $interface;
		return array(
			'title'        => translate('Masquerade As'),
			'modalBody'    => $interface->fetch("MyAccount/ajax-masqueradeAs.tpl"),
			'modalButtons' => '<button class="tool btn btn-primary" onclick="$(\'#masqueradeForm\').submit()">Start</button>'
		);
	}

	function initiateMasquerade(){
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::initiateMasquerade();
	}

	function endMasquerade() {
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::endMasquerade();
	}

	function getPinUpdateForm()
	{
		global $interface;
		$interface->assign('popupTitle', 'Modify PIN number');
		$pageContent = $interface->fetch('MyResearch/modifyPinPopup.tpl');
		$interface->assign('popupContent', $pageContent);
		return $interface->fetch('popup-wrapper.tpl');
	}

	function getChangeHoldLocationForm()
	{
		global $interface;
		/** @var $interface UInterface
		 * @var $user User */
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$patronId = $_REQUEST['patronId'];
			$interface->assign('patronId', $patronId);
			$patronOwningHold = $user->getUserReferredTo($patronId);

			$id = $_REQUEST['holdId'];
			$interface->assign('holdId', $id);

			$location = new Location();
			$pickupBranches = $location->getPickupBranches($patronOwningHold, null);
			$locationList = array();
			foreach ($pickupBranches as $curLocation) {
				$locationList[$curLocation->code] = $curLocation->displayName;
			}
			$interface->assign('pickupLocations', $locationList);

			$results = array(
				'title'        => 'Change Hold Location',
				'modalBody'    => $interface->fetch("MyAccount/changeHoldLocation.tpl"),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="VuFind.Account.doChangeHoldLocation(); return false;">Change Location</span>'
			);
		} else {
			$results = array(
				'title'        => 'Please login',
				'modalBody'    => "You must be logged in.  Please close this dialog and login before changing your hold's pick-up location.",
				'modalButtons' => ""
			);
		}

		return $results;
	}

	// called by js function Account.freezeHold
	function getReactivationDateForm(){
		global $interface;
		global $configArray;

		$id = $_REQUEST['holdId'];
		$interface->assign('holdId', $id);
		$interface->assign('patronId', UserAccount::getActiveUserId());
		$interface->assign('recordId', $_REQUEST['recordId']);

		$ils = $configArray['Catalog']['ils'];
		$reactivateDateNotRequired = ($ils == 'Symphony');
		$interface->assign('reactivateDateNotRequired', $reactivateDateNotRequired);

		$title = translate('Freeze Hold'); // language customization
		$results = array(
			'title'        => $title,
			'modalBody'    => $interface->fetch("MyAccount/reactivationDate.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' id='doFreezeHoldWithReactivationDate' onclick='$(\".form\").submit(); return false;'>$title</button>"
		);
		return $results;
	}

	function changeHoldLocation()
	{
		global $configArray;

		try {
			$holdId = $_REQUEST['holdId'];
			$newPickupLocation = $_REQUEST['newLocation'];

			if (UserAccount::isLoggedIn()){
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$patronOwningHold = $user->getUserReferredTo($patronId);

				$result = $patronOwningHold->changeHoldPickUpLocation($holdId, $newPickupLocation);
				return $result;
			}else{
				return $results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login to change this hold's pick up location.",
					'modalButtons' => ""
				);
			}

		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}
		return array(
			'result'  => false,
			'message' => 'We could not connect to the circulation system, please try again later.'
		);
	}

	function requestPinReset()
	{
		global $configArray;

		try {
			/** @var DriverInterface|Millennium|Nashville|Marmot|Sierra|Horizon $catalog */
			$catalog = CatalogFactory::getCatalogConnectionInstance();

			$barcode = $_REQUEST['barcode'];

			//Get the list of pickup branch locations for display in the user interface.
			$result = $catalog->requestPinReset($barcode);
			return $result;

		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}
	}

	function getCitationFormatsForm(){
		global $interface;
		$interface->assign('popupTitle', 'Please select a citation format');
		$interface->assign('listId', $_REQUEST['listId']);
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormats', $citationFormats);
		$pageContent = $interface->fetch('MyAccount/getCitationFormatPopup.tpl');
		return array(
				'title' => 'Select Citation Format',
				'modalBody' => $pageContent,
				'modalButtons' => '<input class="btn btn-primary" onclick="VuFind.Lists.processCiteListForm(); return false;" value="' . translate('Generate Citations') . '">'
		);
	}


	function sendMyListEmail(){
		global $interface;

		// Get data from AJAX request
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) { // validly formatted List Id
			$listId = $_REQUEST['listId'];
			$to = $_REQUEST['to'];
			$from = $_REQUEST['from'];
			$message = $_REQUEST['message'];

			//Load the list
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			$list = new UserList();
			$list->id = $listId;
			if ($list->find(true)){
				// Build Favorites List
				$listEntries = $list->getListTitles();
				$interface->assign('listEntries', $listEntries);

				// Load the User object for the owner of the list (if necessary):
				if ($list->public == true || (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $list->user_id)) {
					//The user can access the list
					require_once ROOT_DIR . '/services/MyResearch/lib/FavoriteHandler.php';
					$favoriteHandler = new FavoriteHandler($list, UserAccount::getActiveUserObj(), false);
					$titleDetails = $favoriteHandler->getTitles(count($listEntries));
					// get all titles for email list, not just a page's worth
					$interface->assign('titles', $titleDetails);
					$interface->assign('list', $list);

					if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)){
						$interface->assign('message', $message);
						$body = $interface->fetch('Emails/my-list.tpl');

						require_once ROOT_DIR . '/sys/Mailer.php';
						$mail = new VuFindMailer();
						$subject = $list->title;
						$emailResult = $mail->send($to, $from, $subject, $body);

						if ($emailResult === true){
							$result = array(
								'result' => true,
								'message' => 'Your e-mail was sent successfully.'
							);
						} elseif (PEAR_Singleton::isError($emailResult)){
							$result = array(
								'result' => false,
								'message' => "Your e-mail message could not be sent: {$emailResult->message}."
							);
						} else {
							$result = array(
								'result' => false,
								'message' => 'Your e-mail message could not be sent due to an unknown error.'
							);
							global $logger;
							$logger->log("Mail List Failure (unknown reason), parameters: $to, $from, $subject, $body", PEAR_LOG_ERR);
						}
					} else {
						$result = array(
							'result' => false,
							'message' => 'Sorry, we can&apos;t send e-mails with html or other data in it.'
						);
					}

				} else {
					$result = array(
						'result' => false,
						'message' => 'You do not have access to this list.'
					);

				}
			} else {
				$result = array(
					'result' => false,
					'message' => 'Unable to read list.'
				);
			}
		}
		else { // Invalid listId
			$result = array(
				'result' => false,
				'message' => "Invalid List Id. Your e-mail message could not be sent."
			);
		}

		return $result;
	}

	function getEmailMyListForm(){
		global $interface;
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) $listId = $_REQUEST['listId'];

		$interface->assign('listId', $listId);
		$formDefinition = array(
			'title' => 'Email a list',
			'modalBody' => $interface->fetch('MyAccount/emailListPopup.tpl'),
//			'modalButtons' => '<input type="submit" name="submit" value="Send" class="btn btn-primary" onclick="$(\'#emailListForm\').submit();" />'
			'modalButtons' => '<span class="tool btn btn-primary" onclick="$(\'#emailListForm\').submit();">Send E-Mail</span>'
		);
		return $formDefinition;
	}

	function renewItem() {
		if (isset($_REQUEST['patronId']) && isset($_REQUEST['recordId']) && isset($_REQUEST['renewIndicator'])) {
			if (strpos($_REQUEST['renewIndicator'], '|') > 0){
				list($itemId, $itemIndex) = explode('|', $_REQUEST['renewIndicator']);
			}else{
				$itemId = $_REQUEST['renewIndicator'];
				$itemIndex = null;
			}

			if (!UserAccount::isLoggedIn()){
				$renewResults = array(
					'success' => false,
					'message' => 'Not Logged in.'
				);
			}else{
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$recordId = $_REQUEST['recordId'];
				$patron = $user->getUserReferredTo($patronId);
				if ($patron){
					$renewResults = $patron->renewItem($recordId, $itemId, $itemIndex);
				}else{
					$renewResults = array(
						'success' => false,
						'message' => 'Sorry, it looks like you don\'t have access to that patron.'
					);
				}

			}
		} else {
			//error message
			$renewResults = array(
				'success' => false,
				'message' => 'Item to renew not specified'
			);
		}
		global $interface;
		$interface->assign('renewResults', $renewResults);
		$result = array(
			'title' => translate('Renew').' Item',
			'modalBody' => $interface->fetch('MyAccount/renew-item-results.tpl'),
		  'success' => $renewResults['success']
		);
		return $result;
	}

	function renewSelectedItems() {
		if (!UserAccount::isLoggedIn()){
			$renewResults = array(
				'success' => false,
				'message' => 'Not Logged in.'
			);
		} else {
			if (isset($_REQUEST['selected'])) {

//			global $configArray;
//			try {
//				$this->catalog = CatalogFactory::getCatalogConnectionInstance();
//			} catch (PDOException $e) {
//				// What should we do with this error?
//				if ($configArray['System']['debug']) {
//					echo '<pre>';
//					echo 'DEBUG: ' . $e->getMessage();
//					echo '</pre>';
//				}
//			}

				$user = UserAccount::getLoggedInUser();
				if (method_exists($user, 'renewItem')) {

					$failure_messages = array();
					$renewResults     = array();
					foreach ($_REQUEST['selected'] as $selected => $ignore) {
						//Suppress errors because sometimes we don't get an item index
						@list($patronId, $recordId, $itemId, $itemIndex) = explode('|', $selected);
						$patron = $user->getUserReferredTo($patronId);
						if ($patron){
							$tmpResult = $patron->renewItem($recordId, $itemId, $itemIndex);
						}else{
							$tmpResult = array(
								'success' => false,
								'message' => 'Sorry, it looks like you don\'t have access to that patron.'
							);
						}

						if (!$tmpResult['success']) {
							$failure_messages[] = $tmpResult['message'];
						}
					}
					if ($failure_messages) {
						$renewResults['success'] = false;
						$renewResults['message'] = $failure_messages;
					} else {
						$renewResults['success'] = true;
						$renewResults['message'] = "All items were renewed successfully.";
					}
					$renewResults['Total']     = count($_REQUEST['selected']);
					$renewResults['Unrenewed'] = count($failure_messages);
					$renewResults['Renewed']   = $renewResults['Total'] - $renewResults['Unrenewed'];
				} else {
					PEAR_Singleton::raiseError(new PEAR_Error('Cannot Renew Item - ILS Not Supported'));
					$renewResults = array(
						'success' => false,
						'message' => 'Cannot Renew Items - ILS Not Supported.'
					);
				}


			} else {
				//error message
				$renewResults = array(
					'success' => false,
					'message' => 'Items to renew not specified.'
				);
			}
		}
		global $interface;
		$interface->assign('renew_message_data', $renewResults);
		$result = array(
			'title' => translate('Renew').' Selected Items',
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
		  'renewed' => $renewResults['Renewed']
		);
		return $result;
	}

	function renewAll() {
		$renewResults = array(
			'success' => false,
			'message' => array('Unable to renew all titles'),
		);
		$user = UserAccount::getLoggedInUser();
		if ($user){
			$renewResults = $user->renewAll(true);
		}else{
			$renewResults['message'] = array('You must be logged in to renew titles');
		}

		global $interface;
		$interface->assign('renew_message_data', $renewResults);
		$result = array(
			'title' => translate('Renew').' All',
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
			'renewed' => $renewResults['Renewed']
		);
		return $result;
	}

	function setListEntryPositions(){
		$success = false; // assume failure
		$listId = $_REQUEST['listID'];
		$updates = $_REQUEST['updates'];
		if (ctype_digit($listId) && !empty($updates)) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			$list = new UserList();
			$list->id = $listId;
			if ($list->find(true) && $user->canEditList($list)) { // list exists & user can edit
				require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
				$success = true; // assume success now
				foreach ($updates as $update) {
					$update['id'] = str_replace('_', ':', $update['id']); // Rebuilt Islandora PIDs
					$userListEntry                         = new UserListEntry();
					$userListEntry->listId                 = $listId;
					if (!preg_match("/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}|[A-Z0-9_-]+:[A-Z0-9_-]+$/i", $update['id'])) {
						$success = false;
					}else{
						$userListEntry->groupedWorkPermanentId = $update['id'];
						if ($userListEntry->find(true) && ctype_digit($update['newOrder'])) {
							// check entry exists already and the new weight is a number
							$userListEntry->weight = $update['newOrder'];
							if (!$userListEntry->update()) {
								$success = false;
							}
						} else {
							$success = false;
						}
					}
				}
			}
		}
		return array('success' => $success);
	}

	function getMenuData(){
		global $timer;
		global $interface;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$result = array();
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getLoggedInUser();
			$interface->assign('user', $user);

			//Load a list of lists
			$userListData = $memCache->get('user_list_data_' . UserAccount::getActiveUserId());
			if ($userListData == null || isset($_REQUEST['reload'])){
				$lists = array();
				require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
				$tmpList = new UserList();
				$tmpList->user_id = UserAccount::getActiveUserId();
				$tmpList->deleted = 0;
				$tmpList->orderBy("title ASC");
				$tmpList->find();
				if ($tmpList->N > 0){
					while ($tmpList->fetch()){
						$lists[$tmpList->id] = array(
								'name' => $tmpList->title,
								'url' => '/MyAccount/MyList/' .$tmpList->id ,
								'id' => $tmpList->id,
								'numTitles' => $tmpList->numValidListItems()
						);
					}
				}
				$memCache->set('user_list_data_' . UserAccount::getActiveUserId(), $lists, 0, $configArray['Caching']['user']);
				$timer->logTime("Load Lists");
			}else{
				$lists = $userListData;
				$timer->logTime("Load Lists from cache");
			}

			$interface->assign('lists', $lists);
			$result['lists'] = $interface->fetch('MyAccount/listsMenu.tpl');

			//Count of Checkouts
			$result['checkouts'] = '</div><span class="badge">' . $user->getNumCheckedOutTotal() . '</span>';

			//Count of Holds
			$result['holds'] = '<span class="badge">' . $user->getNumHoldsTotal() . '</span>';
			if ($user->getNumHoldsAvailableTotal() > 0){
				$result['holds'] .= '&nbsp;<span class="label label-success">' . $user->getNumHoldsAvailableTotal() . ' ready for pick up</span>';
			}

			//Count of bookings
			global $library;
			if ($library->enableMaterialsBooking){
				$result['bookings'] = '</div><span class="badge">' . $user->getNumBookingsTotal() . '</span>';
			}else{
				$result['bookings'] = '';
			}

			//Count of Reading History
			$result['readingHistory'] = '';
			if ($user->getReadingHistorySize() > 0){
				$result['readingHistory'] = '<span class="badge">' . $user->getReadingHistorySize() . '</span>';
			}

			//Count of Materials Requests
			$result['materialsRequests'] = '<span class="badge">' . $user->getNumMaterialsRequests() . '</span>';

			//Available Holds
			if ($_REQUEST['activeModule'] == 'MyAccount' && $_REQUEST['activeAction'] == 'Holds'){
				$interface->assign('noLink', true);
			}else{
				$interface->assign('noLink', false);
			}
			$result['availableHoldsNotice'] = $interface->fetch('MyAccount/availableHoldsNotice.tpl');

			//Expiration and fines
			$interface->setFinesRelatedTemplateVariables();
			if ($interface->getVariable('expiredMessage')){
				$interface->assign('expiredMessage', str_replace('%date%', $user->expires, $interface->getVariable('expiredMessage')));
			}
			if ($interface->getVariable('expirationNearMessage')){
				$interface->assign('expirationNearMessage', str_replace('%date%', $user->expires, $interface->getVariable('expirationNearMessage')));
			}
			$result['expirationFinesNotice'] = $interface->fetch('MyAccount/expirationFinesNotice.tpl');

			// Get My Tags
			$tagList = $user->getTags();
			$interface->assign('tagList', $tagList);
			$timer->logTime("Load Tags");
			$result['tagsMenu'] = $interface->fetch('MyAccount/tagsMenu.tpl');
		}//User is not logged in

		return $result;
	}
}
