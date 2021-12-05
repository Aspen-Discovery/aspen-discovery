<?php

require_once ROOT_DIR . '/Drivers/HorizonAPI.php';
require_once ROOT_DIR . '/sys/Account/User.php';

class SirsiDynixROA extends HorizonAPI
{
	//Caching of sessionIds by patron for performance (also stored within memcache)
	private static $sessionIdsForUsers = array();
	private static $logAllAPICalls = false;

	// $customRequest is for curl, can be 'PUT', 'DELETE', 'POST'
	public function getWebServiceResponse($requestType, $url, $params = null, $sessionToken = null, $customRequest = null, $additionalHeaders = null, $dataToSanitize = [])
	{
		global $logger;
		global $library;
		$logger->log('WebServiceURL :' . $url, Logger::LOG_NOTICE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$clientId = $this->accountProfile->oAuthClientId;
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'SD-Originating-App-Id: Aspen Discovery',
			'SD-Working-LibraryID: ' . $library->ilsCode,
			'x-sirs-clientID: ' . $clientId,
		);
		if ($sessionToken != null) {
			$headers[] = 'x-sirs-sessionToken: ' . $sessionToken;
		}
		if (!empty($additionalHeaders) && is_array($additionalHeaders)) {
			$headers = array_merge($headers, $additionalHeaders);
		}
		if (empty($customRequest)) {
            $customRequest = 'GET';
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ($customRequest == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // TODO: debugging only: comment out for production
		}
		if ($params != null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$json = curl_exec($ch);
		if (SirsiDynixROA::$logAllAPICalls) {
			$logger->log($url, Logger::LOG_ERROR);
			$logger->log(print_r($headers, true), Logger::LOG_ERROR);
			$logger->log(print_r($json, true), Logger::LOG_ERROR);
		}

        ExternalRequestLogEntry::logRequest('symphony.' . $requestType, $customRequest, $url, $headers, json_encode($params), curl_getinfo($ch)['http_code'], $json, $dataToSanitize);
		if ($json !== false && $json !== 'false') {
			curl_close($ch);
			return json_decode($json);
		} else {
			$logger->log('Curl problem in getWebServiceResponse ' . curl_error($ch), Logger::LOG_ERROR);
			curl_close($ch);
			return false;
		}
	}

	function findNewUser($barcode)
	{
		// Creates a new user like patronLogin but looks up user by barcode.
		// Note: The user pin is not supplied in the Account Info Lookup call.
		$sessionToken = $this->getStaffSessionToken();
		if (!empty($sessionToken)) {
			$webServiceURL = $this->getWebServiceURL();
			$includeFields = urlEncode("firstName,lastName,privilegeExpiresDate,preferredAddress,address1,address2,address3,library,primaryPhone,profile,pin,blockList{owed}");
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse('findNewUser', $webServiceURL . '/user/patron/search?q=ID:' . $barcode . '&rw=1&ct=1&includeFields=' . $includeFields, null, $sessionToken);
			if (!empty($lookupMyAccountInfoResponse->result) && $lookupMyAccountInfoResponse->totalResults == 1) {
				$userID = $lookupMyAccountInfoResponse->result[0]->key;
				$lookupMyAccountInfoResponse = $lookupMyAccountInfoResponse->result[0];
				$lastName = $lookupMyAccountInfoResponse->fields->lastName;
				$firstName = $lookupMyAccountInfoResponse->fields->firstName;

				$fullName = $lastName . ', ' . $firstName;

				$userExistsInDB = false;
				$user = new User();
				$user->source = $this->accountProfile->name;
				$user->username = $userID;
				if ($user->find(true)) {
					$userExistsInDB = true;
				}

				$forceDisplayNameUpdate = false;
				$firstName = isset($firstName) ? $firstName : '';
				if ($user->firstname != $firstName) {
					$user->firstname = $firstName;
					$forceDisplayNameUpdate = true;
				}
				$lastName = isset($lastName) ? $lastName : '';
				if ($user->lastname != $lastName) {
					$user->lastname = isset($lastName) ? $lastName : '';
					$forceDisplayNameUpdate = true;
				}
				if ($forceDisplayNameUpdate) {
					$user->displayName = '';
				}
				$user->_fullname = isset($fullName) ? $fullName : '';
				$user->cat_username = $barcode;
				if (!empty($lookupMyAccountInfoResponse->fields->pin)) {
					$user->cat_password = $lookupMyAccountInfoResponse->fields->pin;
				}

				$Address1 = "";
				$City = "";
				$State = "";
				$Zip = "";

				if (isset($lookupMyAccountInfoResponse->fields->preferredAddress)) {
					$preferredAddress = $lookupMyAccountInfoResponse->fields->preferredAddress;
					// Used by My Account Profile to update Contact Info
					if ($preferredAddress == 1) {
						$address = $lookupMyAccountInfoResponse->fields->address1;
					} elseif ($preferredAddress == 2) {
						$address = $lookupMyAccountInfoResponse->fields->address2;
					} elseif ($preferredAddress == 3) {
						$address = $lookupMyAccountInfoResponse->fields->address3;
					} else {
						$address = array();
					}
					foreach ($address as $addressField) {
						$fields = $addressField->fields;
						switch ($fields->code->key) {
							case 'STREET' :
								$Address1 = $fields->data;
								break;
							case 'CITY/STATE' :
								$cityState = $fields->data;
								if (!empty($cityState)) {
									if (substr_count($cityState, ' ') > 1) {
										//Splitting multiple word cities
										$last_space = strrpos($cityState, ' ');
										$City = substr($cityState, 0, $last_space);
										$State = substr($cityState, $last_space + 1);

									} else {
										list($City, $State) = explode(' ', $cityState);
									}
								}else{
									$City = '';
									$State = '';
								}
								break;
							case 'ZIP' :
								$Zip = $fields->data;
								break;
							case 'PHONE' :
								$phone = $fields->data;
								$user->phone = $phone;
								break;
							case 'EMAIL' :
								$email = $fields->data;
								$user->email = $email;
								break;
						}
					}

				}

				//Get additional information about the patron's home branch for display.
				if (isset($lookupMyAccountInfoResponse->fields->library->key)) {
					$homeBranchCode = strtolower(trim($lookupMyAccountInfoResponse->fields->library->key));
					//Translate home branch to plain text
					$location = new Location();
					$location->code = $homeBranchCode;
					if (!$location->find(true)) {
						unset($location);
					}
				} else {
					global $logger;
					$logger->log('SirsiDynixROA Driver: No Home Library Location or Hold location found in account look-up. User : ' . $user->id, Logger::LOG_ERROR);
					// The code below will attempt to find a location for the library anyway if the homeLocation is already set
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

				if (isset($lookupMyAccountInfoResponse->fields->privilegeExpiresDate)) {
					$user->_expires = $lookupMyAccountInfoResponse->fields->privilegeExpiresDate;
					list ($yearExp, $monthExp, $dayExp) = explode("-", $user->_expires);
					$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
					$timeNow = time();
					$timeToExpire = $timeExpire - $timeNow;
					if ($timeToExpire <= 30 * 24 * 60 * 60) {
						//TODO: the ils also has an expire soon flag in the patronStatusInfo
						if ($timeToExpire <= 0) {
							$user->_expired = 1;
						}
						$user->_expireClose = 1;
					}
				}

				//Get additional information about fines, etc

				$finesVal = 0;
				if (isset($lookupMyAccountInfoResponse->fields->blockList)) {
					foreach ($lookupMyAccountInfoResponse->fields->blockList as $block) {
						// $block is a simplexml object with attribute info about currency, type casting as below seems to work for adding up. plb 3-27-2015
						$fineAmount = (float)$block->fields->owed->amount;
						$finesVal += $fineAmount;
					}
				}

				$numHoldsAvailable = 0;
				$numHoldsRequested = 0;
				if (isset($lookupMyAccountInfoResponse->fields->holdRecordList)) {
					foreach ($lookupMyAccountInfoResponse->fields->holdRecordList as $hold) {
						if ($hold->fields->status == 'BEING_HELD') {
							$numHoldsAvailable++;
						} elseif ($hold->fields->status != 'EXPIRED') {
							$numHoldsRequested++;
						}
					}
				}

				$user->_address1 = $Address1;
				$user->_address2 = $City . ', ' . $State;
				$user->_city = $City;
				$user->_state = $State;
				$user->_zip = $Zip;
//				$user->phone                 = isset($phone) ? $phone : '';
				$user->_fines = sprintf('$%01.2f', $finesVal);
				$user->_finesVal = $finesVal;
				$user->_numCheckedOutIls = isset($lookupMyAccountInfoResponse->fields->circRecordList) ? count($lookupMyAccountInfoResponse->fields->circRecordList) : 0;
				$user->_numHoldsIls = $numHoldsAvailable + $numHoldsRequested;
				$user->_numHoldsAvailableIls = $numHoldsAvailable;
				$user->_numHoldsRequestedIls = $numHoldsRequested;
				$user->patronType = $lookupMyAccountInfoResponse->fields->profile->key;
				$user->_notices = '-';
				$user->_noticePreferenceLabel = 'Email';
				$user->_web_note = '';

				if ($userExistsInDB) {
					$user->update();
				} else {
					$user->created = date('Y-m-d');
					$user->insert();
				}

				return $user;

			}
		}
		return false;
	}

	public function patronLogin($username, $password, $validatedViaSSO)
	{
		global $timer;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Authenticate the user via WebService
		//First call loginUser
		$timer->logTime("Logging in through Symphony APIs");
		/** @noinspection PhpUnusedLocalVariableInspection */
		list($userValid, $sessionToken, $sirsiRoaUserID) = $this->loginViaWebService($username, $password);
		$staffSessionToken = $this->getStaffSessionToken();
		if ($validatedViaSSO) {
			$userValid = true;
		}
		if ($userValid) {
			$timer->logTime("User is valid in symphony");
			$webServiceURL = $this->getWebServiceURL();

			//  Calls that show how patron-related data is represented
			//	$patronDescribeResponse           = $this->getWebServiceResponse('patronDescribe', $webServiceURL . '/user/patron/describe', null, $sessionToken);
			//	$patronPhoneDescribeResponse           = $this->getWebServiceResponse('patronPhoneDescribe', $webServiceURL . '/user/patron/phone/describe', null, $sessionToken);
			//	$patronPhoneListDescribeResponse           = $this->getWebServiceResponse('patronPhoneListDescribe', $webServiceURL . '/user/patron/phoneList/describe', null, $sessionToken);
			//	$patronStatusInfoDescribeResponse = $this->getWebServiceResponse('patronStatusInfoDescribe', $webServiceURL . '/user/patronStatusInfo/describe', null, $sessionToken);
			//	$patronAddress1PolicyDescribeResponse = $this->getWebServiceResponse('patronDAddress1PolicyDescribe', $webServiceURL . '/user/patron/address1/describe', null, $sessionToken);

			$includeFields = urlEncode("firstName,lastName,privilegeExpiresDate,preferredAddress,address1,address2,address3,library,primaryPhone,profile,blockList{owed}");
			$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $sirsiRoaUserID . '?includeFields=' . $includeFields;

			// phoneList is for texting notification preferences
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse('accountLookupForLogin', $accountInfoLookupURL, null, $staffSessionToken);
			if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
				$userExistsInDB = false;
				$user = new User();
				$user->source = $this->accountProfile->name;
				$user->username = $sirsiRoaUserID;
				if ($user->find(true)) {
					$userExistsInDB = true;
				}
				$user->cat_username = $username;
				$user->cat_password = $password;

				$forceDisplayNameUpdate = false;
				$firstName = isset($lookupMyAccountInfoResponse->fields->firstName) ? $lookupMyAccountInfoResponse->fields->firstName : '';
				if ($user->firstname != $firstName) {
					$user->firstname = $firstName;
					$forceDisplayNameUpdate = true;
				}
				$lastName = isset($lookupMyAccountInfoResponse->fields->lastName) ? $lookupMyAccountInfoResponse->fields->lastName : '';
				if ($user->lastname != $lastName) {
					$user->lastname = isset($lastName) ? $lastName : '';
					$forceDisplayNameUpdate = true;
				}
				if ($forceDisplayNameUpdate) {
					$user->displayName = '';
				}

				$this->loadContactInformationFromApiResult($user, $lookupMyAccountInfoResponse);

				if ($userExistsInDB) {
					$user->update();
				} else {
					$user->created = date('Y-m-d');
					$user->insert();
				}

				$timer->logTime("patron logged in successfully");
				return $user;
			} else {
				$timer->logTime("lookupMyAccountInfo failed");
				global $logger;
				$logger->log('Symphony API call lookupMyAccountInfo failed.', Logger::LOG_ERROR);
				return null;
			}
		}
		return null;
	}

	public function getAccountSummary(User $patron) : AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		$webServiceURL = $this->getWebServiceURL();
		$includeFields = urlencode("privilegeExpiresDate,circRecordList{overdue},blockList{owed},holdRecordList{status}");
		$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $patron->username . '?includeFields=' . $includeFields;

		$sessionToken = $this->getSessionToken($patron);
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse('accountSummary', $accountInfoLookupURL, null, $sessionToken);

		if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
			$summary->numCheckedOut = count($lookupMyAccountInfoResponse->fields->circRecordList);
			foreach ($lookupMyAccountInfoResponse->fields->circRecordList as $checkout) {
				if ($checkout->fields->overdue) {
					$summary->numOverdue++;
				}
			}

			if (isset($lookupMyAccountInfoResponse->fields->holdRecordList)) {
				foreach ($lookupMyAccountInfoResponse->fields->holdRecordList as $hold) {
					//Get detailed info about the hold
					if ($hold->fields->status == 'BEING_HELD') {
						$summary->numAvailableHolds++;
					} elseif ($hold->fields->status != 'EXPIRED') {
						$summary->numUnavailableHolds++;
					}
				}
			}

			$finesVal = 0;
			if (isset($lookupMyAccountInfoResponse->fields->blockList)) {
				foreach ($lookupMyAccountInfoResponse->fields->blockList as $block) {
					$fineAmount = (float)$block->fields->owed->amount;
					$finesVal += $fineAmount;
				}
			}
			$summary->totalFines = $finesVal;

			if ($lookupMyAccountInfoResponse->fields->privilegeExpiresDate == null) {
				$summary->expirationDate = 0;
			}else{
				$summary->expirationDate = strtotime($lookupMyAccountInfoResponse->fields->privilegeExpiresDate);
			}
		}

		return $summary;
	}

	private function getStaffSessionToken()
	{
		$staffSessionToken = false;
		if (!empty($this->accountProfile->staffUsername) && !empty($this->accountProfile->staffPassword)) {
			list(, $staffSessionToken) = $this->staffLoginViaWebService($this->accountProfile->staffUsername, $this->accountProfile->staffPassword);
		}
		return $staffSessionToken;
	}

	function selfRegister()
	{
		$selfRegResult = array(
			'success' => false,
		);

		$sessionToken = $this->getStaffSessionToken();
		if (!empty($sessionToken)) {
			$webServiceURL = $this->getWebServiceURL();

			// $patronDescribeResponse   = $this->getWebServiceResponse('patronDescribe', $webServiceURL . '/user/patron/describe');
			// $address1DescribeResponse = $this->getWebServiceResponse('address1Describe', $webServiceURL . '/user/patron/address1/describe');
			// $addressDescribeResponse  = $this->getWebServiceResponse('addressDescribe', $webServiceURL . '/user/patron/address/describe');
			// $userProfileDescribeResponse  = $this->getWebServiceResponse('userProfileDescribe', $webServiceURL . '/policy/userProfile/describe');

			$createPatronInfoParameters = array(
				'fields' => array(),
				'resource' => '/user/patron',
			);
			$preferredAddress = 1;

			// Build Address Field with existing data
			$index = 0;

			$createPatronInfoParameters['fields']['profile'] = array(
				'resource' => '/policy/userProfile',
				'key' => 'SELFREG', //TODO: This needs to be configurable
			);

			if (!empty($_REQUEST['firstName'])) {
				$createPatronInfoParameters['fields']['firstName'] = trim($_REQUEST['firstName']);
			}
			if (!empty($_REQUEST['middleName'])) {
				$createPatronInfoParameters['fields']['middleName'] = trim($_REQUEST['middleName']);
			}
			if (!empty($_REQUEST['lastName'])) {
				$createPatronInfoParameters['fields']['lastName'] = trim($_REQUEST['lastName']);
			}
			if (!empty($_REQUEST['suffix'])) {
				$createPatronInfoParameters['fields']['suffix'] = trim($_REQUEST['suffix']);
			}
			if (!empty($_REQUEST['birthDate'])) {
				$birthdate = date_create_from_format('m-d-Y', trim($_REQUEST['birthDate']));
				$createPatronInfoParameters['fields']['birthDate'] = $birthdate->format('Y-m-d');
			}

			// Update Address Field with new data supplied by the user
			if (isset($_REQUEST['email'])) {
				$this->setPatronUpdateField('EMAIL', $_REQUEST['email'], $updatePatronInfoParameters, $preferredAddress, $index);
			}

			if (isset($_REQUEST['phone'])) {
				$this->setPatronUpdateField('PHONE', $_REQUEST['phone'], $updatePatronInfoParameters, $preferredAddress, $index);
			}

			if (isset($_REQUEST['address'])) {
				$this->setPatronUpdateField('STREET', $_REQUEST['address'], $updatePatronInfoParameters, $preferredAddress, $index);
			}

			if (isset($_REQUEST['city']) && isset($_REQUEST['state'])) {
				$this->setPatronUpdateField('CITY/STATE', $_REQUEST['city'] . ' ' . $_REQUEST['state'], $updatePatronInfoParameters, $preferredAddress, $index);
			}

			if (isset($_REQUEST['zip'])) {
				$this->setPatronUpdateField('ZIP', $_REQUEST['zip'], $updatePatronInfoParameters, $preferredAddress, $index);
			}

			// Update Home Location
			if (!empty($_REQUEST['pickupLocation'])) {
				$homeLibraryLocation = new Location();
				if ($homeLibraryLocation->get('code', $_REQUEST['pickupLocation'])) {
					$homeBranchCode = strtoupper($homeLibraryLocation->code);
					$createPatronInfoParameters['fields']['library'] = array(
						'key' => $homeBranchCode,
						'resource' => '/policy/library'
					);
				}
			}

			//TODO: We should be able to create either a random barcode or a barcode starting with a specific prefix and choose the length.
			$barcode = new Variable();
			$barcode->name = 'self_registration_card_number';
			if ($barcode->find(true)) {
				$createPatronInfoParameters['fields']['barcode'] = $barcode->value;

				//global $configArray;
				//$overrideCode = $configArray['Catalog']['selfRegOverrideCode'];
				//$overrideHeaders = array('SD-Prompt-Return:USER_PRIVILEGE_OVRCD/' . $overrideCode);


				$createNewPatronResponse = $this->getWebServiceResponse('selfRegister', $webServiceURL . '/user/patron/', $createPatronInfoParameters, $sessionToken, 'POST');

				if (isset($createNewPatronResponse->messageList)) {
					foreach ($createNewPatronResponse->messageList as $message) {
						$updateErrors[] = $message->message;
						if ($message->message == 'User already exists') {
							// This means the barcode counter is off.
							global $logger;
							$logger->log('Sirsi Self Registration response was that the user already exists. Advancing the barcode counter by one.', Logger::LOG_ERROR);
							$barcode->value++;
							if (!$barcode->update()) {
								$logger->log('Sirsi Self Registration barcode counter did not increment when a user already exists!', Logger::LOG_ERROR);
							}
						}
					}
					global $logger;
					$logger->log('Symphony Driver - Patron Info Update Error - Error from ILS : ' . implode(';', $updateErrors), Logger::LOG_ERROR);
				} else {

					$selfRegResult = array(
						'success' => true,
						'barcode' => $barcode->value,
						'requirePinReset' => true,
					);
					// Update the card number counter for the next Self-Reg user
					$barcode->value++;
					if (!$barcode->update()) {
						// Log Error temp barcode number not
						global $logger;
						$logger->log('Sirsi Self Registration barcode counter not saving incremented value!', Logger::LOG_ERROR);
					}
				}
			} else {
				// Error: unable to set barcode number.
				global $logger;
				$logger->log('Sirsi Self Registration barcode counter was not found!', Logger::LOG_ERROR);
				$selfRegResult['Barcode starting index was not found.'];
			};
		} else {
			// Error: unable to login in staff user
			global $logger;
			$logger->log('Unable to log in with Sirsi Self Registration staff user', Logger::LOG_ERROR);
		}
		return $selfRegResult;
	}


	protected function loginViaWebService($username, $password)
	{
		/** @var Memcache $memCache */
		global $memCache;
		global $library;
		$memCacheKey = "sirsiROA_session_token_info_{$library->libraryId}_$username";
		$session = $memCache->get($memCacheKey);
		if ($session != false) {
			list(, $sessionToken, $sirsiRoaUserID) = $session;
			SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserID] = $sessionToken;
		} else {
			$session = array(false, false, false);
			$webServiceURL = $this->getWebServiceURL();
			//$loginDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/user/patron/login/describe');
			$loginUserUrl = $webServiceURL . '/user/patron/login';
			$params = [
				'login' => $username,
				'password' => $password,
			];
			$loginUserResponse = $this->getWebServiceResponse('patronLogin', $loginUserUrl, $params, null, null, null, ['password', $password]);
			if ($loginUserResponse && isset($loginUserResponse->sessionToken)) {
				//We got at valid user (A bad call will have isset($loginUserResponse->messageList) )
				$sirsiRoaUserID = $loginUserResponse->patronKey;
				$sessionToken = $loginUserResponse->sessionToken;
				SirsiDynixROA::$sessionIdsForUsers[(string)$sirsiRoaUserID] = $sessionToken;
				$session = array(true, $sessionToken, $sirsiRoaUserID);
				global $configArray;
				$memCache->set($memCacheKey, $session, $configArray['Caching']['sirsi_roa_session_token']);
			} elseif (isset($loginUserResponse->messageList)) {
				global $logger;
				$errorMessage = 'Sirsi ROA Webservice Login Error: ';
				foreach ($loginUserResponse->messageList as $error) {
					$errorMessage .= $error->message . '; ';
				}
				$logger->log($errorMessage, Logger::LOG_ERROR);
				$logger->log(print_r($loginUserResponse, true), Logger::LOG_ERROR);
				$session = array(false, '', '');
			}
		}
		return $session;
	}

	protected function staffLoginViaWebService($username, $password)
	{
		/** @var Memcache $memCache */
		global $memCache;
		global $library;
		$memCacheKey = "sirsiROA_session_token_info_{$library->libraryId}_$username";
		$session = $memCache->get($memCacheKey);
		if ($session) {
			list(, $sessionToken, $sirsiRoaUserID) = $session;
			SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserID] = $sessionToken;
		} else {
			$session = array(false, false, false);
			$webServiceURL = $this->getWebServiceURL();
			$loginUserUrl = $webServiceURL . '/user/staff/login';
			$params = array(
				'login' => $username,
				'password' => $password,
			);
			$loginUserResponse = $this->getWebServiceResponse('staffLogin', $loginUserUrl, $params, null, null, null, ['password', $password]);
			if ($loginUserResponse && isset($loginUserResponse->sessionToken)) {
				//We got at valid user (A bad call will have isset($loginUserResponse->messageList) )

				$sirsiRoaUserID = $loginUserResponse->staffKey;
				//this is the same value as patron Key, if user is logged in with that call.
				$sessionToken = $loginUserResponse->sessionToken;
				SirsiDynixROA::$sessionIdsForUsers[(string)$sirsiRoaUserID] = $sessionToken;
				$session = array(true, $sessionToken, $sirsiRoaUserID);
				global $configArray;
				$memCache->set($memCacheKey, $session, $configArray['Caching']['sirsi_roa_session_token']);
			} elseif (isset($loginUserResponse->messageList)) {
				global $logger;
				$errorMessage = 'Sirsi ROA Staff Webservice Login Error: ';
				foreach ($loginUserResponse->messageList as $error) {
					$errorMessage .= $error->message . '; ';
				}
				$logger->log($errorMessage, Logger::LOG_ERROR);
			}
		}
		return $session;
	}

	/**
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @return Checkout[]
	 */
	public function getCheckouts($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate')
	{
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = array();

		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $checkedOutTitles;
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		//Get a list of holds for the user
		$includeFields = urlencode('circRecordList{*,item{barcode,bib{title,author},itemType,call{dispCallNumber}}}');
		$patronCheckouts = $this->getWebServiceResponse('getCheckouts', $webServiceURL . '/user/patron/key/' . $patron->username . '?includeFields=' . $includeFields, null, $sessionToken);

		if (!empty($patronCheckouts->fields->circRecordList)) {
			$sCount = 0;
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';

			foreach ($patronCheckouts->fields->circRecordList as $checkout) {
				if (empty($checkout->fields->claimsReturnedDate) && $checkout->fields->status != 'INACTIVE') { // Titles with a claims return date will not be displayed in check outs.
					$curCheckout = new Checkout();
					$curCheckout->type = 'ils';
					$curCheckout->source = $this->getIndexingProfile()->name;
					$curCheckout->sourceId = $checkout->key;
					$curCheckout->userId = $patron->id;

					list($bibId) = explode(':', $checkout->key);
					$curCheckout->recordId = 'a' . $bibId;
					$curCheckout->itemId = $checkout->fields->item->key;

					$curCheckout->dueDate = strtotime($checkout->fields->dueDate);
					$curCheckout->checkoutDate = strtotime($checkout->fields->checkOutDate);
					// Note: there is an overdue flag
					$curCheckout->renewCount = $checkout->fields->renewalCount;
					$curCheckout->canRenew = $checkout->fields->seenRenewalsRemaining > 0;
					$curCheckout->renewalId = $checkout->fields->item->key;
					$curCheckout->renewIndicator = $checkout->fields->item->key;
					$curCheckout->barcode = $checkout->fields->item->fields->barcode;

					$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . $curCheckout->recordId);
					if ($recordDriver->isValid()){
						$curCheckout->updateFromRecordDriver($recordDriver);
					}else{
						// Presumably ILL Items
						$bibInfo = $checkout->fields->item->fields->bib;
						$curCheckout->author = $bibInfo->fields->author;
						$curCheckout->title = $bibInfo->fields->title;
						require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
						$curCheckout->author = empty($bibInfo->fields->author) ? '' : StringUtils::removeTrailingPunctuation($bibInfo->fields->author);
					}
					if (!empty($checkout->fields->item->fields->call->fields->dispCallNumber)) {
						$curCheckout->callNumber = $checkout->fields->item->fields->call->fields->dispCallNumber;
					}

					$sCount++;
					$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$sCount";
					$checkedOutTitles[$sortKey] = $curCheckout;
				}
			}
		}
		return $checkedOutTitles;
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
	public function getHolds($patron)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $holds;
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();

		//$patronDescribeResponse = $this->getWebServiceResponse('patronDescribe', $webServiceURL . '/user/patron/describe', null, $sessionToken);
		//$holdRecord  = $this->getWebServiceResponse('holdRecordDescribe', $webServiceURL . "/circulation/holdRecord/describe", null, $sessionToken);
		//$itemDescribe  = $this->getWebServiceResponse('itemDescribe', $webServiceURL . "/catalog/item/describe", null, $sessionToken);
		//$callDescribe  = $this->getWebServiceResponse('callDescribe', $webServiceURL . "/catalog/call/describe", null, $sessionToken);
		//$copyDescribe  = $this->getWebServiceResponse('copyDescribe', $webServiceURL . "/catalog/copy/describe", null, $sessionToken);

		//Get a list of holds for the user
		// (Call now includes Item information for when the hold is an item level hold.)
		$includeFields = urlencode("holdRecordList{*,bib{title,author},selectedItem{call{*},itemType{*}}}");
		$patronHolds = $this->getWebServiceResponse('getHolds', $webServiceURL . '/user/patron/key/' . $patron->username . '?includeFields=' . $includeFields, null, $sessionToken);
		if ($patronHolds && isset($patronHolds->fields)) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			foreach ($patronHolds->fields->holdRecordList as $hold) {
				//Get detailed info about the hold
				$curHold = new Hold();
				$bibId = $hold->fields->bib->key;
				$expireDate = $hold->fields->expirationDate;
				$reactivateDate = $hold->fields->suspendEndDate;
				$createDate = $hold->fields->placedDate;
				$fillByDate = $hold->fields->fillByDate;
				$curHold->userId = $patron->id;
				$curHold->type = 'ils';
				$curHold->source = $this->getIndexingProfile()->name;
				$curHold->sourceId = $bibId;
				$curHold->itemId = empty($hold->fields->item->key) ? '' : $hold->fields->item->key;
				$curHold->cancelId = $hold->key;
				$curHold->position = $hold->fields->queuePosition;
				$curHold->recordId = 'a' . $bibId;
				$curHold->shortId = $bibId;
				$curPickupBranch = new Location();
				$curPickupBranch->code = $hold->fields->pickupLibrary->key;
				if ($curPickupBranch->find(true)) {
					$curPickupBranch->fetch();
					$curHold->pickupLocationId = $curPickupBranch->locationId;
					$curHold->pickupLocationName = $curPickupBranch->displayName;
				}else{
					$curHold->pickupLocationName = $curPickupBranch->code;
				}

				$curHold->status = ucfirst(strtolower($hold->fields->status));
				$curHold->createDate = strtotime($createDate);
				$curHold->expirationDate = strtotime($expireDate);
				$curHold->automaticCancellationDate = strtotime($fillByDate);
				$curHold->reactivateDate = strtotime($reactivateDate);
				$curHold->cancelable = strcasecmp($curHold->status, 'Suspended') != 0 && strcasecmp($curHold->status, 'Expired') != 0;
				$curHold->frozen = strcasecmp($curHold->status, 'Suspended') == 0;
				$curHold->canFreeze = true;
				if (strcasecmp($curHold->status, 'Transit') == 0 || strcasecmp($curHold->status, 'Expired') == 0) {
					$curHold->canFreeze = false;
				}
				$curHold->locationUpdateable = true;
				if (strcasecmp($curHold->status, 'Transit') == 0 || strcasecmp($curHold->status, 'Expired') == 0) {
					$curHold->locationUpdateable = false;
				}
				if (isset($hold->fields->selectedItem->fields->call->fields->volumetric)) {
					$curHold->volume = $hold->fields->selectedItem->fields->call->fields->volumetric;
				}

				if ($hold->fields->holdType == 'COPY') {
					$curHold->title2 = $hold->fields->selectedItem->fields->itemType->fields->description . ' - ' . $hold->fields->selectedItem->fields->call->fields->dispCallNumber;
				}

				$bibInfo = $hold->fields->bib;
				$curHold->title = $bibInfo->fields->title;
				if (isset($bibInfo->fields->author)) {
					$curHold->author = $bibInfo->fields->author;
				}

				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($curHold->recordId); // This needs the $carlID
				if ($recordDriver->isValid()){
					$curHold->updateFromRecordDriver($recordDriver);
				}

				if (!isset($curHold->status) || strcasecmp($curHold->status, "being_held") != 0) {
					$curHold->available = false;
					$holds['unavailable'][] = $curHold;
				} else {
					$curHold->available = true;
					$holds['available'][] = $curHold;
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
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate The date the hold should be automatically cancelled
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		return $this->placeItemHold($patron, $recordId, null, $pickupBranch, 'request', $cancelDate);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $pickupBranch The Pickup Location
	 * @param string $type Whether to place a hold or recall
	 * @param null|string $cancelIfNotFilledByDate When to cancel the hold automatically if it is not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch = null, $type = 'request', $cancelIfNotFilledByDate = null){
		return $this->placeSirsiHold($patron, $recordId, $itemId, false, $pickupBranch, $type, $cancelIfNotFilledByDate);
	}
	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $volume The volume identifier of the item to hold
	 * @param string $pickupBranch The Pickup Location
	 * @param string $type Whether to place a hold or recall
	 * @param null|string $cancelIfNotFilledByDate When to cancel the hold automatically if it is not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeSirsiHold($patron, $recordId, $itemId, $volume = null, $pickupBranch = null, $type = 'request', $cancelIfNotFilledByDate = null)
	{
		//Get the session token for the user
		$staffSessionToken = $this->getStaffSessionToken();
		$sessionToken = $this->getSessionToken($patron);
		if (!$staffSessionToken) {
			$result['success'] = false;
			$result['message'] = translate(['text'=>"Sorry, it does not look like you are logged in currently.  Please login and try again", 'isPublicFacing'=>true]);

			$result['api']['title'] = translate(['text'=>'Error', 'isPublicFacing'=>true]);
			$result['api']['message'] = translate(['text'=> 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=>true]);
			return $result;
		}

		if (strpos($recordId, ':') !== false){
			list(,$shortId) = explode(':', $recordId);
		}else{
			$shortId = $recordId;
		}

		// Retrieve Full Marc Record
		require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
		$record = RecordDriverFactory::initRecordDriverById($this->accountProfile->recordSource . ':' . $shortId);
		if (!$record) {
			$title = null;
		} else {
			$title = $record->getTitle();
		}

		global $offlineMode;
		if ($offlineMode) {
			require_once ROOT_DIR . '/sys/OfflineHold.php';
			$offlineHold                = new OfflineHold();
			$offlineHold->bibId         = $shortId;
			$offlineHold->patronBarcode = $patron->getBarcode();
			$offlineHold->patronId      = $patron->id;
			$offlineHold->timeEntered   = time();
			$offlineHold->status        = 'Not Processed';
			if ($offlineHold->insert()) {
				//TODO: use bib or bid ??
				$result['success'] = true;
				$result['title'] = $title;
				$result['bib'] = $shortId;
				$result['message'] = translate(['text'=>"The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.", 'isPublicFacing'=>true]);

				$result['api']['title'] = translate(['text'=>'Circulation system offline', 'isPublicFacing'=>true]);
				$result['api']['message'] = translate(['text'=> 'The circulation system is currently offline.  This hold will be entered for you automatically when the circulation system is online.', 'isPublicFacing'=>true]);
				return $result;
			} else {
				$result['success'] = false;
				$result['title'] = $title;
				$result['bib'] = $shortId;
				$result['message'] = translate(['text'=>"The circulation system is currently offline and we could not place this hold.  Please try again later.", 'isPublicFacing'=>true]);

				$result['api']['title'] = translate(['text'=>'Circulation system offline', 'isPublicFacing'=>true]);
				$result['api']['message'] = translate(['text'=> 'The circulation system is currently offline and we could not place this hold.  Please try again later.', 'isPublicFacing'=>true]);
				return $result;
			}

		} else {
			if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
				$result          = $this->updateHold($patron, $recordId, $type/*, $title*/);
				$result['title'] = $title;
				$result['bid']   = $shortId;

				return $result;

			} else {
				if (empty($pickupBranch)) {
					$pickupBranch = $patron->_homeLocationCode;
				}
				//create the hold using the web service
				$webServiceURL = $this->getWebServiceURL();

				$holdData = array(
					'patronBarcode' => $patron->getBarcode(),
					'pickupLibrary' => array(
						'resource' => '/policy/library',
						'key' => strtoupper($pickupBranch)
					),
				);

				if (!empty($volume)){
					$holdData['call'] = array(
						'resource' => '/catalog/call',
						'key' => $volume
					);
					$holdData['holdType'] = 'TITLE';
				} elseif (!empty($itemId)) {
					$holdData['itemBarcode'] = $itemId;
					$holdData['holdType'] = 'COPY';
				} else {
					$shortRecordId = str_replace('a', '', $shortId);
					$holdData['bib'] = array(
						'resource' => '/catalog/bib',
						'key' => $shortRecordId
					);
					$holdData['holdType'] = 'TITLE';
				}

				//TODO: Look into holds for different ranges (Group/Library)
				$holdData['holdRange'] = 'SYSTEM';

				if ($cancelIfNotFilledByDate) {
					$holdData['fillByDate'] = date('Y-m-d', strtotime($cancelIfNotFilledByDate));
				}
				//$holdRecord         = $this->getWebServiceResponse('holdRecordDescribe', $webServiceURL . "/circulation/holdRecord/describe", null, $sessionToken);
				//$placeHold          = $this->getWebServiceResponse('placeHoldDescribe', $webServiceURL . "/circulation/holdRecord/placeHold/describe", null, $sessionToken);
				$createHoldResponse = $this->getWebServiceResponse('placeHold', $webServiceURL . "/circulation/holdRecord/placeHold", $holdData, $sessionToken);

				$hold_result = array();
				if (isset($createHoldResponse->messageList)) {
					$hold_result['success'] = false;
					$hold_result['message'] = translate(['text'=>'Your hold could not be placed.', 'isPublicFacing'=>true]);

					$hold_result['api']['title'] = translate(['text'=>'Unable to place hold', 'isPublicFacing'=>true]);
					$hold_result['api']['message'] = translate(['text'=> 'Your hold could not be placed.', 'isPublicFacing'=>true]);

					if (isset($createHoldResponse->messageList)) {
						$hold_result['message'] .= ' ' . translate(['text'=> (string)$createHoldResponse->messageList[0]->message, 'isPublicFacing'=>true]);
						global $logger;
						$errorMessage = 'Sirsi ROA Place Hold Error: ';
						foreach ($createHoldResponse->messageList as $error){
							$errorMessage .= $error->message.'; ';
						}
						if (IPAddress::showDebuggingInformation()){
							$hold_result['message'] .= "<br>\r\n" . print_r($holdData, true);
						}
						$logger->log($errorMessage, Logger::LOG_ERROR);
					}
				} else {
					$hold_result['success'] = true;
					$hold_result['message'] = translate(['text'=>"Your hold was placed successfully.", 'isPublicFacing'=>true]);

					$hold_result['api']['title'] = translate(['text'=>'Hold placed successfully', 'isPublicFacing'=>true]);
					$hold_result['api']['message'] = translate(['text'=> 'Your hold was placed successfully.', 'isPublicFacing'=>true]);
					$hold_result['api']['action'] = translate(['text' => 'Go to Holds', 'isPublicFacing'=>true]);

					$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
					$patron->forceReloadOfHolds();
				}

				$hold_result['title'] = $title;
				$hold_result['bid']   = $shortId;
				return $hold_result;
			}
		}
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch)
	{
		//To place a volume hold in Symphony, we just need to place a hold on one of the items for the volume.
		require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
		$volumeInfo = new IlsVolumeInfo();
		$volumeInfo->volumeId = $volumeId;
		$volumeInfo->recordId = $this->getIndexingProfile()->name . ':' . $recordId;
		if ($volumeInfo->find(true)){
			$relatedItems = explode('|', $volumeInfo->relatedItems);
			$itemToHold = $relatedItems[0];
			return $this->placeSirsiHold($patron, $recordId, $itemToHold, $volumeId, $pickupBranch);
		}else{
			return array(
				'success' => false,
				'message' => 'Sorry, we could not find the specified volume, it may have been deleted.');
		}
	}


	private function getSessionToken($patron)
	{
		if (UserAccount::isUserMasquerading()){
			//If the user is masquerading, we will use the staff login since we might not have the patron PIN
			$sirsiRoaUserId = UserAccount::getGuidingUserObject()->username;
		}else{
			$sirsiRoaUserId = $patron->username;
		}


		//Get the session token for the user
		if (isset(SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserId])) {
			return SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserId];
		} else {
			if (UserAccount::isUserMasquerading()){
				//If the user is masquerading, we will use the staff login since we might not have the patron PIN
				//list($userValid, $sessionToken) = $this->loginViaWebService(UserAccount::getGuidingUserObject()->cat_username, UserAccount::getGuidingUserObject()->cat_password);
				$sessionToken = $this->getStaffSessionToken();
				return $sessionToken;
			}
			list(, $sessionToken) = $this->loginViaWebService($patron->cat_username, $patron->cat_password);
			return $sessionToken;
		}
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		$result = [];
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate(['text' => 'Sorry, we could not connect to the circulation system.', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Error', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Sorry, we could not connect to the circulation system', 'isPublicFacing'=> true]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$cancelHoldResponse = $this->getWebServiceResponse('cancelHold', $webServiceURL . "/circulation/holdRecord/key/$cancelId", null, $sessionToken, 'DELETE');

		if (empty($cancelHoldResponse)) {
			$patron->forceReloadOfHolds();
			$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
			$result['success'] = true;
			$result['message'] = translate(['text'=>'The hold was successfully canceled.', 'isPublicFacing'=> true]);

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Hold cancelled', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'The hold was successfully canceled', 'isPublicFacing'=> true]);

			return $result;
		} else {
			global $logger;
			$errorMessage = 'Sirsi ROA Cancel Hold Error: ';
			foreach ($cancelHoldResponse->messageList as $error){
				$errorMessage .= $error->message.'; ';
			}
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result['success'] = false;
			$result['message'] = translate(['text'=>'Sorry, the hold was not canceled', 'isPublicFacing'=> true]);

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Unable to cancel hold', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'This hold could not be cancelled. Please try again later or see your librarian.', 'isPublicFacing'=> true]);

			return $result;
		}

	}

	function changeHoldPickupLocation(User $patron, $recordId, $holdId, $newPickupLocation)
	{
		$staffSessionToken = $this->getStaffSessionToken();
		if (!$staffSessionToken) {
			$result = [
				'success' => false,
				'message' => translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Error', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=> true]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$params = [
			'holdRecord' => [
				'resource' => '/circulation/holdRecord',
				'key'=>$holdId,
			],
			'pickupLibrary' => [
				'resource' => '/policy/library',
				'key' => strtoupper($newPickupLocation),
			],
		];

		$updateHoldResponse = $this->getWebServiceResponse('changePickupLibrary', $webServiceURL . "/circulation/holdRecord/changePickupLibrary", $params, $this->getSessionToken($patron), 'POST');
		if (isset($updateHoldResponse->holdRecord->key)) {
			$patron->forceReloadOfHolds();
			$result['message'] = translate(['text'=>'The pickup location has been updated.', 'isPublicFacing'=>true]);
			$result['success'] = true;

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Pickup location updated', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'The pickup location of your hold was changed successfully.', 'isPublicFacing'=> true]);

			return $result;
		} else {
			$messages = array();
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Sirsi ROA Change Hold Pickup Location Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			return array(
				'success' => false,
				'message' => 'Failed to update the pickup location : '. implode('; ', $messages)
			);

			$result['message'] = 'Failed to update the pickup location : '. implode('; ', $messages);
			$result['success'] = false;

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Unable to update pickup location', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Sorry, the pickup location of your hold could not be changed. ', 'isPublicFacing'=> true]);
			$result['api']['message'] .= ' ' . implode('; ', $messages);
			return $result;
		}
	}

	function freezeHold(User $patron, $recordId, $holdToFreezeId, $dateToReactivate)
	{
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Error', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=> true]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$today = date('Y-m-d');
		$formattedDateToReactivate = $dateToReactivate ? date('Y-m-d', strtotime($dateToReactivate)) : null;

		$params = array(
			'holdRecord' => [
				'key' => $holdToFreezeId,
				'resource' => '/circulation/holdRecord',
			],
			'suspendBeginDate' => $today,
			'suspendEndDate' => $formattedDateToReactivate
		);

		$updateHoldResponse = $this->getWebServiceResponse('suspendHold', $webServiceURL . "/circulation/holdRecord/suspendHold", $params, $sessionToken, 'POST');

		if (isset($updateHoldResponse->holdRecord->key)) {
			$getHoldResponse = $this->getWebServiceResponse('getHold', $webServiceURL . "/circulation/holdRecord/key/$holdToFreezeId", null, $this->getSessionToken($patron));
			if (isset($getHoldResponse->fields->status) && $getHoldResponse->fields->status == 'SUSPENDED'){
				$patron->forceReloadOfHolds();
				$result = [
					'success' => true,
					'message' => translate(['text' => 'The hold has been frozen.', 'isPublicFacing'=>true]),
				];

				// Result for API or app use
				$result['api']['title'] = translate(['text' => 'Hold frozen', 'isPublicFacing'=> true]);
				$result['api']['message'] = translate(['text' => 'Your hold was frozen successfully.', 'isPublicFacing'=> true]);

				return $result;
			}else{
				$result = [
					'success' => false,
					'message' => translate(['text' => 'The hold could not be frozen.', 'isPublicFacing'=>true]),
				];

				// Result for API or app use
				$result['api']['title'] = translate(['text' => 'Unable to freeze hold', 'isPublicFacing'=> true]);
				$result['api']['message'] = translate(['text' => 'There was an error freezing your hold.', 'isPublicFacing'=> true]);

				return $result;
			}

		} else {
			$messages = array();
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}

			global $logger;
			$errorMessage = 'Sirsi ROA Freeze Hold Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [
				'success' => false,
				'message' => translate(['text' => "Failed to freeze hold", 'isPublicFacing'=>true]) . ' - ' . implode('; ', $messages),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Unable to freeze hold', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'There was an error freezing your hold.', 'isPublicFacing'=> true]);
			$result['api']['message'] .= ' ' . implode('; ', $messages);

			return $result;
		}
	}

	function thawHold($patron, $recordId, $holdToThawId)
	{
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Error', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=> true]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$params = array(
			'holdRecord' => [
				'key' => $holdToThawId,
				'resource' => '/circulation/holdRecord',
			],
		);

		$updateHoldResponse = $this->getWebServiceResponse('unsuspendHold', $webServiceURL . "/circulation/holdRecord/unsuspendHold", $params, $sessionToken, 'POST');

		if (isset($updateHoldResponse->holdRecord->key)) {
			$patron->forceReloadOfHolds();
			$result = [
				'success' => true,
				'message' => translate(['text' => 'The hold has been thawed.', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Hold thawed', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Your hold was thawed successfully.', 'isPublicFacing'=> true]);

			return $result;
		} else {
			$messages = array();
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Sirsi ROA Thaw Hold Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [
				'success' => false,
				'message' => translate(['text' => "Failed to thaw hold", 'isPublicFacing'=>true]) . ' - ' . implode('; ', $messages),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Unable to thaw hold', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'There was an error thawing your hold.', 'isPublicFacing'=> true]);
			$result['api']['message'] .= ' ' . implode('; ', $messages);

			return $result;
		}
	}

	/**
	 * @param User $patron
	 * @param string $recordId
	 * @param string $itemId
	 * @param string $itemIndex
	 * @return array
	 */
	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text' => 'Error', 'isPublicFacing'=> true]);
			$result['api']['message'] = translate(['text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again', 'isPublicFacing'=> true]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$params = array(
			'item' => array(
				'key' => $itemId,
				'resource' => '/catalog/item'
			)
		);

		$circRenewResponse  = $this->getWebServiceResponse('renewCheckout', $webServiceURL . "/circulation/circRecord/renew", $params, $sessionToken, 'POST');

		if (isset($circRenewResponse->circRecord->key)) {
			// Success
			$patron->forceReloadOfCheckouts();
			$result = [
				'success' => true,
				'itemId' => $circRenewResponse->circRecord->key,
				'message' => translate(['text' => 'Your item was successfully renewed.', 'isPublicFacing'=>true]),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text'=>'Renewed title', 'isPublicFacing'=>true]);
			$result['api']['message'] = translate(['text' => 'Your item was successfully renewed.', 'isPublicFacing'=> true]);

			return $result;
		} else {
			// Error
			$messages = array();
			if (isset($circRenewResponse->messageList)) {
				foreach ($circRenewResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Sirsi ROA Renew Error: '. ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [
				'success' => false,
				'itemId' => $circRenewResponse->circRecord->key,
				'message' => "The item failed to renew". ($messages ? ': '. implode(';', $messages) : ''),
			];

			// Result for API or app use
			$result['api']['title'] = translate(['text'=>'Unable to renew title', 'isPublicFacing'=>true]);
			$result['api']['message'] = translate(['text' => 'The item failed to renew.', 'isPublicFacing'=> true]);
			$result['api']['message'] .= ' ' . ($messages ? ': '. implode(';', $messages) : '');

			return $result;

		}

	}

	/**
	 * @param User $patron
	 * @param $includeMessages
	 * @return array|AspenError
	 */
	public function getFines($patron, $includeMessages = false)
	{
		$fines = array();
		$sessionToken = $this->getSessionToken($patron);
		if ($sessionToken) {

			//create the hold using the web service
			$webServiceURL = $this->getWebServiceURL();

			$includeFields = urlencode("blockList{*,item{bib{title,author}}}");
			$blockList = $this->getWebServiceResponse('getFines', $webServiceURL . '/user/patron/key/' . $patron->username . '?includeFields=' . $includeFields, null, $sessionToken);
			// Include Title data if available

			if (!empty($blockList->fields->blockList)) {
				foreach ($blockList->fields->blockList as $block) {
					$fine = $block->fields;
					$title = '';
					if (!empty($fine->item) && !empty($fine->item->key)) {
						$bibInfo  = $fine->item->fields->bib;
						$title = $bibInfo->fields->title;
						if (!empty($bibInfo->fields->author)) {
							$title .= '  by '.$bibInfo->fields->author;
						}

					}
					$fines[] = array(
						'fineId' => str_replace(':', '_', $block->key),
						'reason' => translate(['text'=>$fine->block->key, 'isPublicFacing'=>true, 'isAdminEnteredData'=>true]),
						'type' => $fine->block->key,
						'amount' => $fine->amount->amount,
						'amountVal' => $fine->amount->amount,
						'message' => $title,
						'amountOutstanding' => $fine->owed->amount,
						'amountOutstandingVal' => $fine->owed->amount,
						'date' => $fine->billDate
					);
				}
			}
		}
		return $fines;
	}

	/**
	 * @param User $patron
	 * @param $oldPin
	 * @param $newPin
	 * @return array
	 */
	function updatePin(User $patron, string $oldPin, string $newPin)
	{
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return ['success' => false, 'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again'];
		}

		$params = array(
			'currentPin' => $oldPin,
		    'newPin' => $newPin
		);

		$webServiceURL = $this->getWebServiceURL();

		$updatePinResponse = $this->getWebServiceResponse('changePin', $webServiceURL . "/user/patron/changeMyPin", $params, $sessionToken, 'POST');
		if (!empty($updatePinResponse->patronKey) && $updatePinResponse->patronKey ==  $patron->username) {
			$patron->cat_password = $newPin;
			$patron->update();
			return ['success' => true, 'message' => "Your pin number was updated successfully."];

		} else {
			$messages = array();
			if (isset($updatePinResponse->messageList)) {
				foreach ($updatePinResponse->messageList as $message) {
					$messages[] = $message->message;
					if ($message->message == 'Public access users may not change this user\'s PIN') {
						$staffPinError = 'Staff can not change their PIN through the online catalog.';
					}
				}
				global $logger;
				$logger->log('Symphony ILS encountered errors updating patron pin : '. implode('; ', $messages), Logger::LOG_ERROR);
				if (!empty($staffPinError) ){
					return ['success' => false, 'message' => $staffPinError];
				} else {
					return ['success' => false, 'message' => 'The circulation system encountered errors attempt to update the pin.'];
				}
			}
			return ['success' => false, 'message' =>'Failed to update pin'];
		}
	}

    /**
     * @param User|null $user
     * @param string $newPin
     * @param string $resetToken
     * @return array
     */
	function resetPin($user, $newPin, $resetToken=null){
		if (empty($resetToken)) {
			global $logger;
			$logger->log('No Reset Token passed to resetPin function', Logger::LOG_ERROR);
			return array(
				'error' => 'Sorry, we could not update your pin. The reset token is missing. Please try again later'
			);
		}

		$changeMyPinAPIUrl = $this->getWebServiceUrl() . '/user/patron/changeMyPin';
		$jsonParameters = array(
			'resetPinToken' => $resetToken,
			'newPin' => $newPin,
		);
		$resetPinResponse = $this->getWebServiceResponse('resetPin', $changeMyPinAPIUrl, $jsonParameters, null, 'POST');
		if (is_object($resetPinResponse) &&  isset($resetPinResponse->messageList)) {
			$errors = array();
			foreach ($resetPinResponse->messageList as $message) {
				$errors[] = $message->message;
			}
			global $logger;
			$logger->log('SirsiDynixROA Driver error updating user\'s Pin :'. implode(';',$errors), Logger::LOG_ERROR);
			return array(
				'error' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.'
			);
		} elseif (!empty($resetPinResponse->sessionToken)){
			if ($user != null) {
				if ($user->username == $resetPinResponse->patronKey) { // Check that the ILS user matches the Aspen Discovery user
					$user->cat_password = $newPin;
					$user->update();
				}
			}
			return array(
				'success' => true,
			);
//			return "Your pin number was updated successfully.";
		}else{
			return array(
				'error' => "Sorry, we could not update your pin number. Please try again later."
			);
		}
	}

	function getEmailResetPinResultsTemplate()
	{
		return 'emailResetPinResults.tpl';
	}

	function processEmailResetPinForm()
	{
		$barcode = $_REQUEST['barcode'];

		$patron = new User;
		$patron->get('cat_username', $barcode);
		if (!empty($patron->id)) {
			$aspenUserID = $patron->id;

			// If possible, check if ILS has an email address for the patron
			if (!empty($patron->cat_password)) {
				list($userValid, $sessionToken, $userID) = $this->loginViaWebService($barcode, $patron->cat_password);
				if ($userValid) {
					// Yay! We were able to login with the pin Aspen has!

					//Now check for an email address
					$lookupMyAccountInfoResponse = $this->getWebServiceResponse('lookupAccountInfo', $this->getWebServiceURL() . '/user/patron/key/' . $userID . '?includeFields=preferredAddress,address1,address2,address3', null, $sessionToken);
					if ($lookupMyAccountInfoResponse) {
						if (isset($lookupMyAccountInfoResponse->fields->preferredAddress)){
							$preferredAddress = $lookupMyAccountInfoResponse->fields->preferredAddress;
							$addressField = 'address'. $preferredAddress;
							//TODO: Does Symphony's email reset pin use any email address; or just the one associated with the preferred Address
							if (!empty($lookupMyAccountInfoResponse->fields->$addressField)){
								$addressData = $lookupMyAccountInfoResponse->fields->$addressField;
								$email = '';
								foreach ($addressData as $field) {
									if ($field->fields->code->key == 'EMAIL') {
										$email = $field->fields->data;
										break;
									}
								}
								if (empty($email)) {
									// return an error message because Symphony doesn't have an email.
									return array(
										'success' => false,
										'error' => 'The circulation system does not have an email associated with this card number. Please contact your library to reset your pin.'
									);
								}
							}
						}
					}
				}
			}
		} else {
			//Can't pre-validate the user, but still do the reset.
			$aspenUserID = '';
		}

		// email the pin to the user
		global $configArray;
		$resetPinAPIUrl = $this->getWebServiceUrl() . '/user/patron/resetMyPin';
		$jsonPOST       = array(
			'login' => $barcode,
			'resetPinUrl' => $configArray['Site']['url'] . '/MyAccount/ResetPin?resetToken=<RESET_PIN_TOKEN>&uid=' . $aspenUserID
		);

		$resetPinResponse = $this->getWebServiceResponse('resetPin', $resetPinAPIUrl, $jsonPOST, null, 'POST');
		if (is_object($resetPinResponse) && !isset($resetPinResponse->messageList)) {
			// Reset Pin Response is empty JSON on success.
			return array(
				'success' => true,
			);
		} else {
			$result = array(
				'success' => false,
				'error' => "Sorry, we could not email your pin to you.  Please visit the library to reset your pin."
			);
			if (isset($resetPinResponse->messageList)) {
				$errors = array();
				foreach ($resetPinResponse->messageList as $message) {
					$errors[] = $message->message;
				}
				global $logger;
				$logger->log('SirsiDynixROA Driver error updating user\'s Pin :' . implode(';', $errors), Logger::LOG_ERROR);
			}
			return $result;
		}
	}

	/**
	 * @param User $patron
	 * @param bool $canUpdateContactInfo
	 * @param bool $fromMasquerade
	 * @return array
	 */
	function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade)
	{
		$result = [
			'success' => false,
			'messages' => []
		];
		if ($canUpdateContactInfo) {
			$sessionToken = $this->getStaffSessionToken();
			if ($sessionToken) {
				$webServiceURL = $this->getWebServiceURL();
				if ($userID = $patron->username) {
					//To update the patron, we need to load the patron from Symphony so we only overwrite changed values.
					$updatePatronInfoParametersClass = $this->getWebServiceResponse('getPatronInfo', $this->getWebServiceURL() . '/user/patron/key/' . $userID .'?includeFields=*,preferredAddress,address1,address2,address3', null, $sessionToken );
					if ($updatePatronInfoParametersClass) {
						//Convert from stdClass to associative array
						$updatePatronInfoParameters = json_decode(json_encode($updatePatronInfoParametersClass), true);
						if ($result['success'] == true) {
							$preferredAddress = $updatePatronInfoParameters['fields']['preferredAddress'];

							// Update Address Field with new data supplied by the user
							if (isset($_REQUEST['email'])) {
								$this->setPatronUpdateFieldBySearch('EMAIL', $_REQUEST['email'], $updatePatronInfoParameters, $preferredAddress);
								$patron->email = $_REQUEST['email'];
							}

							if (isset($_REQUEST['phone'])) {
								$this->setPatronUpdateFieldBySearch('PHONE', $_REQUEST['phone'], $updatePatronInfoParameters, $preferredAddress);
								$patron->phone = $_REQUEST['phone'];
							}

							if (isset($_REQUEST['address1'])) {
								$this->setPatronUpdateFieldBySearch('STREET', $_REQUEST['address1'], $updatePatronInfoParameters, $preferredAddress);
								$patron->_address1 = $_REQUEST['address1'];
							}

							if (isset($_REQUEST['city']) && isset($_REQUEST['state'])) {
								$this->setPatronUpdateFieldBySearch('CITY/STATE', $_REQUEST['city'] . ' ' . $_REQUEST['state'], $updatePatronInfoParameters, $preferredAddress);
								$patron->_city = $_REQUEST['city'];
								$patron->_state = $_REQUEST['state'];
							}

							if (isset($_REQUEST['zip'])) {
								$this->setPatronUpdateFieldBySearch('ZIP', $_REQUEST['zip'], $updatePatronInfoParameters, $preferredAddress);
								$patron->_zip = $_REQUEST['zip'];
							}

							// Update Home Location
							if (!empty($_REQUEST['pickupLocation'])) {
								$homeLibraryLocation = new Location();
								if ($homeLibraryLocation->get('code', $_REQUEST['pickupLocation'])) {
									$homeBranchCode = strtoupper($homeLibraryLocation->code);
									$updatePatronInfoParameters['fields']['library'] = array(
										'key' => $homeBranchCode,
										'resource' => '/policy/library'
									);
								}
							}

							$updateAccountInfoResponse = $this->getWebServiceResponse('updatePatronInfo', $webServiceURL . '/user/patron/key/' . $userID . '?includeFields=*,preferredAddress,address1,address2,address3', $updatePatronInfoParameters, $sessionToken, 'PUT');

							if (isset($updateAccountInfoResponse->messageList)) {
								foreach ($updateAccountInfoResponse->messageList as $message) {
									$result['messages'][] = $message->message;
								}
								global $logger;
								$logger->log('Symphony Driver - Patron Info Update Error - Error from ILS : ' . implode(';', $result['messages']), Logger::LOG_ERROR);
							} else {
								$result['success'] = true;
								$result['messages'][] = 'Your account was updated successfully.';
								$patron->update();
							}
						}else{
							$result['messages'][] = 'Could not load patron account information.';
						}
					}else{
						$result['messages'][] = 'Could not find the account to update.';
					}
				} else {
					global $logger;
					$logger->log('Symphony Driver - Patron Info Update Error: Catalog does not have the circulation system User Id', Logger::LOG_ERROR);
					$result['messages'][] = 'Catalog does not have the circulation system User Id';
				}
			} else {
				$result['messages'][] = 'Sorry, it does not look like you are logged in currently.  Please login and try again';
			}
		} else {
			$result['messages'][] = 'You do not have permission to update profile information.';
		}
		return $result;
	}

    public function showOutstandingFines()
    {
        return true;
    }

	function getForgotPasswordType()
	{
		return 'emailResetLink';
	}

	function getEmailResetPinTemplate()
	{
		return 'sirsiROAEmailResetPinLink.tpl';
	}

	function translateFineMessageType($code){
		switch ($code){

			default:
				return $code;
		}
	}

	public function translateLocation($locationCode){
		$locationCode = strtoupper($locationCode);
		$locationMap = array(

		);
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : "Unknown" ;
	}
	public function translateCollection($collectionCode){
		$collectionCode = strtoupper($collectionCode);
		$collectionMap = array(

		);
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : "Unknown $collectionCode";
	}
	public function translateStatus($statusCode){
		$statusCode = strtolower($statusCode);
		$statusMap = array(

		);
		return isset($statusMap[$statusCode]) ? $statusMap[$statusCode] : 'Unknown (' . $statusCode . ')';
	}
	public function logout(User $user){
		/** @var Memcache $memCache */
		global $memCache;
		global $library;
		$memCacheKey = "sirsiROA_session_token_info_{$library->libraryId}_{$user->getBarcode()}";
		$memCache->delete($memCacheKey);
	}

	private function setPatronUpdateField($key, $value, &$updatePatronInfoParameters, $preferredAddress, &$index){
		static $parameterIndex = array();

		$addressField = 'address' . $preferredAddress;
		$patronAddressPolicyResource = '/policy/patron' . ucfirst($addressField);

		$l = array_key_exists($key, $parameterIndex) ? $parameterIndex[$key] : $index++;
		$updatePatronInfoParameters['fields'][$addressField][$l] = array(
			'resource' => '/user/patron/'. $addressField,
			'fields' => array(
				'code' => array(
					'key' => $key,
					'resource' => $patronAddressPolicyResource
				),
				'data' => $value
			)
		);
		$parameterIndex[$key] = $l;
	}

	private function setPatronUpdateFieldBySearch($key, $value, &$updatePatronInfoParameters, $preferredAddress){
		$addressField = 'address' . $preferredAddress;

		$patronAddress = &$updatePatronInfoParameters['fields'][$addressField];
		$fieldFound = false;
		$maxKey = 0;
		foreach ($patronAddress as &$field){
			if ($field['key'] > $maxKey){
				$maxKey = $field['key'];
			}
			if ($field['fields']['code']['key'] == $key){
				$field['fields']['data'] = $value;
				$fieldFound = true;
				break;
			}
		}
		if (!$fieldFound){
			++$maxKey;
			$patronAddress[] = [
				'resource' => "/user/patron/$addressField",
				'key' => $maxKey,
				'fields' => [
					'code' => [
						'resource' => "/user/patron/$addressField",
						'key' => $key,
					],
					'data' => $value
				]
			];
		}
	}

	function getPasswordPinValidationRules(){
		return [
			'minLength' => 4,
			'maxLength' => 60,
			'onlyDigitsAllowed' => false,
		];
	}

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user)
	{
		$webServiceURL = $this->getWebServiceURL();
		$staffSessionToken = $this->getStaffSessionToken();
		$includeFields = urlEncode("firstName,lastName,privilegeExpiresDate,preferredAddress,address1,address2,address3,library,primaryPhone,profile,blockList{owed}");
		$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $user->username . '?includeFields=' . $includeFields;

		// phoneList is for texting notification preferences
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse('loadContactInformation', $accountInfoLookupURL, null, $staffSessionToken);
		if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
			$this->loadContactInformationFromApiResult($user, $lookupMyAccountInfoResponse);
		}
	}

	/**
	 * @param User $user;
	 * @param $lookupMyAccountInfoResponse
	 */
	protected function loadContactInformationFromApiResult($user, $lookupMyAccountInfoResponse)
	{
		$lastName = $lookupMyAccountInfoResponse->fields->lastName;
		$firstName = $lookupMyAccountInfoResponse->fields->firstName;

		$fullName = $lastName . ', ' . $firstName;

		$user->_fullname = isset($fullName) ? $fullName : '';

		$Address1 = "";
		$City = "";
		$State = "";
		$Zip = "";

		if (isset($lookupMyAccountInfoResponse->fields->preferredAddress)) {
			$preferredAddress = $lookupMyAccountInfoResponse->fields->preferredAddress;
			// Used by My Account Profile to update Contact Info
			$addressField = 'address' . $preferredAddress;
			$address = $lookupMyAccountInfoResponse->fields->$addressField;
			foreach ($address as $addressField) {
				$fields = $addressField->fields;
				switch ($fields->code->key) {
					case 'STREET' :
						$Address1 = $fields->data;
						break;
					case 'CITY' :
						$City = $fields->data;
						break;
					case 'STATE' :
						$State  = $fields->data;
						break;
					case 'CITY/STATE' :
						$cityState = $fields->data;
						if (!empty($cityState)) {
							if (substr_count($cityState, ' ') > 1) {
								//Splitting multiple word cities
								$last_space = strrpos($cityState, ' ');
								$City = substr($cityState, 0, $last_space);
								$State = substr($cityState, $last_space + 1);
							} else {
								list($City, $State) = explode(' ', $cityState);
							}
						}else{
							$City = '';
							$State = '';
						}
						break;
					case 'ZIP' :
						$Zip = $fields->data;
						break;
					case 'PHONE' :
						$phone = $fields->data;
						$user->phone = $phone;
						break;
					case 'EMAIL' :
						$email = $fields->data;
						$user->email = $email;
						break;
				}
			}
		}

		//Get additional information about the patron's home branch for display.
		if (isset($lookupMyAccountInfoResponse->fields->library->key)) {
			$homeBranchCode = strtolower(trim($lookupMyAccountInfoResponse->fields->library->key));
			//Translate home branch to plain text
			$location = new Location();
			$location->code = $homeBranchCode;
			if (!$location->find(true)) {
				unset($location);
			}
		} else {
			global $logger;
			$logger->log('SirsiDynixROA Driver: No Home Library Location or Hold location found in account look-up. User : ' . $user->id, Logger::LOG_ERROR);
			// The code below will attempt to find a location for the library anyway if the homeLocation is already set
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

		if (isset($lookupMyAccountInfoResponse->fields->privilegeExpiresDate)) {
			$user->_expires = $lookupMyAccountInfoResponse->fields->privilegeExpiresDate;
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

		$finesVal = 0;
		if (isset($lookupMyAccountInfoResponse->fields->blockList)) {
			foreach ($lookupMyAccountInfoResponse->fields->blockList as $block) {
				$fineAmount = (float)$block->fields->owed->amount;
				$finesVal += $fineAmount;
			}
		}

		$user->_address1 = $Address1;
		$user->_address2 = $City . ', ' . $State;
		$user->_city = $City;
		$user->_state = $State;
		$user->_zip = $Zip;
		$user->_fines = sprintf('$%01.2f', $finesVal);
		$user->_finesVal = $finesVal;
		$user->patronType = $lookupMyAccountInfoResponse->fields->profile->key;
		$user->_notices = '-';
		$user->_noticePreferenceLabel = 'Email';
		$user->_web_note = '';
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings(): bool
	{
		return true;
	}

	/**
	 * @param User $patron
	 * @return string
	 */
	public function getMessagingSettingsTemplate(User $patron) : ?string
	{
		global $interface;
		$webServiceURL = $this->getWebServiceURL();
		$staffSessionToken = $this->getStaffSessionToken();
		if (!empty($staffSessionToken)) {
			$defaultCountryCode = '';
			$getCountryCodesResponse = $this->getWebServiceResponse('getMessagingSettings', $webServiceURL . '/policy/countryCode/simpleQuery?key=*', null, $staffSessionToken);
			$countryCodes = [];
			foreach ($getCountryCodesResponse as $countryCodeInfo){
				//This gets flipped later
				$countryCodes[$countryCodeInfo->fields->translatedDescription] = $countryCodeInfo->key;
				if ($countryCodeInfo->fields->isDefault) {
					$defaultCountryCode = $countryCodeInfo->key;
				}
			}
			ksort($countryCodes);
			$countryCodes = array_flip($countryCodes);
			$interface->assign('countryCodes', $countryCodes);
			$phoneList = [];
			//Create default phone numbers
			for ($i = 1; $i <= 5; $i++) {
				$phoneList[$i] = [
					'enabled' => false,
					'key' => '',
					'label' => '',
					'countryCode' => $defaultCountryCode,
					'number' => '',
					'billNotices' => false,
					'overdueNotices' => false,
					'holdPickupNotices' => false,
					'manualMessages' => false,
					'generalAnnouncements' => false
				];
			}

			//Get a list of phone numbers for the patron from the APIs.
			$includeFields = urlencode("phoneList{*}");
			$getPhoneListResponse = $this->getWebServiceResponse('getPhoneList', $webServiceURL . "/user/patron/key/{$patron->username}?includeFields=$includeFields", null, $staffSessionToken);

			if ($getPhoneListResponse != null){
				foreach ($getPhoneListResponse->fields->phoneList as $index => $phoneInfo){
					$phoneList[$index + 1 ] = [
						'enabled' => true,
						'key' => $phoneInfo->key,
						'label' => $phoneInfo->fields->label,
						'countryCode' => $phoneInfo->fields->countryCode->key,
						'number' => $phoneInfo->fields->number,
						'billNotices' => $phoneInfo->fields->bills,
						'overdueNotices' => $phoneInfo->fields->overdues,
						'holdPickupNotices' => $phoneInfo->fields->holds,
						'manualMessages' => $phoneInfo->fields->manual,
						'generalAnnouncements' => $phoneInfo->fields->general
					];
				}
			}
			$interface->assign('phoneList', $phoneList);
			$interface->assign('numActivePhoneNumbers', 1);

			//Get a list of valid country codes
		}else{
			$interface->assign('error', 'Could not load messaging settings.');
		}

		$library = $patron->getHomeLibrary();
		if ($library->allowProfileUpdates){
			$interface->assign('canSave', true);
		}else{
			$interface->assign('canSave', false);
		}

		return 'symphonyMessagingSettings.tpl';
	}

	public function processMessagingSettingsForm(User $patron) : array
	{
		$result = array(
			'success' => false,
			'message' => 'Unknown error processing messaging settings.');
		$staffSessionToken = $this->getStaffSessionToken();
		$includeFields = urlencode("phoneList{*}");
		$webServiceURL = $this->getWebServiceURL();
		$getPhoneListResponse = $this->getWebServiceResponse($webServiceURL . "/user/patron/key/{$patron->username}?includeFields=$includeFields", null, $staffSessionToken);

		for ($i = 1; $i <=5; $i++){
			$deletePhoneKey = $_REQUEST['phoneNumberDeleted'][$i] == true;
			if (empty($_REQUEST['phoneNumber'][$i]) && empty($_REQUEST['phoneNumber'][$i])){
				$deletePhoneKey = true;
			}
			$phoneKey = $_REQUEST['phoneNumberKey'][$i];
			if ($deletePhoneKey){
				if (!empty($phoneKey)) {
					foreach ($getPhoneListResponse->fields->phoneList as $index => $phoneInfo) {
						if ($phoneInfo->key == $phoneKey) {
							unset ($getPhoneListResponse->fields->phoneList[$index]);
							break;
						}
					}
				}
			}else{
				$phoneToModify = null;
				$phoneIndexToModify = -1;
				if (!empty($phoneKey)) {
					foreach ($getPhoneListResponse->fields->phoneList as $index => $phoneInfo) {
						if ($phoneInfo->key == $phoneKey) {
							$phoneToModify = $phoneInfo;
							$phoneIndexToModify = $index;
							break;
						}
					}
				}
				if ($phoneToModify == null) {
					$phoneToModify = new stdClass();
					$phoneToModify->resource = '/user/patron/phone';
					$phoneToModify->fields = new stdClass();
				}
				$phoneToModify->fields->patron = new stdClass();
				$phoneToModify->fields->patron->resource = "/user/patron";
				$phoneToModify->fields->patron->key = $patron->username;
				$phoneToModify->fields->label = $_REQUEST['phoneLabel'][$i];
				$phoneToModify->fields->countryCode = new stdClass();
				$phoneToModify->fields->countryCode->resource = '/policy/countryCode';
				$phoneToModify->fields->countryCode->key = $_REQUEST['countryCode'][$i];
				$phoneToModify->fields->number = $_REQUEST['phoneNumber'][$i];
				$phoneToModify->fields->bills = isset($_REQUEST['billNotices'][$i]) && ($_REQUEST['billNotices'][$i] == 'on');
				$phoneToModify->fields->general = isset($_REQUEST['generalAnnouncements'][$i]) && ($_REQUEST['generalAnnouncements'][$i] == 'on');
				$phoneToModify->fields->holds = isset($_REQUEST['holdPickupNotices'][$i]) && ($_REQUEST['holdPickupNotices'][$i] == 'on');
				$phoneToModify->fields->manual = isset($_REQUEST['manualMessages'][$i]) && ($_REQUEST['manualMessages'][$i] == 'on');
				$phoneToModify->fields->overdues = isset($_REQUEST['overdueNotices'][$i]) && ($_REQUEST['overdueNotices'][$i] == 'on');

				if ($phoneIndexToModify == -1){
					$getPhoneListResponse->fields->phoneList[] = $phoneToModify;
				}else{
					$getPhoneListResponse->fields->phoneList[$phoneIndexToModify] = $phoneToModify;
				}
			}
		}
		//Compact the array
		$getPhoneListResponse->fields->phoneList = array_values($getPhoneListResponse->fields->phoneList);

		$updateAccountInfoResponse = $this->getWebServiceResponse('processMessagingSettings', $webServiceURL . '/user/patron/key/' . $patron->username .'?includeFields=' . $includeFields, $getPhoneListResponse, $staffSessionToken, 'PUT');
		if (isset($updateAccountInfoResponse->messageList)) {
			$result['message'] = '';
			foreach ($updateAccountInfoResponse->messageList as $message) {
				if (strlen($result['message']) > 0){
					$result['message'] .= '<br/>';
				}
				$result['message'] = $message->message;
			}
			if (strlen($result['message']) == 0){
				$result['message'] = 'Unknown error processing messaging settings.';
			}
		} else {
			$result['success'] = true;
			$result['message'] = 'Your account was updated successfully.';
		}
		return $result;
	}

	public function hasNativeReadingHistory()
	{
		return true;
	}

	/**
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @return array
	 * @throws Exception
	 */
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut")
	{
		//Get reading history information
		$historyActive = false;
		$readingHistoryTitles = [];
		$staffSessionToken = $this->getStaffSessionToken();
		if (!empty($staffSessionToken)) {
			$webServiceURL = $this->getWebServiceURL();
			$includeFields = urlEncode("keepCircHistory,circHistoryRecordList{checkInDate,checkOutDate,itemType,bib,title,author}");
			$getCircHistoryUrl = $webServiceURL . '/user/patron/barcode/' . $patron->getBarcode() . '?includeFields=' . $includeFields;
			$getCircHistoryResponse = $this->getWebServiceResponse('getReadingHistory', $getCircHistoryUrl, null, $staffSessionToken);
			if ($getCircHistoryResponse && !isset($getCircHistoryResponse->messageList)) {
				$keepCircHistory = $getCircHistoryResponse->fields->keepCircHistory;
				if ($keepCircHistory == 'ALLCHARGES'){
					$historyActive = true;
				}elseif ($keepCircHistory == 'NOHISTORY'){
					$historyActive = false;
				}elseif ($keepCircHistory == 'CIRCRULE') {
					$historyActive = !empty($getCircHistoryResponse->fields->circRecordList);
				}else{
					global $logger;
					$logger->log('Unknown keepCircHistory value: ' . $keepCircHistory, Logger::LOG_DEBUG);
				}
				if ($historyActive){
					$readingHistoryTitles = array();
					$systemVariables = SystemVariables::getSystemVariables();
					global $aspen_db;
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

					foreach ( $getCircHistoryResponse->fields->circHistoryRecordList as $circEntry){
						$historyEntry = array();
						$bibId = 'a' . $circEntry->fields->bib->key;
						$historyEntry['id'] = $bibId;
						$historyEntry['shortId'] = $bibId;
						$historyEntry['recordId'] = $bibId;
						$historyEntry['ratingData'] = null;
						$historyEntry['permanentId'] = null;
						$historyEntry['linkUrl'] = null;
						$historyEntry['coverUrl'] = null;
						$historyEntry['title'] = $circEntry->fields->title;
						$historyEntry['author'] = $circEntry->fields->author;
						$historyEntry['format'] = $circEntry->fields->itemType->key;
						$historyEntry['checkout'] = strtotime($circEntry->fields->checkOutDate);
						$historyEntry['checkin'] = strtotime($circEntry->fields->checkInDate);
						if (!empty($historyEntry['recordId'])) {
							if ($systemVariables->storeRecordDetailsInDatabase){
                                $getRecordDetailsQuery = 'SELECT permanent_id, indexed_format.format FROM grouped_work_records 
								  LEFT JOIN grouped_work ON groupedWorkId = grouped_work.id
								  LEFT JOIN indexed_record_source ON sourceId = indexed_record_source.id
								  LEFT JOIN indexed_format on formatId = indexed_format.id
								  where source = ' . $aspen_db->quote($this->accountProfile->recordSource) . ' and recordIdentifier = ' . $aspen_db->quote($bibId) ;
								$results = $aspen_db->query($getRecordDetailsQuery, PDO::FETCH_ASSOC);
								if ($results){
									$result = $results->fetch();
									if ($result) {
										$groupedWorkDriver = new GroupedWorkDriver($result['permanent_id']);
										if ($groupedWorkDriver->isValid()) {
											$historyEntry['ratingData'] = $groupedWorkDriver->getRatingData();
											$historyEntry['permanentId'] = $groupedWorkDriver->getPermanentId();
											$historyEntry['linkUrl'] = $groupedWorkDriver->getLinkUrl();
											$historyEntry['coverUrl'] = $groupedWorkDriver->getBookcoverUrl('medium', true);
											$historyEntry['format'] = $result['format'];
											$historyEntry['title'] = $groupedWorkDriver->getTitle();
											$historyEntry['author'] = $groupedWorkDriver->getPrimaryAuthor();
										}
									}
								}
							}else {
								require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
								$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $historyEntry['recordId']);
								if ($recordDriver->isValid()) {
									$historyEntry['ratingData'] = $recordDriver->getRatingData();
									$historyEntry['permanentId'] = $recordDriver->getPermanentId();
									$historyEntry['linkUrl'] = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
									$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
									$historyEntry['format'] = $recordDriver->getFormats();
									$historyEntry['title'] = $recordDriver->getTitle();
									$historyEntry['author'] = $recordDriver->getPrimaryAuthor();
								}
							}
							$recordDriver = null;
						}else{
							continue;
						}
						$readingHistoryTitles[] = $historyEntry;
					}
				}
			}
		}
		return array('historyActive' => $historyActive, 'titles' => $readingHistoryTitles, 'numTitles' => count($readingHistoryTitles));
	}

	public function performsReadingHistoryUpdatesOfILS(){
		return true;
	}

	public function doReadingHistoryAction(User $patron, $action, $selectedTitles)
	{
		if ($action == 'optIn' || $action == 'optOut') {
			$sessionToken = $this->getStaffSessionToken();
			if ($sessionToken) {
				$webServiceURL = $this->getWebServiceURL();
				if ($userID = $patron->username) {
					//To update the patron, we need to load the patron from Symphony so we only overwrite changed values.
					$updatePatronInfoParametersClass = $this->getWebServiceResponse('getPatronInformation', $this->getWebServiceURL() . '/user/patron/key/' . $userID .'?includeFields=*,preferredAddress,address1,address2,address3', null, $sessionToken );
					if ($updatePatronInfoParametersClass) {
						//Convert from stdClass to associative array
						$updatePatronInfoParameters = json_decode(json_encode($updatePatronInfoParametersClass), true);
						if ($action == 'optOut') {
							$updatePatronInfoParameters['keepCircHistory'] = 'NOHISTORY';
						} elseif ($action == 'optIn') {
							$updatePatronInfoParameters['keepCircHistory'] = 'ALLCHARGES';
						}

						$updateAccountInfoResponse = $this->getWebServiceResponse('updateReadingHistory', $webServiceURL . '/user/patron/key/' . $userID.'?includeFields=*,preferredAddress,address1,address2,address3', $updatePatronInfoParameters, $sessionToken, 'PUT');

						if (isset($updateAccountInfoResponse->messageList)) {
							foreach ($updateAccountInfoResponse->messageList as $message) {
								$result['messages'][] = $message->message;
							}
							global $logger;
							$logger->log('Symphony Driver - Patron Info Update Error - Error updating reading history : ' . implode(';', $result['messages']), Logger::LOG_ERROR);
						} else {
							$patron->update();
						}
					}
				}
			}
		}
	}

	public function completeFinePayment(User $patron, UserPayment $payment)
	{
		$result = [
			'success' => false,
			'message' => ''
		];

		$currencyCode = 'USD';
		$systemVariables = SystemVariables::getSystemVariables();

		if (!empty($systemVariables->currencyCode)) {
			$currencyCode = $systemVariables->currencyCode;
		}

		global $library;
		$paymentType = empty($library->symphonyPaymentType) ? 'CREDITCARD' : $library->symphonyPaymentType;
		$sessionToken = $this->getStaffSessionToken();
		if ($sessionToken) {
			$finePayments = explode(',', $payment->finesPaid);
			$allPaymentsSucceed = true;
			foreach ($finePayments as $finePayment) {
				list($fineId, $paymentAmount) = explode('|', $finePayment);
				$creditRequestBody = [
					'blockKey' => str_replace('_', ':', $fineId),
					'amount' => [
						'amount' => $paymentAmount,
						'currencyCode' => $currencyCode
					],
					'paymentType' => [
						'resource' => '/policy/paymentType',
						'key' => $paymentType
					],
					//We could include the actual transaction id from the processor, but it's limited to 30 chars so we can just use Aspen ID.
					'vendorTransactionID' => $payment->id,
					'creditReason' => [
						'resource' => '/policy/creditReason',
						'key' => 'PAYMENT'
					]
				];
				$postCreditResponse = $this->getWebServiceResponse('addPayment', $this->getWebServiceURL() . '/circulation/block/addPayment', $creditRequestBody, $sessionToken, 'POST');
				if (isset($postCreditResponse->messageList)){
					$messages = [];
					foreach ($postCreditResponse->messageList as $message){
						$messages[] = $message->message;
					}
					$result['message'] = implode("<br/>", $messages);
					$allPaymentsSucceed = false;
				}
			}
			$result['success'] = $allPaymentsSucceed;
		}else{
			$result['message'] = 'Could not connect to Symphony APIs';
		}

		global $logger;
		$logger->log("Marked fines as paid within Symphony for user {$patron->id}, {$result['message']}", Logger::LOG_ERROR);

		return $result;
	}

	public function getSelfRegistrationFields() {
		global $library;

		$pickupLocations = array();
		$location = new Location();
		if ($library->selfRegistrationLocationRestrictions == 1) {
			//Library Locations
			$location->libraryId = $library->libraryId;
		} elseif ($library->selfRegistrationLocationRestrictions == 2) {
			//Valid pickup locations
			$location->whereAdd('validHoldPickupBranch <> 2');
		} elseif ($library->selfRegistrationLocationRestrictions == 3) {
			//Valid pickup locations
			$location->libraryId = $library->libraryId;
			$location->whereAdd('validHoldPickupBranch <> 2');
		}
		if ($location->find()) {
			while ($location->fetch()) {
				$pickupLocations[$location->code] = $location->displayName;
			}
			asort($pickupLocations);
			array_unshift($pickupLocations, translate(['text'=>'Please select a location', 'isPublicFacing'=>true]));
		}

		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text', 'label'=>'First Name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName', 'type'=>'text', 'label'=>'Middle Name', 'maxLength' => 40, 'required' => false);
		$fields[] = array('property'=>'lastName', 'type'=>'text', 'label'=>'Last Name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$birthDateMin = date('Y-m-d', strtotime('-113 years'));
			$birthDateMax = date('Y-m-d', strtotime('-13 years'));
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'min'=>$birthDateMin, 'max'=>$birthDateMax, 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address', 'type'=>'text', 'label'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city', 'type'=>'text', 'label'=>'City', 'maxLength' => 48, 'required' => true);
		if (empty($library->validSelfRegistrationStates)){
			$fields[] = array('property'=>'state', 'type'=>'text', 'label'=>'State', 'maxLength' => 2, 'required' => true);
		}else{
			$validStates = explode('|', $library->validSelfRegistrationStates);
			$validStates = array_combine($validStates, $validStates);
			$fields[] = array('property' => 'state', 'type' => 'enum', 'values' => $validStates, 'label' => 'State', 'description' => 'State', 'maxLength' => 32, 'required' => true);
		}
		$fields[] = array('property'=>'zip', 'type'=>'text', 'label'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'phone', 'type'=>'text',  'label'=>'Primary Phone', 'maxLength'=>15, 'required'=>false);
		$fields[] = array('property'=>'email',  'type'=>'email', 'label'=>'Email', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'email2',  'type'=>'email', 'label'=>'Confirm Email', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'pickupLocation',  'type'=>'enum', 'values' => $pickupLocations, 'label'=>'Library', 'maxLength' => 128, 'required' => true);
		return $fields;
	}

	/**
	 * Import Lists from the ILS
	 *
	 * @param User $patron
	 * @return array - an array of results including the names of the lists that were imported as well as number of titles.
	 */
//	function importListsFromIls($patron)
//	{
//		$results =[
//			'success' => false,
//			'errors' => []
//		];
//		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
//		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
//		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
//
//		$curlWrapper = new CurlWrapper();
//		//Login to Enterprise
//		$enterpriseUrl = $this->accountProfile->vendorOpacUrl;
//		if (substr($enterpriseUrl, -1, 1) != '/'){
//			$enterpriseUrl .= '/';
//		}
//
//		//First navigate to the main page to get cookies and other info needed for login
//		$result = $curlWrapper->curlGetPage($enterpriseUrl . 'search/mylists?ic=true');
//
//		//Extract the login form
//		$formData = [];
/*		if (preg_match('%<form class="loginPageForm".*?>(.*?)</form>%s', $result, $matches)){*/
//			$formElement = $matches[1];
//		}else{
//			$results['errors'][] = "Could not connect to the old catalog to import lists";
//			return $results;
//		}
//
//		$matches = [];
//		if (preg_match('%<input value="(.*?)" name="t:formdata" type="hidden"></input>%s', $formElement, $matches)){
//			$formData['t:formdata'] = $matches[1];
//		}else{
//			$results['errors'][] = "Could not connect to the old catalog to import lists";
//			return $results;
//		}
//
//		$formData['t:submit'] = '["submit_0","submit_0"]';
//		$formData['textfield'] = '';
//		$formData['textfield_0'] = '';
//		$formData['hidden'] = 'SYMWS';
//		$formData['j_username'] = $patron->getBarcode();
//		$formData['j_password'] = $patron->getPasswordOrPin();
//		$formData['t:zoneid'] = 'loginFormZone';
//
//		$loginUrl = $enterpriseUrl . "index.template.patronloginform.loginpageform/false?ic=true";
//
//		$headers  = array(
//			'Accept: text/javascript, text/html, application/xml, text/xml, */*',
//			'Accept-Encoding: gzip, deflate, br',
//			'Accept-Language: en-US,en;q=0.5',
//			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
//			'User-Agent: Aspen Discovery'
//		);
//		$curlWrapper->addCustomHeaders($headers, true);
//		$result = $curlWrapper->curlPostPage($loginUrl, $formData);
//
//		//Get the mylists page now that we are logged in
//		$result = $curlWrapper->curlGetPage($enterpriseUrl . 'search/mylists?ic=true');
//
//		return $results;
//	}
}
