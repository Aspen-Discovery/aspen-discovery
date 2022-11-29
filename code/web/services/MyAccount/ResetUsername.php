<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_ResetUsername extends MyAccount
{
	function launch()
	{
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			$usernameValidationRules = $user->getUsernameValidationRules();
			$interface->assign('usernameValidationRules', $usernameValidationRules);

			// Save/Update Actions
			global $offlineMode;
			if (isset($_POST['submit']) && !$offlineMode) {
				$newUsername = $_REQUEST['username'];
				$result = $user->updateEditableUsername($newUsername);
				$user->updateMessage = $result['message'];
				$user->updateMessageIsError = !$result['success'];
				$user->update();
			} elseif (!$offlineMode) {
				$interface->assign('edit', true);
			} else {
				$interface->assign('edit', false);
			}

			if (!empty($user->updateMessage)) {
				if ($user->updateMessageIsError){
					$interface->assign('profileUpdateErrors', $user->updateMessage);
				}else{
					$interface->assign('profileUpdateMessage', $user->updateMessage);
				}
				$user->updateMessage = '';
				$user->update();
			}

			$interface->assign('profile', $user);
		}

		$this->display('resetUsername.tpl', 'Reset Username');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Reset Username');
		return $breadcrumbs;
	}
}