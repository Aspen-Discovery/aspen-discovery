<?php
require_once ROOT_DIR . '/Action.php';

class RBdigital_AJAX extends Action
{

	function launch()
	{
		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

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
				$holdMessage = $driver->placeHold($patron, $id);
				return json_encode($holdMessage);
			} else {
				return json_encode(array('result' => false, 'message' => translate(['text' => 'no_permissions_for_hold', 'defaultText' => 'Sorry, it looks like you don\'t have permissions to place holds for that user.'])));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in to place a hold.'));
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
				return json_encode($result);
			} else {
				return json_encode(array('result' => false, 'title' => translate("Error Checking Out Title"), 'message' => translate(['text' => 'no_permission_to_checkout', 'defaultText' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.'])));
			}
		} else {
			return json_encode(array('result' => false, 'title' => translate("Error Checking Out Title"), 'message' => translate('You must be logged in to checkout an item.')));
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
				return json_encode($result);
			} else {
				return json_encode(array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.'));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in to checkout an item.'));
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
				return json_encode($createAccountMessage);
			} else {
				return json_encode(array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permission to create an RBdigital account for that user.'));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in prior to creating an account in RBdigital.'));
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
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('RBdigital/ajax-hold-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Place Hold" onclick="return AspenDiscovery.RBdigital.processHoldPrompts();">'
				)
			);
		} elseif (count($usersWithRBdigitalAccess) == 1) {
			return json_encode(
				array(
					'patronId' => reset($usersWithRBdigitalAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No RBdigital Account Found, let the user create one if they want
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => 'Create an Account',
					'prompts' => $interface->fetch('RBdigital/ajax-create-account-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.RBdigital.createAccount(\'hold\', \'' . $user->id . '\', \'' . $id . '\');">'
				)
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
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('RBdigital/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.RBdigital.processCheckoutPrompts();">'
				)
			);
		} elseif (count($usersWithRBdigitalAccess) == 1) {
			return json_encode(
				array(
					'patronId' => reset($usersWithRBdigitalAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No RBdigital Account Found, let the user create one if they want
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => 'Create an Account',
					'prompts' => $interface->fetch('RBdigital/ajax-create-account-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.RBdigital.createAccount(\'checkout\', ' . $user->id . ', ' . $id . ');">'
				)
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
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('RBdigital/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Magazine" onclick="return AspenDiscovery.RBdigital.processCheckoutPrompts();">'
				)
			);
		} elseif (count($usersWithRBdigitalAccess) == 1) {
			return json_encode(
				array(
					'patronId' => reset($usersWithRBdigitalAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No RBdigital Account Found, let the user create one if they want
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => 'Create an Account',
					'prompts' => $interface->fetch('RBdigital/ajax-create-account-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.RBdigital.createAccount(\'checkoutMagazine\', ' . $user->id . ', ' . $id . ');">'
				)
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
				$result = $driver->cancelHold($patron, $id);
				return json_encode($result);
			} else {
				return json_encode(array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to cancel holds for that user.'));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in to cancel holds.'));
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
				$result = $driver->renewCheckout($patron, $id);
				return json_encode($result);
			} else {
				return json_encode(array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.'));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in to renew titles.'));
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
				$result = $driver->returnCheckout($patron, $id);
				return json_encode($result);
			} else {
				return json_encode(array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.'));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in to return titles.'));
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
				list($magzineId, $issueId) = explode('_', $id);
				$result = $driver->returnMagazine($patron, $magzineId, $issueId);
				return json_encode($result);
			} else {
				return json_encode(array('result' => false, 'message' => 'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.'));
			}
		} else {
			return json_encode(array('result' => false, 'message' => 'You must be logged in to return titles.'));
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

}