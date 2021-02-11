<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class Koha extends AbstractIlsDriver
{
	private $dbConnection = null;

	/** @var CurlWrapper */
	private $curlWrapper;
	/** @var CurlWrapper */
	private $apiCurlWrapper;
	/** @var CurlWrapper */
	private $opacCurlWrapper;

	static $fineTypeTranslations = [
		'A' => 'Account management fee',
		'C' => 'Credit',
		'F' => 'Overdue fine',
		'FOR' => 'Forgiven',
		'FU' => 'Overdue, still accruing',
		'L' => 'Lost',
		'LR' => 'Lost item returned/refunded',
		'M' => 'Sundry',
		'N' => 'New card',
		'PAY' => 'Payment',
		'W' => 'Writeoff'
	];

	function updateHomeLibrary(User $patron, string $homeLibraryCode)
	{
		$result = [
			'success' => false,
			'messages' => []
		];
		//Load required fields from Koha here to make sure we don't wipe them out
		/** @noinspection SqlResolve */
		$sql = "SELECT address, city FROM borrowers where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		$address = '';
		$city = '';
		if ($results !== false) {
			while ($curRow = $results->fetch_assoc()) {
				$address = $curRow['address'];
				$city = $curRow['city'];
			}
		}

		$postVariables = [
			'surname' => $patron->lastname,
			'address' => $address,
			'city' => $city,
			'library_id' => $homeLibraryCode,
			'category_id' => $patron->patronType,
		];
		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['messages'][] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$patron->username}";
			$postParams = json_encode($postVariables);

			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json;charset=UTF-8',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
			], true);
			$this->apiCurlWrapper->setupDebugging();
			$response = $this->apiCurlWrapper->curlSendPage($apiUrl, 'PUT', $postParams);
			if ($this->apiCurlWrapper->getResponseCode() != 200) {
				if (strlen($response) > 0) {
					$jsonResponse = json_decode($response);
					if ($jsonResponse) {
						if (!empty($jsonResponse->error)) {
							$result['messages'][] = $jsonResponse->error;
						}else{
							foreach ($jsonResponse->errors as $error) {
								$result['messages'][] = $error->message;
							}
						}
					} else {
						$result['messages'][] = $response;
					}
				} else {
					$result['messages'][] = "Error {$this->apiCurlWrapper->getResponseCode()} updating your account.";
				}
			} else {
				$result['success'] = true;
				$result['messages'][] = 'Your pickup location was updated successfully.';
			}
		}

		return $result;
	}

	/**
	 * @param User $patron The User Object to make updates to
	 * @param boolean $canUpdateContactInfo Permission check that updating is allowed
	 * @return array                  Array of error messages for errors that occurred
	 */
	function updatePatronInfo($patron, $canUpdateContactInfo)
	{
		$result = [
			'success' => false,
			'messages' => []
		];
		if (!$canUpdateContactInfo) {
			$result['messages'][] = "Profile Information can not be updated.";
		} else {
			global $library;
			if ($library->bypassReviewQueueWhenUpdatingProfile) {
				//This method does not use the review queue
				//Load required fields from Koha here to make sure we don't wipe them out
				/** @noinspection SqlResolve */
				$sql = "SELECT address, city FROM borrowers where borrowernumber = {$patron->username}";
				$results = mysqli_query($this->dbConnection, $sql);
				$address = '';
				$city = '';
				if ($results !== false) {
					while ($curRow = $results->fetch_assoc()) {
						$address = $curRow['address'];
						$city = $curRow['city'];
					}
				}

				$postVariables = [
					'surname' => $patron->lastname,
					'address' => $address,
					'city' => $city,
					'library_id' => Location::getUserHomeLocation()->code,
					'category_id' => $patron->patronType
				];

				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'address', 'borrower_address', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'address', 'borrower_address', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'address2', 'borrower_address2', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_address', 'borrower_B_address', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_address2', 'borrower_B_address2', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_city', 'borrower_B_city', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_country', 'borrower_B_country', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_email', 'borrower_B_email', $library->useAllCapsWhenUpdatingProfile);
					//altaddress_notes
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_phone', 'borrower_B_phone', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_postal_code', 'borrower_B_zipcode', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altaddress_state', 'borrower_B_state', $library->useAllCapsWhenUpdatingProfile);
					//altaddress_street_number
					//altaddress_street_type
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_address', 'borrower_altcontactaddress1', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_address2', 'borrower_altcontactaddress2', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_city', 'borrower_altcontactaddress3', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_country', 'borrower_altcontactcountry', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_firstname', 'borrower_altcontactfirstname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_phone', 'borrower_altcontactphone', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_postal_code', 'borrower_altcontactzipcode', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_state', 'borrower_altcontactstate', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'altcontact_surname', 'borrower_altcontactsurname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'city', 'borrower_city', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'country', 'borrower_country', $library->useAllCapsWhenUpdatingProfile);
				if (isset($_REQUEST['borrower_dateofbirth'])) {
					$postVariables['date_of_birth'] = $this->aspenDateToKohaApiDate($_REQUEST['borrower_dateofbirth']);
				}
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'email', 'borrower_email', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'fax', 'borrower_fax', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'firstname', 'borrower_firstname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'gender', 'borrower_sex', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'initials', 'borrower_initials', $library->useAllCapsWhenUpdatingProfile);
				if (!isset($_REQUEST['library_id']) || $_REQUEST['library_id'] == -1){
					$postVariables['library_id'] = $patron->getHomeLocation()->code;
				}else {
					$postVariables = $this->setPostFieldWithDifferentName($postVariables, 'library_id', 'borrower_branchcode', $library->useAllCapsWhenUpdatingProfile);
				}
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'mobile', 'borrower_mobile', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'opac_notes', 'borrower_contactnote', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'other_name', 'borrower_othernames', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'phone', 'borrower_phone', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'postal_code', 'borrower_zipcode', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'secondary_email', 'borrower_emailpro', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'secondary_phone', 'borrower_phonepro', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'state', 'borrower_state', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'surname', 'borrower_surname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostFieldWithDifferentName($postVariables,'title', 'borrower_title', $library->useAllCapsWhenUpdatingProfile);

				$oauthToken = $this->getOAuthToken();
				if ($oauthToken == false) {
					$result['messages'][] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
				} else {
					$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$patron->username}";
					$postParams = json_encode($postVariables);

					$this->apiCurlWrapper->addCustomHeaders([
						'Authorization: Bearer ' . $oauthToken,
						'User-Agent: Aspen Discovery',
						'Accept: */*',
						'Cache-Control: no-cache',
						'Content-Type: application/json;charset=UTF-8',
						'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
					], true);
					$this->apiCurlWrapper->setupDebugging();
					$response = $this->apiCurlWrapper->curlSendPage($apiUrl, 'PUT', $postParams);
					if ($this->apiCurlWrapper->getResponseCode() != 200) {
						if (strlen($response) > 0) {
							$jsonResponse = json_decode($response);
							if ($jsonResponse) {
								if (!empty($jsonResponse->error)) {
									$result['messages'][] = $jsonResponse->error;
								}else{
									foreach ($jsonResponse->errors as $error) {
										$result['messages'][] = $error->message;
									}
								}
							} else {
								$result['messages'][] = $response;
							}
						} else {
							$result['messages'][] = "Error {$this->apiCurlWrapper->getResponseCode()} updating your account.";
						}

					} else {
						$result['success'] = true;
						$result['messages'][] = 'Your account was updated successfully.';
					}
				}
			} else {
				//This method does use the review queue
				$catalogUrl = $this->accountProfile->vendorOpacUrl;

				$this->loginToKohaOpac($patron);

				$updatePage = $this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl');
				//Get the csr token
				$csr_token = '';
				if (preg_match('%<input type="hidden" name="csrf_token" value="(.*?)" />%s', $updatePage, $matches)) {
					$csr_token = $matches[1];
				}

				$postVariables = [];
				if (!isset($_REQUEST['library_id']) || $_REQUEST['library_id'] == -1){
					$postVariables['borrower_branchcode'] = $patron->getHomeLocation()->code;
				}else {
					$postVariables = $this->setPostField($postVariables, 'borrower_branchcode', $library->useAllCapsWhenUpdatingProfile);
				}
				$postVariables = $this->setPostField($postVariables, 'borrower_title', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_surname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_firstname', $library->useAllCapsWhenUpdatingProfile);
				if (!empty($_REQUEST['borrower_dateofbirth'])){
					$postVariables['borrower_dateofbirth'] = $this->aspenDateToKohaDate($_REQUEST['borrower_dateofbirth']);
				}
				$postVariables = $this->setPostField($postVariables, 'borrower_initials', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_othernames', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_sex', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_address', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_address2', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_city', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_state', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_zipcode', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_country', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_phone', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_email', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_phonepro', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_mobile', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_emailpro', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_fax', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_address' , $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_address2', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_city', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_state', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_zipcode', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_country', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_phone', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_B_email', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_contactnote', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactsurname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactfirstname', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactaddress1', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactaddress2', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactaddress3', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactstate', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactzipcode', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactcountry', $library->useAllCapsWhenUpdatingProfile);
				$postVariables = $this->setPostField($postVariables, 'borrower_altcontactphone', $library->useAllCapsWhenUpdatingProfile, $library->requireNumericPhoneNumbersWhenUpdatingProfile);

				$postVariables['csrf_token'] = $csr_token;
				$postVariables['action'] = 'update';

				if (isset($_REQUEST['resendEmail'])) {
					$postVariables['resendEmail'] = strip_tags($_REQUEST['resendEmail']);
				}

				$postResults = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl', $postVariables);

				$messageInformation = [];
				if (preg_match('%<div class="alert alert-warning">(.*?)</div>%s', $postResults, $messageInformation)) {
					$error = $messageInformation[1];
					$error = str_replace('<h3>', '<h4>', $error);
					$error = str_replace('</h3>', '</h4>', $error);
					$result['messages'][] = trim($error);
				} elseif (preg_match('%<div class="alert alert-success">(.*?)</div>%s', $postResults, $messageInformation)) {
					$error = $messageInformation[1];
					$error = str_replace('<h3>', '<h4>', $error);
					$error = str_replace('</h3>', '</h4>', $error);
					$result['success'] = true;
					$result['messages'][] = trim($error);
				} elseif (preg_match('%<div class="alert">(.*?)</div>%s', $postResults, $messageInformation)) {
					$error = $messageInformation[1];
					$result['messages'][] = trim($error);
				}
			}
		}

		if ($result['success'] == false && empty($result['messages'])){
			$result['messages'][] = 'Unknown error updating your account';
		}
		return $result;
	}

	private $checkouts = array();

	public function getCheckouts($patron)
	{
		if (isset($this->checkouts[$patron->id])) {
			return $this->checkouts[$patron->id];
		}

		//Get checkouts by screen scraping
		$checkouts = array();

		$this->initDatabaseConnection();

		$opacRenewalAllowed = $this->getKohaSystemPreference('OpacRenewalAllowed');

		/** @noinspection SqlResolve */
		$sql = "SELECT issues.*, items.biblionumber, items.itype, items.itemcallnumber, items.enumchron, title, author, auto_renew, auto_renew_error from issues left join items on items.itemnumber = issues.itemnumber left join biblio ON items.biblionumber = biblio.biblionumber where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		while ($curRow = $results->fetch_assoc()) {
			$checkout = array();
			$checkout['checkoutSource'] = 'ILS';

			$checkout['id'] = $curRow['issue_id'];
			$checkout['recordId'] = $curRow['biblionumber'];
			$checkout['shortId'] = $curRow['biblionumber'];
			$checkout['title'] = $curRow['title'];
			if (isset($curRow['itemcallnumber'])) {
				$checkout['callNumber'] = $curRow['itemcallnumber'];
			}
			if (isset($curRow['enumchron'])) {
				$checkout['volume'] = $curRow['enumchron'];
			}

			$itemNumber = $curRow['itemnumber'];

			//Check to see if there is a volume for the checkout
			/** @noinspection SqlResolve */
			$volumeSql = "SELECT description from volume_items inner JOIN volumes on volume_id = volumes.id where itemnumber = $itemNumber";
			$volumeResults = mysqli_query($this->dbConnection, $volumeSql);
			if ($volumeResults !== false) { //This is false if Koha does not support volumes
				if ($volumeRow = $volumeResults->fetch_assoc()) {
					$checkout['volume'] = $volumeRow['description'];
				}
				$volumeResults->close();
			}

			//Check to see if the item is Claims Returned
			/** @noinspection SqlResolve */
			$claimsReturnedSql = "SELECT created_on from return_claims where issue_id = {$curRow['issue_id']}";
			$claimsReturnedResults = mysqli_query($this->dbConnection, $claimsReturnedSql);
			$checkout['return_claim'] = '';
			if ($claimsReturnedResults !== false) { //This is false if Koha does not support volumes
				if ($claimsReturnedResult = $claimsReturnedResults->fetch_assoc()) {
					try {
						$claimsReturnedDate = new DateTime($claimsReturnedResult['created_on']);
						$checkout['return_claim'] = translate(['text'=>'return_claim_message','defaultText'=> 'Title marked as returned on %1%, but the library is still processing', 1=>date_format($claimsReturnedDate, 'M j, Y')]);
					} catch (Exception $e) {
						global $logger;
						$logger->log("Error parsing claims returned info " . $claimsReturnedResult['created_on'] . " $e", Logger::LOG_ERROR);
					}
				}
				$claimsReturnedResults->close();
			}

			$checkout['author'] = $curRow['author'];

			$dateDue = DateTime::createFromFormat('Y-m-d H:i:s', $curRow['date_due']);
			if ($dateDue) {
				$renewalDate = $dateDue->format('D M jS');
				$checkout['renewalDate'] = $renewalDate;
				$dueTime = $dateDue->getTimestamp();
			} else {
				$renewalDate = 'Unknown';
				$dueTime = null;
			}
			$checkout['dueDate'] = $dueTime;
			$checkout['itemId'] = $itemNumber;
			$checkout['renewIndicator'] = $curRow['itemnumber'];
			$checkout['renewCount'] = $curRow['renewals'];

			$checkout['canRenew'] = !$curRow['auto_renew'] && $opacRenewalAllowed;
			$checkout['autoRenew'] = $curRow['auto_renew'];
			$autoRenewError = $curRow['auto_renew_error'];

			if ($autoRenewError) {
				if ($autoRenewError == 'on_reserve') {
					$autoRenewError = translate(['text' => 'koha_auto_renew_on_reserve', 'defaultText' => 'Cannot auto renew, on hold for another user']);
				} elseif ($autoRenewError == 'too_many') {
					$autoRenewError = translate(['text' => 'koha_auto_renew_too_many', 'defaultText' => 'Cannot auto renew, too many renewals']);
				} elseif ($autoRenewError == 'auto_account_expired') {
					$autoRenewError = translate(['text' => 'koha_auto_renew_auto_account_expired', 'defaultText' => 'Cannot auto renew, your account has expired']);
				} elseif ($autoRenewError == 'auto_too_soon') {
					$autoRenewError = translate(['text' => 'koha_auto_renew_auto', 'defaultText' => 'If eligible, this item wil renew on<br/>%1%', '1' => $renewalDate]);
				}
			}
			$checkout['autoRenewError'] = $autoRenewError;

			//Get the max renewals by figuring out what rule the checkout was issued under
			$patronType = $patron->patronType;
			$itemType = $curRow['itype'];
			$checkoutBranch = $curRow['branchcode'];
			/** @noinspection SqlResolve */
			$issuingRulesSql = "SELECT renewalsallowed FROM issuingrules where categorycode IN ('{$patronType}', '*') and itemtype IN('{$itemType}', '*') and branchcode IN ('{$checkoutBranch}', '*') order by branchcode desc, categorycode desc, itemtype desc limit 1";
			$issuingRulesRS = mysqli_query($this->dbConnection, $issuingRulesSql);
			if ($issuingRulesRS !== false) {
				if ($issuingRulesRow = $issuingRulesRS->fetch_assoc()) {
					$checkout['maxRenewals'] = $issuingRulesRow['renewalsallowed'];
				}
				$issuingRulesRS->close();
			}

			if ($checkout['id'] && strlen($checkout['id']) > 0) {
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($checkout['recordId']);
				if ($recordDriver->isValid()) {
					$checkout['groupedWorkId'] = $recordDriver->getPermanentId();
					$checkout['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
					$checkout['ratingData'] = $recordDriver->getRatingData();
					$checkout['groupedWorkId'] = $recordDriver->getGroupedWorkId();
					$checkout['format'] = $recordDriver->getPrimaryFormat();
					$checkout['author'] = $recordDriver->getPrimaryAuthor();
					$checkout['title'] = $recordDriver->getTitle();
					$curTitle['title_sort'] = $recordDriver->getSortableTitle();
					$checkout['link'] = $recordDriver->getLinkUrl();
				} else {
					$checkout['coverUrl'] = "";
					$checkout['groupedWorkId'] = "";
					$checkout['format'] = "Unknown";
				}
				$recordDriver->__destruct();
				$recordDriver = null;
			}

			$checkout['user'] = $patron->getNameAndLibraryLabel();

			$checkouts[] = $checkout;
		}

		$this->checkouts[$patron->id] = $checkouts;

		return $checkouts;
	}

	public function getXMLWebServiceResponse($url)
	{
		$xml = $this->curlWrapper->curlGetPage($url);
		if ($xml !== false && $xml !== 'false') {
			if (strpos($xml, '<') !== false) {
				//Strip any non-UTF-8 characters
				$xml = preg_replace('/[^(\x20-\x7F)]*/', '', $xml);
				libxml_use_internal_errors(true);
				$parsedXml = simplexml_load_string($xml);
				if ($parsedXml === false) {
					//Failed to load xml
					global $logger;
					$logger->log("Error parsing xml", Logger::LOG_ERROR);
					$logger->log($xml, Logger::LOG_DEBUG);
					foreach (libxml_get_errors() as $error) {
						$logger->log("\t {$error->message}", Logger::LOG_ERROR);
					}
					return false;
				} else {
					return $parsedXml;
				}
			} else {
				return $xml;
			}
		} else {
			global $logger;
			$logger->log('Curl problem in getWebServiceResponse', Logger::LOG_WARNING);
			return false;
		}
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param boolean $validatedViaSSO
	 * @return AspenError|User|null
	 */
	public function patronLogin($username, $password, $validatedViaSSO)
	{
		//Remove any spaces from the barcode
		$username = trim($username);
		$password = trim($password);

		//Use MySQL connection to load data
		$this->initDatabaseConnection();

		$barcodesToTest = array();
		$barcodesToTest[] = $username;
		$barcodesToTest[] = preg_replace('/[^a-zA-Z\d]/', '', trim($username));
		//Special processing to allow users to login with short barcodes
		global $library;
		if ($library) {
			if ($library->barcodePrefix) {
				if (strpos($username, $library->barcodePrefix) !== 0) {
					//Add the barcode prefix to the barcode
					$barcodesToTest[] = $library->barcodePrefix . $username;
				}
			}
		}

		$userExistsInDB = false;
		foreach ($barcodesToTest as $i => $barcode) {
			//Authenticate the user using KOHA ILSDI
			$authenticationURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?service=AuthenticatePatron&username=' . urlencode($barcode) . '&password=' . urlencode($password);
			$authenticationResponse = $this->getXMLWebServiceResponse($authenticationURL);
			if (isset($authenticationResponse->id)) {
				$patronId = $authenticationResponse->id;
				$result = $this->loadPatronInfoFromDB($patronId, $password);
				if ($result == false) {
					global $logger;
					$logger->log("MySQL did not return a result for getUserInfoStmt", Logger::LOG_ERROR);
					if ($i == count($barcodesToTest) - 1) {
						return new AspenError('authentication_error_technical');
					}
				} else {
					return $result;
				}
			} else {
				if (isset($authenticationResponse->message) && preg_match('/ILS-DI is disabled/', $authenticationResponse->message)){
					global $logger;
					$logger->log("ILS-DI is disabled", Logger::LOG_ERROR);
				}
				//User is not valid, check to see if they have a valid account in Koha so we can return a different error
				/** @noinspection SqlResolve */
				$sql = "SELECT borrowernumber, cardnumber, userId, login_attempts from borrowers where cardnumber = '$barcode' OR userId = '$barcode'";

				$lookupUserResult = mysqli_query($this->dbConnection, $sql);
				if ($lookupUserResult->num_rows > 0) {
					$userExistsInDB = true;
					$lookupUserRow = $lookupUserResult->fetch_assoc();
					if (UserAccount::isUserMasquerading()) {
						$patronId = $lookupUserRow['borrowernumber'];
						$newUser = $this->loadPatronInfoFromDB($patronId, null);
						if (!empty($newUser) && !($newUser instanceof AspenError)) {
							return $newUser;
						}
					}else{
						//Check to see if the user has reached the maximum number of login attempts
						$maxLoginAttempts = $this->getKohaSystemPreference('FailedLoginAttempts');
						if (!empty($maxLoginAttempts) && $maxLoginAttempts <= $lookupUserRow['login_attempts']){
							return new AspenError('Maximum number of failed login attempts reached, your account has been locked.');
						}
					}
				}
			}
		}
		if ($userExistsInDB) {
			return new AspenError('authentication_error_denied');
		} else {
			return null;
		}
	}

	private function loadPatronInfoFromDB($patronId, $password)
	{
		global $timer;
		/** @noinspection SqlResolve */
		$sql = "SELECT borrowernumber, cardnumber, surname, firstname, streetnumber, streettype, address, address2, city, state, zipcode, country, email, phone, mobile, categorycode, dateexpiry, password, userid, branchcode, opacnote, privacy from borrowers where borrowernumber = $patronId";

		$userExistsInDB = false;
		$lookupUserResult = mysqli_query($this->dbConnection, $sql, MYSQLI_USE_RESULT);
		if ($lookupUserResult) {
			$userFromDb = $lookupUserResult->fetch_assoc();
			$lookupUserResult->close();

			$user = new User();
			//Get the unique user id from Millennium
			$user->source = $this->accountProfile->name;
			$user->username = $userFromDb['borrowernumber'];
			if ($user->find(true)) {
				$userExistsInDB = true;
			}

			$forceDisplayNameUpdate = false;
			$firstName = $userFromDb['firstname'];
			if ($user->firstname != $firstName) {
				$user->firstname = $firstName;
				$forceDisplayNameUpdate = true;
			}
			$lastName = $userFromDb['surname'];
			if ($user->lastname != $lastName) {
				$user->lastname = isset($lastName) ? $lastName : '';
				$forceDisplayNameUpdate = true;
			}
			if ($forceDisplayNameUpdate) {
				$user->displayName = '';
			}
			$user->_fullname = $userFromDb['firstname'] . ' ' . $userFromDb['surname'];
			if ($userFromDb['cardnumber'] != null) {
				$user->cat_username = $userFromDb['cardnumber'];
			}else{
				$user->cat_username = $userFromDb['userid'];
			}

			if ($userExistsInDB) {
				$passwordChanged = ($user->cat_password != $password);
				if ($passwordChanged) {
					//The password has changed, disable account linking and give users the appropriate messages
					$user->disableLinkingDueToPasswordChange();
				}
			}else{
				//For new users, we need to check to see if they are opted into reading history or not
				switch ($userFromDb['privacy']){
					case 2:
						//Never track
						$user->trackReadingHistory = false;
						break;
					case 0:
						//Track forever
						$user->trackReadingHistory = true;
						break;
					default:
						//Depends on configuration for the patron category
						$pType = $userFromDb['categorycode'];
						/** @noinspection SqlResolve */
						$patronCategorySql = "select default_privacy from categories where categorycode = '$pType'";
						$patronCategoryResult = mysqli_query($this->dbConnection, $patronCategorySql, MYSQLI_USE_RESULT);
						if ($patronCategoryResult) {
							$privacyInfo = mysqli_fetch_assoc($patronCategoryResult);
							if ($privacyInfo) {
								switch ($privacyInfo['default_privacy']) {
									case 'forever':
										//Never delete
										$user->trackReadingHistory = true;
										break;
									case 'never':
										//Never store
										$user->trackReadingHistory = false;
										break;
									case 'default':
										//Keep until it gets deleted (on in Aspen).
										$user->trackReadingHistory = true;
										break;
								}
							} else {
								global $logger;
								$logger->log("Could not get information about patron category", Logger::LOG_ERROR);
							}
						}else{
							global $logger;
							$logger->log("Could not get information about patron category", Logger::LOG_ERROR);
						}
				}
			}
			$user->cat_password = $password;
			$user->email = $userFromDb['email'];
			$user->patronType = $userFromDb['categorycode'];
			$user->_web_note = '';

			$user->_address1 = trim($userFromDb['streetnumber'] . ' ' . $userFromDb['address']);
			$user->_address2 = $userFromDb['address2'];
			$user->_city = $userFromDb['city'];
			$user->_state = $userFromDb['state'];
			$user->_zip = $userFromDb['zipcode'];
			$user->phone = $userFromDb['phone'];

			$user->_web_note = $userFromDb['opacnote'];

			$timer->logTime("Loaded base patron information for Koha $patronId");

			$homeBranchCode = strtolower($userFromDb['branchcode']);
			$location = new Location();
			$location->code = $homeBranchCode;
			if (!$location->find(1)) {
				$location->__destruct();
				unset($location);
				$user->homeLocationId = 0;
				// Logging for Diagnosing PK-1846
				global $logger;
				$logger->log('Koha Driver: No Location found, user\'s homeLocationId being set to 0. User : ' . $user->id, Logger::LOG_WARNING);
			}

			if ((empty($user->homeLocationId) || $user->homeLocationId == -1) || (isset($location) && $user->homeLocationId != $location->locationId)) { // When homeLocation isn't set or has changed
				if ((empty($user->homeLocationId) || $user->homeLocationId == -1) && !isset($location)) {
					// homeBranch Code not found in location table and the user doesn't have an assigned home location,
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
						$location->__destruct();
						unset($location);
					}
				}
				if (isset($location)) {
					$user->homeLocationId = $location->locationId;
					if (empty($user->myLocation1Id)) {
						$user->myLocation1Id = ($location->nearbyLocation1 > 0) ? $location->nearbyLocation1 : $location->locationId;
						/** @var /Location $location */
						//Get display name for preferred location 1
						$myLocation1 = new Location();
						$myLocation1->locationId = $user->myLocation1Id;
						if ($myLocation1->find(true)) {
							$user->_myLocation1 = $myLocation1->displayName;
						}
						$myLocation1->__destruct();
						$myLocation1 = null;
					}

					if (empty($user->myLocation2Id)) {
						$user->myLocation2Id = ($location->nearbyLocation2 > 0) ? $location->nearbyLocation2 : $location->locationId;
						//Get display name for preferred location 2
						$myLocation2 = new Location();
						$myLocation2->locationId = $user->myLocation2Id;
						if ($myLocation2->find(true)) {
							$user->_myLocation2 = $myLocation2->displayName;
						}
						$myLocation2->__destruct();
						$myLocation2 = null;
					}
				}
			}

			if (isset($location)) {
				//Get display names that aren't stored
				$user->_homeLocationCode = $location->code;
				$user->_homeLocation = $location->displayName;
				//Cleanup
				$location->__destruct();
				$location = null;
			}

			$user->_noticePreferenceLabel = 'Unknown';

			if ($userExistsInDB) {
				$user->update();
			} else {
				$user->created = date('Y-m-d');
				$user->insert();
			}

			$timer->logTime("patron logged in successfully");

			return $user;
		}
		return $userExistsInDB;
	}

	function initDatabaseConnection()
	{
		if ($this->dbConnection == null) {
			$port = empty($this->accountProfile->databasePort) ? '3306' : $this->accountProfile->databasePort;
			$this->dbConnection = mysqli_connect($this->accountProfile->databaseHost, $this->accountProfile->databaseUser, $this->accountProfile->databasePassword, $this->accountProfile->databaseName, $port);

			if (!$this->dbConnection || mysqli_errno($this->dbConnection) != 0) {
				global $logger;
				$logger->log("Error connecting to Koha database " . mysqli_error($this->dbConnection), Logger::LOG_ERROR);
				$this->dbConnection = null;
			}
			global $timer;
			$timer->logTime("Initialized connection to Koha");
		}
	}

	function closeDatabaseConnection()
	{
		if ($this->dbConnection != null){
			mysqli_close($this->dbConnection);
			$this->dbConnection = null;
		}
	}

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		global $timer;
		$timer->logTime("Created Koha Driver");
		$this->curlWrapper = new CurlWrapper();
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct()
	{
		$this->curlWrapper = null;

		//Cleanup any connections we have to other systems
		if ($this->dbConnection != null) {
			if ($this->getNumHoldsStmt != null) {
				$this->getNumHoldsStmt->close();
			}
			mysqli_close($this->dbConnection);
		}
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
		// TODO implement sorting, currently only done in catalogConnection for koha reading history
		//TODO prepend indexProfileType
		$this->initDatabaseConnection();

		//Figure out if the user is opted in to reading history.  Only LibLime Koha has the option to turn it off
		//So assume that it is on if we don't get a good response
		/** @noinspection SqlResolve */
		$sql = "select disable_reading_history from borrowers where borrowernumber = {$patron->username}";
		$historyEnabledRS = mysqli_query($this->dbConnection, $sql);
		if ($historyEnabledRS) {
			$historyEnabledRow = $historyEnabledRS->fetch_assoc();
			$historyEnabled = !$historyEnabledRow['disable_reading_history'];
		}else {
			$historyEnabled = true;
		}

		// Update patron's setting in Aspen if the setting has changed in Koha
		if ($historyEnabled != $patron->trackReadingHistory) {
			$patron->trackReadingHistory = (boolean)$historyEnabled;
			$patron->update();
		}

		if (!$historyEnabled) {
			return array('historyActive' => false, 'titles' => array(), 'numTitles' => 0);
		} else {
			$historyActive = true;
			$readingHistoryTitles = array();

			//Borrowed from C4:Members.pm
			/** @noinspection SqlResolve */
			$readingHistoryTitleSql = "SELECT issues.*,issues.renewals AS renewals,items.renewals AS totalrenewals,items.timestamp AS itemstimestamp,biblio.biblionumber,biblio.title, author, iType
				FROM issues
				LEFT JOIN items on items.itemnumber=issues.itemnumber
				LEFT JOIN biblio ON items.biblionumber=biblio.biblionumber
				LEFT JOIN biblioitems ON items.biblioitemnumber=biblioitems.biblioitemnumber
				WHERE borrowernumber={$patron->username}
				UNION ALL
				SELECT old_issues.*,old_issues.renewals AS renewals,items.renewals AS totalrenewals,items.timestamp AS itemstimestamp,biblio.biblionumber,biblio.title, author, iType
				FROM old_issues
				LEFT JOIN items on items.itemnumber=old_issues.itemnumber
				LEFT JOIN biblio ON items.biblionumber=biblio.biblionumber
				LEFT JOIN biblioitems ON items.biblioitemnumber=biblioitems.biblioitemnumber
				WHERE borrowernumber={$patron->username}";
			$readingHistoryTitleRS = mysqli_query($this->dbConnection, $readingHistoryTitleSql);
			if ($readingHistoryTitleRS) {
				while ($readingHistoryTitleRow = $readingHistoryTitleRS->fetch_assoc()) {
					/** @noinspection SpellCheckingInspection */
					if (!empty($readingHistoryTitleRow['issuedate'])){
						/** @noinspection SpellCheckingInspection */
						$checkOutDate = new DateTime($readingHistoryTitleRow['issuedate']);
					}else{
						$checkOutDate = new DateTime($readingHistoryTitleRow['itemstimestamp']);
					}

					$returnDate = null;
					/** @noinspection SpellCheckingInspection */
					if (!empty($readingHistoryTitleRow['returndate'])){
						/** @noinspection SpellCheckingInspection */
						$returnDate = new DateTime($readingHistoryTitleRow['returndate']);
					}
					$curTitle = array();
					$curTitle['id'] = $readingHistoryTitleRow['biblionumber'];
					$curTitle['shortId'] = $readingHistoryTitleRow['biblionumber'];
					$curTitle['recordId'] = $readingHistoryTitleRow['biblionumber'];
					$curTitle['title'] = $readingHistoryTitleRow['title'];
					$curTitle['author'] = $readingHistoryTitleRow['author'];
					$curTitle['format'] = $readingHistoryTitleRow['iType'];
					$curTitle['checkout'] = $checkOutDate->getTimestamp();
					if (!empty($returnDate)){
						$curTitle['checkin'] = $returnDate->getTimestamp();
					}else{
						$curTitle['checkin'] = null;
					}
					$readingHistoryTitles[] = $curTitle;
				}
			}
		}

		$numTitles = count($readingHistoryTitles);

		//process pagination
		if ($recordsPerPage != -1) {
			$startRecord = ($page - 1) * $recordsPerPage;
			$readingHistoryTitles = array_slice($readingHistoryTitles, $startRecord, $recordsPerPage);
		}

		set_time_limit(20 * count($readingHistoryTitles));
		foreach ($readingHistoryTitles as $key => $historyEntry) {
			//Get additional information from resources table
			$historyEntry['ratingData'] = null;
			$historyEntry['permanentId'] = null;
			$historyEntry['linkUrl'] = null;
			$historyEntry['coverUrl'] = null;
			if (!empty($historyEntry['recordId'])) {
//					if (is_int($historyEntry['recordId'])) $historyEntry['recordId'] = (string) $historyEntry['recordId']; // Marc Record Constructor expects the recordId as a string.
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . $historyEntry['recordId']);
				if ($recordDriver->isValid()) {
					$historyEntry['ratingData'] = $recordDriver->getRatingData();
					$historyEntry['permanentId'] = $recordDriver->getPermanentId();
					$historyEntry['linkUrl'] = $recordDriver->getGroupedWorkDriver()->getLinkUrl();
					$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
					$historyEntry['format'] = $recordDriver->getFormats();
					$historyEntry['author'] = $recordDriver->getPrimaryAuthor();
				}
				$recordDriver = null;
			}
			$readingHistoryTitles[$key] = $historyEntry;
		}

		return array('historyActive' => $historyActive, 'titles' => $readingHistoryTitles, 'numTitles' => $numTitles);
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
		$hold_result = array();
		$hold_result['success'] = false;

		//Set pickup location
		$pickupBranch = strtoupper($pickupBranch);

		$patronEligibleForHolds = $this->patronEligibleForHolds($patron);
		if (!$patronEligibleForHolds['isEligible']){
			$hold_result['message'] = $patronEligibleForHolds['message'];
			return $hold_result;
		}

		//Get a specific item number to place a hold on even though we are placing a title level hold.
		//because.... Koha
		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$recordDriver = new MarcRecordDriver($recordId);
		if (!$recordDriver->isValid()) {
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		$marcRecord = $recordDriver->getMarcRecord();

		//Check to see if the patron already has that record checked out
		$allowHoldsOnCheckedOutTitles = $this->getKohaSystemPreference('AllowHoldsOnPatronsPossessions');
		if ($allowHoldsOnCheckedOutTitles == 0) {
			$existingCheckouts = $this->getCheckouts($patron);
			foreach ($existingCheckouts as $checkout) {
				if ($checkout['recordId'] == $recordId) {
					$hold_result['message'] = 'You already have that title checked out, you cannot place a hold on it until you check it in.';
					return $hold_result;
				}
			}
		}

		//Check to see if the title requires item level holds
		/** @var File_MARC_Data_Field[] $holdTypeFields */
		$itemLevelHoldAllowed = false;
		$itemLevelHoldOnly = false;
		$indexingProfile = $this->getIndexingProfile();
		$holdTypeFields = $marcRecord->getFields($indexingProfile->itemTag);
		foreach ($holdTypeFields as $holdTypeField) {
			if ($holdTypeField->getSubfield('r') != null) {
				if ($holdTypeField->getSubfield('r')->getData() == 'itemtitle') {
					$itemLevelHoldAllowed = true;
				} else if ($holdTypeField->getSubfield('r')->getData() == 'item') {
					$itemLevelHoldAllowed = true;
					$itemLevelHoldOnly = true;
				}
			}
		}

		//Get the items the user can place a hold on
		if ($itemLevelHoldAllowed) {
			//Need to prompt for an item level hold
			$items = array();
			if (!$itemLevelHoldOnly) {
				//Add a first title returned
				$items[-1] = array(
					'itemNumber' => -1,
					'location' => 'Next available copy',
					'callNumber' => '',
					'status' => '',
				);
			}

			$hold_result['title'] = $recordDriver->getTitle();
			$hold_result['items'] = $items;
			if (count($items) > 0) {
				$message = 'This title allows item level holds, please select an item to place a hold on.';
			} else {
				if (!isset($message)) {
					$message = 'There are no holdable items for this title.';
				}
			}
			$hold_result['success'] = false;
			$hold_result['message'] = $message;
			return $hold_result;
		} else {
			//Just a regular bib level hold
			$hold_result['title'] = $recordDriver->getTitle();

			global $active_ip;
			$holdParams = [
				'service' => 'HoldTitle',
				'patron_id' => $patron->username,
				'bib_id' => $recordDriver->getId(),
				'request_location' => $active_ip,
				'pickup_location' => $pickupBranch
			];

			if ($cancelDate != null) {
				if ($this->getKohaVersion() >= 20.05) {
					$holdParams['expiry_date'] = $this->aspenDateToKohaDate($cancelDate);
				}else{
					$holdParams['needed_before_date'] = $this->aspenDateToKohaDate($cancelDate);
				}
			}

			$placeHoldURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($holdParams);
			$placeHoldResponse = $this->getXMLWebServiceResponse($placeHoldURL);

			//If the hold is successful we go back to the account page and can see

			$hold_result['id'] = $recordId;
			if ($placeHoldResponse->title) {
				//everything seems to be good
				$hold_result = $this->getHoldMessageForSuccessfulHold($patron, $recordDriver->getId(), $hold_result);
			} else {
				$hold_result['success'] = false;
				//See if we can get more info on why this failed.
				$holds = $this->getHolds($patron);
				$alreadyOnHold = false;
				foreach($holds['available'] as $hold) {
					if ($hold['recordId'] == $recordDriver->getId()){
						$alreadyOnHold = true;
					}
				}
				foreach($holds['unavailable'] as $hold) {
					if ($hold['recordId'] == $recordDriver->getId()){
						$alreadyOnHold = true;
					}
				}
				//Look for an alert message
				if ($alreadyOnHold){
					$hold_result['message'] = translate(['text'=>'ils_title_already_on_hold', 'defaultText'=>'Your hold could not be placed, you already have this title on hold.']);
				}else {
					$hold_result['message'] = translate(['text'=>'koha_hold_failed', 'defaultText'=>'Your hold could not be placed. %1%', '1'=> $placeHoldResponse->code]);
				}
			}
			return $hold_result;
		}
	}


	/**
	 * @param User $patron
	 * @param string $recordId
	 * @param string $volumeId
	 * @param string $pickupBranch
	 * @return array
	 */
	public function placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch)
	{
		$result = [
			'success' => false,
			'message' => 'Unknown error placing a hold on this volume.'
		];

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$apiUrl = $this->getWebServiceUrl() . "/api/v1/holds";
			$postParams = [
				'patron_id' => $patron->username,
				'pickup_library_id' => $pickupBranch,
				'volume_id' => (int)$volumeId,
				'biblio_id' => $recordId,
			];
			$postParams = json_encode($postParams);
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
				'Accept-Encoding: gzip, deflate',
			], true);
			/** @noinspection PhpUnusedLocalVariableInspection */
			$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postParams, false);
			$responseCode = $this->apiCurlWrapper->getResponseCode();
			if ($responseCode == 201){
				$result['message'] = translate(['text'=>"ils_hold_success", 'defaultText'=>"Your hold was placed successfully."]);
				$result['success'] = true;
			}else{
				$result = [
					'success' => false,
					'message' => "Error ($responseCode) placing a hold on this volume."
				];
			}
		}
		return $result;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		$hold_result = array();
		$hold_result['success'] = false;

		$patronEligibleForHolds = $this->patronEligibleForHolds($patron);
		if ($patronEligibleForHolds['isEligible'] == false){
			return $hold_result['message'] = $patronEligibleForHolds['message'];
		}

		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$recordDriver = new MarcRecordDriver($this->getIndexingProfile()->name . ':' . $recordId);
		if (!$recordDriver->isValid()) {
			$hold_result['message'] = 'Unable to find a valid record for this title.  Please try your search again.';
			return $hold_result;
		}
		$hold_result['title'] = $recordDriver->getTitle();

		//Set pickup location
		if (isset($_REQUEST['pickupBranch'])) {
			$pickupBranch = trim($_REQUEST['pickupBranch']);
		} else {
			$pickupBranch = $patron->homeLocationId;
			//Get the code for the location
			$locationLookup = new Location();
			$locationLookup->locationId = $pickupBranch;
			$locationLookup->find();
			if ($locationLookup->getNumResults() > 0) {
				$locationLookup->fetch();
				$pickupBranch = $locationLookup->code;
			}
		}
		$pickupBranch = strtoupper($pickupBranch);

		$holdParams = [
			'service' => 'HoldItem',
			'patron_id' => $patron->username,
			'bib_id' => $recordDriver->getId(),
			'item_id' => $itemId,
			//'request_location' => $active_ip, //Not allowed for HoldItem, but required for HoldTitle
			'pickup_location' => $pickupBranch
		];
		if ($cancelDate != null) {
			$holdParams['needed_before_date'] = $this->aspenDateToKohaDate($cancelDate);
		}

		$placeHoldURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($holdParams);
		$placeHoldResponse = $this->getXMLWebServiceResponse($placeHoldURL);

		if ($placeHoldResponse->pickup_location) {
			//We redirected to the holds page, everything seems to be good
			$hold_result = $this->getHoldMessageForSuccessfulHold($patron, $recordId, $hold_result);
		} else {
			$hold_result['success'] = false;
			//Look for an alert message
			$hold_result['message'] = 'Your hold could not be placed. ' . $placeHoldResponse->code;
		}
		return $hold_result;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds by a specific patron.
	 *
	 * @param array|User $patron The patron array from patronLogin
	 * @param integer $page The current page of holds
	 * @param integer $recordsPerPage The number of records to show per page
	 * @param string $sortOption How the records should be sorted
	 *
	 * @return mixed        Array of the patron's holds on success, AspenError
	 * otherwise.
	 * @access public
	 */
	public function getHolds($patron, $page = 1, $recordsPerPage = -1, $sortOption = 'title')
	{
		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT reserves.*, biblio.title, biblio.author, items.itemcallnumber, items.enumchron FROM reserves inner join biblio on biblio.biblionumber = reserves.biblionumber left join items on items.itemnumber = reserves.itemnumber where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		while ($curRow = $results->fetch_assoc()) {
			//Each row in the table represents a hold
			$curHold = array();
			$curHold['holdSource'] = 'ILS';
			$bibId = $curRow['biblionumber'];
			$curHold['id'] = $curRow['biblionumber'];
			$curHold['shortId'] = $curRow['biblionumber'];
			$curHold['recordId'] = $curRow['biblionumber'];
			$curHold['title'] = $curRow['title'];
			if (isset($curRow['itemcallnumber'])) {
				$curHold['callNumber'] = $curRow['itemcallnumber'];
			}
			if (isset($curRow['enumchron'])) {
				$curHold['volume'] = $curRow['enumchron'];
			}
			if (isset($curRow['volume_id'])){
				//Get the volume info
				require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
				$volumeInfo = new IlsVolumeInfo();
				$volumeInfo->volumeId = $curRow['volume_id'];
				if ($volumeInfo->find(true)){
					$curHold['volume'] = $volumeInfo->displayLabel;
				}
			}
			if (strpos($curRow['reservedate'], ':') > 0){
				$curHold['create'] = date_parse_from_format('Y-m-d H:i:s', $curRow['reservedate']);
			}else{
				$curHold['create'] = date_parse_from_format('Y-m-d', $curRow['reservedate']);
			}

			if (!empty($curRow['expirationdate'])) {
				$dateTime = date_create_from_format('Y-m-d', $curRow['expirationdate']);
				$curHold['expire'] = $dateTime->getTimestamp();
			}

			if (!empty($curRow['cancellationdate'])) {
				$curHold['automaticCancellation'] = date_parse_from_format('Y-m-d H:i:s', $curRow['cancellationdate']);
			}else{
				$curHold['automaticCancellation'] = '';
			}

			$curHold['currentPickupId'] = $curRow['branchcode'];
			$curHold['location'] = $curRow['branchcode'];
			$curHold['locationUpdateable'] = false;
			$curHold['currentPickupName'] = $curHold['location'];
			$curHold['position'] = $curRow['priority'];
			$curHold['frozen'] = false;
			$curHold['canFreeze'] = false;
			$curHold['cancelable'] = true;
			if ($curRow['suspend'] == '1') {
				$curHold['frozen'] = true;
				$curHold['status'] = "Frozen";
				if ($curRow['suspend_until'] != null) {
					$curHold['status'] .= ' until ' . date("m/d/Y", strtotime($curRow['suspend_until']));
				}
				$curHold['locationUpdateable'] = true;
			} elseif ($curRow['found'] == 'W') {
				$curHold['cancelable'] = false;
				$curHold['status'] = "Ready to Pickup";
			} elseif ($curRow['found'] == 'T') {
				$curHold['status'] = "In Transit";
			} else {
				$curHold['status'] = "Pending";
				$curHold['canFreeze'] = true;
				$curHold['locationUpdateable'] = true;
			}
			$curHold['cancelId'] = $curRow['reserve_id'];

			if ($bibId) {
				require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
				$recordDriver = new MarcRecordDriver($bibId);
				if ($recordDriver->isValid()) {
					$curHold['groupedWorkId'] = $recordDriver->getPermanentId();
					$curHold['sortTitle'] = $recordDriver->getSortableTitle();
					$curHold['format'] = $recordDriver->getFormat();
					$curHold['author'] = $recordDriver->getPrimaryAuthor();
					$curHold['isbn'] = $recordDriver->getCleanISBN();
					$curHold['upc'] = $recordDriver->getCleanUPC();
					$curHold['format_category'] = $recordDriver->getFormatCategory();
					$curHold['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
					$curHold['link'] = $recordDriver->getLinkUrl();

					//Load rating information
					$curHold['ratingData'] = $recordDriver->getRatingData();
				}
			}
			$curHold['user'] = $patron->getNameAndLibraryLabel();

			$isAvailable = isset($curHold['status']) && preg_match('/^Ready to Pickup.*/i', $curHold['status']);
			global $library;
			if ($isAvailable && $library->availableHoldDelay > 0){
				$holdAvailableOn = strtotime($curRow['waitingdate']);
				if ((time() - $holdAvailableOn) < 60 * 60 * 24 * $library->availableHoldDelay){
					$isAvailable = false;
					$curHold['status'] = 'In transit';
				}
			}
			if (!$isAvailable) {
				$holds['unavailable'][$curHold['holdSource'] . $curHold['cancelId']. $curHold['user']] = $curHold;
			} else {
				$holds['available'][$curHold['holdSource'] . $curHold['cancelId']. $curHold['user']] = $curHold;
			}
		}

		return $holds;
	}

	/**
	 * Update a hold that was previously placed in the system.
	 * Can cancel the hold or update pickup locations.
	 * @param User $patron
	 * @param string $type
	 * @param string $xNum
	 * @param string $cancelId
	 * @param integer $locationId
	 * @param string $freezeValue
	 * @return array
	 */
	public function updateHoldDetailed($patron, $type, $xNum, $cancelId, $locationId, /** @noinspection PhpUnusedParameterInspection */ $freezeValue = 'off')
	{
		$titles = array();

		if (!isset($xNum) || empty($xNum)) {
			if (is_array($cancelId)) {
				$holdKeys = $cancelId;
			} else {
				$holdKeys = array($cancelId);
			}
		} else {
			$holdKeys = $xNum;
		}

		if ($type == 'cancel') {
			$allCancelsSucceed = true;

			//Post a request to koha
			foreach ($holdKeys as $holdKey) {
				$holdParams = [
					'service' => 'CancelHold',
					'patron_id' => $patron->username,
					'item_id' => $holdKey,
				];

				$cancelHoldURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($holdParams);
				$cancelHoldResponse = $this->getXMLWebServiceResponse($cancelHoldURL);

				//Parse the result
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if (isset($cancelHoldResponse->code) && ($cancelHoldResponse->code == 'Cancelled' || $cancelHoldResponse->code == 'Canceled')) {
					//We cancelled the hold
				} else {
					$allCancelsSucceed = false;
				}
			}
			if ($allCancelsSucceed) {
				global $memCache;
				$memCache->delete('koha_summary_' . $patron->id);
				return array(
					'title' => $titles,
					'success' => true,
					'message' => count($holdKeys) == 1 ? 'Cancelled 1 hold successfully.' : 'Cancelled ' . count($holdKeys) . ' hold(s) successfully.');
			} else {
				return array(
					'title' => $titles,
					'success' => false,
					'message' => 'Some holds could not be cancelled.  Please try again later or see your librarian.');
			}
		} else {
			if ($locationId) {
				return array(
					'title' => $titles,
					'success' => false,
					'message' => 'Changing location for a hold is not supported.');
			} else {
				return array(
					'title' => $titles,
					'success' => false,
					'message' => 'Freezing and thawing holds is not supported.');
			}
		}
	}

	public function hasFastRenewAll()
	{
		return false;
	}

	public function renewAll($patron)
	{
		return array(
			'success' => false,
			'message' => 'Renew All not supported directly, call through Catalog Connection',
		);
	}

	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		$params = [
			'service' => 'RenewLoan',
			'patron_id' => $patron->username,
			'item_id' => $itemId,
		];

		$renewURL = $this->getWebServiceUrl() . '/cgi-bin/koha/ilsdi.pl?' . http_build_query($params);
		$renewResponse = $this->getXMLWebServiceResponse($renewURL);

		//Parse the result
		if (isset($renewResponse->success) && ($renewResponse->success == 1)) {
			//We renewed the hold
			$success = true;
			$message = 'Your item was successfully renewed';
			global $memCache;
			$memCache->delete('koha_summary_' . $patron->id);
		} else {
			$success = false;
			$message = 'The item could not be renewed';
		}

		return array(
			'itemId' => $itemId,
			'success' => $success,
			'message' => $message);
	}

	/**
	 * Get a list of fines for the user.
	 * Code taken from C4::Account getcharges method
	 *
	 * @param User $patron
	 * @param bool $includeMessages
	 * @return array
	 */
	public function getFines($patron, $includeMessages = false)
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)){
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );

		$this->initDatabaseConnection();

		//Get a list of outstanding fees
		/** @noinspection SqlResolve */
		$query = "SELECT * FROM accountlines WHERE borrowernumber = {$patron->username} and amountoutstanding > 0 ORDER BY date DESC";

		$allFeesRS = mysqli_query($this->dbConnection, $query);

		$fines = [];
		if ($allFeesRS->num_rows > 0) {
			while ($allFeesRow = $allFeesRS->fetch_assoc()) {
				if (isset($allFeesRow['accountType'])){
					$type = array_key_exists($allFeesRow['accounttype'], Koha::$fineTypeTranslations) ? Koha::$fineTypeTranslations[$allFeesRow['accounttype']] : $allFeesRow['accounttype'];
				}elseif (isset($allFeesRow['debit_type_code']) && !empty($allFeesRow['debit_type_code'])){
					//Lookup the type in the account
					$type = array_key_exists($allFeesRow['debit_type_code'], Koha::$fineTypeTranslations) ? Koha::$fineTypeTranslations[$allFeesRow['debit_type_code']] : $allFeesRow['debit_type_code'];
				}elseif (isset($allFeesRow['credit_type_code']) && !empty($allFeesRow['credit_type_code'])){
					//Lookup the type in the account
					$type = array_key_exists($allFeesRow['credit_type_code'], Koha::$fineTypeTranslations) ? Koha::$fineTypeTranslations[$allFeesRow['credit_type_code']] : $allFeesRow['credit_type_code'];
				}else{
					$type = 'Unknown';
				}
				$curFine = [
					'fineId' => $allFeesRow['accountlines_id'],
					'date' => $allFeesRow['date'],
					'type' => $type,
					'reason' => $type,
					'message' => $allFeesRow['description'],
					'amountVal' => $allFeesRow['amount'],
					'amountOutstandingVal' => $allFeesRow['amountoutstanding'],
					'amount' => $currencyFormatter->formatCurrency($allFeesRow['amount'], $currencyCode),
					'amountOutstanding' => $currencyFormatter->formatCurrency($allFeesRow['amountoutstanding'], $currencyCode),
				];
				$fines[] = $curFine;
			}
		}
		$allFeesRS->close();

		return $fines;
	}

	/** @var mysqli_stmt */
	private $getNumHoldsStmt = null;

	/**
	 * Get Total Outstanding fines for a user.  Lifted from Koha:
	 * C4::Accounts.pm gettotalowed method
	 *
	 * @param User $patron
	 * @return mixed
	 */
	private function getOutstandingFineTotal($patron)
	{
		//Since borrowerNumber is stored in fees and payments, not fee_transactions,
		//this is done with two queries: the first gets all outstanding charges, the second
		//picks up any unallocated credits.
		$amountOutstanding = 0;
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$amountOutstandingRS = mysqli_query($this->dbConnection, "SELECT SUM(amountoutstanding) FROM accountlines where borrowernumber = {$patron->username}");
		if ($amountOutstandingRS) {
			$amountOutstanding = $amountOutstandingRS->fetch_array();
			$amountOutstanding = $amountOutstanding[0];
			$amountOutstandingRS->close();
		}

		return $amountOutstanding;
	}

	private $oauthToken = null;

	function getOAuthToken()
	{
		if ($this->oauthToken == null) {
			$apiUrl = $this->getWebServiceUrl() . "/api/v1/oauth/token";
			$postParams = [
				'grant_type' => 'client_credentials',
				'client_id' => $this->accountProfile->oAuthClientId,
				'client_secret' => $this->accountProfile->oAuthClientSecret,
			];

			$this->curlWrapper->addCustomHeaders([
				'Accept: application/json',
				'Content-Type: application/x-www-form-urlencoded',
			], false);
			$response = $this->curlWrapper->curlPostPage($apiUrl, $postParams);
			$json_response = json_decode($response);
			if (!empty($json_response->access_token)) {
				$this->oauthToken = $json_response->access_token;
			} else {
				$this->oauthToken = false;
			}
		}
		return $this->oauthToken;
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		return $this->updateHoldDetailed($patron, 'cancel', null, $cancelId, '', '');
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		$result = [
			'success' => false,
			'message' => translate('Unable to freeze your hold.')
		];

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$apiUrl = $this->getWebServiceUrl() . "/api/v1/holds/$itemToFreezeId/suspension";
			$postParams = "";
			if (strlen($dateToReactivate) > 0) {
				$postParams = [];
				list($month, $day, $year) = explode('/', $dateToReactivate);
				$postParams['end_date'] = "$year-$month-$day";
				$postParams = json_encode($postParams);
			}

			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
				'Accept-Encoding: gzip, deflate',
			], true);
			$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postParams, false);
			if (!$response) {
				return $result;
			} else {
				$hold_response = json_decode($response, false);
				if (isset($hold_response->error)) {
					$result['message'] = $hold_response->error;
					$result['success'] = true;
				} else {
					$result['message'] = translate(['text'=>'ils_freeze_hold_success', 'defaultText' => 'Your hold was frozen successfully.']);
					$result['success'] = true;
				}
			}
		}

		return $result;
	}

	function thawHold($patron, $recordId, $itemToThawId)
	{
		$result = [
			'success' => false,
			'message' => translate('Unable to thaw your hold.')
		];

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$apiUrl = $this->getWebServiceUrl() . "/api/v1/holds/$itemToThawId/suspension";

			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
				'Accept-Encoding: gzip, deflate',
			], true);
			$response = $this->apiCurlWrapper->curlSendPage($apiUrl, 'DELETE', null);
			if (strlen($response) > 0) {
				$result['message'] = $response;
				$result['success'] = true;
			} else {
				$result['message'] = translate(['text'=>'ils_thaw_hold_success', 'defaultText' => 'Your hold was thawed successfully.']);
				$result['success'] = true;
			}
		}

		return $result;
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		$result = [
			'success' => false,
			'message' => 'Unknown error changing hold pickup location.'
		];

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
				'Accept-Encoding: gzip, deflate',
			], true);

			//Get the current hold so we can load priority
			$apiUrl = $this->getWebServiceUrl() . "/api/v1/holds?hold_id=$itemToUpdateId";
			$response = $this->apiCurlWrapper->curlGetPage($apiUrl);
			if (!$response) {
				return $result;
			}else{
				$currentHolds = json_decode($response, false);
				$currentHold = null;
				foreach ($currentHolds as $currentHold){
					if ($currentHold->hold_id == $itemToUpdateId){
						break;
					}
				}

				$apiUrl = $this->getWebServiceUrl() . "/api/v1/holds/$itemToUpdateId";
				$postParams = [];
				$postParams['branchcode'] = $newPickupLocation;
				$postParams['pickup_library_id'] = $newPickupLocation;
				$postParams['priority'] = $currentHold->priority;
				$postParams = json_encode($postParams);
				$response = $this->apiCurlWrapper->curlSendPage($apiUrl, 'PUT', $postParams);
				if (!$response) {
					return $result;
				} else {
					$hold_response = json_decode($response, false);
					if (isset($hold_response->error)) {
						$result['message'] = $hold_response->error;
						$result['success'] = true;
					} elseif ($hold_response->pickup_library_id != $newPickupLocation) {
						$result['message'] = translate(['text'=>'ils_change_pickup_location_failed', 'Sorry, the pickup location of your hold could not be changed.']);
						$result['success'] = true;
					} else {
						$result['message'] = translate(['text'=>'ils_change_pickup_location_success', 'The pickup location of your hold was changed successfully.']);
						$result['success'] = true;
					}
				}
			}
		}

		return $result;
	}

	public function showOutstandingFines()
	{
		return true;
	}

	private function loginToKohaOpac($user)
	{
		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		//Construct the login url
		$loginUrl = "$catalogUrl/cgi-bin/koha/opac-user.pl";
		//Setup post parameters to the login url
		$postParams = array(
			'koha_login_context' => 'opac',
			'password' => $user->cat_password,
			'userid' => $user->cat_username
		);
		$sResult = $this->postToKohaPage($loginUrl, $postParams);
		//Parse the response to make sure the login went ok
		//If we can see the logout link, it means that we logged in successfully.
		if (preg_match('/<a[^>]*?\\s+class="logout"\\s+id="logout"[^>]*?>/si', $sResult)) {
			$result = array(
				'success' => true,
				'summaryPage' => $sResult
			);
		} else {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$info = curl_getinfo($this->opacCurlWrapper->curl_connection);
			$result = array(
				'success' => false,
				'message' => 'Could not login to the backend system'
			);
		}
		return $result;
	}

	/**
	 * @param $kohaUrl
	 * @param $postParams
	 * @return mixed
	 */
	protected function postToKohaPage($kohaUrl, $postParams)
	{
		if ($this->opacCurlWrapper == null) {
			$this->opacCurlWrapper = new CurlWrapper();
			//Extend timeout when talking to Koha via HTTP
			$this->opacCurlWrapper->timeout = 60;
		}
		return $this->opacCurlWrapper->curlPostPage($kohaUrl, $postParams);
	}

	protected function getKohaPage($kohaUrl)
	{
		if ($this->opacCurlWrapper == null) {
			$this->opacCurlWrapper = new CurlWrapper();
			//Extend timeout when talking to Koha via HTTP
			$this->opacCurlWrapper->timeout = 10;
		}
		return $this->opacCurlWrapper->curlGetPage($kohaUrl);
	}

	function getEmailResetPinResultsTemplate()
	{
		return 'emailResetPinResults.tpl';
	}

	function processEmailResetPinForm()
	{
		$result = array(
			'success' => false,
			'error' => translate("Unknown error sending password reset.")
		);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$username = strip_tags($_REQUEST['username']);
		$email = strip_tags($_REQUEST['email']);
		$postVariables = [
			'koha_login_context' => 'opac',
			'username' => $username,
			'email' => $email,
			'sendEmail' => 'Submit'
		];
		if (isset($_REQUEST['resendEmail'])) {
			$postVariables['resendEmail'] = strip_tags($_REQUEST['resendEmail']);
		}

		$postResults = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-password-recovery.pl', $postVariables);

		$messageInformation = [];
		if ($postResults == 'Internal Server Error') {
			if (isset($_REQUEST['resendEmail'])) {
				$result['error'] = translate('There was an error in backend system while resending the password reset email, please contact the library.');
			}else{
				$result['error'] = translate('There was an error in backend system while sending the password reset email, please contact the library.');
			}
		}else if (preg_match('%<div class="alert alert-warning">(.*?)</div>%s', $postResults, $messageInformation)) {
			$error = $messageInformation[1];
			$error = str_replace('<h3>', '<h4>', $error);
			$error = str_replace('</h3>', '</h4>', $error);
			$error = str_replace('/cgi-bin/koha/opac-password-recovery.pl', '/MyAccount/EmailResetPin', $error);
			$result['error'] = trim($error);
		} elseif (preg_match('%<div id="password-recovery">\s+<div class="alert alert-info">(.*?)<a href="/cgi-bin/koha/opac-main.pl">Return to the main page</a>\s+</div>\s+</div>%s', $postResults, $messageInformation)) {
			$message = $messageInformation[1];
			$result['success'] = true;
			$result['message'] = trim($message);
		}

		return $result;
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
		return 'emailResetLink';
	}

	function getEmailResetPinTemplate()
	{
		if (isset($_REQUEST['resendEmail'])){
			global $interface;
			$interface->assign('resendEmail', true);
		}
		return 'kohaEmailResetPinLink.tpl';
	}

	function getSelfRegistrationFields($type = 'selfReg')
	{
		global $library;

		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM systempreferences where variable like 'PatronSelf%';";
		$results = mysqli_query($this->dbConnection, $sql);
		$kohaPreferences = [];
		while ($curRow = $results->fetch_assoc()) {
			$kohaPreferences[$curRow['variable']] = $curRow['value'];
		}

		if ($type == 'selfReg') {
			$unwantedFields = explode('|', $kohaPreferences['PatronSelfRegistrationBorrowerUnwantedField']);
		} else {
			$unwantedFields = explode('|', $kohaPreferences['PatronSelfModificationBorrowerUnwantedField']);
		}
		$requiredFields = explode('|', $kohaPreferences['PatronSelfRegistrationBorrowerMandatoryField']);
		if ($type !== 'selfReg' || strlen($kohaPreferences['PatronSelfRegistrationLibraryList']) == 0) {
			$validLibraries = [];
		} else {
			$validLibraries = array_flip(explode('|', $kohaPreferences['PatronSelfRegistrationLibraryList']));
		}

		$fields = array();
		$location = new Location();

		$pickupLocations = array();
		if ($type == 'selfReg') {
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
					if (count($validLibraries) == 0 || array_key_exists($location->code, $validLibraries)) {
						$pickupLocations[$location->code] = $location->displayName;
					}
				}
				asort($pickupLocations);
			}
		}else{
			$patron = UserAccount::getActiveUserObj();
			$userPickupLocations = $patron->getValidPickupBranches($patron->getAccountProfile()->recordSource);
			$pickupLocations = [];
			foreach ($userPickupLocations as $key => $location){
				if ($location instanceof Location){
					$pickupLocations[$location->code] = $location->displayName;
				}else{
					if ($key == '0default'){
						$pickupLocations[-1] = $location;
					}
				}
			}
		}

		if ($library->requireNumericPhoneNumbersWhenUpdatingProfile){
			$phoneFormat = '';
		}else{
			$phoneFormat = ' (xxx-xxx-xxxx)';
		}

		//Library
		if (count($pickupLocations) == 1){
			$selectedPickupLocation = '';
			foreach ($pickupLocations as $code => $name){
				$selectedPickupLocation = $code;
			}
			$fields['borrower_branchcode'] = array('property' => 'borrower_branchcode', 'type' => 'hidden', 'label' => 'Home Library', 'description' => 'Please choose the Library location you would prefer to use', 'default' => $selectedPickupLocation, 'required' => true);
		}else{
			$fields['librarySection'] = array('property' => 'librarySection', 'type' => 'section', 'label' => 'Library', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
				'borrower_branchcode' => array('property' => 'borrower_branchcode', 'type' => 'enum', 'label' => 'Home Library', 'description' => 'Please choose the Library location you would prefer to use', 'values' => $pickupLocations, 'required' => true)
			]);
		}

		//Identity
		$fields['identitySection'] = array('property' => 'identitySection', 'type' => 'section', 'label' => 'Identity', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_title' => array('property' => 'borrower_title', 'type' => 'enum', 'label' => 'Salutation', 'values' => ['' => '', 'Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms', 'Miss' => 'Miss', 'Dr.' => 'Dr.'], 'description' => 'Your first name', 'required' => false),
			'borrower_surname' => array('property' => 'borrower_surname', 'type' => 'text', 'label' => 'Surname', 'description' => 'Your last name', 'maxLength' => 60, 'required' => true),
			'borrower_firstname' => array('property' => 'borrower_firstname', 'type' => 'text', 'label' => 'First Name', 'description' => 'Your first name', 'maxLength' => 25, 'required' => true),
			'borrower_dateofbirth' => array('property' => 'borrower_dateofbirth', 'type' => 'date', 'label' => 'Date of Birth (MM-DD-YYYY)', 'description' => 'Date of birth', 'maxLength' => 10, 'required' => true),
			'borrower_initials' => array('property' => 'borrower_initials', 'type' => 'text', 'label' => 'Initials', 'description' => 'Initials', 'maxLength' => 25, 'required' => false),
			'borrower_othernames' => array('property' => 'borrower_othernames', 'type' => 'text', 'label' => 'Other names', 'description' => 'Other names you go by', 'maxLength' => 128, 'required' => false),
			'borrower_sex' => array('property' => 'borrower_sex', 'type' => 'enum', 'label' => 'Gender', 'values' => ['' => 'None Specified', 'F' => 'Female', 'M' => 'Male'], 'description' => 'Gender', 'required' => false),

		]);

		if (empty($library->validSelfRegistrationStates)){
			$borrowerStateField = array('property' => 'borrower_state', 'type' => 'text', 'label' => 'State', 'description' => 'State', 'maxLength' => 32, 'required' => true);
		}else{
			$validStates = explode('|', $library->validSelfRegistrationStates);
			$validStates = array_combine($validStates, $validStates);
			$borrowerStateField = array('property' => 'borrower_state', 'type' => 'enum', 'values' => $validStates, 'label' => 'State', 'description' => 'State', 'maxLength' => 32, 'required' => true);
		}

		//Main Address
		$fields['mainAddressSection'] = array('property' => 'mainAddressSection', 'type' => 'section', 'label' => 'Main Address', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_address' => array('property' => 'borrower_address', 'type' => 'text', 'label' => 'Address', 'description' => 'Address', 'maxLength' => 128, 'required' => true),
			'borrower_address2' => array('property' => 'borrower_address2', 'type' => 'text', 'label' => 'Address 2', 'description' => 'Second line of the address', 'maxLength' => 128, 'required' => false),
			'borrower_city' => array('property' => 'borrower_city', 'type' => 'text', 'label' => 'City', 'description' => 'City', 'maxLength' => 48, 'required' => true),
			'borrower_state' => $borrowerStateField,
			'borrower_zipcode' => array('property' => 'borrower_zipcode', 'type' => 'text', 'label' => 'Zip Code', 'description' => 'Zip Code', 'maxLength' => 32, 'required' => true),
			'borrower_country' => array('property' => 'borrower_country', 'type' => 'text', 'label' => 'Country', 'description' => 'Country', 'maxLength' => 32, 'required' => false),
		]);
		if (!empty($library->validSelfRegistrationZipCodes)){
			$fields['mainAddressSection']['properties']['borrower_zipcode']['validationPattern'] = $library->validSelfRegistrationZipCodes;
			$fields['mainAddressSection']['properties']['borrower_zipcode']['validationMessage'] = translate('Please enter a valid zip code');
		}
		//Contact information
		$fields['contactInformationSection'] = array('property' => 'contactInformationSection', 'type' => 'section', 'label' => 'Contact Information', 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
			'borrower_phone' => array('property' => 'borrower_phone', 'type' => 'text', 'label' => 'Primary Phone' . $phoneFormat, 'description' => 'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_email' => array('property' => 'borrower_email', 'type' => 'email', 'label' => 'Primary Email', 'description' => 'Email', 'maxLength' => 128, 'required' => false),
		]);
		//Contact information
		$fields['additionalContactInformationSection'] = array('property' => 'additionalContactInformationSection', 'type' => 'section', 'label' => 'Additional Contact Information', 'hideInLists' => true, 'expandByDefault' => false, 'properties' => [
			'borrower_phonepro' => array('property' => 'borrower_phonepro', 'type' => 'text', 'label' => 'Secondary Phone' . $phoneFormat, 'description' => 'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_mobile' => array('property' => 'borrower_mobile', 'type' => 'text', 'label' => 'Other Phone' . $phoneFormat, 'description' => 'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_emailpro' => array('property' => 'borrower_emailpro', 'type' => 'email', 'label' => 'Secondary Email', 'description' => 'Email', 'maxLength' => 128, 'required' => false),
			'borrower_fax' => array('property' => 'borrower_fax', 'type' => 'text', 'label' => 'Fax' . $phoneFormat, 'description' => 'Fax', 'maxLength' => 128, 'required' => false),
		]);
		//Alternate address
		$fields['alternateAddressSection'] = array('property' => 'alternateAddressSection', 'type' => 'section', 'label' => 'Alternate address', 'hideInLists' => true, 'expandByDefault' => false, 'properties' => [
			'borrower_B_address' => array('property' => 'borrower_B_address', 'type' => 'text', 'label' => 'Alternate Address', 'description' => 'Address', 'maxLength' => 128, 'required' => false),
			'borrower_B_address2' => array('property' => 'borrower_B_address2', 'type' => 'text', 'label' => 'Address 2', 'accessibleLabel' => 'Alternate Address 2', 'description' => 'Second line of the address', 'maxLength' => 128, 'required' => false),
			'borrower_B_city' => array('property' => 'borrower_B_city', 'type' => 'text', 'label' => 'City', 'accessibleLabel' => 'Alternate City', 'description' => 'City', 'maxLength' => 48, 'required' => false),
			'borrower_B_state' => array('property' => 'borrower_B_state', 'type' => 'text', 'label' => 'State', 'accessibleLabel' => 'Alternate State', 'description' => 'State', 'maxLength' => 32, 'required' => false),
			'borrower_B_zipcode' => array('property' => 'borrower_B_zipcode', 'type' => 'text', 'label' => 'Zip Code', 'accessibleLabel' => 'Alternate Zip Code', 'description' => 'Zip Code', 'maxLength' => 32, 'required' => false),
			'borrower_B_country' => array('property' => 'borrower_B_country', 'type' => 'text', 'label' => 'Country', 'accessibleLabel' => 'Alternate Country', 'description' => 'Country', 'maxLength' => 32, 'required' => false),
			'borrower_B_phone' => array('property' => 'borrower_B_phone', 'type' => 'text', 'label' => 'Phone' . $phoneFormat, 'accessibleLabel' => 'Alternate Phone', 'description' => 'Phone', 'maxLength' => 128, 'required' => false),
			'borrower_B_email' => array('property' => 'borrower_B_email', 'type' => 'email', 'label' => 'Email', 'description' => 'Email', 'accessibleLabel' => 'Alternate Email', 'maxLength' => 128, 'required' => false),
			'borrower_contactnote' => array('property' => 'borrower_contactnote', 'type' => 'textarea', 'label' => 'Contact  Notes', 'description' => 'Additional information for the alternate contact', 'maxLength' => 128, 'required' => false),
		]);
		//Alternate contact
		$fields['alternateContactSection'] = array('property' => 'alternateContactSection', 'type' => 'section', 'label' => 'Alternate contact', 'hideInLists' => true, 'expandByDefault' => false, 'properties' => [
			'borrower_altcontactsurname' => array('property' => 'borrower_altcontactsurname', 'type' => 'text', 'label' => 'Surname', 'accessibleLabel' => 'Alternate Contact Surname', 'description' => 'Your last name', 'maxLength' => 60, 'required' => false),
			'borrower_altcontactfirstname' => array('property' => 'borrower_altcontactfirstname', 'type' => 'text', 'label' => 'First Name', 'accessibleLabel' => 'Alternate Contact First Name', 'description' => 'Your first name', 'maxLength' => 25, 'required' => false),
			'borrower_altcontactaddress1' => array('property' => 'borrower_altcontactaddress1', 'type' => 'text', 'label' => 'Address', 'accessibleLabel' => 'Alternate Contact Address', 'description' => 'Address', 'maxLength' => 128, 'required' => false),
			'borrower_altcontactaddress2' => array('property' => 'borrower_altcontactaddress2', 'type' => 'text', 'label' => 'Address 2', 'accessibleLabel' => 'Alternate Contact Address 2', 'description' => 'Second line of the address', 'maxLength' => 128, 'required' => false),
			'borrower_altcontactaddress3' => array('property' => 'borrower_altcontactaddress3', 'type' => 'text', 'label' => 'City', 'accessibleLabel' => 'Alternate Contact City', 'description' => 'City', 'maxLength' => 48, 'required' => false),
			'borrower_altcontactstate' => array('property' => 'borrower_altcontactstate', 'type' => 'text', 'label' => 'State', 'accessibleLabel' => 'Alternate Contact State', 'description' => 'State', 'maxLength' => 32, 'required' => false),
			'borrower_altcontactzipcode' => array('property' => 'borrower_altcontactzipcode', 'type' => 'text', 'label' => 'Zip Code', 'accessibleLabel' => 'Alternate Contact Zip Code', 'description' => 'Zip Code', 'maxLength' => 32, 'required' => false),
			'borrower_altcontactcountry' => array('property' => 'borrower_altcontactcountry', 'type' => 'text', 'label' => 'Country', 'accessibleLabel' => 'Alternate Contact Country', 'description' => 'Country', 'maxLength' => 32, 'required' => false),
			'borrower_altcontactphone' => array('property' => 'borrower_altcontactphone', 'type' => 'text', 'label' => 'Phone' . $phoneFormat, 'accessibleLabel' => 'Alternate Contact Phone', 'description' => 'Phone', 'maxLength' => 128, 'required' => false),
		]);
		if ($type == 'selfReg') {
			$passwordLabel = $library->loginFormPasswordLabel;
			$passwordNotes = $library->selfRegistrationPasswordNotes;
			$fields['passwordSection'] = array('property' => 'passwordSection', 'type' => 'section', 'label' => $passwordLabel, 'hideInLists' => true, 'expandByDefault' => true, 'properties' => [
				'borrower_password' => array('property' => 'borrower_password', 'type' => 'password', 'label' => $passwordLabel, 'description' => $passwordNotes, 'minLength' => 3, 'maxLength' => 25, 'showConfirm' => false, 'required' => false, 'showDescription' => true),
				'borrower_password2' => array('property' => 'borrower_password2', 'type' => 'password', 'label' => 'Confirm ' . $passwordLabel, 'description' => 'Reenter your PIN', 'minLength' => 3, 'maxLength' => 25, 'showConfirm' => false, 'required' => false),
			]);
		}

		$unwantedFields = array_flip($unwantedFields);
		if (array_key_exists('password', $unwantedFields)) {
			$unwantedFields['password2'] = true;
		}
		$requiredFields = array_flip($requiredFields);
		if (array_key_exists('password', $requiredFields)) {
			$requiredFields['password2'] = true;
		}
		foreach ($fields as $sectionKey => &$section) {
			if ($section['type'] == 'section') {
				$allFieldsHidden = true;
				foreach ($section['properties'] as $fieldKey => &$field) {
					$fieldName = str_replace('borrower_', '', $fieldKey);
					if (array_key_exists($fieldName, $unwantedFields)) {
						//There is a case here where a field is marked as both unwanted and required.  If that is the case, do not unset it, just change the type to hidden.
						if (array_key_exists($fieldName, $requiredFields)) {
							$section['properties'][$fieldKey]['type'] = 'hidden';
						} else {
							unset($section['properties'][$fieldKey]);
						}
					} else {
						$field['required'] = array_key_exists($fieldName, $requiredFields);
					}
					if (array_key_exists($fieldKey, $section['properties']) && $section['properties'][$fieldKey]['type'] != 'hidden') {
						$allFieldsHidden = false;
					}
				}
				if (empty($section['properties'])) {
					unset ($fields[$sectionKey]);
				} elseif ($allFieldsHidden) {
					$section['label'] = '';
				}
			}
		}

		return $fields;
	}

	function selfRegister()
	{
		global $library;
		$result = [
			'success' => false,
		];

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$selfRegPage = $this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl');
		$captcha = '';
		$captchaDigest = '';
		$captchaInfo = [];
		if (preg_match('%<span class="hint">(?:.*)<strong>(.*?)</strong></span>%s', $selfRegPage, $captchaInfo)) {
			$captcha = $captchaInfo[1];
		}
		$captchaInfo = [];
		if (preg_match('%<input type="hidden" name="captcha_digest" value="(.*?)" />%s', $selfRegPage, $captchaInfo)) {
			$captchaDigest = $captchaInfo[1];
		}

		$postFields = [];
		$postFields = $this->setPostField($postFields, 'borrower_branchcode', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_title', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_surname', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_firstname', $library->useAllCapsWhenSubmittingSelfRegistration);
		if (isset($_REQUEST['borrower_dateofbirth'])) {
			$postFields['borrower_dateofbirth'] = str_replace('-', '/', $_REQUEST['borrower_dateofbirth']);
		}
		$postFields = $this->setPostField($postFields, 'borrower_initials', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_othernames', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_sex', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_address', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_address2', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_city', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_state', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_zipcode', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_country', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_phone', $library->useAllCapsWhenSubmittingSelfRegistration, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
		$postFields = $this->setPostField($postFields, 'borrower_email', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_phonepro', $library->useAllCapsWhenSubmittingSelfRegistration, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
		$postFields = $this->setPostField($postFields, 'borrower_mobile', $library->useAllCapsWhenSubmittingSelfRegistration, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
		$postFields = $this->setPostField($postFields, 'borrower_emailpro', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_fax', $library->useAllCapsWhenSubmittingSelfRegistration, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
		$postFields = $this->setPostField($postFields, 'borrower_B_address', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_B_address2', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_B_city', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_B_state', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_B_zipcode', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_B_country', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_B_phone', $library->useAllCapsWhenSubmittingSelfRegistration, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
		$postFields = $this->setPostField($postFields, 'borrower_B_email', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_contactnote', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactsurname', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactfirstname', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactaddress1', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactaddress2', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactaddress3', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactstate', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactzipcode', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactcountry', $library->useAllCapsWhenSubmittingSelfRegistration);
		$postFields = $this->setPostField($postFields, 'borrower_altcontactphone', $library->useAllCapsWhenSubmittingSelfRegistration, $library->requireNumericPhoneNumbersWhenUpdatingProfile);
		$postFields = $this->setPostField($postFields, 'borrower_password');
		$postFields = $this->setPostField($postFields, 'borrower_password2');
		$postFields['captcha'] = $captcha;
		$postFields['captcha_digest'] = $captchaDigest;
		$postFields['action'] = 'create';
		$headers = [
			'Content-Type: application/x-www-form-urlencoded'
		];
		$this->opacCurlWrapper->addCustomHeaders($headers, false);
		$selfRegPageResponse = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-memberentry.pl', $postFields);

		$matches = [];
		if (preg_match('%<h1>Registration Complete!</h1>.*?<span id="patron-userid">(.*?)</span>.*?<span id="patron-password">(.*?)</span>.*?<span id="patron-cardnumber">(.*?)</span>%s', $selfRegPageResponse, $matches)) {
			$username = $matches[1];
			$password = $matches[2];
			$barcode = $matches[3];
			$result['success'] = true;
			$result['username'] = $username;
			$result['password'] = $password;
			$result['barcode'] = $barcode;
		}elseif (preg_match('%<h1>Registration Complete!</h1>.*?<span id="patron-userid">(.*?)</span>.*?<span id="patron-password">(.*?)</span>%s', $selfRegPageResponse, $matches)) {
			$username = $matches[1];
			$password = $matches[2];
			$result['success'] = true;
			$result['username'] = $username;
			$result['password'] = $password;
		}elseif (preg_match('%<h1>Registration Complete!</h1>%s', $selfRegPageResponse, $matches)) {
			$result['success'] = true;
			$result['message'] = "Your account was registered, but a barcode was not provided, please contact your library for barcode and password to use when logging in.";
		}elseif (preg_match('%<h1>Please confirm your registration</h1>%s', $selfRegPageResponse, $matches)) {
			//Load the patron's username and barcode
			$result['success'] = true;
			$result['message'] = "Your account was registered, and a confirmation email will be sent to the email you provided. Your account will not be activated until you follow the link provided in the confirmation email.";
		}elseif (preg_match('%This email address already exists in our database.%', $selfRegPageResponse)){
			$result['message'] = 'This email address already exists in our database. Please contact your library for account information or use a different email.';
		}
		return $result;
	}

	function updatePin(User $user, string $oldPin, string $newPin)
	{
		if ($user->cat_password != $oldPin) {
			return ['success' => false, 'message' => "The old PIN provided is incorrect."];
		}
		$result = ['success' => false, 'message' => "Unknown error updating password."];
		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$borrowerNumber = $user->username;
			$result = $this->resetPinInKoha($borrowerNumber, $newPin, $oauthToken);
		}
		return $result;
	}

	function hasMaterialsRequestSupport()
	{
		return true;
	}

	function getNewMaterialsRequestForm(User $user)
	{
		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM systempreferences where variable like 'OpacSuggestion%';";
		$results = mysqli_query($this->dbConnection, $sql);
		$kohaPreferences = [];
		while ($curRow = $results->fetch_assoc()) {
			$kohaPreferences[$curRow['variable']] = $curRow['value'];
		}

		if (isset($kohaPreferences['OPACSuggestionMandatoryFields'])){
			$mandatoryFields = array_flip(explode(',', $kohaPreferences['OPACSuggestionMandatoryFields']));
		}else{
			$mandatoryFields = [];
		}


		/** @noinspection SqlResolve */
		$itemTypesSQL = "SELECT * FROM authorised_values where category = 'SUGGEST_FORMAT' order by lib_opac";
		$itemTypesRS = mysqli_query($this->dbConnection, $itemTypesSQL);
		$itemTypes = [];
		$defaultItemType = '';
		while ($curRow = $itemTypesRS->fetch_assoc()) {
			$itemTypes[$curRow['authorised_value']] = $curRow['lib_opac'];
			if (strtoupper($curRow['authorised_value']) == 'BOOK' || strtoupper($curRow['authorised_value']) == 'BOOKS'){
				$defaultItemType = $curRow['authorised_value'];
			}
		}

		global $interface;
		$allowPurchaseSuggestionBranchChoice = $this->getKohaSystemPreference('AllowPurchaseSuggestionBranchChoice');
		$pickupLocations = [];
		if ($allowPurchaseSuggestionBranchChoice == 1){
			$locations = new Location();
			$locations->orderBy('displayName');
			$locations->whereAdd('validHoldPickupBranch != 2');
			$locations->find();
			while ($locations->fetch()) {
				$pickupLocations[$locations->code] = $locations->displayName;
			}
		}else{
			$userLocation = $user->getHomeLocation();
			$pickupLocations[$userLocation->code] = $userLocation->displayName;
		}

		$interface->assign('pickupLocations', $pickupLocations);

		$fields = [
			array('property' => 'patronId', 'type' => 'hidden', 'label' => 'Patron Id', 'default' => $user->id),
			array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title of the item to be purchased', 'maxLength' => 255, 'required' => true),
			array('property' => 'author', 'type' => 'text', 'label' => 'Author', 'description' => 'The author of the item to be purchased', 'maxLength' => 80, 'required' => false),
			array('property' => 'copyrightdate', 'type' => 'text', 'label' => 'Copyright Date', 'description' => 'Copyright or publication year, for example: 2016', 'maxLength' => 4, 'required' => false),
			array('property' => 'isbn', 'type' => 'text', 'label' => 'Standard number (ISBN, ISSN or other)', 'description' => '', 'maxLength' => 80, 'required' => false),
			array('property' => 'publishercode', 'type' => 'text', 'label' => 'Publisher', 'description' => '', 'maxLength' => 80, 'required' => false),
			array('property' => 'collectiontitle', 'type' => 'text', 'label' => 'Collection', 'description' => '', 'maxLength' => 80, 'required' => false),
			array('property' => 'place', 'type' => 'text', 'label' => 'Publication place', 'description' => '', 'maxLength' => 80, 'required' => false),
			array('property' => 'quantity', 'type' => 'text', 'label' => 'Quantity', 'description' => '', 'maxLength' => 4, 'required' => false, 'default' => 1),
			array('property' => 'itemtype', 'type' => 'enum', 'values' => $itemTypes, 'label' => 'Item type', 'description' => '', 'required' => false, 'default' => $defaultItemType),
			array('property' => 'branchcode', 'type' => 'enum', 'values' => $pickupLocations, 'label' => 'Library', 'description' => '', 'required' => false, 'default' => $user->getHomeLocation()->code),
			array('property' => 'note', 'type' => 'textarea', 'label' => 'Note', 'description' => '', 'required' => false),
		];

		foreach ($fields as &$field) {
			if (array_key_exists($field['property'], $mandatoryFields)) {
				$field['required'] = true;
			} else {
				$field['required'] = false;
			}
		}

		$interface->assign('submitUrl', '/MaterialsRequest/NewRequestIls');
		$interface->assign('structure', $fields);
		$interface->assign('saveButtonText', 'Submit your suggestion');
		$interface->assign('formLabel', 'Materials Request Form');

		$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
		$interface->assign('materialsRequestForm', $fieldsForm);

		return 'new-koha-request.tpl';
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	function processMaterialsRequestForm($user)
	{
		if (empty($user->cat_password)) {
			return ['success' => false, 'message' => 'Unable to place materials request in masquerade mode'];
		}
		$loginResult = $this->loginToKohaOpac($user);
		if (!$loginResult['success']) {
			return ['success' => false, 'message' => 'Unable to login to Koha'];
		} else {
			$postFields = [
				'title' => $_REQUEST['title'],
				'author' => $_REQUEST['author'],
				'copyrightdate' => $_REQUEST['copyrightdate'],
				'isbn' => $_REQUEST['isbn'],
				'publishercode' => $_REQUEST['publishercode'],
				'collectiontitle' => $_REQUEST['collectiontitle'],
				'place' => $_REQUEST['place'],
				'quantity' => $_REQUEST['quantity'],
				'itemtype' => $_REQUEST['itemtype'],
				'branchcode' => $_REQUEST['branchcode'],
				'note' => $_REQUEST['note'],
				'negcap' => '',
				'suggested_by_anyone' => 0,
				'op' => 'add_confirm'
			];
			$catalogUrl = $this->accountProfile->vendorOpacUrl;
			$submitSuggestionResponse = $this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl', $postFields);
			if (preg_match('%<div class="alert alert-error">(.*?)</div>%s', $submitSuggestionResponse, $matches)) {
				return ['success' => false, 'message' => $matches[1]];
			} elseif (preg_match('/Your purchase suggestions/', $submitSuggestionResponse)) {
				return ['success' => true, 'message' => 'Successfully submitted your request'];
			} else {
				return ['success' => false, 'message' => 'Unknown error submitting request'];
			}
		}
	}

	function getNumMaterialsRequests(User $user)
	{
		$numRequests = 0;
		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT count(*) as numRequests FROM suggestions where suggestedby = {$user->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		if ($curRow = $results->fetch_assoc()) {
			$numRequests = $curRow['numRequests'];
		}
		return $numRequests;
	}

	function getMaterialsRequests(User $user)
	{
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM suggestions where suggestedby = {$user->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		$allRequests = [];
		while ($curRow = $results->fetch_assoc()) {
			$managedBy = $curRow['managedby'];
			/** @noinspection SqlResolve */
			$userSql = "SELECT firstname, surname FROM borrowers where borrowernumber = " . mysqli_escape_string($this->dbConnection, $managedBy);
			$userResults = mysqli_query($this->dbConnection, $userSql);
			if ($userResults && $userResult = $userResults->fetch_assoc()) {
				$managedByStr = $userResult['firstname'] . ' ' . $userResult['surname'];
			} else {
				$managedByStr = '';
			}
			$request = [];
			$request['id'] = $curRow['suggestionid'];
			$request['summary'] = $curRow['title'];
			if (!empty($curRow['itemtype'])) {
				$request['summary'] .= ' - ' . $curRow['itemtype'];
			}
			if (!empty($curRow['author'])) {
				$request['summary'] .= '<br/>' . $curRow['author'];
			}
			if (!empty($curRow['copyrightdate'])) {
				$request['summary'] .= '<br/>' . $curRow['copyrightdate'];
			}
			$request['suggestedOn'] = $curRow['suggesteddate'];
			$request['note'] = $curRow['note'];
			$request['managedBy'] = $managedByStr;
			$request['status'] = ucwords(strtolower($curRow['STATUS']));
			if (!empty($curRow['reason'])) {
				$request['status'] .= ' (' . $curRow['reason'] . ')';
			}
			$allRequests[] = $request;
		}

		return $allRequests;
	}

	function getMaterialsRequestsPage(User $user)
	{
		$allRequests = $this->getMaterialsRequests($user);

		global $interface;
		$interface->assign('allRequests', $allRequests);

		return 'koha-requests.tpl';
	}

	function deleteMaterialsRequests(User $user)
	{
		$this->loginToKohaOpac($user);

		$catalogUrl = $this->accountProfile->vendorOpacUrl;
		$this->getKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl');

		$postFields = [
			'op' => 'delete_confirm',
			'delete_field' => $_REQUEST['delete_field']
		];
		$this->postToKohaPage($catalogUrl . '/cgi-bin/koha/opac-suggestions.pl', $postFields);

		global $memCache;
		$memCache->delete('koha_summary_' . $user->id);

		return [
			'success' => true,
			'message' => 'deleted your requests'
		];
	}

	/**
	 * Gets a form to update contact information within the ILS.
	 *
	 * @param User $user
	 * @return string|null
	 */
	function getPatronUpdateForm($user)
	{
		//This is very similar to a patron self so we are going to get those fields and then modify
		$patronUpdateFields = $this->getSelfRegistrationFields('patronUpdate');
		//Display sections as headings
		foreach ($patronUpdateFields as &$section) {
			if ($section['type'] == 'section') {
				$section['renderAsHeading'] = true;
			}
		}


		$this->initDatabaseConnection();
		//Set default values
		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM borrowers where borrowernumber = " . mysqli_escape_string($this->dbConnection, $user->username);
		$results = mysqli_query($this->dbConnection, $sql);
		if ($curRow = $results->fetch_assoc()) {
			foreach ($curRow as $property => $value) {
				$objectProperty = 'borrower_' . $property;
				if ($property == 'dateofbirth') {
					$user->$objectProperty = $this->kohaDateToAspenDate($value);
				} else {
					$user->$objectProperty = $value;
				}
			}
		}

		global $interface;
		$patronUpdateFields[] = array('property' => 'updateScope', 'type' => 'hidden', 'label' => 'Update Scope', 'description' => '', 'default' => 'contact');
		$patronUpdateFields[] = array('property' => 'patronId', 'type' => 'hidden', 'label' => 'Active Patron', 'description' => '', 'default' => $user->id);
		//These need to be part of the object, not just defaults because we can't combine default settings with a provided object.
		/** @noinspection PhpUndefinedFieldInspection */
		$user->updateScope = 'contact';
		/** @noinspection PhpUndefinedFieldInspection */
		$user->patronId = $user->id;

		$library = $user->getHomeLibrary();
		if (!$library->allowProfileUpdates){
			$interface->assign('canSave', false);
			foreach ($patronUpdateFields as $fieldName => &$fieldValue){
				if ($fieldValue['type'] == 'section'){
					foreach ($fieldValue['properties'] as $fieldName2 => &$fieldValue2){
						$fieldValue2['readOnly'] = true;
					}
				}else{
					$fieldValue['readOnly'] = true;
				}
			}
		}

		$interface->assign('submitUrl', '/MyAccount/ContactInformation');
		$interface->assign('structure', $patronUpdateFields);
		$interface->assign('object', $user);
		$interface->assign('saveButtonText', 'Update Contact Information');
		$interface->assign('formLabel', 'Update Contact Information Form');

		return $interface->fetch('DataObjectUtil/objectEditForm.tpl');
	}

	function kohaDateToAspenDate($date)
	{
		if (strlen($date) == 0) {
			return $date;
		} else {
			list($year, $month, $day) = explode('-', $date);
			return "$month-$day-$year";
		}
	}

	/**
	 * Converts the string for submission to the web form which is different than the
	 * format within the database.
	 * @param string $date
	 * @return string
	 */
	function aspenDateToKohaDate($date)
	{
		if (strlen($date) == 0) {
			return $date;
		} else {
			if (strpos($date, '-') !== false){
				list($month, $day, $year) = explode('-', $date);
				return "$month/$day/$year";
			}else{
				return $date;
			}
		}
	}

	function aspenDateToKohaApiDate($date)
	{
		if (strlen($date) == 0) {
			return $date;
		} else {
			if (strpos($date, '-') !== false){
				list($month, $day, $year) = explode('-', $date);
				return "$year-$month-$day";
			}else{
				return $date;
			}
		}
	}

	/**
	 * Import Lists from the ILS
	 *
	 * @param User $patron
	 * @return array - an array of results including the names of the lists that were imported as well as number of titles.
	 */
	function importListsFromIls($patron)
	{
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$this->initDatabaseConnection();
		$user = UserAccount::getLoggedInUser();
		$results = array(
			'totalTitles' => 0,
			'totalLists' => 0
		);

		//Get the lists for the user from the database
		/** @noinspection SqlResolve */
		$listSql = "SELECT * FROM virtualshelves where owner = {$patron->username}";
		$listResults = mysqli_query($this->dbConnection, $listSql);
		while ($curList = $listResults->fetch_assoc()) {
			$shelfNumber = $curList['shelfnumber'];
			$title = $curList['shelfname'];
			//Create the list (or find one that already exists)
			$newList = new UserList();
			$newList->user_id = $user->id;
			$newList->title = $title;
			if (!$newList->find(true)) {
				$newList->public = $curList['category'] == 2;
				$newList->insert();
			}elseif ($newList->deleted == 1){
				$newList->removeAllListEntries(true);
				$newList->deleted = 0;
				$newList->update();
			}

			$currentListTitles = $newList->getListTitles();

			/** @noinspection SqlResolve */
			$listContentsSql = "SELECT * FROM virtualshelfcontents where shelfnumber = {$shelfNumber}";
			$listContentResults = mysqli_query($this->dbConnection, $listContentsSql);
			while ($curTitle = $listContentResults->fetch_assoc()) {
				$bibNumber = $curTitle['biblionumber'];
				$primaryIdentifier = new GroupedWorkPrimaryIdentifier();
				$groupedWork = new GroupedWork();
				$primaryIdentifier->identifier = $bibNumber;
				$primaryIdentifier->type = $this->accountProfile->recordSource;

				if ($primaryIdentifier->find(true)) {
					$groupedWork->id = $primaryIdentifier->grouped_work_id;
					if ($groupedWork->find(true)) {
						//Check to see if this title is already on the list.
						$resourceOnList = false;
						foreach ($currentListTitles as $currentTitle) {
							if ($currentTitle->source == 'GroupedWork' && $currentTitle->sourceId == $groupedWork->permanent_id) {
								$resourceOnList = true;
								break;
							}
						}

						if (!$resourceOnList) {
							$listEntry = new UserListEntry();
							$listEntry->source = 'GroupedWork';
							$listEntry->sourceId = $groupedWork->permanent_id;
							$listEntry->listId = $newList->id;
							$listEntry->notes = '';
							$listEntry->dateAdded = time();
							$listEntry->insert();
							$currentListTitles[] = $listEntry;
						}
					} else {
						if (!isset($results['errors'])) {
							$results['errors'] = array();
						}
						$results['errors'][] = "\"$bibNumber\" on list $title could not be found in the catalog and was not imported.";
					}
				} else {
					//The title is not in the resources, add an error to the results
					if (!isset($results['errors'])) {
						$results['errors'] = array();
					}
					$results['errors'][] = "\"$bibNumber\" on list $title could not be found in the catalog and was not imported.";
				}
				$results['totalTitles']++;
			}
			$results['totalLists']++;
		}

		return $results;
	}

	public function getAccountSummary($user, $forceRefresh = false)
	{
		global $memCache;
		global $timer;
		global $configArray;
		global $library;

		$accountSummary = $memCache->get('koha_summary_' . $user->id);
		if ($accountSummary == false || isset($_REQUEST['reload']) || $forceRefresh) {
			$accountSummary = [
				'numCheckedOut' => 0,
				'numOverdue' => 0,
				'numAvailableHolds' => 0,
				'numUnavailableHolds' => 0,
				'totalFines' => 0
			];
			$this->initDatabaseConnection();

			//Get number of items checked out
			/** @noinspection SqlResolve */
			$checkedOutItemsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numCheckouts FROM issues WHERE borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
			$numCheckouts = 0;
			if ($checkedOutItemsRS) {
				$checkedOutItems = $checkedOutItemsRS->fetch_assoc();
				$numCheckouts = $checkedOutItems['numCheckouts'];
				$checkedOutItemsRS->close();
			}
			$accountSummary['numCheckedOut'] = $numCheckouts;

			$now = date('Y-m-d H:i:s');
			/** @noinspection SqlResolve */
			$overdueItemsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numOverdue FROM issues WHERE date_due < \'' . $now . '\' AND borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
			$numOverdue = 0;
			if ($overdueItemsRS) {
				$overdueItems = $overdueItemsRS->fetch_assoc();
				$numOverdue = $overdueItems['numOverdue'];
				$overdueItemsRS->close();
			}
			$accountSummary['numOverdue'] = $numOverdue;
			$timer->logTime("Loaded checkouts for Koha");

			//Get number of available holds
			if ($library->availableHoldDelay > 0){
				/** @noinspection SqlResolve */
				$holdsRS = mysqli_query($this->dbConnection, 'SELECT waitingdate, found FROM reserves WHERE borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
				if ($holdsRS) {
					while ($curRow = $holdsRS->fetch_assoc()) {
						if ($curRow['found'] !== 'W'){
							$accountSummary['numUnavailableHolds']++;
						}else{
							$holdAvailableOn = strtotime($curRow['waitingdate']);
							if ((time() - $holdAvailableOn) < 60 * 60 * 24 * $library->availableHoldDelay){
								$accountSummary['numUnavailableHolds']++;
							}else{
								$accountSummary['numAvailableHolds']++;
							}
						}
					}
					$holdsRS->close();
				}
			}else{
				/** @noinspection SqlResolve */
				$availableHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE found = "W" and borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
				$numAvailableHolds = 0;
				if ($availableHoldsRS) {
					$availableHolds = $availableHoldsRS->fetch_assoc();
					$numAvailableHolds = $availableHolds['numHolds'];
					$availableHoldsRS->close();
				}
				$accountSummary['numAvailableHolds'] = $numAvailableHolds;
				$timer->logTime("Loaded available holds for Koha");

				//Get number of unavailable
				/** @noinspection SqlResolve */
				$waitingHoldsRS = mysqli_query($this->dbConnection, 'SELECT count(*) as numHolds FROM reserves WHERE (found <> "W" or found is null) and borrowernumber = ' . $user->username, MYSQLI_USE_RESULT);
				$numWaitingHolds = 0;
				if ($waitingHoldsRS) {
					$waitingHolds = $waitingHoldsRS->fetch_assoc();
					$numWaitingHolds = $waitingHolds['numHolds'];
					$waitingHoldsRS->close();
				}
				$accountSummary['numUnavailableHolds'] = $numWaitingHolds;
			}
			$timer->logTime("Loaded total holds for Koha");

			//Get fines
			//Load fines from database
			$outstandingFines = $this->getOutstandingFineTotal($user);
			$accountSummary['totalFines'] = floatval($outstandingFines);

			//Get expiration information
			/** @noinspection SqlResolve */
			$sql = "SELECT dateexpiry from borrowers where borrowernumber = {$user->username}";

			$lookupUserResult = mysqli_query($this->dbConnection, $sql, MYSQLI_USE_RESULT);
			if ($lookupUserResult) {
				$userFromDb = $lookupUserResult->fetch_assoc();
				$accountSummary['expires'] = $userFromDb['dateexpiry']; //TODO: format is year-month-day; millennium is month-day-year; needs converting??

				$accountSummary['expired'] = 0; // default setting
				$accountSummary['expireClose'] = 0;

				if (!empty($userFromDb['dateexpiry'])) { // TODO: probably need a better check of this field
					list ($yearExp, $monthExp, $dayExp) = explode('-', $userFromDb['dateexpiry']);
					$timeExpire = strtotime($monthExp . "/" . $dayExp . "/" . $yearExp);
					$timeNow = time();
					$timeToExpire = $timeExpire - $timeNow;
					if ($timeToExpire <= 30 * 24 * 60 * 60) {
						if ($timeToExpire <= 0) {
							$accountSummary['expired'] = 1;
						}
						$accountSummary['expireClose'] = 1;
					}
				}
				$lookupUserResult->close();
			}

			$memCache->set('koha_summary_' . $user->id, $accountSummary, $configArray['Caching']['account_summary']);
		}
		return $accountSummary;
	}

	/**
	 * @param array $postFields
	 * @param string $postFieldName
	 * @param string $requestFieldName
	 * @param bool $convertToUpperCase
	 * @param bool $stripNonNumericCharacters
	 * @return array
	 */
	private function setPostFieldWithDifferentName(array $postFields, string $postFieldName, string $requestFieldName, $convertToUpperCase = false, $stripNonNumericCharacters = false): array
	{
		if (isset($_REQUEST[$requestFieldName])) {
			$field = $_REQUEST[$requestFieldName];
			if ($stripNonNumericCharacters){
				$field = preg_replace('/[^0-9]/', '', $field );
			}
			if ($convertToUpperCase){
				$postFields[$postFieldName] = strtoupper($field);
			}else{
				$postFields[$postFieldName] = $field;
			}

		}
		return $postFields;
	}

	/**
	 * @param array $postFields
	 * @param string $variableName
	 * @param bool $convertToUpperCase
	 * @param bool $stripNonNumericCharacters
	 * @return array
	 */
	private function setPostField(array $postFields, string $variableName, $convertToUpperCase = false, $stripNonNumericCharacters = false): array
	{
		if (isset($_REQUEST[$variableName])) {
			$field = $_REQUEST[$variableName];
			if ($stripNonNumericCharacters){
				$field = preg_replace('/[^0-9]/', '', $field );
			}
			if ($convertToUpperCase){
				$postFields[$variableName] = strtoupper($field);
			}else{
				$postFields[$variableName] = $field;
			}

		}
		return $postFields;
	}

	public function findNewUser($patronBarcode)
	{
		// Check the Koha database to see if the patron exists
		//Use MySQL connection to load data
		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT borrowernumber, cardnumber, userId from borrowers where cardnumber = '$patronBarcode' OR userId = '$patronBarcode'";

		$lookupUserResult = mysqli_query($this->dbConnection, $sql);
		if ($lookupUserResult->num_rows > 0) {
			$lookupUserRow = $lookupUserResult->fetch_assoc();
			$patronId = $lookupUserRow['borrowernumber'];
			$newUser = $this->loadPatronInfoFromDB($patronId, null);
			if (!empty($newUser) && !($newUser instanceof AspenError)) {
				$this->closeDatabaseConnection();
				return $newUser;
			}
		}
		$this->closeDatabaseConnection();
		return false;
	}

	public function showMessagingSettings()
	{
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = "SELECT * from systempreferences where variable = 'EnhancedMessagingPreferencesOPAC' OR variable = 'EnhancedMessagingPreferences'";
		$preferenceRS = mysqli_query($this->dbConnection, $sql);
		$allowed = true;
		while ($row = mysqli_fetch_assoc($preferenceRS)) {
			if ($row['value'] == 0) {
				$allowed = false;
			}
		}
		return $allowed;
	}

	public function getMessagingSettingsTemplate(User $user)
	{
		global $interface;
		$this->initDatabaseConnection();

		//Figure out if SMS and Phone notifications are enabled
		/** @noinspection SqlResolve */
		$systemPreferencesSql = "SELECT * FROM systempreferences where variable = 'SMSSendDriver' OR variable ='TalkingTechItivaPhoneNotification'";
		$systemPreferencesRS = mysqli_query($this->dbConnection, $systemPreferencesSql);
		while ($systemPreference = $systemPreferencesRS->fetch_assoc()) {
			if ($systemPreference['variable'] == 'SMSSendDriver') {
				$interface->assign('enableSmsMessaging', !empty($systemPreference['value']));
				//Load sms number and provider
				//Load available providers
				/** @noinspection SqlResolve */
				$smsProvidersSql = "SELECT * FROM sms_providers";
				$smsProvidersRS = mysqli_query($this->dbConnection, $smsProvidersSql);
				$smsProviders = [];
				while ($smsProvider = $smsProvidersRS->fetch_assoc()) {
					$smsProviders[$smsProvider['id']] = $smsProvider['name'];
				}
				$interface->assign('smsProviders', $smsProviders);
			} elseif ($systemPreference['variable'] == 'TalkingTechItivaPhoneNotification') {
				$interface->assign('enablePhoneMessaging', !empty($systemPreference['value']));
			}
		}

		/** @noinspection SqlResolve */
		$borrowerSql = "SELECT smsalertnumber, sms_provider_id FROM borrowers where borrowernumber = {$user->username}";
		$borrowerRS = mysqli_query($this->dbConnection, $borrowerSql);
		if ($borrowerRow = mysqli_fetch_assoc($borrowerRS)) {
			$interface->assign('smsAlertNumber', $borrowerRow['smsalertnumber']);
			$interface->assign('smsProviderId', $borrowerRow['sms_provider_id']);
		}

		//Lookup which transports are allowed
		/** @noinspection SqlResolve */
		$transportSettingSql = "SELECT message_attribute_id, MAX(is_digest) as allowDigests, message_transport_type FROM message_transports GROUP by message_attribute_id, message_transport_type";
		$transportSettingRS = mysqli_query($this->dbConnection, $transportSettingSql);
		$messagingSettings = [];
		while ($transportSetting = $transportSettingRS->fetch_assoc()) {
			$transportId = $transportSetting['message_attribute_id'];
			if (!array_key_exists($transportId, $messagingSettings)) {
				$messagingSettings[$transportId] = [
					'allowDigests' => $transportSetting['allowDigests'],
					'allowableTransports' => [],
					'wantsDigest' => 0,
					'selectedTransports' => [],
				];
			}
			$messagingSettings[$transportId]['allowableTransports'][$transportSetting['message_transport_type']] = $transportSetting['message_transport_type'];
		}

		//Get the list of notices to display information for
		/** @noinspection SqlResolve */
		$messageAttributesSql = "SELECT * FROM message_attributes";
		$messageAttributesRS = mysqli_query($this->dbConnection, $messageAttributesSql);
		$messageAttributes = [];
		while ($messageType = $messageAttributesRS->fetch_assoc()) {
			switch ($messageType['message_name']) {
				case "Item_Due":
					$messageType['label'] = 'Item due';
					break;
				case "Advance_Notice":
					$messageType['label'] = 'Advance notice';
					break;
				case "Hold_Filled":
					$messageType['label'] = 'Hold filled';
					break;
				case "Item_Check_in":
					$messageType['label'] = 'Item check-in';
					break;
				case "Item_Checkout":
					$messageType['label'] = 'Item checkout';
					break;
			}
			$messageAttributes[] = $messageType;
		}
		$interface->assign('messageAttributes', $messageAttributes);

		//Get messaging settings for the user
		/** @noinspection SqlResolve */
		$userMessagingSettingsSql = "SELECT borrower_message_preferences.message_attribute_id,
				borrower_message_preferences.wants_digest,
			    borrower_message_preferences.days_in_advance,
				borrower_message_transport_preferences.message_transport_type
			FROM   borrower_message_preferences
			LEFT JOIN borrower_message_transport_preferences
			ON     borrower_message_transport_preferences.borrower_message_preference_id = borrower_message_preferences.borrower_message_preference_id
			WHERE  borrower_message_preferences.borrowernumber = {$user->username}";
		$userMessagingSettingsRS = mysqli_query($this->dbConnection, $userMessagingSettingsSql);
		while ($userMessagingSetting = mysqli_fetch_assoc($userMessagingSettingsRS)) {
			$messageType = $userMessagingSetting['message_attribute_id'];
			if ($userMessagingSetting['wants_digest']) {
				$messagingSettings[$messageType]['wantsDigest'] = $userMessagingSetting['wants_digest'];
			}
			if ($userMessagingSetting['days_in_advance'] != null) {
				$messagingSettings[$messageType]['daysInAdvance'] = $userMessagingSetting['days_in_advance'];
			}
			if ($userMessagingSetting['message_transport_type'] != null) {
				$messagingSettings[$messageType]['selectedTransports'][$userMessagingSetting['message_transport_type']] = $userMessagingSetting['message_transport_type'];
			}
		}
		$interface->assign('messagingSettings', $messagingSettings);

		$validNoticeDays = [];
		for ($i = 0; $i <= 30; $i++) {
			$validNoticeDays[$i] = $i;
		}
		$interface->assign('validNoticeDays', $validNoticeDays);

		$library = $user->getHomeLibrary();
		if ($library->allowProfileUpdates){
			$interface->assign('canSave', true);
		}else{
			$interface->assign('canSave', false);
		}

		return 'kohaMessagingSettings.tpl';
	}

	public function processMessagingSettingsForm(User $user)
	{
		$result = $this->loginToKohaOpac($user);
		if (!$result['success']) {
			return $result;
		} else {
			$params = $_POST;
			unset ($params['submit']);

			$catalogUrl = $this->accountProfile->vendorOpacUrl;
			$updateMessageUrl = "$catalogUrl/cgi-bin/koha/opac-messaging.pl?";
			$getParams = [];
			foreach ($params as $key => $value) {
				//Koha is using non standard HTTP functionality
				//where array values are being passed without array bracket values
				if (is_array($value)) {
					foreach ($value as $arrayValue) {
						$getParams[] = urlencode($key) . '=' . urlencode($arrayValue);
					}
				} else {
					/** @noinspection SpellCheckingInspection */
					if ($key == 'SMSnumber') {
						$getParams[] = urlencode($key) . '=' . urlencode(preg_replace('/\\D/', '', $value));
					}else{
						$getParams[] = urlencode($key) . '=' . urlencode($value);
					}
				}
			}

			//Get the csr token
			$updatePage = $this->getKohaPage($updateMessageUrl);
			if (preg_match('%<input type="hidden" name="csrf_token" value="(.*?)" />%s', $updatePage, $matches)) {
				$getParams[] = 'csrf_token='. $matches[1];
			}

			$updateMessageUrl .= implode('&', $getParams);
			$result = $this->getKohaPage($updateMessageUrl);
			if (strpos($result, 'Settings updated') !== false) {
				$result = [
					'success' => true,
					'message' => 'Settings updated'
				];
			} else {
				$result = [
					'success' => false,
					'message' => 'Sorry your settings could not be updated, please contact the library.'
				];
			}
		}
		return $result;
	}

	/**
	 * @param $patron
	 * @param $recordId
	 * @param array $hold_result
	 * @return array
	 */
	private function getHoldMessageForSuccessfulHold($patron, $recordId, array $hold_result): array
	{
		$holds = $this->getHolds($patron, 1, -1, 'title');
		$hold_result['success'] = true;
		$hold_result['message'] = translate(['text'=>"ils_hold_success", 'defaultText'=>"Your hold was placed successfully."]);
		//Find the correct hold (will be unavailable)
		foreach ($holds['unavailable'] as $holdInfo) {
			if ($holdInfo['id'] == $recordId) {
				if (isset($holdInfo['position'])) {
					$hold_result['message'] .= translate(['text'=>"ils_hold_success_position", 'defaultText'=>"&nbsp;You are number <b>%1%</b> in the queue.", '1' => $holdInfo['position']]);
				}
				//Show the number of holds the patron has used.
				$accountSummary = $this->getAccountSummary($patron, true);
				$maxReserves = $this->getKohaSystemPreference('maxreserves');
				$totalHolds = $accountSummary['numAvailableHolds'] + $accountSummary['numUnavailableHolds'];
				$remainingHolds = $maxReserves - $totalHolds;
				if ($remainingHolds <= 3){
					$hold_result['message'] .= translate(['text'=>"ils_hold_success_total_remaining_holds", 'defaultText'=>"<br/>You have %1% holds currently and can place %2% additional holds.", 1=>$totalHolds, 2=>$remainingHolds]);
				}else{
					$hold_result['message'] .= translate(['text'=>"ils_hold_success_total_holds", 'defaultText'=>"<br/>You have %1% holds currently.", 1 => $totalHolds]);
				}

				break;
			}
		}
		global $memCache;
		$memCache->delete('koha_summary_' . $patron->id);
		return $hold_result;
	}

	public function completeFinePayment(User $patron, UserPayment $payment)
	{
		global $logger;
		$result = [
			'success' => false,
			'message' => 'Unknown error completing fine payment'
		];

		$kohaVersion = $this->getKohaVersion();
		$creditType = 'payment';
		if ((float)$kohaVersion >= 19.11){
			$creditType = 'PAYMENT';
		}

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
			$logger->log('Unable to authenticate with Koha while completing fine payment', Logger::LOG_ERROR);
		} else {
			$accountLinesPaid = explode(',', $payment->finesPaid);
			$partialPayments = [];
			$fullyPaidTotal = $payment->totalPaid;
			foreach ($accountLinesPaid as $index => $accountLinePaid){
				if (strpos($accountLinePaid, '|')){
					//Partial Payments are in the form of fineId|paymentAmount
					$accountLineInfo = explode('|', $accountLinePaid);
					$partialPayments[] = $accountLineInfo;
					$fullyPaidTotal -= $accountLineInfo[1];
					unset($accountLinesPaid[$index]);
				}else{
					$accountLinesPaid[$index] = (int)$accountLinePaid;
				}
			}

			//Process everything that has been fully paid
			$allPaymentsSucceed = true;
			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json;charset=UTF-8',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
			], true);
			$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$patron->username}/account/credits";
			if (count($accountLinesPaid) > 0) {
				$postVariables = [
					'account_lines_ids' => $accountLinesPaid,
					'amount' => (float)$fullyPaidTotal,
					'credit_type' => $creditType,
					'payment_type' => $payment->paymentType,
					'description' => 'Paid Online via Aspen Discovery',
					'note' => $payment->paymentType,
				];

				$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postVariables);
				if ($this->apiCurlWrapper->getResponseCode() != 200) {
					if (strlen($response) > 0) {
						$jsonResponse = json_decode($response);
						if ($jsonResponse) {
							$result['message'] = $jsonResponse->errors[0]->message;
						} else {
							$result['message'] = $response;
						}
					} else {
						$result['message'] = "Error {$this->apiCurlWrapper->getResponseCode()} updating your payment, please visit the library with your receipt.";
						$logger->log("Unable to authenticate with Koha while completing fine payment response code: {$this->apiCurlWrapper->getResponseCode()}", Logger::LOG_ERROR);
					}
					$allPaymentsSucceed = false;
				}
			}
			if (count($partialPayments) > 0){
				foreach ($partialPayments as $paymentInfo){
					$postVariables = [
						'account_lines_ids' => [(int)$paymentInfo[0]],
						'amount' => (float)$paymentInfo[1],
						'credit_type' => $creditType,
						'payment_type' => $payment->paymentType,
						'description' => 'Paid Online via Aspen Discovery',
						'note' => $payment->paymentType,
					];

					$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postVariables);
					if ($this->apiCurlWrapper->getResponseCode() != 200) {
						if (!isset($result['message'])) {$result['message'] = '';}
						if (strlen($response) > 0) {
							$jsonResponse = json_decode($response);
							if ($jsonResponse) {
								$result['message'] .= $jsonResponse->errors[0]['message'];
							} else {
								$result['message'] .= $response;
							}
						} else {
							$result['message'] .= "Error {$this->apiCurlWrapper->getResponseCode()} updating your payment, please visit the library with your receipt.";
							$logger->log("Error {$this->apiCurlWrapper->getResponseCode()} updating your payment", Logger::LOG_ERROR);
						}
						$allPaymentsSucceed = false;
					}
				}
			}
			if ($allPaymentsSucceed){
				$result = [
					'success' => true,
					'message' => 'Your fines have been paid successfully, thank you.'
				];
			}
		}
		global $memCache;
		$memCache->delete('koha_summary_' . $patron->id);
		return $result;
	}

	public function patronEligibleForHolds(User $patron)
	{
		$result = [
			'isEligible' => true,
			'message' => '',
			'fineLimitReached' => false,
			'maxPhysicalCheckoutsReached' => false
		];
		$this->initDatabaseConnection();

		$maxOutstanding = $this->getKohaSystemPreference('MaxOutstanding');

		$accountSummary = $this->getAccountSummary($patron, true);
		if ($maxOutstanding > 0){
			$totalFines = $accountSummary['totalFines'];
			if ($totalFines > $maxOutstanding){
				$result['isEligible'] = false;
				$result['fineLimitReached'] = true;
				$result['message'] = translate(['text' => 'outstanding_fine_limit', 'defaultText' => 'Sorry, your account has too many outstanding fines to place holds.']);
			}
		}

		//Check maximum holds
		$maxHolds = $this->getKohaSystemPreference('maxreserves');
		//Get total holds
		$currentHoldsForUser = $accountSummary['numAvailableHolds'] + $accountSummary['numUnavailableHolds'];
		if ($currentHoldsForUser >= $maxHolds) {
			$result['isEligible'] = false;
			$result['maxPhysicalCheckoutsReached'] = true;
			if (strlen($result['message']) > 0){
				$result['message'] .= '<br/>';
			}
			$result['message'] .= translate(['text' => 'outstanding_holds_limit', 'defaultText' => 'Sorry, you have reached the maximum number of holds for your account.']);
		}

		//Check if the patron is expired
		if ($accountSummary['expired'] == 1) {
			$blockExpiredPatronOpacActions = $this->getKohaSystemPreference('BlockExpiredPatronOpacActions');
			if ($blockExpiredPatronOpacActions == 1){
				$result['isEligible'] = false;
				$result['expiredPatronWhoCannotPlaceHolds'] = true;
				$result['message'] = translate(['text' => 'expired_patron_cannot_place_holds', 'defaultText' => 'Sorry, your account has expired. Please renew your account to place holds.']);
			}
		}

		return $result;
	}

	public function getShowAutoRenewSwitch(User $patron)
	{
		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM systempreferences where variable = 'AllowPatronToControlAutorenewal';";
		$results = mysqli_query($this->dbConnection, $sql);
		$showAutoRenew = false;
		while ($curRow = $results->fetch_assoc()) {
			$showAutoRenew = $curRow['value'];
		}
		return $showAutoRenew;
	}

	public function isAutoRenewalEnabledForUser(User $patron)
	{
		$this->initDatabaseConnection();

		/** @noinspection SqlResolve */
		$sql = "SELECT autorenew_checkouts FROM borrowers where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		$autoRenewEnabled = false;
		if ($results !== false) {
			while ($curRow = $results->fetch_assoc()) {
				$autoRenewEnabled = $curRow['autorenew_checkouts'];
				break;
			}
		}
		return $autoRenewEnabled;
	}

	public function updateAutoRenewal(User $patron, bool $allowAutoRenewal)
	{
		$result = [
			'success' => false,
			'message' => 'Unknown error updating auto renewal'
		];

		//Load required fields from Koha here to make sure we don't wipe them out
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = "SELECT address, city FROM borrowers where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		$address = '';
		$city = '';
		if ($results !== false) {
			while ($curRow = $results->fetch_assoc()) {
				$address = $curRow['address'];
				$city = $curRow['city'];
			}
		}

		$postVariables = [
			'surname' => $patron->lastname,
			'address' => $address,
			'city' => $city,
			'library_id' => Location::getUserHomeLocation()->code,
			'category_id' => $patron->patronType,
			'autorenew_checkouts' => (bool)$allowAutoRenewal,
		];

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$patron->username}";
			$postParams = json_encode($postVariables);

			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json;charset=UTF-8',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
			], true);
			$response = $this->apiCurlWrapper->curlSendPage($apiUrl, 'PUT', $postParams);
			if ($this->apiCurlWrapper->getResponseCode() != 200) {
				if (strlen($response) > 0) {
					$jsonResponse = json_decode($response);
					if ($jsonResponse) {
						$result['message'] = $jsonResponse->error;
					} else {
						$result['message'] = $response;
					}
				} else {
					$result['message'] = "Error {$this->apiCurlWrapper->getResponseCode()} updating your account.";
				}

			} else {
				$response = json_decode($response);
				if ($response->autorenew_checkouts == $allowAutoRenewal){
					$result = [
						'success' => true,
						'message' => 'Your account was updated successfully.'
					];
				}else{
					$result = [
						'success' => true,
						'message' => 'Error updating this setting in the system.'
					];
				}
			}
		}
		return $result;
	}

	function getPasswordRecoveryTemplate(){
		global $interface;
		if (isset($_REQUEST['uniqueKey'])){
			$error = null;
			$uniqueKey = $_REQUEST['uniqueKey'];
			$interface->assign('uniqueKey', $uniqueKey);

			//Validate that the unique key is valid
			$this->initDatabaseConnection();

			/** @noinspection SqlResolve */
			$sql = "SELECT * from borrower_password_recovery where uuid = '" . mysqli_escape_string($this->dbConnection, $uniqueKey) . "'";
			$lookupResult = mysqli_query($this->dbConnection, $sql);
			$uniqueKeyValid = false;
			if ($lookupResult->num_rows > 0) {
				$lookupResultRow = $lookupResult->fetch_assoc();
				if (date_create($lookupResultRow['valid_until'])->getTimestamp() > time()){
					$uniqueKeyValid = true;
				}
			}
			if (!$uniqueKeyValid){
				$error = translate(['text' => 'invalid_pass_reset_uuid', 'defaultText' => ' The link you clicked is either invalid, or expired.<br/>Be sure you used the link from the email, or contact library staff for assistance.<br/>Please contact the library if you need further assistance.']);
			}

			$interface->assign('error', $error);

			$pinValidationRules = $this->getPasswordPinValidationRules();
			$interface->assign('pinValidationRules', $pinValidationRules);

			return 'kohaPasswordRecovery.tpl';
		}else{
			//No key provided, go back to the starting point
			header('Location: /MyAccount/EmailResetPin');
			die();
		}
	}

	function processPasswordRecovery(){
		global $interface;
		if (isset($_REQUEST['uniqueKey'])){
			$error = null;
			$uniqueKey = $_REQUEST['uniqueKey'];
			$borrowerNumber = null;

			//Validate that the unique key is valid
			$this->initDatabaseConnection();

			/** @noinspection SqlResolve */
			$sql = "SELECT * from borrower_password_recovery where uuid = '" . mysqli_escape_string($this->dbConnection, $uniqueKey) . "'";
			$lookupResult = mysqli_query($this->dbConnection, $sql);
			$uniqueKeyValid = false;
			if ($lookupResult->num_rows > 0) {
				$lookupResultRow = $lookupResult->fetch_assoc();
				if (date_create($lookupResultRow['valid_until'])->getTimestamp() > time()){
					$borrowerNumber = $lookupResultRow['borrowernumber'];
					$uniqueKeyValid = true;
				}
			}
			if (!$uniqueKeyValid){
				$error = translate(['text' => 'invalid_pass_reset_uuid', 'defaultText' => ' The link you clicked is either invalid, or expired.<br/>Be sure you used the link from the email, or contact library staff for assistance.<br/>Please contact the library if you need further assistance.']);
			}else{
				$oauthToken = $this->getOAuthToken();
				if ($oauthToken == false) {
					$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
				} else {
					$result = $this->resetPinInKoha($borrowerNumber, $_REQUEST['pin1'], $oauthToken);
					if ($result['success'] == false){
						$error = $result['errors'];
					}else{
						$interface->assign('result', $result);
					}
				}
			}

			$interface->assign('error', $error);
			return 'kohaPasswordRecoveryResult.tpl';
		}else{
			//No key provided, go back to the starting point
			header('Location: /MyAccount/EmailResetPin');
			die();
		}
	}

	/**
	 * @param $borrowerNumber
	 * @param string $newPin
	 * @param string $oauthToken
	 * @return array
	 */
	protected function resetPinInKoha($borrowerNumber, string $newPin, string $oauthToken): array
	{
		$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$borrowerNumber}/password";
		$postParams = [];
		$postParams['password'] = $newPin;
		$postParams['password_2'] = $newPin;
		$postParams = json_encode($postParams);

		$this->apiCurlWrapper->addCustomHeaders([
			'Authorization: Bearer ' . $oauthToken,
			'User-Agent: Aspen Discovery',
			'Accept: */*',
			'Cache-Control: no-cache',
			'Content-Type: application/json',
			'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
			'Accept-Encoding: gzip, deflate',
		], true);
		$response = $this->apiCurlWrapper->curlPostBodyData($apiUrl, $postParams, false);
		if ($this->apiCurlWrapper->getResponseCode() != 200) {
			if (strlen($response) > 0) {
				$jsonResponse = json_decode($response);
				if ($jsonResponse) {
					return ['success' => false, 'message' => $jsonResponse->error];
				} else {
					return ['success' => false, 'message' => $response];
				}
			} else {
				return ['success' => false, 'message' => "Error {$this->apiCurlWrapper->getResponseCode()} updating your PIN."];
			}

		} else {
			return ['success' => true, 'message' => 'Your password was updated successfully.'];
		}
	}

	private function getKohaVersion()
	{
		return $this->getKohaSystemPreference('Version');
	}

	private function getKohaSystemPreference(string $preferenceName)
	{
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = "SELECT value FROM systempreferences WHERE variable='$preferenceName';";
		$results = mysqli_query($this->dbConnection, $sql);
		$preference = '';
		while ($curRow = $results->fetch_assoc()) {
			$preference = $curRow['value'];
		}
		$results->close();
		return $preference;
	}

	function getPasswordPinValidationRules(){
		return [
			'minLength' => $this->getKohaSystemPreference('minPasswordLength'),
			'maxLength' => 60,
			'onlyDigitsAllowed' => false,
		];
	}

	public function hasEditableUsername()
	{
		return true;
	}

	public function getEditableUsername(User $user)
	{
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = "SELECT userId from borrowers where borrowernumber = {$user->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		if ($results !== false) {
			if ($curRow = $results->fetch_assoc()) {
				return $curRow['userId'];
			}
		}
		return null;
	}

	public function updateEditableUsername(User $patron, $username)
	{
		$result = [
			'success' => false,
			'message' => 'Unknown error updating username'
		];
		$this->initDatabaseConnection();
		//Check to see if the username is already in use
		/** @noinspection SqlResolve */
		$sql = "SELECT * FROM borrowers where userId = '{$username}' and borrowernumber != {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		if ($results !== false) {
			if ($results->fetch_assoc()){
				return [
					'success' => false,
					'message' => 'Sorry, that username is not available.'
				];
			}
		}
		//Load required fields from Koha here to make sure we don't wipe them out
		/** @noinspection SqlResolve */
		$sql = "SELECT address, city FROM borrowers where borrowernumber = {$patron->username}";
		$results = mysqli_query($this->dbConnection, $sql);
		$address = '';
		$city = '';
		if ($results !== false) {
			while ($curRow = $results->fetch_assoc()) {
				$address = $curRow['address'];
				$city = $curRow['city'];
			}
		}

		$postVariables = [
			'surname' => $patron->lastname,
			'address' => $address,
			'city' => $city,
			'library_id' => Location::getUserHomeLocation()->code,
			'category_id' => $patron->patronType,
			'userid' => $username,
		];

		$oauthToken = $this->getOAuthToken();
		if ($oauthToken == false) {
			$result['message'] = translate(['text' => 'unable_to_authenticate', 'defaultText' => 'Unable to authenticate with the ILS.  Please try again later or contact the library.']);
		} else {
			$apiUrl = $this->getWebServiceURL() . "/api/v1/patrons/{$patron->username}";
			$postParams = json_encode($postVariables);

			$this->apiCurlWrapper->addCustomHeaders([
				'Authorization: Bearer ' . $oauthToken,
				'User-Agent: Aspen Discovery',
				'Accept: */*',
				'Cache-Control: no-cache',
				'Content-Type: application/json;charset=UTF-8',
				'Host: ' . preg_replace('~http[s]?://~', '', $this->getWebServiceURL()),
			], true);
			$response = $this->apiCurlWrapper->curlSendPage($apiUrl, 'PUT', $postParams);
			if ($this->apiCurlWrapper->getResponseCode() != 200) {
				if (strlen($response) > 0) {
					$jsonResponse = json_decode($response);
					if ($jsonResponse) {
						$result['message'] = $jsonResponse->error;
					} else {
						$result['message'] = $response;
					}
				} else {
					$result['message'] = "Error {$this->apiCurlWrapper->getResponseCode()} updating your account.";
				}

			} else {
				$response = json_decode($response);
				if ($response->userid == $username){
					$result = [
						'success' => true,
						'message' => 'Your account was updated successfully.'
					];
				}else{
					$result = [
						'success' => true,
						'message' => 'Error updating this setting in the system.'
					];
				}
			}
		}
		return $result;
	}
}