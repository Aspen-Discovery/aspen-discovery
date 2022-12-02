<?php

if (file_exists(ROOT_DIR . '/sys/SearchEntry.php')) {
	require_once ROOT_DIR . '/sys/SearchEntry.php';
}

class SessionInterface implements SessionHandlerInterface {

	static public $lifetime = 3600; //one hour
	static public $rememberMeLifetime = 1209600; //2 weeks

	public function init($lt, $rememberMeLifetime) {
		self::$lifetime = $lt;
		self::$rememberMeLifetime = $rememberMeLifetime;
		session_set_save_handler($this);
		//Have to set the default timeout before we call session start, set a really long timeout by default since PHP doesn't like to extend the PHPSESSION timeout
		//Set one year by default
		session_set_cookie_params(0, '/');
		session_start();
	}

	// the following need to be static since they are used as callback functions
	public function open($sess_path, $sess_name) {
		return true;
	}

	public function close() {
		return true;
	}

	public function read($sess_id) {}

	public function write($sess_id, $data) {}

	// IMPORTANT:  The functionality defined in this method is global to all session
	//      mechanisms.  If you override this method, be sure to still call
	//      parent::destroy() in addition to any new behavior.
	public function destroy($sess_id) {
		if (class_exists('SearchEntry')) {
			// Delete the searches stored for this session
			$search = new SearchEntry();
			$searchList = $search->getSearches($sess_id);
			// Make sure there are some
			if (count($searchList) > 0) {
				foreach ($searchList as $oldSearch) {
					// And make sure they aren't saved
					if ($oldSearch->saved == 0) {
						$oldSearch->delete();
					}
				}
			}
		}
		return true;
	}

	// how often does this get called (if at all)?

	// *** 08/Oct/09 - Greg Pendlebury
	// Clearly this is being called. Production installs with
	//   thousands of sessions active are showing no old sessions.
	// What I can't do is reproduce for testing. It might need the
	//   search delete code from 'destroy()' if it is not calling it.
	// *** 09/Oct/09 - Greg Pendlebury
	// Anecdotal testing Today and Yesterday seems to indicate destroy()
	//   is called by the garbage collector and everything is good.
	// Something to keep in mind though.
	public function gc($sess_maxlifetime) {}
}