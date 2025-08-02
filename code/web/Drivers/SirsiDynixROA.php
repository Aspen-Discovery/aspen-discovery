<?php

require_once ROOT_DIR . '/Drivers/HorizonAPI.php';
require_once ROOT_DIR . '/sys/Account/User.php';

class SirsiDynixROA extends HorizonAPI {
	//Caching of sessionIds by patron for performance (also stored within memcache)
	private static $sessionIdsForUsers = [];
	private static $logAllAPICalls = false;
	private $lastWebServiceResponseCode;

	// $customRequest is for curl, can be 'PUT', 'DELETE', 'POST'
	public function getWebServiceResponse($requestType, $url, $params = null, $sessionToken = null, $customRequest = null, $additionalHeaders = null, $dataToSanitize = [], $workingLibraryId = null) {
		global $logger;
		global $library;
		global $locationSingleton;
		global $timer;
		$timer->logTime("Starting to call symphony $requestType API");
		$physicalLocation = $locationSingleton->getPhysicalLocation();

		if (empty($workingLibraryId)) {
			$workingLibraryId = $library->ilsCode;
			if (!empty($physicalLocation)) {
				$workingLibraryId = $physicalLocation->code;
			}
			//If we still don't have a working library id, get the first location for the library
			if (empty($workingLibraryId)) {
				$libraryLocations = $library->getLocations();
				$firstLocation = reset($libraryLocations);
				$workingLibraryId = $firstLocation->code;
			}
		}
		$logger->log('WebServiceURL :' . $url, Logger::LOG_NOTICE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$clientId = $this->accountProfile->oAuthClientId;
		$headers = [
			'Accept: application/json',
			'Content-Type: application/json',
			'SD-Originating-App-Id: Aspen Discovery',
			'SD-Working-LibraryID: ' . $workingLibraryId,
			'x-sirs-clientID: ' . $clientId,
		];
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

		$this->lastWebServiceResponseCode = curl_getinfo($ch)['http_code'];

		$timer->logTime("Finished calling symphony $requestType API");
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

	public function supportsLoginWithUsername() : bool {
		return true;
	}

	function findNewUser($patronBarcode, $patronUsername): User|bool {
		// Creates a new user like patronLogin and looks up user by barcode or username.
		// Note: The user pin is not supplied in the Account Info Lookup call.
		$sessionToken = $this->getStaffSessionToken();
		if (!empty($sessionToken)) {
			$webServiceURL = $this->getWebServiceURL();
			$includeFields = urlEncode("firstName,lastName,privilegeExpiresDate,preferredAddress,preferredName,address1,address2,address3,library,primaryPhone,profile,pin,blockList{owed}");
			$searchString = $patronBarcode != null ? 'ID:' . $patronBarcode : 'ALT_ID:' . $patronUsername;
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse('findNewUser', $webServiceURL . '/user/patron/search?q=' . $searchString . '&rw=1&ct=1&includeFields=' . $includeFields, null, $sessionToken);
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
				$user->unique_ils_id = $userID;
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

				if (!empty($lookupMyAccountInfoResponse->fields->preferredName)) {
					$user->displayName = $lookupMyAccountInfoResponse->fields->preferredName;
				} else {
					if ($forceDisplayNameUpdate) {
						$user->displayName = '';
					}
				}

				$user->_fullname = isset($fullName) ? $fullName : '';
				$user->cat_username = $patronBarcode;
				$user->ils_barcode = $patronBarcode;
				if (!empty($lookupMyAccountInfoResponse->fields->pin)) {
					$user->cat_password = $lookupMyAccountInfoResponse->fields->pin;
					$user->ils_password = $lookupMyAccountInfoResponse->fields->pin;
				}

				global $library;

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
						$address = [];
					}
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
								$State = $fields->data;
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
										[
											$City,
											$State,
										] = explode(' ', $cityState);
									}
								} else {
									$City = '';
									$State = '';
								}
								break;
							case 'ZIP' :
								$Zip = $fields->data;
								break;
							// If the library does not use the PHONE field, set $user->phone to DAYPHONE or HOMEPHONE
							/** @noinspection SpellCheckingInspection */
							case 'DAYPHONE' :
								$dayPhone = $fields->data;
								$user->phone = $dayPhone;
							/** @noinspection SpellCheckingInspection */
							case 'HOMEPHONE' :
								$homePhone = $fields->data;
								$user->phone = $homePhone;
							case 'PHONE' :
								$phone = $fields->data;
								$user->phone = $phone;
								break;
							case 'CELLPHONE' :
								$cellphone = $fields->data;
								$user->_mobileNumber = $cellphone;
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
					[
						$yearExp,
						$monthExp,
						$dayExp,
					] = explode("-", $user->_expires);
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
					if (!$user->insert()) {
						return false;
					}
				}

				return $user;

			}
		}
		return false;
	}

	public function findNewUserByEmail($patronEmail): mixed {
		return false;
	}

	public function patronLogin($username, $password, $validatedViaSSO) {
		global $timer;

		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Authenticate the user via WebService
		//First call loginUser
		$timer->logTime("Logging in through Symphony APIs");
		/** @noinspection PhpUnusedLocalVariableInspection */
		[
			$userValid,
			$sessionToken,
			$sirsiRoaUserID,
		] = $this->loginViaWebService($username, $password);
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

			$includeFields = urlEncode("firstName,lastName,alternateID,barcode,privilegeExpiresDate,preferredAddress,preferredName,address1,address2,address3,library,primaryPhone,profile,blockList{owed},category01,category02,category03,category04,category05,category06,category07,category08,category09,category10,category11,category12}");
			$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $sirsiRoaUserID . '?includeFields=' . $includeFields;

			// phoneList is for texting notification preferences
			$lookupMyAccountInfoResponse = $this->getWebServiceResponse('accountLookupForLogin', $accountInfoLookupURL, null, $staffSessionToken);
			if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
				$userExistsInDB = false;
				$user = new User();
				$user->source = $this->accountProfile->name;
				$user->username = $sirsiRoaUserID;
				$user->unique_ils_id = $sirsiRoaUserID;
				if ($user->find(true)) {
					$userExistsInDB = true;
				}
				$user->cat_username = $lookupMyAccountInfoResponse->fields->barcode;
				$user->ils_barcode = $lookupMyAccountInfoResponse->fields->barcode;
				$user->ils_username = $lookupMyAccountInfoResponse->fields->alternateID;
				$user->cat_password = $password;
				$user->ils_password = $password;

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
				if (!empty($lookupMyAccountInfoResponse->fields->preferredName)) {
					$user->displayName = $lookupMyAccountInfoResponse->fields->preferredName;
				} else {
					if ($forceDisplayNameUpdate) {
						$user->displayName = '';
					}
				}

				$this->loadContactInformationFromApiResult($user, $lookupMyAccountInfoResponse);

				if ($userExistsInDB) {
					$user->update();
				} else {
					$user->created = date('Y-m-d');
					if (!$user->insert()) {
						return null;
					}
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

	public function getAccountSummary(User $patron): AccountSummary {
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();

		$webServiceURL = $this->getWebServiceURL();
		$includeFields = urlencode("privilegeExpiresDate,circRecordList{overdue},blockList{owed},holdRecordList{status}");
		$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields;

		$sessionToken = $this->getSessionToken($patron);
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse('accountSummary', $accountInfoLookupURL, null, $sessionToken);

		if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
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

			$finesVal = 0;
			if (isset($lookupMyAccountInfoResponse->fields->blockList)) {
				foreach ($lookupMyAccountInfoResponse->fields->blockList as $block) {
					$fineAmount = (float)$block->fields->owed->amount;
					$finesVal += $fineAmount;
				}
			}
			$summary->totalFines = $finesVal;

			//Don't call getExpirationInformation since we already have the data available.
			if ($lookupMyAccountInfoResponse->fields->privilegeExpiresDate == null) {
				$summary->expirationDate = 0;
			} else {
				$summary->expirationDate = strtotime($lookupMyAccountInfoResponse->fields->privilegeExpiresDate);
			}
		}

		return $summary;
	}

	public function getExpirationInformation(User $patron) : ExpirationInformation {
		$expirationInformation = new ExpirationInformation();

		$webServiceURL = $this->getWebServiceURL();
		$includeFields = urlencode("privilegeExpiresDate");
		$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields;

		$sessionToken = $this->getSessionToken($patron);
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse('accountSummary', $accountInfoLookupURL, null, $sessionToken);

		if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
			if ($lookupMyAccountInfoResponse->fields->privilegeExpiresDate == null) {
				$expirationInformation->expirationDate = 0;
			} else {
				$expirationInformation->expirationDate = strtotime($lookupMyAccountInfoResponse->fields->privilegeExpiresDate);
			}
		}

		return $expirationInformation;
	}

	private function getStaffSessionToken() {
		$staffSessionToken = false;
		if (!empty($this->accountProfile->staffUsername) && !empty($this->accountProfile->staffPassword)) {
			[
				,
				$staffSessionToken,
			] = $this->staffLoginViaWebService($this->accountProfile->staffUsername, $this->accountProfile->staffPassword);
		}
		return $staffSessionToken;
	}

	function selfRegister(): array {
		$selfRegResult = [
			'success' => false,
			'message' => 'Unknown Error while registering your account'
		];

		$sessionToken = $this->getStaffSessionToken();
		if (!empty($sessionToken)) {
			global $library;
			$webServiceURL = $this->getWebServiceURL();

			// $patronDescribeResponse   = $this->getWebServiceResponse('patronDescribe', $webServiceURL . '/user/patron/describe');
			// $address1DescribeResponse = $this->getWebServiceResponse('address1Describe', $webServiceURL . '/user/patron/address1/describe');
			// $addressDescribeResponse  = $this->getWebServiceResponse('addressDescribe', $webServiceURL . '/user/patron/address/describe');
			// $userProfileDescribeResponse  = $this->getWebServiceResponse('userProfileDescribe', $webServiceURL . '/policy/userProfile/describe');

			$selfRegistrationForm = null;
			$formFields = null;
			if ($library->selfRegistrationFormId > 0){
				$selfRegistrationForm = new SelfRegistrationForm();
				$selfRegistrationForm->id = $library->selfRegistrationFormId;
				if ($selfRegistrationForm->find(true)) {
					$formFields = $selfRegistrationForm->getFields();
				}else {
					$selfRegistrationForm = null;
				}
			}

			$firstName = isset($_REQUEST['firstName']) ? trim($_REQUEST['firstName']) : '';
			$lastName = isset($_REQUEST['lastName']) ? trim($_REQUEST['lastName']) : '';
			if (isset($_REQUEST['dob'])){
				$birthDate = isset($_REQUEST['dob']) ? trim($_REQUEST['dob']) : '';
			} elseif (isset($_REQUEST['birthdate'])){
				$birthDate = isset($_REQUEST['birthdate']) ? trim($_REQUEST['birthdate']) : '';
			}
			//birthDate field is only used in old forms, new forms are either dob or birthdate
			if (empty($birthDate)) {
				$birthDate = isset($_REQUEST['birthDate']) ? trim($_REQUEST['birthDate']) : '';
			}
			if($selfRegistrationForm->noDuplicateCheck == 0){
				if ($this->isDuplicatePatron($firstName, $lastName, $birthDate)) {
					$selfRegResult['message'] = 'We have found an existing account for you. Please contact the library to access your account.';
					return $selfRegResult;
				}
			}

			$createPatronInfoParameters = [
				'fields' => [],
				'resource' => '/user/patron',
			];
			$preferredAddress = 1;

			// Build Address Field with existing data
			$index = 0;

			$createPatronInfoParameters['fields']['profile'] = [
				'resource' => '/policy/userProfile',
				'key' => $selfRegistrationForm->selfRegistrationUserProfile,
			];
			//$formFields = (new SelfRegistrationFormValues)->getFormFieldsInOrder($library->selfRegistrationFormId);

			if ($formFields != null) {
				foreach ($formFields as $fieldObj){
					$field = $fieldObj->ilsName;
					//General Info
					if ($field == 'firstName' && (!empty($_REQUEST['firstName'])) ) {
						$createPatronInfoParameters['fields']['firstName'] = $this->getPatronFieldValue(trim($_REQUEST['firstName']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif ($field == 'middleName' && (!empty($_REQUEST['middleName']))) {
						$createPatronInfoParameters['fields']['middleName'] = $this->getPatronFieldValue(trim($_REQUEST['middleName']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif ($field == 'lastName' && (!empty($_REQUEST['lastName']))) {
						$createPatronInfoParameters['fields']['lastName'] = $this->getPatronFieldValue(trim($_REQUEST['lastName']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif ($field == 'preferredName' && (!empty($_REQUEST['preferredName']))) {
						$createPatronInfoParameters['fields']['preferredName'] = $this->getPatronFieldValue(trim($_REQUEST['preferredName']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif ($field == 'suffix' && (!empty($_REQUEST['suffix']))) {
						$createPatronInfoParameters['fields']['suffix'] = $this->getPatronFieldValue(trim($_REQUEST['suffix']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif ($field == 'title' && (!empty($_REQUEST['title']))) {
						$createPatronInfoParameters['fields']['title'] = $this->getPatronFieldValue(trim($_REQUEST['title']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif (($field == 'birthDate' || $field == 'dob') && (!empty($_REQUEST['dob']))) {
						$createPatronInfoParameters['fields']['birthDate'] = $this->getPatronFieldValue(trim($_REQUEST['dob']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}
					elseif ($field == 'birthdate' && (!empty($_REQUEST['birthdate']))) {
						$createPatronInfoParameters['fields']['BIRTHDATE'] = $this->getPatronFieldValue(trim($_REQUEST['birthdate']), $library->useAllCapsWhenSubmittingSelfRegistration);
					}

					// Update Address Field with new data supplied by the user

					elseif ($field == 'care_of' && (!empty($_REQUEST['care_of']))) {
						$this->setPatronUpdateField('CARE/OF', $this->getPatronFieldValue($_REQUEST['care_of'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'careof' && (!empty($_REQUEST['careof']))) {
						$this->setPatronUpdateField('CARE_OF', $this->getPatronFieldValue($_REQUEST['careof'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'guardian' && (!empty($_REQUEST['guardian']))) {
						$this->setPatronUpdateField('GUARDIAN', $this->getPatronFieldValue($_REQUEST['guardian'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'parentname' && (!empty($_REQUEST['parentname']))) {
						$this->setPatronUpdateField('PARENTNAME', $this->getPatronFieldValue($_REQUEST['parentname'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'email' && (!empty($_REQUEST['email']))) {
						$this->setPatronUpdateField('EMAIL', $this->getPatronFieldValue($_REQUEST['email'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'phone' && (!empty($_REQUEST['phone']))) {
						$this->setPatronUpdateField('PHONE', $_REQUEST['phone'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'homephone' && (!empty($_REQUEST['homephone']))) {
						$this->setPatronUpdateField('HOMEPHONE', $_REQUEST['homephone'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'cellPhone' && (!empty($_REQUEST['cellPhone']))) {
						$this->setPatronUpdateField('CELLPHONE', $_REQUEST['cellPhone'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'dayphone' && (!empty($_REQUEST['dayphone']))) {
						$this->setPatronUpdateField('DAYPHONE', $_REQUEST['dayphone'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'workphone' && (!empty($_REQUEST['workphone']))) {
						$this->setPatronUpdateField('WORKPHONE', $_REQUEST['workphone'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'ext' && (!empty($_REQUEST['ext']))) {
						$this->setPatronUpdateField('EXT', $_REQUEST['ext'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'fax' && (!empty($_REQUEST['fax']))) {
						$this->setPatronUpdateField('FAX', $_REQUEST['fax'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'employer' && (!empty($_REQUEST['employer']))) {
						$this->setPatronUpdateField('EMPLOYER', $_REQUEST['employer'], $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'po_box' && (!empty($_REQUEST['po_box']))) {
						$this->setPatronUpdateField('PO_BOX', $this->getPatronFieldValue($_REQUEST['po_box'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'street' && (!empty($_REQUEST['street']))) {
						$this->setPatronUpdateField('STREET', $this->getPatronFieldValue($_REQUEST['street'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'mailingaddr' && (!empty($_REQUEST['mailingaddr']))) {
						$this->setPatronUpdateField('MAILNGADDR', $this->getPatronFieldValue($_REQUEST['mailingaddr'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'apt_suite' && (!empty($_REQUEST['apt_suite']))) {
						$this->setPatronUpdateField('APT/SUITE', $this->getPatronFieldValue($_REQUEST['apt_suite'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'city' && (!empty($_REQUEST['city']) && (!empty($_REQUEST['state'])))) {
						if ($selfRegistrationForm->cityStateField == 1) {
							$this->setPatronUpdateField('CITY', $this->getPatronFieldValue($_REQUEST['city'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
							$this->setPatronUpdateField('STATE', $this->getPatronFieldValue($_REQUEST['state'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
						} elseif ($selfRegistrationForm->cityStateField == 2) {
							$this->setPatronUpdateField('CITY/STATE', $this->getPatronFieldValue($_REQUEST['city'] . ', ' . $_REQUEST['state'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
						} else {
							$this->setPatronUpdateField('CITY/STATE', $this->getPatronFieldValue($_REQUEST['city'] . ' ' . $_REQUEST['state'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
						}
					}
					elseif ($field == 'zip' && (!empty($_REQUEST['zip']))) {
						$this->setPatronUpdateField('ZIP', $this->getPatronFieldValue($_REQUEST['zip'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}

					//unsure about these
					elseif ($field == 'location' && (!empty($_REQUEST['location']))) {
						$this->setPatronUpdateField('LOCATION', $this->getPatronFieldValue($_REQUEST['location'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'type' && (!empty($_REQUEST['type']))) {
						$this->setPatronUpdateField('TYPE', $this->getPatronFieldValue($_REQUEST['type'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'not_type' && (!empty($_REQUEST['not_type']))) {
						$this->setPatronUpdateField('NOT TYPE', $this->getPatronFieldValue($_REQUEST['not_type'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'usefor' && (!empty($_REQUEST['usefor']))) {
						$this->setPatronUpdateField('USEFOR', $this->getPatronFieldValue($_REQUEST['usefor'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'customInformation' && (!empty($_REQUEST['customInformation']))) {
						$this->setPatronUpdateField('customInformation', $this->getPatronFieldValue($_REQUEST['customInformation'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'primaryAddress' && (!empty($_REQUEST['primaryAddress']))) {
						$this->setPatronUpdateField('primaryAddress', $this->getPatronFieldValue($_REQUEST['primaryAddress'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
					elseif ($field == 'primaryPhone' && (!empty($_REQUEST['primaryPhone']))) {
						$this->setPatronUpdateField('primaryPhone', $this->getPatronFieldValue($_REQUEST['primaryPhone'], $library->useAllCapsWhenSubmittingSelfRegistration), $createPatronInfoParameters, $preferredAddress, $index);
					}
				}
			}

			// Update Home Location
			if (!empty($_REQUEST['pickupLocation'])) {
				$homeLibraryLocation = new Location();
				if ($homeLibraryLocation->get('code', $_REQUEST['pickupLocation'])) {
					$homeBranchCode = strtoupper($homeLibraryLocation->code);
					$createPatronInfoParameters['fields']['library'] = [
						'key' => $homeBranchCode,
						'resource' => '/policy/library',
					];
				}
			} else {
				$selfRegResult['message'] = 'Your preferred library must be provided during registration.';
				return $selfRegResult;
			}

			if (isset($_REQUEST['email']) && (isset($_REQUEST['email2']))) {
				if ($_REQUEST['email'] != ($_REQUEST['email2'])){
					$selfRegResult['message'] = 'Email validation must match original email entered.';
					return $selfRegResult;
				}
			}

			//If the user is opted in to SMS messages, set up their notifications automatically.
			if (!empty($_REQUEST['smsNotices']) && !empty($_REQUEST['cellPhone'])) {
				$defaultCountryCode = '';
				$getCountryCodesResponse = $this->getWebServiceResponse('getMessagingSettings', $webServiceURL . '/policy/countryCode/simpleQuery?key=*', null, $sessionToken);
				foreach ($getCountryCodesResponse as $countryCodeInfo) {
					//This gets flipped later
					if ($countryCodeInfo->fields->isDefault) {
						$defaultCountryCode = $countryCodeInfo->key;
						break;
					}
				}
				$cellPhoneInfo = [
					'resource' => '/user/patron/phone',
					'fields' => [
						'label' => 'Cell phone',
						'countryCode' => [
							'resource' => '/policy/countryCode',
							'key' => $defaultCountryCode,
						],
						'number' => $_REQUEST['cellPhone'],
						'bills' => false,
						'general' => true,
						'holds' => true,
						'manual' => true,
						'overdues' => true,
					],
				];
				$createPatronInfoParameters['fields']['phoneList'][] = $cellPhoneInfo;
			}

			$foundValidBarcode = false;
			if ($selfRegistrationForm != null){
				$barcodePrefix = $selfRegistrationForm->selfRegistrationBarcodePrefix;
				$barcodeSuffixLength = $selfRegistrationForm->selfRegBarcodeSuffixLength;

				if (!empty($barcodeSuffixLength)){
					$barcode = null;
					while ($barcode == null || $this->isBarcodeInUse($barcode)) {
						$barcode = $barcodePrefix;
						for ($i = 0; $i < $barcodeSuffixLength; $i++) {
							$barcode .= rand(0, 9);
						}
					}
					$foundValidBarcode = true;
				}
			}
			if (!$foundValidBarcode) {
				$barcodeVariable = new Variable();
				$barcodeVariable->name = 'self_registration_card_number';
				if ($barcodeVariable->find(true)) {
					$barcode = $barcodeVariable->value;
					//If the barcode is in use, increment it by one and try again.
					while ($this->isBarcodeInUse($barcode)) {
						$barcode++;
					}
					//Now that we have a valid barcode increment the variable by one so that next time we get the next number
					$barcodeVariable->value = $barcode + 1;
					if (!$barcodeVariable->update()) {
						global $logger;
						$logger->log('Sirsi Self Registration barcode counter did not increment when a user already exists!', Logger::LOG_ERROR);
					}
					$foundValidBarcode = true;
				}
			}

			if ($foundValidBarcode) {
				$createPatronInfoParameters['fields']['barcode'] = (string)$barcode;

				//global $configArray;
				//$overrideCode = $configArray['Catalog']['selfRegOverrideCode'];
				//$overrideHeaders = array('SD-Prompt-Return:USER_PRIVILEGE_OVRCD/' . $overrideCode);

				$createNewPatronResponse = $this->getWebServiceResponse('selfRegister', $webServiceURL . '/user/patron/', $createPatronInfoParameters, $sessionToken, 'POST');

				if (isset($createNewPatronResponse->messageList)) {
					foreach ($createNewPatronResponse->messageList as $message) {
						$updateErrors[] = $message->message;
					}
					global $logger;
					$logger->log('Symphony Driver - Patron Info Update Error - Error from ILS : ' . implode(';', $updateErrors), Logger::LOG_ERROR);
					$selfRegResult['message'] = 'There was an error registering your account, please try again later or contact the library to register.';
				} else {

					$selfRegResult = [
						'success' => true,
						'barcode' => $barcode,
						'requirePinReset' => true,
					];
					// Symphony needs time to process the full information of the user when registering,
					// so add a delay to ensure the results array is populated when finding the user.
					usleep(500000);
					$newUser = $this->findNewUser($barcode, null);
					if ($newUser != null) {
						$selfRegResult['newUser'] = $newUser;
						$selfRegResult['sendWelcomeMessage'] = true;
					}
				}
			} else {
				// Error: unable to set barcode number.
				global $logger;
				$logger->log('Could not generate barcode to self register.', Logger::LOG_ERROR);
				$selfRegResult['message'] = 'Could not generate barcode to self register. Please try again later or contact the library to register.';
			}
		} else {
			// Error: unable to login in staff user
			global $logger;
			$logger->log('Unable to log in with Sirsi Self Registration staff user', Logger::LOG_ERROR);
		}
		return $selfRegResult;
	}

	protected function isBarcodeInUse($barcode){
		$webServiceURL = $this->getWebServiceURL();
		$lookupBarcodeUrl = $webServiceURL . "/user/patron/barcode/$barcode";
		$sessionToken = $this->getStaffSessionToken();
		/** @noinspection PhpUnusedLocalVariableInspection */
		$lookupBarcodeResponse = $this->getWebServiceResponse('lookupBarcode', $lookupBarcodeUrl, null, $sessionToken);
		if ($this->lastWebServiceResponseCode == 404) {
			return false;
		} else {
			return true;
		}
	}

	protected function isDuplicatePatron($firstName, $lastName, $birthDate) : bool {
		//Get birthday in the form YYYY-MM-dd
		$webServiceURL = $this->getWebServiceURL();
		$lastNameSearch = trim($lastName);
		$firstNameSearch = trim($firstName);
		$numericalBirthDate = str_replace("-", "", $birthDate);
		$numericalBirthDate = str_replace("/", "", $numericalBirthDate);
		if (empty($birthDate)) {
			$patronSearchUrl = $webServiceURL . "/user/patron/search?includeFields=firstName,lastName,birthDate&rw=1&ct=200&q=name:$lastNameSearch";
		}else {
			$patronSearchUrl = $webServiceURL . "/user/patron/search?includeFields=firstName,lastName,birthDate&rw=1&ct=200&q=name:$lastNameSearch,birthDate:$numericalBirthDate";
		}

		$sessionToken = $this->getStaffSessionToken();
		/** @noinspection PhpUnusedLocalVariableInspection */
		$lookupBarcodeResponse = $this->getWebServiceResponse('patronSearch', $patronSearchUrl, null, $sessionToken);
		if (!empty($lookupBarcodeResponse) && is_object($lookupBarcodeResponse)) {
			if ($lookupBarcodeResponse->totalResults != 0) {
				foreach ( $lookupBarcodeResponse->result as $patron ) {
					if ( strcasecmp($patron->fields->firstName, $firstNameSearch ) === 0 && strcasecmp($patron->fields->lastName, $lastNameSearch ) === 0 ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	protected function loginViaWebService($username, $password) {
		global $memCache;
		global $library;
		$memCacheKey = "sirsiROA_session_token_info_{$library->libraryId}_{$username}_" . session_id();
		$session = $memCache->get($memCacheKey);
		if ($session != false) {
			[
				,
				$sessionToken,
				$sirsiRoaUserID,
			] = $session;
			SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserID] = $sessionToken;
		} else {
			$session = [
				false,
				false,
				false,
			];
			$webServiceURL = $this->getWebServiceURL();
			//$loginDescribeResponse = $this->getWebServiceResponse($webServiceURL . '/user/patron/login/describe');
			$loginUserUrl = $webServiceURL . '/user/patron/login';
			$params = [
				'login' => $username,
				'password' => $password,
			];
			$loginUserResponse = $this->getWebServiceResponse('patronLogin', $loginUserUrl, $params, null, null, null, [
				'password',
				$password,
			]);
			if ($loginUserResponse && isset($loginUserResponse->sessionToken)) {
				//We got a valid user (A bad call will have isset($loginUserResponse->messageList) )
				$sirsiRoaUserID = $loginUserResponse->patronKey;
				$sessionToken = $loginUserResponse->sessionToken;
				SirsiDynixROA::$sessionIdsForUsers[(string)$sirsiRoaUserID] = $sessionToken;
				$session = [
					true,
					$sessionToken,
					$sirsiRoaUserID,
				];
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
				$session = [
					false,
					'',
					'',
				];
			}
		}
		return $session;
	}

	protected function staffLoginViaWebService($username, $password) {
		global $memCache;
		global $library;
		$memCacheKey = "sirsiROA_session_token_info_{$library->libraryId}_{$username}_" . session_id();
		$session = $memCache->get($memCacheKey);
		if ($session) {
			[
				,
				$sessionToken,
				$sirsiRoaUserID,
			] = $session;
			SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserID] = $sessionToken;
		} else {
			$session = [
				false,
				false,
				false,
			];
			$webServiceURL = $this->getWebServiceURL();
			$loginUserUrl = $webServiceURL . '/user/staff/login';
			$params = [
				'login' => $username,
				'password' => $password,
			];
			$loginUserResponse = $this->getWebServiceResponse('staffLogin', $loginUserUrl, $params, null, null, null, [
				'password',
				$password,
			]);
			if ($loginUserResponse && isset($loginUserResponse->sessionToken)) {
				//We got at valid user (A bad call will have isset($loginUserResponse->messageList) )

				$sirsiRoaUserID = $loginUserResponse->staffKey;
				//this is the same value as patron Key, if user is logged in with that call.
				$sessionToken = $loginUserResponse->sessionToken;
				SirsiDynixROA::$sessionIdsForUsers[(string)$sirsiRoaUserID] = $sessionToken;
				$session = [
					true,
					$sessionToken,
					$sirsiRoaUserID,
				];
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
			global $timer;
			$timer->logTime("logged in with staff user");
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
	public function getCheckouts(User $patron, $page = 1, $recordsPerPage = -1, $sortOption = 'dueDate'): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkedOutTitles = [];

		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $checkedOutTitles;
		}

		require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroup.php';
		require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroupLocation.php';

		$homeLocation = $patron->getHomeLocation();
		$holdGroupsForLocation = new HoldGroupLocation();
		$holdGroupsForLocation->locationId = $homeLocation->locationId;
		$holdGroupIds = $holdGroupsForLocation->fetchAll('holdGroupId');
		$holdGroups = [];
		foreach ($holdGroupIds as $holdGroupId) {
			$holdGroup = new HoldGroup();
			$holdGroup->id = $holdGroupId;
			if ($holdGroup->find(true)) {
				$holdGroups[] = clone $holdGroup;
			}
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		//Get a list of holds for the user
		$includeFields = urlencode('circRecordList{*,item{barcode,currentLibrary,bib{title,author},itemType,call{dispCallNumber}}}');
		$patronCheckouts = $this->getWebServiceResponse('getCheckouts', $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields, null, $sessionToken);

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

					[$bibId] = explode(':', $checkout->key);
					$curCheckout->recordId = 'a' . $bibId;
					require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
					$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . $curCheckout->recordId);
					if (!$recordDriver->isValid()) {
						$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . 'u' . $bibId);
						if ($recordDriver->isValid()) {
							$curCheckout->recordId = 'u' . $bibId;
						}
					}
					$curCheckout->itemId = $checkout->fields->item->key;

					$curCheckout->dueDate = strtotime($checkout->fields->dueDate);
					$curCheckout->checkoutDate = strtotime($checkout->fields->checkOutDate);
					// Note: there is an overdue flag
					$curCheckout->renewCount = $checkout->fields->renewalCount;
					$curCheckout->canRenew = $checkout->fields->seenRenewalsRemaining > 0;
					$curCheckout->renewalId = $checkout->fields->item->key;
					$curCheckout->renewIndicator = $checkout->fields->item->key;
					$curCheckout->barcode = $checkout->fields->item->fields->barcode;

					if ($recordDriver->isValid()) {
						$curCheckout->updateFromRecordDriver($recordDriver);
					} else {
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

					if (empty($holdGroups)) {
						$inHoldGroup = true;
					}else{
						$inHoldGroup = false;
						foreach ($holdGroups as $holdGroup){
							if (in_array($checkout->fields->item->fields->currentLibrary->key, $holdGroup->getLocationCodes())){
								$inHoldGroup = true;
								break;
							}
						}
					}

					if (!$inHoldGroup) {
						$curCheckout->outOfHoldGroupMessage = translate(['text' => 'Provided by Another Library', 'isPublicFacing' => true]);
						$library = $patron->getHomeLibrary();
						if (!$library->allowRenewingOutOfHoldGroupCheckouts){
							$curCheckout->canRenew = false;
						}
					}

					$sCount++;
					$sortKey = "{$curCheckout->source}_{$curCheckout->sourceId}_$sCount";
					$checkedOutTitles[$sortKey] = $curCheckout;
				}
			}
		}
		return $checkedOutTitles;
	}

	public function isBlockedFromIllRequests(User $user) {
		$sessionToken = $this->getSessionToken($user);
		if ($sessionToken) {

			//create the hold using the web service
			$webServiceURL = $this->getWebServiceURL();

			$patronStatusInfo = $this->getWebServiceResponse('patronStatusInfo', $webServiceURL . '/user/patronStatusInfo/key/' . $user->unique_ils_id, null, $sessionToken);
			if (!empty($patronStatusInfo)) {
				$patronStanding = $patronStatusInfo->fields->standing->key;
				if ($patronStanding == 'BLOCKED' || $patronStanding == 'BARRED' || $patronStanding == 'COLLECTION' || $patronStanding == 'REVIEW') {
					return true;
				}
			}
		}
		return false;
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
	public function getHolds($patron): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		global $library;
		$availableHolds = [];
		$unavailableHolds = [];
		$holds = [
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds,
		];

		//Get the session token for the user
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return $holds;
		}

		require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroup.php';
		require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroupLocation.php';

		$homeLocation = $patron->getHomeLocation();
		$holdGroupsForLocation = new HoldGroupLocation();
		$holdGroupsForLocation->locationId = $homeLocation->locationId;
		$holdGroupIds = $holdGroupsForLocation->fetchAll('holdGroupId');
		$holdGroups = [];
		foreach ($holdGroupIds as $holdGroupId) {
			$holdGroup = new HoldGroup();
			$holdGroup->id = $holdGroupId;
			if ($holdGroup->find(true)) {
				$holdGroups[] = clone $holdGroup;
			}
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
		$includeFields = urlencode("holdRecordList{*,bib{title,author},selectedItem{call{*},itemType{*},barcode,currentLocation,currentLibrary}}");
		$patronHolds = $this->getWebServiceResponse('getHolds', $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields, null, $sessionToken);
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
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . $curHold->recordId);
				if (!$recordDriver->isValid()) {
					$recordDriver = RecordDriverFactory::initRecordDriverById($this->getIndexingProfile()->name . ':' . 'u' . $bibId);
					if ($recordDriver->isValid()) {
						$curHold->recordId = 'u' . $bibId;
					}
				}

				$curHold->shortId = $bibId;
				$curPickupBranch = new Location();
				$curPickupBranch->code = $hold->fields->pickupLibrary->key;
				if ($curPickupBranch->find(true)) {
					$curPickupBranch->fetch();
					$curHold->pickupLocationId = $curPickupBranch->locationId;
					$curHold->pickupLocationName = $curPickupBranch->displayName;
				} else {
					$curHold->pickupLocationName = $curPickupBranch->code;
				}

				$curHold->status = ucfirst(strtolower($hold->fields->status));
				$isLocalIllHold = false;
				$currentLocation = '';
				if (!empty($hold->fields->mailFlag)){
					$isLocalIllHold = true;
				}elseif (!empty($hold->fields->selectedItem->fields->currentLocation->key)) {
					$currentLocation = $hold->fields->selectedItem->fields->currentLocation->key;
					if (in_array(strtoupper($currentLocation), ['ILL', 'ILLSHIPPED', 'ILLPENDING', 'ILL_WYLD'])) {
						$isLocalIllHold = true;
						$curHold->status = str_replace('_', ' ', $currentLocation);
					}
				}
				if ($isLocalIllHold){
					if (in_array(strtoupper($curHold->status), ['INSHIPPING', 'INTRANSIT', 'ILLSHIPPED'])){
						$curHold->outOfHoldGroupMessage = translate(['text' => 'Shipping from Another Library', 'isPublicFacing' => true]);
					}else{
						$curHold->outOfHoldGroupMessage = translate(['text' => 'Hold Pending from Another Library', 'isPublicFacing' => true]);
					}
				}
				$curHold->createDate = strtotime($createDate);
				$curHold->expirationDate = strtotime($expireDate);
				$curHold->automaticCancellationDate = strtotime($fillByDate);
				$curHold->reactivateDate = strtotime($reactivateDate);
				$curHold->cancelable = !in_array(strtoupper($curHold->status), ['SUSPENDED', 'EXPIRED', 'INSHIPPING', 'INTRANSIT', 'ILL_WYLD', 'ILLSHIPPED']);

				$curHold->frozen = strcasecmp($curHold->status, 'Suspended') == 0;
				$curHold->canFreeze = true;
				$curHold->locationUpdateable = true;
				if (in_array(strtoupper($curHold->status), ['TRANSIT', 'EXPIRED', 'INSHIPPING', 'ILL WYLD', 'ILLPENDING', 'ILLSHIPPED'])) {
					$curHold->locationUpdateable = false;
					$curHold->canFreeze = false;
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

				if ($recordDriver->isValid()) {
					$curHold->updateFromRecordDriver($recordDriver);
				}

				$holdAvailable = false;
				if (empty($hold->fields->mailFlag)) {
					if (!isset($curHold->status)) {
						$holdAvailable = true;
					} elseif (strcasecmp($curHold->status, "being_held") === 0) {
						$holdAvailable = true;
					}
				}else{
					if (strcasecmp($curHold->status, "being_held") === 0) {
						$curHold->cancelable = false;
						$curHold->canFreeze = false;
						$curHold->locationUpdateable = false;
					}
				}

				if (!$holdAvailable) {
					$curHold->available = false;
					$holds['unavailable'][] = $curHold;
				} else {
					$curHold->available = true;
					$curHold->canFreeze = false;
					$curHold->locationUpdateable = false;
					if (!$library->allowCancellingAvailableHolds) {
						$curHold->cancelable = false;
					}
					//Do not allow holds that are outside the active hold group from being canceled.
					if (empty($holdGroups)) {
						$inHoldGroup = true;
					}else {
						$inHoldGroup = false;
						foreach ($holdGroups as $holdGroup) {
							if (in_array($hold->fields->selectedItem->fields->currentLibrary->key, $holdGroup->getLocationCodes())) {
								$inHoldGroup = true;
								break;
							}
						}
					}
					if (!$inHoldGroup) {
						$curHold->cancelable = false;
					}
					$holds['available'][] = $curHold;
				}
			}
		}
		return $holds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds and placing recalls.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pick up the item when available
	 * @param null|string $cancelDate The date the hold should be automatically cancelled
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return an AspenError
	 * @access  public
	 */
	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null) {
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
	 *                              If an error occurs, return an AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch = null, $type = 'request', $cancelIfNotFilledByDate = null, $pickupSublocation = null) {
		return $this->placeSirsiHold($patron, $recordId, $itemId, false, $pickupBranch, $type, $cancelIfNotFilledByDate);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param ?string $itemId The id of the item to hold
	 * @param ?string $volume The volume identifier of the item to hold
	 * @param ?string $pickupBranch The Pickup Location
	 * @param string $type Whether to place a hold or recall
	 * @param null|string $cancelIfNotFilledByDate When to cancel the hold automatically if it is not filled
	 * @return  array  The results of the error
	 * @access  public
	 */
	function placeSirsiHold(User $patron, string $recordId, ?string $itemId, ?string $volume = null, ?string $pickupBranch = null, string $type = 'request', ?string $cancelIfNotFilledByDate = null, bool $forceVolumeHold = false, bool $useBooksByMail = false) : array {
		//Get the session token for the user
		$staffSessionToken = $this->getStaffSessionToken();
		$sessionToken = $this->getSessionToken($patron);
		if (!$staffSessionToken) {
			$result['success'] = false;
			$result['message'] = translate([
				'text' => "Sorry, it does not look like you are logged in currently.  Please login and try again",
				'isPublicFacing' => true,
			]);

			$result['api']['title'] = translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
				'isPublicFacing' => true,
			]);
			return $result;
		}

		if (str_contains($recordId, ':')) {
			[
				,
				$shortId,
			] = explode(':', $recordId);
		} else {
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
		global $timer;
		$timer->logTime("Loaded record driver");

		if ($type == 'cancel' || $type == 'recall' || $type == 'update') {
			$result = $this->updateHold($patron, $recordId, $type/*, $title*/);
			$result['title'] = $title;
			$result['bid'] = $shortId;

			return $result;

		} else {
			if (empty($pickupBranch)) {
				$pickupBranch = $patron->_homeLocationCode;
			}
			//create the hold using the web service
			$webServiceURL = $this->getWebServiceURL();

			$holdData = [
				'patron' => [
					'resource' => '/user/patron',
					'key' => $patron->unique_ils_id,
				],
				'pickupLibrary' => [
					'resource' => '/policy/library',
					'key' => strtoupper($pickupBranch),
				],
			];

			//Check whether we need to look up the volume key because we are no longer getting it from a txt file
			if (str_starts_with($volume, "LOOKUP")) {
				$idParts = explode(":", $volume);
				$displayVolume = $idParts[2] ?? '';
				$volume = $this->getMissingVolumeKey($webServiceURL, $shortId, $sessionToken, $displayVolume);
			}

			if (!empty($volume)) {
				$holdData['call'] = [
					'resource' => '/catalog/call',
					'key' => $volume,
				];
				$holdData['holdType'] = 'TITLE';
			} elseif (!empty($itemId)) {
				$holdData['itemBarcode'] = $itemId;
				if ($forceVolumeHold) {
					$holdData['holdType'] = 'TITLE';
				}else{
					$holdData['holdType'] = 'COPY';
				}
			} else {
				$shortRecordId = str_replace('a', '', $shortId);
				$shortRecordId = str_replace('u', '', $shortRecordId);
				$holdData['bib'] = [
					'resource' => '/catalog/bib',
					'key' => $shortRecordId,
				];
				$holdData['holdType'] = 'TITLE';
			}

			$userLibrary = $patron->getHomeLibrary();
			$holdData['holdRange'] = $userLibrary->holdRange;

			if ($cancelIfNotFilledByDate) {
				$holdData['fillByDate'] = date('Y-m-d', strtotime($cancelIfNotFilledByDate));
			}

			global $library;
			if (UserAccount::isUserMasquerading()) {
				if (!empty($library->systemHoldNoteMasquerade)) {
					$holdData['comment'] = $library->systemHoldNoteMasquerade;
				}
			} else {
				if (!empty($library->systemHoldNote)) {
					$holdData['comment'] = $library->systemHoldNote;
				}
			}

			if ($useBooksByMail) {
				$holdData['mailFlag'] = true;
				$holdData['mailService'] = [
					'resource' => '/policy/mailService',
					'key' => 'USPS',
				];
				$holdData['holdRange'] = 'SYSTEM';

				if (!empty($volume)) {
					//Local ILL with books by mail doesn't work, just return an error message
					$result['success'] = false;
					$result['message'] = translate([
						'text' => "Titles with volumes cannot be requested from other libraries via the catalog. Please contact the library to request this title.",
						'isPublicFacing' => true,
					]);

					$result['api']['title'] = translate([
						'text' => 'Error',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = $result['message'];
					return $result;
				}
			}

			//$holdRecord         = $this->getWebServiceResponse('holdRecordDescribe', $webServiceURL . "/circulation/holdRecord/describe", null, $sessionToken);
			//$placeHold          = $this->getWebServiceResponse('placeHoldDescribe', $webServiceURL . "/circulation/holdRecord/placeHold/describe", null, $sessionToken);
			global $locationSingleton;
			$physicalLocation = $locationSingleton->getPhysicalLocation();

			if ($library->holdPlacedAt == 0) {
				$workingLibraryId = $library->ilsCode;
				if (!empty($physicalLocation)) {
					$workingLibraryId = $physicalLocation->code;
				}
			} elseif ($library->holdPlacedAt == 1) {
				$workingLibraryId = $patron->getHomeLocation()->code;
			} else {
				$workingLibraryId = $pickupBranch;
			}

			//Check to see if there is a maximum fee
			if (!empty($_REQUEST['maximumFeeAmount'])) {
				$maximumFeeNote = 'Max Fee ' . $_REQUEST['maximumFeeAmount'];
				if (empty($holdData['comment'])) {
					$holdData['comment'] = $maximumFeeNote;
				}else{
					/** @noinspection PhpArrayToStringConversionInspection */
					$holdData['comment'] = $holdData['comment'] . "; " . $maximumFeeNote;
				}
			}

			//Check to see if there is a note
			if (!empty($_REQUEST['note'])) {
				if (empty($holdData['comment'])) {
					$holdData['comment'] = $_REQUEST['note'];
				}else{
					/** @noinspection PhpArrayToStringConversionInspection */
					$holdData['comment'] = $holdData['comment'] . "; " . $_REQUEST['note'];
				}
			}

			if (!empty($holdData['comment']) && strlen($holdData['comment']) > 50) {
				$holdData['comment'] = substr($holdData['comment'], 0, 50);
			}

			$createHoldResponse = $this->getWebServiceResponse('placeHold', $webServiceURL . "/circulation/holdRecord/placeHold", $holdData, $sessionToken, null, null, [], $workingLibraryId);

			$hold_result = [];
			if (isset($createHoldResponse->messageList)) {
				$hold_result['success'] = false;
				$hold_result['message'] = translate([
					'text' => 'Your hold could not be placed.',
					'isPublicFacing' => true,
				]);

				$hold_result['api']['title'] = translate([
					'text' => 'Unable to place hold',
					'isPublicFacing' => true,
				]);
				$hold_result['api']['message'] = translate([
					'text' => 'Your hold could not be placed.',
					'isPublicFacing' => true,
				]);

				$hold_result['message'] .= ' ' . translate([
						'text' => (string)$createHoldResponse->messageList[0]->message,
						'isPublicFacing' => true,
					]);
				$hold_result['error_code'] = $createHoldResponse->messageList[0]->code;
				//Do not return error code to LiDA as part of the message
				$hold_result['api']['message'] .= ' ' . translate([
						'text' => (string)$createHoldResponse->messageList[0]->message,
						'isPublicFacing' => true,
					]);
				global $logger;
				$errorMessage = 'Sirsi ROA Place Hold Error: ';
				foreach ($createHoldResponse->messageList as $error) {
					$errorMessage .= $error->message . '; ';
				}
				if (IPAddress::showDebuggingInformation()) {
					$hold_result['message'] .= "<br>\r\n" . print_r($holdData, true);
				}
				$logger->log($errorMessage, Logger::LOG_ERROR);
			} else {
				$hold_result['success'] = true;
				$hold_result['message'] = translate([
					'text' => "Your hold was placed successfully.",
					'isPublicFacing' => true,
				]);

				$hold_result['api']['title'] = translate([
					'text' => 'Hold placed successfully',
					'isPublicFacing' => true,
				]);
				$hold_result['api']['message'] = translate([
					'text' => 'Your hold was placed successfully.',
					'isPublicFacing' => true,
				]);
				$hold_result['api']['action'] = translate([
					'text' => 'Go to Holds',
					'isPublicFacing' => true,
				]);

				$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
				$patron->forceReloadOfHolds();
			}

			$hold_result['title'] = $title;
			$hold_result['bid'] = $shortId;
			return $hold_result;
		}
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation = null) {
		if ($volumeId == '' && !$this->alwaysPlaceVolumeHoldWhenVolumesArePresent()) {
			return $this->placeSirsiHold($patron, $recordId, '', $volumeId, $pickupBranch);
		} elseif ($volumeId == '' && $this->alwaysPlaceVolumeHoldWhenVolumesArePresent()) {
			//To place a volume hold on a blank volume we need to find an item without a volume, preferably owned by this system.
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($this->getIndexingProfile()->name . ':' . $recordId);
			$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
			$itemIdToUse = null;
			//Check all items to get the item id we want
			foreach ($relatedRecord->getItems() as $item) {
				//we only care about items with no volume
				if (empty($item->volume) && !$item->isEContent) {
					if ($item->libraryOwned || $item->locallyOwned) {
						$itemIdToUse = $item->itemId;
						break;
					}elseif ($itemIdToUse == null){
						$itemIdToUse = $item->itemId;
					}
				}
			}

			return $this->placeSirsiHold($patron, $recordId, $itemIdToUse, $volumeId, $pickupBranch, 'request', null, true);
		} else {
			//To place a volume hold in Symphony, we just need to place a hold on one of the items for the volume.
			require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
			$volumeInfo = new IlsVolumeInfo();
			$volumeInfo->volumeId = $volumeId;
			$volumeInfo->recordId = $this->getIndexingProfile()->name . ':' . $recordId;
			if ($volumeInfo->find(true)) {
				$relatedItems = explode('|', $volumeInfo->relatedItems);
				$itemToHold = $relatedItems[0];
				return $this->placeSirsiHold($patron, $recordId, $itemToHold, $volumeId, $pickupBranch);
			} else {
				return [
					'success' => false,
					'message' => 'Sorry, we could not find the specified volume, it may have been deleted.',
					'api' => [
						'title' => 'Unable to place hold',
						'message' => 'Sorry, we could not find the specified volume, it may have been deleted.',
					],
				];
			}
		}
	}

	private function getMissingVolumeKey($webServiceURL, $shortId, $sessionToken, $volume) {
		// We need a call number key (formatted bibId:itemId) to place a volume hold, but the itemId isn't in the MARC record
		// First get all the keys for the record
		$numericId = str_replace('a', '', $shortId);
		$getKeysForBib = $this->getWebServiceResponse('catalogBib', $webServiceURL . "/catalog/bib/key/" . $numericId . "?includeFields=callList", null, $sessionToken);
		foreach ($getKeysForBib->fields->callList as $call) {
			// Get the item that matches that key
			$item = $this->getWebServiceResponse('catalogCall', $webServiceURL . "/catalog/call/key/" . $call->key, null, $sessionToken);
			// Create a lookup array that returns the first key that matches the volume number
			if ($item->fields->volumetric == $volume) {
				return $item->key;
			}
		}
		// Return a blank string if there was no matching volume key
		return "";
	}


	private function getSessionToken(User $patron) {
		if (UserAccount::isUserMasquerading()) {
			//If the user is masquerading, we will use the staff login since we might not have the patron PIN
			$sirsiRoaUserId = UserAccount::getGuidingUserObject()->unique_ils_id;
		} else {
			$sirsiRoaUserId = $patron->unique_ils_id;
		}


		//Get the session token for the user
		if (isset(SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserId])) {
			return SirsiDynixROA::$sessionIdsForUsers[$sirsiRoaUserId];
		} else {
			if (UserAccount::isUserMasquerading()) {
				//If the user is masquerading, we will use the staff login since we might not have the patron PIN
				//list($userValid, $sessionToken) = $this->loginViaWebService(UserAccount::getGuidingUserObject()->cat_username, UserAccount::getGuidingUserObject()->cat_password);
				return $this->getStaffSessionToken();
			}
			[
				,
				$sessionToken,
			] = $this->loginViaWebService($patron->ils_barcode, $patron->ils_password);
			global $timer;
			$timer->logTime("Created session token");
			return $sessionToken;
		}
	}

	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false): array {
		$result = [];
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, we could not connect to the circulation system.',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, we could not connect to the circulation system',
				'isPublicFacing' => true,
			]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$cancelHoldResponse = $this->getWebServiceResponse('cancelHold', $webServiceURL . "/circulation/holdRecord/key/$cancelId", null, $sessionToken, 'DELETE');

		if (empty($cancelHoldResponse)) {
			$patron->forceReloadOfHolds();
			$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'The hold was successfully canceled.',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold cancelled',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'The hold was successfully canceled',
				'isPublicFacing' => true,
			]);

			return $result;
		} else {
			global $logger;
			$errorMessage = 'Sirsi ROA Cancel Hold Error: ';
			foreach ($cancelHoldResponse->messageList as $error) {
				$errorMessage .= $error->message . '; ';
			}
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result['success'] = false;
			$result['message'] = translate([
				'text' => 'Sorry, the hold was not canceled',
				'isPublicFacing' => true,
			]);

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to cancel hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'This hold could not be cancelled. Please try again later or see your librarian.',
				'isPublicFacing' => true,
			]);

			return $result;
		}

	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation, $newPickupSublocation = null): array {
		$staffSessionToken = $this->getStaffSessionToken();
		if (!$staffSessionToken) {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
				'isPublicFacing' => true,
			]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$params = [
			'holdRecord' => [
				'resource' => '/circulation/holdRecord',
				'key' => $itemToUpdateId,
			],
			'pickupLibrary' => [
				'resource' => '/policy/library',
				'key' => strtoupper($newPickupLocation),
			],
		];

		$updateHoldResponse = $this->getWebServiceResponse('changePickupLibrary', $webServiceURL . "/circulation/holdRecord/changePickupLibrary", $params, $this->getSessionToken($patron), 'POST');
		if (isset($updateHoldResponse->holdRecord->key)) {
			$patron->forceReloadOfHolds();
			$result['message'] = translate([
				'text' => 'The pickup location has been updated.',
				'isPublicFacing' => true,
			]);
			$result['success'] = true;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Pickup location updated',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'The pickup location of your hold was changed successfully.',
				'isPublicFacing' => true,
			]);

			return $result;
		} else {
			$messages = [];
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Sirsi ROA Change Hold Pickup Location Error: ' . ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [];
			$result['message'] = 'Failed to update the pickup location : ' . implode('; ', $messages);
			$result['success'] = false;

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to update pickup location',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, the pickup location of your hold could not be changed. ',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] .= ' ' . implode('; ', $messages);
			return $result;
		}
	}

	function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate): array {
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
				'isPublicFacing' => true,
			]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$today = date('Y-m-d');
		$formattedDateToReactivate = $dateToReactivate ? date('Y-m-d', strtotime($dateToReactivate)) : null;

		$params = [
			'holdRecord' => [
				'key' => $itemToFreezeId,
				'resource' => '/circulation/holdRecord',
			],
			'suspendBeginDate' => $today,
			'suspendEndDate' => $formattedDateToReactivate,
		];

		$updateHoldResponse = $this->getWebServiceResponse('suspendHold', $webServiceURL . "/circulation/holdRecord/suspendHold", $params, $sessionToken, 'POST');

		if (isset($updateHoldResponse->holdRecord->key)) {
			$getHoldResponse = $this->getWebServiceResponse('getHold', $webServiceURL . "/circulation/holdRecord/key/$itemToFreezeId", null, $this->getSessionToken($patron));
			if (isset($getHoldResponse->fields->status) && $getHoldResponse->fields->status == 'SUSPENDED') {
				$patron->forceReloadOfHolds();
				$result = [
					'success' => true,
					'message' => translate([
						'text' => 'The hold has been frozen.',
						'isPublicFacing' => true,
					]),
				];

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Hold frozen',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'Your hold was frozen successfully.',
					'isPublicFacing' => true,
				]);

				return $result;
			} else {
				$result = [
					'success' => false,
					'message' => translate([
						'text' => 'The hold could not be frozen.',
						'isPublicFacing' => true,
					]),
				];

				// Result for API or app use
				$result['api']['title'] = translate([
					'text' => 'Unable to freeze hold',
					'isPublicFacing' => true,
				]);
				$result['api']['message'] = translate([
					'text' => 'There was an error freezing your hold.',
					'isPublicFacing' => true,
				]);

				return $result;
			}

		} else {
			$messages = [];
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}

			global $logger;
			$errorMessage = 'Sirsi ROA Freeze Hold Error: ' . ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [
				'success' => false,
				'message' => translate([
						'text' => "Failed to freeze hold",
						'isPublicFacing' => true,
					]) . ' - ' . implode('; ', $messages),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to freeze hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'There was an error freezing your hold.',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] .= ' ' . implode('; ', $messages);

			return $result;
		}
	}

	function thawHold($patron, $recordId, $itemToThawId): array {
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
				'isPublicFacing' => true,
			]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$params = [
			'holdRecord' => [
				'key' => $itemToThawId,
				'resource' => '/circulation/holdRecord',
			],
		];

		$updateHoldResponse = $this->getWebServiceResponse('unsuspendHold', $webServiceURL . "/circulation/holdRecord/unsuspendHold", $params, $sessionToken, 'POST');

		if (isset($updateHoldResponse->holdRecord->key)) {
			$patron->forceReloadOfHolds();
			$result = [
				'success' => true,
				'message' => translate([
					'text' => 'The hold has been thawed.',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Hold thawed',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your hold was thawed successfully.',
				'isPublicFacing' => true,
			]);

			return $result;
		} else {
			$messages = [];
			if (isset($updateHoldResponse->messageList)) {
				foreach ($updateHoldResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Sirsi ROA Thaw Hold Error: ' . ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [
				'success' => false,
				'message' => translate([
						'text' => "Failed to thaw hold",
						'isPublicFacing' => true,
					]) . ' - ' . implode('; ', $messages),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to thaw hold',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'There was an error thawing your hold.',
				'isPublicFacing' => true,
			]);
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
	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null) {
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
				'isPublicFacing' => true,
			]);

			return $result;
		}

		//create the hold using the web service
		$webServiceURL = $this->getWebServiceURL();

		$params = [
			'item' => [
				'key' => $itemId,
				'resource' => '/catalog/item',
			],
		];

		$circRenewResponse = $this->getWebServiceResponse('renewCheckout', $webServiceURL . "/circulation/circRecord/renew", $params, $sessionToken, 'POST');

		if (isset($circRenewResponse->circRecord->key)) {
			// Success
			$patron->forceReloadOfCheckouts();
			$result = [
				'success' => true,
				'itemId' => $circRenewResponse->circRecord->key,
				'message' => translate([
					'text' => 'Your item was successfully renewed.',
					'isPublicFacing' => true,
				]),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Renewed title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Your item was successfully renewed.',
				'isPublicFacing' => true,
			]);

			return $result;
		} else {
			// Error
			$messages = [];
			if (isset($circRenewResponse->messageList)) {
				foreach ($circRenewResponse->messageList as $message) {
					$messages[] = $message->message;
				}
			}
			global $logger;
			$errorMessage = 'Sirsi ROA Renew Error: ' . ($messages ? implode('; ', $messages) : '');
			$logger->log($errorMessage, Logger::LOG_ERROR);

			$result = [
				'success' => false,
				'itemId' => $circRenewResponse->circRecord->key,
				'message' => "The item failed to renew" . ($messages ? ': ' . implode(';', $messages) : ''),
			];

			// Result for API or app use
			$result['api']['title'] = translate([
				'text' => 'Unable to renew title',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'The item failed to renew.',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] .= ' ' . ($messages ? ': ' . implode(';', $messages) : '');

			return $result;

		}

	}

	/**
	 * @param User $patron
	 * @param $includeMessages
	 * @return array
	 */
	public function getFines($patron, $includeMessages = false): array {
		$fines = [];
		$sessionToken = $this->getSessionToken($patron);
		if ($sessionToken) {

			//create the hold using the web service
			$webServiceURL = $this->getWebServiceURL();

			$includeFields = urlencode("blockList{*,item{bib{title,author},barcode}}");
			$blockList = $this->getWebServiceResponse('getFines', $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields, null, $sessionToken);
			// Include Title data if available

			$totalFinesOwed = 0;

			if (!empty($blockList->fields->blockList)) {
				foreach ($blockList->fields->blockList as $block) {
					$fine = $block->fields;
					$title = '';
					$barcode = '';
					if (!empty($fine->item) && !empty($fine->item->key)) {
						$bibInfo = $fine->item->fields->bib;
						$title = $bibInfo->fields->title;
						if (!empty($bibInfo->fields->author)) {
							$title .= '  by ' . $bibInfo->fields->author;
						}
						if (!empty($fine->item->fields->barcode)) {
							$barcode = $fine->item->fields->barcode;
						}
					}

					$fines[] = [
						'fineId' => str_replace(':', '_', $block->key),
						'reason' => translate([
							'text' => $fine->block->key,
							'isPublicFacing' => true,
							'isAdminEnteredData' => true,
						]),
						'type' => $fine->block->key,
						'amount' => $fine->amount->amount,
						'amountVal' => $fine->amount->amount,
						'message' => $title,
						'barcode' => $barcode,
						'amountOutstanding' => $fine->owed->amount,
						'amountOutstandingVal' => $fine->owed->amount,
						'date' => $fine->billDate,
					];
					$totalFinesOwed += $fine->owed->amount;
				}
			}

			$accountSummary = $patron->getAccountSummary();
			if ($accountSummary->totalFines != $totalFinesOwed) {
				$patron->clearCachedAccountSummaryForSource($this->getIndexingProfile()->name);
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
	function updatePin(User $patron, ?string $oldPin, string $newPin) {
		$sessionToken = $this->getSessionToken($patron);
		if (!$sessionToken) {
			return [
				'success' => false,
				'message' => 'Sorry, it does not look like you are logged in currently.  Please login and try again',
			];
		}

		$params = [
			'currentPin' => $oldPin,
			'newPin' => $newPin,
		];

		$webServiceURL = $this->getWebServiceURL();

		if (!empty($this->accountProfile->overrideCode)) {
			$additionalHeaders = [
				'SD-Prompt-Return: USER_PRIVILEGE_OVRCD/' . $this->accountProfile->overrideCode,
			];
		} else {
			$additionalHeaders = [];
		}

		$updatePinResponse = $this->getWebServiceResponse('changePin', $webServiceURL . "/user/patron/changeMyPin", $params, $sessionToken, 'POST', $additionalHeaders);
		if (!empty($updatePinResponse->patronKey) && $updatePinResponse->patronKey == $patron->unique_ils_id) {
			$patron->cat_password = $newPin;
			$patron->lastLoginValidation = 0;
			$patron->update();
			$patron->clearActiveSessions();
			return [
				'success' => true,
				'message' => "Your pin number was updated successfully.",
			];

		} else {
			$messages = [];
			if (isset($updatePinResponse->messageList)) {
				foreach ($updatePinResponse->messageList as $message) {
					$messages[] = $message->message;
					if ($message->message == 'Public access users may not change this user\'s PIN') {
						$staffPinError = 'Staff can not change their PIN through the online catalog.';
					}
				}
				global $logger;
				$logger->log('Symphony ILS encountered errors updating patron pin : ' . implode('; ', $messages), Logger::LOG_ERROR);
				if (!empty($staffPinError)) {
					return [
						'success' => false,
						'message' => $staffPinError,
					];
				} else {
					return [
						'success' => false,
						'message' => 'The circulation system encountered errors attempt to update the pin.',
					];
				}
			}
			return [
				'success' => false,
				'message' => 'Failed to update pin',
			];
		}
	}

	/**
	 * @param User|null $user
	 * @param string $newPin
	 * @param string $resetToken
	 * @return array
	 */
	function resetPin($user, $newPin, $resetToken = null) {
		if (empty($resetToken)) {
			global $logger;
			$logger->log('No Reset Token passed to resetPin function', Logger::LOG_ERROR);
			return [
				'error' => 'Sorry, we could not update your pin. The reset token is missing. Please try again later',
			];
		}

		$changeMyPinAPIUrl = $this->getWebServiceUrl() . '/user/patron/changeMyPin';
		$jsonParameters = [
			'resetPinToken' => $resetToken,
			'newPin' => $newPin,
		];
		$resetPinResponse = $this->getWebServiceResponse('resetPin', $changeMyPinAPIUrl, $jsonParameters, null, 'POST');
		if (is_object($resetPinResponse) && isset($resetPinResponse->messageList)) {
			$errors = [];
			foreach ($resetPinResponse->messageList as $message) {
				$errors[] = $message->message;
			}
			global $logger;
			$logger->log('SirsiDynixROA Driver error updating user\'s Pin :' . implode(';', $errors), Logger::LOG_ERROR);
			return [
				'error' => 'Sorry, we encountered an error while attempting to update your pin. Please contact your local library.',
			];
		} elseif (!empty($resetPinResponse->sessionToken)) {
			if ($user != null) {
				if ($user->unique_ils_id == $resetPinResponse->patronKey) { // Check that the ILS user matches the Aspen Discovery user
					$user->cat_password = $newPin;
					$user->lastLoginValidation = 0;
					$user->update();

					$user->clearActiveSessions();
				}
			}
			return [
				'success' => true,
			];
//			return "Your pin number was updated successfully.";
		} else {
			return [
				'error' => "Sorry, we could not update your pin number. Please try again later.",
			];
		}
	}

	function getEmailResetPinResultsTemplate() {
		return 'emailResetPinResults.tpl';
	}

	function processEmailResetPinForm() : array {
		$barcode = $_REQUEST['barcode'];

		$patron = new User;
		$patron->ils_barcode = $barcode;
		if ($patron->find(true)) {
			$aspenUserID = $patron->id;

			// If possible, check if ILS has an email address for the patron
			if (!empty($patron->cat_password)) {
				[
					$userValid,
					$sessionToken,
					$userID,
				] = $this->loginViaWebService($barcode, $patron->cat_password);
				if ($userValid) {
					// Yay! We were able to login with the pin Aspen has!

					//Now check for an email address
					$lookupMyAccountInfoResponse = $this->getWebServiceResponse('lookupAccountInfo', $this->getWebServiceURL() . '/user/patron/key/' . $userID . '?includeFields=preferredAddress,address1,address2,address3', null, $sessionToken);
					if ($lookupMyAccountInfoResponse) {
						if (isset($lookupMyAccountInfoResponse->fields->preferredAddress)) {
							$preferredAddress = $lookupMyAccountInfoResponse->fields->preferredAddress;
							$addressField = 'address' . $preferredAddress;
							//TODO: Does Symphony's email reset pin use any email address; or just the one associated with the preferred Address
							if (!empty($lookupMyAccountInfoResponse->fields->$addressField)) {
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
									return [
										'success' => false,
										'error' => 'The circulation system does not have an email associated with this card number. Please contact your library to reset your pin.',
									];
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
		$jsonPOST = [
			'login' => $barcode,
			'resetPinUrl' => $configArray['Site']['url'] . '/MyAccount/ResetPin?resetToken=<RESET_PIN_TOKEN>&uid=' . $aspenUserID,
		];

		$resetPinResponse = $this->getWebServiceResponse('resetPin', $resetPinAPIUrl, $jsonPOST, null, 'POST');
		if (is_object($resetPinResponse) && !isset($resetPinResponse->messageList)) {
			// Reset Pin Response is empty JSON on success.
			return [
				'success' => true,
			];
		} else {
			$result = [
				'success' => false,
				'error' => "Sorry, we could not email your pin to you.  Please visit the library to reset your pin.",
			];
			if (isset($resetPinResponse->messageList)) {
				$errors = [];
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
	function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade): array {
		require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationForm.php';
		$result = [
			'success' => false,
			'messages' => [],
		];
		$cityState = 0;
		$homeLibrary = $patron->getHomeLibrary();
		$selfRegistrationForm = new SelfRegistrationForm();
		$selfRegistrationForm->id = $homeLibrary->selfRegistrationFormId;
		if ($selfRegistrationForm->find(true)) {
			$cityState = $selfRegistrationForm->cityStateField;
		}

		if ($canUpdateContactInfo) {
			$sessionToken = $this->getStaffSessionToken();
			if ($sessionToken) {
				$webServiceURL = $this->getWebServiceURL();
				if ($userID = $patron->unique_ils_id) {
					//To update the patron, we need to load the patron from Symphony so we only overwrite changed values.
					$updatePatronInfoParametersClass = $this->getWebServiceResponse('getPatronInfo', $this->getWebServiceURL() . '/user/patron/key/' . $userID . '?includeFields=*,preferredAddress,address1,address2,address3', null, $sessionToken);
					if ($updatePatronInfoParametersClass) {
						//Convert from stdClass to associative array
						$updatePatronInfoParameters = json_decode(json_encode($updatePatronInfoParametersClass), true);
						if (isset($updatePatronInfoParameters['resource']) && $updatePatronInfoParameters['resource'] == '/user/patron') {
							$preferredAddress = $updatePatronInfoParameters['fields']['preferredAddress'];

							if (isset($_REQUEST['preferredName'])) {
								$updatePatronInfoParameters['fields']['preferredName'] = $this->getPatronFieldValue($_REQUEST['preferredName'], $homeLibrary->useAllCapsWhenUpdatingProfile);
								if ($homeLibrary->setUsePreferredNameInIlsOnUpdate) {
									$updatePatronInfoParameters['fields']['usePreferredName'] = !empty($_REQUEST['preferredName']);
								}
								$patron->_preferredName = $_REQUEST['preferredName'];
								$patron->displayName = $_REQUEST['preferredName'];
							}

							// Update Address Field with new data supplied by the user
							if (isset($_REQUEST['email'])) {
								$this->setPatronUpdateFieldBySearch('EMAIL', $this->getPatronFieldValue($_REQUEST['email'], $homeLibrary->useAllCapsWhenUpdatingProfile), $updatePatronInfoParameters, $preferredAddress);
								$patron->email = $_REQUEST['email'];
							}

							if (isset($_REQUEST['homephone'])) {
								$this->setPatronUpdateFieldBySearch('HOMEPHONE', $_REQUEST['homephone'], $updatePatronInfoParameters, $preferredAddress);
								$patron->phone = $_REQUEST['homephone'];
							}

							if (isset($_REQUEST['dayphone'])) {
								$this->setPatronUpdateFieldBySearch('DAYPHONE', $_REQUEST['dayphone'], $updatePatronInfoParameters, $preferredAddress);
								$patron->phone = $_REQUEST['dayphone'];
							}

							if (isset($_REQUEST['cellphone'])) {
								$this->setPatronUpdateFieldBySearch('CELLPHONE', $_REQUEST['cellphone'], $updatePatronInfoParameters, $preferredAddress);
								$patron->_mobileNumber = $_REQUEST['cellphone'];
							}

							if (isset($_REQUEST['phone'])) {
								$this->setPatronUpdateFieldBySearch('PHONE', $_REQUEST['phone'], $updatePatronInfoParameters, $preferredAddress);
								$patron->phone = $_REQUEST['phone'];
							}

							if (isset($_REQUEST['address1'])) {
								$this->setPatronUpdateFieldBySearch('STREET', $this->getPatronFieldValue($_REQUEST['address1'], $homeLibrary->useAllCapsWhenUpdatingProfile), $updatePatronInfoParameters, $preferredAddress);
								$patron->_address1 = $_REQUEST['address1'];
							}

							if ($cityState == 1) {
								if (isset($_REQUEST['city'])) {
									$this->setPatronUpdateFieldBySearch('CITY', $this->getPatronFieldValue($_REQUEST['city'], $homeLibrary->useAllCapsWhenUpdatingProfile), $updatePatronInfoParameters, $preferredAddress);
									$patron->_city = $_REQUEST['city'];
								}

								if (isset($_REQUEST['state'])) {
									$this->setPatronUpdateFieldBySearch('STATE', $this->getPatronFieldValue($_REQUEST['state'], $homeLibrary->useAllCapsWhenUpdatingProfile), $updatePatronInfoParameters, $preferredAddress);
									$patron->_state = $_REQUEST['state'];
								}
							} else {
								if (isset($_REQUEST['city']) && isset($_REQUEST['state'])) {
									if ($cityState == 2) {
										$this->setPatronUpdateFieldBySearch('CITY/STATE', $this->getPatronFieldValue($_REQUEST['city'] . ', ' . $_REQUEST['state'], $homeLibrary->useAllCapsWhenUpdatingProfile), $updatePatronInfoParameters, $preferredAddress);
									} else {
										$this->setPatronUpdateFieldBySearch('CITY/STATE', $this->getPatronFieldValue($_REQUEST['city'] . ' ' . $_REQUEST['state'], $homeLibrary->useAllCapsWhenUpdatingProfile), $updatePatronInfoParameters, $preferredAddress);
									}
									$patron->_city = $_REQUEST['city'];
									$patron->_state = $_REQUEST['state'];
								}
							}

							if (isset($_REQUEST['zip'])) {
								$this->setPatronUpdateFieldBySearch('ZIP', $_REQUEST['zip'], $updatePatronInfoParameters, $preferredAddress);
								$patron->_zip = $_REQUEST['zip'];
							}

							// Update notice preferences
							$noticeCategory = $homeLibrary->symphonyNoticeCategoryNumber;
							if (!empty($noticeCategory) && isset($_REQUEST['category' . $noticeCategory])) {
								$updatePatronInfoParameters['fields']['category' . $noticeCategory]['key'] = $this->getPatronFieldValue($_REQUEST['category' . $noticeCategory], $homeLibrary->useAllCapsWhenUpdatingProfile);
								$patron->_notices = $_REQUEST['category' . $noticeCategory];
							}
							$billingNoticeCategory = $homeLibrary->symphonyBillingNoticeCategoryNumber;
							if (!empty($billingNoticeCategory) && isset($_REQUEST['category' . $billingNoticeCategory])) {
								$updatePatronInfoParameters['fields']['category' . $billingNoticeCategory]['key'] = $this->getPatronFieldValue($_REQUEST['category' . $billingNoticeCategory], $homeLibrary->useAllCapsWhenUpdatingProfile);
								$patron->_billingNotices = $_REQUEST['category' . $billingNoticeCategory];
							}

							// Update Home Location
							if (!empty($_REQUEST['pickupLocation'])) {
								$homeLibraryLocation = new Location();
								if ($homeLibraryLocation->get('code', $_REQUEST['pickupLocation'])) {
									$homeBranchCode = strtoupper($homeLibraryLocation->code);
									$updatePatronInfoParameters['fields']['library'] = [
										'key' => $homeBranchCode,
										'resource' => '/policy/library',
									];
								}
							}

							$updateAccountInfoResponse = $this->getWebServiceResponse('updatePatronInfo', $webServiceURL . '/user/patron/key/' . $userID . '?includeFields=*,preferredAddress,preferredName,address1,address2,address3', $updatePatronInfoParameters, $sessionToken, 'PUT');

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
						} else {
							$result['messages'][] = 'Could not load existing contact information to update.';
						}
					} else {
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

	public function showOutstandingFines() {
		return true;
	}

	function getForgotPasswordType() {
		return 'emailResetLink';
	}

	function getEmailResetPinTemplate() {
		return 'sirsiROAEmailResetPinLink.tpl';
	}

	function translateFineMessageType($code) {
		switch ($code) {

			default:
				return $code;
		}
	}

	public function translateLocation($locationCode) {
		$locationCode = strtoupper($locationCode);
		$locationMap = [

		];
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : "Unknown";
	}

	public function translateCollection($collectionCode) {
		$collectionCode = strtoupper($collectionCode);
		$collectionMap = [

		];
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : "Unknown $collectionCode";
	}

	public function translateStatus($statusCode) {
		$statusCode = strtolower($statusCode);
		$statusMap = [

		];
		return isset($statusMap[$statusCode]) ? $statusMap[$statusCode] : 'Unknown (' . $statusCode . ')';
	}

	public function logout(User $user) {
		global $memCache;
		global $library;
		$memCacheKey = "sirsiROA_session_token_info_{$library->libraryId}_{$user->getBarcode()}_" . session_id();
		$memCache->delete($memCacheKey);
	}

	private function setPatronUpdateField($key, $value, &$updatePatronInfoParameters, $preferredAddress, &$index) {
		static $parameterIndex = [];

		$addressField = 'address' . $preferredAddress;
		$patronAddressPolicyResource = '/policy/patron' . ucfirst($addressField);

		$l = array_key_exists($key, $parameterIndex) ? $parameterIndex[$key] : $index++;
		$updatePatronInfoParameters['fields'][$addressField][$l] = [
			'resource' => '/user/patron/' . $addressField,
			'fields' => [
				'code' => [
					'key' => $key,
					'resource' => $patronAddressPolicyResource,
				],
				'data' => $value,
			],
		];
		$parameterIndex[$key] = $l;
	}

	private function setPatronUpdateFieldBySearch($key, $value, &$updatePatronInfoParameters, $preferredAddress) {
		$addressField = 'address' . $preferredAddress;

		$patronAddress = &$updatePatronInfoParameters['fields'][$addressField];
		$fieldFound = false;
		$maxKey = 0;
		foreach ($patronAddress as &$field) {
			if ($field['key'] > $maxKey) {
				$maxKey = $field['key'];
			}
			if ($field['fields']['code']['key'] == $key) {
				$field['fields']['data'] = $value;
				$fieldFound = true;
				break;
			}
		}
		if (!$fieldFound) {
			++$maxKey;
			$patronAddress[] = [
				'resource' => "/user/patron/$addressField",
				'key' => $maxKey,
				'fields' => [
					'code' => [
						'resource' => "/user/patron/$addressField",
						'key' => $key,
					],
					'data' => $value,
				],
			];
		}
	}

//	function getPasswordPinValidationRules() : array {
//		return [
//			'minLength' => 4,
//			'maxLength' => 60,
//			'onlyDigitsAllowed' => false,
////		'requireStrongPassword' => false
//		];
//	}

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user) {
		$webServiceURL = $this->getWebServiceURL();
		$staffSessionToken = $this->getStaffSessionToken();
		$homeLibrary = $user->getHomeLibrary();
		$categories = '';
		$categories .= $homeLibrary->symphonyNoticeCategoryNumber ? ',category' . $homeLibrary->symphonyNoticeCategoryNumber : '';
		$categories .= $homeLibrary->symphonyBillingNoticeCategoryNumber ? ',category' . $homeLibrary->symphonyBillingNoticeCategoryNumber : '';
		$includeFields = urlEncode("firstName,lastName,privilegeExpiresDate,preferredAddress,preferredName,address1,address2,address3,library,primaryPhone,profile,blockList{owed}" . $categories);
		$accountInfoLookupURL = $webServiceURL . '/user/patron/key/' . $user->unique_ils_id . '?includeFields=' . $includeFields;

		// phoneList is for texting notification preferences
		$lookupMyAccountInfoResponse = $this->getWebServiceResponse('loadContactInformation', $accountInfoLookupURL, null, $staffSessionToken);
		if ($lookupMyAccountInfoResponse && !isset($lookupMyAccountInfoResponse->messageList)) {
			$this->loadContactInformationFromApiResult($user, $lookupMyAccountInfoResponse);
		}
	}

	/**
	 * @param User $user ;
	 * @param $lookupMyAccountInfoResponse
	 */
	protected function loadContactInformationFromApiResult($user, $lookupMyAccountInfoResponse) {
		$lastName = $lookupMyAccountInfoResponse->fields->lastName;
		$firstName = $lookupMyAccountInfoResponse->fields->firstName;

		$fullName = $lastName . ', ' . $firstName;

		$user->_fullname = isset($fullName) ? $fullName : '';

		if (isset($lookupMyAccountInfoResponse->fields->preferredName)) {
			$user->_preferredName = $lookupMyAccountInfoResponse->fields->preferredName;
		} else {
			$user->_preferredName = "";
		}

		$Address1 = "";
		$City = "";
		$State = "";
		$Zip = "";

		$homeLibrary = $user->getHomeLibrary();
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
						$State = $fields->data;
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
								[
									$City,
									$State,
								] = explode(' ', $cityState);
							}
						} else {
							if (empty($City)) {
								$City = '';
							}
							if (empty($State)) {
								$State = '';
							}
						}
						break;
					case 'ZIP' :
						$Zip = $fields->data;
						break;
					// If the library does not use the PHONE field, set $user->phone to DAYPHONE or HOMEPHONE
					/** @noinspection SpellCheckingInspection */
					case 'DAYPHONE' :
						$dayPhone = $fields->data;
						$user->phone = $dayPhone;
						break;
					/** @noinspection SpellCheckingInspection */
					case 'HOMEPHONE' :
						$homePhone = $fields->data;
						$user->phone = $homePhone;
						break;
					case 'PHONE' :
						$phone = $fields->data;
						$user->phone = $phone;
						break;
					case 'CELLPHONE' :
						$cellphone = $fields->data;
						$user->_mobileNumber = $cellphone;
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
			[
				$yearExp,
				$monthExp,
				$dayExp,
			] = explode("-", $user->_expires);
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
		$user->_billingNotices = '-';
		// Get notice and billing notice preferences out of patron categories (for CLEVNET)
		if (isset($homeLibrary->symphonyNoticeCategoryNumber)) {
			$noticeCategoryField = "category" . $homeLibrary->symphonyNoticeCategoryNumber;
			if (isset($lookupMyAccountInfoResponse->fields->$noticeCategoryField->key)) {
				$user->_notices = $lookupMyAccountInfoResponse->fields->$noticeCategoryField->key;
			}
		}
		if (isset($homeLibrary->symphonyBillingNoticeCategoryNumber)) {
			$billingNoticeCategoryField = "category" . $homeLibrary->symphonyBillingNoticeCategoryNumber;
			if (isset($lookupMyAccountInfoResponse->fields->$billingNoticeCategoryField->key)) {
				$user->_billingNotices = $lookupMyAccountInfoResponse->fields->$billingNoticeCategoryField->key;
			}
		}
		$user->_noticePreferenceLabel = 'Email';
		$user->_web_note = '';
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings(): bool {
		return true;
	}

	/**
	 * @param User $patron
	 * @return string
	 */
	public function getMessagingSettingsTemplate(User $patron): ?string {
		global $interface;
		$webServiceURL = $this->getWebServiceURL();
		$staffSessionToken = $this->getStaffSessionToken();
		if (!empty($staffSessionToken)) {
			$defaultCountryCode = '';
			$getCountryCodesResponse = $this->getWebServiceResponse('getMessagingSettings', $webServiceURL . '/policy/countryCode/simpleQuery?key=*', null, $staffSessionToken);
			$countryCodes = [];
			foreach ($getCountryCodesResponse as $countryCodeInfo) {
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
					'generalAnnouncements' => false,
				];
			}

			//Get a list of phone numbers for the patron from the APIs.
			$includeFields = urlencode("phoneList{*}");
			$getPhoneListResponse = $this->getWebServiceResponse('getPhoneList', $webServiceURL . "/user/patron/key/{$patron->unique_ils_id}?includeFields=$includeFields", null, $staffSessionToken);

			if ($getPhoneListResponse != null) {
				foreach ($getPhoneListResponse->fields->phoneList as $index => $phoneInfo) {
					$phoneList[(int)$index + 1] = [
						'enabled' => true,
						'key' => $phoneInfo->key,
						'label' => $phoneInfo->fields->label,
						'countryCode' => $phoneInfo->fields->countryCode->key,
						'number' => $phoneInfo->fields->number,
						'billNotices' => $phoneInfo->fields->bills,
						'overdueNotices' => $phoneInfo->fields->overdues,
						'holdPickupNotices' => $phoneInfo->fields->holds,
						'manualMessages' => $phoneInfo->fields->manual,
						'generalAnnouncements' => $phoneInfo->fields->general,
					];
				}
			}
			$interface->assign('phoneList', $phoneList);
			$interface->assign('numActivePhoneNumbers', 1);

			//Get a list of valid country codes
		} else {
			$interface->assign('error', 'Could not load messaging settings.');
		}

		$library = $patron->getHomeLibrary();
		if ($library->allowProfileUpdates) {
			$interface->assign('canSave', true);
		} else {
			$interface->assign('canSave', false);
		}

		return 'symphonyMessagingSettings.tpl';
	}

	public function processMessagingSettingsForm(User $patron): array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$result = [
			'success' => false,
			'message' => 'Unknown error processing messaging settings.',
		];
		$staffSessionToken = $this->getStaffSessionToken();
		$includeFields = urlencode("phoneList{*}");
		$webServiceURL = $this->getWebServiceURL();
		$getPhoneListResponse = $this->getWebServiceResponse('GET', $webServiceURL . "/user/patron/key/$patron->unique_ils_id?includeFields=$includeFields", null, $staffSessionToken);

		for ($i = 1; $i <= 5; $i++) {
			$deletePhoneKey = $_REQUEST['phoneNumberDeleted'][$i] == true;
			if (empty($_REQUEST['phoneNumber'][$i])) {
				$deletePhoneKey = true;
			}
			$phoneKey = $_REQUEST['phoneNumberKey'][$i];
			if ($deletePhoneKey) {
				if (!empty($phoneKey)) {
					foreach ($getPhoneListResponse->fields->phoneList as $index => $phoneInfo) {
						if ($phoneInfo->key == $phoneKey) {
							unset ($getPhoneListResponse->fields->phoneList[$index]);
							break;
						}
					}
				}
			} else {
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
				$phoneToModify->fields->patron->key = $patron->unique_ils_id;
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

				if ($phoneIndexToModify == -1) {
					$getPhoneListResponse->fields->phoneList[] = $phoneToModify;
				} else {
					$getPhoneListResponse->fields->phoneList[$phoneIndexToModify] = $phoneToModify;
				}
			}
		}
		//Compact the array
		$getPhoneListResponse->fields->phoneList = array_values($getPhoneListResponse->fields->phoneList);

		$updateAccountInfoResponse = $this->getWebServiceResponse('processMessagingSettings', $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields, $getPhoneListResponse, $staffSessionToken, 'PUT');
		if (isset($updateAccountInfoResponse->messageList)) {
			$result['message'] = '';
			foreach ($updateAccountInfoResponse->messageList as $message) {
				if (strlen($result['message']) > 0) {
					$result['message'] .= '<br/>';
				}
				$result['message'] = $message->message;
			}
			if (strlen($result['message']) == 0) {
				$result['message'] = 'Unknown error processing messaging settings.';
			}
		} else {
			$result['success'] = true;
			$result['message'] = 'Your account was updated successfully.';
		}
		return $result;
	}

	public function hasNativeReadingHistory(): bool {
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
	public function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut") {
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
				if ($keepCircHistory == 'ALLCHARGES') {
					$historyActive = true;
				} elseif ($keepCircHistory == 'NOHISTORY') {
					$historyActive = false;
				} elseif ($keepCircHistory == 'CIRCRULE') {
					$historyActive = !empty($getCircHistoryResponse->fields->circRecordList);
				} else {
					global $logger;
					$logger->log('Unknown keepCircHistory value: ' . $keepCircHistory, Logger::LOG_DEBUG);
				}
				if ($historyActive) {
					$readingHistoryTitles = [];
					$systemVariables = SystemVariables::getSystemVariables();
					global $aspen_db;
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

					foreach ($getCircHistoryResponse->fields->circHistoryRecordList as $circEntry) {
						$historyEntry = [];
						$shortId = $circEntry->fields->bib->key;
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
							if ($systemVariables->storeRecordDetailsInDatabase) {
								/** @noinspection SqlResolve */
								$getRecordDetailsQuery = 'SELECT permanent_id, indexed_format.format, recordIdentifier FROM grouped_work_records
								  LEFT JOIN grouped_work ON groupedWorkId = grouped_work.id
								  LEFT JOIN indexed_record_source ON sourceId = indexed_record_source.id
								  LEFT JOIN indexed_format on formatId = indexed_format.id
								  where source = ' . $aspen_db->quote($this->accountProfile->recordSource) . ' AND (recordIdentifier = ' . $aspen_db->quote('a' . $shortId) . ' OR recordIdentifier = ' . $aspen_db->quote('u' . $shortId) . ')';
								$results = $aspen_db->query($getRecordDetailsQuery, PDO::FETCH_ASSOC);
								if ($results) {
									$result = $results->fetch();
									if ($result) {
										$historyEntry['id'] = $result['recordIdentifier'];
										$historyEntry['shortId'] = $result['recordIdentifier'];
										$historyEntry['recordId'] = $result['recordIdentifier'];
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
							} else {
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
						} else {
							continue;
						}
						$readingHistoryTitles[] = $historyEntry;
					}
				}
			}
		}
		return [
			'historyActive' => $historyActive,
			'titles' => $readingHistoryTitles,
			'numTitles' => count($readingHistoryTitles),
		];
	}

	public function performsReadingHistoryUpdatesOfILS() : bool {
		return true;
	}

	public function doReadingHistoryAction(User $patron, string $action, array $selectedTitles) : void {
		if ($action == 'optIn' || $action == 'optOut') {
			$sessionToken = $this->getStaffSessionToken();
			if ($sessionToken) {
				$webServiceURL = $this->getWebServiceURL();
				if ($userID = $patron->unique_ils_id) {
					//To update the patron, we need to load the patron from Symphony so we only overwrite changed values.
					$updatePatronInfoParametersClass = $this->getWebServiceResponse('getPatronInformation', $this->getWebServiceURL() . '/user/patron/key/' . $userID . '?includeFields=*,preferredAddress,preferredName,address1,address2,address3', null, $sessionToken);
					if ($updatePatronInfoParametersClass) {
						//Convert from stdClass to associative array
						$updatePatronInfoParameters = json_decode(json_encode($updatePatronInfoParametersClass), true);
						if ($action == 'optOut') {
							$updatePatronInfoParameters['fields']['keepCircHistory'] = 'NOHISTORY';
						} elseif ($action == 'optIn') {
							$updatePatronInfoParameters['fields']['keepCircHistory'] = 'ALLCHARGES';
						}

						$updateAccountInfoResponse = $this->getWebServiceResponse('updateReadingHistory', $webServiceURL . '/user/patron/key/' . $userID . '?includeFields=*,preferredAddress,preferredName,address1,address2,address3', $updatePatronInfoParameters, $sessionToken, 'PUT');

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

	public function completeFinePayment(User $patron, UserPayment $payment) {
		$result = [
			'success' => false,
			'message' => '',
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
				[
					$fineId,
					$paymentAmount,
				] = explode('|', $finePayment);
				$creditRequestBody = [
					'blockKey' => str_replace('_', ':', $fineId),
					'amount' => [
						'amount' => $paymentAmount,
						'currencyCode' => $currencyCode,
					],
					'paymentType' => [
						'resource' => '/policy/paymentType',
						'key' => $paymentType,
					],
					//We could include the actual transaction id from the processor, but it's limited to 30 chars so we can just use Aspen ID.
					'vendorTransactionID' => (string)$payment->id,
					'creditReason' => [
						'resource' => '/policy/creditReason',
						'key' => 'PAYMENT',
					],
				];
				$postCreditResponse = $this->getWebServiceResponse('addPayment', $this->getWebServiceURL() . '/circulation/block/addPayment', $creditRequestBody, $sessionToken, 'POST');
				if (isset($postCreditResponse->messageList)) {
					$messages = [];
					foreach ($postCreditResponse->messageList as $message) {
						$messages[] = $message->message;
					}
					$result['message'] = implode("<br/>", $messages);
					$allPaymentsSucceed = false;
				}
			}
			$result['success'] = $allPaymentsSucceed;
		} else {
			$result['message'] = 'Could not connect to Symphony APIs';
		}

		global $logger;
		$logger->log("Marked fines as paid within Symphony for user {$patron->id}, {$result['message']}", Logger::LOG_ERROR);

		return $result;
	}

	public function getSelfRegistrationTerms() {
		global $library;

		if (!empty($library->selfRegistrationFormId)) {
			require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationForm.php';
			$selfRegistrationForm = new SelfRegistrationForm();
			$selfRegistrationForm->id = $library->selfRegistrationFormId;
			if ($selfRegistrationForm->find(true)) {
				$tosId = $selfRegistrationForm->termsOfServiceSetting;
				require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationTerms.php';
				$tos = new SelfRegistrationTerms();
				$tos->id = $tosId;
				if ($tosId != -1){
					if ($tos->find(true)) {
						return $tos;
					}
				}
			}
			return null;
		}
		return null;
	}
	public function getSelfRegistrationFields() {
		global $library;

		$pickupLocations = [];
		$location = new Location();
		//0 = no restrictions (ignore location setting)
		if ($library->selfRegistrationLocationRestrictions == 1) {
			//All Library Locations (ignore location setting)
			$location->libraryId = $library->libraryId;
		} elseif ($library->selfRegistrationLocationRestrictions == 2) {
			//Valid pickup locations
			$location->whereAdd('validSelfRegistrationBranch <> 2');
			$location->orderBy('isMainBranch DESC, displayName');
		} elseif ($library->selfRegistrationLocationRestrictions == 3) {
			//Valid pickup locations
			$location->libraryId = $library->libraryId;
			$location->whereAdd('validSelfRegistrationBranch <> 2');
			$location->orderBy('isMainBranch DESC, displayName');
		}
		if ($location->find()) {
			while ($location->fetch()) {
				$pickupLocations[$location->code] = $location->displayName;
			}
			if (count($pickupLocations) > 1) {
				array_unshift($pickupLocations, translate([
					'text' => 'Please select a location',
					'isPublicFacing' => true,
				]));
			}
		}

		global $library;
		$hasCustomSelfRegistrationFrom = false;

		if (!empty($library->selfRegistrationFormId)) {
			require_once ROOT_DIR . '/sys/SelfRegistrationForms/SelfRegistrationForm.php';
			$selfRegistrationForm = new SelfRegistrationForm();
			$selfRegistrationForm->id = $library->selfRegistrationFormId;
			if ($selfRegistrationForm->find(true)) {
				$customFields = $selfRegistrationForm->getFields();
				if ($customFields != null && count($customFields) > 0) {
					$hasCustomSelfRegistrationFrom = true;
				}
			}
		}

		$pickupLocationField = [
			'property' => 'pickupLocation',
			'type' => 'enum',
			'label' => 'Home Library',
			'description' => 'Please choose the Library location you would prefer to use',
			'values' => $pickupLocations,
			'required' => true,
		];

		$fields = [];
		if ($hasCustomSelfRegistrationFrom) {
			$hiddenDefault = false;
			if ($selfRegistrationForm->promptForParentInSelfReg){
				$fields[] = [
					'property' => 'cardType',
					'type' => 'enum',
					'values' => [
						'adult' => 'Adult (18 and Over)',
						'minor' => 'Minor (Under 18)',
					],
					'label' => 'Type of Card',
					'onchange' => 'AspenDiscovery.Account.updateSelfRegistrationFields()',
				];
				$hiddenDefault = true;
			}
			$fields['librarySection'] = [
				'property' => 'librarySection',
				'type' => 'section',
				'label' => 'Library',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$fields['identitySection'] = [
				'property' => 'identitySection',
				'type' => 'section',
				'label' => 'Identity',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$fields['mainAddressSection'] = [
				'property' => 'mainAddressSection',
				'type' => 'section',
				'label' => 'Main Address',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			$fields['contactInformationSection'] = [
				'property' => 'contactInformationSection',
				'type' => 'section',
				'label' => 'Contact Information',
				'hideInLists' => true,
				'expandByDefault' => true,
				'properties' => [],
			];
			//Use self registration fields
			/** @var SelfRegistrationFormValues $customField */
			foreach ($customFields as $customField) {
				if ($customField->ilsName == 'library') {
					if (count($pickupLocations) == 1) {
						$fields['librarySection'] = [
							'property' => 'librarySection',
							'type' => 'section',
							'label' => 'Library',
							'hideInLists' => true,
							'expandByDefault' => true,
							'properties' => [
								$customField->ilsName => $pickupLocationField,
							],
							'hiddenByDefault' => true,
						];
					} else {
						$fields['librarySection'] = [
							'property' => 'librarySection',
							'type' => 'section',
							'label' => 'Library',
							'hideInLists' => true,
							'expandByDefault' => true,
							'properties' => [
								$customField->ilsName => $pickupLocationField,
							],
						];
					}
				} elseif (($customField->ilsName == 'parentname' || $customField->ilsName == 'guardian' || $customField->ilsName == 'care_of' || $customField->ilsName == 'careof')) {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => $customField->fieldType,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note,
						'hiddenByDefault' => $hiddenDefault,
					];
				} elseif ($customField->ilsName == 'cellPhone' && $selfRegistrationForm->promptForSMSNoticesInSelfReg) {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => $customField->fieldType,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note,
						'hiddenByDefault' => true,
					];
					$fields[$customField->section]['properties']['SMS Notices'] = [
						'property' => 'smsNotices',
						'type' => 'checkbox',
						'label' => 'Receive notices via text',
						'onchange' => 'AspenDiscovery.Account.updateSelfRegistrationFields()',
					];
				} elseif ($customField->ilsName == "email"){
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => 'email',
						'label' => $customField->displayName,
						'maxLength' => 128,
						'required' => $customField->required,
						'note' => $customField->note,
						'autocomplete' => false,
					];
					$fields[$customField->section]['properties']['email2'] = [
							'property' => 'email2',
							'type' => 'email2',
							'label' => 'Confirm Email',
							'maxLength' => 128,
							'required' => $customField->required,
							'autocomplete' => false,
					];
				} elseif ($customField->ilsName == 'zip' && !empty($library->validSelfRegistrationZipCodes)) {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => $customField->fieldType,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note,
						'validationPattern' => $library->validSelfRegistrationZipCodes,
						'validationMessage' => translate([
							'text' => 'Please enter a valid zip code',
							'isPublicFacing' => true,
						]),
					];
				} elseif ($customField->ilsName == 'state') {
					if (!empty($library->validSelfRegistrationStates)){
						$validStates = explode('|', $library->validSelfRegistrationStates);
						$validStates = array_combine($validStates, $validStates);
						$fields[$customField->section]['properties'][] = [
							'property' => $customField->ilsName,
							'type' => 'enum',
							'values' => $validStates,
							'label' => $customField->displayName,
							'required' => $customField->required,
							'note' => $customField->note,
						];
					} else {
						$fields[$customField->section]['properties'][] = [
							'property' => $customField->ilsName,
							'type' => $customField->fieldType,
							'label' => $customField->displayName,
							'required' => $customField->required,
							'note' => $customField->note,
							'maxLength' => 2,
						];
					}
				} else {
					$fields[$customField->section]['properties'][] = [
						'property' => $customField->ilsName,
						'type' => $customField->fieldType,
						'label' => $customField->displayName,
						'required' => $customField->required,
						'note' => $customField->note
					];
				}
			}
			foreach ($fields as $section) {
				if ($section['type'] == 'section') {
					if (empty($section['properties'])) {
						unset ($fields[$section['property']]);
					}
				}
			}
		}
		return $fields;
	}

	private function getPatronFieldValue(string $value, $useAllCaps) {
		if ($useAllCaps) {
			return strtoupper($value);
		} else {
			return $value;
		}
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

	public function showHoldPosition(): bool {
		return true;
	}

	public function showHoldExpirationTime(): bool {
		return true;
	}

	/**
	 * Determine if volume level holds are always done when volumes are present.
	 * When this is on, items without volumes will present a blank volume for the user to choose from.
	 *
	 * @return false
	 */
	public function alwaysPlaceVolumeHoldWhenVolumesArePresent(): bool {
		return true;
	}

	public function showPreferredNameInProfile(): bool {
		return true;
	}

	public function allowUpdatesOfPreferredName(User $patron) : bool {
		return true;
	}

	public function suspendRequiresReactivationDate(): bool {
		return true;
	}

	public function showDateWhenSuspending(): bool {
		return true;
	}

	public function showOutDateInCheckouts(): bool {
		return true;
	}

	public function reactivateDateNotRequired(): bool {
		return true;
	}

	public function showTimesRenewed(): bool {
		return true;
	}

	public function showWaitListInCheckouts(): bool {
		return false;
	}

	public function showHoldPlacedDate(): bool {
		return true;
	}

	public function showDateInFines(): bool {
		return false;
	}

	public function hasAPICheckout() : bool {
		return true;
	}

	public function checkoutByAPI(User $patron, $barcode, Location $currentLocation): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'There was an error checking out this title.',
				'isPublicFacing' => true,
			]),
			'title' => translate([
				'text' => 'Unable to checkout title',
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Unable to checkout title',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'There was an error checking out this title.',
					'isPublicFacing' => true,
				]),
			],
			'itemData' => []
		];

		//Find the correct stat group to use
		$doCheckout = false;
		$addOverrideCode = false;

		//Use the current location for the item
		//To get the current location, we need to determine if the item is already on hold.
		//If it is, make sure it is on hold for the active user and use the pickup_location
		//If it is not on hold, use the current location for the item
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			return $result;
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();

		$lookupItemResponse = $this->getWebServiceResponse('lookupItem', $webServiceURL . '/catalog/item/barcode/' . $barcode, null, $sessionToken);
		if (empty($lookupItemResponse) || !empty($lookupItemResponse->messageList)) {
			$result['message'] = translate([
				'text' => 'Could not find an item with that barcode, unable to checkout item.',
				'isPublicFacing' => true,
			]);
			$result['api']['message'] = translate([
				'text' => 'Could not find an item with that barcode, unable to checkout item.',
				'isPublicFacing' => true,
			]);
		}else{
			require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
			$scoSettings = new AspenLiDASelfCheckSetting();
			$checkoutLocationSetting = $scoSettings->getCheckoutLocationSetting($currentLocation->code);

			if ($checkoutLocationSetting == 0) {
				//Use the active location, no change needed
				$doCheckout = true;
			}elseif ($checkoutLocationSetting == 1) {
				//Use home location for the user
				$currentLocation = $patron->getHomeLocation();
				$doCheckout = true;
			}else {
				$doCheckout = true;

				$currentItemLocation = $lookupItemResponse->fields->currentLocation->key;
				if ($currentItemLocation == 'CHECKEDOUT') {
					$result['message'] = translate([
						'text' => 'This title is already checked out, cannot check it out again.',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'This title is already checked out, cannot check it out again.',
						'isPublicFacing' => true,
					]);
					$doCheckout = false;
				}elseif ($currentItemLocation == 'HOLDS') {
					//The title is on the hold shelf, make sure it is on the hold shelf for the current patron
					$doCheckout = false;

					$itemKey = $lookupItemResponse->key;

					//Get holds for the patron
					$includeFields = urlencode("holdRecordList{*,bib{title,author},selectedItem{call{*},itemType{*},barcode}}");
					$patronHolds = $this->getWebServiceResponse('getHolds', $webServiceURL . '/user/patron/key/' . $patron->unique_ils_id . '?includeFields=' . $includeFields, null, $sessionToken);
					if ($patronHolds && isset($patronHolds->fields)) {
						foreach ($patronHolds->fields->holdRecordList as $hold) {
							if (isset($hold->fields->status)) {
								$holdStatus = strtolower($hold->fields->status);
								if ($holdStatus == "being_held") {
									$holdItemId = empty($hold->fields->item->key) ? '' : $hold->fields->item->key;
									if ($holdItemId == $itemKey) {
										$doCheckout = true;
										//For titles that are on hold, we need to add an override.
										$addOverrideCode = true;
										$curPickupBranch = new Location();
										$curPickupBranch->code = $hold->fields->pickupLibrary->key;
										if ($curPickupBranch->find(true)) {
											$currentLocation = $curPickupBranch;
										}else{
											//We didn't get a valid code, use the passed in location
										}
										break;
									}
								}
							}
						}
					}else{
						$doCheckout = true;
						$curPickupBranch = new Location();
						$curPickupBranch->code = $currentItemLocation;
						if ($curPickupBranch->find(true)) {
							$currentLocation = $curPickupBranch;
						}else{
							//We didn't get a valid code, use the passed in location
						}
					}

					if (!$doCheckout) {
						$result['message'] = translate([
							'text' => 'This title is on hold for another user or is not available yet and cannot be checked out.',
							'isPublicFacing' => true,
						]);
						$result['api']['message'] = translate([
							'text' => 'This title is on hold for another user or is not available yet and cannot be checked out.',
							'isPublicFacing' => true,
						]);
					}
				}
			}
		}

		//For patrons that have fines, but that are not at the fine threshold, we need to add an override.
		$totalFines = $patron->getTotalFines(false);
		if ($totalFines > 0) {
			$userProfileResponse = $this->getWebServiceResponse('getUserProfile', $webServiceURL . '/policy/userProfile/key/' . $patron->patronType, null, $sessionToken);
			if ($userProfileResponse) {
				if ($totalFines < $userProfileResponse->fields->billThreshold->amount) {
					$addOverrideCode = true;
				}else{
					$result['message'] = translate([
						'text' => 'Your account has too many fines to check out this title.',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'Your account has too many fines to check out this title.',
						'isPublicFacing' => true,
					]);
					$doCheckout = false;
				}
			}
		}

		if ($doCheckout) {
			$checkOutParams = [
				'itemBarcode' => $barcode,
				'patronBarcode' => $patron->ils_barcode
			];

			$additionalHeaders = [
				'SD-Preferred-Role: STAFF'
			];

			//For titles that are on hold, we need to add an override.
			if ($addOverrideCode && !empty($this->accountProfile->overrideCode)) {
				$additionalHeaders[] = 'SD-Prompt-Return: CKOBLOCKS/' . $this->accountProfile->overrideCode;
			}

			$checkOutResponse = $this->getWebServiceResponse('checkOutItem', $webServiceURL . '/circulation/circRecord/checkOut', $checkOutParams, $sessionToken, 'POST', $additionalHeaders, [], $currentLocation->code);

			if (!empty($checkOutResponse)) {
				$checkOutMessage = '';
				if (!empty($checkOutResponse->messageList)){
					foreach ($checkOutResponse->messageList as $message) {
						if (!empty($checkOutMessage)) {
							$checkOutMessage .= '<br/>';
						}else{
							$checkOutMessage .= translate([
								'text' => $message->message,
								'isPublicFacing' => true,
							]);
						}
					}
				}

				$result['message'] = $checkOutMessage;
				if ($this->lastWebServiceResponseCode == 200) {
					$result['success'] = true;
					$result['api']['title'] = translate([
						'text' => 'Check Out successful',
						'isPublicFacing' => true,
					]);

					$checkouts = $this->getCheckouts($patron);
					foreach ($checkouts as $checkout) {
						if ($checkout->barcode == $barcode) {
							$result['itemData'] = [
								'title' => $checkout->getTitle(),
								'due' => date('M j Y', $checkout->dueDate),
								'barcode' => $barcode,
							];
							break;
						}
					}
				}

				$result['api']['message'] = $checkOutMessage;
			}
		}

		return $result;
	}

	public function hasAPICheckIn() {
		return true;
	}

	public function checkInByAPI(User $patron, $barcode, Location $currentLocation): array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'There was an error checking in this title.',
				'isPublicFacing' => true,
			]),
			'title' => translate([
				'text' => 'Unable to check in title',
				'isPublicFacing' => true,
			]),
			'api' => [
				'title' => translate([
					'text' => 'Unable to check in title',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'There was an error checking in this title.',
					'isPublicFacing' => true,
				]),
			],
			'itemData' => []
		];

		//Find the item in the list of checkouts
		$checkouts = $this->getCheckouts($patron);
		foreach ($checkouts as $checkout) {
			if ($checkout->barcode == $barcode) {
				$result['itemData'] = [
					'title' => $checkout->getTitle(),
					'barcode' => $barcode,
				];
				break;
			}
		}

		//Find the correct stat group to use
		$doCheckout = false;
		require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
		$scoSettings = new AspenLiDASelfCheckSetting();
		$checkInLocationSetting = $scoSettings->getCheckoutLocationSetting($currentLocation->code);
		if ($checkInLocationSetting == 0) {
			//Use the active location, no change needed
			$doCheckIn = true;
		}elseif ($checkInLocationSetting == 1) {
			//Use home location for the user
			$currentLocation = $patron->getHomeLocation();
			$doCheckIn = true;
		}else {
			$doCheckIn = true;
		}

		if ($doCheckIn) {
			$sessionToken = $this->getStaffSessionToken();
			if (!$sessionToken) {
				return $result;
			}

			//Now that we have the session token, get holds information
			$webServiceURL = $this->getWebServiceURL();

			$lookupItemResponse = $this->getWebServiceResponse('lookupItem', $webServiceURL . '/catalog/item/barcode/' . $barcode, null, $sessionToken);
			if (!empty($lookupItemResponse)) {
				$currentLocation = $lookupItemResponse->fields->currentLocation->key;
				if ($currentLocation !== 'CHECKEDOUT') {
					$result['message'] = translate([
						'text' => 'This title is not currently checked out. Cannot check it in.',
						'isPublicFacing' => true,
					]);
					$result['api']['message'] = translate([
						'text' => 'This title is not currently checked out. Cannot check it in.',
						'isPublicFacing' => true,
					]);
				}else{
					$checkInParams = [
						'itemBarcode' => $barcode
					];

					$additionalHeaders = [];

					$checkInResponse = $this->getWebServiceResponse('checkInItem', $webServiceURL . '/circulation/circRecord/checkIn', $checkInParams, $sessionToken, 'POST', $additionalHeaders);

					if (!empty($checkInResponse)) {
						$checkInMessage = '';
						if (!empty($checkInResponse->messageList)) {
							foreach ($checkInResponse->messageList as $message) {
								if (!empty($checkInMessage)) {
									$checkInMessage .= '<br/>';
								} else {
									$checkInMessage .= translate([
										'text' => $message->message,
										'isPublicFacing' => true,
									]);
								}
							}
						}

						$result['message'] = $checkInMessage;
						if ($this->lastWebServiceResponseCode == 200) {
							$result['success'] = true;
							$result['api']['title'] = translate([
								'text' => 'Check in successful',
								'isPublicFacing' => true,
							]);
						}

						$result['api']['message'] = $checkInMessage;
					}
				}
			}
		}

		return $result;
	}

	public function describePath(string $path) : ?stdClass {
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			return null;
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		return $this->getWebServiceResponse('describePath', "$webServiceURL/$path/describe", null, $sessionToken);
	}

	public function getRequest(string $path): null|stdClass|array {
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			return null;
		}

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		return $this->getWebServiceResponse('getRequest', "$webServiceURL/$path", null, $sessionToken);
	}

	public function loadLocations() : array {
		//Get a list of locations for the system.
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			return [
				'success' => false,
				'message' => 'Unable to authenticate with Symphony'
			];
		}

		global $library;
		$numAdded = 0;

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		$allLocationsResponse = $this->getWebServiceResponse('getAllLibraries', "$webServiceURL/policy/library/simpleQuery?libraryNumber=*", null, $sessionToken);
		foreach ($allLocationsResponse as $locationInfo) {
			$locationCode = $locationInfo->key;
			$location = new Location();
			$location->code = $locationCode;
			if (!$location->find(true)) {
				//This location has not been added to the database yet.
				$location = new Location();
				$location->code = $locationCode;
				$location->displayName = $locationInfo->fields->description;
				$location->createSearchInterface = 1;
				$location->showInSelectInterface = 1;
				$location->enableAppAccess = 1;
				$location->theme = -1;
				$location->useLibraryThemes = 1;
				$location->languageAndDisplayInHeader = 1;
				$location->displayExploreMoreBarInSummon = 1;
				$location->displayExploreMoreBarInEbscoEds = 1;
				$location->displayExploreMoreBarInEbscoHost = 1;
				$location->displayExploreMoreBarInCatalogSearch = 1;
				$location->showInLocationsAndHoursList = 1;
				$location->validHoldPickupBranch = 1;
				$location->validSelfRegistrationBranch = 1;
				$location->showHoldButton = 1;
				$location->publicListsToInclude = 4;
				$location->automaticTimeoutLength = 90;
				$location->automaticTimeoutLengthLoggedOut = 450;
				$location->showEmailThis = 1;
				$location->showShareOnExternalSites = 1;
				$location->showFavorites = 1;
				$location->includeLibraryRecordsToInclude = 1;

				$recordToInclude = new LocationRecordToInclude();
				$recordToInclude->indexingProfileId = $this->getIndexingProfile()->id;
				$recordToInclude->markRecordsAsOwned = 1;
				$recordToInclude->location = $locationCode;
				$recordsToInclude = [$recordToInclude];

				//Figure out the library for the location
				$libraryForLocation = new Library();
				$libraryForLocation->ilsCode = $locationInfo->fields->holdGroup->key;
				if ($libraryForLocation->find(true)){
					$location->libraryId = $libraryForLocation->libraryId;
				}else{
					$location->libraryId = $library->libraryId;
				}

				$location->__set('recordsToInclude', $recordsToInclude);

				$location->insert();
				$numAdded++;
			}
		}
		return [
			'success' => true,
			'message' => translate(['text' => 'Added %1% locations from Symphony.', 1=>$numAdded, 'isAdminFacing' => true])
		];
	}

	public function loadHoldGroups() : array {
		//Get a list of locations for the system.
		$sessionToken = $this->getStaffSessionToken();
		if (!$sessionToken) {
			return [
				'success' => false,
				'message' => 'Unable to authenticate with Symphony'
			];
		}

		$numGroupsAdded = 0;
		$numLocationsAdded = 0;

		//Now that we have the session token, get holds information
		$webServiceURL = $this->getWebServiceURL();
		$allLocationsResponse = $this->getWebServiceResponse('getAllLibraries', "$webServiceURL/policy/library/simpleQuery?libraryNumber=*", null, $sessionToken);
		foreach ($allLocationsResponse as $locationInfo) {
			$locationCode = $locationInfo->key;
			$holdGroupKey = $locationInfo->fields->holdGroup->key;
			require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroup.php';
			$holdGroup = new HoldGroup();
			$holdGroup->name = $holdGroupKey;
			if (!$holdGroup->find(true)){
				$holdGroup->insert();
				$numGroupsAdded++;
			}

			//Check to see if the location is part of the hold group
			$holdGroupLocations = $holdGroup->getLocationCodes();
			if (!in_array($locationCode, $holdGroupLocations)){
				$location = new Location();
				$location->code = $locationCode;
				if ($location->find(true)) {
					$locationIds = $holdGroup->getLocations();
					$locationIds[] = $location->locationId;
					$holdGroup->setLocations($locationIds);
					$holdGroup->update();
					$numLocationsAdded++;
				}
			}
		}
		return [
			'success' => true,
			'message' => translate(['text' => 'Added %1% groups from Symphony and %2% locations.', 1=>$numGroupsAdded, 2=>$numLocationsAdded, 'isAdminFacing' => true])
		];
	}

	public function supportsLocalIllRequests() : bool {
		return true;
	}

	public function submitLocalIllRequest(User $patron, LocalIllForm $localIllForm) : array {
		$result = [
			'success' => false,
			'title' => translate(['text' => 'An error occurred', 'isPublicFacing' => true]),
			'message' => translate(['text' => 'There was an unknown error placing your request', 'isPublicFacing' => true]),
		];
		$userHomeLocation = $patron->getHomeLocation();
		if ($userHomeLocation->getParentLibrary()->localIllRequestType == 1) {
			//Get values for the request
			$catalogKey = $_REQUEST['catalogKey'] ?? '';
			$acceptFee = $_REQUEST['acceptFee'] ?? false;
			$pickupLocation = $_REQUEST['pickupLocation'] ?? '';
			$volumeId = $_REQUEST['volumeId'] ?? '';
			$okToProcess = true;
			if ($localIllForm ->requireAcceptFee) {
				if (empty($acceptFee) || $acceptFee == 'false') {
					$result['message'] = translate(['text' => 'You must agree to pay any fees associated with this request before continuing.', 'isPublicFacing' => true]);
					$okToProcess = false;
				}
			}
			if (empty($catalogKey)) {
				$result['message'] = translate(['text' => 'Could not find the title to place a hold on.', 'isPublicFacing' => true]);
				$okToProcess = false;
			}
			if (empty($pickupLocation)) {
				$result['message'] = translate(['text' => 'Please provide the location where you would like to pickup this request.', 'isPublicFacing' => true]);
				$okToProcess = false;
			}
			if ($okToProcess) {
				$forceVolumeHold = false;
				$itemId = '';
				if (array_key_exists('volumeSelected', $_REQUEST)) {
					$forceVolumeHold = boolval($_REQUEST['volumeSelected']);
					if (empty($volumeId)) {
						//To place a volume hold on a blank volume, we need to find an item without a volume, preferably owned by this system.
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$marcRecord = new MarcRecordDriver($this->getIndexingProfile()->name . ':' . $catalogKey);
						$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
						$itemIdToUse = null;
						//Check all items to get the item id we want
						foreach ($relatedRecord->getItems() as $item) {
							//we only care about items with no volume
							if (empty($item->volume) && !$item->isEContent) {
								if ($item->libraryOwned || $item->locallyOwned) {
									$itemId = $item->itemId;
									break;
								}elseif ($itemIdToUse == null){
									$itemId = $item->itemId;
								}
							}
						}
					}
				}
				$holdResult = $this->placeSirsiHold($patron, $catalogKey, $itemId, $volumeId, $pickupLocation, 'request', null, $forceVolumeHold, true);
				if ($holdResult['success']) {
					$result['success'] = true;
					$result['title'] = translate(['text' => 'Success', 'isPublicFacing' => true]);
					$result['message'] = translate(['text' => 'Your request was placed successfully.', 'isPublicFacing' => true]);
				}else{
					$result = $holdResult;
				}
			}
		}else{
			$result['message'] = translate(['text' => 'The specified ILL Request Type is not supported by this driver.', 'isPublicFacing' => true]);
		}
		return $result;
	}
}
