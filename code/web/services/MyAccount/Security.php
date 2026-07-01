<?php

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class Security extends MyAccount {
	function launch() : void {
		global $interface;

		$twoFactor = UserAccount::has2FAEnabledForPType();
		$interface->assign('twoFactorEnabled', $twoFactor);
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();

			$twoFactorAuthSetting = $user->getTwoFactorAuthenticationSetting();
			if ($twoFactorAuthSetting != null) {
				$isEnabled = $twoFactorAuthSetting->isEnabled;
				if ($isEnabled != 'notAvailable') {
					$interface->assign('twoFactorStatus', (int)$user->twoFactorStatus);
					$interface->assign('twoFactorMethodSetup', $user->twoFactorMethod);
					$interface->assign('allowEmail2FA', $twoFactorAuthSetting->allowEmail);
					$interface->assign('allowTOTP2FA', $twoFactorAuthSetting->allowTotp);
					$interface->assign('showBackupCodes', false);
					$interface->assign('userHasTOTP', $user->twoFactorMethod == 'totp');
					$userHasEmailCodes = false;

					$emailCode = new TwoFactorAuthCode();
					$emailCode->userId = $user->id;
					$emailCode->status = 'backup';
					if ($emailCode->find(true)) {
						$userHasEmailCodes = true;
					}

					$interface->assign('userHasEmailCodes', $userHasEmailCodes);

					if ($user->twoFactorStatus == '1') {
						$interface->assign('showBackupCodes', true);
						require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
						require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';
						$backupCode = new TwoFactorAuthCode();
						$backupCodes = $backupCode->getBackups();
						$numBackupCodes = count($backupCodes);
						$interface->assign('backupCodes', $backupCodes);
						$interface->assign('numBackupCodes', $numBackupCodes);
					}

					$twoFactorData = UserAccount::get2FAMethodStatus();
					$interface->assign('setupMethods', $twoFactorData['setupMethods']);
					$interface->assign('showSetupEmail', $twoFactorData['showSetupEmail']);
					$interface->assign('showSetupTotp', $twoFactorData['showSetupTotp']);
					$interface->assign('showDisableEmail', $twoFactorData['showDisableEmail']);
					$interface->assign('showDisableTotp', $twoFactorData['showDisableTotp']);
					$interface->assign('canDisableEmail', $twoFactorData['canDisableEmail']);
					$interface->assign('canDisableTotp', $twoFactorData['canDisableTotp']);
					$interface->assign('requiredSetupWarning', $twoFactorData['requiredSetupWarning']);

				}
			}
		}

		$this->display('securityPage.tpl', 'Security');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Security');
		return $breadcrumbs;
	}
}