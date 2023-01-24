<?php
require_once ROOT_DIR . '/sys/Authentication/OAuthAuthentication.php';

class Authentication_OAuth extends Action {
	/**
	 * @throws UnknownAuthenticationMethodException
	 */
	public function launch() {
		global $logger;
		$logger->log("Completing OAuth Authentication", Logger::LOG_ERROR);
		try {
			$auth = new OAuthAuthentication();
			$result = $auth->verifyIdToken($_REQUEST);
			if ($result['success']) {
				$logger->log(print_r($result, true), Logger::LOG_ERROR);
				if (!empty($result['returnTo'])) {
					header('Location: ' . $result['returnTo']);
				} else {
					header('Location: /MyAccount/Home');
				}
			} else {
				$errorMessage = $result['message'];
				require_once ROOT_DIR . '/services/MyAccount/Login.php';
				$launchAction = new MyAccount_Login();
				$launchAction->launch($errorMessage);
				exit();
			}
		}catch (UnknownAuthenticationMethodException $e) {
			$errorMessage = $result['message'];
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$launchAction = new MyAccount_Login();
			$launchAction->launch("Could not initialize authentication");
			exit();
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}

}