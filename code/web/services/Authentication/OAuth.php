<?php

require_once 'bootstrap.php';
require_once ROOT_DIR . '/sys/Authentication/OAuthAuthentication.php';

class Authentication_OAuth extends Action {
	/**
	 * @throws UnknownAuthenticationMethodException
	 */
	public function launch() {
		global $logger;
		global $interface;
		$logger->log("Completing OAuth Authentication", Logger::LOG_ERROR);
		$auth = new OAuthAuthentication();
		$result = $auth->verifyIdToken($_REQUEST);
		if ($result['success']) {
			$logger->log(print_r($result, true), Logger::LOG_ERROR);
			if (isset($result['returnTo'])) {
				header('Location: ' . $result['returnTo']);
			}
			else {
				header('Location: /MyAccount/Home');
			}
		}
		else {
			$interface->assign('error', $result['error']);
			$interface->assign('message', $result['message']);
			$this->display('../MyAccount/login.tpl', 'Login', '');
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}

}