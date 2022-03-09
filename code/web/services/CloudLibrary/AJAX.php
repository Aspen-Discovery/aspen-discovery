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
				return array('result' => false, 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to place holds for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text' => 'You must be logged in to place a hold.', 'isPublicFacing'=>true]));
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
				return array('result' => false, 'title' => translate(['text' => "Error Checking Out Title", 'isPublicFacing'=>true]), 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'title' => translate(['text' => "Error Checking Out Title", 'isPublicFacing'=>true]), 'message' => translate(['text' => 'You must be logged in to checkout an item.', 'isPublicFacing'=>true]));
		}
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		if (empty($user)){
			$loggedOutMessage = translate(['text' => "Your login has timed out. Please login again.", 'isPublicFacing'=>true]);
			return array(
					'promptTitle' => translate(['text' => 'Invalid Account', 'isPublicFacing'=>true]),
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
			$promptTitle = 'cloudLibrary Hold Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('CloudLibrary/ajax-hold-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate(['text' => 'Place Hold', 'isPublicFacing'=>true, 'inAttribute'=>true]) . '" onclick="return AspenDiscovery.CloudLibrary.processHoldPrompts();">'
				);
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			return array(
					'patronId' => reset($usersWithCloudLibraryAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No cloudLibrary Account Found
			$invalidAccountMessage = translate(['text' => "The barcode or library for this account is not valid for cloudLibrary. Please contact your local library for more information.", 'isPublicFacing'=>true]);
			return array(
					'promptTitle' => translate(['text' => 'Invalid Account', 'isPublicFacing'=>true]),
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
			$loggedOutMessage = translate(['text' => "Your login has timed out. Please login again.", 'isPublicFacing'=>true]);
			return array(
					'promptTitle' => translate(['text' => 'Invalid Account', 'isPublicFacing'=>true]),
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
			$promptTitle = 'cloudLibrary Checkout Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('CloudLibrary/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate(['text' => 'Checkout Title', 'isPublicFacing'=>true, 'inAttribute'=>true]) . '" onclick="return AspenDiscovery.CloudLibrary.processCheckoutPrompts();">'
				);
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			return array(
					'patronId' => reset($usersWithCloudLibraryAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No cloudLibrary Account Found
			$invalidAccountMessage = translate(['text' => "The barcode or library for this account is not valid for cloudLibrary. Please contact your local library for more information.", 'isPublicFacing'=>true]);
			return array(
					'promptTitle' => translate(['text' => 'Invalid Account', 'isPublicFacing'=>true]),
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
				return array('result' => false, 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to cancel holds for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text' => 'You must be logged in to cancel holds.', 'isPublicFacing'=>true]));
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
				return array('result' => false, 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text' => 'You must be logged in to renew titles.', 'isPublicFacing'=>true]));
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
				return array('result' => false, 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text' => 'You must be logged in to return titles.', 'isPublicFacing'=>true]));
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

		//Before we place the hold, check the status since cloudLibrary doesn't always update properly
		$itemStatus = $driver->getItemStatus($id, $patron);
		if ($itemStatus == 'CAN_LOAN' || $itemStatus == 'RESERVATION') {
			$result = $driver->checkoutTitle($patron, $id);
			//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
			if ($result['success']) {
				/** @noinspection HtmlUnknownTarget */
				$result['title'] = translate(['text' => "Title Checked Out Successfully", 'isPublicFacing'=>true]);
				$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate(['text'=>'View My Check Outs', 'isPublicFacing'=>true]) . '</a>';
			} else {
				$result['title'] = translate(['text' => "Error Checking Out Title", 'isPublicFacing'=>true]);
			}
		} elseif ($itemStatus == 'CAN_HOLD') {
			$result = $driver->placeHold($patron, $id);
		} elseif ($itemStatus == 'HOLD' || $itemStatus == 'ON_HOLD') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'This title is already on hold for you.', 'isPublicFacing'=>true])
			];
		} elseif ($itemStatus == 'LOAN') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'This title is already checked out to you.', 'isPublicFacing'=>true])
			];
		} elseif ($itemStatus == 'CAN_WISH') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'Sorry, this title is no longer available.', 'isPublicFacing'=>true])
			];
		} elseif ($itemStatus == 'Authentication failed') {
			$result = [
				'result' => true,
				'message' => translate(['text' => 'We were unable to authenticate your account in cloudLibrary. If this problem persists, please contact the library.', 'isPublicFacing'=>true])
			];
		} else {
			$result = [
				'result' => true,
				'message' => translate(['text' => "cloudLibrary returned an invalid item status (%1%).", 1=>$itemStatus, 'isPublicFacing'=>true ])
			];
		}
		return $result;
	}

	function getStaffView(){
		$result = [
			'success' => false,
			'message' => translate(['text' => 'Unknown error loading staff view', 'isPublicFacing'=>true])
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
			$result['message'] = translate(['text' => 'Could not find that record', 'isPublicFacing'=>true]);
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getLargeCover(){
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		return array(
			'title' => translate(['text'=>'Cover Image', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("CloudLibrary/largeCover.tpl"),
			'modalButtons' => ""
		);
	}
}