<?php

require_once ROOT_DIR . '/JSON_Action.php';

class MyAccount_AJAX extends JSON_Action
{
	const SORT_LAST_ALPHA = 'zzzzz';

	function launch($method = null)
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		switch ($method){
			case 'renewItem':
				$method = 'renewCheckout';
				break;
		}
		if (method_exists($this, $method)) {
			if (in_array($method, array('getLoginForm'))) {
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			} else {
				parent::launch($method);
			}
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

	/** @noinspection PhpUnused */
	function getAddBrowseCategoryFromListForm()
	{
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
		return array(
			'title' => 'Add as Browse Category to Home Page',
			'modalBody' => $interface->fetch('Browse/addBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>Create Category</button>"
		);
	}

	/** @noinspection PhpUnused */
	function addAccountLink()
	{
		if (!UserAccount::isLoggedIn()) {
			$result = array(
				'result' => false,
				'message' => 'Sorry, you must be logged in to manage accounts.'
			);
		} else {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];

			$accountToLink = UserAccount::validateAccount($username, $password);
			$user = UserAccount::getLoggedInUser();

			if (!UserAccount::isLoggedIn()) {
				$result = array(
					'result' => false,
					'message' => 'You must be logged in to link accounts, please login again'
				);
			} elseif ($accountToLink) {
				if ($accountToLink->id != $user->id) {
					$addResult = $user->addLinkedUser($accountToLink);
					if ($addResult === true) {
						$result = array(
							'result' => true,
							'message' => 'Successfully linked accounts.'
						);
					} else { // insert failure or user is blocked from linking account or account & account to link are the same account
						$result = array(
							'result' => false,
							'message' => 'Sorry, we could not link to that account.  Accounts cannot be linked if all libraries do not allow account linking.  Please contact your local library if you have questions.'
						);
					}
				} else {
					$result = array(
						'result' => false,
						'message' => 'You cannot link to yourself.'
					);
				}
			} else {
				$result = array(
					'result' => false,
					'message' => 'Sorry, we could not find a user with that information to link to.'
				);
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function removeAccountLink()
	{
		if (!UserAccount::isLoggedIn()) {
			$result = array(
				'result' => false,
				'message' => 'Sorry, you must be logged in to manage accounts.'
			);
		} else {
			$accountToRemove = $_REQUEST['idToRemove'];
			$user = UserAccount::getLoggedInUser();
			if ($user->removeLinkedUser($accountToRemove)) {
				$result = array(
					'result' => true,
					'message' => 'Successfully removed linked account.'
				);
			} else {
				$result = array(
					'result' => false,
					'message' => 'Sorry, we could remove that account.'
				);
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getAddAccountLinkForm()
	{
		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', 0);
		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));
		// Display Page
		return array(
			'title' => 'Account to Manage',
			'modalBody' => $interface->fetch('MyAccount/addAccountLink.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.processAddLinkedUser(); return false;'>Add Account</span>"
		);
	}

	/** @noinspection PhpUnused */
	function getBulkAddToListForm()
	{
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		$interface->assign('popupTitle', 'Add titles to list');
		return array(
			'title' => 'Add titles to list',
			'modalBody' => $interface->fetch('MyAccount/bulkAddToListPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Lists.processBulkAddForm(); return false;'>Add To List</span>"
		);
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
					$message = $saveOk ? 'Your search was saved successfully.  You can view the saved search by clicking on <a href="/Search/History?require_login">Search History</a> within ' . translate('My Account') . '.' : "Sorry, we could not save that search for you.  It may have expired.";
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
		return array(
			'result' => $saveOk,
			'message' => $message,
		);
	}

	/** @noinspection PhpUnused */
	function confirmCancelHold()
	{
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

	function cancelHold()
	{
		$result = array(
			'success' => false,
			'message' => 'Error cancelling hold.'
		);

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to cancel a hold.  Please close this dialog and login again.';
		} else {
			//Determine which user the hold is on so we can cancel it.
			$patronId = $_REQUEST['patronId'];
			$user = UserAccount::getLoggedInUser();
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = 'Sorry, you do not have access to cancel holds for the supplied user.';
			} else {
				//MDN 9/20/2015 The recordId can be empty for Prospector holds
				if (empty($_REQUEST['cancelId']) && empty($_REQUEST['recordId'])) {
					$result['message'] = 'Information about the hold to be cancelled was not provided.';
				} else {
					$cancelId = $_REQUEST['cancelId'];
					$recordId = $_REQUEST['recordId'];
					$result = $patronOwningHold->cancelHold($recordId, $cancelId);
				}
			}
		}

		global $interface;
		// if title come back a single item array, set as the title instead. likewise for message
		if (isset($result['title'])) {
			if (is_array($result['title']) && count($result['title']) == 1) $result['title'] = current($result['title']);
		}
		if (is_array($result['message']) && count($result['message']) == 1) $result['message'] = current($result['message']);

		$interface->assign('cancelResults', $result);

		return array(
			'title' => 'Cancel Hold',
			'body' => $interface->fetch('MyAccount/cancelHold.tpl'),
			'success' => $result['success']
		);
	}

	/** @noinspection PhpUnused */
	function cancelBooking()
	{
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
					$userResult = $patron->cancelBookedMaterial($cancelId);
					$numCancelled += $userResult['success'] ? count($cancelId) : count($cancelId) - count($userResult['message']);
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
			global $logger;
			$logger->log('Booking : ' . $e->getMessage(), Logger::LOG_ERROR);

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

		return array(
			'title' => 'Cancel Booking',
			'modalBody' => $interface->fetch('MyAccount/cancelBooking.tpl'),
			'success' => $result['success'],
			'failed' => $failed
		);
	}

	function freezeHold()
	{
		$user = UserAccount::getLoggedInUser();
		$result = array(
			'success' => false,
			'message' => 'Error ' . translate('freezing') . ' hold.'
		);
		if (!$user) {
			$result['message'] = 'You must be logged in to ' . translate('freeze') . ' a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = 'Sorry, you do not have access to ' . translate('freeze') . ' holds for the supplied user.';
			} else {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					global $logger;
					$logger->log('Freeze Hold, no record or hold Id was passed in AJAX call.', Logger::LOG_ERROR);
					$result['message'] = 'Information about the hold to be ' . translate('frozen') . ' was not provided.';
				} else {
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					$reactivationDate = isset($_REQUEST['reactivationDate']) ? $_REQUEST['reactivationDate'] : null;
					$result = $patronOwningHold->freezeHold($recordId, $holdId, $reactivationDate);
					if ($result['success']) {
						$notice = translate('freeze_info_notice');
						if (translate('frozen') != 'frozen') {
							$notice = str_replace('frozen', translate('frozen'), $notice);  // Translate the phrase frozen from the notice.
						}
						$message = '<div class="alert alert-success">' . $result['message'] . '</div>' . ($notice ? '<div class="alert alert-info">' . $notice . '</div>' : '');
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

	function thawHold()
	{
		$user = UserAccount::getLoggedInUser();
		$result = array( // set default response
			'success' => false,
			'message' => 'Error thawing hold.'
		);

		if (!$user) {
			$result['message'] = 'You must be logged in to ' . translate('thaw') . ' a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = 'Sorry, you do not have access to ' . translate('thaw') . ' holds for the supplied user.';
			} else {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					$result['message'] = 'Information about the hold to be ' . translate('thawed') . ' was not provided.';
				} else {
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					$result = $patronOwningHold->thawHold($recordId, $holdId);
					if ($result['success']) {
						$message = '<div class="alert alert-success">' . $result['message'] . '</div>';
						$result['message'] = $message;
					}
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

	/** @noinspection PhpUnused */
	function addList()
	{
		$return = array();
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$title = (isset($_REQUEST['title']) && !is_array($_REQUEST['title'])) ? urldecode($_REQUEST['title']) : '';
			if (strlen(trim($title)) == 0) {
				$return['success'] = "false";
				$return['message'] = "You must provide a title for the list";
			} else {
				//If the record is not valid, skip the whole thing since the title could be bad too
				if (!empty($_REQUEST['sourceId']) && !is_array($_REQUEST['sourceId'])) {
					$recordToAdd = urldecode($_REQUEST['sourceId']);
					if (!preg_match("/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}|[A-Z0-9_-]+:[A-Z0-9_-]+|\d+$/i", $recordToAdd)) {
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
				if (isset($_REQUEST['desc'])) {
					$desc = $_REQUEST['desc'];
					if (is_array($desc)) {
						$desc = reset($desc);
					}
				} else {
					$desc = "";
				}

				$list->description = strip_tags(urldecode($desc));
				$list->public = isset($_REQUEST['public']) && $_REQUEST['public'] == 'true';
				$list->searchable = isset($_REQUEST['searchable']) && $_REQUEST['searchable'] == 'true';
				if ($existingList) {
					$list->update();
				} else {
					$list->insert();
				}

				if (!empty($_REQUEST['sourceId']) && !is_array($_REQUEST['sourceId'])) {
					$sourceId = urldecode($_REQUEST['sourceId']);
					$source = urldecode($_REQUEST['source']);
					require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
					//Check to see if the user has already added the title to the list.
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
					$userListEntry->source = $source;
					$userListEntry->sourceId = $sourceId;
					if (!$userListEntry->find(true)) {
						$userListEntry->dateAdded = time();
						$userListEntry->insert();
					}
				}

				$return['success'] = 'true';
				$return['newId'] = $list->id;

				$userObject = UserAccount::getActiveUserObj();
				if ($userObject->lastListUsed != $list->id) {
					$userObject->lastListUsed = $list->id;
					$userObject->update();
				}
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

		if (isset($_REQUEST['sourceId'])) {
			$sourceId = $_REQUEST['sourceId'];
			$source = $_REQUEST['source'];
			$interface->assign('sourceId', $sourceId);
			$interface->assign('source', $source);
		}

		//Check to see if we will index the list if it is public
		$location = Location::getSearchLocation();
		$ownerHasListPublisherRole = UserAccount::userHasPermission('Include Lists In Search Results');
		if ($location != null){
			$publicListWillBeIndexed = ($location->publicListsToInclude == 3) || //All public lists
				($location->publicListsToInclude == 1) || //All lists for the current library
				(($location->publicListsToInclude== 2) && $location->locationId == UserAccount::getUserHomeLocationId()) || //All lists for the current location
				(($location->publicListsToInclude == 4) && $ownerHasListPublisherRole) || //All lists for list publishers at the current library
				(($location->publicListsToInclude == 5) && $ownerHasListPublisherRole) || //All lists for list publishers the current location
				(($location->publicListsToInclude == 6) && $ownerHasListPublisherRole) //All lists for list publishers
			;
		}else{
			global $library;
			$publicListWillBeIndexed = ($library->publicListsToInclude == 2) || //All public lists
				(($library->publicListsToInclude == 1)) || //All lists for the current library
				(($library->publicListsToInclude == 3) && $ownerHasListPublisherRole) || //All lists for list publishers at the current library
				(($library->publicListsToInclude == 4) && $ownerHasListPublisherRole) //All lists for list publishers
			;
		}
		$interface->assign('publicListWillBeIndexed', $publicListWillBeIndexed);

		return array(
			'title' => 'Create new List',
			'modalBody' => $interface->fetch("MyAccount/createListForm.tpl"),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.addList(); return false;'>Create List</span>"
		);
	}

	/** @noinspection PhpUnused */
	function getLoginForm()
	{
		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');
		if (!empty($library->loginNotes)){
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$parsedown->setBreaksEnabled(true);
			$loginNotes = $parsedown->parse($library->loginNotes);
			$interface->assign('loginNotes', $loginNotes);
		}

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$interface->assign('forgotPasswordType', $catalog->getForgotPasswordType());
		if (!$library->enableForgotPasswordLink) {
			$interface->assign('forgotPasswordType', 'none');
		}

		if (isset($_REQUEST['multiStep'])) {
			$interface->assign('multiStep', true);
		}
		return $interface->fetch('MyAccount/ajax-login.tpl');
	}

	/** @noinspection PhpUnused */
	function getMasqueradeAsForm()
	{
		global $interface;
		return array(
			'title' => translate('Masquerade As'),
			'modalBody' => $interface->fetch("MyAccount/ajax-masqueradeAs.tpl"),
			'modalButtons' => '<button class="tool btn btn-primary" onclick="$(\'#masqueradeForm\').submit()">Start</button>'
		);
	}

	/** @noinspection PhpUnused */
	function initiateMasquerade()
	{
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::initiateMasquerade();
	}

	/** @noinspection PhpUnused */
	function endMasquerade()
	{
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::endMasquerade();
	}

	/** @noinspection PhpUnused */
	function getChangeHoldLocationForm()
	{
		global $interface;
		/** @var $interface UInterface
		 * @var $user User
		 */
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$patronId = $_REQUEST['patronId'];
			$interface->assign('patronId', $patronId);
			$patronOwningHold = $user->getUserReferredTo($patronId);

			$id = $_REQUEST['holdId'];
			$interface->assign('holdId', $id);

			$currentLocation = $_REQUEST['currentLocation'];
			if (!is_numeric($currentLocation)){
				$location = new Location();
				$location->code = $currentLocation;
				if ($location->find(true)){
					$currentLocation = $location->locationId;
				}else{
					$currentLocation = null;
				}
			}
			$interface->assign('currentLocation', $currentLocation);

			$location = new Location();
			$pickupBranches = $location->getPickupBranches($patronOwningHold);
			$interface->assign('pickupLocations', $pickupBranches);

			$results = array(
				'title' => 'Change Hold Location',
				'modalBody' => $interface->fetch("MyAccount/changeHoldLocation.tpl"),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="AspenDiscovery.Account.doChangeHoldLocation(); return false;">Change Location</span>'
			);
		} else {
			$results = array(
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before changing your hold's pick-up location.",
				'modalButtons' => ""
			);
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function getReactivationDateForm()
	{
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
		return array(
			'title' => $title,
			'modalBody' => $interface->fetch("MyAccount/reactivationDate.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' id='doFreezeHoldWithReactivationDate' onclick='$(\".form\").submit(); return false;'>$title</button>"
		);
	}

	/** @noinspection PhpUnused */
	function changeHoldLocation()
	{
		try {
			$holdId = $_REQUEST['holdId'];
			$newPickupLocation = $_REQUEST['newLocation'];

			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$patronOwningHold = $user->getUserReferredTo($patronId);
				if ($patronOwningHold != false) {
					if ($patronOwningHold->validatePickupBranch($newPickupLocation)){
						return $patronOwningHold->changeHoldPickUpLocation($holdId, $newPickupLocation);
					}else{
						return array(
							'result' => false,
							'message' => 'The selected pickup location is not valid.'
						);
					}
				}else{
					return array(
						'result' => false,
						'message' => 'The logged in user does not have permission to change hold location for the specified user, please login as that user.'
					);
				}
			} else {
				return $results = array(
					'title' => 'Please login',
					'modalBody' => "You must be logged in.  Please close this dialog and login to change this hold's pick up location.",
					'modalButtons' => ""
				);
			}

		} catch (PDOException $e) {
			// What should we do with this error?
			if (IPAddress::showDebuggingInformation()) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}
		return array(
			'result' => false,
			'message' => 'We could not connect to the circulation system, please try again later.'
		);
	}

	/** @noinspection PhpUnused */
	function requestPinReset()
	{
		$catalog = CatalogFactory::getCatalogConnectionInstance();

		//Get the list of pickup branch locations for display in the user interface.
		return $catalog->processEmailResetPinForm();
	}

	/** @noinspection PhpUnused */
	function getCitationFormatsForm()
	{
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

	/** @noinspection PhpUnused */
	function sendMyListEmail()
	{
		global $interface;

		// Get data from AJAX request
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) { // validly formatted List Id
			$listId = $_REQUEST['listId'];
			$to = $_REQUEST['to'];
			$from = $_REQUEST['from'];
			$message = $_REQUEST['message'];

			//Load the list
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $listId;
			if ($list->find(true)) {
				// Build Favorites List
				$listEntries = $list->getListTitles();
				$interface->assign('listEntries', $listEntries);

				// Load the User object for the owner of the list (if necessary):
				if ($list->public == true || (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $list->user_id)) {
					//The user can access the list
					$titleDetails = $list->getListRecords(0, -1, false, 'recordDrivers');
					// get all titles for email list, not just a page's worth
					$interface->assign('titles', $titleDetails);
					$interface->assign('list', $list);

					if (strpos($message, 'http') === false && strpos($message, 'mailto') === false && $message == strip_tags($message)) {
						$interface->assign('message', $message);
						$body = $interface->fetch('Emails/my-list.tpl');

						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = $list->title;
						$emailResult = $mail->send($to, $subject, $body, $from);

						if ($emailResult === true) {
							$result = array(
								'result' => true,
								'message' => 'Your email was sent successfully.'
							);
						} elseif (($emailResult instanceof AspenError)) {
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
		} else { // Invalid listId
			$result = array(
				'result' => false,
				'message' => "Invalid List Id. Your email message could not be sent."
			);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEmailMyListForm()
	{
		global $interface;
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) {
			$listId = $_REQUEST['listId'];

			$interface->assign('listId', $listId);
			return array(
				'title' => 'Email a list',
				'modalBody' => $interface->fetch('MyAccount/emailListPopup.tpl'),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="$(\'#emailListForm\').submit();">Send Email</span>'
			);
		} else {
			return [
				'success' => false,
				'message' => 'You must provide the id of the list to email'
			];
		}
	}

	function renewCheckout()
	{
		if (isset($_REQUEST['patronId']) && isset($_REQUEST['recordId']) && isset($_REQUEST['renewIndicator'])) {
			if (strpos($_REQUEST['renewIndicator'], '|') > 0) {
				list($itemId, $itemIndex) = explode('|', $_REQUEST['renewIndicator']);
			} else {
				$itemId = $_REQUEST['renewIndicator'];
				$itemIndex = null;
			}

			if (!UserAccount::isLoggedIn()) {
				$renewResults = array(
					'success' => false,
					'message' => 'Not Logged in.'
				);
			} else {
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$recordId = $_REQUEST['recordId'];
				$patron = $user->getUserReferredTo($patronId);
				if ($patron) {
					$renewResults = $patron->renewCheckout($recordId, $itemId, $itemIndex);
				} else {
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
		return array(
			'title' => translate('Renew') . ' Item',
			'modalBody' => $interface->fetch('MyAccount/renew-item-results.tpl'),
			'success' => $renewResults['success']
		);
	}

	/** @noinspection PhpUnused */
	function renewSelectedItems()
	{
		if (!UserAccount::isLoggedIn()) {
			$renewResults = array(
				'success' => false,
				'message' => 'Not Logged in.'
			);
		} else {
			if (isset($_REQUEST['selected'])) {
				$user = UserAccount::getLoggedInUser();
				if (method_exists($user, 'renewCheckout')) {
					$failure_messages = array();
					$renewResults = array();
					if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
						foreach ($_REQUEST['selected'] as $selected => $ignore) {
							//Suppress errors because sometimes we don't get an item index
							@list($patronId, $recordId, $itemId, $itemIndex) = explode('|', $selected);
							$patron = $user->getUserReferredTo($patronId);
							if ($patron) {
								$tmpResult = $patron->renewCheckout($recordId, $itemId, $itemIndex);
							} else {
								$tmpResult = array(
									'success' => false,
									'message' => 'Sorry, it looks like you don\'t have access to that patron.'
								);
							}

							if (!$tmpResult['success']) {
								$failure_messages[] = $tmpResult['message'];
							}
						}
						$renewResults['Total'] = count($_REQUEST['selected']);
						$renewResults['NotRenewed'] = count($failure_messages);
						$renewResults['Renewed'] = $renewResults['Total'] - $renewResults['NotRenewed'];
					}else{
						$failure_messages[] = 'No items were selected to renew';
						$renewResults['Total'] = 0;
						$renewResults['NotRenewed'] = 0;
					}
					if ($failure_messages) {
						$renewResults['success'] = false;
						$renewResults['message'] = $failure_messages;
					} else {
						$renewResults['success'] = true;
						$renewResults['message'] = "All items were renewed successfully.";
					}
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

		return array(
			'title' => translate('Renew') . ' Selected Items',
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
			'renewed' => isset($renewResults['Renewed']) ? $renewResults['Renewed'] : []
		);
	}

	function renewAll()
	{
		$renewResults = array(
			'success' => false,
			'message' => array('Unable to renew all titles'),
		);
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			$renewResults = $user->renewAll(true);
		} else {
			$renewResults['message'] = array('You must be logged in to renew titles');
		}

		global $interface;
		$interface->assign('renew_message_data', $renewResults);
		return array(
			'title' => translate('Renew') . ' All',
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
			'renewed' => $renewResults['Renewed']
		);
	}

	/** @noinspection PhpUnused */
	function setListEntryPositions()
	{
		$success = false; // assume failure
		$listId = $_REQUEST['listID'];
		$updates = $_REQUEST['updates'];
		if (ctype_digit($listId) && !empty($updates)) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $listId;
			if ($list->find(true) && $user->canEditList($list)) { // list exists & user can edit
				require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
				$success = true; // assume success now
				foreach ($updates as $update) {
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $listId;
					$userListEntry->id = $update['id'];
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
		return array('success' => $success);
	}

	/** @noinspection PhpUnused */
	function getMenuDataIls()
	{
		global $timer;
		global $interface;

		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->hasIlsConnection()) {
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
				if ($library->enableMaterialsBooking) {
					$ilsSummary['bookings'] = $user->getNumBookingsTotal();
				} else {
					$ilsSummary['bookings'] = '';
				}

				//Expiration and fines
				$interface->assign('ilsSummary', $ilsSummary);
				$interface->setFinesRelatedTemplateVariables();
				if ($interface->getVariable('expiredMessage')) {
					$interface->assign('expiredMessage', str_replace('%date%', $ilsSummary['expires'], $interface->getVariable('expiredMessage')));
				}
				if ($interface->getVariable('expirationNearMessage')) {
					$interface->assign('expirationNearMessage', str_replace('%date%', $ilsSummary['expires'], $interface->getVariable('expirationNearMessage')));
				}
				$ilsSummary['expirationFinesNotice'] = $interface->fetch('MyAccount/expirationFinesNotice.tpl');

				$result = [
					'success' => true,
					'summary' => $ilsSummary
				];
			} else {
				$result['message'] = 'Unknown error';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataRBdigital()
	{
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('rbdigital')) {
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				$rbdigitalSummary = $driver->getAccountSummary($user);
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						if ($linkedUser->isValidForEContentSource('rbdigital')){
							$linkedUserSummary = $driver->getAccountSummary($linkedUser);
							$rbdigitalSummary['numCheckedOut'] += $linkedUserSummary['numCheckedOut'];
							$rbdigitalSummary['numUnavailableHolds'] += $linkedUserSummary['numUnavailableHolds'];
						}
					}
				}
				$timer->logTime("Loaded RBdigital Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $rbdigitalSummary
				];
			} else {
				$result['message'] = 'Invalid for RBdigital';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataCloudLibrary()
	{
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()) {
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
			} else {
				$result['message'] = 'Unknown error';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataAxis360()
	{
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('axis360')) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				$axis360Summary = $driver->getAccountSummary($user);
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$axis360Summary['numCheckedOut'] += $linkedUserSummary['numCheckedOut'];
						$axis360Summary['numUnavailableHolds'] += $linkedUserSummary['numUnavailableHolds'];
						$axis360Summary['numAvailableHolds'] += $linkedUserSummary['numAvailableHolds'];
						$axis360Summary['numHolds'] += $linkedUserSummary['numHolds'];
					}
				}
				$timer->logTime("Loaded Axis 360 Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $axis360Summary
				];
			} else {
				$result['message'] = 'Unknown error';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataHoopla()
	{
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('hoopla')) {
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$hooplaSummaryRaw = $driver->getAccountSummary($user);
				if ($hooplaSummaryRaw == false) {
					$hooplaSummary = [
						'numCheckedOut' => 0,
						'numCheckoutsRemaining' => 0,
					];
				} else {
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
			} else {
				$result['message'] = 'Invalid for Hoopla';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataOverdrive()
	{
		global $timer;
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];
		if (UserAccount::isLoggedIn()) {
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
			} else {
				$result['message'] = 'Invalid for OverDrive';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getRatingsData()
	{
		global $interface;
		$result = array();
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$interface->assign('user', $user);

			//Count of ratings
			$result['ratings'] = $user->getNumRatings();
		}//User is not logged in

		return $result;
	}

	/** @noinspection PhpUnused */
	function getListData()
	{
		global $timer;
		global $interface;
		global $configArray;
		global $memCache;
		$result = array();
		if (UserAccount::isLoggedIn()) {
			//Load a list of lists
			$userListData = $memCache->get('user_list_data_' . UserAccount::getActiveUserId());
			if ($userListData == null || isset($_REQUEST['reload'])) {
				$lists = array();
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$tmpList = new UserList();
				$tmpList->user_id = UserAccount::getActiveUserId();
				$tmpList->whereAdd('deleted = 0');
				$tmpList->orderBy("title ASC");
				$tmpList->find();
				if ($tmpList->getNumResults() > 0) {
					while ($tmpList->fetch()) {
						$lists[$tmpList->id] = array(
							'name' => $tmpList->title,
							'url' => '/MyAccount/MyList/' . $tmpList->id,
							'id' => $tmpList->id,
							'numTitles' => $tmpList->numValidListItems()
						);
					}
				}
				$memCache->set('user_list_data_' . UserAccount::getActiveUserId(), $lists, $configArray['Caching']['user']);
				$timer->logTime("Load Lists");
			} else {
				$lists = $userListData;
				$timer->logTime("Load Lists from cache");
			}

			$interface->assign('lists', $lists);
			$result['lists'] = $interface->fetch('MyAccount/listsMenu.tpl');

		}//User is not logged in

		return $result;
	}

	/** @noinspection PhpUnused */
	public function exportCheckouts()
	{
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
		$showRenewed = ($ils == 'Horizon' || $ils == 'Millennium' || $ils == 'Sierra' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
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
			if ($showOut) {
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Out');
			}
			$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Due');
			if ($showRenewed) {
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Renewed');
			}
			if ($showWaitList) {
				$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, 'Wait List');
			}

			$a = 4;
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
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR);
		}
		exit;
	}

	/** @noinspection PhpUnused */
	public function exportHolds()
	{
		global $configArray;
		$source = $_REQUEST['source'];
		$user = UserAccount::getActiveUserObj();

		$ils = $configArray['Catalog']['ils'];
		$showPosition = ($ils == 'Horizon' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
		$showExpireTime = ($ils == 'Horizon' || $ils == 'Symphony');
		$selectedAvailableSortOption = $this->setSort('availableHoldSort', 'availableHold');
		$selectedUnavailableSortOption = $this->setSort('unavailableHoldSort', 'unavailableHold');
		if ($selectedAvailableSortOption == null) {
			$selectedAvailableSortOption = 'expire';
		}
		if ($selectedUnavailableSortOption == null) {
			$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
		}

		$allHolds = $user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, $source);
		if ($source == 'rbdigital') {
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

		try {
			$curRow = 1;
			for ($i = 0; $i < 2; $i++) {
				if ($i == 0) {
					$exportType = "available";
				} else {
					$exportType = "unavailable";
				}
				if (count($allHolds[$exportType]) == 0) {
					continue;
				}
				if ($exportType == "available") {
					// Add some data
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $curRow, 'Holds - ' . ucfirst($exportType));
					$curRow += 2;

					$objPHPExcel->getActiveSheet()->setCellValue('A' . $curRow, 'Title')
						->setCellValue('B' . $curRow, 'Author')
						->setCellValue('C' . $curRow, 'Format')
						->setCellValue('D' . $curRow, 'Placed')
						->setCellValue('E' . $curRow, 'Pickup')
						->setCellValue('F' . $curRow, 'Available')
						->setCellValue('G' . $curRow, translate('Pickup By'));
				} else {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $curRow, 'Holds - ' . ucfirst($exportType));
					$curRow += 2;
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $curRow, 'Title')
						->setCellValue('B' . $curRow, 'Author')
						->setCellValue('C' . $curRow, 'Format')
						->setCellValue('D' . $curRow, 'Placed')
						->setCellValue('E' . $curRow, 'Pickup');

					if ($showPosition) {
						$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, 'Position')
							->setCellValue('G' . $curRow, 'Status');
						if ($showExpireTime) {
							$objPHPExcel->getActiveSheet()->setCellValue('H' . $curRow, 'Expires');
						}
					} else {
						$objPHPExcel->getActiveSheet()
							->setCellValue('F' . $curRow, 'Status');
						if ($showExpireTime) {
							$objPHPExcel->getActiveSheet()->setCellValue('G' . $curRow, 'Expires');
						}
					}
				}


				$curRow++;
				//Loop Through The Report Data
				foreach ($allHolds[$exportType] as $row) {
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

					if (empty($row['create'])) {
						$placedDate = '';
					} else {
						if (is_array($row['create'])) {
							$placedDate = new DateTime();
							$placedDate->setDate($row['create']['year'], $row['create']['month'], $row['create']['day']);
							$placedDate = $placedDate->format('M d, Y');
						} else {
							$placedDate = $this->isValidTimeStamp($row['create']) ? $row['create'] : strtotime($row['create']);
							$placedDate = date('M d, Y', $placedDate);
						}
					}

					if (isset($row['location'])) {
						$locationString = $row['location'];
					} else {
						$locationString = '';
					}

					if (empty($row['expire'])) {
						$expireDate = '';
					} else {
						if (is_array($row['expire'])) {
							$expireDate = new DateTime();
							$expireDate->setDate($row['expire']['year'], $row['expire']['month'], $row['expire']['day']);
							$expireDate = $expireDate->format('M d, Y');
						} else {
							$expireDate = $this->isValidTimeStamp($row['expire']) ? $row['expire'] : strtotime($row['expire']);
							$expireDate = date('M d, Y', $expireDate);
						}
					}

					if ($exportType == "available") {
						if (empty($row['availableTime'])) {
							$availableDate = 'Now';
						} else {
							$availableDate = $this->isValidTimeStamp($row['availableTime']) ? $row['availableTime'] : strtotime($row['availableTime']);
							$availableDate = date('M d, Y', $availableDate);
						}
						$objPHPExcel->getActiveSheet()
							->setCellValue('A' . $curRow, $titleCell)
							->setCellValue('B' . $curRow, $authorCell)
							->setCellValue('C' . $curRow, $formatString)
							->setCellValue('D' . $curRow, $placedDate)
							->setCellValue('E' . $curRow, $locationString)
							->setCellValue('F' . $curRow, $availableDate)
							->setCellValue('G' . $curRow, $expireDate);
					} else {
						if (isset($row['status'])) {
							$statusCell = $row['status'];
						} else {
							$statusCell = '';
						}

						if (isset($row['frozen']) && $row['frozen'] && $showDateWhenSuspending && !empty($row['reactivateTime'])) {
							$reactivateTime = $this->isValidTimeStamp($row['reactivateTime']) ? $row['reactivateTime'] : strtotime($row['reactivateTime']);
							$statusCell .= " until " . date('M d, Y', $reactivateTime);
						}
						$objPHPExcel->getActiveSheet()
							->setCellValue('A' . $curRow, $titleCell)
							->setCellValue('B' . $curRow, $authorCell)
							->setCellValue('C' . $curRow, $formatString)
							->setCellValue('D' . $curRow, $placedDate);
						if (isset($row['location'])) {
							$objPHPExcel->getActiveSheet()->setCellValue('E' . $curRow, $row['location']);
						} else {
							$objPHPExcel->getActiveSheet()->setCellValue('E' . $curRow, '');
						}

						if ($showPosition) {
							if (isset($row['position'])) {
								$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, $row['position']);
							} else {
								$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, '');
							}

							$objPHPExcel->getActiveSheet()->setCellValue('G' . $curRow, $statusCell);
							if ($showExpireTime) {
								$objPHPExcel->getActiveSheet()->setCellValue('H' . $curRow, $expireDate);
							}
						} else {
							$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, $statusCell);
							if ($showExpireTime) {
								$objPHPExcel->getActiveSheet()->setCellValue('G' . $curRow, $expireDate);
							}
						}
					}
					$curRow++;
				}
				$curRow += 2;
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
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR);
		}
		exit;
	}

	/** @noinspection PhpUnused */
	public function exportReadingHistory()
	{
		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$selectedSortOption = $this->setSort('sort', 'readingHistory');
			if ($selectedSortOption == null) {
				$selectedSortOption = 'checkedOut';
			}
			$readingHistory = $user->getReadingHistory(1, -1, $selectedSortOption, '', true);

			try {
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
					->setCellValue('E3', 'Last Used');

				$a = 4;
				//Loop Through The Report Data
				foreach ($readingHistory['titles'] as $row) {

					$format = is_array($row['format']) ? implode(',', $row['format']) : $row['format'];
					if ($row['checkedOut']) {
						$lastCheckout = translate('In Use');
					} else {
						if (is_numeric($row['checkout'])) {
							$lastCheckout = date('M Y', $row['checkout']);
						} else {
							$lastCheckout = $row['checkout'];
						}
					}

					$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('A' . $a, $row['title'])
						->setCellValue('B' . $a, $row['author'])
						->setCellValue('C' . $a, $format)
						->setCellValue('E' . $a, $lastCheckout);

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
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error exporting to Excel " . $e, Logger::LOG_ERROR);
			}
		}
		exit;
	}

	public function getCheckouts()
	{
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
			$showRenewed = ($source == 'ils' || $source == 'all') && ($ils == 'Horizon' || $ils == 'Millennium' || $ils == 'Sierra' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
			$showWaitList = ($source == 'ils' || $source == 'all') && ($ils == 'Horizon');

			$interface->assign('showOut', $showOut);
			$interface->assign('showRenewed', $showRenewed);
			$interface->assign('showWaitList', $showWaitList);

			// Define sorting options
			$sortOptions = array('title' => 'Title',
				'author' => 'Author',
				'dueDate' => 'Due Date',
				'format' => 'Format',
			);
			$user = UserAccount::getActiveUserObj();
			if (UserAccount::isLoggedIn() == false || empty($user)){
				$result['message'] = translate(['text' => 'login_expired', 'defaultText' => "Your login has timed out. Please login again."]);
			}else{
				if (count($user->getLinkedUsers()) > 0) {
					$sortOptions['libraryAccount'] = 'Library Account';
				}
				if ($showWaitList) {
					$sortOptions['holdQueueLength'] = 'Wait List';
				}
				if ($showRenewed) {
					$sortOptions['renewed'] = 'Times Renewed';
				}

				$interface->assign('sortOptions', $sortOptions);

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

				$result['success'] = true;
				$result['message'] = "";
				$result['checkouts'] = $interface->fetch('MyAccount/checkoutsList.tpl');
			}
		} else {
			$result['message'] = translate('The catalog is offline');
		}

		return $result;
	}

	public function getHolds()
	{
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
			$selectedAvailableSortOption = $this->setSort('availableHoldSort', 'availableHold');
			$selectedUnavailableSortOption = $this->setSort('unavailableHoldSort', 'unavailableHold');

			$user = UserAccount::getActiveUserObj();
			if (UserAccount::isLoggedIn() == false || empty($user)){
				$result['message'] = translate(['text' => 'login_expired', 'defaultText' => "Your login has timed out. Please login again."]);
			}else {

				$interface->assign('allowFreezeHolds', true);

				$ils = $configArray['Catalog']['ils'];
				$showPosition = ($ils == 'Horizon' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX');
				$suspendRequiresReactivationDate = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony' || $ils == 'Koha');
				$interface->assign('suspendRequiresReactivationDate', $suspendRequiresReactivationDate);
				$canChangePickupLocation = ($ils != 'Koha');
				$interface->assign('canChangePickupLocation', $canChangePickupLocation);
				$showPlacedColumn = ($ils == 'Symphony');
				$interface->assign('showPlacedColumn', $showPlacedColumn);

				// Define sorting options
				$unavailableHoldSortOptions = array(
					'title' => 'Title',
					'author' => 'Author',
					'format' => 'Format',
				);
				if ($source != 'rbdigital') {
					$unavailableHoldSortOptions['status'] = 'Status';
				}
				if ($source == 'all' || $source == 'ils') {
					$unavailableHoldSortOptions['location'] = 'Pickup Location';
				}
				if ($showPosition && $source != 'rbdigital') {
					$unavailableHoldSortOptions['position'] = 'Position';
				}
				if ($showPlacedColumn) {
					$unavailableHoldSortOptions['placed'] = 'Date Placed';
				}

				$availableHoldSortOptions = array(
					'title' => 'Title',
					'author' => 'Author',
					'format' => 'Format',
					'expire' => 'Expiration Date',
				);
				if ($source == 'all' || $source == 'ils') {
					$availableHoldSortOptions['location'] = 'Pickup Location';
				}

				if (count($user->getLinkedUsers()) > 0) {
					$unavailableHoldSortOptions['libraryAccount'] = 'Library Account';
					$availableHoldSortOptions['libraryAccount'] = 'Library Account';
				}

				$interface->assign('sortOptions', array(
					'available' => $availableHoldSortOptions,
					'unavailable' => $unavailableHoldSortOptions
				));

				if ($selectedAvailableSortOption == null || !array_key_exists($selectedAvailableSortOption, $availableHoldSortOptions)) {
					$selectedAvailableSortOption = 'expire';
				}
				if ($selectedUnavailableSortOption == null || !array_key_exists($selectedUnavailableSortOption, $unavailableHoldSortOptions)) {
					$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
				}
				$interface->assign('defaultSortOption', array(
					'available' => $selectedAvailableSortOption,
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
						if ($source == 'rbdigital') {
							//RBdigital automatically checks out records so don't show the available section
							unset($allHolds['available']);
						}
						$interface->assign('recordList', $allHolds);
					}
				}

				if (!$library->showDetailedHoldNoticeInformation) {
					$notification_method = '';
				} else {
					$notification_method = ($user->_noticePreferenceLabel != 'Unknown') ? $user->_noticePreferenceLabel : '';
					if ($notification_method == 'Mail' && $library->treatPrintNoticesAsPhoneNotices) {
						$notification_method = 'Telephone';
					}
				}
				$interface->assign('notification_method', strtolower($notification_method));

				$result['success'] = true;
				$result['message'] = "";
				$result['holds'] = $interface->fetch('MyAccount/holdsList.tpl');
			}
		} else {
			$result['message'] = translate('The catalog is offline');
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function getReadingHistory()
	{
		global $interface;
		$showCovers = $this->setShowCovers();

		$result = [
			'success' => false,
			'message' => 'Unknown error',
		];


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

		return $result;
	}

	/** @noinspection PhpUnused */
	function renderReadingHistoryPaginationLink($page, $options)
	{
		return "<a class='page-link btn btn-default btn-sm' onclick='AspenDiscovery.Account.loadReadingHistory(\"{$options['patronId']}\", \"{$options['sort']}\", \"{$page}\", undefined, \"{$options['filter']}\");AspenDiscovery.goToAnchor(\"topOfList\")'>";
	}

	private function isValidTimeStamp($timestamp)
	{
		return is_numeric($timestamp)
			&& ($timestamp <= PHP_INT_MAX)
			&& ($timestamp >= ~PHP_INT_MAX);
	}

	function setShowCovers()
	{
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

	function setSort($requestParameter, $sortType)
	{
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
	function deleteReadingHistoryEntry()
	{
		$result = [
			'success' => false,
			'title' => translate('Error'),
			'message' => translate('Unknown error'),
		];

		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron == null) {
				$result['message'] = 'You do not have permissions to delete reading history for this user';
			} else {
				$permanentId = $_REQUEST['permanentId'];
				$selectedTitles = [$permanentId => $permanentId];
				$readingHistoryAction = 'deleteMarked';
				$result = $patron->doReadingHistoryAction($readingHistoryAction, $selectedTitles);
			}
		} else {
			$result['message'] = 'You must be logged in to delete from the reading history';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteReadingHistoryEntryByTitleAuthor()
	{
		$result = [
			'success' => false,
			'title' => translate('Error'),
			'message' => translate('Unknown error'),
		];

		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron == null) {
				$result['message'] = 'You do not have permissions to delete reading history for this user';
			} else {
				$title = $_REQUEST['title'];
				$author = $_REQUEST['author'];
				$result = $patron->deleteReadingHistoryEntryByTitleAuthor($title, $author);
			}
		} else {
			$result['message'] = 'You must be logged in to delete from the reading history';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function dismissMessage()
	{
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		if (!isset($_REQUEST['messageId'])) {
			return ['success' => false, 'message' => 'Message Id not provided'];
		} else if (UserAccount::getActiveUserId() == false) {
			return ['success' => false, 'message' => 'User is not logged in'];
		} else {
			$message = new UserMessage();
			$message->id = $_REQUEST['messageId'];
			if ($message->find(true)) {
				if ($message->userId != UserAccount::getActiveUserId()) {
					return ['success' => false, 'message' => 'Message is not for the active user'];
				} else {
					$message->isDismissed = 1;
					$message->update();
					return ['success' => true, 'message' => 'Message was dismissed'];
				}
			} else {
				return ['success' => false, 'message' => 'Could not find the message to dismiss'];
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissSystemMessage()
	{
		require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
		if (!isset($_REQUEST['messageId'])) {
			return ['success' => false, 'message' => 'Message Id not provided'];
		} else if (UserAccount::getActiveUserId() == false) {
			return ['success' => false, 'message' => 'User is not logged in'];
		} else {
			$message = new SystemMessage();
			$message->id = $_REQUEST['messageId'];
			if ($message->find(true)) {
				require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
				$systemMessageDismissal = new SystemMessageDismissal();
				$systemMessageDismissal->userId = UserAccount::getActiveUserId();
				$systemMessageDismissal->systemMessageId = $message->id;
				if ($systemMessageDismissal->find(true)) {
					return ['success' => true, 'message' => 'Message was already dismissed'];
				} else {
					$systemMessageDismissal->insert();
					return ['success' => true, 'message' => 'Message was dismissed'];
				}
			} else {
				return ['success' => false, 'message' => 'Could not find the message to dismiss'];
			}
		}
	}

	/** @noinspection PhpUnused */
	function enableAccountLinking()
	{
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		require_once ROOT_DIR . '/sys/Account/UserLink.php';
		$activeUserId = UserAccount::getActiveUserId();
		$userLink = new UserLink();
		$userLink->linkedAccountId = $activeUserId;
		$userLink->find();
		while ($userLink->fetch()) {
			$userLink->linkingDisabled = "0";
			$userLink->update();

			$userMessage = new UserMessage();
			$userMessage->messageType = 'linked_acct_notify_pause_' . $activeUserId;
			$userMessage->userId = $userLink->primaryAccountId;
			$userMessage->isDismissed = "0";
			if ($userMessage->find()) {
				while ($userMessage->fetch()) {
					$userMessage->isDismissed = 1;
					$userMessage->update();
				}
			}
		}

		$userMessage = new UserMessage();
		$userMessage->messageType = 'confirm_linked_accts';
		$userMessage->userId = $activeUserId;
		$userMessage->isDismissed = "0";
		$userMessage->find();
		while ($userMessage->fetch()) {
			$userMessage->isDismissed = 1;
			$userMessage->update();
		}

		return ['success' => true, 'message' => 'Account Linking Resumed'];
	}

	/** @noinspection PhpUnused */
	function stopAccountLinking()
	{
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		require_once ROOT_DIR . '/sys/Account/UserLink.php';
		$activeUserId = UserAccount::getActiveUserId();
		$userLink = new UserLink();
		$userLink->linkedAccountId = $activeUserId;
		$userLink->find();
		while ($userLink->fetch()) {
			$userLink->delete();

			$userMessage = new UserMessage();
			$userMessage->messageType = 'linked_acct_notify_pause_' . $activeUserId;
			$userMessage->userId = $userLink->primaryAccountId;
			$userMessage->isDismissed = "0";
			if ($userMessage->find()) {
				while ($userMessage->fetch()) {
					$userMessage->message = "An account you are linking to changed their login. Account linking with them has been disabled.";
					$userMessage->update();
				}
			}
		}

		$userMessage = new UserMessage();
		$userMessage->messageType = 'confirm_linked_accts';
		$userMessage->userId = $activeUserId;
		$userMessage->isDismissed = "0";
		$userMessage->find();
		while ($userMessage->fetch()) {
			$userMessage->isDismissed = 1;
			$userMessage->update();
		}

		return ['success' => true, 'message' => 'Account Linking Stopped'];
	}

	/** @noinspection PhpUnused */
	function createGenericOrder($paymentType = '')
	{
		$transactionDate = time();
		global $configArray;
		$ils = $configArray['Catalog']['ils'];
		$user = UserAccount::getLoggedInUser();
		if ($user == null) {
			return ['success' => false, 'message' => translate(['text' => 'payment_not_signed_in', 'defaultText' => 'You must be signed in to pay fines, please sign in.'])];
		} else {
			$patronId = $_REQUEST['patronId'];

			$patron = $user->getUserReferredTo($patronId);

			if ($patron == false) {
				return ['success' => false, 'message' => translate(['text' => 'payment_patron_not_found', 'defaultText' => 'Could not find the patron referred to, please try again.'])];
			}
			$userLibrary = $patron->getHomeLibrary();

			if (empty($_REQUEST['selectedFine']) && $userLibrary->finesToPay != 0) {
				return ['success' => false, 'message' => translate(['text' => 'payment_none_selected', 'defaultText' => 'Select at least one fine to pay.'])];
			}
			if (isset($_REQUEST['selectedFine'])) {
				$selectedFines = $_REQUEST['selectedFine'];
			}
			$fines = $patron->getFines(false);
			$useOutstanding = $patron->getCatalogDriver()->showOutstandingFines();

			$finesPaid = '';
			$purchaseUnits = [];
			$purchaseUnits['items'] = [];
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$totalFines = 0;

			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

			//List how fines have been paid by type
			//0 = no payments applied
			//1 = partial payment applied
			//2 = fully paid
			$finesPaidByType = [];

			foreach ($fines[$patronId] as $fine) {
				$finePayment = 0;
				$addToOrder = false;
				if ($userLibrary->finesToPay == 0) {
					$addToOrder = true;
				} else {
					foreach ($selectedFines as $fineId => $status) {
						if ($fine['fineId'] == $fineId) {
							$addToOrder = true;
						}
					}
				}
				if ($addToOrder) {
					$finePayment = 2;
					if (!empty($finesPaid)) {
						$finesPaid .= ',';
					}
					$fineId = $fine['fineId'];
					$finesPaid .= $fineId;
					if (isset($_REQUEST['amountToPay'][$fineId])) {
						$fineAmount = $_REQUEST['amountToPay'][$fineId];
						$maxFineAmount = $useOutstanding ? $fine['amountOutstandingVal'] : $fine['amountVal'];
						if (!is_numeric($fineAmount) || $fineAmount <= 0 || $fineAmount > $maxFineAmount) {
							return ['success' => false, 'message' => translate(['text' => 'payment_invalid_amount', 'defaultText' => 'Invalid amount entered for fine. Please enter an amount over 0 and less than the total amount owed.'])];
						}
						if ($fineAmount != $maxFineAmount) {
							//Record this is a partially paid fine
							$finesPaid .= '|' . $fineAmount;
							$finePayment = 1;
						} else {
							if ($ils == 'CarlX') { // CarlX SIP2 Fee Paid requires amount 
								$finesPaid .= '|' . $fineAmount;
							}
						}

					} else {
						$fineAmount = $useOutstanding ? $fine['amountOutstandingVal'] : $fine['amountVal'];
					}

					$purchaseUnits['items'][] = [
						'custom_id' => $fineId,
						'name' => StringUtils::trimStringToLengthAtWordBoundary($fine['reason'], 120, true),
						'description' => StringUtils::trimStringToLengthAtWordBoundary($fine['message'], 120, true),
						'unit_amount' => [
							'currency_code' => $currencyCode,
							'value' => round($fineAmount, 2),
						],
						'quantity' => 1
					];
					$totalFines += $fineAmount;
				}

				if (!array_key_exists(strtolower($fine['type']), $finesPaidByType)) {
					$finesPaidByType[strtolower($fine['type'])] = $finePayment;
				} else {
					if ($finePayment == 0) {
						if ($finesPaidByType[strtolower($fine['type'])] >= 1) {
							$finesPaidByType[strtolower($fine['type'])] = 1;
						}
					} elseif ($finePayment == 1) {
						$finesPaidByType[strtolower($fine['type'])] = 1;
					} elseif ($finePayment == 2) {
						if ($finesPaidByType[strtolower($fine['type'])] != 2) {
							$finesPaidByType[strtolower($fine['type'])] = 1;
						}
					}
				}
			}

			//Determine if fines have been paid in the proper order
			if (!empty($userLibrary->finePaymentOrder)) {
				$paymentOrder = explode('|', strtolower($userLibrary->finePaymentOrder));

				//Add another category for everything else.
				$paymentOrder[] = '!!other!!';
				//Find the actual status for each category
				$paymentOrder = array_flip($paymentOrder);
				foreach ($paymentOrder as $paymentOrderKey => $value) {
					//-1 indicates there are no fines for this type
					$paymentOrder[$paymentOrderKey] = -1;
				}

				foreach ($finesPaidByType as $type => $finePayment) {
					if (array_key_exists($type, $paymentOrder)) {
						$paymentOrder[$type] = $finePayment;
					} else {
						if ($finePayment > $paymentOrder['!!other!!']) {
							$paymentOrder['!!other!!'] = $finePayment;
						}
					}
				}

				//This is the order everything should be paid in.
				//We want to check to be sure nothing is partially or fully paid if the previous status is not fully paid
				$paymentKeys = array_keys($paymentOrder);
				for ($i = 0; $i < count($paymentKeys) - 1; $i++) {
					$lastPaymentType = $paymentKeys[$i];
					$lastPaymentStatus = $paymentOrder[$lastPaymentType];
					for ($j = $i + 1; $j < count($paymentKeys); $j++) {
						$nextPaymentType = $paymentKeys[$j];
						$nextPaymentStatus = $paymentOrder[$nextPaymentType];
						//We have a problem if a lower priority fine is partially or fully paid and the higher priority is not fully paid
						if ($lastPaymentStatus != -1 && $lastPaymentStatus != 2 && $nextPaymentStatus >= 1) {
							return ['success' => false, 'message' => translate(['text' => 'bad_payment_order', 'defaultText' => 'You must pay all fines of type <strong>%1%</strong> before paying other types.', 1 => $lastPaymentType])];
						}
					}
				}
			}

			$purchaseUnits['amount'] = [
				'currency_code' => $currencyCode,
				'value' => round($totalFines, 2),
				'breakdown' => [
					'item_total' => [
						'currency_code' => $currencyCode,
						'value' => round($totalFines, 2),
					],
				]
			];

			if ($totalFines < $userLibrary->minimumFineAmount) {
				return ['success' => false, 'message' => translate(['text' => 'You must select at least %1% in fines to pay.', 1 => sprintf('$%01.2f', $userLibrary->minimumFineAmount)])];
			}

			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->userId = $patronId;
			$payment->completed = 0;
			$payment->finesPaid = $finesPaid;
			$payment->totalPaid = $totalFines;
			$payment->paymentType = $paymentType;
			$payment->transactionDate = $transactionDate;
			$paymentId = $payment->insert();
			$purchaseUnits['custom_id'] = $paymentId;

			return [$userLibrary, $payment, $purchaseUnits];
		}
	}

	function createPayPalOrder(){
		global $configArray;
		list($userLibrary, $payment, $purchaseUnits) = $this->createGenericOrder('paypal');

		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$payPalAuthRequest = new CurlWrapper();
		//Connect to PayPal
		if ($userLibrary->payPalSandboxMode == 1) {
			$baseUrl = 'https://api.sandbox.paypal.com';
		} else {
			$baseUrl = 'https://api.paypal.com';
		}

		$clientId = $userLibrary->payPalClientId;
		$clientSecret = $userLibrary->payPalClientSecret;

		//Get the access token
		$authInfo = base64_encode("$clientId:$clientSecret");
		$payPalAuthRequest->addCustomHeaders([
			"Accept: application/json",
			"Accept-Language: en_US",
			"Authorization: Basic $authInfo"
		], true);
		$postParams = [
			'grant_type' => 'client_credentials',
		];

		$accessTokenUrl = $baseUrl . "/v1/oauth2/token";
		$accessTokenResults = $payPalAuthRequest->curlPostPage($accessTokenUrl, $postParams);
		$accessTokenResults = json_decode($accessTokenResults);
		if (empty($accessTokenResults->access_token)) {
			return ['success' => false, 'message' => 'Unable to authenticate with PayPal, please try again in a few minutes.'];
		} else {
			$accessToken = $accessTokenResults->access_token;
		}

		//Setup the payment request (https://developer.paypal.com/docs/checkout/reference/server-integration/set-up-transaction/)
		$payPalPaymentRequest = new CurlWrapper();
		$payPalPaymentRequest->addCustomHeaders([
			"Accept: application/json",
			"Content-Type: application/json",
			"Accept-Language: en_US",
			"Authorization: Bearer $accessToken"
		], false);
		$paymentRequestUrl = $baseUrl . '/v2/checkout/orders';
		$paymentRequestBody = [
			'intent' => 'CAPTURE',
			'application_context' => [
				'brand_name' => $userLibrary->displayName,
				'locale' => 'en-US',
				'shipping_preferences' => 'NO_SHIPPING',
				'user_action' => 'PAY_NOW',
				'return_url' => $configArray['Site']['url'] . '/MyAccount/PayPalReturn',
				'cancel_url' => $configArray['Site']['url'] . '/MyAccount/Fines'
			],
			'purchase_units' => [
				0 => $purchaseUnits,
			]
		];

		$paymentResponse = $payPalPaymentRequest->curlPostBodyData($paymentRequestUrl, $paymentRequestBody);
		$paymentResponse = json_decode($paymentResponse);

		if ($paymentResponse->status != 'CREATED') {
			return ['success' => false, 'message' => 'Unable to create your order in PayPal.'];
		}

		//Log the request in the database so we can validate it on return
		$payment->orderId = $paymentResponse->id;
		$payment->update();

		return ['success' => true, 'orderInfo' => $paymentResponse, 'orderID' => $paymentResponse->id];
	}

	/** @noinspection PhpUnused */
	function completePayPalOrder()
	{
		$orderId = $_REQUEST['orderId'];
		$patronId = $_REQUEST['patronId'];

		//Get the order information
		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$payment = new UserPayment();
		$payment->orderId = $orderId;
		$payment->userId = $patronId;
		if ($payment->find(true)) {
			if ($payment->completed) {
				return ['success' => false, 'message' => 'This payment has already been processed'];
			} else {
				$user = UserAccount::getActiveUserObj();
				$patron = $user->getUserReferredTo($patronId);
				return $patron->completeFinePayment($payment);
			}
		} else {
			return ['success' => false, 'message' => 'Unable to find the order you processed, please visit the library with your receipt'];
		}
	}

	/** @noinspection PhpUnused */
	function createMSBOrder()
	{
		global $configArray;
		list($userLibrary, $payment, $purchaseUnits) = $this->createGenericOrder('msb');
		$baseUrl = "https://msbpay.demo.gilacorp.com/"; // TODO: create a database variable
		$paymentRequestUrl = $baseUrl . "NashvillePublicLibrary/"; // TODO: create a database variable
		$paymentRequestUrl .= "?ReferenceID=".$payment->id;
		$paymentRequestUrl .= "&PaymentType=CC";
		$paymentRequestUrl .= "&TotalAmount=".$payment->totalPaid;
		$paymentRequestUrl .= "&PaymentRedirectUrl=".$configArray['Site']['url'] . '/MyAccount/Fines';
		return ['success' => true, 'message' => 'Redirecting to payment processor', 'paymentRequestUrl' => $paymentRequestUrl];
	}

	/** @noinspection PhpUnused */
	function dismissPlacard(){
		$patronId = $_REQUEST['patronId'];
		$placardId = $_REQUEST['placardId'];

		$result = [
			'success' => false,
			'message' => 'Unknown Error',
		];

		if ($patronId != UserAccount::getActiveUserId()){
			$result['message'] = 'Incorrect user information, please login again.';
		}else{
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			$placard = new Placard();
			$placard->id = $placardId;
			if (!$placard->find(true)){
				$result['message'] = 'Incorrect placard provided, please try again.';
			}else{
				require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
				$placardDismissal = new PlacardDismissal();
				$placardDismissal->placardId = $placardId;
				$placardDismissal->userId = $patronId;
				$placardDismissal->insert();
				$result = [
					'success' => true
				];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function updateAutoRenewal(){
		$patronId = $_REQUEST['patronId'];
		$allowAutoRenewal = ($_REQUEST['allowAutoRenewal'] == 'on' || $_REQUEST['allowAutoRenewal'] == 'true');

		if (!UserAccount::isLoggedIn()) {
			$result = array(
				'success' => false,
				'message' => 'Sorry, you must be logged in to change auto renewal.'
			);
		} else {
			$user = UserAccount::getActiveUserObj();
			if ($user->id == $patronId){
				$result = $user->updateAutoRenewal($allowAutoRenewal);
			}else{
				$result = array(
					'success' => false,
					'message' => 'Invalid user information, please logout and login again.'
				);
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getSaveToListForm(){
		global $interface;

		$sourceId = $_REQUEST['sourceId'];
		$source = $_REQUEST['source'];
		$interface->assign('sourceId', $sourceId);
		$interface->assign('source', $source);

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		UserList::getUserListsForSaveForm($source, $sourceId);

		return array(
			'title' => 'Add To List',
			'modalBody' => $interface->fetch("MyAccount/saveToList.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.saveToList(); return false;'>" . translate("Save To List") . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function saveToList(){
		$result = array();

		if (!UserAccount::isLoggedIn()) {
			$result['success'] = false;
			$result['message'] = 'Please login before adding a title to list.';
		}else{
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
			$result['success'] = true;
			$sourceId = $_REQUEST['sourceId'];
			$source = $_REQUEST['source'];
			$listId = $_REQUEST['listId'];
			$notes = $_REQUEST['notes'];

			//Check to see if we need to create a list
			$userList = new UserList();
			$listOk = true;
			if (empty($listId)){
				$userList->title = "My Favorites";
				$userList->user_id = UserAccount::getActiveUserId();
				$userList->public = 0;
				$userList->description = '';
				$userList->insert();
			}else{
				$userList->id = $listId;
				if (!$userList->find(true)){
					$result['success'] = false;
					$result['message'] = 'Sorry, we could not find that list in the system.';
					$listOk = false;
				}
			}

			if ($listOk){
				$userListEntry = new UserListEntry();
				$userListEntry->listId = $userList->id;

				//TODO: Validate the entry
				$isValid = true;
				if (!$isValid) {
					$result['success'] = false;
					$result['message'] = 'Sorry, that is not a valid entry for the list.';
				}else {
					if (empty($sourceId) || empty($source)){
						$result['success'] = false;
						$result['message'] = 'Unable to add that to a list, not correctly specified.';
					}else {
						$userListEntry->source = $source;
						$userListEntry->sourceId = $sourceId;

						$existingEntry = false;
						if ($userListEntry->find(true)) {
							$existingEntry = true;
						}
						$userListEntry->notes = strip_tags($notes);
						$userListEntry->dateAdded = time();
						if ($existingEntry) {
							$userListEntry->update();
						} else {
							$userListEntry->insert();
						}

						$userObject = UserAccount::getActiveUserObj();
						if ($userObject->lastListUsed != $userList->id) {
							$userObject->lastListUsed = $userList->id;
							$userObject->update();
						}
						$result['success'] = true;
						$result['message'] = 'This title was saved to your list successfully.';
					}
				}
			}

		}

		return $result;
	}
}
