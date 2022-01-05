<?php

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class Security extends MyAccount{
	function launch()
	{
		global $interface;

		$twoFactor = UserAccount::has2FAEnabledForPType();
		$interface->assign('twoFactorEnabled', $twoFactor);
		$user = new User();
		$user->id = UserAccount::getActiveUserId();
		if($user->find(true)){
			require_once ROOT_DIR . '/sys/Account/PType.php';
			$patronType = new PType();
			$patronType->pType = $user->patronType;
			if($patronType->find(true)) {
				require_once  ROOT_DIR . '/sys/TwoFactorAuthSetting.php';
				$twoFactorAuthSetting = new TwoFactorAuthSetting();
				$twoFactorAuthSetting->id = $patronType->twoFactorAuthSettingId;
				if($twoFactorAuthSetting->find(true)) {
					$isEnabled = $twoFactorAuthSetting->isEnabled;
					if($isEnabled != 'notAvailable') {
						$interface->assign('twoFactorStatus', $user->twoFactorStatus);
						$interface->assign('showBackupCodes', false);
						$interface->assign('enableDeactivation', true);
						if($user->twoFactorStatus == '1') {
							$interface->assign('showBackupCodes', true);
							require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
							$backupCode = new TwoFactorAuthCode();
							$backupCodes = $backupCode->getBackups();
							$numBackupCodes = count($backupCodes);
							$interface->assign('backupCodes', $backupCodes);
							$interface->assign('numBackupCodes', $numBackupCodes);
							if($isEnabled == 'mandatory') {
								$interface->assign('enableDeactivation', false);
							}
						}

					}
				}
			}
		}

		$this->display('securityPage.tpl', 'Security');
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'My Account');
		$breadcrumbs[] = new Breadcrumb('', 'Security');
		return $breadcrumbs;
	}
}