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
		if (!in_array($method, array('getLibraries', 'getLibrary')) && !IPAddress::allowAPIAccessForClientIP()){
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
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
		}
		echo $output;
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
			if (($aspenSite->appAccess == 1) || ($aspenSite->appAccess == 3)) {
				$existingCachedValues = new AspenSiteCache();
				$existingCachedValues->siteId = $aspenSite->id;
				$numRows = $existingCachedValues->count();

				if($numRows > 1){
					$reloadCache = false;
					// check for forced reload of cache
					if (isset($_REQUEST['reload']) && $reload) {
						$reloadCache = true;
					}else{
						//Check to see if we have anything cached
						$libraryLocation = new AspenSiteCache();
						$libraryLocation->siteId = $aspenSite->id;
						if ($libraryLocation->find(true)){
							if ((time() - $libraryLocation->lastUpdated) > (24.5 * 60 * 60)) {
								$reloadCache = true;
							}
						}else{
							//Nothing cached, reload
							$reloadCache = true;
						}
					}

					if ($reloadCache){
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
				} else {
					$this->setLibraryCache($aspenSite, null);
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
		$theta = ($userLongitude - $libraryLongitude);
		$distance = sin(deg2rad($userLatitude)) * sin(deg2rad($libraryLatitude)) + cos(deg2rad($userLatitude)) * cos(deg2rad($libraryLatitude)) * cos(deg2rad($theta));

		$distance = acos($distance);
		$distance = rad2deg($distance);
		$distance = $distance * 60 * 1.1515;
		if ($unit == "Km") {
			$distance = $distance * 1.609344;
		}
		$distance = round($distance, 2);

		return $distance;
	}

	/** @noinspection PhpUnused */
	public function getLibrary() : array {
		$return = [
			'success' => true,
			'theme' => [],
			'library' => [],
		];
		global $configArray;
		global $librarySingleton;

		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		require_once ROOT_DIR . '/sys/Theming/Theme.php';

		$location = new Location();
		$location->find();
		while($location->fetch()) {
			if ($location->enableAppAccess == 1){
				$libraryId = $location->libraryId;
				$library = new Library();
				$library->libraryId = $libraryId;
				if ($library->find(true)) {
						$baseUrl = $library->baseUrl;

						if (empty($baseUrl)){
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

						$return['library'][] = [
							'latitude' => $latitude,
							'longitude' => $longitude,
							'unit' => $location->unit,
							'locationName' => $location->displayName,
							'locationId' => $location->locationId,
							'libraryId' => $libraryId,
							'solrScope' => $solrScope,
							'baseUrl' => $baseUrl,
							'releaseChannel' => $location->appReleaseChannel,
						];
				}
			}
		}

		$activeTheme = new Theme();
		$activeTheme->id = $librarySingleton->theme;
		if ($activeTheme->find(true)){
			$activeTheme->applyDefaults();
			if ($activeTheme->logoName) {
				$return['theme']['logo'] = $configArray['Site']['url'] . '/files/original/' . $activeTheme->logoName;
			}
			if($activeTheme->favicon) {
				$return['theme']['favicon'] = $configArray['Site']['url'] . '/files/original/' . $activeTheme->favicon;
			}
			$return['theme']['primaryBackgroundColor'] = $activeTheme->primaryBackgroundColor;
			$return['theme']['primaryForegroundColor'] = $activeTheme->primaryForegroundColor;
			$return['theme']['secondaryBackgroundColor'] = $activeTheme->secondaryBackgroundColor;
			$return['theme']['secondaryForegroundColor'] = $activeTheme->secondaryForegroundColor;
			$return['theme']['tertiaryBackgroundColor'] = $activeTheme->tertiaryBackgroundColor;
			$return['theme']['tertiaryForegroundColor'] = $activeTheme->tertiaryForegroundColor;

		}

		return $return;
	}


	public function setLibrary($aspenSite, $libraryLocation, $distance) {

		$thisLibrary = [
			'name' => $libraryLocation->name,
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

			foreach ($searchData->library as $findLibrary) {
				$libraryLocation = new AspenSiteCache();

				$libraryLocation->siteId = $aspenSite->id;
				$libraryLocation->libraryId = $findLibrary->libraryId;
				$libraryLocation->locationId = $findLibrary->locationId;
				$libraryLocation->name = $findLibrary->locationName;
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

				if (isset($searchData->theme)) {
					$libraryLocation->logo = $searchData->theme->logo;
					$libraryLocation->favicon = $searchData->theme->favicon;
					$libraryLocation->primaryBackgroundColor = $searchData->theme->primaryBackgroundColor;
					$libraryLocation->primaryForegroundColor = $searchData->theme->primaryForegroundColor;
					$libraryLocation->secondaryBackgroundColor = $searchData->theme->secondaryBackgroundColor;
					$libraryLocation->secondaryForegroundColor = $searchData->theme->secondaryForegroundColor;
					$libraryLocation->tertiaryBackgroundColor = $searchData->theme->tertiaryBackgroundColor;
					$libraryLocation->tertiaryForegroundColor = $searchData->theme->tertiaryForegroundColor;
				}

				$libraryLocation->lastUpdated = time();
				$libraryLocation->insert();
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