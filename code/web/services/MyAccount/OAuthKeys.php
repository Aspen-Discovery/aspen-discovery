<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class MyAccount_OAuthKeys extends MyAccount {
	function launch() {
		global $interface;

		$user = UserAccount::getLoggedInUser();

		if ($user) {
			require_once ROOT_DIR . '/sys/Account/UserOAuthKey.php';

			if (!UserOAuthKey::isOAuthEnabled()) {
				$interface->assign('error', 'OAuth key management is not enabled on this system.');
			} elseif (!$user->hasPermission('Use API Keys')) {
				$interface->assign('error', 'You do not have permission to manage API keys.');
			} else {
				$oauthKey = new UserOAuthKey();
				$oauthKey->userId = $user->id;
				$oauthKey->orderBy('created DESC');

				$keys = [];
				if ($oauthKey->find()) {
					while ($oauthKey->fetch()) {
						$keys[] = clone $oauthKey;
					}
				}

				$interface->assign('oauthKeys', $keys);
			}

			$this->display('oauthKeys.tpl', 'API Keys');
		} else {
			$this->display('../MyAccount/login.tpl', 'Login');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'API Keys');
		return $breadcrumbs;
	}
}
