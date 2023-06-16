<?php

require_once ROOT_DIR . '/Action.php';

class MyAccount_Logout extends Action {

	public function launch() {
		if(UserAccount::isLoggedInViaSSO()) {
			global $library;
			require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
			$ssoSettings = new SSOSetting();
			$ssoSettings->id = $library->ssoSettingId;
			if ($ssoSettings->find(true)) {
				if($ssoSettings->service == 'saml') {
					UserAccount::logout();
					session_write_close();
					header('Location: ' . $ssoSettings->ssoSPLogoutUrl);
					die();
				} else {
					if ($ssoSettings->ssoSPLogoutUrl) {
						$_REQUEST['return'] = $ssoSettings->ssoSPLogoutUrl;
					}
				}
			}
		}

		UserAccount::logout();
		session_write_close();

		if(isset($_REQUEST['return'])) {
			header('Location: ' . $_REQUEST['return']);
			die();
		} else {
			header('Location: /');
			die();
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}