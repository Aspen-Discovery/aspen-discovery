<?php

class SystemUtils {
	// Returns a file size limit in bytes based on the PHP upload_max_filesize
	// and post_max_size
	static function file_upload_max_size() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$post_max_size = SystemUtils::parse_size(ini_get('post_max_size'));
			if ($post_max_size > 0) {
				$max_size = $post_max_size;
			}

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = SystemUtils::parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}

	static function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		/** @noinspection RegExpRedundantEscape */
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}

	static function recursive_rmdir($dir): bool {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object)) {
						SystemUtils::recursive_rmdir($dir . "/" . $object);
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			rmdir($dir);
			return true;
		} else {
			return false;
		}
	}

	static function validateAge($minimumAge, $dob, $maximumAge = null): bool {
		$today = date("d-m-Y");//today's date

		$dob = new DateTime($dob);
		$today = new DateTime($today);

		$interval = $dob->diff($today);

		$age = $interval->y;

		if ($age >= $minimumAge) {
			if(isset($maximumAge) && $maximumAge <= $age){
				return false;
			}
			return true;
		}
		return false;
	}

	static function validatePhoneNumber($phone): bool {
		$justNumber = preg_replace('/[^0-9]/', '', $phone);
		if (preg_match('/[^0-9()\-+\s.]/', $phone)) {
			return false;
		}
		else if (strlen($justNumber) == 10) {
			return true;
		}
		else if (substr($phone, 0, 1) == '+') {
			if (strlen($justNumber) >= 10) {
				return true;
			}
		}
		return false;
	}

	static function validateAddress($streetAddress, $city, $state, $zip): bool {
		$baseUrl = 'https://api.usps.com';
		require_once ROOT_DIR . '/sys/CurlWrapper.php';

		//GET OAUTH TOKEN
		$getOauthToken = new CurlWrapper();
		$getOauthToken->addCustomHeaders([
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		], false);

		require_once ROOT_DIR . '/sys/Administration/USPS.php';
		$uspsInfo = USPS::getUSPSInfo();
		$postParams = [
			'grant_type'=>'client_credentials',
			'client_id'=>$uspsInfo->clientId,
			'client_secret'=>$uspsInfo->clientSecret,
		];

		$url = $baseUrl . '/oauth2/v3/token';
		$accessTokenResults = $getOauthToken->curlPostPage($url, $postParams);
		$accessToken = "";
		if ($accessTokenResults) {
			$jsonResponse = json_decode($accessTokenResults);
			if (isset($jsonResponse->access_token)) {
				$accessToken = $jsonResponse->access_token;
			}
		}

		//ADDRESS VALIDATION
		$validateAddress = new CurlWrapper();
		$validateAddress->addCustomHeaders([
			'Authorization: Bearer ' . $accessToken,
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		], true);

		$url = $baseUrl . '/addresses/v3/address?streetAddress=' . urlencode($streetAddress) . '&city=' . urlencode($city) . '&state=' . $state . '&ZIPCode=' . $zip;
		$validateAddressResults = $validateAddress->curlGetPage($url);
		ExternalRequestLogEntry::logRequest('usps.validateAddress', 'GET', $url, $validateAddress->getHeaders(), '', $validateAddress->getResponseCode(), $validateAddressResults, []);

		if ($validateAddress->getResponseCode() == 200){
			return true;
		}

		return false;
	}

	static function startBackgroundProcess($processName, $additionalArguments = null) : array {
		if (file_exists(ROOT_DIR . "/cron/$processName.php")) {
			if (!UserAccount::isLoggedIn()) {
				return [
					'success' => false,
					'message' => 'Cannot start background process when not logged in.'
				];
			}elseif (!UserAccount::isStaff()) {
				return [
					'success' => false,
					'message' => 'Only Staff can start background processes.'
				];
			}else{
				require_once ROOT_DIR . '/sys/Administration/BackgroundProcess.php';
				$backgroundProcess = new BackgroundProcess();
				$backgroundProcess->name = $processName;
				$backgroundProcess->startTime = time();
				$backgroundProcess->isRunning = 1;
				$backgroundProcess->owningUserId = UserAccount::getActiveUserId();
				if ($backgroundProcess->insert()) {
					$backgroundProcessId = $backgroundProcess->id;
					foreach ($additionalArguments as $argument) {
						if (str_contains($argument, ';')) {
							return [
								'success' => false,
								'message' => 'Invalid argument.'
							];
						}
					}
					$args = "";
					if (!empty($additionalArguments)) {
						$args = implode(" ", $additionalArguments);
					}
					global $serverName;

					$phpProcess = "php";
					if (str_starts_with(php_uname(), "Windows")){
						$phpProcess = $_SERVER['PHPRC'] . '\php.exe';
					}
					if (SystemUtils::execInBackground($phpProcess . ' ' . ROOT_DIR . "/cron/$processName.php $serverName $backgroundProcessId $args")){
						return [
							'success' => true,
							'message' => 'Background process has been started.',
							'backgroundProcessId' => $backgroundProcessId
						];
					}else{
						return [
							'success' => false,
							'message' => 'Could not add background process to the database.'
						];
					}
				}else{
					return [
						'success' => false,
						'message' => 'Could not add background process to the database.'
					];
				}
			}
		}else{
			return [
				'success' => false,
				'message' => 'Invalid process name'
			];
		}
	}

	private static function execInBackground($cmd) : bool {
		if (str_starts_with(php_uname(), "Windows")){
			$cmd = str_replace('/', '\\', $cmd);
			$fullCommand = "start /B ". $cmd . ' 2>&1 &';
			$processHnd = proc_open($fullCommand, [], $pipes, null, null);
			if ($processHnd === false) {
				return false;
			}else{
				proc_close($processHnd);
				return true;
			}
		} else {
			if (exec($cmd . " > /dev/null &") === false) {
				return false;
			}else{
				return true;
			}
		}
	}
}