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
						$session->delete();

						// create a new shared session
						$data = random_bytes(16);
						assert(strlen($data) == 16);
						$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
						$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
						$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
						require_once ROOT_DIR . '/sys/Session/SharedSession.php';
						$sharedSession = new SharedSession();
						$sharedSession->setSessionId($uuid);
						$sharedSession->setUserId($_REQUEST['user']);
						$sharedSession->setCreated(strtotime('now'));

						if (UserAccount::findNewAspenUser('id', $_REQUEST['user'])) {
							$tmpUser = new User();
							$tmpUser->id = $_REQUEST['user'];
							if ($tmpUser->find(true)) {
								$session->redirectUser($tmpUser, $returnTo);
							}
						}
					}
				} else {
					// no matching shared session found, ask the user to log into Discovery
					header('Location: /MyAccount/' . $returnTo . '?minimalInterface=true');
				}
			} else {
				// not enough data provided, ask the user to log into Discovery
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