<?php

abstract class SIP2Driver extends AbstractIlsDriver{
	/** @var sip2 $sipConnection  */
	private $sipConnection = null;


	public function patronLogin($username, $password, $validatedViaSSO) {
		global $timer;
		global $logger;
		if (isset($username) && isset($password)) {
			if (trim($username) != '' && trim($password) != '') {
				// Attempt SIP2 Authentication

				$mysip = new sip2();
				$mysip->hostname = $this->accountProfile->sipHost;
				$mysip->port = $this->accountProfile->sipPort;

				if ($mysip->connect($this->accountProfile->sipUser, $this->accountProfile->sipPassword)) {
					//send selfcheck status message
					$in = $mysip->msgSCStatus();
					$msg_result = $mysip->get_message($in);

					// Make sure the response is 98 as expected
					if (preg_match("/^98/", $msg_result)) {
						$result = $mysip->parseACSStatusResponse($msg_result);

						//  Use result to populate SIP2 settings
						$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
						if (!empty($result['variable']['AN'])) {
							$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
						}

						$mysip->patron = $username;
						$mysip->patronpwd = $password;

						$in = $mysip->msgPatronStatusRequest();
						$msg_result = $mysip->get_message($in);

						// Make sure the response is 24 as expected
						if (preg_match("/^24/", $msg_result)) {
							$result = $mysip->parsePatronStatusResponse( $msg_result );

							if (($result['variable']['BL'][0] == 'Y') and ($result['variable']['CQ'][0] == 'Y')) {
								//Get patron info as well
								$in = $mysip->msgPatronInformation('fine');
								$msg_result = $mysip->get_message($in);

								// Make sure the response is 24 as expected
								$patronInfoResponse = null;
								if (preg_match("/^64/", $msg_result)) {
									$patronInfoResponse = $mysip->parsePatronInfoResponse( $msg_result );

									// Success!!!
									$user = $this->loadPatronInformation($result, $username, $password, $patronInfoResponse);

									$user->password = $password;
								}
							}
						}
					}
					$mysip->disconnect();
				}else{
					$logger->log("Unable to connect to SIP server", Logger::LOG_ERROR);
				}
			}
		}

		$timer->logTime("Validated Account in SIP2Authentication");
		if (isset($user)){
			return $user;
		}else{
			return null;
		}
	}
	protected function initSipConnection($host, $post) {
		if ($this->sipConnection == null){
			require_once ROOT_DIR . '/sys/SIP2.php';
			$this->sipConnection = new sip2();
			$this->sipConnection->hostname = $host;
			$this->sipConnection->port = $post;
			if ($this->sipConnection->connect($this->accountProfile->sipUser, $this->accountProfile->sipPassword)) {
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

	/**
	 * Process SIP2 User Account
	 *
	 * @param   array   $info           An array of user information
	 * @param   string   $username       The user's ILS username
	 * @param   string   $password       The user's ILS password
	 * @param   array   $patronInfoResponse       The user's ILS password
	 * @return  User
	 * @access  public
	 * @author  Bob Wicksall <bwicksall@pls-net.org>
	 */
	private function loadPatronInformation($info, $username, $password, $patronInfoResponse){
		global $timer;
		$user = new User();
		$user->username = $info['variable']['AA'][0];
		if ($user->find(true)) {
			$insert = false;
		} else {
			$insert = true;
		}

		$fullName = $info['variable']['AE'][0];
		$user->_fullname = $fullName;
		if (strpos($info['variable']['AE'][0], ',') !== false){
			$firstName = trim(substr($fullName, 1 + strripos($fullName, ',')));
			$lastName = trim(substr($fullName, 0, strripos($fullName, ',')));
		}else{
			$lastName = trim(substr($fullName, 1 + strripos($fullName, ' ')));
			$firstName = trim(substr($fullName, 0, strripos($fullName, ' ')));
		}
		$forceDisplayNameUpdate = false;
		if ($user->firstname != $firstName) {
			$user->firstname = $firstName;
			$forceDisplayNameUpdate = true;
		}
		if ($user->lastname != $lastName) {
			$user->lastname = isset($lastName) ? $lastName : '';
			$forceDisplayNameUpdate = true;
		}
		if ($forceDisplayNameUpdate) {
			$user->displayName = '';
		}

		// I'm inserting the sip username and password since the ILS is the source.
		// Should revisit this.
		$user->cat_username = $username;
		$user->cat_password = $password;
		$user->email = isset($patronInfoResponse['variable']['BE'][0]) ? $patronInfoResponse['variable']['BE'][0] : '';
		$user->phone = isset($patronInfoResponse['variable']['BF'][0]) ? $patronInfoResponse['variable']['BF'][0] : '';
		$user->patronType = $patronInfoResponse['variable']['PC'][0];
		//TODO: Figure out how to parse the address
		$fullAddress = $patronInfoResponse['variable']['BD'][0];

		if (!empty($patronInfoResponse['variable']['PA'][0])){
			$expireTime = $patronInfoResponse['variable']['PA'][0];
			$expireTime = DateTime::createFromFormat('Ymd', $expireTime)->getTimestamp();
			$user->_expires = date('n-j-Y', $expireTime);
			if (!empty($user->_expires)) {
				$timeNow = time();
				$timeToExpire = $expireTime - $timeNow;
				if ($timeToExpire <= 30 * 24 * 60 * 60) {
					if ($timeToExpire <= 0) {
						$user->_expired = 1;
					}
					$user->_expireClose = 1;
				}
			}
		}

		//Get home location
		//Check AO?
		if ((!isset($user->homeLocationId) || $user->homeLocationId == 0) && (isset($patronInfoResponse['variable']['AQ']) || isset($patronInfoResponse['variable']['AO']))){
			$location = new Location();
			if (isset($patronInfoResponse['variable']['AQ'])){
				$location->code = $patronInfoResponse['variable']['AQ'][0];
			}else{
				$location->code = $patronInfoResponse['variable']['AO'][0];
			}

			$location->find();
			if ($location->getNumResults() > 0){
				$location->fetch();
				$user->homeLocationId = $location->locationId;
			}
			if ((!isset($user->homeLocationId) || $user->homeLocationId == 0)) {
				// Logging for Diagnosing PK-1846
				global $logger;
				$logger->log('Sip Authentication: Attempted look up user\'s homeLocationId and failed to find one. User : '.$user->id, Logger::LOG_WARNING);
			}
		}

		if ($insert) {
			$user->created = date('Y-m-d');
			$user->insert();
		} else {
			$user->update();
		}

		$timer->logTime("Processed SIP2 User");
		return $user;
	}

	public function hasNativeReadingHistory()
	{
		return false;
	}

	public function getAccountSummary(User $patron) : AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		$mysip = new sip2();
		$mysip->hostname = $this->accountProfile->sipHost;
		$mysip->port = $this->accountProfile->sipPort;

		if ($mysip->connect($this->accountProfile->sipUser, $this->accountProfile->sipPassword)) {
			//send selfcheck status message
			$in = $mysip->msgSCStatus();
			$msg_result = $mysip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mysip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				if (!empty($result['variable']['AN'])) {
					$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}

				$mysip->patron = $patron->getBarcode();
				$mysip->patronpwd = $patron->getPasswordOrPin();

				$in = $mysip->msgPatronInformation('fine');
				$msg_result = $mysip->get_message($in);

				// Make sure the response is 24 as expected
				$patronInfoResponse = null;
				if (preg_match("/^64/", $msg_result)) {
					$patronInfoResponse = $mysip->parsePatronInfoResponse( $msg_result );

					if (!empty($patronInfoResponse['variable']['PA'][0])) {
						$expireTime = $patronInfoResponse['variable']['PA'][0];
						$expireTime = DateTime::createFromFormat('Ymd', $expireTime)->getTimestamp();
						$summary->expirationDate = $expireTime;
					}
					if (!empty($patronInfoResponse['variable']['BV'][0])) {
						$summary->totalFines = (float)$patronInfoResponse['variable']['BV'][0];
					}
					$summary->numUnavailableHolds = $patronInfoResponse['fixed']['UnavailableCount'];
					$summary->numAvailableHolds = $patronInfoResponse['fixed']['HoldCount'] - $patronInfoResponse['fixed']['UnavailableCount'];
					$summary->numCheckedOut = $patronInfoResponse['fixed']['ChargedCount'];
					$summary->numOverdue = $patronInfoResponse['fixed']['OverdueCount'];
				}
			}
		}

		return $summary;
	}
}