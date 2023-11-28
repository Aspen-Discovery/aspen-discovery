<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

class MySQLSession extends SessionInterface {
	public function open($sess_path, $sess_name) {
		return true;
	}

	public function read($sess_id) {
		$s = new Session();
		$s->setSessionId($sess_id);

		if ($s->find(true)) {
			//global $logger;
			//$logger->log("Reading existing session $sess_id", Logger::LOG_DEBUG);
			if ($s->getRememberMe() == '1') {
				$earliestValidSession = time() - self::$rememberMeLifetime;
			} else {
				$earliestValidSession = time() - self::$lifetime;
			}
			if ($s->getLastUsed() < $earliestValidSession) {
				return "";
			}
			SessionInterface::$activeSessionObject = $s;
			return $s->getData();
		} else {
			return "";
		}
	}

	/**
	 * @param $sess_id
	 * @param $data
	 * @return bool
	 */
	public function write($sess_id, $data) : bool{
		//global $logger;
		global $module;
		global $action;
		if ($module == 'AJAX' || $action == 'AJAX' || $action == 'JSON') {
			//Don't update sessions on AJAX and JSON calls
			if (isset($_REQUEST['method'])) {
				$method = $_REQUEST['method'];
				if ($method != 'loginUser' && $method != 'login' && $method != 'initiateMasquerade' && $method != 'endMasquerade' && $method != 'lockFacet' && $method != 'unlockFacet' && $method != 'updateDisplaySettings' && !isset($_REQUEST['showCovers']) && !isset($_REQUEST['sort']) && !isset($_REQUEST['availableHoldSort']) && !isset($_REQUEST['unavailableHoldSort']) && !isset($_REQUEST['autologout'])) {
					//$logger->log("Not updating session $sess_id $module $action $method", Logger::LOG_DEBUG);
					return true;
				}
			} else {
				//$logger->log("Not updating session $sess_id, no method provided", Logger::LOG_DEBUG);
				return true;
			}
		}

		$s = new Session();
		$s->setSessionId($sess_id);
		if ($s->find(true)) {
			//$logger->log("Updating session $sess_id {$_SERVER['REQUEST_URI']}", Logger::LOG_DEBUG);
			$s->setData($data);
			$s->setLastUsed(time());
			if (empty($s->getRememberMe())) {
				if (isset($_REQUEST['rememberMe']) && ($_REQUEST['rememberMe'] === true || $_REQUEST['rememberMe'] === "true")) {
					$s->setRememberMe(1);
				} else {
					$activeUser = UserAccount::getActiveUserObj();
					if (!empty($activeUser)) {
						if ($activeUser->bypassAutoLogout) {
							$s->setRememberMe(1);
						}
					}
				}
			}
			$s->update();
			$result = true;
		} else {
			//$logger->log("Inserting new session $sess_id", Logger::LOG_DEBUG);
			$s->setData($data);
			$s->setCreated(date('Y-m-d h:i:s'));
			$s->setLastUsed(time());
			$s->setRememberMe(0);
			$result = $s->insert();
			//Don't bother to count sessions that are from bots.
			global $isAJAX;
			if (!BotChecker::isRequestFromBot() && !$isAJAX) {
				global $aspenUsage;
				$aspenUsage->__set('sessionsStarted', $aspenUsage->__get('sessionsStarted') + 1);
				if (!empty($aspenUsage->__get('id'))) {
					$aspenUsage->update();
				} else {
					$aspenUsage->insert();
				}
			}
		}
		//$logger->log(" Result = $result", Logger::LOG_DEBUG);
		return $result;
	}

	public function destroy($sess_id) {
		// Now do database-specific destruction:
		$s = new Session();
		$s->setSessionId($sess_id);
		if ($s->find(true)) {
			//global $logger;
			//$logger->log("Destroying session $sess_id {$_SERVER['REQUEST_URI']}", Logger::LOG_DEBUG);
			// Perform standard actions required by all session methods:
			parent::destroy($sess_id);

			$numDeleted = $s->delete();
			return $numDeleted == 1;
		} else {
			global $logger;
			$logger->log("Session $sess_id has already been destroyed {$_SERVER['REQUEST_URI']}", Logger::LOG_DEBUG);
			//Already deleted
			return false;
		}

	}

	public function gc($sess_maxlifetime) {
		$s = new Session();
		$earliestValidSession = time() - self::$lifetime;
		$s->setRememberMe('0');
		$s->whereAdd('last_used < ' . $earliestValidSession);
		$s->delete(true);
		//Delete any sessions where remember me was true
		$s2 = new Session();
		$earliestValidRememberMeSession = time() - self::$rememberMeLifetime;
		$s2->setRememberMe ('1');
		$s2->whereAdd('last_used < ' . $earliestValidRememberMeSession);
		$numRememberMeDeleted = $s2->delete(true);

		return true;
	}
}