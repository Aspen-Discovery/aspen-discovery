<?php

/**
 * Complete integration via APIs including availability and account informatino.
 *
 * Copyright (C) Douglas County Libraries 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class OverDriveDriver3 {
	public $version = 3;

	protected $requirePin,
		$ILSName;


	protected $format_map = array(
		'ebook-epub-adobe' => 'Adobe EPUB eBook',
		'ebook-epub-open' => 'Open EPUB eBook',
		'ebook-pdf-adobe' => 'Adobe PDF eBook',
		'ebook-pdf-open' => 'Open PDF eBook',
		'ebook-kindle' => 'Kindle Book',
		'ebook-disney' => 'Disney Online Book',
		'ebook-overdrive' => 'OverDrive Read',
		'ebook-microsoft' => 'Microsoft eBook',
		'audiobook-wma' => 'OverDrive WMA Audiobook',
		'audiobook-mp3' => 'OverDrive MP3 Audiobook',
		'audiobook-streaming' => 'Streaming Audiobook',
		'music-wma' => 'OverDrive Music',
		'video-wmv' => 'OverDrive Video',
		'video-wmv-mobile' => 'OverDrive Video (mobile)',
		'periodicals-nook' => 'NOOK Periodicals',
		'audiobook-overdrive' => 'OverDrive Listen',
		'video-streaming' => 'OverDrive Video',
		'ebook-mediado' => 'MediaDo Reader',
	);

	private function _connectToAPI($forceNewConnection = false){
		/** @var Memcache $memCache */
		global $memCache;
		$tokenData = $memCache->get('overdrive_token');
		if ($forceNewConnection || $tokenData == false){
			global $configArray;
			if (isset($configArray['OverDrive']['clientKey']) && $configArray['OverDrive']['clientKey'] != '' && isset($configArray['OverDrive']['clientSecret']) && $configArray['OverDrive']['clientSecret'] != ''){
				$ch = curl_init("https://oauth.overdrive.com/token");
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
				curl_setopt($ch, CURLOPT_USERPWD, $configArray['OverDrive']['clientKey'] . ":" . $configArray['OverDrive']['clientSecret']);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$return = curl_exec($ch);
				curl_close($ch);
				$tokenData = json_decode($return);
				if ($tokenData){
					$memCache->set('overdrive_token', $tokenData, 0, $tokenData->expires_in - 10);
				}
			}else{
				//OverDrive is not configured
				return false;
			}
		}
		return $tokenData;
	}

	//private function _connectToPatronAPI($patronBarcode, $patronPin = 1234, $forceNewConnection = false){
	private function _connectToPatronAPI($user, $patronBarcode, $patronPin, $forceNewConnection = false){
		/** @var Memcache $memCache */
		global $memCache;
		global $timer;
		$patronTokenData = $memCache->get('overdrive_patron_token_' . $patronBarcode);
		if ($forceNewConnection || $patronTokenData == false){
			$tokenData = $this->_connectToAPI($forceNewConnection);
			$timer->logTime("Connected to OverDrive API");
			if ($tokenData){
				global $configArray;
				$ch = curl_init("https://oauth-patron.overdrive.com/patrontoken");
				if (!isset($configArray['OverDrive']['patronWebsiteId'])){
					return false;
				}
				$websiteId = $configArray['OverDrive']['patronWebsiteId'];
				//$websiteId = 100300;

				$ilsname = $this->getILSName($user);
				if (!$ilsname) {
					return false;
				}
				//$ilsname = "default";

				if (!isset($configArray['OverDrive']['clientSecret'])){
					return false;
				}
				$clientSecret = $configArray['OverDrive']['clientSecret'];
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$encodedAuthValue = base64_encode($configArray['OverDrive']['clientKey'] . ":" . $clientSecret);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
					"Authorization: Basic " . $encodedAuthValue,
					"User-Agent: VuFind-Plus"
				));
				//curl_setopt($ch, CURLOPT_USERPWD, "");
				//$clientSecret = $configArray['OverDrive']['clientSecret'];
				//curl_setopt($ch, CURLOPT_USERPWD, $configArray['OverDrive']['clientKey'] . ":" . $clientSecret);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
				curl_setopt($ch, CURLOPT_POST, 1);

				if ($patronPin == null){
					$postFields = "grant_type=password&username={$patronBarcode}&password=ignore&password_required=false&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}else{
					$postFields = "grant_type=password&username={$patronBarcode}&password={$patronPin}&password_required=true&scope=websiteId:{$websiteId}%20ilsname:{$ilsname}";
				}
				//$postFields = "grant_type=client_credentials&scope=websiteid:{$websiteId}%20ilsname:{$ilsname}%20cardnumber:{$patronBarcode}";

				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

				$return = curl_exec($ch);
				$curlInfo = curl_getinfo($ch);
				$timer->logTime("Logged $patronBarcode into OverDrive API");
				curl_close($ch);
				$patronTokenData = json_decode($return);
				$timer->logTime("Decoded return for login of $patronBarcode into OverDrive API");
				if ($patronTokenData){
					if (isset($patronTokenData->error)){
						if ($patronTokenData->error == 'unauthorized_client'){ // login failure
							// patrons with too high a fine amount will get this result.
							return false;
						}else{
							if ($configArray['System']['debug']){
								echo("Error connecting to overdrive apis ". $patronTokenData->error);
							}
						}
					}else{
						if (property_exists($patronTokenData, 'expires_in')){
							$memCache->set('overdrive_patron_token_' . $patronBarcode, $patronTokenData, 0, $patronTokenData->expires_in - 10);
						}
					}
				}
			}else{
				return false;
			}
		}
		return $patronTokenData;
	}

	public function _callUrl($url){
		$tokenData = $this->_connectToAPI();
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}", "User-Agent: VuFind-Plus"));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			curl_close($ch);
			$returnVal = json_decode($return);
			//print_r($returnVal);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}
		}
		return null;
	}

	private function getILSName($user){
		if (!isset($this->ILSName)) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			global $library, $configArray;
			$patronHomeLibrary = Library::getPatronHomeLibrary($user);
			if (!empty($patronHomeLibrary->overdriveAuthenticationILSName)) {
				$this->ILSName = $patronHomeLibrary->overdriveAuthenticationILSName;
			}elseif (!empty($library->overdriveAuthenticationILSName)) {
				$this->ILSName = $library->overdriveAuthenticationILSName;
			} elseif (isset($configArray['OverDrive']['LibraryCardILS'])){
				$this->ILSName = $configArray['OverDrive']['LibraryCardILS'];
			}
		}
		return $this->ILSName;
	}

	/**
	 * @param $user User
	 * @return bool
	 */
	private function getRequirePin($user){
		if (!isset($this->requirePin)) {
			// use library setting if it has a value. if no library setting, use the configuration setting.
			global $library, $configArray;
			$patronHomeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			if (!empty($patronHomeLibrary->overdriveRequirePin)) {
				$this->requirePin = $patronHomeLibrary->overdriveRequirePin;
			}elseif (isset($library->overdriveRequirePin)) {
				$this->requirePin = $library->overdriveRequirePin;
			} elseif (isset($configArray['OverDrive']['requirePin'])){
				$this->requirePin = $configArray['OverDrive']['requirePin'];
			} else {
				$this->requirePin = false;
			}
		}
		return $this->requirePin;
	}

	public function _callPatronUrl($user, $url, $postParams = null){
		global $configArray;

		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		$userBarcode = $user->$barcodeProperty;
		if ($this->getRequirePin($user)){
			$userPin = ($barcodeProperty == 'cat_username') ? $user->cat_password : $user->cat_username;
				// determine which column is the pin by using the opposing field to the barcode. (between pin & username)
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, $userPin, false);
			// this worked for flatirons checkout.  plb 1-13-2015
//			$tokenData = $this->_connectToPatronAPI($user->cat_username, $user->cat_password, false);
		}else{
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, null, false);
		}
		if ($tokenData){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if (isset($tokenData->token_type) && isset($tokenData->access_token)){
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$headers = array(
					"Authorization: $authorizationData",
					"User-Agent: VuFind-Plus",
					"Host: patron.api.overdrive.com" // production
					//"Host: integration-patron.api.overdrive.com" // testing
				);
			}else{
				//The user is not valid
				if (isset($configArray['Site']['debug']) && $configArray['Site']['debug'] == true){
					print_r($tokenData);
				}
				return false;
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			if ($postParams != null){
				curl_setopt($ch, CURLOPT_POST, 1);
				//Convert post fields to json
				$jsonData = array('fields' => array());
				foreach ($postParams as $key => $value){
					$jsonData['fields'][] = array(
						'name' => $key,
						'value' => $value
					);
				}
				$postData = json_encode($jsonData);
				//print_r($postData);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
				$headers[] = 'Content-Type: application/vnd.overdrive.content.api+json';
			}else{
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

				$return = curl_exec($ch);
			$curlInfo = curl_getinfo($ch);
			curl_close($ch);
			$returnVal = json_decode($return);
			//print_r($returnVal);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}
		}
		return false;
	}

	private function _callPatronDeleteUrl($user, $patronBarcode, $patronPin, $url){
		$tokenData = $this->_connectToPatronAPI($user, $patronBarcode, $patronPin, false);
		//TODO: Remove || true when oauth works
		if ($tokenData || true){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			if ($tokenData){
				$authorizationData = $tokenData->token_type . ' ' . $tokenData->access_token;
				$headers = array(
					"Authorization: $authorizationData",
					"User-Agent: VuFind-Plus",
					"Host: patron.api.overdrive.com",
					//"Host: integration-patron.api.overdrive.com"
				);
			}else{
				$headers = array("User-Agent: VuFind-Plus", "Host: api.overdrive.com");
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$return = curl_exec($ch);
			$returnInfo = curl_getinfo($ch);
			if ($returnInfo['http_code'] == 204){
				$result = true;
			}else{
				//echo("Response code was " . $returnInfo['http_code']);
				$result = false;
			}
			curl_close($ch);
			$returnVal = json_decode($return);
			//print_r($returnVal);
			if ($returnVal != null){
				if (!isset($returnVal->message) || $returnVal->message != 'An unexpected error has occurred.'){
					return $returnVal;
				}
			}else{
				return $result;
			}
		}
		return false;
	}

	public function getLibraryAccountInformation(){
		global $configArray;
		$libraryId = $configArray['OverDrive']['accountId'];
		return $this->_callUrl("https://api.overdrive.com/v1/libraries/$libraryId");
	}

	public function getAdvantageAccountInformation(){
		global $configArray;
		$libraryId = $configArray['OverDrive']['accountId'];
		return $this->_callUrl("https://api.overdrive.com/v1/libraries/$libraryId/advantageAccounts");
	}

	public function getProductsInAccount($productsUrl = null, $start = 0, $limit = 25){
		global $configArray;
		if ($productsUrl == null){
			$libraryId = $configArray['OverDrive']['accountId'];
			$productsUrl = "https://api.overdrive.com/v1/collections/$libraryId/products";
		}
		$productsUrl .= "?offset=$start&limit=$limit";
		return $this->_callUrl($productsUrl);
	}

	public function getProductById($overDriveId, $productsKey = null){
		$productsUrl = "https://api.overdrive.com/v1/collections/$productsKey/products";
		$productsUrl .= "?crossRefId=$overDriveId";
		return $this->_callUrl($productsUrl);
	}

	public function getProductMetadata($overDriveId, $productsKey = null){
		global $configArray;
		if ($productsKey == null){
			$productsKey = $configArray['OverDrive']['productsKey'];
		}
		$overDriveId= strtoupper($overDriveId);
		$metadataUrl = "https://api.overdrive.com/v1/collections/$productsKey/products/$overDriveId/metadata";
		//echo($metadataUrl);
		return $this->_callUrl($metadataUrl);
	}

	public function getProductAvailability($overDriveId, $productsKey = null){
		global $configArray;
		if ($productsKey == null){
			$productsKey = $configArray['OverDrive']['productsKey'];
		}
		$availabilityUrl = "https://api.overdrive.com/v2/collections/$productsKey/products/$overDriveId/availability";
		//print_r($availabilityUrl);
		return $this->_callUrl($availabilityUrl);
	}

	private function _parseLendingOptions($lendingPeriods){
		$lendingOptions = array();
		//print_r($lendingPeriods);
		if (preg_match('/<script>.*?var hazVariableLending.*?<\/script>.*?<noscript>(.*?)<\/noscript>/si', $lendingPeriods, $matches)){
			preg_match_all('/<li>\\s?\\d+\\s-\\s(.*?)<select name="(.*?)">(.*?)<\/select><\/li>/si', $matches[1], $lendingPeriodInfo, PREG_SET_ORDER);
			for ($i = 0; $i < count($lendingPeriodInfo); $i++){
				$lendingOption = array();
				$lendingOption['name'] = $lendingPeriodInfo[$i][1];
				$lendingOption['id'] = $lendingPeriodInfo[$i][2];
				$options = $lendingPeriodInfo[$i][3];
				$lendingOption['options']= array();
				preg_match_all('/<option value="(.*?)".*?(selected="selected")?>(.*?)<\/option>/si', $options, $optionInfo, PREG_SET_ORDER);
				for ($j = 0; $j < count($optionInfo); $j++){
					$option = array();
					$option['value'] = $optionInfo[$j][1];
					$option['selected'] = strlen($optionInfo[$j][2]) > 0;
					$option['name'] = $optionInfo[$j][3];
					$lendingOption['options'][] = $option;
				}
				$lendingOptions[] = $lendingOption;
			}
		}
		//print_r($lendingOptions);
		return $lendingOptions;
	}

	private $checkouts = array();

	/**
	 * Loads information about items that the user has checked out in OverDrive
	 *
	 * @param User $user
	 * @param array $overDriveInfo optional array of information loaded from _loginToOverDrive to improve performance.
	 *
	 * @return array
	 */
	public function getOverDriveCheckedOutItems($user, $overDriveInfo = null, $forSummary = false){
		if (isset($this->checkouts[$user->id])){
			return $this->checkouts[$user->id];
		}
		global $configArray;
		if (!$this->isUserValidForOverDrive($user)){
			return array();
		}
		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts';
		$response = $this->_callPatronUrl($user, $url);
		if ($response == false){
			//The user is not authorized to use OverDrive
			return array();
		}

		//print_r($response);
		$checkedOutTitles = array();
		if (isset($response->checkouts)){
			foreach ($response->checkouts as $curTitle){
				$bookshelfItem = array();
				//Load data from api
				$bookshelfItem['checkoutSource'] = 'OverDrive';
				$bookshelfItem['overDriveId'] = $curTitle->reserveId;
				$bookshelfItem['expiresOn'] = $curTitle->expires;
				$expirationDate = new DateTime($curTitle->expires);
				$bookshelfItem['dueDate'] = $expirationDate->getTimestamp();
				$checkOutDate = new DateTime($curTitle->checkoutDate);
				$bookshelfItem['checkoutdate'] = $checkOutDate->getTimestamp();
				$bookshelfItem['overdriveRead'] = false;
				if (isset($curTitle->isFormatLockedIn) && $curTitle->isFormatLockedIn == 1){
					$bookshelfItem['formatSelected'] = true;
				}else{
					$bookshelfItem['formatSelected'] = false;
				}
				$bookshelfItem['formats'] = array();
				if (!$forSummary){
					if (isset($curTitle->formats)){
						foreach ($curTitle->formats as $id => $format){
							if ($format->formatType == 'ebook-overdrive' || $format->formatType == 'ebook-mediado') {
								$bookshelfItem['overdriveRead'] = true;
							}else if ($format->formatType == 'audiobook-overdrive'){
									$bookshelfItem['overdriveListen'] = true;
							}else if ($format->formatType == 'video-streaming'){
								$bookshelfItem['overdriveVideo'] = true;
							}else{
								$bookshelfItem['selectedFormat'] = array(
									'name' => $this->format_map[$format->formatType],
									'format' => $format->formatType,
								);
							}
							$curFormat = array();
							$curFormat['id'] = $id;
							$curFormat['format'] = $format;
							$curFormat['name'] = $format->formatType;
							if (isset($format->links->self)){
								$curFormat['downloadUrl'] = $format->links->self->href . '/downloadlink';
							}
							if ($format->formatType != 'ebook-overdrive' && $format->formatType != 'ebook-mediado' && $format->formatType != 'audiobook-overdrive' && $format->formatType != 'video-streaming'){
								$bookshelfItem['formats'][] = $curFormat;
							}else{
								if (isset($curFormat['downloadUrl'])){
									if ($format->formatType = 'ebook-overdrive' || $format->formatType == 'ebook-mediado') {
										$bookshelfItem['overdriveReadUrl'] = $curFormat['downloadUrl'];
									}else if ($format->formatType == 'video-streaming') {
										$bookshelfItem['overdriveVideoUrl'] = $curFormat['downloadUrl'];
									}else{
										$bookshelfItem['overdriveListenUrl'] = $curFormat['downloadUrl'];
									}
								}
							}
						}
					}
					if (isset($curTitle->actions->format) && !$bookshelfItem['formatSelected']){
						//Get the options for the format which includes the valid formats
						$formatField = null;
						foreach ($curTitle->actions->format->fields as $curFieldIndex => $curField){
							if ($curField->name == 'formatType'){
								$formatField = $curField;
								break;
							}
						}
						if (isset($formatField->options)){
							foreach ($formatField->options as $index => $format){
								$curFormat = array();
								$curFormat['id'] = $format;
								$curFormat['name'] = $this->format_map[$format];
								$bookshelfItem['formats'][] = $curFormat;
							}
						}else{
							//No formats found for the title, do we need to do anything special?
						}
					}

					if (isset($curTitle->actions->earlyReturn)){
						$bookshelfItem['earlyReturn']  = true;
					}
					//Figure out which eContent record this is for.
					require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
					$overDriveRecord = new OverDriveRecordDriver($bookshelfItem['overDriveId']);
					$bookshelfItem['recordId'] = $overDriveRecord->getUniqueID();
					$groupedWorkId = $overDriveRecord->getGroupedWorkId();
					if ($groupedWorkId != null){
						$bookshelfItem['groupedWorkId'] = $overDriveRecord->getGroupedWorkId();
					}
					$formats = $overDriveRecord->getFormats();
					$bookshelfItem['format']     = reset($formats);
					$bookshelfItem['coverUrl']   = $overDriveRecord->getCoverUrl('medium');
					$bookshelfItem['recordUrl']  = $configArray['Site']['path'] . '/OverDrive/' . $overDriveRecord->getUniqueID() . '/Home';
					$bookshelfItem['title']      = $overDriveRecord->getTitle();
					$bookshelfItem['author']     = $overDriveRecord->getAuthor();
					$bookshelfItem['linkUrl']    = $overDriveRecord->getLinkUrl(false);
					$bookshelfItem['ratingData'] = $overDriveRecord->getRatingData();
				}
				$bookshelfItem['user'] = $user->getNameAndLibraryLabel();
				$bookshelfItem['userId'] = $user->id;

				$key = $bookshelfItem['checkoutSource'] . $bookshelfItem['overDriveId'];
				$checkedOutTitles[$key] = $bookshelfItem;
			}
		}
		if (!$forSummary){
			$this->checkouts[$user->id] = $checkedOutTitles;
		}
		return $checkedOutTitles;
	}

	private $holds = array();

	/**
	 * @param User $user
	 * @param null $overDriveInfo
	 * @return array
	 */
	public function getOverDriveHolds($user, $overDriveInfo = null, $forSummary = false){
		//Cache holds for the user just for this call.
		if (isset($this->holds[$user->id])){
			return $this->holds[$user->id];
		}
		global $configArray;
		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);
		if (!$this->isUserValidForOverDrive($user)){
			return $holds;
		}
		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/holds';
		$response = $this->_callPatronUrl($user, $url);
		if (isset($response->holds)){
			foreach ($response->holds as $curTitle){
				$hold = array();
				$hold['overDriveId'] = $curTitle->reserveId;
				if ($curTitle->emailAddress){
					$hold['notifyEmail'] = $curTitle->emailAddress;
				}else{
					//print_r($curTitle);
				}
				$datePlaced                = strtotime($curTitle->holdPlacedDate);
				if ($datePlaced) {
					$hold['create']            = $datePlaced;
				}
				$hold['holdQueueLength']   = $curTitle->numberOfHolds;
				$hold['holdQueuePosition'] = $curTitle->holdListPosition;
				$hold['position']          = $curTitle->holdListPosition;  // this is so that overdrive holds can be sorted by hold position with the IlS holds
				$hold['available']         = isset($curTitle->actions->checkout);
				if ($hold['available']){
					$hold['expire'] = strtotime($curTitle->holdExpires);
				}
				$hold['holdSource'] = 'OverDrive';

				//Figure out which eContent record this is for.
				require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
				if (!$forSummary){
					$overDriveRecord = new OverDriveRecordDriver($hold['overDriveId']);
					$hold['recordId'] = $overDriveRecord->getUniqueID();
					$hold['coverUrl'] = $overDriveRecord->getCoverUrl('medium');
					$hold['recordUrl'] = $configArray['Site']['path'] . '/OverDrive/' . $overDriveRecord->getUniqueID() . '/Home';
					$hold['title'] = $overDriveRecord->getTitle();
					$hold['sortTitle'] = $overDriveRecord->getTitle();
					$hold['author'] = $overDriveRecord->getAuthor();
					$hold['linkUrl'] = $overDriveRecord->getLinkUrl(false);
					$hold['format'] = $overDriveRecord->getFormats();
					$hold['ratingData'] = $overDriveRecord->getRatingData();
				}
				$hold['user'] = $user->getNameAndLibraryLabel();
				$hold['userId'] = $user->id;

				$key = $hold['holdSource'] . $hold['overDriveId'] . $hold['user'];
				if ($hold['available']){
					$holds['available'][$key] = $hold;
				}else{
					$holds['unavailable'][$key] = $hold;
				}
			}
		}
		if (!$forSummary){
			$this->holds[$user->id] = $holds;
		}
		return $holds;
	}

	/**
	 * Returns a summary of information about the user's account in OverDrive.
	 *
	 * @param User $user
	 *
	 * @return array
	 */
	public function getOverDriveSummary($user){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;

		if ($user == false){
			return array(
				'numCheckedOut' => 0,
				'numAvailableHolds' => 0,
				'numUnavailableHolds' => 0,
				'checkedOut' => array(),
				'holds' => array()
			);
		}

		$summary = $memCache->get('overdrive_summary_' . $user->id);
		if ($summary == false || isset($_REQUEST['reload'])){

			//Get account information from api

			//TODO: Optimize so we don't need to load all checkouts and holds
			$summary = array();
			$checkedOutItems = $this->getOverDriveCheckedOutItems($user, null, true);
			$summary['numCheckedOut'] = count($checkedOutItems);

			$holds = $this->getOverDriveHolds($user, null, true);
			$summary['numAvailableHolds'] = count($holds['available']);
			$summary['numUnavailableHolds'] = count($holds['unavailable']);

			$summary['checkedOut'] = $checkedOutItems;
			$summary['holds'] = $holds;

			$timer->logTime("Finished loading titles from overdrive summary");
			$memCache->set('overdrive_summary_' . $user->id, $summary, 0, $configArray['Caching']['overdrive_summary']);
		}

		return $summary;
	}

	public function getLendingPeriods($user){
		//TODO: Replace this with an API when available
		require_once ROOT_DIR . '/Drivers/OverDriveDriver2.php';
		$overDriveDriver2 = new OverDriveDriver2();
		return $overDriveDriver2->getLendingPeriods($user);
	}

	/**
	 * Places a hold on an item within OverDrive
	 *
	 * @param string $overDriveId
	 * @param int $format
	 * @param User $user
	 *
	 * @return array (result, message)
	 */
	public function placeOverDriveHold($overDriveId, $user){
		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/holds/' . $overDriveId;
		$params = array(
			'reserveId' => $overDriveId,
			'emailAddress' => trim($user->overdriveEmail)
		);
		$response = $this->_callPatronUrl($user, $url, $params);

		$holdResult = array();
		$holdResult['success'] = false;
		$holdResult['message'] = '';

		//print_r($response);
		if (isset($response->holdListPosition)){
			$holdResult['success'] = true;
			$holdResult['message'] = 'Your hold was placed successfully.  You are number ' . $response->holdListPosition . ' on the wait list.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Place Hold', 'succeeded');
		}else{
			$holdResult['message'] = 'Sorry, but we could not place a hold for you on this title.';
			if (isset($response->message)) $holdResult['message'] .= "  {$response->message}";
			if ($analytics) $analytics->addEvent('OverDrive', 'Place Hold', 'failed');
		}
		$user->clearCache();
		$memCache->delete('overdrive_summary_' . $user->id);

		return $holdResult;
	}

	/**
	 * @param string  $overDriveId
	 * @param User    $user
	 * @return array
	 */
	public function cancelOverDriveHold($overDriveId, $user){
		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/holds/' . $overDriveId;
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		$userBarcode = $user->getBarcode();
		if ($this->getRequirePin($user)){
			$userPin = ($barcodeProperty == 'cat_username') ? $user->cat_password : $user->cat_username;
			$response = $this->_callPatronDeleteUrl($user, $userBarcode, $userPin, $url);
		}else{
			$response = $this->_callPatronDeleteUrl($user, $userBarcode, null, $url);
		}


		$cancelHoldResult = array();
		$cancelHoldResult['success'] = false;
		$cancelHoldResult['message'] = '';
		if ($response === true){
			$cancelHoldResult['success'] = true;
			$cancelHoldResult['message'] = 'Your hold was cancelled successfully.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Cancel Hold', 'succeeded');
		}else{
			$cancelHoldResult['message'] = 'There was an error cancelling your hold.';
		 if (isset($response->message)) $cancelHoldResult['message'] .= "  {$response->message}";
			if ($analytics) $analytics->addEvent('OverDrive', 'Cancel Hold', 'failed');
		}
		$memCache->delete('overdrive_summary_' . $user->id);
		$user->clearCache();
		return $cancelHoldResult;
	}

	/**
	 *
	 * Add an item to the cart in overdrive and then process the cart so it is checked out.
	 *
	 * @param string $overDriveId
	 * @param User $user
	 *
	 * @return array results (result, message)
	 */
	public function checkoutOverDriveItem($overDriveId, $user){

		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts';
		$params = array(
			'reserveId' => $overDriveId,
		);
		$response = $this->_callPatronUrl($user, $url, $params);

		$result = array();
		$result['success'] = false;
		$result['message'] = '';

		//print_r($response);
		if (isset($response->expires)) {
			$result['success'] = true;
			$result['message'] = 'Your title was checked out successfully. You may now download the title from your Account.';
			if ($analytics) {
				$analytics->addEvent('OverDrive', 'Checkout Item', 'succeeded');
			}
		}else{
			$result['message'] = 'Sorry, we could not checkout this title to you.';
			if (isset($response->errorCode) && $response->errorCode == 'PatronHasExceededCheckoutLimit'){
				$result['message'] .= "\r\n\r\nYou have reached the maximum number of OverDrive titles you can checkout one time.";
			}else{
				if (isset($response->message)) $result['message'] .= "  {$response->message}";
			}

			if (isset($response->errorCode) && ($response->errorCode == 'NoCopiesAvailable' || $response->errorCode == 'PatronHasExceededCheckoutLimit')) {
				$result['noCopies'] = true;
				$result['message'] .= "\r\n\r\nWould you like to place a hold instead?";
			}else{
				//Give more information about why it might gave failed, ie expired card or too much fines
				$result['message'] = 'Sorry, we could not checkout this title to you.  Please verify that your card has not expired and that you do not have excessive fines.';
			}

			if ($analytics) $analytics->addEvent('OverDrive', 'Checkout Item', 'failed');
		}

		$memCache->delete('overdrive_summary_' . $user->id);
		$user->clearCache();
		return $result;
	}

	public function getLoanPeriodsForFormat($formatId){
		//TODO: API for this?
		if ($formatId == 35){
			return array(3, 5, 7);
		}else{
			return array(7, 14, 21);
		}
	}

	public function returnOverDriveItem($overDriveId, $transactionId, $user){
		global $configArray;
		global $analytics;
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts/' . $overDriveId;
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		$userBarcode = $user->$barcodeProperty;
		if ($this->getRequirePin($user)){
			$userPin = ($barcodeProperty == 'cat_username') ? $user->cat_password : $user->cat_username;
			$response = $this->_callPatronDeleteUrl($user, $userBarcode, $userPin, $url);
		}else{
			$response = $this->_callPatronDeleteUrl($user, $userBarcode, null, $url);
		}

		$cancelHoldResult = array();
		$cancelHoldResult['success'] = false;
		$cancelHoldResult['message'] = '';
		if ($response === true){
			$cancelHoldResult['success'] = true;
			$cancelHoldResult['message'] = 'Your item was returned successfully.';
			if ($analytics) $analytics->addEvent('OverDrive', 'Return Item', 'succeeded');
		}else{
			$cancelHoldResult['message'] = 'There was an error returning this item.';
			if (isset($response->message)) $cancelHoldResult['message'] .= "  {$response->message}";
			if ($analytics) $analytics->addEvent('OverDrive', 'Return Item', 'failed');
		}

		$memCache->delete('overdrive_summary_' . $user->id);
		$user->clearCache();
		return $cancelHoldResult;
	}

	public function selectOverDriveDownloadFormat($overDriveId, $formatId, $user){
		global $configArray;
		global $analytics;
		/** @var Memcache $memCache */
		global $memCache;

		$url = $configArray['OverDrive']['patronApiUrl'] . '/v1/patrons/me/checkouts/' . $overDriveId . '/formats';
		$params = array(
			'reserveId' => $overDriveId,
			'formatType' => $formatId
		);
		$response = $this->_callPatronUrl($user, $url, $params);
		//print_r($response);

		$result = array();
		$result['success'] = false;
		$result['message'] = '';

		if (isset($response->linkTemplates->downloadLink)){
			$result['success'] = true;
			$result['message'] = 'This format was locked in';
			if ($analytics) $analytics->addEvent('OverDrive', 'Select Download Format', 'succeeded');
			$downloadLink = $this->getDownloadLink($overDriveId, $formatId, $user);
			$result = $downloadLink;
		}else{
			$result['message'] = 'Sorry, but we could not select a format for you.';
			if (isset($response->message)) $result['message'] .= "  {$response->message}";
			if ($analytics) $analytics->addEvent('OverDrive', 'Select Download Format', 'failed');
		}
		$memCache->delete('overdrive_summary_' . $user->id);

		return $result;
	}

	/**
	 * @param $user  User
	 * @return bool
	 */
	public function isUserValidForOverDrive($user){
		global $configArray;
		global $timer;
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		$userBarcode = $user->getBarcode();
		if ($this->getRequirePin($user)){
			$userPin = ($barcodeProperty == 'cat_username') ? $user->cat_password : $user->cat_username;
			// determine which column is the pin by using the opposing field to the barcode. (between catalog password & username)
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, $userPin, false);
			// this worked for flatirons checkout.  plb 1-13-2015
		}else{
			$tokenData = $this->_connectToPatronAPI($user, $userBarcode, null, false);
		}
		$timer->logTime("Checked to see if the user $userBarcode is valid for OverDrive");
		return ($tokenData !== false) && ($tokenData !== null) && !array_key_exists('error', $tokenData);
	}

	public function updateLendingOptions(){
		//TODO: Replace this with an API when available
		require_once ROOT_DIR . '/Drivers/OverDriveDriver2.php';
		$overDriveDriver2 = new OverDriveDriver2();
		return $overDriveDriver2->updateLendingOptions();
	}

	public function getDownloadLink($overDriveId, $format, $user){
		global $configArray;
		global $analytics;

		$url = $configArray['OverDrive']['patronApiUrl'] . "/v1/patrons/me/checkouts/{$overDriveId}/formats/{$format}/downloadlink";
		$url .= '?errorpageurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		if ($format == 'ebook-overdrive' || $format == 'ebook-mediado'){
			$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		}elseif ($format == 'audiobook-overdrive'){
			$url .= '&odreadauthurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
		}elseif ($format == 'video-streaming'){
			$url .= '&errorurl=' . urlencode($configArray['Site']['url'] . '/Help/OverDriveError');
			$url .= '&streamingauthurl=' . urlencode($configArray['Site']['url'] . '/Help/streamingvideoauth');
		}

		$response = $this->_callPatronUrl($user, $url);
		//print_r($response);

		$result = array();
		$result['success'] = false;
		$result['message'] = '';

		if (isset($response->links->contentlink)){
			$result['success'] = true;
			$result['message'] = 'Created Download Link';
			$result['downloadUrl'] = $response->links->contentlink->href;
			if ($analytics) $analytics->addEvent('OverDrive', 'Get Download Link', 'succeeded');
		}else{
			$result['message'] = 'Sorry, but we could not get a download link for you.';
			if (isset($response->message)) $result['message'] .= "  {$response->message}";
			if ($analytics) $analytics->addEvent('OverDrive', 'Get Download Link', 'failed');
		}

		return $result;
	}

	/**
	 * Get Holding
	 *
	 * This is responsible for retrieving the holding information of a certain
	 * record.
	 *
	 * @param   OverDriveRecordDriver  $overDriveRecordDriver   The record id to retrieve the holdings for
	 * @return  mixed               An associative array with the following keys:
	 *                              availability (boolean), status, location,
	 *                              reserve, callnumber, dueDate, number,
	 *                              holding summary, holding notes
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	public function getHoldings($overDriveRecordDriver){
		/** @var OverDriveAPIProductFormats[] $items */
		$items = $overDriveRecordDriver->getItems();
		//Add links as needed
		$availability = $overDriveRecordDriver->getAvailability();
		$addCheckoutLink = false;
		$addPlaceHoldLink = false;
		foreach($availability as $availableFrom){
			if ($availableFrom->copiesAvailable > 0){
				$addCheckoutLink = true;
			}else{
				$addPlaceHoldLink = true;
			}
		}
		foreach ($items as $key => $item){
			$item->links = array();
			if ($addCheckoutLink){
				$checkoutLink = "return VuFind.OverDrive.checkOutOverDriveTitle('{$overDriveRecordDriver->getUniqueID()}');";
				$item->links[] = array(
					'onclick' => $checkoutLink,
					'text' => 'Check Out',
					'overDriveId' => $overDriveRecordDriver->getUniqueID(),
					'formatId' => $item->numericId,
					'action' => 'CheckOut'
				);
			}else if ($addPlaceHoldLink){
				$item->links[] = array(
					'onclick' => "return VuFind.OverDrive.placeOverDriveHold('{$overDriveRecordDriver->getUniqueID()}', '{$item->numericId}');",
					'text' => 'Place Hold',
					'overDriveId' => $overDriveRecordDriver->getUniqueID(),
					'formatId' => $item->numericId,
					'action' => 'Hold'
				);
			}
			$items[$key] = $item;
		}

		return $items;
	}

	public function getLibraryScopingId(){
		//For econtent, we need to be more specific when restricting copies
		//since patrons can't use copies that are only available to other libraries.
		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$activeLibrary = Library::getActiveLibrary();
		$activeLocation = Location::getActiveLocation();
		$homeLibrary = Library::getPatronHomeLibrary();

		//Load the holding label for the branch where the user is physically.
		if (!is_null($homeLibrary)){
			return $homeLibrary->includeOutOfSystemExternalLinks ? -1 : $homeLibrary->libraryId;
		}else if (!is_null($activeLocation)){
			$activeLibrary = Library::getLibraryForLocation($activeLocation->locationId);
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (isset($activeLibrary)) {
			return $activeLibrary->includeOutOfSystemExternalLinks ? -1 : $activeLibrary->libraryId;
		}else if (!is_null($searchLocation)){
			$searchLibrary = Library::getLibraryForLocation($searchLibrary->locationId);
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLocation->libraryId;
		}else if (isset($searchLibrary)) {
			return $searchLibrary->includeOutOfSystemExternalLinks ? -1 : $searchLibrary->libraryId;
		}else{
			return -1;
		}
	}

	/**
	 * @param OverDriveRecordDriver $overDriveRecordDriver
	 * @return array
	 */
	public function getScopedAvailability($overDriveRecordDriver){
		$availability = array();
		$availability['mine'] = $overDriveRecordDriver->getAvailability();
		$availability['other'] = array();
		$scopingId = $this->getLibraryScopingId();
		if ($scopingId != -1){
			foreach ($availability['mine'] as $key => $availabilityItem){
				if ($availabilityItem->libraryId != -1 && $availabilityItem->libraryId != $scopingId){
					$availability['other'][$key] = $availability['mine'][$key];
					unset($availability['mine'][$key]);
				}
			}
		}
		return $availability;
	}

	public function getStatusSummary($id, $scopedAvailability, $holdings){
		$holdPosition = 0;

		$availableCopies = 0;
		$totalCopies = 0;
		$onOrderCopies = 0;
		$checkedOut = 0;
		$onHold = 0;
		$wishListSize = 0;
		$numHolds = 0;
		if (count($scopedAvailability['mine']) > 0){
			foreach ($scopedAvailability['mine'] as $curAvailability){
				$availableCopies += $curAvailability->copiesAvailable;
				$totalCopies += $curAvailability->copiesOwned;
				if ($curAvailability->numberOfHolds > $numHolds){
					$numHolds = $curAvailability->numberOfHolds;
				}
			}
		}

		//Load status summary
		$statusSummary = array();
		$statusSummary['recordId'] = $id;
		$statusSummary['totalCopies'] = $totalCopies;
		$statusSummary['onOrderCopies'] = $onOrderCopies;
		$statusSummary['accessType'] = 'overdrive';
		$statusSummary['isOverDrive'] = false;
		$statusSummary['alwaysAvailable'] = false;
		$statusSummary['class'] = 'checkedOut';
		$statusSummary['available'] = false;
		$statusSummary['status'] = 'Not Available';

		$statusSummary['availableCopies'] = $availableCopies;
		$statusSummary['isOverDrive'] = true;
		if ($totalCopies >= 999999){
			$statusSummary['alwaysAvailable'] = true;
		}
		if ($availableCopies > 0){
			$statusSummary['status'] = "Available from OverDrive";
			$statusSummary['available'] = true;
			$statusSummary['class'] = 'available';
		}else{
			$statusSummary['status'] = 'Checked Out';
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'checkedOut';
			$statusSummary['isOverDrive'] = true;
		}


		//Determine which buttons to show
		$statusSummary['holdQueueLength'] = $numHolds;
		$statusSummary['showPlaceHold'] = $availableCopies == 0 && count($scopedAvailability['mine']) > 0;
		$statusSummary['showCheckout'] = $availableCopies > 0 && count($scopedAvailability['mine']) > 0;
		$statusSummary['showAddToWishlist'] = false;
		$statusSummary['showAccessOnline'] = false;

		$statusSummary['onHold'] = $onHold;
		$statusSummary['checkedOut'] = $checkedOut;
		$statusSummary['holdPosition'] = $holdPosition;
		$statusSummary['numHoldings'] = count($holdings);
		$statusSummary['wishListSize'] = $wishListSize;

		return $statusSummary;
	}
}
