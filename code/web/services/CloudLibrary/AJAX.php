<?php
require_once ROOT_DIR . '/Action.php';

/** @noinspection PhpUnused */

class CloudLibrary_AJAX extends Action
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
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
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
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
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

	/** @noinspection PhpUnused */
	function getHoldPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		if (empty($user)){
			$loggedOutMessage = translate(['text' => 'cloud_library_logged_out', 'defaultText' => "Your login has timed out. Please login again."]);
			return json_encode(
				array(
					'promptTitle' => translate('Invalid Account'),
					'prompts' => '<p class="alert alert-danger">' . $loggedOutMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true
				)
			);
		}
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$usersWithCloudLibraryAccess = $this->getCloudLibraryUsers($user);

		if (count($usersWithCloudLibraryAccess) > 1) {
			$promptTitle = 'Cloud Library Hold Options';
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('CloudLibrary/ajax-hold-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Place Hold" onclick="return AspenDiscovery.CloudLibrary.processHoldPrompts();">'
				)
			);
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			return json_encode(
				array(
					'patronId' => reset($usersWithCloudLibraryAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No Cloud Library Account Found
			$invalidAccountMessage = translate(['text' => 'cloud_library_invalid_account_or_library', 'defaultText' => "The barcode or library for this account is not valid for Cloud Library."]);
			return json_encode(
				array(
					'promptTitle' => translate('Invalid Account'),
					'prompts' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true
				)
			);
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		if (empty($user)){
			$loggedOutMessage = translate(['text' => 'cloud_library_logged_out', 'defaultText' => "Your login has timed out. Please login again."]);
			return json_encode(
				array(
					'promptTitle' => translate('Invalid Account'),
					'prompts' => '<p class="alert alert-danger">' . $loggedOutMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true
				)
			);
		}
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithCloudLibraryAccess = $this->getCloudLibraryUsers($user);

		if (count($usersWithCloudLibraryAccess) > 1) {
			$promptTitle = 'Cloud Library Checkout Options';
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('CloudLibrary/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.CloudLibrary.processCheckoutPrompts();">'
				)
			);
		} elseif (count($usersWithCloudLibraryAccess) == 1) {
			return json_encode(
				array(
					'patronId' => reset($usersWithCloudLibraryAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No Cloud Library Account Found
			$invalidAccountMessage = translate(['text' => 'cloud_library_invalid_account_or_library', 'defaultText' => "The barcode or library for this account is not valid for Cloud Library."]);
			return json_encode(
				array(
					'promptTitle' => 'Invalid Account',
					'prompts' => '<p class="alert alert-danger">' . $invalidAccountMessage . '</p>',
					'buttons' => '',
					'promptNeeded' => true,
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
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
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
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
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
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				$result = $driver->returnCheckout($patron, $id);
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
}