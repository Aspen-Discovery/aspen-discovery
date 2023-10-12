<?php
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class WebBuilder_QuickPoll extends Action {
	private $quickPoll;

	function launch() {
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
		$this->quickPoll = new QuickPoll();
		$this->quickPoll->id = $id;
		if (!$this->quickPoll->find(true)) {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		if (!UserAccount::isLoggedIn()) {
			if (!$this->quickPoll->requireLogin) {
				require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
				$recaptcha = new RecaptchaSetting();
				if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
					$captchaCode = recaptcha_get_html($recaptcha->publicKey, $this->quickPoll->id);
					$interface->assign('captcha', $captchaCode);
					$interface->assign('captchaKey', $recaptcha->publicKey);
				}
			} else {
				//Display a message that the user must be logged in
				require_once ROOT_DIR . '/services/MyAccount/Login.php';
				$myAccountAction = new MyAccount_Login();
				$myAccountAction->launch();
				exit();
			}
		}
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		$introText = $parsedown->parse($this->quickPoll->introText);

		$interface->assign('introText', $introText);
		$interface->assign('poll', $this->quickPoll);
		$interface->assign('pollOptions', $this->quickPoll->getPollOptions());

		$this->display('quickPoll.tpl', $this->quickPoll->title, '', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->quickPoll->title, true);
		if (UserAccount::userHasPermission([
			'Administer All Custom Forms',
			'Administer Library Custom Forms',
		])) {
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPolls?id=' . $this->quickPoll->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}