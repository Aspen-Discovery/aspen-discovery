<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

class MySQLSession extends SessionInterface
{
	static public function open($sess_path, $sess_name) {
		global $logger;
		//$logger->log("Opening session ", Logger::LOG_ALERT);
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

	static public function read($sess_id)
	{
		$s = new Session();
		$s->session_id = $sess_id;

		if ($s->find(true)) {
			//$logger->log("Reading existing session $sess_id", Logger::LOG_ALERT);
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
	static public function write($sess_id, $data)
	{
//		global $logger;
//		$logger->log("Writing session $sess_id", Logger::LOG_ALERT);
		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)){
			$s->data = $data;
			$s->last_used = time();
			$result = $s->update();
		}else{
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
		return $result;
//		global $logger;
//		global $module;
//		global $action;
//		if (isset($_REQUEST['method'])) {
//			$method = $_REQUEST['method'];
//		}else{
//			$method = '';
//		}
//		if ($module == 'AJAX' || $action == 'AJAX' || $action == 'JSON') {
//			//Don't update sessions on AJAX and JSON calls
//			////TODO: Make sure this doesn't break anything
//			if (isset($_REQUEST['method'])) {
//				$method = $_REQUEST['method'];
//				if ($method != 'loginUser'
//					&& $method != 'login'
//					&& $method != 'initiateMasquerade'
//					&& $method != 'endMasquerade'
//					&& $method != 'lockFacet'
//					&& $method != 'unlockFacet'
//					&& !isset($_REQUEST['showCovers'])
//					&& !isset($_REQUEST['sort'])
//					&& !isset($_REQUEST['availableHoldSort'])
//					&& !isset($_REQUEST['unavailableHoldSort'])) {
//					$logger->log("Not updating session $sess_id $module $action $method", Logger::LOG_DEBUG);
//					return true;
//				}
//			} else {
//				$logger->log("Not updating session $sess_id, no method provided", Logger::LOG_ERROR);
//				return true;
//			}
//
//		}
//		if (MySQLSession::$active_session->session_id != $sess_id) {
//			echo("Session id changed since load time");
//			die();
//		}
//
//		$s = MySQLSession::$active_session;
//		//$logger->log("Saving session for " . $_SERVER['REQUEST_URI'] . " {$s->last_used}, " . MySQLSession::$sessionStartTime, Logger::LOG_DEBUG);
//		if ($s->data != $data) {
//			$s->data = $data;
//			$s->last_used = MySQLSession::$sessionStartTime;
//			$logger->log("Session data changed $sess_id {$s->last_used} $module $action $method: " . print_r($data, true), Logger::LOG_DEBUG);
//		//}else{
//		//	$logger->log("Not updating session $sess_id, the session data has not changed", Logger::LOG_ERROR);
//		}
//		if (isset($_SESSION['rememberMe']) && ($_SESSION['rememberMe'] == true || $_SESSION['rememberMe'] === "true")) {
//			$s->remember_me = 1;
//		}
//		parent::write($sess_id, $data);
//		$ret = $s->update();
//		return $ret;
	}

	static public function destroy($sess_id)
	{
		global $logger;
		$logger->log("Destroying session $sess_id", Logger::LOG_DEBUG);
		// Perform standard actions required by all session methods:
		parent::destroy($sess_id);

		// Now do database-specific destruction:
		$s = new Session();
		$s->session_id = $sess_id;
		$numDeleted = $s->delete();
		return $numDeleted == 1;
	}

	static public function gc($sess_maxlifetime)
	{
		//Do nothing here, delete old sessions in Java Cron
		return true;
	}

}