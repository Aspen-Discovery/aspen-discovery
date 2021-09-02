<?php

abstract class SIP2Driver extends AbstractIlsDriver{
	/** @var sip2 $sipConnection  */
	private $sipConnection = null;


	public function patronLogin($username, $password, $validatedViaSSO) {
		//TODO: Implement $validatedViaSSO
		//Koha uses SIP2 authentication for login.  See
		//The catalog is offline, check the database to see if the user is valid
		global $timer;
		$user = new User();
		$user->cat_username = $username;
		if ($user->find(true)){
			$userValid = false;
			if ($user->cat_username){
				$userValid = true;
			}
			if ($userValid){
				$returnVal = array(
					'id'        => $password,
					'username'  => $user->username,
					'firstname' => $user->firstname,
					'lastname'  => $user->lastname,
					'fullname'  => $user->firstname . ' ' . $user->lastname,     //Added to array for possible display later.
					'cat_username' => $username,
					'cat_password' => $password,
					'email' => $user->email,
					'patronType' => $user->patronType,
					'web_note' => translate(['text' => 'The catalog is currently down.  You will have limited access to circulation information.', 'isPublicFacing'=>true]));
				$timer->logTime("patron logged in successfully");
				return $returnVal;
			} else {
				$timer->logTime("patron login failed");
				return null;
			}
		} else {
			$timer->logTime("patron login failed");
			return null;
		}
	}
	protected function initSipConnection($host, $post) {
		if ($this->sipConnection == null){
			require_once ROOT_DIR . '/sys/SIP2.php';
			$this->sipConnection = new sip2();
			$this->sipConnection->hostname = $host;
			$this->sipConnection->port = $post;
			if ($this->sipConnection->connect()) {
				//send self check status message
				$in = $this->sipConnection->msgSCStatus();
				$msg_result = $this->sipConnection->get_message($in);
				// Make sure the response is 98 as expected
				if (preg_match("/^98/", $msg_result)) {
					$result = $this->sipConnection->parseACSStatusResponse($msg_result);
					//  Use result to populate SIP2 settings
					$this->sipConnection->AO = $result['variable']['AO'][0]; /* set AO to value returned */
					if (isset($result['variable']['AN'])){
						$this->sipConnection->AN = $result['variable']['AN'][0]; /* set AN to value returned */
					}
					return true;
				}
				$this->sipConnection->disconnect();
			}
			$this->sipConnection = null;
			return false;
		}else{
			return true;
		}
	}
	function __destruct(){
		//Cleanup any connections we have to other systems
		if ($this->sipConnection != null){
			$this->sipConnection->disconnect();
			$this->sipConnection = null;
		}
	}
}