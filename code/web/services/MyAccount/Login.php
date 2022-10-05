<?php

require_once ROOT_DIR . "/Action.php";

class MyAccount_Login extends Action
{
	function launch($msg = null)
	{
		global $interface;
		global $module;
		global $action;
		global $library;
		global $locationSingleton;
		global $configArray;

		// Assign the followup task to come back to after they login -- note that
		//     we need to check for a pre-existing followup task in case we've
		//     looped back here due to an error (bad username/password, etc.).

		$followupAction = isset($_REQUEST['followupAction']) ? strip_tags($_REQUEST['followupAction']) : $action;
		$followupModule = isset($_REQUEST['followupModule']) ? strip_tags($_REQUEST['followupModule']) : $module;

		// We should never access this module directly -- this is called by other
		// actions as a support function.  If accessed directly, just redirect to
		// the MyAccount home page.
		if (!isset($_REQUEST['followupModule']) && $module == 'MyAccount' && $action == 'Login') {
			header('Location: /MyAccount/Home');
			die();
		}

		// Don't go to the trouble if we're just logging in to the Home action
		if (!($followupAction == 'Home' && $followupModule == 'MyAccount')) {
			$interface->assign('followupModule', $followupModule);
			$interface->assign('followupAction', $followupAction);

			$pageId = isset($_REQUEST['pageId']) ? strip_tags($_REQUEST['pageId']) : '';
			$interface->assign('pageId', $pageId);

			$recordId = isset($_REQUEST['id']) ? strip_tags($_REQUEST['id']) : '';
			$interface->assign('recordId', $recordId);

			// comments need to be preserved if present
			if (isset($_REQUEST['comment'])) {
				$interface->assign('comment', $_REQUEST['comment']);
			}

			// preserve card Number for Masquerading
			if (isset($_REQUEST['cardNumber'])) {
				$interface->assign('cardNumber', $_REQUEST['cardNumber']);
				$interface->assign('followupModule', 'MyAccount');
				$interface->assign('followupAction', 'Masquerade');
			}

		}
		$interface->assign('message', $msg);
		if (isset($_REQUEST['username'])) {
			$interface->assign('username', $_REQUEST['username']);
		}
		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('selfRegistrationUrl', $library->selfRegistrationUrl);
		$interface->assign('checkRememberMe', 0);
		if ($library->defaultRememberMe && $locationSingleton->getOpacStatus() == false) {
			$interface->assign('checkRememberMe', 1);
		}
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

		//SSO
		$loginOptions = 0;
		if ($library->ssoSettingId != -1) {
			require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
			$sso = new \SSOSetting();
			$sso->id = $library->ssoSettingId;
			if ($sso->find(true)) {
				$loginOptions = $sso->loginOptions;
				$interface->assign('ssoLoginHelpText', $sso->loginHelpText);
				$interface->assign('ssoService', $sso->service);
				if ($sso->service == "oauth") {
					$interface->assign('oAuthGateway', $sso->oAuthGateway);
					if ($sso->oAuthGateway == "custom") {
						$interface->assign('oAuthCustomGatewayLabel', $sso->oAuthGatewayLabel);
						$interface->assign('oAuthButtonBackgroundColor', $sso->oAuthButtonBackgroundColor);
						$interface->assign('oAuthButtonTextColor', $sso->oAuthButtonTextColor);
						if ($sso->oAuthGatewayIcon) {
							$interface->assign('oAuthCustomGatewayIcon', $configArray['Site']['url'] . '/files/original/' . $sso->oAuthGatewayIcon);
						}
					}
				}
			}
		}

		$loginOptions = isset($_REQUEST['showBoth']) ? 0 : $loginOptions;

		$interface->assign('ssoLoginOptions', $loginOptions);

		//SAML
		if (isset($library->ssoXmlUrl)) {
			$interface->assign('ssoXmlUrl', $library->ssoXmlUrl);
		}
		$interface->assign('ssoName', isset($library->ssoName) ? $library->ssoName : 'single sign-on');
		if (!empty($library->loginNotes)) {
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$parsedown->setBreaksEnabled(true);
			$loginNotes = $parsedown->parse($library->loginNotes);
			$interface->assign('loginNotes', $loginNotes);
		}

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if ($catalog != null) {
			$interface->assign('forgotPasswordType', $catalog->getForgotPasswordType());
			if (!$library->enableForgotPasswordLink) {
				$interface->assign('forgotPasswordType', 'none');
			}
		} else {
			$interface->assign('forgotPasswordType', 'none');
		}

		$interface->assign('isLoginPage', true);

		if ($msg === 'You must authenticate before logging in. Please provide the 6-digit code that was emailed to you.') {
			require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
			$twoFactorAuthCode = new TwoFactorAuthCode();
			$twoFactorAuthCode->createCode();
			$this->display('../MyAccount/login-2fa.tpl', 'Login', '');
		} elseif ($msg === 'You must enroll into two-factor authentication before logging in.') {
			$this->display('../MyAccount/login-2fa-enroll.tpl', 'Login', '');
		} else {
			$this->display('../MyAccount/login.tpl', 'Login', '');
		}

	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Login');
		return $breadcrumbs;
	}
}

