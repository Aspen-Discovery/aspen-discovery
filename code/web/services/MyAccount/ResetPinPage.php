<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_ResetPinPage extends MyAccount {
	function launch() {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			$pinValidationRules = $user->getPasswordPinValidationRules();
			$interface->assign('pinValidationRules', $pinValidationRules);

			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($user);
			if ($patronHomeLibrary == null) {
				$allowPinReset = false;
			} else {
				$allowPinReset = ($patronHomeLibrary->allowPinReset == 1);
			}

			$interface->assign('allowPinReset', $allowPinReset);
			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$result = $user->updatePin();
				$user->updateMessage = $result['message'];
				$user->updateMessageIsError = !$result['success'];
				$user->update();
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			if (!empty($user->updateMessage)) {
				if ($user->updateMessageIsError) {
					$interface->assign('profileUpdateErrors', $user->updateMessage);
				} else {
					$interface->assign('profileUpdateMessage', $user->updateMessage);
				}
				$user->updateMessage = '';
				$user->updateMessageIsError = 0;
				$user->update();
			}

			$interface->assign('profile', $user);
			$interface->assign('barcodePin', $user->getAccountProfile()->loginConfiguration == 'barcode_pin');
		}

		$this->display('resetPinPage.tpl', 'Reset PIN/Password');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Reset PIN');
		return $breadcrumbs;
	}
}