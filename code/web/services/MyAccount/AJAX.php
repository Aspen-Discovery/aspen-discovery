<?php

require_once ROOT_DIR . '/JSON_Action.php';

class MyAccount_AJAX extends JSON_Action {
	const SORT_LAST_ALPHA = 'zzzzz';

	function launch($method = null) {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		switch ($method) {
			case 'renewItem':
				$method = 'renewCheckout';
				break;
		}
		if (method_exists($this, $method)) {
			if (in_array($method, ['getLoginForm'])) {
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			} else {
				parent::launch($method);
			}
		} else {
			echo json_encode(['error' => 'invalid_method']);
		}
	}

	/** @noinspection PhpUnused */
	function getAddBrowseCategoryFromListForm() {
		global $interface;

		// Select List Creation using Object Editor functions
		require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
		$temp = SubBrowseCategories::getObjectStructure('');
		$temp['subCategoryId']['values'] = [0 => 'Select One'] + $temp['subCategoryId']['values'];
		// add default option that denotes nothing has been selected to the options list
		// (this preserves the keys' numeric values (which is essential as they are the Id values) as well as the array's order)
		// btw addition of arrays is kinda a cool trick.
		$interface->assign('propName', 'addAsSubCategoryOf');
		$interface->assign('property', $temp['subCategoryId']);

		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));
		return [
			'title' => translate([
				'text' => 'Add as Browse Category to Home Page',
				'isAdminFacing' => 'true',
			]),
			'modalBody' => $interface->fetch('Browse/newBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>" . translate([
					'text' => 'Create Category',
					'isAdminFacing' => 'true',
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function addAccountLink(): array {
		if (!UserAccount::isLoggedIn()) {
			$result = [
				'success' => false,
				'title' => translate([
					'text' => 'Unable to link accounts',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Sorry, you must be logged in to manage accounts.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			$username = $_REQUEST['username'];
			$password = $_REQUEST['password'];
			$accountToLink = UserAccount::validateAccount($username, $password);

			if (!UserAccount::isLoggedIn()) {
				$result = [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to link accounts',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'You must be logged in to link accounts, please login again',
						'isPublicFacing' => true,
					]),
				];
			} elseif ($accountToLink) {
				$user = UserAccount::getLoggedInUser();
				$userPtype = $user->getPType();

				if ($accountToLink->id != $user->id) {
					$linkeePtype = $accountToLink->getPType();

					require_once ROOT_DIR . '/sys/Account/PType.php';
					$linkingSettingUser = PType::getAccountLinkingSetting($userPtype);
					$linkingSettingLinkee = PType::getAccountLinkingSetting($linkeePtype);

					if (($accountToLink->disableAccountLinking == 0) && ($linkingSettingUser != '1' && $linkingSettingUser != '3') && ($linkingSettingLinkee != '2' && $linkingSettingLinkee != '3')) {
						$addResult = $user->addLinkedUser($accountToLink);
						if ($addResult === true) {
							$result = [
								'success' => true,
								'title' => translate([
									'text' => 'Success',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Successfully linked accounts.',
									'isPublicFacing' => true,
								]),
							];
							$accountToLink->newLinkMessage();
						} else { // insert failure or user is blocked from linking account or account & account to link are the same account
							$result = [
								'success' => false,
								'title' => translate([
									'text' => 'Unable to link accounts',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Sorry, we could not link to that account.  Accounts cannot be linked if all libraries do not allow account linking.  Please contact your local library if you have questions.',
									'isPublicFacing' => true,
								]),
							];
						}
					} else {
						if ($linkingSettingUser == '1' || $linkingSettingUser == '3'){
							$result = [
								'success' => false,
								'title' => translate([
									'text' => 'Unable to link accounts',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Sorry, you are not permitted to link to others.',
									'isPublicFacing' => true,
								]),
							];
						}else if ($linkingSettingLinkee == '2' || $linkingSettingLinkee == '3') {
							$result = [
								'success' => false,
								'title' => translate([
									'text' => 'Unable to link accounts',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Sorry, that account cannot be linked to.',
									'isPublicFacing' => true,
								]),
							];
						}else {
							$result = [
								'success' => false,
								'title' => translate([
									'text' => 'Unable to link accounts',
									'isPublicFacing' => true,
								]),
								'message' => translate([
									'text' => 'Sorry, this user does not allow account linking.',
									'isPublicFacing' => true,
								]),
							];
						}
					}
				} else {
					$result = [
						'success' => false,
						'title' => translate([
							'text' => 'Unable to link accounts',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'You cannot link to yourself.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				$result = [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to link accounts',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'The information for the user to link to was not correct.',
						'isPublicFacing' => true,
					]),
				];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function removeManagingAccount(): array {
		if (!UserAccount::isLoggedIn()) {
			$result = [
				'success' => false,
				'title' => translate([
					'text' => 'Unable to Remove Account Link',
					'isAdminFacing' => 'true',
				]),
				'message' => translate([
					'text' => 'Sorry, you must be logged in to manage accounts.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			$accountToRemove = $_REQUEST['idToRemove'];
			$user = UserAccount::getLoggedInUser();
			if ($user->removeManagingAccount($accountToRemove)) {
				global $librarySingleton;
				// Get Library Settings from the home library of the current user-account being displayed
				$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($user);
				if ($patronHomeLibrary->allowPinReset == 1) {
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Linked Account Removed',
							'isAdminFacing' => 'true',
						]),
						'message' => translate([
							'text' => 'Successfully removed linked account. Removing this link does not guarantee the security of your account. If another user has your barcode and PIN/password they will still be able to access your account. Would you like to change your password?',
							'isPublicFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.redirectPinReset(); return false;'>" . translate([
								'text' => "Request PIN Change",
								'isPublicFacing' => true,
							]) . "</span>",
					];
				} else {
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Linked Account Removed',
							'isAdminFacing' => 'true',
						]),
						'message' => translate([
							'text' => 'Successfully removed linked account. Removing this link does not guarantee the security of your account. If another user has your barcode and PIN/password they will still be able to access your account. Please contact your library if you wish to update your PIN/Password.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				$result = [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to Remove Account Link',
						'isAdminFacing' => 'true',
					]),
					'message' => translate([
						'text' => 'Sorry, we could not remove that account.',
						'isPublicFacing' => true,
					]),
				];
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function removeAccountLink(): array {
		if (!UserAccount::isLoggedIn()) {
			$result = [
				'success' => false,
				'title' => translate([
					'text' => 'Unable to Remove Account Link',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => translate([
						'Sorry, you must be logged in to manage accounts.',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
			];
		} else {
			$accountToRemove = $_REQUEST['idToRemove'];
			$user = UserAccount::getLoggedInUser();
			if ($user->removeLinkedUser($accountToRemove)) {
				$result = [
					'success' => true,
					'title' => translate([
						'text' => 'Success',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => translate([
							'text' => 'Successfully removed linked account.',
							'isPublicFacing' => true,
						]),
						'isPublicFacing' => true,
					]),
				];
			} else {
				$result = [
					'success' => false,
					'title' => translate([
						'text' => 'Unable to Remove Account Link',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => translate([
							'text' => 'Sorry, we could remove that account.',
							'isPublicFacing' => true,
						]),
						'isPublicFacing' => true,
					]),
				];
			}
		}
		return $result;
	}

	//WHAT IS IN MODAL POPUP FOR LINK DISABLE

	/** @noinspection PhpUnused */
	function disableAccountLinkingInfo(): array {
		$user = UserAccount::getActiveUserObj();
		if ($user->disableAccountLinking == 1) {
			return [
				'title' => translate([
					'text' => 'Enable Account Linking',
					'isPublicFacing' => true,
				]),
				'modalBody' => translate([
					'text' => 'Re-enabling account linking will allow others to link to your account. Do you want to continue?',
					'isPublicFacing' => true,
				]),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.toggleAccountLinkingAccept(); return false;'>" . translate([
						'text' => "Accept",
						'isPublicFacing' => true,
					]) . "</span>",
			];
		} else {
			return [
				'title' => translate([
					'text' => 'Disable Account Linking',
					'isPublicFacing' => true,
				]),
				'modalBody' => translate([
					'text' => 'Disabling account linking will sever any current links and prevent any new ones. Do you want to continue?',
					'isPublicFacing' => true,
				]),
				'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.toggleAccountLinkingAccept(); return false;'>" . translate([
						'text' => "Accept",
						'isPublicFacing' => true,
					]) . "</span>",
			];
		}
	}

	//USED UPON SUBMITTING

	/** @noinspection PhpUnused */
	function toggleAccountLinking() {
		if (!UserAccount::isLoggedIn()) {
			$result = [
				'message' => translate([
					'text' => 'Sorry, you must be logged in to manage accounts.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			$user = UserAccount::getActiveUserObj();
			if ($user->disableAccountLinking == 1) {
				$success = $user->accountLinkingToggle();
				$result = [
					'success' => $success,
					'title' => translate([
						'text' => 'Linking Enabled',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Account linking has been enabled',
						'isPublicFacing' => true,
					]),
				];
			} else {
				if ($user->disableAccountLinking == 0) {
					$success = $user->accountLinkingToggle();
					global $librarySingleton;
					// Get Library Settings from the home library of the current user-account being displayed
					$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($user);
					if ($patronHomeLibrary->allowPinReset == 1) {
						$result = [
							'success' => $success,
							'title' => translate([
								'text' => 'Linking Disabled',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Account linking has been disabled. Disabling account linking does not guarantee the security of your account. If another user has your barcode and PIN/password they will still be able to access your account. Would you like to change your password?',
								'isPublicFacing' => true,
							]),
							'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.redirectPinReset(); return false;'>" . translate([
									'text' => "Request PIN Change",
									'isPublicFacing' => true,
								]) . "</span>",
						];
					} else {
						$result = [
							'success' => $success,
							'title' => translate([
								'text' => 'Linking Disabled',
								'isAdminFacing' => 'true',
							]),
							'message' => translate([
								'text' => 'Account linking has been disabled. Disabling account linking does not guarantee the security of your account. If another user has your barcode and PIN/password they will still be able to access your account. Please contact your library if you wish to update your PIN/Password.',
								'isPublicFacing' => true,
							]),
						];

					}
				} else {
					$result = [
						'success' => false,
						'message' => translate([
							'text' => 'Sorry, something went wrong and we were unable to process this request.',
							'isPublicFacing' => true,
						]),
					];
				}
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getAddAccountLinkForm() {
		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', 0);
		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));
		// Display Page
		return [
			'title' => translate([
				'text' => 'Account to Manage',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/addAccountLink.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' id = 'AddAccountSubmit' onclick='AspenDiscovery.Account.processAddLinkedUser(); return false;'>" . translate([
					'text' => "Add Account",
					'isPublicFacing' => true,
				]) . "</span>",
		];
	}

	/** @noinspection PhpUnused */
	function allowAccountLink() {
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';

		$activeUserId = UserAccount::getActiveUserId();
		$userMessage = new UserMessage();
		$userMessage->messageType = 'confirm_linked_accts';
		$userMessage->userId = $activeUserId;
		$userMessage->isDismissed = "0";
		$userMessage->find();
		while ($userMessage->fetch()) {
			$userMessage->isDismissed = 1;
			$userMessage->update();
		}

		return [
			'success' => true,
			'message' => 'Account Link Accepted',
		];
	}

	/** @noinspection PhpUnused */
	function getBulkAddToListForm() {
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));

		return [
			'title' => translate([
				'text' => 'Add titles to list',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/bulkAddToListPopup.tpl'),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Lists.processBulkAddForm(); return false;'>" . translate([
					'text' => "Add To List",
					'isPublicFacing' => true,
				]) . "</span>",
		];
	}

	/** @noinspection PhpUnused */
	function saveSearch() {
		$result = [
			'success' => false,
			'message' => 'Unknown error saving search',
		];
		$searchId = $_REQUEST['searchId'];
		$title = $_REQUEST['title'];
		$search = new SearchEntry();
		$search->id = $searchId;
		if ($search->find(true)) {
			// Found, make sure this is a search from this user
			if ($search->session_id == session_id() || $search->user_id == UserAccount::getActiveUserId()) {
				if ($search->saved != 1) {
					$search->user_id = UserAccount::getActiveUserId();
					$search->saved = 1;
					$search->title = $title;
					if ($search->update() !== FALSE) {
						$result['success'] = true;
						$result['message'] = translate([
							'text' => "Your search was saved successfully.  You can view the saved search by clicking on Your Searches within the Account Menu.",
							'isPublicFacing' => true,
						]);
						$result['modalButtons'] = "<a class='tool btn btn-primary' id='viewSavedSearches' href='/Search/History?require_login'>" . translate([
								'text' => "View Saved Searches",
								'isPublicFacing' => true,
							]) . "</a>";
					} else {
						$result['message'] = translate([
							'text' => "Sorry, we could not save that search for you.  It may have expired.",
							'isPublicFacing' => true,
						]);
					}
				} else {
					$result['success'] = true;
					$result['message'] = translate([
						'text' => "That search was already saved.",
						'isPublicFacing' => true,
					]);
					$result['modalButtons'] = "<a class='tool btn btn-primary' id='viewSavedSearches' href='/Search/History?require_login'>" . translate([
							'text' => "View Saved Searches",
							'isPublicFacing' => true,
						]) . "</a>";
				}
			} else {
				$result['message'] = translate([
					'text' => "Sorry, it looks like that search does not belong to you.",
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => "Sorry, it looks like that search has expired.",
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getSaveSearchForm() {
		global $interface;

		$searchId = $_REQUEST['searchId'];
		$interface->assign('searchId', $searchId);

		require_once ROOT_DIR . '/services/Search/History.php';
		History::getSearchForSaveForm($searchId);

		return [
			'title' => translate([
				'text' => 'Save Search',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/saveSearch.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.saveSearch(); return false;'>" . translate([
					'text' => 'Save',
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function confirmCancelHold(): array {
		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'];
		$cancelId = $_REQUEST['cancelId'];
		$isIll = $_REQUEST['isIll'];
		$cancelButtonLabel = translate([
			'text' => 'Confirm Cancel Hold',
			'isPublicFacing' => true,
		]);
		return [
			'title' => translate([
				'text' => 'Cancel Hold',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => "Are you sure you want to cancel this hold?",
				'isPublicFacing' => true,
			]),
			'buttons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.cancelHold(\"$patronId\", \"$recordId\", \"$cancelId\", \"$isIll\")'>$cancelButtonLabel</span>",
		];
	}

	/** @noinspection PhpUnused */
	function cancelHold(): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Error cancelling hold.',
				'isPublicFacing' => true,
			]),
		];

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = translate([
				'text' => 'You must be logged in to cancel a hold.  Please close this dialog and login again.',
				'isPublicFacing' => true,
			]);;
		} else {
			//Determine which user the hold is on so we can cancel it.
			$patronId = $_REQUEST['patronId'];
			$user = UserAccount::getLoggedInUser();
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate([
					'text' => 'Sorry, you do not have access to cancel holds for the supplied user.',
					'isPublicFacing' => true,
				]);;
			} else {
				//MDN 9/20/2015 The recordId can be empty for INN-Reach holds
				if (empty($_REQUEST['cancelId']) && empty($_REQUEST['recordId'])) {
					$result['message'] = translate([
						'text' => 'Information about the hold to be cancelled was not provided.',
						'isPublicFacing' => true,
					]);;
				} else {
					$cancelId = $_REQUEST['cancelId'];
					$recordId = $_REQUEST['recordId'];
					$isIll = $_REQUEST['isIll'] ?? false;
					$result = $patronOwningHold->cancelHold($recordId, $cancelId, $isIll);
				}
			}
		}

		global $interface;
		// if title come back a single item array, set as the title instead. likewise for message
		if (isset($result['title'])) {
			if (is_array($result['title']) && count($result['title']) == 1) {
				$result['title'] = current($result['title']);
			}
		}
		if (is_array($result['message']) && count($result['message']) == 1) {
			$result['message'] = current($result['message']);
		}

		$interface->assign('cancelResults', $result);

		return [
			'title' => translate([
				'text' => 'Cancel Hold',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/cancelHold.tpl'),
			'success' => $result['success'],
		];
	}

	function cancelHoldSelectedItems() {
		$result = [
			'success' => false,
			'message' => 'Error cancelling hold.',
		];

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to cancel a hold.  Please close this dialog and login again.';
		} else {
			$success = 0;
			$user = UserAccount::getLoggedInUser();
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
				$total = count($_REQUEST['selected']);
				foreach ($_REQUEST['selected'] as $selected => $ignore) {
					@list($patronId, $recordId, $cancelId) = explode('|', $selected);
					$patronOwningHold = $user->getUserReferredTo($patronId);
					if ($patronOwningHold == false) {
						$tmpResult = [
							'success' => false,
							'message' => 'Sorry, it looks like you don\'t have access to that patron.',
						];
					} else {
						foreach ($allUnavailableHolds as $key) {
							if ($key->sourceId == $recordId) {
								$holdType = $key->source;
								break;
							}
						}
						if ($holdType == 'ils') {
							$tmpResult = $user->cancelHold($recordId, $cancelId, $key->isIll);
							if ($tmpResult['success']) {
								$success++;
							}
						} elseif ($holdType == 'axis360') {
							require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
							$driver = new Axis360Driver();
							$tmpResult = $driver->cancelHold($user, $recordId);
							if ($tmpResult['success']) {
								$success++;
							}
						} elseif ($holdType == 'overdrive') {
							require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
							$driver = new OverDriveDriver();
							$tmpResult = $driver->cancelHold($user, $recordId);
							if ($tmpResult['success']) {
								$success++;
							}
						} elseif ($holdType == 'cloud_library') {
							require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
							$driver = new CloudLibraryDriver();
							$tmpResult = $driver->cancelHold($user, $recordId);
							if ($tmpResult['success']) {
								$success++;
							}
						}

						$message = '<div class="alert alert-success">' . translate([
								'text' => '%1% of %2% holds were cancelled',
								1 => $success,
								2 => $total,
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</div>';
						$tmpResult['message'] = $message;
					}
				}
			} else {
				$tmpResult['message'] = translate([
					'text' => 'No holds were selected to canceled',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]);
			}
		}

		return $tmpResult;
	}

	/** @noinspection PhpUnused */
	function cancelVdxRequest(): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Error cancelling request.',
				'isPublicFacing' => true,
			]),
		];

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = translate([
				'text' => 'You must be logged in to cancel a request.  Please close this dialog and login again.',
				'isPublicFacing' => true,
			]);;
		} else {
			//Determine which user the request is on so we can cancel it.
			$patronId = $_REQUEST['patronId'];
			$user = UserAccount::getLoggedInUser();
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate([
					'text' => 'Sorry, you do not have access to cancel requests for the supplied user.',
					'isPublicFacing' => true,
				]);;
			} else {
				//MDN 9/20/2015 The recordId can be empty for INN-Reach holds
				if (empty($_REQUEST['requestId']) || !isset($_REQUEST['cancelId'])) {
					$result['message'] = translate([
						'text' => 'Information about the requests to be cancelled was not provided.',
						'isPublicFacing' => true,
					]);;
				} else {
					$requestId = $_REQUEST['requestId'];
					$cancelId = $_REQUEST['cancelId'];
					$result = $patronOwningHold->cancelVdxRequest($requestId, $cancelId);
				}
			}
		}

		return $result;
	}

	function cancelAllHolds() {
		$tmpResult = [
			'success' => false,
			'message' => ['Unable to cancel all holds'],
		];
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
				$isIll = $hold->isIll;
				if ($holdType == 'ils') {
					$tmpResult = $user->cancelHold($recordId, $cancelId, $isIll);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'axis360') {
					require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
					$driver = new Axis360Driver();
					$tmpResult = $driver->cancelHold($user, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'overdrive') {
					require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
					$driver = new OverDriveDriver();
					$tmpResult = $driver->cancelHold($user, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'cloud_library') {
					require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
					$driver = new CloudLibraryDriver();
					$tmpResult = $driver->cancelHold($user, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
				}

				$message = '<div class="alert alert-success">' . translate([
						'text' => '%1% of %2% holds were canceled',
						1 => $success,
						2 => $total,
						'isPublicFacing' => true,
						'inAttribute' => true,
					]) . '</div>';
				$tmpResult['message'] = $message;

			}
		} else {
			$tmpResult['message'] = translate([
				'text' => 'You must be logged in to cancel holds',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);
		}

		return $tmpResult;
	}

	function freezeHold(): array {
		$user = UserAccount::getLoggedInUser();
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Error freezing hold.',
				'isPublicFacing' => true,
			]),
		];
		if (!$user) {
			$result['message'] = translate([
				'text' => 'You must be logged in to freeze a hold.  Please close this dialog and login again.',
				'isPublicFacing' => true,
			]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate([
					'text' => 'Sorry, you do not have access to freeze holds for the supplied user.',
					'isPublicFacing' => true,
				]);
			} else {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					global $logger;
					$logger->log('Freeze Hold, no record or hold Id was passed in AJAX call.', Logger::LOG_ERROR);
					$result['message'] = translate([
						'text' => 'Information about the hold to be frozen was not provided.',
						'isPublicFacing' => true,
					]);
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
			$result['message'] = translate([
				'text' => 'No Patron was specified.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	function freezeHoldSelectedItems() {
		$tmpResult = [ // set default response
			'success' => false,
			'message' => 'Error freezing hold.',
		];

		if (!UserAccount::isLoggedIn()) {
			$tmpResult['message'] = 'You must be logged in to freeze a hold.  Please close this dialog and login again.';
		} else {
			$user = UserAccount::getLoggedInUser();
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			$success = 0;
			$failed = 0;
			if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
				$total = count($_REQUEST['selected']);
				foreach ($_REQUEST['selected'] as $selected => $ignore) {
					@list($patronId, $recordId, $holdId) = explode('|', $selected);
					$patronOwningHold = $user->getUserReferredTo($patronId);
					if ($patronOwningHold == false) {
						$tmpResult = [
							'success' => false,
							'message' => translate([
								'text' => 'Sorry, it looks like you don\'t have access to that patron.',
								'isPublicFacing' => true,
								'inAttribute' => true,
							]),
						];
					} else {
						foreach ($allUnavailableHolds as $key) {
							if ($key->sourceId == $recordId) {
								$holdType = $key->source;
								$frozen = $key->frozen;
								$canFreeze = $key->canFreeze;
								break;
							}
						}
						if ($frozen != 1 && $canFreeze == 1) {
							if ($holdType == 'ils') {
								$tmpResult = $patronOwningHold->freezeHold($recordId, $holdId, false);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
							} elseif ($holdType == 'axis360') {
								require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
								$driver = new Axis360Driver();
								$tmpResult = $driver->freezeHold($patronOwningHold, $recordId);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
							} elseif ($holdType == 'overdrive') {
								require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
								$driver = new OverDriveDriver();
								$tmpResult = $driver->freezeHold($patronOwningHold, $recordId, null);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
								//cloudLibrary holds can't be frozen
//							} else if ($holdType == 'cloud_library') {
//								require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
//								$driver = new CloudLibraryDriver();
//								$tmpResult = $driver->freezeHold($user, $recordId);
//								if($tmpResult['success']){$success++;}else{$failed++;}
							} else {
								$failed++;
							}
						} else {
							if ($canFreeze == 0) {
								$failed++;
							} elseif ($frozen == 1) {
								$failed++;
							}
						}

						$message = '<div class="alert alert-success">' . translate([
								'text' => '%1% of %2% holds were frozen',
								1 => $success,
								2 => $total,
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</div>';
						$tmpResult['message'] = $message;

					}
				}
			} else {
				$tmpResult['message'] = translate([
					'text' => 'No holds were selected to freeze',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]);
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

	function thawHold(): array {
		$user = UserAccount::getLoggedInUser();
		$result = [ // set default response
			'success' => false,
			'message' => 'Error thawing hold.',
		];

		if (!$user) {
			$result['message'] = translate([
				'text' => 'You must be logged in to thaw a hold.  Please close this dialog and login again.',
				'isPublicFacing' => true,
			]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate([
					'text' => 'Sorry, you do not have access to thaw holds for the supplied user.',
					'isPublicFacing' => true,
				]);
			} else {
				if (empty($_REQUEST['recordId']) || empty($_REQUEST['holdId'])) {
					$result['message'] = translate([
						'text' => 'Information about the hold to be thawed was not provided.',
						'isPublicFacing' => true,
					]);
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
			$result['message'] = translate([
				'text' => 'No Patron was specified.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	function thawHoldSelectedItems() {
		$result = [ // set default response
			'success' => false,
			'message' => 'Error thawing hold.',
		];

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to thaw a hold.  Please close this dialog and login again.';
		} else {
			$success = 0;
			$failed = 0;
			$user = UserAccount::getLoggedInUser();
			$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
			$allUnavailableHolds = $allHolds['unavailable'];
			if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
				$total = count($_REQUEST['selected']);
				foreach ($_REQUEST['selected'] as $selected => $ignore) {
					@list($patronId, $recordId, $holdId) = explode('|', $selected);
					$patronOwningHold = $user->getUserReferredTo($patronId);
					if ($patronOwningHold == false) {
						$tmpResult = [
							'success' => false,
							'message' => 'Sorry, it looks like you don\'t have access to that patron.',
						];
					} else {
						foreach ($allUnavailableHolds as $key) {
							if ($key->sourceId == $recordId) {
								$holdType = $key->source;
								$frozen = $key->frozen;
								$canFreeze = $key->canFreeze;
								break;
							}
						}
						if ($frozen != 0 && $canFreeze == 1) {
							if ($holdType == 'ils') {
								$tmpResult = $user->thawHold($recordId, $holdId);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
							} elseif ($holdType == 'axis360') {
								require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
								$driver = new Axis360Driver();
								$tmpResult = $driver->thawHold($user, $recordId);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
							} elseif ($holdType == 'overdrive') {
								require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
								$driver = new OverDriveDriver();
								$tmpResult = $driver->thawHold($user, $recordId);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
							} elseif ($holdType == 'cloud_library') {
								require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
								$driver = new CloudLibraryDriver();
								$tmpResult = $driver->thawHold($user, $recordId);
								if ($tmpResult['success']) {
									$success++;
								} else {
									$failed++;
								}
							} else {
								$failed++;
							}
						}

						$message = '<div class="alert alert-success">' . translate([
								'text' => '%1% of %2% holds were thawed',
								1 => $success,
								2 => $total,
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</div>';
						$tmpResult['message'] = $message;

					}
				}
			} else {
				$tmpResult['message'] = translate([
					'text' => 'No holds were selected to thaw',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]);
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
	function addList() {
		$return = [];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$title = (isset($_REQUEST['title']) && !is_array($_REQUEST['title'])) ? urldecode($_REQUEST['title']) : '';
			if (strlen(trim($title)) == 0) {
				$return['success'] = "false";
				$return['message'] = "You must provide a title for the list";
			} else {
				//If the record is not valid, skip the whole thing since the title could be bad too
				if (!empty($_REQUEST['sourceId']) && !is_array($_REQUEST['sourceId']) && $_REQUEST['source'] != 'Events') {
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
				$list->displayListAuthor = isset($_REQUEST['displayListAuthor']) && $_REQUEST['displayListAuthor'] == 'true';
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
						if ($userListEntry->source == 'GroupedWork') {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
							$groupedWork = new GroupedWork();
							$groupedWork->permanent_id = $userListEntry->sourceId;
							if ($groupedWork->find(true)) {
								$userListEntry->title = substr($groupedWork->full_title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Lists') {
							require_once ROOT_DIR . '/sys/UserLists/UserList.php';
							$list = new UserList();
							$list->id = $userListEntry->sourceId;
							if ($list->find(true)) {
								$userListEntry->title = substr($list->title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Events') {
							if (preg_match('`^communico`', $userListEntry->sourceId)){
								require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
								$recordDriver = new CommunicoEventRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} elseif (preg_match('`^libcal`', $userListEntry->sourceId)){
								require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
								$recordDriver = new SpringshareLibCalEventRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} else {
								require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
								$recordDriver = new LibraryCalendarEventRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							}
						} elseif ($userListEntry->source == 'OpenArchives') {
							require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
							$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
							if ($recordDriver->isValid()) {
								$title = $recordDriver->getTitle();
								$userListEntry->title = substr($title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Genealogy') {
							require_once ROOT_DIR . '/sys/Genealogy/Person.php';
							$person = new Person();
							$person->personId = $userListEntry->sourceId;
							if ($person->find(true)) {
								$userListEntry->title = substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
							}
						} elseif ($userListEntry->source == 'EbscoEds') {
							require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
							$recordDriver = new EbscoRecordDriver($userListEntry->sourceId);
							if ($recordDriver->isValid()) {
								$title = $recordDriver->getTitle();
								$userListEntry->title = substr($title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Ebscohost') {
							require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
							$recordDriver = new EbscohostRecordDriver($userListEntry->sourceId);
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
					$return['message'] = "Updated list $list->title successfully";
				} else {
					$return['message'] = "Created list $list->title successfully";
				}
			}
		} else {
			$return['success'] = "false";
			$return['message'] = "You must be logged in to create a list";
		}

		return $return;
	}

	/** @noinspection PhpUnused */
	function getCreateListForm() {
		global $interface;

		if (isset($_REQUEST['sourceId'])) {
			$sourceId = $_REQUEST['sourceId'];
			$source = $_REQUEST['source'];
			$interface->assign('sourceId', $sourceId);
			$interface->assign('source', $source);
		}

		//Check to see if we will index the list if it is public
		global $library;
		$location = Location::getSearchLocation();
		$ownerHasListPublisherRole = UserAccount::userHasPermission('Include Lists In Search Results');
		if ($location != null) {
			$publicListWillBeIndexed = ($location->publicListsToInclude == 3) || //All public lists
				($location->publicListsToInclude == 1) || //All lists for the current library
				(($location->publicListsToInclude == 2) && $location->locationId == UserAccount::getUserHomeLocationId()) || //All lists for the current location
				(($location->publicListsToInclude == 4) && $ownerHasListPublisherRole) || //All lists for list publishers at the current library
				(($location->publicListsToInclude == 5) && $ownerHasListPublisherRole) || //All lists for list publishers the current location
				(($location->publicListsToInclude == 6) && $ownerHasListPublisherRole) //All lists for list publishers
			;
		} else {
			$publicListWillBeIndexed = ($library->publicListsToInclude == 2) || //All public lists
				(($library->publicListsToInclude == 1)) || //All lists for the current library
				(($library->publicListsToInclude == 3) && $ownerHasListPublisherRole) || //All lists for list publishers at the current library
				(($library->publicListsToInclude == 4) && $ownerHasListPublisherRole) //All lists for list publishers
			;
		}
		$interface->assign('publicListWillBeIndexed', $publicListWillBeIndexed);
		$interface->assign('enableListDescriptions', $library->enableListDescriptions);

		if (!empty($library->allowableListNames)) {
			$validListNames = explode('|', $library->allowableListNames);
			foreach ($validListNames as $index => $listName) {
				$validListNames[$index] = translate([
					'text' => $listName,
					'isPublicFacing' => true,
					'isAdminEnteredData' => true,
				]);
			}
		} else {
			$validListNames = [];
		}
		$interface->assign('validListNames', $validListNames);

		return [
			'title' => translate([
				'text' => 'Create new List',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("MyAccount/createListForm.tpl"),
			'modalButtons' => "<span class='tool btn btn-primary' onclick='AspenDiscovery.Account.addList(); return false;'>" . translate([
					'text' => 'Create List',
					'isPublicFacing' => true,
				]) . "</span>",
		];
	}

	/** @noinspection PhpUnused */
	function getLoginForm() {
		global $interface;
		global $library;
		global $locationSingleton;
		global $configArray;

		$isPrimaryAccountAuthenticationSSO = UserAccount::isPrimaryAccountAuthenticationSSO();
		$interface->assign('isPrimaryAccountAuthenticationSSO', $isPrimaryAccountAuthenticationSSO);

		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
		$interface->assign('checkRememberMe', 0);
		if ($library->defaultRememberMe && $locationSingleton->getOpacStatus() == false) {
			$interface->assign('checkRememberMe', 1);
		}
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

		//SSO
		$loginOptions = 0;
		$ssoService = null;
		if ($isPrimaryAccountAuthenticationSSO || $library->ssoSettingId != -1) {
			try {
				$ssoSettingId = null;
				if($isPrimaryAccountAuthenticationSSO) {
					require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
					$accountProfile = new AccountProfile();
					$accountProfile->id = $library->accountProfileId;
					if($accountProfile->find(true)) {
						$ssoSettingId = $accountProfile->ssoSettingId;
					}
				} else {
					$ssoSettingId = $library->ssoSettingId;
				}

				// only try to get SSO settings if the module is enabled
				global $enabledModules;
				if (array_key_exists('Single sign-on', $enabledModules) && $ssoSettingId > 0) {
					require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
					$sso = new SSOSetting();
					$sso->id = $ssoSettingId;
					if ($sso->find(true)) {
						if (!$sso->staffOnly) {
							$ssoService = $sso->service;
							$loginOptions = $sso->loginOptions;
							$interface->assign('ssoLoginHelpText', $sso->loginHelpText);
							if ($sso->service == "oauth") {
								$interface->assign('oAuthGateway', $sso->oAuthGateway);
								if ($sso->oAuthGateway == "custom") {
									$interface->assign('oAuthCustomGatewayLabel', $sso->oAuthGatewayLabel);
									$interface->assign('oAuthButtonBackgroundColor', $sso->oAuthButtonBackgroundColor);
									$interface->assign('oAuthButtonTextColor', $sso->oAuthButtonTextColor);
									if ($sso->oAuthGatewayIcon) {
										$interface->assign('oAuthCustomGatewayIcon', $configArray['Site']['url'] . '/files/original/' . $sso->oAuthGatewayIcon);
									}
								}
							}
							if ($sso->service == 'saml') {
								$interface->assign('samlEntityId', $sso->ssoEntityId);
								$interface->assign('samlBtnLabel', $sso->ssoName);
								$interface->assign('samlBtnBgColor', $sso->samlBtnBgColor);
								$interface->assign('samlBtnTextColor', $sso->samlBtnTextColor);
								if ($sso->oAuthGatewayIcon) {
									$interface->assign('samlBtnIcon', $configArray['Site']['url'] . '/files/original/' . $sso->samlBtnIcon);
								}
							}
							if ($sso->service == 'ldap') {
								if ($sso->ldapLabel) {
									$interface->assign('ldapLabel', $sso->ldapLabel);
								}
							}
						}
					}
				}
			} catch (Exception $e) {
				//This happens before the table is defined
			}
		}

		$interface->assign('ssoService', $ssoService);
		$interface->assign('ssoLoginOptions', $loginOptions);

		if (!empty($library->loginNotes)) {
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
		} else {
			$interface->assign('forgotPasswordType', 'none');
		}

		if (isset($_REQUEST['multiStep'])) {
			$interface->assign('multiStep', true);
		}
		return $interface->fetch('MyAccount/ajax-login.tpl');
	}

	/** @noinspection PhpUnused */
	function getMasqueradeAsForm() {
		global $interface;
		return [
			'title' => translate([
				'text' => 'Masquerade As',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("MyAccount/ajax-masqueradeAs.tpl"),
			'modalButtons' => '<button class="tool btn btn-primary" onclick="$(\'#masqueradeForm\').submit()">' . translate([
					'text' => 'Start',
					'isPublicFacing' => true,
				]) . '</button>',
		];
	}

	/** @noinspection PhpUnused */
	function initiateMasquerade() {
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::initiateMasquerade();
	}

	/** @noinspection PhpUnused */
	function endMasquerade() {
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::endMasquerade();
	}

	/** @noinspection PhpUnused */
	function getChangeHoldLocationForm() {
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

			$recordId = $_REQUEST['recordId'];
			$sourceId = $_REQUEST['source'] . ":" . $_REQUEST['recordId'];

			$currentLocation = $_REQUEST['currentLocation'];
			if (!is_numeric($currentLocation)) {
				$location = new Location();
				$location->code = $currentLocation;
				if ($location->find(true)) {
					$currentLocation = $location->locationId;
				} else {
					$currentLocation = null;
				}
			}
			$interface->assign('currentLocation', $currentLocation);

			$location = new Location();
			$pickupBranches = $location->getPickupBranches($patronOwningHold);

			$pickupAt = 0;
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($sourceId);
			if ($marcRecord->isValid()) {
				$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
				$pickupAt = $relatedRecord->getHoldPickupSetting();
				if ($pickupAt > 0) {
					$itemLocations = $marcRecord->getValidPickupLocations($pickupAt);
					foreach ($pickupBranches as $locationKey => $location) {
						if (is_object($location) && !in_array(strtolower($location->code), $itemLocations)) {
							unset($pickupBranches[$locationKey]);
						}
					}
				}
			}

			$interface->assign('pickupAt', $pickupAt);
			$interface->assign('pickupLocations', $pickupBranches);

			$results = [
				'title' => translate([
					'text' => 'Change Hold Location',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch("MyAccount/changeHoldLocation.tpl"),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="AspenDiscovery.Account.doChangeHoldLocation(); return false;">' . translate([
						'text' => 'Change Location',
						'isPublicFacing' => true,
					]) . '</span>',
			];
		} else {
			$results = [
				'title' => 'Please login',
				'modalBody' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before changing your hold's pick-up location.",
					'isPublicFacing' => true,
				]),
				'modalButtons' => "",
			];
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function getReactivationDateForm() {
		global $interface;

		$user = UserAccount::getLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$patronOwningHold = $user->getUserReferredTo($patronId);
		if ($patronOwningHold != false) {
			$id = $_REQUEST['holdId'];
			$interface->assign('holdId', $id);
			$interface->assign('patronId', $patronId);
			$interface->assign('recordId', $_REQUEST['recordId']);

			$reactivateDateNotRequired = $user->reactivateDateNotRequired();
			$interface->assign('reactivateDateNotRequired', $reactivateDateNotRequired);

			$title = translate([
				'text' => 'Freeze Hold',
				'isPublicFacing' => true,
			]); // language customization
			return [
				'title' => $title,
				'modalBody' => $interface->fetch("MyAccount/reactivationDate.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' id='doFreezeHoldWithReactivationDate' onclick='$(\".form\").submit(); return false;'>$title</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, you do not have access to freeze holds for the supplied user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function changeHoldLocation() {
		try {
			$holdId = $_REQUEST['holdId'];
			$newPickupLocation = $_REQUEST['newLocation'];

			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$patronOwningHold = $user->getUserReferredTo($patronId);
				if ($patronOwningHold != false) {
					if ($patronOwningHold->validatePickupBranch($newPickupLocation)) {
						return $patronOwningHold->changeHoldPickUpLocation($holdId, $newPickupLocation);
					} else {
						return [
							'result' => false,
							'message' => translate([
								'text' => 'The selected pickup location is not valid.',
								'isPublicFacing' => true,
							]),
						];
					}
				} else {
					return [
						'result' => false,
						'message' => translate([
							'text' => 'The logged in user does not have permission to change hold location for the specified user, please login as that user.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				return $results = [
					'title' => translate([
						'text' => 'Please login',
						'isPublicFacing' => true,
					]),
					'modalBody' => translate([
						'text' => "You must be logged in.  Please close this dialog and login to change this hold's pick up location.",
						'isPublicFacing' => true,
					]),
					'modalButtons' => "",
				];
			}

		} catch (PDOException $e) {
			// What should we do with this error?
			if (IPAddress::showDebuggingInformation()) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}
		return [
			'result' => false,
			'message' => translate([
				'text' => 'We could not connect to the circulation system, please try again later.',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	function requestPinReset() {
		$catalog = CatalogFactory::getCatalogConnectionInstance();

		//Get the list of pickup branch locations for display in the user interface.
		return $catalog->processEmailResetPinForm();
	}

	/** @noinspection PhpUnused */
	function getCitationFormatsForm() {
		global $interface;
		$interface->assign('listId', $_REQUEST['listId']);
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormats', $citationFormats);
		$pageContent = $interface->fetch('MyAccount/getCitationFormatPopup.tpl');
		return [
			'title' => translate([
				'text' => 'Select Citation Format',
				'isPublicFacing' => true,
			]),
			'modalBody' => $pageContent,
			'modalButtons' => '<input class="btn btn-primary" onclick="AspenDiscovery.Lists.processCiteListForm(); return false;" value="' . translate([
					'text' => 'Generate Citations',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]) . '">',
		];
	}

	/** @noinspection PhpUnused */
	function sendMyListEmail() {
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
							$result = [
								'result' => true,
								'message' => 'Your email was sent successfully.',
							];
						} elseif (($emailResult instanceof AspenError)) {
							$result = [
								'result' => false,
								'message' => "Your email message could not be sent: {$emailResult->getMessage()}.",
							];
						} else {
							$result = [
								'result' => false,
								'message' => 'Your email message could not be sent due to an unknown error.',
							];
							global $logger;
							$logger->log("Mail List Failure (unknown reason), parameters: $to, $from, $subject, $body", Logger::LOG_ERROR);
						}
					} else {
						$result = [
							'result' => false,
							'message' => 'Sorry, we can&apos;t send emails with html or other data in it.',
						];
					}

				} else {
					$result = [
						'result' => false,
						'message' => 'You do not have access to this list.',
					];

				}
			} else {
				$result = [
					'result' => false,
					'message' => 'Unable to read list.',
				];
			}
		} else { // Invalid listId
			$result = [
				'result' => false,
				'message' => "Invalid List Id. Your email message could not be sent.",
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEmailMyListForm() {
		global $interface;
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) {
			$listId = $_REQUEST['listId'];

			$interface->assign('listId', $listId);
			return [
				'title' => 'Email a list',
				'modalBody' => $interface->fetch('MyAccount/emailListPopup.tpl'),
				'modalButtons' => '<span class="tool btn btn-primary" onclick="$(\'#emailListForm\').submit();">Send Email</span>',
			];
		} else {
			return [
				'success' => false,
				'message' => 'You must provide the id of the list to email',
			];
		}
	}

	function renewCheckout() {
		if (isset($_REQUEST['patronId']) && isset($_REQUEST['recordId']) && isset($_REQUEST['renewIndicator'])) {
			if (strpos($_REQUEST['renewIndicator'], '|') > 0) {
				[
					$itemId,
					$itemIndex,
				] = explode('|', $_REQUEST['renewIndicator']);
			} else {
				$itemId = $_REQUEST['renewIndicator'];
				$itemIndex = null;
			}

			if (!UserAccount::isLoggedIn()) {
				$renewResults = [
					'success' => false,
					'message' => 'Not Logged in.',
				];
			} else {
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$recordId = $_REQUEST['recordId'];
				$patron = $user->getUserReferredTo($patronId);
				if ($patron) {
					$renewResults = $patron->renewCheckout($recordId, $itemId, $itemIndex);
				} else {
					$renewResults = [
						'success' => false,
						'message' => 'Sorry, it looks like you don\'t have access to that patron.',
					];
				}
			}
		} else {
			//error message
			$renewResults = [
				'success' => false,
				'message' => 'Item to renew not specified',
			];
		}
		global $interface;
		$interface->assign('renewResults', $renewResults);
		return [
			'title' => translate([
				'text' => 'Renew Item',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/renew-item-results.tpl'),
			'success' => $renewResults['success'],
		];
	}

	/** @noinspection PhpUnused */
	function renewSelectedItems() {
		if (!UserAccount::isLoggedIn()) {
			$renewResults = [
				'success' => false,
				'message' => 'Not Logged in.',
			];
		} else {
			if (isset($_REQUEST['selected'])) {
				$user = UserAccount::getLoggedInUser();
				if (method_exists($user, 'renewCheckout')) {
					$failure_messages = [];
					$renewResults = [];
					if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
						foreach ($_REQUEST['selected'] as $selected => $ignore) {
							//Suppress errors because sometimes we don't get an item index
							@list($patronId, $recordId, $itemId, $itemIndex) = explode('|', $selected);
							$patron = $user->getUserReferredTo($patronId);
							if ($patron) {
								$tmpResult = $patron->renewCheckout($recordId, $itemId, $itemIndex);
							} else {
								$tmpResult = [
									'success' => false,
									'message' => 'Sorry, it looks like you don\'t have access to that patron.',
								];
							}

							if (!$tmpResult['success']) {
								$failure_messages[] = $tmpResult['message'];
							}
						}
						$renewResults['Total'] = count($_REQUEST['selected']);
						$renewResults['NotRenewed'] = count($failure_messages);
						$renewResults['Renewed'] = $renewResults['Total'] - $renewResults['NotRenewed'];
					} else {
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
					$renewResults = [
						'success' => false,
						'message' => 'Cannot Renew Items - ILS Not Supported.',
					];
				}
			} else {
				//error message
				$renewResults = [
					'success' => false,
					'message' => 'Items to renew not specified.',
				];
			}
		}
		global $interface;
		$interface->assign('renew_message_data', $renewResults);

		return [
			'title' => translate([
				'text' => 'Renew Selected Items',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
			'renewed' => isset($renewResults['Renewed']) ? $renewResults['Renewed'] : [],
		];
	}

	function renewAll() {
		$renewResults = [
			'success' => false,
			'message' => ['Unable to renew all titles'],
		];
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			$renewResults = $user->renewAll(true);
		} else {
			$renewResults['message'] = ['You must be logged in to renew titles'];
		}

		global $interface;
		$interface->assign('renew_message_data', $renewResults);
		return [
			'title' => translate([
				'text' => 'Renew All',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
			'renewed' => $renewResults['Renewed'],
		];
	}

	/** @noinspection PhpUnused */
	function setListEntryPositions() {
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
		return ['success' => $success];
	}

	/** @noinspection PhpUnused */
	function getMenuDataIls() {
		global $timer;
		global $interface;

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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

				$searchEntry = new SearchEntry();
				$searchEntry->user_id = $user->id;
				$searchEntry->saved = 1;
				$searchEntry->hasNewResults = 1;
				$searchEntry->find();
				$ilsSummary->hasUpdatedSavedSearches = ($searchEntry->getNumResults() > 0);
				$ilsSummary->setNumUpdatedSearches($searchEntry->getNumResults());

				//Expiration and fines
				$interface->assign('ilsSummary', $ilsSummary);
				$interface->setFinesRelatedTemplateVariables();
				if ($interface->getVariable('expiredMessage')) {
					$interface->assign('expiredMessage', str_replace('%date%', date('M j, Y', $ilsSummary->expirationDate), $interface->getVariable('expiredMessage')));
				}
				if ($interface->getVariable('expirationNearMessage')) {
					$interface->assign('expirationNearMessage', str_replace('%date%', date('M j, Y', $ilsSummary->expirationDate), $interface->getVariable('expirationNearMessage')));
				}
				$ilsSummary->setExpirationNotice($interface->fetch('MyAccount/expirationNotice.tpl'));
				$ilsSummary->setFinesBadge($interface->fetch('MyAccount/finesBadge.tpl'));

				$result = [
					'success' => true,
					'summary' => $ilsSummary->toArray(),
				];
			} else {
				$result['message'] = translate([
					'text' => 'Unknown Error',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataCloudLibrary() {
		global $timer;
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
				$timer->logTime("Loaded cloudLibrary Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $cloudLibrarySummary->toArray(),
				];
			} else {
				$result['message'] = translate([
					'text' => 'Unknown Error',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataAxis360() {
		global $timer;
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
					'summary' => $axis360Summary->toArray(),
				];
			} else {
				$result['message'] = translate([
					'text' => 'Unknown Error',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataHoopla() {
		global $timer;
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
					'summary' => $hooplaSummary->toArray(),
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
	function getMenuDataOverdrive() {
		global $timer;
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
					'summary' => $overDriveSummary->toArray(),
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
	function getMenuDataInterlibraryLoan() {
		global $timer;
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			if ($user->hasInterlibraryLoan()) {
				require_once ROOT_DIR . '/Drivers/VdxDriver.php';
				$driver = new VdxDriver();
				$vdxSummary = $driver->getAccountSummary($user);
				if ($user->getLinkedUsers() != null) {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$vdxSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
					}
				}
				$timer->logTime("Loaded VDX Summary for User and linked users");
				$result = [
					'success' => true,
					'summary' => $vdxSummary->toArray(),
				];
			} else {
				$result['message'] = 'Invalid for VDX';
			}
		} else {
			$result['message'] = 'You must be logged in to get menu data';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getRatingsData() {
		global $interface;
		$result = [];
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$interface->assign('user', $user);

			//Count of ratings
			$result['ratings'] = $user->getNumRatings();
			$result['notInterested'] = $user->getNumNotInterested();
		}//User is not logged in

		return $result;
	}

	/** @noinspection PhpUnused */
	function getListData() {
		global $timer;
		global $interface;
		global $configArray;
		global $memCache;
		$result = [];
		if (UserAccount::isLoggedIn()) {
			//Load a list of lists
			$userListData = $memCache->get('user_list_data_' . UserAccount::getActiveUserId());
			if ($userListData == null || isset($_REQUEST['reload'])) {
				$lists = [];
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$tmpList = new UserList();
				$tmpList->user_id = UserAccount::getActiveUserId();
				$tmpList->whereAdd('deleted = 0');
				$tmpList->orderBy("title ASC");
				$tmpList->find();
				if ($tmpList->getNumResults() > 0) {
					while ($tmpList->fetch()) {
						$lists[$tmpList->id] = [
							'name' => $tmpList->title,
							'url' => '/MyAccount/MyList/' . $tmpList->id,
							'id' => $tmpList->id,
							'numTitles' => $tmpList->numValidListItems(),
						];
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
	public function exportCheckouts() {
		$source = $_REQUEST['source'];
		$user = UserAccount::getActiveUserObj();
		$allCheckedOut = $user->getCheckouts(true, $source);
		$selectedSortOption = $this->setSort('sort', 'checkout');
		if ($selectedSortOption == null) {
			$selectedSortOption = 'dueDate';
		}
		$allCheckedOut = $this->sortCheckouts($selectedSortOption, $allCheckedOut);

		$hasLinkedUsers = count($user->getLinkedUsers()) > 0;

		$showOut = $user->showOutDateInCheckouts();
		$showRenewed = $user->showTimesRenewed();
		$showRenewalsRemaining = $user->showRenewalsRemaining();
		$showWaitList = $user->showWaitListInCheckouts();


		try {
			// Redirect output to a client's web browser
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="CheckedOutItems.csv"');
			header('Cache-Control: max-age=0');
			$fp = fopen('php://output', 'w');

			$fields = ['Title', 'Author', 'Format'];
			if ($showOut) {
				$fields[] = 'Out';
			}
			$fields[] = 'Due';
			if ($showRenewed){
				$fields[] = 'Renewed';
			}
			if ($showWaitList){
				$fields[] = 'Wait List';
			}
			if ($hasLinkedUsers){
				$fields[] = 'User';
			}

			fputcsv($fp, $fields);

			//Loop Through The Report Data
			/** @var Checkout $row */
			foreach ($allCheckedOut as $row) {
				$titleCell = preg_replace("~([/:])$~", "", $row->title);
				if (!empty($row->title2)) {
					$titleCell .= preg_replace("~([/:])$~", "", $row->title2);
				}
				$title = $titleCell;

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
				$author = $authorCell;

				if (isset($row->format)) {
					if (is_array($row->format)) {
						$formatString = implode(', ', $row->format);
					} else {
						$formatString = $row->format;
					}
				} else {
					$formatString = '';
				}
				$format = $formatString;

				$checkoutDate = '';
				if ($showOut) {
					$checkoutDate = date('M d, Y', $row->checkoutDate);
				}

				if (isset($row->dueDate)) {
					$dueDate = date('M d, Y', $row->dueDate);
				} else {
					$dueDate = '';
				}

				$Renewed = '';
				if ($showRenewed) {
					if (isset($row->dueDate)) {
						$Renewed = $row->renewCount;
					}
				}

				$waitList = '';
				if ($showWaitList) {
					$waitList = $row->holdQueueLength;
				}

				$userName = '';
				if ($hasLinkedUsers) {
					$userName = $row->getUserName();
				}


				$row = array ($title, $author, $format);
				if ($showOut) {
					$row[] = $checkoutDate;
				}
				$row[] = $dueDate;
				if ($showRenewed){
					$row[] =$Renewed;
				}
				if ($showWaitList){
					$row[] =$waitList;
				}
				if ($hasLinkedUsers){
					$row[] =$userName;
				}
				fputcsv($fp, $row);
			}

		} catch (Exception $e) {
			global $logger;
			$logger->log("Error exporting to csv " . $e, Logger::LOG_ERROR);
		}
		exit;
	}

	/** @noinspection PhpUnused */
	public function exportHolds() {
		$source = $_REQUEST['source'];
		$user = UserAccount::getActiveUserObj();

		$showPosition = $user->showHoldPosition();
		$showExpireTime = $user->showHoldExpirationTime();
		$selectedAvailableSortOption = $this->setSort('availableHoldSort', 'availableHold');
		$selectedUnavailableSortOption = $this->setSort('unavailableHoldSort', 'unavailableHold');
		if ($selectedAvailableSortOption == null) {
			$selectedAvailableSortOption = 'expire';
		}
		if ($selectedUnavailableSortOption == null) {
			$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
		}

		$allHolds = $user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, $source);

		$showDateWhenSuspending = $user->showDateWhenSuspending();

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="Holds.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');


		$hasLinkedUsers = count($user->getLinkedUsers()) > 0;
		try {
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
					// Section header
					$holdType = translate([
						'text' => 'Holds - ' . ucfirst($exportType),
						'isPublicFacing' => true,
					]);
					$header = array($holdType);
					fputcsv($fp, $header);

					// Column names
					$titleCol = translate([
						'text' => 'Title',
						'isPublicFacing' => true,
					]);
					$authorCol = translate([
						'text' => 'Author',
						'isPublicFacing' => true,
					]);
					$formatCol = translate([
						'text' => 'Format',
						'isPublicFacing' => true,
					]);
					$placedCol = translate([
						'text' => 'Placed',
						'isPublicFacing' => true,
					]);
					$pickupCol = translate([
						'text' => 'Pickup',
						'isPublicFacing' => true,
					]);
					$statusCol = translate([
						'text' => 'Available',
						'isPublicFacing' => true,
					]);
					$pickupByCol = translate([
						'text' => 'Pickup By',
						'isPublicFacing' => true,
					]);
					$userCol = translate([
						'text' => 'User',
						'isPublicFacing' => true,
					]);

					$availFields = [$titleCol, $authorCol, $formatCol, $placedCol, $pickupCol, $statusCol, $pickupByCol];
					if ($hasLinkedUsers){
						$availFields[] = $userCol;
					}
					fputcsv($fp, $availFields);

					foreach ($allHolds['available'] as $row) {
						$title = preg_replace("~([/:])$~", "", $row->title);
						if (isset ($row->title2)) {
							$title .= preg_replace("~([/:])$~", "", $row->title2);
						}

						if (isset ($row->author)) {
							if (is_array($row->author)) {
								$author = implode(',', $row->author);
							} else {
								$author = $row->author;
							}
							$author = str_replace('&nbsp;', ' ', $author);
						} else {
							$author = '';
						}

						if (isset($row->format)) {
							if (is_array($row->format)) {
								$format = implode(', ', $row->format);
							} else {
								$format = $row->format;
							}
						} else {
							$format = '';
						}

						if (empty($row->createDate)) {
							$placed = '';
						} else {
							if (is_array($row->createDate)) {
								$placed = new DateTime();
								$placed->setDate($row->createDate['year'], $row->createDate['month'], $row->createDate['day']);
								$placed = $placed->format('M d, Y');
							} else {
								$placed = $this->isValidTimeStamp($row->createDate) ? $row->createDate : strtotime($row->createDate);
								$placed = date('M d, Y', $placed);
							}
						}
						$pickup = $row->pickupLocationName ?? '';

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

						if (empty($row->availableDate)) {
							$status = 'Now';
						} else {
							$status = $this->isValidTimeStamp($row->availableDate) ? $row->availableDate : strtotime($row->availableDate);
							$status = date('M d, Y', $status);
						}

						$user = $row->getUserName();

						$availValues = [$title, $author, $format, $placed, $pickup, $status, $expireDate, $user];
						if ($hasLinkedUsers){
							$availValues[] = $user;
						}
						fputcsv($fp, $availValues);
					}
				}
				else {
					// Section header
					$holdType = translate([
						'text' => 'Holds - ' . ucfirst($exportType),
						'isPublicFacing' => true,
					]);
					$header = array($holdType);
					fputcsv($fp, $header);
					// Col names
					$titleCol = translate([
						'text' => 'Title',
						'isPublicFacing' => true,
					]);
					$authorCol = translate([
						'text' => 'Author',
						'isPublicFacing' => true,
					]);
					$formatCol = translate([
						'text' => 'Format',
						'isPublicFacing' => true,
					]);
					$placedCol = translate([
						'text' => 'Placed',
						'isPublicFacing' => true,
					]);
					$pickupCol = translate([
						'text' => 'Pickup',
						'isPublicFacing' => true,
					]);
					$positionCol = translate([
						'text' => 'Position',
						'isPublicFacing' => true,
					]);
					$statusCol = translate([
						'text' => 'Status',
						'isPublicFacing' => true,
					]);
					$userCol = translate([
						'text' => 'User',
						'isPublicFacing' => true,
					]);

					$unavailFields = [$titleCol, $authorCol, $formatCol, $placedCol, $pickupCol];
					if ($showPosition){
						$unavailFields[] = $positionCol;
					}
					$unavailFields[] = $statusCol;
					if ($hasLinkedUsers){
						$unavailFields[] = $userCol;
					}
					fputcsv($fp, $unavailFields);

					foreach ($allHolds['unavailable'] as $row) {
						$title = preg_replace("~([/:])$~", "", $row->title);
						if (isset ($row->title2)) {
							$title .= preg_replace("~([/:])$~", "", $row->title2);
						}

						if (isset ($row->author)) {
							if (is_array($row->author)) {
								$author = implode(', ', $row->author);
							} else {
								$author = $row->author;
							}
							$author = str_replace('&nbsp;', ' ', $author);
						} else {
							$author = '';
						}
						if (isset($row->format)) {
							if (is_array($row->format)) {
								$format = implode(', ', $row->format);
							} else {
								$format = $row->format;
							}
						} else {
							$format = '';
						}
						if (empty($row->createDate)) {
							$placed= '';
						} else {
							if (is_array($row->createDate)) {
								$placed = new DateTime();
								$placed->setDate($row->createDate['year'], $row->createDate['month'], $row->createDate['day']);
								$placed = $placed->format('M d, Y');
							} else {
								$placed = $this->isValidTimeStamp($row->createDate) ? $row->createDate : strtotime($row->createDate);
								$placed = date('M d, Y', $placed);
							}
						}

						$pickup = $row->pickupLocationName ?? '';

						$status = $row->status ?? '';

						if (isset($row->frozen) && $row->frozen && $showDateWhenSuspending && !empty($row->reactivateDate)) {
							$reactivateTime = $this->isValidTimeStamp($row->reactivateDate) ? $row->reactivateDate : strtotime($row->reactivateDate);
							$status .= " until " . date('M d, Y', $reactivateTime);
						}

						$position = $row->position ?? '';

						$user = $row->getUserName();

						$unavailValues = [$title, $author, $format, $placed, $pickup];
						if ($showPosition){
							$unavailValues[] = $position;
						}
						$unavailValues[] = $status;
						if ($hasLinkedUsers){
							$unavailValues[] = $user;
						}
						fputcsv($fp, $unavailValues);
					}
				}
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error exporting to csv " . $e, Logger::LOG_ERROR);
		}
		exit;
	}

	/** @noinspection PhpUnused */
	public function exportReadingHistory() {
		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$selectedSortOption = $this->setSort('sort', 'readingHistory');
			if ($selectedSortOption == null) {
				$selectedSortOption = 'checkedOut';
			}
			$readingHistory = $user->getReadingHistory(1, -1, $selectedSortOption, '', true);

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="ReadingHistory.csv"');
			header('Cache-Control: max-age=0');
			$fp = fopen('php://output', 'w');

			try {
				// Set properties
				$fields = array('Title', 'Author', 'Format', 'Last used');
				fputcsv($fp, $fields);

				//Loop Through The Report Data
				foreach ($readingHistory['titles'] as $row) {

					$title = $row['title'];
					$author = $row['author'];
					$format = is_array($row['format']) ? implode(',', $row['format']) : $row['format'];
					if ($row['checkedOut']) {
						$lastCheckout = translate([
							'text' => 'In Use',
							'isPublicFacing' => true,
						]);
					} else {
						if (is_numeric($row['checkout'])) {
							$lastCheckout = date('M Y', $row['checkout']);
						} else {
							$lastCheckout = $row['checkout'];
						}
					}
					$results = array ($title, $author, $format, $lastCheckout);
					fputcsv($fp, $results);
				}
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error exporting to csv " . $e, Logger::LOG_ERROR);
			}
		}
		exit;
	}

	/** @noinspection PhpUnused */
	public function getCheckouts(): array {
		global $interface;

		$renewableCheckouts = 0;

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		global $offlineMode;
		if (!UserAccount::isLoggedIn()) {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'Your session has ended, please login to view checkouts.',
					'isPublicFacing' => true,
				]),
			];
		}else if (!$offlineMode || $interface->getVariable('enableEContentWhileOffline')) {
			$source = $_REQUEST['source'];
			$interface->assign('source', $source);
			$this->setShowCovers();

			//Determine which columns to show
			$user = UserAccount::getActiveUserObj();
			$showOut = $user->showOutDateInCheckouts();
			$showRenewed = $user->showTimesRenewed();
			$showRenewalsRemaining = $user->showRenewalsRemaining();
			$showWaitList = $user->showWaitListInCheckouts();

			$interface->assign('showOut', $showOut);
			$interface->assign('showRenewed', $showRenewed);
			$interface->assign('showRenewalsRemaining', $showRenewalsRemaining);
			$interface->assign('showWaitList', $showWaitList);

			// Define sorting options
			$sortOptions = [
				'title' => 'Title',
				'author' => 'Author',
				'dueDate' => 'Due Date Asc',
				'dueDateDesc' => 'Due Date Desc',
				'format' => 'Format',
			];
			$user = UserAccount::getActiveUserObj();
			if (UserAccount::isLoggedIn() == false || empty($user)) {
				$result['message'] = translate([
					'text' => "Your login has timed out. Please login again.",
					'isPublicFacing' => true,
				]);
			} else {
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
			$result['message'] = translate([
				'text' => 'The catalog is offline',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function getHolds(): array {
		global $interface;

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		global $offlineMode;
		if (!$offlineMode || $interface->getVariable('enableEContentWhileOffline')) {
			global $configArray;
			global $library;

			$source = $_REQUEST['source'];
			$interface->assign('source', $source);
			$this->setShowCovers();
			$selectedAvailableSortOption = $this->setSort('availableHoldSort', 'availableHold');
			$selectedUnavailableSortOption = $this->setSort('unavailableHoldSort', 'unavailableHold');

			$user = UserAccount::getActiveUserObj();
			if (UserAccount::isLoggedIn() == false || empty($user)) {
				$result['message'] = translate([
					'text' => "Your login has timed out. Please login again.",
					'isPublicFacing' => true,
				]);
			} else {
				if ($source != 'interlibrary_loan') {
					if ($user->getHomeLibrary() != null) {
						$allowFreezeHolds = $user->getHomeLibrary()->allowFreezeHolds;
					} else {
						$allowFreezeHolds = $library->allowFreezeHolds;
					}
					if ($allowFreezeHolds) {
						$interface->assign('allowFreezeAllHolds', true);
					} else {
						$interface->assign('allowFreezeAllHolds', false);
					}
					$interface->assign('allowFreezeHolds', true);
				} else {
					$interface->assign('allowFreezeAllHolds', false);
					$interface->assign('allowFreezeHolds', false);
				}

				$showPosition = $user->showHoldPosition();
				$suspendRequiresReactivationDate = $user->suspendRequiresReactivationDate();
				$interface->assign('suspendRequiresReactivationDate', $suspendRequiresReactivationDate);
				$showPlacedColumn = $user->showHoldPlacedDate();
				$interface->assign('showPlacedColumn', $showPlacedColumn);

				$location = new Location();
				$pickupBranches = $location->getPickupBranches($user);
				$interface->assign('numPickupBranches', count($pickupBranches));

				// Define sorting options
				$unavailableHoldSortOptions = [
					'title' => 'Title',
					'author' => 'Author',
					'format' => 'Format',
				];
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

				$availableHoldSortOptions = [
					'title' => 'Title',
					'author' => 'Author',
					'format' => 'Format',
					'expire' => 'Expiration Date',
					'placed' => 'Date Placed',
				];
				if ($source == 'all' || $source == 'ils') {
					$availableHoldSortOptions['location'] = 'Pickup Location';
				}

				if (count($user->getLinkedUsers()) > 0) {
					$unavailableHoldSortOptions['libraryAccount'] = 'Library Account';
					$availableHoldSortOptions['libraryAccount'] = 'Library Account';
				}

				$interface->assign('sortOptions', [
					'available' => $availableHoldSortOptions,
					'unavailable' => $unavailableHoldSortOptions,
				]);

				if ($selectedAvailableSortOption == null || !array_key_exists($selectedAvailableSortOption, $availableHoldSortOptions)) {
					$selectedAvailableSortOption = 'expire';
				}
				if ($selectedUnavailableSortOption == null || !array_key_exists($selectedUnavailableSortOption, $unavailableHoldSortOptions)) {
					$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
				}
				$interface->assign('defaultSortOption', [
					'available' => $selectedAvailableSortOption,
					'unavailable' => $selectedUnavailableSortOption,
				]);

				$showDateWhenSuspending = $user->showDateWhenSuspending();
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

				$notification_method = ($user->_noticePreferenceLabel != 'Unknown') ? $user->_noticePreferenceLabel : '';
				$interface->assign('notification_method', strtolower($notification_method));
				$interface->assign('userId', $user->id);

				$result['success'] = true;
				$result['message'] = "";
				$result['holdInfoLastLoaded'] = $user->getFormattedHoldInfoLastLoaded();
				$result['holds'] = $interface->fetch('MyAccount/holdsList.tpl');
			}
		} else {
			$result['message'] = translate([
				'text' => 'The catalog is offline',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function getSavedEvents()
	{
		global $interface;
		global $timer;

		//Load user ratings
		require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';

		$page = $_REQUEST['page'] ?? 1;
		$interface->assign('page', $page);
		$pageSize = $_REQUEST['pageSize'] ?? 20;

		$eventsFilter = $_REQUEST['eventsFilter'] ?? 'upcoming';
		$curTime = time();

		$user = UserAccount::getActiveUserObj();
		$numSaved = $user->getNumSavedEvents($eventsFilter);
		$event = new UserEventsEntry();
		$event->userId = UserAccount::getActiveUserId();
		if ($eventsFilter == 'past') {
			$event->whereAdd("eventDate < $curTime");
			$event->orderBy('eventDate DESC');
		}
		if ($eventsFilter == 'upcoming') {
			$event->whereAdd("eventDate >= $curTime");
			$event->orderBy('eventDate ASC');
		}
		if ($eventsFilter == 'all'){
			$event->orderBy('eventDate DESC');

		}
		$event->limit(($page - 1) * $pageSize, $pageSize);
		$event->find();
		$events = [];
		$eventIds = [];
		while ($event->fetch()) {
			if (!array_key_exists($event->sourceId, $eventIds)) {
				$eventIds[$event->sourceId] = clone $event;
			}
		}
		$timer->logTime("Loaded events the user has saved");

		/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject("Events");
		$eventRecords = $searchObject->getRecords(array_keys($eventIds));

		foreach ($eventIds as $curEventId => $entry) {
			$registration = UserAccount::getActiveUserObj()->isRegistered($entry->sourceId);
			if (array_key_exists($curEventId, $eventRecords)) {
				$eventRecordDriver = $eventRecords[$curEventId];
				$events[$entry->sourceId] = [
					'id' => $entry->id,
					'sourceId' => $entry->sourceId,
					'title' => $entry->title,
					'link' => $eventRecordDriver->getLinkUrl(),
					'externalLink' => $eventRecordDriver->getExternalUrl(),
					'regModalBody' => $eventRecordDriver->getRegistrationModalBody(),
					'location' => $entry->location,
					'regRequired' => $entry->regRequired,
					'isRegistered' => $registration,
					'eventDate' => $entry->eventDate,
					'pastEvent' => false,
				];
			} else {
				$events[$entry->sourceId] = [
				'id' => $entry->id,
				'sourceId' => $entry->sourceId,
				'title' => $entry->title,
				'link' => null,
				'externalLink' => null,
				'location' => $entry->location,
				'regRequired' => $entry->regRequired,
				'isRegistered' => $registration,
				'eventDate' => $entry->eventDate,
				'pastEvent' => true,
			];
		}
	}

		$filter = isset($_REQUEST['eventsFilter']) ? $_REQUEST['eventsFilter'] : '';
		$interface->assign('eventsFilter', $filter);

		// Process Paging
		$options = [
			'perPage' => $pageSize,
			'totalItems' => $numSaved,
			'append' => false,
			'filter' => urlencode($filter),
			'fileName' => "/MyAccount/MyEvents?page=%d&eventsFilter=$eventsFilter",
		];

		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());
		$interface->assign('events', $events);

		$result['success'] = true;
		$result['message'] = "";
		$result['myEvents']= $interface->fetch('MyAccount/myEventsList.tpl');

		return $result;
	}

	public function getReadingHistory() {
		global $interface;
		$showCovers = $this->setShowCovers();

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
			$sortOptions = [
				'title' => translate([
					'text' => 'Title',
					'isPublicFacing' => true,
				]),
				'author' => translate([
					'text' => 'Author',
					'isPublicFacing' => true,
				]),
				'checkedOut' => translate([
					'text' => 'Last Used',
					'isPublicFacing' => true,
				]),
				'format' => translate([
					'text' => 'Format',
					'isPublicFacing' => true,
				]),
			];
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
			} else {
				if (strpos($link, "?") > 0) {
					$link .= "&page=%d";
				} else {
					$link .= "?page=%d";
				}
			}
			if ($recordsPerPage != '-1') {
				$options = [
					'totalItems' => $result['numTitles'],
					'fileName' => $link,
					'perPage' => $recordsPerPage,
					'append' => false,
					'linkRenderingObject' => $this,
					'linkRenderingFunction' => 'renderReadingHistoryPaginationLink',
					'patronId' => $patronId,
					'sort' => $selectedSortOption,
					'showCovers' => $showCovers,
					'filter' => urlencode($filter),
				];
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

	function renderReadingHistoryPaginationLink($page, $options) {
		return "<a class='page-link btn btn-default btn-sm' onclick='AspenDiscovery.Account.loadReadingHistory(\"{$options['patronId']}\", \"{$options['sort']}\", \"{$page}\", undefined, \"{$options['filter']}\");AspenDiscovery.goToAnchor(\"topOfList\")'>";
	}

	private function isValidTimeStamp($timestamp) {
		return is_numeric($timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
	}

	function setShowCovers() {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			if (isset($_SESSION)) {
				$_SESSION['showCovers'] = $showCovers;
			}
		} elseif (isset($_SESSION['showCovers'])) {
			$showCovers = $_SESSION['showCovers'];
		}
		$interface->assign('showCovers', $showCovers);
		return $showCovers;
	}

	function setSort($requestParameter, $sortType) {
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$sort = null;
		if (isset($_REQUEST[$requestParameter])) {
			$sort = $_REQUEST[$requestParameter];
			if (isset($_SESSION)) {
				$_SESSION['sort_' . $sortType] = $sort;
			}
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
	private function sortCheckouts(string $selectedSortOption, array $allCheckedOut): array {
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
				} else {
					//Always put things where the due date isn't set last.
					if ($selectedSortOption == 'dueDate') {
						$sortKey = '9999999999-' . $sortTitle;
					} else {
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

	function deleteReadingHistoryEntry() {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
	function deleteReadingHistoryEntryByTitleAuthor() {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
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
	function dismissMessage() {
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		if (!isset($_REQUEST['messageId'])) {
			return [
				'success' => false,
				'message' => 'Message Id not provided',
			];
		} else {
			if (UserAccount::getActiveUserId() == false) {
				return [
					'success' => false,
					'message' => 'User is not logged in',
				];
			} else {
				$message = new UserMessage();
				$message->id = $_REQUEST['messageId'];
				if ($message->find(true)) {
					if ($message->userId != UserAccount::getActiveUserId()) {
						return [
							'success' => false,
							'message' => 'Message is not for the active user',
						];
					} else {
						$message->isDismissed = 1;
						$message->update();
						return [
							'success' => true,
							'message' => 'Message was dismissed',
						];
					}
				} else {
					return [
						'success' => false,
						'message' => 'Could not find the message to dismiss',
					];
				}
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissSystemMessage() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
		if (!isset($_REQUEST['messageId'])) {
			return [
				'success' => false,
				'message' => 'Message Id not provided',
			];
		} else {
			if (UserAccount::getActiveUserId() == false) {
				return [
					'success' => false,
					'message' => 'User is not logged in',
				];
			} else {
				$message = new SystemMessage();
				$message->id = $_REQUEST['messageId'];
				if ($message->find(true)) {
					require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
					$systemMessageDismissal = new SystemMessageDismissal();
					$systemMessageDismissal->userId = UserAccount::getActiveUserId();
					$systemMessageDismissal->systemMessageId = $message->id;
					if ($systemMessageDismissal->find(true)) {
						return [
							'success' => true,
							'message' => 'Message was already dismissed',
						];
					} else {
						$systemMessageDismissal->insert();
						return [
							'success' => true,
							'message' => 'Message was dismissed',
						];
					}
				} else {
					return [
						'success' => false,
						'message' => 'Could not find the message to dismiss',
					];
				}
			}
		}
	}

	/** @noinspection PhpUnused */
	function createGenericDonation($paymentType = '') {
		$transactionDate = time();
		$user = UserAccount::getLoggedInUser();

		global $library;
		$paymentLibrary = $library;

		$patronId = $_REQUEST['patronId'];
		$currencyCode = 'USD'; // set a default, check system variables later

		// if logged in validate the user
		if ($patronId != 'Guest') {
			if ($user->getUserReferredTo($patronId)) {
				$patron = $user->getUserReferredTo($patronId);
				$userLibrary = $patron->getHomeLibrary();
			} else {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Could not find the patron referred to, please try again.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			$patron = null;
			$patronId = null;
			$userLibrary = $library;
		}

		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->find(true)) {
			$currencyCode = $systemVariables->currencyCode;
		}

		$donateToLibrary = $_REQUEST['toLocation'];
		$toLocation = 'None';
		if($donateToLibrary) {
			require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
			$location = new Location();
			$location->displayName = $donateToLibrary;
			if ($location->find(true)) {
				$toLocation = $location->locationId;
			}
		} else {
			$donateToLibrary = 'None';
		}

		$earmarkId = $_REQUEST['earmark'] ?? null;
		$comments = 'None';
		if($earmarkId) {
			require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
			$earmark = new DonationEarmark();
			$earmark->id = $earmarkId;
			if ($earmark->find(true)) {
				$comments = $earmark->label;
			}
		}

		// check for a minimum value to donate
		// for now we will use minimumFineAmount and decide later if donations should be separate
		$minimumAmountToProcess = $paymentLibrary->minimumFineAmount;
		$setupCurrencyFormat = numfmt_create($currencyCode, NumberFormatter::CURRENCY);
		$currencyFormat = numfmt_format_currency($setupCurrencyFormat, $minimumAmountToProcess, $currencyCode);

		// check for good values
		if (empty($_REQUEST['amount']) || empty($_REQUEST['emailAddress']) || empty($_REQUEST['firstName']) || empty($_REQUEST['lastName']) || (isset($_REQUEST['amount'])) && ($_REQUEST['amount'] < $minimumAmountToProcess)) {
			$message = null;
			if (!empty($_REQUEST['amount']) && $_REQUEST['amount'] < $minimumAmountToProcess) {
				$thisAmount = numfmt_format_currency($setupCurrencyFormat, $_REQUEST['amount'], $currencyCode);
				$message .= "<div class='alert alert-danger'><p><b>The minimum value for donating online is $currencyFormat, but you entered $thisAmount</b>.</p></div>";
			}

			$message .= "<div class='alert alert-danger'><p><b>The following fields were left blank or contain invalid values</b></p>";
			$message .= "<ul>";
			if (empty($_REQUEST['amount'])) {
				$message .= "<li>A valid amount value to donate</li>";
			}

			if (empty($_REQUEST['emailAddress'])) {
				$message .= "<li>Your email address</li>";
			}
			if (empty($_REQUEST['firstName'])) {
				$message .= "<li>Your first name</li>";
			}
			if (empty($_REQUEST['lastName'])) {
				$message .= "<li>Your last name</li>";
			}

			if (isset($_REQUEST['isDedicated']) && ($_REQUEST['isDedicated'] == "on")) {
				if (empty($_REQUEST['dedicationType'])) {
					$message .= "<li>The type of dedication you'd like</li>";
				}
				if (empty($_REQUEST['honoreeFirstName'])) {
					$message .= "<li>A first name for the dedication</li>";
				}
				if (empty($_REQUEST['honoreeLastName'])) {
					$message .= "<li>A last name for the dedication</li>";
				}
			}

			if (isset($_REQUEST['shouldBeNotified']) && ($_REQUEST['shouldBeNotified'] == "on")) {
				if (empty($_REQUEST['notificationFirstName'])) {
					$message .= "<li>A first name for the notification party</li>";
				}
				if (empty($_REQUEST['notificationLastName'])) {
					$message .= '<li>A last name for the notification party</li>';
				}
				if (empty($_REQUEST['notificationAddress'])) {
					$message .= '<li>Address to send notification to</li>';
				}
				if (empty($_REQUEST['notificationCity'])) {
					$message .= '<li>City to send notification to</li>';
				}
				if (empty($_REQUEST['notificationState'])) {
					$message .= '<li>State to send notification to</li>';
				}
				if (empty($_REQUEST['notificationZip'])) {
					$message .= '<li>Zip Code to send notification to</li>';
				}
			}

			$message .= "</ul></div>";
			return [
				'success' => false,
				'message' => $message,
				'isPublicFacing' => true,
			];
		}

		$donationValue = $_REQUEST['amount'];

		// prep donation for processor
		$purchaseUnits['items'][] = [
			'custom_id' => $paymentLibrary->subdomain,
			'name' => 'Donation to Library',
			'description' => 'Donation to ' . $library->displayName . ' for ' . numfmt_format_currency($setupCurrencyFormat, $donationValue, $currencyCode),
			'unit_amount' => [
				'currency_code' => $currencyCode,
				'value' => round($donationValue, 2),
			],
			'quantity' => 1,
		];

		$purchaseUnits['amount'] = [
			'currency_code' => $currencyCode,
			'value' => round($donationValue, 2),
			'breakdown' => [
				'item_total' => [
					'currency_code' => $currencyCode,
					'value' => round($donationValue, 2),
				],
			],
		];

		$tempDonation = [
			'firstName' => $_REQUEST['firstName'],
			'lastName' => $_REQUEST['lastName'],
			'email' => $_REQUEST['emailAddress'],
			'isAnonymous' => isset($_REQUEST['isAnonymous']) ? 1 : 0,
			'donateToLibraryId' => $toLocation,
			'donateToLibrary' => $donateToLibrary,
			'isDedicated' => isset($_REQUEST['isDedicated']) ? 1 : 0,
			'shouldBeNotified' => isset($_REQUEST['shouldBeNotified']) ? 1 : 0,
			'comments' => $comments,
			'donationSettingId' => $_REQUEST['settingId'],
		];

		if ($tempDonation['isDedicated'] == 1) {
			$tempDonation['dedication'] = [
				'type' => $_REQUEST['dedicationType'],
				'honoreeFirstName' => $_REQUEST['honoreeFirstName'],
				'honoreeLastName' => $_REQUEST['honoreeLastName'],
			];
		}

		if($tempDonation['shouldBeNotified'] == 1) {
			$tempDonation['notification'] = [
				'notificationFirstName' => $_REQUEST['notificationFirstName'],
				'notificationLastName' => $_REQUEST['notificationLastName'],
				'notificationAddress' => $_REQUEST['notificationAddress'],
				'notificationCity' => $_REQUEST['notificationCity'],
				'notificationState' => $_REQUEST['notificationState'],
				'notificationZip' => $_REQUEST['notificationZip'],
			];
		}

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$payment = new UserPayment();
		$payment->userId = $patronId;
		$payment->completed = 0;
		$payment->finesPaid = null;
		$payment->totalPaid = $donationValue;
		$payment->paymentType = $paymentType;
		$payment->transactionDate = $transactionDate;
		$payment->transactionType = "donation";
		global $interface;
		$payment->requestingUrl = $interface->getVariable('url');
		global $library;
		$payment->paidFromInstance = $library->subdomain;

		if (isset($_REQUEST['token'])) {
			$payment->aciToken = $_REQUEST['token'];
		}

		$paymentId = $payment->insert();
		$purchaseUnits['custom_id'] = $paymentLibrary->subdomain;

		return [
			$paymentLibrary,
			$userLibrary,
			$payment,
			$purchaseUnits,
			$patron,
			$tempDonation,
		];

	}

	/** @noinspection PhpUnused */
	function addDonation($payment, $tempDonation) {
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		$donation = new Donation();
		$donation->paymentId = $payment->id;
		$donation->firstName = $tempDonation['firstName'];
		$donation->lastName = $tempDonation['lastName'];
		$donation->email = $tempDonation['email'];
		$donation->anonymous = $tempDonation['isAnonymous'];
		$donation->dedicate = $tempDonation['isDedicated'];
		if ($tempDonation['isDedicated'] == 1) {
			$donation->dedicateType = $tempDonation['dedication']['type'];
			$donation->honoreeFirstName = $tempDonation['dedication']['honoreeFirstName'];
			$donation->honoreeLastName = $tempDonation['dedication']['honoreeLastName'];
		}
		$donation->shouldBeNotified = $tempDonation['shouldBeNotified'];
		if($tempDonation['shouldBeNotified'] == 1) {
			$donation->notificationFirstName = $tempDonation['notification']['notificationFirstName'];
			$donation->notificationLastName = $tempDonation['notification']['notificationLastName'];
			$donation->notificationAddress = $tempDonation['notification']['notificationAddress'];
			$donation->notificationCity = $tempDonation['notification']['notificationCity'];
			$donation->notificationState = $tempDonation['notification']['notificationState'];
			$donation->notificationZip = $tempDonation['notification']['notificationZip'];
		}
		$donation->donateToLibraryId = $tempDonation['donateToLibraryId'];
		$donation->donateToLibrary = $tempDonation['donateToLibrary'];
		$donation->comments = $tempDonation['comments'];
		$donation->donationSettingId = $tempDonation['donationSettingId'];
		$donation->sendEmailToUser = 1;
		$donation->insert();

		return $donation;
	}

	/** @noinspection PhpUnused */
	function createGenericOrder($paymentType = '') {
		$transactionDate = time();
		$user = UserAccount::getLoggedInUser();
		if ($user == null) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must be signed in to pay fines, please sign in.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			$patronId = $_REQUEST['patronId'];

			$patron = $user->getUserReferredTo($patronId);

			if ($patron == false) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Could not find the patron referred to, please try again.',
						'isPublicFacing' => true,
					]),
				];
			}
			$userLibrary = $patron->getHomeLibrary();

			global $library;
			$paymentLibrary = $library;
			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables->libraryToUseForPayments == 0) {
				$paymentLibrary = $userLibrary;
			}

			if (empty($_REQUEST['selectedFine']) && $paymentLibrary->finesToPay != 0) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Select at least one fine to pay.',
						'isPublicFacing' => true,
					]),
				];
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
				if ($paymentLibrary->finesToPay == 0) {
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
							return [
								'success' => false,
								'message' => translate([
									'text' => 'Invalid amount entered for fine. Please enter an amount over 0 and less than the total amount owed.',
									'isPublicFacing' => true,
								]),
							];
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

					$name = StringUtils::trimStringToLengthAtWordBoundary($fine['reason'], 120, true);
					if (empty($name)) {
						$name = StringUtils::trimStringToLengthAtWordBoundary($fine['message'], 120, true);
					}
					$purchaseUnits['items'][] = [
						'custom_id' => $paymentLibrary->subdomain,
						'name' => $name,
						'description' => StringUtils::trimStringToLengthAtWordBoundary($fine['message'], 120, true),
						'unit_amount' => [
							'currency_code' => $currencyCode,
							'value' => round($fineAmount, 2),
						],
						'quantity' => 1,
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
			if (!empty($paymentLibrary->finePaymentOrder)) {
				$paymentOrder = explode('|', strtolower($paymentLibrary->finePaymentOrder));

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
							return [
								'success' => false,
								'message' => translate([
									'text' => 'You must pay all fines of type <strong>%1%</strong> before paying other types.',
									1 => $lastPaymentType,
									'isPublicFacing' => true,
								]),
							];
						}
					}
				}
			}

			if ($totalFines < $paymentLibrary->minimumFineAmount) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'You must select at least %1% in fines to pay.',
						1 => sprintf('$%01.2f', $paymentLibrary->minimumFineAmount),
						'isPublicFacing' => true,
					]),
				];
			}

			$purchaseUnits['amount'] = [
				'currency_code' => $currencyCode,
				'value' => round($totalFines, 2),
				'breakdown' => [
					'item_total' => [
						'currency_code' => $currencyCode,
						'value' => round($totalFines, 2),
					],
				],
			];

			if ($totalFines < $paymentLibrary->minimumFineAmount) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'You must select at least %1% in fines to pay.',
						1 => sprintf('$%01.2f', $paymentLibrary->minimumFineAmount),
						'isPublicFacing' => true,
					]),
				];
			}

			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->userId = $patronId;
			$payment->completed = 0;
			$payment->finesPaid = $finesPaid;
			$payment->totalPaid = $totalFines;
			$payment->paymentType = $paymentType;
			$payment->transactionDate = $transactionDate;
			$payment->transactionType = "fine";
			global $interface;
			$payment->requestingUrl = $interface->getVariable('url');

			global $library;
			$payment->paidFromInstance = $library->subdomain;

			if (isset($_REQUEST['token'])) {
				if($paymentType == 'ACI') {
					$payment->aciToken = $_REQUEST['token'];
				}
				if($paymentType == 'deluxe') {
					$payment->deluxeRemittanceId = $_REQUEST['token'];
				}
				if($paymentType == 'square') {
					$payment->squareToken = $_REQUEST['token'];
				}
			}

			$paymentId = $payment->insert();
			$purchaseUnits['custom_id'] = $paymentLibrary->subdomain;


			return [
				$paymentLibrary,
				$userLibrary,
				$payment,
				$purchaseUnits,
				$patron,
			];
		}
	}

	/** @noinspection PhpUnused */
	function createPayPalOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('paypal');
		} else {
			$result = $this->createGenericOrder('paypal');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			/** @var Library $paymentLibrary */ /** @var Library $userLibrary */ /** @var UserPayment $payment */
			/** @var User $patron */
			if ($transactionType == 'donation') {
				/** @noinspection PhpUnusedLocalVariableInspection */
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
			} else {
				/** @noinspection PhpUnusedLocalVariableInspection */
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}

			require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
			$payPalSettings = new PayPalSetting();
			$payPalSettings->id = $paymentLibrary->payPalSettingId;
			if (!$payPalSettings->find(true)) {
				return [
					'success' => false,
					'message' => "PayPal payments are not configured correctly for .",
				];
			}
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$payPalAuthRequest = new CurlWrapper();
			//Connect to PayPal
			if ($payPalSettings->sandboxMode == 1) {
				$baseUrl = 'https://api.sandbox.paypal.com';
			} else {
				$baseUrl = 'https://api.paypal.com';
			}

			$clientId = $payPalSettings->clientId;
			$clientSecret = $payPalSettings->clientSecret;

			//Get the access token
			$authInfo = base64_encode("$clientId:$clientSecret");
			$payPalAuthRequest->addCustomHeaders([
				"Accept: application/json",
				"Accept-Language: en_US",
				"Authorization: Basic $authInfo",
			], true);
			$postParams = ['grant_type' => 'client_credentials',];

			$accessTokenUrl = $baseUrl . "/v1/oauth2/token";
			$accessTokenResults = $payPalAuthRequest->curlPostPage($accessTokenUrl, $postParams);
			$accessTokenResults = json_decode($accessTokenResults);
			if (empty($accessTokenResults->access_token)) {
				return [
					'success' => false,
					'message' => 'Unable to authenticate with PayPal, please try again in a few minutes.',
				];
			} else {
				$accessToken = $accessTokenResults->access_token;
			}

			global $library;
			foreach ($purchaseUnits['items'] as &$item) {
				$item['reference_id'] = $payment->id . "|" . $library->subdomain . "|" . ($userLibrary == null ? 'none' : $userLibrary->subdomain);
				$item['invoice_id'] = $payment->id;
			}

			//Setup the payment request (https://developer.paypal.com/docs/checkout/reference/server-integration/set-up-transaction/)
			$payPalPaymentRequest = new CurlWrapper();
			$payPalPaymentRequest->addCustomHeaders([
				"Accept: application/json",
				"Content-Type: application/json",
				"Accept-Language: en_US",
				"Authorization: Bearer $accessToken",
				"Prefer: return=representation",
			], false);
			$paymentRequestUrl = $baseUrl . '/v2/checkout/orders';
			$paymentRequestBody = [
				'intent' => 'CAPTURE',
				'application_context' => [
					'brand_name' => $paymentLibrary->displayName,
					'locale' => 'en-US',
					'shipping_preference' => 'NO_SHIPPING',
					'user_action' => 'PAY_NOW',
					'return_url' => $configArray['Site']['url'] . '/MyAccount/Fines',
					'cancel_url' => $configArray['Site']['url'] . '/MyAccount/Fines',
				],
				'purchase_units' => [0 => $purchaseUnits,],
			];

			$paymentResponse = $payPalPaymentRequest->curlPostBodyData($paymentRequestUrl, $paymentRequestBody);
			$paymentResponse = json_decode($paymentResponse);

			if ($paymentResponse->status != 'CREATED') {
				return [
					'success' => false,
					'message' => 'Unable to create your order in PayPal.',
				];
			}

			//Log the request in the database so we can validate it on return
			$payment->orderId = $paymentResponse->id;
			$payment->update();

			if ($payment->transactionType == 'donation') {
				$this->addDonation($payment, $tempDonation);
			}

			return [
				'success' => true,
				'orderInfo' => $paymentResponse,
				'orderID' => $paymentResponse->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function completePayPalOrder() {
		global $configArray;

		$orderId = $_REQUEST['orderId'];
		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];

		global $library;
		$paymentLibrary = $library;

		if ($transactionType == 'donation') {
			//Get the order information
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->orderId = $orderId;
			$payment->transactionType = 'donation';
			if ($payment->find(true)) {
				require_once ROOT_DIR . '/sys/Donations/Donation.php';
				$donation = new Donation();
				$donation->paymentId = $payment->id;
				if (!$donation->find(true)) {
					header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?type=paypal&payment=' . $payment->id . '&donation=' . $donation->id);
				}
			} else {
				header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?type=paypal&payment=' . $payment->id);
			}
		} else {
			//Get the order information
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->orderId = $orderId;
			$payment->userId = $patronId;
			if ($payment->find(true)) {

				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];

				$patron = $user->getUserReferredTo($patronId);
				$userLibrary = $patron->getHomeLibrary();
				global $library;
				$paymentLibrary = $library;
				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables->libraryToUseForPayments == 0) {
					$paymentLibrary = $userLibrary;
				}
			}
		}

		require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
		$payPalSettings = new PayPalSetting();
		$payPalSettings->id = $paymentLibrary->payPalSettingId;
		if ($payPalSettings->find(true)) {
			//Get Payment details

			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$payPalAuthRequest = new CurlWrapper();
			//Connect to PayPal
			if ($payPalSettings->sandboxMode == 1) {
				$baseUrl = 'https://api.sandbox.paypal.com';
			} else {
				$baseUrl = 'https://api.paypal.com';
			}

			$clientId = $payPalSettings->clientId;
			$clientSecret = $payPalSettings->clientSecret;

			//Get the access token
			$authInfo = base64_encode("$clientId:$clientSecret");
			$payPalAuthRequest->addCustomHeaders([
				"Accept: application/json",
				"Accept-Language: en_US",
				"Authorization: Basic $authInfo",
			], true);
			$postParams = ['grant_type' => 'client_credentials',];

			$accessTokenUrl = $baseUrl . "/v1/oauth2/token";
			$accessTokenResults = $payPalAuthRequest->curlPostPage($accessTokenUrl, $postParams);
			$accessTokenResults = json_decode($accessTokenResults);
			if (empty($accessTokenResults->access_token)) {
				return [
					'success' => false,
					'message' => 'Unable to authenticate with PayPal, please try again in a few minutes.',
				];
			} else {
				$accessToken = $accessTokenResults->access_token;
			}

			$payPalPaymentRequest = new CurlWrapper();
			$payPalPaymentRequest->addCustomHeaders([
				"Accept: application/json",
				"Content-Type: application/json",
				"Accept-Language: en_US",
				"Authorization: Bearer $accessToken",
				"Prefer: return=representation",
			], false);
			$paymentRequestUrl = $baseUrl . '/v2/checkout/orders/' . $payment->orderId;

			$paymentResponse = $payPalPaymentRequest->curlGetPage($paymentRequestUrl);
			$paymentResponse = json_decode($paymentResponse);

			$purchaseUnits = $paymentResponse->purchase_units;
			if (!empty($purchaseUnits)) {
				$firstItem = reset($purchaseUnits);
				$payments = $firstItem->payments;
				if (!empty($payments->captures)) {
					foreach ($payments->captures as $capture) {
						if ($capture->status == 'COMPLETED') {
							$paymentTransactionId = $capture->id;
							$payment->transactionId = $paymentTransactionId;
							$payment->update();
							break;
						}
					}
				}
			}
		}

		if ($transactionType == 'donation') {
			$payment->completed = 1;
			$payment->update();
			return [
				'success' => true,
				'isDonation' => true,
				'paymentId' => $payment->id,
				'donationId' => $donation->id,
			];
		} else {
			if ($payment->completed) {
				return [
					'success' => false,
					'message' => 'This payment has already been processed',
				];
			} else {
				$user = UserAccount::getActiveUserObj();
				$patron = $user->getUserReferredTo($patronId);

				$result = $patron->completeFinePayment($payment);
				if ($result['success'] == false) {
					//If the payment does not complete in the ILS, add information to the payment for tracking
					//Also send an email to admin that it was completed in paypal, but not the ILS
					$payment->message .= 'Your payment was received, but was not cleared in our library software. Your account will be updated within the next business day. If you need more immediate assistance, please visit the library with your receipt. ' . $result['message'];
					$payment->update();
					$result['message'] = $payment->message;

					if (!empty($payPalSettings->errorEmail)) {
						require_once ROOT_DIR . '/sys/Email/Mailer.php';
						$mail = new Mailer();
						$subject = 'Error updating ILS after PayPal Payment';
						$body = "There was an error updating payment $payment->id within the ILS for patron with barcode {$user->getBarcode()}. The payment should either be voided or the ILS should be updated.";
						global $configArray;
						$baseUrl = $configArray['Site']['url'];
						$htmlBody = "There was an error updating payment <a href='$baseUrl/Admin/eCommerceReport?objectAction=edit&id=$payment->id'>$payment->id</a> within the ILS for patron with barcode {$user->getBarcode()}. The payment should either be voided or the ILS should be updated.";
						$mail->send($payPalSettings->errorEmail, $subject, $body, null, $htmlBody);
					}
				}
				return $result;
			}
		}
	}

	/** @noinspection PhpUnused */
	function createSquareOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('square');
		} else {
			$result = $this->createGenericOrder('square');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}

			return [
				'success' => true,
				'paymentId' => $payment->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function completeSquareOrder() {
		global $configArray;

		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];
		$paymentToken = $_REQUEST['token'];

		global $library;
		$paymentLibrary = $library;

		if ($transactionType == 'donation') {
			//Get the order information
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->squareToken = $paymentToken;
			$payment->transactionType = 'donation';
			if ($payment->find(true)) {
				$paymentId = $payment->id;
				require_once ROOT_DIR . '/sys/Donations/Donation.php';
				$donation = new Donation();
				$donation->paymentId = $payment->id;
				if (!$donation->find(true)) {
					header('Location: ' . $configArray['Site']['url'] . '/Donations/DonationCancelled?type=square&payment=' . $payment->id . '&donation=' . $donation->id);
				}
			} else {
				header('Location: ' . $configArray['Site']['url'] . '/Donations/DonationCancelled?type=square&payment=' . $payment->id);
			}
		} else {
			//Get the order information
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->squareToken = $paymentToken;
			$payment->userId = $patronId;
			if ($payment->find(true)) {
				$paymentId = $payment->id;
				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];
				$patron = $user->getUserReferredTo($patronId);
				$userLibrary = $patron->getHomeLibrary();
				global $library;
				$paymentLibrary = $library;
				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables->libraryToUseForPayments == 0) {
					$paymentLibrary = $userLibrary;
				}
			}
		}

		require_once ROOT_DIR . '/sys/ECommerce/SquareSetting.php';
		$squareSettings = new SquareSetting();
		$squareSettings->id = $paymentLibrary->squareSettingId;
		if($squareSettings->find(true)) {
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$paymentRequest = new CurlWrapper();
			$baseUrl = 'https://connect.squareup.com';
			if($squareSettings->sandboxMode == 1) {
				$baseUrl = 'https://connect.squareupsandbox.com';
			}

			$paymentRequest->addCustomHeaders([
				'Content-Type: application/json',
				'Square-Version: 2023-06-08',
				"Authorization: Bearer $squareSettings->accessToken",
			], true);

			$paymentId = null;
			$paymentAmount = null;
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->squareToken = $paymentToken;
			if ($payment->find(true)) {
				$paymentId = $payment->id;
				$paymentAmount = $payment->totalPaid;
				$body = [
					'idempotency_key' => $paymentId,
					'amount_money' => [
						'amount' => 2000,
						'currency' => 'USD'
					],
					'source_id' => $paymentToken
				];

				$paymentUrl = $baseUrl . '/v2/payments';
				$paymentRequestResults = $paymentRequest->curlPostBodyData($paymentUrl, $body);
				$paymentRequestResults = json_decode($paymentRequestResults);
				if ($paymentRequestResults->payment) {
					$paymentResults = $paymentRequestResults->payment;
					if ($paymentResults->status == 'COMPLETED' || $paymentResults->status == 'APPROVED') {
						if($transactionType == 'donation') {
							$payment->completed = 1;
							$payment->transactionId = $paymentResults->id;
							$payment->orderId = $paymentResults->order_id;
							$payment->update();
							return [
								'success' => true,
								'isDonation' => true,
								'paymentId' => $payment->id,
								'donationId' => $donation->id,
							];
						} else {
							if($payment->completed) {
								return [
									'success' => false,
									'message' => 'This payment has already been processed'
								];
							} else {
								$payment->transactionId = $paymentResults->id;
								$payment->orderId = $paymentResults->order_id;
								$payment->update();
								$user = UserAccount::getActiveUserObj();
								$patron = $user->getUserReferredTo($patronId);
								$result = $patron->completeFinePayment($payment);
								if($result['success'] == false) {
									$payment->message .= 'Your payment was received, but was not cleared in our library software. Your account will be updated within the next business day. If you need more immediate assistance, please visit the library with your receipt. ' . $result['message'];
									$payment->update();
									$result['message'] = $payment->message;
								}

								return $result;
							}
						}
					}
				} else {
					$error = $paymentRequestResults->error;
					$payment->error = 1;
					$payment->message = $error->detail;
					$payment->update();
					return [
						'success' => false,
						'message' => $error->detail,
					];
				}
			}
		}
	}

	/** @noinspection PhpUnused */
	function createMSBOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('msb');
		} else {
			$result = $this->createGenericOrder('msb');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			if ($transactionType == 'donation') {
				/** @noinspection PhpUnusedLocalVariableInspection */
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				/** @noinspection PhpUnusedLocalVariableInspection */
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
				] = $result;
			}
			/** @var Library $paymentLibrary */
			$paymentRequestUrl = $paymentLibrary->msbUrl;
			$paymentRequestUrl .= "?ReferenceID=" . $payment->id;
			$paymentRequestUrl .= "&PaymentType=CC";
			$paymentRequestUrl .= "&TotalAmount=" . $payment->totalPaid;
			if ($transactionType == 'donation') {
				$paymentRequestUrl .= "&PaymentRedirectUrl=" . $configArray['Site']['url'] . '/Donations/DonationCompleted?type=msb&payment=' . $payment->id . '&donation=' . $donation->id;
			} else {
				$paymentRequestUrl .= "&PaymentRedirectUrl=" . $configArray['Site']['url'] . '/MyAccount/Fines/' . $payment->id;
			}
			return [
				'success' => true,
				'message' => 'Redirecting to payment processor',
				'paymentRequestUrl' => $paymentRequestUrl,
			];
		}
	}

	/** @noinspection PhpUnused */
	function createCompriseOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('comprise');
		} else {
			$result = $this->createGenericOrder('comprise');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);
			$currencyFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '');

			/** @var Library $userLibrary */ /** @var UserPayment $payment */
			/** @var User $patron */
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}
			require_once ROOT_DIR . '/sys/ECommerce/CompriseSetting.php';
			$compriseSettings = new CompriseSetting();
			$compriseSettings->id = $paymentLibrary->compriseSettingId;
			if ($compriseSettings->find(true)) {
				$paymentRequestUrl = 'https://smartpayapi2.comprisesmartterminal.com/smartpayapi/websmartpay.dll?GetCreditForm';
				$paymentRequestUrl .= "&LocationID=" . $compriseSettings->customerName;
				$paymentRequestUrl .= "&CustomerID=" . $compriseSettings->customerId;
				if ($transactionType == 'donation') {
					$paymentRequestUrl .= "&PatronID=Guest";
				} else {
					$paymentRequestUrl .= "&PatronID=" . $patron->getBarcode();
				}
				$paymentRequestUrl .= '&UserName=' . urlencode($compriseSettings->username);
				$paymentRequestUrl .= '&Password=' . urlencode($compriseSettings->password);
				$paymentRequestUrl .= '&Amount=' . $currencyFormatter->format($payment->totalPaid);
				if ($transactionType == 'donation') {
					$donation = $this->addDonation($payment, $tempDonation);
					$paymentRequestUrl .= "&URLPostBack=" . urlencode($configArray['Site']['url'] . '/Comprise/Complete');
					$paymentRequestUrl .= "&URLReturn=" . urlencode($configArray['Site']['url'] . '/Donations/DonationCompleted?payment=' . $payment->id);
					$paymentRequestUrl .= "&URLCancel=" . urlencode($configArray['Site']['url'] . '/Donations/DonationCancelled?payment=' . $payment->id);
				} else {
					$paymentRequestUrl .= "&URLPostBack=" . urlencode($configArray['Site']['url'] . '/Comprise/Complete');
					$paymentRequestUrl .= "&URLReturn=" . urlencode($configArray['Site']['url'] . '/MyAccount/CompriseCompleted?payment=' . $payment->id);
					$paymentRequestUrl .= "&URLCancel=" . urlencode($configArray['Site']['url'] . '/MyAccount/CompriseCancel?payment=' . $payment->id);
				}
				$paymentRequestUrl .= '&INVNUM=' . $payment->id;
				$paymentRequestUrl .= '&Field1=';
				$paymentRequestUrl .= '&Field2=';
				$paymentRequestUrl .= '&Field3=';
				$paymentRequestUrl .= '&ItemsData=';

				return [
					'success' => true,
					'message' => 'Redirecting to payment processor',
					'paymentRequestUrl' => $paymentRequestUrl,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Comprise was not properly configured',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function createProPayOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('propay');
		} else {
			$result = $this->createGenericOrder('propay');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);
			$currencyFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '');

			/** @var Library $paymentLibrary */ /** @var Library $userLibrary */ /** @var UserPayment $payment */ /** @var User $patron */
			/** @noinspection PhpUnusedLocalVariableInspection */
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}
			require_once ROOT_DIR . '/sys/ECommerce/ProPaySetting.php';
			$proPaySetting = new ProPaySetting();
			$proPaySetting->id = $paymentLibrary->proPaySettingId;
			if ($proPaySetting->find(true)) {

				if ($transactionType == 'donation') {
					$donation = $this->addDonation($payment, $tempDonation);
				}
				$curlWrapper = new CurlWrapper();
				$authorization = $proPaySetting->billerAccountId . ':' . $proPaySetting->authenticationToken;
				$authorization = 'Basic ' . base64_encode($authorization);
				$curlWrapper->addCustomHeaders([
					'User-Agent: Aspen Discovery',
					'Accept: application/json',
					'Cache-Control: no-cache',
					'Content-Type: application/json',
					'Accept-Encoding: gzip, deflate',
					'Authorization: ' . $authorization,
				], true);

				//Create the payer if one doesn't exist already.
				if (empty($patron->proPayPayerAccountId)) {
					$createPayer = new stdClass();
					$createPayer->EmailAddress = $patron->email;
					$createPayer->ExternalId = $patron->id;
					$createPayer->Name = $patron->_fullname;

					//Issue PUT request to
					if ($proPaySetting->useTestSystem) {
						$url = 'https://xmltestapi.propay.com/protectpay/Payers/';
					} else {
						$url = 'https://api.propay.com/protectpay/Payers/';
					}

					$createPayerResponse = $curlWrapper->curlSendPage($url, 'PUT', json_encode($createPayer));
					if ($createPayerResponse && $curlWrapper->getResponseCode() == 200) {
						$jsonResponse = json_decode($createPayerResponse);
						if ($patron != null) {
							$patron->proPayPayerAccountId = $jsonResponse->ExternalAccountID;
							$proPayPayerAccountId = null;
							$patron->update();
						} else {
							$proPayPayerAccountId = $jsonResponse->ExternalAccountID;
						}
					}
				}

				if (empty($proPaySetting->merchantProfileId) || $proPaySetting->merchantProfileId == 0) {
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
					} else {
						$url = 'https://api.propay.com/protectpay/MerchantProfiles/';
					}

					$createMerchantProfileResponse = $curlWrapper->curlSendPage($url, 'PUT', json_encode($createMerchantProfile));
					if ($createMerchantProfileResponse && $curlWrapper->getResponseCode() == 200) {
						$jsonResponse = json_decode($createMerchantProfileResponse);
						$proPaySetting->merchantProfileId = $jsonResponse->ProfileId;
						$proPaySetting->update();
					}
				}

				if (!empty($patron->proPayPayerAccountId) || ($proPayPayerAccountId != null)) {
					//Create the Hosted Transaction Instance
					$requestElements = new stdClass();
					$requestElements->Amount = (int)round($payment->totalPaid * 100);
					$requestElements->AuthOnly = false;
					$requestElements->AvsRequirementType = 2;
					$requestElements->BillerAccountId = $proPaySetting->billerAccountId;
					$requestElements->CardHolderNameRequirementType = 1;
					$requestElements->CssUrl = $configArray['Site']['url'] . '/interface/themes/responsive/css/main.css';
					$requestElements->CurrencyCode = $currencyCode;
					$requestElements->InvoiceNumber = (string)$payment->id;
					$requestElements->MerchantProfileId = (int)$proPaySetting->merchantProfileId;
					$requestElements->PaymentTypeId = "0";
					if ($proPayPayerAccountId) {
						$requestElements->PayerAccountId = (int)$proPayPayerAccountId;
					} else {
						$requestElements->PayerAccountId = (int)$patron->proPayPayerAccountId;
					}
					$requestElements->ProcessCard = true;
					if ($transactionType == 'donation') {
						$requestElements->ReturnURL = $configArray['Site']['url'] . "/ProPay/{$payment->id}/Complete?type=" . $payment->transactionType . "&donation=" . $donation->id;
					} else {
						$requestElements->ReturnURL = $configArray['Site']['url'] . "/ProPay/{$payment->id}/Complete?type=" . $payment->transactionType;
					}
					$requestElements->SecurityCodeRequirementType = 1;
					$requestElements->StoreCard = false;
					if ($transactionType == 'donation' && $payment->userId == null) {
						$requestElements->Name = $donation->firstName . $donation->lastName;
					} else {
						$patron->loadContactInformation();
						$requestElements->Address1 = $patron->_address1;
						$requestElements->Address2 = $patron->_address2;
						$requestElements->City = $patron->_city;
						$requestElements->Name = $patron->_fullname;
						$requestElements->State = $patron->_state;
						$requestElements->ZipCode = $patron->_zip;
					}

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

						return [
							'success' => true,
							'message' => 'Redirecting to payment processor',
							'paymentRequestUrl' => $paymentRequestUrl,
						];
					} else {
						return [
							'success' => false,
							'message' => 'Could not connect to the payment processor',
						];
					}
				} else {
					return [
						'success' => false,
						'message' => 'Payer Account ID could not be determined.',
					];
				}

			} else {
				return [
					'success' => false,
					'message' => 'ProPay was not properly configured',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function createWorldPayOrder() {
		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('worldpay');
		} else {
			$result = $this->createGenericOrder('worldpay');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}

			return [
				'success' => true,
				'paymentId' => $payment->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function checkWorldPayOrderStatus() {
		$result = [
			'success' => false,
			'message' => 'Unable to check user payment status',
		];

		if (empty($_REQUEST['paymentId'])) {
			$result['message'] = 'No payment id was provided';
		} else {
			$paymentId = $_REQUEST['paymentId'];
			$currentStatus = $_REQUEST['currentStatus'];
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayment = new UserPayment();
			$userPayment->id = $paymentId;
			if ($userPayment->find(true)) {
				if ($userPayment->completed != $currentStatus) {
					global $interface;
					$interface->assign('pendingStatus', false);

					$result['success'] = true;
					$result['message'] = translate([
						'text' => 'Your payment has been completed.',
						'isPublicFacing' => 'true',
					]);
					if (!empty($userPayment->message)) {
						$result['message'] .= ' ' . $userPayment->message;
					}
				} else {
					$result['message'] = 'User payment has not changed';
				}
			} else {
				$result['message'] = 'User payment not found with given id';
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function createXPressPayOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('xpresspay');
		} else {
			$result = $this->createGenericOrder('xpresspay');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			/** @noinspection PhpUnusedLocalVariableInspection */
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}

			require_once ROOT_DIR . '/sys/ECommerce/XpressPaySetting.php';
			$xpressPaySettings = new XpressPaySetting();
			$xpressPaySettings->id = $paymentLibrary->xpressPaySettingId;
			if (!$xpressPaySettings->find(true)) {
				return [
					'success' => false,
					'message' => "Xpress-pay payments are not configured correctly for .",
				];
			}

			$patron->loadContactInformation();
			$baseUrl = 'https://pay.xpress-pay.com/';
			$paymentRequestUrl = $baseUrl . "?pk=" . $xpressPaySettings->paymentTypeCode;
			$paymentRequestUrl .= "&l1=" . $payment->id;
			$paymentRequestUrl .= "&l2=" . $patron->_fullname;
			$paymentRequestUrl .= "&a=" . $payment->totalPaid;
			$paymentRequestUrl .= "&n=" . $patron->_fullname;
			$paymentRequestUrl .= "&addr=" . $patron->_address1;
			$paymentRequestUrl .= "&z=" . $patron->_zip;
			$paymentRequestUrl .= "&e=" . $patron->email;
			$paymentRequestUrl .= "&p=" . $patron->phone;
			$paymentRequestUrl .= "&uid=" . $payment->id;

			return [
				'success' => true,
				'message' => 'Redirecting to payment processor',
				'paymentRequestUrl' => $paymentRequestUrl,
			];
		}
	}

	function createCertifiedPaymentsByDeluxeOrder() {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('deluxe');
		} else {
			$result = $this->createGenericOrder('deluxe');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			/** @noinspection PhpUnusedLocalVariableInspection */
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}

			require_once ROOT_DIR . '/sys/ECommerce/CertifiedPaymentsByDeluxeSetting.php';
			$deluxeSettings = new CertifiedPaymentsByDeluxeSetting();
			$deluxeSettings->id = $paymentLibrary->deluxeCertifiedPaymentsSettingId;
			if (!$deluxeSettings->find(true)) {
				return [
					'success' => false,
					'message' => 'Certified Payments by Deluxe settings are not configured correctly for ' . $paymentLibrary->displayName,
				];
			}

			$patron->loadContactInformation();
			$paymentRequestUrl = 'https://www.velocitypayment.com/vrelay/verify.do';
			if ($deluxeSettings->sandboxMode == 1 || $deluxeSettings->sandboxMode == '1') {
				$paymentRequestUrl = 'https://demo.velocitypayment.com/vrelay/verify.do';
			}

			return [
				'success' => true,
				'message' => 'Redirecting to payment processor',
				'paymentRequestUrl' => $paymentRequestUrl,
			];
		}
	}

	function createPayPalPayflowOrder() {
		global $configArray;
		global $interface;
		global $activeLanguage;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('payflow');
		} else {
			$result = $this->createGenericOrder('payflow');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			/** @noinspection PhpUnusedLocalVariableInspection */
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}

			$bodyBackgroundColor = $interface->getVariable('bodyBackgroundColor');
			$bodyTextColor = $interface->getVariable('bodyTextColor');
			$defaultButtonBackgroundColor = $interface->getVariable('defaultButtonBackgroundColor');
			$defaultButtonForegroundColor = $interface->getVariable('defaultButtonForegroundColor');

			require_once ROOT_DIR . '/sys/ECommerce/PayPalPayflowSetting.php';
			$payflowSettings = new PayPalPayflowSetting();
			$payflowSettings->id = $paymentLibrary->paypalPayflowSettingId;
			if (!$payflowSettings->find(true)) {
				return [
					'success' => false,
					'message' => 'PayPal Payflow settings are not configured correctly for ' . $paymentLibrary->displayName,
				];
			}

			$iframeUrl = 'https://payflowlink.paypal.com/';
			$mode = 'LIVE';
			$tokenRequestUrl = 'https://payflowpro.paypal.com/';
			if ($payflowSettings->sandboxMode == 1 || $payflowSettings->sandboxMode == '1') {
				$iframeUrl = 'https://pilot-payflowlink.paypal.com/';
				$tokenRequestUrl = 'https://pilot-payflowpro.paypal.com/';
				$mode = 'TEST';
			}

			//Create unique token
			$uid = random_bytes(12);
			$tokenId = bin2hex($uid);

			//Get the access token
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$payflowTokenRequest = new CurlWrapper();

			$patron->loadContactInformation();
			$postParams = [
				'PARTNER' => $payflowSettings->partner,
				'VENDOR' => $payflowSettings->vendor,
				'USER' => $payflowSettings->user,
				'PWD' => $payflowSettings->password,
				'TRXTYPE' => 'S',
				'CURRENCY' => 'USD',
				'TEMPLATE' => 'MOBILE',
				'AMT' => "$payment->totalPaid",
				'CREATESECURETOKEN' => 'Y',
				'SECURETOKENID' => $tokenId,
				'RETURNURL' => $configArray['Site']['url'] . '/MyAccount/PayflowComplete',
				'CANCELURL' => $configArray['Site']['url'] . '/MyAccount/PayflowCancelled',
				'ERRORURL' => $configArray['Site']['url'] . '/MyAccount/PayflowComplete',
				'SILENTPOSTURL' => $configArray['Site']['url'] . '/MyAccount/PayflowComplete',
				'USER1' => $payment->id,
				'USER2' => $_SESSION['activeUserId'],
				'USER3' => $activeLanguage->code,
				'PAGECOLLAPSEBGCOLOR' => $bodyBackgroundColor,
				'PAGECOLLAPSETEXTCOLOR' => $bodyTextColor,
				'PAGEBUTTONBGCOLOR' => $defaultButtonBackgroundColor,
				'PAGEBUTTONTEXTCOLOR' => $defaultButtonForegroundColor,
				'LABELTEXTCOLOR' => $bodyTextColor
			];

			foreach ($postParams as $index => $value) {
				$paramList[] = $index . '[' . strlen($value) . ']=' . $value;
			}

			$params = implode('&', $paramList);

			$tokenResults = $payflowTokenRequest->curlSendPage($tokenRequestUrl, 'POST', $params);
			$tokenResults = PayPalPayflowSetting::parsePayflowString($tokenResults);
			if ($tokenResults['RESULT'] != 0) {
				ExternalRequestLogEntry::logRequest('getPayflowToken', 'POST', $tokenRequestUrl, $payflowTokenRequest->getHeaders(), $params, $payflowTokenRequest->getResponseCode(), $tokenResults, []);
				return [
					'success' => false,
					'message' => 'Unable to authenticate with Payflow, please try again in a few minutes.',
				];
			} else {
				$token = $tokenResults['SECURETOKEN'];
				$tokenId = $tokenResults['SECURETOKENID'];
			}

			return [
				'success' => true,
				'paymentIframe' => "<iframe class='fulfillmentFrame' id='payflow-link-iframe' src='{$iframeUrl}/?SECURETOKEN={$token}&SECURETOKENID={$tokenId}' sandbox='allow-top-navigation allow-scripts allow-same-origin allow-forms allow-modals' border='0' frameborder='0' scrolling='no' allowtransparency='true'>\n</iframe>",
			];
		}
	}

	/** @noinspection PhpUnused */
	function createACIOrder() {
		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('ACI');
		} else {
			$result = $this->createGenericOrder('ACI');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}
			$payment->aciToken = $_REQUEST['token'];
			$payment->update();
			return [
				'success' => true,
				'paymentId' => $payment->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function completeACIOrder() {
		global $configArray;

		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];
		$fundingToken = $_REQUEST['fundingToken'];
		$accessToken = $_REQUEST['accessToken'];
		$paymentId = $_REQUEST['paymentId'];
		global $library;
		$paymentLibrary = $library;
		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		require_once ROOT_DIR . '/sys/ECommerce/ACISpeedpaySetting.php';

		if ($transactionType == 'donation') {
			//Get the order information
			$payment = new UserPayment();
			$payment->id = $paymentId;
			$payment->transactionType = 'donation';
			if ($payment->find(true)) {
				$donation = new Donation();
				$donation->paymentId = $payment->id;
				if (!$donation->find(true)) {
					header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?type=aciSpeedpay&payment=' . $payment->id . '&donation=' . $donation->id);
				}
			} else {
				header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?type=aciSpeedpay&payment=' . $payment->id);
			}
		} else {
			//Get the order information
			$payment = new UserPayment();
			$payment->id = $paymentId;
			$payment->userId = $patronId;
			if ($payment->find(true)) {

				$user = UserAccount::getLoggedInUser();
				$patronId = $_REQUEST['patronId'];

				$patron = $user->getUserReferredTo($patronId);
				$userLibrary = $patron->getHomeLibrary();
				global $library;
				$paymentLibrary = $library;
				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables->libraryToUseForPayments == 0) {
					$paymentLibrary = $userLibrary;
				}

				$aciSpeedpaySettings = new ACISpeedpaySetting();
				$aciSpeedpaySettings->id = $paymentLibrary->aciSpeedpaySettingId;
				if ($aciSpeedpaySettings->find(true)) {
					$billerAccount = $aciSpeedpaySettings->billerAccountId;
					$billerAccount = $patron->$billerAccount;

					if ($aciSpeedpaySettings->sandboxMode == 1) {
						$billerAccount = '56050';
					}

					$result = $aciSpeedpaySettings->submitTransaction($patron, $payment, $fundingToken, $billerAccount, $accessToken);
					if ($result['success'] == false) {
						return $result;
					}
				}

				if ($transactionType == 'donation') {
					$donation = new Donation();
					$donation->paymentId = $payment->id;
					$payment->completed = 1;
					$payment->update();
					return [
						'success' => true,
						'isDonation' => true,
						'paymentId' => $payment->id,
						'donationId' => $donation->id,
					];
				} else {
					if ($payment->completed) {
						return [
							'success' => false,
							'message' => 'This payment has already been processed',
						];
					} else {
						$user = UserAccount::getActiveUserObj();
						$patron = $user->getUserReferredTo($patronId);

						$result = $patron->completeFinePayment($payment);
						if ($result['success'] == false) {
							$payment->message .= 'Your payment was received, but was not cleared in our library software. Your account will be updated within the next business day. If you need more immediate assistance, please visit the library with your receipt. ' . $result['message'];
							$payment->update();
							$result['message'] = $payment->message;
						}
						return $result;
					}
				}
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissPlacard() {
		$patronId = $_REQUEST['patronId'];
		$placardId = $_REQUEST['placardId'];

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		if ($patronId != UserAccount::getActiveUserId()) {
			$result['message'] = 'Incorrect user information, please login again.';
		} else {
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			$placard = new Placard();
			$placard->id = $placardId;
			if (!$placard->find(true)) {
				$result['message'] = 'Incorrect placard provided, please try again.';
			} else {
				require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
				$placardDismissal = new PlacardDismissal();
				$placardDismissal->placardId = $placardId;
				$placardDismissal->userId = $patronId;
				$placardDismissal->insert();
				$result = ['success' => true];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function createInvoiceCloudOrder(): array {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('InvoiceCloud');
		} else {
			$result = $this->createGenericOrder('InvoiceCloud');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);
			$currencyFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '');

			/** @var Library $paymentLibrary */ /** @var Library $userLibrary */ /** @var UserPayment $payment */ /** @var User $patron */
			/** @noinspection PhpUnusedLocalVariableInspection */
			if ($transactionType == 'donation') {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
					$tempDonation,
				] = $result;
				$donation = $this->addDonation($payment, $tempDonation);
			} else {
				[
					$paymentLibrary,
					$userLibrary,
					$payment,
					$purchaseUnits,
					$patron,
				] = $result;
			}
			require_once ROOT_DIR . '/sys/ECommerce/InvoiceCloudSetting.php';
			$invoiceCloudSetting = new InvoiceCloudSetting();
			$invoiceCloudSetting->id = $paymentLibrary->invoiceCloudSettingId;
			if ($invoiceCloudSetting->find(true)) {
				$authRequest = new CurlWrapper();
				$authorization = $invoiceCloudSetting->apiKey;
				$authorization = 'Basic ' . base64_encode($authorization);
				$authRequest->addCustomHeaders([
					'Content-Type: application/json',
					'Authorization: ' . $authorization,
				], true);

				$url = 'https://www.invoicecloud.com/api/v1/biller/status';
				$authResponse = $authRequest->curlGetPage($url);
				$authResponse = json_decode($authResponse);
				if (!$authResponse->Active) {
					return [
						'success' => false,
						'message' => 'Unable to create your order in InvoiceCloud. Library has an inactive account.'
					];
				}

				$now = time();
				$token = 'B' . $patron->getBarcode() . 'T' . $now;
				$createInvoice = new StdClass();
				$createInvoice->InvoiceNumber = $token;
				$createInvoice->TypeID = intval($invoiceCloudSetting->invoiceTypeId);
				$createInvoice->BalanceDue = $payment->totalPaid;
				$createInvoice->CCServiceFee = $invoiceCloudSetting->ccServiceFee;
				$createInvoice->ACHServiceFee = $invoiceCloudSetting->ccServiceFee;
				$createInvoice->DueDate = date('m/d/Y');
				$createInvoice->InvoiceDate = date('m/d/Y');

				$createCustomer = new StdClass();
				$createCustomer->AccountNumber = $patron->getBarcode();
				$createCustomer->Name = $patron->firstname . ' ' . $patron->lastname;
				$createCustomer->EmailAddress = $patron->email;
				$createCustomer->Invoices = [$createInvoice];

				$postParams = [
					'CreateCustomerRecord' => true,
					'Customers' => [
						$createCustomer
					],
					'AllowSwipe' => false,
					'AllowCCPayment' => true,
					'AllowACHPayment' => false,
					'ReturnURL' => $configArray['Site']['url'] . "/InvoiceCloud/Complete?payment=" . $payment->id,
					'PostBackURL' => $configArray['Site']['url'] . "/InvoiceCloud/Process",
					'BillerReference' => $payment->id,
					'ViewMode' => 0,
				];

				$paymentRequest = new CurlWrapper();
				$paymentRequest->addCustomHeaders([
					'Content-Type: application/json',
					'Authorization: ' . $authorization,
				], true);

				$url = 'https://www.invoicecloud.com/cloudpaymentsapi/v2';
				$paymentResponse = $paymentRequest->curlPostBodyData($url, $postParams);
				$paymentResponse = json_decode($paymentResponse);
				if ($paymentResponse->Message != 'SUCCESS') {
					return [
						'success' => false,
						'message' => 'Unable to create your order in InvoiceCloud. ' . $paymentResponse->Message
					];
				}
				$paymentRequestUrl = $paymentResponse->Data->CloudPaymentURL;

				return [
					'success' => true,
					'message' => 'Redirecting to payment processor',
					'paymentRequestUrl' => $paymentRequestUrl,
				];
			} else {
				return [
					'success' => false,
					'message' => 'InvoiceCloud was not properly configured for the library.',
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissBrowseCategory() {
		$patronId = $_REQUEST['patronId'];
		$browseCategoryId = $_REQUEST['browseCategoryId'];

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		if ($patronId != UserAccount::getActiveUserId()) {
			$result['message'] = 'Incorrect user information, please login again.';
		} else {
			if (strpos($browseCategoryId, "system_saved_searches") !== false) {
				$label = explode('_', $browseCategoryId);
				$id = $label[3];
				$searchEntry = new SearchEntry();
				$searchEntry->id = $id;
				if (!$searchEntry->find(true)) {
					$result['message'] = 'Invalid browse category provided, please try again.';
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $patronId;
					if ($browseCategoryDismissal->find(true)) {
						$result['message'] = translate([
							'text' => 'You already dismissed this browse category',
							'isPublicFacing' => true,
						]);
					} else {
						$browseCategoryDismissal->insert();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category has been hidden',
								'isPublicFacing' => true,
							]),
						];
					}
				}
			} elseif (strpos($browseCategoryId, "system_user_lists") !== false) {
				$label = explode('_', $browseCategoryId);
				$id = $label[3];
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$userList = new UserList();
				$userList->id = $id;
				if (!$userList->find(true)) {
					$result['message'] = 'Invalid browse category provided, please try again.';
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $patronId;
					if ($browseCategoryDismissal->find(true)) {
						$result['message'] = translate([
							'text' => 'You already dismissed this browse category',
							'isPublicFacing' => true,
						]);
					} else {
						$browseCategoryDismissal->insert();
						$result = [
							'success' => true,
							'title' => translate([
								'text' => 'Preferences updated',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => 'Browse category has been hidden',
								'isPublicFacing' => true,
							]),
						];
					}
				}
			} else {
				require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
				$browseCategory = new BrowseCategory();
				$browseCategory->textId = $browseCategoryId;
				if (!$browseCategory->find(true)) {
					$result['message'] = 'Invalid browse category provided, please try again.';
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$browseCategoryDismissal = new BrowseCategoryDismissal();
					$browseCategoryDismissal->browseCategoryId = $browseCategoryId;
					$browseCategoryDismissal->userId = $patronId;
					if ($browseCategoryDismissal->find(true)) {
						$result['message'] = "User already dismissed this category.";
					} else {
						$browseCategoryDismissal->insert();
						$browseCategory->numTimesDismissed += 1;
						$browseCategory->update();
						$result = ['success' => true];
					}
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getHiddenBrowseCategories() {
		global $interface;

		if (isset($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$interface->assign('patronId', $patronId);

			$hiddenCategories = [];
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissals = new BrowseCategoryDismissal();
			$browseCategoryDismissals->userId = $patronId;
			$browseCategoryDismissals->find();
			while ($browseCategoryDismissals->fetch()) {
				$hiddenCategories[] = clone($browseCategoryDismissals);
			}

			if ($browseCategoryDismissals->count() > 0) {
				$categories = [];
				foreach ($hiddenCategories as $hiddenCategory) {
					if (strpos($hiddenCategory->browseCategoryId, "system_saved_searches") !== false) {
						$parentLabel = "";
						require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
						$savedSearchesBrowseCategory = new BrowseCategory();
						$savedSearchesBrowseCategory->textId = "system_saved_searches";
						if ($savedSearchesBrowseCategory->find(true)) {
							$parentLabel = $savedSearchesBrowseCategory->label . ": ";
						}

						$label = explode('_', $hiddenCategory->browseCategoryId);
						$id = $label[3] ?? $hiddenCategory->browseCategoryId;
						$searchEntry = new SearchEntry();
						$searchEntry->id = $id;
						if ($searchEntry->find(true)) {
							$category['id'] = $hiddenCategory->browseCategoryId;
							$category['name'] = $parentLabel;
							if ($searchEntry->title) {
								$category['name'] = $parentLabel . $searchEntry->title;
							}
							$category['description'] = "";
							$categories[] = $category;
						}
					} elseif (strpos($hiddenCategory->browseCategoryId, "system_user_lists") !== false) {
						$parentLabel = "";
						require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
						$userListsBrowseCategory = new BrowseCategory();
						$userListsBrowseCategory->textId = "system_user_lists";
						if ($userListsBrowseCategory->find(true)) {
							$parentLabel = $userListsBrowseCategory->label . ": ";
						}

						$label = explode('_', $hiddenCategory->browseCategoryId);
						$id = $label[3] ?? $hiddenCategory->browseCategoryId;
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$sourceList = new UserList();
						$sourceList->id = $id;
						if ($sourceList->find(true)) {
							$category['id'] = $hiddenCategory->browseCategoryId;
							$category['name'] = $parentLabel;
							if ($sourceList->title) {
								$category['name'] = $parentLabel . $sourceList->title;
							}
							$category['description'] = $sourceList->description;
							$categories[] = $category;
						}
					} else {
						require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
						$browseCategory = new BrowseCategory();
						$browseCategory->textId = $hiddenCategory->browseCategoryId;
						if ($browseCategory->find(true)) {
							$parentLabel = "";
							require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
							$subBrowseCategory = new SubBrowseCategories();
							$subBrowseCategory->subCategoryId = $browseCategory->id;
							if($subBrowseCategory->find(true)) {
								$parentCategory = new BrowseCategory();
								$parentCategory->id = $subBrowseCategory->browseCategoryId;
								if($parentCategory->find(true)) {
									$parentLabel = $parentCategory->label . ': ';
								}
							}
							$category['id'] = $browseCategory->textId;
							$category['name'] = $parentLabel . $browseCategory->label;
							$category['description'] = $browseCategory->description;
							$categories[] = $category;
						}
					}
				}
				$interface->assign('hiddenBrowseCategories', $categories);
				return [
					'title' => 'Hidden browse categories',
					'modalBody' => $interface->fetch('MyAccount/hiddenBrowseCategories.tpl'),
					'modalButtons' => '<span class="tool btn btn-primary" onclick="return AspenDiscovery.Account.showBrowseCategory()">Show these Browse Categories</span>',
				];
			} else {
				$interface->assign('message', 'You have no hidden browse categories.');
				return [
					'success' => false,
					'title' => 'Error',
					'modalBody' => $interface->fetch('MyAccount/hiddenBrowseCategories.tpl'),
					'message' => 'You have no hidden browse categories.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'You must be logged in to show hidden browse categories.',
			];
		}
	}

	function showBrowseCategory() {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Show hidden browse categories',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Sorry your visible browse categories not be updated',
				'isPublicFacing' => true,
			]),
		];

		$patronId = $_REQUEST['patronId'];

		if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
			$categoriesToShow = $_REQUEST['selected'];
			foreach ($categoriesToShow as $showThisCategory => $selected) {
				if (strpos($showThisCategory, "system_saved_searches") !== false) {
					$label = explode('_', $showThisCategory);
					$id = $label[3];
					$searchEntry = new SearchEntry();
					$searchEntry->id = $id;
					if (!$searchEntry->find(true)) {
						$result['message'] = translate([
							'text' => 'Invalid browse category provided, please try again',
							'isPublicFacing' => true,
						]);
					} else {
						require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
						$browseCategoryDismissal = new BrowseCategoryDismissal();
						$browseCategoryDismissal->browseCategoryId = $showThisCategory;
						$browseCategoryDismissal->userId = $patronId;
						if ($browseCategoryDismissal->find(true)) {
							$browseCategoryDismissal->delete();
							$result = ['success' => true];
						} else {
							$result['message'] = "User already had this category visible.";
						}
					}
				} elseif (strpos($showThisCategory, "system_user_lists") !== false) {
					$label = explode('_', $showThisCategory);
					$id = $label[3];
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$userList = new UserList();
					$userList->id = $id;
					if (!$userList->find(true)) {
						$result['message'] = translate([
							'text' => 'Invalid browse category provided, please try again',
							'isPublicFacing' => true,
						]);
					} else {
						require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
						$browseCategoryDismissal = new BrowseCategoryDismissal();
						$browseCategoryDismissal->browseCategoryId = $showThisCategory;
						$browseCategoryDismissal->userId = $patronId;
						if ($browseCategoryDismissal->find(true)) {
							$browseCategoryDismissal->delete();
							$result = ['success' => true];
						} else {
							$result['message'] = "User already had this category visible.";
						}
					}
				} else {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
					$browseCategory = new BrowseCategory();
					$browseCategory->textId = $showThisCategory;
					if (!$browseCategory->find(true)) {
						$result['message'] = 'Invalid browse category provided, please try again.';
					} else {
						require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
						$browseCategoryDismissal = new BrowseCategoryDismissal();
						$browseCategoryDismissal->browseCategoryId = $browseCategory->textId;
						$browseCategoryDismissal->userId = $patronId;
						if ($browseCategoryDismissal->find(true)) {
							$browseCategoryDismissal->delete();
							$result = ['success' => true];
						} else {
							$result['message'] = "User already had this category visible.";
						}
					}
				}
			}
		} else {
			$result['message'] = 'No browse categories were selected';
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function updateAutoRenewal() {
		$patronId = $_REQUEST['patronId'];
		$allowAutoRenewal = ($_REQUEST['allowAutoRenewal'] == 'on' || $_REQUEST['allowAutoRenewal'] == 'true');

		if (!UserAccount::isLoggedIn()) {
			$result = [
				'success' => false,
				'message' => 'Sorry, you must be logged in to change auto renewal.',
			];
		} else {
			$user = UserAccount::getActiveUserObj();
			if ($user->id == $patronId) {
				$result = $user->updateAutoRenewal($allowAutoRenewal);
			} else {
				$result = [
					'success' => false,
					'message' => 'Invalid user information, please logout and login again.',
				];
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function eventRegistrationModal() {
		$eventUrl = $_REQUEST['regLink'];
		return [
			'success' => true,
			'title' => translate([
				'text' => 'Registration Information',
				'isPublicFacing' => true,
			]),
			'buttons' => '<a href="' .$eventUrl. '" class="btn btn-sm btn-info btn-wrap" target="_blank"><i class="fas fa-external-link-alt"></i>'
				. translate([
					'text' => 'Take Me To Event Registration',
					'isPublicFacing' => true,
				]) . '</a>',
		];
	}

	/** @noinspection PhpUnused */
	function saveEvent() {
		$result = [];

		if (!UserAccount::isLoggedIn()) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Please login before saving an event.',
				'isPublicFacing' => true,
			]);
		} else {
			require_once ROOT_DIR . '/services/MyAccount/MyEvents.php';
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$sourceId = $_REQUEST['sourceId'];

			$userEventsEntry = new UserEventsEntry();
			$userEventsEntry->userId = UserAccount::getActiveUserId();

			if (empty($sourceId)) {
				$result['success'] = false;
				$result['message'] = translate([
					'text' => 'Unable to save event, not correctly specified.',
					'isPublicFacing' => true,
				]);
			} else {
				$userEventsEntry->sourceId = $sourceId;

				if (preg_match('`^communico`', $userEventsEntry->sourceId)){
					require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
					$recordDriver = new CommunicoEventRecordDriver($userEventsEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userEventsEntry->title = substr($title, 0, 50);
						$eventDate = $recordDriver->getStartDate();
						$userEventsEntry->eventDate = $eventDate->getTimestamp();
						if ($recordDriver->isRegistrationRequired()){
							$regRequired = 1;
						}else{
							$regRequired = 0;
						}
						$userEventsEntry->regRequired = $regRequired;
						$userEventsEntry->location = $recordDriver->getBranch();
						$externalUrl = $recordDriver->getExternalUrl();
					}
				} elseif (preg_match('`^libcal`', $userEventsEntry->sourceId)){
					require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
					$recordDriver = new SpringshareLibCalEventRecordDriver($userEventsEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userEventsEntry->title = substr($title, 0, 50);
						$eventDate = $recordDriver->getStartDate();
						$userEventsEntry->eventDate = $eventDate->getTimestamp();
						if ($recordDriver->isRegistrationRequired()){
							$regRequired = 1;
						}else{
							$regRequired = 0;
						}
						$userEventsEntry->regRequired = $regRequired;
						$userEventsEntry->location = $recordDriver->getBranch();
						$externalUrl = $recordDriver->getExternalUrl();
					}
				} elseif (preg_match('`^lc_`', $userEventsEntry->sourceId)){
					require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
					$recordDriver = new LibraryCalendarEventRecordDriver($userEventsEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userEventsEntry->title = substr($title, 0, 50);
						$eventDate = $recordDriver->getStartDate();
						$userEventsEntry->eventDate = $eventDate->getTimestamp();
						if ($recordDriver->isRegistrationRequired()){
							$regRequired = 1;
						}else{
							$regRequired = 0;
						}
						$userEventsEntry->regRequired = $regRequired;
						$userEventsEntry->location = $recordDriver->getBranch();
						$externalUrl = $recordDriver->getExternalUrl();
					}
				}
				$existingEntry = false;

				if ($userEventsEntry->find(true)) {
					$existingEntry = true;
				}
				$userEventsEntry->dateAdded = time();

				if ($existingEntry) {
					$userEventsEntry->update();
				} else {
					$userEventsEntry->insert();
				}

				$result['success'] = true;
				$result['title'] = translate([
					'text' => "Added Successfully",
				]);
				if ($regRequired){
					$result['message'] = translate([
						'text' => "This event was saved to your events successfully. Saving an event to your events is not the same as registering.</br></br> 
						We are taking you to the library’s event management page where you will need to complete your registration. 
						If you are not redirected to the event registration page, please follow <a href='$externalUrl'>this link.</a>",
						'isPublicFacing' => true,
					]);
				}else{
					$result['message'] = translate([
						'text' => 'This event was saved to your events successfully.',
						'isPublicFacing' => true,
					]);
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteSavedEvent() {
		$id = $_GET['id'];
		$result = ['result' => false];
		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to remove events.';
		} else {
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$userEventsEntry = new UserEventsEntry();
			$userEventsEntry->sourceId = $id;
			$userEventsEntry->userId = UserAccount::getActiveUserId();
			if ($userEventsEntry->find(true)) {
				$userEventsEntry->delete();
				$result = [
					'result' => true,
					'message' => 'Event successfully removed from your events.',
				];
			} else {
				$result['message'] = 'Sorry, we could not find that event in the system.';
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getSaveToListForm() {
		global $interface;
		global $library;

		$sourceId = $_REQUEST['sourceId'];
		$source = $_REQUEST['source'];
		$interface->assign('sourceId', $sourceId);
		$interface->assign('source', $source);

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		UserList::getUserListsForSaveForm($source, $sourceId);

		$interface->assign('enableListDescriptions', $library->enableListDescriptions);

		return [
			'title' => translate([
				'text' => 'Add To List',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("MyAccount/saveToList.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.saveToList(); return false;'>" . translate([
					'text' => "Save To List",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function saveToList() {
		$result = [];

		if (!UserAccount::isLoggedIn()) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Please login before adding a title to list.',
				'isPublicFacing' => true,
			]);
		} else {
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
			if (empty($listId)) {
				$userList->title = translate([
					'text' => "My Favorites",
					'isPublicFacing' => true,
				]);
				$userList->user_id = UserAccount::getActiveUserId();
				$userList->public = 0;
				$userList->description = '';
				$userList->insert();
				$totalRecords = 0;
			} else {
				$userList->id = $listId;
				$totalRecords = $userList->numValidListItems();
				if (!$userList->find(true)) {
					$result['success'] = false;
					$result['message'] = translate([
						'text' => 'Sorry, we could not find that list in the system.',
						'isPublicFacing' => true,
					]);
					$listOk = false;
				}
			}

			if ($listOk) {
				$userListEntry = new UserListEntry();
				$userListEntry->listId = $userList->id;

				//TODO: Validate the entry
				$isValid = true;
				if (!$isValid) {
					$result['success'] = false;
					$result['message'] = translate([
						'text' => 'Sorry, that is not a valid entry for the list.',
						'isPublicFacing' => true,
					]);
				} else {
					if (empty($sourceId) || empty($source)) {
						$result['success'] = false;
						$result['message'] = translate([
							'text' => 'Unable to add that to a list, not correctly specified.',
							'isPublicFacing' => true,
						]);
					} else {
						$userListEntry->source = $source;
						$userListEntry->sourceId = $sourceId;
						$userListEntry->weight = $totalRecords + 1;

						if ($userListEntry->source == 'GroupedWork') {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
							$groupedWork = new GroupedWork();
							$groupedWork->permanent_id = $userListEntry->sourceId;
							if ($groupedWork->find(true)) {
								$userListEntry->title = substr($groupedWork->full_title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Lists') {
							require_once ROOT_DIR . '/sys/UserLists/UserList.php';
							$list = new UserList();
							$list->id = $userListEntry->sourceId;
							if ($list->find(true)) {
								$userListEntry->title = substr($list->title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Events') {
							if (preg_match('`^communico`', $userListEntry->sourceId)){
								require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
								$recordDriver = new CommunicoEventRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} elseif (preg_match('`^libcal`', $userListEntry->sourceId)){
								require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
								$recordDriver = new SpringshareLibCalEventRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							} elseif (preg_match('`^lc_`', $userListEntry->sourceId)){
								require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
								$recordDriver = new LibraryCalendarEventRecordDriver($userListEntry->sourceId);
								if ($recordDriver->isValid()) {
									$title = $recordDriver->getTitle();
									$userListEntry->title = substr($title, 0, 50);
								}
							}
						} elseif ($userListEntry->source == 'OpenArchives') {
							require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
							$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
							if ($recordDriver->isValid()) {
								$title = $recordDriver->getTitle();
								$userListEntry->title = substr($title, 0, 50);
							}
						} elseif ($userListEntry->source == 'Genealogy') {
							require_once ROOT_DIR . '/sys/Genealogy/Person.php';
							$person = new Person();
							$person->personId = $userListEntry->sourceId;
							if ($person->find(true)) {
								$userListEntry->title = substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
							}
						} elseif ($userListEntry->source == 'EbscoEds') {
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
						if ($userListEntry->source == 'Events'){
							$result['message'] = translate([
								'text' => 'This event was saved to your list successfully.',
								'isPublicFacing' => true,
							]);
						}else{
							$result['message'] = translate([
								'text' => 'This title was saved to your list successfully.',
								'isPublicFacing' => true,
							]);
						}
					}
				}
			}

		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function reloadCover() {
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$listId = htmlspecialchars($_GET["id"]);
		$listEntry = new UserListEntry();
		$listEntry->listId = $listId;

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->recordType = 'list';
		$bookCoverInfo->recordId = $listEntry->listId;
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->imageSource = '';
			$bookCoverInfo->thumbnailLoaded = 0;
			$bookCoverInfo->mediumLoaded = 0;
			$bookCoverInfo->largeLoaded = 0;
			$bookCoverInfo->update();
		}

		return [
			'success' => true,
			'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.',
		];
	}

	/** @noinspection PhpUnused */
	function getUploadListCoverForm() {
		global $interface;

		$id = htmlspecialchars($_GET["id"]);
		$interface->assign('id', $id);

		return [
			'title' => translate([
				'text' => 'Upload a New List Cover',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("Lists/upload-cover-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadListCoverForm\").submit()'>" . translate([
					'text' => "Upload Cover",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function uploadListCover() {
		$result = [
			'success' => false,
			'title' => 'Uploading custom list cover',
			'message' => 'Sorry your cover could not be uploaded',
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload List Covers'))) {
			if (isset($_FILES['coverFile'])) {
				$uploadedFile = $_FILES['coverFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No Cover file was uploaded";
				} else {
					if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
						$result['message'] = "Error in file upload for cover " . $uploadedFile["error"];
					} else {
						$id = htmlspecialchars($_GET["id"]);
						global $configArray;
						$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
						$fileType = $uploadedFile["type"];
						if ($fileType == 'image/png') {
							if (copy($uploadedFile["tmp_name"], $destFullPath)) {
								$result['success'] = true;
							}
						} elseif ($fileType == 'image/gif') {
							$imageResource = @imagecreatefromgif($uploadedFile["tmp_name"]);
							if (!$imageResource) {
								$result['message'] = 'Unable to process this image, please try processing in an image editor and reloading';
							} else {
								if (@imagepng($imageResource, $destFullPath, 9)) {
									$result['success'] = true;
								}
							}
						} elseif ($fileType == 'image/jpg' || $fileType == 'image/jpeg') {
							$imageResource = @imagecreatefromjpeg($uploadedFile["tmp_name"]);
							if (!$imageResource) {
								$result['message'] = 'Unable to process this image, please try processing in an image editor and reloading';
							} else {
								if (@imagepng($imageResource, $destFullPath, 9)) {
									$result['success'] = true;
								}
							}
						} else {
							$result['message'] = 'Incorrect image type.  Please upload a PNG, GIF, or JPEG';
						}
					}
				}
			} else {
				$result['message'] = 'No cover was uploaded, please try again.';
			}
		}
		if ($result['success']) {
			$this->reloadCover();
			$result['message'] = 'Your cover has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadListCoverFormByURL() {
		global $interface;

		$id = htmlspecialchars($_GET["id"]);
		$interface->assign('id', $id);

		return [
			'title' => translate([
				'text' => 'Upload a New List Cover by URL',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("Lists/upload-cover-form-url.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadListCoverFormByURL\").submit()'>" . translate([
					'text' => "Upload Cover",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function uploadListCoverByURL() {
		$result = [
			'success' => false,
			'title' => 'Uploading custom list cover',
			'message' => 'Sorry your cover could not be uploaded',
		];
		if (isset($_POST['coverFileURL'])) {
			$url = $_POST['coverFileURL'];
			$filename = basename($url);
			$uploadedFile = file_get_contents($url);

			if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
				$result['message'] = "No Cover file was uploaded";
			} else {
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] = "Error in file upload for cover " . $uploadedFile["error"];
				}
			}

			$id = htmlspecialchars($_GET["id"]);
			global $configArray;
			$destFullPath = $configArray['Site']['coverPath'] . '/original/' . $id . '.png';
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if ($ext == "jpg" or $ext == "png" or $ext == "gif" or $ext == "jpeg") {
				$upload = file_put_contents($destFullPath, file_get_contents($url));
				if ($upload) {
					$result['success'] = true;
				} else {
					$result['message'] = 'Incorrect image type.  Please upload a PNG, GIF, or JPEG';
				}
			}
		} else {
			$result['message'] = 'No cover was uploaded, please try again.';
		}
		if ($result['success']) {
			$this->reloadCover();
			$result['message'] = 'Your cover has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteListItems() {
		$result = [
			'success' => false,
			'message' => 'Something went wrong.',
		];

		$listId = htmlspecialchars($_GET["id"]);
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$list = new UserList();
		$list->id = $listId;
		if ($list->find(true)) {
			//Perform an action on the list, but verify that the user has permission to do so.
			$userCanEdit = false;
			$userObj = UserAccount::getActiveUserObj();
			if ($userObj != false) {
				$userCanEdit = $userObj->canEditList($list);
			}
		} else {
			$result['message'] = "Sorry, that list wasn't found.";
		}

		if ($userCanEdit) {
			if (isset($_REQUEST['selected'])) {
				$itemsToRemove = $_REQUEST['selected'];
				foreach ($itemsToRemove as $listEntryId => $selected) {
					$list->removeListEntry($listEntryId);
				}
				$this->reloadCover();
				$result['success'] = true;
				$result['message'] = 'Selected items removed from the list successfully';
			} else {
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
		} else {
			$result['message'] = "Sorry, you don't have permissions to edit this list.";
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteList() {
		$result = [
			'success' => false,
			'message' => 'Something went wrong.',
		];

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		if (isset($_REQUEST['selected'])) {
			$itemsToRemove = $_REQUEST['selected'];
			foreach ($itemsToRemove as $listId => $selected) {
				$list = new UserList();
				$list->id = $listId;
				if ($list->find(true)) {
					//Perform an action on the list, but verify that the user has permission to do so.
					$userCanEdit = false;
					$userObj = UserAccount::getActiveUserObj();
					if ($userObj != false) {
						$userCanEdit = $userObj->canEditList($list);
					}
					if ($userCanEdit) {
						$list->delete();
						$result['success'] = true;
						$result['message'] = 'Selected lists deleted successfully';
					} else {
						$result['message'] = 'You do not have permissions to delete that list';
						$result['success'] = false;
					}
				} else {
					$result['success'] = false;
					$result['message'] = 'Could not find the list to delete';
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEditListForm() {
		global $interface;

		if (isset($_REQUEST['listId']) && isset($_REQUEST['listEntryId'])) {
			$listId = $_REQUEST['listId'];
			$listEntry = $_REQUEST['listEntryId'];

			$interface->assign('listId', $listId);
			$interface->assign('listEntry', $listEntry);

			if (is_array($listId)) {
				$listId = array_pop($listId);
			}
			if (!empty($listId) && is_numeric($listId)) {
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$userList = new UserList();
				$userList->id = $listId;

				$userLists = new UserList();
				$userLists->user_id = UserAccount::getActiveUserId();
				$userLists->whereAdd('deleted = 0');
				$userLists->orderBy('title');
				$userLists->find();
				$lists = [];
				while ($userLists->fetch()) {
					$lists[] = clone $userLists;
				}

				$interface->assign('lists', $lists);

				if ($userList->find(true)) {
					$userObj = UserAccount::getActiveUserObj();
					if ($userObj) {
						$this->listId = $userList->id;
						$this->listTitle = $userList->title;
						$userCanEdit = $userObj->canEditList($userList);
						if ($userCanEdit) {
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

			global $library;
			$interface->assign('enableListDescriptions', $library->enableListDescriptions);

			return [
				'title' => translate([
					'text' => 'Edit List Item',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('MyAccount/editListTitle.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#listEntryEditForm\").submit()'>" . translate([
						'text' => 'Save',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must provide the id of the list to email',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function editListItem(): array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Updating list entry',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Sorry your list entry could not be updated',
				'isPublicFacing' => true,
			]),
		];
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		$userListEntry = new UserListEntry();
		$userListEntry->id = $_REQUEST['listEntry'];
		$listId = $_REQUEST['listId'];
		$position = $_REQUEST['position'];

		$moveTo = $_REQUEST['moveTo'];
		$copyTo = $_REQUEST['copyTo'];

		$list = new UserList();
		$list->id = $listId;

		if ($list->find(true)) {
			if ($userListEntry->find(true)) {

				if ($userListEntry->notes != strip_tags($_REQUEST['notes'])) {
					$userListEntry->notes = strip_tags($_REQUEST['notes']);
					$userListEntry->update();
					$result['success'] = true;
				}

				$numListEntries = count($list->getListTitles());

				if (!empty($position) && ($position != $userListEntry->weight)) {
					$moveToPosition = $_REQUEST['position'];
					$moveFromPosition = $userListEntry->weight;

					$lowestPosition = min($moveFromPosition, $moveToPosition);
					$highestPosition = max($moveFromPosition, $moveToPosition);

					$listEntryMoveTo = new UserListEntry();
					$listEntryMoveTo->listId = $_REQUEST['listId'];
					$listEntryMoveTo->weight = $moveToPosition;
					if ($listEntryMoveTo->find(true)) {
						$listEntry = new UserListEntry();
						$listEntry->listId = $_REQUEST['listId'];
						$listEntry->orderBy('weight');
						$listEntry->whereAdd("weight >= $lowestPosition && weight <= $highestPosition");
						$listEntry->find();
						while ($listEntry->fetch()) {
							if ($listEntry->weight < $lowestPosition) {
								//No change needed, this is outside the range of things changing.
							} elseif ($listEntry->weight > $highestPosition) {
								//No change needed, this is outside the range of things changing.
							} else {
								//Things be changing!
								if ($listEntry->id == $_REQUEST['listEntry']) {
									$listEntry->weight = $moveToPosition;
									$listEntry->update();
								} else {
									if ($moveToPosition > $moveFromPosition) {
										// if item is increasing in weight, move items down by 1
										$listEntry->weight = $listEntry->weight - 1;
										$listEntry->update();
									} elseif ($moveToPosition < $moveFromPosition) {
										$listEntry->weight = $listEntry->weight + 1;
										$listEntry->update();
									}
								}
							}
						}

						$result['success'] = true;
					} elseif ($moveToPosition <= $numListEntries) {
						//The positions are out of order, fix it.
						$userListEntry->weight = $position;
						$userListEntry->update();
						$result['success'] = true;
					}
				}
				if (($moveTo != $listId) && ($moveTo != 'null')) {
					// check to make sure item isn't on new list?

					//Make sure the list gets marked as updated
					$moveToList = new UserList();
					$moveToList->id = $moveTo;
					$moveToList->find(true);

					$userListEntry->listId = $moveTo;
					$userListEntry->weight = count($moveToList->getListEntries()) + 1;
					$userListEntry->update();

					$list->fixWeights();
					$moveToList->fixWeights();
					$moveToList->update();

					$result['success'] = true;
				}
				if (($copyTo != $listId) && ($copyTo != 'null')) {
					// check to make sure item isn't on new list?
					$copyToList = new UserList();
					$copyToList->id = $copyTo;
					if ($copyToList->find(true)) {
						$copyUserListEntry = new UserListEntry();
						$copyUserListEntry->listId = $copyTo;
						$copyUserListEntry->sourceId = $userListEntry->sourceId;
						$copyUserListEntry->notes = $userListEntry->notes;
						$copyUserListEntry->weight = count($copyToList->getListEntries()) + 1;
						$copyUserListEntry->source = $userListEntry->source;
						$copyUserListEntry->dateAdded = time();
						$copyUserListEntry->update();

						//Make sure the list gets marked as updated
						$copyToList = new UserList();
						$copyToList->id = $copyTo;
						$copyToList->fixWeights();
						$copyToList->update();

						$result['success'] = true;
					} else {
						$result['message'] = translate([
							'text' => 'Could not find list to copy to',
							'isPublicFacing' => true,
						]);
					}

				}
				$list->update();
			} else {
				$result['success'] = false;
			}
		} else {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Invalid List Id was specified',
				'isPublicFacing' => true,
			]);
		}

		if ($result['success']) {
			$result['message'] = translate([
				'text' => 'List item updated successfully',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function updateWeight() {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error moving list entry',
				'isPublicFacing' => true,
			]),
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
					if ($listEntry->find(true)) {
						//Figure out new weights for list entries
						$direction = $_REQUEST['direction'];
						$oldWeight = $listEntry->weight;
						if ($direction == 'up') {
							$newWeight = $oldWeight - 1;
						} else {
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
						} else {
							if ($direction == 'up') {
								$result['message'] = 'List entry is already at the top';
							} else {
								$result['message'] = 'List entry is already at the bottom';
							}
						}
					} else {
						$result['message'] = 'Unable to find that list entry';
					}
				} else {
					$result['message'] = 'No list entry id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to move a list entry';
			}
		} else {
			$result['message'] = 'You must be logged in to move a list entry';
		}
		return $result;
	}

	function getSuggestionsSpotlight() {
		$result = [
			'success' => false,
			'message' => 'Error loading suggestions spotlight.',
		];

		if (!UserAccount::isLoggedIn()) {
			$result['message'] = 'You must be logged in to view suggestions.  Please close this dialog and login again.';
		} else {
			require_once ROOT_DIR . '/sys/Suggestions.php';
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			global $interface;
			$interface->assign('listName', 'recommendedForYou');
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

	function getCurbsidePickupScheduler() {
		global $interface;
		global $library;

		$result = [
			'success' => false,
			'message' => 'Error loading curbside pickup scheduler',
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
			// clear out anything that would load specific data
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

			$pickupSettings = $user->getCatalogDriver()->getCurbsidePickupSettings($user->getHomeLocation()->code);

			$result = [
				'success' => true,
				'title' => translate([
					'text' => 'Schedule your pickup at ' . $pickupLocation["name"],
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/curbsidePickupsNew.tpl'),
				'buttons' => "<button class='btn btn-primary' onclick='return AspenDiscovery.Account.createCurbsidePickup();'>" . translate([
						'text' => 'Schedule Pickup',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} else {
			// no settings found
			$result['message'] = "Curbside pickup settings not found.";
		}

		return $result;
	}

	function createCurbsidePickup() {
		global $interface;
		global $library;
		$user = UserAccount::getLoggedInUser();
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Scheduling curbside pickup',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Error scheduling curbside pickup',
				'isPublicFacing' => true,
			]),
		];
		if (!$user) {
			$result['message'] = translate([
				'text' => 'You must be logged in to schedule a curbside pickup.  Please close this dialog and login again.',
				'isPublicFacing' => true,
			]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate([
					'text' => 'Sorry, you do not have access to schedule a curbside pickup for this patron.',
					'isPublicFacing' => true,
				]);
			} else {
				if (empty($_REQUEST['location']) || empty($_REQUEST['date']) || empty($_REQUEST['time'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					global $logger;
					$logger->log('New curbside pickup, pickup library or pickup date/time was not passed in AJAX call.', Logger::LOG_ERROR);
					$result['message'] = translate([
						'text' => 'Schedule information about the curbside pickup was not provided.',
						'isPublicFacing' => true,
					]);
				} else {
					$pickupLocation = $_REQUEST['location'];
					$pickupDate = $_REQUEST['date'];
					$pickupTime = $_REQUEST['time'];
					if (isset($_REQUEST['note'])) {
						$pickupNote = $_REQUEST['note'];
						if ($pickupNote == 'undefined') {
							$pickupNote = null;
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
								'text' => 'Error scheduling curbside pickup',
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
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('New curbside pickup, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = translate([
				'text' => 'No patron was specified.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	function getCancelCurbsidePickup() {
		$patronId = $_REQUEST['patronId'];
		$pickupId = $_REQUEST['pickupId'];
		return [
			'title' => translate([
				'text' => 'Cancel curbside pickup',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Are you sure you want to cancel this curbside pickup?',
				'isPublicFacing' => true,
			]),
			'buttons' => "<span class='btn btn-primary' onclick='AspenDiscovery.Account.cancelCurbsidePickup(\"$patronId\", \"$pickupId\")'>" . translate([
					'text' => 'Yes, cancel pickup',
					'isPublicFacing' => true,
				]) . "</span>",
		];
	}

	function checkInCurbsidePickup() {
		global $interface;
		global $library;
		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Checking in curbside pickup',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Error checking in for curbside pickup',
				'isPublicFacing' => true,
			]),
		];

		if (!isset($_REQUEST['patronId']) || !isset($_REQUEST['pickupId'])) {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Check-in for curbside pickup, no patron Id and/or pickup Id was passed in AJAX call.', Logger::LOG_ERROR);
			$results['message'] = translate([
				'text' => 'No patron or pickup was specified.',
				'isPublicFacing' => true,
			]);
		} else {
			$patronId = $_REQUEST['patronId'];
			$pickupId = $_REQUEST['pickupId'];

			require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';
			$curbsidePickupSetting = new CurbsidePickupSetting();
			$curbsidePickupSetting->id = $library->curbsidePickupSettingId;
			if ($curbsidePickupSetting->find(true)) {
				$interface->assign('contentCheckedIn', $curbsidePickupSetting->contentCheckedIn);
			}

			$user = UserAccount::getActiveUserObj();
			$result = $user->getCatalogDriver()->checkInCurbsidePickup($patronId, $pickupId);

			if ($result['success']) {
				$results = [
					'success' => true,
					'title' => translate([
						'text' => 'Check-in successful',
						'isPublicFacing' => true,
					]),
					'body' => $interface->fetch('MyAccount/curbsidePickupsNewSuccess.tpl'),
				];
			} else {
				$results = [
					'title' => translate([
						'text' => 'Error checking in for curbside pickup',
						'isPublicFacing' => true,
					]),
					'body' => translate([
						'text' => $result['message'],
						'isPublicFacing' => true,
					]),
				];
			}
		}

		return $results;
	}

	function cancelCurbsidePickup() {
		global $interface;
		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Cancel curbside pickup',
				'isPublicFacing' => true,
			]),
		];

		if (!isset($_REQUEST['patronId']) || !isset($_REQUEST['pickupId'])) {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Cancelling curbside pickup, no patron Id and/or pickup Id was passed in AJAX call.', Logger::LOG_ERROR);
			$results['message'] = translate([
				'text' => 'No patron or pickup was specified.',
				'isPublicFacing' => true,
			]);
		} else {
			$patronId = $_REQUEST['patronId'];
			$pickupId = $_REQUEST['pickupId'];

			$user = UserAccount::getActiveUserObj();
			$result = $user->getCatalogDriver()->cancelCurbsidePickup($patronId, $pickupId);

			if ($result['success']) {
				$results = [
					'success' => true,
					'title' => translate([
						'text' => 'Cancel curbside pickup',
						'isPublicFacing' => true,
					]),
					'body' => translate([
						'text' => 'Your pickup was cancelled successfully.',
						'isPublicFacing' => true,
					]),
				];
			} else {
				$results = [
					'title' => translate([
						'text' => 'Cancel curbside pickup',
						'isPublicFacing' => true,
					]),
					'body' => translate([
						'text' => $result['message'],
						'isPublicFacing' => true,
					]),
				];
			}
		}

		return $results;
	}

	function getCurbsidePickupUnavailableDays() {
		if (isset($_REQUEST['locationCode'])) {
			$pickupLocation = $_REQUEST['locationCode'];
		} else {
			return [
				'title' => translate([
					'text' => 'Error loading curbside pickup availability',
					'isPublicFacing' => true,
				]),
				'body' => translate([
					'text' => "A valid pickup location parameter was not provided.",
					'isPublicFacing' => true,
				]),
			];
		}
		$user = UserAccount::getActiveUserObj();
		$pickupSettings = $user->getCatalogDriver()->getCurbsidePickupSettings($pickupLocation);
		return $pickupSettings['disabledDays'];
	}

	function getCurbsidePickupAvailableTimes() {
		if (isset($_REQUEST['locationCode']) && isset($_REQUEST['date'])) {
			$pickupLocation = $_REQUEST['locationCode'];
			$pickupDate = $_REQUEST['date'];
			// check to make sure the date has been sent
		} else {
			return [
				'title' => translate([
					'text' => 'Error loading curbside pickup availability',
					'isPublicFacing' => true,
				]),
				'body' => translate([
					'text' => "A valid pickup date was not provided.",
					'isPublicFacing' => true,
				]),
			];
		}

		$days = [
			0 => 'Mon',
			1 => 'Tue',
			2 => 'Wed',
			3 => 'Thu',
			4 => 'Fri',
			5 => 'Sat',
			6 => 'Sun',
		];

		$user = UserAccount::getActiveUserObj();
		$pickupSettings = $user->getCatalogDriver()->getCurbsidePickupSettings($pickupLocation);

		if ($pickupSettings['success'] == true && $pickupSettings['enabled'] == 1) {

			$date = strtotime($pickupDate);
			$dayOfWeek = date('D', $date);
			$todayDay = date('D');
			$now = date('H:i');
			$allPossibleTimes = $pickupSettings['pickupTimes'][$dayOfWeek];

			// check if max number of patrons are signed up for timeWindow
			$maxPatrons = $pickupSettings['maxPickupsPerInterval'];
			$allScheduledPickups = $user->getCatalogDriver()->getAllCurbsidePickups();

			if ($allPossibleTimes) {
				$range = range(strtotime($allPossibleTimes['startTime']), strtotime($allPossibleTimes['endTime']), $pickupSettings['interval'] * 60);
				$timeWindow = [];
				foreach ($range as $time) {
					$numPickups = 0;
					$formattedTime = strtotime(date('H:i', $time));
					if ($dayOfWeek == $todayDay) {
						if ($formattedTime > strtotime($now)) {
							if (!empty($allScheduledPickups['pickups'])) {
								foreach ($allScheduledPickups['pickups'] as $pickup) {
									if ($pickupLocation == $pickup->branchcode) {
										$scheduledDate = strtotime($pickup->scheduled_pickup_datetime);
										$scheduledDay = date('D', $scheduledDate);
										$scheduledTime = date('H:i', $scheduledDate);
										if ($dayOfWeek == $scheduledDay) {
											if ($formattedTime == strtotime($scheduledTime)) {
												$numPickups += 1;
											}
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
					} else {
						if (!empty($allScheduledPickups['pickups'])) {
							foreach ($allScheduledPickups['pickups'] as $pickup) {
								if ($pickupLocation == $pickup->branchcode) {
									$scheduledDate = strtotime($pickup->scheduled_pickup_datetime);
									$scheduledDay = date('D', $scheduledDate);
									$scheduledTime = date('H:i', $scheduledDate);
									if ($dayOfWeek == $scheduledDay) {
										if ($formattedTime == strtotime($scheduledTime)) {
											$numPickups += 1;
										}
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
				}

				return $timeWindow;
			}
		}
		return [
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => "There was an error loading curbside pickup availability",
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	function get2FAEnrollment() {
		global $interface;

		// if there were multiple verification methods available, you'd want to fetch them here for display

		$step = $_REQUEST['step'] ?? "register";
		$mandatoryEnrollment = $_REQUEST['mandatoryEnrollment'] ?? false;

		if ($step == "register") {

			function mask($str, $first, $last) {
				$len = strlen($str);
				$toShow = $first + $last;
				return substr($str, 0, $len <= $toShow ? 0 : $first) . str_repeat("*", $len - ($len <= $toShow ? 0 : $toShow)) . substr($str, $len - $last, $len <= $toShow ? 0 : $last);
			}

			function mask_email($email) {
				$mail_parts = explode("@", $email);
				$domain_parts = explode('.', $mail_parts[1]);

				$mail_parts[0] = mask($mail_parts[0], 2, 1); // show first 2 letters and last 1 letter
				$domain_parts[0] = mask($domain_parts[0], 2, 1); // same here
				$mail_parts[1] = implode('.', $domain_parts);

				return implode("@", $mail_parts);
			}

			$email = null;
			$user = new User();
			$user->id = UserAccount::getActiveUserId();
			if ($user->find(true)) {
				$email = mask_email($user->email);
			}
			$interface->assign('emailAddress', $email);

			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-register.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.show2FAEnrollmentVerify(\"{$mandatoryEnrollment}\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} elseif ($step == "verify") {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			$twoFactorAuth = new TwoFactorAuthCode();
			$twoFactorAuth->createCode();

			$invalid = $_REQUEST['invalid'] ?? false;
			$alert = null;
			if ($invalid) {
				$alert = 'The code entered is invalid.';
			}
			$interface->assign('alert', $alert);
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-verify.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.verify2FA(\"{$mandatoryEnrollment}\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} elseif ($step == "validate") {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			$twoFactorAuth = new TwoFactorAuthCode();
			$twoFactorAuth->createCode();

			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-verify.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.verify2FA(\"{$mandatoryEnrollment}\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} elseif ($step == "backup") {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			$twoFactorAuth = new TwoFactorAuthCode();
			$twoFactorAuth->createNewBackups();

			$backupCode = new TwoFactorAuthCode();
			$backupCodes = $backupCode->getBackups();
			$interface->assign('backupCodes', $backupCodes);

			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-backup.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.show2FAEnrollmentSuccess(\"{$mandatoryEnrollment}\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} elseif ($step == "complete") {
			// update user table to enrolled status
			$user = new User();
			$user->id = UserAccount::getActiveUserId();
			if ($user->find(true)) {
				$user->twoFactorStatus = 1;
				$user->update();
			}
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-success.tpl'),
			];
		} else {
			return false;
		}
	}

	/** @noinspection PhpUnused */
	function verify2FA() {
		$code = $_REQUEST['code'] ?? '0';
		$isLoggingIn = $_REQUEST['loggingIn'] ?? false;
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		if ($isLoggingIn) {
			global $logger;
			$logger->log("Starting AJAX/2faLogin session: " . session_id(), Logger::LOG_DEBUG);
			$result = $twoFactorAuth->validateCode($code);
			if ($result['success'] == true) {
				UserAccount::$isAuthenticated = true;
				try {
					UserAccount::login();
				} catch (UnknownAuthenticationMethodException $e) {
					$logger->log("Error logging authenticated user in $e", Logger::LOG_DEBUG);
					return [
						'success' => false,
						'message' => $e->getMessage(),
					];
				}
			} else {
				return $result;
			}
		} else {
			$result = $twoFactorAuth->validateCode($code);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function confirmCancel2FA() {
		global $interface;

		// on submit of button, update user table for (un)enrollment status

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Disable Two-Factor Authentication',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/2fa/unenroll.tpl'),
			'buttons' => "<button class='tool btn btn-primary' onclick='return AspenDiscovery.Account.cancel2FA();'>Yes, turn off</button>",
		];
	}

	/** @noinspection PhpUnused */
	function cancel2FA() {
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		$twoFactorAuth->deactivate2FA();

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Disable Two-Factor Authentication',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Two-factor authentication has been disabled for your account.',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	function newBackupCodes() {
		global $interface;

		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		$twoFactorAuth->createNewBackups();

		$backupCode = new TwoFactorAuthCode();
		$backupCodes = $backupCode->getBackups();
		$interface->assign('backupCodes', $backupCodes);

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Two-Factor Authentication Backup Codes',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/2fa/backupCodes.tpl'),
		];
	}

	/** @noinspection PhpUnused */
	function new2FACode() {
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		$twoFactorAuth->createCode();

		return [
			'success' => true,
			'body' => translate([
				'text' => 'A new code was sent.',
				'isPublicFacing' => true,
			]),
		];
	}

	/** @noinspection PhpUnused */
	function auth2FALogin() {
		global $interface;
		global $logger;
		$logger->log("Creating AJAX/2faLogin session: " . session_id(), Logger::LOG_DEBUG);


		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		$twoFactorAuth->createCode();

		$referer = $_REQUEST['referer'] ?? null;
		$interface->assign('referer', $referer);
		$name = $_REQUEST['name'] ?? null;
		$interface->assign('name', $name);

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Two-Factor Authentication',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/2fa/login.tpl'),
			'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.verify2FALogin(); return false;'>" . translate([
					'text' => 'Verify',
					'isPublicFacing' => true,
				]) . "</button>",
		];

	}

	function exportUserList() {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Export User List to CSV: something went wrong.',
				'isPublicFacing' => true,
			]),
		];
		global $interface;
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) { // validly formatted List Id
			$userListId = $_REQUEST['listId'];
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $userListId;
			if ($list->find(true)) {
				// Load the User object for the owner of the list (if necessary):
				if ($list->public == true || (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $list->user_id)) {
					$list->buildCSV();
				} else {
					$result = [
						'result' => false,
						'message' => translate([
							'text' => 'Export User List to CSV: You do not have access to this list.',
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				$result = [
					'result' => false,
					'message' => translate([
						'text' => 'Export User List to CSV: Unable to read list.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else { // Invalid listId
			$result = [
				'result' => false,
				'message' => translate([
					'text' => 'Export User List to CSV: Invalid list id.',
					'isPublicFacing' => true,
				]),
			];
		}
	}
}