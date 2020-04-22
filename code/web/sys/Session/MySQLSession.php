<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

class MySQLSession extends SessionInterface
{
	public function open($sess_path, $sess_name) {
		global $logger;
		$logger->log("Opening session ", Logger::LOG_DEBUG);
		//Delete any sessions where remember me was false
		$s = new Session();
		$earliestValidSession = time() - self::$lifetime;
		$s->remember_me = '0';
		$s->whereAdd('last_used < ' . $earliestValidSession);
		$s->delete(true);
		//Delete any sessions where remember me was true
		$s2 = new Session();
		$earliestValidSession = time() - self::$rememberMeLifetime;
		$s2->remember_me = 1;
		$s2->whereAdd('last_used < ' . $earliestValidSession);
		$s2->delete(true);
		return true;
	}

	public function read($sess_id)
	{
		$s = new Session();
		$s->session_id = $sess_id;

		if ($s->find(true)) {
			global $logger;
			$logger->log("Reading existing session $sess_id", Logger::LOG_ALERT);
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
					&& !isset($_REQUEST['unavailableHoldSort'])) {
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
			$logger->log("Updating session $sess_id", Logger::LOG_DEBUG);
			$s->data = $data;
			$s->last_used = time();
			$result = $s->update();
		}else{
			$logger->log("Inserting new session $sess_id", Logger::LOG_DEBUG);
			$s->data = $data;
			$s->created = date('Y-m-d h:i:s');
			$s->last_used = time();
			if (isset($_SESSION['rememberMe']) && ($_SESSION['rememberMe'] == true || $_SESSION['rememberMe'] === "true")) {
				$s->remember_me = 1;
			}else{
				$s->remember_me = 0;
			}
			$result = $s->insert();
		}
		$logger->log(" Result = $result", Logger::LOG_DEBUG);
		return $result == 1;
	}

	public function destroy($sess_id)
	{
		// Now do database-specific destruction:
		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)){
			global $logger;
			$logger->log("Destroying session $sess_id", Logger::LOG_DEBUG);
			// Perform standard actions required by all session methods:
			parent::destroy($sess_id);

			$numDeleted = $s->delete();
			return $numDeleted == 1;
		}else{
			global $logger;
			$logger->log("Session $sess_id has already been destroyed", Logger::LOG_DEBUG);
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