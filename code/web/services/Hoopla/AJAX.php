<?php
require_once ROOT_DIR . '/JSON_Action.php';

class Hoopla_AJAX extends JSON_Action {
	function launch($method = null): void {
		$this->checkRequiredModule('Hoopla');
		parent::launch($method);
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to checkout an item.');
		$this->checkRequiredParameters(['id']);
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if (str_contains($id, ':')) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		$hooplaType = $_REQUEST['hooplaType'];

		$hooplaUsers = $user->getRelatedEcontentUsers('hoopla');

		require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
		$driver = new HooplaDriver();

		global $interface;
		$interface->assign('hooplaId', $id);

		//TODO: need to determine what happens to cards without a Hoopla account
		$hooplaUserStatuses = [];
		foreach ($hooplaUsers as $tmpUser) {
			$checkOutStatus = $driver->getAccountSummary($tmpUser);
			$hooplaUserStatuses[$tmpUser->id] = $checkOutStatus;
		}

		if (count($hooplaUsers) > 1) {
			// For multiple users, show the checkout prompt according to the hooplaType
			$interface->assign('hooplaUsers', $hooplaUsers);
			$interface->assign('hooplaUserStatuses', $hooplaUserStatuses);
			$interface->assign('hooplaType', $hooplaType);

			return [
				'title' => translate([
					'text' => 'Hoopla Check Out',
					'isPublicFacing' => true,
				]),
				'body' => $interface->fetch('Hoopla/ajax-checkout-prompt.tpl'),
				'buttons' => '<button class="btn btn-primary" type= "button" title="Check Out" onclick="return AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $id . '\', $(\'#patronId\').val(), \'' . $hooplaType . '\');">' . translate([
						'text' => 'Check Out',
						'isPublicFacing' => true,
					]) . '</button>',
			];
		} elseif (count($hooplaUsers) == 1) {
			// Single user
			$hooplaUser = reset($hooplaUsers);
			if ($hooplaUser->id != $user->id) {
				$interface->assign('hooplaUser', $hooplaUser); // Display the account name when not using the main user
			}
			$checkOutStatus = $hooplaUserStatuses[$hooplaUser->id];
			if (!$checkOutStatus) {
				// This block is currently unused since checkOutStatus always has a value even if patron is not registered
				// Keeping it here for potential future use cases
				require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
				$hooplaRecord = new HooplaRecordDriver($id);

				// Base Hoopla Title View Url
				$accessLink = $hooplaRecord->getAccessLink();
				$hooplaRegistrationUrl = $accessLink['url'];
				$hooplaRegistrationUrl .= (parse_url($hooplaRegistrationUrl, PHP_URL_QUERY) ? '&' : '?') . 'showRegistration=true'; // Add Registration URL parameter

				return [
					'title' => translate([
						'text' => 'Create Hoopla Account',
						'isPublicFacing' => true,
					]),
					'body' => $interface->fetch('Hoopla/ajax-hoopla-single-user-checkout-prompt.tpl'),
					'buttons' => '<button id="theHooplaButton" class="btn btn-default" type="button" title="Check Out" onclick="return AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $id . '\', ' . ', \'' . $hooplaType . '\');">' . translate([
							'text' => 'I registered, Check Out now',
							'isPublicFacing' => true,
						]) . '</button>' . '<a class="btn btn-primary" role="button" href="' . $hooplaRegistrationUrl . '" target="_blank" title="Register at Hoopla" aria-label="Register at Hoopla ('.translate(['text' => 'opens in a new window', 'isPublicFacing' => true, 'inAttribute' => true]) .')" onclick="$(\'#theHooplaButton+a,#theHooplaButton\').toggleClass(\'btn-primary btn-default\');">' . translate([
							'text' => 'Register at Hoopla',
							'isPublicFacing' => true,
						]) . '</a>',
				];
			}
			if ($hooplaUser->hooplaCheckOutConfirmation && $hooplaType == 'Instant') {
				// Instant titles require a prompt to show the remaining checkouts
				$interface->assign('hooplaPatronStatus', $checkOutStatus);
				/** @noinspection CommaExpressionJS */
				return [
					'title' => translate([
						'text' => 'Confirm Hoopla Check Out',
						'isPublicFacing' => true,
					]),
					'body' => $interface->fetch('Hoopla/ajax-hoopla-single-user-checkout-prompt.tpl'),
					'buttons' => '<button class="btn btn-primary" type="button" title="Check Out" onclick="return AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $id . '\', ' . $hooplaUser->id . ', \'' . $hooplaType . '\')">' . translate([
							'text' => 'Check Out',
							'isPublicFacing' => true,
						]) . '</button>',
				];
			} else {
				// Flex titles can be checked out directly
				return [
					'flexDirectCheckout' => true,
					'patronId' => $hooplaUser->id,
					'id' => $id,
					'hooplaType' => $hooplaType
				];
			}
		} else {
			// No Hoopla Account Found, give the user an error message
			$invalidAccountMessage = translate([
				'text' => 'The barcode or library for this account is not valid for Hoopla. Please contact your local library for more information.',
				'isPublicFacing' => true,
			]);
			global $logger;
			$logger->log('No valid Hoopla account was found to check out a Hoopla title.', Logger::LOG_ERROR);
			return [
				'title' => translate([
					'text' => 'Invalid Hoopla Account',
					'isPublicFacing' => true,
				]),
				'body' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
				'buttons' => '',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to place a hold.');
		$this->checkRequiredParameters(['id']);

		$user = UserAccount::getLoggedInUser();

		$id = $_REQUEST['id'];
		$hooplaUsers = $user->getRelatedEcontentUsers('hoopla');

		global $interface;
		$interface->assign('hooplaId', $id);

		$driver = new HooplaDriver();
		$holdQueueSize = $driver->getHoldQueueSize($id, $user->getHomeLibrary()->libraryId);
		$interface->assign('holdQueueSize', $holdQueueSize);
		if (count($hooplaUsers) > 0) {
			$interface->assign('hooplaUsers', $hooplaUsers);
			if (count($hooplaUsers) == 1) {
				$singleUser = reset($hooplaUsers);
				$interface->assign('singleUser', $singleUser);
				if (!$singleUser->hooplaHoldQueueSizeConfirmation) {
					return [
						'success' => true,
						'promptNeeded' => false,
						'patronId' => $singleUser->id
					];
				}
				$buttonOnClick = "return AspenDiscovery.Hoopla.doHold('" . $singleUser->id . "', '" . $id . "');";
			} else {
				$buttonOnClick = "return AspenDiscovery.Hoopla.doHold($('#patronId').val(), '" . $id . "');";
			}

			return [
				'success' => true,
				'promptNeeded' => true,
				'promptTitle' => translate(['text' => 'Place Hoopla Flex Hold', 'isPublicFacing' => true]),
				'prompts' => $interface->fetch('Hoopla/ajax-hold-prompt.tpl'),
				'buttons' => '<button class="btn btn-primary" onclick="' . $buttonOnClick . '">' . translate(['text' => 'Place Hold', 'isPublicFacing' => true]) . '</button>'
			];
		} else {
			$invalidAccountMessage = translate([
				'text' => 'The barcode or library for this account is not valid for Hoopla. Please contact your local library for more information.',
				'isPublicFacing' => true,
			]);
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Invalid Hoopla Account',
					'isPublicFacing' => true,
				]),
				'body' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
				'buttons' => '',
			];
		}

	}

	/** @noinspection PhpUnused */
	function placeHold() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to place a hold.');
		$this->checkRequiredParameters(['id']);

		$user = UserAccount::getLoggedInUser();
		$patronId = !empty($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
		$id = $_REQUEST['id'];
		$patron = $user->getUserReferredTo($patronId);

		if ($patron) {
			if (isset($_REQUEST['stopHooplaHoldConfirmation'])) {
				$patron->hooplaHoldQueueSizeConfirmation = 0;
				$patron->update();
			}
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			return $driver->placeHold($patron, $id);
		} else {
			return [
				'success' => false,
				'message' => translate(['text' => 'Invalid patron selected', 'isPublicFacing' => true])
			];
		}
	}

	function cancelHold() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to cancel a hold.');
		$this->checkRequiredParameters(['recordId']);
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			return $driver->cancelHold($patron, $id);
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to cancel holds for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function checkOutHooplaTitle() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to checkout an item.');
		$this->checkRequiredParameters(['id']);
		$user = UserAccount::getLoggedInUser();

		$patronId = !empty($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;

		$hooplaType = $_REQUEST['hooplaType'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			global $interface;
			if ($patron->id != $user->id) {
				$interface->assign('hooplaUser', $patron); // Display the account name when not using the main user
			}

			if (isset($_REQUEST['stopHooplaConfirmation'])) {
				$patron->hooplaCheckOutConfirmation = 0;
				$patron->update();
			}

			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$result = $driver->checkOutTitle($patron, $id);
			if ($result['success']) {
				$checkOutStatus = $driver->getAccountSummary($patron);
				$interface->assign('hooplaPatronStatus', $checkOutStatus);
				$interface->assign('hooplaType', $hooplaType);
				$title = empty($result['title']) ? translate([
					'text' => "Title checked out successfully",
					'isPublicFacing' => true,
				]) : translate([
					'text' => "%1% checked out successfully",
					1 => $result['title'],
					'isPublicFacing' => true,
				]);
				/** @noinspection HtmlUnknownTarget */
				return [
					'success' => true,
					'title' => $title,
					'message' => $interface->fetch('Hoopla/hoopla-checkout-success.tpl'),
					'buttons' => '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate([
							'text' => 'View My Check Outs',
							'isPublicFacing' => true,
						]) . '</a>',
				];
			} else {
				return $result;
			}
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function returnCheckout() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to return a checkout.');
		$this->checkRequiredParameters(['id']);
		$user = UserAccount::getLoggedInUser();

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			$id = $_REQUEST['id'];
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			return $driver->returnCheckout($patron, $id);
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to return titles for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
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
			'modalBody' => $interface->fetch("Hoopla/largeCover.tpl"),
			'modalButtons' => "",
		];
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
		require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
		$recordDriver = new HooplaRecordDriver($id);
		if ($recordDriver->isValid()) {
			global $interface;
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

	function getBreadcrumbs(): array {
		return [];
	}
}