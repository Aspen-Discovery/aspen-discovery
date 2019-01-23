<?php
/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 1/8/2018
 *
 */


class HooplaDriver
{
	const memCacheKey = 'hoopla_api_access_token';
//	public $hooplaAPIBaseURL = 'hoopla-api-dev.hoopladigital.com';
	public $hooplaAPIBaseURL = 'hoopla-api-dev.hoopladigital.com';
	private $accessToken;
	private $hooplaEnabled = false;



	public function __construct()
	{
		global $configArray;
		if (!empty($configArray['Hoopla']['HooplaAPIUser']) && !empty($configArray['Hoopla']['HooplaAPIpassword'])) {
			$this->hooplaEnabled = true;
			if (!empty($configArray['Hoopla']['APIBaseURL'])) {
				$this->hooplaAPIBaseURL = $configArray['Hoopla']['APIBaseURL'];
				$this->getAccessToken();
			}
		}
	}

	/**
	 * Clean an assumed Hoopla RecordID to Hoopla ID number
	 * @param $hooplaRecordId
	 * @return string
	 */
	public static function recordIDtoHooplaID($hooplaRecordId)
	{
		if (strpos($hooplaRecordId, ':') !== false) {
			list(,$hooplaRecordId) = explode(':', $hooplaRecordId, 2);
		}
		return preg_replace('/^MWT/', '', $hooplaRecordId);
	}


	// Originally copied from SirsiDynixROA Driver
	// $customRequest is for curl, can be 'PUT', 'DELETE', 'POST'
	private function getAPIResponse($url, $params = null, $customRequest = null, $additionalHeaders = null)
	{
		global $logger;
		$logger->log('Hoopla API URL :' .$url, PEAR_LOG_INFO);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$headers  = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->accessToken,
			'Originating-App-Id: Pika',
		);
		if (!empty($additionalHeaders) && is_array($additionalHeaders)) {
			$headers = array_merge($headers, $additionalHeaders);
		}
		if (empty($customRequest)) {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ($customRequest == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		}
		else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			// For local debugging only
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		}
		if ($params !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		$json = curl_exec($ch);
//		// For debugging only
//		if (stripos($instanceName, 'localhost') !== false) {
//		$err  = curl_getinfo($ch);
//		$headerRequest = curl_getinfo($ch, CURLINFO_HEADER_OUT);
//		}
		if (!$json && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 401) {
			$logger->log('401 Response in getAPIResponse. Attempting to renew access token', PEAR_LOG_WARNING);
			$this->renewAccessToken();
			return false;
		}

		$logger->log("Hoopla API response\r\n$json", PEAR_LOG_DEBUG);
		curl_close($ch);

		if ($json !== false && $json !== 'false') {
			return json_decode($json);
		} else {
			$logger->log('Curl problem in getAPIResponse', PEAR_LOG_WARNING);
			return false;
		}
	}

	/**
	 * Simplified CURL call for returning a title. Sucess is determined by recieving a http status code of 204
	 * @param $url
	 * @return bool
	 */
	private function getAPIResponseReturnHooplaTitle($url)
	{
		$ch = curl_init();
		$headers  = array(
			'Authorization: Bearer ' . $this->accessToken,
			'Originating-App-Id: Pika',
		);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		global $instanceName;
		if (stripos($instanceName, 'localhost') !== false) {
			// For local debugging only
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//		// For debugging only
//		if (stripos($instanceName, 'localhost') !== false) {
//		$err  = curl_getinfo($ch);
//		$headerRequest = curl_getinfo($ch, CURLINFO_HEADER_OUT);
//		}
		curl_close($ch);
		return $http_code == 204;
	}


	private static $hooplaLibraryIdsForUser;

	/**
	 * @param $user User
	 */
	public function getHooplaLibraryID($user) {
		if ($this->hooplaEnabled) {
			if (isset(self::$hooplaLibraryIdsForUser[$user->id])) {
				return self::$hooplaLibraryIdsForUser[$user->id]['libraryId'];
			} else {
				$library                                               = $user->getHomeLibrary();
				$hooplaID                                              = $library->hooplaLibraryID;
				self::$hooplaLibraryIdsForUser[$user->id]['libraryId'] = $hooplaID;
				return $hooplaID;
			}
		}
		return false;
	}

	/**
	 * @param $user User
	 */
	private function getHooplaBasePatronURL($user) {
		$url = null;
		if ($this->hooplaEnabled) {
			$hooplaLibraryID = $this->getHooplaLibraryID($user);
			$barcode         = $user->getBarcode();
			if (!empty($hooplaLibraryID) && !empty($barcode)) {
				$url = $this->hooplaAPIBaseURL . '/api/v1/libraries/' . $hooplaLibraryID . '/patrons/' . $barcode;
			}
		}
		return $url;
		}

	private $hooplaPatronStatuses = array();
	/**
	 * @param $user User
	 */
	public function getHooplaPatronStatus($user) {
		if ($this->hooplaEnabled) {
			if (isset($this->hooplaPatronStatuses[$user->id])) {
				return $this->hooplaPatronStatuses[$user->id];
			} else {
				$getPatronStatusURL = $this->getHooplaBasePatronURL($user);
				if (!empty($getPatronStatusURL)) {
					$getPatronStatusURL         .= '/status';
					$hooplaPatronStatusResponse = $this->getAPIResponse($getPatronStatusURL);
					if (!empty($hooplaPatronStatusResponse) && !isset($hooplaPatronStatusResponse->message)) {
						$this->hooplaPatronStatuses[$user->id] = $hooplaPatronStatusResponse;
						return $hooplaPatronStatusResponse;
					} else {
						global $logger;
						$hooplaErrorMessage = empty($hooplaPatronStatusResponse->message) ? '' : ' Hoopla Message :' . $hooplaPatronStatusResponse->message;
						$logger->log('Error retrieving patron status from Hoopla. User ID : ' . $user->id . $hooplaErrorMessage, PEAR_LOG_INFO);
						$this->hooplaPatronStatuses[$user->id] = false; // Don't do status call again for this user
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param $user User
	 */
	public function getHooplaCheckedOutItems($user)
	{
		$checkedOutItems = array();
		if ($this->hooplaEnabled) {
			$hooplaCheckedOutTitlesURL = $this->getHooplaBasePatronURL($user);
			if (!empty($hooplaCheckedOutTitlesURL)) {
				$hooplaCheckedOutTitlesURL  .= '/checkouts/current';
				$checkOutsResponse = $this->getAPIResponse($hooplaCheckedOutTitlesURL);
				if (is_array($checkOutsResponse)) {
					if (count($checkOutsResponse)) { // Only get Patron status if there are checkouts
						$hooplaPatronStatus = $this->getHooplaPatronStatus($user);
					}
					foreach ($checkOutsResponse as $checkOut) {
						$hooplaRecordID  = 'MWT' . $checkOut->contentId;
						$simpleSortTitle = preg_replace('/^The\s|^A\s/i', '', $checkOut->title); // remove beginning The or A

						$currentTitle = array(
							'checkoutSource' => 'Hoopla',
							'user'           => $user->getNameAndLibraryLabel(),
							'userId'         => $user->id,
							'hooplaId'       => $checkOut->contentId,
							'title'          => $checkOut->title,
							'title_sort'     => empty($simpleSortTitle) ? $checkOut->title : $simpleSortTitle,
							'author'         => isset($checkOut->author) ? $checkOut->author : null,
							'format'         => $checkOut->kind,
							'checkoutdate'   => $checkOut->borrowed,
							'dueDate'        => $checkOut->due,
							'hooplaUrl'      => $checkOut->url
						);

						if (isset($hooplaPatronStatus->borrowsRemaining)) {
							$currentTitle['borrowsRemaining'] = $hooplaPatronStatus->borrowsRemaining;
						}

						require_once ROOT_DIR . '/RecordDrivers/HooplaDriver.php';
						$hooplaRecordDriver = new HooplaRecordDriver($hooplaRecordID);
						if ($hooplaRecordDriver->isValid()) {
							// Get Record For other details
							$currentTitle['coverUrl']      = $hooplaRecordDriver->getBookcoverUrl('medium');
							$currentTitle['linkUrl']       = $hooplaRecordDriver->getLinkUrl();
							$currentTitle['groupedWorkId'] = $hooplaRecordDriver->getGroupedWorkId();
							$currentTitle['ratingData']    = $hooplaRecordDriver->getRatingData();
							$currentTitle['title_sort']    = $hooplaRecordDriver->getSortableTitle();
							$currentTitle['author']        = $hooplaRecordDriver->getPrimaryAuthor();
							$currentTitle['format']        = implode(', ', $hooplaRecordDriver->getFormat());
						}
						$key = $currentTitle['checkoutSource'] . $currentTitle['hooplaId']; // This matches the key naming scheme in the Overdrive Driver
						$checkedOutItems[$key] = $currentTitle;
					}
				} else {
					global $logger;
					$logger->log('Error retrieving checkouts from Hoopla.', PEAR_LOG_ERR);
				}
			}
		}
		return $checkedOutItems;
	}

	/**
	 * @return string
	 */
	private function getAccessToken()
	{
		if (empty($this->accessToken)) {
			/** @var Memcache $memCache */
			global $memCache;
			$accessToken = $memCache->get(self::memCacheKey);
			if (empty($accessToken)) {
				$this->renewAccessToken();
			} else {
				$this->accessToken = $accessToken;
			}

		}
		return $this->accessToken;
	}

	private function renewAccessToken (){
		global $configArray;
		if (!empty($configArray['Hoopla']['HooplaAPIUser']) && !empty($configArray['Hoopla']['HooplaAPIpassword'])) {
			$url = 'https://' . str_replace(array('http://', 'https://'),'', $this->hooplaAPIBaseURL) . '/v2/token';
			// Ensure https is used

			$username = $configArray['Hoopla']['HooplaAPIUser'];
			$password = $configArray['Hoopla']['HooplaAPIpassword'];

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, array());

			global $instanceName;
			if (stripos($instanceName, 'localhost') !== false) {
				// For local debugging only
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			}
			$response = curl_exec($curl);
//			// Use for debugging
//			if (stripos($instanceName, 'localhost') !== false) {
//				$err  = curl_getinfo($curl);
//				$headerRequest = curl_getinfo($curl, CURLINFO_HEADER_OUT);
//			}
			curl_close($curl);

			if ($response) {
				$json = json_decode($response);
				if (!empty($json->access_token)) {
					$this->accessToken = $json->access_token;

					/** @var Memcache $memCache */
					global $memCache;
					$memCache->set(self::memCacheKey, $this->accessToken, null, $configArray['Caching']['hoopla_api_access_token']);
					return true;

				} else {
					global $logger;
					$logger->log('Hoopla API retrieve access token call did not contain an access token', PEAR_LOG_ERR);
				}
			} else {
				global $logger;
				$logger->log('Curl Error in Hoopla API call to retrieve access token', PEAR_LOG_ERR);
			}
		} else {
			global $logger;
			$logger->log('Hoopla API user and/or password not set. Can not retrieve access token', PEAR_LOG_ERR);
		}
		return false;
	}

	/**
	 * @param $hooplaId
	 * @param $user User
	 */
	public function checkoutHooplaItem($hooplaId, $user) {
		if ($this->hooplaEnabled) {
			$checkoutURL = $this->getHooplaBasePatronURL($user);
			if (!empty($checkoutURL)) {

				$hooplaId = self::recordIDtoHooplaID($hooplaId);
				$checkoutURL      .= '/' . $hooplaId;
				$checkoutResponse = $this->getAPIResponse($checkoutURL, array(), 'POST');
				if ($checkoutResponse) {
					if (!empty($checkoutResponse->contentId)) {
						return array(
							'success'   => true,
							'message'   => $checkoutResponse->message,
							'title'     => $checkoutResponse->title,
							'HooplaURL' => $checkoutResponse->url,
							'due'       => $checkoutResponse->due
						);
 						// Example Success Response
						//{
						//	'contentId': 10051356,
						//  'title': 'The Night Before Christmas',
						//  'borrowed': 1515799430,
						//  'due': 1517613830,
						//  'kind': 'AUDIOBOOK',
						//  'url': 'https://www-dev.hoopladigital.com/title/10051356',
						//  'message': 'You can now enjoy this title through Friday, February 2.  You can stream it to your browser, or download it for offline viewing using our Amazon, Android, or iOS mobile apps.'
						//}
					} else {
						return array(
							'success' => false,
							'message' => isset($checkoutResponse->message) ? $checkoutResponse->message : 'An error occurred checking out the Hoopla title.'
						);
					}

				} else {
					return array(
						'success' => false,
						'message' => 'An error occurred checking out the Hoopla title.'
					);
				}
			} elseif (!$this->getHooplaLibraryID($user)) {
				return array(
					'success' => false,
					'message' => 'Your library does not have Hoopla integration enabled.'
				);
			} else {
				return array(
					'success' => false,
					'message' => 'There was an error retrieving your library card number.'
				);
			}
		} else {
			return array(
				'success' => false,
				'message' => 'Hoopla integration is not enabled.'
			);
		}
	}

	public function returnHooplaItem($hooplaId, $user) {
		if ($this->hooplaEnabled) {
			$returnHooplaItemURL = $this->getHooplaBasePatronURL($user);
			if (!empty($returnHooplaItemURL)) {
				$itemId = self::recordIDtoHooplaID($hooplaId);
				$returnHooplaItemURL .= "/$itemId";
				$result = $this->getAPIResponseReturnHooplaTitle($returnHooplaItemURL);
				if ($result) {
					return array(
						'success' => true,
						'message' => 'The title was successfully returned.'
					);
				} else {
					return array(
						'success' => false,
						'message' => 'There was an error returning this title.'
					);
				}

			} elseif (!$this->getHooplaLibraryID($user)) {
				return array(
					'success' => false,
					'message' => 'Your library does not have Hoopla integration enabled.'
				);
			} else {
				return array(
					'success' => false,
					'message' => 'There was an error retrieving your library card number.'
				);
			}
		} else {
			return array(
				'success' => false,
				'message' => 'Hoopla integration is not enabled.'
			);
		}
	}
}
