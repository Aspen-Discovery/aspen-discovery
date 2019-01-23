<?php
/**
 * Integration with Sierra.  Mostly inherits from Millennium since the systems are similar
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/28/13
 * Time: 9:58 AM
 */
require_once ROOT_DIR . '/Drivers/Millennium.php';
class Sierra extends Millennium{
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
			$apiVersion = $configArray['Catalog']['api_version'];
			$ch = curl_init($this->getVendorOpacUrl() . "/iii/sierra-api/v{$apiVersion}/token/");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$authInfo = base64_encode($configArray['Catalog']['clientKey'] . ":" . $configArray['Catalog']['clientSecret']);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
					'Authorization: Basic ' . $authInfo,
			));
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($ch);
			$curlInfo = curl_getinfo($ch);
			curl_close($ch);
			$tokenData = json_decode($return);
			if ($tokenData){
				$memCache->set('sierra_token', $tokenData, 0, $tokenData->expires_in - 10);
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
					"User-Agent: VuFind-Plus",
					"X-Forwarded-For: " . Location::getActiveIp(),
					"Host: " . $_SERVER['SERVER_NAME'],
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
		return null;
	}

}