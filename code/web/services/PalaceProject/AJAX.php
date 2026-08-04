<?php
require_once ROOT_DIR . '/JSON_Action.php';

class PalaceProject_AJAX extends JSON_Action {
	function launch($method = null): void {
		$this->checkRequiredModule('Palace Project');
		parent::launch($method);
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
		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$recordDriver = new PalaceProjectRecordDriver($id);
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
	function getPreview() : array {
		$result = [
			'success' => false,
			'message' => 'Unknown error loading preview',
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$recordDriver = new PalaceProjectRecordDriver($id);
		if ($recordDriver->isValid()) {
			$linkUrl = $recordDriver->getPreviewUrl();
			if ($linkUrl != null) {
				$result['success'] = true;
				$result['title'] = translate([
					'text' => 'Preview',
					'isPublicFacing' => true,
					'isAdminEnteredData' => true,
				]);
				$sampleUrl = $linkUrl;

//				$palaceProjectDriver = new PalaceProjectDriver();
//				$palaceProjectDriver->incrementStat('numPreviews');

				$result['modalBody'] = "<iframe src='$sampleUrl' class='previewFrame'></iframe>";
				$result['modalButtons'] = "<a class='tool btn btn-primary' id='viewPreviewFullSize' href='$sampleUrl' target='_blank' aria-label='".translate([
						'text' => 'View Full Screen',
						'isPublicFacing' => true,
						'inAttribute' => true,
					])." (".translate(['text' => 'opens in a new window', 'isPublicFacing' => true, 'inAttribute' => true]).")'>" . translate([
						'text' => "View Full Screen",
						'isPublicFacing' => true,
					]) . "</a>";
			} else {
				$result['message'] = 'No preview found for this title';
			}
		} else {
			$result['message'] = 'The specified Palace Project Product was not valid';
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
			'modalBody' => $interface->fetch("PalaceProject/largeCover.tpl"),
			'modalButtons' => "",
		];
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts() : array {
		$this->requireLoggedInUser();
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithPalaceProjectAccess = $this->getPalaceProjectUsers($user);

		if (count($usersWithPalaceProjectAccess) > 1) {
			$promptTitle = translate([
				'text' => 'Palace Project Checkout Options',
				'isPublicFacing' => true,
			]);
			return [
				'promptNeeded' => true,
				'promptTitle' => $promptTitle,
				'prompts' => $interface->fetch('PalaceProject/ajax-checkout-prompt.tpl'),
				'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate([
						'text' => 'Checkout Title',
						'inAttribute' => true,
						'isPublicFacing' => true,
					]) . '" onclick="return AspenDiscovery.PalaceProject.processCheckoutPrompts();">',
			];
		} elseif (count($usersWithPalaceProjectAccess) == 1) {
			return [
				'patronId' => reset($usersWithPalaceProjectAccess)->id,
				'promptNeeded' => false,
			];
		} else {
			// No Palace Project Account Found, let the user create one if they want
			return [
				'promptNeeded' => true,
				'promptTitle' => translate([
					'Error',
					'isPublicFacing' => true,
				]),
				'prompts' => translate([
					'Your account is not valid for Palace Project, please contact your local library.',
					'isPublicFacing' => true,
				]),
				'buttons' => '',
			];
		}
	}

	/**
	 * @param User $user
	 * @return User[]
	 */
	private function getPalaceProjectUsers(User $user) : array {
		global $interface;
		$users = $user->getRelatedEcontentUsers('palace_project');
		$usersWithPalaceProjectAccess = [];
		foreach ($users as $tmpUser) {
			$usersWithPalaceProjectAccess[] = $tmpUser;
		}
		$interface->assign('users', $usersWithPalaceProjectAccess);
		return $usersWithPalaceProjectAccess;
	}

	function checkOutTitle() : array {
		$this->requireLoggedInUser("Error Checking Out Title", 'You must be logged in to checkout an item.');

		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
			$driver = new PalaceProjectDriver();
			$result = $driver->checkoutTitle($patron, $id);
			//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
			if ($result['success']) {
				/** @noinspection HtmlUnknownTarget */
				if ($result['checkedOut']) {
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate([
							'text' => 'View My Check Outs',
							'isPublicFacing' => true,
						]) . '</a>';
				}else{
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/Holds" role="button">' . translate([
							'text' => 'View My Holds',
							'isPublicFacing' => true,
						]) . '</a>';
				}

			} else {
				$result['title'] = translate([
					'text' => "Error Checking Out Title",
					'isPublicFacing' => true,
				]);
			}
			return $result;
		} else {
			return [
				'result' => false,
				'title' => translate([
					'text' => "Error Checking Out Title",
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
	function returnCheckout() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to return titles.');

		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
			$driver = new PalaceProjectDriver();
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

	/** @noinspection PhpUnused */
	function getHoldPrompts() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to return titles.');

		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$usersWithPalaceProjectAccess = $this->getPalaceProjectUsers($user);

		if (count($usersWithPalaceProjectAccess) == 1) {
			$interface->assign('patronId', reset($usersWithPalaceProjectAccess)->id);
		}

		if (count($usersWithPalaceProjectAccess) == 0) {
			// No Boundless Account Found, let the user create one if they want
			return [
				'success' => false,
				'promptNeeded' => true,
				'promptTitle' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'prompts' => translate([
					'text' => 'Your account is not valid for Palace Project, please contact your local library.',
					'isPublicFacing' => true,
				]),
				'buttons' => '',
			];
		} elseif (count($usersWithPalaceProjectAccess) > 1) {
			$promptTitle = translate([
				'text' => 'Palace Project Hold Options',
				'isPublicFacing' => true,
			]);
			return [
				'success' => true,
				'promptNeeded' => true,
				'promptTitle' => translate([
					'text' => $promptTitle,
					'isPublicFacing' => true,
				]),
				'prompts' => $interface->fetch('PalaceProject/ajax-hold-prompt.tpl'),
				'buttons' => '<button class="btn btn-primary" type="submit" name="submit" onclick="return AspenDiscovery.PalaceProject.processHoldPrompts();">' . translate([
						'text' => 'Place Hold',
						'isPublicFacing' => true,
					]) . '</button>',
			];
		} else {
			return [
				'success' => true,
				'patronId' => reset($usersWithPalaceProjectAccess)->id,
				'promptNeeded' => false,
			];
		}
	}

	function placeHold() : array {
		$this->requireLoggedInUser(null, 'You must be logged in to place a hold.');

		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
			$driver = new PalaceProjectDriver();
			$result = $driver->placeHold($patron, $id);
			//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
			if ($result['success']) {
				/** @noinspection HtmlUnknownTarget */
				if ($result['checkedOut']) {
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate([
							'text' => 'View My Check Outs',
							'isPublicFacing' => true,
						]) . '</a>';
				}else{
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/Holds" role="button">' . translate([
							'text' => 'View My Holds',
							'isPublicFacing' => true,
						]) . '</a>';
				}
			} else {
				$result['title'] = translate([
					'text' => "Error Checking Out Title",
					'isPublicFacing' => true,
				]);
			}
			return $result;
		} else {
			return [
				'result' => false,
				'title' => translate([
					'text' => "Error Checking Out Title",
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	function cancelHold(): array {
		$this->requireLoggedInUser(null, 'You must be logged in to cancel a hold.');

		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];

		$patronId = $_REQUEST['patronId'];
		$patron = $user->getUserReferredTo($patronId);
		if ($patron) {
			require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
			$driver = new PalaceProjectDriver();
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

	/** @noinspection PhpUnused */
	function getUsageInstructions() : array {
		require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
		$driver = new PalaceProjectDriver();
		$usageInstructions = $driver->getUsageInstructions();
		if ($usageInstructions === false) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error loading instructions',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to find instructions for using Palace Project titles.',
					'isPublicFacing' => true,
				]),
			];
		} else{
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Using Palace Project',
					'isPublicFacing' => true,
				]),
				'message' => $usageInstructions,
			];
		}
	}
}