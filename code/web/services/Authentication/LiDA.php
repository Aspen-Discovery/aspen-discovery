<?php
require_once ROOT_DIR . '/sys/Session/SharedSession.php';

class Authentication_LiDA extends Action {
	/**
	 * @throws UnknownAuthenticationMethodException
	 */
	public function launch() {
		if (isset($_GET['init'])) {
			global $logger;
			$logger->log('Starting LiDA Authentication', Logger::LOG_ERROR);

			$returnTo = $_REQUEST['goTo'] ?? 'Home';

			if(isset($_REQUEST['session']) && isset($_REQUEST['user'])) {
				$session = new SharedSession();
				$session->setSessionId($_REQUEST['session']);
				$session->setUserId($_REQUEST['user']);
				if($session->find(true)) {
					if($session->isSessionStillValid()) {
						if (UserAccount::findNewAspenUser('id', $_REQUEST['user'])) {
							$tmpUser = new User();
							$tmpUser->id = $_REQUEST['user'];
							if ($tmpUser->find(true)) {
								$session->redirectUser($tmpUser, $returnTo);
							}
						}
					} else {

					}
				} else {
					// ask the user to log into Discovery
					header('Location: /MyAccount/' . $returnTo . '?minimalInterface=true');
				}
			} else {
				// ask the user to log into Discovery
				header('Location: /MyAccount/' . $returnTo . '?minimalInterface=true');
			}
		} else {
			// probably ended up here by mistake
			header('Location: /');
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}