<?php

use JetBrains\PhpStorm\NoReturn;

require_once ROOT_DIR . '/JSON_Action.php';

class MyAccount_AJAX extends JSON_Action {
	/** @noinspection PhpMissingClassConstantTypeInspection */
	const SORT_LAST_ALPHA = 'zzzzz';

	function launch($method = null): void {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		switch ($method) {
			case 'renewItem':
				$method = 'renewCheckout';
				break;
			case 'getUserCheckouts':
				$method = 'getUserCheckouts';
				break;
			case 'getUserHolds':
				$method = 'getUserHolds';
		}
		if (method_exists($this, $method)) {
			parent::launch($method);
		} else {
			echo json_encode(['error' => 'invalid_method']);
		}
	}

	/** @noinspection PhpUnused */
	function getAddBrowseCategoryFromListForm(): array {
		global $interface;
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Browse Categories', 'Administer Library Browse Categories','Administer Selected Browse Category Groups']);

		// Select List Creation using Object Editor functions
		require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
		$temp = SubBrowseCategories::getObjectStructure();
		$temp['subCategoryId']['values'] = [0 => 'Select One'] + $temp['subCategoryId']['values'];
		// add default option that denotes nothing has been selected to the options list
		// (this preserves the keys' numeric values (which is essential as they are the ID values) as well as the array's order)
		// (btw addition of arrays is kinda a cool trick)
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
		$this->requireLoggedInUser('Unable to link accounts', 'You must be logged in to link accounts, please login again');
		$this->checkRequiredParameters(['username', 'password']);

		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		$accountToLink = UserAccount::validateAccount($username, $password);

		if ($accountToLink) {
			$user = UserAccount::getLoggedInUser();
			$userPtype = $user->getPType();

			if ($accountToLink->id != $user->id) {
				$linkeePtype = $accountToLink->getPType();
				if ($linkeePtype != null) {
					require_once ROOT_DIR . '/sys/Account/PType.php';
					$linkingSettingUser = PType::getAccountLinkingSetting($userPtype);
					$linkingSettingLinkee = PType::getAccountLinkingSetting($linkeePtype);

					if (($accountToLink->disableAccountLinking == 0) && ($linkingSettingUser != '1' && $linkingSettingUser != '3') && ($linkingSettingLinkee != '2' && $linkingSettingLinkee != '3')) {
						$addResult = $user->addLinkedUser($accountToLink);
						if ($addResult === true) {
							$result = $this->successResult('Success', 'Successfully linked accounts.');
						} else { // insert failure or user is blocked from linking account or account & account to link are the same account
							$result = $this->failureResult( 'Unable to link accounts', 'Sorry, we could not link to that account.  Accounts cannot be linked if all libraries do not allow account linking.  Please contact your local library if you have questions.');
						}
					} else {
						if ($linkingSettingUser == '1' || $linkingSettingUser == '3') {
							$result = $this->failureResult( 'Unable to link accounts', 'Sorry, you are not permitted to link to others.');
						} else if ($linkingSettingLinkee == '2' || $linkingSettingLinkee == '3') {
							$result = $this->failureResult( 'Unable to link accounts', 'Sorry, that account cannot be linked to.');
						} else {
							$result = $this->failureResult( 'Unable to link accounts', 'Sorry, this user does not allow account linking.');
						}
					}
				} else {
					$result = $this->failureResult( 'Unable to link accounts', 'Sorry, this user type cannot be linked to.');
				}
			} else {
				$result = $this->failureResult( 'Unable to link accounts', 'You cannot link to yourself.');
			}
		} else {
			$result = $this->failureResult('Unable to link accounts', 'The information for the user to link to was not correct.');
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function removeManagingAccount(): array {
		$this->requireLoggedInUser('Unable to Remove Account Link', 'Sorry, you must be logged in to manage accounts');
		$this->checkRequiredParameters(['idToRemove']);

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
					'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.redirectPinReset(); return false;'>" . translate([
							'text' => "Request PIN Change",
							'isPublicFacing' => true,
						]) . "</button>",
				];
			} else {
				$result = $this->successResult('Linked Account Removed', 'Successfully removed linked account. Removing this link does not guarantee the security of your account. If another user has your barcode and PIN/password they will still be able to access your account. Please contact your library if you wish to update your PIN/Password.');
			}
		} else {
			$result = $this->failureResult('Unable to Remove Account Link', 'Sorry, we could not remove that account.');
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function removeAccountLink(): array {
		$this->requireLoggedInUser('Unable to Remove Account Link', 'Sorry, you must be logged in to manage accounts');
		$this->checkRequiredParameters(['idToRemove']);

		$accountToRemove = $_REQUEST['idToRemove'];
		$user = UserAccount::getLoggedInUser();
		if ($user->removeLinkedUser($accountToRemove)) {
			$result = $this->successResult('Success', 'Successfully removed linked account.');
		} else {
			$result = $this->failureResult('Unable to Remove Account Link', 'Sorry, we could remove that account.');
		}

		return $result;
	}

	//WHAT IS IN MODAL POPUP FOR LINK DISABLE

	/** @noinspection PhpUnused */
	function disableAccountLinkingInfo(): array {
		$this->requireLoggedInUser();

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
				'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.toggleAccountLinkingAccept(); return false;'>" . translate([
						'text' => "Accept",
						'isPublicFacing' => true,
					]) . "</button>",
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
				'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.toggleAccountLinkingAccept(); return false;'>" . translate([
						'text' => "Accept",
						'isPublicFacing' => true,
					]) . "</button>",
			];
		}
	}

	//USED UPON SUBMITTING

	/** @noinspection PhpUnused */
	function toggleAccountLinking() : array {
		$this->requireLoggedInUser(null, 'Sorry, you must be logged in to manage accounts.');

		$user = UserAccount::getActiveUserObj();
		if ($user->disableAccountLinking == 1) {
			$success = $user->accountLinkingToggle();
			if ($success) {
				$result = $this->successResult('Linking Enabled', 'Account linking has been enabled');
			}else{
				$result = $this->failureResult('Linking Enabled', 'Account linking could not be enabled');
			}
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
						'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.redirectPinReset(); return false;'>" . translate([
								'text' => "Request PIN Change",
								'isPublicFacing' => true,
							]) . "</button>",
					];
				} else {
					if ($success) {
						$result = $this->successResult('Linking Disabled', 'Account linking has been disabled. Disabling account linking does not guarantee the security of your account. If another user has your barcode and PIN/password they will still be able to access your account. Please contact your library if you wish to update your PIN/Password.');
					}else{
						$result = $this->failureResult('Linking Disabled', 'Account linking could not be disabled');
					}
				}
			} else {
				$result = $this->failureResult(null, 'Sorry, something went wrong and we were unable to process this request.');
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getTermsModalContent() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$selfRegTerms = $catalog->getSelfRegistrationTerms();
		return [
			'title' => translate([
				'text' => 'Terms of Service',
				'isPublicFacing' => true,
			]),
			'message' => $selfRegTerms->terms,
			'modalButtons' => "<button type='button' class='tool btn btn-primary' id = 'AcceptTOS' onclick='AspenDiscovery.Account.selfRegistrationAgreeToTOS();'>" . translate([
					'text' => "Agree",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function getAddAccountLinkForm() : array {
		$this->requireLoggedInUser();

		global $interface;
		global $library;

		$interface->assign('enableSelfRegistration', 0);
		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ?? 'Your Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ?? 'Library Card Number'));
		// Display Page
		return [
			'title' => translate([
				'text' => 'Account to Manage',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/addAccountLink.tpl'),
			'modalButtons' => "<button type='button' class='tool btn btn-primary' id = 'AddAccountSubmit' onclick='AspenDiscovery.Account.processAddLinkedUser(); return false;'>" . translate([
					'text' => "Add Account",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function allowAccountLink() : array {
		$this->requireLoggedInUser();

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

		return $this->successResult(null, 'Account Link Accepted');
	}

	/** @noinspection PhpUnused */
	function getBulkAddToListForm() : array {
		global $interface;
		// Display Page
		$interface->assign('listId', strip_tags($_REQUEST['listId']));

		/** @noinspection JSUnresolvedReference */
		return [
			'title' => translate([
				'text' => 'Add titles to list',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/bulkAddToListPopup.tpl'),
			'modalButtons' => "<button type='button' id='doBulkAddToListBtn' class='tool btn btn-primary' onclick=\"$('#doBulkAddToListBtn').prop('disabled', true).addClass('disabled');$('#doBulkAddToListBtn .fa-spinner').removeClass('hidden');AspenDiscovery.Lists.processBulkAddForm(); return false;\"><i class='fas fa-spinner fa-spin hidden'></i> " . translate([
					'text' => "Add To List",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function saveSearch() : array {
		$this->requireLoggedInUser();

		$result = [
			'success' => false,
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
					$search->sendNotification = 1;
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
	function toggleSearchNotification(): array {
		$this->requireLoggedInUser();

		$result = [
			'success' => false,
			'message' => 'Unable to update notification setting',
		];

		$searchId = $_REQUEST['searchId'] ?? null;
		$sendNotification = $_REQUEST['sendNotification'] ?? null;

		if ($searchId === null || $sendNotification === null) {
			return $result;
		}

		$search = new SearchEntry();
		$search->id = $searchId;

		if (!$search->find(true)) {
			return $result;
		}

		if ($search->session_id != session_id() && $search->user_id != UserAccount::getActiveUserId()) {
			return $result;
		}

		$search->sendNotification = (int)$sendNotification;

		if ($search->update() === false) {
			return $result;
		}

		return [
			'success' => true,
		];
	}


	/** @noinspection PhpUnused */
	function getSaveSearchForm() : array {
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
	function confirmCancelHold() : array {
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
			'buttons' => "<button type='button' class='tool btn btn-primary confirmCancelButton' onclick='AspenDiscovery.Account.cancelHold(\"$patronId\", \"$recordId\", \"$cancelId\", \"$isIll\")'>$cancelButtonLabel</button>",
		];
	}

	/** @noinspection PhpUnused */
	function cancelHold(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to cancel a hold.  Please close this dialog and login again.');
		$result = $this->failureResult('Cancelling hold failed', 'Error cancelling hold.');

		//Determine which user the hold is on so we can cancel it.
		$patronId = $_REQUEST['patronId'];
		$user = UserAccount::getLoggedInUser();
		$patronOwningHold = $user->getUserReferredTo($patronId);

		if ($patronOwningHold === false) {
			$result['message'] = translate([
				'text' => 'Sorry, you do not have access to cancel holds for the supplied user.',
				'isPublicFacing' => true,
			]);
		} else {
			//MDN 9/20/2015 The recordId can be empty for INN-Reach holds
			if (empty($_REQUEST['cancelId']) && empty($_REQUEST['recordId'])) {
				$result['message'] = translate([
					'text' => 'Information about the hold to be cancelled was not provided.',
					'isPublicFacing' => true,
				]);
			} else {
				$cancelId = $_REQUEST['cancelId'];
				$recordId = $_REQUEST['recordId'];
				$isIll = $_REQUEST['isIll'] ?? false;
				$result = $patronOwningHold->cancelHold($recordId, $cancelId, $isIll);
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

	/** @noinspection PhpUnused */
	function confirmCancelHoldSelected(): array {
		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'];
		$cancelId = $_REQUEST['cancelId'];
		$cancelButtonLabel = translate([
			'text' => 'Confirm Cancel Holds',
			'isPublicFacing' => true,
		]);
		return [
			'title' => translate([
				'text' => 'Cancel Holds',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Are you sure you want to cancel selected holds?',
				'isPublicFacing' => true,
			]),
			'buttons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.cancelHoldSelected(\"$patronId\", \"$recordId\", \"$cancelId\")'>$cancelButtonLabel</button>",
		];
	}

	/** @noinspection PhpUnused */
	function cancelHoldSelectedItems(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to cancel a hold.  Please close this dialog and login again.');
		$tmpResult = $this->failureResult('Error', 'Error cancelling selected holds.');

		$success = 0;
		$user = UserAccount::getLoggedInUser();
		$allHolds = $user->getHolds();
		$allUnavailableHolds = $allHolds['unavailable'];
		if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
			$total = count($_REQUEST['selected']);
			foreach ($_REQUEST['selected'] as $selected => $ignore) {
				@list($patronId, $recordId, $cancelId) = explode('|', $selected);
				$patronOwningHold = $user->getUserReferredTo($patronId);
				if ($patronOwningHold === false) {
					$tmpResult = $this->failureResult('Error', 'Sorry, it looks like you don\'t have access to that patron.');
				} else {
					$holdType = 'unknown';
					$isIll = false;
					foreach ($allUnavailableHolds as $key) {
						if ($key->sourceId == $recordId) {
							$holdType = $key->source;
							$isIll = $key->isIll;
							break;
						}
					}
					if ($holdType == 'ils') {
						$tmpResult = $patronOwningHold->cancelHold($recordId, $cancelId, $isIll);
						if (!empty($tmpResult['success'])) {
							$success++;
						}
					} elseif ($holdType == 'axis360') {
						require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
						$driver = new Axis360Driver();
						$tmpResult = $driver->cancelHold($patronOwningHold, $recordId);
						if (!empty($tmpResult['success'])) {
							$success++;
						}
					} elseif ($holdType == 'overdrive') {
						require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
						$driver = new OverDriveDriver();
						$tmpResult = $driver->cancelHold($patronOwningHold, $recordId);
						if (!empty($tmpResult['success'])) {
							$success++;
						}
					} elseif ($holdType == 'cloud_library') {
						require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
						$driver = new CloudLibraryDriver();
						$tmpResult = $driver->cancelHold($patronOwningHold, $recordId);
						if (!empty($tmpResult['success'])) {
							$success++;
						}
					} elseif ($holdType == 'hoopla') {
						require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
						$driver = new HooplaDriver();
						$tmpResult = $driver->cancelHold($patronOwningHold, $recordId);
						if (!empty($tmpResult['success'])) {
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
					$tmpResult['title'] = translate([
						'text' => 'Success',
						'isPublicFacing' => true,
					]);
				}
			}
		} else {
			$tmpResult['message'] = translate([
				'text' => 'No holds were selected to canceled',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);
		}

		return $tmpResult;
	}

	/** @noinspection PhpUnused */
	function cancelVdxRequest(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to cancel a request.  Please close this dialog and login again.');
		$result = $this->failureResult(null, 'Error cancelling request.');

		//Determine which user the request is on so we can cancel it.
		$patronId = $_REQUEST['patronId'];
		$user = UserAccount::getLoggedInUser();
		$patronOwningHold = $user->getUserReferredTo($patronId);

		if ($patronOwningHold === false) {
			$result['message'] = translate([
				'text' => 'Sorry, you do not have access to cancel requests for the supplied user.',
				'isPublicFacing' => true,
			]);
		} else {
			//MDN 9/20/2015 The recordId can be empty for INN-Reach holds
			if (empty($_REQUEST['requestId']) || !isset($_REQUEST['cancelId'])) {
				$result['message'] = translate([
					'text' => 'Information about the requests to be cancelled was not provided.',
					'isPublicFacing' => true,
				]);
			} else {
				$requestId = $_REQUEST['requestId'];
				$cancelId = $_REQUEST['cancelId'];
				$result = $patronOwningHold->cancelVdxRequest($requestId, $cancelId);
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function confirmCancelHoldAll(): array {
		$this->requireLoggedInUser();

		$cancelButtonLabel = translate([
			'text' => 'Confirm Cancel Holds',
			'isPublicFacing' => true,
		]);
		return [
			'title' => translate([
				'text' => 'Cancel Holds',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Are you sure you want to cancel all holds?',
				'isPublicFacing' => true,
			]),
			'buttons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.cancelHoldAll()'>$cancelButtonLabel</button>",
		];
	}

	/** @noinspection PhpUnused */
	function cancelAllHolds() : array {
		$this->requireLoggedInUser();
		$tmpResult = $this->failureResult('Error', 'Unable to cancel all holds');
		$user = UserAccount::getLoggedInUser();

		$allHolds = $user->getHolds();
		$allUnavailableHolds = $allHolds['unavailable'];
		$total = count($allUnavailableHolds);
		$success = 0;

		/** Hold $hold **/
		foreach ($allUnavailableHolds as $hold) {
			// cancel each hold
			$recordId = $hold->sourceId;
			$cancelId = $hold->cancelId;
			$holdType = $hold->source;
			$isIll = $hold->isIll;
			$patron = $user->getUserReferredTo($hold->userId);
			if ($patron && $hold->cancelable) {
				if ($holdType == 'ils') {
					$tmpResult = $patron->cancelHold($recordId, $cancelId, $isIll);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'axis360') {
					require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
					$driver = new Axis360Driver();
					$tmpResult = $driver->cancelHold($patron, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'overdrive') {
					require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
					$driver = new OverDriveDriver();
					$tmpResult = $driver->cancelHold($patron, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'cloud_library') {
					require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
					$driver = new CloudLibraryDriver();
					$tmpResult = $driver->cancelHold($patron, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
				} elseif ($holdType == 'hoopla') {
					require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
					$driver = new HooplaDriver();
					$tmpResult = $driver->cancelHold($patron, $recordId);
					if ($tmpResult['success']) {
						$success++;
					}
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
			$tmpResult['title'] = translate([
				'text' => 'Success',
				'isPublicFacing' => true
			]);

		}

		return $tmpResult;
	}

	/** @noinspection PhpUnused */
	function confirmReplaceHold(): array {
		$this->requireLoggedInUser();
		global $library;
		$user = UserAccount::getLoggedInUser();
		$bypassPickupChoice = false;
		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'];
		$pickupLocationId = $_REQUEST['pickupLocationId'];
		$isIll = $_REQUEST['isIll'];
		$cancelButtonLabel = translate([
			'text' => 'Confirm Place Hold',
			'isPublicFacing' => true,
		]);

		if ($user->rememberHoldPickupLocation) {
			$pickupLocation = $user->getPickupLocationCode();
			// If the pickup location defaults to the user's home location, its validity must still be checked.
			if ($pickupLocation != null && $pickupLocation->validHoldPickupBranch != 2) {
				$bypassPickupChoice = true;
				$pickupLocationId = $pickupLocation;
			}
		}
		if ($library->hidePickupLocationPrompt) {
			$numLocationsToSelectFrom = 0;
			$firstLocationCode = '';
			$locations = $user->getValidPickupBranches('ils');
			foreach ($locations as $location) {
				if (is_object($location)) {
					$numLocationsToSelectFrom++;
					if (empty($firstLocationCode)) {
						$firstLocationCode = $location->code;
					}
				}
			}
			if ($numLocationsToSelectFrom == 1) {
				$pickupLocationId = $firstLocationCode;
				$bypassPickupChoice = true;
			}
		}

		if (!$bypassPickupChoice) {
			global $interface;

			$patronOwningHold = $user->getUserReferredTo($patronId);
			$defaultPickupLocation = $user->pickupLocationId;
			$sourceId = 'ils:' . $_REQUEST['recordId'];

			$location = new Location();
			$pickupBranches = $location->getPickupBranches($patronOwningHold);
			$pickupSublocations = [];

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

				foreach ($pickupBranches as $locationKey => $location) {
					if (is_object($location)) {
						$pickupSublocations[$locationKey] = $user->getValidSublocations($location->locationId);
					}
				}

				$catalogDriver = $user->getCatalogDriver();
				if (!empty($catalogDriver) && $catalogDriver->restrictValidPickupLocationsForRecordByILS()) {
					$getPickupLocationsFromILS = $catalogDriver->getValidPickupLocationsForRecordFromILS($marcRecord->getUniqueID(), $user);
					if (!empty($getPickupLocationsFromILS['locationCodes']) && $getPickupLocationsFromILS['success']) {
						$validLocationCodesFromILS = $getPickupLocationsFromILS['locationCodes'];
						$pickupBranches = array_filter($pickupBranches, function ($location) use ($validLocationCodesFromILS) {
							if (!is_object($location)) {
								return true;
							}
							foreach ($validLocationCodesFromILS as $validCode) {
								if (str_starts_with($validCode, $location->code)) {
									return true;
								}
							}
							return false;
						});
					} elseif (empty($getPickupLocationsFromILS['useDefaultLocationFiltering'])) {
						$pickupBranches = [];
					}
				}
			}

			$interface->assign('patronId', $patronId);
			$interface->assign('defaultPickupLocation', $defaultPickupLocation);
			$interface->assign('pickupAt', $pickupAt);
			$interface->assign('pickupLocations', $pickupBranches);
			$interface->assign('pickupSublocations', $pickupSublocations);
			$pickupLocationId = null;

			return [
				'title' => translate([
					'text' => 'Place Hold',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch("MyAccount/replaceHoldForm.tpl"),
				'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.replaceHold(\"$patronId\", \"$recordId\", \"$pickupLocationId\", \"$isIll\");'>" . translate([
						'text' => 'Confirm Pickup Location',
						'isPublicFacing' => true,
					]) . '</button>',
			];
		}
		return [
			'title' => translate([
				'text' => 'Place Hold',
				'isPublicFacing' => true,
			]),
			'modalBody' => translate([
				'text' => "Are you sure you want to re-place this hold?",
				'isPublicFacing' => true,
			]),
			'modalButtons' => "<button type='button' class='tool btn btn-primary confirmCancelButton' onclick='AspenDiscovery.Account.replaceHold(\"$patronId\", \"$recordId\", \"$pickupLocationId\", \"$isIll\")'>$cancelButtonLabel</button>",
		];
	}

	/** @noinspection PhpUnused */
	function replaceHold(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to replace a hold.  Please close this dialog and login again.');
		$result = $this->failureResult('Replacing hold failed', 'Error replacing hold.');

		//Determine which user the hold is on so we can cancel it.
		$patronId = $_REQUEST['patronId'];
		$user = UserAccount::getLoggedInUser();
		$patronOwningHold = $user->getUserReferredTo($patronId);

		if ($patronOwningHold === false) {
			$result['message'] = translate([
				'text' => 'Sorry, you do not have access to replace holds for the supplied user.',
				'isPublicFacing' => true,
			]);
		} else {
			//MDN 9/20/2015 The recordId can be empty for INN-Reach holds
			if (empty($_REQUEST['cancelId']) && empty($_REQUEST['recordId'])) {
				$result['message'] = translate([
					'text' => 'Information about the hold to be replace was not provided.',
					'isPublicFacing' => true,
				]);
			} else {
				$pickupLocationId = $_REQUEST['pickupLocationId'];
				$recordId = $_REQUEST['recordId'];
				$isIll = $_REQUEST['isIll'] ?? false;
				$result = $patronOwningHold->placeHold($recordId, $pickupLocationId, $isIll);
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

		$interface->assign('placeHoldResults', $result);

		return [
			'title' => translate([
				'text' => 'Place Hold',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/replaceHold.tpl'),
			'success' => $result['success'],
		];
	}

	/** @noinspection PhpUnused */
	function freezeHold(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to freeze a hold.  Please close this dialog and login again.');
		$this->checkRequiredParameters(['patronId']);
		$user = UserAccount::getLoggedInUser();
		$result = $this->failureResult(null, 'Error freezing hold.');
		$patronId = $_REQUEST['patronId'];
		$patronOwningHold = $user->getUserReferredTo($patronId);

		if ($patronOwningHold === false) {
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
				$reactivationDate = $_REQUEST['reactivationDate'] ?? null;

				if ($_REQUEST['isAlreadyFrozen'] === 'true') {
					// If we get here, we are updating the reactivation date, so we thaw the hold and freeze it again.
					$thawResult = $patronOwningHold->thawHold($recordId, $holdId);
					if (!$thawResult['success']) {
						$message = '<div class="alert alert-danger">' . $thawResult['message'] . '</div>';
						$thawResult['message'] = $message;
						$thawResult['title'] = translate([
							'text' => 'Error',
							'isPublicFacing' => true,
						]);
						return $thawResult;
					}
				}

				$result = $patronOwningHold->freezeHold($recordId, $holdId, $reactivationDate);
				if ($result['success']) {
					$message = '<div class="alert alert-success">' . $result['message'] . '</div>';
					$result['message'] = $message;
					$result['title'] = translate([
						'text' => 'Success',
						'isPublicFacing' => true,
					]);
				} else {
					if (is_array($result['message'])) {
						/** @var string[] $messageArray */
						$messageArray = $result['message'];
						$result['message'] = implode('; ', $messageArray);
						// Millennium Holds assumes there can be more than one item processed. Here we know only one got processed,
						// but do implode as a fallback
					}
					$message = '<div class="alert alert-danger">' . $result['message'] . '</div>';
					$result['message'] = $message;
					$result['title'] = translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]);
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function confirmFreezeHoldSelected(): array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'];
		$holdId = $_REQUEST['holdId'];
		//$selected = $_REQUEST['selected'];
		$freezeButtonLabel = translate([
			'text' => 'Confirm Freeze Holds',
			'isPublicFacing' => true,
		]);

		$promptForReactivationDate = $_REQUEST['reactivationDate'] ?? false;
		if ($promptForReactivationDate === "false") {
			$promptForReactivationDate = false;
		}

		if ($promptForReactivationDate) {
			global $interface;
			$reactivateDateNotRequired = $user->reactivateDateNotRequired();
			$interface->assign('reactivateDateNotRequired', $reactivateDateNotRequired);
			$interface->assign('patronId', $patronId);
			$body = $interface->fetch('MyAccount/freezeMultipleReactivationDate.tpl');
			$button = "<button class='tool btn btn-primary' id='doFreezeHoldAllWithReactivationDate' onclick='AspenDiscovery.Account.doFreezeSelectedWithReactivationDate(\"$patronId\", \"$recordId\", \"$holdId\")'>$freezeButtonLabel</button>";
		} else {
			$body = translate([
				'text' => 'Are you sure you want to freeze selected holds?',
				'isPublicFacing' => true,
			]);
			$button = "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.freezeHoldSelected(\"$patronId\", \"$recordId\", \"$holdId\")'>$freezeButtonLabel</button>";
		}

		return [
			'title' => translate([
				'text' => 'Freeze Holds',
				'isPublicFacing' => true,
			]),
			'body' => $body,
			'buttons' => $button,
		];
	}

	/** @noinspection PhpUnused */
	function freezeHoldSelectedItems() : array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		$tmpResult = $this->failureResult(null, 'Error freezing hold.');

		$reactivationDate = $_REQUEST['reactivationDate'] ?? null;
		$user = UserAccount::getLoggedInUser();
		$allHolds = $user->getHolds();
		$allUnavailableHolds = $allHolds['unavailable'];
		$success = 0;
		$failed = 0;
		if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
			$total = count($_REQUEST['selected']);
			foreach ($_REQUEST['selected'] as $selected => $ignore) {
				@list($patronId, $recordId, $holdId) = explode('|', $selected);
				$patronOwningHold = $user->getUserReferredTo($patronId);
				if ($patronOwningHold === false) {
					$tmpResult = [
						'success' => false,
						'message' => translate([
							'text' => 'Sorry, it looks like you don\'t have access to that patron.',
							'isPublicFacing' => true,
							'inAttribute' => true,
						]),
					];
				} else {
					$frozen = 0;
					$canFreeze = 0;
					$holdType = 'unknown';
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
							$tmpResult = $patronOwningHold->freezeHold($recordId, $holdId, $reactivationDate);
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
							$tmpResult = $driver->freezeHold($patronOwningHold, $recordId);
							if ($tmpResult['success']) {
								$success++;
							} else {
								$failed++;
							}
							//cloudLibrary holds can't be frozen
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

					if ($success == 0) {
						$alertStatus = 'alert-danger';
					} else if ($success != $total) {
						$alertStatus = 'alert-warning';
					} else {
						$alertStatus = 'alert-success';
					}
					$message = '<div class="alert ' . $alertStatus . '">' . translate([
							'text' => '%1% of %2% holds were frozen',
							1 => $success,
							2 => $total,
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</div>';
					$tmpResult['message'] = $message;
					$tmpResult['title'] = translate([
						'text' => 'Your results',
						'isPublicFacing' => true,
					]);

				}
			}
		} else {
			$tmpResult['message'] = translate([
				'text' => 'No holds were selected to freeze',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);
		}

		return $tmpResult;
	}

	/** @noinspection PhpUnused */
	function confirmFreezeHoldAll(): array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$freezeButtonLabel = translate([
			'text' => 'Confirm Freeze Holds',
			'isPublicFacing' => true,
		]);

		$promptForReactivationDate = $_REQUEST['reactivationDate'] ?? false;
		if ($promptForReactivationDate === "false") {
			$promptForReactivationDate = false;
		}

		if ($promptForReactivationDate) {
			global $interface;
			$reactivateDateNotRequired = $user->reactivateDateNotRequired();
			$interface->assign('reactivateDateNotRequired', $reactivateDateNotRequired);
			$interface->assign('patronId', $patronId);
			$body = $interface->fetch('MyAccount/freezeMultipleReactivationDate.tpl');
			$button = "<button class='tool btn btn-primary' id='doFreezeHoldAllWithReactivationDate' onclick='AspenDiscovery.Account.doFreezeAllWithReactivationDate(\"$patronId\")'>$freezeButtonLabel</button>";
		} else {
			$body = translate([
				'text' => 'Are you sure you want to freeze all holds?',
				'isPublicFacing' => true,
			]);
			$button = "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.freezeHoldAll(\"$patronId\")'>$freezeButtonLabel</button>";
		}
		return [
			'title' => translate([
				'text' => 'Freeze Holds',
				'isPublicFacing' => true,
			]),
			'body' => $body,
			'buttons' => $button,
		];
	}

	/** @noinspection PhpUnused */
	function freezeHoldAll() : array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		$tmpResult['title'] = translate([
			'text' => 'Error',
			'isPublicFacing' => true,
		]);
		$tmpResult['message'] = translate([
			'text' => 'Error freezing hold.',
			'isPublicFacing' => true,
		]);
		if (!empty($_REQUEST['patronId'])) {
			$reactivationDate = $_REQUEST['reactivationDate'] ?? false;
			$tmpResult = $user->freezeAllHolds($reactivationDate);
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Modifying Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$tmpResult['message'] = translate([
				'text' => 'No Patron was specified.',
				'isPublicFacing' => true,
			]);
		}
		return $tmpResult;
	}

	/** @noinspection PhpUnused */
	function thawHold(): array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		$result = $this->failureResult(null, 'Error thawing hold.');

		if (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold === false) {
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

	/** @noinspection PhpUnused */
	function confirmThawHoldSelected(): array {
		$this->requireLoggedInUser();

		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'];
		$holdId = $_REQUEST['holdId'];
		$thawButtonLabel = translate([
			'text' => 'Confirm Thaw Holds',
			'isPublicFacing' => true,
		]);
		return [
			'title' => translate([
				'text' => 'Thaw Holds',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Are you sure you want to thaw selected holds?',
				'isPublicFacing' => true,
			]),
			'buttons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.thawHoldSelected(\"$patronId\", \"$recordId\", \"$holdId\")'>$thawButtonLabel</button>",
		];
	}

	/** @noinspection PhpUnused */
	function thawHoldSelectedItems(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to thaw a hold.  Please close this dialog and login again.');
		$tmpResult = [];

		$success = 0;
		$failed = 0;
		$user = UserAccount::getLoggedInUser();
		$allHolds = $user->getHolds();
		$allUnavailableHolds = $allHolds['unavailable'];
		if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
			$total = count($_REQUEST['selected']);
			foreach ($_REQUEST['selected'] as $selected => $ignore) {
				@list($patronId, $recordId, $holdId) = explode('|', $selected);
				$patronOwningHold = $user->getUserReferredTo($patronId);
				if ($patronOwningHold === false) {
					$tmpResult = [
						'success' => false,
						'message' => translate([
							'text' => 'Sorry, it looks like you don\'t have access to that patron.',
							'isPublicFacing' => true,
						]),
					];
				} else {
					$frozen = 0;
					$canFreeze = 0;
					$holdType = 'unknown';
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
							$tmpResult = $patronOwningHold->thawHold($recordId, $holdId);
							if ($tmpResult['success']) {
								$success++;
							} else {
								$failed++;
							}
						} elseif ($holdType == 'axis360') {
							require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
							$driver = new Axis360Driver();
							$tmpResult = $driver->thawHold($patronOwningHold, $recordId);
							if ($tmpResult['success']) {
								$success++;
							} else {
								$failed++;
							}
						} elseif ($holdType == 'overdrive') {
							require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
							$driver = new OverDriveDriver();
							$tmpResult = $driver->thawHold($patronOwningHold, $recordId);
							if ($tmpResult['success']) {
								$success++;
							} else {
								$failed++;
							}
						} else {
							$failed++;
						}
					}

					if ($success == 0) {
						$alertStatus = 'alert-danger';
					} else if ($success != $total) {
						$alertStatus = 'alert-warning';
					} else {
						$alertStatus = 'alert-success';
					}
					$message = '<div class="alert ' . $alertStatus . '">' . translate([
							'text' => '%1% of %2% holds were thawed',
							1 => $success,
							2 => $total,
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</div>';
					$tmpResult['message'] = $message;
					$tmpResult['title'] = translate([
						'text' => 'Your results',
						'isPublicFacing' => true,
					]);
				}
			}
		} else {
			$tmpResult['message'] = translate([
				'text' => 'No holds were selected to thaw',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);
		}

		return $tmpResult;
	}

	/** @noinspection PhpUnused */
	function confirmThawHoldAll(): array {
		$this->requireLoggedInUser();

		$patronId = $_REQUEST['patronId'];
		$thawButtonLabel = translate([
			'text' => 'Confirm Thaw Holds',
			'isPublicFacing' => true,
		]);
		return [
			'title' => translate([
				'text' => 'Thaw Holds',
				'isPublicFacing' => true,
			]),
			'body' => translate([
				'text' => 'Are you sure you want to thaw all holds?',
				'isPublicFacing' => true,
			]),
			'buttons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.thawHoldAll(\"$patronId\")'>$thawButtonLabel</button>",
		];
	}

	/** @noinspection PhpUnused */
	function thawHoldAll() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to modify a hold.  Please close this dialog and login again.');
		$tmpResult['title'] = translate([
			'text' => 'Error',
			'isPublicFacing' => true,
		]);
		$user = UserAccount::getLoggedInUser();

		if (!empty($_REQUEST['patronId'])) {
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
	function addList() : array {
		$this->requireLoggedInUser(null, "You must be logged in to create a list");
		$return = [];
		$user = UserAccount::getLoggedInUser();
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$title = (isset($_REQUEST['title']) && !is_array($_REQUEST['title'])) ? urldecode($_REQUEST['title']) : '';
		if (strlen(trim($title)) == 0) {
			$return['success'] = "false";
			$return['message'] = "You must provide a title for the list";
		} else {
			//If the record is not valid, skip the whole thing since the title could be bad too
			if (!empty($_REQUEST['sourceId']) && !is_array($_REQUEST['sourceId']) && $_REQUEST['source'] != 'Events' && $_REQUEST['source'] != 'CloudSource') {
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
			if (UserAccount::userHasPermission('Include Lists In Search Results')) {
				$list->searchable = isset($_REQUEST['searchable']) && $_REQUEST['searchable'] == 'true';
				$list->displayListAuthor = isset($_REQUEST['displayListAuthor']) && $_REQUEST['displayListAuthor'] == 'true';
				$list->customAuthorName = $_REQUEST['customAuthorName'] ?? '';
			}

			$list->listGroupId = -1;
			if (isset($_REQUEST['addToListGroupOption'])) {
				$addToListGroupOption = $_REQUEST['addToListGroupOption'];
				$addToListGroupNested = $_REQUEST['addToListGroupNested'] ?? 'none';
				if ($addToListGroupOption == 'new') {
					//Create a new list group
					require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
					$listGroup = new UserListGroup();
					$listGroup->title = $_REQUEST['addToListGroupNewName'];
					$listGroup->userId = $user->id;
					if ($addToListGroupNested != 'none') {
						$listGroup->parentGroupId = $addToListGroupNested;
					}
					$listGroup->insert();
					$list->listGroupId = $listGroup->id;
				} elseif ($addToListGroupOption == "existing" && is_numeric($addToListGroupNested)) {
					//Add to an existing list group
					$list->listGroupId = intval($addToListGroupNested);
				}
			}

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
							$userListEntry->title = mb_substr($groupedWork->full_title, 0, 50);
						}
					} elseif ($userListEntry->source == 'Lists') {
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$list = new UserList();
						$list->id = $userListEntry->sourceId;
						if ($list->find(true)) {
							$userListEntry->title = mb_substr($list->title, 0, 50);
						}
					} elseif ($userListEntry->source == 'Events') {
						if (str_starts_with($userListEntry->sourceId, 'communico')) {
							require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
							$recordDriver = new CommunicoEventRecordDriver($userListEntry->sourceId);
						} elseif (str_starts_with($userListEntry->sourceId, 'libcal')) {
							require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
							$recordDriver = new SpringshareLibCalEventRecordDriver($userListEntry->sourceId);
						} elseif (str_starts_with($userListEntry->sourceId, 'assabet')) {
							require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
							$recordDriver = new AssabetEventRecordDriver($userListEntry->sourceId);
						} else {
							require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
							$recordDriver = new LibraryCalendarEventRecordDriver($userListEntry->sourceId);
						}
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif ($userListEntry->source == 'OpenArchives') {
						require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
						$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif ($userListEntry->source == 'Genealogy') {
						require_once ROOT_DIR . '/sys/Genealogy/Person.php';
						$person = new Person();
						$person->personId = $userListEntry->sourceId;
						if ($person->find(true)) {
							$userListEntry->title = mb_substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
						}
					} elseif ($userListEntry->source == 'EbscoEds') {
						require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
						$recordDriver = new EbscoRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif ($userListEntry->source == 'Ebscohost') {
						require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
						$recordDriver = new EbscohostRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif ($userListEntry->source == 'Summon') {
						require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
						$recordDriver = new SummonRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif ($userListEntry->source == 'CloudSource') {
						require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
						$recordDriver = new CloudSourceRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif ($userListEntry->source == 'Gale') {
						require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
						$recordDriver = new GaleRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
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

		return $return;
	}

	/** @noinspection PhpUnused */
	function getCreateListForm() : array {
		$this->requireLoggedInUser();
		global $interface;

		if (isset($_REQUEST['sourceId'])) {
			$sourceId = $_REQUEST['sourceId'];
			$source = $_REQUEST['source'];
			$interface->assign('sourceId', $sourceId);
			$interface->assign('source', $source);
		}
		if (isset($_REQUEST['defaultGroupId'])) {
			$defaultGroupId = $_REQUEST['defaultGroupId'];
			$interface->assign('defaultGroupId', $defaultGroupId);
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

		require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
		$listGroup = new UserListGroup();
		$userListGroups = $listGroup->getListGroups(UserAccount::getActiveUserObj());
		$interface->assign('userListGroups', $userListGroups);

		$user = UserAccount::getActiveUserObj();
		$userListGroupLastAdded = $user->lastListGroupAdded;
		$userListGroupLastViewed = $user->lastListGroupViewed;
		$interface->assign('userListGroupLastAdded', $userListGroupLastAdded);
		$interface->assign('userListGroupLastViewed', $userListGroupLastViewed);

		return [
			'title' => translate([
				'text' => 'Create new List',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("MyAccount/createListForm.tpl"),
			'modalButtons' => "<button type='button' class='tool btn btn-primary' onclick='AspenDiscovery.Account.addList(); return false;'>" . translate([
					'text' => 'Create List',
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function getLoginForm() : array {
		global $interface;
		global $library;
		/** @var Location $locationSingleton */ global $locationSingleton;
		global $configArray;

		$isPrimaryAccountAuthenticationSSO = UserAccount::isPrimaryAccountAuthenticationSSO();
		$interface->assign('isPrimaryAccountAuthenticationSSO', $isPrimaryAccountAuthenticationSSO);

		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
		$interface->assign('checkRememberMe', 0);
		if ($library->defaultRememberMe && !$locationSingleton->getOpacStatus()) {
			$interface->assign('checkRememberMe', 1);
		}
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ?? 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ?? 'Library Card Number');

		//SSO
		$loginOptions = 0;
		$ssoService = null;
		if ($isPrimaryAccountAuthenticationSSO || $library->ssoSettingId != -1) {
			try {
				$ssoSettingId = null;
				if ($isPrimaryAccountAuthenticationSSO) {
					require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
					$accountProfile = new AccountProfile();
					$accountProfile->id = $library->accountProfileId;
					if ($accountProfile->find(true)) {
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

		$twoFactorStart = empty($_SESSION['twoFactorStart']) ? 0 : $_SESSION['twoFactorStart'];
		//Expire 2 factor after 5 minutes
		$twoFactorExpired = $twoFactorStart < time() - (5 * 60);

		if (!empty($_SESSION['enroll2FA'])) {
			if ($twoFactorExpired) {
				//We have an abandoned 2-factor authentication enrollment
				UserAccount::softLogout();
			} else {
				return $this->get2FAEnrollment();
			}
		} elseif (!empty($_SESSION['has2FA'])) {
			if ($twoFactorExpired) {
				//We have an abandoned 2-factor authentication enrollment
				UserAccount::softLogout();
			} else {
				$authStatus = UserAccount::get2FAMethodStatus();
				$interface->assign('setupMethods', $authStatus['setupMethods']);
				$interface->assign('hasTotp', $authStatus['hasTotp']);
				$interface->assign('hasEmail', $authStatus['hasEmail']);
				$interface->assign('codeSent', !empty($_SESSION['codeSent']));
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
					'closeDestination' => '/MyAccount/Logout'
				];
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

		$enableForgotBarcode = 0;
		if ($library->enableForgotBarcode && $library->twilioSettingId != -1) {
			$enableForgotBarcode = $library->enableForgotBarcode;
		}
		$interface->assign('enableForgotBarcode', $enableForgotBarcode);

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if ($catalog != null) {
			$interface->assign('tos', false);
			if ($catalog->accountProfile != null && $catalog->accountProfile->ils == "symphony") {
				$selfRegTerms = $catalog->getSelfRegistrationTerms();
				if ($selfRegTerms != null) {
					$interface->assign('tos', true);
				}
			}
			$interface->assign('forgotPasswordType', $catalog->getForgotPasswordType());
			if (!$library->enableForgotPasswordLink) {
				$interface->assign('forgotPasswordType', 'none');
			}
		} else {
			$interface->assign('forgotPasswordType', 'none');
		}

		if (isset($_REQUEST['multiStep'])) {
			$multiStep = true;
			$interface->assign('multiStep', true);
		} else {
			$multiStep = false;
		}

		//return $interface->fetch('MyAccount/ajax-login.tpl');
		$loginButtons = '';
		if ($interface->getVariable('ssoIsEnabled') && !$interface->getVariable('$ssoStaffOnly') && $interface->getVariable('ssoService') == 'ldap') {
			$loginButtons .= '<input type="hidden" id="ldapLogin" value="true">';
		}

		$loginButtons .= '<input type="hidden" id="multiStep" name="multiStep" value="';
		if (!empty($multiStep)) {
			$loginButtons .= 'true';
		} else {
			$loginButtons .= 'false';
		}
		$loginButtons .= '"/>';
		if ($interface->getVariable('ssoIsEnabled') && !$interface->getVariable('ssoStaffOnly') && $interface->getVariable('ssoService') == 'ldap' && !empty($interface->getVariable('ldapLabel'))) {
			$loginButtons .= '<input type="submit" name="submit" value="' . translate([
					'text' => "Sign in with %1%",
					'1' => $interface->getVariable('ldapLabel'),
					'isPublicFacing' => true
				]) . ' id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return AspenDiscovery.Account.processAjaxLogin();">';
		} else {
			$loginButtons .= '<input type="submit" name="submit" value="';
			if (!empty($multiStep)) {
				$loginButtons .= translate([
					'text' => "Continue",
					'isPublicFacing' => true,
					'inAttribute' => true
				]);
			} else {
				$loginButtons .= translate([
					'text' => "Sign In",
					'isPublicFacing' => true,
					'inAttribute' => true
				]);
			}
			$loginButtons .= '" id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return AspenDiscovery.Account.processAjaxLogin();">';
		}
		return [
			'success' => true,
			'title' => translate([
				'text' => 'Sign In',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/ajax-login.tpl'),
			'buttons' => $loginButtons,
		];
	}

	/** @noinspection PhpUnused */
	function getMasqueradeAsForm() : array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		if (!$user->canMasquerade()) {
			$this->failureResult("Error", "You do not have permissions to masquerade");
		}
		global $library;
		global $interface;
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$interface->assign('supportsLoginWithUsername', $catalog->supportsLoginWithUsername());
		$interface->assign('allowMasqueradeWithUsername', $library->allowMasqueradeWithUsername);
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
	function initiateMasquerade() : array {
		$this->requireLoggedInUser();
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::initiateMasquerade();
	}

	/** @noinspection PhpUnused */
	function endMasquerade() : array {
		$this->requireLoggedInUser();
		require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
		return MyAccount_Masquerade::endMasquerade();
	}

	/** @noinspection PhpUnused */
	function getChangeHoldLocationForm(): array {
		$this->requireLoggedInUser(null, "You must be logged in.  Please close this dialog and login before changing your hold's pick-up location.");
		global $interface;
		$user = UserAccount::getLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$interface->assign('patronId', $patronId);
		$patronOwningHold = $user->getUserReferredTo($patronId);

		$id = $_REQUEST['holdId'];
		$interface->assign('holdId', $id);

		$recordId = $_REQUEST['recordId'];
		$sourceId = $_REQUEST['source'] . ":" . $_REQUEST['recordId'];

		$currentLocation = $_REQUEST['currentLocation'];
		$currentSublocationId = $_REQUEST['currentSublocation'];
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
		$interface->assign('currentSublocation', $currentSublocationId);

		$location = new Location();
		$pickupBranches = $location->getPickupBranches($patronOwningHold);
		$pickupSublocations = [];

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

			foreach ($pickupBranches as $locationKey => $location) {
				if (is_object($location)) {
					$pickupSublocations[$locationKey] = $user->getValidSublocations($location->locationId);
				}
			}

			$catalogDriver = $user->getCatalogDriver();
			if (!empty($catalogDriver) && $catalogDriver->restrictValidPickupLocationsForRecordByILS()) {
				$getPickupLocationsFromILS = $catalogDriver->getValidPickupLocationsForRecordFromILS($marcRecord->getUniqueID(), $user);
				if (!empty($getPickupLocationsFromILS['locationCodes']) && $getPickupLocationsFromILS['success']) {
					$validLocationCodesFromILS = $getPickupLocationsFromILS['locationCodes'];
					$pickupBranches = array_filter($pickupBranches, function ($location) use ($validLocationCodesFromILS) {
						if (!is_object($location)) {
							return true;
						}
						foreach ($validLocationCodesFromILS as $validCode) {
							if (str_starts_with($validCode, $location->code)) {
								return true;
							}
						}
						return false;
					});
				} elseif (empty($getPickupLocationsFromILS['useDefaultLocationFiltering'])) {
					$pickupBranches = [];
				}
			}
		}

		$interface->assign('pickupAt', $pickupAt);
		$interface->assign('pickupLocations', $pickupBranches);
		$interface->assign('pickupSublocations', $pickupSublocations);

		return [
			'title' => translate([
				'text' => 'Change Hold Location',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("MyAccount/changeHoldLocation.tpl"),
			'modalButtons' => '<button type="button" class="tool btn btn-primary" onclick="AspenDiscovery.Account.doChangeHoldLocation(); return false;">' . translate([
					'text' => 'Change Location',
					'isPublicFacing' => true,
				]) . '</button>',
		];
	}

	/** @noinspection PhpUnused */
	function getReactivationDateForm() : array {
		$this->requireLoggedInUser();
		global $interface;

		$user = UserAccount::getLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$patronOwningHold = $user->getUserReferredTo($patronId);
		$isAlreadyFrozen = $_REQUEST['isAlreadyFrozen'];
		if ($patronOwningHold !== false) {
			$id = $_REQUEST['holdId'];
			$interface->assign('holdId', $id);
			$interface->assign('patronId', $patronId);
			$interface->assign('recordId', $_REQUEST['recordId']);
			$interface->assign('isAlreadyFrozen', $isAlreadyFrozen);

			$reactivateDateNotRequired = $user->reactivateDateNotRequired();
			$interface->assign('reactivateDateNotRequired', $reactivateDateNotRequired);

			// Fetch the hold's createDate to calculate maxDaysToFreeze from hold placement date (Koha only)
			$accountProfile = $patronOwningHold->getAccountProfile();
			if ($accountProfile != null && $accountProfile->ils == 'koha') {
				require_once ROOT_DIR . '/sys/User/Hold.php';
				$hold = new Hold();
				$hold->cancelId = $id;
				$hold->userId = $patronOwningHold->id;
				if ($hold->find(true) && !empty($hold->createDate)) {
					$interface->assign('holdCreateDate', $hold->createDate);
				}
			}

			$title = translate([
				'text' => $isAlreadyFrozen === 'true' ? 'Change Activation Date' : 'Freeze Hold',
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
	function changeHoldLocation() : array {
		$this->requireLoggedInUser(null, "You must be logged in.  Please close this dialog and login to change this hold's pick up location.");
		try {
			$holdId = $_REQUEST['holdId'];
			$newPickupLocation = $_REQUEST['newLocation'];
			$newPickupSublocation = $_REQUEST['newSublocation'] ?? null;
			$pickupSublocation = null;
			if (!empty($newPickupSublocation)) {
				//In the form this is set as the id of the sublocation in Aspen, but we want to pass the ILS ID
				require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
				$pickupSublocationObject = new Sublocation();
				$pickupSublocationObject->id = $newPickupSublocation;
				if ($pickupSublocationObject->find(true)) {
					$pickupSublocation = $pickupSublocationObject->ilsId;
				}
			}

			$user = UserAccount::getLoggedInUser();
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);
			if ($patronOwningHold !== false) {
				if ($patronOwningHold->validatePickupBranch($newPickupLocation)) {
					return $patronOwningHold->changeHoldPickUpLocation($holdId, $newPickupLocation, $pickupSublocation);
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
	function requestPinReset() : array {
		$catalog = CatalogFactory::getCatalogConnectionInstance();

		//Get the list of pickup branch locations for display in the user interface.
		return $catalog->processEmailResetPinForm();
	}

	/** @noinspection PhpUnused */
	function getCitationFormatsForm(): array {
		global $interface;
		$interface->assign('listId', $_REQUEST['listId']);
		$interface->assign('selectedResourceTypes', $_REQUEST['selectedResourceTypes']);
		$interface->assign('activeFilters', $_REQUEST['activeFilters']);
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
	function sendMyListEmail(): array {
		global $interface;

		// Get data from AJAX request
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) { // validly formatted List ID
			$listId = $_REQUEST['listId'];
			$to = $_REQUEST['to'];
			$from = $_REQUEST['from'] ?? '';
			$message = $_REQUEST['message'];

			//Load the list
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $listId;
			if ($list->find(true)) {
				// Load the User object for the owner of the list (if necessary):
				if ($list->public || (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $list->user_id)) {
					$_GET['id'] = $list->id;
					$selectedResourceTypes = empty($_REQUEST['selectedResourceTypes']) ? [] : explode('|', $_REQUEST['selectedResourceTypes']);
					$activeFilters = empty($_REQUEST['activeFilters']) ? [] : explode('|', $_REQUEST['activeFilters']);

					//The user can access the list
					if (count($selectedResourceTypes) && in_array('GroupedWork', $selectedResourceTypes) && !empty($activeFilters)) {
						$titleDetailInfo = $list->getListRecordsUsingSolr(0, -1, false, 'recordDrivers', null, null, $activeFilters);
						$titleDetails = $titleDetailInfo['formattedRecords'];
					} else {
						$titleDetails = $list->getListRecords(0, -1, false, 'recordDrivers', null, null, false, 0, $selectedResourceTypes);
					}
					// get all titles for email list, not just a page's worth
					$interface->assign('titles', $titleDetails);
					$interface->assign('list', $list);

					if (!str_contains($message, 'http') && !str_contains($message, 'mailto') && $message == strip_tags($message)) {
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
	function getEmailMyListForm(): array {
		global $interface;
		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) {
			$listId = $_REQUEST['listId'];

			$interface->assign('listId', $listId);
			$interface->assign('selectedResourceTypes', $_REQUEST['selectedResourceTypes']);
			$interface->assign('activeFilters', $_REQUEST['activeFilters']);

			return [
				'title' => 'Email a list',
				'modalBody' => $interface->fetch('MyAccount/emailListPopup.tpl'),
				'modalButtons' => '<button type="button" class="btn btn-primary" onclick="$(\'#emailListForm\').submit();">' . translate([
						'text' => 'Send Email',
						'isPublicFacing' => true,
						'inAttribute' => true,
					]) . '</button>',
			];
		} else {
			return $this->failureResult(null, 'You must provide the id of the list to email');
		}
	}

	function renewCheckout(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['patronId']);
		$user = UserAccount::getLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$recordId = $_REQUEST['recordId'] ?? '';
		$renewIndicator = $_REQUEST['renewIndicator'] ?? '';
		$patron = $user->getUserReferredTo($patronId);
		$itemId = null;
		$itemIndex = null;

		if ($patron) {
			$accountProfile = $patron->getAccountProfile();
			$driver = $accountProfile?->driver ?? '';

			// Sierra ILL does not have recordId
			$requiresRecordId = $driver !== 'Sierra';

			// Evolve does not require a renew indicator
			$requiresRenewIndicator = $driver !== 'Evolve';

			if ($requiresRecordId) {
				$this->checkRequiredParameters(['recordId']);
			}
			if ($requiresRenewIndicator) {
				$this->checkRequiredParameters(['renewIndicator']);
				if (strpos($renewIndicator, '|') > 0) {
					[
						$itemId,
						$itemIndex,
					] = explode('|', $renewIndicator);
				} else {
					$itemId = $renewIndicator;
					$itemIndex = null;
				}
			}
			$renewResults = $patron->renewCheckout($recordId, $itemId, $itemIndex);
		} else {
			$renewResults = $this->failureResult(null, 'Sorry, it looks like you don\'t have access to that patron.');
		}


		global $interface;
		$interface->assign('renewResults', $renewResults);
		if (isset($renewResults['confirmRenewalFee']) && $renewResults['confirmRenewalFee']) {
			return [
				'title' => translate([
					'text' => 'Confirm Renewal',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('MyAccount/renew-item-results.tpl'),
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Account.confirmRenewalFee('$patronId', '$recordId', '$renewIndicator');\" class=\"modal-buttons btn btn-primary\">" . translate([
						'text' => 'Renew Item',
						'isAdminFacing' => true,
					]) . '</button>',
				'success' => $renewResults['success'],
			];
		}

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
	function renewSelectedItems() : array {
		$this->requireLoggedInUser();

		if (isset($_REQUEST['selected'])) {
			$user = UserAccount::getLoggedInUser();
			if (method_exists($user, 'renewCheckout')) {
				$failure_messages = [];
				$renewResults = [];
				$failedRenewals = 0;
				if (is_array($_REQUEST['selected'])) {
					foreach ($_REQUEST['selected'] as $selected => $ignore) {
						//Suppress errors because sometimes we don't get an item index
						@list($patronId, $recordId, $itemId, $itemIndex) = explode('|', $selected);
						$patron = $user->getUserReferredTo($patronId);
						if ($patron) {
							$tmpResult = $patron->renewCheckout($recordId, $itemId, $itemIndex);
						} else {
							$tmpResult = $this->failureResult(null, 'Sorry, it looks like you don\'t have access to that patron.');
						}

						if (!$tmpResult['success']) {
							$failedRenewals++;
							if (isset($tmpResult['confirmRenewalFee']) && $tmpResult['confirmRenewalFee']) {
								$failure_messages = translate([
									'text' => 'Renewing some overdue items will result in a charge to your account. Please renew overdue items individually.',
									'isPublicFacing' => true,
								]);
							} else {
								$failure_messages[] = $tmpResult['message'];
							}
						}
					}
					$renewResults['Total'] = count($_REQUEST['selected']);
					$renewResults['NotRenewed'] = $failedRenewals;
					$renewResults['Renewed'] = $renewResults['Total'] - $failedRenewals;
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
				$renewResults = $this->failureResult(null, 'Cannot Renew Items - ILS Not Supported.');
			}
		} else {
			//error message
			$renewResults = $this->failureResult(null, 'Items to renew not specified.');
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
			'renewed' => $renewResults['Renewed'] ?? [],
		];
	}

	function renewAll(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to renew titles');
		$renewResults = [
			'success' => false,
			'message' => ['Unable to renew all titles'],
		];
		$user = UserAccount::getLoggedInUser();
		if ($user){
			// Renew linked accounts as well if applicable
			$renewResults = $user->renewAll(true);
		} else {
			$renewResults = $this->failureResult(null, 'Sorry, it looks like you don\'t have access to that patron.');
		}

		global $interface;
		$interface->assign('renew_message_data', $renewResults);
		if (isset($renewResults['confirmRenewalFee']) && $renewResults['confirmRenewalFee']) {
			return [
				'title' => translate([
					'text' => 'Confirm Renewal',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('Record/renew-results.tpl'),
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Account.confirmRenewalFeeAll();\" class=\"modal-buttons btn btn-primary\">" . translate([
						'text' => 'Renew Items',
						'isPublicFacing' => true,
					]) . '</button>',
				'success' => $renewResults['success'],
			];
		}

		return [
			'title' => translate([
				'text' => 'Renew All',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('Record/renew-results.tpl'),
			'success' => $renewResults['success'],
			'renewed' => $renewResults['Renewed'] ?? 0,
		];
	}

	/** @noinspection PhpUnused */
	function setListEntryPositions() : array {
		$this->requireLoggedInUser();
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
	function getMenuDataIls() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		global $interface;

		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->hasIlsConnection()) {
			$ilsSummary = $user->getAccountSummary();
			$ilsSummary->setMaterialsRequests($user->getNumMaterialsRequests());
			if ($user->getLinkedUsers() != null) {
				$selectedLinkedUser = $this->setFilterLinkedUser();
				$selectedLinkedUserCheckouts = $this->setFilterLinkedUserCheckouts();
				if ($selectedLinkedUser) {
					$filterLinkedUser = new User();
					$filterLinkedUser->id = $selectedLinkedUser;
					if ($filterLinkedUser->find(true)) {
						$filterLinkedUserSummary = $filterLinkedUser->getAccountSummary();
						$ilsSummary->numAvailableHolds = $filterLinkedUserSummary->numAvailableHolds;
						$ilsSummary->numUnavailableHolds = $filterLinkedUserSummary->numUnavailableHolds;
					}
				} else {
					/** @var User $user */
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $linkedUser->getAccountSummary();
						$ilsSummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
						$ilsSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;

					}
				}
				if ($selectedLinkedUserCheckouts) {
					$filterLinkedUserCheckouts = new User();
					$filterLinkedUserCheckouts->id = $selectedLinkedUserCheckouts;
					if ($filterLinkedUserCheckouts->find(true)) {
						$filterLinkedUserCheckoutsSummary = $filterLinkedUserCheckouts->getAccountSummary();
						$ilsSummary->numCheckedOut = $filterLinkedUserCheckoutsSummary->numCheckedOut;
						$ilsSummary->numOverdue = $filterLinkedUserCheckoutsSummary->numOverdue;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $linkedUser->getAccountSummary();
						$ilsSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
						$ilsSummary->numOverdue += $linkedUserSummary->numOverdue;
					}
				}
				foreach ($user->getLinkedUsers() as $linkedUser) {
					$linkedUserSummary = $linkedUser->getAccountSummary();
					$ilsSummary->totalFines += $linkedUserSummary->totalFines;
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

			$showRenewalLink = $user->showRenewalLink($ilsSummary);
			$interface->assign('showRenewalLink', $showRenewalLink);
			if ($showRenewalLink) {
				$userLibrary = $user->getHomeLibrary();
				if ($userLibrary->enableCardRenewal == 2) {
					if (!empty($userLibrary->cardRenewalUrl)) {
						$interface->assign('cardRenewalLink', $userLibrary->cardRenewalUrl);
					}
				} elseif ($userLibrary->enableCardRenewal == 3) {
					require_once ROOT_DIR . '/sys/Enrichment/QuipuECardSetting.php';
					$quipuECardSettings = new QuipuECardSetting();
					if ($quipuECardSettings->find(true) && $quipuECardSettings->hasERenew) {
						$interface->assign('cardRenewalLink', "/MyAccount/eRENEW");
					}
				}
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataCloudLibrary() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->isValidForEContentSource('cloud_library')) {
			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$cloudLibrarySummary = $driver->getAccountSummary($user);
			if ($user->getLinkedUsers() != null) {
				/** @var User $user */

				$selectedLinkedUser = $this->setFilterLinkedUser();
				$selectedLinkedUserCheckouts = $this->setFilterLinkedUserCheckouts();
				if ($selectedLinkedUser) {
					$filterLinkedUser = new User();
					$filterLinkedUser->id = $selectedLinkedUser;
					if ($filterLinkedUser->find(true)) {
						$filterLinkedUserSummary = $driver->getAccountSummary($filterLinkedUser);
						$cloudLibrarySummary->numAvailableHolds = $filterLinkedUserSummary->numAvailableHolds;
						$cloudLibrarySummary->numUnavailableHolds = $filterLinkedUserSummary->numUnavailableHolds;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$cloudLibrarySummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
						$cloudLibrarySummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
					}
				}
				if ($selectedLinkedUserCheckouts) {
					$filterLinkedUserCheckouts = new User();
					$filterLinkedUserCheckouts->id = $selectedLinkedUserCheckouts;
					if ($filterLinkedUserCheckouts->find(true)) {
						$filterLinkedUserCheckoutsSummary = $driver->getAccountSummary($filterLinkedUserCheckouts);
						$cloudLibrarySummary->numCheckedOut = $filterLinkedUserCheckoutsSummary->numCheckedOut;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$cloudLibrarySummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
					}
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataAxis360() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->isValidForEContentSource('axis360')) {
			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$axis360Summary = $driver->getAccountSummary($user);
			if ($user->getLinkedUsers() != null) {
				/** @var User $user */
				$selectedLinkedUser = $this->setFilterLinkedUser();
				$selectedLinkedUserCheckouts = $this->setFilterLinkedUserCheckouts();
				if ($selectedLinkedUser) {
					$filterLinkedUser = new User();
					$filterLinkedUser->id = $selectedLinkedUser;
					if ($filterLinkedUser->find(true)) {
						$filterLinkedUserSummary = $driver->getAccountSummary($filterLinkedUser);
						$axis360Summary->numAvailableHolds = $filterLinkedUserSummary->numAvailableHolds;
						$axis360Summary->numUnavailableHolds = $filterLinkedUserSummary->numUnavailableHolds;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$axis360Summary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
						$axis360Summary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
					}
				}
				if ($selectedLinkedUserCheckouts) {
					$filterLinkedUserCheckouts = new User();
					$filterLinkedUserCheckouts->id = $selectedLinkedUserCheckouts;
					if ($filterLinkedUserCheckouts->find(true)) {
						$filterLinkedUserCheckoutsSummary = $driver->getAccountSummary($filterLinkedUserCheckouts);
						$axis360Summary->numCheckedOut = $filterLinkedUserCheckoutsSummary->numCheckedOut;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$axis360Summary->numCheckedOut += $linkedUserSummary->numCheckedOut;

					}
				}
			}
			$timer->logTime("Loaded Boundless Summary for User and linked users");
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataHoopla() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->isValidForEContentSource('hoopla')) {
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$hooplaSummary = $driver->getAccountSummary($user);

			if ($user->getLinkedUsers() != null) {
				/** @var User $user */
				$selectedLinkedUserCheckouts = $this->setFilterLinkedUserCheckouts();
				if ($selectedLinkedUserCheckouts) {
					$filterLinkedUserCheckouts = new User();
					$filterLinkedUserCheckouts->id = $selectedLinkedUserCheckouts;
					if ($filterLinkedUserCheckouts->find(true)) {
						$filterLinkedUserCheckoutsSummary = $driver->getAccountSummary($filterLinkedUserCheckouts);
						$hooplaSummary->numCheckedOut = $filterLinkedUserCheckoutsSummary->numCheckedOut;
						$hooplaSummary->numCheckoutsRemaining = $filterLinkedUserCheckoutsSummary->numCheckoutsRemaining;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);

						$hooplaSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
						$hooplaSummary->numCheckoutsRemaining += $linkedUserSummary->numCheckoutsRemaining;
						$hooplaSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
						$hooplaSummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataOverdrive() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$driver = new OverDriveDriver();
		$readerName = $driver->getReaderName();
		if ($user->isValidForEContentSource('overdrive')) {
			$overDriveSummary = $driver->getAccountSummary($user);
			if ($user->getLinkedUsers() != null) {
				/** @var User $user */

				$selectedLinkedUser = $this->setFilterLinkedUser();
				$selectedLinkedUserCheckouts = $this->setFilterLinkedUserCheckouts();
				if ($selectedLinkedUser) {
					$filterLinkedUser = new User();
					$filterLinkedUser->id = $selectedLinkedUser;
					if ($filterLinkedUser->find(true)) {
						$filterLinkedUserSummary = $driver->getAccountSummary($filterLinkedUser);
						$overDriveSummary->numAvailableHolds = $filterLinkedUserSummary->numAvailableHolds;
						$overDriveSummary->numUnavailableHolds = $filterLinkedUserSummary->numUnavailableHolds;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$overDriveSummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
						$overDriveSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
					}
				}
				if ($selectedLinkedUserCheckouts) {
					$filterLinkedUserCheckouts = new User();
					$filterLinkedUserCheckouts->id = $selectedLinkedUserCheckouts;
					if ($filterLinkedUserCheckouts->find(true)) {
						$filterLinkedUserCheckoutsSummary = $driver->getAccountSummary($filterLinkedUserCheckouts);
						$overDriveSummary->numCheckedOut = $filterLinkedUserCheckoutsSummary->numCheckedOut;

					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$overDriveSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
					}
				}
			}
			$timer->logTime("Loaded " . $readerName . " Summary for User and linked users");
			$result = [
				'success' => true,
				'summary' => $overDriveSummary->toArray(),
			];
		} else {
			$result['message'] = 'Invalid for ' . $readerName;
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataPalaceProject() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->isValidForEContentSource('palace_project')) {
			require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
			$driver = new PalaceProjectDriver();
			$palaceProjectSummary = $driver->getAccountSummary($user);
			if ($user->getLinkedUsers() != null) {
				/** @var User $user */
				$selectedLinkedUser = $this->setFilterLinkedUser();
				$selectedLinkedUserCheckouts = $this->setFilterLinkedUserCheckouts();
				if ($selectedLinkedUser) {
					$filterLinkedUser = new User();
					$filterLinkedUser->id = $selectedLinkedUser;
					if ($filterLinkedUser->find(true)) {
						$filterLinkedUserSummary = $driver->getAccountSummary($filterLinkedUser);
						$palaceProjectSummary->numAvailableHolds = $filterLinkedUserSummary->numAvailableHolds;
						$palaceProjectSummary->numUnavailableHolds = $filterLinkedUserSummary->numUnavailableHolds;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$palaceProjectSummary->numAvailableHolds += $linkedUserSummary->numAvailableHolds;
						$palaceProjectSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
					}
				}
				if ($selectedLinkedUserCheckouts) {
					$filterLinkedUserCheckouts = new User();
					$filterLinkedUserCheckouts->id = $selectedLinkedUserCheckouts;
					if ($filterLinkedUserCheckouts->find(true)) {
						$filterLinkedUserCheckoutsSummary = $driver->getAccountSummary($filterLinkedUserCheckouts);
						$palaceProjectSummary->numCheckedOut = $filterLinkedUserCheckoutsSummary->numCheckedOut;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$palaceProjectSummary->numCheckedOut += $linkedUserSummary->numCheckedOut;
					}
				}
			}
			$timer->logTime("Loaded Palace Project Summary for User and linked users");
			$result = [
				'success' => true,
				'summary' => $palaceProjectSummary->toArray(),
			];
		} else {
			$result['message'] = translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getMenuDataInterlibraryLoan() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->hasInterlibraryLoan()) {
			require_once ROOT_DIR . '/Drivers/VdxDriver.php';
			$driver = new VdxDriver();
			$vdxSummary = $driver->getAccountSummary($user);
			if ($user->getLinkedUsers() != null) {
				/** @var User $user */
				$selectedLinkedUser = $this->setFilterLinkedUser();
				if ($selectedLinkedUser) {
					$filterLinkedUser = new User();
					$filterLinkedUser->id = $selectedLinkedUser;
					if ($filterLinkedUser->find(true)) {
						$filterLinkedUserSummary = $driver->getAccountSummary($filterLinkedUser);
						$vdxSummary->numUnavailableHolds = $filterLinkedUserSummary->numUnavailableHolds;
					}
				} else {
					foreach ($user->getLinkedUsers() as $linkedUser) {
						$linkedUserSummary = $driver->getAccountSummary($linkedUser);
						$vdxSummary->numUnavailableHolds += $linkedUserSummary->numUnavailableHolds;
					}
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getRatingsData() : array {
		$this->requireLoggedInUser();
		global $interface;
		$result = [];

		$user = UserAccount::getLoggedInUser();
		$interface->assign('user', $user);

		//Count of ratings
		$result['ratings'] = $user->getNumRatings();
		$result['notInterested'] = $user->getNumNotInterested();

		return $result;
	}

	/** @noinspection PhpUnused */
	function getListData() : array {
		$this->requireLoggedInUser();
		global $timer;
		global $interface;
		global $configArray;
		global $memCache;
		$result = [];

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

		return $result;
	}

	/** @noinspection PhpUnused */
	public function exportCheckouts() : array {
		$this->requireLoggedInUser();

		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		$source = $_REQUEST['source'];
		$user = UserAccount::getActiveUserObj();
		$allCheckedOut = $user->getCheckouts(true, $source);
		$selectedSortOption = $this->setSortByUserObj('sort', 'checkout', $user);
		if ($selectedSortOption == null) {
			$selectedSortOption = 'dueDate';
		}

		$selectedUser = $this->setFilterLinkedUserCheckouts();

		$selectedCheckouts = isset($_REQUEST['selectedCheckouts']) ? json_decode($_REQUEST['selectedCheckouts'], true) : [];
		if (!empty($selectedCheckouts)) {
			$allCheckedOut = $this->filterCheckoutsBySelected($allCheckedOut, $selectedCheckouts, $selectedUser);
		} else {
			$allCheckedOut = $this->filterCheckoutsByUser($allCheckedOut, $selectedUser);
		}

		$allCheckedOut = $this->sortCheckouts($selectedSortOption, $allCheckedOut);

		$hasLinkedUsers = count($user->getLinkedUsers()) > 0;

		$showOut = $user->showOutDateInCheckouts();
		$showRenewed = $user->showTimesRenewed();
		$showWaitList = $user->showWaitListInCheckouts();

		try {
			// Redirect output to a client's web browser
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="CheckedOutItems.csv"');
			header('Cache-Control: max-age=0');
			$fp = fopen('php://output', 'w');

			$fields = [
				'Title',
				'Author',
				'Format'
			];
			if ($showOut) {
				$fields[] = 'Out';
			}
			$fields[] = 'Due';
			if ($showRenewed) {
				$fields[] = 'Renewed';
			}
			if ($showWaitList) {
				$fields[] = 'Wait List';
			}
			if ($hasLinkedUsers) {
				$fields[] = 'User';
			}

			fputcsv($fp, $fields);

			//Loop Through The Report Data
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


				$row = array(
					$title,
					$author,
					$format
				);
				if ($showOut) {
					$row[] = $checkoutDate;
				}
				$row[] = $dueDate;
				if ($showRenewed) {
					$row[] = $Renewed;
				}
				if ($showWaitList) {
					$row[] = $waitList;
				}
				if ($hasLinkedUsers) {
					$row[] = $userName;
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
	public function exportHolds() : array {
		$this->requireLoggedInUser();

		$source = $_REQUEST['source'];
		$user = UserAccount::getActiveUserObj();

		$showPosition = $user->showHoldPosition();
		$selectedAvailableSortOption = $this->setSortByUserObj('availableHoldSort', 'availableHold', $user);
		$selectedUnavailableSortOption = $this->setSortByUserObj('unavailableHoldSort', 'unavailableHold', $user);
		if ($selectedAvailableSortOption == null) {
			$selectedAvailableSortOption = 'expire';
		}
		if ($selectedUnavailableSortOption == null) {
			$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
		}
		$selectedUser = $this->setFilterLinkedUser();
		$selectedHolds = isset($_REQUEST['selectedHolds']) ? json_decode($_REQUEST['selectedHolds'], true) : [];

		if (!empty($selectedHolds)) {
			$allHolds = $this->filterHoldsBySelected($user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, $source), $selectedHolds);
		} else {
			$allHolds = $this->filterHolds($user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, $source), $selectedUser);
		}


		$showDateWhenSuspending = $user->showDateWhenSuspending();

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename="Holds.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');


		$hasLinkedUsers = count($user->getLinkedUsers()) > 0;
		try {
			for ($i = 0; $i < 3; $i++) {
				if ($i == 0) {
					$exportType = "available";
				} elseif ($i == 1) {
					$exportType = "cancelled";
				} else {
					$exportType = "unavailable";
				}
				if (count($allHolds[$exportType]) == 0) {
					continue;
				}
				$holdType = translate([
					'text' => 'Holds - ' . ucfirst($exportType),
					'isPublicFacing' => true,
				]);
				$header = array($holdType);
				fputcsv($fp, $header);
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
				if ($exportType == "available") {
					// Column names
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

					$availFields = [
						$titleCol,
						$authorCol,
						$formatCol,
						$placedCol,
						$pickupCol,
						$statusCol,
						$pickupByCol
					];
					if ($hasLinkedUsers) {
						$availFields[] = $userCol;
					}
					fputcsv($fp, $availFields);

					/** @var Hold $row * */
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

						$availValues = [
							$title,
							$author,
							$format,
							$placed,
							$pickup,
							$status,
							$expireDate,
							$user
						];
						if ($hasLinkedUsers) {
							$availValues[] = $user;
						}
						fputcsv($fp, $availValues);
					}
				} elseif ($exportType == "cancelled") {
					// Col names
					$pickupCol = translate([
						'text' => 'Pickup',
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

					$cancelledFields = [
						$titleCol,
						$authorCol,
						$formatCol,
						$pickupCol
					];
					$cancelledFields[] = $statusCol;
					if ($hasLinkedUsers) {
						$cancelledFields[] = $userCol;
					}
					fputcsv($fp, $cancelledFields);

					foreach ($allHolds['cancelled'] as $row) {
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

						$pickup = $row->pickupLocationName ?? '';

						$status = $row->status ?? '';

						if (isset($row->frozen) && $row->frozen && $showDateWhenSuspending && !empty($row->reactivateDate)) {
							$reactivateTime = $this->isValidTimeStamp($row->reactivateDate) ? $row->reactivateDate : strtotime($row->reactivateDate);
							$status .= " until " . date('M d, Y', $reactivateTime);
						}

						$user = $row->getUserName();

						$cancelledValues = [
							$title,
							$author,
							$format,
							$pickup
						];
						$cancelledValues[] = $status;
						if ($hasLinkedUsers) {
							$cancelledValues[] = $user;
						}
						fputcsv($fp, $cancelledValues);
					}
				} else {
					// Section header
					// Col names
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

					$unavailFields = [
						$titleCol,
						$authorCol,
						$formatCol,
						$placedCol,
						$pickupCol
					];
					if ($showPosition) {
						$unavailFields[] = $positionCol;
					}
					$unavailFields[] = $statusCol;
					if ($hasLinkedUsers) {
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

						$status = $row->status ?? '';

						if (isset($row->frozen) && $row->frozen && $showDateWhenSuspending && !empty($row->reactivateDate)) {
							$reactivateTime = $this->isValidTimeStamp($row->reactivateDate) ? $row->reactivateDate : strtotime($row->reactivateDate);
							$status .= " until " . date('M d, Y', $reactivateTime);
						}

						$position = $row->position ?? '';

						$user = $row->getUserName();

						$unavailValues = [
							$title,
							$author,
							$format,
							$placed,
							$pickup
						];
						if ($showPosition) {
							$unavailValues[] = $position;
						}
						$unavailValues[] = $status;
						if ($hasLinkedUsers) {
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
	public function exportReadingHistory(): void {
		$this->requireLoggedInUser();

		$user = UserAccount::getActiveUserObj();

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
			$fields = array(
				'Title',
				'Author',
				'Format',
				'Last used'
			);
			fputcsv($fp, $fields);

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
				$results = array(
					$title,
					$author,
					$format,
					$lastCheckout
				);
				fputcsv($fp, $results);
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error exporting to csv " . $e->getMessage(), Logger::LOG_ERROR);
		}

		exit;
	}

	/** @noinspection PhpUnused */
	public function getCheckouts(): array {
		$this->requireLoggedInUser(null, 'Your session has ended, please login to view checkouts.');
		global $interface;
		global $library;

		$renewableCheckouts = 0;

		$result = [
			'success' => false,
			'showCostSavings' => false,
		];

		global $offlineMode;
		if (!$offlineMode || $interface->getVariable('enableEContentWhileOffline')) {
			$source = $_REQUEST['source'];
			$interface->assign('source', $source);
			$this->setShowCovers();

			//Determine which columns to show
			$user = UserAccount::getActiveUserObj();
			$showOut = $user->showOutDateInCheckouts();
			$showRenewed = $user->showTimesRenewed();
			$showRenewalsRemaining = $user->showRenewalsRemaining();
			$showWaitList = $user->showWaitListInCheckouts();

			$alwaysDisplayRenewalCount = $library->alwaysDisplayRenewalCount ?? false;

			$interface->assign('showOut', $showOut);
			$interface->assign('showRenewed', $showRenewed);
			$interface->assign('showRenewalsRemaining', $showRenewalsRemaining);
			$interface->assign('showWaitList', $showWaitList);
			$interface->assign('alwaysDisplayRenewalCount', $alwaysDisplayRenewalCount);

			// Define sorting options
			$sortOptions = [
				'title' => 'Title',
				'author' => 'Author',
				'dueDate' => 'Due Date Asc',
				'dueDateDesc' => 'Due Date Desc',
				'format' => 'Format',
			];
			$user = UserAccount::getActiveUserObj();

			if ($user->getHomeLibrary() != null) {
				$allowSelectingCheckoutsToExport = $user->getHomeLibrary()->allowSelectingCheckoutsToExport;
			} else {
				$allowSelectingCheckoutsToExport = $library->allowSelectingCheckoutsToExport;
			}
			$interface->assign('allowSelectingCheckoutsToExport', $allowSelectingCheckoutsToExport);


			if (count($user->getLinkedUsers()) > 0) {
				$sortOptions['libraryAccount'] = 'Library Account';
			}
			if ($showWaitList) {
				$sortOptions['holdQueueLength'] = 'Wait List';
			}
			if ($showRenewed) {
				$sortOptions['renewed'] = 'Times Renewed';
			}
			if ($showRenewalsRemaining) {
				$sortOptions['renewalsRemainingAsc'] = 'Renewals Remaining Asc';
				$sortOptions['renewalsRemainingDesc'] = 'Renewals Remaining Desc';
			}

			$interface->assign('sortOptions', $sortOptions);

			$interface->assign('showNotInterested', false);

			// Get My Transactions
			$selectedUser = $this->setFilterLinkedUserCheckouts();

			$allCheckedOut = $this->filterCheckoutsByUser($user->getCheckouts(true, $source), $selectedUser);

			foreach ($allCheckedOut as $checkout) {
				if ($checkout->canRenew == 1) {
					$renewableCheckouts++;
				}
			}

			$interface->assign('renewableCheckouts', $renewableCheckouts);
			$selectedSortOption = $this->setSortByUserObj('sort', 'checkout', $user);

			//Map LiDA Sort Options to Aspen
			if ($selectedSortOption == null) {
				$selectedSortOption = 'dueDate';
			} elseif (!array_key_exists($selectedSortOption, $sortOptions)) {
				if (array_key_exists($selectedSortOption, User::$lidaToAspenCheckoutSortMapping)) {
					$selectedSortOption = User::$lidaToAspenCheckoutSortMapping[$selectedSortOption];
				} else {
					$selectedSortOption = 'dueDate';
				}
			}
			if (isset($_REQUEST['sort'])) {
				$user->updateSortPreferences();
			}

			$interface->assign('defaultSortOption', $selectedSortOption);
			$allCheckedOut = $this->sortCheckouts($selectedSortOption, $allCheckedOut);

			$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
			$recordsPerPage = 100; // Could be made configurable in the future if requested.
			$totalCheckouts = count($allCheckedOut);
			if ($recordsPerPage != -1) {
				$interface->assign('page', $page);
				$link = $_SERVER['REQUEST_URI'];
				if (preg_match('/[&?]page=/', $link)) {
					$link = preg_replace('/page=\d+/', 'page=%d', $link);
				} else {
					$link .= (str_contains($link, '?') ? '&' : '?') . 'page=%d';
				}
				$options = [
					'totalItems' => $totalCheckouts,
					'fileName' => $link,
					'perPage' => $recordsPerPage,
					'append' => false,
					'linkRenderingObject' => $this,
					'linkRenderingFunction' => 'renderCheckoutPaginationLink',
					'source' => $source,
					'sort' => $selectedSortOption,
					'selectedUser' => $selectedUser,
				];
				$pager = new Pager($options);
				$interface->assign('pageLinks', $pager->getLinks());
				$interface->assign('recordsPerPage', $recordsPerPage);
				$interface->assign('startIndex', ($page - 1) * $recordsPerPage);
				$displayedCheckouts = array_slice($allCheckedOut, ($page - 1) * $recordsPerPage, $recordsPerPage);
			} else {
				$displayedCheckouts = $allCheckedOut;
			}
			$interface->assign('transList', $displayedCheckouts);

			$result['success'] = true;
			$result['message'] = "";
			$result['checkoutInfoLastLoaded'] = $user->getFormattedCheckoutInfoLastLoaded();

			$readerName = new OverDriveDriver();
			$readerName = $readerName->getReaderName();
			$interface->assign('readerName', $readerName);

			if ($interface->getVariable('enableCostSavings') && $source == 'all') {
				//Get costs savings
				$result['showCostSavings'] = true;
				$result['costSavingsMessage'] = $user->getCurrentCostSavingsMessage(true);
			}

			$result['checkouts'] = $interface->fetch('MyAccount/checkoutsList.tpl');
		} else {
			$result['message'] = translate([
				'text' => 'The catalog is offline',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	private function normalizeRecordId(string $recordId): string {
		$recordId = urldecode($recordId);
		$recordId = trim($recordId);
		return strtolower($recordId);
	}

	public function filterHoldsBySelected(array $allHolds, $selectedHolds): array {

		if (!empty($selectedHolds) && !is_array($selectedHolds)) {
			$selectedHoldsArray = [];
			parse_str($selectedHolds, $parsedHolds);

			if (isset($parsedHolds['selected'])) {
				foreach ($parsedHolds['selected'] as $holdKey => $value) {

					if (preg_match('/(\d+)\|([a-zA-Z0-9:._-]+)\|?/', $holdKey, $matches)) {
						$selectedHoldsArray[] = [
							'recordId' => $this->normalizeRecordId($matches[2]),
						];
					}
				}
			}
			$selectedHolds = $selectedHoldsArray;
		}
		$filteredHolds = [
			'available' => [],
			'unavailable' => [],
		];

		foreach ($allHolds['available'] as $key => $hold) {
			$hold->recordId = $this->normalizeRecordId($hold->recordId);
			$matchFound = false;
			foreach ($selectedHolds as $selectedHold) {
				if ($hold->recordId === strval($selectedHold['recordId'])) {
					$matchFound = true;
					break;
				}
			}
			if ($matchFound) {
				$filteredHolds['available'][$key] = $hold;
			}
		}

		foreach ($allHolds['unavailable'] as $key => $hold) {
			$hold->recordId = $this->normalizeRecordId($hold->recordId);
			$matchFound = false;
			foreach ($selectedHolds as $selectedHold) {
				if ($hold->recordId === strval($selectedHold['recordId'])) {
					$matchFound = true;
					break;
				}
			}
			if ($matchFound) {
				$filteredHolds['unavailable'][$key] = $hold;
			}
		}

		return $filteredHolds;
	}

	public function filterCheckoutsBySelected(array $allCheckedOut, $selectedCheckouts, string $selectedUser): array {
		if (!empty($selectedCheckouts) && !is_array($selectedCheckouts)) {
			$selectedCheckoutsArray = [];
			parse_str($selectedCheckouts, $parsedCheckouts);

			if (isset($parsedCheckouts['selected'])) {
				foreach ($parsedCheckouts['selected'] as $checkoutKey => $value) {

					if (preg_match('/(\d+)\|([a-zA-Z0-9:._-]+)\|?/', $checkoutKey, $matches)) {
						$selectedCheckoutsArray[] = [
							'recordId' => $this->normalizeRecordId($matches[2]),
						];
					}
				}
			}
			$selectedCheckouts = $selectedCheckoutsArray;

		}
		$filteredCheckouts = [];

		foreach ($allCheckedOut as $key => $checkout) {
			$checkout->recordId = $this->normalizeRecordId($checkout->recordId);

			if (!empty ($selectedUser) && $checkout->userId != $selectedUser) {
				continue;
			}
			$matchFound = false;
			foreach ($selectedCheckouts as $selectedCheckout) {
				if ($checkout->recordId === strval($selectedCheckout['recordId'])) {
					$matchFound = true;
					break;
				}
			}
			if ($matchFound) {
				$filteredCheckouts[$key] = $checkout;
			}
		}
		return $filteredCheckouts;
	}


	/**
	 * Filter the holds for display to the user
	 * @param array $allHolds - The full list of unfiltered holds.
	 * @param array $filters - Information about the filters to apply to the holds.
	 * @return array|array[]
	 */
	private function filterHolds(array $allHolds, array $filters): array {

		$filteredHolds = [];

		// Check if we're filtering by a specific user
		foreach ($allHolds as $group => $holdGroup) {
			$filteredHolds[$group] = [];
			foreach ($holdGroup as $hold) {
				$holdIsValid = true;
				foreach ($filters as $field => $filterInformation) {
					if (!in_array($hold->$field, $filterInformation['selected'])) {
						$holdIsValid = false;
						break;
					}
				}
				if ($holdIsValid) {
					$filteredHolds[$group][] = $hold;
				}
			}
		}

		return $filteredHolds;
	}

	private function filterCheckoutsByUser(array $allCheckedOut, string $selectedUser): array {
		$filteredCheckouts = [];

		$allUsersSelected = (empty($selectedUser) || $selectedUser === '[""]');

		foreach ($allCheckedOut as $key => $checkout) {
			if ($allUsersSelected || intval($checkout->userId) === intval($selectedUser)) {
				$filteredCheckouts[$key] = $checkout;
			}
		}

		return $filteredCheckouts;
	}

	public function setFilterLinkedUser(): string {
		$selectedUser = '';
		if (isset($_REQUEST['selectedUser'])) {
			$selectedUser = $_REQUEST['selectedUser'];
			if ($selectedUser == "") {
				$_SESSION['selectedUser'] = '';
			} else {
				$_SESSION['selectedUser'] = $selectedUser;
			}

		} elseif (isset($_SESSION['selectedUser'])) {
			$selectedUser = $_SESSION['selectedUser'];
		}
		return (string)$selectedUser;
	}

	public function setFilterLinkedUserCheckouts(): string {

		$selectedUser = '';
		if (isset($_REQUEST['selectedUserCheckouts'])) {
			$selectedUser = $_REQUEST['selectedUserCheckouts'];
			if ($selectedUser == "") {
				$_SESSION['selectedUserCheckouts'] = '';
			} else {
				$_SESSION['selectedUserCheckouts'] = $selectedUser;
			}

		} elseif (isset($_SESSION['selectedUserCheckouts'])) {

			$selectedUser = $_SESSION['selectedUserCheckouts'];
		}
		return (string)$selectedUser;
	}


	/** @noinspection PhpUnused */
	public function getHolds(): array {
		$this->requireLoggedInUser(null, "Your login has timed out. Please login again.");
		global $interface;

		$result = [
			'success' => false
		];

		global $offlineMode;
		if (!$offlineMode || $interface->getVariable('enableEContentWhileOffline')) {
			global $library;
			global $logger;
			$holdsSource = 'all';
			$interface->assign('source', $holdsSource);
			$this->setShowCovers();

			$user = UserAccount::getActiveUserObj();

			$selectedUser = $this->setFilterLinkedUser();

			if ($user->getHomeLibrary() != null) {
				$allowSelectingHoldsToExport = $user->getHomeLibrary()->allowSelectingHoldsToExport;
			} else {
				$allowSelectingHoldsToExport = $library->allowSelectingHoldsToExport;
			}

			$catalogDriver = $user->getCatalogDriver();
			$allowHoldsToBeGrouped = $catalogDriver && $catalogDriver->supportsHyperholdsGrouping() && User::resolveAllowHoldsToBeGrouped($user, $library);
		
			if ($allowHoldsToBeGrouped) {
				$groupedHolds = [];
				$holdGroupUsers = array_merge([$user], $user->getLinkedUsers());
				foreach ($holdGroupUsers as $holdGroupUser) {
					$patronId = $holdGroupUser->unique_ils_id;
					$groupedHoldsResponse = $catalogDriver->getPatronHoldGroups($patronId);

					$patronGroups = [];
					if (isset($groupedHoldsResponse['content'])) {
						if (is_string($groupedHoldsResponse['content'])) {
							$patronGroups = json_decode($groupedHoldsResponse['content'], true) ?: [];
						} elseif (is_array($groupedHoldsResponse['content'])) {
							$patronGroups = $groupedHoldsResponse['content'];
						} else {
							$logger->log(
								'Unexpected type for groupedHoldsResponse["content"]: ' . gettype($groupedHoldsResponse['content']),
								Logger::LOG_ERROR
							);
						}
					} elseif (is_array($groupedHoldsResponse)) {
						$patronGroups = $groupedHoldsResponse;
					} else {
						$logger->log(
							'Unexpected type for groupedHoldsResponse: ' . gettype($groupedHoldsResponse),
							Logger::LOG_ERROR
						);
					}

					if ($holdGroupUser->id !== $user->id) {
						foreach ($patronGroups as &$patronGroup) {
							$patronGroup['linked_user_id'] = $holdGroupUser->id;
							$patronGroup['linked_user_name'] = $holdGroupUser->displayName;
						}
						unset($patronGroup);
					}

					$groupedHolds = array_merge($groupedHolds, $patronGroups);
				}
			}

			$interface->assign('allowSelectingHoldsToExport', $allowSelectingHoldsToExport);


			if ($holdsSource != 'interlibrary_loan') {
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

			$interface->assign('allowHoldsToBeGrouped', $allowHoldsToBeGrouped);

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
			$unavailableHoldSortOptions['location'] = 'Pickup Location';
			if ($showPosition) {
				$unavailableHoldSortOptions['position'] = 'Position';
			}
			if ($showPlacedColumn) {
				$unavailableHoldSortOptions['placed'] = 'Date Placed';
			}
			if ($library->showHoldCancelDate) {
				$unavailableHoldSortOptions['cancelDate'] = 'Hold Cancellation Date';
			}
			if ($user->suspendRequiresReactivationDate()) {
				$unavailableHoldSortOptions['reactivate'] = 'Reactivation Date';
			}

			$availableHoldSortOptions = [
				'title' => 'Title',
				'author' => 'Author',
				'format' => 'Format',
				'expire' => 'Expiration Date',
				'placed' => 'Date Placed',
			];
			$availableHoldSortOptions['location'] = 'Pickup Location';

			if (count($user->getLinkedUsers()) > 0) {
				$unavailableHoldSortOptions['libraryAccount'] = 'Library Account';
				$availableHoldSortOptions['libraryAccount'] = 'Library Account';
			}

			$interface->assign('sortOptions', [
				'available' => $availableHoldSortOptions,
				'unavailable' => $unavailableHoldSortOptions,
				/*'cancelled' => $cancelledHoldSortOptions,*/
			]);

			$selectedAvailableSortOption = $this->setSortByUserObj('availableHoldSort', 'availableHold', $user);
			$selectedUnavailableSortOption = $this->setSortByUserObj('unavailableHoldSort', 'unavailableHold', $user);
			/*$selectedCancelledSortOption = $this->setSortByUserObj('cancelledHoldSort', 'cancelledHold', $user);*/

			if ($selectedAvailableSortOption == null) {
				$selectedAvailableSortOption = 'expire';
			} elseif (!array_key_exists($selectedAvailableSortOption, $availableHoldSortOptions)) {
				if (array_key_exists($selectedAvailableSortOption, User::$lidaToAspenAvailableHoldSortMapping)) {
					$selectedAvailableSortOption = User::$lidaToAspenAvailableHoldSortMapping[$selectedAvailableSortOption];
				} else {
					$selectedAvailableSortOption = 'expire';
				}
			}
			if ($selectedUnavailableSortOption == null) {
				$selectedAvailableSortOption = ($showPosition ? 'position' : 'title');
			} elseif (!array_key_exists($selectedUnavailableSortOption, $unavailableHoldSortOptions)) {
				if (array_key_exists($selectedUnavailableSortOption, User::$lidaToAspenUnavailableHoldSortMapping)) {
					$selectedUnavailableSortOption = User::$lidaToAspenUnavailableHoldSortMapping[$selectedUnavailableSortOption];
				} else {
					$selectedUnavailableSortOption = ($showPosition ? 'position' : 'title');
				}
			}

			$user->updateSortPreferences();

			$defaultCancelledSortOption = 'cancellationDate';
			$interface->assign('defaultSortOption', [
				'available' => $selectedAvailableSortOption,
				'unavailable' => $selectedUnavailableSortOption,
				/*'cancelled' => $defaultCancelledSortOption,*/
			]);

			$showDateWhenSuspending = $user->showDateWhenSuspending();
			$interface->assign('showDateWhenSuspending', $showDateWhenSuspending);

			$interface->assign('showPosition', $showPosition);
			$interface->assign('showNotInterested', false);

			// List of filters
			$filtersList = [
				'status',
				'userId',
				'source',
				'format'
			];

			global $offlineMode;
			$allHolds = null;
			if (!$offlineMode) {

				// Get & Set Filter Options
				$activeFilters = $this->getActiveHoldFilters($filtersList);
				$filters = $this->getHoldFiltersForUser($user, $activeFilters, $filtersList);
				//If we have nothing to filter, don't show the filter options
				$showFilterOptions = false;
				foreach ($filters as $filter) {
					if (count($filter['options']) > 1) {
						$showFilterOptions = true;
					}
				}
				if ($showFilterOptions) {
					$interface->assign('filterOptions', $filters);
					$result['filterOptions'] = $interface->fetch('MyAccount/holdsFilters.tpl');
				}


				$allHolds = $this->filterHolds($user->getHolds(true, $selectedUnavailableSortOption, $selectedAvailableSortOption, 'all', $defaultCancelledSortOption), $filters);
				$hyperHolds = [];
				$hiddenHoldIds = [];

				if (!empty($groupedHolds) && !empty($allHolds['unavailable'])) {
					foreach($groupedHolds as $group) {
						if (!empty($group['holds']) && is_array($group['holds']) && count ($group['holds']) > 1) {
							$groupHoldIds = [];
							foreach ($group['holds'] as $hold) {
								$groupHoldIds[] = $hold['hold_id'] ?? null;
							}
							$matchingHolds = [];
							foreach ($allHolds['unavailable'] as $holdKey => $holdObj) {
								if (in_array($holdObj->cancelId, $groupHoldIds)) {
									$matchingHolds[] = $holdObj;
									$hiddenHoldIds[] = $holdKey;
								}
							}
							if (count($matchingHolds) > 1) {
								$hyperHolds[] = [
									'visual_hold_id' => $group['hold_group_id'],
									'hold_group_id' => $group['hold_group_id'],
									'holdCount' => count($matchingHolds),
									'holds' => $matchingHolds,
									'type' => 'hyperhold',
									'userName' => $group['linked_user_name'] ?? $user->displayName,
								];
							}
						}
					}
				}

				if (!empty($hiddenHoldIds)) {
					foreach ($hiddenHoldIds as $holdKey) {
						unset($allHolds['unavailable'][$holdKey]);
					}
				}

				$interface->assign('hyperHolds', $hyperHolds);
				$interface->assign('hasHyperHolds', !empty($hyperHolds));
				$interface->assign('recordList', $allHolds);

				if (!empty($hyperHolds)) {
					foreach ($hyperHolds as $hyperHold) {
						$holdGroupId = $hyperHold['hold_group_id'];
						$visualGroupId = $hyperHold['visual_hold_id'];

						foreach ($hyperHold['holds'] as $holdObj) {
							$holdObj->holdGroupId = $holdGroupId;
							$holdObj->visualHoldGroupId = $visualGroupId;

							$holdRecord = new Hold();
							$holdRecord->id = $holdObj->id;
							if ($holdRecord->find(true)) {
								$holdRecord->holdGroupId = $holdGroupId;
								$holdRecord->visualHoldGroupId = $visualGroupId;
								$holdRecord->update();
							}
						}
					}
				}
			}

			$notification_method = ($user->_noticePreferenceLabel != 'Unknown') ? $user->_noticePreferenceLabel : '';
			$interface->assign('notification_method', strtolower($notification_method));
			$interface->assign('userId', $user->id);

			$result['success'] = true;
			$result['message'] = "";
			$result['holdInfoLastLoaded'] = $user->getFormattedHoldInfoLastLoaded();

			$showCancelled = false;
			if ($library->showCancelledHolds && $library->getAccountProfile()->ils == "polaris") {
				$showCancelled = true;
			}

			$readerName = new OverDriveDriver();
			$readerName = $readerName->getReaderName();
			$interface->assign('readerName', $readerName);
			$interface->assign('showCancelled', $showCancelled);

			$showAvailableHoldsSection = $library->showHoldsReadyForPickupSection == 1 || ($allHolds != null && count($allHolds['available']) > 0);
			$interface->assign('showAvailableHoldsSection', $showAvailableHoldsSection);
			$interface->assign('showHoldHelpMessages', $user->showHoldHelpMessages);

			$result['holds'] = $interface->fetch('MyAccount/holdsList.tpl');

		} else {
			$result['message'] = translate([
				'text' => 'The catalog is offline',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** Hold Filtering Functions */
	private function getHoldFilterValue(Hold|array $hold, string $field): ?array {
		if (is_array($hold)) {
			$fieldValue = isset($hold[$field]) ? (string)$hold[$field] : null;
		}else{
			$fieldValue = $hold->$field;
		}

		$label = $fieldValue;
		//Do special processing of some fields
		switch ($field) {
			case "userId":
				$label = $hold->getUserName();
				break;
			case "status":
				if ($hold->available) {
					$label = translate(['text' => 'Available', 'isPublicFacing' => true]);
				}else{
					if (empty($hold->status)) {
						$label = translate(['text' => 'Unavailable', 'isPublicFacing' => true]);
					}else{
						$label = translate(['text' => (string)$hold->$field, 'isPublicFacing' => true]);
					}
				}
				break;
			case "format":
				$label = translate(['text' => (string)$hold->$field, 'isPublicFacing' => true]);
				break;
			case "source":
				switch ($hold->source) {
					case 'ils':
						$sourceUntranslated = 'Physical Materials';
						break;
					case 'overdrive':
						$readerName = new OverDriveDriver();
						$sourceUntranslated = $readerName->getReaderName();
						break;
					case 'cloud_library':
						$sourceUntranslated = 'Cloud Library';
						break;
					case 'hoopla':
						$sourceUntranslated = 'Hoopla';
						break;
					case 'axis360':
						$sourceUntranslated = 'Boundless';
						break;
					default:
						$sourceUntranslated = 'Unknown';
				}
				$label = translate(['text' => $sourceUntranslated, 'isPublicFacing' => true]);
				break;
			default:
				$label = (string)$hold->$field;
		}
		return [
			'value' => $fieldValue,
			'label' => $label
		];
	}

	/**
	 * Get the filters that apply to the active user's holds.
	 *
	 * @param User $user - The user that we are getting the filters for
	 * @param array $activeFilters - The filters that have been applied by the user
	 * @param array $filtersList - The list of filters that are available to the user
	 * @return array
	 */
	private function getHoldFiltersForUser(User $user, array $activeFilters, array $filtersList): array {
		//Get all holds for the user including linked users
		$allHolds = $user->getHolds();

		//Gather all holds into a single array
		$holds = [];
		foreach ($allHolds as $group => $holdGroup) {
			foreach ($holdGroup as $hold) {
				if (is_array($hold)) {
					$hold['_holdGroup'] = $group;
				} elseif (is_object($hold)) {
					$hold->_holdGroup = $group;
				}
				$holds[] = $hold;
			}
		}

		//Loop through all the filters that we want to process to get the valid values for each
		$filters = [];
		foreach ($filtersList as $facet) {
			$options = [];
			foreach ($holds as $hold) {
				$value = $this->getHoldFilterValue($hold, $facet);
				if (!array_key_exists($value['value'], $options)) {
					$options[$value['value']] = $value;
				}
			}
			uasort($options, function ($a, $b) {
				return strnatcasecmp($a['label'], $b['label']);
			});

			$noFiltersSet = empty($activeFilters);
			if ($noFiltersSet) {
				$selectedValues = array_keys($options);
			}else{
				//Get selected values from the active filters. If nothing is provided, use all options since we don't show options with only 1 value.
				$selectedValues = $activeFilters[$facet] ?? array_keys($options);
			}

			$filters[$facet] = [
				'label' => $facet === 'userId' ? 'Account' : ucwords(trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', (string)$facet))),
				'type' => 'multiselect',
				'options' => $options,
				'selected' => $selectedValues,
			];
		}

		return $filters;
	}

	private function normalizeFilterValue($value): array {
		if ($value === null) {
			return [];
		}

		if (!is_array($value)) {
			$value = [$value];
		}

		$out = [];
		foreach ($value as $v) {
			if ($v === null) {
				continue;
			}
			$s = trim((string)$v);
			if ($s === '') {
				continue;
			}
			$out[$s] = true; // dedupe
		}

		return array_keys($out);
	}

	/**
	 * Get a list of filters that are being applied to the hold list by the user.
	 *
	 * @param array $filtersList
	 * @return array
	 */
	private function getActiveHoldFilters(array $filtersList): array {
		$filters = [];
		$rawFilters = $_REQUEST['filters'] ?? null;
		if (is_array($rawFilters)) {
			foreach ($rawFilters as $field => $value) {
				$normalized = $this->normalizeFilterValue($value);
				if (!empty($normalized)) {
					$filters[(string)$field] = $normalized;
				}
			}
		}

		foreach ($filtersList as $field) {
			if (!array_key_exists($field, $_REQUEST)) {
				continue;
			}
			if (array_key_exists($field, $filters)) {
				continue;
			}
			$normalized = $this->normalizeFilterValue($_REQUEST[$field]);
			if (!empty($normalized)) {
				$filters[$field] = $normalized;
			}
		}

		return $filters;
	}

	private function getVendor($sourceId) : string {
		if (str_starts_with($sourceId, 'communico')) {
			return "communico";
		} elseif (str_starts_with($sourceId, 'libcal')) {
			return "springshare";
		} elseif (str_starts_with($sourceId, 'lc_')) {
			return "library_market";
		} elseif (str_starts_with($sourceId, 'assabet')) {
			return "assabet";
		} else {
			return '';
		}
	}

	/** @noinspection PhpUnused */
	public function getSavedEvents() : array {
		$this->requireLoggedInUser();
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
		if ($eventsFilter == 'all') {
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
			$nativeAspenEvent = strpos($entry->sourceId, 'aspenEvent') !== false;
			$registration = null;
			$numberOfSeats = null;
			$availableSeats = null;
			$eventFull = null;

			// check aspen native events registration
			if($nativeAspenEvent) {
				$eventInstanceId = preg_replace("/aspenEvent_\d+_/", '', $entry->sourceId);

				require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
				$aspenEventRegistration = new UserAspenEventInstanceRegistration();
				$aspenEventRegistration->userId = UserAccount::getActiveUserId();
				$aspenEventRegistration->eventInstanceId = $eventInstanceId;
				$registration = $aspenEventRegistration->isUserRegisteredForEvent();
				$registeredByStaff = $registration && !empty($aspenEventRegistration->registeredByStaffId);
				$savedByStaff = !empty($event->savedByStaffId);

				require_once ROOT_DIR . '/sys/Events/EventInstance.php';
				$eventInstance = new EventInstance();
				$eventInstance->id = $eventInstanceId;
				if ($eventInstance->find(true)) {
					require_once ROOT_DIR . '/services/EventRegistrationService.php';
					$numberOfSeats = $eventInstance->getEffectiveNumberOfSeats();
					$availableSeats = EventRegistrationService::getAvailableSeats($eventInstance);
					$eventFull = !EventRegistrationService::hasAvailableSeats($eventInstance);
					$waitingList = $eventInstance->isWaitingListEnabled();
					$waitingListNumberOfSeats = $eventInstance->getEffectiveWaitingListNumberOfSeats();

					$userOnWaitingList = false;
					$userWaitingListPosition = null;
					$userCanRegisterFromWaitingList = false;

					if (!$waitingList || UserAspenEventInstanceRegistration::getWaitingListCount($eventInstance->id) === 0) {
						$userCanRegisterFromWaitingList = EventRegistrationService::hasAvailableSeats($eventInstance);
					} else {
						$waitingListInfo = $aspenEventRegistration->getWaitingListInfo();
						$userOnWaitingList = $waitingListInfo['onWaitingList'];
						$userWaitingListPosition = $waitingListInfo['position'];
						$userCanRegisterFromWaitingList = $waitingListInfo['canRegister'];
					}
				}

				// Generate registration form using custom fields
				$eventType = $eventInstance->getEventType();
				$registrationFormStructure = $eventType->getFieldSetFieldsByUse(2);
				$interface->assign('registrationFormStructure', $registrationFormStructure);
				$interface->assign('attendeeCategories', $eventType->getEventTypeAttendeeCategories());

				$savedRegistrationFieldValues = [];
				if ($registration && $aspenEventRegistration->id) {
					require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationEventField.php';
					$savedRegistrationFieldValues = UserAspenEventInstanceRegistrationEventField::getValuesForRegistration((int)$aspenEventRegistration->id);
				}

				$savedAttendeeCounts = [];
				if ($registration && $aspenEventRegistration->id) {
					require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
					$savedAttendeeCounts = UserAspenEventInstanceRegistrationAttendee::getCountsForRegistration((int)$aspenEventRegistration->id);
				}
			} else {
				$registration = UserAccount::getActiveUserObj()->isRegistered($entry->sourceId);
			}

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
					'vendor' => self::getVendor($entry->sourceId)
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
					'vendor' => self::getVendor($entry->sourceId)
				];
			}
			if($nativeAspenEvent) {
				$events[$entry->sourceId]['registeredByStaff'] = $registeredByStaff;
				$events[$entry->sourceId]['savedByStaff'] = $savedByStaff;
				$events[$entry->sourceId]['savedRegistrationFieldValues'] = $savedRegistrationFieldValues;
				$events[$entry->sourceId]['savedAttendeeCounts'] = $savedAttendeeCounts;
				$events[$entry->sourceId]['numberOfSeats'] = $numberOfSeats;
				$events[$entry->sourceId]['availableSeats'] = $availableSeats;
				$events[$entry->sourceId]['isEventFull'] = $eventFull;
				$events[$entry->sourceId]['isEventFull'] = !EventRegistrationService::hasAvailableSeats($eventInstance);
				$events[$entry->sourceId]['waitingList'] = $waitingList;
				$events[$entry->sourceId]['waitingListNumberOfSeats'] = $waitingListNumberOfSeats;
				$events[$entry->sourceId]['userOnWaitingList'] = $userOnWaitingList;
				$events[$entry->sourceId]['userWaitingListPosition'] = $userWaitingListPosition;
				$events[$entry->sourceId]['userCanRegisterFromWaitingList'] = $userCanRegisterFromWaitingList;
				$isWaitingListFull = EventRegistrationService::isWaitingListFull($eventInstance);
				$events[$entry->sourceId]['waitingListFull'] = $isWaitingListFull;
				$events[$entry->sourceId]['registrationStatusMessage'] = EventRegistrationService::getRegistrationStatusMessage($waitingList, $userOnWaitingList, $userCanRegisterFromWaitingList, $userWaitingListPosition ?? 0, $eventFull, $isWaitingListFull);
				$events[$entry->sourceId]['registrationAction'] = EventRegistrationService::getRegistrationAction(
					$registration,
					$eventFull,
					$waitingList,
					$userOnWaitingList,
					$userCanRegisterFromWaitingList,
					$isWaitingListFull
				);
			}
		}

		$filter = $_REQUEST['eventsFilter'] ?? '';
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
		$interface->assign('userId', $user->id);

		$user = UserAccount::getLoggedInUser();
		if ($user) {
			$interface->assign('loggedIn', true);
			$interface->assign('userId', $user->id);
			$interface->assign('userDisplayName', $user->getDisplayName());
			$interface->assign('userEmail', $user->email);
			$interface->assign('userHomeLocation', $user->getHomeLocationName());
			$linkedUsers = [];
			global $library;
			if ($library->allowLinkedAccounts) {
				$linkedUsers = $user->getLinkedUsers();
				foreach ($linkedUsers as $linkedUser) {
					$linkedUser->loadContactInformation();
				}
			}
			$interface->assign('allowEventRegistration', isset($library->allowEventRegistration) && $library->allowEventRegistration != 0);
			$interface->assign('linkedUsers', $linkedUsers);
		}


		$result['success'] = true;
		$result['message'] = "";
		$result['myEvents'] = $interface->fetch('MyAccount/myEventsList.tpl');

		return $result;
	}

	public function getReadingHistory() : array {
		$this->requireLoggedInUser();

		global $interface;
		$showCovers = $this->setShowCovers();

		require_once ROOT_DIR . '/sys/IP/IPAddress.php';
		$interface->assign('showDebuggingInformation', IPAddress::showDebuggingInformation());

		$user = UserAccount::getActiveUserObj();

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
		$page = $_REQUEST['page'] ?? 1;
		$interface->assign('page', $page);

		$recordsPerPage = 20;
		$interface->assign('curPage', $page);

		$filter = $_REQUEST['readingHistoryFilter'] ?? '';
		$interface->assign('readingHistoryFilter', $filter);

		$result = $patron->getReadingHistory($page, $recordsPerPage, $selectedSortOption, $filter);

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

		$interface->assign('historyActive', $result['historyActive']);
		$interface->assign('transList', $result['titles']);
		$patronHomeLibrary = $patron->getHomeLibrary();
		$interface->assign('library', $patronHomeLibrary);
		$result['showCostSavings'] = $patronHomeLibrary->enableCostSavings && $patron->enableCostSavings;
		$result['costSavingsMessage'] = $user->getTotalCostSavingsMessage(true);

		$result['success'] = true;
		$result['message'] = "";
		$result['readingHistory'] = $interface->fetch('MyAccount/readingHistoryList.tpl');

		return $result;
	}

	/** @noinspection PhpUnused */
	function renderReadingHistoryPaginationLink(int $page, array $options): string {
		$currentPage = isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$activeClass = ($currentPage == $page) ? ' active' : '';
		return "<a class='page-link btn btn-default btn-sm $activeClass' onclick='AspenDiscovery.Account.loadReadingHistory(\"{$options['patronId']}\", \"{$options['sort']}\", \"$page\", undefined, \"{$options['filter']}\")'>";
	}

	/** @noinspection PhpUnused */
	function renderCheckoutPaginationLink(int $page, array $options): string {
		$currentPage = isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$activeClass = ($currentPage == $page) ? ' active' : '';
		return "<a class='page-link btn btn-default btn-sm $activeClass' onclick='AspenDiscovery.Account.loadCheckouts(\"{$options['source']}\", \"{$options['sort']}\", undefined, \"{$options['selectedUser']}\", \"$page\")'>";
	}

	private function isValidTimeStamp($timestamp) : bool {
		return is_numeric($timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
	}

	public function setShowCovers() : bool {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers']) && $_REQUEST['showCovers'] !== 'null' && $_REQUEST['showCovers'] !== 'undefined') {
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

	function setSort($requestParameter, $sortType) : ?string {
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

	function setSortByUserObj(string $requestParameter, string $sortType, User $user): ?string {
		$sort = null;
		if (isset($_REQUEST[$requestParameter])) {
			$sort = $_REQUEST[$requestParameter];
		} else {
			if ($sortType == 'checkout') {
				$sort = $user->checkoutSort;
			} elseif ($sortType == 'availableHold') {
				$sort = $user->holdSortAvailable;
			} elseif ($sortType == 'unavailableHold') {
				$sort = $user->holdSortUnavailable;
			}
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
			if ($selectedSortOption == 'author') {
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
			} elseif ($selectedSortOption == 'renewalsRemainingAsc') {
				$numRenewalsRemaining = $curTitle->maxRenewals - $curTitle->renewCount;
				$sortKey = str_pad($numRenewalsRemaining, 3, '0', STR_PAD_LEFT) . '-' . $sortTitle;
			} elseif ($selectedSortOption == 'renewalsRemainingDesc') {
				$numRenewalsRemaining = $curTitle->maxRenewals - $curTitle->renewCount;
				$sortKey = str_pad(999 - $numRenewalsRemaining, 3, '0', STR_PAD_LEFT) . '-' . $sortTitle;
			} elseif ($selectedSortOption == 'libraryAccount') {
				$sortKey = $curTitle->getUserName() . '-' . $sortTitle;
			}
			$sortKey = strtolower($sortKey);
			$sortKey = mb_convert_encoding($sortKey . '-' . $curTransaction, 'UTF-8', 'ISO-8859-1');

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

	function deleteReadingHistoryEntry(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to delete from the reading history.');
		$result = $this->failureResult('Failed to Delete Reading History Entry', 'An unknown error has occurred deleting this reading history entry.');

		$user = UserAccount::getActiveUserObj();
		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron == null) {
			$result['message'] = 'You do not have permissions to delete reading history for this user.';
		} else {
			$entryId = $_REQUEST['entryId'] ?? null;
			if (!empty($entryId)) {
				$selectedTitles = [$entryId => $entryId];
				$readingHistoryAction = 'deleteMarked';
				$result = $patron->doReadingHistoryAction($readingHistoryAction, $selectedTitles);
			} else {
				$result['message'] = 'No reading history entry ID was provided.';
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function updateReadingHistoryReturnDate(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to update reading history.');
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Check-In Update Failed',
				'isPublicFacing' => true,
			])
		];

		$user = UserAccount::getActiveUserObj();

		$entryId = $_REQUEST['entryId'] ?? null;
		$newReturnDate = $_REQUEST['newReturnDate'] ?? null;

		if (empty($entryId) || !is_numeric($entryId)) {
			$result['title'] = translate([
				'text' => 'Invalid Entry',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Invalid entry ID provided.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if (empty($newReturnDate) || !is_numeric($newReturnDate)) {
			$result['title'] = translate([
				'text' => 'Invalid Date Input',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Invalid return date provided.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		$todayTimestamp = strtotime('today');
		if ($newReturnDate > $todayTimestamp) {
			$result['title'] = translate([
				'text' => 'Invalid Date Input',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Return date cannot be in the future.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->id = $entryId;

		if (!$readingHistoryEntry->find(true)) {
			$result['title'] = translate([
				'text' => 'Entry Not Found',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Reading history entry not found.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if ($readingHistoryEntry->userId != $user->id) {
			$result['title'] = translate([
				'text' => 'Permission Denied',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'You do not have permission to update this entry.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		$readingHistoryEntry->editedCheckInDate = $newReturnDate;
		if ($readingHistoryEntry->update()) {
			$result['success'] = true;
			$result['title'] = translate([
				'text' => 'Check-In Date Updated',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Return date updated successfully.',
				'isPublicFacing' => true,
			]);
			$result['formattedDate'] = date('M d, Y', $newReturnDate);
		} else {
			$result['message'] = translate([
				'text' => 'Failed to update return date in database.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteGroupedReadingHistoryEntry(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to delete from the reading history.');
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Failed to Delete Reading History Entry',
				'isPublicFacing' => true,
			])
		];

		$user = UserAccount::getActiveUserObj();

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron == null) {
			$result['message'] = 'You do not have permissions to delete reading history for this user.';
		} else {
			$groupedWorkPermanentId = $_REQUEST['groupedWorkPermanentId'] ?? null;
			$title = $_REQUEST['title'] ?? null;
			$author = $_REQUEST['author'] ?? null;

			if (!empty($groupedWorkPermanentId) || (!empty($title))) {
				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$readingHistoryEntry = new ReadingHistoryEntry();
				$readingHistoryEntry->userId = $patron->id;
				$readingHistoryEntry->deleted = 0;

				// Match by groupedWorkPermanentId if available, otherwise by title/author
				if (!empty($groupedWorkPermanentId)) {
					$readingHistoryEntry->groupedWorkPermanentId = $groupedWorkPermanentId;
				} else {
					$readingHistoryEntry->title = $title;
					if (!empty($author)) {
						$readingHistoryEntry->author = $author;
					}
				}

				$readingHistoryEntry->find();
				$numDeleted = 0;
				while ($readingHistoryEntry->fetch()) {
					$readingHistoryEntry->deleted = 1;
					$readingHistoryEntry->update();
					$numDeleted++;
				}

				if ($numDeleted > 0) {
					$result['success'] = true;
					$result['title'] = translate([
						'text' => 'Successfully Deleted Reading History Entry',
						'isPublicFacing' => true,
					]);
					//Based on user testing, this was confusing to users since they only clicked
					// on a button to delete the group. Simplify to just indicate it was deleted.
					$result['message'] = translate([
						'text' => "Deleted entry from your reading history.",
						'isPublicFacing' => true,
					]);
				} else {
					$result['message'] = translate([
						'text' => 'No entries found to delete.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = 'No reading history entry information was provided.';
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteSelectedReadingHistoryEntries(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to delete from the reading history.');
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Failed to Delete Reading History Entries',
				'isPublicFacing' => true,
			])
		];

		$user = UserAccount::getActiveUserObj();

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron == null) {
			$result['message'] = 'You do not have permissions to delete reading history for this user.';
		} else {
			$ids = $_REQUEST['ids'] ?? [];
			if (is_array($ids) && count($ids) > 0) {
				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$totalDeleted = 0;
				$numFailed = 0;
				// For each selected grouped entry, delete all associated checkout records.
				foreach ($ids as $id) {
					$groupedEntry = new ReadingHistoryEntry();
					$groupedEntry->id = $id;
					$groupedEntry->userId = $patron->id;
					$groupedEntry->deleted = 0;
					if ($groupedEntry->find(true)) {
						// Now find and delete all entries with the same groupedWorkPermanentId (or title/author).
						$deleteQuery = new ReadingHistoryEntry();
						$deleteQuery->userId = $patron->id;
						$deleteQuery->deleted = 0;
						if (!empty($groupedEntry->groupedWorkPermanentId)) {
							$deleteQuery->groupedWorkPermanentId = $groupedEntry->groupedWorkPermanentId;
						} else {
							$deleteQuery->title = $groupedEntry->title;
							if (!empty($groupedEntry->author)) {
								$deleteQuery->author = $groupedEntry->author;
							}
						}
						$deleteQuery->find();
						while ($deleteQuery->fetch()) {
							$deleteQuery->deleted = 1;
							$deleteQuery->update();
						}
						$totalDeleted++;
					} else {
						$numFailed++;
					}
				}

				if ($totalDeleted > 0) {
					$result['success'] = true;
					$result['title'] = translate([
						'text' => $totalDeleted === 1 ? 'Successfully Deleted Reading History Entry' : 'Successfully Deleted Reading History Entries',
						'isPublicFacing' => true,
					]);
					if ($numFailed > 0) {
						$deletedText = $totalDeleted === 1 ? 'entry' : 'entries';
						$failedText = $numFailed === 1 ? 'entry' : 'entries';
						$result['message'] = translate([
							'text' => "Deleted %1% $deletedText from your reading history. %2% $failedText could not be deleted.",
							1 => $totalDeleted,
							2 => $numFailed,
							'isPublicFacing' => true,
						]);
					} else {
						$entryText = $totalDeleted === 1 ? 'entry' : 'entries';
						$result['message'] = translate([
							'text' => "Deleted %1% $entryText from your reading history.",
							1 => $totalDeleted,
							'isPublicFacing' => true,
						]);
					}
				} else {
					$result['success'] = false;
					$result['title'] = translate([
						'text' => 'Failed to Delete Reading History Entries',
						'isPublicFacing' => true,
					]);
					$result['message'] = translate([
						'text' => 'No entries could be deleted from your reading history.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = 'No reading history entries were selected.';
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function dismissMessage(): array {
		$this->requireLoggedInUser();
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		if (!isset($_REQUEST['messageId'])) {
			return $this->failureResult(null, 'Message Id not provided');
		} else {
			$message = new UserMessage();
			$message->id = $_REQUEST['messageId'];
			if ($message->find(true)) {
				if ($message->userId != UserAccount::getActiveUserId()) {
					return $this->failureResult(null, 'Message is not for the active user');
				} else {
					$message->isDismissed = 1;
					$message->update();
					return $this->successResult(null, 'Message was dismissed');
				}
			} else {
				return $this->failureResult(null, 'Could not find the message to dismiss');
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissSystemMessage(): array {
		$this->requireLoggedInUser();
		require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
		if (!isset($_REQUEST['messageId'])) {
			return $this->failureResult(null, 'Message Id not provided');
		} else {
			$message = new SystemMessage();
			$message->id = $_REQUEST['messageId'];
			if ($message->find(true)) {
				require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
				$systemMessageDismissal = new SystemMessageDismissal();
				$systemMessageDismissal->userId = UserAccount::getActiveUserId();
				$systemMessageDismissal->systemMessageId = $message->id;
				if ($systemMessageDismissal->find(true)) {
					return $this->successResult(null, 'Message was already dismissed');
				} else {
					$systemMessageDismissal->insert();
					return $this->successResult(null, 'Message was dismissed');
				}
			} else {
				return $this->failureResult(null, 'Could not find the message to dismiss');
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissHoldHelpMessages(): array {
		$this->requireLoggedInUser();

		$user = UserAccount::getLoggedInUser();
		$user->showHoldHelpMessages = 0;
		$user->update();
		return $this->successResult(null, 'The help messages will no longer be shown');
	}

	/** @noinspection PhpUnused */
	private function createGenericDonation($paymentType = '') : array {
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

		$donateToLocation = false;
		if (!empty($_REQUEST['toLocation'])) {
			$donateToLocation = $_REQUEST['toLocation'];
		}
		$toLocation = -1;
		if ($donateToLocation) {
			require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
			$location = new Location();
			$location->displayName = $donateToLocation;
			if ($location->find(true)) {
				$toLocation = $location->locationId;
			}
		} else {
			$donateToLocation = 'None';
		}

		$earmarkId = $_REQUEST['earmark'] ?? null;
		$comments = 'None';
		if ($earmarkId) {
			require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
			$earmark = new DonationEarmark();
			$earmark->id = $earmarkId;
			if ($earmark->find(true)) {
				$comments = $earmark->label;
			}
		}

		// check for a minimum value to donate
		// for now we will use minimumFineAmount and decide later if donations should be separate
		global $activeLanguage;
		$minimumAmountToProcess = $paymentLibrary->minimumFineAmount;
		$setupCurrencyFormat = numfmt_create($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);
		$currencyFormat = numfmt_format_currency($setupCurrencyFormat, $minimumAmountToProcess, $currencyCode);

		// check for good values
		if (empty($_REQUEST['amount']) || empty($_REQUEST['emailAddress']) || empty($_REQUEST['firstName']) || empty($_REQUEST['lastName']) || ($_REQUEST['amount'] < $minimumAmountToProcess)) {
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

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		require_once ROOT_DIR . '/sys/Account/UserPaymentLine.php';
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
			if ($paymentType == 'square') {
				$payment->squareToken = $_REQUEST['token'];
			} else {
				$payment->aciToken = $_REQUEST['token'];
			}
		}

		$paymentInsert = $payment->insert();
		$paymentId = $payment->id;

		//Add a line item for the donation
		$paymentLine = new UserPaymentLine();
		$paymentLine->paymentId = $payment->id;
		$paymentLine->description = translate([
			'text' => 'Donation',
			'inAttribute' => true,
			'isPublicFacing' => true
		]);
		$paymentLine->amountPaid = $donationValue;
		$paymentLine->insert();

		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		$donation = new Donation();
		$donation->paymentId = $payment->id;
		$donation->firstName = $_REQUEST['firstName'];
		$donation->lastName = $_REQUEST['lastName'];
		$donation->email = $_REQUEST['emailAddress'];
		$donation->anonymous = isset($_REQUEST['isAnonymous']) ? 1 : 0;
		$donation->dedicate = isset($_REQUEST['isDedicated']) ? 1 : 0;
		if ($donation->dedicate == 1) {
			$donation->dedicateType = $_REQUEST['dedicationType'];
			$donation->honoreeFirstName = $_REQUEST['honoreeFirstName'];
			$donation->honoreeLastName = $_REQUEST['honoreeLastName'];
		}
		$donation->shouldBeNotified = isset($_REQUEST['shouldBeNotified']) ? 1 : 0;
		if ($donation->shouldBeNotified == 1) {
			$donation->notificationFirstName = $_REQUEST['notificationFirstName'];
			$donation->notificationLastName = $_REQUEST['notificationLastName'];
			$donation->notificationAddress = $_REQUEST['notificationAddress'];
			$donation->notificationCity = $_REQUEST['notificationCity'];
			$donation->notificationState = $_REQUEST['notificationState'];
			$donation->notificationZip = $_REQUEST['notificationZip'];
		}
		$donation->donateToLocationId = $toLocation;
		$donation->donateToLocation = $donateToLocation;
		$donation->comments = $comments;
		$donation->donationSettingId = $_REQUEST['settingId'];
		$donation->sendEmailToUser = 1;

		if (!empty($_REQUEST['address'])) {
			$donation->address = $_REQUEST['address'];
		}
		if (!empty($_REQUEST['address2'])) {
			$donation->address2 = $_REQUEST['address2'];
		}
		if (!empty($_REQUEST['city'])) {
			$donation->city = $_REQUEST['city'];
		}
		if (!empty($_REQUEST['state'])) {
			$donation->state = $_REQUEST['state'];
		}
		if (!empty($_REQUEST['zip'])) {
			$donation->zip = $_REQUEST['zip'];
		}

		$donation->insert();

		$purchaseUnits['custom_id'] = $paymentLibrary->subdomain;

		return [
			$paymentLibrary,
			$userLibrary,
			$payment,
			$purchaseUnits,
			$patron,
			$donation,
		];

	}

	/** @noinspection PhpUnused */
	private function addDonation($payment, $tempDonation) : Donation {
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		$donation = new Donation();
		$donation->paymentId = $payment->id;
		$donation->firstName = $tempDonation->firstName;
		$donation->lastName = $tempDonation->lastName;
		$donation->email = $tempDonation->email;
		$donation->anonymous = $tempDonation->isAnonymous;
		$donation->dedicate = $tempDonation->isDedicated;
		if ($tempDonation->isDedicated == 1) {
			$donation->dedicateType = $tempDonation->dedication->type;
			$donation->honoreeFirstName = $tempDonation->dedication->honoreeFirstName;
			$donation->honoreeLastName = $tempDonation->dedication->honoreeLastName;
		}
		$donation->shouldBeNotified = $tempDonation->shouldBeNotified;
		if ($tempDonation->shouldBeNotified == 1) {
			$donation->notificationFirstName = $tempDonation->notification->notificationFirstName;
			$donation->notificationLastName = $tempDonation->notification->notificationLastName;
			$donation->notificationAddress = $tempDonation->notification->notificationAddress;
			$donation->notificationCity = $tempDonation->notification->notificationCity;
			$donation->notificationState = $tempDonation->notification->notificationState;
			$donation->notificationZip = $tempDonation->notification->notificationZip;
		}
		$donation->donateToLocationId = $tempDonation->donateToLocationId;
		$donation->donateToLocation = $tempDonation->donateToLocation;
		$donation->comments = $tempDonation->comments;
		$donation->donationSettingId = $tempDonation->donationSettingId;
		$donation->sendEmailToUser = 1;
		$donation->insert();

		return $donation;
	}

	/** @noinspection PhpUnused */
	private function createGenericOrder($paymentType = '') {
		$this->requireLoggedInUser(null, 'You must be signed in to pay fines, please sign in.');
		$transactionDate = time();
		$user = UserAccount::getLoggedInUser();

		$patronId = $_REQUEST['patronId'];

		$patron = $user->getUserReferredTo($patronId);

		if ($patron === false) {
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
		$selectedFines = $_REQUEST['selectedFine'] ?? [];
		$fines = $patron->getFines(false);
		$useOutstanding = $patron->getCatalogDriver()->showOutstandingFines();

		// For Sierra, check if user's account is locked
		if (!empty($fines) && $patron->getCatalogDriver()->isPatronAccountLocked($patron, reset($fines[$patronId]))) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'This account is currently in use by staff.  Fine payments cannot be made at this time.  Please try again after a few moments or contact the library if this issue persists.',
					'isPublicFacing' => true,
				]),
			];
		}

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
					if (!is_numeric($fineAmount) || $fineAmount <= 0 || round($fineAmount, 2) > round($maxFineAmount, 2)) {
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
		require_once ROOT_DIR . '/sys/Account/UserPaymentLine.php';
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
			if ($paymentType == 'ACI') {
				$payment->aciToken = $_REQUEST['token'];
				// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
				/** @noinspection PhpUnhandledExceptionInspection */
				$data = random_bytes(16);
				assert(strlen($data) == 16);

				// Set version to 0100
				$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
				// Set bits 6-7 to 10
				$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

				// Output the 36 character UUID.
				$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
				$payment->orderId = $uuid;
			}
			if ($paymentType == 'deluxe') {
				$payment->deluxeRemittanceId = $_REQUEST['token'];
			}
			if ($paymentType == 'square') {
				$payment->squareToken = $_REQUEST['token'];
			}
			if ($paymentType == 'stripe') {
				$payment->stripeToken = $_REQUEST['token'];
			}
		}

		$payment->insert();
		$paymentId = $payment->id;

		//Add payment lines for each fine paid
		foreach ($fines[$patronId] as $fine) {
			$addToOrder = false;
			if ($paymentLibrary->finesToPay == 0) {
				$addToOrder = true;
			} else {
				foreach ($selectedFines as $fineId => $status) {
					if ($fine['fineId'] == $fineId) {
						$addToOrder = true;
						break;
					}
				}
			}

			if ($addToOrder) {
				$fineId = $fine['fineId'];
				$paymentLine = new UserPaymentLine();
				$paymentLine->paymentId = $paymentId;
				$description = $fine['reason'];
				if (!empty($description) && !empty($fine['message'])) {
					$description .= " - " . $fine['message'];
				}
				$paymentLine->description = $description;
				$fineAmount = $_REQUEST['amountToPay'][$fineId] ?? ($useOutstanding ? $fine['amountOutstandingVal'] : $fine['amountVal']);
				$paymentLine->amountPaid = $fineAmount;
				$paymentLine->insert();
			}
		}

		$purchaseUnits['custom_id'] = $paymentLibrary->subdomain;

		return [
			$paymentLibrary,
			$userLibrary,
			$payment,
			$purchaseUnits,
			$patron,
		];
	}

	/** @noinspection PhpUnused */
	function createPayPalOrder() : array {
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
			$postParams = ['grant_type' => 'client_credentials'];

			$accessTokenUrl = $baseUrl . "/v1/oauth2/token";
			$accessTokenResults = $payPalAuthRequest->curlPostPage($accessTokenUrl, $postParams);
			$decodedAccessTokenResults = json_decode($accessTokenResults);

			ExternalRequestLogEntry::logRequest('fine_payment.createPayPalOrder', 'POST', $accessTokenUrl, $payPalAuthRequest->getHeaders(), json_encode($postParams), $payPalAuthRequest->getResponseCode(), $accessTokenResults, ['client_secret' => $clientSecret]);

			if (empty($decodedAccessTokenResults->access_token)) {
				return $this->failureResult(null, 'Unable to authenticate with PayPal, please try again in a few minutes.');
			} else {
				$accessToken = $decodedAccessTokenResults->access_token;
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
			$decodedPaymentResponse = json_decode($paymentResponse);

			ExternalRequestLogEntry::logRequest('fine_payment.createPayPalOrder', 'POST', $paymentRequestUrl, $payPalPaymentRequest->getHeaders(), json_encode($postParams), $payPalPaymentRequest->getResponseCode(), $paymentResponse, []);

			if ($decodedPaymentResponse->status != 'CREATED') {
				return $this->failureResult(null, 'Unable to create your order in PayPal.');
			}

			//Log the request in the database so we can validate it on return
			$payment->orderId = $decodedPaymentResponse->id;
			$payment->update();

			return [
				'success' => true,
				'orderInfo' => $paymentResponse,
				'orderID' => $decodedPaymentResponse->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function completePayPalOrder() : array {
		global $configArray;

		$orderId = $_REQUEST['orderId'];
		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];

		global $library;
		$paymentLibrary = $library;

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$payment = new UserPayment();
		$payment->orderId = $orderId;
		if ($transactionType == 'donation') {
			//Get the order information
			$payment->transactionType = 'donation';
			if ($payment->find(true)) {
				require_once ROOT_DIR . '/sys/Donations/Donation.php';
				$donation = new Donation();
				$donation->paymentId = $payment->id;
				if (!$donation->find(true)) {
					header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
				}
			} else {
				header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
			}
		} else {
			//Get the order information
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
			$postParams = ['grant_type' => 'client_credentials'];

			$accessTokenUrl = $baseUrl . "/v1/oauth2/token";
			$accessTokenResults = $payPalAuthRequest->curlPostPage($accessTokenUrl, $postParams);
			$decodedAccessTokenResults = json_decode($accessTokenResults);

			ExternalRequestLogEntry::logRequest('fine_payment.completePayPalOrder', 'POST', $accessTokenUrl, $payPalAuthRequest->getHeaders(), json_encode($postParams), $payPalAuthRequest->getResponseCode(), $accessTokenResults, ['client_secret' => $clientSecret]);

			if (empty($decodedAccessTokenResults->access_token)) {
				return $this->failureResult(null, 'Unable to authenticate with PayPal, please try again in a few minutes.');
			} else {
				$accessToken = $decodedAccessTokenResults->access_token;
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
			$decodedPaymentResponse = json_decode($paymentResponse);

			ExternalRequestLogEntry::logRequest('fine_payment.completePayPalOrder', 'GET', $paymentRequestUrl, $payPalPaymentRequest->getHeaders(), '', $payPalPaymentRequest->getResponseCode(), $paymentResponse, []);

			$purchaseUnits = $decodedPaymentResponse->purchase_units;
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
			$donation = new Donation();
			$donation->paymentId = $payment->id;
			if ($donation->find(true)) {
				$donation->sendReceiptEmail();
				return [
					'success' => true,
					'isDonation' => true,
					'paymentId' => $payment->id,
					'donationId' => $donation->id,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Unable to find donation with provided id',
					'isDonation' => true,
					'paymentId' => $payment->id,
					'donationId' => '',
				];
			}
		} else {
			if ($payment->completed) {
				return $this->failureResult(null, 'This payment has already been processed');
			} else {
				$user = UserAccount::getActiveUserObj();
				$patron = $user->getUserReferredTo($patronId);

				$result = $patron->completeFinePayment($payment);
				if (!$result['success']) {
					//If the payment does not complete in the ILS, add information to the payment for tracking
					//Also email the admin that it was completed in PayPal, but not the ILS
					$payment->message .= translate([
							'text' => 'Your payment was received, but was not cleared in our library software. Your account will be updated within the next business day. If you need more immediate assistance, please visit the library with your receipt.',
							'isPublicFacing' => true
						]) . ' ' . $result['message'];
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
	function createSquareOrder() : array {
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
					,
					,
					$payment,
					,
					,
					$tempDonation,
				] = $result;
				$this->addDonation($payment, $tempDonation);
			} else {
				[
					,
					,
					$payment,
					,
					,
				] = $result;
			}

			return [
				'success' => true,
				'paymentId' => $payment->id,
			];
		}
	}

/** @noinspection PhpUnused */
	function completeSquareOrder(): array {
		global $configArray;
		global $library;

		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];
		$paymentToken = $_REQUEST['token'];

		$paymentLibrary = $library;

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';

		$payment = new UserPayment();
		$payment->squareToken = $paymentToken;
		$payment->userId = $patronId;

		if ($transactionType == 'donation') {
			$payment->transactionType = 'donation';
		}

		if (!$payment->find(true)) {
			if ($transactionType == 'donation') {
				header('Location: ' . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
			}

			return $this->failureResult(
				null,
				'Your payment with Square could not be completed. Please contact library staff for assistance.'
			);
		}

		if ($transactionType == 'donation') {
			require_once ROOT_DIR . '/sys/Donations/Donation.php';

			$donation = new Donation();
			$donation->paymentId = $payment->id;

			if (!$donation->find(true)) {
				header('Location: ' . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
				return $this->failureResult( null, 'Your payment with Square could not be completed. Please contact library staff for assistance.');
			}

		} else {
			$user = UserAccount::getLoggedInUser();
			$patron = $user->getUserReferredTo($patronId);
			$userLibrary = $patron->getHomeLibrary();

			$systemVariables = SystemVariables::getSystemVariables();

			if ($systemVariables->libraryToUseForPayments == 0) {
				$paymentLibrary = $userLibrary;
			}

			if ($payment->completed) {
				return $this->failureResult(null, 'This payment has already been processed');
			}
		}

		require_once ROOT_DIR . '/sys/ECommerce/SquareSetting.php';

		$squareSettings = new SquareSetting();
		$squareSettings->id = $paymentLibrary->squareSettingId;

		if (!$squareSettings->find(true)) {
			return $this->failureResult(
				null,
				'Your payment with Square could not be completed. Please contact library staff for assistance.'
			);
		}

		$lineItemResult = $squareSettings->createLineItems($payment);

		if (!$lineItemResult['success']) {
			return $lineItemResult;
		}

		$paymentResult = $squareSettings->submitTransaction(
			$payment,
			$paymentToken,
			$lineItemResult['orderId'],
			$lineItemResult['amountMoney']
		);

		if (!$paymentResult['success']) {
			$payment->error = 1;
			$payment->message = $paymentResult['message'];
			$payment->update();

			return $paymentResult;
		}

		if ($paymentResult['payment']->status != 'COMPLETED' && $paymentResult['payment']->status != 'APPROVED') {
			return $this->failureResult(
				null,
				'Payment status is ' . ($paymentResult['payment']->status ?? 'NO STATUS RECEIVED') . '. Please make sure the information you entered is correct.'
			);
		}

		$payment->transactionId = $paymentResult['payment']->id;
		$payment->receiptUrl = $paymentResult['payment']->receipt_url;
		$payment->orderId = $paymentResult['payment']->order_id;

		if ($transactionType == 'donation') {
			$payment->completed = 1;
			$payment->update();

			$donation = new Donation();
			$donation->paymentId = $payment->id;

			if (!$donation->find(true)) {
				return [
					'success' => false,
					'message' => 'Unable to find donation with provided id',
					'isDonation' => true,
					'paymentId' => $payment->id,
					'donationId' => '',
				];
			}

			$donation->sendReceiptEmail();

			return [
				'success' => true,
				'isDonation' => true,
				'paymentId' => $payment->id,
				'donationId' => $donation->id,
				'message' => 'Your payment has been completed.',
				'receiptUrl' => $payment->receiptUrl,
			];
		}

		$payment->update();

		$user = UserAccount::getActiveUserObj();
		$patron = $user->getUserReferredTo($patronId);

		$result = $patron->completeFinePayment($payment);

		if (!$result['success']) {
			$payment->message .= 'Your payment was received, but was not cleared in our library software. Your account will be updated within the next business day. If you need more immediate assistance, please visit the library with your receipt. ' . $result['message'];

			$payment->update();
		}

		$result['receiptUrl'] = $payment->receiptUrl;

		return $result;
	}

	/** @noinspection PhpUnused */
	function createStripeOrder() : array {
		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('stripe');
		} else {
			$result = $this->createGenericOrder('stripe');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			if ($transactionType == 'donation') {
				[
					,
					,
					$payment,
					,
					,
					$tempDonation,
				] = $result;
				$this->addDonation($payment, $tempDonation);
			} else {
				[
					,
					,
					$payment,
					,
					,
				] = $result;
			}

			return [
				'success' => true,
				'paymentId' => $payment->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function completeStripeOrder(): array {
		global $configArray;

		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];
		$paymentId = $_REQUEST['paymentId'];
		$paymentMethodId = $_REQUEST['paymentMethodId'];

		global $library;
		$paymentLibrary = $library;

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		require_once ROOT_DIR . '/sys/ECommerce/StripeSetting.php';

		$payment = new UserPayment();
		$payment->id = $paymentId;
		if ($transactionType == 'donation') {
			//Get the order information
			$payment->transactionType = 'donation';
			if ($payment->find(true)) {
				$paymentId = $payment->id;
				require_once ROOT_DIR . '/sys/Donations/Donation.php';
				$donation = new Donation();
				$donation->paymentId = $payment->id;
				if (!$donation->find(true)) {
					header('Location: ' . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
					die();
				} else {
					$stripeSettings = new StripeSetting();
					$stripeSettings->id = $paymentLibrary->stripeSettingId;
					if ($stripeSettings->find(true)) {
						//header('Location: ' . $configArray['Site']['url'] . '/Donations/DonationCompleted?id=' . $payment->id);
						$result = $stripeSettings->submitTransaction($payment, $paymentMethodId, $transactionType);
						$result['submitPaymentText'] = translate([
							'text' => 'Submit Payment',
							'isPublicFacing' => true
						]);
						return $result;
					} else {
						return [
							'success' => false,
							'message' => 'Could not complete donation. Stripe is not setup for this library.',
							'submitPaymentText' => translate([
								'text' => 'Submit Payment',
								'isPublicFacing' => true
							])
						];
					}
				}
			} else {
				return [
					'success' => false,
					'message' => 'Payment settings were not properly configured.',
					'submitPaymentText' => translate([
						'text' => 'Submit Payment',
						'isPublicFacing' => true
					])
				];
			}
		} else {
			//Get the order information
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

				$stripeSettings = new StripeSetting();
				$stripeSettings->id = $paymentLibrary->stripeSettingId;
				if ($stripeSettings->find(true)) {
					$result = $stripeSettings->submitTransaction($payment, $paymentMethodId, $transactionType);
					$result['submitPaymentText'] = translate([
						'text' => 'Submit Payment',
						'isPublicFacing' => true
					]);
					return $result;
				} else {
					return [
						'success' => false,
						'message' => 'Could not complete payment. Stripe is not setup for this library.',
						'submitPaymentText' => translate([
							'text' => 'Submit Payment',
							'isPublicFacing' => true
						])
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'Unable to find payment in system to complete.',
					'submitPaymentText' => translate([
						'text' => 'Submit Payment',
						'isPublicFacing' => true
					])
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function createMSBOrder() : array {
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
				$this->addDonation($payment, $tempDonation);
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
				$paymentRequestUrl .= "&PaymentRedirectUrl=" . $configArray['Site']['url'] . '/Donations/DonationCompleted?id=' . $payment->id;
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
	function createCompriseOrder() : array {
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
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

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
				if ($transactionType == 'donation' && !empty($compriseSettings->customerNameForDonation) && !empty($compriseSettings->customerIdForDonation)) {
					$paymentRequestUrl .= "&LocationID=" . $compriseSettings->customerNameForDonation;
					$paymentRequestUrl .= "&CustomerID=" . $compriseSettings->customerIdForDonation;
				} else {
					$paymentRequestUrl .= "&LocationID=" . $compriseSettings->customerName;
					$paymentRequestUrl .= "&CustomerID=" . $compriseSettings->customerId;
				}
				if ($transactionType == 'donation') {
					$paymentRequestUrl .= "&PatronID=Guest";
				} else {
					$paymentRequestUrl .= "&PatronID=" . $patron->getBarcode();
				}
				$paymentRequestUrl .= '&UserName=' . urlencode($compriseSettings->username);
				$paymentRequestUrl .= '&Password=' . urlencode($compriseSettings->password);
				$paymentRequestUrl .= '&Amount=' . number_format($payment->totalPaid, 2, '.', '');
				$paymentRequestUrl .= "&URLPostBack=" . urlencode($configArray['Site']['url'] . '/Comprise/Complete');
				if ($transactionType == 'donation') {
					$paymentRequestUrl .= "&URLReturn=" . urlencode($configArray['Site']['url'] . '/Donations/DonationCompleted?id=' . $payment->id);
					$paymentRequestUrl .= "&URLCancel=" . urlencode($configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
				} else {
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
				return $this->failureResult(null, 'Comprise was not properly configured');
			}
		}
	}

	/** @noinspection PhpUnused */
	function createProPayOrder() : array {
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
				$proPayPayerAccountId = null;
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

					ExternalRequestLogEntry::logRequest('fine_payment.createpropayorder', 'PUT', $url, $curlWrapper->getHeaders(), json_encode($createPayer), $curlWrapper->getResponseCode(), $createPayerResponse, []);

					if ($createPayerResponse && $curlWrapper->getResponseCode() == 200) {
						$jsonResponse = json_decode($createPayerResponse);
						if ($patron != null) {
							$patron->proPayPayerAccountId = $jsonResponse->ExternalAccountID;
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

					ExternalRequestLogEntry::logRequest('fine_payment.createpropayorder', 'PUT', $url, $curlWrapper->getHeaders(), json_encode($createMerchantProfile), $curlWrapper->getResponseCode(), $createMerchantProfileResponse, []);

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
						$requestElements->ReturnURL = $configArray['Site']['url'] . "/ProPay/$payment->id/Complete?type=" . $payment->transactionType . "&donation=" . $donation->id;
					} else {
						$requestElements->ReturnURL = $configArray['Site']['url'] . "/ProPay/$payment->id/Complete?type=" . $payment->transactionType;
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

					ExternalRequestLogEntry::logRequest('fine_payment.createpropayorder', 'PUT', $url, $curlWrapper->getHeaders(), json_encode($requestElements), $curlWrapper->getResponseCode(), $response, []);

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
						return $this->failureResult(null, 'Could not connect to the payment processor');
					}
				} else {
					return $this->failureResult(null, 'Payer Account ID could not be determined.');
				}

			} else {
				return $this->failureResult(null, 'ProPay was not properly configured');
			}
		}
	}

	/** @noinspection PhpUnused */
	function createWorldPayOrder() : array {
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

			// Log the WorldPay order creation request
			require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
			ExternalRequestLogEntry::logRequest('fine_payment.createworldpayorder', 'GET', '/MyAccount/AJAX?method=createWorldPayOrder', [], json_encode($_REQUEST), '200', json_encode([
				'success' => true,
				'paymentId' => $payment->id,
				'url' => $payment->url,
				'transactionDate' => $payment->transactionDate,
				'userId' => $payment->userId
			]), []);

			return [
				'success' => true,
				'paymentId' => $payment->id,
			];
		}
	}

	/** @noinspection PhpUnused */
	function checkWorldPayOrderStatus() : array {
		$result = [
			'success' => false,
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
	function createXPressPayOrder() : array {
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

	/** @noinspection PhpUnused */
	function createCertifiedPaymentsByDeluxeOrder() : array {
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

	/** @noinspection PhpUnused */
	function createNCROrder() : array {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('NCR');
		} else {
			$result = $this->createGenericOrder('NCR');
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
			require_once ROOT_DIR . '/sys/ECommerce/NCRPaymentsSetting.php';
			$NCRPaymentsSetting = new NCRPaymentsSetting();
			$NCRPaymentsSetting->id = $userLibrary->ncrSettingId;
			if ($NCRPaymentsSetting->find(true)) {
				//hard coded api route
				$url = "https://magic.collectorsolutions.com/magic-api/api/transaction/redirect";

				$transactionIDNumber = $NCRPaymentsSetting->lastTransactionNumber + 1;
				$NCRPaymentsSetting->lastTransactionNumber = $transactionIDNumber;
				$NCRPaymentsSetting->update();
				$transactionIdentifier = "AspenPayment" . $userLibrary->libraryId . $userLibrary->ilsCode . $transactionIDNumber;
				$newRedirectRequest = new CurlWrapper();
				$newRedirectRequest->addCustomHeaders([
					"Content-Type: application/json",
					"Accept: application/json",
					"Accept-Charset: utf-8",
				], true);

				$lineItem = new stdClass(); //line items need to be objects not arrays
				$lineItem->identifiers[0] = "Illinet/OCLC Invoice";
				$lineItem->amount = $payment->totalPaid;
				$lineItem->paymentType = $NCRPaymentsSetting->paymentTypeId;

				$postParams = [
					'clientKey' => $NCRPaymentsSetting->clientKey,
					'transactionIdentifier' => $transactionIdentifier,
					'collectionMode' => 1,
					'amount' => $payment->totalPaid,
					'billing' => [
						'email' => $patron->email
					],
					'lineItems' => [$lineItem],
					'urlReturnPost' => $configArray['Site']['url'] . "/MyAccount/NCRComplete",
					'allowedPaymentMethod' => 3,
				];

				$paymentRequestUrl = "https://magic.collectorsolutions.com/magic-ui/PaymentRedirect/" . $NCRPaymentsSetting->webKey . "/" . $transactionIdentifier;

				$payment->orderId = $transactionIdentifier;
				$payment->update();

				$resultJSON = $newRedirectRequest->curlPostBodyData($url, $postParams);
				$result = json_decode($resultJSON);

				ExternalRequestLogEntry::logRequest('fine_payment.createNCROrder', 'POST', $url, $newRedirectRequest->getHeaders(), json_encode($postParams), $newRedirectRequest->getResponseCode(), $resultJSON, []);

				if ($result->status != "ok") {
					return [
						'success' => false,
						'message' => $result->errors[0]->message,
					];
				}
				return [
					'success' => true,
					'message' => 'Redirecting to payment processor',
					'paymentRequestUrl' => $paymentRequestUrl,
				];
			} else {
				return $this->failureResult(null, 'NCR was not properly configured for the library.');
			}
		}
	}

	/** @noinspection PhpUnused */
	function createSnapPayOrder() : array {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('SnapPay');
		} else {
			$result = $this->createGenericOrder('SnapPay');
		}
		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		} else {
			// Get the current sessionId to set cookie SameSite=None AND pass to SnapPay as udf8
			$sessionVariable = $_COOKIE['aspen_session'] ?? '';
			$sessionValue = '';
			// Check if the session variable matches the pattern
			if (preg_match('/^[0-9a-z]{26}$/', $sessionVariable, $matches)) {
				$sessionValue = $matches[0];
			}

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
			require_once ROOT_DIR . '/sys/ECommerce/SnapPaySetting.php';
			$snapPaySetting = new SnapPaySetting();
			$snapPaySetting->id = $userLibrary->snapPaySettingId;
			if ($snapPaySetting->find(true)) {
				$patron->loadContactInformation();
				// hard coded SnapPay hosted payment page URL
				$paymentRequestUrl = "https://www.snappayglobal.com/Interop/HostedPaymentPage";
				if ($snapPaySetting->sandboxMode == 1 || $snapPaySetting->sandboxMode == '1') {
					$paymentRequestUrl = "https://stage.snappayglobal.com/Interop/HostedPaymentPage";
				}

				$lineItem = new stdClass(); //line items need to be objects not arrays
				$lineItem->identifiers[0] = "SnapPay Invoice";
				$lineItem->amount = $payment->totalPaid;
				$lineItem->paymentType = '';

// create the HMAC signature
				$apiAuthCode = $snapPaySetting->apiAuthenticationCode;
				$accountid = $snapPaySetting->accountId;
				$customerid = $patron->id;
				$merchantid = $snapPaySetting->merchantId;
				$transactionamount = number_format($payment->totalPaid, 2);
				$currencycode = 'USD'; // TO DO: fix this hardcode
				$paymentmode = 'CC'; // TO DO: allow ACH too
				$email = $patron->email;
				/** @noinspection PhpUnhandledExceptionInspection */
				$nonce = bin2hex(random_bytes(16));

				/** @noinspection PhpUnhandledExceptionInspection */
				$epochStart = new DateTime("1970-01-01 00:00:00", new DateTimeZone("UTC"));
				/** @noinspection PhpUnhandledExceptionInspection */
				$timeSpan = (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp() - $epochStart->getTimestamp();
				$requestTimeStamp = (string)$timeSpan;

				$signatureRawData = $accountid . $customerid . $merchantid . $transactionamount . $currencycode . $paymentmode . $email . $nonce . $requestTimeStamp;
// Convert base64-encoded apiAuthCode to byte array
				$secretKeyByteArray = base64_decode($apiAuthCode);
// Encode signatureRawData to byte array using UTF-8
				$signature = mb_convert_encoding($signatureRawData, 'UTF-8', 'ISO-8859-1');
// Compute HMAC SHA-256 hash
				$signatureBytes = hash_hmac('sha256', $signature, $secretKeyByteArray, true);
// Convert hash to base64-encoded string
				$requestSignatureBase64String = base64_encode($signatureBytes);
// Format signatureData string
				$signatureData = sprintf("%s:%s:%s", $requestSignatureBase64String, $nonce, $requestTimeStamp);
// Encode signatureData to byte array using UTF-8 and convert to base64-encoded string
				$HmacValue = base64_encode(mb_convert_encoding($signatureData, 'UTF-8', 'ISO-8859-1'));

				$postParams = [
					'udf1' => $payment->id,
					'udf9' => $payment->id,
					// Aspen user payment id is duplicated in udf1 and udf9. As of 2025 05 23, Nashville's SnapPay configuration has udf9 associated with the SnapPay 'orderId' field, which is searchable via SnapPay GetTransaction API.
					'udf8' => $sessionValue,
					'accountid' => $snapPaySetting->accountId,
					'customerid' => $patron->id,
					// TO DO: ensure correct ID
					'currencycode' => 'USD',
					// TO DO: Allow for other currency types
					'transactionamount' => number_format($payment->totalPaid, 2),
					'merchantid' => $snapPaySetting->merchantId,
					'paymentmode' => 'CC',
					// TO DO: allow ACH too
					'cvvrequired' => 'Y',
					// TO DO: allow N too
					'enableemailreceipt' => 'Y',
					// TO DO: allow N too
					'redirectionurl' => $configArray['Site']['url'] . "/SnapPay/Complete?u=" . $payment->id,
					// TO DO: documentation: FISERV PDF has 'redirectionurl'; error has 'redirecturl'; 'redirectionurl ' is correct
					'signature' => $HmacValue,
					// TO DO: documentation: FISERV PDF has 'signature'; error has 'Signature'; 'signature' is correct
					'firstname' => $patron->firstname,
					'lastname' => $patron->lastname,
					'addressline1' => $patron->_address1,
					'city' => $patron->_city,
					'state' => $patron->_state,
					'zip' => $patron->_zip,
					'email' => $patron->email,
					'phone' => $patron->phone,
				];

				return [
					'success' => true,
					'message' => 'Redirecting to payment processor',
					'postParams' => $postParams,
					'paymentRequestUrl' => $paymentRequestUrl,
				];
			} else {
				return $this->failureResult(null, 'SnapPay was not properly configured for the library.');
			}
		}
	}

	/** @noinspection PhpUnused */
	function createPayPalPayflowOrder() : array {
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
			/** @noinspection PhpUnhandledExceptionInspection */
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
				'LABELTEXTCOLOR' => $bodyTextColor,
				'BILLTOFIRSTNAME' => $patron->firstname,
				'BILLTOLASTNAME' => $patron->lastname,
				'COMMENT1' => $patron->ils_barcode,
				'COMMENT2' => $patron->ils_username
			];

			foreach ($postParams as $index => $value) {
				$paramList[] = $index . '[' . strlen($value) . ']=' . $value;
			}

			$params = implode('&', $paramList);

			$tokenResults = $payflowTokenRequest->curlSendPage($tokenRequestUrl, 'POST', $params);
			ExternalRequestLogEntry::logRequest('fine_payment.getPayflowToken', 'POST', $tokenRequestUrl, $payflowTokenRequest->getHeaders(), $params, $payflowTokenRequest->getResponseCode(), $tokenResults, []);
			$tokenResults = PayPalPayflowSetting::parsePayflowString($tokenResults);

			if ($tokenResults['RESULT'] != 0) {
				return $this->failureResult(null, 'Unable to authenticate with Payflow, please try again in a few minutes.');
			} else {
				$token = $tokenResults['SECURETOKEN'];
				$tokenId = $tokenResults['SECURETOKENID'];
			}

			/** @noinspection HtmlUnknownAttribute */
			/** @noinspection HtmlDeprecatedAttribute */
			return [
				'success' => true,
				'paymentIframe' => "<iframe class='fulfillmentFrame' id='payflow-link-iframe' src='$iframeUrl/?SECURETOKEN=$token&SECURETOKENID=$tokenId' sandbox='allow-top-navigation allow-scripts allow-same-origin allow-forms allow-modals' border='0' frameborder='0' scrolling='yes' allowtransparency='true'>\n</iframe>",
			];
		}
	}

	/** @noinspection PhpUnused */
	function createACIOrder() : array {
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
					,
					,
					$payment,
					,
					,
					,
				] = $result;
			} else {
				[
					,
					,
					$payment,
					,
					,
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
	function completeACIOrder() : array {
		global $configArray;

		$patronId = $_REQUEST['patronId'];
		$transactionType = $_REQUEST['type'];
		$fundingToken = $_REQUEST['fundingToken'];
		$accessToken = $_REQUEST['accessToken'];
		$paymentId = $_REQUEST['paymentId'];
		$billerAccount = $_REQUEST['billerAccountId'];
		global $library;
		$paymentLibrary = $library;
		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		require_once ROOT_DIR . '/sys/Donations/Donation.php';
		require_once ROOT_DIR . '/sys/ECommerce/ACISpeedpaySetting.php';

		$payment = new UserPayment();
		$payment->id = $paymentId;
		if ($transactionType == 'donation') {
			//Get the order information
			$payment->transactionType = 'donation';
			if ($payment->find(true)) {
				$donation = new Donation();
				$donation->paymentId = $payment->id;
				if (!$donation->find(true)) {
					header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
					return [];
				}else{
					return $this->failureResult(null, 'ACI Donation payment not applied.');
				}
			} else {
				header("Location: " . $configArray['Site']['url'] . '/Donations/DonationCancelled?id=' . $payment->id);
				return [];
			}
		} else {
			//Get the order information
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
					return $aciSpeedpaySettings->submitTransaction($patron, $payment, $fundingToken, $billerAccount);
				} else {
					return $this->failureResult(null, 'Could not complete payment. ACI Speedpay is not setup for this library.');
				}
			} else {
				return $this->failureResult(null, 'Unable to find payment in system to complete.');
			}
		}
	}

	/** @noinspection PhpUnused */
	function dismissPlacard() : array {
		$this->requireLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$placardId = $_REQUEST['placardId'];

		$result = $this->failureResult(null, 'Unknown Error');

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
				$decodedAuthResponse = json_decode($authResponse);

				ExternalRequestLogEntry::logRequest('fine_payment.createInvoiceCloudOrder', 'GET', $url, $authRequest->getHeaders(), '', $authRequest->getResponseCode(), $authResponse, []);

				if (!$decodedAuthResponse->Active) {
					return $this->failureResult(null, 'Unable to create your order in InvoiceCloud. Library has an inactive account.');
				}

				$now = time();
				$token = 'B' . $patron->getBarcode() . 'T' . $now;
				$createInvoice = new StdClass();
				$createInvoice->InvoiceNumber = $token;
				$createInvoice->TypeID = intval($invoiceCloudSetting->invoiceTypeId);
				$createInvoice->BalanceDue = number_format((float)$payment->totalPaid, 2, '.', '');
				$ccServiceFee = $invoiceCloudSetting->ccServiceFee;
				if (isset($ccServiceFee) && str_contains($ccServiceFee, '%')) {
					$percent = floatval(str_replace('%', '', $ccServiceFee));
					$ccServiceFee = round($payment->totalPaid * ($percent / 100), 2);
				}
				$createInvoice->CCServiceFee = $ccServiceFee;
				$createInvoice->ACHServiceFee = $ccServiceFee;
				$createInvoice->DueDate = date('m/d/Y');
				$createInvoice->InvoiceDate = date('m/d/Y');

				$createCustomer = new StdClass();
				$createCustomer->AccountNumber = $patron->getBarcode();
				$createCustomer->Name = $patron->firstname . ' ' . $patron->lastname;
				$createCustomer->EmailAddress = $patron->email;
				$createCustomer->Invoices = [$createInvoice];

				$PageResultOptions = [
					'AllowRegisterAccount' => true,
					'InvoiceOptions' => [
						'AllowViewRelated' => false,
						'AllowRemindMe' => false,
					]
				];

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
					'PageResultOptions' => $PageResultOptions,
				];

				$paymentRequest = new CurlWrapper();
				$paymentRequest->addCustomHeaders([
					'Content-Type: application/json',
					'Authorization: ' . $authorization,
				], true);

				$url = 'https://www.invoicecloud.com/cloudpaymentsapi/v2';
				$paymentResponse = $paymentRequest->curlPostBodyData($url, $postParams);
				$decodedPaymentResponse = json_decode($paymentResponse);

				ExternalRequestLogEntry::logRequest('fine_payment.createInvoiceCloudOrder', 'POST', $url, $paymentRequest->getHeaders(), json_encode($postParams), $paymentRequest->getResponseCode(), $paymentResponse, []);

				if ($decodedPaymentResponse->Message != 'SUCCESS') {
					return [
						'success' => false,
						'message' => 'Unable to create your order in InvoiceCloud. ' . $decodedPaymentResponse->Message
					];
				}
				$paymentRequestUrl = $decodedPaymentResponse->Data->CloudPaymentURL;

				return [
					'success' => true,
					'message' => 'Redirecting to payment processor',
					'paymentRequestUrl' => $paymentRequestUrl,
				];
			} else {
				return $this->failureResult(null, 'InvoiceCloud was not properly configured for the library.');
			}
		}
	}

	/** @noinspection PhpUnused */
	function createHeyCentricOrder() : array {
		global $configArray;

		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('HeyCentric');
		} else {
			$result = $this->createGenericOrder('HeyCentric');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		}

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

		require_once ROOT_DIR . '/sys/ECommerce/HeyCentricSetting.php';
		$heyCentricSettings = new HeyCentricSetting();
		$homeLocationHeyCentricSettingId = $patron->getHomeLocation()->heyCentricSettingId;
		$heyCentricSettings->id = $homeLocationHeyCentricSettingId != -1 ? $homeLocationHeyCentricSettingId : $paymentLibrary->heyCentricSettingId;

		if (!$heyCentricSettings->find(true)) {
			return $this->failureResult(null, 'HeyCentric was not properly configured');
		}

		$urlParameterSettings = $heyCentricSettings->__get('urlParameterSettingList');

		$finesSelected = [];

		foreach (explode(',', $payment->finesPaid) as $fineSelected) {
			$finesSelected[] = [
				'id' => explode('|', $fineSelected)[0],
				'amount' => explode('|', $fineSelected)[1]
			];
		}

		$locationDetails = $patron->getCatalogDriver()->hasAdditionalFineFields() ? $patron->getCatalogDriver()->getAdditionalLocationDetails($patron->getHomeLocationCode()) : [];

		// URL parameters
		$paymentRequestUrl = $heyCentricSettings->baseUrl;
		if ($urlParameterSettings["client_includeInUrl"]) {
			$paymentRequestUrl .= "client=";
			if (isset($urlParameterSettings['client_kohaAdditionalField']) && $urlParameterSettings['client_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['client_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['client_value']) ? $urlParameterSettings['client_value'] : "";
			}
		}
		if ($urlParameterSettings["area_includeInUrl"]) {
			$paymentRequestUrl .= "&area=";
			if (isset($urlParameterSettings['area_kohaAdditionalField']) && $urlParameterSettings['area_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['area_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['area_value']) ? $urlParameterSettings['area_value'] : "";
			}
		}
		if ($urlParameterSettings["till_includeInUrl"]) {
			$paymentRequestUrl .= "&till=";
			if (isset($urlParameterSettings['till_kohaAdditionalField']) && $urlParameterSettings['till_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['till_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['till_value']) ? $urlParameterSettings['till_value'] : "";
			}
		}
		if ($urlParameterSettings["entity_includeInUrl"]) {
			$paymentRequestUrl .= "&entity=";
			if (isset($urlParameterSettings['entity_kohaAdditionalField']) && $urlParameterSettings['entity_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['entity_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['entity_value']) ? $urlParameterSettings['entity_value'] : "";
			}
		}
		if ($urlParameterSettings["co_includeInUrl"]) {
			$paymentRequestUrl .= "&co=";
			if (isset($urlParameterSettings['co_kohaAdditionalField']) && $urlParameterSettings['co_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['co_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['co_value']) ? $urlParameterSettings['co_value'] : "";
			}
		}
		if ($urlParameterSettings["bu_includeInUrl"]) {
			$paymentRequestUrl .= "&bu=";
			if (isset($urlParameterSettings['bu_kohaAdditionalField']) && $urlParameterSettings['bu_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['bu_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['bu_value']) ? $urlParameterSettings['bu_value'] : "";
			}
		}
		if ($urlParameterSettings["lang_includeInUrl"]) {
			$paymentRequestUrl .= "&lang=";
			if (isset($urlParameterSettings['lang_kohaAdditionalField']) && $urlParameterSettings['lang_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['lang_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['lang_value']) ? $urlParameterSettings['lang_value'] : "";
			}
		}
		if ($urlParameterSettings["mode_includeInUrl"]) {
			$paymentRequestUrl .= "&mode=";
			if (isset($urlParameterSettings['mode_kohaAdditionalField']) && $urlParameterSettings['mode_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['mode_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['mode_value']) ? $urlParameterSettings['mode_value'] : "";
			}
		}

		// hash parameters
		$hashParams = "";
		if ($urlParameterSettings["client_includeInHash"]) {
			$hashParams .= "client=";
			if (isset($urlParameterSettings['client_kohaAdditionalField']) && $urlParameterSettings['client_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['client_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "client=" . $urlParameterSettings['client_value'] ? $urlParameterSettings['client_value'] : "";
			}
		}
		if ($urlParameterSettings["area_includeInHash"]) {
			$hashParams .= "&area=";
			if (isset($urlParameterSettings['area_kohaAdditionalField']) && $urlParameterSettings['area_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['area_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "area=" . $urlParameterSettings['area_value'] ? $urlParameterSettings['area_value'] : "";
			}
		}
		if ($urlParameterSettings["till_includeInHash"]) {
			$hashParams .= "&till=";
			if (isset($urlParameterSettings['till_kohaAdditionalField']) && $urlParameterSettings['till_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['till_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "till=" . $urlParameterSettings['till_value'] ? $urlParameterSettings['till_value'] : "";
			}
		}
		if ($urlParameterSettings["entity_includeInHash"]) {
			$hashParams .= "&entity=";
			if (isset($urlParameterSettings['entity_kohaAdditionalField']) && $urlParameterSettings['entity_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['entity_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "entity=" . $urlParameterSettings['entity_value'] ? $urlParameterSettings['entity_value'] : "";
			}
		}
		if ($urlParameterSettings["co_includeInHash"]) {
			$hashParams .= "&co=";
			if (isset($urlParameterSettings['co_kohaAdditionalField']) && $urlParameterSettings['co_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['co_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "co=" . $urlParameterSettings['co_value'] ? $urlParameterSettings['co_value'] : "";
			}
		}
		if ($urlParameterSettings["bu_includeInHash"]) {
			$hashParams .= "&bu=";
			if (isset($urlParameterSettings['bu_kohaAdditionalField']) && $urlParameterSettings['bu_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['bu_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "bu=" . $urlParameterSettings['bu_value'] ? $urlParameterSettings['bu_value'] : "";
			}
		}
		if ($urlParameterSettings["lang_includeInHash"]) {
			$hashParams .= "&lang=";
			if (isset($urlParameterSettings['lang_kohaAdditionalField']) && $urlParameterSettings['lang_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['lang_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "lang=" . $urlParameterSettings['lang_value'] ? $urlParameterSettings['lang_value'] : "";
			}
		}
		if ($urlParameterSettings["mode_includeInHash"]) {
			$hashParams .= "&mode=";
			if (isset($urlParameterSettings['mode_kohaAdditionalField']) && $urlParameterSettings['mode_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['mode_kohaAdditionalField']));
				$hashParams .= urlencode(isset($locationDetails[$snakeCaseFieldName]) && $locationDetails[$snakeCaseFieldName] ? $locationDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "mode=" . $urlParameterSettings['mode_value'] ? $urlParameterSettings['mode_value'] : "";
			}
		}

		// multiline hash and URL parameters
		foreach ($finesSelected as $index => $fine) {
			$fineDetails = $patron->getCatalogDriver()->hasAdditionalFineFields() ? $patron->getCatalogDriver()->getFineById($fine['id'], true) : [];
			$multilineSuffix = $index > 0 ? "_$index=" : "=";

			// URL parameters
			if ($urlParameterSettings["pmtTyp_includeInUrl"]) {
				$paymentRequestUrl .= "&pmtTyp" . $multilineSuffix;
				if (isset($urlParameterSettings['pmtTyp_kohaAdditionalField']) && $urlParameterSettings['pmtTyp_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['pmtTyp_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['pmtTyp_value']) ? $urlParameterSettings['pmtTyp_value'] : "";
				}
			}
			if ($urlParameterSettings["val1_includeInUrl"]) {
				$paymentRequestUrl .= "&val1" . $multilineSuffix;
				if (isset($urlParameterSettings['val1_kohaAdditionalField']) && $urlParameterSettings['val1_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val1_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['val1_value']) ? $urlParameterSettings['val1_value'] : urlencode($fineDetails['fineId']);
				}
			}
			if ($urlParameterSettings["val1Desc_includeInUrl"]) {
				$paymentRequestUrl .= "&val1Desc" . $multilineSuffix;
				if (isset($urlParameterSettings['val1Desc_kohaAdditionalField']) && $urlParameterSettings['val1Desc_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val1Desc_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['val1Desc_value']) ? $urlParameterSettings['val1Desc_value'] : urlencode($fineDetails['message']);
				}
			}
			if ($urlParameterSettings["val2_includeInUrl"]) {
				$paymentRequestUrl .= "&val2" . $multilineSuffix;
				if (isset($urlParameterSettings['val2_kohaAdditionalField']) && $urlParameterSettings['val2_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val2_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['val2_value']) ? $urlParameterSettings['val2_value'] : "";
				}
			}
			if ($urlParameterSettings["val2Desc_includeInUrl"]) {
				$paymentRequestUrl .= "&val2Desc" . $multilineSuffix;
				if (isset($urlParameterSettings['val2Desc_kohaAdditionalField']) && $urlParameterSettings['val2Desc_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val2Desc_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['val2Desc_value']) ? $urlParameterSettings['val2Desc_value'] : "";
				}
				$paymentRequestUrl .= "&val2Desc" . $multilineSuffix . $urlParameterSettings['val2Desc_value'];
			}
			if ($urlParameterSettings["am_includeInUrl"]) {
				$paymentRequestUrl .= "&am" . $multilineSuffix;
				if (isset($urlParameterSettings['am_kohaAdditionalField']) && $urlParameterSettings['am_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['am_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['am_value']) ? $urlParameterSettings['am_value'] : str_replace(SystemVariables::getSystemVariables()->getCurrencySymbol(), '', $fineDetails['amount']);
				}
			}
			if ($urlParameterSettings["cmt_includeInUrl"]) {
				$paymentRequestUrl .= "&cmt" . $multilineSuffix;
				if (isset($urlParameterSettings['cmt_kohaAdditionalField']) && $urlParameterSettings['cmt_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['cmt_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['cmt_value']) ? $urlParameterSettings['cmt_value'] : "";
				}
			}
			if ($urlParameterSettings["extRef_includeInUrl"]) {
				$paymentRequestUrl .= "&extRef" . $multilineSuffix;
				if (isset($urlParameterSettings['extRef_kohaAdditionalField']) && $urlParameterSettings['extRef_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['extRef_kohaAdditionalField']));
					$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$paymentRequestUrl .= !empty($urlParameterSettings['extRef_value']) ? $urlParameterSettings['extRef_value'] : "";
				}
			}

			// hash parameters
			if ($urlParameterSettings["pmtTyp_includeInHash"]) {
				$hashParams .= "&pmtTyp" . $multilineSuffix;
				if (isset($urlParameterSettings['pmtTyp_kohaAdditionalField']) && $urlParameterSettings['pmtTyp_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['pmtTyp_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['pmtTyp_value']) && $urlParameterSettings['pmtTyp_value'] ? $urlParameterSettings['pmtTyp_value'] : "";
				}
			}
			if ($urlParameterSettings["val1_includeInHash"]) {
				$hashParams .= "&val1" . $multilineSuffix;
				if (isset($urlParameterSettings['val1_kohaAdditionalField']) && $urlParameterSettings['val1_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val1_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['val1_value']) && $urlParameterSettings['val1_value'] ? $urlParameterSettings['val1_value'] : urlencode($fineDetails['fineId']);
				}
			}
			if ($urlParameterSettings["val1Desc_includeInHash"]) {
				$hashParams .= "&val1Desc" . $multilineSuffix;
				if (isset($urlParameterSettings['val1Desc_kohaAdditionalField']) && $urlParameterSettings['val1Desc_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val1Desc_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['val1Desc_value']) && $urlParameterSettings['val1Desc_value'] ? $urlParameterSettings['val1Desc_value'] : urlencode($fineDetails['message']);
				}
			}
			if ($urlParameterSettings["val2_includeInHash"]) {
				$hashParams .= "&val2" . $multilineSuffix;
				if (isset($urlParameterSettings['val2_kohaAdditionalField']) && $urlParameterSettings['val2_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val2_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['val2_value']) && $urlParameterSettings['val2_value'] ? $urlParameterSettings['val2_value'] : "";
				}
			}
			if ($urlParameterSettings["val2Desc_includeInHash"]) {
				$hashParams .= "&val2Desc" . $multilineSuffix;
				if (isset($urlParameterSettings['val2Desc_kohaAdditionalField']) && $urlParameterSettings['val2Desc_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['val2Desc_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['val2Desc_value']) && $urlParameterSettings['val2Desc_value'] ? $urlParameterSettings['val2Desc_value'] : "";
				}
			}
			if ($urlParameterSettings["am_includeInHash"]) {
				$hashParams .= "&am" . $multilineSuffix;
				if (isset($urlParameterSettings['am_kohaAdditionalField']) && $urlParameterSettings['am_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['am_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['am_value']) && $urlParameterSettings['am_value'] ? $urlParameterSettings['am_value'] : str_replace(SystemVariables::getSystemVariables()->getCurrencySymbol(), '', $fineDetails['amount']);
				}
			}
			if ($urlParameterSettings["cmt_includeInHash"]) {
				$hashParams .= "&cmt" . $multilineSuffix;
				if (isset($urlParameterSettings['cmt_kohaAdditionalField']) && $urlParameterSettings['cmt_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['cmt_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['cmt_value']) && $urlParameterSettings['cmt_value'] ? $urlParameterSettings['cmt_value'] : "";
				}
			}
			if ($urlParameterSettings["extRef_includeInHash"]) {
				$hashParams .= "&extRef" . $multilineSuffix;
				if (isset($urlParameterSettings['extRef_kohaAdditionalField']) && $urlParameterSettings['extRef_kohaAdditionalField'] != "none") {
					$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['extRef_kohaAdditionalField']));
					$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
				} else {
					$hashParams .= isset($urlParameterSettings['extRef_value']) && $urlParameterSettings['extRef_value'] ? $urlParameterSettings['extRef_value'] : "";
				}
			}
		}

		// hash parameters
		if ($urlParameterSettings["rurl_includeInHash"]) {
			$hashParams .= "&rurl=";
			if (isset($urlParameterSettings['rurl_kohaAdditionalField']) && $urlParameterSettings['rurl_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['rurl_kohaAdditionalField']));
				$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "rurl=" . $urlParameterSettings['rurl_value'] ? $urlParameterSettings['rurl_value'] : "";
			}
		}
		if ($urlParameterSettings["burl_includeInHash"]) {
			$hashParams .= "&burl=";
			if (isset($urlParameterSettings['burl_kohaAdditionalField']) && $urlParameterSettings['burl_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['burl_kohaAdditionalField']));
				$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "burl=" . $urlParameterSettings['burl_value'] ? $urlParameterSettings['burl_value'] : "";
			}
		}
		if ($urlParameterSettings["email_includeInHash"]) {
			$hashParams .= "&email=";
			if (isset($urlParameterSettings['email_kohaAdditionalField']) && $urlParameterSettings['email_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['email_kohaAdditionalField']));
				$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "email=" . $urlParameterSettings['email_value'] ? $urlParameterSettings['email_value'] : $patron->email;
			}
		}
		if ($urlParameterSettings["ccemail_includeInHash"]) {
			$hashParams .= "&ccemail=";
			if (isset($urlParameterSettings['ccemail_kohaAdditionalField']) && $urlParameterSettings['ccemail_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['ccemail_kohaAdditionalField']));
				$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "ccemail=" . $urlParameterSettings['ccemail_value'] ? $urlParameterSettings['ccemail_value'] : "";
			}
		}
		if ($urlParameterSettings["sid_includeInHash"]) {
			$hashParams .= "&sid=";
			if (isset($urlParameterSettings['sid_kohaAdditionalField']) && $urlParameterSettings['sid_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['sid_kohaAdditionalField']));
				$hashParams .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$hashParams .= "sid=" . $urlParameterSettings['sid_value'] ? $urlParameterSettings['sid_value'] : "";
			}
		}

		// URL parameters
		if ($urlParameterSettings["rurl_includeInUrl"]) {
			$paymentRequestUrl .= "&rurl=";
			if (isset($urlParameterSettings['rurl_kohaAdditionalField']) && $urlParameterSettings['rurl_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['rurl_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['rurl_value']) ? $urlParameterSettings['rurl_value'] . "/AJAX?method=completeHeyCentricOrder%26paymentId=" . $payment->id : $configArray['Site']['url'] . "/MyAccount/AJAX?method=completeHeyCentricOrder%26paymentId=" . $payment->id;
			}
		}
		if ($urlParameterSettings["burl_includeInUrl"]) {
			$paymentRequestUrl .= "&burl=";
			if (isset($urlParameterSettings['burl_kohaAdditionalField']) && $urlParameterSettings['burl_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['burl_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['burl_value']) ? $urlParameterSettings['burl_value'] : "";
			}
		}
		if ($urlParameterSettings["email_includeInUrl"]) {
			$paymentRequestUrl .= "&email=";
			if (isset($urlParameterSettings['email_kohaAdditionalField']) && $urlParameterSettings['email_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['email_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['email_value']) ? $urlParameterSettings['email_value'] : $patron->email;
			}
		}
		if ($urlParameterSettings["ccemail_includeInUrl"]) {
			$paymentRequestUrl .= "&ccemail=";
			if (isset($urlParameterSettings['ccemail_kohaAdditionalField']) && $urlParameterSettings['ccemail_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['ccemail_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['ccemail_value']) ? $urlParameterSettings['ccemail_value'] : "";
			}
		}
		if ($urlParameterSettings["sid_includeInUrl"]) {
			$paymentRequestUrl .= "&sid=";
			if (isset($urlParameterSettings['sid_kohaAdditionalField']) && $urlParameterSettings['sid_kohaAdditionalField'] != "none") {
				$snakeCaseFieldName = str_replace(" ", "_", strtolower($urlParameterSettings['sid_kohaAdditionalField']));
				$paymentRequestUrl .= urlencode(isset($fineDetails[$snakeCaseFieldName]) && $fineDetails[$snakeCaseFieldName] ? $fineDetails[$snakeCaseFieldName] : "none specified");
			} else {
				$paymentRequestUrl .= !empty($urlParameterSettings['sid_value']) ? $urlParameterSettings['sid_value'] : "";
			}
		}

		$paymentRequestUrl .= "&hash=" . base64_encode(md5($hashParams . $heyCentricSettings->privateKey));

		return [
			'success' => true,
			'message' => 'Redirecting to payment processor',
			'paymentRequestUrl' => $paymentRequestUrl,
		];
	}

	/** @noinspection PhpUnused */
	function completeHeyCentricOrder(): void {
		global $configArray;
		$paymentId = $_REQUEST['paymentId'];
		$rc = $_REQUEST['Rc'];
		$pmt = $_REQUEST['Pmt'];
		$recNo = $_REQUEST['RecNo'] ?? "";

		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$payment = new UserPayment();
		$payment->id = $paymentId;

		$updateDebtInIls = false;

		if ($rc == 'A') {
			if ($recNo) {
				$payment->heyCentricPaymentReferenceNumber = $recNo;
			}
			$updateDebtInIls = true;
		}
		if ($rc == 'C') {
			$payment->cancelled = true;
		}
		if ($rc == 'D') {
			$payment->declined = true;
		}

		$payment->update(); // update user payment status in Aspen db

		$params = "Rc=$rc&Pmt=$pmt";
		if ($updateDebtInIls) {
			$params .= "&RecNo=$recNo";
			$payment->find(true);
			$patron = UserAccount::getActiveUserObj();
			$patron->completeFinePayment($payment); // updated debt status in ILS
		}
		header("Location: " . $configArray['Site']['url'] . "/MyAccount/Fines?" . $params);
	}

	/** @noinspection PhpUnused */
	function createPay360Order() : array {
		$transactionType = $_REQUEST['type'];
		if ($transactionType == 'donation') {
			$result = $this->createGenericDonation('Pay360');
		} else {
			$result = $this->createGenericOrder('Pay360');
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return $result;
		}

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

		$homeLocationPay360SettingId = $patron->getHomeLocation()->pay360SettingId;
		$pay360SettingsId = $homeLocationPay360SettingId != -1 ? $homeLocationPay360SettingId : $paymentLibrary->pay360SettingId;

		$selectedFines = [];

		foreach (explode(',', $payment->finesPaid) as $selectedFine) {
			$selectedFines[] = [
				'id' => explode('|', $selectedFine)[0],
				'amount' => explode('|', $selectedFine)[1]
			];
		}

		require_once ROOT_DIR . '/services/Pay360/Client.php';
		$client = new Pay360_Client($pay360SettingsId, $payment->id, $selectedFines, $patron->getCatalogDriver(), true);
		$success = $client->createOrder();


		if (!$success) {
			return $this->failureResult(null, 'Could not connect to Pay360.');
		}

		$result = [
			'success' => true,
			'message' => 'Redirecting to payment processor',
			'paymentRequestUrl' => $client->invokeResponse->invokeResult->redirectUrl,
		];

		if (!$client->isPay360PollingEnabled()) {
			return $result;
		}

		require_once ROOT_DIR . '/services/Pay360/PaymentHandler.php';
		Pay360_PaymentHandler::spawnPoller($pay360SettingsId, $payment->id);

		return $result;
	}

	/** @noinspection PhpUnused */
	function completePay360Order(): void {
		$this->requireLoggedInUser();
		require_once ROOT_DIR . '/services/Pay360/PaymentHandler.php';
		Pay360_PaymentHandler::completeOrder();
	}

	function handlePay360OrderNotAttempted(): void {
		$this->requireLoggedInUser();
		require_once ROOT_DIR . '/services/Pay360/PaymentHandler.php';
		Pay360_PaymentHandler::handleNotAttempted();
	}

	/** @noinspection PhpUnused */
	function dismissBrowseCategory() : array {
		$this->requireLoggedInUser();

		$patronId = UserAccount::getActiveUserId();
		$browseCategoryId = $_REQUEST['browseCategoryId'];

		$result = $this->failureResult(null, 'Unknown Error');

		if ($browseCategoryId != "system_saved_searches" && str_contains($browseCategoryId, "system_saved_searches")) {
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
					$result = $this->successResult('Preferences updated', 'Browse category has been hidden');
				}
			}
		} elseif ($browseCategoryId != "system_user_lists" && str_starts_with($browseCategoryId, "system_user_lists")) {
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
					$result = $this->successResult('Preferences updated', 'Browse category has been hidden');
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

		return $result;
	}

	/** @noinspection PhpUnused */
	function getHiddenBrowseCategories() : array {
		$this->requireLoggedInUser();
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
					if ($hiddenCategory->browseCategoryId != 'system_saved_searches' && str_contains($hiddenCategory->browseCategoryId, "system_saved_searches")) {
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
					} elseif ($hiddenCategory->browseCategoryId != 'system_user_lists' && str_contains($hiddenCategory->browseCategoryId, "system_user_lists")) {
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
							if ($subBrowseCategory->find(true)) {
								$parentCategory = new BrowseCategory();
								$parentCategory->id = $subBrowseCategory->browseCategoryId;
								if ($parentCategory->find(true)) {
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
					'modalButtons' => '<button type="button" class="tool btn btn-primary" onclick="return AspenDiscovery.Account.showBrowseCategory()">' . translate([
							'text' => 'Show These Browse Categories',
							'isPublicFacing' => true,
						]) . '</button>',
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
			return $this->failureResult(null, 'You must be logged in to show hidden browse categories.');
		}
	}

	/** @noinspection PhpUnused */
	function showBrowseCategory() : array {
		$this->requireLoggedInUser();
		$result = $this->failureResult('Show hidden browse categories', 'Sorry your visible browse categories not be updated');

		$patronId = $_REQUEST['patronId'];

		if (isset($_REQUEST['selected']) && is_array($_REQUEST['selected'])) {
			$categoriesToShow = $_REQUEST['selected'];
			foreach ($categoriesToShow as $showThisCategory => $selected) {
				if ($showThisCategory != "system_saved_searches" && str_contains($showThisCategory, "system_saved_searches")) {
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
				} elseif ($showThisCategory != "system_user_lists" && str_contains($showThisCategory, "system_user_lists")) {
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
	function updateAutoRenewal() : array {
		$this->requireLoggedInUser();
		$patronId = $_REQUEST['patronId'];
		$allowAutoRenewal = ($_REQUEST['allowAutoRenewal'] == 'on' || $_REQUEST['allowAutoRenewal'] == 'true');

		$user = UserAccount::getActiveUserObj();
		if ($user->id == $patronId) {
			$result = $user->updateAutoRenewal($allowAutoRenewal);
		} else {
			$result = $this->failureResult(null, 'Invalid user information, please logout and login again.');
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function eventRegistrationModal() : array {
		$eventUrl = $_REQUEST['regLink'];
		$result =  [
			'success' => false,
			'title' => translate([
				'text' => 'Registration Information',
				'isPublicFacing' => true,
			]),
			'buttons' => '<a href="' . $eventUrl . '" class="btn btn-primary" target="_blank" aria-label="' . translate([
					'text' => 'Go to Registration',
					'isPublicFacing' => true,
					'inAttribute' => true
				]) . ' (' . translate([
					'text' => 'opens in a new window',
					'isPublicFacing' => true,
					'inAttribute' => true
				]) . ')"><i class="fas fa-external-link-alt" role="presentation"></i> ' . translate([
					'text' => 'Go to Registration',
					'isPublicFacing' => true,
				]) . '</a>',
		];

		if (!isset($_REQUEST['vendor'])) {
			return $result;
		}

		$vendor = $_REQUEST['vendor'];
		$body = "";

		global $library;
		require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
		$libraryEventSettings = new LibraryEventsSetting();
		$libraryEventSettings->settingSource = $vendor;
		$libraryEventSettings->libraryId = $library->libraryId;
		if (!$libraryEventSettings->find(true)) {
			return $result;
		}
		if ($vendor == 'communico') {
			require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';
			$communicoSettings = new CommunicoSetting();
			$communicoSettings->id = $libraryEventSettings->settingId;
			if ($communicoSettings->find(true)) {
				$body = $communicoSettings->registrationModalBody;
			}
		} else if ($vendor == 'springshare') {
			require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';
			$springshareSettings = new SpringshareLibCalSetting();
			$springshareSettings->id = $libraryEventSettings->settingId;
			if ($springshareSettings->find(true)) {
				$body = $springshareSettings->registrationModalBody;
			}
		} else if ($vendor == 'library_market') {
			require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
			$libraryMarketSettings = new LMLibraryCalendarSetting();
			$libraryMarketSettings->id = $libraryEventSettings->settingId;
			if ($libraryMarketSettings->find(true)) {
				$body = $libraryMarketSettings->registrationModalBody;
			}
		} else if ($vendor == 'assabet') {
			require_once ROOT_DIR . '/sys/Events/AssabetSetting.php';
			$assabetSettings = new AssabetSetting();
			$assabetSettings->id = $libraryEventSettings->settingId;
			if ($assabetSettings->find(true)) {
				$body = $assabetSettings->registrationModalBody;
			}
		} else if ($vendor == 'aspenEvents') {
			require_once ROOT_DIR . '/sys/Events/AspenEventSetting.php';
			$aspenEventSettings = new AspenEventSetting();
			$aspenEventSettings->id = $libraryEventSettings->settingId;
			if (!$aspenEventSettings->find(true)) {
				unset($result['buttons']);
				$result['message'] = translate([
					'text' => 'Aspen Events are not configured for this library.',
					'isPublicFacing' => true,
				]);
				return $result;
			}

			$body = $aspenEventSettings->getRegistrationModalBody() ?? '';

			require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
			$sourceId = AspenEventRecordDriver::sanitizeSourceId($_REQUEST['sourceId'] ?? '');
			if ($sourceId === null) {
				return AspenEventRecordDriver::invalidSourceIdResult();
			}
			$eventInstanceId = AspenEventRecordDriver::extractEventInstanceId($sourceId);

			require_once ROOT_DIR . '/sys/Events/EventInstance.php';
			$eventInstance = new EventInstance();
			$eventInstance->id = $eventInstanceId;
			if (!$eventInstance->find(true)) {
				unset($result['buttons']);
				$result['message'] = translate([
					'text' => 'Event not found.',
					'isPublicFacing' => true,
				]);
				return $result;
			}

			global $interface;
			$numberOfSeats = $eventInstance->getEffectiveNumberOfSeats();
			require_once ROOT_DIR . '/services/EventRegistrationService.php';
			$available = EventRegistrationService::getAvailableSeats($eventInstance);

			require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
			$aspenEventInstanceUserRegistration = new UserAspenEventInstanceRegistration();
			$aspenEventInstanceUserRegistration->eventInstanceId = $eventInstanceId;
			$aspenEventInstanceUserRegistration->userId = UserAccount::getActiveUserId();
			$waitingListInfo = $aspenEventInstanceUserRegistration->getWaitingListInfo();

			$interface->assign('numberOfSeats', $numberOfSeats);
			$interface->assign('availableSeats', $available);
			$interface->assign('isEventFull', !EventRegistrationService::hasAvailableSeats($eventInstance));
			$interface->assign('userCanRegisterFromWaitingList', $waitingListInfo['canRegister']);
			$interface->assign('userOnWaitingList', $waitingListInfo['onWaitingList']);
			$interface->assign('userWaitingListPosition', $waitingListInfo['position']);
			$interface->assign('userIsRegistered', false);

			$user = UserAccount::getLoggedInUser();
			if (empty($user)) {
				$result['success'] = true;
				$result['buttons'] = $interface->fetch('AspenEvents/loginToRegisterButton.tpl');
				$result['body'] = translate([
					'text' => 'You must log in to register to events.',
					'isPublicFacing' => true,
				]);
				return $result;
			}

			$interface->assign('eventSourceId', $sourceId);

			$interface->assign('loggedIn', true);
			$interface->assign('userId', $user->id);
			$interface->assign('userDisplayName', $user->getDisplayName());
			$interface->assign('userEmail', $user->email);
			$interface->assign('userHomeLocation', $user->getHomeLocationName());

			$linkedUsers = [];
			if ($library->allowLinkedAccounts) {
				$linkedUsers = $user->getLinkedUsers();
				foreach ($linkedUsers as $linkedUser) {
					$linkedUser->loadContactInformation();
				}
			}
			$interface->assign('linkedUsers', $linkedUsers);

			$isRegistered = $aspenEventInstanceUserRegistration->status === 'registered';
			$isEventFull = !EventRegistrationService::hasAvailableSeats($eventInstance);
			$canRegister = $waitingListInfo['canRegister'];
			$isWaitingListFull = EventRegistrationService::isWaitingListFull($eventInstance);
			$registrationAction = EventRegistrationService::getRegistrationAction(
				$isRegistered,
				$isEventFull,
				$eventInstance->isWaitingListEnabled(),
				$waitingListInfo['onWaitingList'],
				$canRegister,
				$isWaitingListFull
			);
			if ($registrationAction === 'showPosition' && EventRegistrationService::hasUnregisteredLinkedUsers($eventInstance)) {
				$registrationAction = 'joinWaitingList';
			}
			$interface->assign('userIsRegistered', $isRegistered);
			$interface->assign('registrationAction', $registrationAction);

			// Generate registration form using custom fields
			$eventType = $eventInstance->getEventType();
			$registrationFormStructure = $eventType->getFieldSetFieldsByUse(2);
			$interface->assign('registrationFormStructure', $registrationFormStructure);
			$interface->assign('attendeeCategories', $eventType->getEventTypeAttendeeCategories());

			$body .= $interface->fetch('AspenEvents/registrationModalContents.tpl');
			$result['buttons'] =  $interface->fetch('AspenEvents/registrationToggleButton.tpl');
		}

		$result['success'] = true;
		$result['body'] = $body;

		return $result;
	}

	/** @noinspection PhpUnused */
	function isUserRegisteredForEvent(): array {
		$result = [
			'success' => false,
		];
		$this->requireLoggedInUser(null, 'You must be logged in to check event registration.');
		$eventSourceId = $_REQUEST['eventSourceId'];
		$eventInstanceId = preg_replace("/aspenEvent_\d+_/", '', $eventSourceId);
		$userId = $_REQUEST['userId'];

		if (!$userId || !$eventInstanceId) {
			$result['message'] = translate([
				'text' => 'Event or User information is missing.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if (!UserAccount::isAuthorizedToActOnBehalfOf((int)$userId)) {
			$result['message'] = translate([
				'text' => 'You do not have permission to view registration information for this user.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		require_once ROOT_DIR . '/sys/Events/EventInstance.php';
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		if (!$eventInstance->find(true)) {
			$result['message'] = translate(['text' => 'Event not found.', 'isPublicFacing' => true]);
			return $result;
		}

		$result['success'] = true;
		$result['message'] = translate([
			'text' => 'Registration information found',
			'isPublicFacing' => true,
		]);
		$result['body'] = $eventInstance->getUserEventRegistrationStatus((int)$userId);
		return $result;
	}

	/** @noinspection PhpUnused */
	function saveEvent() : array {
		$this->requireLoggedInUser(null, 'Please login before saving an event.');
		$result = [];
		$regRequired = 0; // set a default

		require_once ROOT_DIR . '/services/MyAccount/MyEvents.php';
		require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
		$sourceId = $_REQUEST['sourceId'];
		$source = $_REQUEST['source'];
		$vendor = $_REQUEST['vendor'];

		$userEventsEntry = new UserEventsEntry();
		$userEventsEntry->userId = UserAccount::getActiveUserId();

		if (empty($sourceId) || empty($source) || empty($vendor)) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Unable to save event, not correctly specified. Must include the source id, source, and event vendor.',
				'isPublicFacing' => true,
			]);
		} else {
			$userEventsEntry->sourceId = $sourceId;
			$externalUrl = null;

			if (str_starts_with($userEventsEntry->sourceId, 'communico')) {
				require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
				$recordDriver = new CommunicoEventRecordDriver($userEventsEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = mb_substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()) {
						$regRequired = 1;
					} else {
						$regRequired = 0;
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif (str_starts_with($userEventsEntry->sourceId, 'libcal')) {
				require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
				$recordDriver = new SpringshareLibCalEventRecordDriver($userEventsEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = mb_substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()) {
						$regRequired = 1;
					} else {
						$regRequired = 0;
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif (str_starts_with($userEventsEntry->sourceId, 'lc_')) {
				require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
				$recordDriver = new LibraryCalendarEventRecordDriver($userEventsEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = mb_substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()) {
						$regRequired = 1;
					} else {
						$regRequired = 0;
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif (str_starts_with($userEventsEntry->sourceId, 'assabet_')) {
				require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
				$recordDriver = new AssabetEventRecordDriver($userEventsEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = mb_substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					if ($recordDriver->isRegistrationRequired()) {
						$regRequired = 1;
					} else {
						$regRequired = 0;
					}
					$userEventsEntry->regRequired = $regRequired;
					$userEventsEntry->location = $recordDriver->getBranch();
					$externalUrl = $recordDriver->getExternalUrl();
				}
			} elseif (str_starts_with($userEventsEntry->sourceId, 'aspenEvent_')) {
				require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
				$recordDriver = new AspenEventRecordDriver($userEventsEntry->sourceId);
				if ($recordDriver->isValid()) {
					$title = $recordDriver->getTitle();
					$userEventsEntry->title = mb_substr($title, 0, 50);
					$eventDate = $recordDriver->getStartDate();
					$userEventsEntry->eventDate = $eventDate->getTimestamp();
					$userEventsEntry->displayEventBranchOnThumbnail = $recordDriver->getDisplayBranchOnThumbnail();
					if ($recordDriver->isRegistrationRequired()) {
						$regRequired = 1;
					} else {
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
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'This event was saved to your events successfully.',
				'isPublicFacing' => true,
			]);
			$result['regRequired'] = false;

			if ($regRequired) {
				$result['message'] = translate([
					'text' => "This event was saved to your events successfully. Saving an event to your events is not the same as registering.",
					'isPublicFacing' => true,
				]);
				$result['buttons'] = "<button class='btn btn-primary' onclick='return AspenDiscovery.Account.regInfoModal(\"this\", \"$source\", \"$sourceId\", \"$vendor\", \"$externalUrl\");'>" . translate([
						'text' => 'Registration Information',
						'isPublicFacing' => true,
					]) . "</button>";
				$result['regRequired'] = true;
			}
		}

		return $result;
	}

	function toggleUserRegistrationToEvent(): array {
		require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
		$sourceId = AspenEventRecordDriver::sanitizeSourceId($_REQUEST['eventSourceId'] ?? '');
		if ($sourceId === null) {
			return AspenEventRecordDriver::invalidSourceIdResult();
		}
		$eventInstanceId = AspenEventRecordDriver::extractEventInstanceId($sourceId);
		$userId = (int)($_REQUEST['userId'] ?? 0);

		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Event or User information is missing.',
				'isPublicFacing' => true
			]),
		];

		if (!$eventInstanceId || !$userId) {
			return $result;
		}

		$this->requireLoggedInUser(null, 'You must be logged in to register for events.');
		$activeUserId = (int)UserAccount::getActiveUserId();

		if (!UserAccount::isAuthorizedToActOnBehalfOf((int)$userId)) {
			$result['message'] = translate([
				'text' => 'You do not have permission to manage registrations for this user.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		require_once ROOT_DIR . '/sys/Account/User.php';
		$user = new User();
		$user->id = $userId;
		if (!$user->find(true)) {
			$result['message']['text'] = 'User not found';
			return $result;
		}

		require_once ROOT_DIR . '/sys/Events/EventInstance.php';
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;
		if (!$eventInstance->find(true)) {
			$result['message'] = translate([
				'text' => 'Event not found.',
				'isPublicFacing' => true
			]);
			return $result;
		}

		$recordDriver = new AspenEventRecordDriver($sourceId);
		if (!$recordDriver->isValid()) {
			$result['message'] = translate([
				'text' => 'Event instance not found.',
				'isPublicFacing' => true
			]);
			return $result;
		}

		require_once ROOT_DIR . '/services/EventRegistrationService.php';

		// unregister the user if registered
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		$registration = new UserAspenEventInstanceRegistration();
		$registration->userId = $userId;
		$registration->eventInstanceId = $eventInstanceId;

		if ($registration->isUserRegisteredForEvent()) {
			$registration->delete();
			EventRegistrationService::inviteNextOnWaitingList($eventInstance);
			
			$result['success'] = true;
			$result['title'] = translate([
				'text' => 'Registration Information',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'Your registration to this event was cancelled successfully.',
				'isPublicFacing' => true
			]);
			return $result;
		}

		// add the event to saved events if it has not yet been saved
		EventRegistrationService::saveToUserEvents($eventInstance, $userId);

		// so the registered may manage their registration, also add the event to the active user's saved events if the user this was added for is a linked user
		if ($userId != $activeUserId) {
			EventRegistrationService::saveToUserEvents($eventInstance, $activeUserId);
		}

		// so the parent linked account display all events their linked user is registered to, save the event if the user registering have had their account linked.
		foreach ($user->getViewerIds() as $viewerId) {
			EventRegistrationService::saveToUserEvents($eventInstance, $viewerId);
		}

		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistrationAttendee.php';
		$attendeeCounts = [];
		if (isset($_REQUEST['attendeeCategory']) && is_array($_REQUEST['attendeeCategory'])) {
			$attendeeCounts = $_REQUEST['attendeeCategory'];
		}

		$validatedCounts = UserAspenEventInstanceRegistrationAttendee::validateAttendeeCounts($eventInstance, $attendeeCounts);
		if ($validatedCounts === false) {
			$result['message'] = translate([
				'text' => 'Invalid attendee counts. One or more categories exceed the allowed maximum.',
				'isPublicFacing' => true
			]);
			return $result;
		}

		$requestedSeats = !empty($validatedCounts) ? array_sum($validatedCounts) : 1;
		$waitingListInfo = $registration->getWaitingListInfo();

		if (!EventRegistrationService::hasAvailableSeats($eventInstance, $requestedSeats) && !$waitingListInfo['canRegister']) {
			$result['message'] = translate([
				'text' => 'This event is full - no seats currently available. We have saved it to your events list so you can keep track of it.',
				'isPublicFacing' => true
			]);
			return $result;
		}

		// register the user
		$registration = new UserAspenEventInstanceRegistration();
		$registration->userId = $userId;
		$registration->eventInstanceId = $eventInstanceId;
		$registration->registerUser();

		if (!empty($validatedCounts)) {
			UserAspenEventInstanceRegistrationAttendee::saveForRegistration((int)$registration->id, $validatedCounts);
		}

		// save the user inputed registration information
		foreach ($_REQUEST as $key => $value) {
		    if (is_numeric($key)) {
				$registration->saveEventFieldValue($key, $value); 
		    }
		}


		$result['success'] = true;
		$result['title'] = translate([
			'text' => 'Registration Information',
			'isPublicFacing' => true,
		]);
		$result['message'] = translate([
			'text' => 'Registration successful.',
			'isPublicFacing' => true
		]);
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteSavedEvent() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to remove events.');
		$id = $_GET['id'];
		$result = ['result' => false];
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

		return $result;
	}

	/** @noinspection PhpUnused */
	function getSaveToListForm() : array {
		$this->requireLoggedInUser();
		global $interface;
		global $library;

		$sourceId = $_REQUEST['sourceId'];
		$source = $_REQUEST['source'];
		$interface->assign('sourceId', $sourceId);
		$interface->assign('source', $source);

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		UserList::getUserListsForSaveForm($source, $sourceId);

		$interface->assign('enableListDescriptions', $library->enableListDescriptions);

		$enableAddToReadingHistory = false;
		if ($source == 'GroupedWork') {
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
				if ($user->isReadingHistoryEnabled()) {
					if ($library->enableAddToReadingHistory) {
						$enableAddToReadingHistory = true;
					}
				}
			}
		}
		$interface->assign('enableAddToReadingHistory', $enableAddToReadingHistory);
		$interface->assign('curYear', date('Y'));
		$interface->assign('curMonth', date('m'));

		if ($enableAddToReadingHistory) {
			$title = translate([
				'text' => 'Add To',
				'isPublicFacing' => true,
			]);
		} else {
			$title = translate([
				'text' => 'Add To List',
				'isPublicFacing' => true,
			]);
		}
		$modalButtons = "<button class='tool btn btn-primary' id='saveToListButton' onclick='AspenDiscovery.Account.saveToList(); return false;'>" . translate([
				'text' => "Save To List",
				'isPublicFacing' => true,
			]) . "</button>";
		if ($enableAddToReadingHistory) {
			$modalButtons .= "<button class='tool btn btn-primary' id='saveToReadingHistoryButton' style='display: none' onclick='AspenDiscovery.Account.saveToReadingHistory(); return false;'>" . translate([
					'text' => "Save To Reading History",
					'isPublicFacing' => true,
				]) . "</button>";
		}

		return [
			'title' => $title,
			'modalBody' => $interface->fetch("MyAccount/saveToList.tpl"),
			'modalButtons' => $modalButtons,
		];
	}

	/** @noinspection PhpUnused */
	function saveToList() : array {
		$this->requireLoggedInUser(null, 'Please login before adding a title to list.');
		$result = [];

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
		$totalRecords = 0;
		if (empty($listId)) {
			$userList->title = translate([
				'text' => "My Favorites",
				'isPublicFacing' => true,
			]);
			$userList->user_id = UserAccount::getActiveUserId();
			$userList->public = 0;
			$userList->description = '';
			$userList->insert();
		} else {
			$userList->id = $listId;
			if (!$userList->find(true)) {
				$result['success'] = false;
				$result['message'] = translate([
					'text' => 'Sorry, we could not find that list in the system.',
					'isPublicFacing' => true,
				]);
				$listOk = false;
			} else {
				//Authorization check: Ensure list belongs to the logged-in user
				$currentUser = UserAccount::getActiveUserObj();
				if (!$currentUser->canEditList($userList)) {
					$result['success'] = false;
					$result['message'] = translate([
						'text' => 'You are not authorized to modify this list.',
						'isPublicFacing' => true,
					]);
					return $result;
				}
				$totalRecords = $userList->numValidListItems();
			}
		}

		if ($listOk) {
			$userListEntry = new UserListEntry();
			$userListEntry->listId = $userList->id;

			//TODO: Validate the entry
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
						$userListEntry->title = mb_substr($groupedWork->full_title, 0, 50);
					}
				} elseif ($userListEntry->source == 'Lists') {
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$list = new UserList();
					$list->id = $userListEntry->sourceId;
					if ($list->find(true)) {
						$userListEntry->title = mb_substr($list->title, 0, 50);
					}
				} elseif ($userListEntry->source == 'Events') {
					if (str_starts_with($userListEntry->sourceId, 'communico')) {
						require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
						$recordDriver = new CommunicoEventRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif (str_starts_with($userListEntry->sourceId, 'libcal')) {
						require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
						$recordDriver = new SpringshareLibCalEventRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif (str_starts_with($userListEntry->sourceId, 'lc_')) {
						require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
						$recordDriver = new LibraryCalendarEventRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif (str_starts_with($userListEntry->sourceId, 'assabet_')) {
						require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
						$recordDriver = new AssabetEventRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					} elseif (str_starts_with($userListEntry->sourceId, 'aspenEvent_')) {
						require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
						$recordDriver = new AspenEventRecordDriver($userListEntry->sourceId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
							$userListEntry->title = mb_substr($title, 0, 50);
						}
					}
				} elseif ($userListEntry->source == 'OpenArchives') {
					require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
					$recordDriver = new OpenArchivesRecordDriver($userListEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userListEntry->title = mb_substr($title, 0, 50);
					}
				} elseif ($userListEntry->source == 'Genealogy') {
					require_once ROOT_DIR . '/sys/Genealogy/Person.php';
					$person = new Person();
					$person->personId = $userListEntry->sourceId;
					if ($person->find(true)) {
						$userListEntry->title = mb_substr($person->firstName . $person->middleName . $person->lastName, 0, 50);
					}
				} elseif ($userListEntry->source == 'EbscoEds') {
					require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
					$recordDriver = new EbscoRecordDriver($userListEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userListEntry->title = mb_substr($title, 0, 50);
					}
				} elseif ($userListEntry->source == 'Summon') {
					require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
					$recordDriver = new SummonRecordDriver($userListEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userListEntry->title = mb_substr($title, 0, 50);
					}
				} elseif ($userListEntry->source == 'CloudSource') {
					require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
					$recordDriver = new CloudSourceRecordDriver($userListEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userListEntry->title = mb_substr($title, 0, 50);
					}
				} elseif ($userListEntry->source == 'Gale') {
					require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
					$recordDriver = new GaleRecordDriver($userListEntry->sourceId);
					if ($recordDriver->isValid()) {
						$title = $recordDriver->getTitle();
						$userListEntry->title = mb_substr($title, 0, 50);
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
				if ($userListEntry->source == 'Events') {
					$result['message'] = translate([
						'text' => 'This event was saved to your list successfully.',
						'isPublicFacing' => true,
					]);
				} else {
					$result['message'] = translate([
						'text' => 'This title was saved to your list successfully.',
						'isPublicFacing' => true,
					]);
				}
			}

		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function saveToReadingHistory(): array {
		$this->requireLoggedInUser(null, 'Please login before adding a title to your reading history.');
		$result = [];

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$sourceId = $_REQUEST['sourceId'];
		$source = $_REQUEST['source'];
		$year = $_REQUEST['year'];
		$month = $_REQUEST['month'];

		$user = UserAccount::getActiveUserObj();

		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $user->id;

		if (empty($sourceId) || empty($source)) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Unable to add that to reading history, not correctly specified.',
				'isPublicFacing' => true,
			]);
		} else {
			if ($source == 'GroupedWork') {
				$readingHistoryEntry->source = $source;
				$readingHistoryEntry->groupedWorkPermanentId = $sourceId;
				$readingHistoryEntry->sourceId = $sourceId;

				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $sourceId;
				if ($groupedWork->find(true)) {
					$groupedWorkDriver = new GroupedWorkDriver($sourceId);
					$readingHistoryEntry->title = mb_substr($groupedWorkDriver->getTitle(), 0, 150);
					$readingHistoryEntry->author = mb_substr($groupedWorkDriver->getPrimaryAuthor(), 0, 75);
					//Leave the format blank
					$readingHistoryEntry->format = '';
					$checkoutDate = mktime(0, 0, 0, $month, 1, $year);
					$readingHistoryEntry->checkOutDate = $checkoutDate;
					$readingHistoryEntry->checkInDate = $checkoutDate;
					$readingHistoryEntry->isIll = 0;
					$readingHistoryEntry->isManuallyAdded = 1;
					//No cost savings updates since this is outside the library
					if ($readingHistoryEntry->find(true)) {
						$existingEntry = true;
					} else {
						$existingEntry = false;
					}
					if ($existingEntry) {
						$readingHistoryEntry->deleted = 0;
						$readingHistoryEntry->update();
					} else {
						$readingHistoryEntry->insert();
					}

					$result['success'] = true;
					$result['message'] = translate([
						'text' => 'This title was saved to your reading history successfully.',
						'isPublicFacing' => true,
					]);
				} else {
					$result['success'] = false;
					$result['message'] = translate([
						'text' => 'Could not find that title in the catalog.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['success'] = false;
				$result['message'] = translate([
					'text' => 'Only catalog titles may be added to your reading history.',
					'isPublicFacing' => true,
				]);
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function reloadCover() : array {
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		if (isset($_REQUEST['id'])) {
			$listId = htmlspecialchars($_GET["id"]);
			$listEntry = new UserListEntry();
			$listEntry->listId = $listId;

			require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
			$bookCoverInfo = new BookCoverInfo();
			$bookCoverInfo->setRecordType('list');
			$bookCoverInfo->setRecordId($listEntry->listId);
			if ($bookCoverInfo->find(true)) {
				$bookCoverInfo->setImageSource('');
				$bookCoverInfo->setThumbnailLoaded(0);
				$bookCoverInfo->setMediumLoaded(0);
				$bookCoverInfo->setLargeLoaded(0);
				$bookCoverInfo->update();
				// Update dateUpdated to refresh cached image
				$listEntry->updateParentListDateUpdated();
			}

			return $this->successResult(null, 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.');
		} else {
			return $this->failureResult(null, 'ID of the cover to reload was not supplied.');
		}

	}

	/** @noinspection PhpUnused */
	function getUploadListCoverForm() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Upload List Covers']);
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
	function uploadListCover() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Upload List Covers']);

		$result = [
			'success' => false,
			'title' => 'Uploading custom list cover',
			'message' => 'Sorry your cover could not be uploaded',
		];

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
					$destPath = $configArray['Site']['coverPath'] . '/original/lists/';
					if (!file_exists($destPath)) {
						mkdir($destPath, 0755, true);
					}
					$destFullPath = $destPath . $id . '.png';
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
		if ($result['success']) {
			$this->reloadCover();
			$result['message'] = 'Your cover has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadListCoverFormByURL() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Upload List Covers']);
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
	function uploadListCoverByURL() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Upload List Covers']);
		$result = [
			'success' => false,
			'title' => 'Uploading custom list cover',
			'message' => 'Sorry your cover could not be uploaded',
		];
		if (isset($_POST['coverFileURL'])) {
			$url = $_POST['coverFileURL'];
			$filename = basename($url);
			$uploadedFile = file_get_contents($url);

			if (!$uploadedFile) {
				$result['message'] = "No Cover file was uploaded";
			}else{
				$id = htmlspecialchars($_GET["id"]);
				global $configArray;
				$destPath = $configArray['Site']['coverPath'] . '/original/lists/';
				if (!file_exists($destPath)) {
					mkdir($destPath, 0755, true);
				}
				$destFullPath = $destPath . $id . '.png';
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				if ($ext == "jpg" or $ext == "png" or $ext == "gif" or $ext == "jpeg") {
					$upload = file_put_contents($destFullPath, file_get_contents($url));
					if ($upload) {
						$result['success'] = true;
					} else {
						$result['message'] = 'Could not save image';
					}
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
	function removeUploadedListCover(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Upload List Covers']);
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Removing custom list cover',
				'isAdminFacing' => true
			]),
			'message' => translate([
				'text' => 'Sorry your cover could not be removed',
				'isAdminFacing' => true
			]),
		];

		$id = $_REQUEST['listId'] ?? null;
		if (empty($id) || !is_numeric($id)) {
			$result = [
				'success' => false,
				'title' => translate(['text'=>'Error','isAdminFacing'=>true]),
				'message' => translate(['text'=>'Invalid List Id provided','isAdminFacing'=>true]),
			];
		}else{
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$userList = new UserList();
			$userList->id = $id;
			if ($userList->find(true)) {
				$activeUser = UserAccount::getActiveUserObj();
				if ($activeUser->canEditList($userList)){
					global $configArray;
					$customCoverPath =  $configArray['Site']['coverPath'] . '/original/lists/' . $id . '.png';
					if (file_exists($customCoverPath)){
						$fileRemoved = unlink($customCoverPath);
					}else{
						//No file existed, treat this as working
						$fileRemoved = true;
					}
					if ($fileRemoved) {
						$result = [
							'success' => true,
							'title' => translate(['text'=>'Removing Custom Cover','isAdminFacing'=>true]),
							'message' => translate(['text'=>'The cover was removed successfully','isAdminFacing'=>true]),
						];
					}else{
						$result = [
							'success' => false,
							'title' => translate(['text'=>'Error','isAdminFacing'=>true]),
							'message' => translate(['text'=>'You do not have permissions to edit this list','isAdminFacing'=>true]),
						];
					}
				}else{
					$result = [
						'success' => false,
						'title' => translate(['text'=>'Error','isAdminFacing'=>true]),
						'message' => translate(['text'=>'You do not have permissions to edit this list','isAdminFacing'=>true]),
					];
				}
			}else{
				$result = [
					'success' => false,
					'title' => translate(['text'=>'Error','isAdminFacing'=>true]),
					'message' => translate(['text'=>'Incorrect List Id provided','isAdminFacing'=>true]),
				];
			}
		}

		if ($result['success']) {
			$_GET['id'] = $_REQUEST['listId'];
			$this->reloadCover();
			$result['message'] = translate([
				'text' => 'The cover has been removed',
				'isAdminFacing' => true
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteListItems(): array {
		$this->requireLoggedInUser();
		$result = [
			'success' => false,
		];

		$listId = htmlspecialchars($_GET["id"]);
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
		$list = new UserList();
		$list->id = $listId;
		$userCanEdit = false;
		if ($list->find(true)) {
			//Perform an action on the list, but verify that the user has permission to do so.
			$userObj = UserAccount::getActiveUserObj();
			if ($userObj !== false) {
				$userCanEdit = $userObj->canEditList($list);
			}
		} else {
			$result['message'] = "Sorry, that list wasn't found.";
			return $result;
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
		} else {
			$result['message'] = "Sorry, you don't have permissions to edit this list.";
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteList(): array {
		$this->requireLoggedInUser();

		$result = $this->failureResult(null, 'The selected lists could not be deleted. Please try again or contact library staff.');

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		$hardDelete = isset($_REQUEST['optOutSoftDeletion']) && $_REQUEST['optOutSoftDeletion'] == 'true';

		if (isset($_REQUEST['selected'])) {
			$itemsToRemove = $_REQUEST['selected'];
			foreach ($itemsToRemove as $listId => $selected) {
				$list = new UserList();
				$list->id = $listId;
				if ($list->find(true)) {
					// Perform an action on the list, but verify that the user has permission to do so.
					$userCanEdit = false;
					$userObj = UserAccount::getActiveUserObj();
					if ($userObj) {
						$userCanEdit = $userObj->canEditList($list);
					}
					if ($userCanEdit) {
						$list->delete(false, $hardDelete);
						$result['success'] = true;
						$result['message'] = $hardDelete ? 'The selected lists have been permanently deleted.' : 'The selected lists have been soft deleted.';
					} else {
						$result['message'] = 'You do not have permissions to delete that list.';
						$result['success'] = false;
					}
				} else {
					$result['success'] = false;
					$result['message'] = 'The list to delete could not be found. Please try again or contact library staff.';
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getDeleteListForm(): array {
		$this->requireLoggedInUser();

		$userObj = UserAccount::getActiveUserObj();
		$hideUI = false;

		$patronHomeLibrary = $userObj->getHomeLibrary();
		if ($patronHomeLibrary) {
			$hideUI = !empty($patronHomeLibrary->hideSoftDeleteListUI);
		}

		if ($hideUI) {
			$modalBody = translate([
				'text' => 'Are you sure you want to delete this entire list?',
				'isPublicFacing' => true
			]);
		} else {
			$modalBody = translate([
					'text' => 'Are you sure you want to delete this entire list? The list and all titles within it will be soft-deleted and can be restored by library staff within 30 days.',
					'isPublicFacing' => true
				]) . '<br/><br/>' . '<div>' . '<input type="checkbox" id="optOutSoftDeletion" style="margin-right: 5px;">' . '<label class="form-check-label" for="optOutSoftDeletion">' . translate([
					'text' => 'Opt Out of Soft Deletion',
					'isPublicFacing' => true
				]) . '</label>' . '</div>';
		}

		$modalButtons = '<button id="confirmDeleteList" class="tool btn btn-danger" onclick="AspenDiscovery.Lists.doDeleteList()"><span class="fas fa-spinner fa-spin" style="display:none; margin-right: 4px;"></span>' . translate([
				'text' => 'Yes',
				'isPublicFacing' => true
			]) . '</button>';
		$modalButtons .= '<button id="cancelDeleteList" class="tool btn btn-default" onclick="AspenDiscovery.closeLightbox()">' . translate([
				'text' => 'No',
				'isPublicFacing' => true
			]) . '</button>';

		return [
			'title' => translate([
				'text' => 'Delete List?',
				'isPublicFacing' => true
			]),
			'modalBody' => $modalBody,
			'modalButtons' => $modalButtons
		];
	}

	/** @noinspection PhpUnused */
	function getDeleteSelectedListsForm(): array {
		$this->requireLoggedInUser();

		$userObj = UserAccount::getActiveUserObj();
		$hideUI = false;
		$patronHomeLibrary = $userObj->getHomeLibrary();
		if ($patronHomeLibrary) {
			$hideUI = !empty($patronHomeLibrary->hideSoftDeleteListUI);
		}

		if ($hideUI) {
			$modalBody = translate([
				'text' => 'Are you sure you want to delete the selected lists?',
				'isPublicFacing' => true
			]);
		} else {
			$modalBody = translate([
					'text' => 'Are you sure you want to delete the selected lists? The lists and all titles within them will be soft-deleted and can be restored by library staff within 30 days.',
					'isPublicFacing' => true
				]) . '<br/><br/>' . '<div>' . '<input type="checkbox" id="optOutSoftDeletionBulk" style="margin-right: 5px;">' . '<label class="form-check-label" for="optOutSoftDeletionBulk">' . translate([
					'text' => 'Opt Out of Soft Deletion',
					'isPublicFacing' => true
				]) . '</label>' . '</div>';
		}

		$modalButtons = '<button id="confirmDeleteSelectedLists" class="tool btn btn-danger" onclick="AspenDiscovery.Account.doDeleteSelectedLists()"><span class="fas fa-spinner fa-spin" style="display:none; margin-right: 4px;"></span>' . translate([
				'text' => 'Yes',
				'isPublicFacing' => true
			]) . '</button>';
		$modalButtons .= '<button id="cancelDeleteSelectedLists" class="tool btn btn-default" onclick="AspenDiscovery.closeLightbox()">' . translate([
				'text' => 'No',
				'isPublicFacing' => true
			]) . '</button>';

		return [
			'title' => translate([
				'text' => 'Delete Selected Lists?',
				'isPublicFacing' => true
			]),
			'modalBody' => $modalBody,
			'modalButtons' => $modalButtons
		];
	}

	/** @noinspection PhpUnused */
	function getEditListForm() : array {
		$this->requireLoggedInUser();

		global $interface;

		if (isset($_REQUEST['listId']) && isset($_REQUEST['listEntryId'])) {
			$listId = $_REQUEST['listId'];
			$listEntry = $_REQUEST['listEntryId'];
			$listHasFiltersApplied = $_REQUEST['listHasFiltersApplied'] ?? 0;

			$interface->assign('listId', $listId);
			$interface->assign('listEntry', $listEntry);
			$interface->assign('listHasFiltersApplied', $listHasFiltersApplied);

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
						$userCanEdit = $userObj->canEditList($userList);
						if ($userCanEdit) {
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
		$this->requireLoggedInUser();

		$result = $this->failureResult('Updating list entry', 'Sorry your list entry could not be updated');
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
	function updateWeight() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to move a list entry');

		$result = $this->failureResult(null, 'Unknown error moving list entry');
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getSuggestionsSpotlight() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to view suggestions.  Please close this dialog and login again.');
		$result = [];

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

		return $result;
	}

	/** @noinspection PhpUnused */
	function get2FAEnrollment() : array {
		global $interface;

		$step = $_REQUEST['step'] ?? "register";
		$mandatoryEnrollment = $_REQUEST['mandatoryEnrollment'] ?? 'false';
		$method = $_REQUEST['useMethod'] ?? '';

		if ($step == "register") {
			function mask($str, $first, $last) : string {
				$len = strlen($str);
				$toShow = $first + $last;
				return substr($str, 0, $len <= $toShow ? 0 : $first) . str_repeat("*", $len - ($len <= $toShow ? 0 : $toShow)) . substr($str, $len - $last, $len <= $toShow ? 0 : $last);
			}

			function mask_email($email) : string {
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
			$hasValidEmail = false;
			if ($user->find(true)) {
				if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
					$email = mask_email($user->email);
					$hasValidEmail = true;
				}
			}else{
				//No valid user
				$this->failureResult(null, 'Unable to retrieve user information');
			}
			$interface->assign('hasValidEmail', $hasValidEmail);
			$interface->assign('emailAddress', $email);

			global $library;

			$twoFactorAuthSetting = $user->getTwoFactorAuthenticationSetting();
			if ($method == 'undefined' || $method == '') {
				$method = $twoFactorAuthSetting->allowTotp ? 'totp' : 'email';
			}

			$secret = null;
			if ($method == 'totp') {
				require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';
				// Generate QR code

				$secret = TwoFactorAuthTOTPSecret::getOrCreateSecret(true);
				$issuer = !empty($twoFactorAuthSetting->issuerTOTP) ? $twoFactorAuthSetting->issuerTOTP : $library->displayName . ' Catalog';

				// the issuer needs to be the 2FA Setting value, not raw
				$qrCodeUri = TwoFactorAuthTOTPSecret::generateQRCodeURI($secret, $issuer, $user);

				$interface->assign('secretId', $secret->id);
				$interface->assign('secret', $secret->secretKey);
				$interface->assign('qrCodeUri', $qrCodeUri);
			}

			if ($hasValidEmail && $method == 'email') {
				$buttons = "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.show2FAEnrollmentVerify(\"$mandatoryEnrollment\", \"email\", null); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>";
			} elseif ($method == 'totp') {
				$buttons = "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.show2FAEnrollmentVerify(\"$mandatoryEnrollment\", \"totp\", \"$secret->id\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>";
			} else {
				$buttons = "";
			}
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $method == 'email' ? $interface->fetch('MyAccount/2fa/enroll-register-email.tpl') : $interface->fetch('MyAccount/2fa/enroll-register-totp.tpl'),
				'buttons' => $buttons,
				'closeDestination' => '/MyAccount/Logout'
			];
		} elseif ($step == "verify") {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			if ($method == 'email') {
				$twoFactorAuth = new TwoFactorAuthCode();
				$twoFactorAuth->createCode();
			}

			$invalid = $_REQUEST['invalid'] ?? false;
			$secretId = $_REQUEST['secretId'] ?? null;

			$alert = null;
			if ($invalid) {
				$alert = 'The code entered is invalid.';
			}
			$interface->assign('alert', $alert);
			$interface->assign('twoFactorMethod', $method);

			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-verify.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.verify2FA(\"$mandatoryEnrollment\", \"$method\", \"$secretId\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} elseif ($step == "validate") {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			$twoFactorAuth = new TwoFactorAuthCode();
			$twoFactorAuth->createCode();

			$secretId = $_REQUEST['secretId'] ?? null;

			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-verify.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.verify2FA(\"$mandatoryEnrollment\", \"$method\", \"$secretId\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
				'closeDestination' => '/MyAccount/Logout'
			];
		} elseif ($step == "backup") {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			$twoFactorAuth = new TwoFactorAuthCode();
			$twoFactorAuth->createNewBackups();

			$backupCode = new TwoFactorAuthCode();
			$backupCodes = $backupCode->getBackups();
			$interface->assign('backupCodes', $backupCodes);

			$secretId = $_REQUEST['secretId'] ?? null;

			return [
				'success' => true,
				'title' => translate([
					'text' => 'Two-Factor Authentication',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('MyAccount/2fa/enroll-backup.tpl'),
				'buttons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.show2FAEnrollmentSuccess(\"$mandatoryEnrollment\", \"$method\", \"$secretId\"); return false;'>" . translate([
						'text' => 'Next',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} elseif ($step == "complete") {
			// update user table to enrolled status
			$user = new User();
			$user->id = UserAccount::getActiveUserId();
			if ($user->find(true)) {
				$existingMethods = array_values(array_filter(array_map('trim', explode(',', $user->twoFactorMethod ?? '')), static function ($m) {
					return $m !== '';
				}));
				if (!in_array($method, $existingMethods, true)) {
					$existingMethods[] = $method;
					$user->twoFactorMethod = implode(',', $existingMethods);
				} else {
					$user->twoFactorMethod = implode(',', $existingMethods);
				}
				$user->twoFactorStatus = 1;
				$user->update();
			}

			if ($method == 'totp' && isset($_REQUEST['secretId'])) {
				require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';
				$unverifiedSecret = new TwoFactorAuthTOTPSecret();
				$unverifiedSecret->id = $_REQUEST['secretId'];
				$unverifiedSecret->verified = 1;
				$unverifiedSecret->update();
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
			return $this->failureResult(null, 'Invalid 2FA step');
		}
	}

	/** @noinspection PhpUnused */
	function verify2FA() : array {
		$code = $_REQUEST['code'] ?? '0';
		$isLoggingIn = $_REQUEST['loggingIn'] ?? false;
		$secretId = $_REQUEST['secretId'] ?? null;
		$authMethod = $_REQUEST['useMethod'] ?? $_SESSION['useMethod'] ?? null;
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';
		$twoFactorAuth = new TwoFactorAuthCode();

		if ($secretId !== null) {
			// TOTP enrollment verification
			return $twoFactorAuth->validateCode($code, $authMethod, $secretId);
		}

		if ($isLoggingIn) {
			global $logger;
			$logger->log("Starting AJAX/2faLogin session: " . session_id(), Logger::LOG_DEBUG);
			$result = $twoFactorAuth->validateCode($code, $authMethod);
			if ($result['success']) {
				UserAccount::$isAuthenticated = true;
				if ($authMethod === 'totp') {
					require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
					$authCodeForSession = new TwoFactorAuthCode();
					$authCodeForSession->sessionId = session_id();
					$authCodeForSession->userId = $_SESSION['activeUserId'] ?? UserAccount::getActiveUserId();
					if ($authCodeForSession->find(true)) {
						$authCodeForSession->status = 'used';
						$authCodeForSession->update();
					} else {
						$authCodeForSession->code = 'totp'; // we don't actually use TOTP verification, its just to make needsToComplete2FA() happy
						$authCodeForSession->dateSent = time() + 300;
						$authCodeForSession->status = 'used';
						$authCodeForSession->insert();
					}
				}
				try {
					$_REQUEST['authMethod'] = $authMethod;
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
			$result = $twoFactorAuth->validateCode($code, $authMethod);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function confirmCancel2FA() : array {
		$this->requireLoggedInUser();
		global $interface;

		$user = UserAccount::getLoggedInUser();
		$activeMethods = explode(',', $user->twoFactorMethod);
		$methodToCancel = $_REQUEST['type'];
		$willHave2FAActiveAfterRemoval = count($activeMethods) > 1;
		$interface->assign('willHave2FAActiveAfterRemoval', $willHave2FAActiveAfterRemoval);
		$interface->assign('methodToCancel', $methodToCancel);

		// on submit of button, update user table for (un)enrollment status
		return [
			'success' => true,
			'title' => translate([
				'text' => 'Disable Two-Factor Authentication',
				'isPublicFacing' => true,
			]),
			'body' => $interface->fetch('MyAccount/2fa/unenroll.tpl'),
			'buttons' => "<button class='tool btn btn-primary' onclick='return AspenDiscovery.Account.cancel2FA(\"$methodToCancel\");'>Yes, turn off</button>",
		];
	}

	/** @noinspection PhpUnused */
	function cancel2FA() : array {
		$this->requireLoggedInUser();

		$methodToCancel = $_REQUEST['type'];
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		$twoFactorAuth->deactivate2FA($methodToCancel);

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
	function newBackupCodes() : array {
		$this->requireLoggedInUser();

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
	function new2FACode(): array {
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
	function exportUserListCSV(): array {
		$result = [
			'success' => false,
			'title' => "Export to CSV Failed",
			'message' => translate([
				'text' => 'An error has occurred exporting this list to CSV.',
				'isPublicFacing' => true,
			]),
		];

		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) {
			$userListId = $_REQUEST['listId'];
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $userListId;
			if ($list->find(true)) {
				if ($list->public || (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $list->user_id)) {
					// Get user's saved filters if logged in.
					$selectedResourceTypes = empty($_REQUEST['selectedResourceTypes']) ? [] : explode('|', $_REQUEST['selectedResourceTypes']);
					$activeFilters = empty($_REQUEST['activeFilters']) ? [] : explode('|', $_REQUEST['activeFilters']);

					$list->buildCSV($selectedResourceTypes, $activeFilters);
					// If buildCSV succeeds, it exits.
				} else {
					$result['message'] = translate([
						'text' => 'You do not have access to this list to export to CSV.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = translate([
					'text' => 'The list you wish to export to CSV could not be found.',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'No list ID or an invalid list ID has been provided.',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function exportUserListRIS(): array {
		$result = [
			'success' => false,
			'title' => "Export to RIS Failed",
			'message' => translate([
				'text' => 'An error has occurred exporting this list to RIS.',
				'isPublicFacing' => true,
			]),
		];

		if (isset($_REQUEST['listId']) && ctype_digit($_REQUEST['listId'])) {
			$userListId = $_REQUEST['listId'];
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $userListId;
			if ($list->find(true)) {
				if ($list->public || (UserAccount::isLoggedIn() && UserAccount::getActiveUserId() == $list->user_id)) {
					// Get user's saved filters if logged in.
					$selectedResourceTypes = empty($_REQUEST['selectedResourceTypes']) ? [] : explode('|', $_REQUEST['selectedResourceTypes']);
					$activeFilters = empty($_REQUEST['activeFilters']) ? [] : explode('|', $_REQUEST['activeFilters']);
					$list->buildRIS($selectedResourceTypes, $activeFilters);
					// If buildRIS succeeds, it exits.
				} else {
					$result['message'] = translate([
						'text' => 'You do not have access to this list to export to RIS.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = translate([
					'text' => 'The list you wish to export to RIS could not be found.',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'No list ID or an invalid list ID has been provided.',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getILSMessage() : array {
		$this->requireLoggedInUser();
		global $interface;
		$result = $this->failureResult(null, 'Something went wrong.');

		if (isset($_REQUEST['messageId']) && ctype_digit($_REQUEST['messageId'])) {
			$userMessageId = $_REQUEST['messageId'];
			require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';
			$ilsMessage = new UserILSMessage();
			$ilsMessage->id = $userMessageId;
			$ilsMessage->userId = UserAccount::getActiveUserId();
			if ($ilsMessage->find(true)) {
				$interface->assign('userMessage', $ilsMessage);
				return [
					'title' => $ilsMessage->title ?? '',
					'modalBody' => $interface->fetch('MyAccount/message.tpl'),
				];
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function markILSMessageAsRead() : array {
		$this->requireLoggedInUser();

		$result = $this->failureResult(null, 'Something went wrong.');

		if (isset($_REQUEST['messageId']) && ctype_digit($_REQUEST['messageId'])) {
			$userMessageId = $_REQUEST['messageId'];
			require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';
			$ilsMessage = new UserILSMessage();
			$ilsMessage->id = $userMessageId;
			$ilsMessage->userId = UserAccount::getActiveUserId();
			if ($ilsMessage->find(true)) {
				$ilsMessage->isRead = 1;
				if ($ilsMessage->update()) {
					$result = [
						'success' => true,
						'message' => translate([
							'text' => 'Message marked as read',
							'isPublicFacing' => true,
						]),
					];
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function markILSMessageAsUnread() : array {
		$this->requireLoggedInUser();

		$result = $this->failureResult(null, 'Something went wrong.');

		if (isset($_REQUEST['messageId']) && ctype_digit($_REQUEST['messageId'])) {
			$userMessageId = $_REQUEST['messageId'];
			require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';
			$ilsMessage = new UserILSMessage();
			$ilsMessage->id = $userMessageId;
			$ilsMessage->userId = UserAccount::getActiveUserId();
			if ($ilsMessage->find(true)) {
				$ilsMessage->isRead = 0;
				if ($ilsMessage->update()) {
					$result = [
						'success' => true,
						'message' => translate([
							'text' => 'Message marked as unread',
							'isPublicFacing' => true,
						]),
					];
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function enrollCampaign() : array {
		$this->checkRequiredModule('Community Engagement');
		$this->checkRequiredParameters(['campaignId', 'userId']);
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/Account/User.php';

		$campaignId = $_GET['campaignId'] ?? null;
		$userId = $_GET['userId'] ?? null;

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;
		$campaign = new Campaign();
		$campaign->id = $campaignId;
		if (!$campaign->find(true)) {
			return $this->failureResult('Error', 'Campaign not found.');
		}

		$today = new DateTime();
		/** @noinspection PhpUnhandledExceptionInspection */
		$enrollmentStartDate = !empty($campaign->enrollmentStartDate) ? new DateTime($campaign->enrollmentStartDate) : null;
		/** @noinspection PhpUnhandledExceptionInspection */
		$enrollmentEndDate = !empty($campaign->enrollmentEndDate) ? new DateTime($campaign->enrollmentEndDate) : null;

		if ($enrollmentStartDate && $enrollmentEndDate) {
			if ($today < $enrollmentStartDate) {
				return $this->failureResult('Cannot Enroll', 'Enrollment for this campaign has not started yet.');
			}
			if ($today > $enrollmentEndDate) {
				return $this->failureResult('Cannot Enroll', 'Enrollment for this campaign has ended.');
			}
		}

		if ($userCampaign->find(true)) {
			return $this->failureResult('Already Enrolled', 'User is already enrolled in this campaign.');
		}

		if ($userCampaign->insert()) {

			$this->applyCampaignProgress($userId, $campaignId);

			$campaign->enrollmentCounter++;
			$campaign->currentEnrollments++;
			$campaign->update();

			return [
				'success' => true,
				'campaignId' => $campaignId,
				'userId' => $userId,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'You have enrolled in the campaign successfully.',
					'isPublicFacing' => true
				])
			];
		} else {
			return $this->failureResult('Error', 'Failed to enroll user in campaign.');
		}
	}

	public function unenrollCampaign() : array {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCreditActivityUsersProgress.php';

		$this->checkRequiredModule('Community Engagement');
		$this->checkRequiredParameters(['campaignId', 'userId']);
		$campaignId = $_GET['campaignId'] ?? null;
		$userId = $_GET['userId'] ?? null;

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		//Find user campaign entry and delete
		if ($userCampaign->find(true)) {
			$campaign = new Campaign();
			$campaign->id = $campaignId;
			if ($campaign->find(true)) {
				if ($userCampaign->delete()) {
					$progressEntry = new CampaignMilestoneProgressEntry();
					$progressEntry->userId = $userId;
					$progressEntry->ce_campaign_id = $campaignId;
					$progressEntry->delete(true);

					$milestoneProgress = new CampaignMilestoneUsersProgress();
					$milestoneProgress->userId = $userId;
					$milestoneProgress->ce_campaign_id = $campaignId;
					$milestoneProgress->delete(true);

					$extraCreditProgress = new CampaignExtraCreditActivityUsersProgress();
					$extraCreditProgress->userId = $userId;
					$extraCreditProgress->campaignId = $campaignId;
					$extraCreditProgress->delete(true);
					//Increase unenrollment counter
					$campaign->unenrollmentCounter++;
					$campaign->currentEnrollments--;
					$campaign->update();

					return $this->successResult('Success', 'You have successfully unenrolled.');
				} else {
					return $this->failureResult('Error', 'Failed to unenroll.');
				}
			} else {
				return $this->failureResult('Error', 'Campaign not found.');
			}
		} else {
			return $this->failureResult('User Not Enrolled', 'User is not enrolled in this campaign.');
		}
	}

	/** @noinspection PhpUnused */
	public function getEnrolledCampaigns() : array {
		$this->checkRequiredModule('Community Engagement');
		$this->requireLoggedInUser();

		require_once ROOT_DIR . '/sys/UserAccount.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';

		$userId = UserAccount::getActiveUserId();
		$enrolledCampaigns = Campaign::getUserEnrolledCampaigns($userId);
		return [
			'success' => true,
			'numCampaigns' => count($enrolledCampaigns)
		];
	}

	public function applyCampaignProgress($userId, $campaignId) : void {
		$this->checkRequiredModule('Community Engagement');

		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
		$campaign = new Campaign();
		$campaign->id = $campaignId;
		if (!$campaign->find(true)) {
			return;
		}

		$campaignStartDate = strtotime($campaign->startDate);
		$campaignEndDate = strtotime($campaign->endDate);

		$entities = $this->getUserEntities($userId);

		foreach ($entities as $entity) {
			$entityDate = $entity->date;
			$entityId = $entity->groupedWorkId;

			if ($entityDate >= $campaignStartDate && $entityDate <= $campaignEndDate) {
				CampaignMilestone::processCampaignMilestoneProgress($entity, $entity->__table, $userId, $entityDate, $entityId);
			}
		}
	}

	private function getUserEntities($userId) : array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$entities = [];

		$hold = new Hold();
		$hold->userId = $userId;
		if ($hold->find()) {
			while ($hold->fetch()) {
				$hold->type = 'user_hold';
				/** @noinspection PhpUndefinedFieldInspection */
				$hold->date = $hold->createDate;
				$entities[] = clone $hold;
			}
		}

		$checkout = new Checkout();
		$checkout->userId = $userId;
		if ($checkout->find()) {
			while ($checkout->fetch()) {
				$checkout->type = 'user_checkout';
				/** @noinspection PhpUndefinedFieldInspection */
				$checkout->date = $checkout->checkoutDate;
				$entities[] = clone $checkout;
			}
		}

		$review = new UserWorkReview();
		$review->userId = $userId;
		if ($review->find()) {

			while ($review->fetch()) {

				/** @noinspection PhpUndefinedFieldInspection */
				$review->type = 'user_work_review';
				/** @noinspection PhpUndefinedFieldInspection */
				$review->date = $review->dateRated;
				/** @noinspection PhpUndefinedFieldInspection */
				$review->groupedWorkId = $review->groupedRecordPermanentId;
				$entities[] = clone $review;
			}
		}
		return $entities;
	}

	private function processCampaignMilestones($entity, $campaignId, $entityId) : void {
		$this->checkRequiredModule('Community Engagement');

		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/action-hooks.php';

		$campaignMilestone = new CampaignMilestone();
		$campaignMilestone->campaignId = $campaignId;

		if ($campaignMilestone->find()) {
			while ($campaignMilestone->fetch()) {
				$milestone = new Milestone();
				$milestone->id = $campaignMilestone->milestoneId;

				if (!$milestone->find(true)) {
					continue;
				}

				if ($milestone->milestoneType !== $entity->type) {
					continue;
				}

				if (_campaignMilestoneProgressEntryObjectAlreadyExists($entity, $campaignMilestone)) {
					continue;
				}

				$campaignMilestone->addCampaignMilestoneProgressEntry($entity, $entity->userId, $entityId);
			}
		}
	}

	/**
	 * Returns polling results for toast notifications about community engagement progress.
	 * @noinspection PhpUnused
	 */

	public function CommunityEngagementPoll() {
		$this->checkRequiredModule('Community Engagement');
		require_once ROOT_DIR . '/sys/CommunityEngagement/CommunityEngagementPoll.php';
		$debug = false; // Set to true to enable debug mode. true for dev only.
		$CEPoll = new CommunityEngagementPoll($debug);
		$CEPoll->CommunityEngagementPoll();
	}

	/** @noinspection PhpUnused */
	function getYearInReviewSlide(): array {
		$this->requireLoggedInUser(null, "You must be logged in.  Please close this dialog and login to view your Year in Review.");
		$result = $this->failureResult('Error', 'Unknown error loading year in review slide.');

		$patron = UserAccount::getActiveUserObj();

		require_once ROOT_DIR . '/sys/YearInReview/YearInReviewGenerator.php';
		generateYearInReview($patron);

		if ($patron->hasYearInReview()) {
			$slideNumber = $_REQUEST['slide'] ?? 1;
			if (is_numeric($slideNumber)) {
				$yearInReviewSettings = $patron->getYearInReviewSetting();
				$result = $yearInReviewSettings->getSlide($patron, (int)$slideNumber);
			} else {
				$result['message'] = translate([
					'text' => "Invalid Slide Number.",
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => "Year in Review is not active for your account.",
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	#[NoReturn]
	function getYearInReviewSlideImage() : void {
		$gotImage = false;
		//This returns an image to the browser
		if (UserAccount::isLoggedIn()) {
			$patron = UserAccount::getActiveUserObj();

			//TODO: Take this out, the data should already be generated at this point
			require_once ROOT_DIR . '/sys/YearInReview/YearInReviewGenerator.php';
			generateYearInReview($patron);

			if ($patron->hasYearInReview()) {
				$slideNumber = $_REQUEST['slide'] ?? 1;
				if (is_numeric($slideNumber)) {
					$yearInReviewSettings = $patron->getYearInReviewSetting();
					$gotImage = $yearInReviewSettings->getSlideImage($patron, (int)$slideNumber);
				}
			}
		}
		if (!$gotImage) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			$module = 'Error';
			$action = 'Handle404';
			require_once ROOT_DIR . "/services/Error/Handle404.php";
		}
		//Since this returns an image, don't return
		die();
	}

	/** @noinspection PhpUnused */
	function getSublocationsSelect(): array {
		$this->requireLoggedInUser();
		$html = '';
		$success = false;
		$context = $_REQUEST['context'] ?? '';

		$patron = UserAccount::getActiveUserObj();
		if (isset($_REQUEST['locationCode'])) {
			$location = new Location();
			if ($context === 'myPreferences') {
				$location->locationId = $_REQUEST['locationCode'];
			} else {
				$location->code = $_REQUEST['locationCode'];
			}
			if ($location->find(true)) {
				$sublocations = [];
				$allSublocations = $location->getPickupSublocations($patron);
				foreach ($allSublocations as $sublocation) {
					$sublocations[$sublocation->id] = $sublocation->name;
				}

				if (count($sublocations) > 1) {
					$success = true;
					if ($context === 'myPreferences') {
						$labelText = 'Preferred Pickup Area';
					} elseif ($context === 'changePickupLocation') {
						$labelText = 'Select a new area to pickup your hold';
					} else {
						$labelText = 'Select your pickup area';
					}
					$html .= '<label class="control-label" for="pickupSublocation">' . translate([
							'text' => $labelText,
							'isPublicFacing' => true,
						]) . '</label>';
					$html .= '<div class="controls">';
					$html .= '<select name="pickupSublocation" id="pickupSublocation" class="form-control">';
					foreach ($sublocations as $location => $label) {
						$selected = false;
						if ($patron->pickupSublocationId === $location) {
							$selected = true;
						}
						$html .= '<option value="' . $location . '"' . ($selected ? ' selected="selected"' : '') . '>' . $label . '</option>';
					}
					$html .= '</select>';
					$html .= '</div>';
				}
			}

		}
		return [
			'success' => $success,
			'selectHtml' => $html
		];
	}

	public function getUserCheckouts(): array {
		$this->checkRequiredModule('Community Engagement');
		$this->checkRequiredParameters(['userId']);
		$userId = $_REQUEST['userId'] ?? null;

		$user = new User();
		$user->id = $userId;
		if (!$user->find(true)) {
			return $this->failureResult(null, 'User not found');
		}

		$user->checkoutInfoLastLoaded = 0;
		$user->update();

		$user->getCheckouts();

		return [
			'success' => true,
		];
	}

	public function getUserHolds(): array {
		$this->checkRequiredModule('Community Engagement');
		$this->checkRequiredParameters(['userId']);

		$userId = $_REQUEST['userId'] ?? null;

		$user = new User();
		$user->id = $userId;
		if (!$user->find(true)) {
			return $this->failureResult(null, 'User not found');
		}

		$user->holdInfoLastLoaded = 0;
		$user->update();
		$user->getHolds();

		return [
			'success' => true,
		];
	}

	/**
	 * Refresh user circulation cache by forcing reload of checkouts and holds data.
	 *
	 * @noinspection PhpUnused
	 */
	public function refreshUserCirculationCache(): array {
		$this->requireLoggedInUser('Error', 'You must be logged in to refresh circulation data.');

		$user = UserAccount::getActiveUserObj();

		// Skip if data was refreshed in the last 5 seconds to prevent multiple calls.
		$lastRefresh = max($user->checkoutInfoLastLoaded, $user->holdInfoLastLoaded);
		if ($lastRefresh > (time() - 5)) {
			return [
				'success' => true,
			];
		}

		$user->checkoutInfoLastLoaded = 0;
		$user->holdInfoLastLoaded = 0;
		$user->update();
		$user->getCheckouts();
		$user->getHolds();

		return [
			'success' => true,
		];
	}

	/**
	 * Get updated circulation button HTML for multiple records after cache refresh.
	 *
	 * @return array
	 */
	/** @noinspection PhpUnused */
	public function getUpdatedCirculationButtons(): array {
		$this->requireLoggedInUser('Error', 'You must be logged in to get circulation buttons.');

		$recordData = $_REQUEST['recordData'] ?? [];
		if (empty($recordData)) {
			return $this->failureResult(null, 'No record data provided');
		}

		$user = UserAccount::getActiveUserObj();
		$updatedButtons = [];

		foreach ($recordData as $record) {
			$source = $record['source'] ?? '';
			$recordId = $record['recordId'] ?? '';
			$clearAllButtons = false;

			if (!empty($source) && !empty($recordId)) {
				$actions = $user->getCirculatedRecordActions($source, $recordId);
				// If actions are empty, there are no circulation actions,
				// so load the default actions from the respective record driver.
				if (empty($actions)) {
					try {
						require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
						$recordDriver = RecordDriverFactory::initRecordDriverById($source . ':' . $recordId);
						if ($recordDriver && !($recordDriver instanceof AspenError)) {
							$actions = $recordDriver->getRecordActionsFromIndex();
							$clearAllButtons = true;
						}
					} catch (Exception $e) {
						global $logger;
						$logger->log("Failed to load actions from record driver for $source:$recordId: " . $e->getMessage(), Logger::LOG_ERROR);
					}
				}

				$updatedButtons[] = [
					'source' => $source,
					'recordId' => $recordId,
					'actions' => $actions,
					'clearAllButtons' => $clearAllButtons,
				];
			}
		}

		return [
			'success' => true,
			'buttons' => $updatedButtons,
		];
	}

	/** @noinspection PhpUnused */
	function getListPrintOptions(): array {
		global $interface;
		$interface->assign('printListId', strip_tags($_REQUEST['listId']));
		$interface->assign('selectedResourceTypes', empty($_REQUEST['selectedResourceTypes']) ? '' : $_REQUEST['selectedResourceTypes']);
		$interface->assign('activeFilters', empty($_REQUEST['activeFilters']) ? '' : $_REQUEST['activeFilters']);

		return [
			'title' => translate([
				'text' => 'Print Options',
				'isAdminFacing' => 'true',
			]),
			'modalBody' => $interface->fetch('MyAccount/list-print-options.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Lists.buildAndOpenPrintUrl()'>" . translate([
					'text' => 'Print',
					'isAdminFacing' => 'true',
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function getEditListGroupParentForm(): array {
		$this->requireLoggedInUser();
		global $interface;
		if (isset($_REQUEST['groupId'])) {
			$groupId = $_REQUEST['groupId'];
			$parentId = $_REQUEST['parentId'];
			$listGroups = [];
			require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
			$group = new UserListGroup();
			$listGroups = $group->getListGroups(UserAccount::getActiveUserObj());
			$interface->assign('groupId', $groupId);
			$interface->assign('parentId', $parentId);
			$interface->assign('listGroups', $listGroups);
			return [
				'title' => translate([
					'text' => 'Move List Group',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('MyAccount/editListGroupParent.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#moveListGroupForm\").submit()'>" . translate([
						'text' => 'Save',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must provide the id of the group to modify',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function editListGroupParent(): array {
		$this->requireLoggedInUser();
		$result = $this->failureResult('Moving List Group', 'Sorry your list group was unabled to be moved.');

		$groupId = $_REQUEST['groupId'];
		$listGroupMoveId = $_REQUEST['listGroupMove'];
		if ($groupId && $listGroupMoveId) {
			require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
			$group = new UserListGroup();
			$group->id = $groupId;
			$group->userId = UserAccount::getActiveUserId();
			if ($group->find(true)) {
				$group->parentGroupId = $listGroupMoveId;
				if ($group->update()) {
					$result = $this->successResult('Moving List Group', 'Your list group was successfully moved.');
				} else {
					$result['message'] = translate([
						'text' => 'The list group could not be updated.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = translate([
					'text' => 'The specified group could not be found.',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'You must provide the id of the group to modify and the new parent group.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getEditListGroupNameForm(): array {
		$this->requireLoggedInUser();
		global $interface;
		if (isset($_REQUEST['groupId'])) {
			$groupId = $_REQUEST['groupId'];
			$interface->assign('groupId', $groupId);
			return [
				'title' => translate([
					'text' => 'Rename List Group',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('MyAccount/editListGroupName.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#renameListGroupForm\").submit()'>" . translate([
						'text' => 'Save',
						'isPublicFacing' => true,
					]) . "</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must provide the id of the group to modify',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function editListGroupName(): array {
		$this->requireLoggedInUser();
		$result = $this->failureResult('Rename List Group', 'Sorry your list group was unabled to be renamed.');

		$groupId = $_REQUEST['groupId'];
		$newName = $_REQUEST['listGroupNameNew'];
		if ($groupId && $newName) {
			require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
			$group = new UserListGroup();
			$group->id = $groupId;
			$group->userId = UserAccount::getActiveUserId();
			if ($group->find(true)) {
				$group->title = $newName;
				if ($group->update()) {
					$result = [
						'success' => true,
						'message' => translate([
							'text' => 'Your list group was successfully renamed.',
							'isPublicFacing' => true,
						]),
					];
				} else {
					$result['message'] = translate([
						'text' => 'The list group could not be updated.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = translate([
					'text' => 'The specified group could not be found.',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'You must provide the id of the group to modify and a new title.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getCreateListGroupForm(): array {
		$this->requireLoggedInUser();
		global $interface;
		require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
		$listGroup = new UserListGroup();
		$userListGroups = $listGroup->getListGroups(UserAccount::getActiveUserObj());
		$interface->assign('userListGroups', $userListGroups);
		$groupId = null;
		if ($_REQUEST['groupId']) {
			$groupId = $_REQUEST['groupId'];
		}
		$interface->assign('groupId', $groupId);
		return [
			'title' => translate([
				'text' => 'Create New List Group',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/createListGroupForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.createListGroup()'>" . translate([
					'text' => 'Create List Group',
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function createListGroup(): array {
		$this->requireLoggedInUser(null, "You must be logged in to create a list group");
		$result = [];
		$user = UserAccount::getLoggedInUser();
		$title = (isset($_REQUEST['title']) && !is_array($_REQUEST['title'])) ? urldecode($_REQUEST['title']) : '';
		if (strlen(trim($title)) == 0) {
			$result['success'] = "false";
			$result['message'] = "You must provide a title for the list";
		} else {
			$parentId = $_REQUEST['nestedGroupId'] ?? -1;
			require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
			$listGroup = new UserListGroup();
			$listGroup->userId = $user->id;
			$listGroup->title = $title;
			$listGroup->parentGroupId = $parentId;
			if ($listGroup->insert()) {
				// Set the last viewed group to the newly created group
				//$user->lastListGroupViewed = $listGroup->id;
				//$user->update();
				$result['success'] = "true";
				$result['message'] = "List group $listGroup->title created successfully";
			} else {
				$result['success'] = "false";
				$result['message'] = "Could not create list group";
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getDeleteListGroupForm(): array {
		$this->requireLoggedInUser();
		$groupId = $_REQUEST['groupId'] ?? null;
		$modalBody = translate([
			'text' => 'Are you sure you want to delete the list group? If any lists remain in the group, they will be unassigned. This action cannot be undone.',
			'isPublicFacing' => true
		]);

		/** @noinspection BadExpressionStatementJS */
		$modalButtons = '<button id="confirmDeleteListGroup" class="tool btn btn-danger" onclick="AspenDiscovery.Account.deleteListGroup(' . $groupId . ')">' . translate([
				'text' => 'Yes',
				'isPublicFacing' => true
			]) . '</button>';
		/** @noinspection BadExpressionStatementJS */
		$modalButtons .= '<button id="cancelDeleteListGroup" class="tool btn btn-default" onclick="AspenDiscovery.closeLightbox()">' . translate([
				'text' => 'No',
				'isPublicFacing' => true
			]) . '</button>';

		return [
			'title' => translate([
				'text' => 'Delete List Group?',
				'isPublicFacing' => true
			]),
			'modalBody' => $modalBody,
			'modalButtons' => $modalButtons
		];
	}

	/** @noinspection PhpUnused */
	function deleteListGroup(): array {
		$this->requireLoggedInUser();
		$result = $this->failureResult('Delete List Group', 'Sorry, the list group could not be deleted.');

		$groupId = $_REQUEST['groupId'] ?? null;
		if ($groupId) {
			require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
			$group = new UserListGroup();
			$group->id = $groupId;
			$group->userId = UserAccount::getActiveUserId();
			if ($group->find(true)) {
				if ($group->delete()) {
					// clear last viewed group if it was this one
					$user = UserAccount::getLoggedInUser();
					if ($user->lastListGroupViewed == $groupId) {
						$user = UserAccount::getActiveUserObj();
						$user->lastListGroupViewed = -1;
						$user->update();
					}

					// Unassign any lists that were in this group
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$userList = new UserList();
					$userList->listGroupId = $groupId;
					$userList->user_id = UserAccount::getActiveUserId();
					$userList->find();
					while ($userList->fetch()) {
						$userList->listGroupId = -1;
						$userList->update();
					}

					// Unassign any subgroups that were in this group
					$subGroup = new UserListGroup();
					$subGroup->parentGroupId = $groupId;
					$subGroup->userId = UserAccount::getActiveUserId();
					$subGroup->find();
					while ($subGroup->fetch()) {
						$subGroup->parentGroupId = -1;
						$subGroup->update();
					}

					$result = $this->successResult('Delete List Group', 'The list group was successfully deleted.');
				} else {
					$result['message'] = translate([
						'text' => 'The list group could not be deleted.',
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['message'] = translate([
					'text' => 'The specified group could not be found.',
					'isPublicFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'You must provide the id of the group to delete.',
				'isPublicFacing' => true,
			]);
		}

		return $result;

	}

	/** @noinspection PhpUnused */
	function getMenuDataSearches() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to get menu data');
		global $timer;
		$result = $this->failureResult(null, 'Unknown Error');

		$user = UserAccount::getActiveUserObj();
		if ($user->canSaveSearches()) {
			$searchEntry = new SearchEntry();
			$savedSearches = $searchEntry::getUserSavedSearches($user->id);
			$recentSearches = $searchEntry::getUserRecentSearches(session_id(), $user->id);
			$timer->logTime("Loaded user searches for menu data");
			$result = [
				'success' => true,
				'numSavedSearches' => count($savedSearches),
				'numRecentSearches' => count($recentSearches),
			];
		} else {
			$result['message'] = translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function getSearchHistory(): array {
		global $interface;

		if (!UserAccount::isLoggedIn()) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'You must be logged in to view search history',
					'isPublicFacing' => true
				]),
				'searches' => '',
				'pagination' => ''
			];
		}

		$type = $_REQUEST['type'] ?? 'saved';
		$sort = $_REQUEST['sort'] ?? 'id';
		$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
		$limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 20;
		$filter = $_REQUEST['filter'] ?? '';

		// Validate type parameter
		if (!in_array($type, [
			'saved',
			'recent'
		])) {
			$type = 'saved';
		}

		$interface->assign('type', $type);
		$interface->assign('sort', $sort);
		$interface->assign('page', $page);
		$interface->assign('limit', $limit);
		$interface->assign('savedSearchFilter', $filter);


		$sortOptions = [
			'id' => 'Id (Default)',
			'title_asc' => 'Name (A-Z)',
			'title_desc' => 'Name (Z-A)',
		];
		$interface->assign('sortOptions', $sortOptions);

		if ($type === 'saved') {
			return $this->getSavedSearches();
		} else {
			return $this->getRecentSearches();
		}
	}

	/** @noinspection PhpUnused */
	private function getSavedSearches(): array {
		$this->requireLoggedInUser();
		global $interface;

		$interface->assign('noSavedSearches', false);
		$result = [
			'success' => false,
			'message' => 'Unknown error loading saved searches',
			'searches' => '',
			'pagination' => '',
			'totalCount' => 0,
		];

		$user = UserAccount::getActiveUserObj();

		$sort = $_REQUEST['sort'] ?? 'id';
		$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
		$limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 20;
		$filter = $_REQUEST['filter'] ?? '';
		$interface->assign('limit', $limit);
		$interface->assign('sort', $sort);
		$interface->assign('filter', $filter);
		$interface->assign('showNotificationColumn', $user->notifySavedSearches === 1);

		$searches = [];
		$savedSearches = [];
		$savedSearch = new SearchEntry();
		$savedSearch->whereAdd("searchSource <> 'user_list'");
		$savedSearch->user_id = $user->id;
		$savedSearch->saved = 1;
		if (!empty($filter)) {
			$escapedFilter = $savedSearch->escape('%' . $filter . '%');
			$savedSearch->whereAdd("title LIKE $escapedFilter");
		}
		$totalCount = $savedSearch->count();
		switch ($sort) {
			case 'created_asc':
				$savedSearch->orderBy('created ASC');
				break;
			case 'created_desc':
				$savedSearch->orderBy('created DESC');
				break;
			case 'source_asc':
				$savedSearch->orderBy('searchSource ASC');
				break;
			case 'source_desc':
				$savedSearch->orderBy('searchSource DESC');
				break;
			case 'title_asc':
				$savedSearch->orderBy('title ASC');
				break;
			case 'title_desc':
				$savedSearch->orderBy('title DESC');
				break;
			default:
				$savedSearch->orderBy('id DESC');
				break;
		}
		$savedSearch->limit(($page - 1) * $limit, $limit);
		$savedSearch->find();
		while ($savedSearch->fetch()) {
			$savedSearches[] = clone $savedSearch;
		}

		foreach ($savedSearches as $savedSearch) {
			/** @var SearchObject_AbstractGroupedWorkSearcher|SearchObject_BaseSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject($savedSearch->searchSource);
			$size = strlen($savedSearch->search_object);
			$minSO = unserialize($savedSearch->search_object);
			$searchObject->deminify($minSO);
			$searchObject->activateAllFacets();

			$searchSourceLabels = [
				'local' => 'Catalog',
				'genealogy' => 'Genealogy',
				'series' => 'Series'
			];

			$searchSourceLabel = $searchObject->getSearchSource();
			if (array_key_exists($searchSourceLabel, $searchSourceLabels)) {
				$searchSourceLabel = $searchSourceLabels[$searchSourceLabel];
			}

			$newItem = [
				'id' => $savedSearch->id,
				'time' => date("g:ia, jS M y", $searchObject->getStartTime()),
				'title' => $savedSearch->title,
				'url' => $searchObject->renderSearchUrl(),
				'searchId' => $searchObject->getSearchId(),
				'description' => $searchObject->displayQuery(),
				'filters' => $searchObject->getFilterList(),
				'hits' => number_format($searchObject->getResultTotal()),
				'source' => $searchSourceLabel,
				'speed' => round($searchObject->getQuerySpeed(), 2) . "s",
				// Size is purely for debugging. Not currently displayed in the template.
				// It's the size of the serialized, minified search in the database.
				'size' => round($size / 1024, 3) . "kb",
				'hasNewResults' => $savedSearch->hasNewResults == 1,
				'sendNotification' => $savedSearch->sendNotification,
			];

			if ($savedSearch->hasNewResults) {
				$searchObject->addFilter('time_since_added:Week');
				$newItem['newTitlesUrl'] = $searchObject->renderSearchUrl();
			}

			$searches[] = $newItem;
		}

		if (count($searches) > 0) {
			$interface->assign('searches', $searches);
			$interface->assign('userSearchType', 'saved');
			$interface->assign('totalPages', ceil($totalCount / $limit));
			$interface->assign('currentPage', $page);
			$interface->assign('totalCount', $totalCount);

			$result['success'] = true;
			$result['searches'] = $interface->fetch('Search/historyList.tpl');
			$result['pagination'] = $interface->fetch('Search/historyPagination.tpl');
			$result['totalCount'] = $totalCount;
		} else if (!empty($filter)) {
			$interface->assign('searches', $searches);
			$interface->assign('userSearchType', 'saved');
			$interface->assign('totalPages', ceil($totalCount / $limit));
			$interface->assign('currentPage', $page);
			$interface->assign('totalCount', $totalCount);

			$result['success'] = true;
			$result['searches'] = $interface->fetch('Search/historyList.tpl');
			$result['pagination'] = $interface->fetch('Search/historyPagination.tpl');
			$result['totalCount'] = $totalCount;
		} else {
			$interface->assign('noSavedSearches', true);
			$result['message'] = translate([
				'text' => 'No saved searches found.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	private function getRecentSearches(): array {
		$this->requireLoggedInUser(null, "Your login has timed out. Please login again.");
		global $interface;
		$interface->assign('noRecentSearches', false);
		$result = [
			'success' => false,
			'message' => 'Unknown error loading recent searches',
			'searches' => '',
			'pagination' => '',
			'totalCount' => 0,
		];

		$user = UserAccount::getActiveUserObj();

		$searches = [];

		$sort = $_REQUEST['sort'] ?? 'id';
		$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
		$limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 20;
		$interface->assign('limit', $limit);
		$interface->assign('sort', $sort);

		$savedSearches = [];
		$savedSearch = new SearchEntry();
		$savedSearch->whereAdd("session_id = '" . session_id() . "' OR user_id = " . $user->id);
		$savedSearch->whereAdd("searchSource <> 'user_list'");
		$savedSearch->saved = 0;
		$totalCount = $savedSearch->count();
		switch ($sort) {
			case 'created_asc':
				$savedSearch->orderBy('created ASC');
				break;
			case 'created_desc':
				$savedSearch->orderBy('created DESC');
				break;
			case 'query_asc':
				$savedSearch->orderBy('description ASC');
				break;
			case 'query_desc':
				$savedSearch->orderBy('description DESC');
				break;
			default:
				$savedSearch->orderBy('id DESC');
				break;
		}
		$savedSearch->limit(($page - 1) * $limit, $limit);
		$savedSearch->orderBy('id');
		$savedSearch->find();
		while ($savedSearch->fetch()) {
			$savedSearches[] = clone $savedSearch;
		}

		foreach ($savedSearches as $savedSearch) {
			/** @var SearchObject_AbstractGroupedWorkSearcher|SearchObject_BaseSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init();
			$size = strlen($savedSearch->search_object);
			$minSO = unserialize($savedSearch->search_object);
			$searchObject = SearchObjectFactory::deminify($minSO);
			$searchObject->activateAllFacets();

			$searchSourceLabels = [
				'local' => 'Catalog',
				'genealogy' => 'Genealogy',
			];

			$searchSourceLabel = $searchObject->getSearchSource();
			if (array_key_exists($searchSourceLabel, $searchSourceLabels)) {
				$searchSourceLabel = $searchSourceLabels[$searchSourceLabel];
			}

			$newItem = [
				'id' => $savedSearch->id,
				'time' => date("g:ia, jS M y", $searchObject->getStartTime()),
				'url' => $searchObject->renderSearchUrl(),
				'searchId' => $searchObject->getSearchId(),
				'description' => $searchObject->displayQuery(),
				'filters' => $searchObject->getFilterList(),
				'hits' => number_format($searchObject->getResultTotal()),
				'source' => $searchSourceLabel,
				'speed' => round($searchObject->getQuerySpeed(), 2) . "s",
				// Size is purely for debugging. Not currently displayed in the template.
				// It's the size of the serialized, minified search in the database.
				'size' => round($size / 1024, 3) . "kb",
				'hasNewResults' => $savedSearch->hasNewResults == 1,
			];

			$searches[] = $newItem;
		}

		if (count($searches) > 0) {
			$interface->assign('searches', $searches);
			$interface->assign('userSearchType', 'recent');
			$interface->assign('totalPages', ceil($totalCount / $limit));
			$interface->assign('currentPage', $page);
			$interface->assign('totalCount', $totalCount);

			$result['success'] = true;
			$result['searches'] = $interface->fetch('Search/historyList.tpl');
			$result['pagination'] = $interface->fetch('Search/historyPagination.tpl');
			$result['totalCount'] = $totalCount;
		} else {
			$interface->assign('noRecentSearches', true);
			$result['message'] = translate([
				'text' => 'No recent searches found.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function removeCampaignModal() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredModule('Community Engagement');
		$activeUser = UserAccount::getActiveUserObj();

		$userId = $activeUser->id;
		$campaignId = $_REQUEST['campaignId'] ?? null;

		if (!$campaignId) {
			return [
				'sucess' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Cannot find a campaign with this ID',
					'isPublicFacing' => true,
				]),
			];
		}

		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';

		$campaign = new Campaign();
		$campaign->id = $campaignId;
		if (!$campaign->find(true)) {
			return [
				'sucess' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Campaign not found',
					'isPublicFacing' => true,
				]),
			];
		}

		$campaignName = $campaign->name;
		global $interface;
		$interface->assign('campaignName', $campaignName);

		/** @noinspection BadExpressionStatementJS */
		/** @noinspection JSVoidFunctionReturnValueUsed */
		/** @noinspection CommaExpressionJS */
		return [
			'success' => true,
			'title' => translate([
				'text' => 'Remove Campaign',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/remove-campaign-modal.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.removeCampaignFromUI($campaignId, $userId)'>" . translate([
					'text' => 'Remove',
					'isAdminFacing' => 'true',
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	public function removeCampaignFromUI() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredModule('Community Engagement');

		$activeUser = UserAccount::getActiveUserObj();

		$userId = $_REQUEST['userId'] ?? null;
		$campaignId = $_REQUEST['campaignId'] ?? null;

		if (!$campaignId || !$userId) {
			return $this->failureResult('Error', 'Missing Parameter');
		}

		$isAdmin = UserAccount::userHasPermission('View Community Engagement Admin View');
		$isSelf = ($activeUser->id == $userId);

		if (!$isAdmin && !$isSelf) {
			return $this->failureResult('Error', 'You do not have permission to perform this action.');
		 }

		require_once ROOT_DIR . '/sys/CommunityEngagement/UserRemovedCampaign.php';

		$removedCampaign = new UserRemovedCampaign();
		$removedCampaign->userId = $userId;
		$removedCampaign->campaignId = $campaignId;

		if (!$removedCampaign->find(true)) {
			$removedCampaign->insert();
		}

		return $this->successResult('Success', 'Campaign removed from view');
	}

	/** @noinspection PhpUnused */
	function getListTransferForm(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['listId']);

		global $interface;
		$interface->assign('listId', strip_tags($_REQUEST['listId']));

		if (isset($_REQUEST['validationFailed'])) {
			$interface->assign('hasListValidationError', $_REQUEST['validationFailed']);
		}

		return [
			'title' => translate([
				'text' => 'List Transfer',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/listTransferPopup.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#transferListForm\").submit();'>" . translate([
					'text' => 'Save',
					'isAdminFacing' => 'true',
				]) . "</button>",

		];
	}

	/** @noinspection PhpUnused */
	function listTransferValidation(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['listId', 'newListOwner']);

		global $interface;

		$listId = $_REQUEST['listId'];
		$newListOwner = $_REQUEST['newListOwner'];

		$patron = new User();
		$escapedNewOwner = $patron->escape($newListOwner);
		$patron->whereAdd("ils_barcode = $escapedNewOwner OR ils_username = $escapedNewOwner OR username = $escapedNewOwner");
		$patron->find();
		$numResults = $patron->count();
		if ($numResults == 1 && $patron->find(true)) {
			if ($patron->id == UserAccount::getActiveUserId()) {
				return $this->failureResult(null, 'Cannot transfer a list to yourself.');
			}else if ($patron->isStaff()) {
				$interface->assign('listId', $listId);
				$interface->assign('newListOwner', $patron);
				return [
					'success' => true,
					'title' => translate([
						'text' => 'List Transfer',
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch('MyAccount/listTransferConfirm.tpl'),
					'modalButtons' => "<button id='listTransferProcesBtn' class='tool btn btn-primary' onclick='AspenDiscovery.Lists.listTransferProcess(\"$listId\", \"$patron->id\")'>" . translate([
							'text' => 'Confirm',
							'isAdminFacing' => 'true',
						]) . "</button>",
				];
			}else{
				return $this->failureResult(null, 'Cannot transfer a list to the specified user.');
			}
		}else{
			return $this->failureResult(null, 'User not found.');
		}
	}

	/** @noinspection PhpUnused */
	function listTransferProcess(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['listId', 'userId']);

		global $configArray;
		$listId = $_REQUEST['listId'];
		$newListOwner = $_REQUEST['userId'];

		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Unable to Transfer List',
				'isAdminFacing' => true
			]),
			'message' => "",
		];

		$user = new User();
		$user->id = $newListOwner;
		if ($user->find(true)) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->id = $listId;
			if ($list->find(true)) {
				$list->user_id = $user->id;
				$list->listGroupId = -1;
				if ($list->update()) {
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mailer = new Mailer();
					$subject = translate([
						'text' => 'An Aspen list has been transferred to you',
						'isAdminFacing' => true,
					]);
					$body = translate([
							'text' => 'The following list(s) have been transferred to your account by an administrator:',
							'isPublicFacing' => true,
						]) . "\r\n" . $configArray['Site']['url'] . '/MyAccount/MyList/' . $list->id;
					$htmlBody = '<p>' . translate([
							'text' => 'The following list(s) have been transferred to your account by an administrator:',
							'isAdminFacing' => true,
						]) . '</p>';
					$htmlBody .= '<ul><li>' . translate([
							'text' => '%1%' . $list->title . '%2%',
							1 => '<a href="' . $configArray['Site']['url'] . '/MyAccount/MyList/' . $list->id . '">',
							2 => '</a>',
							'isPublicFacing' => true,
						]) . '</li></ul>';
					if ($mailer->send($user->email, $subject, $body, null, $htmlBody)) {
						$results['success'] = true;
					} else {
						$results['title'] = translate([
							'text' => 'Success',
							'isAdminFacing' => true
						]);
						$results['message'] = "The list was transferred sucessfully but we were unable to send an email to the new list owner.";
					}
				} else {
					$results['message'] = "There was an error updating the list owner: " . $list->getLastError();
				}
			} else {
				$results['message'] = "Could not locate the list by id " . $listId;
			}
		} else {
			$results['message'] = "Could not locate a user by id " . $newListOwner;
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function getListGroupTransferForm(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['listGroupId']);

		global $interface;
		$interface->assign('listGroupId', strip_tags($_REQUEST['listGroupId']));

		if (isset($_REQUEST['validationFailed'])) {
			$interface->assign('hasListValidationError', $_REQUEST['validationFailed']);
		}

		return [
			'title' => translate([
				'text' => 'List Group Transfer',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/listGroupTransferPopup.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#transferListGroupForm\").submit();'>" . translate([
					'text' => 'Save',
					'isAdminFacing' => 'true',
				]) . "</button>",

		];
	}

	/** @noinspection PhpUnused */
	function listGroupTransferValidation(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['listGroupId', 'newListGroupOwner']);

		global $interface;

		$listGroupId = $_REQUEST['listGroupId'];
		$newListOwner = $_REQUEST['newListGroupOwner'];

		$patron = new User();
		$escapedNewOwner = $patron->escape($newListOwner);
		$patron->whereAdd("ils_barcode = $escapedNewOwner OR ils_username = $escapedNewOwner OR username = $escapedNewOwner");
		$patron->find();
		$numResults = $patron->count();
		if ($numResults == 1 && $patron->find(true)) {
			if ($patron->isStaff()) {
				$interface->assign('listGroupId', $listGroupId);
				$interface->assign('newListGroupOwner', $patron);
				return [
					'success' => true,
					'title' => translate([
						'text' => 'List Group Transfer',
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch('MyAccount/listGroupTransferConfirm.tpl'),
					'modalButtons' => "<button id='listTransferProcesBtn' class='tool btn btn-primary' onclick='AspenDiscovery.Lists.listGroupTransferProcess(\"$listGroupId\", \"$patron->id\")'>" . translate([
							'text' => 'Confirm',
							'isAdminFacing' => 'true',
						]) . "</button>",
				];
			}
		}
		return [
			'success' => false
		];
	}

	/** @noinspection PhpUnused */
	function listGroupTransferProcess(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['listGroupId', 'userId']);

		global $configArray;
		$listGroupId = $_REQUEST['listGroupId'];
		$newListOwner = $_REQUEST['userId'];

		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Unable to Transfer List Group',
				'isAdminFacing' => true
			]),
			'message' => "",
		];

		$user = new User();
		$user->id = $newListOwner;
		if ($user->find(true)) {
			require_once ROOT_DIR . '/sys/UserLists/UserListGroup.php';
			$listGroup = new UserListGroup();
			$listGroup->id = $listGroupId;
			if ($listGroup->find(true)) {
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$lists = new UserList();
				$lists->listGroupId = $listGroup->id;
				$lists->find();
				while ($lists->fetch()) {
					$lists->user_id = $user->id;
					$lists->update();
				}
				// since we aren't transferring the parent, we should orphan it
				if ($listGroup->parentGroupId != -1) {
					$listGroup->parentGroupId = -1;
				}
				$listGroup->userId = $user->id;
				if ($listGroup->update()) {
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mailer = new Mailer();
					$subject = translate([
						'text' => 'An Aspen list group has been transferred to you',
						'isAdminFacing' => true,
					]);
					$body = translate([
							'text' => 'The following list group has been transferred to your account by an administrator:',
							'isPublicFacing' => true,
						]) . "\r\n" . $configArray['Site']['url'] . '/MyAccount/Lists?groupId=' . $listGroup->id;
					$htmlBody = '<p>' . translate([
							'text' => 'The following list group has been transferred to your account by an administrator:',
							'isAdminFacing' => true,
						]) . '</p>';
					$htmlBody .= '<ul><li><a href="' . $configArray['Site']['url'] . '/MyAccount/Lists?groupId' . $listGroup->id . '">' . $listGroup->title . '</a></li></ul>';
					if ($mailer->send($user->email, $subject, $body, null, $htmlBody)) {
						$results['success'] = true;
					} else {
						$results['title'] = translate([
							'text' => 'Success',
							'isAdminFacing' => true
						]);
						$results['message'] = "The list group was transferred successfully but we were unable to send an email to the new list group owner.";
					}
				} else {
					$results['message'] = "There was an error updating the list group owner: " . $listGroup->getLastError();
				}
			} else {
				if ($listGroupId == -1) {
					$results['message'] = "Cannot transfer the Unassigned List group.";
				} else {
					$results['message'] = "Could not locate the list group by id " . $listGroupId;
				}
			}
		} else {
			$results['message'] = "Could not locate a user by id " . $newListOwner;
		}

		return $results;
	}

	/** @noinspection PhpUnused */
	function getListsTransferForm(): array {
		$this->requireLoggedInUser();
		global $interface;
		$interface->assign('prevListOwner', strip_tags($_REQUEST['prevListOwner']));

		if (isset($_REQUEST['validationFailed'])) {
			$interface->assign('hasListValidationError', $_REQUEST['validationFailed']);
		}

		return [
			'title' => translate([
				'text' => 'Transfer All Lists',
				'isAdminFacing' => true,
			]),
			'modalBody' => $interface->fetch('MyAccount/listsTransferPopup.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#transferListsForm\").submit();'>" . translate([
					'text' => 'Save',
					'isAdminFacing' => 'true',
				]) . "</button>",

		];
	}

	/** @noinspection PhpUnused */
	function listsTransferValidation(): array {
		global $interface;
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['newListOwner']);

		$newListOwner = $_REQUEST['newListOwner'];

		$patron = new User();
		$newListOwnerEscaped = $patron->escape($newListOwner);
		$patron->whereAdd("ils_barcode = $newListOwnerEscaped OR ils_username = $newListOwnerEscaped OR username = $newListOwnerEscaped");
		$patron->find();
		$numResults = $patron->count();
		if ($numResults == 1 && $patron->find(true)) {
			if ($patron->isStaff()) {
				$interface->assign('newListOwner', $patron);
				return [
					'success' => true,
					'title' => translate([
						'text' => 'Transfer All Lists',
						'isAdminFacing' => true,
					]),
					'modalBody' => $interface->fetch('MyAccount/listsTransferConfirm.tpl'),
					'modalButtons' => "<button id='listsTransferProcesBtn' class='tool btn btn-primary' onclick='AspenDiscovery.Lists.listsTransferProcess(\"$patron->id\")'>" . translate([
							'text' => 'Confirm',
							'isAdminFacing' => 'true',
						]) . "</button>",
				];
			}
		}
		return [
			'success' => false
		];
	}

	/** @noinspection PhpUnused */
	function listsTransferProcess(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredParameters(['userId']);
		global $configArray;
		$newListOwner = $_REQUEST['userId'];

		$results = [
			'success' => false,
			'title' => translate([
				'text' => 'Unable to Transfer Lists',
				'isAdminFacing' => true
			]),
			'message' => "",
		];

		$user = new User();
		$user->id = $newListOwner;
		if ($user->find(true)) {
			$lists = [];
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$list = new UserList();
			$list->user_id = UserAccount::getActiveUserId();
			$list->find();
			while ($list->fetch()) {
				$lists[$list->id] = $list->title;
				$list->user_id = $user->id;
				$list->listGroupId = -1;
				$list->update();
			}
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$subject = translate([
				'text' => 'An Aspen list has been transferred to you',
				'isAdminFacing' => true,
			]);

			$topLists = array_slice($lists, 0, 20, true); // true preserves keys
			$body = translate([
				'text' => 'The following list(s) have been transferred to your account by an administrator:',
				'isPublicFacing' => true,
			]);
			$htmlBody = '<p>' . translate([
					'text' => 'The following list(s) have been transferred to your account by an administrator:',
					'isAdminFacing' => true,
				]) . '</p><ul>';
			foreach ($topLists as $listId => $listTitle) {
				$body .= "\r\n" . $configArray['Site']['url'] . '/MyAccount/MyList/' . $listId;
				$htmlBody .= '<li><a href="' . $configArray['Site']['url'] . '/MyAccount/MyList/' . $listId . '">' . $listTitle . '</a></li>';
			}

			if (count($lists) > 20) {
				$body .= "\r\n" . translate([
						'text' => 'To see additional transferred lists, please log in to your account.',
						'isAdminFacing' => true
					]);
				$htmlBody .= '</br>' . translate([
						'text' => 'To see additional transferred lists, please log in to your account.',
						'isAdminFacing' => true
					]);
			}

			if ($mailer->send($user->email, $subject, $body, null, $htmlBody)) {
				$results['success'] = true;
			} else {
				$results['title'] = translate([
					'text' => 'Success',
					'isAdminFacing' => true
				]);
				$results['message'] = "The lists were transferred successfully but we were unable to send an email to the new list owner.";
			}
		} else {
			$results['message'] = "Could not locate a user by id " . $newListOwner;
		}

		return $results;
	}

	public function groupPatronHolds() {
		global $interface;
		global $logger;

		$this->requireLoggedInUser(null, 'You must be logged in to group holds.  Please close this dialog and login again.');

		$holdIds = $_REQUEST['holdIds'] ?? [];
		$forceGrouped = $_REQUEST['forceGrouped'] ?? false;
		$userIds = $_REQUEST['userIds'] ?? null;


		if (!is_array($userIds)) {
			$userIds = [$userIds];
		}

		if (count(array_unique($userIds)) > 1) {
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'You cannot group holds from different users', 'isPublicFacing' => true])
			];
		}

		// Convert string to array if needed
		if (is_string($holdIds)) {
			$holdIds = array_filter(array_map('trim', explode(',', $holdIds)));
		}

		if (!is_array($holdIds) || count($holdIds) <= 1) {
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'Please select at least two holds to group', 'isPublicFacing' => true])
			];
		}

		try {
			$userId = $userIds[0];
			$currentUser = UserAccount::getLoggedInUser();
			$targetUser = new User();
			$targetUser->id = $userId;

			if (!$targetUser->find(true)) {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
					'message' => translate(['text' => 'Invalid user specified', 'isPublicFacing' => true])
				];
			}

			$canManage = false;
			if ($currentUser == $userId) {
				$canManage = true;
			} else {
				$linkedUsers = $currentUser->getLinkedUsers();
				foreach ($linkedUsers as $linkedUser) {
					if ($linkedUser->id == $userId) {
						$canManage = true;
						break;
					}
				}
			}

			if (!$canManage) {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error' , 'isPublicFacing' => true]),
					'message' => translate(['text' => 'You do not have permission to manage this user\'s holds.', 'isPublicFacing' => true])
				];
			}

			$patronId = $targetUser->unique_ils_id;
			$catalogDriver = $targetUser->getCatalogDriver();

			if ($catalogDriver->driver instanceof Koha) {
				// Pass forceGrouped to groupHolds
				$groupedHolds = $catalogDriver->groupHolds($patronId, $holdIds, $forceGrouped);

				// Check if holds are already in a group
				if (isset($groupedHolds['error_code']) && $groupedHolds['error_code'] === 'HoldAlreadyBelongsToHoldGroup') {
					$interface->assign('conflictIds', $groupedHolds['hold_ids'] ?? []);
					return [
						'success' => false,
						'specialError' => 'holdAlreadyGrouped',
						'title' => translate(['text' => 'Holds Already Grouped', 'isPublicFacing' => true]),
						'modalBody' => $interface->fetch('HoldGroups/forceGroupedHoldsModal.tpl'),
						'modalButtons' => "<button class='tool btn btn-danger' id='forcegroupHoldsGroupBtn' onclick='AspenDiscovery.Account.forceGroupHolds(" . json_encode($holdIds) . ", " . json_encode($userIds) . "); return false;'>"  
								. translate(['text' => 'Continue to Group Holds', 'isPublicFacing' => true]) . "</button>",
					];
				}

				if ($groupedHolds['success']) {
					return [
						'success' => true,
						'title' => translate(['text' => 'Success', 'isPublicFacing' => true]),
						'message' => translate(['text' => 'Holds grouped successfully', 'isPublicFacing' => true])
					];
				} else {
					return [
						'success' => false,
						'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
						'message' => translate(['text' => 'Failed to group holds', 'isPublicFacing' => true])
					];
				}
			} else {
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
					'message' => translate(['text' => 'Your catalog driver does not support this feature', 'isPublicFacing' => true])
				];
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log('Error grouping patron holds: ' . $e->getMessage(), Logger::LOG_ERROR);
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'An error occurred while grouping holds', 'isPublicFacing' => true])
			];
		}
	}

	public function requestDeleteHoldGroupConfirmation() {
		global $interface;
		$this->requireLoggedInUser(null, 'You must be logged in to alter hold groups. Please close this dialog and login again.');

		$holdGroupId = $_REQUEST['holdGroupId'] ?? null;
		$visualHoldId = $_REQUEST['visualHoldId'] ?? '';
		$userId = $_REQUEST['userId'] ?? '';

		if (empty($holdGroupId)) {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'No hold group specified.'
			];
		}

		$interface->assign('holdGroupId', $holdGroupId);
		$interface->assign('visualHoldId', $visualHoldId);
		

		return [
			'success' => true,
			'title' => translate([
				'text' => 'Confirm Ungroup',
				'isPublicFacing' => true
			]),
			'modalBody' => $interface->fetch('HoldGroups/confirmDeleteHoldGroup.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Account.confirmDeleteHoldGroup(" . json_encode($holdGroupId) . ", " . json_encode($visualHoldId) . ", " . json_encode($userId) . "); return false;'>" .  translate([
				'text' => "Ungroup Holds",
				'isPublicFacing' => true,
			]) . "</button>",
		];
	}

	public function deleteHoldGroup() {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$this->requireLoggedInUser(null, 'You must be logged in to alter hold groups. Please close this dialog and login again.');

		$holdGroupId = $_REQUEST['holdGroupId'] ?? null;
		$userId = $_REQUEST['userId'] ?? null;
		$user = new User();
		$user->id = $userId;

		if ($user->find(true)) {
			$patronId = $user->unique_ils_id;
		} else {
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
				'message' => translate(['text' => 'User not found', 'isPublicFacing' => true]),
			];
		}

		if (empty($holdGroupId)) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'No hold group specified',
					'isPublicFacing' => true,
				])
			];
		}

		$catalogDriver = $user->getCatalogDriver();
		if ($catalogDriver->driver instanceof Koha) {
			try {
				$result = $catalogDriver->deletepatronHoldGroup($patronId, $holdGroupId);
				if ($result === true) {
					$holdRecord = new Hold();
					$holdRecord->userId = $user;
					$holdRecord->holdGroupId = $holdGroupId;
					if ($holdRecord->find()) {
						do {
							$holdRecord->holdGroupId = '';
							$holdRecord->visualHoldGroupId = '';
							$holdRecord->update();
						} while ($holdRecord->fetch());
					}

					return [
						'success' => true,
						'title' => translate([
						'text' => 'Success',
						'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Hold Group Deleted',
							'isPublicFacing' => true,
						])
					];
				} else {
					return [
						'success' => false,
						'title' => translate([
							'text' => 'Error',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => 'Failed to delete hold group',
							'isPublicFacing' => true,
						])
					];
				}
			} catch (Exception $e) {
				global $logger;
				$logger->log('Error deleting hold group: ' . $e->getErrorMessage(), Logger::LOG_ERROR);
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'An error occurred while deleting the hold group',
						'isPublicFacing' => true,
					])
				];
			}
		}
	}

	public function joinEventWaitingList() {
		$this->requireLoggedInUser();

		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown error occurred',
				'isPublicFacing' => true,
			])
		];

		$activeUserId = (int)UserAccount::getActiveUserId();
		$userId = isset($_REQUEST['userId']) ? (int)$_REQUEST['userId'] : $activeUserId;
		$eventInstanceId = (int)($_REQUEST['eventInstanceId'] ?? 0);

		if (!UserAccount::isAuthorizedToActOnBehalfOf($userId)) {
			$result['message'] = translate([
				'text' => 'You do not have permission to manage registrations for this user.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if ($eventInstanceId <= 0) {
			$result['message'] = translate([
				'text' => 'Invalid Event ID.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		require_once ROOT_DIR . '/sys/Events/EventInstance.php';
		$eventInstance = new EventInstance();
		$eventInstance->id = $eventInstanceId;

		if (!$eventInstance->find(true)) {
			$result['message'] = translate([
				'text' => 'Event not found.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if (!$eventInstance->isWaitingListEnabled()) {
			$result['message'] = translate([
				'text' => 'This event does not have a waiting list enabled',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		require_once ROOT_DIR . '/services/EventRegistrationService.php';
		if (EventRegistrationService::isWaitingListFull($eventInstance)) {
			$result['message'] = translate([
				'text' => 'The waiting list for this event is full.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		// Add user to waiting list
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $eventInstanceId;
		$registration->userId = $userId;

		if (!$registration->addUserToWaitingList()) {
			$result['success'] = true;
			$result['title'] = translate([
				'text' => 'Already on Waiting List',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'You are already on the waiting list for this event.',
				'isPublicFacing' => true,
			]);
			$registration->find(true);
			$result['position'] = UserAspenEventInstanceRegistration::getWaitingListPosition($registration->eventInstanceId, $registration->createdAt);
			return $result;
		}

		$position = UserAspenEventInstanceRegistration::getWaitingListPosition($registration->eventInstanceId, $registration->createdAt);

		require_once ROOT_DIR . '/sys/Events/Event.php';
		$event = new Event();
		$event->id = $eventInstance->eventId;
		$event->find(true);

		$result['success'] = true;
		$result['title'] = translate([
			'text' => 'Added to Waiting List',
			'isPublicFacing' => true,
		]);

		if ($userId !== $activeUserId) {
			require_once ROOT_DIR . '/sys/Account/User.php';
			$linkedUser = new User();
			$linkedUser->id = $userId;
			$linkedUser->find(true);
			$subject = $linkedUser->getDisplayName();
			$auxVerb = 'has';
			$linkVerb = 'is';
			$pronoun = 'their';
			$reflexive = 'them';
		} else {
			$subject = 'You';
			$auxVerb = 'have';
			$linkVerb = 'are';
			$pronoun = 'your';
			$reflexive = 'you';
		}

		$message = translate([
			'text' => "%1% %6% been added to the waiting list for %2%. %1% %7% in position #%3%. <br><br> To receive waiting list invites, make sure a contact email is saved to %4% account and that event email notifications are enabled in %4% preferences. Without both, %4% spot is held but, as we have no way to let %5% know when it is %4% turn to register %5% will not be able to access registration",
			'isPublicFacing' => true,
		]);
		$result['message'] = str_replace(['%1%', '%2%', '%3%', '%4%', '%5%', '%6%', '%7%'], [$subject, $event->title, $position, $pronoun, $reflexive, $auxVerb, $linkVerb], $message);

		if ($userId !== $activeUserId) {
			$fallbackNote = translate([
				'text' => "If %1% cannot be reached, we will attempt to notify you instead, provided you have a contact email saved and event email notifications enabled on your account.",
				'isPublicFacing' => true,
			]);
			$result['message'] .= ' ' . str_replace('%1%', $subject, $fallbackNote);
		}
		$result['position'] = $position;

		EventRegistrationService::saveToUserEvents($eventInstance, $userId);

		return $result;
	}

	public function leaveEventWaitingList(): array {
		$this->requireLoggedInUser();
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown error occurred',
				'isPublicFacing' => true,
			])
		];

		$activeUserId = (int)UserAccount::getActiveUserId();
		$userId = isset($_REQUEST['userId']) ? (int)$_REQUEST['userId'] : $activeUserId;
		$eventInstanceId = (int)($_REQUEST['eventInstanceId'] ?? 0);

		if (!UserAccount::isAuthorizedToActOnBehalfOf($userId)) {
			$result['message'] = translate([
				'text' => 'You do not have permission to manage registrations for this user.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if ($eventInstanceId <= 0) {
			$result['message'] = translate([
				'text' => 'Invalid Event ID.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		$registration = new UserAspenEventInstanceRegistration();
		$registration->eventInstanceId = $eventInstanceId;
		$registration->userId = $userId;
		$registration->whereAdd('status IN ("waiting", "invited")');

		if (!$registration->find(true)) {
			$result['title'] = translate([
				'text' => 'Waiting List Spot Not Found',
				'isPublicFacing' => true,
			]);
			$result['message'] = translate([
				'text' => 'You were not found on the waiting list for this event instance.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		$registration->delete();

		$result['success'] = true;
		$result['title'] = translate([
			'text' => 'Removed from Waiting List',
			'isPublicFacing' => true,
		]);

		if ($userId !== $activeUserId) {
			require_once ROOT_DIR . '/sys/Account/User.php';
			$linkedUser = new User();
			$linkedUser->id = $userId;
			$linkedUser->find(true);
			$message = translate([
				'text' => '%1% has been removed from the waiting list.',
				'isPublicFacing' => true,
			]);
			$result['message'] = str_replace('%1%', $linkedUser->getDisplayName(), $message);
		} else {
			$result['message'] = translate([
				'text' => 'You have been removed from the waiting list.',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	public function getJoinWaitlistModal(): array {
		$this->requireLoggedInUser();
		$result = [
			'success' => false,
			'title' => translate(['text' => 'Error', 'isPublicFacing' => true]),
			'message' => translate(['text' => 'Unknown error occurred', 'isPublicFacing' => true]),
		];

		$sourceId = $_REQUEST['sourceId'] ?? '';
		$eventInstanceId = (int)preg_replace("/aspenEvent_\d+_/", '', $sourceId);

		if ($eventInstanceId <= 0) {
			$result['message'] = translate(['text' => 'Invalid Event ID.', 'isPublicFacing' => true]);
			return $result;
		}

		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			return $result;
		}

		global $interface, $library;

		$interface->assign('eventSourceId', $sourceId);
		$interface->assign('userId', $user->id);
		$interface->assign('userDisplayName', $user->getDisplayName());
		$interface->assign('userEmail', $user->email);
		$interface->assign('userHomeLocation', $user->getHomeLocationName());

		$linkedUsers = [];
		if ($library->allowLinkedAccounts) {
			$linkedUsers = $user->getLinkedUsers();
			foreach ($linkedUsers as $linkedUser) {
				$linkedUser->loadContactInformation();
			}
		}
		$interface->assign('linkedUsers', $linkedUsers);

		$result['success'] = true;
		$result['hasLinkedAccounts'] = count($linkedUsers) > 0;
		$result['title'] = translate(['text' => 'Join Waiting List', 'isPublicFacing' => true]);
		$result['body'] = $interface->fetch('AspenEvents/joinWaitlistModal.tpl');
		return $result;
	}
}
