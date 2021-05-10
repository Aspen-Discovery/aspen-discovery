<?php
require_once ROOT_DIR . '/JSON_Action.php';

class RBdigital_AJAX extends JSON_Action
{
	function placeHold()
	{
		$user = UserAccount::getLoggedInUser();

		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				return $driver->placeHold($patron, $id);
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
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				$result = $driver->checkoutTitle($patron, $id);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']) {
					/** @noinspection HtmlUnknownTarget */
					$result['title'] = translate("Title Checked Out Successfully");
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate('View My Check Outs') . '</a>';
				} else {
					$result['title'] = translate("Error Checking Out Title");
				}
				return $result;
			} else {
				return array('result' => false, 'title' => translate("Error Checking Out Title"), 'message' => translate(['text' => 'no_permission_to_checkout', 'defaultText' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.']));
			}
		} else {
			return array('result' => false, 'title' => translate("Error Checking Out Title"), 'message' => translate('You must be logged in to checkout an item.'));
		}
	}

	function checkOutMagazine()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				$result = $driver->checkoutMagazine($patron, $id);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']) {
					/** @noinspection HtmlUnknownTarget */
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate('View My Check Outs') . '</a>';
				}
				return $result;
			} else {
				return array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.');
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to checkout an item.');
		}
	}

	/** @noinspection PhpUnused */
	function createAccount()
	{
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				$createAccountMessage = $driver->createAccount($patron);
				if ($createAccountMessage['success']) {
					$followupAction = $_REQUEST['followupAction'];
					if ($followupAction == 'checkout') {
						return $this->checkOutTitle();
					} elseif ($followupAction == 'checkoutMagazine') {
						return $this->checkOutMagazine();
					} else {
						return $this->placeHold();
					}
				}
				return $createAccountMessage;
			} else {
				return array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permission to create an RBdigital account for that user.');
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in prior to creating an account in RBdigital.');
		}
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$usersWithRBdigitalAccess = $this->getRBdigitalUsers($user);

		if (count($usersWithRBdigitalAccess) > 1) {
			$promptTitle = 'RBdigital Hold Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('RBdigital/ajax-hold-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Place Hold" onclick="return AspenDiscovery.RBdigital.processHoldPrompts();">'
			);
		} elseif (count($usersWithRBdigitalAccess) == 1) {
			return array(
					'patronId' => reset($usersWithRBdigitalAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No RBdigital Account Found, let the user create one if they want
			return array(
					'promptNeeded' => true,
					'promptTitle' => 'Create an Account',
					'prompts' => $interface->fetch('RBdigital/ajax-create-account-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.RBdigital.createAccount(\'hold\', \'' . $user->id . '\', \'' . $id . '\');">'
				);
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithRBdigitalAccess = $this->getRBdigitalUsers($user);

		if (count($usersWithRBdigitalAccess) > 1) {
			$promptTitle = 'RBdigital Checkout Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('RBdigital/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.RBdigital.processCheckoutPrompts();">'
				);
		} elseif (count($usersWithRBdigitalAccess) == 1) {
			return array(
					'patronId' => reset($usersWithRBdigitalAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No RBdigital Account Found, let the user create one if they want
			return array(
					'promptNeeded' => true,
					'promptTitle' => 'Create an Account',
					'prompts' => $interface->fetch('RBdigital/ajax-create-account-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.RBdigital.createAccount(\'checkout\', ' . $user->id . ', ' . $id . ');">'
				);
		}
	}

	/** @noinspection PhpUnused */
	function getMagazineCheckOutPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'magazine');

		$usersWithRBdigitalAccess = $this->getRBdigitalUsers($user);

		if (count($usersWithRBdigitalAccess) > 1) {
			$promptTitle = 'RBdigital Checkout Options';
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('RBdigital/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Magazine" onclick="return AspenDiscovery.RBdigital.processCheckoutPrompts();">'
				);
		} elseif (count($usersWithRBdigitalAccess) == 1) {
			return array(
					'patronId' => reset($usersWithRBdigitalAccess)->id,
					'promptNeeded' => false,
				);
		} else {
			// No RBdigital Account Found, let the user create one if they want
			return array(
					'promptNeeded' => true,
					'promptTitle' => 'Create an Account',
					'prompts' => $interface->fetch('RBdigital/ajax-create-account-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.RBdigital.createAccount(\'checkoutMagazine\', ' . $user->id . ', ' . $id . ');">'
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
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
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
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
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
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				return $driver->returnCheckout($patron, $id);
			} else {
				return array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.');
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to return titles.');
		}
	}

	/** @noinspection PhpUnused */
	function returnMagazine()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
				$driver = new RBdigitalDriver();
				list($magazineId, $issueId) = explode('_', $id);
				return $driver->returnMagazine($patron, $magazineId, $issueId);
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
	private function getRBdigitalUsers(User $user)
	{
		global $interface;
		$users = $user->getRelatedEcontentUsers('rbdigital');
		$usersWithRBdigitalAccess = [];
		require_once ROOT_DIR . '/Drivers/RBdigitalDriver.php';
		$driver = new RBdigitalDriver();
		foreach ($users as $tmpUser) {
			if ($driver->getRBdigitalId($tmpUser) != false) {
				$usersWithRBdigitalAccess[] = $tmpUser;
			}
		}
		$interface->assign('users', $usersWithRBdigitalAccess);
		return $usersWithRBdigitalAccess;
	}

	function getStaffView(){
		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view'
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
		$recordDriver = new RBdigitalRecordDriver($id);
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

	/** @noinspection PhpUnused */
	function getMagazineStaffView(){
		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view'
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/RBdigitalMagazineDriver.php';
		$recordDriver = new RBdigitalMagazineDriver($id);
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

	/** @noinspection PhpUnused */
	function getLargeCover()
	{
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		return array(
			'title' => 'Cover Image',
			'modalBody' => $interface->fetch("RBdigital/largeCover.tpl"),
			'modalButtons' => ""
		);
	}
}