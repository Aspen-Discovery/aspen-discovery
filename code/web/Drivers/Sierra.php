<?php

require_once ROOT_DIR . '/Drivers/Millennium.php';
class Sierra extends Millennium{
	protected $urlIdRegExp = "/.*\/(\d*)$/";

	public function getItemInfo($bibId){
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/items/{$bibId}";
		$itemData = $this->_callUrl($apiUrl);
		return $itemData;
	}

	public function getBib($bibId){
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/bibs/{$bibId}";
		$itemData = $this->_callUrl($apiUrl);
		return $itemData;
	}

	public function getMarc($bibId){
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/bibs/{$bibId}/marc";
		$itemData = $this->_callUrl($apiUrl);
		return $itemData;
	}

	public function getItemsForBib($bibId) {
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/items/?bibIds=$bibId";
		$itemData = $this->_callUrl($apiUrl);
		return $itemData;
	}

	public function getBibsChangedSince($date, $offset = 0) {
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/bibs/?updatedDate=[$date,]&limit=2000&fields=id&deleted=false&suppressed=false";
		$itemData = $this->_callUrl($apiUrl);
		$bibIds = array();
		if (isset($itemData->entries)){
			foreach ($itemData->entries as $entry){
				$bibIds[] = '.b' . $entry->id . $this->getCheckDigit($entry->id);
				//$bibIds[] = $entry->id;
			}
			if (count($itemData->entries) == 2000){
				$bibIds = array_merge($bibIds, $this->getBibsChangedSince($date, $offset + 2000));
			}
		}
		return $bibIds;
	}

	public function getBibsDeletedSince($date, $offset = 0) {
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/bibs/?deletedDate=[$date,]&limit=2000&fields=id&offset=$offset";
		$itemData = $this->_callUrl($apiUrl);
		$bibIds = array();
		if (isset($itemData->entries)){
			foreach ($itemData->entries as $entry){
				$bibIds[] = '.b' . $entry->id . $this->getCheckDigit($entry->id);
				//$bibIds[] = $entry->id;
			}
			if (count($itemData->entries) == 2000){
				$bibIds = array_merge($bibIds, $this->getBibsDeletedSince($date, $offset + 2000));
			}
		}
		return $bibIds;
	}

	public function getBibsCreatedSince($date, $offset = 0) {
		global $configArray;
		$apiVersion = $configArray['Catalog']['api_version'];
		$apiUrl = $this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/bibs/?createdDate=[$date,]&limit=2000&fields=id&deleted=false&suppressed=false&offset=$offset";
		$itemData = $this->_callUrl($apiUrl);
		$bibIds = array();
		if (isset($itemData->entries)){
			foreach ($itemData->entries as $entry){
				$bibIds[] = '.b' . $entry->id . $this->getCheckDigit($entry->id);
			}
			if (count($itemData->entries) == 2000){
				$bibIds = array_merge($bibIds, $this->getBibsCreatedSince($date, $offset + 2000));
			}
		}
		return $bibIds;
	}

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

	public function _callUrl($url){
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

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
			//
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
			$sierraUrl .= "?fields=default,pickupByDate,frozen,priority,priorityQueueLength,notWantedBeforeDate,notNeededAfterDate&limit=1000&expand=record";
		} else {
			$sierraUrl .= "?fields=default,frozen,priority,priorityQueueLength,notWantedBeforeDate,notNeededAfterDate&limit=1000";
		}
		$holds = $this->_callUrl($sierraUrl);

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
					require_once ROOT_DIR . '/sys/Grouping/GroupedWorkItem.php';
					require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
					$groupedWorkItem = new GroupedWorkItem();
					$groupedWorkItem->itemId = ".i{$id}" . $this->getCheckDigit($id);
					if ($groupedWorkItem->find(true)){
						$groupedWorkRecord = new GroupedWorkRecord();
						$groupedWorkRecord->id = $groupedWorkItem->groupedWorkRecordId;
						if ($groupedWorkRecord->find(true)){
							$id = $groupedWorkRecord->sourceId;
						}else{
							$id = false;
						}
					}else{
						$id = false;
					}
				}

				if ($id != false) {
					$recordXD = $this->getCheckDigit($id);
					$curHold->recordId = ".b{$id}{$recordXD}";
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
}