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
			'title' => translate(['text'=>'Add as Browse Category to Home Page', 'isAdminFacing'=>'true']),
			'modalBody' => $interface->fetch('Browse/newBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>" . translate(['text'=>'Create Category', 'isAdminFacing'=>'true']) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function addAccountLink()
	{
		if (!UserAccount::isLoggedIn()) {
			$result = array(
				'result' => false,
				'message' => translate(['text'=>'Sorry, you must be logged in to manage accounts.', 'isPublicFacing'=>true])
			);
		} else {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];

			$accountToLink = UserAccount::validateAccount($username, $password);
			$user = UserAccount::getLoggedInUser();

			if (!UserAccount::isLoggedIn()) {
				$result = array(
					'result' => false,
					'message' => translate(['text'=>'You must be logged in to link accounts, please login again', 'isPublicFacing'=>true])
				);
			} elseif ($accountToLink) {
				if ($accountToLink->id != $user->id) {
					$addResult = $user->addLinkedUser($accountToLink);
					if ($addResult === true) {
						$result = array(
							'result' => true,
							'message' => translate(['text'=>'Successfully linked accounts.', 'isPublicFacing'=>true])
						);
					} else { // insert failure or user is blocked from linking account or account & account to link are the same account
						$result = array(
							'result' => false,
							'message' => translate(['text'=>'Sorry, we could not link to that account.  Accounts cannot be linked if all libraries do not allow account linking.  Please contact your local library if you have questions.', 'isPublicFacing'=>true])
						);
					}
				} else {
					$result = array(
						'result' => false,
						'message' => translate(['text'=>'You cannot link to yourself.', 'isPublicFacing'=>true])
					);
				}
			} else {
				$result = array(
					'result' => false,
					'message' => translate(['text'=>'Sorry, we could not find a user with that information to link to.', 'isPublicFacing'=>true])
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
				'message' => translate(['text'=>'Sorry, you must be logged in to manage accounts.', 'isPublicFacing'=>true])
			);
		} else {
			$accountToRemove = $_REQUEST['idToRemove'];
			$user = UserAccount::getLoggedInUser();
			if ($user->removeLinkedUser($accountToRemove)) {
				$result = array(
					'result' => true,
					'message' => translate(['text'=>'Successfully removed linked account.', 'isPublicFacing'=>true])
				);
			} else {
				$result = array(
					'result' => false,
					'message' => translate(['text'=>'Sorry, we could remove that account.', 'isPublicFacing'=>true])
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
			'title' => translate(['text'=>'Account to Manage','isPublicFacing'=>true]),
			'modalBody' => $interface->fetch('MyAccount/addAccountLink.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.processAddLinkedUser(); return false;'>" . translate(['text'=>"Add Account",'isPublicFacing'=>true]) . "</span>"
		);
	}

	/** @noinspection PhpUnused */
	function getBulkAddToListForm()
	{
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		return array(
			'title' => translate(['text'=>'Add titles to list','isPublicFacing'=>true]),
			'modalBody' => $interface->fetch('MyAccount/bulkAddToListPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Lists.processBulkAddForm(); return false;'>" . translate(['text'=>"Add To List",'isPublicFacing'=>true]) . "</span>"
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
					$message = $saveOk ? 'Your search was saved successfully.  You can view the saved search by clicking on Search History within the Account Menu.' . '<a href="/Search/History?require_login">' . 'View Saved Searches' . '</a>' : "Sorry, we could not save that search for you.  It may have expired.";
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
		$cancelButtonLabel = translate(['text'=>'Confirm Cancel Hold','isPublicFacing'=>true]);
		return array(
			'title' => translate(['text'=>'Cancel Hold','isPublicFacing'=>true]),
			'body' => translate(['text'=>"Are you sure you want to cancel this hold?",'isPublicFacing'=>true]),
			'buttons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.cancelHold(\"$patronId\", \"$recordId\", \"$cancelId\")'>$cancelButtonLabel</span>",
		);
	}

	function cancelHold()
	{
		$result = array(
			'success' => false,
			'message' => translate(['text'=>'Error cancelling hold.','isPublicFacing'=>true])
		);

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = translate(['text'=>'You must be logged in to cancel a hold.  Please close this dialog and login again.','isPublicFacing'=>true]);;
		} else {
			//Determine which user the hold is on so we can cancel it.
			$patronId = $_REQUEST['patronId'];
			$user = UserAccount::getLoggedInUser();
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate(['text'=>'Sorry, you do not have access to cancel holds for the supplied user.','isPublicFacing'=>true]);;
			} else {
				//MDN 9/20/2015 The recordId can be empty for Prospector holds
				if (empty($_REQUEST['cancelId']) && empty($_REQUEST['recordId'])) {
					$result['message'] = translate(['text'=>'Information about the hold to be cancelled was not provided.','isPublicFacing'=>true]);;
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
			'title' => translate(['text'=>'Cancel Hold','isPublicFacing'=>true]),
			'body' => $interface->fetch('MyAccount/cancelHold.tpl'),
			'success' => $result['success']
		);
	}

	function cancelHoldSelectedItems()
	{
		$result = array(
			'success' => false,
			'message' => 'Error cancelling hold.'
		);

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to cancel a hold.  Please close this dialog and login again.';
		} else {
			$success = 0;
			$user = UserAccount::getLoggedInUser();
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			if(isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
				$total = count($_REQUEST['selected']);
				foreach($_REQUEST['selected'] as $selected => $ignore) {
					@list($patronId, $recordId, $cancelId) = explode('|', $selected);
					$patronOwningHold = $user->getUserReferredTo($patronId);
					if ($patronOwningHold == false) {
						$tmpResult = array(
							'success' => false,
							'message' => 'Sorry, it looks like you don\'t have access to that patron.'
						);
					} else {
						foreach ($allUnavailableHolds as $key) {
							if($key->sourceId == $recordId) {
								$holdType = $key->source;
								break;
							}
						}
						if ($holdType == 'ils') {
							$tmpResult = $user->cancelHold($recordId, $cancelId);
							if($tmpResult['success']){$success++;}
						} else if ($holdType == 'axis360') {
							require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
							$driver = new Axis360Driver();
							$tmpResult = $driver->cancelHold($user, $recordId);
							if($tmpResult['success']){$success++;}
						} else if ($holdType == 'overdrive') {
							require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
							$driver = new OverDriveDriver();
							$tmpResult = $driver->cancelHold($user, $recordId);
							if($tmpResult['success']){$success++;}
						} else if ($holdType == 'cloud_library') {
							require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
							$driver = new CloudLibraryDriver();
							$tmpResult = $driver->cancelHold($user, $recordId);
							if($tmpResult['success']){$success++;}
						}

						$message = '<div class="alert alert-success">' . $success . ' of ' . $total . ' holds were canceled.</div>';
						$tmpResult['message'] = $message;
					}
				}
			} else {
				$tmpResult['message'] = 'No holds were selected to canceled';
			}
		}

		return $tmpResult;
	}

	function cancelAllHolds()
	{
		$tmpResult = array(
			'success' => false,
			'message' => array('Unable to cancel all holds'),
		);
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			$total = count($allUnavailableHolds);
			$success = 0;

			foreach ($allUnavailableHolds as $hold) {
				// cancel each hold
				$recordId = $hold->sourceId;
				$cancelId = $hold->cancelId;
				$holdType = $hold->source;
				if ($holdType == 'ils') {
					$tmpResult = $user->cancelHold($recordId, $cancelId);
					if($tmpResult['success']){$success++;}
				} else if ($holdType == 'axis360') {
					require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
					$driver = new Axis360Driver();
					$tmpResult = $driver->cancelHold($user, $recordId);
					if($tmpResult['success']){$success++;}
				} else if ($holdType == 'overdrive') {
					require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
					$driver = new OverDriveDriver();
					$tmpResult = $driver->cancelHold($user, $recordId);
					if($tmpResult['success']){$success++;}
				} else if ($holdType == 'cloud_library') {
					require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
					$driver = new CloudLibraryDriver();
					$tmpResult = $driver->cancelHold($user, $recordId);
					if($tmpResult['success']){$success++;}
				}

				$message = '<div class="alert alert-success">' . $success . ' of ' . $total . ' holds were canceled.</div>';
				$tmpResult['message'] = $message;

			}
		} else {
			$tmpResult['message'] = 'You must be logged in to cancel holds';
		}

		return $tmpResult;
	}

	function freezeHold()
	{
		$user = UserAccount::getLoggedInUser();
		$result = array(
			'success' => false,
			'message' => translate(['text' => 'Error freezing hold.', 'isPublicFacing'=>true])
		);
		if (!$user) {
			$result['message'] = translate(['text' => 'You must be logged in to freeze a hold.  Please close this dialog and login again.', 'isPublicFacing'=>true]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate(['text' => 'Sorry, you do not have access to freeze holds for the supplied user.', 'isPublicFacing'=>true]);
			} else {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					global $logger;
					$logger->log('Freeze Hold, no record or hold Id was passed in AJAX call.', Logger::LOG_ERROR);
					$result['message'] = translate(['text' => 'Information about the hold to be frozen was not provided.', 'isPublicFacing'=>true]);
				} else {
					$recordId = $_REQUEST['recordId'];
					$holdId = $_REQUEST['holdId'];
					$reactivationDate = isset($_REQUEST['reactivationDate']) ? $_REQUEST['reactivationDate'] : null;
					$result = $patronOwningHold->freezeHold($recordId, $holdId, $reactivationDate);
					if ($result['success']) {
						$message = '<div class="alert alert-success">' . $result['message'] . '</div>';
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
			$result['message'] = translate(['text' => 'No Patron was specified.', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	function freezeHoldSelectedItems() {
		$tmpResult = array( // set default response
			'success' => false,
			'message' => 'Error freezing hold.'
		);

		if (!UserAccount::isLoggedIn()) {
			$tmpResult['message'] = 'You must be logged in to freeze a hold.  Please close this dialog and login again.';
		} else {
			$user = UserAccount::getLoggedInUser();
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			$success = 0;
			$failed = 0;
			if(isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
				$total = count($_REQUEST['selected']);
				foreach($_REQUEST['selected'] as $selected => $ignore) {
					@list($patronId, $recordId, $holdId) = explode('|', $selected);
					$patronOwningHold = $user->getUserReferredTo($patronId);
					if ($patronOwningHold == false) {
						$tmpResult = array(
							'success' => false,
							'message' => 'Sorry, it looks like you don\'t have access to that patron.'
						);
					} else {
						foreach ($allUnavailableHolds as $key) {
							if($key->sourceId == $recordId) {
								$holdType = $key->source;
								$frozen = $key->frozen;
								$canFreeze = $key->canFreeze;
								break;
							}
						}
						if($frozen != 1 && $canFreeze == 1){
							if ($holdType == 'ils') {
								$tmpResult = $user->freezeHold($recordId, $holdId, false);
								if($tmpResult['success']){$success++;}else{$failed++;}
							} else if ($holdType == 'axis360') {
								require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
								$driver = new Axis360Driver();
								$tmpResult = $driver->freezeHold($user, $recordId);
								if($tmpResult['success']){$success++;}else{$failed++;}
							} else if ($holdType == 'overdrive') {
								require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
								$driver = new OverDriveDriver();
								$tmpResult = $driver->freezeHold($user, $recordId, null);
								if($tmpResult['success']){$success++;}else{$failed++;}
							//Cloud Library holds can't be frozen
//							} else if ($holdType == 'cloud_library') {
//								require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
//								$driver = new CloudLibraryDriver();
//								$tmpResult = $driver->freezeHold($user, $recordId);
//								if($tmpResult['success']){$success++;}else{$failed++;}
							} else {
								$failed++;
							}
						} else if ($canFreeze == 0){
							$failed++;
						} else if ($frozen == 1) {
							$failed++;
						}

						$message = '<div class="alert alert-success">' . $success . ' of ' . $total . ' holds were frozen.</div>';
						$tmpResult['message'] = $message;

					}
				}
			} else {
				$tmpResult['message'] = 'No holds were selected to freeze';
			}
		}

		return $tmpResult;
	}

	function freezeHoldAll() {
		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			$tmpResult['message'] = 'You must be logged in to modify a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$tmpResult = $user->freezeAllHolds();
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Modifying Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$tmpResult['message'] = 'No Patron was specified.';
		}
		return $tmpResult;
	}

	function thawHold()
	{
		$user = UserAccount::getLoggedInUser();
		$result = array( // set default response
			'success' => false,
			'message' => 'Error thawing hold.'
		);

		if (!$user) {
			$result['message'] = translate(['text' => 'You must be logged in to thaw a hold.  Please close this dialog and login again.', 'isPublicFacing'=>true]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate(['text' => 'Sorry, you do not have access to thaw holds for the supplied user.', 'isPublicFacing'=>true]);
			} else {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					$result['message'] = translate(['text' => 'Information about the hold to be thawed was not provided.', 'isPublicFacing'=>true]);
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
			$result['message'] = translate(['text' => 'No Patron was specified.', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	function thawHoldSelectedItems() {
		$result = array( // set default response
			'success' => false,
			'message' => 'Error thawing hold.'
		);

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to thaw a hold.  Please close this dialog and login again.';
		} else {
			$success = 0;
			$failed = 0;
			$user = UserAccount::getLoggedInUser();
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			if(isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
				$total = count($_REQUEST['selected']);
				foreach($_REQUEST['selected'] as $selected => $ignore) {
					@list($patronId, $recordId, $holdId) = explode('|', $selected);
					$patronOwningHold = $user->getUserReferredTo($patronId);
					if ($patronOwningHold == false) {
						$tmpResult = array(
							'success' => false,
							'message' => 'Sorry, it looks like you don\'t have access to that patron.'
						);
					} else {
						foreach ($allUnavailableHolds as $key) {
							if($key->sourceId == $recordId) {
								$holdType = $key->source;
								$frozen = $key->frozen;
								$canFreeze = $key->canFreeze;
								break;
							}
						}
						if($frozen != 0 && $canFreeze == 1) {
							if ($holdType == 'ils') {
								$tmpResult = $user->thawHold($recordId, $holdId);
								if($tmpResult['success']){$success++;}else{$failed++;}
							} else if ($holdType == 'axis360') {
								require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
								$driver = new Axis360Driver();
								$tmpResult = $driver->thawHold($user, $recordId);
								if($tmpResult['success']){$success++;}else{$failed++;}
							} else if ($holdType == 'overdrive') {
								require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
								$driver = new OverDriveDriver();
								$tmpResult = $driver->thawHold($user, $recordId);
								if($tmpResult['success']){$success++;}else{$failed++;}
							} else if ($holdType == 'cloud_library') {
								require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
								$driver = new CloudLibraryDriver();
								$tmpResult = $driver->thawHold($user, $recordId);
								if($tmpResult['success']){$success++;}else{$failed++;}
							} else {
								$failed++;
							}
						}

						$message = '<div class="alert alert-success">' . $success . ' of ' . $total . ' holds were thawed.</div>';
						$tmpResult['message'] = $message;

					}
				}
			} else {
				$tmpResult['message'] = 'No holds were selected to thaw';
			}
		}

		return $tmpResult;
	}

	function thawHoldAll() {
		$user = UserAccount::getLoggedInUser();

		if (!$user) {
			$tmpResult['message'] = 'You must be logged in to modify a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$tmpResult = $user->thawAllHolds();

		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Modifying Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$tmpResult['message'] = 'No Patron was specified.';
		}

		return $tmpResult;
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

				$totalRecords = $list->numValidListItems();

				if (!empty($_REQUEST['sourceId']) && !is_array($_REQUEST['sourceId'])) {
					$sourceId = urldecode($_REQUEST['sourceId']);
					$source = urldecode($_REQUEST['source']);
					require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
					//Check to see if the user has already added the title to the list.
					$userListEntry = new UserListEntry();
					$userListEntry->listId = $list->id;
					$userListEntry->source = $source;
					$userListEntry->sourceId = $sourceId;
					$userListEntry->weight = $totalRecords++;
					if (!$userListEntry->find(true)) {
						$userListEntry->dateAdded = time();
						if($userListEntry->source == 'GroupedWork') {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
							$groupedWork = new GroupedWork();
							$groupedWork->permanent_id = $userListEntry->sourceId;
							if ($groupedWork->find(true)) {
								$userListEntry->title = substr($groupedWork->full_title, 0, 50);
							}
						}elseif($userListEntry->source == 'Lists') {
							require_once ROOT_DIR . '/sys/UserLists/UserList.php';
							$list = new UserList();
							$list->id  = $userListEntry->sourceId;
							if ($list->find(true)) {
								$userListEntry->title = substr($list->title, 0, 50);
							}
						}elseif($userListEntry->source == 'OpenArchives') {
							require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
							$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
							if ($recordDriver->isValid()){
								$title = $recordDriver->getTitle();
								$userListEntry->title = substr($title, 0, 50);
							}
						}elseif($userListEntry->source == 'Genealogy') {
							require_once ROOT_DIR . '/sys/Genealogy/Person.php';
							$person = new Person();
							$person->personId = $userListEntry->sourceId;
							if ($person->find(true)) {
								$userListEntry->title = substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
							}
						}elseif($userListEntry->source == 'EbscoEds') {
							require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
							$recordDriver = new EbscoRecordDriver($userListEntry->sourceId);
							if ($recordDriver->isValid()) {
								$title = $recordDriver->getTitle();
								$userListEntry->title = substr($title, 0, 50);
							}
						}
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
		if ($catalog != null) {
			$interface->assign('forgotPasswordType', $catalog->getForgotPasswordType());
			if (!$library->enableForgotPasswordLink) {
				$interface->assign('forgotPasswordType', 'none');
			}
		}else{
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
			'title' => translate(['text'=>'Masquerade As','isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("MyAccount/ajax-masqueradeAs.tpl"),
			'modalButtons' => '<button class="tool btn btn-primary" onclick="$(\'#masqueradeForm\').submit()">' . translate(['text'=>'Start','isPublicFacing'=>true]) . '</button>'
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
				'title' => translate(['text'=>'Change Hold Location','isPublicFacing'=>true]),
				'modalBody' => $interface->fetch("MyAccount/changeHoldLocation.tpl"),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="AspenDiscovery.Account.doChangeHoldLocation(); return false;">' . translate(['text'=>'Change Location','isPublicFacing'=>true]) . '</span>'
			);
		} else {
			$results = array(
				'title' => 'Please login',
				'modalBody' => translate(['text'=>"You must be logged in.  Please close this dialog and login before changing your hold's pick-up location.",'isPublicFacing'=>true]),
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
		$reactivateDateNotRequired = ($ils == 'Symphony' || $ils == 'Koha' || $ils == 'Polaris');
		$interface->assign('reactivateDateNotRequired', $reactivateDateNotRequired);

		$title = translate(translate(['text' => 'Freeze Hold', 'isPublicFacing'=>true])); // language customization
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
							'message' => translate(['text'=>'The selected pickup location is not valid.','isPublicFacing'=>true])
						);
					}
				}else{
					return array(
						'result' => false,
						'message' => translate(['text'=>'The logged in user does not have permission to change hold location for the specified user, please login as that user.','isPublicFacing'=>true])
					);
				}
			} else {
				return $results = array(
					'title' => translate(['text'=>'Please login','isPublicFacing'=>true]),
					'modalBody' => translate(['text'=>"You must be logged in.  Please close this dialog and login to change this hold's pick up location.",'isPublicFacing'=>true]),
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
			'message' => translate(['text'=>'We could not connect to the circulation system, please try again later.','isPublicFacing'=>true])
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
		$interface->assign('listId', $_REQUEST['listId']);
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormats', $citationFormats);
		$pageContent = $interface->fetch('MyAccount/getCitationFormatPopup.tpl');
		return array(
			'title' => translate(['text'=>'Select Citation Format','isPublicFacing'=>true]),
			'modalBody' => $pageContent,
			'modalButtons' => '<input class="btn btn-primary" onclick="AspenDiscovery.Lists.processCiteListForm(); return false;" value="' . translate(['text'=>'Generate Citations','isPublicFacing'=>true, 'inAttribute'=>true]) . '">'
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
			$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : '';
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
						$interface->assign('from', $from);
						$interface->assign('message', $message);
						$body = $interface->fetch('Emails/my-list.tpl');

						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = $list->title;
						$emailResult = $mail->send($to, $subject, $body);

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
			'title' => translate(['text' => 'Renew Item', 'isPublicFacing'=>true]),
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
			'title' => translate(['text' => 'Renew Selected Items', 'isPublicFacing'=>true]),
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
			'title' => translate(['text' => 'Renew All', 'isPublicFacing'=>true]),
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
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->hasIlsConnection()) {
				$ilsSummary = $user->getCatalogDriver()->getAccountSummary($user);
				$ilsSummary->setMaterialsRequests($user->getNumMaterialsRequests());
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $linkedUser->getCatalogDriver()->getAccountSummary($linkedUser);
						$ilsSummary->totalFines += $linkedUserSummary->totalFines;
						$ilsSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
						$ilsSummary->numOverdue += $linkedUserSummary->numOverdue;
						$ilsSummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
						$ilsSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
						$ilsSummary->setMaterialsRequests($ilsSummary->getMaterialsRequests() + $linkedUser->getNumMaterialsRequests());
					}
				}
				$timer->logTime("Loaded ILS Summary for User and linked users");

				$ilsSummary->setReadingHistory($user->getReadingHistorySize());

				//Expiration and fines
				$interface->assign('ilsSummary', $ilsSummary);
				$interface->setFinesRelatedTemplateVariables();
				if ($interface->getVariable('expiredMessage')) {
					$interface->assign('expiredMessage', str_replace('%date%', date('M j, Y', $ilsSummary->expirationDate), $interface->getVariable('expiredMessage')));
				}
				if ($interface->getVariable('expirationNearMessage')) {
					$interface->assign('expirationNearMessage', str_replace('%date%', date('M j, Y', $ilsSummary->expirationDate), $interface->getVariable('expirationNearMessage')));
				}
				$ilsSummary->setExpirationFinesNotice($interface->fetch('MyAccount/expirationFinesNotice.tpl'));

				$result = [
					'success' => true,
					'summary' => $ilsSummary->toArray()
				];
			} else {
				$result['message'] = translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]);
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
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
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
						$cloudLibrarySummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
						$cloudLibrarySummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
						$cloudLibrarySummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
					}
				}
				$timer->logTime("Loaded Cloud Library Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $cloudLibrarySummary->toArray()
				];
			} else {
				$result['message'] = translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]);
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
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
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
						$axis360Summary->numCheckedOut += $linkedUserSummary->numCheckedOut;
						$axis360Summary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
						$axis360Summary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
					}
				}
				$timer->logTime("Loaded Axis 360 Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $axis360Summary->toArray()
				];
			} else {
				$result['message'] = translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]);
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
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->isValidForEContentSource('hoopla')) {
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$hooplaSummary = $driver->getAccountSummary($user);

				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						if ($linkedUserSummary != false) {
							$hooplaSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
							$hooplaSummary->numCheckoutsRemaining += $linkedUserSummary->numCheckoutsRemaining;
						}
					}
				}
				$timer->logTime("Loaded Hoopla Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $hooplaSummary->toArray()
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
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
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
						$overDriveSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
						$overDriveSummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
						$overDriveSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
					}
				}
				$timer->logTime("Loaded OverDrive Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $overDriveSummary->toArray()
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
		/** @var Memcache $memCache */
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

		$hasLinkedUsers = count($user->getLinkedUsers()) > 0;

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
				$activeSheet->setCellValueByColumnAndRow($curCol++, $curRow, 'Wait List');
			}
			if ($hasLinkedUsers) {
				$activeSheet->setCellValueByColumnAndRow($curCol, $curRow, 'User');
			}

			$a = 4;
			//Loop Through The Report Data
			/** @var Checkout $row */
			foreach ($allCheckedOut as $row) {
				$titleCell = preg_replace("~([/:])$~", "", $row->title);
				if (!empty($row->title2)) {
					$titleCell .= preg_replace("~([/:])$~", "", $row->title2);
				}

				if (isset ($row->author)) {
					if (is_array($row->author)) {
						$authorCell = implode(', ', $row->author);
					} else {
						$authorCell = $row->author;
					}
					$authorCell = str_replace('&nbsp;', ' ', $authorCell);
				} else {
					$authorCell = '';
				}
				if (isset($row->format)) {
					if (is_array($row->format)) {
						$formatString = implode(', ', $row->format);
					} else {
						$formatString = $row->format;
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
					$activeSheet->setCellValueByColumnAndRow($curCol++, $a, date('M d, Y', $row->checkoutDate));
				}
				if (isset($row->dueDate)) {
					$activeSheet->setCellValueByColumnAndRow($curCol++, $a, date('M d, Y', $row->dueDate));
				} else {
					$activeSheet->setCellValueByColumnAndRow($curCol++, $a, '');
				}

				if ($showRenewed) {
					if (isset($row->dueDate)) {
						$activeSheet->setCellValueByColumnAndRow($curCol++, $a, isset($row->renewCount) ? $row->renewCount : '');
					} else {
						$activeSheet->setCellValueByColumnAndRow($curCol++, $a, '');
					}
				}
				if ($showWaitList) {
					$activeSheet->setCellValueByColumnAndRow($curCol++, $a, $row->holdQueueLength);
				}
				if ($hasLinkedUsers) {
					$activeSheet->setCellValueByColumnAndRow($curCol, $a, $row->getUserName());
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
			$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
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

		$showDateWhenSuspending = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony' || $ils == 'Koha');

		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("Aspen Discovery")
			->setLastModifiedBy("Aspen Discovery")
			->setTitle("Library Holds for " . $user->displayName)
			->setCategory("Holds");

		$hasLinkedUsers = count($user->getLinkedUsers()) > 0;
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
				$statusPosition = null;
				$expiresPosition = null;
				$userPosition = null;
				if ($exportType == "available") {
					// Add some data
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $curRow, 'Holds - ' . ucfirst($exportType));
					$curRow += 2;

					$objPHPExcel->getActiveSheet()->setCellValue('A' . $curRow, translate(['text' => 'Title', 'isPublicFacing'=>true]))
						->setCellValue('B' . $curRow, translate(['text' => 'Author', 'isPublicFacing'=>true]))
						->setCellValue('C' . $curRow, translate(['text' => 'Format', 'isPublicFacing'=>true]))
						->setCellValue('D' . $curRow, translate(['text' => 'Placed', 'isPublicFacing'=>true]))
						->setCellValue('E' . $curRow, translate(['text' => 'Pickup', 'isPublicFacing'=>true]))
						->setCellValue('F' . $curRow, translate(['text' => 'Available', 'isPublicFacing'=>true]))
						->setCellValue('G' . $curRow, translate(['text' => 'Pickup By', 'isPublicFacing'=>true]));
					if ($hasLinkedUsers){
						$userPosition = 'H';
						$objPHPExcel->getActiveSheet()->setCellValue('H' . $curRow, translate(['text' => 'User', 'isPublicFacing'=>true]));
					}
				} else {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $curRow, translate(['text' => 'Holds - ' . ucfirst($exportType), 'isPublicFacing'=>true]));
					$curRow += 2;
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $curRow, translate(['text' => 'Title', 'isPublicFacing'=>true]))
						->setCellValue('B' . $curRow, translate(['text' => 'Author', 'isPublicFacing'=>true]))
						->setCellValue('C' . $curRow, translate(['text' => 'Format', 'isPublicFacing'=>true]))
						->setCellValue('D' . $curRow, translate(['text' => 'Placed', 'isPublicFacing'=>true]))
						->setCellValue('E' . $curRow, translate(['text' => 'Pickup', 'isPublicFacing'=>true]));

					if ($showPosition) {
						$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, translate(['text' => 'Position', 'isPublicFacing'=>true]));
						$statusPosition = 'G';
						if ($showExpireTime) {
							$expiresPosition = 'H';
							if ($hasLinkedUsers){
								$userPosition = 'I';
							}
						}else{
							if ($hasLinkedUsers){
								$userPosition = 'H';
							}
						}

					} else {
						$statusPosition = 'F';
						if ($showExpireTime) {
							$expiresPosition = 'G';
							if ($hasLinkedUsers){
								$userPosition = 'H';
							}
						}else{
							if ($hasLinkedUsers){
								$userPosition = 'G';
							}
						}
					}
					$objPHPExcel->getActiveSheet()->setCellValue($statusPosition . $curRow, translate(['text' => 'Status', 'isPublicFacing'=>true]));
					if ($expiresPosition != null) {
						$objPHPExcel->getActiveSheet()->setCellValue($expiresPosition . $curRow, translate(['text' => 'Expires', 'isPublicFacing'=>true]));
					}
					if ($userPosition != null){
						$objPHPExcel->getActiveSheet()->setCellValue($userPosition . $curRow, translate(['text' => 'User', 'isPublicFacing'=>true]));
					}
				}

				$curRow++;
				//Loop Through The Report Data
				/** @var Hold $row */
				foreach ($allHolds[$exportType] as $row) {
					$titleCell = preg_replace("~([/:])$~", "", $row->title);
					if (isset ($row->title2)) {
						$titleCell .= preg_replace("~([/:])$~", "", $row->title2);
					}

					if (isset ($row->author)) {
						if (is_array($row->author)) {
							$authorCell = implode(', ', $row->author);
						} else {
							$authorCell = $row->author;
						}
						$authorCell = str_replace('&nbsp;', ' ', $authorCell);
					} else {
						$authorCell = '';
					}
					if (isset($row->format)) {
						if (is_array($row->format)) {
							$formatString = implode(', ', $row->format);
						} else {
							$formatString = $row->format;
						}
					} else {
						$formatString = '';
					}

					if (empty($row->createDate)) {
						$placedDate = '';
					} else {
						if (is_array($row->createDate)) {
							$placedDate = new DateTime();
							$placedDate->setDate($row->createDate['year'], $row->createDate['month'], $row->createDate['day']);
							$placedDate = $placedDate->format('M d, Y');
						} else {
							$placedDate = $this->isValidTimeStamp($row->createDate) ? $row->createDate : strtotime($row->createDate);
							$placedDate = date('M d, Y', $placedDate);
						}
					}

					if (isset($row->pickupLocationName)) {
						$locationString = $row->pickupLocationName;
					} else {
						$locationString = '';
					}

					if (empty($row->expirationDate)) {
						$expireDate = '';
					} else {
						if (is_array($row->expirationDate)) {
							$expireDate = new DateTime();
							$expireDate->setDate($row->expirationDate['year'], $row->expirationDate['month'], $row->expirationDate['day']);
							$expireDate = $expireDate->format('M d, Y');
						} else {
							$expireDate = $this->isValidTimeStamp($row->expirationDate) ? $row->expirationDate : strtotime($row->expirationDate);
							$expireDate = date('M d, Y', $expireDate);
						}
					}

					if ($exportType == "available") {
						if (empty($row->availableDate)) {
							$availableDate = 'Now';
						} else {
							$availableDate = $this->isValidTimeStamp($row->availableDate) ? $row->availableDate : strtotime($row->availableDate);
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
						if ($userPosition != null){
							$objPHPExcel->getActiveSheet()->setCellValue($userPosition . $curRow, $row->getUserName());
						}
					} else {
						if (isset($row->status)) {
							$statusCell = $row->status;
						} else {
							$statusCell = '';
						}

						if (isset($row->frozen) && $row->frozen && $showDateWhenSuspending && !empty($row->reactivateDate)) {
							$reactivateTime = $this->isValidTimeStamp($row->reactivateDate) ? $row->reactivateDate : strtotime($row->reactivateDate);
							$statusCell .= " until " . date('M d, Y', $reactivateTime);
						}
						$objPHPExcel->getActiveSheet()
							->setCellValue('A' . $curRow, $titleCell)
							->setCellValue('B' . $curRow, $authorCell)
							->setCellValue('C' . $curRow, $formatString)
							->setCellValue('D' . $curRow, $placedDate);
						if (isset($row->pickupLocationName)) {
							$objPHPExcel->getActiveSheet()->setCellValue('E' . $curRow, $row->pickupLocationName);
						} else {
							$objPHPExcel->getActiveSheet()->setCellValue('E' . $curRow, '');
						}

						if ($statusPosition !== null){
							$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, $statusCell);
						}
						if ($showPosition) {
							if (isset($row->position)) {
								$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, $row->position);
							} else {
								$objPHPExcel->getActiveSheet()->setCellValue('F' . $curRow, '');
							}

							$objPHPExcel->getActiveSheet()->setCellValue('G' . $curRow, $statusCell);

						} else {

							if ($showExpireTime) {
								$objPHPExcel->getActiveSheet()->setCellValue('G' . $curRow, $expireDate);
							}
						}
						if ($expiresPosition) {
							$objPHPExcel->getActiveSheet()->setCellValue($expiresPosition . $curRow, $expireDate);
						}
						if ($userPosition != null){
							$objPHPExcel->getActiveSheet()->setCellValue($userPosition . $curRow, $row->getUserName());
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
			$objPHPExcel->getActiveSheet()->setTitle(translate(['text' => 'Holds', 'isPublicFacing'=>true]));

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
						$lastCheckout = translate(['text' => 'In Use', 'isPublicFacing'=>true]);
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

		$renewableCheckouts = 0;

		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
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
			$sortOptions = array(
				'title' => 'Title',
				'author' => 'Author',
				'dueDate' => 'Due Date Asc',
				'dueDateDesc' => 'Due Date Desc',
				'format' => 'Format',
			);
			$user = UserAccount::getActiveUserObj();
			if (UserAccount::isLoggedIn() == false || empty($user)){
				$result['message'] = translate(['text' => "Your login has timed out. Please login again.", 'isPublicFacing'=> true]);
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

				foreach ($allCheckedOut as $checkout) {
					if ($checkout->canRenew == 1) {
						$renewableCheckouts++;
					}
				}

				$interface->assign('renewableCheckouts', $renewableCheckouts);
				$selectedSortOption = $this->setSort('sort', 'checkout');
				if ($selectedSortOption == null || !array_key_exists($selectedSortOption, $sortOptions)) {
					$selectedSortOption = 'dueDate';
				}
				$interface->assign('defaultSortOption', $selectedSortOption);
				$allCheckedOut = $this->sortCheckouts($selectedSortOption, $allCheckedOut);

				$interface->assign('transList', $allCheckedOut);

				$result['success'] = true;
				$result['message'] = "";
				$result['checkoutInfoLastLoaded'] = $user->getFormattedCheckoutInfoLastLoaded();
				$result['checkouts'] = $interface->fetch('MyAccount/checkoutsList.tpl');
			}
		} else {
			$result['message'] = translate(['text' => 'The catalog is offline', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	public function getHolds()
	{
		global $interface;

		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
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
				$result['message'] = translate(['text' => "Your login has timed out. Please login again.", 'isPublicFacing'=> true]);
			}else {
				$allowFreezeHolds = $user->getHomeLibrary()->allowFreezeHolds;
				if($allowFreezeHolds) {
					$interface->assign('allowFreezeAllHolds', true);
				} else {
					$interface->assign('allowFreezeAllHolds', false);
				}

				$interface->assign('allowFreezeHolds', true);

				$ils = $configArray['Catalog']['ils'];
				$showPosition = ($ils == 'Horizon' || $ils == 'Koha' || $ils == 'Symphony' || $ils == 'CarlX' || 'Polaris');
				$suspendRequiresReactivationDate = ($ils == 'Horizon' || $ils == 'CarlX' || $ils == 'Symphony' || $ils == 'Koha');
				$interface->assign('suspendRequiresReactivationDate', $suspendRequiresReactivationDate);
				$showPlacedColumn = ($ils == 'Symphony');
				$interface->assign('showPlacedColumn', $showPlacedColumn);

				$location = new Location();
				$pickupBranches = $location->getPickupBranches($user);
				$interface->assign('numPickupBranches', count($pickupBranches));

				// Define sorting options
				$unavailableHoldSortOptions = array(
					'title' => 'Title',
					'author' => 'Author',
					'format' => 'Format',
				);
				$unavailableHoldSortOptions['status'] = 'Status';
				if ($source == 'all' || $source == 'ils') {
					$unavailableHoldSortOptions['location'] = 'Pickup Location';
				}
				if ($showPosition) {
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
				$interface->assign('userId', $user->id);

				$result['success'] = true;
				$result['message'] = "";
				$result['holdInfoLastLoaded'] = $user->getFormattedHoldInfoLastLoaded();
				$result['holds'] = $interface->fetch('MyAccount/holdsList.tpl');
			}
		} else {
			$result['message'] = translate(['text' => 'The catalog is offline', 'isPublicFacing'=>true]);
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
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
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
	 * @param Checkout[] $allCheckedOut
	 * @return array
	 */
	private function sortCheckouts(string $selectedSortOption, array $allCheckedOut): array
	{
		//Do sorting now that we have all records
		$curTransaction = 0;
		foreach ($allCheckedOut as $i => $curTitle) {
			$curTransaction++;
			$sortTitle = !empty($curTitle->getSortTitle()) ? $curTitle->getSortTitle() : (empty($curTitle->getTitle()) ? $this::SORT_LAST_ALPHA : $curTitle->getTitle());
			$sortKey = $sortTitle;
			if ($selectedSortOption == 'title') {
				$sortKey = $sortTitle;
			} elseif ($selectedSortOption == 'author') {
				$sortKey = (empty($curTitle->getAuthor()) ? $this::SORT_LAST_ALPHA : $curTitle->getAuthor()) . '-' . $sortTitle;
			} elseif ($selectedSortOption == 'dueDate' || $selectedSortOption == 'dueDateDesc') {
				if (isset($curTitle->dueDate)) {
					$sortKey = $curTitle->dueDate . '-' . $sortTitle;
				}else{
					//Always put things where the due date isn't set last.
					if ($selectedSortOption == 'dueDate'){
						$sortKey = '9999999999-' . $sortTitle;
					}else{
						$sortKey = '0000000000-' . $sortTitle;
					}
				}
			} elseif ($selectedSortOption == 'format') {
				$sortKey = ((empty($curTitle->getPrimaryFormat()) || strcasecmp($curTitle->getPrimaryFormat(), 'unknown') == 0) ? $this::SORT_LAST_ALPHA : $curTitle->getPrimaryFormat()) . '-' . $sortTitle;
			} elseif ($selectedSortOption == 'renewed') {
				if (isset($curTitle->renewCount) && is_numeric($curTitle->renewCount)) {
					$sortKey = str_pad($curTitle->renewCount, 3, '0', STR_PAD_LEFT) . '-' . $sortTitle;
				} else {
					$sortKey = '***' . '-' . $sortTitle;
				}
			} elseif ($selectedSortOption == 'libraryAccount') {
				$sortKey = $curTitle->getUserName() . '-' . $sortTitle;
			}
			$sortKey = strtolower($sortKey);
			$sortKey = utf8_encode($sortKey . '-' . $curTransaction);

			$allCheckedOut[$sortKey] = $curTitle;
			unset($allCheckedOut[$i]);
		}

		//Now that we have all the transactions we can sort them
		if ($selectedSortOption == 'renewed' || $selectedSortOption == 'holdQueueLength' || $selectedSortOption == 'dueDateDesc') {
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
			'title' => translate(['text' => 'Error', 'isPublicFacing'=>true]),
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
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
			'title' => translate(['text' => 'Error', 'isPublicFacing'=>true]),
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
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
		$user = UserAccount::getLoggedInUser();
		if ($user == null) {
			return ['success' => false, 'message' => translate(['text' => 'You must be signed in to pay fines, please sign in.', 'isPublicFacing'=> true])];
		} else {
			$patronId = $_REQUEST['patronId'];

			$patron = $user->getUserReferredTo($patronId);

			if ($patron == false) {
				return ['success' => false, 'message' => translate(['text' => 'Could not find the patron referred to, please try again.', 'isPublicFacing'=> true])];
			}
			$userLibrary = $patron->getHomeLibrary();

			if (empty($_REQUEST['selectedFine']) && $userLibrary->finesToPay != 0) {
				return ['success' => false, 'message' => translate(['text' => 'Select at least one fine to pay.', 'isPublicFacing'=> true])];
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
							return ['success' => false, 'message' => translate(['text' => 'Invalid amount entered for fine. Please enter an amount over 0 and less than the total amount owed.', 'isPublicFacing'=> true])];
						}
						$finesPaid .= '|' . $fineAmount;
						if ($fineAmount != $maxFineAmount) {
							//Record this is a partially paid fine
							$finePayment = 1;
						}

					} else {
						$fineAmount = $useOutstanding ? $fine['amountOutstandingVal'] : $fine['amountVal'];
						$finesPaid .= '|' . $fineAmount;
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
							return ['success' => false, 'message' => translate(['text' => 'You must pay all fines of type <strong>%1%</strong> before paying other types.', 1 => $lastPaymentType, 'isPublicFacing'=> true])];
						}
					}
				}
			}

			if ($totalFines < $userLibrary->minimumFineAmount) {
				return ['success' => false, 'message' => translate(['text' => 'You must select at least %1% in fines to pay.', 1 => sprintf('$%01.2f', $userLibrary->minimumFineAmount), 'isPublicFacing'=> true])];
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
				return ['success' => false, 'message' => translate(['text' => 'You must select at least %1% in fines to pay.', 1 => sprintf('$%01.2f', $userLibrary->minimumFineAmount), 'isPublicFacing'=> true])];
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

			return [$userLibrary, $payment, $purchaseUnits, $patron];
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
		$result = $this->createGenericOrder('msb');
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($userLibrary, $payment, $purchaseUnits) = $result;
			$paymentRequestUrl = $userLibrary->msbUrl;
			$paymentRequestUrl .= "?ReferenceID=" . $payment->id;
			$paymentRequestUrl .= "&PaymentType=CC";
			$paymentRequestUrl .= "&TotalAmount=" . $payment->totalPaid;
			$paymentRequestUrl .= "&PaymentRedirectUrl=" . $configArray['Site']['url'] . '/MyAccount/Fines/' . $payment->id;
			return ['success' => true, 'message' => 'Redirecting to payment processor', 'paymentRequestUrl' => $paymentRequestUrl];
		}
	}

	/** @noinspection PhpUnused */
	function createCompriseOrder() {
		global $configArray;
		$result = $this->createGenericOrder('comprise');
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)){
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );
			$currencyFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '');

			/** @var Library $userLibrary */
			/** @var UserPayment $payment */
			/** @var User $patron */
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($userLibrary, $payment, $purchaseUnits, $patron) = $result;
			require_once ROOT_DIR . '/sys/ECommerce/CompriseSetting.php';
			$compriseSettings = new CompriseSetting();
			$compriseSettings->id = $userLibrary->compriseSettingId;
			if ($compriseSettings->find(true)) {
				$paymentRequestUrl = 'https://smartpayapi2.comprisesmartterminal.com/smartpayapi/websmartpay.dll?GetCreditForm';
				$paymentRequestUrl .= "&LocationID=" . $compriseSettings->username;
				$paymentRequestUrl .= "&CustomerID=" . $compriseSettings->customerId;
				$paymentRequestUrl .= "&PatronID=" . $patron->getBarcode();
				$paymentRequestUrl .= '&UserName=' . urlencode($compriseSettings->username);
				$paymentRequestUrl .= '&Password=' . $compriseSettings->password;
				$paymentRequestUrl .= '&Amount=' . $currencyFormatter->format($payment->totalPaid);
				$paymentRequestUrl .= "&URLPostBack=" . urlencode($configArray['Site']['url'] . '/Comprise/Complete');
				$paymentRequestUrl .= "&URLReturn=" . urlencode($configArray['Site']['url'] . '/MyAccount/CompriseCompleted?payment=' . $payment->id);
				$paymentRequestUrl .= "&URLCancel=" . urlencode($configArray['Site']['url'] . '/MyAccount/CompriseCancel?payment=' . $payment->id);
				$paymentRequestUrl .= '&INVNUM=' . $payment->id;
				$paymentRequestUrl .= '&Field1=';
				$paymentRequestUrl .= '&Field2=';
				$paymentRequestUrl .= '&Field3=';
				$paymentRequestUrl .= '&ItemsData=';

				return ['success' => true, 'message' => 'Redirecting to payment processor', 'paymentRequestUrl' => $paymentRequestUrl];
			}else{
				return ['success' => false, 'message' => 'Comprise was not properly configured'];
			}
		}
	}

	/** @noinspection PhpUnused */
	function createProPayOrder() {
		global $configArray;
		$result = $this->createGenericOrder('propay');
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)){
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );
			$currencyFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '');

			/** @var Library $userLibrary */
			/** @var UserPayment $payment */
			/** @var User $patron */
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($userLibrary, $payment, $purchaseUnits, $patron) = $result;
			require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';
			$proPaySetting = new ProPaySetting();
			$proPaySetting->id = $userLibrary->proPaySettingId;
			if ($proPaySetting->find(true)) {
				$curlWrapper = new CurlWrapper();
				$authorization = $proPaySetting->billerAccountId . ':' . $proPaySetting->authenticationToken;
				$authorization = 'Basic ' . base64_encode($authorization);
				$curlWrapper->addCustomHeaders([
					'User-Agent: Aspen Discovery',
					'Accept: application/json',
					'Cache-Control: no-cache',
					'Content-Type: application/json',
					'Accept-Encoding: gzip, deflate',
					'Authorization: ' . $authorization
				], true);

				//Create the payer if one doesn't exist already.
				if (empty($patron->proPayPayerAccountId)){
					$createPayer = new stdClass();
					$createPayer->EmailAddress = $patron->email;
					$createPayer->ExternalId = $patron->id;
					$createPayer->Name = $patron->_fullname;

					//Issue PUT request to
					if ($proPaySetting->useTestSystem) {
						$url = 'https://xmltestapi.propay.com/protectpay/Payers/';
					}else{
						$url = 'https://api.propay.com/protectpay/Payers/';
					}

					$createPayerResponse = $curlWrapper->curlSendPage($url, 'PUT', json_encode($createPayer));
					if ($createPayerResponse && $curlWrapper->getResponseCode() == 200){
						$jsonResponse = json_decode($createPayerResponse);
						$patron->proPayPayerAccountId = $jsonResponse->ExternalAccountID;
						$patron->update();
					}
				}

				if (empty($proPaySetting->merchantProfileId) || $proPaySetting->merchantProfileId == 0){
					//Create a merchant profile id
					$createMerchantProfile = new stdClass();
					$createMerchantProfile->ProfileName = $proPaySetting->name;
					$createMerchantProfile->PaymentProcessor = 'LegacyProPay';
					$createMerchantProfile->ProcessorData = [];
					$certStrField = new stdClass();
					$certStrField->ProcessorField = 'certStr';
					$certStrField->Value = $proPaySetting->certStr;
					$createMerchantProfile->ProcessorData[] = $certStrField;
					$accountNumField = new stdClass();
					$accountNumField->ProcessorField = 'accountNum';
					$accountNumField->Value = $proPaySetting->accountNum;
					$createMerchantProfile->ProcessorData[] = $accountNumField;
					$termIdField = new stdClass();
					$termIdField->ProcessorField = 'termId';
					$termIdField->Value = $proPaySetting->termId;
					$createMerchantProfile->ProcessorData[] = $termIdField;

					//Issue PUT request to
					if ($proPaySetting->useTestSystem) {
						$url = 'https://xmltestapi.propay.com/protectpay/MerchantProfiles/';
					}else{
						$url = 'https://api.propay.com/protectpay/MerchantProfiles/';
					}

					$createMerchantProfileResponse = $curlWrapper->curlSendPage($url, 'PUT', json_encode($createMerchantProfile));
					if ($createMerchantProfileResponse && $curlWrapper->getResponseCode() == 200){
						$jsonResponse = json_decode($createMerchantProfileResponse);
						$proPaySetting->merchantProfileId = $jsonResponse->ProfileId;
						$proPaySetting->update();
					}
				}

				if (!empty($patron->proPayPayerAccountId)) {
					//Create the Hosted Transaction Instance
					$requestElements = new stdClass();
					$requestElements->Amount = (int)($payment->totalPaid * 100);
					$requestElements->AuthOnly = false;
					$requestElements->AvsRequirementType = 2;
					$requestElements->BillerAccountId = $proPaySetting->billerAccountId;
					$requestElements->CardHolderNameRequirementType = 1;
					$requestElements->CssUrl = $configArray['Site']['url'] . '/interface/themes/responsive/css/main.css';
					$requestElements->CurrencyCode = $currencyCode;
					$requestElements->InvoiceNumber = (string)$payment->id;
					$requestElements->MerchantProfileId = (int)$proPaySetting->merchantProfileId;
					$requestElements->PaymentTypeId = "0";
					$requestElements->PayerAccountId = (int)$patron->proPayPayerAccountId;
					$requestElements->ProcessCard = true;
					$requestElements->ReturnURL = $configArray['Site']['url'] . "/ProPay/{$payment->id}/Complete";
					$requestElements->SecurityCodeRequirementType = 1;
					$requestElements->StoreCard = false;
					$patron->loadContactInformation();
					$requestElements->Address1 = $patron->_address1;
					$requestElements->Address2 = $patron->_address2;
					$requestElements->City = $patron->_city;
					$requestElements->Name = $patron->_fullname;
					$requestElements->State = $patron->_state;
					$requestElements->ZipCode = $patron->_zip;

					//Issue PUT request to
					if ($proPaySetting->useTestSystem) {
						$url = 'https://xmltestapi.propay.com/protectpay/HostedTransactions/';
					} else {
						$url = 'https://api.propay.com/protectpay/HostedTransactions/';
					}

					$response = $curlWrapper->curlSendPage($url, 'PUT', json_encode($requestElements));
					if ($response && $curlWrapper->getResponseCode() == 200) {
						$jsonResponse = json_decode($response);
						$transactionIdentifier = $jsonResponse->HostedTransactionIdentifier;

						$payment->orderId = $transactionIdentifier;
						$payment->update();

						if ($proPaySetting->useTestSystem) {
							$paymentRequestUrl = 'https://protectpaytest.propay.com/hpp/v2/' . $transactionIdentifier;
						} else {
							$paymentRequestUrl = 'https://protectpay.propay.com/hpp/v2/' . $transactionIdentifier;
						}

						return ['success' => true, 'message' => 'Redirecting to payment processor', 'paymentRequestUrl' => $paymentRequestUrl];
					} else {
						return ['success' => false, 'message' => 'Could not connect to the payment processor'];
					}
				}else{
					return ['success' => false, 'message' => 'Payer Account ID could not be determined.'];
				}

			}else{
				return ['success' => false, 'message' => 'ProPay was not properly configured'];
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissPlacard(){
		$patronId = $_REQUEST['patronId'];
		$placardId = $_REQUEST['placardId'];

		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]),
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
			'title' => translate(['text'=>'Add To List','isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("MyAccount/saveToList.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.saveToList(); return false;'>" . translate(['text'=>"Save To List",'isPublicFacing'=>true]) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function saveToList(){
		$result = array();

		if (!UserAccount::isLoggedIn()) {
			$result['success'] = false;
			$result['message'] = translate(['text'=>'Please login before adding a title to list.','isPublicFacing'=>true]);
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
				$userList->title = translate(['text'=>"My Favorites",'isPublicFacing'=>true]);
				$userList->user_id = UserAccount::getActiveUserId();
				$userList->public = 0;
				$userList->description = '';
				$userList->insert();
				$totalRecords = 0;
			}else{
				$userList->id = $listId;
				$totalRecords = $userList->numValidListItems();
				if (!$userList->find(true)){
					$result['success'] = false;
					$result['message'] = translate(['text'=>'Sorry, we could not find that list in the system.','isPublicFacing'=>true]);
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
					$result['message'] = translate(['text'=>'Sorry, that is not a valid entry for the list.','isPublicFacing'=>true]);
				}else {
					if (empty($sourceId) || empty($source)){
						$result['success'] = false;
						$result['message'] = translate(['text'=>'Unable to add that to a list, not correctly specified.','isPublicFacing'=>true]);
					}else {
						$userListEntry->source = $source;
						$userListEntry->sourceId = $sourceId;
						$userListEntry->weight = $totalRecords +1;

						if($userListEntry->source == 'GroupedWork') {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
							$groupedWork = new GroupedWork();
							$groupedWork->permanent_id = $userListEntry->sourceId;
								if ($groupedWork->find(true)) {
									$userListEntry->title = substr($groupedWork->full_title, 0, 50);
								}
						}elseif($userListEntry->source == 'Lists') {
							require_once ROOT_DIR . '/sys/UserLists/UserList.php';
							$list = new UserList();
							$list->id  = $userListEntry->sourceId;
								if ($list->find(true)) {
									$userListEntry->title = substr($list->title, 0, 50);
								}
						}elseif($userListEntry->source == 'OpenArchives') {
							require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
							$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()){
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
						}elseif($userListEntry->source == 'Genealogy') {
							require_once ROOT_DIR . '/sys/Genealogy/Person.php';
							$person = new Person();
							$person->personId = $userListEntry->sourceId;
								if ($person->find(true)) {
									$userListEntry->title = substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
								}
						}elseif($userListEntry->source == 'EbscoEds') {
							require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
							$recordDriver = new EbscoRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
						}

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
						$result['message'] = translate(['text'=>'This title was saved to your list successfully.','isPublicFacing'=>true]);
					}
				}
			}

		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function reloadCover(){
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listId = htmlspecialchars($_GET["id"]);
		$listEntry = new UserListEntry();
		$listEntry->listId = $listId;

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->recordType = 'list';
		$bookCoverInfo->recordId = $listEntry->listId;
		if ($bookCoverInfo->find(true)){
			$bookCoverInfo->imageSource = '';
			$bookCoverInfo->thumbnailLoaded = 0;
			$bookCoverInfo->mediumLoaded = 0;
			$bookCoverInfo->largeLoaded = 0;
			$bookCoverInfo->update();
		}

		return array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.');
	}

	/** @noinspection PhpUnused */
	function getUploadListCoverForm(){
		global $interface;

		$id = htmlspecialchars($_GET["id"]);
		$interface->assign('id', $id);

		return array(
			'title' => translate(['text' => 'Upload a New List Cover', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Lists/upload-cover-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadListCoverForm\").submit()'>" . translate(['text' => "Upload Cover", 'isPublicFacing'=>true]) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function uploadListCover(){
		$result = [
			'success' => false,
			'title' => 'Uploading custom list cover',
			'message' => 'Sorry your cover could not be uploaded'
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload List Covers'))){
			if (isset($_FILES['coverFile'])) {
				$uploadedFile = $_FILES['coverFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No Cover file was uploaded";
				} else if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] =  "Error in file upload for cover " . $uploadedFile["error"];
				} else {
					$id = htmlspecialchars($_GET["id"]);
					global $configArray;
					$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
					$fileType = $uploadedFile["type"];
					if ($fileType == 'image/png'){
						if (copy($uploadedFile["tmp_name"], $destFullPath)){
							$result['success'] = true;
						}
					}elseif ($fileType == 'image/gif'){
						$imageResource = @imagecreatefromgif($uploadedFile["tmp_name"]);
						if (!$imageResource){
							$result['message'] = 'Unable to process this image, please try processing in an image editor and reloading';
						}else if (@imagepng( $imageResource, $destFullPath, 9)){
							$result['success'] = true;
						}
					}elseif ($fileType == 'image/jpg' || $fileType == 'image/jpeg'){
						$imageResource = @imagecreatefromjpeg($uploadedFile["tmp_name"]);
						if (!$imageResource){
							$result['message'] = 'Unable to process this image, please try processing in an image editor and reloading';
						}else if (@imagepng( $imageResource, $destFullPath, 9)){
							$result['success'] = true;
						}
					}else{
						$result['message'] = 'Incorrect image type.  Please upload a PNG, GIF, or JPEG';
					}
				}
			} else {
				$result['message'] = 'No cover was uploaded, please try again.';
			}
		}
		if ($result['success']){
			$this->reloadCover();
			$result['message'] = 'Your cover has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadListCoverFormByURL(){
		global $interface;

		$id = htmlspecialchars($_GET["id"]);
		$interface->assign('id', $id);

		return array(
			'title' => translate(['text' => 'Upload a New List Cover by URL', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Lists/upload-cover-form-url.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadListCoverFormByURL\").submit()'>" . translate(['text' => "Upload Cover", 'isPublicFacing'=>true]) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function uploadListCoverByURL(){
		$result = [
			'success' => false,
			'title' => 'Uploading custom list cover',
			'message' => 'Sorry your cover could not be uploaded'
		];
		if (isset($_POST['coverFileURL'])) {
			$url = $_POST['coverFileURL'];
			$filename = basename($url);
			$uploadedFile = file_get_contents($url);

			if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
				$result['message'] = "No Cover file was uploaded";
			} else if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
				$result['message'] = "Error in file upload for cover " . $uploadedFile["error"];
			}

			$id = htmlspecialchars($_GET["id"]);
			global $configArray;
			$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if($ext == "jpg" or $ext == "png" or $ext == "gif" or $ext == "jpeg") {
				$upload = file_put_contents($destFullPath, file_get_contents($url));
				if ($upload) {
					$result['success'] = true;
				} else {
					$result['message'] = 'Incorrect image type.  Please upload a PNG, GIF, or JPEG';
				}
			}
		}else{
			$result['message'] = 'No cover was uploaded, please try again.';
		}
		if ($result['success']){
			$this->reloadCover();
			$result['message'] = 'Your cover has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteListItems(){
		$result = [
			'success' => false,
			'message' => 'Something went wrong.'
		];

		$listId = htmlspecialchars($_GET["id"]);
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$list = new UserList();
		$list->id = $listId;
		if($list->find(true)){
			//Perform an action on the list, but verify that the user has permission to do so.
			$userCanEdit = false;
			$userObj = UserAccount::getActiveUserObj();
			if ($userObj != false){
				$userCanEdit = $userObj->canEditList($list);
			}
		} else{
			$result['message'] = "Sorry, that list wasn't found.";
		}

		if ($userCanEdit){
			if (isset($_REQUEST['selected'])){
				$itemsToRemove = $_REQUEST['selected'];
				foreach ($itemsToRemove as $listEntryId => $selected){
					$list->removeListEntry($listEntryId);
				}
				$this->reloadCover();
				$result['success'] = true;
				$result['message'] = 'Selected items removed from the list successfully';
			}else {
				$list->find(true);
				$list->removeAllListEntries();
				$this->reloadCover();
				$result['success'] = true;
				$result['message'] = 'All items removed from the list successfully';
			}
			$list->update();
			$this->reloadCover();
			$result['success'] = true;
			$result['message'] = 'Items removed from the list successfully';
		}else{
			$result['message'] = "Sorry, you don't have permissions to edit this list.";
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteList(){
		$result = [
			'success' => false,
			'message' => 'Something went wrong.'
		];

		//$listId = htmlspecialchars($_GET["id"]);
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

			if (isset($_REQUEST['selected'])){
					$itemsToRemove = $_REQUEST['selected'];
					foreach ($itemsToRemove as $listId => $selected) {
						$list = new UserList();
						$list->id = $listId;

						//Perform an action on the list, but verify that the user has permission to do so.
						$userCanEdit = false;
						$userObj = UserAccount::getActiveUserObj();
						if ($userObj != false){
							$userCanEdit = $userObj->canEditList($list);
						}
						if ($userCanEdit) {
							$list->find();
							$list->delete();
							$result['success'] = true;
							$result['message'] = 'Selected lists deleted successfully';
						} else {
							$result['success'] = false;
						}
					}
			}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEditListForm()
	{
		global $interface;

		if (isset($_REQUEST['listId']) && isset($_REQUEST['listEntryId'])) {
			$listId = $_REQUEST['listId'];
			$listEntry =  $_REQUEST['listEntryId'];

			$interface->assign('listId', $listId);
			$interface->assign('listEntry', $listEntry);

			if (is_array($listId)){
				$listId = array_pop($listId);
			}
			if (!empty($listId) && is_numeric($listId)) {
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$userList     = new UserList();
				$userList->id = $listId;

				$userLists    = new UserList();
				$userLists->user_id = UserAccount::getActiveUserId();
				$userLists->whereAdd('deleted = 0');
				$userLists->orderBy('title');
				$userLists->find();
				$lists = [];
				while ($userLists->fetch()){
					$lists[] = clone $userLists;
				}

				$interface->assign('lists', $lists);

				if ($userList->find(true)) {
					$userObj = UserAccount::getActiveUserObj();
					if ($userObj){
						$this->listId = $userList->id;
						$this->listTitle = $userList->title;
						$userCanEdit = $userObj->canEditList($userList);
						if ($userCanEdit){
							if (isset($_POST['submit'])) {
								$this->saveChanges();

								// After changes are saved, send the user back to an appropriate page;
								// either the list they were viewing when they started editing, or the
								// overall favorites list.
								if (isset($listId)) {
									$nextAction = 'MyList/' . $listId;
								} else {
									$nextAction = 'Home';
								}
								header('Location: /MyAccount/' . $nextAction);
								exit();
							}

							$interface->assign('list', $userList);

							$listEntryId = $_REQUEST['listEntryId'];
							if (!empty($listEntryId)) {

								// Retrieve saved information about record
								require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
								$userListEntry = new UserListEntry();
								$userListEntry->id = $listEntryId;

								if ($userListEntry->find(true)) {
									$interface->assign('listEntry', $userListEntry);
									$interface->assign('recordDriver', $userListEntry->getRecordDriver());
								}
								$userListEntryCount = new UserListEntry();
								$userListEntryCount->listId = $listId;
								$interface->assign('maxListPosition', $userListEntryCount->count());
							}
						}
					}
				}
			}

			return array(
				'title' => translate(['text'=>'Edit List Item','isPublicFacing'=>true]),
				'modalBody' => $interface->fetch('MyAccount/editListTitle.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#listEntryEditForm\").submit()'>" . translate(['text'=>'Save','isPublicFacing'=>true]) . "</button>",
			);
		} else {
			return [
				'success' => false,
				'message' => translate(['text'=>'You must provide the id of the list to email','isPublicFacing'=>true])
			];
		}
	}

	/** @noinspection PhpUnused */
	function editListItem()
	{
		$result = [
			'success' => false,
			'title' => translate(['text'=>'Updating list entry','isPublicFacing'=>true]),
			'message' => translate(['text'=>'Sorry your list entry could not be updated','isPublicFacing'=>true])
		];
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		$userListEntry = new UserListEntry();
		$userListEntry->id = $_REQUEST['listEntry'];
		$currentLoc = $_REQUEST['listId'];
		$position = $_REQUEST['position'];

		$moveTo = $_REQUEST['moveTo'];
		$copyTo = $_REQUEST['copyTo'];

		$list = new UserList();
		$list->id = $currentLoc;

		if ($userListEntry->find(true)) {

			$userListEntry->notes = strip_tags($_REQUEST['notes']);
			$userListEntry->update();

			if(($position != $userListEntry->weight) && ($position != '')) {
				$newPosition = $_REQUEST['position'];
				$currentPosition = $userListEntry->weight;

				$desiredPosition = new UserListEntry();
				$desiredPosition->listId = $_REQUEST['listId'];
				$desiredPosition->weight = $newPosition;
				if ($desiredPosition->find(true)){
					$entriesToSwap = new UserListEntry();
					$entriesToSwap->listId = $_REQUEST['listId'];
					$maxPosition = $entriesToSwap->count();
					$entriesToSwap->find();
					while ($entriesToSwap->fetch()){
						if($newPosition > $currentPosition){
							// move up
							if ($entriesToSwap->weight == 1) {
								$entriesToSwap->weight = $entriesToSwap->weight + 1;
								$entriesToSwap->update();
							}
							elseif ($entriesToSwap->weight == $maxPosition) {
								$entriesToSwap->weight = $entriesToSwap->weight - 1;
								$entriesToSwap->update();
							}
							elseif ($entriesToSwap->weight < $newPosition) {
								$entriesToSwap->weight = $entriesToSwap->weight - 1;
								$entriesToSwap->update();
							}
						}
						if($newPosition < $currentPosition){
							// move down
							if ($entriesToSwap->weight == 1) {
								$entriesToSwap->weight = $entriesToSwap->weight + 1;
								$entriesToSwap->update();
							}
							elseif ($entriesToSwap->weight == $maxPosition) {
								$entriesToSwap->weight = $entriesToSwap->weight - 1;
								$entriesToSwap->update();
							}
							elseif ($entriesToSwap->weight > $newPosition) {
								$entriesToSwap->weight = $entriesToSwap->weight + 1;
								$entriesToSwap->update();
							}
						}
					}

					$userListEntry->weight = $newPosition;
					$userListEntry->update();

					$result['success'] = true;
				}
			}
			if(($moveTo != $currentLoc) && ($moveTo != 'null')) {
				// check to make sure item isn't on new list?

				$userListEntry->listId = $moveTo;
				$userListEntry->update();

				$moveToList = new UserList();
				$moveToList->id = $moveTo;
				$moveToList->update();

				$result['success'] = true;
			}
			if(($copyTo != $currentLoc) && ($copyTo != 'null')) {
				// check to make sure item isn't on new list?

				$copyUserListEntry = new UserListEntry();
				$copyUserListEntry->listId = $copyTo;
				$copyUserListEntry->sourceId = $userListEntry->sourceId;
				$copyUserListEntry->notes = $userListEntry->notes;
				$copyUserListEntry->weight = $userListEntry->weight;
				$copyUserListEntry->source = $userListEntry->source;
				$copyUserListEntry->dateAdded = time();
				$copyUserListEntry->update();

				$copyToList = new UserList();
				$copyToList->id = $copyTo;
				$copyToList->update();

				$result['success'] = true;
			}
			$list->update();
			$result['success'] = true;
		} else {
			$result['success'] = false;
		}

		if ($result['success']){
			$result['message'] = translate(['text'=>'List item updated successfully','isPublicFacing'=>true]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function updateWeight() {
		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown error moving list entry','isPublicFacing'=>true])
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->user_id = $user;
			if ($list->find(true) && $user->canEditList($list)) {
				if (isset($_REQUEST['listEntryId'])) {
					require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
					$listEntry = new UserListEntry();
					$listEntry->id = $_REQUEST['listEntryId'];
					if ($listEntry->find(true)){
						//Figure out new weights for list entries
						$direction = $_REQUEST['direction'];
						$oldWeight = $listEntry->weight;
						if ($direction == 'up'){
							$newWeight = $oldWeight - 1;
						}else{
							$newWeight = $oldWeight + 1;
						}

						$entryToSwap = new UserListEntry();
						$entryToSwap->listId = $listEntry->listId;
						$entryToSwap->weight = $newWeight;
						if ($entryToSwap->find(true)) {
							$listEntry->weight = $newWeight;
							$listEntry->update();
							$entryToSwap->weight = $oldWeight;
							$entryToSwap->update();

							$result['success'] = true;
							$result['message'] = 'The list entry was moved successfully';
							$result['swappedWithId'] = $entryToSwap->id;
						}else{
							if ($direction == 'up'){
								$result['message'] = 'List entry is already at the top';
							}else{
								$result['message'] = 'List entry is already at the bottom';
							}
						}
					}else{
						$result['message'] = 'Unable to find that list entry';
					}
				}else{
					$result['message'] = 'No list entry id was provided';
				}
			}else {
				$result['message'] = 'You don\'t have the correct permissions to move a list entry';
			}
		}else{
			$result['message'] = 'You must be logged in to move a list entry';
		}
		return $result;
	}

	function getSuggestionsSpotlight() {
		$result = array(
			'success' => false,
			'message' => 'Error loading suggestions spotlight.'
		);

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to view suggestions.  Please close this dialog and login again.';
		} else {
			require_once ROOT_DIR . '/sys/Suggestions.php';
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$suggestions = Suggestions::getSuggestions(UserAccount::getActiveUserId());
			foreach ($suggestions as $index => $suggestionInfo) {
				$groupedWorkDriver = new GroupedWorkDriver($suggestionInfo['titleInfo']);
				$result['suggestions'][] = $groupedWorkDriver->getSuggestionSpotlightResult($index);
			}
			$result['success'] = true;
			$result['message'] = '';
		}

		return $result;
	}
}
