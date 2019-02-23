<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Session.php';

class MySQLSession extends SessionInterface {
	static $sessionStartTime;
	/** @var Session */
	static $active_session;
	static public function read($sess_id) {
		global $logger;
		$s = new Session();
		$s->session_id = $sess_id;

		$curTime = time();
		$logger->log("Loading session $sess_id for " . $_SERVER['REQUEST_URI'] . " $curTime", PEAR_LOG_DEBUG);
        $createSession = false;
		if ($s->find(true)) {
			//First check to see if the session expired
			MySQLSession::$sessionStartTime = $curTime;
			if ($s->remember_me == 1){
				$sessionExpirationTime = $s->last_used + self::$rememberMeLifetime;
			}else{
				$sessionExpirationTime = $s->last_used + self::$lifetime;
			}
			if ($curTime > $sessionExpirationTime){
			    //Clear any previously saved data
			    $s->data = '';
			    $s->delete();
                $_SESSION = array();
				$createSession = true;
			}else{
				// updated the session in the database to show that we just used it
                MySQLSession::$active_session = $s;
			}
		} else {
			$createSession = true;
		}
		if ($createSession){
		    $s = new Session();
			$s->session_id = $sess_id;
			//There is no active session, we need to create a new one.
			$s->last_used = MySQLSession::$sessionStartTime;
			// in date format - easier to read
			$s->created = date('Y-m-d h:i:s');
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true){
				$s->remember_me = 1;
			}else{
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
	static public function write($sess_id, $data) {
		global $logger;
		global $module;
		global $action;
		if ($module == 'AJAX' || $action == 'AJAX' || $action == 'JSON') {
		    //Don't update sessions on AJAX and JSON calls
            //TODO: Make sure this doesn't break anything
		    return true;
        }
		if (MySQLSession::$active_session->session_id != $sess_id) {
		    echo("Session id changed since load time");
		    die();
        }

		$s = MySQLSession::$active_session;
        $logger->log("Saving session for " . $_SERVER['REQUEST_URI'] . " {$s->last_used}, " . MySQLSession::$sessionStartTime, PEAR_LOG_DEBUG);
        if ($s->data != $data) {
            $s->data = $data;
            $s->last_used = MySQLSession::$sessionStartTime;
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
        $ret = $s->update();
        return $ret;
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
        //Do nothing here, delete old sessions in Java Cron
        return true;
	}

}