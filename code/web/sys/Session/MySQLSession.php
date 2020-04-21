<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/sys/Session/Session.php';

class MySQLSession extends SessionInterface
{
	static $sessionStartTime;
	/** @var Session */
	static $active_session;

	static public function read($sess_id)
	{
		global $logger;
		$s = new Session();
		$s->session_id = $sess_id;

		$curTime = time();
		//$logger->log("Loading session $sess_id for " . $_SERVER['REQUEST_URI'] . " $curTime", Logger::LOG_DEBUG);
		$createSession = false;
		if ($s->find(true)) {
			//First check to see if the session expired
			MySQLSession::$sessionStartTime = $curTime;
			if ($s->remember_me == 1) {
				$sessionExpirationTime = $s->last_used + self::$rememberMeLifetime;
			} else {
				$sessionExpirationTime = $s->last_used + self::$lifetime;
			}
			if ($curTime > $sessionExpirationTime) {
				//Clear any previously saved data
				$s->data = '';
				$s->delete();
				$_SESSION = array();
				$createSession = true;
			} else {
				// updated the session in the database to show that we just used it
				MySQLSession::$active_session = $s;
				//Update the cookie to extend session time as well
				if ($s->remember_me) {
					setcookie(session_name(), session_id(), time() + self::$rememberMeLifetime, '/');
				}
			}
		} else {
			$createSession = true;
		}
		if ($createSession) {
			$s = new Session();
			$s->session_id = $sess_id;
			//There is no active session, we need to create a new one.
			$s->last_used = MySQLSession::$sessionStartTime;
			// in date format - easier to read
			$s->created = date('Y-m-d h:i:s');
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true) {
				$s->remember_me = 1;
			} else {
				$s->remember_me = 0;
			}
			$s->data = '';
			MySQLSession::$active_session = $s;
			$ret = $s->insert();
		}
		$cookieData = MySQLSession::$active_session->data;
		return $cookieData;
	}

	/**
	 * @param $sess_id
	 * @param $data
	 * @return bool
	 */
	static public function write($sess_id, $data)
	{
		global $logger;
		global $module;
		global $action;
		if (isset($_REQUEST['method'])) {
			$method = $_REQUEST['method'];
		}else{
			$method = '';
		}
		if ($module == 'AJAX' || $action == 'AJAX' || $action == 'JSON') {
			//Don't update sessions on AJAX and JSON calls
			////TODO: Make sure this doesn't break anything
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
				$logger->log("Not updating session $sess_id, no method provided", Logger::LOG_ERROR);
				return true;
			}

		}
		if (MySQLSession::$active_session->session_id != $sess_id) {
			echo("Session id changed since load time");
			die();
		}

		$s = MySQLSession::$active_session;
		//$logger->log("Saving session for " . $_SERVER['REQUEST_URI'] . " {$s->last_used}, " . MySQLSession::$sessionStartTime, Logger::LOG_DEBUG);
		if ($s->data != $data) {
			$s->data = $data;
			$s->last_used = MySQLSession::$sessionStartTime;
			$logger->log("Session data changed $sess_id {$s->last_used} $module $action $method: " . print_r($data, true), Logger::LOG_DEBUG);
		//}else{
		//	$logger->log("Not updating session $sess_id, the session data has not changed", Logger::LOG_ERROR);
		}
		if (isset($_SESSION['rememberMe']) && ($_SESSION['rememberMe'] == true || $_SESSION['rememberMe'] === "true")) {
			$s->remember_me = 1;
		}
		parent::write($sess_id, $data);
		$ret = $s->update();
		return $ret;
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
		return $s->delete();
	}

	static public function gc($sess_maxlifetime)
	{
		//Do nothing here, delete old sessions in Java Cron
		return true;
	}

}