<?php

abstract class HorizonROA extends AbstractIlsDriver
{
	private static $sessionIdsForUsers = array();
	public function __construct($accountProfile){
		parent::__construct($accountProfile);
		$this->webServiceURL  = $this->getWebServiceURL();
	}
	/**
	 * Split a name into firstName, lastName, middleName.
	 *
	 * Assumes the name is entered as LastName, FirstName MiddleName
	 * @param $fullName
	 * @return array
	 */
	public function splitFullName($fullName) {
		$fullName   = str_replace(",", ' ', $fullName);
		$fullName   = str_replace(";", ' ', $fullName);
		$fullName   = preg_replace("/\\s{2,}/", ' ', $fullName);
		$nameParts  = explode(' ', $fullName);
		$lastName   = strtolower($nameParts[0]);
		$middleName = isset($nameParts[2]) ? strtolower($nameParts[2]) : '';
		$firstName  = isset($nameParts[1]) ? strtolower($nameParts[1]) : $middleName;
		$firstName  = trim($firstName, '()');
		return array($fullName, $lastName, $firstName);
	}

	// $customRequest is for curl, can be 'PUT', 'DELETE', 'POST'
	public function getWebServiceResponse($url, $params = null, $sessionToken = null, $customRequest = null, $additionalHeaders = null, $alternateClientId = null)
	{
		global $configArray;
		global $logger;
		$logger->log('WebServiceURL :' .$url, Logger::LOG_NOTICE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$clientId = empty($alternateClientId) ? $configArray['Catalog']['clientId'] : $alternateClientId;
		$headers  = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'SD-Originating-App-Id: ' . $configArray['System']['applicationName'],
			'x-sirs-clientID: ' . $clientId,
		);
		if ($sessionToken != null) {
			$headers[] = 'x-sirs-sessionToken: ' . $sessionToken;
		}
		if (!empty($additionalHeaders) && is_array($additionalHeaders)) {
			$headers = array_merge($headers, $additionalHeaders);
		}
		if (empty($customRequest)) {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ($customRequest == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (!$configArray['Site']['isProduction']) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}
		if ($params != null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$json = curl_exec($ch);
		if (!$configArray['Site']['isProduction']) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$err  = curl_getinfo($ch);
			/** @noinspection PhpUnusedLocalVariableInspection */
			$headerRequest = curl_getinfo($ch, CURLINFO_HEADER_OUT);
		}
		$logger->log("Web service response\r\n$json", Logger::LOG_NOTICE); //TODO: For debugging
		curl_close($ch);
		if ($json !== false && $json !== 'false') {
			return json_decode($json);
		} else {
			$logger->log('Curl problem in getWebServiceResponse', Logger::LOG_WARNING);
			return false;
		}
	}
	protected function loginViaWebService($username, $password)
	{
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "horizon_ROA_session_token_info_$username";
		$session = $memCache->get($memCacheKey);
		if ($session) {
			list(, $sessionToken, $horizonRoaUserID) = $session;
			self::$sessionIdsForUsers[$horizonRoaUserID] = $sessionToken;
		} else {
			$session = array(false, false, false);
			$webServiceURL = $this->getWebServiceURL();
//		$loginDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/user/patron/login/describe');
			$loginUserUrl      = $webServiceURL . '/user/patron/login';
			$params            = array(
				'login'    => $username,
				'password' => $password,
			);
			$loginUserResponse = $this->getWebServiceResponse($loginUserUrl, $params);
			if ($loginUserResponse && isset($loginUserResponse->sessionToken)) {
				//We got at valid user (A bad call will have isset($loginUserResponse->messageList) )
				$horizonRoaUserID                            = $loginUserResponse->patronKey;
				$sessionToken                                = $loginUserResponse->sessionToken;
				self::$sessionIdsForUsers[$horizonRoaUserID] = $sessionToken;
				$session = array(true, $sessionToken, $horizonRoaUserID);
				global $configArray;
				$memCache->set($memCacheKey, $session, $configArray['Caching']['horizon_roa_session_token']);
			} elseif (isset($loginUserResponse->messageList)) {
				global $logger;
				$errorMessage = 'Horizon ROA Webservice Login Error: ';
				foreach ($loginUserResponse->messageList as $error){
					$errorMessage .= $error->message.'; ';
				}
				$logger->log($errorMessage, Logger::LOG_ERROR);
			}
		}
		return $session;
	}
	private function getSessionToken($patron)
	{
		$horizonRoaUserId = $patron->username;
		//Get the session token for the user
		if (isset(self::$sessionIdsForUsers[$horizonRoaUserId])) {
			return self::$sessionIdsForUsers[$horizonRoaUserId];
		} else {
			list(, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			return $sessionToken;
		}
	}
	/**
	 * @param $username
	 * @param $password
	 * @param $validatedViaSSO
	 * @return null|User
	 */
	public function patronLogin($username, $password, $validatedViaSSO)
	{
		//TODO: check wihich login style in use. Right now assuming barcode_pin
		$username = preg_replace('/[\s]/', '', $username); // remove all space characters
		$password = trim($password);
		//Authenticate the user via WebService
		//First call loginUser
		global $timer;
		$timer->logTime("Logging in through Horizon ROA APIs");
		list($userValid, $sessionToken, $horizonRoaUserID) = $this->loginViaWebService($username, $password);
		if ($validatedViaSSO) {
			$userValid = true;
		}
		if ($userValid) {
			$timer->logTime("User is valid in horizon");
			$webServiceURL = $this->getWebServiceURL();
//  Calls that show how patron-related data is represented
//			$patronDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/describe', null, $sessionToken);
//			$patronDSearchescribeResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/search/describe', null, $sessionToken);
			//TODO: a patron search may require a staff user account.
//			$patronSearchResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/search', array('q' => 'borr|2:22046027101218'), $sessionToken);
//			$patronTypesQuery = $this->getWebServiceResponse($webServiceURL . '/v1/policy/patronType/simpleQuery?key=*&includeFields=*', null, $sessionToken);
			$acountInfoLookupURL = $webServiceURL . '/v1/user/patron/key/' . $horizonRoaUserID
				. '?includeFields=displayName,birthDate,privilegeExpiresDate,primaryAddress,primaryPhone,library,patronType'
				. ',holdRecordList,circRecordList,blockList'
//			. ",estimatedOverdueAmount"  // TODO: fields to play with
				// Note that {*} notation doesn't work for Horizon ROA yet
			;
			// phoneList is for texting notification preferences
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse($acountInfoLookupURL, null, $sessionToken);
			if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
				$fullName = $lookupMyAccountInfoResponse->fields->displayName;
				if (strpos($fullName, ',')) {
					list(, $lastName, $firstName) = $this->splitFullName($fullName);
				}
				$userExistsInDB = false;
				/** @var User $user */
				$user           = new User();
				$user->source   = $this->accountProfile->name;
				$user->username = $horizonRoaUserID;
				if ($user->find(true)) {
					$userExistsInDB = true;
				}
				$forceDisplayNameUpdate = false;
				$firstName              = isset($firstName) ? $firstName : '';
				if ($user->firstname != $firstName) {
					$user->firstname        = $firstName;
					$forceDisplayNameUpdate = true;
				}
				$lastName = isset($lastName) ? $lastName : '';
				if ($user->lastname != $lastName) {
					$user->lastname         = isset($lastName) ? $lastName : '';
					$forceDisplayNameUpdate = true;
				}
				if ($forceDisplayNameUpdate) {
					$user->displayName = '';
				}
				$user->fullname     = isset($fullName) ? $fullName : '';
				$user->cat_username = $username;
				$user->cat_password = $password;
				$Address1    = "";
				$City        = "";
				$State       = "";
				$Zip         = "";
				if (isset($lookupMyAccountInfoResponse->fields->primaryAddress)) {
					$preferredAddress = $lookupMyAccountInfoResponse->fields->primaryAddress->fields;
					// Set for Account Updating
					// TODO: area isn't valid any longer. Response from server looks like this:
					// {"ROAObject":"\/ROAObject\/primaryPatronAddressObject","fields":{"line1":"4020 Carya Dr","line2":"1","line3":"Lizard Lick, NC","line4":null,"postalCode":"20001","emailAddress":"askwcpl@wakegov.com"}}
					// city state looks to be line3
					//$cityState = $preferredAddress->area;
					$cityState = $preferredAddress->line3;
					if (strpos($cityState, ', ')) {
						list($City, $State) = explode(', ', $cityState);
					} elseif ($preferredAddress->area == 'other' && !empty($preferredAddress->line3)) {
						//For Wake County, when this is other; the city state is listed in address3
						list($City, $State) = explode(', ', $preferredAddress->address3);
					}
					$Address1 = $preferredAddress->line1;
					if (!empty($preferredAddress->line2)){
						//apt number
						$Address1 .= ' '. $preferredAddress->line2;
					}
					$email = $preferredAddress->emailAddress;
					$user->email = $email;
					$Zip = $preferredAddress->postalCode;
					$phone = $lookupMyAccountInfoResponse->fields->primaryPhone;
					$user->phone = $phone;
				}
				$ptype = 0;
				if (isset($lookupMyAccountInfoResponse->fields->patronType)) {
					$ptype = $lookupMyAccountInfoResponse->fields->patronType->key;
				}
				//Get additional information about the patron's home branch for display.
				if (isset($lookupMyAccountInfoResponse->fields->library->key)) {
					$homeBranchCode = strtolower(trim($lookupMyAccountInfoResponse->fields->library->key));
					//Translate home branch to plain text
					/** @var Location $location */
					$location       = new Location();
					$location->code = $homeBranchCode;
					if (!$location->find(true)) {
						unset($location);
					}
				} else {
					global $logger;
					$logger->log('HorizonROA Driver: No Home Library Location or Hold location found in account look-up. User : ' . $user->id, Logger::LOG_ERROR);
					// The code below will attempt to find a location for the library anyway if the homeLocation is already set
				}
				if (empty($user->homeLocationId) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
					if (empty($user->homeLocationId) && !isset($location)) {
						// homeBranch Code not found in location table and the user doesn't have an assigned homelocation,
						// try to find the main branch to assign to user
						// or the first location for the library
						global $library;
						/** @var Location $location */
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
							/** @var Location $myLocation1 */
							//Get display name for preferred location 1
							$myLocation1             = new Location();
							$myLocation1->locationId = $user->myLocation1Id;
							if ($myLocation1->find(true)) {
								$user->myLocation1 = $myLocation1->displayName;
							}
						}
						if (empty($user->myLocation2Id)){
							$user->myLocation2Id  = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
							//Get display name for preferred location 2
							/** @var Location $myLocation2 */
							$myLocation2             = new Location();
							$myLocation2->locationId = $user->myLocation2Id;
							if ($myLocation2->find(true)) {
								$user->myLocation2 = $myLocation2->displayName;
							}
						}
					}
				}
				if (isset($location)) {
					//Get display names that aren't stored
					$user->homeLocationCode = $location->code;
					$user->homeLocation     = $location->displayName;
				}
				if (isset($lookupMyAccountInfoResponse->fields->privilegeExpiresDate)) {
					$user->expires = $lookupMyAccountInfoResponse->fields->privilegeExpiresDate;
					list ($yearExp, $monthExp, $dayExp) = explode("-", $user->expires);
					$timeExpire   = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
					if ($timeExpire) {
						$timeNow      = time();
						$timeToExpire = $timeExpire - $timeNow;
						if ($timeToExpire <= 30 * 24 * 60 * 60) {
							if ($timeToExpire <= 0) {
								$user->expired = 1;
							}
							$user->expireClose = 1;
						}
					}
				}
				//Get additional information about fines, etc
				$finesVal = 0;
				if (isset($lookupMyAccountInfoResponse->fields->blockList)) {
					foreach ($lookupMyAccountInfoResponse->fields->blockList as $blockEntry) {
						$block = $this->getWebServiceResponse($webServiceURL . '/v1/circulation/block/key/' . $blockEntry->key . '?includeFields=owed', null, $sessionToken);
						if (isset($block->fields)){
							$fineAmount = (float) $block->fields->owed->amount;
							$finesVal   += $fineAmount;
						}
					}
				}
				$numHolds = 0;
				$numHoldsAvailable = 0;
				$numHoldsRequested = 0;
				if (isset($lookupMyAccountInfoResponse->fields->holdRecordList)) {
					$numHolds = count($lookupMyAccountInfoResponse->fields->holdRecordList);
					foreach ($lookupMyAccountInfoResponse->fields->holdRecordList as $hold) {
						$lookupHoldResponse = $this->getWebServiceResponse($webServiceURL . '/v1/circulation/holdRecord/key/' . $hold->key . '?includeFields=status', null, $sessionToken);
						if (!empty($lookupHoldResponse->fields)) {
							if ($lookupHoldResponse->fields->status == 'BEING_HELD') {
								$numHoldsAvailable++;
							} elseif ($lookupHoldResponse->fields->status != 'EXPIRED') {
								$numHoldsRequested++;
							}
						}
					}
				}
//
				$numCheckedOut = 0;
				if (isset($lookupMyAccountInfoResponse->fields->circRecordList)) {
					$numCheckedOut = count($lookupMyAccountInfoResponse->fields->circRecordList);
				}
				$user->_address1              = $Address1;
				$user->_address2              = $City . ', ' . $State; //TODO: Is there a reason to do this?
				$user->_city                  = $City;
				$user->_state                 = $State;
				$user->_zip                   = $Zip;
				$user->fines                 = sprintf('$%01.2f', $finesVal);
				$user->finesVal              = $finesVal;
				$user->numCheckedOutIls      = $numCheckedOut;
				$user->numHoldsIls           = $numHolds;
				$user->numHoldsAvailableIls  = $numHoldsAvailable;
				$user->numHoldsRequestedIls  = $numHoldsRequested;
				$user->patronType            = $ptype;
				$user->notices               = '-';
				$user->noticePreferenceLabel = 'E-mail';
				$user->web_note              = '';
				if ($userExistsInDB) {
					$user->update();
				} else {
					$user->created = date('Y-m-d');
					$user->insert();
				}
				$timer->logTime("patron logged in successfully");
				return $user;
			} else {
				if (isset($lookupMyAccountInfoResponse->messageList[0]->code) && $lookupMyAccountInfoResponse->messageList[0]->code == 'sessionTimedOut') {
					//If it was just a session timeout, just clear out the session
					/** @var Memcache $memCache */
					global $memCache;
					$memCacheKey = "horizon_ROA_session_token_info_$username";
					$memCache->delete($memCacheKey);
				} else {
					$timer->logTime("lookupMyAccountInfo failed");
					global $logger;
					$logger->log('Horizon ROA API call lookupMyAccountInfo failed.', Logger::LOG_ERROR);
				}
				return null;
			}
		}
		return null;
	}
	public function hasNativeReadingHistory() : bool
	{
		return false;
	}
	/**
	 * Return the number of holds that are on a record
	 * @param  string|int $bibId
	 * @return bool|int
	 */
	public function getNumHolds($bibId) : int {
		//This uses the standard / REST method to retrieve this information from the ILS.
		// It isn't an ROA call.
		global $offlineMode;
		if (!$offlineMode){
			$webServiceURL = $this->getWebServiceURL();
			$lookupTitleInfoUrl = $webServiceURL . '/rest/standard/lookupTitleInfo?titleKey=' . $bibId . '&includeItemInfo=false&includeHoldCount=true' ;
			$lookupTitleInfoResponse = $this->getWebServiceResponse($lookupTitleInfoUrl);
			if ($lookupTitleInfoResponse->titleInfo){
				if (is_array($lookupTitleInfoResponse->titleInfo) && isset($lookupTitleInfoResponse->titleInfo[0]->holdCount)) {
					return (int) $lookupTitleInfoResponse->titleInfo[0]->holdCount;
				} elseif (isset($lookupTitleInfoResponse->titleInfo->holdCount)) {
					//TODO: I suspect that this never occurs
					return (int) $lookupTitleInfoResponse->titleInfo->holdCount;
				}
			}
		}
		return false;
	}
	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 *
	 * @return array        Array of the patron's transactions on success
	 * @access public
	 */
	public function getMyCheckouts($patron)
	{
		$checkedOutTitles = array();
		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $checkedOutTitles;
		}
		// Now that we have the session token, get checkout  information
		$webServiceURL = $this->getWebServiceURL();
		//Get a list of checkouts for the user
		$patronCheckouts = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/key/' . $patron->username . '?includeFields=circRecordList', null, $sessionToken);
		if (!empty($patronCheckouts->fields->circRecordList)) {
//			$sCount = 0;
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			foreach ($patronCheckouts->fields->circRecordList as $checkoutRecord) {
				$checkOutKey = $checkoutRecord->key;
				$lookupCheckOutResponse = $this->getWebServiceResponse($webServiceURL . '/v1/circulation/circRecord/key/' . $checkOutKey, null, $sessionToken);
				if (isset($lookupCheckOutResponse->fields)) {
					$checkout = $lookupCheckOutResponse->fields;
					$itemId  = $checkout->item->key;
					list($bibId, $barcode, $itemType) = $this->getItemInfo($itemId, $patron);
					if (!empty($bibId)) {
						//TODO: volumes?
						$dueDate      = empty($checkout->dueDate) ? null : $checkout->dueDate;
						$checkOutDate = empty($checkout->checkOutDate) ? null : $checkout->checkOutDate;
						$fine         = empty($checkout->checkOutFee->amount) ? null : $checkout->checkOutFee->amount;
						if (!empty($fine) && (float) $fine <= 0) {
							// handle case of string '0.00'
							$fine = null;
						}
						$curTitle                   = array();
						$curTitle['checkoutSource'] = 'ILS';
						$curTitle['recordId']       = $bibId;
						$curTitle['shortId']        = $bibId;
						$curTitle['id']             = $bibId;
						$curTitle['itemid']         = $itemId;
						$curTitle['barcode']        = $barcode;
						$curTitle['renewIndicator'] = $itemId;
						$curTitle['dueDate']        = strtotime($dueDate);
						$curTitle['checkoutdate']   = strtotime($checkOutDate);
						$curTitle['renewCount']     = $checkout->renewalCount;
						$curTitle['canRenew']       = $this->canRenew($itemType);
						$curTitle['format']         = 'Unknown'; //TODO: I think this makes sorting working better
						$curTitle['overdue']        = $checkout->overdue; // (optional) CatalogConnection method will calculate this based on due date
						$curTitle['fine']           = $fine;
						$curTitle['holdQueueLength'] = $this->getNumHolds($bibId);
						$recordDriver = new MarcRecordDriver($bibId);
						if ($recordDriver->isValid()) {
							$curTitle['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
							$curTitle['groupedWorkId'] = $recordDriver->getGroupedWorkId();
							$curTitle['format']        = $recordDriver->getPrimaryFormat();
							$curTitle['title']         = $recordDriver->getTitle();
							$curTitle['title_sort']    = $recordDriver->getSortableTitle();
							$curTitle['author']        = $recordDriver->getPrimaryAuthor();
							$curTitle['link']          = $recordDriver->getLinkUrl();
							$curTitle['ratingData']    = $recordDriver->getRatingData();
						} else {
							// If we don't have good marc record, ask the ILS for title info
							list($title, $author)   = $this->getTitleAuthorForBib($bibId, $patron);
							$simpleSortTitle        = preg_replace('/^The\s|^A\s/i', '', $title); // remove beginning The or A
							$curTitle['title']      = $title;
							$curTitle['title_sort'] = empty($simpleSortTitle) ? $title : $simpleSortTitle;
							$curTitle['author']     = $author;
						}
						$checkedOutTitles[] = $curTitle;
					}
				}
			}
		}
		return $checkedOutTitles;
	}
	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public function hasFastRenewAll() : bool
	{
		return false;
	}
	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return array
	 */
	public function renewAll(User $patron){
		return array(
			'success' => false,
			'message' => 'Renew All not supported directly, call through Catalog Connection',
		);
	}

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return array
	 */
	function renewItem($patron, /** @noinspection PhpUnusedParameterInspection */ $recordId, $itemId, /** @noinspection PhpUnusedParameterInspection */ $itemIndex = null)
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return array(
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'
			);
		}
		$params = array(
			'item' => array(
				'key'      => $itemId,
				'resource' => '/catalog/item'
			)
		);
		$webServiceURL = $this->getWebServiceURL();
		$renewCheckOutResponse = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/circRecord/renew", $params, $sessionToken, 'POST');
		if (!empty($renewCheckOutResponse->circRecord)) {
			return array(
				'itemId'  => $itemId,
				'success' => true,
				'message' => 'Your item was successfully renewed.'
			);
		} elseif (isset($renewCheckOutResponse->messageList)) {
			$messages = array();
			foreach ($renewCheckOutResponse->messageList as $message) {
				$messages[] = $message->message;
			}
			global $logger;
			$errorMessage = 'Horizon ROA Renew Item Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);
			return array(
				'itemId'  => $itemId,
				'success' => false,
				'message' => 'Failed to renew item : '. implode('; ', $messages)
			);
		} else {
			return array(
				'itemId'  => $itemId,
				'success' => false,
				'message' => 'Failed to renew item : Unknown error'
			);
		}
	}
	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $patron The user to load transactions for
	 *
	 * @return array          Array of the patron's holds
	 * @access public
	 */
	public function getMyHolds($patron)
	{
		$availableHolds   = array();
		$unavailableHolds = array();
		$holds            = array(
			'available'   => $availableHolds,
			'unavailable' => $unavailableHolds
		);
		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $holds;
		}
		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
//		$holdRecordDescribe  = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/describe", null, $sessionToken);
//		$itemDescribe  = $this->getWebServiceResponse($webServiceURL . "/v1/catalog/item/describe", null, $sessionToken);
//		$callDescribe  = $this->getWebServiceResponse($webServiceURL . "/v1/catalog/call/describe", null, $sessionToken);
//		$copyDescribe  = $this->getWebServiceResponse($webServiceURL . "/v1/catalog/copy/describe", null, $sessionToken);
		//Get a list of holds for the user
		// (Call now includes Item information for when the hold is an item level hold.)
//		$patronHolds = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/key/' . $patron->username . '?includeFields=holdRecordList{*,item{itemType,barcode,call{callNumber}}}', null, $sessionToken);
		$patronHolds = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/key/' . $patron->username . '?includeFields=holdRecordList', null, $sessionToken);
		if ($patronHolds && isset($patronHolds->fields)) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			foreach ($patronHolds->fields->holdRecordList as $holdRecord) {
				$holdKey                = $holdRecord->key;
				$lookupHoldResponse = $this->getWebServiceResponse($webServiceURL . '/v1/circulation/holdRecord/key/' . $holdKey, null, $sessionToken);
				if (isset($lookupHoldResponse->fields)) {
					$hold = $lookupHoldResponse->fields;
					//TODO: Volume for title?
					//TODO: AvailableTime (availableTime only referenced in ilsHolds template and Holds Excel function)
					$bibId          = $hold->bib->key;
					$expireDate     = empty($hold->expirationDate) ? null : $hold->expirationDate;
					$reactivateDate = empty($hold->suspendEndDate) ? null : $hold->suspendEndDate;
					$createDate     = empty($hold->placedDate) ? null : $hold->placedDate;
					$fillByDate     = empty($hold->fillByDate) ? null : $hold->fillByDate;
					$curHold                         = array();
					$curHold['id']                    = $bibId; // Template uses record Id for the ID instead of the hold ID
					$curHold['recordId']              = $bibId;
					$curHold['shortId']               = $bibId;
					$curHold['holdSource']            = 'ILS';
					$curHold['itemId']                = empty($hold->item->key) ? '' : $hold->item->key; //TODO: test
					$curHold['cancelId']              = $holdKey;
					$curHold['position']              = empty($hold->queuePosition) ? null : $hold->queuePosition;
					$curHold['status']                = ucfirst(strtolower($hold->status));
					$curHold['create']                = strtotime($createDate);
					$curHold['expire']                = strtotime($expireDate);
					$curHold['automaticCancellation'] = strtotime($fillByDate);
					$curHold['reactivate']            = $reactivateDate;
					$curHold['reactivateTime']        = strtotime($reactivateDate);
					$curHold['cancelable']            = strcasecmp($curHold['status'], 'Suspended') != 0 && strcasecmp($curHold['status'], 'Expired') != 0;
					$curHold['frozen']                = strcasecmp($curHold['status'], 'Suspended') == 0;
					$curHold['freezeable']            = true;
					if (strcasecmp($curHold['status'], 'Transit') == 0 || strcasecmp($curHold['status'], 'Expired') == 0) {
						$curHold['freezeable'] = false;
					}
					$curHold['locationUpdateable']    = true;
					if (strcasecmp($curHold['status'], 'Transit') == 0 || strcasecmp($curHold['status'], 'Expired') == 0) {
						$curHold['locationUpdateable'] = false;
					}
					$curPickupBranch       = new Location();
					$curPickupBranch->code = $hold->pickupLibrary->key;
					if ($curPickupBranch->find(true)) {
						$curPickupBranch->fetch();
						$curHold['currentPickupId']   = $curPickupBranch->locationId;
						$curHold['currentPickupName'] = $curPickupBranch->displayName;
						$curHold['location']          = $curPickupBranch->displayName;
					}
					$recordDriver = new MarcRecordDriver($bibId);
					if ($recordDriver->isValid()) {
						$curHold['title']           = $recordDriver->getTitle();
						$curHold['author']          = $recordDriver->getPrimaryAuthor();
						$curHold['sortTitle']       = $recordDriver->getSortableTitle();
						$curHold['format']          = $recordDriver->getFormat();
						$curHold['isbn']            = $recordDriver->getCleanISBN();
						$curHold['upc']             = $recordDriver->getCleanUPC();
						$curHold['format_category'] = $recordDriver->getFormatCategory();
						$curHold['coverUrl']        = $recordDriver->getBookcoverUrl('medium');
						$curHold['link']            = $recordDriver->getRecordUrl();
						$curHold['ratingData']      = $recordDriver->getRatingData(); //Load rating information
						//TODO: WCPL doesn't do item level holds
//						if ($hold->fields->holdType == 'COPY') {
//
//							$curHold['title2'] = $hold->fields->item->fields->itemType->key . ' - ' . $hold->fields->item->fields->call->fields->callNumber;
//
//
////						$itemInfo = $this->getWebServiceResponse($webServiceURL . '/v1' . $hold->fields->selectedItem->resource . '/key/' . $hold->fields->selectedItem->key. '?includeFields=barcode,call{*}', null, $sessionToken);
////						$curHold['title2'] = $itemInfo->fields->itemType->key . ' - ' . $itemInfo->fields->call->fields->callNumber;
//							//TODO: Verify that this matches the title2 built below
////						if (isset($itemInfo->fields)){
////							$barcode = $itemInfo->fields->barcode;
////							$copies = $recordDriver->getCopies();
////							foreach ($copies as $copy){
////								if ($copy['itemId'] == $barcode){
////									$curHold['title2'] = $copy['shelfLocation'] . ' - ' . $copy['callNumber'];
////									break;
////								}
////							}
////						}
//						}
					} else {
						// If we don't have good marc record, ask the ILS for title info
						list($title, $author) = $this->getTitleAuthorForBib($bibId, $patron);
						$simpleSortTitle      = preg_replace('/^The\s|^A\s/i', '', $title); // remove beginning The or A
						$curHold['title']     = $title;
						$curHold['sortTitle'] = empty($simpleSortTitle) ? $title : $simpleSortTitle;
						$curHold['author']    = $author;
					}
					if (!isset($curHold['status']) || strcasecmp($curHold['status'], "being_held") != 0) {
						$holds['unavailable'][] = $curHold;
					} else {
						$holds['available'][] = $curHold;
					}
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
	 * @param   User    $patron          The User to place a hold for
	 * @param   string  $recordId        The id of the bib record
	 * @param   string  $pickupBranch    The branch where the user wants to pickup the item when available
	 * @param   null|string $cancelDate  The date to cancel the Hold if it isn't filled
	 * @return  array                                 Array of (success and message) to be used for an AJAX response
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
		$result = $this->placeItemHold($patron, $recordId, null, $pickupBranch, 'request', $cancelDate);
		return $result;
		// WCPL doesn't have item-level holds, so there is no need for this at this point.
//		$result = array();
//		$needsItemHold = false;
//
//		$holdableItems = array();
//		/** @var MarcRecord $recordDriver */
//		$recordDriver = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $recordId);
//		if ($recordDriver->isValid()){
//			$result['title'] = $recordDriver->getTitle();
//
//			$items = $recordDriver->getCopies();
//			$firstCallNumber = null;
//			foreach ($items as $item){
//				$itemNumber = $item['itemId'];
//				if ($itemNumber && $item['holdable']){
//					$itemCallNumber = $item['callNumber'];
//					if ($firstCallNumber == null){
//						$firstCallNumber = $itemCallNumber;
//					}else if ($firstCallNumber != $itemCallNumber){
//						$needsItemHold = true;
//					}
//
//					$holdableItems[] = array(
//						'itemNumber' => $item['itemId'],
//						'location'   => $item['shelfLocation'],
//						'callNumber' => $itemCallNumber,
//						'status'     => $item['status'],
//					);
//				}
//			}
//		}
//
//		if (!$needsItemHold){
//			$result = $this->placeItemHold($patron, $recordId, null, $pickupBranch, 'request', $cancelDate);
//		}else{
//			$result['items'] = $holdableItems;
//			if (count($holdableItems) > 0){
//				$message = 'This title requires item level holds, please select an item to place a hold on.';
//			}else{
//				$message = 'There are no holdable items for this title.';
//			}
//			$result['success'] = false;
//			$result['message'] = $message;
//		}
//
//		return $result;
	}
	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User $patron                          The User to place a hold for
	 * @param   string $recordId                      The id of the bib record
	 * @param   string $itemId                        The id of the item to hold
	 * @param   string $pickUpLocation                The Pickup Location
	 * @param   string $type                          Whether to place a hold or recall
	 * @param   null|string $cancelIfNotFilledByDate  The date to cancel the Hold if it isn't filled
	 * @return  array                                 Array of (success and message) to be used for an AJAX response
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickUpLocation = null, $type = 'request', $cancelIfNotFilledByDate = null)
	{
		//TODO: parameter $type can be removed.
		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return array(
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'
			);
		}
		if (empty($pickUpLocation)) {
			$pickUpLocation = $patron->homeLocationCode;
		}
		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();
		$holdData = array(
			'patronBarcode' => $patron->getBarcode(),
			'pickupLibrary' => array(
				'resource' => '/policy/library',
				'key'      => strtoupper($pickUpLocation)
			),
		);
		if (!empty($itemId)) {
			//TODO: item-level holds haven't been tested yet.
			$holdData['itemBarcode'] = $itemId;
			$holdData['holdType']    = 'COPY';
		} else {
			$holdData['holdType']   = 'TITLE';
			$holdData['bib']         = array(
				'resource' => '/catalog/bib',
				'key'      => $recordId
			);
		}
		if (!empty($cancelIfNotFilledByDate)) {
			$timestamp = strtotime($cancelIfNotFilledByDate);
			if ($timestamp) {
				$holdData['fillByDate'] = date('Y-m-d', $timestamp);
			}
		}
//				$holdRecordDescribe = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/describe", null, $sessionToken);
//				$placeHoldDescribe  = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/placeHold/describe", null, $sessionToken);
		$createHoldResponse = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/placeHold", $holdData, $sessionToken);
		$hold_result = array(
			'success' => false,
			'message' => 'Your hold could not be placed. '
		);
		if (isset($createHoldResponse->messageList)) {
			$errorMessage = '';
			foreach ($createHoldResponse->messageList as $error){
				$errorMessage .= $error->message.'; ';
			}
			$hold_result['message'] .= $errorMessage;
			global $logger;
			$logger->log('Horizon ROA Place Hold Error: ' . $errorMessage, Logger::LOG_ERROR);
		} elseif (!empty($createHoldResponse->holdRecord)) {
			$hold_result['success'] = true;
			$hold_result['message'] = translate(['text'=>"Your hold was placed successfully.", 'isPublicFacing'=>true]);
		}
		// Retrieve Full Marc Record
		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById('ils:' . $recordId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}
		$hold_result['title'] = $title;
		$hold_result['bid']   = $recordId; //TODO: bid or bib
		return $hold_result;
	}
	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false) : array
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return array(
				'success' => false,
				'message' => 'Sorry, we could not connect to the circulation system.'
			);
		}
		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();
		$cancelHoldResponse = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/key/$cancelId", null, $sessionToken, 'DELETE');
		if (empty($cancelHoldResponse)) {
			return array(
				'success' => true,
				'message' => 'The hold was successfully canceled'
			);
		} else {
			global $logger;
			$errorMessage = 'Horizon ROA Cancel Hold Error: ';
			foreach ($cancelHoldResponse->messageList as $error){
				$errorMessage .= $error->message.'; ';
			}
			$logger->log($errorMessage, Logger::LOG_ERROR);
			return array(
				'success' => false,
				'message' => 'Sorry, the hold was not canceled');
		}
	}
	function freezeHold($patron, $recordId, $holdToFreezeId, $dateToReactivate) : array
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return array(
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'
			);
		}
		$formattedDateToReactivate = $dateToReactivate ? date('Y-m-d', strtotime($dateToReactivate)) : null;
		$params = array(
			'suspendEndDate' => $formattedDateToReactivate,
			'holdRecord'     => array(
				'key'      => $holdToFreezeId,
				'resource' => '/circulation/holdRecord',
			)
		);
		$webServiceURL = $this->getWebServiceURL();
//		$describe  = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/unsuspendHold/describe", null, $sessionToken);
		$updateHoldResponse = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/suspendHold", $params, $sessionToken, 'POST');
		if (!empty($updateHoldResponse->holdRecord)) {
			return array(
				'success' => true,
				'message' => translate(['text' => "The hold has been frozen.", 'isPublicFacing'=>true])
			);
		} else {
			$messages = array();
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Horizon ROA Freeze Hold Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);
			return array(
				'success' => false,
				'message' => translate(['text' => "Failed to freeze hold", 'isPublicFacing'=>true]) . ' - '. implode('; ', $messages)
			);
		}
	}
	function thawHold($patron, $recordId, $holdToThawId) : array
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return array(
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'
			);
		}
		$params = array(
			'holdRecord'     => array(
				'key'      => $holdToThawId,
				'resource' => '/circulation/holdRecord',
			)
		);
		$webServiceURL = $this->getWebServiceURL();
//		$describe  = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/unsuspendHold/describe", null, $sessionToken);
		//TODO: This should be disabled in production
		/** @noinspection PhpUnusedLocalVariableInspection */
		$describe  = $this->getWebServiceResponse($webServiceURL . "/circulation/holdRecord/changePickupLibrary/describe", null, $sessionToken);
		$updateHoldResponse = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/unsuspendHold", $params, $sessionToken, 'POST');
		if (!empty($updateHoldResponse->holdRecord)) {
			return array(
				'success' => true,
				'message' => translate(['text' => "The hold has been thawed.", 'isPublicFacing'=>true])
			);
		} else {
			$messages = array();
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Horizon ROA Thaw Hold Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);
			return array(
				'success' => false,
				'message' => translate(['text' => "Failed to thaw hold ", 'isPublicFacing'=>true]) . ' - '. implode('; ', $messages)
			);
		}
	}
	function changeHoldPickupLocation(User $patron, $recordId, $holdToUpdateId, $newPickupLocation) : array
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return array(
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'
			);
		}
		$params = array(
			'pickupLibrary' => array(
				'key'      => $newPickupLocation,
				'resource' => '/policy/library',
			),
			'holdRecord'    => array(
				'key'      => $holdToUpdateId,
				'resource' => '/circulation/holdRecord',
			)
		);
		$webServiceURL      = $this->getWebServiceURL();
//		$describe           = $this->getWebServiceResponse($webServiceURL . "/circulation/holdRecord/changePickupLibrary/describe", null, $sessionToken);
		$updateHoldResponse = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/holdRecord/changePickupLibrary", $params, $sessionToken, 'POST');
		if (!empty($updateHoldResponse->holdRecord)) {
			return array(
				'success' => true,
				'message' => 'The pickup location has been updated.'
			);
		} else {
			$messages = array();
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Horizon ROA Change Hold Pickup Location Error: ' . ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);
			return array(
				'success' => false,
				'message' => 'Failed to update the pickup location : ' . implode('; ', $messages)
			);
		}
	}
	/**
	 * Look up information about an item record in the ILS.
	 *
	 * @param string $itemId  Id of the Item to lookup
	 * @param User   $patron  User object to create a sesion with
	 * @return array          An array of Bib ID, Item Barcode, and the Item Type
	 */
	function getItemInfo($itemId, $patron) {
		$itemInfo = array(
			null, // bibId
			null, // barcode
			null, // item Type
		);
		if (!empty($itemId)) {
			/** @var Memcache $memCache */
			global $memCache;
			$memCacheKeyPrefix = 'horizon_ROA_bib_info_for_item';
			$memCacheKey       = "{$memCacheKeyPrefix}_$itemId";
			$itemInfo          = $memCache->get($memCacheKey);
			if (!$itemInfo || isset($_REQUEST['reload'])) {
				$webServiceURL = $this->getWebServiceURL();
				$sessionToken  = $this->getSessionToken($patron);
//				$itemInfoLookupResponse  = $this->getWebServiceResponse($webServiceURL . "/v1/catalog/item/key/" . $itemId, null, $sessionToken);
				$itemInfoLookupResponse = $this->getWebServiceResponse($webServiceURL . "/v1/catalog/item/key/" . $itemId . '?includeFields=bib,barcode,itemType', null, $sessionToken);
				if (!empty($itemInfoLookupResponse->fields)) {
					$bibId    = $itemInfoLookupResponse->fields->bib->key;
					$barcode  = $itemInfoLookupResponse->fields->barcode;
					$itemType = $itemInfoLookupResponse->fields->itemType->key;
					$itemInfo = array(
						$bibId,
						$barcode,
						$itemType,
					);
					global $configArray;
					$memCache->set($memCacheKey, $itemInfo, $configArray['Caching'][$memCacheKeyPrefix]);
				}
			}
		}
		return $itemInfo;
	}
	function getTitleAuthorForBib($bibId, $patron) {
		$bibInfo = array(
			null, // title
			null, // author
		);
		if (!empty($bibId)) {
			/** @var Memcache $memCache */
			global $memCache;
			$memCacheKeyPrefix = 'horizon_ROA_title_info_for_bib';
			$memCacheKey       = "{$memCacheKeyPrefix}_$bibId";
			$bibInfo           = $memCache->get($memCacheKey);
			if (!$bibInfo || isset($_REQUEST['reload'])) {
				$webServiceURL = $this->getWebServiceURL();
				$sessionToken  = $this->getSessionToken($patron);
//				$bibInfoLookupResponse = $this->getWebServiceResponse($webServiceURL . '/v1/catalog/bib/key/' . $bibId . '?includeFields=*', null, $sessionToken);
				$bibInfoLookupResponse = $this->getWebServiceResponse($webServiceURL . '/v1/catalog/bib/key/' . $bibId . '?includeFields=title,author', null, $sessionToken);
				if (!empty($bibInfoLookupResponse->fields)) {
					$title      = $bibInfoLookupResponse->fields->title;
					$shortTitle = strstr($title, '/', true); //drop everything from title after '/' character (author info)
					$title      = ($shortTitle) ? $shortTitle : $title;
					$title      = trim($title);
					$author     = $bibInfoLookupResponse->fields->author;
					$bibInfo = array(
						$title,
						$author,
					);
					global $configArray;
					$memCache->set($memCacheKey, $bibInfo, $configArray['Caching'][$memCacheKeyPrefix]);
				}
			}
		}
		return $bibInfo;
	}
	public function getFines($patron, $includeMessages = false) : array
	{
		$fines = array();
		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $fines;
		}
		// Now that we have the session token, get fines information
		$webServiceURL = $this->getWebServiceURL();
//		$blockListDescribe  = $this->getWebServiceResponse($webServiceURL . "/v1/circulation/block/describe", null, $sessionToken);
		//Get a list of fines for the user
		$patronFines = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/key/' . $patron->username . '?includeFields=blockList', null, $sessionToken);
		if (!empty($patronFines->fields->blockList)) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			foreach ($patronFines->fields->blockList as $blockList) {
				$blockListKey = $blockList->key;
				$lookupBlockResponse = $this->getWebServiceResponse($webServiceURL . '/v1/circulation/block/key/' . $blockListKey, null, $sessionToken);
				if (isset($lookupBlockResponse->fields)){
					$fine = $lookupBlockResponse->fields;
					// Lookup book title associated with the block
					$title = '';
					if (isset($fine->item->key)) {
						$itemId       = $fine->item->key;
						list($bibId)  = $this->getItemInfo($itemId, $patron);
						$recordDriver = new MarcRecordDriver($bibId);
						if ($recordDriver->isValid()) {
							$title = $recordDriver->getTitle();
						} else {
							list($title) = $this->getTitleAuthorForBib($bibId, $patron);
						}
					}
					$reason = $this->getBlockPolicy($fine->block->key, $patron);
					$fines[] = array(
						'reason'            => $reason,
						'amount'            => $fine->amount->amount,
						'message'           => $title,
						'amountOutstanding' => $fine->owed->amount,
						'date'              => date('M j, Y', strtotime($fine->createDate))
					);
				}
			}
		}
		return $fines;
	}
	private function getBlockPolicy($blockPolicyKey, $patron) {
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey     = "horizon_ROA_block_policy_$blockPolicyKey";
		$blockPolicy = $memCache->get($memCacheKey);
		if (!$blockPolicy) {
			$webServiceURL = $this->getWebServiceURL();
			$sessionToken  = $this->getSessionToken($patron);
			$lookupBlockPolicy = $this->getWebServiceResponse($webServiceURL . '/v1/policy/block/key/' . $blockPolicyKey, null, $sessionToken);
			if (!empty($lookupBlockPolicy->fields)) {
				$blockPolicy = empty($lookupBlockPolicy->fields->description) ? null : $lookupBlockPolicy->fields->description;
				global $configArray;
				$memCache->set($memCacheKey, $blockPolicy, $configArray['Caching']['horizon_ROA_block_policy']);
			}
		}
		return $blockPolicy;
	}
	public function updatePin(User $patron, string $oldPin, string $newPin){
		$updatePinResponse = $this->changeMyPin($patron, $newPin, $oldPin);
		if (isset($updatePinResponse->messageList)) {
			$errors = '';
			foreach ($updatePinResponse->messageList as $errorMessage) {
				$errors .= $errorMessage->message . ';';
			}
			global $logger;
			$logger->log('Horizon ROA Driver error updating user\'s Pin :'.$errors, Logger::LOG_ERROR);
			return 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.';
		} elseif (!empty($updatePinResponse->sessionToken)){
			$patron->cat_password = $newPin;
			$patron->update();
			return "Your pin number was updated successfully.";
		}else{
			return "Sorry, we could not update your pin number. Please try again later.";
		}
	}
	private function changeMyPin($patron, $newPin, $currentPin = null, $resetToken = null) {
		global $configArray;
		if (empty($resetToken)) {
			$sessionToken = $this->getSessionToken($patron);
			if (!$sessionToken) {
				return 'Sorry, it does not look like you are logged in currently.  Please login and try again';
			}
			if (!empty($newPin) && !empty($currentPin)) {
				$jsonParameters = array(
					'currentPin' => $currentPin,
					'newPin'     => $newPin,
				);
			} else {
				return 'Sorry the current PIN or new PIN is blank';
			}
		} else {
			$sessionToken = null;
			$profile = $configArray['Catalog']['webServiceSelfRegProfile'];
			$xtraHeaders = ['sd-working-libraryid'=>$profile];
			$jsonParameters = array(
				'newPin'     => $newPin,
				'resetPinToken' => $resetToken
			);
		}
		$webServiceURL = $this->getWebServiceURL();
		$updatePinUrl =  $webServiceURL . '/v1/user/patron/changeMyPin';
		$updatePinResponse = $this->getWebServiceResponse($updatePinUrl, $jsonParameters, empty($sessionToken) ? null : $sessionToken, 'POST', empty($xtraHeaders) ? null : $xtraHeaders);
		return $updatePinResponse;
	}
	public function emailResetPin($barcode)
	{
		if (!empty($barcode)) {
			$patron = new User;
			$patron->get('cat_username', $barcode); // This will always be for barcode/pin configurations
			if (!empty($patron->id)) {
				global $configArray;
				$userID = $patron->id;
			} /** @noinspection PhpStatementHasEmptyBodyInspection */ else {
				//TODO: Look up user in Horizon
			}

			if ($userID) {
				//TODO: looks like user ID will still be required
				// email the pin to the user
				$resetPinAPIUrl = $this->getWebServiceURL() . '/v1/user/patron/resetMyPin';
				$jsonPOST       = array(
					'barcode'     => $barcode,
					'resetPinUrl' => $configArray['Site']['url'] . '/MyAccount/ResetPin?resetToken=<RESET_PIN_TOKEN>' . (empty($userID) ? '' : '&uid=' . $userID)
				);
				$resetPinResponse = $this->getWebServiceResponse($resetPinAPIUrl, $jsonPOST, null, 'POST');
				// Reset Pin Response is empty JSON on success.
				if (!empty($resetPinResponse) && is_object($resetPinResponse) && !isset($resetPinResponse->messageList)) {
					// Successfull response is an empty json object "{}"
					return array(
						'success' => true,
					);
				} else {
					$result = array(
						'error' => "Sorry, we could not e-mail your pin to you.  Please visit the library to reset your pin."
					);
					if (isset($resetPinResponse['messageList'])) {
						$errors = '';
						foreach ($resetPinResponse['messageList'] as $errorMessage) {
							$errors .= $errorMessage['message'] . ';';
						}
						global $logger;
						$logger->log('WCPL Driver error updating user\'s Pin :' . $errors, Logger::LOG_ERROR);
					}
					return $result;
				}
			} else {
				return array(
					'error' => 'Sorry, we did not find the card number you entered or you have not logged into the catalog previously.  Please contact your library to reset your pin.'
				);
			}
		}
		return array(
			'error' => 'Unknown error.'
		);
	}
	public function resetPin(User $user, $newPin, $resetToken){
		//TODO: the reset PIN call looks to need a staff account to complete
		if (empty($resetToken)) {
			global $logger;
			$logger->log('No Reset Token passed to resetPin function', Logger::LOG_ERROR);
			return array(
				'error' => 'Sorry, we could not update your pin. The reset token is missing. Please try again later'
			);
		}
		$changeMyPinResponse = $this->changeMyPin($user, $newPin, null, $resetToken);
		if (isset($changeMyPinResponse->messageList)) {
			$errors = '';
			foreach ($changeMyPinResponse->messageList as $errorMessage) {
				$errors .= $errorMessage->message . ';';
			}
			global $logger;
			$logger->log('Horizon ROA Driver error updating user\'s Pin :'.$errors, Logger::LOG_ERROR);
			return array(
				'error' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.'
			);
		} elseif (!empty($changeMyPinResponse->sessionToken)){
			if ($user->username == $changeMyPinResponse->patronKey) { // Check that the ILS user matches the Aspen Discovery user
				//TODO: check that this still applies
				$user->cat_password = $newPin;
				$user->update();
			}
			return array(
				'success' => true,
			);
		}else{
			return array(
				'error' => "Sorry, we could not update your pin number. Please try again later."
			);
		}
	}
	private function getStaffSessionToken() {
		global $configArray;
		//Get a staff token
		$staffUser = $configArray['Catalog']['webServiceStaffUser'];
		$staffPass = $configArray['Catalog']['webServiceStaffPass'];
		$body = ['login'=>$staffUser, 'password'=>$staffPass];
		$xtraHeaders = ['sd-originating-app-id'=>'Aspen Discovery'];
		$res = $this->getWebServiceResponse($this->webServiceURL . '/v1/user/staff/login', $body, null, "POST", $xtraHeaders);
		if(!$res || !isset($res->sessionToken)) {
			return false;
		}
		return $res->sessionToken;
	}
	/**
	 * @param User $patron                   The User Object to make updates to
	 * @param boolean $canUpdateContactInfo  Permission check that updating is allowed
	 * @param boolean $fromMasquerade
	 * @return array                         Array of error messages for errors that occurred
	 */
	function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade) : array {
		$result = [
			'success' => false,
			'messages' => []
		];
		if ($canUpdateContactInfo) {
			$sessionToken = $this->getSessionToken($patron);
			if ($sessionToken) {
				$horizonRoaUserId = $patron->username;
				$updatePatronInfoParameters = array(
					'fields' => array(),
				);
				$emailAddress = trim($_REQUEST['email']);
				if (is_array($emailAddress)) {
					$emailAddress = '';
				}
				$primaryAddress = array(
					// TODO: check this may need to add address from patron.
					'ROAObject' => '/ROAObject/primaryPatronAddressObject',
					'fields' => array(
						'line1' => '4020 Carya Dr',
						//						'line2' => NULL,
						//						'line3' => NULL,
						//						'line4' => NULL,
						'line3' => 'Raleigh, NC',
						'postalCode' => '27610',
						'emailAddress' => $emailAddress,
					)
				);
				$updatePatronInfoParameters['fields'][] = $primaryAddress;
				//$staffSessionToken = $this->getStaffSessionToken();
				// TODO: update call not working.
				$webServiceURL             = $this->getWebServiceURL();
				$updateAccountInfoResponse = $this->getWebServiceResponse($webServiceURL . '/v1/user/patron/key/' . $horizonRoaUserId, $updatePatronInfoParameters, $sessionToken, 'PUT');
				if (isset($updateAccountInfoResponse->messageList)) {
					foreach ($updateAccountInfoResponse->messageList as $message) {
						$result['messages'][] = $message->message;
					}
					global $logger;
					$logger->log('Horizon ROA Driver - Patron Info Update Error - Error from ILS : '.implode(';', $result['messages']), Logger::LOG_ERROR);
				}
			} else {
				$result['messages'][] = 'Sorry, it does not look like you are logged in currently.  Please login and try again';
			}
		} else {
			$result['messages'][] = 'You do not have permission to update profile information.';
		}
		if (empty($result['messages'])){
			$result['success'] = true;
			$result['messages'][] = 'Your account was updated successfully.';
		}
		return $result;
	}
	public function selfRegisterViaSSO(){
		return false;
	}
	public function selfRegister() {
		$patronFields = $this->getSelfRegistrationFields();
		$body = [];
		foreach ($patronFields as $field){
			if (isset($_REQUEST[$field['property']])){
				$body[$field['property']] = $_REQUEST[$field['property']];
			}
		}
		$extraHeaders = [
			'SD-Working-LibraryID: WCPL'
		];
		$res = $this->getWebServiceResponse($this->webServiceURL . '/user/patron/register', $body, null, "POST", $extraHeaders);
		if(!$res || isset($res->Fault)) {
			return ['success' => false, 'barcode' => ''];
		}
		return ['success' => true, 'barcode' => $res];
	}
	/**
	 * Get self registration fields from Horizon web services.
	 *
	 * Checks if self registration is enabled. Gets self registration fields from web service and builds form fields.
	 *
	 * @return array|bool An array of form fields or false if user registration isn't enabled (or something goes wrong)
	 */
	public function getSelfRegistrationFields()
	{
		// SelfRegistrationEnabled?
		$patronRegDescribeResponse = $this->getWebServiceResponse($this->webServiceURL . '/user/patron/register/describe');

		if(!$patronRegDescribeResponse) {
			return false;
		}
		// build form fields
		$fields = [];
		foreach($patronRegDescribeResponse->params as $roaField) {
			$aspenField = [
				'property' => $roaField->name,
				'label' => $roaField->name,
				'required' => $roaField->required,
			];
			if (isset($roaField->max)){
				$aspenField['maxLength'] = $roaField->max;
			}

			if ($roaField->type == 'resource') {
				$resourceResponse = $this->getWebServiceResponse($this->webServiceURL . $roaField->uri . '/simpleQuery?key=*');
				if ($resourceResponse) {
					$values = [];
					foreach ($resourceResponse as $resource) {
						$values[$resource->key] = $resource->fields->displayName;
					}
					$aspenField['values'] = $values;
				}
				$aspenField['type'] = 'enum';
				//TODO:  We will want to provide these at some point.
				continue;
			}elseif ($roaField->type == 'list'){
				//TODO:  We will want to provide these at some point.
				continue;
			}else{
				$aspenField['type'] = 'text';
			}

			$fields[] = $aspenField;
		}
		return $fields;
	}

	/**
	 * A place holder method to override with site specific logic
	 *
	 * @return bool
	 */
	public function canRenew(/** @noinspection PhpUnusedParameterInspection */ $itemType)
	{
		return true;
	}
}