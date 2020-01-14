<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_ResetPinPage extends MyAccount
{
	function launch()
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			/** @var Library $librarySingleton */
			global $librarySingleton;
			// Get Library Settings from the home library of the current user-account being displayed
			$patronHomeLibrary = $librarySingleton->getPatronHomeLibrary($user);
			if ($patronHomeLibrary == null){
				$allowPinReset = false;
			}else{
				$allowPinReset = ($patronHomeLibrary->allowPinReset == 1);
			}

			$interface->assign('allowPinReset', $allowPinReset);
			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['updateScope']) && !$offlineMode) {
				$updateResult = $user->updatePin();
				if (!$updateResult['success']){
					$interface->assign('profileUpdateErrors', $updateResult['errors']);
				}else{
					$interface->assign('profileUpdateMessage', $updateResult['message']);
				}
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			if (!empty($_SESSION['profileUpdateErrors'])) {
				$interface->assign('profileUpdateErrors', $_SESSION['profileUpdateErrors']);
				@session_start();
				unset($_SESSION['profileUpdateErrors']);
			}
			if (!empty($_SESSION['profileUpdateMessage'])) {
				$interface->assign('profileUpdateMessage', $_SESSION['profileUpdateMessage']);
				@session_start();
				unset($_SESSION['profileUpdateMessage']);
			}

			$interface->assign('profile', $user); //
			$interface->assign('barcodePin', $user->getAccountProfile()->loginConfiguration == 'barcode_pin');
		}

		$this->display('resetPinPage.tpl', 'Reset PIN/Password');
	}

}