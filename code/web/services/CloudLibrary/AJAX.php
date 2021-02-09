<?php
require_once ROOT_DIR . '/JSON_Action.php';

class CloudLibrary_AJAX extends JSON_Action
{
	function placeHold()
	{
		$user = UserAccount::getLoggedInUser();

		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				return $this->processHoldOrCheckout($id, $patron);
			} else {
				return array('result' => false, 'message' => translate(['text' => 'no_permissions_for_hold', 'defaultText' => 'Sorry, it looks like you don\'t have permissions to place holds for that user.']));
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to place a hold.');
		}
	}

	function checkOutTitle()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				return $this->processHoldOrCheckout($id, $patron);
			} else {
				return array('result' => false, 'title' => translate("Error Checking Out Title"), 'message' => translate(['text' => 'no_permission_to_checkout', 'defaultText' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.']));
			}
		} else {
			return array('result' => false, 'title' => translate("Error Checking Out Title"), 'message' => translate('You must be logged in to checkout an item.'));
		}
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		if (empty($user)){
			$loggedOutMessage = translate(['text' => 'login_expired', 'defaultText' => "Your login has timed out. Please login again."]);
			return array(
					'promptTitle' => translate('Invalid Account'),
					'prompts' => '<p class="alert alert-danger">' . $loggedOutMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true
				);
		}
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$usersWithCloudLibraryAccess = $this->getCloudLibraryUsers($user);

		if (count($usersWithCloudLibraryAccess) > 1) {
			$promptTitle = 'Cloud Library Hold Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('CloudLibrary/ajax-hold-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Place Hold" onclick="return AspenDiscovery.CloudLibrary.processHoldPrompts();">'
				);
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			return array(
					'patronId' => reset($usersWithCloudLibraryAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No Cloud Library Account Found
			$invalidAccountMessage = translate(['text' => 'cloud_library_invalid_account_or_library', 'defaultText' => "The barcode or library for this account is not valid for Cloud Library."]);
			return array(
					'promptTitle' => translate('Invalid Account'),
					'prompts' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true
				);
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		if (empty($user)){
			$loggedOutMessage = translate(['text' => 'login_expired', 'defaultText' => "Your login has timed out. Please login again."]);
			return array(
					'promptTitle' => translate('Invalid Account'),
					'prompts' => '<p class="alert alert-danger">' . $loggedOutMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true
				);
		}
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithCloudLibraryAccess = $this->getCloudLibraryUsers($user);

		if (count($usersWithCloudLibraryAccess) > 1) {
			$promptTitle = 'Cloud Library Checkout Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('CloudLibrary/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.CloudLibrary.processCheckoutPrompts();">'
				);
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			return array(
					'patronId' => reset($usersWithCloudLibraryAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No Cloud Library Account Found
			$invalidAccountMessage = translate(['text' => 'cloud_library_invalid_account_or_library', 'defaultText' => "The barcode or library for this account is not valid for Cloud Library."]);
			return array(
					'promptTitle' => 'Invalid Account',
					'prompts' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true,
				);
		}
	}

	function cancelHold()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				return $driver->cancelHold($patron, $id);
			} else {
				return array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to cancel holds for that user.');
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to cancel holds.');
		}
	}

	function renewCheckout()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				return $driver->renewCheckout($patron, $id);
			} else {
				return array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.');
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to renew titles.');
		}
	}

	/** @noinspection PhpUnused */
	function returnCheckout()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				return $driver->returnCheckout($patron, $id);
			} else {
				return array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.');
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to return titles.');
		}
	}

	/**
	 * @param User $user
	 * @return User[]
	 */
	private function getCloudLibraryUsers(User $user)
	{
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
	 * @return false|array
	 */
	private function processHoldOrCheckout($id, User $patron)
	{
		require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
		$driver = new CloudLibraryDriver();

		//Before we place the hold, check the status since Cloud Library doesn't always update properly
		$itemStatus = $driver->getItemStatus($id, $patron);
		if ($itemStatus == 'CAN_LOAN') {
			$result = $driver->checkoutTitle($patron, $id);
			//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
			if ($result['success']) {
				/** @noinspection HtmlUnknownTarget */
				$result['title'] = translate("Title Checked Out Successfully");
				$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate('View My Check Outs') . '</a>';
			} else {
				$result['title'] = translate("Error Checking Out Title");
			}
		} elseif ($itemStatus == 'CAN_HOLD') {
			$result = $driver->placeHold($patron, $id);
		} elseif ($itemStatus == 'HOLD' || $itemStatus == 'ON_HOLD') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'already_on_hold', 'defaultText' => 'This title is already on hold for you.'])
			];
		} elseif ($itemStatus == 'LOAN') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'already_checked_out', 'defaultText' => 'This title is already checked out to you.'])
			];
		} elseif ($itemStatus == 'CAN_WISH') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'cloud_library_not_available', 'defaultText' => 'Sorry, this title is no longer available.'])
			];
		} elseif ($itemStatus == 'Authentication failed') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'cloud_library_authentication_failed', 'defaultText' => 'We were unable to authenticate your account in Cloud Library. If this problem persists, please contact the library.'])
			];
		} else {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'invalid_status_cloud_library_w_message', 'defaultText' => "Cloud Library returned an invalid item status (%1%).", 1=>$itemStatus ])
			];
		}
		return $result;
	}

	function getStaffView(){
		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view'
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
		$recordDriver = new CloudLibraryRecordDriver($id);
		if ($recordDriver->isValid()){
			global $interface;
			$interface->assign('recordDriver', $recordDriver);
			$result = [
				'success' => true,
				'staffView' => $interface->fetch($recordDriver->getStaffView())
			];
		}else{
			$result['message'] = 'Could not find that record';
		}
		return $result;
	}
}