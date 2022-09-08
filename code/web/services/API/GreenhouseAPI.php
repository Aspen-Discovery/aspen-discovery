<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class GreenhouseAPI extends Action
{
	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		//Make sure the user can access the API based on the IP address
		if (!in_array($method, array('getLibraries', 'getLibrary', 'authenticateTokens', 'getNotificationAccessToken')) && !IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}

		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if ($method != 'getCatalogConnection' && $method != 'getUserForApiCall' && method_exists($this, $method)) {
			$result = $this->$method();
			$output = json_encode($result);
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('GreenhouseAPI', $method);
			ExternalRequestLogEntry::logRequest('GreenhouseAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
		}
		echo $output;
	}

	public function authenticateTokens() : array {
		if (isset($_POST['key1']) && isset($_POST['key2'])) {

			$key1 = $_POST['key1'];
			$key2 = $_POST['key2'];

			require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
			$greenhouseSettings = new GreenhouseSettings();
			$keychain = null;
			if ($greenhouseSettings->find(true)) {
				for ($key = 1; $key <= 5; $key += 1) {
					$currentKey = "apiKey" . $key;

					if ($key1 == $greenhouseSettings->$currentKey) {
						$keychain['1'] = true;
					}

					if ($key2 == $greenhouseSettings->$currentKey) {
						$keychain['2'] = true;
					}
				}
			}

			if ($keychain['1'] && $keychain['2'] == true) {
				return ['success' => true];
			}
		}

		return [ 'success' => false ];
	}

	public function getNotificationAccessToken() {
		$accessToken = null;
		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		if($greenhouseSettings->find(true)){
			$accessToken = $greenhouseSettings->notificationAccessToken;
		}
		return ['token' => $accessToken];
	}

	public function updateSiteStatuses() {
		require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCheck.php';
		$sites = new AspenSite();
		$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
		$sites->orderBy('name ASC');
		$sites->find();
		$numSitesUpdated = 0;

		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		$greenhouseAlertSlackHook = null;
		if ($greenhouseSettings->find(true)){
			$greenhouseAlertSlackHook = $greenhouseSettings->greenhouseAlertSlackHook;
		}
		$start = time();
		while ($sites->fetch()){
			$statusTime = time();
			$siteStatus = $sites->updateStatus();
			if ($sites->version != $siteStatus['version']){
				$sites->version = $siteStatus['version'];
				$sites->update();
			}

			//Store checks
			$alertText = "";
			$notification = "";
			$sendAlert = false;

			if ($siteStatus['alive'] == false && $sites->isOnline !== 1 && $sites->isOnline !== "1") {
				if ((($start - $sites->lastOfflineTime) > 4 * 60 * 60) || ($sites->lastOfflineTime > $sites->lastNotificationTime)) {
					$sendAlert = true;
					$alertText .= "- :fire: Greenhouse unable to connect to server: {$sites->lastOfflineNote}\n";
					$notification = "<!here>";
				}
			}

			if($siteStatus['wasOffline']) {
				// send offline recovery message
				$sendAlert = true;
				$alertText .= "- ~Greenhouse connectivity recovered!~\n";
			}

			foreach ($siteStatus['checks'] as $key => $check){
				$aspenSiteCheck = new AspenSiteCheck();
				$aspenSiteCheck->siteId = $sites->id;
				$aspenSiteCheck->checkName = $check['name'];
				$checkExists = false;
				if ($aspenSiteCheck->find(true)){
					$checkExists = true;
				}
				$status = $check['status'];
				if ($status == 'okay'){
					if ($aspenSiteCheck->currentStatus !== "0" && $aspenSiteCheck->currentStatus !== 0) {
						$alertText .= '- ~' . $check['name'] . " recovered!~\n";
						$wasCritical = false;
						$wasWarning = false;
						if ($aspenSiteCheck->currentStatus == 2){
							$wasCritical = true;
						}
						if ($aspenSiteCheck->currentStatus == 1){
							$wasWarning = true;
						}
						$aspenSiteCheck->currentStatus = 0;
						$aspenSiteCheck->currentNote = '';
						$aspenSiteCheck->lastOkTime = $statusTime;
						//Only send an alert when the service recovers if we alerted since the time it failed last.
						if ($wasWarning) {
							if ((($start - $aspenSiteCheck->lastWarningTime) > 4 * 60 * 60) && ($aspenSiteCheck->lastWarningTime > $sites->lastNotificationTime)) {
								$sendAlert = true;
							}
						}
						if ($wasCritical){
							$sendAlert = true;
						}

					}
				}elseif ($status == 'warning'){
					if ($aspenSiteCheck->currentStatus != 1) {
						$aspenSiteCheck->currentStatus = 1;
						$aspenSiteCheck->currentNote = $check['note'];
						$aspenSiteCheck->lastWarningTime = $statusTime;
					}
					$alertText .= "- <{$aspenSiteCheck->getUrl($sites)}|" . $check['name'] . "> is warning : {$aspenSiteCheck->currentNote} \n";
					//We will add this to the alert if we have been warning for more than 4 hours and the warning started after the last alert was sent.
					if ((($start - $aspenSiteCheck->lastWarningTime) > 4 * 60 * 60) && ($aspenSiteCheck->lastWarningTime > $sites->lastNotificationTime)){
						$sendAlert = true;
					}
				}else{
					if ($aspenSiteCheck->currentStatus != 2) {
						$aspenSiteCheck->currentStatus = 2;
						$aspenSiteCheck->currentNote = $check['note'];
						$aspenSiteCheck->lastErrorTime = $statusTime;
						//Send an alert as soon as we see a critical alert the first time.
						$sendAlert = true;
					}
					//Send an alert if we have never sent an alert
					if ($sites->lastNotificationTime == 0){
						$sendAlert = true;
					}
					$alertText .= "- :fire: <{$aspenSiteCheck->getUrl($sites)}|" . $check['name'] . "> is critical : {$aspenSiteCheck->currentNote}\n";
					$notification = "<!here>";
				}
				if ($checkExists){
					$aspenSiteCheck->update();
				}else{
					$aspenSiteCheck->insert();
				}
			}

			//Store stats


			//Check to see if we need to send an alert
			if (strlen($alertText) > 0 && $sendAlert){
				$alertText = '*' . $sites->name . "* $notification\n" . $alertText;
				if (!empty($greenhouseAlertSlackHook)) {
					$curlWrapper = new CurlWrapper();
					$headers = array(
						'Accept: application/json',
						'Content-Type: application/json',
					);
					$curlWrapper->addCustomHeaders($headers, false);
					$body = new stdClass();
					$body->text = $alertText;
					$curlWrapper->curlPostPage($greenhouseAlertSlackHook, json_encode($body));
				}
				$sites->lastNotificationTime = $start;
				$sites->update();
			}

			//store stats
			$numSitesUpdated++;
		}
		$return = [
			'success' => true,
			'numSitesUpdated' => $numSitesUpdated,
			'elapsedTime' => time() - $start,
		];
		return $return;
	}

	public function getLibraries($returnAll = false, $reload = true) : array
	{
		$return = [
			'success' => true,
			'libraries' => [],
		];

		// prep user location
		if (isset($_GET['latitude'])) { $userLatitude = $_GET['latitude']; } else { $userLatitude = 0; }
		if (isset($_GET['longitude'])) { $userLongitude = $_GET['longitude']; } else { $userLongitude = 0; }

		// get release channel
		$releaseChannel = "any";
		if (isset($_GET['release_channel'])) { $releaseChannel = $_GET['release_channel']; }

		$aspenSite = new AspenSite();
		$aspenSite->find();
		while($aspenSite->fetch()) {
			//Now see if we should return this for use in LiDA
			if($aspenSite->implementationStatus == 1 || $aspenSite->implementationStatus == 2|| $aspenSite->implementationStatus == 3) {
				// Check the implementation status to make sure it's eligible for LiDA

				$version = $aspenSite->version;

				if($aspenSite->appAccess == 1 || $aspenSite->appAccess == 3) {
				//See if we need to reload the cache
				$reloadCache = false;

				$existingCachedValues = new AspenSiteCache();
				$existingCachedValues->siteId = $aspenSite->id;
				$numRows = $existingCachedValues->count();
				$existingCachedValues->find();
				if($numRows >= 1) {
					// check for forced reload of cache
					if (isset($_REQUEST['reload']) && $reload) {
						$reloadCache = true;
					} else {
						//Check to see when the cache was last set
						$existingCachedValues->fetch();
						if ((time() - $existingCachedValues->lastUpdated) > (24.5 * 60 * 60)) {
							$reloadCache = true;
						}
					}
				}else {
					$reloadCache = true;
				}
				if ($reloadCache) {
					$this->setLibraryCache($aspenSite);
				}

				$libraryLocation = new AspenSiteCache();
				$libraryLocation->siteId = $aspenSite->id;
				$libraryLocation->find();
				while ($libraryLocation->fetch()) {
					$distance = $this->findDistance($userLongitude, $userLatitude, $libraryLocation->longitude, $libraryLocation->latitude, $libraryLocation->unit);

					if (($userLatitude == 0 && $userLongitude == 0) || $returnAll == true) {
						if ($releaseChannel == "production" && $libraryLocation->releaseChannel == '1') {
							$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
						} elseif ($releaseChannel == "beta" && ($libraryLocation->releaseChannel == '0' || $libraryLocation->releaseChannel == '1')) {
							$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
						} else {
							$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
						}
					} else {
						if ($distance <= 60) {
							if ($releaseChannel == "production" && $libraryLocation->releaseChannel == '1') {
								$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
							} elseif ($releaseChannel == "beta" && ($libraryLocation->releaseChannel == '0' || $libraryLocation->releaseChannel == '1')) {
								$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
							} else {
								$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
							}
						} elseif($aspenSite->name == "Test (ByWater)") {
							$return['libraries'][] = $this->setLibrary($aspenSite, $libraryLocation, $distance);
						}
					}
				}
			}
			}
		}
		if(!empty($return['libraries'])) {
			return $return;
		} else if(empty($return['libraries'])) {
			return $this->getLibraries(true, false);
		} else {
			$return['success'] = false;
			$return['message'] = 'Error fetching libraries';
			return $return;
		}
	}

	/** @noinspection PhpUnused */
	public function findDistance($userLongitude, $userLatitude, $libraryLongitude, $libraryLatitude, $unit) {
		$distance = 99999;
		if(is_numeric($libraryLatitude) && is_numeric($libraryLongitude)) {
			$theta = ($userLongitude - $libraryLongitude);
			$distance = sin(deg2rad($userLatitude)) * sin(deg2rad($libraryLatitude)) + cos(deg2rad($userLatitude)) * cos(deg2rad($libraryLatitude)) * cos(deg2rad($theta));

			$distance = acos($distance);
			$distance = rad2deg($distance);
			$distance = $distance * 60 * 1.1515;
			if ($unit == "Km") {
				$distance = $distance * 1.609344;
			}
			$distance = round($distance, 2);
		}
		return $distance;
	}

	/** @noinspection PhpUnused */
	public function getLibrary() : array {
		$return = [
			'success' => true,
			'library' => [],
		];
		global $configArray;
		global $interface;

		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		require_once ROOT_DIR . '/sys/AspenLiDA/AppSetting.php';

		$num = 0;
		$location = new Location();
		$location->find();
		while($location->fetch()) {
			$appSettings = new AppSetting();
			$appSettings->id = $location->lidaGeneralSettingId;
			if($appSettings->find(true)) {
				if ($appSettings->enableAccess == 1 || $appSettings->enableAccess == "1") {
					$libraryId = $location->libraryId;
					$library = new Library();
					$library->libraryId = $libraryId;
					if ($library->find(true)) {
						$version = $interface->getVariable('gitBranch');
						$baseUrl = $library->baseUrl;

						if (empty($baseUrl)) {
							$baseUrl = $configArray['Site']['url'];
						}

						$solrScope = false;

						$searchLibrary = $library;
						if ($searchLibrary) {
							$solrScope = $searchLibrary->subdomain;
						}

						if (!empty($location->latitude) || !empty($location->longitude)) {
							$latitude = $location->latitude;
							$longitude = $location->longitude;
						} else {
							$latitude = 0;
							$longitude = 0;
						}

						//TODO: We will eventually want to be able to search individual library branches in the app.
						// i.e. for schools
						//$searchLocation = $location;
						/*if ($searchLocation && $searchLibrary->getNumSearchLocationsForLibrary() > 1) {
							if ($searchLibrary && strtolower($searchLocation->code) == $solrScope) {
								$solrScope .= 'loc';
							} else {
								$solrScope = strtolower($searchLocation->code);
							}
							if (!empty($searchLocation->subLocation)) {
								$solrScope = strtolower($searchLocation->subLocation);
							}
						}*/

						//get the theme for the location
						$themeArray = [];
						$theme = new Theme();
						if (isset($location) && $location->theme != -1) {
							$theme->id = $location->theme;
						} else {
							$theme->id = $library->theme;
						}
						if ($theme->find(true)) {
							$theme->applyDefaults();

							$themeArray['themeId'] = $theme->id;
							$themeArray['logo'] = $configArray['Site']['url'] . '/files/original/' . $theme->logoName;
							$themeArray['favicon'] = $configArray['Site']['url'] . '/files/original/' . $theme->favicon;
							$themeArray['primaryBackgroundColor'] = $theme->primaryBackgroundColor;
							$themeArray['primaryForegroundColor'] = $theme->primaryForegroundColor;
							$themeArray['secondaryBackgroundColor'] = $theme->secondaryBackgroundColor;
							$themeArray['secondaryForegroundColor'] = $theme->secondaryForegroundColor;
							$themeArray['tertiaryBackgroundColor'] = $theme->tertiaryBackgroundColor;
							$themeArray['tertiaryForegroundColor'] = $theme->tertiaryForegroundColor;
						}

						//get the app settings for the location
						$releaseChannel = 0;
						if ($version >= "22.09.00") {
							require_once ROOT_DIR . '/sys/AspenLiDA/AppSetting.php';
							$appSettings = new AppSetting();
							$appSettings->id = $location->lidaGeneralSettingId;
							if ($appSettings->find(true)) {
								$releaseChannel = $appSettings->releaseChannel;
							}
						} else {
							$releaseChannel = $location->appReleaseChannel;
						}

						$return['library'][] = [
							'latitude' => $latitude,
							'longitude' => $longitude,
							'unit' => $location->unit,
							'name' => $location->displayName,
							'locationId' => $location->locationId,
							'libraryId' => $libraryId,
							'siteId' => $libraryId . '.' . $location->locationId,
							'solrScope' => $solrScope,
							'baseUrl' => $baseUrl,
							'releaseChannel' => $releaseChannel,
							'favicon' => $themeArray['favicon'],
							'logo' => $themeArray['logo'],
							'theme' => $themeArray,
						];

						$num = $num + 1;
					}
				}
			}
		}

		$return['count'] = $num;

		return $return;
	}


	public function setLibrary($aspenSite, $libraryLocation, $distance) {

		$thisLibrary = [
			'name' => $libraryLocation->name,
			'version' => $aspenSite->version,
			'librarySystem' => $aspenSite->name,
			'libraryId' => $libraryLocation->libraryId,
			'locationId' => $libraryLocation->locationId,
			'baseUrl' => $libraryLocation->baseUrl,
			'accessLevel' => $aspenSite->appAccess,
			'distance' => $distance,
			'solrScope' => $libraryLocation->solrScope,
			'releaseChannel' => $libraryLocation->releaseChannel,
			'siteId' => $libraryLocation->id,
			'logo' => $libraryLocation->logo,
			'favicon' => $libraryLocation->favicon,
			'primaryBackgroundColor' => $libraryLocation->primaryBackgroundColor,
			'primaryForegroundColor' => $libraryLocation->primaryForegroundColor,
			'secondaryBackgroundColor' => $libraryLocation->secondaryBackgroundColor,
			'secondaryForegroundColor' => $libraryLocation->secondaryForegroundColor,
			'tertiaryBackgroundColor' => $libraryLocation->tertiaryBackgroundColor,
			'tertiaryForegroundColor' => $libraryLocation->tertiaryForegroundColor,
		];

		return $thisLibrary;
	}

	public function setLibraryCache($aspenSite)
	{
		$fetchLibraryUrl = $aspenSite->baseUrl . '/API/GreenhouseAPI?method=getLibrary';
		if ($data = file_get_contents($fetchLibraryUrl)) {
			$searchData = json_decode($data);
			$libraryLocation = new AspenSiteCache();
			$libraryLocation->siteId = $aspenSite->id;
			$libraryLocation->delete(true);

			if ($searchData != null && $searchData->success) {
				foreach ($searchData->library as $findLibrary) {
					$libraryLocation = new AspenSiteCache();

					$libraryLocation->siteId = $aspenSite->id;
					$libraryLocation->version = $aspenSite->version;
					$libraryLocation->libraryId = $findLibrary->libraryId;
					$libraryLocation->locationId = $findLibrary->locationId;
					if (!isset($findLibrary->name)) {
						$libraryLocation->name = $findLibrary->locationName;
					} else {
						$libraryLocation->name = $findLibrary->name;
					}
					$libraryLocation->solrScope = $findLibrary->solrScope;
					$libraryLocation->latitude = $findLibrary->latitude;
					$libraryLocation->longitude = $findLibrary->longitude;
					$libraryLocation->unit = $findLibrary->unit;
					$libraryLocation->releaseChannel = $findLibrary->releaseChannel;

					if ($findLibrary->baseUrl == NULL) {
						$libraryLocation->baseUrl = $aspenSite->baseUrl;
					} else {
						$libraryLocation->baseUrl = $findLibrary->baseUrl;
					}

					if (isset($findLibrary->theme)) {
						$libraryLocation->logo = empty($findLibrary->theme->logo) ? '' : $findLibrary->theme->logo;
						$libraryLocation->favicon = empty($findLibrary->theme->favicon) ? '' : $findLibrary->theme->favicon;
						$libraryLocation->primaryBackgroundColor = empty($findLibrary->theme->primaryBackgroundColor) ? '' : $findLibrary->theme->primaryBackgroundColor;
						$libraryLocation->primaryForegroundColor = empty($findLibrary->theme->primaryForegroundColor) ? '' : $findLibrary->theme->primaryForegroundColor;
						$libraryLocation->secondaryBackgroundColor = empty($findLibrary->theme->secondaryBackgroundColor) ? '' : $findLibrary->theme->secondaryBackgroundColor;
						$libraryLocation->secondaryForegroundColor = empty($findLibrary->theme->secondaryForegroundColor) ? '' : $findLibrary->theme->secondaryForegroundColor;
						$libraryLocation->tertiaryBackgroundColor = empty($findLibrary->theme->tertiaryBackgroundColor) ? '' : $findLibrary->theme->tertiaryBackgroundColor;
						$libraryLocation->tertiaryForegroundColor = empty($findLibrary->theme->tertiaryForegroundColor) ? '' : $findLibrary->theme->tertiaryForegroundColor;
					}

					$libraryLocation->lastUpdated = time();
					$libraryLocation->insert();
				}
			}
		}
	}

	/** @noinspection PhpUnused */
	public function addTranslationTerm() : array {
		$translationTerm = new TranslationTerm();
		$translationTerm->term = $_REQUEST['term'];
		if (!$translationTerm->find(true)) {
			$translationTerm->isPublicFacing = $_REQUEST['isPublicFacing'];
			$translationTerm->isAdminFacing = $_REQUEST['isAdminFacing'];
			$translationTerm->isMetadata = $_REQUEST['isMetadata'];
			$translationTerm->isAdminEnteredData = $_REQUEST['isAdminEnteredData'];
			$translationTerm->lastUpdate = time();
			try {
				$translationTerm->insert();
				$result = [
					'success' => true,
					'message' => translate(['text' => 'The term was added.', 'isAdminFacing' => true])
				];
			}catch (Exception $e){
				$result = [
					'success' => false,
					'message' => translate(['text' => 'Could not update term. %1%', 'isAdminFacing'=> true, 1=>(string)$e])
				];
			}
		}else{
			$termChanged = false;
			if ($_REQUEST['isPublicFacing'] && !$translationTerm->isPublicFacing) {
				$translationTerm->isPublicFacing = $_REQUEST['isPublicFacing'];
				$termChanged = true;
			}
			if ($_REQUEST['isAdminFacing'] && !$translationTerm->isAdminFacing) {
				$translationTerm->isAdminFacing = $_REQUEST['isAdminFacing'];
				$termChanged = true;
			}
			if ($_REQUEST['isAdminFacing'] && !$translationTerm->isMetadata) {
				$translationTerm->isMetadata = $_REQUEST['isAdminFacing'];
				$termChanged = true;
			}
			if ($_REQUEST['isAdminEnteredData'] && !$translationTerm->isAdminEnteredData) {
				$translationTerm->isAdminEnteredData = $_REQUEST['isAdminEnteredData'];
				$termChanged = true;
			}
			if ($termChanged) {
				$translationTerm->lastUpdate = time();
				$translationTerm->update();
				$result = [
					'success' => true,
					'message' => translate(['text' => 'The term was updated.', 'isAdminFacing'=> true])
				];
			}else{
				$result = [
					'success' => true,
					'message' => translate(['text' => 'The term already existed.', 'isAdminFacing'=> true])
				];
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function getDefaultTranslation() {
		$result = [
			'success' => false
		];
		if (!empty($_REQUEST['term']) && !empty($_REQUEST['languageCode'])) {
			$translationTerm = new TranslationTerm();
			$translationTerm->term = $_REQUEST['term'];
			if ($translationTerm->find(true)) {
				$language = new Language();
				$language->code = $_REQUEST['languageCode'];
				if ($language->find(true)) {
					$translation = new Translation();
					$translation->termId = $translationTerm->id;
					$translation->languageId = $language->id;
					if ($translation->find(true)) {
						$result['success'] = true;
						$result['translation'] = $translation->translation;
					}else{
						$result['message'] = 'No translation found';
					}
				}else{
					$result['message'] = 'Could not find language';
				}
			}else{
				$result['message'] = 'Could not find term';
			}
		}else{
			$result['message'] = 'Term and/or languageCode not provided';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function setTranslation() {
		$result = [
			'success' => false
		];
		if (!empty($_REQUEST['term']) && !empty($_REQUEST['languageCode']) && !empty($_REQUEST['translation'])) {
			$translationTerm = new TranslationTerm();
			$translationTerm->term = $_REQUEST['term'];
			if ($translationTerm->find(true)) {
				$language = new Language();
				$language->code = $_REQUEST['languageCode'];
				if ($language->find(true)) {
					$translation = new Translation();
					$translation->termId = $translationTerm->id;
					$translation->languageId = $language->id;
					if ($translation->find(true)) {
						if (!$translation->translated) {
							$translation->translation = $_REQUEST['translation'];
							$translation->translated = 1;
							$translation->update();
							$result['success'] = true;
						}else{
							$result['message'] = 'Term already translated';
						}
					}else{
						$result['message'] = 'No translation found';
					}
				}else{
					$result['message'] = 'Could not find language';
				}
			}else{
				$result['message'] = 'Could not find term';
			}
		}else{
			$result['message'] = 'Term, languageCode, and/or translation  not provided';
		}
		return $result;
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}