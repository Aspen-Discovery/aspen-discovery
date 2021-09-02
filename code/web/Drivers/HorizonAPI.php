<?php

require_once ROOT_DIR . '/Drivers/Horizon.php';

abstract class HorizonAPI extends Horizon{

	//TODO: Additional caching of sessionIds by patron
	private static $sessionIdsForUsers = array();
	/** uses SIP2 login the user via web services API **/
	public function patronLogin($username, $password, $validatedViaSSO){
		global $timer;
		global $configArray;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Authenticate the user via WebService
		//First call loginUser
		list($userValid, $sessionToken, $userID) = $this->loginViaWebService($username, $password);
		if ($validatedViaSSO){
			$userValid = true;
		}
		if ($userValid){
			if (!empty($this->accountProfile->patronApiUrl)) {
				$webServiceURL = $this->accountProfile->patronApiUrl;
			} else {
				global $logger;
				$logger->log('No Web Service URL defined in Horizon API Driver', Logger::LOG_ALERT);
                echo("Web service URL must be defined in the account profile to work with the Horizon API");
                die();
			}
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeAddressInfo=true&includeHoldInfo=true&includeBlockInfo=true&includeItemsOutInfo=true');
			if ($lookupMyAccountInfoResponse){
				$fullName = (string)$lookupMyAccountInfoResponse->name;
				list($fullName, $lastName, $firstName) = $this->splitFullName($fullName);

				$email = '';
				if (isset($lookupMyAccountInfoResponse->AddressInfo)){
					if (isset($lookupMyAccountInfoResponse->AddressInfo->email)){
						$email = (string)$lookupMyAccountInfoResponse->AddressInfo->email;
					}
				}

				$userExistsInDB = false;
				$user = new User();
//				$user->source = $this->accountProfile->name;
				$user->username = $userID;
				if ($user->find(true)){
					$userExistsInDB = true;
				}

				$forceDisplayNameUpdate = false;
				$firstName = isset($firstName) ? $firstName : '';
				if ($user->firstname != $firstName) {
					$user->firstname = $firstName;
					$forceDisplayNameUpdate = true;
				}
				$lastName = isset($lastName) ? $lastName : '';
				if ($user->lastname != $lastName){
					$user->lastname = isset($lastName) ? $lastName : '';
					$forceDisplayNameUpdate = true;
				}
				if ($forceDisplayNameUpdate){
					$user->displayName = '';
				}
				$user->_fullname = isset($fullName) ? $fullName : '';
				$user->cat_username = $username;
				$user->cat_password = $password;
				$user->email = $email;

				if (isset($lookupMyAccountInfoResponse->AddressInfo)){
					$Address1 = (string)$lookupMyAccountInfoResponse->AddressInfo->line1;
					if (isset($lookupMyAccountInfoResponse->AddressInfo->cityState)){
						$cityState = (string)$lookupMyAccountInfoResponse->AddressInfo->cityState;
						list($City, $State) = explode(', ', $cityState);
					}else{
						$City = "";
						$State = "";
					}
					$Zip = (string)$lookupMyAccountInfoResponse->AddressInfo->postalCode;

				}else{
					$Address1 = "";
					$City = "";
					$State = "";
					$Zip = "";
				}

				//Get additional information about the patron's home branch for display.
				if (isset($lookupMyAccountInfoResponse->locationID)){
					$homeBranchCode = strtolower(trim((string)$lookupMyAccountInfoResponse->locationID));
					//Translate home branch to plain text
					$location = new Location();
					$location->code = $homeBranchCode;
//					$location->find(1);
					if (!$location->find(true)){
						unset($location);
					}
				} else {
					global $logger;
					$logger->log('HorizonAPI Driver: No Home Library Location or Hold location found in account look-up. User : '.$user->id, Logger::LOG_ERROR);
					// The code below will attempt to find a location for the library anyway if the homeLocation is already set
				}

				if (empty($user->homeLocationId) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
					if (empty($user->homeLocationId) && !isset($location)) {
						// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
						// try to find the main branch to assign to user
						// or the first location for the library
						global $library;

						$location            = new Location();
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
							$user->myLocation1Id  = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
							/** @var /Location $location */
							//Get display name for preferred location 1
							$myLocation1             = new Location();
							$myLocation1->locationId = $user->myLocation1Id;
							if ($myLocation1->find(true)) {
								$user->_myLocation1 = $myLocation1->displayName;
							}
						}

						if (empty($user->myLocation2Id)){
							$user->myLocation2Id  = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
							//Get display name for preferred location 2
							$myLocation2             = new Location();
							$myLocation2->locationId = $user->myLocation2Id;
							if ($myLocation2->find(true)) {
								$user->_myLocation2 = $myLocation2->displayName;
							}
						}
					}
				}

				if (isset($location)){
					//Get display names that aren't stored
					$user->_homeLocationCode = $location->code;
					$user->_homeLocation     = $location->displayName;
				}


				//TODO: See if we can get information about card expiration date
				$expireClose = 0;

				$finesVal = 0;
				if (isset($lookupMyAccountInfoResponse->BlockInfo)){
					foreach ($lookupMyAccountInfoResponse->BlockInfo as $block){
						// $block is a simplexml object with attribute info about currency, type casting as below seems to work for adding up. plb 3-27-2015
						$fineAmount = (float) $block->balance;
						$finesVal += $fineAmount;
					}
				}

				$numHoldsAvailable = 0;
				$numHoldsRequested = 0;
				if (isset($lookupMyAccountInfoResponse->HoldInfo)){
					foreach ($lookupMyAccountInfoResponse->HoldInfo as $hold){
						if ($hold->status == 'FILLED'){
							$numHoldsAvailable++;
						}else{
							$numHoldsRequested++;
						}
					}
				}

				$user->_address1              = $Address1;
				$user->_address2              = $City . ', ' . $State;
				$user->_city                  = $City;
				$user->_state                 = $State;
				$user->_zip                   = $Zip;
				$user->phone                 = isset($lookupMyAccountInfoResponse->phone) ? (string)$lookupMyAccountInfoResponse->phone : '';
				$user->_fines                 = sprintf('$%01.2f', $finesVal);
				$user->_finesVal              = $finesVal;
				$user->_expires               = ''; //TODO: Determine if we can get this
				$user->_expireClose           = $expireClose;
				$user->_numCheckedOutIls      = isset($lookupMyAccountInfoResponse->ItemsOutInfo) ? count($lookupMyAccountInfoResponse->ItemsOutInfo) : 0;
				$user->_numHoldsIls           = $numHoldsAvailable + $numHoldsRequested;
				$user->_numHoldsAvailableIls  = $numHoldsAvailable;
				$user->_numHoldsRequestedIls  = $numHoldsRequested;
				$user->patronType            = 0;
				$user->_notices               = '-';
				$user->_noticePreferenceLabel = 'Email';
				$user->_web_note              = '';

				if ($userExistsInDB){
					$user->update();
				}else{
					$user->created = date('Y-m-d');
					$user->insert();
				}

				$timer->logTime("patron logged in successfully");
				return $user;
			} else {
				$timer->logTime("lookupMyAccountInfo failed");
				global $logger;
				$logger->log('Horizon API call lookupMyAccountInfo failed.', Logger::LOG_ERROR);
				return null;
			}
		}
		return null;
	}

	protected function loginViaWebService($username, $password) {
		global $configArray;
		$loginUserUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/loginUser?clientID=' . $configArray['Catalog']['clientId'] . '&login=' . urlencode($username) . '&password=' . urlencode($password);
		$loginUserResponse = $this->getWebServiceResponse($loginUserUrl);
		if (!$loginUserResponse){
			return array(false, false, false);
		}else if (isset($loginUserResponse->Fault)){
			return array(false, false, false);
		}else{
			//We got at valid user, next call lookupMyAccountInfo
			if (isset($loginUserResponse->sessionToken)){
				$userID = (string)$loginUserResponse->userID;
				$sessionToken = (string)$loginUserResponse->sessionToken;
				HorizonAPI::$sessionIdsForUsers[$userID] = $sessionToken;
				return array(true, $sessionToken, $userID);
			}else{
				return array(false, false, false);
			}
		}
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron    The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getHolds($patron){
		global $configArray;

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available'   => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$patron->id])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$patron->id];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return $holds;
			}
		}

		//Now that we have the session token, get holds information
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeHoldInfo=true');
		if (isset($lookupMyAccountInfoResponse->HoldInfo)){
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			foreach ($lookupMyAccountInfoResponse->HoldInfo as $hold){
				$curHold = array();
				$bibId          = (string) $hold->titleKey;
				$expireDate     = (string) $hold->expireDate;
				$reactivateDate = (string) $hold->reactivateDate;
				$curHold['user']               = $patron->getNameAndLibraryLabel(); //TODO: Likely not needed, because Done in Catalog Connection
				$curHold['id']                 = $bibId;
				$curHold['holdSource']         = 'ILS';
				$curHold['itemId']             = (string)$hold->itemKey;
				$curHold['cancelId']           = (string)$hold->holdKey;
				$curHold['position']           = (string)$hold->queuePosition;
				$curHold['recordId']           = $bibId;
				$curHold['shortId']            = $bibId;
				$curHold['title']              = (string)$hold->title;
				$curHold['sortTitle']          = (string)$hold->title;
				$curHold['author']             = (string)$hold->author;
				$curHold['location']           = (string)$hold->pickupLocDescription;
				$curHold['locationUpdateable'] = true;
				$curHold['currentPickupName']  = $curHold['location'];
				$curHold['status']             = ucfirst(strtolower((string)$hold->status));
				$curHold['expire']             = strtotime($expireDate);
				$curHold['reactivate']         = $reactivateDate;
				$curHold['reactivateTime']     = strtotime($reactivateDate);
				$curHold['cancelable']         = strcasecmp($curHold['status'], 'Suspended') != 0;
				$curHold['frozen']             = strcasecmp($curHold['status'], 'Suspended') == 0;
				$curHold['canFreeze'] = true;
				if (strcasecmp($curHold['status'], 'Transit') == 0) {
					$curHold['canFreeze'] = false;
				}

				$recordDriver = new MarcRecordDriver($bibId);
				if ($recordDriver->isValid()){
					$curHold['sortTitle']       = $recordDriver->getSortableTitle();
					$curHold['format']          = $recordDriver->getFormat();
					$curHold['isbn']            = $recordDriver->getCleanISBN();
					$curHold['upc']             = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();
					$curHold['coverUrl']        = $recordDriver->getBookcoverUrl('medium', true);
					$curHold['link']            = $recordDriver->getLinkUrl();

					//Load rating information
					$curHold['ratingData']      = $recordDriver->getRatingData();

					if (empty($curHold['title'])){
						$curHold['title'] = $recordDriver->getTitle();
					}
					if (empty($curHold['author'])){
						$curHold['author'] = $recordDriver->getPrimaryAuthor();
					}
				}

				if (!isset($curHold['status']) || strcasecmp($curHold['status'], "filled") != 0){
					$holds['unavailable'][] = $curHold;
				}else{
					$holds['available'][]   = $curHold;
				}
			}
		}
		return $holds;
	}

    /**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param User $patron              The User to place a hold for
     * @param string $recordId          The id of the bib record
     * @param string $pickupBranch      The branch where the user wants to pickup the item when available
     * @param null|string $cancelDate   The date when the patron no longer needs the item
     * @return  mixed                   True if successful, false if unsuccessful
     *                                  If an error occurs, return a AspenError
     * @access  public
     */
	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		$result = $this->placeItemHold($patron, $recordId, null, $pickupBranch);
		return $result;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User    $patron     The User to place a hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $comment    Any comment regarding the hold or recall
	 * @param   string  $type       Whether to place a hold or recall
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold(User $patron, $recordId, $itemId, $comment = '', $type = 'request') {
		global $configArray;

		$userId = $patron->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return array(
					'success' => false,
					'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		// Retrieve Full Marc Record
		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById('ils:' . $recordId);
		if (!$record) {
			$title = null;
		}else{
			$title = $record->getTitle();
		}

		global $offlineMode;
		if ($offlineMode){
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold = new OfflineHold();
			$offlineHold->bibId = $recordId;
			$offlineHold->patronBarcode = $patron->getBarcode();
			$offlineHold->patronId = $patron->id;
			$offlineHold->timeEntered = time();
			$offlineHold->status = 'Not Processed';
			if ($offlineHold->insert()){
				//TODO: use bib or bid ??
				return array(
					'title'   => $title,
					'bib'     => $recordId,
					'success' => true,
					'message' => 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.');
			}else{
				return array(
					'title'   => $title,
					'bib'     => $recordId,
					'success' => false,
					'message' => 'The circulation system is currently offline and we could not place this hold.  Please try again later.');
			}

		}else{
			if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
				$result = $this->updateHold($patron, $recordId, $type/*, $title*/);
				$result['title'] = $title;
				$result['bid']   = $recordId;
				return $result;

			} else {
				if (isset($_REQUEST['pickupBranch'])){
					$pickupBranch=trim($_REQUEST['pickupBranch']);
				}else{
					$pickupBranch = $patron->homeLocationId;
				}
				//create the hold using the web service
				$createHoldUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/createMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&pickupLocation=' . $pickupBranch . '&titleKey=' . $recordId ;
				if ($itemId){
					$createHoldUrl .= '&itemKey=' . $itemId;
				}

				$createHoldResponse = $this->getWebServiceResponse($createHoldUrl);

				$hold_result = array();
				if ($createHoldResponse == true){
					$hold_result['success'] = true;
					$hold_result['message'] = translate(['text'=>"ils_hold_success", 'defaultText'=>"Your hold was placed successfully."]);
				}else{
					$hold_result['success'] = false;
					$hold_result['message'] = translate(['text'=>"ils_hold_failed", 'defaultText'=>"Your hold could not be placed."]);
					if (isset($createHoldResponse->message)){
						$hold_result['message'] .= (string)$createHoldResponse->message;
					}else if (isset($createHoldResponse->string)){
						$hold_result['message'] .= (string)$createHoldResponse->string;
					}

				}

				$hold_result['title']  = $title;
				$hold_result['bid']    = $recordId;
				//Clear the patron profile
				return $hold_result;

			}
		}
	}

	function cancelHold(User $patron, $recordId, $cancelId = null) {
		return $this->updateHoldDetailed($patron, 'cancel', null, $cancelId, '', '');
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToFreezeId, '', 'on');
	}

	function thawHold(User $patron, $recordId, $itemToThawId) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToThawId, '', 'off');
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		return $this->updateHoldDetailed($patron, 'update', null, $itemToUpdateId, $newPickupLocation, 'off');
	}

	public function updateHold($requestId, $patronId, $type){
		$xnum = "x" . $_REQUEST['x'];
		//Strip the . off the front of the bib and the last char from the bib
		if (isset($_REQUEST['cancelId'])){
			$cancelId = $_REQUEST['cancelId'];
		}else{
			$cancelId = substr($requestId, 1, -1);
		}
		$locationId = $_REQUEST['location'];
		$freezeValue = isset($_REQUEST['freeze']) ? 'on' : 'off';
		return $this->updateHoldDetailed($patronId, $type, $xnum, $cancelId, $locationId, $freezeValue);
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 */
	public function updateHoldDetailed($patron, $type, $xNum, $cancelId, $locationId, $freezeValue='off'){
		global $configArray;

		$patronId = $patron->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$patronId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$patronId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return array(
					'success' => false,
					'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		if (!isset($xNum)){
			$holdKeys = is_array($cancelId) ? $cancelId : array($cancelId);
		}

        $holds = $this->getHolds($patron);
        $combined_holds = array_merge($holds['unavailable'], $holds['available']);

		$titles = array();
		if ($type == 'cancel'){
			$allCancelsSucceed = true;
			$failure_messages = array();

			foreach ($holdKeys as $holdKey){
				$title = 'an item';  // default in case title name isn't found.

				foreach ($combined_holds as $hold){
					if ($hold['cancelId'] == $holdKey) {
						$title = $hold['title'];
						break;
					}
				}
				$titles[] = $title; // build array of all titles


				//create the hold using the web service
				$cancelHoldUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/cancelMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey;

				$cancelHoldResponse = $this->getWebServiceResponse($cancelHoldUrl);

				if ($cancelHoldResponse){
					//Clear the patron profile
				}else{
					$allCancelsSucceed = false;
					$failure_messages[$holdKey] = "The hold for $title could not be cancelled.  Please try again later or see your librarian.";
				}
			}
			if ($allCancelsSucceed){
				$plural = count($holdKeys) > 1;

				return array(
					'title' => $titles,
					'success' => true,
					'message' => 'Your hold'.($plural ? 's were' : ' was' ).' cancelled successfully.');
			}else{
				return array(
					'title' => $titles,
					'success' => false,
					'message' => $failure_messages
				);
			}

		}else{
			if ($locationId){
				$allLocationChangesSucceed = true;

				foreach ($holdKeys as $holdKey){

					foreach ($combined_holds as $hold){
						if ($hold['cancelId'] == $holdKey) {
							$title = $hold['title'];
							break;
						}
					}
					$titles[] = $title; // build array of all titles

					//create the hold using the web service
					$changePickupLocationUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/changePickupLocation?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey . '&newLocation=' . $locationId;

					$changePickupLocationResponse = $this->getWebServiceResponse($changePickupLocationUrl);

					if ($changePickupLocationResponse){
						//Clear the patron profile
					}else{
						$allLocationChangesSucceed = false;
					}
				}
				if ($allLocationChangesSucceed){
					return array(
						'title' => $titles,
						'success' => true,
						'message' => 'Pickup location for your hold(s) was updated successfully.');
				}else{
					return array(
						'title' => $titles,
						'success' => false,
						'message' => 'Pickup location for your hold(s) was could not be updated.  Please try again later or see your librarian.');
				}
			}else{
				//Freeze/Thaw the hold
				if ($freezeValue == 'on'){
					//Suspend the hold
					$reactivationDate = strtotime($_REQUEST['reactivationDate']);
					$reactivationDate = date('Y-m-d', $reactivationDate);
					$allLocationChangesSucceed = true;

					foreach ($holdKeys as $holdKey){
						//create the hold using the web service
						$changePickupLocationUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/suspendMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey . '&suspendEndDate=' . $reactivationDate;

						$changePickupLocationResponse = $this->getWebServiceResponse($changePickupLocationUrl);

						if ($changePickupLocationResponse){
							//Clear the patron profile
						}else{
							$allLocationChangesSucceed = false;
						}
					}

					if ($allLocationChangesSucceed){
						return array(
							'title' => $titles,
							'success' => true,
							'message' => translate(['text' => "Your hold(s) were frozen successfully.", 'isPublicFacing'=>true]));
					}else{
						return array(
							'title' => $titles,
							'success' => false,
							'message' => translate(['text' => "Some holds could not be frozen.  Please try again later or see your librarian.", 'isPublicFacing'=>true]));
					}
				}else{
					//Reactivate the hold
					$allUnsuspendsSucceed = true;

					foreach ($holdKeys as $holdKey){
						//create the hold using the web service
						$changePickupLocationUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/unsuspendMyHold?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&holdKey=' . $holdKey;

						$changePickupLocationResponse = $this->getWebServiceResponse($changePickupLocationUrl);

						if ($changePickupLocationResponse){
							//Clear the patron profile
						}else{
							$allUnsuspendsSucceed = false;
						}
					}

					if ($allUnsuspendsSucceed){
						return array(
							'title' => $titles,
							'success' => true,
							'message' => translate(['text' => "Your hold(s) were thawed successfully.", 'isPublicFacing'=>true]));
					}else{
						return array(
							'title' => $titles,
							'success' => false,
							'message' => translate(['text' => "Some holds could not be thawed.  Please try again later or see your librarian.", 'isPublicFacing'=>true]));
					}
				}
			}
		}
	}

	public function getCheckouts($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate') {
		global $configArray;

		$userId = $patron->id;

		$checkedOutTitles = array();

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
//				echo("No session id found for user");
				return $checkedOutTitles;
			}
		}

		//Now that we have the session token, get checkouts information
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse($configArray['Catalog']['webServiceUrl'] . '/standard/lookupMyAccountInfo?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&includeItemsOutInfo=true');
		if (isset($lookupMyAccountInfoResponse->ItemsOutInfo)){
			$sCount = 0;
			foreach ($lookupMyAccountInfoResponse->ItemsOutInfo as $itemOut){
				$sCount++;
				$bibId = (string)$itemOut->titleKey;
				$curTitle['checkoutSource']  = 'ILS';
				$curTitle['recordId']        = $bibId;
				$curTitle['shortId']         = $bibId;
				$curTitle['id']              = $bibId;
				$curTitle['title']           = (string)$itemOut->title;
				$curTitle['author']          = (string)$itemOut->author;

				$curTitle['dueDate']         = strtotime((string)$itemOut->dueDate);
				$curTitle['checkoutDate']    = (string)$itemOut->ckoDate;
				$curTitle['renewCount']      = (string)$itemOut->renewals;
				$curTitle['canRenew']        = true; //TODO: Figure out if the user can renew the title or not
				$curTitle['renewIndicator']  = (string)$itemOut->itemBarcode;
				$curTitle['barcode']         = (string)$itemOut->itemBarcode;
				$curTitle['holdQueueLength'] = $this->getNumHolds($bibId);

				$curTitle['format']          = 'Unknown';
				if ($curTitle['id'] && strlen($curTitle['id']) > 0){
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = new MarcRecordDriver($curTitle['id']);
					if ($recordDriver->isValid()){
						$curTitle['coverUrl']      = $recordDriver->getBookcoverUrl('medium', true);
						$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
						$curTitle['ratingData']    = $recordDriver->getRatingData();
						$curTitle['format']        = $recordDriver->getPrimaryFormat();
						$curTitle['author']        = $recordDriver->getPrimaryAuthor();
						$curTitle['title']         = $recordDriver->getTitle();
						$curTitle['title_sort']    = $recordDriver->getSortableTitle();
						$curTitle['link']          = $recordDriver->getLinkUrl();
					}else{
						$curTitle['coverUrl'] = "";
					}
				}
				//TODO: Sort Keys Created in CheckedOut.php. Needed here?
				$sortTitle = isset($curTitle['title_sort']) ? $curTitle['title_sort'] : $curTitle['title'];
				$sortKey = $sortTitle;
				if ($sortOption == 'title'){
					$sortKey = $sortTitle;
				}elseif ($sortOption == 'author'){
					$sortKey = (isset($curTitle['author']) ? $curTitle['author'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'dueDate'){
					if (isset($curTitle['dueDate'])){
						if (preg_match('/.*?(\\d{1,2})[-\/](\\d{1,2})[-\/](\\d{2,4}).*/', $curTitle['dueDate'], $matches)) {
							$sortKey = $matches[3] . '-' . $matches[1] . '-' . $matches[2] . '-' . $sortTitle;
						} else {
							$sortKey = $curTitle['dueDate'] . '-' . $sortTitle;
						}
					}
				}elseif ($sortOption == 'format'){
					$sortKey = (isset($curTitle['format']) ? $curTitle['format'] : "Unknown") . '-' . $sortTitle;
				}elseif ($sortOption == 'renewed'){
					$sortKey = (isset($curTitle['renewCount']) ? $curTitle['renewCount'] : 0) . '-' . $sortTitle;
				}elseif ($sortOption == 'holdQueueLength'){
					$sortKey = (isset($curTitle['holdQueueLength']) ? $curTitle['holdQueueLength'] : 0) . '-' . $sortTitle;
				}
				$sortKey .= "_$sCount";
				$checkedOutTitles[$sortKey] = $curTitle;
			}
		}

		return $checkedOutTitles;
	}

	public function hasFastRenewAll(){
		return false;
	}

	public function renewAll(User $patron){
		return array(
			'success' => false,
			'message' => 'Renew All not supported directly, call through Catalog Connection',
		);
	}

	// TODO: Test with linked accounts (9-3-2015)
	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null){
		global $configArray;

		$userId = $patron->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			if (!$userValid){
				return array(
					'success' => false,
					'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again');
			}
		}

		//create the hold using the web service
		$renewCheckoutUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/renewMyCheckout?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&itemID=' . $itemId;

		$renewCheckoutResponse = $this->getWebServiceResponse($renewCheckoutUrl);

		if ($renewCheckoutResponse && !isset($renewCheckoutResponse->string)){
			$success = true;
			$message = 'Your item was successfully renewed.  The title is now due on ' . $renewCheckoutResponse->dueDate;
		}else{
			//TODO: check that title is included in the message
			$success = false;
			$message = $renewCheckoutResponse->string;
		}
		return array(
			'itemId' => $itemId,
			'success'  => $success,
			'message' => $message);
	}

	public function getNumHolds($id) {
		global $offlineMode;
		if (!$offlineMode){
			global $configArray;
			$lookupTitleInfoUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/lookupTitleInfo?clientID=' . $configArray['Catalog']['clientId'] . '&titleKey=' . $id . '&includeItemInfo=false&includeHoldCount=true' ;
			$lookupTitleInfoResponse = $this->getWebServiceResponse($lookupTitleInfoUrl);
			if ($lookupTitleInfoResponse->titleInfo){
				return (int)$lookupTitleInfoResponse->titleInfo->holdCount;
			}
		}

		return 0;
	}

    /**
     * @param User $user
     * @param string $oldPin
     * @param string $newPin
     * @return array
     */
    function updatePinUser(User $user, string $oldPin, string $newPin){
		global $configArray;
		$userId = $user->id;

		//Get the session token for the user
		if (isset(HorizonAPI::$sessionIdsForUsers[$userId])){
			$sessionToken = HorizonAPI::$sessionIdsForUsers[$userId];
		}else{
			//Log the user in
			list($userValid, $sessionToken) = $this->loginViaWebService($user->cat_username, $user->cat_password);
			if (!$userValid){
				return ['success' => false, 'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'];
			}
		}

		//create the hold using the web service
		$updatePinUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/changeMyPin?clientID=' . $configArray['Catalog']['clientId'] . '&sessionToken=' . $sessionToken . '&currentPin=' . $oldPin . '&newPin=' . $newPin;
		$updatePinResponse = $this->getWebServiceResponse($updatePinUrl);

		if ($updatePinResponse){
			$user->cat_password = $newPin;
			$user->update();
//			UserAccount::updateSession($user);  //TODO only if $user is the primary user
			return ['success' => true, 'message' => "Your pin number was updated successfully."];
		}else{
			return ['success' => false, 'message' => "Sorry, we could not update your pin number. Please try again later."];
		}
	}

	public function emailPin($barcode){
		global $configArray;
		if (empty($barcode)) {
			$barcode = $_REQUEST['barcode'];
		}

		//email the pin to the user
		$updatePinUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/emailMyPin?clientID=' . $configArray['Catalog']['clientId'] . '&secret=' . $configArray['Catalog']['clientSecret'] . '&login=' . $barcode . '&profile=' . $this->hipProfile;
		$updatePinResponse = $this->getWebServiceResponse($updatePinUrl);
		//$updatePinResponse is an XML object, at least when there is an error with the API call
		// otherwise, it is true for the pin sent, or false for pin not sent.

		if ($updatePinResponse && !isset($updatePinResponse->code)){
			return array(
				'success' => true,
			);
		}else{
			$result = array(
				'error' => "Sorry, we could not email your pin to you.  Please visit the library to reset your pin."
			);
			if (isset($updatePinResponse->code)){
				$result['error'] .= '  ' . $updatePinResponse->string;
			}
			return $result;
		}
	}

	public function getSelfRegistrationFields() {
		global $configArray;
		$lookupSelfRegistrationFieldsUrl = $configArray['Catalog']['webServiceUrl'] . '/standard/lookupSelfRegistrationFields?clientID=' . $configArray['Catalog']['clientId'];

		$lookupSelfRegistrationFieldsResponse = $this->getWebServiceResponse($lookupSelfRegistrationFieldsUrl);
		$fields = array();
		if ($lookupSelfRegistrationFieldsResponse){
			foreach($lookupSelfRegistrationFieldsResponse->registrationField as $registrationField){
				$newField = array(
					'property' => (string)$registrationField->column,
					'label' => (string)$registrationField->label,
					'maxLength' => (int)$registrationField->length,
					'type' => 'text',
					'required' => (string)$registrationField->required == 'true',
				);
				if ((string)$registrationField->masked == 'true'){
					$newField['type'] = 'password';
				}
				if (isset($registrationField->values)){
					$newField['type'] = 'enum';
					$values = array();
					foreach($registrationField->values->value as $value){
						$values[(string)$value->code] = (string)$value->description;
					}
					$newField['values'] = $values;
				}
				$fields[] = $newField;
			}
		}
		return $fields;
	}

	/**
	 * Split a name into firstName, lastName, middleName.
	 *a
	 * Assumes the name is entered as LastName, FirstName MiddleName
	 * @param $fullName
	 * @return array
	 */
	public function splitFullName($fullName) {
		$fullName = str_replace(",", " ", $fullName);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = str_replace(";", "'", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$nameParts = explode(' ', $fullName);
		$lastName = strtolower($nameParts[0]);
		$middleName = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstName = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middleName;
		return array($fullName, $lastName, $firstName);
	}

	public function getWebServiceResponse($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: utf-8'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$xml = curl_exec($ch);
		curl_close($ch);

		if ($xml !== false && $xml !== 'false'){
			if (strpos($xml, '<') !== FALSE){
				//Strip any non-UTF-8 characters
				$xml = preg_replace('/[^(\x20-\x7F)]*/','', $xml);

				libxml_use_internal_errors(true);
				$parsedXml = simplexml_load_string($xml);
				if ($parsedXml === false){
					//Failed to load xml
					global $logger;
					$logger->log("Error parsing xml", Logger::LOG_ERROR);
					$logger->log($xml, Logger::LOG_DEBUG);
					foreach(libxml_get_errors() as $error) {
						$logger->log("\t {$error->message}", Logger::LOG_ERROR);
					}
					return false;
				}else{
					return $parsedXml;
				}
			}else{
				return $xml;
			}
		}else{
			global $logger;
			$logger->log('Curl problem in getWebServiceResponse', Logger::LOG_WARNING);
			return false;
		}
	}

	public function hasNativeReadingHistory() {
		return false;
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
		return 'emailPin';
	}
}