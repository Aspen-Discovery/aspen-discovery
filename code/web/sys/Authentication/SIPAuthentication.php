<?php
require_once ROOT_DIR . '/sys/SIP2.php';
require_once 'Authentication.php';

class SIPAuthentication implements Authentication {
  private static $processedUsers = array();

	public function __construct($additionalInfo) {

	}
	
	public function validateAccount($username, $password, $parentAccount, $validatedViaSSO) {
		global $configArray;
		global $timer;
		global $logger;
		if (isset($username) && isset($password)) {
			//Check to see if we have already processed this user
			if (array_key_exists($username, self::$processedUsers)){
				return self::$processedUsers[$username];
			}
			
			if (trim($username) != '' && trim($password) != '') {
				// Attempt SIP2 Authentication

				$mysip = new sip2;
				$mysip->hostname = $configArray['SIP2']['host'];
				$mysip->port = $configArray['SIP2']['port'];

				if ($mysip->connect()) {
					//send selfcheck status message
					$in = $mysip->msgSCStatus();
					$msg_result = $mysip->get_message($in);

					// Make sure the response is 98 as expected
					if (preg_match("/^98/", $msg_result)) {
						$result = $mysip->parseACSStatusResponse($msg_result);

						//  Use result to populate SIP2 setings
						$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
						$mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */

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
								}
								
								// Success!!!
								$user = $this->processSIP2User($result, $username, $password, $patronInfoResponse);

								// Set login cookie for 1 hour
								$user->password = $password; // Need this for Metalib
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
			self::$processedUsers[$username] = $user;
			return $user;
		}else{
			return null;
		}
		
	}
	public function authenticate($validatedViaSSO) {
		global $configArray;
		global $timer;

		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = $_POST['password'];
			//Set this up to use library prefix
			$barcodePrefix = $configArray['Catalog']['barcodePrefix'];
			if (strlen($barcodePrefix) > 0){
				if (strlen($username) == 9){
					$username = substr($barcodePrefix, 0, 5) . $username;
				}elseif (strlen($username) == 8){
					$username = substr($barcodePrefix, 0, 6) . $username;
				}elseif (strlen($username) == 7){
					$username = $barcodePrefix . $username;
				}
			}

		  //Check to see if we have already processed this user
      if (array_key_exists($username, self::$processedUsers)){
        return self::$processedUsers[$username];
      }
      
			if ($username != '' && $password != '') {
				// Attempt SIP2 Authentication

				$mysip = new sip2;
				$mysip->hostname = $configArray['SIP2']['host'];
				$mysip->port = $configArray['SIP2']['port'];

				if ($mysip->connect()) {
					//send selfcheck status message
					$in = $mysip->msgSCStatus();
					$msg_result = $mysip->get_message($in);

					// Make sure the response is 98 as expected
					if (preg_match("/^98/", $msg_result)) {
						$result = $mysip->parseACSStatusResponse($msg_result);

						//  Use result to populate SIP2 setings
						$mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
						if (isset($result['variable']['AN'])){
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
								$in = $mysip->msgPatronInformation('none');
								$msg_result = $mysip->get_message($in);
				
								// Make sure the response is 24 as expected
								if (preg_match("/^64/", $msg_result)) {
									$patronInfoResponse = $mysip->parsePatronInfoResponse( $msg_result );
									//print_r($patronInfoResponse);
								}
								
								// Success!!!
								$user = $this->processSIP2User($result, $username, $password, $patronInfoResponse);

								// Set login cookie for 1 hour
								$user->password = $password; // Need this for Metalib
							} else {
								$user = new AspenError('Sorry that login information was not recognized, please try again.');
							}
						} else {
							$user = new AspenError('We cannot log you in at this time.  Please try again later.');
						}
					} else {
						$user = new AspenError('We cannot log you in at this time.  Please try again later.');
					}
					$mysip->disconnect();

				} else {
					$user = new AspenError('We cannot log you in at this time.  Please try again later.');
					global $logger;
					$logger->log("Unable to connect to SIP server", Logger::LOG_ERROR);
				}
			} else {
				$user = new AspenError('Login information cannot be blank.');
			}
			$timer->logTime("Authenticated user in SIP2Authentication");
			self::$processedUsers[$username] = $user;
		} else {
			$user = new AspenError('Login information cannot be blank.');
		}

		
		return $user;
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
	private function processSIP2User($info, $username, $password, $patronInfoResponse){
		global $timer;
		$user = new User();
		$user->username = $info['variable']['AA'][0];
		if ($user->find(true)) {
			$insert = false;
		} else {
			$insert = true;
		}
		
		// This could potentially be different depending on the ILS.  Name could be Bob Wicksall or Wicksall, Bob.
		// This is currently assuming Wicksall, Bob
		if (strpos($info['variable']['AE'][0], ',') !== false){
			$user->firstname = trim(substr($info['variable']['AE'][0], 1 + strripos($info['variable']['AE'][0], ',')));
			$user->lastname = trim(substr($info['variable']['AE'][0], 0, strripos($info['variable']['AE'][0], ',')));
		}else{
			$user->lastname = trim(substr($info['variable']['AE'][0], 1 + strripos($info['variable']['AE'][0], ' ')));
			$user->firstname = trim(substr($info['variable']['AE'][0], 0, strripos($info['variable']['AE'][0], ' ')));
		}

		// I'm inserting the sip username and password since the ILS is the source.
		// Should revisit this.
		$user->cat_username = $username;
		$user->cat_password = $password;
		$user->email = isset($patronInfoResponse['variable']['BE'][0]) ? $patronInfoResponse['variable']['BE'][0] : '';
		$user->phone = isset($patronInfoResponse['variable']['BF'][0]) ? $patronInfoResponse['variable']['BF'][0] : '';
		$user->patronType = $patronInfoResponse['variable']['PC'][0];
		
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
}