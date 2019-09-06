<?php

/** @noinspection PhpUnused */
class MyAccount_AJAX
{
	const SORT_LAST_ALPHA = 'zzzzz';

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			if (in_array($method, array('getLoginForm', 'getPinUpdateForm'))) {
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			}else {
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$result = $this->$method();
				echo json_encode($result);
			}
		}else {
			echo json_encode(array('error'=>'invalid_method'));
		}
	}

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
	function getAddAccountLinkForm(){
		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', 0);
		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));
		// Display Page
		$formDefinition = array(
			'title' => 'Account to Manage',
			'modalBody' => $interface->fetch('MyAccount/addAccountLink.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.processAddLinkedUser(); return false;'>Add Account</span>"
		);
		return $formDefinition;
	}

	/** @noinspection PhpUnused */
	function getBulkAddToListForm()	{
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		$interface->assign('popupTitle', 'Add titles to list');
		$formDefinition = array(
			'title' => 'Add titles to list',
			'modalBody' => $interface->fetch('MyAccount/bulkAddToListPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Lists.processBulkAddForm(); return false;'>Add To List</span>"
		);
		return $formDefinition;
	}

	/** @noinspection PhpUnused */
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
				'buttons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.cancelHold(\"$patronId\", \"$recordId\", \"$cancelId\")'>$cancelButtonLabel</span>",
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
			'body' => $interface->fetch('MyAccount/cancelHold.tpl'),
			'success' => $result['success']
		);
		return $cancelResult;
	}

	function cancelBooking() {
        $totalCancelled = null;
        $numCancelled = null;
		try {
			$user = UserAccount::getLoggedInUser();

			if (!empty($_REQUEST['cancelAll']) && $_REQUEST['cancelAll'] == 1) {
				$result = $user->cancelAllBookedMaterial();
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
			$logger->log('Booking : '.$e->getMessage(), Logger::LOG_ERROR);

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
					$logger->log('Freeze Hold, no record or hold Id was passed in AJAX call.', Logger::LOG_ERROR);
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
					    /** @var string[] $messageArray */
					    $messageArray = $result['message'];
						$result['message'] = implode('; ', $messageArray);
						// Millennium Holds assumes there can be more than one item processed. Here we know only one got processed,
						// but do implode as a fallback
					}
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Freeze Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
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
			$logger->log('Thaw Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = 'No Patron was specified.';
		}

		return $result;
	}

	function addList()
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
				if (!empty($_REQUEST['recordId']) && !is_array($_REQUEST['recordId'])) {
					$recordToAdd = urldecode($_REQUEST['recordId']);
					if (!preg_match("/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}|[A-Z0-9_-]+:[A-Z0-9_-]+$/i", $recordToAdd)) {
						$return['success'] = false;
						$return['message'] = 'The recordId provided is not valid';
						return $return;
					}
				}

				$list = new UserList();
				$list->title = strip_tags($title);
				$list->user_id = $user->id;
				$list->deleted = "0";
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

				if (!empty($_REQUEST['recordId']) && !is_array($_REQUEST['recordId'])) {
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

	/** @noinspection PhpUnused */
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
			'modalBody' => $interface->fetch("MyAccount/createListForm.tpl"),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.addList(\"{$id}\"); return false;'>Create List</span>"
		);
		return $results;
	}

	function getLoginForm()
	{
		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$interface->assign('forgotPasswordType', $catalog->getForgotPasswordType());

		if (isset($_REQUEST['multiStep'])) {
			$interface->assign('multiStep', true);
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
				'modalButtons' => '<span class="tool btn btn-primary" onclick="AspenDiscovery.Account.doChangeHoldLocation(); return false;">Change Location</span>'
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
		$reactivateDateNotRequired = ($ils == 'Symphony' || $ils == 'Koha');
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

	function requestPinReset(){
		/** @var CatalogConnection $catalog */
		$catalog = CatalogFactory::getCatalogConnectionInstance();

		$barcode = $_REQUEST['barcode'];

		//Get the list of pickup branch locations for display in the user interface.
		$result = $catalog->requestPinReset($barcode);
		return $result;
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
				'modalButtons' => '<input class="btn btn-primary" onclick="AspenDiscovery.Lists.processCiteListForm(); return false;" value="' . translate('Generate Citations') . '">'
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

						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = $list->title;
						$emailResult = $mail->send($to, $subject, $body, $from);

						if ($emailResult === true){
							$result = array(
								'result' => true,
								'message' => 'Your email was sent successfully.'
							);
						} elseif (($emailResult instanceof AspenError)){
							$result = array(
								'result' => false,
								'message' => "Your email message could not be sent: {$emailResult->getMessage()}."
							);
						} else {
							$result = array(
								'result' => false,
								'message' => 'Your email message could not be sent due to an unknown error.'
							);
							global $logger;
							$logger->log("Mail List Failure (unknown reason), parameters: $to, $from, $subject, $body", Logger::LOG_ERROR);
						}
					} else {
						$result = array(
							'result' => false,
							'message' => 'Sorry, we can&apos;t send emails with html or other data in it.'
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
				'message' => "Invalid List Id. Your email message could not be sent."
			);
		}

		return $result;
	}

	function getEmailMyListForm(){
		global $interface;
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) {
		    $listId = $_REQUEST['listId'];

            $interface->assign('listId', $listId);
            $formDefinition = array(
                'title' => 'Email a list',
                'modalBody' => $interface->fetch('MyAccount/emailListPopup.tpl'),
//			'modalButtons' => '<input type="submit" name="submit" value="Send" class="btn btn-primary" onclick="$(\'#emailListForm\').submit();" />'
                'modalButtons' => '<span class="tool btn btn-primary" onclick="$(\'#emailListForm\').submit();">Send Email</span>'
            );
            return $formDefinition;
        } else {
		    return [
		        'success' => false,
                'message' => 'You must provide the id of the list to email'
            ];
        }
	}

	function renewCheckout() {
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
					$renewResults = $patron->renewCheckout($recordId, $itemId, $itemIndex);
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
				if (method_exists($user, 'renewCheckout')) {

					$failure_messages = array();
					$renewResults     = array();
					foreach ($_REQUEST['selected'] as $selected => $ignore) {
						//Suppress errors because sometimes we don't get an item index
						@list($patronId, $recordId, $itemId, $itemIndex) = explode('|', $selected);
						$patron = $user->getUserReferredTo($patronId);
						if ($patron){
							$tmpResult = $patron->renewCheckout($recordId, $itemId, $itemIndex);
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
					$renewResults['NotRenewed'] = count($failure_messages);
					$renewResults['Renewed']   = $renewResults['Total'] - $renewResults['NotRenewed'];
				} else {
					AspenError::raiseError(new AspenError('Cannot Renew Item - ILS Not Supported'));
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

	function getMenuDataIls(){
		global $timer;
		global $interface;

		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			if ($user->getCatalogDriver() != null) {
				$ilsSummary = $user->getCatalogDriver()->getAccountSummary($user);
				$ilsSummary['materialsRequests'] = $user->getNumMaterialsRequests();
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $linkedUser->getCatalogDriver()->getAccountSummary($linkedUser);
						$ilsSummary['totalFines'] += $linkedUserSummary['totalFines'];
						$ilsSummary['numCheckedOut'] += $linkedUserSummary['numCheckedOut'];
						$ilsSummary['numOverdue'] += $linkedUserSummary['numOverdue'];
						$ilsSummary['numAvailableHolds'] += $linkedUserSummary['numAvailableHolds'];
						$ilsSummary['numUnavailableHolds'] += $linkedUserSummary['numUnavailableHolds'];
						$ilsSummary['materialsRequests'] += $linkedUser->getNumMaterialsRequests();
					}
				}
				$ilsSummary['numHolds'] = $ilsSummary['numAvailableHolds'] + $ilsSummary['numUnavailableHolds'];
				$timer->logTime("Loaded ILS Summary for User and linked users");

				$ilsSummary['readingHistory'] = $user->getReadingHistorySize();

				global $library;
				if ($library->enableMaterialsBooking){
					$ilsSummary['bookings'] = $user->getNumBookingsTotal();
				}else{
					$ilsSummary['bookings'] = '';
				}

				//Expiration and fines
				$interface->assign('ilsSummary', $ilsSummary);
				$interface->setFinesRelatedTemplateVariables();
				if ($interface->getVariable('expiredMessage')){
					$interface->assign('expiredMessage', str_replace('%date%', $ilsSummary['expires'], $interface->getVariable('expiredMessage')));
				}
				if ($interface->getVariable('expirationNearMessage')){
					$interface->assign('expirationNearMessage', str_replace('%date%', $ilsSummary['expires'], $interface->getVariable('expirationNearMessage')));
				}
				$ilsSummary['expirationFinesNotice'] = $interface->fetch('MyAccount/expirationFinesNotice.tpl');

				$result = [
					'success' => true,
					'summary' => $ilsSummary
				];
			}else{
				$result['message'] = 'Unknown error';
			}
		}else{
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	function getMenuDataRBdigital(){
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('rbdigital')) {
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				$rbdigitalSummary = $driver->getAccountSummary($user);
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$rbdigitalSummary['numCheckedOut'] += $linkedUserSummary['numCheckedOut'];
						$rbdigitalSummary['numUnavailableHolds'] += $linkedUserSummary['numUnavailableHolds'];
					}
				}
				$timer->logTime("Loaded RBdigital Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $rbdigitalSummary
				];
			}else{
				$result['message'] = 'Unknown error';
			}
		}else{
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	function getMenuDataCloudLibrary(){
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('cloud_library')) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				$cloudLibrarySummary = $driver->getAccountSummary($user);
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$cloudLibrarySummary['numCheckedOut'] += $linkedUserSummary['numCheckedOut'];
						$cloudLibrarySummary['numUnavailableHolds'] += $linkedUserSummary['numUnavailableHolds'];
						$cloudLibrarySummary['numAvailableHolds'] += $linkedUserSummary['numAvailableHolds'];
						$cloudLibrarySummary['numHolds'] += $linkedUserSummary['numHolds'];
					}
				}
				$timer->logTime("Loaded Cloud Library Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $cloudLibrarySummary
				];
			}else{
				$result['message'] = 'Unknown error';
			}
		}else{
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	function getMenuDataHoopla(){
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('hoopla')) {
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$hooplaSummaryRaw = $driver->getAccountSummary($user);
				if ($hooplaSummaryRaw == false){
					$hooplaSummary = [
						'numCheckedOut' => 0,
						'numCheckoutsRemaining' => 0,
					];
				}else{
					$hooplaSummary = [
						'numCheckedOut' => $hooplaSummaryRaw->currentlyBorrowed,
						'numCheckoutsRemaining' => $hooplaSummaryRaw->borrowsRemaining,
					];
				}

				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						if ($linkedUserSummary != false) {
							$hooplaSummary['numCheckedOut'] += $linkedUserSummary->currentlyBorrowed;
							$hooplaSummary['numCheckoutsRemaining'] += $linkedUserSummary->borrowsRemaining;
						}
					}
				}
				$timer->logTime("Loaded Hoopla Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $hooplaSummary
				];
			}else{
				$result['message'] = 'Unknown error';
			}
		}else{
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	function getMenuDataOverdrive(){
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('overdrive')) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$overDriveSummary = $driver->getAccountSummary($user);
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$overDriveSummary['numCheckedOut'] += $linkedUserSummary['numCheckedOut'];
						$overDriveSummary['numAvailableHolds'] += $linkedUserSummary['numAvailableHolds'];
						$overDriveSummary['numUnavailableHolds'] += $linkedUserSummary['numUnavailableHolds'];
					}
				}
				$overDriveSummary['numHolds'] = $overDriveSummary['numAvailableHolds'] + $overDriveSummary['numUnavailableHolds'];
				$timer->logTime("Loaded OverDrive Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $overDriveSummary
				];
			}else{
				$result['message'] = 'Unknown error';
			}
		}else{
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	function getRatingsData(){
		global $interface;
		$result = array();
		if (UserAccount::isLoggedIn()){
			$user = UserAccount::getLoggedInUser();
			$interface->assign('user', $user);

			//Count of ratings
			$result['ratings'] = $user->getNumRatings();
		}//User is not logged in

		return $result;
	}

    function getListData(){
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
                $tmpList->whereAdd('deleted = 0');
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
                $memCache->set('user_list_data_' . UserAccount::getActiveUserId(), $lists, $configArray['Caching']['user']);
                $timer->logTime("Load Lists");
            }else{
                $lists = $userListData;
                $timer->logTime("Load Lists from cache");
            }

            $interface->assign('lists', $lists);
            $result['lists'] = $interface->fetch('MyAccount/listsMenu.tpl');

        }//User is not logged in

        return $result;
    }

    public function exportCheckouts(){
		global $configArray;
	    $source = $_REQUEST['source'];
	    $user = UserAccount::getActiveUserObj();
	    $allCheckedOut = $user->getCheckouts(true, $source);
	    $selectedSortOption = $this->setSort('sort', 'checkout');
	    if ($selectedSortOption == null) {
		    $selectedSortOption = 'dueDate';
	    }
	    $allCheckedOut = $this->sortCheckouts($selectedSortOption, $allCheckedOut);

	    $ils = $configArray['Catalog']['ils'];
	    $showOut = ($ils == 'Horizon');
	    $showRenewed = ($ils == 'Horizon' || $ils == 'Millennium'  || $ils == 'Sierra' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
	    $showWaitList = $ils == 'Horizon';

	    // Create new PHPExcel object
	    $objPHPExcel = new PHPExcel();

	    // Set properties
	    $objPHPExcel->getProperties()->setCreator("Aspen Discovery")
		    ->setLastModifiedBy("Aspen Discovery")
		    ->setTitle("Library Checkouts for " . $user->displayName)
		    ->setCategory("Checked Out Items");

	    try {
		    $activeSheet = $objPHPExcel->setActiveSheetIndex(0);
		    $curRow = 1;
		    $curCol = 0;
		    $activeSheet->setCellValueByColumnAndRow($curCol, $curRow, 'Checked Out Items');
		    $curRow = 3;
		    $curCol = 0;
		    $activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Title');
		    $activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Author');
		    $activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Format');
		    if ($showOut){
			    $activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Out');
		    }
		    $activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Due');
		    if ($showRenewed){
			    $activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Renewed');
		    }
		    if ($showWaitList){
			    $activeSheet->setCellValueByColumnAndRow($curCol, $curRow, 'Wait List');
		    }

		    $a=4;
		    //Loop Through The Report Data
	        foreach ($allCheckedOut as $row) {
			    $titleCell = preg_replace("~([/:])$~", "", $row['title']);
			    if (isset ($row['title2'])) {
				    $titleCell .= preg_replace("~([/:])$~", "", $row['title2']);
			    }

			    if (isset ($row['author'])) {
				    if (is_array($row['author'])) {
					    $authorCell = implode(', ', $row['author']);
				    } else {
					    $authorCell = $row['author'];
				    }
				    $authorCell = str_replace('&nbsp;', ' ', $authorCell);
			    } else {
				    $authorCell = '';
			    }
			    if (isset($row['format'])) {
				    if (is_array($row['format'])) {
					    $formatString = implode(', ', $row['format']);
				    } else {
					    $formatString = $row['format'];
				    }
			    } else {
				    $formatString = '';
			    }
			    $activeSheet = $objPHPExcel->setActiveSheetIndex(0);
			    $curCol = 0;
			    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, $titleCell);
			    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, $authorCell);
			    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, $formatString);
			    if ($showOut) {
				    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, date('M d, Y', $row['checkoutDate']));
			    }
			    if (isset($row['dueDate'])) {
				    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, date('M d, Y', $row['dueDate']));
			    } else {
				    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, '');
			    }

			    if ($showRenewed) {
				    if (isset($row['dueDate'])) {
					    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, isset($row['renewCount']) ? $row['renewCount'] : '');
				    } else {
					    $activeSheet->setCellValueByColumnAndRow($curCol++, $a, '');
				    }
			    }
			    if ($showWaitList) {
				    $activeSheet->setCellValueByColumnAndRow($curCol, $a, $row['holdQueueLength']);
			    }

			    $a++;
		    }
		    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		    $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);

		    // Rename sheet
		    $objPHPExcel->getActiveSheet()->setTitle('Checked Out');

		    // Redirect output to a client's web browser (Excel5)
		    header('Content-Type: application/vnd.ms-excel');
		    header('Content-Disposition: attachment;filename="CheckedOutItems.xls"');
		    header('Cache-Control: max-age=0');

		    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		    $objWriter->save('php://output');
	    }catch (Exception $e){
		    global $logger;
		    $logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR );
	    }
	    exit;
    }

	public function exportHolds(){
		global $configArray;
		$source = $_REQUEST['source'];
		$user = UserAccount::getActiveUserObj();

		$ils = $configArray['Catalog']['ils'];
		$showPosition = ($ils == 'Horizon' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
		$showExpireTime = ($ils == 'Horizon' || $ils == 'Symphony');
		$selectedAvailableSortOption   = $this->setSort('availableHoldSort', 'availableHold');
		$selectedUnavailableSortOption = $this->setSort('unavailableHoldSort', 'unavailableHold') ;
		if ($selectedAvailableSortOption == null){
			$selectedAvailableSortOption = 'expire';
		}
		if ($selectedUnavailableSortOption == null){
			$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
		}

		$allHolds = $user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, $source);
		if ($source == 'rbdigital'){
			//RBdigital automatically checks out records so don't show the available section
			unset($allHolds['available']);
		}

		$showDateWhenSuspending = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony' || $ils == 'Koha');

		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("Aspen Discovery")
			->setLastModifiedBy("Aspen Discovery")
			->setTitle("Library Holds for " . $user->displayName)
			->setCategory("Holds");

		try{
			$curRow = 1;
			for ($i = 0; $i < 2; $i++){
				if ($i == 0){
					$exportType = "available";
				}else{
					$exportType = "unavailable";
				}
				if (count($allHolds[$exportType]) == 0){
					continue;
				}
				if ($exportType == "available") {
					// Add some data
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $curRow, 'Holds - '.ucfirst($exportType));
					$curRow+=2;

					$objPHPExcel->getActiveSheet()->setCellValue('A' . $curRow, 'Title')
						->setCellValue('B' . $curRow, 'Author')
						->setCellValue('C' . $curRow, 'Format')
						->setCellValue('D' . $curRow, 'Placed')
						->setCellValue('E' . $curRow, 'Pickup')
						->setCellValue('F' . $curRow, 'Available')
						->setCellValue('G' . $curRow, translate('Pickup By'));
				} else {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $curRow, 'Holds - '.ucfirst($exportType));
					$curRow+=2;
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $curRow, 'Title')
						->setCellValue('B' . $curRow, 'Author')
						->setCellValue('C' . $curRow, 'Format')
						->setCellValue('D' . $curRow, 'Placed')
						->setCellValue('E' . $curRow, 'Pickup');

					if ($showPosition){
						$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, 'Position')
							->setCellValue('G' . $curRow, 'Status');
						if ($showExpireTime){
							$objPHPExcel->getActiveSheet()->setCellValue('H' . $curRow, 'Expires');
						}
					}else{
						$objPHPExcel->getActiveSheet()
							->setCellValue('F' . $curRow, 'Status');
						if ($showExpireTime){
							$objPHPExcel->getActiveSheet()->setCellValue('G' . $curRow, 'Expires');
						}
					}
				}


				$curRow++;
				//Loop Through The Report Data
				foreach ($allHolds[$exportType] as $row) {
					$titleCell =  preg_replace("~([/:])$~", "", $row['title']);
					if (isset ($row['title2'])){
						$titleCell .= preg_replace("~([/:])$~", "", $row['title2']);
					}

					if (isset ($row['author'])){
						if (is_array($row['author'])){
							$authorCell = implode(', ', $row['author']);
						}else{
							$authorCell = $row['author'];
						}
						$authorCell = str_replace('&nbsp;', ' ', $authorCell);
					}else{
						$authorCell = '';
					}
					if (isset($row['format'])){
						if (is_array($row['format'])){
							$formatString = implode(', ', $row['format']);
						}else{
							$formatString = $row['format'];
						}
					}else{
						$formatString = '';
					}

					if (empty($row['create'])) {
						$placedDate = '';
					} else {
						if (is_array($row['create'])){
							$placedDate = new DateTime();
							$placedDate->setDate($row['create']['year'],$row['create']['month'],$row['create']['day']);
							$placedDate = $placedDate->format('M d, Y');
						}else{
							$placedDate = $this->isValidTimeStamp($row['create']) ? $row['create'] : strtotime($row['create']);
							$placedDate = date('M d, Y', $placedDate);
						}
					}

					if (isset($row['location'])){
						$locationString = $row['location'];
					}else{
						$locationString = '';
					}

					if (empty($row['expire'])) {
						$expireDate = '';
					} else {
						if (is_array($row['expire'])) {
							$expireDate = new DateTime();
							$expireDate->setDate($row['expire']['year'],$row['expire']['month'],$row['expire']['day']);
							$expireDate = $expireDate->format('M d, Y');
						}else{
							$expireDate = $this->isValidTimeStamp($row['expire']) ? $row['expire'] : strtotime($row['expire']);
							$expireDate = date('M d, Y', $expireDate);
						}
					}

					if ($exportType == "available") {
						if (empty($row['availableTime'])) {
							$availableDate = 'Now';
						} else {
							$availableDate = $this->isValidTimeStamp($row['availableTime']) ? $row['availableTime'] : strtotime($row['availableTime']);
							$availableDate =  date('M d, Y', $availableDate);
						}
						$objPHPExcel->getActiveSheet()
							->setCellValue('A'.$curRow, $titleCell)
							->setCellValue('B'.$curRow, $authorCell)
							->setCellValue('C'.$curRow, $formatString)
							->setCellValue('D'.$curRow, $placedDate)
							->setCellValue('E'.$curRow, $locationString)
							->setCellValue('F'.$curRow, $availableDate)
							->setCellValue('G'.$curRow, $expireDate);
					} else {
						if (isset($row['status'])){
							$statusCell = $row['status'];
						}else{
							$statusCell = '';
						}

						if (isset($row['frozen']) && $row['frozen'] && $showDateWhenSuspending && !empty($row['reactivateTime'])){
							$reactivateTime = $this->isValidTimeStamp($row['reactivateTime']) ? $row['reactivateTime'] : strtotime($row['reactivateTime']);
							$statusCell .= " until " . date('M d, Y',$reactivateTime);
						}
						$objPHPExcel->getActiveSheet()
							->setCellValue('A'.$curRow, $titleCell)
							->setCellValue('B'.$curRow, $authorCell)
							->setCellValue('C'.$curRow, $formatString)
							->setCellValue('D'.$curRow, $placedDate);
						if (isset($row['location'])){
							$objPHPExcel->getActiveSheet()->setCellValue('E'.$curRow, $row['location']);
						}else{
							$objPHPExcel->getActiveSheet()->setCellValue('E'.$curRow, '');
						}

						if ($showPosition){
							if (isset($row['position'])){
								$objPHPExcel->getActiveSheet()->setCellValue('F'.$curRow, $row['position']);
							}else{
								$objPHPExcel->getActiveSheet()->setCellValue('F'.$curRow, '');
							}

							$objPHPExcel->getActiveSheet()->setCellValue('G'.$curRow, $statusCell);
							if ($showExpireTime){
								$objPHPExcel->getActiveSheet()->setCellValue('H'.$curRow, $expireDate);
							}
						}else{
							$objPHPExcel->getActiveSheet()->setCellValue('F'.$curRow, $statusCell);
							if ($showExpireTime){
								$objPHPExcel->getActiveSheet()->setCellValue('G'.$curRow, $expireDate);
							}
						}
					}
					$curRow++;
				}
				$curRow+=2;
			}
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);

			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle('Holds');

			// Redirect output to a client's web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="Holds.xls"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');
		}catch (Exception $e){
			global $logger;
			$logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR );
		}
		exit;
	}

	public function exportReadingHistory(){
		$user = UserAccount::getActiveUserObj();
		if ($user){
			$selectedSortOption = $this->setSort('sort', 'readingHistory');
			if ($selectedSortOption == null) {
				$selectedSortOption = 'checkedOut';
			}
			$readingHistory = $user->getReadingHistory(1, -1, $selectedSortOption, '', true);

			try{
				// Create new PHPExcel object
				$objPHPExcel = new PHPExcel();

				// Set properties
				$objPHPExcel->getProperties()->setCreator("Aspen Discovery")
					->setLastModifiedBy("Aspen Discovery")
					->setTitle("Reading History for " . $user->displayName)
					->setCategory("Reading History");

				$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A1', 'Reading History')
					->setCellValue('A3', 'Title')
					->setCellValue('B3', 'Author')
					->setCellValue('C3', 'Format')
					->setCellValue('D3', 'Times Used')
					->setCellValue('E3', 'Last Used');

				$a=4;
				//Loop Through The Report Data
				foreach ($readingHistory['titles'] as $row) {

					$format = is_array($row['format']) ? implode(',', $row['format']) : $row['format'];
					if ($row['checkedOut']){
						$lastCheckout = translate('In Use');
					}else{
						if (is_numeric($row['checkout'])){
							$lastCheckout = date('M Y', $row['checkout']);
						}else{
							$lastCheckout = $row['checkout'];
						}
					}

					$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('A'.$a, $row['title'])
						->setCellValue('B'.$a, $row['author'])
						->setCellValue('C'.$a, $format)
						->setCellValue('D'.$a, $row['timesUsed'])
						->setCellValue('E'.$a, $lastCheckout);

					$a++;
				}
				$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

				// Rename sheet
				$objPHPExcel->getActiveSheet()->setTitle('Reading History');

				// Redirect output to a client's web browser (Excel5)
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="ReadingHistory.xls"');
				header('Cache-Control: max-age=0');

				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
				$objWriter->save('php://output');
			}catch (Exception $e){
				global $logger;
				$logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR );
			}
		}
		exit;
	}

    public function getCheckouts(){
		global $interface;

	    $result = [
			'success' => false,
			'message' => 'Unknown error',
		];

	    global $offlineMode;
	    if (!$offlineMode) {
		    global $configArray;

		    $source = $_REQUEST['source'];
		    $interface->assign('source', $source);
		    $this->setShowCovers();

		    //Determine which columns to show
		    $ils = $configArray['Catalog']['ils'];
		    $showOut = ($ils == 'Horizon');
		    $showRenewed =  ($source == 'ils' || $source == 'all') && ($ils == 'Horizon' || $ils == 'Millennium'  || $ils == 'Sierra' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
		    $showWaitList = ($source == 'ils' || $source == 'all') && ($ils == 'Horizon');

		    $interface->assign('showOut', $showOut);
		    $interface->assign('showRenewed', $showRenewed);
		    $interface->assign('showWaitList', $showWaitList);

		    // Define sorting options
		    $sortOptions = array('title'   => 'Title',
			    'author'  => 'Author',
			    'dueDate' => 'Due Date',
			    'format'  => 'Format',
		    );
		    $user = UserAccount::getActiveUserObj();
		    if (count($user->getLinkedUsers()) > 0){
			    $sortOptions['libraryAccount'] = 'Library Account';
		    }
		    if ($showWaitList){
			    $sortOptions['holdQueueLength']  = 'Wait List';
		    }
		    if ($showRenewed){
			    $sortOptions['renewed'] = 'Times Renewed';
		    }

		    $interface->assign('sortOptions', $sortOptions);

		    if ($user) {
			    $interface->assign('showNotInterested', false);

			    // Get My Transactions
			    $allCheckedOut = $user->getCheckouts(true, $source);

			    $selectedSortOption = $this->setSort('sort', 'checkout');
			    if ($selectedSortOption == null || !array_key_exists($selectedSortOption, $sortOptions)) {
				    $selectedSortOption = 'dueDate';
			    }
			    $interface->assign('defaultSortOption', $selectedSortOption);
			    $allCheckedOut = $this->sortCheckouts($selectedSortOption, $allCheckedOut);

			    $interface->assign('transList', $allCheckedOut);
		    }
		    $result['success'] = true;
		    $result['message'] = "";
		    $result['checkouts'] = $interface->fetch('MyAccount/checkoutsList.tpl');
	    }else{
	    	$result['message'] = translate('The catalog is offline');
	    }

		return $result;
    }

    public function getHolds(){
	    global $interface;

	    $result = [
		    'success' => false,
		    'message' => 'Unknown error',
	    ];

	    global $offlineMode;
	    if (!$offlineMode) {
		    global $configArray;
		    global $library;

		    $source = $_REQUEST['source'];
		    $interface->assign('source', $source);
		    $this->setShowCovers();
		    $selectedAvailableSortOption   = $this->setSort('availableHoldSort', 'availableHold');
		    $selectedUnavailableSortOption = $this->setSort('unavailableHoldSort', 'unavailableHold') ;

		    $user = UserAccount::getActiveUserObj();

		    $interface->assign('allowFreezeHolds', true);

		    $ils = $configArray['Catalog']['ils'];
		    $showPosition = ($ils == 'Horizon' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
		    $suspendRequiresReactivationDate = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony'|| $ils == 'Koha');
		    $interface->assign('suspendRequiresReactivationDate', $suspendRequiresReactivationDate);
		    $canChangePickupLocation = ($ils != 'Koha');
		    $interface->assign('canChangePickupLocation', $canChangePickupLocation);
		    $showPlacedColumn = ($ils == 'Symphony');
		    $interface->assign('showPlacedColumn', $showPlacedColumn);

		    // Define sorting options
		    $unavailableHoldSortOptions = array(
			    'title'  => 'Title',
			    'author' => 'Author',
			    'format' => 'Format',
		    );
		    if ($source != 'rbdigital'){
			    $unavailableHoldSortOptions['status'] = 'Status';
		    }
		    if ($source == 'all' || $source == 'ils'){
			    $unavailableHoldSortOptions['location'] = 'Pickup Location';
		    }
		    if ($showPosition && $source != 'rbdigital'){
			    $unavailableHoldSortOptions['position'] = 'Position';
		    }
		    if ($showPlacedColumn) {
			    $unavailableHoldSortOptions['placed'] = 'Date Placed';
		    }

		    $availableHoldSortOptions = array(
			    'title'  => 'Title',
			    'author' => 'Author',
			    'format' => 'Format',
			    'expire' => 'Expiration Date',
		    );
		    if ($source == 'all' || $source == 'ils'){
			    $availableHoldSortOptions['location'] = 'Pickup Location';
		    }

		    if (count($user->getLinkedUsers()) > 0){
			    $unavailableHoldSortOptions['libraryAccount'] = 'Library Account';
			    $availableHoldSortOptions['libraryAccount']   = 'Library Account';
		    }

		    $interface->assign('sortOptions', array(
			    'available'   => $availableHoldSortOptions,
			    'unavailable' => $unavailableHoldSortOptions
		    ));

		    if ($selectedAvailableSortOption == null || !array_key_exists($selectedAvailableSortOption, $availableHoldSortOptions)){
			    $selectedAvailableSortOption = 'expire';
		    }
		    if ($selectedUnavailableSortOption == null || !array_key_exists($selectedUnavailableSortOption, $unavailableHoldSortOptions)){
			    $selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
		    }
		    $interface->assign('defaultSortOption', array(
			    'available'   => $selectedAvailableSortOption,
			    'unavailable' => $selectedUnavailableSortOption
		    ));

		    $allowChangeLocation = ($ils == 'Millennium' || $ils == 'Sierra');
		    $interface->assign('allowChangeLocation', $allowChangeLocation);
		    $showDateWhenSuspending = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony' || $ils == 'Koha');
		    $interface->assign('showDateWhenSuspending', $showDateWhenSuspending);

		    $interface->assign('showPosition', $showPosition);
		    $interface->assign('showNotInterested', false);

		    global $offlineMode;
	        if (!$offlineMode) {
			    if ($user) {
				    $allHolds = $user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, $source);
				    if ($source == 'rbdigital'){
				    	//RBdigital automatically checks out records so don't show the available section
				    	unset($allHolds['available']);
				    }
				    $interface->assign('recordList', $allHolds);
			    }
		    }

		    if (!$library->showDetailedHoldNoticeInformation){
			    $notification_method = '';
		    }else{
			    $notification_method = ($user->_noticePreferenceLabel != 'Unknown') ? $user->_noticePreferenceLabel : '';
			    if ($notification_method == 'Mail' && $library->treatPrintNoticesAsPhoneNotices){
				    $notification_method = 'Telephone';
			    }
		    }
		    $interface->assign('notification_method', strtolower($notification_method));

		    $result['success'] = true;
		    $result['message'] = "";
		    $result['holds'] = $interface->fetch('MyAccount/holdsList.tpl');
	    }else{
		    $result['message'] = translate('The catalog is offline');
	    }

	    return $result;
    }

    public function getReadingHistory(){
	    global $interface;
	    $showCovers = $this->setShowCovers();

	    $result = [
		    'success' => false,
		    'message' => 'Unknown error',
	    ];

	    global $offlineMode;
	    if (!$offlineMode) {
		    $user = UserAccount::getActiveUserObj();
		    if ($user) {
			    $patronId = empty($_REQUEST['patronId']) ? $user->id : $_REQUEST['patronId'];
			    $interface->assign('selectedUser', $patronId);

			    $patron = $user->getUserReferredTo($patronId);
			    if (!$patron) {
				    AspenError::raiseError(new AspenError("The patron provided is invalid"));
			    }

			    // Define sorting options
			    $sortOptions = array('title' => 'Title',
				    'author' => 'Author',
				    'checkedOut' => 'Last Used',
				    'format' => 'Format',
			    );
			    $selectedSortOption = $this->setSort('sort', 'readingHistory');
			    if ($selectedSortOption == null || !array_key_exists($selectedSortOption, $sortOptions)) {
				    $selectedSortOption = 'checkedOut';
			    }

			    $interface->assign('sortOptions', $sortOptions);
			    $interface->assign('defaultSortOption', $selectedSortOption);
			    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			    $interface->assign('page', $page);

			    $recordsPerPage = 20;
			    $interface->assign('curPage', $page);

			    $filter = isset($_REQUEST['readingHistoryFilter']) ? $_REQUEST['readingHistoryFilter'] : '';
			    $interface->assign('readingHistoryFilter', $filter);

			    $result = $patron->getReadingHistory($page, $recordsPerPage, $selectedSortOption, $filter, false);

			    $link = $_SERVER['REQUEST_URI'];
			    if (preg_match('/[&?]page=/', $link)) {
				    $link = preg_replace("/page=\\d+/", "page=%d", $link);
			    } else if (strpos($link, "?") > 0) {
				    $link .= "&page=%d";
			    } else {
				    $link .= "?page=%d";
			    }
			    if ($recordsPerPage != '-1') {
				    $options = array('totalItems' => $result['numTitles'],
					    'fileName' => $link,
					    'perPage' => $recordsPerPage,
					    'append' => false,
					    'linkRenderingObject' => $this,
					    'linkRenderingFunction' => 'renderReadingHistoryPaginationLink',
					    'patronId' => $patronId,
					    'sort' => $selectedSortOption,
					    'showCovers' => $showCovers,
					    'filter' => urlencode($filter)
				    );
				    $pager = new Pager($options);

				    $interface->assign('pageLinks', $pager->getLinks());
			    }
			    if (!($result instanceof AspenError)) {
				    $interface->assign('historyActive', $result['historyActive']);
				    $interface->assign('transList', $result['titles']);
			    }
		    }
		    $result['success'] = true;
		    $result['message'] = "";
		    $result['readingHistory'] = $interface->fetch('MyAccount/readingHistoryList.tpl');
	    }else{
		    $result['message'] = translate('The catalog is offline');
	    }

	    return $result;
    }

    function renderReadingHistoryPaginationLink($page, $options){
	    return "<a class='page-link' onclick='AspenDiscovery.Account.loadReadingHistory(\"{$options['patronId']}\", \"{$options['sort']}\", \"{$page}\", undefined, \"{$options['filter']}\");AspenDiscovery.goToAnchor(\"topOfList\")'>";
    }

	private function isValidTimeStamp($timestamp) {
		return is_numeric($timestamp)
			&& ($timestamp <= PHP_INT_MAX)
			&& ($timestamp >= ~PHP_INT_MAX);
	}

	function setShowCovers() {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			if (isset($_SESSION)) $_SESSION['showCovers'] = $showCovers;
		} elseif (isset($_SESSION['showCovers'])) {
			$showCovers = $_SESSION['showCovers'];
		}
		$interface->assign('showCovers', $showCovers);
		return $showCovers;
	}

	function setSort($requestParameter, $sortType){
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$sort = null;
		if (isset($_REQUEST[$requestParameter])) {
			$sort = $_REQUEST[$requestParameter];
			if (isset($_SESSION)) $_SESSION['sort_' . $sortType] = $sort;
		} elseif (isset($_SESSION['sort_' . $sortType])) {
			$sort = $_SESSION['sort_' . $sortType];
		}
		return $sort;
	}

	/**
	 * @param string $selectedSortOption
	 * @param array $allCheckedOut
	 * @return array
	 */
	private function sortCheckouts(string $selectedSortOption, array $allCheckedOut): array
	{
		//Do sorting now that we have all records
		$curTransaction = 0;
		foreach ($allCheckedOut as $i => $curTitle) {
			$curTransaction++;
			$sortTitle = !empty($curTitle['title_sort']) ? $curTitle['title_sort'] : (empty($curTitle['title']) ? $this::SORT_LAST_ALPHA : $curTitle['title']);
			$sortKey = $sortTitle;
			if ($selectedSortOption == 'title') {
				$sortKey = $sortTitle;
			} elseif ($selectedSortOption == 'author') {
				$sortKey = (empty($curTitle['author']) ? $this::SORT_LAST_ALPHA : $curTitle['author']) . '-' . $sortTitle;
			} elseif ($selectedSortOption == 'dueDate') {
				if (isset($curTitle['dueDate'])) {
					if (preg_match('~.*?(\\d{1,2})[-/](\\d{1,2})[-/](\\d{2,4}).*~', $curTitle['dueDate'], $matches)) {
						$sortKey = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
					} else {
						$sortKey = $curTitle['dueDate'] . '-' . $sortTitle;
					}
				}
			} elseif ($selectedSortOption == 'format') {
				$sortKey = ((empty($curTitle['format']) || strcasecmp($curTitle['format'], 'unknown') == 0) ? $this::SORT_LAST_ALPHA : $curTitle['format']) . '-' . $sortTitle;
			} elseif ($selectedSortOption == 'renewed') {
				if (isset($curTitle['renewCount']) && is_numeric($curTitle['renewCount'])) {
					$sortKey = str_pad($curTitle['renewCount'], 3, '0', STR_PAD_LEFT) . '-' . $sortTitle;
				} else {
					$sortKey = '***' . '-' . $sortTitle;
				}
			} elseif ($selectedSortOption == 'holdQueueLength') {
				if (isset($curTitle['holdQueueLength']) && is_numeric($curTitle['holdQueueLength'])) {
					$sortKey = str_pad($curTitle['holdQueueLength'], 3, '0', STR_PAD_LEFT) . '-' . $sortTitle;
				} else {
					$sortKey = '***' . '-' . $sortTitle;
				}

			} elseif ($selectedSortOption == 'libraryAccount') {
				$sortKey = $curTitle['user'] . '-' . $sortTitle;
			}
			$sortKey = strtolower($sortKey);
			$sortKey = utf8_encode($sortKey . '-' . $curTransaction);

			$allCheckedOut[$sortKey] = $curTitle;
			unset($allCheckedOut[$i]);
		}

		//Now that we have all the transactions we can sort them
		if ($selectedSortOption == 'renewed' || $selectedSortOption == 'holdQueueLength') {
			krsort($allCheckedOut);
		} else {
			ksort($allCheckedOut);
		}
		return $allCheckedOut;
	}

	/** @noinspection PhpUnused */
	function deleteReadingHistoryEntry(){
		$result = [
			'success' => false,
			'title' => translate('Error'),
			'message' => translate('Unknown error'),
		];

		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron == null){
				$result['message'] = 'You do not have permissions to delete reading history for this user';
			}else{
				$permanentId = $_REQUEST['permanentId'];
				$selectedTitles = [$permanentId => $permanentId];
				$readingHistoryAction = 'deleteMarked';
				$result = $patron->doReadingHistoryAction($readingHistoryAction, $selectedTitles);
			}
		}else{
			$result['message'] = 'You must be logged in to delete from the reading history';
		}
		return $result;
	}
}
