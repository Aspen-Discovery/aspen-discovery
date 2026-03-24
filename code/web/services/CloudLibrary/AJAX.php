<?php
require_once ROOT_DIR . '/JSON_Action.php';

class CloudLibrary_AJAX extends JSON_Action {
	function launch($method = null): void {
		$this->checkRequiredModule('Cloud Library');
		parent::launch($method);
	}
	function placeHold(): array {
		$this->requireLoggedInUser("Error Placing Hold on CloudLibrary Title", 'You must be logged in to place a hold.');
		$user = UserAccount::getLoggedInUser();

		$id = $_REQUEST['id'];
		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			return $this->processHoldOrCheckout($id, $patron);
		} else {
			return [
				'result' => false,
				'title' => translate([
					'text' => "Error Placing Hold on CloudLibrary Title",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to place holds for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function checkOutTitle(): array {
		$this->requireLoggedInUser("Error Checking Out CloudLibrary Title", 'You must be logged in to checkout an item.');
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			return $this->processHoldOrCheckout($id, $patron);
		} else {
			return [
				'result' => false,
				'title' => translate([
					'text' => "Error Checking Out CloudLibrary Title",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts(): array {
		$user = UserAccount::getLoggedInUser();
		if (empty($user)) {
			$loggedOutMessage = translate([
				'text' => "Your login has timed out. Please login again.",
				'isPublicFacing' => true,
			]);
			return [
				'promptTitle' => translate([
					'text' => 'Invalid Account',
					'isPublicFacing' => true,
				]),
				'prompts' => '<p class="alert alert-danger">' . $loggedOutMessage . '</p>',
				'buttons' => '',
				'promptNeeded' => true,
			];
		}
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$usersWithCloudLibraryAccess = $this->getCloudLibraryUsers($user);
		$driver = new CloudLibraryDriver();
		$settings = $driver->getSettings($user);

		if (count($usersWithCloudLibraryAccess) > 1) {
			$promptTitle = 'CloudLibrary Hold Options';
			$interface->assign('useAlternateLibraryCard', $settings->useAlternateLibraryCard);
			$validCards = [];
			foreach ($usersWithCloudLibraryAccess as $userWithAccess) {
				$userAltCardOptions = $userWithAccess->getHomeLibrary()->getAlternateLibraryCardOptions();
				if ($userAltCardOptions['useAlternateLibraryCardForCloudLibrary'] && !empty($userWithAccess->alternateLibraryCard) && $driver->checkAuthentication($userWithAccess)) {
					$validCards[] = $userWithAccess;
				}
			}
			$interface->assign('validCards', $validCards);
			return [
				'promptNeeded' => true,
				'promptTitle' => $promptTitle,
				'prompts' => $interface->fetch('CloudLibrary/ajax-hold-prompt.tpl'),
				'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate([
						'text' => 'Place Hold',
						'isPublicFacing' => true,
						'inAttribute' => true,
					]) . '" onclick="return AspenDiscovery.CloudLibrary.processHoldPrompts();">',
			];
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			if ($settings->useAlternateLibraryCard && (empty($user->alternateLibraryCard) || !$driver->checkAuthentication($user))) {
				return $this->getAlternateLibraryCardPrompts('placeHold', $user->id, $id);
			}
			return [
				'patronId' => reset($usersWithCloudLibraryAccess)->id,
				'promptNeeded' => false,
			];
		} else {
			$invalidAccountMessage = translate([
				'text' => "The barcode or library for this account is not valid for CloudLibrary. Please contact your local library for more information.",
				'isPublicFacing' => true,
			]);
			return [
				'promptTitle' => translate([
					'text' => 'Invalid Account',
					'isPublicFacing' => true,
				]),
				'prompts' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
				'buttons' => '',
				'promptNeeded' => true,
			];
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts(): array {
		$user = UserAccount::getLoggedInUser();
		if (empty($user)) {
			$loggedOutMessage = translate([
				'text' => "Your login has timed out. Please login again.",
				'isPublicFacing' => true,
			]);
			return [
				'promptTitle' => translate([
					'text' => 'Invalid Account',
					'isPublicFacing' => true,
				]),
				'prompts' => '<p class="alert alert-danger">' . $loggedOutMessage . '</p>',
				'buttons' => '',
				'promptNeeded' => true,
			];
		}
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithCloudLibraryAccess = $this->getCloudLibraryUsers($user);
		$driver = new CloudLibraryDriver();
		$settings = $driver->getSettings($user);

		if (count($usersWithCloudLibraryAccess) > 1) {
			$promptTitle = 'CloudLibrary Checkout Options';
			$interface->assign('useAlternateLibraryCard', $settings->useAlternateLibraryCard);
			$validCards = [];
			foreach ($usersWithCloudLibraryAccess as $userWithAccess) {
				$userAltCardOptions = $userWithAccess->getHomeLibrary()->getAlternateLibraryCardOptions();
				if ($userAltCardOptions['useAlternateLibraryCardForCloudLibrary'] && !empty($userWithAccess->alternateLibraryCard) && $driver->checkAuthentication($userWithAccess)) {
					$validCards[] = $userWithAccess;
				}
			}
			$interface->assign('validCards', $validCards);
			return [
				'promptNeeded' => true,
				'promptTitle' => $promptTitle,
				'prompts' => $interface->fetch('CloudLibrary/ajax-checkout-prompt.tpl'),
				'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate([
						'text' => 'Checkout Title',
						'isPublicFacing' => true,
						'inAttribute' => true,
					]) . '" onclick="return AspenDiscovery.CloudLibrary.processCheckoutPrompts();">',
			];
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			if ($settings->useAlternateLibraryCard && (empty($user->alternateLibraryCard) || !$driver->checkAuthentication($user))) {
				return $this->getAlternateLibraryCardPrompts('checkOutTitle', $user->id, $id);
			}
			return [
				'patronId' => reset($usersWithCloudLibraryAccess)->id,
				'promptNeeded' => false,
			];
		} else {
			$invalidAccountMessage = translate([
				'text' => "The barcode or library for this account is not valid for CloudLibrary. Please contact your local library for more information.",
				'isPublicFacing' => true,
			]);
			return [
				'promptTitle' => translate([
					'text' => 'Invalid Account',
					'isPublicFacing' => true,
				]),
				'prompts' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
				'buttons' => '',
				'promptNeeded' => true,
			];
		}
	}

	function cancelHold(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to cancel holds.');
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			return $driver->cancelHold($patron, $id);
		} else {
			return [
				'result' => false,
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to cancel holds for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function renewCheckout() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to renew titles.');
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			return $driver->renewCheckout($patron, $id);
		} else {
			return [
				'result' => false,
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function returnCheckout() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to return titles.');
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			return $driver->returnCheckout($patron, $id);
		} else {
			return [
				'result' => false,
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/**
	 * @param User $user
	 * @return User[]
	 */
	private function getCloudLibraryUsers(User $user) : array {
		global $interface;
		$users = $user->getRelatedEcontentUsers('cloud_library');
		$usersWithCloudLibraryAccess = [];
		require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
		foreach ($users as $tmpUser) {
			$usersWithCloudLibraryAccess[] = $tmpUser;
		}
		$interface->assign('users', $usersWithCloudLibraryAccess);
		return $usersWithCloudLibraryAccess;
	}

	/**
	 * @param $id
	 * @param User $patron
	 * @return array
	 */
	private function processHoldOrCheckout($id, User $patron): array {
		require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
		$driver = new CloudLibraryDriver();

		// If using alternate library cards, first check if account exists in CloudLibrary.
		$settings = $driver->getSettings($patron);
		if ($settings->useAlternateLibraryCard && !$driver->checkAuthentication($patron)) {
			$result['message'] = translate([
				'text' => 'Sorry, your alternate library card is not valid for CloudLibrary. Please check that you have entered it correctly.',
				'isPublicFacing' => true,
			]);

			$result['api']['title'] = translate([
				'text' => 'Invalid alternate library card.',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, your alternate library card is not valid for CloudLibrary. Please check that you have entered it correctly.',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		// Before placing the hold, check the status because CloudLibrary doesn't always update properly.
		$itemStatus = $driver->getItemStatus($id, $patron);
		if ($itemStatus == 'CAN_LOAN' || $itemStatus == 'RESERVATION') {
			$result = $driver->checkoutTitle($patron, $id);
			if ($result['success']) {
				/** @noinspection HtmlUnknownTarget */
				$result['title'] = translate([
					'text' => "CloudLibrary Title Checked Out Successfully",
					'isPublicFacing' => true,
				]);
				$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate([
						'text' => 'View My Check Outs',
						'isPublicFacing' => true,
					]) . '</a>';
			} else {
				$result['title'] = translate([
					'text' => "Error Checking Out Title",
					'isPublicFacing' => true,
				]);
			}
		} elseif ($itemStatus == 'CAN_HOLD') {
			$result = $driver->placeHold($patron, $id);
		} elseif ($itemStatus == 'HOLD' || $itemStatus == 'ON_HOLD') {
			$result = [
				'result' => true,
				'title' => translate([
					'text' => "Failed to Place CloudLibrary Hold",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'This title is already on hold for you.',
					'isPublicFacing' => true,
				]),
			];
		} elseif ($itemStatus == 'LOAN') {
			$result = [
				'result' => true,
				'title' => translate([
					'text' => "Failed to Check Out CloudLibrary Title",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'This title is already checked out to you.',
					'isPublicFacing' => true,
				]),
			];
		} elseif ($itemStatus == 'CAN_WISH') {
			$result = [
				'result' => true,
				'title' => translate([
					'text' => "CloudLibrary Title Unavailable",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Sorry, this title is no longer available.',
					'isPublicFacing' => true,
				]),
			];
		} elseif ($itemStatus == 'Authentication failed') {
			$result = [
				'result' => true,
				'title' => translate([
					'text' => "Failed to Authenticate with CloudLibrary",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'We were unable to authenticate your account in CloudLibrary. If this problem persists, please contact the library.',
					'isPublicFacing' => true,
				]),
			];
		} else {
			$result = [
				'result' => true,
				'title' => translate([
					'text' => "Failed to Complete CloudLibrary Checkout or Hold",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => "CloudLibrary returned an invalid item status (%1%).",
					1 => $itemStatus,
					'isPublicFacing' => true,
				]),
			];
		}
		return $result;
	}

	function getStaffView() : array {
		global $interface;
		if (!$interface->getVariable('showStaffView')) {
			$this->failureResult(null, 'Staff View is not available.');
		}
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error loading staff view',
				'isPublicFacing' => true,
			]),
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
		$recordDriver = new CloudLibraryRecordDriver($id);
		if ($recordDriver->isValid()) {
			$interface->assign('recordDriver', $recordDriver);
			$result = [
				'success' => true,
				'staffView' => $interface->fetch($recordDriver->getStaffView()),
			];
		} else {
			$result['message'] = translate([
				'text' => 'Could not find that record',
				'isPublicFacing' => true,
			]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getLargeCover() : array {
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		return [
			'title' => translate([
				'text' => 'Cover Image',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("CloudLibrary/largeCover.tpl"),
			'modalButtons' => "",
		];
	}

	/** @noinspection PhpUnused */
	function addAlternateLibraryCard(): array {
		$this->requireLoggedInUser();
		$jsonData = file_get_contents('php://input');
		$cardData = json_decode($jsonData, true);
		$user = UserAccount::getLoggedInUser();
		$user = $user->getUserReferredTo($cardData['patronId']);
		if (!$user) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Failed to add Alternate Library Card',
					'isPublicFacing' => true,
				]),
			];
		}
		if (isset($cardData['alternateLibraryCard'])) {
			$user->alternateLibraryCard = $cardData['alternateLibraryCard'];
		}
		if (isset($cardData['alternateLibraryCardPassword'])) {
			$user->alternateLibraryCardPassword = $cardData['alternateLibraryCardPassword'];
		}
		$user->update();
		if (!empty($user->alternateLibraryCard)) {
			return [
				'success' => true,
				'message' => translate([
					'text' => 'Alternate Library Card successfully added',
					'isPublicFacing' => true,
				]),
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Failed to add Alternate Library Card',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function getAlternateLibraryCardPrompts($type, $patronId, $titleId): array {
		$this->requireLoggedInUser();
		global $library;
		global $interface;
		$user = UserAccount::getLoggedInUser();
		$user = $user->getUserReferredTo($patronId);
		$interface->assign('id', $titleId);
		$interface->assign('showAlternateLibraryCard', $library->showAlternateLibraryCard);
		$interface->assign('alternateLibraryCardFormMessage', $library->alternateLibraryCardFormMessage);
		$interface->assign('showAlternateLibraryCardPassword', $library->showAlternateLibraryCardPassword);
		$interface->assign('alternateLibraryCardLabel', $library->alternateLibraryCardLabel);
		$interface->assign('alternateLibraryCardPasswordLabel', $library->alternateLibraryCardPasswordLabel);
		$interface->assign('patronId', $patronId);
		$interface->assign('type', $type);
		$interface->assign('user', $user);
		$promptTitle = translate(['text' =>'Add an Alternate Library Card', 'isPublicFacing' => true]);
		return [
			'promptNeeded' => true,
			'promptTitle' => $promptTitle,
			'prompts' => $interface->fetch('CloudLibrary/add-alternate-library-card.tpl'),
			'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate([
					'text' => 'Add Card',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]) . '" onclick="return AspenDiscovery.CloudLibrary.addAlternateLibraryCard();">',
		];
	}

	/** @noinspection PhpUnused */
	function prepareAlternateLibraryCardPrompts(): array {
		$type = $_REQUEST['type'];
		$patronId = $_REQUEST['patronId'];
		$titleId = $_REQUEST['id'];
		return $this->getAlternateLibraryCardPrompts($type, $patronId, $titleId);
	}
}