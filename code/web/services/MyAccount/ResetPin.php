<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class ResetPin extends Action {
	function launch($msg = null) {
		global $interface;

		if (!empty($_REQUEST['resetToken'])) {
			$interface->assign('resetToken', $_REQUEST['resetToken']);
		}
		if (!empty($_REQUEST['uid'])) {
			$interface->assign('userID', $_REQUEST['uid']);
		}

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if (isset($_REQUEST['pin1']) && isset($_REQUEST['pin2'])) {
			$driver = $catalog->driver;
			if ($catalog->checkFunction('resetPin')) {
				$newPin = trim($_REQUEST['pin1']);
				$confirmNewPin = trim($_REQUEST['pin2']);
				$resetToken = $_REQUEST['resetToken'];
				$userID = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : null;

				if (!empty($userID)) {
					$patron = new User();
					$patron->id = $userID;
					if (!$patron->find(true)) {
						// Did not find a matching user to the uid
						$patron = null;
					}
				} else {
					$patron = null;
				}
				if (empty($newPin)) {
					$resetPinResult = [
						'error' => 'Please enter a new Pin number.',
					];
				} elseif (empty($confirmNewPin)) {
					$resetPinResult = [
						'error' => 'Please confirm your new Pin number.',
					];
				} elseif ($newPin !== $confirmNewPin) {
					$resetPinResult = [
						'error' => 'The new Pin numbers you entered did not match. Please try again.',
					];
				} else {
					$resetPinResult = $driver->resetPin($patron, $newPin, $resetToken);
				}
			} else {
				$resetPinResult = [
					'error' => 'This functionality is not available in the ILS.',
				];
			}
			$interface->assign('resetPinResult', $resetPinResult);
			$this->display('resetPinResults.tpl', 'Reset My Pin');
		} else {
			$pinValidationRules = $catalog->getPasswordPinValidationRules();
			$interface->assign('pinValidationRules', $pinValidationRules);
			$this->display('resetPin.tpl', 'Reset My Pin');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Reset PIN');
		return $breadcrumbs;
	}
}