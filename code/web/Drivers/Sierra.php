<?php

require_once ROOT_DIR . '/Drivers/Millennium.php';
class Sierra extends Millennium{
	protected $urlIdRegExp = "/.*\/(\d*)$/";

	private $sierraToken = null;
	private $lastResponseCode;
	private $lastError;
	private $lastErrorMessage;

	public function _connectToApi(){
		if ($this->sierraToken == null){
			$apiVersion = $this->accountProfile->apiVersion;
			$ch = curl_init($this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/token/");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$authInfo = base64_encode($this->accountProfile->oAuthClientId . ":" . $this->accountProfile->oAuthClientSecret);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
					'Authorization: Basic ' . $authInfo,
			));
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			curl_close($ch);
			$this->sierraToken = json_decode($return);
		}
		return $this->sierraToken;
	}

	public function _callUrl($requestType, $url){
		$tokenData = $this->_connectToAPI();
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$host = parse_url($url, PHP_URL_HOST);
			$headers = array(
					"Authorization: " . $tokenData->token_type . " {$tokenData->access_token}",
					"User-Agent: Aspen Discovery",
					"X-Forwarded-For: " . IPAddress::getActiveIp(),
					"Host: " . $host,
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			$this->lastResponseCode = $responseCode;

			ExternalRequestLogEntry::logRequest($requestType, 'GET', $url, $headers, '', $responseCode, $return, []);
			curl_close($ch);

			$returnVal = json_decode($return);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}
		}
		return null;
	}

	public function _postPage($requestType, $url, $postParams){

		$tokenData = $this->_connectToAPI();
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$host = parse_url($url, PHP_URL_HOST);
			$headers = array(
				"Authorization: " . $tokenData->token_type . " {$tokenData->access_token}",
				"User-Agent: Aspen Discovery",
				"X-Forwarded-For: " . IPAddress::getActiveIp(),
				"Host: " . $host,
			);
			if ($postParams != null && is_string($postParams)){
				$headers[] = 'Content-Type: application/json';
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postParams != null) {
				if (is_string($postParams)) {
					$post_string = $postParams;
				} else {
					$post_string = http_build_query($postParams);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
			}else{
				$post_string = '';
			}
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			$this->lastResponseCode = $responseCode;
			$this->lastError = curl_errno($ch);
			$this->lastErrorMessage = curl_error($ch);


			ExternalRequestLogEntry::logRequest($requestType, 'POST', $url, $headers, $post_string, $responseCode, $return, []);
			curl_close($ch);
			$returnVal = json_decode($return);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}
		}
		return null;
	}

	public function _sendPage($requestType, $httpMethod, $url, $postParams){

		$tokenData = $this->_connectToAPI();
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$host = parse_url($url, PHP_URL_HOST);
			$headers = array(
				"Authorization: " . $tokenData->token_type . " {$tokenData->access_token}",
				"User-Agent: Aspen Discovery",
				"X-Forwarded-For: " . IPAddress::getActiveIp(),
				"Host: " . $host,
			);
			if ($httpMethod == 'PUT'){
				$headers[] = 'Content-Type: application/json';
				if ($postParams === null || $postParams === false) {
					$headers[] = 'Content-Length: 0';
				}else{
					if (is_array($postParams)) {
						$postParams = json_encode($postParams);
					}
				}
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			if ($httpMethod == 'GET') {
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			} elseif ($httpMethod == 'POST') {
				curl_setopt($ch, CURLOPT_POST, true);
			} else {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
			}
			if ($postParams != null) {
				if (is_array($postParams)){
					$postFields = http_build_query($postParams);
				}else{
					$postFields = $postParams;
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			}else{
				$postFields = '';
			}
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];
			$this->lastResponseCode = $responseCode;
			$this->lastError = curl_errno($ch);
			$this->lastErrorMessage = curl_error($ch);

			ExternalRequestLogEntry::logRequest($requestType, $httpMethod, $url, $headers, $postFields, $responseCode, $return, []);
			curl_close($ch);
			$returnVal = json_decode($return);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}
		}
		return null;
	}

	/**
	 * Returns one of three values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * @return string
	 */
	function getForgotPasswordType()
	{
		if ($this->accountProfile->loginConfiguration == 'barcode_pin') {
			return 'emailResetLink';
		} else {
			return 'none';
		}
	}

	public function getHolds($patron)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = array();
		$unavailableHolds = array();
		$return = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$patronId = $patron->username;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;

		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patronId."/holds";
		if ($this->accountProfile->apiVersion > 4){
			$sierraUrl .= "?fields=default,pickupByDate,frozen,priority,priorityQueueLength,notWantedBeforeDate,notNeededAfterDate&limit=1000";
		} else {
			$sierraUrl .= "?fields=default,frozen,priority,priorityQueueLength,notWantedBeforeDate,notNeededAfterDate&limit=1000";
		}
		$holds = $this->_callUrl('sierra.getHolds', $sierraUrl);

		if($holds->total == 0) {
			return $return;
		}

		// these will be consistent for every hold
		$pickupLocations = $patron->getValidPickupBranches($this->accountProfile->recordSource);
		if(is_array($pickupLocations)) {
			if (count($pickupLocations) > 1) {
				$canUpdatePL = true;
			} else {
				$canUpdatePL = false;
			}
		} else {
			$canUpdatePL = false;
		}
		foreach ($holds->entries as $sierraHold) {
			$curHold = new Hold();
			$curHold->createDate = null;
			$curHold->userId = $patron->id;
			$curHold->type = 'ils';
			$curHold->source = $this->accountProfile->getIndexingProfile()->name;

			$curHold->frozen = $sierraHold->frozen;
			$curHold->createDate = strtotime($sierraHold->placed); // date hold created
			// innreach holds don't include notNeededAfterDate
			$curHold->automaticCancellationDate = isset($sierraHold->notNeededAfterDate) ? strtotime($sierraHold->notNeededAfterDate) : null; // not needed after date
			$curHold->expirationDate = isset($sierraHold->pickupByDate) ? strtotime($sierraHold->pickupByDate) : false; // pick up by date // this isn't available in api v4

			if (isset($sierraHold->priority)) {
				if ($this->accountProfile->apiVersion == 4) {
					$holdPriority = (integer)$sierraHold->priority + 1;
				} else {
					$holdPriority = $sierraHold->priority;
				}
				$curHold->position = $holdPriority;
			}
			if (isset($sierraHold->priorityQueueLength)) {
				$curHold->holdQueueLength = $sierraHold->priorityQueueLength;
			}

			// cancel id
			preg_match($this->urlIdRegExp, $sierraHold->id, $m);
			$curHold->cancelId = $m[1];

			// status, cancelable, freezable
			$recordStatus = $sierraHold->status->code;
			// check item record status
			preg_match($this->urlIdRegExp, $sierraHold->record, $m);
			$recordId = $m[1];
			if ($sierraHold->recordType == 'i') {
				$recordItemStatus = $sierraHold->status->code;
				// If this is an inn-reach exclude from check -- this comes later
				if(! strstr($recordId, "@")) {
					// if the item status is "on hold shelf" (!) but the hold record status is "on hold" (0) use "on hold" status
					// the "on hold shelf" status is for another patron.
					if($recordItemStatus != "!" && $recordStatus != '0') {
						// check for in transit status see
						if($recordItemStatus == 't') {
							if(isset($sierraHold->priority) && (int)$sierraHold->priority == 1)
								$recordStatus = 't';
						}
					}
				} else {
					// inn-reach status
					$recordStatus = $recordItemStatus;
				}
			}

			$available = false;
			// type hint so '0' != false
			switch ((string)$recordStatus) {
				case '0':
				case '-':
					if($sierraHold->frozen) {
						$status = "Frozen";
					} else {
						$status = 'On hold';
					}
					$freezeable = true;
					$cancelable = true;

					if($canUpdatePL) {
						$updatePickup = true;
					} else {
						$updatePickup = false;
					}
					break;
				case 'b':
				case 'j':
				case 'i':
				case '!':
					$status       = 'Ready';
					$cancelable   = true;
					$freezeable   = false;
					$updatePickup = false;
					$available = true;
					break;
				case 't':
					$status     = 'In transit';
					$cancelable = true;
					$freezeable = false;
					if($canUpdatePL) {
						$updatePickup = true;
					} else {
						$updatePickup = false;
					}
					break;
				case "&": // inn-reach status
					$status       = "Requested";
					$cancelable   = true;
					$freezeable   = false;
					$updatePickup = false;
					break;
				case "#": // inn-reach status
					$sierraHold->status->code = 'i';
					$status             = 'Ready';
					$freezeable         = false;
					$cancelable         = false;
					$updatePickup = false;
					$available = true;
					break;
				default:
					if(isset($recordItemStatusMessage)) {
						$status = $recordItemStatusMessage;
					} else {
						$status = 'On hold';
					}
					$cancelable   = false;
					$freezeable   = false;
					$updatePickup = false;
			}
			$curHold->status    = $status;
			if(isset($curHold->holdQueueLength)) {
				if(isset($curHold->position) && ((int)$curHold->position <= 2 && (int)$curHold->holdQueueLength >= 2)) {
					$freezeable = false;
					// if the patron is the only person on wait list hold can't be frozen
				} elseif(isset($curHold->position) && ($curHold->position == 1 && (int)$curHold->holdQueueLength == 1)) {
					$freezeable = false;
					// if there is no priority set but queueLength = 1
				} elseif(!isset($curHold->position) && $curHold->holdQueueLength == 1) {
					$freezeable = false;
				}
			}
			$curHold->canFreeze = $freezeable;
			$curHold->cancelable = $cancelable;
			$curHold->locationUpdateable = $updatePickup;
			$curHold->available = $available;
			// unset for next round.
			unset($status, $freezeable, $cancelable, $updatePickup);

			// pick up location
			if (!empty($sierraHold->pickupLocation)){
				$pickupBranch = new Location();
				$pickupBranch->code = $sierraHold->pickupLocation->code;
				if ($pickupBranch->find(true)){
					$curHold->pickupLocationId   = $pickupBranch->locationId;
					$curHold->pickupLocationName = $pickupBranch->displayName;
				}else{
					$curHold->pickupLocationId   = false;
					$curHold->pickupLocationName = $sierraHold->pickupLocation->name;
				}
			} else{
				//This shouldn't happen but we have had examples where it did
				global $logger;
				$logger->log("Patron with barcode {$patron->getBarcode()} has a hold with out a pickup location ", Logger::LOG_ERROR);
				$curHold->pickupLocationId   = false;
				$curHold->pickupLocationName = '';
			}

			// determine if this is an innreach hold
			// or if it's a regular ILS hold
			if(strstr($recordId, "@")) {
				//TODO: Handle INNREACH Holds
			} else {
				///////////////
				// ILS HOLD
				//////////////
				// record type and record id
				$recordType = $sierraHold->recordType;
				// for item level holds we need to grab the bib id.
				$id = $recordId; //$m[1];
				if($recordType == 'i') {
					$itemId = ".i{$id}" . $this->getCheckDigit($id);
					$id = $this->getBibIdForItem($itemId, $id);
				}else{
					$recordXD = $this->getCheckDigit($id);
					$id = ".b{$id}{$recordXD}";
				}

				if ($id != false) {

					$curHold->recordId = $id;
					$curHold->sourceId = $curHold->recordId;

					// get more info from record
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver((string)$curHold->recordId);
					if ($recordDriver->isValid()) {
						$curHold->updateFromRecordDriver($recordDriver);
					}
				}else{
					$curHold->sourceId = '';
					$curHold->recordId = '';
				}
			}
			if($available) {
				$return['available'][] = $curHold;
			} else {
				$return['unavailable'][] = $curHold;
			}
		}

		return $return;
	}

	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut")
	{
		$readingHistoryEnabled = false;
		$patronId = $patron->username;

		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patronId."/checkouts/history/activationStatus";

		$readingHistoryEnabledResponse = $this->_callUrl('sierra.getReadingHistoryStatus', $sierraUrl);

		if (!empty($readingHistoryEnabledResponse)){
			$readingHistoryEnabled = $readingHistoryEnabledResponse->readingHistoryActivation;
		}
		$readingHistoryTitles = array();
		if (true || $readingHistoryEnabled){
			$numProcessed = 0;
			$totalToProcess = 1000;
			while ($numProcessed < $totalToProcess){
				$getReadingHistoryUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patronId."/checkouts/history?limit=100&offset=$numProcessed&sortField=outDate&sortOrder=desc";

				$readingHistoryResponse = $this->_callUrl('sierra.getReadingHistory', $getReadingHistoryUrl);
				if ($readingHistoryResponse && $readingHistoryResponse->total > 0){
					$totalToProcess = $readingHistoryResponse->total;
					foreach ($readingHistoryResponse->entries as $historyEntry){
						$curTitle = array();
						preg_match($this->urlIdRegExp, $historyEntry->bib, $matches);
						$bibId = ".b{$matches[1]}" . $this->getCheckDigit($matches[1]);
						$curTitle['id'] = $bibId;
						$curTitle['shortId'] = "{$matches[1]}";
						$curTitle['recordId'] = $bibId;
						$curTitle['checkout'] = strtotime($historyEntry->outDate);
						$curTitle['checkin'] = null; //Polaris doesn't indicate when things are checked in
						$curTitle['ratingData'] = null;
						$curTitle['permanentId'] = null;
						$curTitle['linkUrl'] = null;
						$curTitle['coverUrl'] = null;
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $curTitle['recordId']);
						if ($recordDriver->isValid()) {
							$curTitle['ratingData'] = $recordDriver->getRatingData();
							$curTitle['permanentId'] = $recordDriver->getPermanentId();
							$curTitle['linkUrl'] = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
							$curTitle['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
							$curTitle['title'] = $recordDriver->getTitle();
							$curTitle['format'] = $recordDriver->getFormats();
							$curTitle['author'] = $recordDriver->getPrimaryAuthor();
						}else{
							//get title and author by looking up the bib
							$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/{$curTitle['shortId']}");
							if ($getBibResponse){
								$curTitle['title'] = $getBibResponse->title;
								$curTitle['author'] = $getBibResponse->author;
								$curTitle['format'] = isset($getBibResponse->materialType->value) ? $getBibResponse->materialType->value : 'Unknown';
							}else{
								$curTitle['title'] = 'Unknown';
								$curTitle['author'] = 'Unknown';
								$curTitle['format'] = 'Unknown';
							}
						}
						$recordDriver->__destruct();
						$recordDriver = null;

						$readingHistoryTitles[] = $curTitle;
					}
					$numProcessed += count($readingHistoryResponse->entries);
				}else{
					break;
				}
			}
		}

		return array('historyActive' => $readingHistoryEnabled, 'titles' => $readingHistoryTitles, 'numTitles' => count($readingHistoryTitles));
	}

	public function getCheckouts(User $patron)
	{
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = array();

		$patronId = $patron->username;

		$numProcessed = 0;
		$total = -1;

		while ($numProcessed < $total || $total == -1){
			$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patronId."/checkouts?fields=default,barcode,callNumber&limit=100&offset={$numProcessed}";
			$checkouts = $this->_callUrl('sierra.getCheckouts', $sierraUrl);
			if ($total == -1){
				$total = $checkouts->total;
			}

			foreach ($checkouts->entries as $i => $entry){
				preg_match($this->urlIdRegExp, $entry->id, $m);
				$checkoutId = $m[1];

				preg_match($this->urlIdRegExp, $entry->item, $m);
				$itemIdShort = $m[1];
				$itemId = ".i" . $itemIdShort . $this->getCheckDigit($itemIdShort);
				$bibId = $this->getBibIdForItem($itemId, $itemIdShort);

				$curCheckout = new Checkout();
				$curCheckout->type = 'ils';
				$curCheckout->source = $this->getIndexingProfile()->name;
				$curCheckout->sourceId = $checkoutId;
				$curCheckout->userId = $patron->id;
				$curCheckout->dueDate = strtotime($entry->dueDate);
				$curCheckout->checkoutDate = strtotime($entry->outDate);
				$curCheckout->renewCount = $entry->numberOfRenewals;
				$curCheckout->canRenew = true;
				$curCheckout->callNumber = $entry->callNumber;
				$curCheckout->barcode = $entry->barcode;
				$curCheckout->itemId = $itemId;
				$curCheckout->renewalId = $checkoutId;
				$curCheckout->renewIndicator = $checkoutId;
				if ($bibId != false){
					$curCheckout->sourceId = $bibId;
					$curCheckout->recordId = $bibId;
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver((string)$curCheckout->recordId);
					if ($recordDriver->isValid()){
						$curCheckout->updateFromRecordDriver($recordDriver);
					}else{
						$bibIdShort = substr(str_replace('.b', 'b', $bibId), 0, -1);
						$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/{$bibIdShort}");
						if ($getBibResponse){
							$curCheckout->title = $getBibResponse->title;
							$curCheckout->author = $getBibResponse->author;
							$curCheckout->formats = [isset($getBibResponse->materialType->value) ? $getBibResponse->materialType->value : 'Unknown'];
						}else{
							$curCheckout->title = 'Unknown';
							$curCheckout->author = 'Unknown';
							$curCheckout->formats = ['Unknown'];
						}
					}
				}else{
					$curCheckout->sourceId = '';
					$curCheckout->recordId = '';
				}

				$index = $i + $numProcessed;
				$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$index";
				$checkedOutTitles[$sortKey] = $curCheckout;
			}
			$numProcessed += $checkouts->total;
		}

		return $checkedOutTitles;
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/checkouts/{$itemId}/renewal";
		$renewResponse = $this->_postPage('sierra.renewCheckout', $sierraUrl, null);

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204){
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ":" . $recordId);
			if ($recordDriver->isValid()) {
				$title = $recordDriver->getTitle();
			} else {
				$title = false;
			}

			$return = ['success' => true];
			if ($title) {
				$return['message'] = $title.' has been renewed.';
			} else {
				$return['message'] = 'Your item has been renewed';
			}
		}else{
			$message = "Unable to renew your checkout";
			if (!empty($renewResponse) && !empty($renewResponse->description)){
				$message .= '<br/>' . $renewResponse->description;
			}
			return [
				'success' => false,
				'message' => $message
			];
		}

		$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
		$patron->forceReloadOfCheckouts();
		return $return;
	}

	/**
	 * @param string $itemId
	 * @return string|false
	 */
	private function getBibIdForItem(string $itemId, $shortId)
	{
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkItem.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
		$groupedWorkItem = new GroupedWorkItem();
		$groupedWorkItem->itemId = $itemId;
		if ($groupedWorkItem->find(true)) {
			$groupedWorkRecord = new GroupedWorkRecord();
			$groupedWorkRecord->id = $groupedWorkItem->groupedWorkRecordId;
			if ($groupedWorkRecord->find(true)) {
				$id = $groupedWorkRecord->recordIdentifier;
			} else {
				$id = false;
			}
		} else {
			//Lookup the bib id from the Sierra APIs
			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/items/$shortId";
			$itemInfo = $this->_callUrl('sierra.getItemInfo', $sierraUrl);
			if (!empty($itemInfo)){
				$id = reset($itemInfo->bibIds);
				$id = '.b' . $id . $this->getCheckDigit($id);
			}else{
				$id = false;
			}
		}
		return $id;
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate){
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/{$itemToFreezeId}";
		$params = [
			'freeze' => true
		];
		$freezeResponse = $this->_sendPage('sierra.freezeHold', 'PUT', $sierraUrl, $params);
		if (!$freezeResponse){
			$patron->forceReloadOfHolds();
			return [
				'success' => true,
				'message' => translate(['text'=>"Hold frozen successfully.", 'isPublicFacing'=>true])
			];
		}else{
			$return = [
				'success' => true,
				'message' => translate(['text'=>"Unable to freeze your hold.", 'isPublicFacing'=>true])
			];
			$return['message'] .= ' ' . translate(['text'=>trim(str_replace('WebPAC Error : ', '', $freezeResponse->description)), 'isPublicFacing'=>true]);
			return $return;
		}
	}

	function thawHold($patron, $recordId, $itemToThawId){
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/{$itemToThawId}";
		$params = [
			'freeze' => false
		];
		$thawResponse = $this->_sendPage('sierra.thawHold', 'PUT', $sierraUrl, json_encode($params));
		if (!$thawResponse){
			$patron->forceReloadOfHolds();
			return [
				'success' => true,
				'message' => translate(['text'=>'Hold thawed successfully.', 'isPublicFacing'=>true])
			];
		}else{
			$return = [
				'success' => true,
				'message' => translate(['text'=>"Unable to thaw your hold.", 'isPublicFacing'=>true])
			];
			$return['message'] .= ' ' . translate(['text'=>trim(str_replace('WebPAC Error : ', '', $thawResponse->description)), 'isPublicFacing'=>true]);
			return $return;
		}
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation){
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/{$itemToUpdateId}";
		$params = [
			'pickupLocation' => $newPickupLocation
		];
		$changePickupResponse = $this->_sendPage('sierra.changePickupLocation', 'PUT', $sierraUrl, json_encode($params));
		if (!$changePickupResponse){
			$patron->forceReloadOfHolds();
			$result['success'] = true;
			$result['message'] = translate(['text'=>'The pickup location of your hold was changed successfully.', 'isPublicFacing'=>true]);

			// Result for API or app use
			$result['api']['title'] = translate(['text'=>'Pickup location updated', 'isPublicFacing'=>true]);
			$result['api']['message'] = translate(['text'=>'The pickup location of your hold was changed successfully.', 'isPublicFacing'=>true]);

			return $result;
		}else{
			$message = translate(['text'=>'Sorry, the pickup location of your hold could not be changed.', 'isPublicFacing'=>true]) . " {$changePickupResponse->ErrorMessage}";;
			$result['success'] = false;
			$result['message'] = $message;

			// Result for API or app use
			$result['api']['title'] = translate(['text'=>'Unable to update pickup location', 'isPublicFacing'=>true]);
			$result['api']['message'] = trim(str_replace('WebPAC Error : ', '', $changePickupResponse->ErrorMessage));

			return $result;
		}
	}

	public function cancelHold($patron, $recordId, $cancelId = null){
		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/holds/{$cancelId}";
		$cancelHoldResponse = $this->_sendPage('sierra.cancelHold', 'DELETE', $sierraUrl, '');
		if (!$cancelHoldResponse){
			$patron->forceReloadOfHolds();
			return [
				'success' => true,
				'message' => translate(['text'=>'Hold cancelled successfully.', 'isPublicFacing'=>true])
			];
		}else{
			$return = [
				'success' => true,
				'message' => translate(['text'=>"Unable to cancel your hold.", 'isPublicFacing'=>true])
			];
			$return['message'] .= ' ' . translate(['text'=>trim(str_replace('WebPAC Error : ', '', $cancelHoldResponse->description)), 'isPublicFacing'=>true]);
			return $return;
		}
	}

	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		$hold_result = [
			'success' => false,
			'message' => translate(['text' => 'There was an error placing your hold.', 'isPublicFacing'=> true]),
			'api' => [
				'title' => translate(['text' => 'Unable to place hold', 'isPublicFacing'=> true]),
				'message' => translate(['text' => 'There was an error placing your hold.', 'isPublicFacing'=> true])
			],
		];

		$recordType = substr($recordId, 1, 1);
		$recordNumber = substr($recordId, 2, -1);

		$params = [
			'recordType' => $recordType,
			'recordNumber' => (int)$recordNumber,
			'pickupLocation' => $pickupBranch
		];

		if ($cancelDate != null){
			$params['neededBy'] = $cancelDate;
		}

		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
		$hold_result['bib'] = $recordId;
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
			$hold_result['title'] = $title;
		}

		$sierraUrl = $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/{$patron->username}/holds/requests";
		$placeHoldResponse = $this->_postPage('sierra.placeHold', $sierraUrl, json_encode($params));
		if ($placeHoldResponse == null && ($this->lastResponseCode == 200 || $this->lastResponseCode = 204)){
			$hold_result['success'] = true;
			$hold_result['message'] = translate(['text'=>"Your hold was placed successfully.", 'isPublicFacing'=>true]);

			$hold_result['api']['title'] = translate(['text'=>'Hold placed successfully', 'isPublicFacing'=>true]);
			$hold_result['api']['message'] = translate(['text'=> 'Your hold was placed successfully.', 'isPublicFacing'=>true]);
			$hold_result['api']['action'] = translate(['text' => 'Go to Holds', 'isPublicFacing'=>true]);

			$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
			$patron->forceReloadOfHolds();
		}else{
			//Get the hold form
			$message = isset($placeHoldResponse->description) ? $placeHoldResponse->description : $placeHoldResponse->name;
			$hold_result['success'] = false;
			$hold_result['message'] = translate(['text'=>$message, 'isPublicFacing'=>true]);

			$hold_result['api']['title'] = translate(['text'=>$message, 'isPublicFacing'=>true]);
			$hold_result['api']['message'] = translate(['text'=> $message, 'isPublicFacing'=>true]);
			if (isset($placeHoldResponse->code) && isset($placeHoldResponse->details->itemsAsVolumes)){
				$items = [];
				foreach ($placeHoldResponse->details->itemsAsVolumes as $itemFromSierra){
					$items[] = [
						'itemNumber' => '.i' . $itemFromSierra->id . $this->getCheckDigit($itemFromSierra->id),
						'location' => $itemFromSierra->location->name,
						'callNumber' => $itemFromSierra->callNumber,
						'status' => $itemFromSierra->status->display
					];
				}
				$hold_result['items'] = $items;
			}
		}

		return $hold_result;
	}

	public function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		return parent::placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate);
		//return $this->placeHold($patron, $itemId, $pickupBranch, $cancelDate);
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch)
	{
		return parent::placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch);
		// TODO: Use Sierra APIs to place volume holds
	}

	function allowFreezingPendingHolds(){
		return false;
	}

	public function hasFastRenewAll(){
		return false;
	}

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		$username = trim($username);
		$password = trim($password);
		$loginMethod = $this->accountProfile->loginConfiguration;
		if ($loginMethod == 'barcode_pin'){
			//If we use user names, we may need to lookup the barcode by the user name.
//			$params = [
//				'varFieldTag' => 'i',
//				'varFieldContent' => $username,
//				'fields' => 'id,barcodes'
//			];
//			$sierraUrl = $this->accountProfile->vendorOpacUrl;
//			$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/find?";
//			$sierraUrl .= http_build_query($params);
//			$patronInfo = $this->_callUrl('sierra.getPatronByUsername', $sierraUrl);
//			if (!empty($patronInfo->barcodes)){
//				$username = reset($patronInfo->barcodes);
//			}

			//No validate the barcode and pin
			$params = [
				'barcode' => $username,
				'pin' => $password
			];

			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/validate";
			$this->_postPage('sierra.validatePatron', $sierraUrl, json_encode($params));
			if ($this->lastResponseCode != 204){
				return false;
			}


		}else{ // $loginMethod == 'name_barcode'

		}

		//We've passed validation, get information for the patron
		$patronInfo = $this->getPatronInfoByBarcode($username);

		if (!$patronInfo){
			return false;
		}

		$userExistsInDB = false;
		$user = new User();
		$user->source = $this->accountProfile->name;
		$user->username = $patronInfo->id;
		if ($user->find(true)){
			$userExistsInDB = true;
		}
		$user->cat_username = $username;
		$user->cat_password = $password;

		$forceDisplayNameUpdate = false;
		$primaryName = reset($patronInfo->names);
		if (strpos($primaryName, ',') !== false){
			list($firstName, $lastName) = explode(',', $primaryName, 2);
		}else{
			$lastName = $primaryName;
			$firstName = '';
		}
		$firstName = trim($firstName);
		$lastName = trim($lastName);
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

		$this->loadContactInformationFromApiResult($user, $patronInfo);

		if ($userExistsInDB) {
			$user->update();
		} else {
			$user->created = date('Y-m-d');
			$user->insert();
		}
		return $user;
	}

	public function getPatronInfoByBarcode($barcode){
		$params = [
			'varFieldTag' => 'b',
			'varFieldContent' => $barcode,
			'fields' => 'id,names,deleted,suppressed,addresses,phones,emails,expirationDate,homeLibraryCode,moneyOwed,patronType,patronCodes,blockInfo,message,pMessage,langPref,fixedFields,varFields,updatedDate,createdDate'
		];

		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl .= "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/find?";
		$sierraUrl .= http_build_query($params);

		$response = $this->_callUrl('sierra.findPatronByBarcode', $sierraUrl);
		if (!$response){
			return false;
		}else{
			if ($response->deleted || $response->suppressed){
				return false;
			}else{
				return $response;
			}
		}
	}

	public function findNewUser($patronBarcode)
	{
		$patronId = $this->getPatronInfoByBarcode($patronBarcode);

		if (!$patronId){
			return false;
		}
		return parent::findNewUser($patronBarcode);
	}

	public function getAccountSummary(User $patron): AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();
		$patronInfo = $this->getPatronInfoByBarcode($patron->getBarcode());
		if ($patronInfo){
			$checkouts = $this->getCheckouts($patron);
			$summary->numCheckedOut = count($checkouts);
			foreach ($checkouts as $checkout) {
				if ($checkout->isOverdue()) {
					$summary->numOverdue++;
				}
			}

			$holds = $this->getHolds($patron);
			$summary->numAvailableHolds = count($holds['available']);
			$summary->numUnavailableHolds = count($holds['unavailable']);

			$summary->totalFines = $patronInfo->moneyOwed;

			list ($yearExp, $monthExp, $dayExp) = explode("-", $patronInfo->expirationDate);
			$summary->expirationDate = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
		}

		return $summary;
	}

	public function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade)
	{
		$result = [
			'success' => false,
			'messages' => []
		];

		if ($canUpdateContactInfo){
			global $library;
			$params = [];

			if (isset($_REQUEST['email'])){
				$patron->email = $_REQUEST['email'];
				$params['emails'] = [$_REQUEST['email']];
			}
			if ($library->allowPatronPhoneNumberUpdates) {
				$params['phones'] = [];
				if (isset($_REQUEST['phone'])) {
					$patron->phone = $_REQUEST['phone'];
					$tmpPhone = new stdClass();
					$tmpPhone->type = 'p';
					$tmpPhone->number = $_REQUEST['phone'];
					$params['phones'][] = $tmpPhone;
				}
			}
			if ($library->allowPatronAddressUpdates){
				$params['addresses'] = [];
				$address = new stdClass();
				$address->lines = [];
				$address->type = 'a';
				$address->lines[] = $_REQUEST['address1'];
				$cityStateZip = $_REQUEST['city'] . ', ' . $_REQUEST['state'] . ' ' . $_REQUEST['zip'];
				$address->lines[] = $cityStateZip;

				$params['addresses'][] = $address;
			}

			if (isset($_REQUEST['notices']) && !empty($_REQUEST['notices'])){
				$params['fixedFields'] = [];
				$noticeField = new stdClass();
				$fieldValue = new stdClass();
				$fieldValue->label = 'Notice Preference';
				$fieldValue->value = $_REQUEST['notices'];
				$noticeField->{'268'} = $fieldValue;
				$params['fixedFields']['268'] = $fieldValue;
			}

			$sierraUrl = $this->accountProfile->vendorOpacUrl;
			$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patron->username;
			$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($params));

			if ($this->lastResponseCode == 204){
				$result['success'] = true;
				$result['messages'][] = 'Your account was updated successfully.';
				$patron->update();
			}else{
				$result['messages'][] = 'Unable to update patron. ' . $this->lastErrorMessage;
			}
		}else{
			$result['messages'][] = 'You do not have permission to update profile information.';
		}

		return $result;
	}

	public function getSelfRegistrationFields()
	{
		return parent::getSelfRegistrationFields();
		// TODO: Use Sierra APIs to get Self Registration fields
	}

	public function selfRegister()
	{
		return parent::selfRegister();
		// TODO: Use Sierra APIs to self register
	}

	public function getFines($patron = null, $includeMessages = false)
	{
		$fines = [];

		$params = [
			'fields' => 'default,assessedDate,itemCharge,processingFee,billingFee,chargeType,paidAmount,datePaid,description,returnDate,location,description,invoiceNumber'
		];

		$patronId = $patron->username;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patronId."/fines?";
		$sierraUrl .= http_build_query($params);

		$finesResponse = $this->_callUrl('sierra.getFines', $sierraUrl);
		if ($finesResponse && $finesResponse->total > 0){
			foreach ($finesResponse->entries as $fineEntry){
				$fineUrl = $fineEntry->id;
				$fineId = substr($fineUrl, strrpos($fineUrl, '/') + 1);
				$fineAmount = $fineEntry->itemCharge + $fineEntry->processingFee + $fineEntry->billingFee;
				$message = '';
				if (isset($fineEntry->description)){
					$message = $fineEntry->description;
				}else{
					if (isset($fineEntry->item)){
						preg_match($this->urlIdRegExp, $fineEntry->item, $m);
						$itemIdShort = $m[1];
						$itemId = ".i" . $itemIdShort . $this->getCheckDigit($itemIdShort);
						$bibId = $this->getBibIdForItem($itemId, $itemIdShort);
						if ($bibId != false){
							require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
							$recordDriver = new MarcRecordDriver((string)$bibId);
							if ($recordDriver->isValid()){
								$message = $recordDriver->getTitle();
							}else{
								$bibIdShort = substr(str_replace('.b', 'b', $bibId), 0, -1);
								$getBibResponse = $this->_callUrl('sierra.getBib', $this->accountProfile->vendorOpacUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/bibs/{$bibIdShort}");
								if ($getBibResponse){
									$message = $getBibResponse->title;
								}
							}
						}
					}
				}
				$fines[] = [
					'fineId' => $fineId,
					'reason' => $fineEntry->chargeType->display,
					'type' => $fineEntry->chargeType->display,
					'amount' => $fineAmount,
					'amountVal' => $fineAmount,
					'message' => $message,
					'amountOutstanding' => $fineAmount - $fineEntry->paidAmount,
					'amountOutstandingVal' => $fineAmount - $fineEntry->paidAmount,
					'date' => date('M j, Y', strtotime($fineEntry->assessedDate)),
					'invoiceNumber' => $fineEntry->invoiceNumber,
				];
			}
		}
		return $fines;
	}

	public function completeFinePayment(User $patron, UserPayment $payment){
		$result = [
			'success' => false,
			'message' => ''
		];

		$userFines = $this->getFines($patron);

		//Before adding payments, we need to

		$paymentParams = [
			'payments' => []
		];

		$finePayments = explode(',', $payment->finesPaid);
		foreach ($finePayments as $finePayment){
			list($fineId, $paymentAmount) = explode('|', $finePayment);

			//Find the fine in the list of user payments so we can tell if it's fully paid or partially paid
			$fineInvoiceNumber = '';
			$oldTotal = 0;
			foreach ($userFines as $userFine){
				if ($userFine['fineId'] == $fineId){
					$oldTotal = $userFine['amountOutstandingVal'];
					$fineInvoiceNumber = $userFine['invoiceNumber'];
					break;
				}
			}

			$paymentType = 1; //Fully or partially paid, do not waive the remainder

			$tmpPayment = new stdClass();
			$tmpPayment->amount = (int)((float)$paymentAmount * 100);
			$tmpPayment->paymentType = $paymentType;
			$tmpPayment->invoiceNumber = (string)$fineInvoiceNumber;
			$tmpPayment->initials = 'aspen';

			$paymentParams['payments'][] = $tmpPayment;
		}

		$patronId = $patron->username;
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patronId."/fines/payment";

		$makePaymentResponse = $this->_sendPage('sierra.addPayment', 'PUT', $sierraUrl, json_encode($paymentParams));

		if ($this->lastResponseCode == 200 || $this->lastResponseCode == 204){
			$result['success'] = true;
		}else{
			$result['success'] = false;
			$result['message'] = 'Could not record fine payment.';
			if (isset($makePaymentResponse->description)){
				$result['message'] .= ' '. $makePaymentResponse->description;
			}
		}
	}


	public function getEmailResetPinTemplate(){
		return 'requestPinReset.tpl';
	}

	public function getEmailResetPinResultsTemplate(){
		return 'requestPinResetResults.tpl';
	}

	public function processEmailResetPinForm()
	{
		return parent::processEmailResetPinForm();
		// TODO: Use Sierra APIs for PIN Reset
	}

	function importListsFromIls($patron)
	{
		//There is no way to do this from the APIs so we need to resort to screen scraping.
		return parent::importListsFromIls($patron);
	}

	private function loadContactInformationFromApiResult(User $user, stdClass $patronInfo)
	{
		$user->_fullname = reset($patronInfo->names);
		if (!empty($patronInfo->addresses)) {
			$primaryAddress = reset($patronInfo->addresses);
			$user->_address1 = $primaryAddress->lines[0];
			$line2 = $primaryAddress->lines[1];
			if (strpos($line2, ',')){
				$user->_city = substr($line2, 0, strrpos($line2, ','));
				$stateZip = substr($line2, strrpos($line2, ','));
				$user->_state = substr($stateZip, 0, strrpos($stateZip, ' '));
				$user->_zip = substr($stateZip, strrpos($stateZip, ' '));
			}else{
				$user->_city = $line2;
			}
		}
		$primaryPhone = reset($patronInfo->phones);
		if (!empty($primaryPhone)){
			$user->phone = $primaryPhone->number;
		}
		$user->email = reset($patronInfo->emails);

		$homeLocationCode = $patronInfo->homeLibraryCode;
		$location = new Location();
		$location->code = $homeLocationCode;
		if (!$location->find(true)) {
			unset($location);
		}

		if (empty($user->homeLocationId) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
			if (empty($user->homeLocationId) && !isset($location)) {
				// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
				// try to find the main branch to assign to user
				// or the first location for the library
				global $library;

				$location = new Location();
				$location->libraryId = $library->libraryId;
				$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
				if (!$location->find(true)) {
					// Seriously no locations even?
					global $logger;
					$logger->log('Failed to find any location to assign to user as home location', Logger::LOG_ERROR);
					unset($location);
				}
			}
			if (isset($location)) {
				$user->homeLocationId = $location->locationId;
				if (empty($user->myLocation1Id)) {
					$user->myLocation1Id = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
					//Get display name for preferred location 1
					$myLocation1 = new Location();
					$myLocation1->locationId = $user->myLocation1Id;
					if ($myLocation1->find(true)) {
						$user->_myLocation1 = $myLocation1->displayName;
					}
				}

				if (empty($user->myLocation2Id)) {
					$user->myLocation2Id = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
					//Get display name for preferred location 2
					$myLocation2 = new Location();
					$myLocation2->locationId = $user->myLocation2Id;
					if ($myLocation2->find(true)) {
						$user->_myLocation2 = $myLocation2->displayName;
					}
				}
			}
		}

		if (isset($location)) {
			//Get display names that aren't stored
			$user->_homeLocationCode = $location->code;
			$user->_homeLocation = $location->displayName;
		}

		if ($patronInfo->expirationDate){
			$user->_expires = $patronInfo->expirationDate;
			list ($yearExp, $monthExp, $dayExp) = explode("-", $user->_expires);
			$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
			$timeNow = time();
			$timeToExpire = $timeExpire - $timeNow;
			if ($timeToExpire <= 30 * 24 * 60 * 60) {
				if ($timeToExpire <= 0) {
					$user->_expired = 1;
				}
				$user->_expireClose = 1;
			}
		}

		$finesVal = $patronInfo->moneyOwed;
		$user->_fines = sprintf('$%01.2f', $finesVal);
		$user->_finesVal = $finesVal;
		$user->patronType = $patronInfo->patronType;
		$user->_notices = $patronInfo->fixedFields->{'268'}->value;
		switch ($user->_notices) {
			case '-':
				$user->_noticePreferenceLabel = 'none';
				break;
			case 'a':
				$user->_noticePreferenceLabel = 'Mail';
				break;
			case 'p':
				$user->_noticePreferenceLabel = 'Telephone';
				break;
			case 'z':
				$user->_noticePreferenceLabel = 'Email';
				break;
			default:
				$user->_noticePreferenceLabel = 'none';
		}
	}

	function getPasswordPinValidationRules(){
		return [
			'minLength' => 4,
			'maxLength' => 60,
			'onlyDigitsAllowed' => false,
		];
	}

	function updatePin(User $patron, string $oldPin, string $newPin)
	{
		if ($patron->cat_password != $oldPin) {
			return ['success' => false, 'message' => "The old PIN provided is incorrect."];
		}
		$result = ['success' => false, 'message' => "Unknown error updating password."];
		$params = [
			'pin' => $newPin,
		];
		$sierraUrl = $this->accountProfile->vendorOpacUrl;
		$sierraUrl = $sierraUrl . "/iii/sierra-api/v{$this->accountProfile->apiVersion}/patrons/".$patron->username;
		$updatePatronResponse = $this->_sendPage('sierra.updatePatron', 'PUT', $sierraUrl, json_encode($params));
		if ($this->lastResponseCode == 204){
			$result['success'] = true;
			$result['message'] = 'Your password was updated successfully.';
			$patron->cat_password = $newPin;
			$patron->update();
		}else{
			$message = 'Unable to update PIN. ';
			if (!empty($this->lastErrorMessage)){
				$message .= $this->lastErrorMessage;
			}
			if (!empty($updatePatronResponse) && !empty($updatePatronResponse->description)){
				$message .= '<br/>' . $updatePatronResponse->description;
			}
			$result['message'] = $message;
		}
		return $result;
	}
}