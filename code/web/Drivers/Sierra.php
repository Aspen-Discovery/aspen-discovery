<?php

require_once ROOT_DIR . '/Drivers/Millennium.php';
class Sierra extends Millennium{
	protected $urlIdRegExp = "/.*\/(\d*)$/";

	public function _connectToApi($forceNewConnection = false){
		/** @var Memcache $memCache */
		global $memCache;
		$tokenData = $memCache->get('sierra_token');
		if ($forceNewConnection || $tokenData == false){
			global $configArray;
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
			$tokenData = json_decode($return);
			if ($tokenData){
				$memCache->set('sierra_token', $tokenData, $tokenData->expires_in - 10);
			}
		}
		return $tokenData;
	}

	public function _callUrl($requestType, $url){
		$tokenData = $this->_connectToAPI();
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			$headers = array(
					"Authorization: " . $tokenData->token_type . " {$tokenData->access_token}",
					"User-Agent: Aspen Discovery",
					"X-Forwarded-For: " . IPAddress::getActiveIp(),
					"Host: " . $_SERVER['SERVER_NAME'],
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			$return = curl_exec($ch);
			$curl_info = curl_getinfo($ch);
			$responseCode = $curl_info['http_code'];

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
			$headers = array(
				"Authorization: " . $tokenData->token_type . " {$tokenData->access_token}",
				"User-Agent: Aspen Discovery",
				"X-Forwarded-For: " . IPAddress::getActiveIp(),
				"Host: " . $_SERVER['SERVER_NAME'],
			);
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

//	public function patronLogin($username, $password, $validatedViaSSO)
//	{
//		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
//			//
//		}
//	}public function patronLogin($username, $password, $validatedViaSSO)
//	{
//		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
//			//
//		}
//	}

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
			$sierraUrl .= "?fields=default,pickupByDate,frozen,priority,priorityQueueLength,notWantedBeforeDate,notNeededAfterDate&limit=1000&expand=record";
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
			if ($sierraHold->recordType == 'i') {
				$recordItemStatus = $sierraHold->record->status->code;
				// If this is an inn-reach exclude from check -- this comes later
				if(! strstr($sierraHold->record->id, "@")) {
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
			// type hint so '0' != false
			switch ((string)$recordStatus) {
				case '0':
				case '-':
					if($sierraHold->frozen) {
						$status = "Frozen";
					} else {
						$status = 'On hold';
					}
					$cancelable = true;
					$freezeable = true;
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
			$curHold->canFreeze = $freezeable;
			$curHold->cancelable = $cancelable;
			$curHold->locationUpdateable = $updatePickup;
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
			if(strstr($sierraHold->record->id, "@")) {
				//TODO: Handle INNREACH Holds
			} else {
				///////////////
				// ILS HOLD
				//////////////
				// record type and record id
				$recordType = $sierraHold->recordType;
				// for item level holds we need to grab the bib id.
				$id = $sierraHold->record->id; //$m[1];
				if($recordType == 'i') {
					$itemId = ".i{$id}" . $this->getCheckDigit($id);
					$id = $this->getBibIdForItem($itemId);
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
			if($sierraHold->status->code == "b" || $sierraHold->status->code == "j" || $sierraHold->status->code == "i") {
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
		if ($readingHistoryEnabled){
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
				$bibId = $this->getBibIdForItem($itemId);

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
		if (!$renewResponse){
			return [
				'success' => false,
				'message' => "Unable to renew your checkout"
			];
		}

		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ":" . $recordId);
		if ($recordDriver->isValid()) {
			$title = $recordDriver->getTitle();
		} else {
			$title = false;
		}

		$return = ['success' => true];
		if($title) {
			$return['message'] = $title.' has been renewed.';
		} else {
			$return['message'] = 'Your item has been renewed';
		}

		$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
		return $return;
	}

	/**
	 * @param string $itemId
	 * @return string|false
	 */
	private function getBibIdForItem(string $itemId)
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
			$id = false;
		}
		return $id;
	}
}