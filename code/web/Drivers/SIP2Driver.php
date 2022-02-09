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
		$user->username = $patronInfoResponse['variable']['XI'][0];
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

			if ($location->find(true)){
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

		$patronInfoResponse = $this->getPatronInformationResponse($patron, 'none');
		if ($patronInfoResponse != null){
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

		return $summary;
	}

	protected function getPatronInformationResponse(User $patron, string $type, $start = 1, $end = 5){
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		if ($mySip->connect($this->accountProfile->sipUser, $this->accountProfile->sipPassword)) {
			//send self check status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				if (!empty($result['variable']['AN'])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}

				$mySip->patron = $patron->getBarcode();
				$mySip->patronpwd = $patron->getPasswordOrPin();

				$in = $mySip->msgPatronInformation($type, $start, $end);
				$msg_result = $mySip->get_message($in);

				// Make sure the response is 24 as expected
				$patronInfoResponse = null;
				if (preg_match("/^64/", $msg_result)) {
					$patronInfoResponse = $mySip->parsePatronInfoResponse( $msg_result );

					return $patronInfoResponse;
				}
			}
		}
		return null;
	}

	public function getFines(User $patron, $includeMessages = false)
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)){
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );

		$fines = [];
		$start = 1;
		$batchSize = 25;
		$end = $start + $batchSize -1 ;
		$numFines = -1;
		$ctr = 0;
		while ($numFines == -1 || $ctr < $numFines){
			$patronInfoResponse = $this->getPatronInformationResponse($patron, 'fine', $start, $end);
			if ($patronInfoResponse != null){
				if ($numFines == -1){
					$numFines = $patronInfoResponse['fixed']['FineCount'];
				}
				foreach ($patronInfoResponse['variable']['AV'] as $fineDescription){
					list($amount, $reason, $message) = explode(' ', $fineDescription, 3);
					$curFine = [
						'fineId' => $ctr,
						'date' => '',
						'type' => '',
						'reason' => $reason,
						'message' => $message,
						'amountVal' => (float)$amount,
						'amountOutstandingVal' => (float)$amount,
						'amount' => $currencyFormatter->formatCurrency((float)$amount, $currencyCode),
						'amountOutstanding' => $currencyFormatter->formatCurrency((float)$amount, $currencyCode),
					];
					$fines[] = $curFine;
					$ctr++;
				}
			}else{
				//Could not load information, break to avoid infinite loop
				break;
			}
			$start = $end + 1;
			$end = $start + $batchSize -1;

			if ($start > $numFines){
				//Extra safety check against infinite loop if we have an error with a SIP response.
				break;
			}
		}


		return $fines;
	}

	/**
	 * @inheritDoc
	 */
	function placeHold(User $patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		if (strpos($recordId, ':') !== false){
			list(,$shortId) = explode(':', $recordId);
		}else{
			$shortId = $recordId;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $shortId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}

		$hold_result = [];
		$success = false;
		$title = '';
		$message = 'Failed to connect to complete requested action.';
		$apiResult = [
			'title' => translate(['text' => 'Unable to place hold', 'isPublicFacing' => true])
		];

		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		if ($mySip->connect($this->accountProfile->sipUser, $this->accountProfile->sipPassword)) {
			//send self check status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);

				//  Use result to populate SIP2 settings
				$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				if (!empty($result['variable']['AN'])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				}else{
					$mySip->AN = '';
				}

				$mySip->patron = $patron->getBarcode();
				$mySip->patronpwd = $patron->getPasswordOrPin();

				if (!empty($cancelDate)) {
					$dateObject = date_create_from_format('m/d/Y', $cancelDate);
					$expirationTime = $dateObject->getTimestamp();
				} else {
					$expirationTime = '';
				}

				//$mySip->sleeptime = 4000000;
				$in = $mySip->msgHold('+', '', 2, '', $shortId, 'N', '');
				$msg_result = $mySip->get_message($in);

				// Make sure the response is 24 as expected
				if (preg_match("/^16/", $msg_result)) {
					$result = $mySip->parseHoldResponse( $msg_result );
					$success = ($result['fixed']['Ok'] == 1);
					$message = $result['variable']['AF'][0];
					if (!empty($result['variable']['AJ'][0])) {
						$title = $result['variable']['AJ'][0];
					}

					if ($success){
						$apiResult['title'] = translate(['text' => 'Hold placed successfully', 'isPublicFacing' => true]);
						$apiResult['action'] = translate(['text' => 'Go to Holds', 'isPublicFacing'=>true]);
						$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
						$patron->forceReloadOfHolds();
					}
				}else{
					$message = $msg_result;
				}
			}
		}

		$apiResult['message'] = $message;
		return array(
			'title'   => $title,
			'bib'     => $recordId,
			'success' => $success,
			'message' => translate(['text'=>$message,'isPublicFacing'=>true]),
			'api'     => $apiResult
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getHolds(User $patron)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$start = 1;
		$batchSize = 25;
		$end = $start + $batchSize -1 ;
		$numAvailable = -1;
		$ctr = 0;
		while ($numAvailable == -1 || $ctr < $numAvailable) {
			$availableHoldsResponse = $this->getPatronInformationResponse($patron, 'hold', $start, $end);
			if ($availableHoldsResponse != null){
				if ($numAvailable == -1){
					$numAvailable = $availableHoldsResponse['fixed']['HoldCount'] - $availableHoldsResponse['fixed']['UnavailableCount'];
				}

				foreach ($availableHoldsResponse['variable']['AV'] as $availableHoldInfo) {
					$ctr++;
				}
			}else{
				//Could not load information, break to avoid infinite loop
				break;
			}
			$start = $end + 1;
			$end = $start + $batchSize -1;

			if ($start > $numAvailable){
				//Extra safety check against infinite loop if we have an error with a SIP response.
				break;
			}
		}

		$start = 1;
		$batchSize = 25;
		$end = $start + $batchSize -1 ;
		$numUnavailable = -1;
		$ctr = 0;
		while ($numUnavailable == -1 || $ctr < $numUnavailable) {
			$unavailableHoldsResponse = $this->getPatronInformationResponse($patron, 'unavail', $start, $end);
			if ($unavailableHoldsResponse != null){
				if ($numUnavailable == -1){
					$numUnavailable = $unavailableHoldsResponse['fixed']['UnavailableCount'];
				}

				foreach ($unavailableHoldsResponse['variable']['CD'] as $unavailableHoldInfo) {
					$ctr++;
				}
			}else{
				//Could not load information, break to avoid infinite loop
				break;
			}
			$start = $end + 1;
			$end = $start + $batchSize -1;

			if ($start > $numUnavailable){
				//Extra safety check against infinite loop if we have an error with a SIP response.
				break;
			}
		}
	}
}