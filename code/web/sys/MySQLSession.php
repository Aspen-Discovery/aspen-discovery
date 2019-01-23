<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Session.php';

class MySQLSession extends SessionInterface {
	static $sessionStartTime;
	static public function read($sess_id) {
		global $logger;
		$s = new Session();
		$s->session_id = $sess_id;

		$cookieData = '';
		$saveNewSession = false;
		$curTime = time();
		$logger->log("Loading session $sess_id for " . $_SERVER['REQUEST_URI'] . " $curTime", PEAR_LOG_DEBUG);
		if ($s->find(true)) {
			//First check to see if the session expired
			MySQLSession::$sessionStartTime = $curTime;
			if ($s->remember_me == 1){
				$sessionExpirationTime = $s->last_used + self::$rememberMeLifetime;
			}else{
				$sessionExpirationTime = $s->last_used + self::$lifetime;
			}
			if ($curTime > $sessionExpirationTime){
				$s->delete();
				//Start a new session.  Ignore previous errors if the last expired
				@session_start();
				session_regenerate_id(true);
				$sess_id = session_id();
				$_SESSION = array();
				$saveNewSession = true;
			}else{
				// updated the session in the database to show that we just used it
				$s->last_used = $curTime;
				$s->update();
				$cookieData = $s->data;
			}
		} else {
			$saveNewSession = true;
		}
		if ($saveNewSession){
			$s->session_id = $sess_id;
			//There is no active session, we need to create a new one.
			$s->last_used = $curTime;
			// in date format - easier to read
			$s->created = date('Y-m-d h:i:s');
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true){
				$s->remember_me = 1;
			}else{
				$s->remember_me = 0;
			}
			$s->insert();
		}
		return $cookieData;
	}

	static public function write($sess_id, $data) {
		global $logger;
		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)) {
			//$logger->log("Saving session for " . $_SERVER['REQUEST_URI'] . " {$s->last_used}, " . MySQLSession::$sessionStartTime, PEAR_LOG_DEBUG);
			if ($s->last_used != MySQLSession::$sessionStartTime){
				$logger->log("Not Writing Session data $sess_id because another process wrote to it already", PEAR_LOG_DEBUG);
				return true;
			}
			if ($s->data != $data) {
				$s->data = $data;
				$s->last_used = time();
				$logger->log("Session data changed $sess_id {$s->last_used} " . print_r($data, true), PEAR_LOG_DEBUG);
			}
			if (isset($_SESSION['rememberMe']) && ($_SESSION['rememberMe'] == true || $_SESSION['rememberMe'] === "true")){
				$s->remember_me = 1;
				setcookie(session_name(),session_id(),time()+self::$rememberMeLifetime,'/');
			}else{
				$s->remember_me = 0;
				session_set_cookie_params(0);
			}
			parent::write($sess_id, $data);
			return $s->update();
		} else {
			//No session active
			return false;
		}
	}

	static public function destroy($sess_id) {
		global $logger;
		$logger->log("Destroying session $sess_id", PEAR_LOG_DEBUG);
		// Perform standard actions required by all session methods:
		parent::destroy($sess_id);

		// Now do database-specific destruction:
		$s = new Session();
		$s->session_id = $sess_id;
		return $s->delete();
	}

	static public function gc($sess_maxlifetime) {
		//Doing this in PHP  at random times, causes problems for VuFind, do it as part of cron in Java
		/*$s = new Session();
		$s->whereAdd('last_used + ' . $sess_maxlifetime . ' < ' . time());
		$s->whereAdd('remember_me = 0');
		$s->delete(true);

		$s = new Session();
		$s->whereAdd('last_used + ' . SessionInterface::$rememberMeLifetime . ' < ' . time());
		$s->whereAdd('remember_me = 1');
		$s->delete(true);*/
	}

}