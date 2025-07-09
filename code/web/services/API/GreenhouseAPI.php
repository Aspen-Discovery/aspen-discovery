<?php

require_once ROOT_DIR . '/services/API/AbstractAPI.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCache.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class GreenhouseAPI extends AbstractAPI {
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		//Make sure the user can access the API based on the IP address
		if (!in_array($method, [
				'getLibraries',
				'getLibrary',
				'authenticateTokens',
				'getNotificationAccessToken',
				'updateAspenLiDABuild',
			]) && !IPAddress::allowAPIAccessForClientIP()) {

			$this->forbidAPIAccess();
		}

		//Move a few methods from GreenhouseAPI to CommunityAPI, but maintain compatibility
		// with existing installations by forwarding the requests
		if (in_array($method, [
			'addTranslationTerm',
			'getDefaultTranslation',
			'setTranslation',
		])) {
			require_once ROOT_DIR . '/services/API/CommunityAPI.php';
			$communityAPI = new CommunityAPI();
			$communityAPI->launch();
			return;
		}

		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (method_exists($this, $method)) {
			$result = $this->$method();
			$output = json_encode($result);
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('GreenhouseAPI', $method);
			ExternalRequestLogEntry::logRequest('GreenhouseAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
		} else {
			$output = json_encode(['error' => 'invalid_method']);
		}
		echo $output;
	}

	public function authenticateTokens(): array {
		if (isset($_POST['key1']) && isset($_POST['key2'])) {

			$key1 = $_POST['key1'];
			$key2 = $_POST['key2'];

			$keychain = [];

			require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
			$greenhouseSettings = new GreenhouseSettings();
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

			if (array_key_exists('1', $keychain) && array_key_exists('2', $keychain) && $keychain['1'] === true && $keychain['2'] === true) {
				return ['success' => true];
			}
		}

		return ['success' => false];
	}


	/** @noinspection PhpUnused */
	public function getNotificationAccessToken() : array {
		$accessToken = null;
		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		if ($greenhouseSettings->find(true)) {
			$accessToken = $greenhouseSettings->notificationAccessToken;
		}
		return ['token' => $accessToken];
	}

	/** @noinspection PhpUnused */
	public function updateSiteStatuses() : array {
		require_once ROOT_DIR . '/sys/Greenhouse/AspenSiteCheck.php';
		$sites = new AspenSite();
		$sites->whereAdd('implementationStatus != 4 AND implementationStatus != 0');
		$sites->monitored = 1;
		if (!empty($_REQUEST['siteId']) && is_numeric($_REQUEST['siteId'])) {
			$sites->id = $_REQUEST['siteId'];
		}
		$sites->orderBy('name ASC');
		$sites->find();
		$numSitesUpdated = 0;

		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		$greenhouseDevAlertSlackHook = null;
		$greenhouseSystemsAlertSlackHook = null;
		if ($greenhouseSettings->find(true)) {
			$greenhouseDevAlertSlackHook = $greenhouseSettings->greenhouseAlertSlackHook;
			$greenhouseSystemsAlertSlackHook = $greenhouseSettings->greenhouseSystemsAlertSlackHook;
		}
		$start = time();
		while ($sites->fetch()) {
			$statusTime = time();
			$siteStatus = $sites->updateStatus();
			if ($sites->version != $siteStatus['version']) {
				$sites->version = $siteStatus['version'];
				$sites->update();
			}

			//Store checks
			$alertText = "";
			$systemsAlertText = "";
			$notification = "";
			$sendAlert = false;

			if ($siteStatus['alive'] == false && $sites->isOnline !== 1 && $sites->isOnline !== "1") {
				if ((($start - $sites->lastOfflineTime) > 4 * 60 * 60) || ($sites->lastOfflineTime > $sites->lastNotificationTime)) {
					$sendAlert = true;
					$systemsAlertText .= "- :fire: Greenhouse unable to connect to server: {$sites->lastOfflineNote}\n";
					$notification = "<!here>";
				}
			}

			if ($siteStatus['wasOffline']) {
				// send offline recovery message
				$sendAlert = true;
				$systemsAlertText .= "- ~Greenhouse connectivity recovered!~\n";
			}

			foreach ($siteStatus['checks'] as $key => $check) {
				$aspenSiteCheck = new AspenSiteCheck();
				$aspenSiteCheck->siteId = $sites->id;
				$aspenSiteCheck->checkName = $check['name'];
				if (in_array(strtolower($aspenSiteCheck->checkName), [
					'antivirus',
					'backup',
					'data disk space',
					'encryption key',
					'load average',
					'memory usage',
					'usr disk space',
					'wait time',
				])) {
					$channel = 'systems';
				} else {
					$channel = 'dev';
				}
				$checkExists = false;
				if ($aspenSiteCheck->find(true)) {
					$checkExists = true;
				}
				$status = $check['status'];
				if ($status == 'okay') {
					if ($aspenSiteCheck->currentStatus !== "0" && $aspenSiteCheck->currentStatus !== 0) {
						if ($channel == 'systems') {
							$systemsAlertText .= '- ~' . $check['name'] . " recovered!~\n";
						} else {
							$alertText .= '- ~' . $check['name'] . " recovered!~\n";
						}
						$wasCritical = false;
						$wasWarning = false;
						if ($aspenSiteCheck->currentStatus == 2) {
							$wasCritical = true;
						}
						if ($aspenSiteCheck->currentStatus == 1) {
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
						if ($wasCritical) {
							$sendAlert = true;
						}

					}
				} elseif ($status == 'warning') {
					if ($aspenSiteCheck->currentStatus != 1) {
						$aspenSiteCheck->currentStatus = 1;
						$aspenSiteCheck->currentNote = $check['note'];
						$aspenSiteCheck->lastWarningTime = $statusTime;
					}
					if ($channel == 'systems') {
						$systemsAlertText .= "- <{$aspenSiteCheck->getUrl($sites)}|" . $check['name'] . "> is warning : {$aspenSiteCheck->currentNote} \n";
					} else {
						$alertText .= "- <{$aspenSiteCheck->getUrl($sites)}|" . $check['name'] . "> is warning : {$aspenSiteCheck->currentNote} \n";
						//We will add this to the alert if we have been warning for more than 4 hours and the warning started after the last alert was sent.
					}
					if ((($start - $aspenSiteCheck->lastWarningTime) > 4 * 60 * 60) && ($aspenSiteCheck->lastWarningTime > $sites->lastNotificationTime)) {
						$sendAlert = true;
					}
				} else {
					if ($aspenSiteCheck->currentStatus != 2) {
						$aspenSiteCheck->currentStatus = 2;
						$aspenSiteCheck->currentNote = $check['note'];
						$aspenSiteCheck->lastErrorTime = $statusTime;
						//Send an alert as soon as we see a critical alert the first time.
						$sendAlert = true;
					}
					//Send an alert if we have never sent an alert
					if ($sites->lastNotificationTime == 0) {
						$sendAlert = true;
					}
					if ($channel == 'systems') {
						$systemsAlertText .= "- :fire: <{$aspenSiteCheck->getUrl($sites)}|" . $check['name'] . "> is critical : {$aspenSiteCheck->currentNote}\n";
					} else {
						$alertText .= "- :fire: <{$aspenSiteCheck->getUrl($sites)}|" . $check['name'] . "> is critical : {$aspenSiteCheck->currentNote}\n";
					}
					$notification = "<!here>";
				}
				if ($checkExists) {
					$aspenSiteCheck->update();
				} else {
					$aspenSiteCheck->insert();
				}
			}

			//Store stats


			//We won't send slack alerts for anything that is a test site or still in implementation
			if (($sites->implementationStatus == 0) || ($sites->implementationStatus == 1) || ($sites->implementationStatus == 4)) {
				//The site is installing, implementing, or retired, don't alert
				$sendAlert = false;
			} elseif ($sites->siteType != 0 ) {
				//The site is not a library partner
				$sendAlert = false;
			}

			//Check to see if we need to send an alert
			global $serverName;
			if (strlen($alertText) > 0 && $sendAlert) {
				$alertText = '*' . $sites->name . "* $notification\n" . $alertText . "\nFrom $serverName";
				if (!empty($greenhouseDevAlertSlackHook)) {
					$curlWrapper = new CurlWrapper();
					$headers = [
						'Accept: application/json',
						'Content-Type: application/json',
					];
					$curlWrapper->addCustomHeaders($headers, false);
					$body = new stdClass();
					$body->text = $alertText;
					$curlWrapper->curlPostPage($greenhouseDevAlertSlackHook, json_encode($body));
				}
				$sites->lastNotificationTime = $start;
				$sites->update();
			}
			// Also check if there are systems alerts to send
			if (strlen($systemsAlertText) > 0 && $sendAlert) {
				$alertText = '*' . $sites->name . "* $notification\n" . $systemsAlertText . "\nFrom $serverName";
				$slackHook = $greenhouseDevAlertSlackHook;
				if (!empty($greenhouseSystemsAlertSlackHook)) {
					$slackHook = $greenhouseSystemsAlertSlackHook;
				}
				if (!empty($slackHook)) {
					$curlWrapper = new CurlWrapper();
					$headers = [
						'Accept: application/json',
						'Content-Type: application/json',
					];
					$curlWrapper->addCustomHeaders($headers, false);
					$body = new stdClass();
					$body->text = $alertText;
					$curlWrapper->curlPostPage($slackHook, json_encode($body));
				}
				$sites->lastNotificationTime = $start;
				$sites->update();
			}

			//store stats
			$numSitesUpdated++;
		}
		return [
			'success' => true,
			'numSitesUpdated' => $numSitesUpdated,
			'elapsedTime' => time() - $start,
		];
	}

	public function getLibraries($returnAll = false, $reload = true): array {
		$return = [
			'success' => true,
			'libraries' => [],
		];

		// prep user location
		$userLatitude = $_GET['latitude'] ?? 0;
		$userLongitude = $_GET['longitude'] ?? 0;

		// get the release channel
		$releaseChannel = "any";
		if (isset($_GET['release_channel'])) {
			$releaseChannel = $_GET['release_channel'];
		}

		$aspenSite = new AspenSite();
		$aspenSite->find();
		while ($aspenSite->fetch()) {
			//Now see if we should return this for use in LiDA
			if ($aspenSite->implementationStatus == 1 || $aspenSite->implementationStatus == 2 || $aspenSite->implementationStatus == 3) {
				// Check the implementation status to make sure it's eligible for LiDA

				$version = $aspenSite->version;

				if ($aspenSite->appAccess == 1 || $aspenSite->appAccess == 3 || ($aspenSite->appAccess == 2 && ($releaseChannel == 'alpha' || $releaseChannel == 'beta' || $releaseChannel == 'zeta'))) {
					//See if we need to reload the cache
					$reloadCache = false;

					$existingCachedValues = new AspenSiteCache();
					$existingCachedValues->siteId = $aspenSite->id;
					$numRows = $existingCachedValues->count();
					$existingCachedValues->find();
					if ($numRows >= 1) {
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
					} else {
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
							}
						}
					}
				}
			}
		}
		if (!empty($return['libraries'])) {
			return $return;
		} elseif ($reload) {
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
		if (is_numeric($libraryLatitude) && is_numeric($libraryLongitude)) {
			if (empty($userLatitude) && empty($userLongitude)) {
				return 0;
			}else{
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
		}
		return $distance;
	}

	/** @noinspection PhpUnused */
	public function getLibrary(): array {
		$return = [
			'success' => true,
			'library' => [],
		];
		global $configArray;
		global $interface;

		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		require_once ROOT_DIR . '/sys/AspenLiDA/LocationSetting.php';

		// prep user location
		$userLatitude = $_GET['latitude'] ?? 0;
		$userLongitude = $_GET['longitude'] ?? 0;

		$num = 0;
		$enabledAccess = 0;
		$releaseChannel = 0;
		$location = new Location();
		$location->find();
		while ($location->fetch()) {
			$library = new Library();
			$library->libraryId = $location->libraryId;
			if ($library->find(true)) {
				$version = $interface->getVariable('aspenVersion');
				if (str_contains($version, ' ')) {
					$version  = substr($version, 0, strpos($version, ' '));
				}
				if ($version >= "22.09.00") {
					require_once ROOT_DIR . '/sys/AspenLiDA/LocationSetting.php';
					$appSettings = new LocationSetting();
					$appSettings->id = $location->lidaLocationSettingId;
					if ($appSettings->find(true)) {
						$releaseChannel = $appSettings->releaseChannel;
						$enabledAccess = $appSettings->enableAccess;
					} else {
						//There should be settings available, but if not disable access
						$releaseChannel = 0;
						$enabledAccess = 0;
					}
				} else {
					$releaseChannel = $location->appReleaseChannel;
					$enabledAccess = $location->enableAppAccess;
				}

				if ($enabledAccess == 1 || $enabledAccess == "1") {
					$baseUrl = $library->baseUrl;
					if (empty($baseUrl)) {
						$baseUrl = $configArray['Site']['url'];
					}
					if (!str_ends_with($baseUrl, '/')){
						$baseUrl .= '/';
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

					$distance = $this->findDistance($userLongitude, $userLatitude, $location->longitude, $location->latitude, $location->unit);

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
					if (isset($location) && ($location->useLibraryThemes || empty($location->getThemes()))) {
						$theme->id = $library->getPrimaryTheme()->themeId;
					} else {
						$theme->id = $location->getPrimaryTheme()->themeId;
					}
					if ($theme->find(true)) {
						$theme->applyDefaults();

						$themeArray['themeId'] = $theme->id;

						if($theme->logoName) {
							$themeArray['logo'] = $configArray['Site']['url'] . '/files/original/' . $theme->logoName;
						}else{
							$themeArray['logo'] = '';
						}
						if($theme->favicon) {
							$themeArray['favicon'] = $configArray['Site']['url'] . '/files/original/' . $theme->favicon;
						}else{
							$themeArray['favicon'] = '';
						}
						if($theme->headerLogoApp) {
							$themeArray['headerLogo'] = $configArray['Site']['url'] . '/files/original/' . $theme->headerLogoApp;
							[
								$width,
								$height,
							] = @getimagesize(ROOT_DIR . '/files/original/' . $theme->headerLogoApp);
							$themeArray['headerLogoWidth'] = $width;
							$themeArray['headerLogoHeight'] = $height;
						}else{
							$themeArray['headerLogo'] = '';
							$themeArray['headerLogoWidth'] = 0;
							$themeArray['headerLogoHeight'] = 0;
						}
						$themeArray['headerLogoAlignment'] = $theme->headerLogoAlignmentApp;
						$themeArray['headerLogoBackgroundColor'] = $theme->headerLogoAlignmentApp;
						$themeArray['primaryBackgroundColor'] = $theme->primaryBackgroundColor;
						$themeArray['primaryForegroundColor'] = $theme->primaryForegroundColor;
						$themeArray['secondaryBackgroundColor'] = $theme->secondaryBackgroundColor;
						$themeArray['secondaryForegroundColor'] = $theme->secondaryForegroundColor;
						$themeArray['tertiaryBackgroundColor'] = $theme->tertiaryBackgroundColor;
						$themeArray['tertiaryForegroundColor'] = $theme->tertiaryForegroundColor;

						$return['library'][] = [
							'latitude' => $latitude,
							'longitude' => $longitude,
							'unit' => $location->unit,
							'name' => $location->displayName,
							'locationId' => (string)$location->locationId,
							'libraryId' => (string)$library->libraryId,
							'siteId' => $library->libraryId . '.' . $location->locationId,
							'solrScope' => $solrScope,
							'baseUrl' => $baseUrl,
							'releaseChannel' => $releaseChannel,
							'favicon' => $themeArray['favicon'],
							'logo' => $themeArray['logo'],
							'theme' => $themeArray,
							'distance' => $distance,
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
			'libraryId' => (string)$libraryLocation->libraryId,
			'locationId' => (string)$libraryLocation->locationId,
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

	public function setLibraryCache($aspenSite) {
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
	function updateAspenLiDABuild() {
		global $logger;
		$logger->log('Patch update found, updating Aspen LiDA Build Tracker...', Logger::LOG_ERROR);
		$logger->log(print_r($_REQUEST, true), Logger::LOG_ERROR);
		$result = [
			'success' => false,
		];
		if (!empty($_REQUEST['app']) && !empty($_REQUEST['version']) && !empty($_REQUEST['build']) && !empty($_REQUEST['channel']) && !empty($_REQUEST['platform']) && !empty($_REQUEST['id']) && !empty($_REQUEST['patch']) && !empty($_REQUEST['timestamp'])) {
			require_once ROOT_DIR . '/sys/Greenhouse/AspenLiDABuild.php';
			$build = new AspenLiDABuild();
			$build->name = $_REQUEST['app'];
			$build->version = $_REQUEST['version'];
			$build->buildVersion = $_REQUEST['build'];
			$build->channel = $_REQUEST['channel'];
			$build->platform = $_REQUEST['platform'];
			if($build->find(true)) {
				if($build->isEASUpdate == 0) {
					$patch = $build;
					unset($patch->id);
					$patch->isEASUpdate = 1;
					$patch->updateId = $_REQUEST['id'];
					$patch->updateCreated = $_REQUEST['timestamp'];
					$patch->patch = $_REQUEST['patch'];
					if($patch->insert()) {
						$result['success'] = true;
						$result['message'] = 'Aspen LiDA Build Tracker has been updated for this patch.';

						require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
						$greenhouseSettings = new GreenhouseSettings();
						$greenhouseAlertSlackHook = null;
						$shouldSendBuildAlert = false;
						if ($greenhouseSettings->find(true)) {
							$greenhouseAlertSlackHook = $greenhouseSettings->greenhouseAlertSlackHook;
							$shouldSendBuildAlert = $greenhouseSettings->sendBuildTrackerAlert;
						}

						if ($greenhouseAlertSlackHook && $shouldSendBuildAlert) {
							global $configArray;
							$buildTracker = $configArray['Site']['url'] . '/Greenhouse/AspenLiDABuildTracker/';
							$notification = "- <$buildTracker|Patch completed> for $patch->platform for version $patch->version b[$patch->buildVersion] p[$patch->patch] c[$patch->channel]";
							$alertText = "*$patch->name* $notification\n";
							$curlWrapper = new CurlWrapper();
							$headers = [
								'Accept: application/json',
								'Content-Type: application/json',
							];
							$curlWrapper->addCustomHeaders($headers, false);
							$body = new stdClass();
							$body->text = $alertText;
							$curlWrapper->curlPostPage($greenhouseAlertSlackHook, json_encode($body));
						}

					} else {
						$result['message'] = 'Unable to update Aspen LiDA Build Tracker.';
					}
				} else {
					$result['success'] = true;
					$result['message'] = 'Aspen LiDA Build Tracker already updated for this patch.';
				}
			} else {
				$result['message'] = 'Unable to find existing build using provided data.';
			}
		} else {
			$result['message'] = 'Not enough data provided in request to update Greenhouse';
		}

		$logger->log(print_r($result, true), Logger::LOG_ERROR);
		return $result;
	}

	/** @noinspection PhpUnused */
	function addSharedContent(): array {
		$result = [
			'success' => false,
		];

		require_once ROOT_DIR . '/sys/Community/SharedContent.php';
		$sharedContent = new SharedContent();
		$sharedContent->name = $_REQUEST['name'];
		$sharedContent->type = $_REQUEST['type'];
		$sharedContent->description = $_REQUEST['description'];
		$sharedContent->shareDate = time();
		$sharedContent->sharedFrom = $_REQUEST['sharedFrom'];
		$sharedContent->sharedByUserName = $_REQUEST['sharedByUserName'];
		$sharedContent->data = $_REQUEST['data'];
		if ($sharedContent->insert()) {
			$result['success'] = true;
		} else {
			$result['message'] = $sharedContent->getLastError();
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function addScheduledUpdate(): array {
		$result = [
			'success' => false,
		];

		require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
		$scheduledUpdate = new ScheduledUpdate();
		$scheduledUpdate->status = $_REQUEST['status'];
		$scheduledUpdate->updateType = $_REQUEST['runType'] ?? 'patch';
		$scheduledUpdate->updateToVersion = $_REQUEST['updateToVersion'];
		$scheduledUpdate->dateScheduled = $_REQUEST['dateScheduled'];
		$scheduledUpdate->greenhouseId = $_REQUEST['greenhouseId'];
		$scheduledUpdate->remoteUpdate = false;
		$scheduledUpdate->siteId = $_REQUEST['greenhouseSiteId'] ?? '';
		if($scheduledUpdate->insert()) {
			$result = [
				'success' => true,
			];
		} else {
			$result = [
				'success' => false,
				'message' => $scheduledUpdate->getLastError(),
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function updateScheduledUpdate(): array {
		$result = [
			'success' => false,
		];

		require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
		$scheduledUpdate = new ScheduledUpdate();
		$scheduledUpdate->id = $_REQUEST['greenhouseId'];
		$scheduledUpdate->siteId = $_REQUEST['greenhouseSiteId'];
		if($scheduledUpdate->find(true)) {
			$scheduledUpdate->status = $_REQUEST['status'];
			$scheduledUpdate->updateType = $_REQUEST['runType'];
			$scheduledUpdate->updateToVersion = $_REQUEST['updateToVersion'];
			$scheduledUpdate->dateScheduled = $_REQUEST['dateScheduled'];
			$scheduledUpdate->dateRun = $_REQUEST['dateRun'];
			$scheduledUpdate->notes = $_REQUEST['notes'];
			if($scheduledUpdate->update()) {
				$result = [
					'success' => true,
				];
			} else {
				$result = [
					'success' => false,
					'message' => $scheduledUpdate->getLastError(),
				];
			}

			$siteName = 'Unknown Site';
			require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
			$site = new AspenSite();
			$site->id = $scheduledUpdate->siteId;
			if($site->find(true)) {
				$siteName = $site->name;
			}

			// send Slack notification
			require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
			$greenhouseSettings = new GreenhouseSettings();
			$greenhouseAlertSlackHook = null;
			if ($greenhouseSettings->find(true)) {
				$greenhouseAlertSlackHook = $greenhouseSettings->greenhouseAlertSlackHook;
			}

			if ($greenhouseAlertSlackHook) {
				if($scheduledUpdate->status === 'failed') {
					$notification = "- :fire: Update failed for $siteName while updating to $scheduledUpdate->updateToVersion ($scheduledUpdate->updateType)";
					$notification .= '<!here>';
				} else if($scheduledUpdate->status === 'complete') {
					$notification = "- Update completed for $siteName to $scheduledUpdate->updateToVersion ($scheduledUpdate->updateType)";
				} else {
					$notification = null;
				}
				$alertText = "*$siteName* $notification\n";
				if($notification) {
					$curlWrapper = new CurlWrapper();
					$headers = [
						'Accept: application/json',
						'Content-Type: application/json',
					];
					$curlWrapper->addCustomHeaders($headers, false);
					$body = new stdClass();
					$body->text = $alertText;
					$curlWrapper->curlPostPage($greenhouseAlertSlackHook, json_encode($body));
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getReleaseInformation(): array {
		require_once ROOT_DIR . '/sys/Development/AspenRelease.php';
		$release = new AspenRelease();
		$release->orderBy('name DESC');
		$release->find();
		$releases = [];
		while($release->fetch()) {
			$releases[$release->name]['id'] = $release->id;
			$releases[$release->name]['version'] = $release->name;
			$releases[$release->name]['date'] = $release->releaseDate;
		}

		return [
			'success' => true,
			'releases' => $releases,
		];
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
