<?php

/**
 * Loads information from OverDrive Next Gen interface (version 2) and provides updates to OverDrive by screen scraping
 * Will be updated to use APIs when APIs become available.
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
class OverDriveDriver2 {
	public $version = 2;

	private function _connectToAPI($forceNewConnection = false){
		/** @var Memcache $memCache */
		global $memCache;
		$tokenData = $memCache->get('overdrive_token');
		if ($forceNewConnection || $tokenData == false){
			global $configArray;
			$ch = curl_init("https://oauth.overdrive.com/token");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));
			curl_setopt($ch, CURLOPT_USERPWD, $configArray['OverDrive']['clientKey'] . ":" . $configArray['OverDrive']['clientSecret']);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			curl_close($ch);
			$tokenData = json_decode($return);
			if ($tokenData){
				$memCache->set('overdrive_token', $tokenData, 0, $tokenData->expires_in - 10);
			}
		}
		return $tokenData;
	}

	public function _callUrl($url){
		for ($i = 1; $i < 5; $i++){
			$tokenData = $this->_connectToAPI($i != 1);
			if ($tokenData){
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$tokenData->token_type} {$tokenData->access_token}"));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
			usleep(500);
		}
		return null;
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



	public function getLendingPeriods($user){
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $timer;
		global $logger;

		$lendingOptions = $memCache->get('overdrive_lending_periods_' . $user->id);
		if ($lendingOptions == false || isset($_REQUEST['reload'])){
			$ch = curl_init();

			$overDriveInfo = $this->_loginToOverDrive($ch, $user);
			//Navigate to the account page
			//Load the My Holds page
			//print_r("Account url: " . $overDriveInfo['accountUrl']);
			curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $overDriveInfo['accountUrl']);
			$accountPage = curl_exec($overDriveInfo['ch']);

			//Get lending options
			if (preg_match('/<li id="myAccount4Tab">(.*?)<!-- myAccountContent -->/s', $accountPage, $matches)) {
				$lendingOptionsSection = $matches[1];
				$lendingOptions = $this->_parseLendingOptions($lendingOptionsSection);
			}else{
				$start = strpos($accountPage, '<li id="myAccount4Tab">') + strlen('<li id="myAccount4Tab">');
				$end = strpos($accountPage, '<!-- myAccountContent -->');
				$logger->log("Lending options from $start to $end", PEAR_LOG_DEBUG);

				$lendingOptionsSection = substr($accountPage, $start, $end);
				$lendingOptions = $this->_parseLendingOptions($lendingOptionsSection);
			}

			curl_close($ch);

			$timer->logTime("Finished loading titles from overdrive summary");
			$memCache->set('overdrive_lending_periods_' . $user->id, $lendingOptions, 0, $configArray['Caching']['overdrive_summary']);
		}

		return $lendingOptions;
	}



	/**
	 * Logs the user in to OverDrive and returns urls for the pages that can be accessed from the account as wel
	 * as the curl handle to use when accessing the
	 *
	 * @param mixed $ch An open curl connection to use when talking to OverDrive.  Will not be closed by this method.
	 * @param User $user The user to login.
	 *
	 * @return array
	 */
	private function _loginToOverDrive($ch, $user){
		global $configArray;
		global $analytics;
		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");
		$overdriveUrl = $configArray['OverDrive']['url'];
		curl_setopt_array($ch, array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPGET => true,
			CURLOPT_URL => $overdriveUrl . '/10/50/en/Default.htm',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0.1) Gecko/20100101 Firefox/8.0.1",
			CURLOPT_AUTOREFERER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_COOKIEJAR => $cookieJar ,
			CURLOPT_COOKIESESSION => (is_null($cookieJar) ? true : false)
		));
		curl_exec($ch);
		$pageInfo = curl_getinfo($ch);

		$urlWithSession = $pageInfo['url'];
		//print_r($pageInfo);


		//Go to the login form
		$loginUrl = str_replace('Default.htm', 'BANGAuthenticate.dll?Action=AuthCheck&URL=MyAccount.htm&ForceLoginFlag=0',  $urlWithSession);
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		curl_exec($ch);

		//Post to the login form
		curl_setopt($ch, CURLOPT_POST, true);
		$barcodeProperty = isset($configArray['Catalog']['barcodeProperty']) ? $configArray['Catalog']['barcodeProperty'] : 'cat_username';
		$barcode = $user->$barcodeProperty;
		if (strlen($barcode) == 5){
			$user->cat_password = '41000000' . $barcode;
		}else if (strlen($barcode) == 6){
			$user->cat_password = '4100000' . $barcode;
		}
		if (isset($configArray['OverDrive']['maxCardLength'])){
			$barcode = substr($barcode, -$configArray['OverDrive']['maxCardLength']);
		}
		$postParams = array(
			'LibraryCardNumber' => $barcode,
			'URL' => 'Default.htm',
			'RememberMe' => 'on'
		);
		if ($configArray['OverDrive']['requirePin']){
			$postParams['LibraryCardPin'] = $user->cat_password;
		}
		if (isset($configArray['OverDrive']['LibraryCardILS']) && strlen($configArray['OverDrive']['LibraryCardILS']) > 0){
			$postParams['LibraryCardILS'] = $configArray['OverDrive']['LibraryCardILS'];
		}
		$post_items = array();
		foreach ($postParams as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		//$loginUrl = str_replace('SignIn.htm?URL=MyAccount%2ehtm', 'BANGAuthenticate.dll',  $loginFormUrl);
		$loginUrl = str_replace('lib.overdrive.com', 'libraryreserve.com', $overdriveUrl . '/10/50/en/BANGAuthenticate.dll');
		$loginUrl = str_replace('http://', 'https://', $loginUrl);
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		$myAccountMenuContent = curl_exec($ch);

		$matchAccount = preg_match('/sign-out-link-top/is', $myAccountMenuContent);
		if (($matchAccount > 0)){
			$overDriveInfo = array(
				'baseLoginUrl' => str_replace('BANGAuthenticate.dll', '', $loginUrl),
				'baseUrlWithSession' => str_replace('Default.htm', '',  $urlWithSession),
				'contentInfoPage' => str_replace('Default.htm', 'ContentDetails.htm',  $urlWithSession),
				'accountUrl' => str_replace('BANGAuthenticate.dll', 'MyAccount.htm?PerPage=80', $loginUrl),
				'waitingListUrl' => str_replace('Default.htm', 'BANGAuthenticate.dll?Action=AuthCheck&ForceLoginFlag=0&URL=WaitingListForm.htm',  $urlWithSession),
				'placeHoldUrl' => str_replace('Default.htm', 'BANGAuthenticate.dll?Action=LibraryWatingList',  $urlWithSession),
				'checkoutUrl' => str_replace('Default.htm', 'BANGPurchase.dll?Action=OneClickCheckout&ForceLoginFlag=0&URL=MyAccount.htm%3FPerPage=80',  $urlWithSession),
				'returnUrl' => str_replace('Default.htm', 'BANGPurchase.dll?Action=EarlyReturn&URL=MyAccount.htm%3FPerPage=80',  $urlWithSession),
				'success' => true,
				'ch' => $ch,
			);
			$analytics->addEvent('OverDrive', 'Login', 'success');
		}else if (preg_match('/You are barred from borrowing/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['success'] = false;
			$overDriveInfo['message'] = "We're sorry, your account is currently barred from borrowing OverDrive titles. Please see the circulation desk.";
			$analytics->addEvent('OverDrive', 'Login', 'barred');
		}else if (preg_match('/Library card has expired/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['success'] = false;
			$overDriveInfo['message'] = "We're sorry, your library card has expired. Please contact your library to renew.";
			$analytics->addEvent('OverDrive', 'Login', 'expired');
		}else if (preg_match('/more than (.*?) in library fines are outstanding/si', $myAccountMenuContent)){
			$overDriveInfo = array();
			$overDriveInfo['success'] = false;
			$overDriveInfo['message'] = "We're sorry, your account cannot borrow from OverDrive because you have unpaid fines.";
			$analytics->addEvent('OverDrive', 'Login', 'over fine limit');
		}else{
			global $logger;
			$logger->log("Could not login to OverDrive ($matchAccount), page results: \r\n" . $myAccountMenuContent, PEAR_LOG_INFO);
			$overDriveInfo = null;
			$overDriveInfo = array();
			$overDriveInfo['success'] = false;
			$overDriveInfo['message'] = "Unknown error logging in to OverDrive.";
			$analytics->addEvent('OverDrive', 'Login', 'unknown error');
		}
		//global $logger;
		//$logger->log(print_r($overDriveInfo, true) , PEAR_LOG_INFO);
		return $overDriveInfo;
	}

	public function getLoanPeriodsForFormat($formatId){
		if ($formatId == 35){
			return array(3, 5, 7);
		}else{
			return array(7, 14, 21);
		}
	}

	public function updateLendingOptions(){
		/** @var Memcache $memCache */
		global $memCache;
		$user = UserAccount::getLoggedInUser();
		global $logger;
		global $analytics;
		$ch = curl_init();
		$overDriveInfo = $this->_loginToOverDrive($ch, $user);

		$updateSettingsUrl = $overDriveInfo['baseLoginUrl']  . 'BANGAuthenticate.dll?Action=EditUserLendingPeriodsFormatClass';
		$postParams = array(
			'URL' => 'MyAccount.htm?PerPage=80#myAccount4',
		);

		//Load settings
		foreach ($_REQUEST as $key => $value){
			if (preg_match('/class_\d+_preflendingperiod/i', $key)){
				$postParams[$key] = $value;
			}
		}

		$post_items = array();
		foreach ($postParams as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($overDriveInfo['ch'], CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($overDriveInfo['ch'], CURLOPT_URL, $updateSettingsUrl);

		$logger->log("Updating user lending options $updateSettingsUrl $post_string", PEAR_LOG_DEBUG);
		curl_exec($overDriveInfo['ch']);
		//$logger->log($lendingOptionsPage, PEAR_LOG_DEBUG);

		$memCache->delete('overdrive_summary_' . $user->id);
		$memCache->delete('overdrive_lending_periods_' . $user->id);

		$analytics->addEvent('OverDrive', 'Update Lending Periods');
		return true;
	}
}
