<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class ResetPin extends Action{
	function launch($msg = null)
	{
		global $interface;

		if (!empty($_REQUEST['resetToken'])) {
			$interface->assign('resetToken', $_REQUEST['resetToken']);
		}
		if (!empty($_REQUEST['uid'])) {
			$interface->assign('userID', $_REQUEST['uid']);
		}

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if (isset($_REQUEST['submit'])){
			$driver = $catalog->driver;
			if ($catalog->checkFunction('resetPin')) {
				$newPin        = trim($_REQUEST['pin1']);
				$confirmNewPin = trim($_REQUEST['pin2']);
				$resetToken    = $_REQUEST['resetToken'];
				$userID        = $_REQUEST['uid'];

				if (!empty($userID)) {
					$patron = new User();
					$patron->id = $userID;
					if (!$patron->find(true)){
						$barcode = $userID;
						$resetPinResult = $driver->resetPinForBarcode($barcode, $newPin, $resetToken);
						// Did not find a matching user to the uid
						// This check could be optional if the resetPin method verifies that the ILS user matches the Aspen Discovery user.
						$resetPinResult = array(
							'error' => 'Invalid parameter. Your Pin can not be reset'
						);
					} elseif (empty($newPin)) {
						$resetPinResult = array(
							'error' => 'Please enter a new Pin number.'
						);
					} elseif (empty($confirmNewPin)) {
						$resetPinResult = array(
							'error' => 'Please confirm your new Pin number.'
						);
					} elseif ($newPin !== $confirmNewPin) {
						$resetPinResult = array(
							'error' => 'The new Pin numbers you entered did not match. Please try again.'
						);
					} elseif (empty($resetToken) || empty($userID)) {
						// These checks is for Horizon Driver, this may need to be moved into resetPin function if used for another ILS
						$resetPinResult = array(
							'error' => 'Required parameter missing. Your Pin can not be reset.'
						);
					} else {
						$resetPinResult = $driver->resetPin($patron, $newPin, $resetToken);
					}
				}
			}else{
				$resetPinResult = array(
					'error' => 'This functionality is not available in the ILS.',
				);
			}
			$interface->assign('resetPinResult', $resetPinResult);
			$this->display('resetPinResults.tpl', 'Reset My Pin');
		}else{
			$pinValidationRules = $catalog->getPasswordPinValidationRules();
			$interface->assign('pinValidationRules', $pinValidationRules);
			$this->display('resetPin.tpl', 'Reset My Pin');
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Reset PIN');
		return $breadcrumbs;
	}
}