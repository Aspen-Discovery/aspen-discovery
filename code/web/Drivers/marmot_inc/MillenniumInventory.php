<?php
/**
 * Functionality related to doing Inventory within Millennium
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/20/13
 * Time: 2:41 PM
 */

class MillenniumInventory {
	/** @var  Millennium $driver */
	private $driver;
	/** @var  Solr $db */
	private $db;
	private $curl_connection;

	public function __construct($driver){
		$this->driver = $driver;
	}

	/**
	 * Process inventory for a particular item in the catalog
	 *
	 * @param string $login     Login for the user doing the inventory
	 * @param string $password1 Password for the user doing the inventory
	 * @param string $initials
	 * @param string $password2
	 * @param string[] $barcodes
	 * @param boolean $updateIncorrectStatuses
	 *
	 * @return array
	 */
	function doInventory($login, $password1, $initials, $password2, $barcodes, $updateIncorrectStatuses){
		global $configArray;
		global $logger;
		$results = array();
		if ($this->driver->getVendorOpacUrl()){
			return array(
				'success' => false,
				'message' => 'There is not a url to millennium set in the config.ini file.  Please update the configuration',
			);
		}

		$ils = $configArray['Catalog']['ils'];
		if ($login == '' || $password1 == ''){
			return array(
					'success' => false,
					'message' => 'Login information not provided correctly.  Please fill out all login fields',
			);
		}
		if ($ils != 'Sierra'){
			if ($initials == '' || $password2 == ''){
				return array(
						'success' => false,
						'message' => 'Login information not provided correctly.  Please fill out all login fields',
				);
			}
		}

		if (is_string($barcodes) && strlen($barcodes) == 0){
			return array(
				'success' => false,
				'message' => 'Please enter at least one barcode to inventory.',
			);
		}else{
			if (!is_array($barcodes)){
				$barcodes = preg_split('/[\\s\\r\\n]+/', $barcodes);
			}
			if (count($barcodes) == 0){
				return array(
					'success' => false,
					'message' => 'Please enter at least one barcode to inventory.',
				);
			}
		}

		//Setup Solr to be able to get additional information about the title
		global $configArray;
		$url = $configArray['Index']['url'];
		$this->db = new GroupedWorksSolrConnector($url);

		$baseUrl = $this->driver->getVendorOpacUrl();
		$circaUrl = $this->driver->getVendorOpacUrl() . '/iii/airwkst/airwkstcore';
		//Setup curl
		$curl_url = $circaUrl;
		$this->curl_connection = curl_init($curl_url);

		$cookieJar = tempnam ("/tmp", "CURLCOOKIE");

		curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($this->curl_connection, CURLOPT_COOKIEJAR, $cookieJar );
		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl_connection, CURLOPT_COOKIESESSION, is_null($cookieJar) ? true : false);

		//First get the login page
		curl_exec($this->curl_connection);
		sleep(1);

		//Login to circa
		$post_data = array(
			'action' => 'ValidateAirWkstUserAction',
			'login' => $login,
			'loginpassword' => $password1,
			'nextaction' => 'null',
			'purpose' => 'null',
			'submit.x' => 41,
			'submit.y' => 10,
			'subpurpose' => 'null',
			'validationstatus' => 'needlogin',
		);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($this->curl_connection, CURLOPT_POST, true);
		curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($this->curl_connection);
		$logger->log("Calling {$curl_url}?{$post_string}", PEAR_LOG_DEBUG);
		//$logger->log("result of circa login $sresult", PEAR_LOG_DEBUG);
		sleep(1);

		//Check that we logged in successfully
		$loginSuccess = false;
		if (!preg_match('/Invalid login\/password/i', $sresult)){
			if ($ils == 'Sierra'){
				$loginSuccess = true;
			}else{
				$loginSuccess = preg_match('/initials/i', $sresult);
			}
		}
		if ($loginSuccess){
			if ($ils != 'Sierra'){
				//we logged in successfully.
				//enter initials
				$post_data = array(
					'action' => 'ValidateAirWkstUserAction',
					'initials' => $initials,
					'initialspassword' => $password2,
					'nextaction' => 'null',
					'purpose' => 'null',
					'submit.x' => 30,
					'submit.y' => 10,
					'subpurpose' => 'null',
					'validationstatus' => 'needinitials',
				);
				$post_items = array();
				foreach ($post_data as $key => $value) {
					$post_items[] = $key . '=' . urlencode($value);
				}
				$post_string = implode ('&', $post_items);
				curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);
				$sresult = curl_exec($this->curl_connection);
				sleep(1);
				$logger->log("Calling {$curl_url}?{$post_string}", PEAR_LOG_DEBUG);
				//$logger->log("result of circa initials $sresult", PEAR_LOG_DEBUG);
			}

			if (!preg_match('/You are not authorized to use Circa/i', $sresult) && preg_match('/inventory control/i', $sresult)){
				//Logged in and authorized, check in each barcode
				//Go to the Inventory page
				curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
				curl_setopt($this->curl_connection, CURLOPT_URL, $baseUrl . '/iii/airwkst/?action=GetAirWkstUserInfoAction&purpose=updinvdt');
				curl_exec($this->curl_connection);

				curl_setopt($this->curl_connection, CURLOPT_POST, true);
				curl_setopt($this->curl_connection, CURLOPT_URL, $curl_url);
				$results['barcodes']= array();
				foreach ($barcodes as $barcode){
					set_time_limit(60);
					$post_data = array(
						'action' => 'GetAirWkstItemOneAction',
						'prevscreen' => 'AirWkstItemRequestPage',
						'purpose' => 'updinvdt',
						'searchstring' => $barcode,
						'searchtype' => 'b',
						'sourcebrowse' => 'airwkstpage',
					);
					$post_items = array();
					foreach ($post_data as $key => $value) {
						$post_items[] = $key . '=' . urlencode($value);
					}
					$post_string = implode ('&', $post_items);
					curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);

					$sresult = curl_exec($this->curl_connection);
					$titleInfo = $this->db->getRecordByBarcode($barcode);

					$itemInfo = null;
					if ($titleInfo != null){
						$marcInfo = MarcLoader::loadMarcRecordFromRecord($titleInfo);
						//Get the matching item from the item records
						$marcItemField = isset($configArray['Reindex']['itemTag']) ? $configArray['Reindex']['itemTag'] : '989';
						$itemFields = $marcInfo->getFields($marcItemField);
						$itemInfo = null;
						if ($itemFields){
							/** @var File_MARC_Data_Field $fieldInfo */
							foreach ($itemFields as $fieldInfo){
								if ($fieldInfo->getSubfield('b')->getData() == $barcode){
									$itemInfo = $fieldInfo;
								}
							}
						}else{
							$logger->log("Did not find item records $barcode", PEAR_LOG_ERR);
						}
					}
					if ($itemInfo == null){
						$logger->log("Did not find an item for barcode $barcode", PEAR_LOG_ERR);
					}


					if (preg_match("/$barcode updated successfully/i", $sresult)){
						$results['barcodes'][$barcode] = array(
							'inventoryResult' => 'Updated successfully.',
							'title' => $titleInfo['title'],
							'callNumber' => is_null($itemInfo) ? '' : $itemInfo->getSubfield('a')->getData(),
						);
						if (preg_match('/Unexpected status; adjust below if appropriate/i', $sresult)){
							if ($updateIncorrectStatuses){
								//Automatically update the item to checked in
								//extract the current status and item record
								preg_match('/<input type="hidden" name="olditemstatuscode" value="(.*?)">/', $sresult, $matches);
								$lastStatus = $matches[1];
								preg_match('/<input type="hidden" name="itemrecordkey" value="(.*?)">/', $sresult, $matches);
								$itemrecordkey = $matches[1];
								$post_data = array(
									'action' => 'UpdateAirWkstItemOneAction',
									'itemrecordkey' => $itemrecordkey,
									'lastaction' => 'updstatus',
									'lastactionstatus' => 'good',
									'lastitembarcode' => $barcode,
									'newitemstatuscode' => '-',
									'olditemstatuscode' => $lastStatus,
									'purpose' => 'updstatus',
									'submit.x' => 40,
									'submit.y' => 10,
									'ulang' => 'eng',
								);
								$post_items = array();
								foreach ($post_data as $key => $value) {
									$post_items[] = $key . '=' . urlencode($value);
								}
								$post_string = implode ('&', $post_items);
								curl_setopt($this->curl_connection, CURLOPT_POSTFIELDS, $post_string);

								$sresult = curl_exec($this->curl_connection);

								if (preg_match("/$barcode updated successfully/i", $sresult)){
									$results['barcodes'][$barcode]['inventoryResult'] = "Automatically changed status from $lastStatus to on shelf";
									$results['barcodes'][$barcode]['needsAdditionalProcessing'] = false;
								}else{
									$logger->log("Could not update status for barcode $barcode/r/n$sresult", PEAR_LOG_ERR);
									$results['barcodes'][$barcode]['inventoryResult'] = "Could not automatically fix status, old status is $lastStatus";
									$results['barcodes'][$barcode]['needsAdditionalProcessing'] = true;
								}

							}else{
								$results['barcodes'][$barcode]['inventoryResult'] = "Unexpected Status, Needs Update";
								$results['barcodes'][$barcode]['needsAdditionalProcessing'] = true;
							}
						}elseif (preg_match('/Unexpected status; pull this item for correction/i', $sresult)){
							$results['barcodes'][$barcode]['inventoryResult'] .= " Pull this item for status correction";
							$results['barcodes'][$barcode]['needsAdditionalProcessing'] = true;
						}else{
							$results['barcodes'][$barcode]['needsAdditionalProcessing'] = false;
						}
						sleep(1);
					}else{
						$results['barcodes'][$barcode] = array(
							'inventoryResult' => 'Not updated',
							'title' => $titleInfo['title'],
							'callNumber' => is_null($itemInfo) ? '' : $itemInfo->getSubfield('a')->getData(),
						);
					}
				}

				$results['success'] = true;
			}else{
				//Did not log in correctly.
				$results['success'] = false;
				$results['message'] = "The initials or password were incorrect or you are not authorized to use circa.";
				if (preg_match('/class="error">(.*?)<\/h2>/i', $sresult, $matches)){
					$results['message'] = $matches[1];
				}
			}

		}else{
			//Did not log in correctly.
			$results['success'] = false;
			$results['message'] = "The login or password were incorrect.  Please reenter.";
		}

		//Logout of the system
		curl_setopt($this->curl_connection, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl_connection, CURLOPT_URL, $circaUrl . '/iii/airwkst/airwkstcore?action=AirWkstReturnToWelcomeAction');
		$sresult = curl_exec($this->curl_connection);

		//Cleanup
		curl_close($this->curl_connection);
		unlink($cookieJar);

		return $results;
	}
}