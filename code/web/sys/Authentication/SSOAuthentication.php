<?php
require_once 'Authentication.php';

class SSOAuthentication implements Authentication {
	/** @var  AccountProfile */
	private AccountProfile $accountProfile;
	private SSOSetting $ssoSettings;

	public function __construct($additionalInfo) {
		$this->accountProfile = $additionalInfo['accountProfile'];
		$this->ssoSettings = $this->getSSOSettings();
	}

	public function authenticate($validatedViaSSO, $accountProfile) {
		if(!$validatedViaSSO) {
			if ($this->ssoSettings->service === 'ldap' && (!isset($_POST['username']) || !isset($_POST['password']))) {
				return new AspenError('Login information cannot be blank during LDAP authentication.');
			}
			return $this->login($_POST['username'], $_POST['password'], $accountProfile);
		} else {
			return $this->getValidatedAspenUser($_REQUEST['username'], $accountProfile);
		}
	}

	public function validateAccount($username, $password, $accountProfile, $parentAccount = null, $validatedViaSSO = false) {
		return $this->login($username, $password, $accountProfile);
	}

	private function login($username, $password, AccountProfile $accountProfile) {
		global $logger;
		if ((($username == '') || ($password == '')) && $this->ssoSettings->service === 'ldap') {
			return new AspenError('Login information cannot be blank for LDAP login.');
		} else {
			if($this->ssoSettings->service) {
				if ($this->ssoSettings->service === 'ldap') {
					$logger->log('Initiating logging the user in via LDAP', Logger::LOG_NOTICE);
					$_SESSION['ssoAuthSession'] = true;
					require_once ROOT_DIR . '/sys/Authentication/LDAPAuthentication.php';
					$ldapAuthentication = new LDAPAuthentication();
					return $ldapAuthentication->validateAccount($username, $password, $accountProfile);
				}

				// We probably won't get oAuth/SAML requests from the AuthenticationFactory
				if ($this->ssoSettings->service === 'oauth') {
					$logger->log('Sending user to oAuth for authentication...', Logger::LOG_NOTICE);
					$_SESSION['ssoAuthSession'] = true;
					header('Location: /init_oauth.php');
					exit();
				}

				if ($this->ssoSettings->service === 'saml') {
					$logger->log('Sending user to SAML for authentication...', Logger::LOG_NOTICE);
					$_SESSION['ssoAuthSession'] = true;
					header('Location: /saml2auth.php?samlLogin=y&idp=' . $this->ssoSettings->ssoEntityId);
					exit();
				}
			}
			return new AspenError('Valid SSO Settings were not provided');
		}
	}

	private function getValidatedAspenUser($username, AccountProfile $accountProfile) {
		require_once ROOT_DIR . '/sys/Account/User.php';
		$tmpUser = new User();
		$tmpUser->username = $username;
		$tmpUser->unique_ils_id = $username;
		$tmpUser->source = $accountProfile->name;
		if($tmpUser->find(true)) {
			return $tmpUser;
		}
		return new AspenError('Unable to validate Aspen user ' . $username);
	}

	private function getSSOSettings(): SSOSetting {
		require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
		global $library;
		$ssoSetting = new SSOSetting();
		if($library->ssoSettingId !== -1) {
			$ssoSetting->id = $this->accountProfile->ssoSettingId;
			if($ssoSetting->find(true)){
				return $ssoSetting;
			}
		} else {
			$ssoSetting->id = $library->ssoSettingId;
			if($ssoSetting->find(true)){
				return $ssoSetting;
			}
		}
		return $ssoSetting;
	}
}