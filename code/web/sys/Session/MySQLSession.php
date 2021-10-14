<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

class MySQLSession extends SessionInterface
{
	public function open($sess_path, $sess_name) {
		global $logger;
		$logger->log("Opening session ({$_SERVER['REQUEST_URI']})", Logger::LOG_DEBUG);
		//Delete any sessions where remember me was false
		$s = new Session();
		$earliestValidSession = time() - self::$lifetime;
		$s->remember_me = '0';
		$s->whereAdd('last_used < ' . $earliestValidSession);
		$s->delete(true);
		//Delete any sessions where remember me was true
		$s2 = new Session();
		$earliestValidRememberMeSession = time() - self::$rememberMeLifetime;
		$s2->remember_me = '1';
		$s2->whereAdd('last_used < ' . $earliestValidRememberMeSession);
		$numRememberMeDeleted = $s2->delete(true);
		return true;
	}

	public function read($sess_id)
	{
		$s = new Session();
		$s->session_id = $sess_id;

		if ($s->find(true)) {
			global $logger;
			$logger->log("Reading existing session $sess_id", Logger::LOG_DEBUG);
			return $s->data;
		}else{
			return "";
		}
	}

	/**
	 * @param $sess_id
	 * @param $data
	 * @return bool
	 */
	public function write($sess_id, $data)
	{
		global $logger;
		global $module;
		global $action;
		if ($module == 'AJAX' || $action == 'AJAX' || $action == 'JSON') {
			//Don't update sessions on AJAX and JSON calls
			if (isset($_REQUEST['method'])) {
				$method = $_REQUEST['method'];
				if ($method != 'loginUser'
					&& $method != 'login'
					&& $method != 'initiateMasquerade'
					&& $method != 'endMasquerade'
					&& $method != 'lockFacet'
					&& $method != 'unlockFacet'
					&& !isset($_REQUEST['showCovers'])
					&& !isset($_REQUEST['sort'])
					&& !isset($_REQUEST['availableHoldSort'])
					&& !isset($_REQUEST['unavailableHoldSort'])
					&& !isset($_REQUEST['autologout'])) {
					$logger->log("Not updating session $sess_id $module $action $method", Logger::LOG_DEBUG);
					return true;
				}
			} else {
				$logger->log("Not updating session $sess_id, no method provided", Logger::LOG_DEBUG);
				return true;
			}
		}

		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)){
			$logger->log("Updating session $sess_id {$_SERVER['REQUEST_URI']}", Logger::LOG_DEBUG);
			$s->data = $data;
			$s->last_used = time();
			if (isset($_REQUEST['rememberMe']) && ($_REQUEST['rememberMe'] == true || $_REQUEST['rememberMe'] === "true")) {
				$s->remember_me = 1;
			}
			$result = $s->update();
		}else{
			$logger->log("Inserting new session $sess_id", Logger::LOG_DEBUG);
			$s->data = $data;
			$s->created = date('Y-m-d h:i:s');
			$s->last_used = time();
			$s->remember_me = 0;
			$result = $s->insert();
			//Don't bother to count sessions that are from bots.
			global $isAJAX;
			if (!BotChecker::isRequestFromBot() && !$isAJAX) {
				global $aspenUsage;
				$aspenUsage->sessionsStarted++;
				if (!empty($aspenUsage->id)) {
					$aspenUsage->update();
				}
			}
		}
		$logger->log(" Result = $result", Logger::LOG_DEBUG);
		return true;
	}

	public function destroy($sess_id)
	{
		// Now do database-specific destruction:
		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)){
			global $logger;
			$logger->log("Destroying session $sess_id {$_SERVER['REQUEST_URI']}", Logger::LOG_DEBUG);
			// Perform standard actions required by all session methods:
			parent::destroy($sess_id);

			$numDeleted = $s->delete();
			return $numDeleted == 1;
		}else{
			global $logger;
			$logger->log("Session $sess_id has already been destroyed {$_SERVER['REQUEST_URI']}", Logger::LOG_DEBUG);
			//Already deleted
			return false;
		}

	}

	public function gc($sess_maxlifetime)
	{
		//Do nothing here, delete old sessions in Java Cron
		return true;
	}

}