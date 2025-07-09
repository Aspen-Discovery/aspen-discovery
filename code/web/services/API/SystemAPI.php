<?php
require_once ROOT_DIR . '/services/API/AbstractAPI.php';

class SystemAPI extends AbstractAPI {
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		//Set Headers
		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		if ($method === 'getLogoFile') {
			return $this->$method();
		}else if ($method === 'getTranslation' || $method === 'getTranslationWithValues' || $method === 'getBulkTranslations') {
			//These methods don't need additional authentication, just return the data.
			$result = [
				'result' => $this->$method(),
			];
			$output = json_encode($result);
			echo $output;
			die();
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getLibraryInfo',
					'getLocationInfo',
					'getThemeInfo',
					'getAppSettings',
					'getLocationAppSettings',
					'getLanguages',
					'getVdxForm',
					'getLocalIllForm',
					'getSelfCheckSettings',
					'getSystemMessages',
					'dismissSystemMessage',
					'getLibraryLinks',
					'getCatalogStatus',
					'getLocations',
					'getMaterialsRequestForm'
				])) {
					$result = [
						'result' => $this->$method(),
					];
					$output = json_encode($result);
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('SystemAPI', $method);
				} else {
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('SystemAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if (!in_array($method, [
					'getCatalogConnection',
					'getUserForApiCall',
					'checkWhichUpdatesHaveRun',
					'getPendingDatabaseUpdates',
					'runSQLStatement',
					'markUpdateAsRun',
				]) && method_exists($this, $method)) {
				$result = [
					'result' => $this->$method(),
				];
				$output = json_encode($result);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('SystemAPI', $method);
			} else {
				$output = json_encode(['error' => 'invalid_method']);
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}

		return '';
	}

	/** @noinspection PhpUnused */
	public function getCatalogStatus(): array {
		$results = [
			'success' => true,
			'catalogStatus' => 0,
			'message' => null,
			'api' => [
				'message' => null
			]
		];

		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables !== false) {
			$results['catalogStatus'] = (int)$systemVariables->catalogStatus;
			if($systemVariables->catalogStatus > 0) {
				$results['message'] = translate(['text'=>$systemVariables->offlineMessage, 'isPublicFacing'=>true]);
				$results['api']['message'] = translate(['text'=>strip_tags($systemVariables->offlineMessage), 'isPublicFacing'=>true]);
			}
		}

		return $results;
	}

	public function getLibraries(): array {
		$return = [
			'success' => true,
			'libraries' => [],
		];
		$library = new Library();
		$library->orderBy('isDefault desc');
		$library->orderBy('displayName');
		$library->find();
		while ($library->fetch()) {
			$return['libraries'][$library->libraryId] = $library->getApiInfo();
		}
		return $return;
	}

	/** @noinspection PhpUnused */
	public function getLibraryInfo(): array {
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$library = new Library();
			$library->libraryId = $_REQUEST['id'];
			if ($library->find(true)) {
				return [
					'success' => true,
					'library' => $library->getApiInfo(),
				];
			} else {
				return [
					'success' => false,
					'message' => 'Library not found',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'id not provided',
			];
		}
	}

	/** @noinspection PhpUnused */
	public function getLocationInfo(): array {
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$location = new Location();
			$location->locationId = $_REQUEST['id'];
			if ($location->find(true)) {
				return [
					'success' => true,
					'location' => $location->getApiInfo(),
				];
			} else {
				return [
					'success' => false,
					'message' => 'Location not found',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'id not provided',
			];
		}
	}

	/** @noinspection PhpUnused */
	public function getLocations(): array {
		global $library;
		$return = [
			'success' => true,
			'locations' => [],
		];
		$location = new Location();
		if($library) {
			$location->libraryId = $library->libraryId;
		}
		$location->showInLocationsAndHoursList = 1;
		$location->orderBy('isMainBranch DESC, displayName');
		$location->find();
		while ($location->fetch()) {
			$return['locations'][$location->locationId] = $location->getApiInfo();
		}

		$userLatitude = $_GET['latitude'] ?? null;
		$userLongitude = $_GET['longitude'] ?? null;
		require_once ROOT_DIR . '/services/API/GreenhouseAPI.php';
		$greenhouseApi = new GreenhouseAPI();
		foreach ($return['locations'] as $location) {
			$return['locations'][$location['locationId']]['distance'] = null;
			if($userLongitude && $userLatitude && $location['longitude'] !== 0 && $location['latitude'] !== 0) {
				$return['locations'][$location['locationId']]['distance'] = $greenhouseApi->findDistance($userLongitude, $userLatitude, $location['longitude'], $location['latitude'], $location['unit']);
			}
		}

		return $return;
	}

	/** @noinspection PhpUnused */
	public function getThemeInfo(): array {
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$theme = new Theme();
			$theme->id = $_REQUEST['id'];
			if ($theme->find(true)) {
				return [
					'success' => true,
					'theme' => $theme->getApiInfo(),
				];
			} else {
				return [
					'success' => false,
					'message' => 'Theme not found',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Theme id not provided',
			];
		}
	}

	/** @noinspection PhpUnused */
	public function getAppSettings(): array {
		global $configArray;
		if (isset($_REQUEST['slug'])) {
			require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';
			$app = new BrandedAppSetting();
			$app->slugName = $_REQUEST['slug'];
			if ($app->find(true)) {
				$settings = [];
				if ($app->logoLogin) {
					$settings['logoLogin'] = $configArray['Site']['url'] . '/files/original/' . $app->logoLogin;
				}

				if ($app->logoSplash) {
					$settings['logoSplash'] = $configArray['Site']['url'] . '/files/original/' . $app->logoSplash;
				}

				if ($app->privacyPolicy) {
					$settings['privacyPolicy'] = $app->privacyPolicy;
				}

				if ($app->autoPickUserHomeLocation) {
					$settings['autoPickUserHomeLocation'] = $app->autoPickUserHomeLocation;
				}

				//Check to see if we should use custom loading text

				$settings['loadingMessageType'] = $app->loadingMessageType;
				if ($app->loadingMessageType == 2) {
					//Get a loading message at random
					$loadingMessage = new LiDALoadingMessage();
					$loadingMessage->brandedAppSettingId = $app->id;
					$loadingMessage->orderBy('rand()');
					$loadingMessage->limit(0, 1);
					if ($loadingMessage->find(true)) {
						$settings['loadingMessage'] = $loadingMessage->message;
					}
				}

				return [
					'success' => true,
					'settings' => $settings,
				];
			} else {
				return [
					'success' => false,
					'message' => 'App settings for slug name not found',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Slug name for app not provided',
			];
		}
	}

	/** @noinspection PhpUnused */
	public function getNotificationSettings(): array {
		if (isset($_REQUEST['libraryId'])) {
			$library = new Library();
			$library->libraryId = $_REQUEST['libraryId'];
			if ($library->find(true)) {
				require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';
				$notificationSettings = new NotificationSetting();
				$notificationSettings->id = $library->lidaNotificationSettingId;
				if ($notificationSettings->find(true)) {
					$settings['sendTo'] = $notificationSettings->sendTo;
					$settings['notifySavedSearch'] = $notificationSettings->notifySavedSearch;
					$settings['notifyCustom'] = $notificationSettings->notifyCustom;
					$settings['notifyAccount'] = $notificationSettings->notifyAccount;
					return [
						'success' => true,
						'settings' => $settings,
					];
				} else {
					return [
						'success' => false,
						'message' => 'No notification settings found for library',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'No library found with provided id',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Must provide a library id',
			];
		}
	}

	/** @noinspection PhpUnused */
	public function getLocationAppSettings(): array {
		if (isset($_REQUEST['locationId'])) {
			$location = new Location();
			$location->locationId = $_REQUEST['locationId'];
			if ($location->find(true)) {
				require_once ROOT_DIR . '/sys/AspenLiDA/LocationSetting.php';
				$appSettings = new LocationSetting();
				$appSettings->id = $location->lidaLocationSettingId;
				if ($appSettings->find(true)) {
					$settings['releaseChannel'] = $appSettings->releaseChannel;
					$settings['enableAccess'] = $appSettings->enableAccess;
					return [
						'success' => true,
						'settings' => $settings,
					];
				} else {
					return [
						'success' => false,
						'message' => 'No app settings found for location',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'No location found with provided id',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Must provide a location id',
			];
		}
	}

	/** @noinspection PhpUnused */
	public function getCurrentVersion(): array {
		global $interface;
		$aspenVersion = $interface->getVariable('aspenVersion');
		return [
			'version' => $aspenVersion,
		];
	}

	public function getDatabaseUpdates(): array {
		require_once ROOT_DIR . '/sys/DBMaintenance/library_location_updates.php';
		$library_location_updates = getLibraryLocationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/summon_updates.php';
		$summonUpdates = getSummonUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/cloud_library_updates.php';
		$cloudLibraryUpdates = getCloudLibraryUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/grapes_web_builder_updates.php';
		$grapesWebBuilderUpdates = getGrapesWebBuilderUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/heycentric_updates.php';
		$heycentricUpdates = getHeyCentricUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/community_engagement_updates.php';
		$communityEngagementUpdates = getCommunityEngagementUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/talpa_updates.php';
		$talpaUpdates = getTalpaUpdates();
		
		$baseUpdates = array_merge($library_location_updates, $summonUpdates, $cloudLibraryUpdates, $grapesWebBuilderUpdates, $communityEngagementUpdates, $talpaUpdates, $heycentricUpdates);

		//Get version updates
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$versionUpdates = scandir(ROOT_DIR . '/sys/DBMaintenance/version_updates', SCANDIR_SORT_ASCENDING);
		foreach ($versionUpdates as $updateFile) {
			if (is_file(ROOT_DIR . '/sys/DBMaintenance/version_updates/' . $updateFile)) {
				if (StringUtils::endsWith($updateFile, '.php')) {
					include_once ROOT_DIR . "/sys/DBMaintenance/version_updates/$updateFile";
					$version = substr($updateFile, 0, strrpos($updateFile, '.'));
					$updateFunction = 'getUpdates' . str_replace('.', '_', $version);
					$updates = $updateFunction();
					$baseUpdates = array_merge($baseUpdates, $updates);
				}
			}
		}

		return $baseUpdates;
	}

	public function hasPendingDatabaseUpdates() : bool {
		$availableUpdates = $this->getPendingDatabaseUpdates();
		return count($availableUpdates) > 0;
	}

	public function getPendingDatabaseUpdates() : array {
		$availableUpdates = $this->getDatabaseUpdates();
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);
		$pendingUpdates = [];
		foreach ($availableUpdates as $key => $update) {
			if (!$update['alreadyRun']) {
				$pendingUpdates[$key] = $update;
			}
		}
		return $pendingUpdates;
	}

	public function runDatabaseUpdate(&$availableUpdates, $updateName) : array {
		if ($availableUpdates == null) {
			$availableUpdates = $this->getDatabaseUpdates();
		}
		if (isset($availableUpdates[$updateName])) {
			$updateToRun = $availableUpdates[$updateName];
			$sqlStatements = $updateToRun['sql'];
			$updateOk = true;
			foreach ($sqlStatements as $sql) {
				//Give enough time for long queries to run

				if (method_exists($this, $sql)) {
					$this->$sql($updateToRun);
				} elseif (function_exists($sql)) {
					$sql($updateToRun);
				} else {
					if (!$this->runSQLStatement($updateToRun, $sql)) {
						break;
					}
				}
			}
			if ($updateOk) {
				$this->markUpdateAsRun($updateName);
			}
			$availableUpdates[$updateName] = $updateToRun;
			return [
				'success' => $updateOk,
				'message' => empty($updateToRun['status']) ? 'Status not returned' : $updateToRun['status'],
			];
		} else {
			return [
				'success' => false,
				'message' => 'Could not find update to run',
			];
		}
	}

	private function runSQLStatement(&$update, $sql) : bool {
		global $aspen_db;
		set_time_limit(500);
		$updateOk = true;
		try {
			$aspen_db->query($sql);
			if (!isset($update['status'])) {
				$update['success'] = true;
				$update['status'] = translate([
					'text' => 'Update succeeded',
					'isAdminFacing' => true,
				]);
			}
		} catch (PDOException $e) {
			$update['success'] = false;
			if (isset($update['continueOnError']) && $update['continueOnError']) {
				if (!isset($update['status'])) {
					$update['status'] = '';
				}
				$update['status'] .= '<br/><strong>' . $sql . '</strong><br/>Warning: ' . $e;
			} else {
				$update['status'] = '<br/><strong>' . $sql . '</strong><br/>Update failed: ' . $e;
				$updateOk = false;
			}
		}

		return $updateOk;
	}

	private function markUpdateAsRun($update_key) {
		global $aspen_db;
		$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($update_key));
		if ($result->rowCount() != false) {
			//Update the existing value
			$aspen_db->query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = " . $aspen_db->quote($update_key));
		} else {
			$aspen_db->query("INSERT INTO db_update (update_key) VALUES (" . $aspen_db->quote($update_key) . ")");
		}
	}

	public function runPendingDatabaseUpdates() {
		$pendingUpdates = $this->getPendingDatabaseUpdates();
		$numRun = 0;
		$numFailed = 0;
		$errors = '';
		foreach ($pendingUpdates as $key => $pendingUpdate) {
			$numRun++;
			$this->runDatabaseUpdate($pendingUpdates, $key);
			if (!$pendingUpdates[$key]['success']) {
				$numFailed++;
				$errors .= $pendingUpdates[$key]['title'] . '<br/>' . $pendingUpdates[$key]['status'] . '<br/>';
			}
		}

		// make sure full nightly index is set to run after completing db updates
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->find(true)) {
			if($systemVariables->runNightlyFullIndex == 0) {
				$systemVariables->runNightlyFullIndex = 1;
				$systemVariables->update();
			}
		}

		if ($numFailed == 0) {
			return [
				'success' => true,
				'message' => $numRun . " updates ran successfully",
			];
		} else {
			return [
				'success' => false,
				'message' => $numFailed . " of " . $numRun . " updates ran successfully<br/>" . $errors,
			];
		}
	}

	public function checkWhichUpdatesHaveRun($availableUpdates) {
		global $aspen_db;
		foreach ($availableUpdates as $key => $update) {
			$update['alreadyRun'] = false;
			$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($key));
			if ($result != false && $result->rowCount() > 0) {
				$update['alreadyRun'] = true;
			}
			$availableUpdates[$key] = $update;
		}
		return $availableUpdates;
	}

	function doesKeyFileExist() {
		global $serverName;
		$passkeyFile = ROOT_DIR . "/../../sites/$serverName/conf/passkey";
		if (!file_exists($passkeyFile)) {
			return false;
		} else {
			return true;
		}
	}

	public function displayAdminAlert(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	public function getLogoFile() {
		if (isset($_REQUEST['type'])) {
			global $configArray;
			$type = strip_tags($_REQUEST['type']);

			require_once ROOT_DIR . '/sys/Theming/Theme.php';
			$theme = new Theme();
			if (isset($_REQUEST['themeId'])) {
				$theme->id = $_REQUEST['themeId'];
				if (!$theme->find(true)) {
					die();
				}
			}

			require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';
			$app = new BrandedAppSetting();
			if (isset($_REQUEST['slug'])) {
				$app->slugName = $_REQUEST['slug'];
				if (!$app->find(true)) {
					die();
				}
			}

			$dataPath = $configArray['Site']['local'] . '/files/original/';

			if ($type === "logo") {
				$fileName = $theme->logoName;
			} elseif ($type === "favicon") {
				$fileName = $theme->favicon;
			} elseif ($type === "footerLogo") {
				$fileName = $theme->footerLogo;
			} elseif ($type === "logoApp") {
				$fileName = $theme->logoApp;
			} elseif ($type === "headerLogoApp") {
				$fileName = $theme->headerLogoApp;
			} elseif ($type === "appSplash") {
				$fileName = $app->logoSplash;
			} elseif ($type === "appLogin") {
				$fileName = $app->logoLogin;
			} elseif ($type === "appIcon" || $type === "appIconApple") {
				$fileName = $app->logoAppIcon;
			} elseif ($type === "appIconAndroid") {
				//Fall back to the app icon if an android-specific icon isn't provided
				$fileName = $app->logoAppIconAndroid ?? $app->logoAppIcon;
			} elseif ($type === "appNotification") {
				$fileName = $app->logoNotification;
			} else {
				die();
			}

			$fullPath = $dataPath . $fileName;
			$extension = pathinfo($fileName, PATHINFO_EXTENSION);

			if ($file = @fopen($fullPath, 'r')) {
				set_time_limit(300);
				$chunkSize = 2 * (1024 * 1024);

				$size = intval(sprintf("%u", filesize($fullPath)));

				if ($extension == 'svg') {
					header('Content-Type: image/svg+xml');
				} else {
					header('Content-Type: image/png');
				}
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: ' . $size);

				if ($size > $chunkSize) {
					$handle = fopen($fullPath, 'rb');

					while (!feof($handle)) {
						set_time_limit(300);
						print(@fread($handle, $chunkSize));

						ob_flush();
						flush();
					}

					fclose($handle);
				} else {
					readfile($fullPath);
				}

				die();
			}
		}
	}


	function getTranslation() {
		if (isset($_REQUEST['term'])) {
			$terms[] = $_REQUEST['term'];
		} elseif (isset($_REQUEST['terms'])) {
			if (is_array($_REQUEST['terms'])) {
				$terms = $_REQUEST['terms'];
			} else {
				$terms[] = $_REQUEST['term'];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide at least one term to translate.',
			];
		}

		if (isset($_REQUEST['language'])) {
			$language = new Language();
			$language->code = $_REQUEST['language'];
			if ($language->find(true)) {
				global $activeLanguage;
				$activeLanguage = $language;
			} else {
				return [
					'success' => false,
					'message' => 'Invalid language provided.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide the language to translate the term into.',
			];
		}

		$response = [
			'success' => true,
		];
		/** @var Translator $translator */ global $translator;
		foreach ($terms as $term) {
			$translatedTerm = $translator->translate($term, $term, [], true, true);
			$translatedTerm = html_entity_decode($translatedTerm);
			$translatedTerm = strip_tags($translatedTerm);
			$response[$_REQUEST['language']][$term] = trim($translatedTerm);
		}
		return $response;
	}

	function getTranslationWithValues() {
		if (isset($_REQUEST['term'])) {
			$term = $_REQUEST['term'];
		} else {
			return [
				'success' => false,
				'message' => 'Please provide at least one term to translate.',
			];
		}

		if (isset($_REQUEST['values'])) {
			if (is_array($_REQUEST['values'])) {
				$givenValues = $_REQUEST['values'];
			} else {
				$givenValues[] = $_REQUEST['values'];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide at least one value to translate with the term.',
			];
		}

		if (isset($_REQUEST['language'])) {
			$language = new Language();
			$language->code = $_REQUEST['language'];
			if ($language->find(true)) {
				global $activeLanguage;
				$activeLanguage = $language;
			} else {
				return [
					'success' => false,
					'message' => 'Invalid language code provided.',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide a language code to translate to.',
			];
		}

		if (is_array($givenValues)) {
			$num = 1;
			$values = [];
			foreach ($givenValues as $value) {
				if (is_array($value)) {
					$values[$num] = $value[0];
				} else {
					$values[$num] = $value;
				}
				$num++;
			}
		} else {
			$values = $givenValues;
		}

		/** @var Translator $translator */ global $translator;
		$translatedTerm = $translator->translate($term, $term, $values, true, true);
		$translatedTerm = html_entity_decode($translatedTerm);
		$translatedTerm = strip_tags($translatedTerm);
		return [
			'success' => true,
			'translation' => [
				$term => trim($translatedTerm)
			],
		];
	}

	/** @noinspection PhpUnused */
	function getBulkTranslations(): array {
		global $timer;
		global $logger;
		if (isset($_REQUEST['language'])) {
			$language = new Language();
			$language->code = $_REQUEST['language'];
			if ($language->find(true)) {
				global $activeLanguage;
				$activeLanguage = $language;
			} else {
				return [
					'success' => false,
					'message' => 'Invalid language code provided.',
				];
			}
			if (isset($_REQUEST['terms']) || file_get_contents('php://input')) {
				if (isset($_REQUEST['terms'])) {
					$terms['terms'] = $_REQUEST['terms'];
				}else{
					$data = file_get_contents('php://input');
					$terms = json_decode($data, true);
				}

				$logger->log("Preparing to translate " . count($terms['terms']), Logger::LOG_DEBUG);
				$translatedTerms = [];
				/** @var Translator $translator */ global $translator;
				foreach ($terms['terms'] as $key => $term) {
					$translatedTerm = $translator->translate($term, $term, [], true, true);
					$translatedTerm = html_entity_decode($translatedTerm);
					$translatedTerm = strip_tags($translatedTerm);
					$translatedTerms[$key] = trim($translatedTerm);
				}
				$timer->logTime("Finished translating terms");
				return [
					'success' => true,
					$_REQUEST['language'] => $translatedTerms,
				];
			} else {
				return [
					'success' => false,
					'message' => 'Please provide terms as JSON data',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Please provide a language code to translate to.',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getLanguages() : array {
		$validLanguages = [];
		require_once ROOT_DIR . '/sys/Translation/Language.php';
		$validLanguage = new Language();
		$validLanguage->orderBy(["weight", "displayName"]);
		$validLanguage->find();
		while ($validLanguage->fetch()) {
			if (!$validLanguage->displayToTranslatorsOnly) {
				$validLanguages[$validLanguage->code]['weight'] = (int)$validLanguage->weight;
				$validLanguages[$validLanguage->code]['id'] = (int)$validLanguage->id;
				$validLanguages[$validLanguage->code]['code'] = $validLanguage->code;
				$validLanguages[$validLanguage->code]['displayName'] = $validLanguage->displayName;
				$validLanguages[$validLanguage->code]['displayNameEnglish'] = $validLanguage->displayNameEnglish;
			}
		}

		return [
			'success' => true,
			'languages' => $validLanguages,
		];
	}

	function getDevelopmentPriorities(): array {
		require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
		$supportConnections = new RequestTrackerConnection();
		$activeTickets = [];
		$numActiveTickets = 0;
		$priorities = [
			'priority1' => [
				'id' => '-1',
				'title' => 'none',
				'link' => '',
			],
			'priority2' => [
				'id' => '-1',
				'title' => 'none',
				'link' => '',
			],
			'priority3' => [
				'id' => '-1',
				'title' => 'none',
				'link' => '',
			],
		];
		if ($supportConnections->find(true)) {
			$activeTickets = $supportConnections->getActiveTickets();
			$numActiveTickets = count($activeTickets);

			require_once ROOT_DIR . '/sys/Support/DevelopmentPriorities.php';
			$developmentPriorities = new DevelopmentPriorities();
			if ($developmentPriorities->find(true)) {
				$priorities['priority1'] = ($developmentPriorities->priority1 == -1 || !array_key_exists($developmentPriorities->priority1, $activeTickets)) ? [
					'id' => '-1',
					'title' => 'none',
					'link' => '',
				] : $activeTickets[$developmentPriorities->priority1];
				$priorities['priority2'] = ($developmentPriorities->priority2 == -1 || !array_key_exists($developmentPriorities->priority2, $activeTickets)) ? [
					'id' => '-1',
					'title' => 'none',
					'link' => '',
				] : $activeTickets[$developmentPriorities->priority2];
				$priorities['priority3'] = ($developmentPriorities->priority1 == -1 || !array_key_exists($developmentPriorities->priority3, $activeTickets)) ? [
					'id' => '-1',
					'title' => 'none',
					'link' => '',
				] : $activeTickets[$developmentPriorities->priority3];
			}
		}

		return [
			'success' => true,
			'priorities' => $priorities,
			'numActiveTickets' => $numActiveTickets,
		];
	}

	/** @noinspection PhpUnused */
	function getVdxForm() : array {
		$result = [
			'success' => false,
			'title' => 'Error',
			'message' => 'Unable to load VDX form',
		];

		require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
		require_once ROOT_DIR . '/sys/VDX/VdxForm.php';

		if (isset($_REQUEST['formId'])) {
			$formId = $_REQUEST['formId'];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Invalid Configuration',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'A VDX form id was not given.',
					'isPublicFacing' => true,
				]),
			];
		}

		$vdxSettings = new VdxSetting();
		if ($vdxSettings->find(true)) {
			$vdxForm = new VdxForm();
			$vdxForm->id = $formId;
			if ($vdxForm->find(true)) {
				$vdxFormFields = $vdxForm->getFormFieldsForApi();
				$result = [
					'success' => true,
					'title' => translate([
						'text' => 'Request Title',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'If you cannot find a title in our catalog, you can request the title via this form. Please enter as much information as possible so we can find the exact title you are looking for. For example, if you are looking for a specific season of a TV show, please include that information.',
						'isPublicFacing' => true,
					]),
					'buttonLabel' => translate([
						'text' => 'Place Request',
						'isPublicFacing' => true,
					]),
					'buttonLabelProcessing' => translate([
						'text' => 'Placing Request',
						'isPublicFacing' => true,
					]),
					'fields' => $vdxFormFields,
				];
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Invalid Configuration',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => 'Unable to find the specified form.',
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Invalid Configuration',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'VDX Settings do not exist, please contact the library to make a request.',
					'isPublicFacing' => true,
				]),
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getLocalIllForm(): array {
		$result = [
			'success' => false,
			'title' => 'Error',
			'message' => 'Unable to load VDX form',
		];

		require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';

		if (isset($_REQUEST['formId'])) {
			$formId = $_REQUEST['formId'];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Invalid Configuration',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'A LocalIll form id was not given.',
					'isPublicFacing' => true,
				]),
			];
		}

		$localIllForm = new LocalIllForm();
		$localIllForm->id = $formId;
		if ($localIllForm->find(true)) {
			$localIllFormFields = $localIllForm->getFormFieldsForApi();
			$result = [
				'success' => true,
				'title' => translate([
					'text' => 'Request Title',
					'isPublicFacing' => true,
				]),
				'message' => '',
				'buttonLabel' => translate([
					'text' => 'Place Request',
					'isPublicFacing' => true,
				]),
				'buttonLabelProcessing' => translate([
					'text' => 'Placing Request',
					'isPublicFacing' => true,
				]),
				'fields' => $localIllFormFields,
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Invalid Configuration',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => 'Unable to find the specified form.',
					'isPublicFacing' => true,
				]),
			];
		}

		return $result;
	}

	public function getSelfCheckSettings(): array {
		if (isset($_REQUEST['locationId'])) {
			$location = new Location();
			$location->locationId = $_REQUEST['locationId'];
			if ($location->find(true)) {
				require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
				$scoSettings = new AspenLiDASelfCheckSetting();
				$scoSettings->id = $location->lidaSelfCheckSettingId;
				if ($scoSettings->find(true)) {
					$validBarcodeStyles = [];
					require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckBarcode.php';
					$barcodeStyle = new AspenLiDASelfCheckBarcode();
					$barcodeStyle->selfCheckSettingsId = $scoSettings->id;
					if($barcodeStyle->find()) {
						while($barcodeStyle->fetch()) {
							$validBarcodeStyles[] = $barcodeStyle->barcodeStyle;
						}
					} else {
						// load defaults
						$validBarcodeStyles = ['codabar', 'upc_a', 'upc_e', 'upc_ean', 'ean13', 'ean8'];
					}
					return [
						'success' => true,
						'settings' => [
							'isEnabled' => $scoSettings->isEnabled,
							'barcodeStyles' => $validBarcodeStyles,
							'barcodeEntryKeyboardType' => $scoSettings->barcodeEntryKeyboardType,
						],
					];
				} else {
					return [
						'success' => false,
						'message' => 'No self-check settings found for location',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'No location found with provided id',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Must provide a location id',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getSystemMessages(): array {
		$user = UserAccount::validateAccount($_POST['username'], $_POST['password']);
		if ($user && !($user instanceof AspenError)) {
			$locationId = $_REQUEST['locationId'] ?? null;
			$libraryId = $_REQUEST['libraryId'] ?? null;

			require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
			$systemMessages = [];
			$message = new SystemMessage();
			$message->find();
			while ($message->fetch()) {
				if ($message->isValidForDisplayInApp($user, $locationId, $libraryId)) {
					$systemMessages[] = [
						'id' => (int)$message->id,
						'style' => $message->messageStyle,
						'dismissable' => (int)$message->dismissable,
						'message' => $message->appMessage,
						'showOn' => (string)$message->showOn,
					];
				}
			}
			return [
				'success' => true,
				'systemMessages' => $systemMessages,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/** @noinspection PhpUnused */
	function dismissSystemMessage(): array {
		$user = UserAccount::validateAccount($_POST['username'], $_POST['password']);
		if ($user && !($user instanceof AspenError)) {
			if($_REQUEST['systemMessageId']) {
				$id = $_REQUEST['systemMessageId'];
				require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
				$message = new SystemMessage();
				$message->id = $id;
				if ($message->find(true)) {
					require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
					$systemMessageDismissal = new SystemMessageDismissal();
					$systemMessageDismissal->userId = $user->id;
					$systemMessageDismissal->systemMessageId = $message->id;
					if($systemMessageDismissal->find(true)) {
						return [
							'success' => true,
							'message' => 'Message was already dismissed',
						];
					} else {
						$systemMessageDismissal->insert();
						return [
							'success' => true,
							'message' => 'Message was dismissed',
						];
					}
				} else {
					return [
						'success' => false,
						'message' => 'Could not find the message to dismiss',
					];
				}
			} else {
				return [
					'success' => false,
					'message' => 'Message ID not provided',
				];
			}
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	/**
	 * Returns information about menu links for a library so that they can be displayed within Aspen LiDA and other
	 * third party apps.
	 *
	 * @noinspection PhpUnused
	 */
	function getLibraryLinks() : array {
		$user = $this->getUserForApiCall();
		if ($user && !($user instanceof AspenError)) {
			global $library;
			global $configArray;
			$links = $library->libraryLinks;
			$libraryLinks = [];
			/** @var LibraryLink $libraryLink */
			foreach ($links as $libraryLink) {
				if(!$libraryLink->isValidForDisplayForApp($user)) {
					continue;
				}

				if (empty($libraryLink->category)) {
					$libraryLink->category = 'none-' . $libraryLink->id;
				}
				if (!array_key_exists($libraryLink->category, $libraryLinks)) {
					$libraryLinks[$libraryLink->category] = [];
				}

				$url = $libraryLink->url;
				if(!str_starts_with($url, 'http')) {
					$libraryLink->url = $configArray['Site']['url'] . $url;
				}

				$libraryLinks[$libraryLink->category][$libraryLink->linkText] = $libraryLink;

			}

			return [
				'success' => true,
				'items' => $libraryLinks,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Login unsuccessful',
			];
		}
	}

	function getMaterialsRequestForm() {
		if(!isset($_REQUEST['libraryId'])) {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => 'Unable to locate materials request form. A library id was not given',
			];
		}

		$materialsRequest = MaterialsRequest::getRequestFormFieldsForApi($_REQUEST['libraryId']);

		return 	[
			'success' => true,
			'title' => 'Success',
			'message' => 'Loaded materials request form',
			'form' => $materialsRequest,
		];
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
