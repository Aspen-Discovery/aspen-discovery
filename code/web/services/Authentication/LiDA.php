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
			$id = $_REQUEST['id'] ?? null;
			$logger->log('Return to: ' . $returnTo, Logger::LOG_ERROR);
			$logger->log($_REQUEST, Logger::LOG_ERROR);

			if (isset($_REQUEST['session']) && isset($_REQUEST['user'])) {
				$session = new SharedSession();
				$session->setSessionId($_REQUEST['session']);
				$session->setUserId($_REQUEST['user']);
				if ($session->find(true)) {
					if ($session->isSessionStillValid()) {
						if (UserAccount::findNewAspenUser('id', $_REQUEST['user'])) {
							$tmpUser = new User();
							$tmpUser->id = $_REQUEST['user'];
							if ($tmpUser->find(true)) {
								$session->redirectUser($tmpUser, $returnTo, $id);
							}
						}
					} else {
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
						$sharedSession->insert();

						if (UserAccount::findNewAspenUser('id', $_REQUEST['user'])) {
							$tmpUser = new User();
							$tmpUser->id = $_REQUEST['user'];
							if ($tmpUser->find(true)) {
								$session->redirectUser($tmpUser, $returnTo, $id);
							}
						}
					}
				} else {
					// no matching shared session found, we just redirect them to the requested page and will be asked to log in when necessary
					if ($returnTo === 'GroupedWork' && $id) {
						header('Location: /GroupedWork/' . $id . '/Home/?minimalInterface=true');
					}else if ($returnTo === 'NewMaterialRequest') {
						$url = 'Location: /MaterialsRequest/NewRequest?minimalInterface=true';
						if (isset($_REQUEST['title'])) {
							$url .= '&title=' . urlencode($_REQUEST['title']);
						}
						if (isset($_REQUEST['author'])) {
							$url .= '&author=' . urlencode($_REQUEST['author']);
						}
						if (isset($_REQUEST['volume'])) {
							$url .= '&volume=' . urlencode($_REQUEST['volume']);
						}
						header($url);
					}else if ($returnTo === 'NewMaterialRequestIls') {
						$url = 'Location: /MaterialsRequest/NewRequestIls?minimalInterface=true';
						if (isset($_REQUEST['title'])) {
							$url .= '&title=' . urlencode($_REQUEST['title']);
						}
						if (isset($_REQUEST['author'])) {
							$url .= '&author=' . urlencode($_REQUEST['author']);
						}
						if (isset($_REQUEST['volume'])) {
							$url .= '&volume=' . urlencode($_REQUEST['volume']);
						}
						header($url);
					} else {
						header('Location: /MyAccount/' . $returnTo . '?minimalInterface=true');
					}
				}
			} else {
				// not enough data provided, we just redirect them to the requested page and will be asked to log in when necessary
				if ($returnTo === 'GroupedWork' && $id) {
					header('Location: /GroupedWork/' . $id . '/Home/?minimalInterface=true');
				}else if ($returnTo === 'NewMaterialRequest') {
					header('Location: /MaterialsRequest/NewRequest?minimalInterface=true');
				}else if ($returnTo === 'NewMaterialRequestIls') {
					header('Location: /MaterialsRequest/NewRequestIls?minimalInterface=true');
				} else {
					header('Location: /MyAccount/' . $returnTo . '?minimalInterface=true');
				}
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