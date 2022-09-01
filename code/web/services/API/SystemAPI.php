<?php
require_once ROOT_DIR . '/Action.php';

class SystemAPI extends Action
{
	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		//Set Headers
		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if($method === "getLogoFile") {
			return $this->$method();
		};

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if($this->grantTokenAccess()) {
				if (in_array($method, array('getLibraryInfo', 'getLocationInfo', 'getThemeInfo', 'getAppSettings', 'getLocationAppSettings', 'getTranslation', 'getLanguages'))) {
					$result = [
						'result' => $this->$method()
					];
					$output = json_encode($result);
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('SystemAPI', $method);
				} else {
					$output = json_encode(array('error' => 'invalid_method'));
				}
			} else {
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(array('error' => 'unauthorized_access'));
			}
			ExternalRequestLogEntry::logRequest('SystemAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if (!in_array($method, ['getCatalogConnection', 'getUserForApiCall', 'checkWhichUpdatesHaveRun', 'getPendingDatabaseUpdates', 'runSQLStatement', 'markUpdateAsRun'])
				&& method_exists($this, $method)) {
				$result = [
					'result' => $this->$method()
				];
				$output = json_encode($result);
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('SystemAPI', $method);
			} else {
				$output = json_encode(array('error' => 'invalid_method'));
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}

	}

	public function getLibraries() : array
	{
		$return = [
			'success' => true,
			'libraries' => []
		];
		$library = new Library();
		$library->orderBy('isDefault desc');
		$library->orderBy('displayName');
		$library->find();
		while ($library->fetch()){
			$return['libraries'][$library->libraryId] = $library->getApiInfo();
		}
		return $return;
	}

	/** @noinspection PhpUnused */
	public function getLibraryInfo() : array
	{
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$library = new Library();
			$library->libraryId = $_REQUEST['id'];
			if ($library->find(true)){
				return ['success' => true, 'library' => $library->getApiInfo()];
			}else{
				return ['success' => false, 'message' => 'Library not found'];
			}
		}else{
			return ['success' => false, 'message' => 'id not provided'];
		}
	}

	/** @noinspection PhpUnused */
	public function getLocationInfo() : array
	{
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$location = new Location();
			$location->locationId = $_REQUEST['id'];
			if ($location->find(true)){
				return ['success' => true, 'location' => $location->getApiInfo()];
			}else{
				return ['success' => false, 'message' => 'Location not found'];
			}
		}else{
			return ['success' => false, 'message' => 'id not provided'];
		}
	}

	/** @noinspection PhpUnused */
	public function getThemeInfo() : array
	{
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$theme = new Theme();
			$theme->id = $_REQUEST['id'];
			if ($theme->find(true)){
				return ['success' => true, 'theme' => $theme->getApiInfo()];
			}else{
				return ['success' => false, 'message' => 'Theme not found'];
			}
		}else{
			return ['success' => false, 'message' => 'Theme id not provided'];
		}
	}

	/** @noinspection PhpUnused */
	public function getAppSettings() : array
	{
		global $configArray;
		if (isset($_REQUEST['slug'])) {
			require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';
			$app = new BrandedAppSetting();
			$app->slugName = $_REQUEST['slug'];
			if ($app->find(true)){
				$settings = [];
				if($app->logoLogin) {
					$settings['logoLogin'] = $configArray['Site']['url'] . '/files/original/' . $app->logoLogin;
				}

				if($app->logoSplash) {
					$settings['logoSplash'] = $configArray['Site']['url'] . '/files/original/' . $app->logoSplash;
				}

				if($app->privacyPolicy) {
					$settings['privacyPolicy'] = $app->privacyPolicy;
				}

				return [
					'success' => true,
					'settings' => $settings,
				];
			}else{
				return ['success' => false, 'message' => 'App settings for slug name not found'];
			}
		}else{
			return ['success' => false, 'message' => 'Slug name for app not provided'];
		}
	}

	/** @noinspection PhpUnused */
	public function getNotificationSettings() : array
	{
		if (isset($_REQUEST['libraryId'])) {
			$library = new Library();
			$library->libraryId = $_REQUEST['libraryId'];
			if($library->find(true)) {
				require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';
				$notificationSettings = new NotificationSetting();
				$notificationSettings->id = $library->lidaNotificationSettingId;
				if($notificationSettings->find(true)) {
					$settings['sendTo'] = $notificationSettings->sendTo;
					$settings['notifySavedSearch'] = $notificationSettings->notifySavedSearch;
					return ['success' => true, 'settings' => $settings];
				} else {
					return ['success' => false, 'message' => 'No notification settings found for library'];
				}
			} else {
				return ['success' => false, 'message' => 'No library found with provided id'];
			}
		} else{
			return ['success' => false, 'message' => 'Must provide a library id'];
		}
	}

	/** @noinspection PhpUnused */
	public function getLocationAppSettings() : array
	{
		if (isset($_REQUEST['locationId'])) {
			$location = new Location();
			$location->locationId = $_REQUEST['locationId'];
			if($location->find(true)) {
				require_once ROOT_DIR . '/sys/AspenLiDA/AppSetting.php';
				$appSettings = new AppSetting();
				$appSettings->id = $location->lidaGeneralSettingId;
				if($appSettings->find(true)) {
					$settings['releaseChannel'] = $appSettings->releaseChannel;
					$settings['enableAccess'] = $appSettings->enableAccess;
					return ['success' => true, 'settings' => $settings];
				} else {
					return ['success' => false, 'message' => 'No app settings found for location'];
				}
			} else {
				return ['success' => false, 'message' => 'No location found with provided id'];
			}
		} else{
			return ['success' => false, 'message' => 'Must provide a location id'];
		}
	}

	/** @noinspection PhpUnused */
	public function getCurrentVersion() : array {
		global $interface;
		$gitBranch = $interface->getVariable('gitBranchWithCommit');
		return [
			'version' => $gitBranch
		];
	}

	public function getDatabaseUpdates() : array {
		require_once ROOT_DIR . '/sys/DBMaintenance/base_updates.php';
		$initialUpdates = getInitialUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/library_location_updates.php';
		$library_location_updates = getLibraryLocationUpdates();
		$postLibraryBaseUpdates = getPostLibraryBaseUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/grouped_work_updates.php';
		$grouped_work_updates = getGroupedWorkUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/user_updates.php';
		$user_updates = getUserUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/genealogy_updates.php';
		$genealogy_updates = getGenealogyUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/browse_updates.php';
		$browse_updates = getBrowseUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/collection_spotlight_updates.php';
		$collection_spotlight_updates = getCollectionSpotlightUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/indexing_updates.php';
		$indexing_updates = getIndexingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/hoopla_updates.php';
		$hoopla_updates = getHooplaUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/rbdigital_updates.php';
		$rbdigital_updates = getRBdigitalUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/sierra_api_updates.php';
		$sierra_api_updates = getSierraAPIUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/overdrive_updates.php';
		$overdrive_updates = getOverDriveUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/ebsco_updates.php';
		$ebscoUpdates = getEbscoUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/axis360_updates.php';
		$axis360Updates = getAxis360Updates();
		require_once ROOT_DIR . '/sys/DBMaintenance/theming_updates.php';
		$theming_updates = getThemingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/translation_updates.php';
		$translation_updates = getTranslationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/open_archives_updates.php';
		$open_archives_updates = getOpenArchivesUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/redwood_archive_updates.php';
		$redwood_updates = getRedwoodArchiveUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/cloud_library_updates.php';
		$cloudLibraryUpdates = getCloudLibraryUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/website_indexing_updates.php';
		$websiteIndexingUpdates = getWebsiteIndexingUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/web_builder_updates.php';
		$webBuilderUpdates = getWebBuilderUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/events_integration_updates.php';
		$eventsIntegrationUpdates = getEventsIntegrationUpdates();
		require_once ROOT_DIR . '/sys/DBMaintenance/file_upload_updates.php';
		$fileUploadUpdates = getFileUploadUpdates();
		$finalBaseUpdates = getFinalBaseUpdates();

		$baseUpdates = array_merge(
			$initialUpdates,
			$library_location_updates,
			$postLibraryBaseUpdates,
			$user_updates,
			$grouped_work_updates,
			$genealogy_updates,
			$browse_updates,
			$collection_spotlight_updates,
			$indexing_updates,
			$overdrive_updates,
			$ebscoUpdates,
			$axis360Updates,
			$hoopla_updates,
			$rbdigital_updates,
			$sierra_api_updates,
			$theming_updates,
			$translation_updates,
			$open_archives_updates,
			$redwood_updates,
			$cloudLibraryUpdates,
			$websiteIndexingUpdates,
			$webBuilderUpdates,
			$eventsIntegrationUpdates,
			$fileUploadUpdates,
			$finalBaseUpdates
		);

		//Get version updates
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$versionUpdates = scandir(ROOT_DIR . '/sys/DBMaintenance/version_updates', SCANDIR_SORT_ASCENDING );
		foreach ($versionUpdates as $updateFile){
			if (is_file(ROOT_DIR . '/sys/DBMaintenance/version_updates/' . $updateFile)){
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

	public function hasPendingDatabaseUpdates(){
		$availableUpdates = $this->getPendingDatabaseUpdates();
		return count($availableUpdates) > 0;
	}

	public function getPendingDatabaseUpdates(){
		$availableUpdates = $this->getDatabaseUpdates();
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);
		$pendingUpdates = [];
		foreach ($availableUpdates as $key => $update){
			if (!$update['alreadyRun']){
				$pendingUpdates[$key] = $update;
			}
		}
		return $pendingUpdates;
	}

	public function runDatabaseUpdate(&$availableUpdates, $updateName){
		if ($availableUpdates == null){
			$availableUpdates = $this->getDatabaseUpdates();
		}
		if (isset($availableUpdates[$updateName])){
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
				'message' => $updateToRun['status']
			];
		}else{
			return [
				'success' => false,
				'message' => 'Could not find update to run'
			];
		}
	}

	private function runSQLStatement(&$update, $sql)
	{
		global $aspen_db;
		set_time_limit(500);
		$updateOk = true;
		try {
			$aspen_db->query($sql);
			if (!isset($update['status'])) {
				$update['success'] = true;
				$update['status'] = translate(['text' => 'Update succeeded', 'isAdminFacing'=>true]);
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

	private function markUpdateAsRun($update_key)
	{
		global $aspen_db;
		$result = $aspen_db->query("SELECT * from db_update where update_key = " . $aspen_db->quote($update_key));
		if ($result->rowCount() != false) {
			//Update the existing value
			$aspen_db->query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = " . $aspen_db->quote($update_key));
		} else {
			$aspen_db->query("INSERT INTO db_update (update_key) VALUES (" . $aspen_db->quote($update_key) . ")");
		}
	}

	public function runPendingDatabaseUpdates(){
		$pendingUpdates = $this->getPendingDatabaseUpdates();
		$numRun = 0;
		$numFailed = 0;
		$errors = '';
		foreach ($pendingUpdates as $key => $pendingUpdate){
			$numRun++;
			$this->runDatabaseUpdate($pendingUpdates, $key);
			if (!$pendingUpdates[$key]['success']){
				$numFailed++;
				$errors .= $pendingUpdates[$key]['title'] . '<br/>' . $pendingUpdates[$key]['status'] . '<br/>';
			}
		}
		if ($numFailed == 0){
			return [
				'success' => true,
				'message' => $numRun . " updates ran successfully"
			];
		}else{
			return [
				'success' => false,
				'message' => $numFailed . " of " . $numRun . " updates ran successfully<br/>" . $errors
			];
		}
	}

	public function checkWhichUpdatesHaveRun($availableUpdates)
	{
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

	/** @noinspection PhpUnused */
	function createKeyFile(){
		global $serverName;
		$passkeyFile = ROOT_DIR . "/../../sites/$serverName/conf/passkey";
		if (!file_exists($passkeyFile)) {
			// Return the file path (note that all ini files are in the conf/ directory)
			$methods = [
				'aes-256-gcm',
				'aes-128-gcm'
			];
			foreach ($methods as $cipher) {
				if (in_array($cipher, openssl_get_cipher_methods())) {
					//Generate a 32 character password which will encode to 64 characters in hex notation
					$key = bin2hex(openssl_random_pseudo_bytes(32));
					break;
				}
			}
			$passkeyFhnd = fopen($passkeyFile, 'w');
			fwrite($passkeyFhnd, $cipher . ':' . $key);
			fclose($passkeyFhnd);

			//Make sure the file is not readable by anyone except the aspen user
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
				$runningOnWindows = true;
			}else{
				$runningOnWindows = false;
			}
			if (!$runningOnWindows){
				exec('chown aspen:aspen_apache ' . $passkeyFile);
				exec('chmod 440 ' . $passkeyFile);
			}
		}
	}

	function doesKeyFileExist() {
		global $serverName;
		$passkeyFile = ROOT_DIR . "/../../sites/$serverName/conf/passkey";
		if (!file_exists($passkeyFile)) {
			return false;
		}else{
			return true;
		}
	}

	public function displayAdminAlert() : bool {
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}

	public function getLogoFile()
	{
		if (isset($_REQUEST['type'])) {
			global $configArray;
			$type = strip_tags($_REQUEST['type']);

			require_once ROOT_DIR . '/sys/Theming/Theme.php';
			$theme = new Theme();
			if(isset($_REQUEST['themeId'])) {
				$theme->id = $_REQUEST['themeId'];
				if (!$theme->find(true)) {
					die();
				}
			}

			require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';
			$app = new BrandedAppSetting();
			if(isset($_REQUEST['slug'])) {
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
			} elseif ($type === "appSplash") {
				$fileName = $app->logoSplash;
			} elseif ($type === "appLogin") {
				$fileName = $app->logoLogin;
			} elseif ($type === "appIcon") {
				$fileName = $app->logoAppIcon;
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

				if($extension == 'svg'){
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


	function getTranslation(){
		if (isset($_REQUEST['term'])){
			$terms[] = $_REQUEST['term'];
		}elseif (isset($_REQUEST['terms'])){
			if (is_array($_REQUEST['terms'])) {
				$terms = $_REQUEST['terms'];
			}else{
				$terms[] = $_REQUEST['term'];
			}
		}else{
			return [
				'success' => false,
				'message' => 'Please provide at least one term to translate.'
			];
		}

		if (isset($_REQUEST['language'])){
			$language = new Language();
			$language->code = $_REQUEST['language'];
			if ($language->find(true)) {
				global $activeLanguage;
				$activeLanguage = $language;
			}else{
				return [
					'success' => false,
					'message' => 'Invalid language provided.'
				];
			}
		}else{
			return [
				'success' => false,
				'message' => 'Please provide the term to translate into.'
			];
		}

		$response = [
			'success' => true,
		];
		/** @var Translator $translator */
		global $translator;
		foreach ($terms as $term){
			$response['translations'][$term] = $translator->translate($term, $term, [], true, true);
		}
		return $response;
	}

	function getLanguages() {
		$validLanguages = [];
		require_once ROOT_DIR . '/sys/Translation/Language.php';
		$validLanguage = new Language();
		$validLanguage->orderBy("weight");
		$validLanguage->find();
		while($validLanguage->fetch()) {
			if (!$validLanguage->displayToTranslatorsOnly) {
				$validLanguages[$validLanguage->code]['id'] = $validLanguage->id;
				$validLanguages[$validLanguage->code]['code'] = $validLanguage->code;
				$validLanguages[$validLanguage->code]['displayName'] = $validLanguage->displayName;
				$validLanguages[$validLanguage->code]['displayNameEnglish'] = $validLanguage->displayNameEnglish;
			}
		}

		return array (
			'success' => true,
			'languages' => $validLanguages,
		);
	}

	function getDevelopmentPriorities() : array {
		require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
		$supportConnections = new RequestTrackerConnection();
		$activeTickets = [];
		$numActiveTickets = 0;
		$priorities = [
			'priority1' => ['id' => '-1', 'title' => 'none', 'link'=>''],
			'priority2' => ['id' => '-1', 'title' => 'none', 'link'=>''],
			'priority3' => ['id' => '-1', 'title' => 'none', 'link'=>''],
		];
		if ($supportConnections->find(true)) {
			$activeTickets = $supportConnections->getActiveTickets();
			$numActiveTickets = count($activeTickets);

			require_once ROOT_DIR . '/sys/Support/DevelopmentPriorities.php';
			$developmentPriorities = new DevelopmentPriorities();
			if ($developmentPriorities->find(true)){
				$priorities['priority1'] = ($developmentPriorities->priority1 == -1 || !array_key_exists($developmentPriorities->priority1, $activeTickets)) ? ['id' => '-1', 'title' => 'none', 'link'=>''] : $activeTickets[$developmentPriorities->priority1];
				$priorities['priority2'] = ($developmentPriorities->priority2 == -1 || !array_key_exists($developmentPriorities->priority2, $activeTickets)) ? ['id' => '-1', 'title' => 'none', 'link'=>''] : $activeTickets[$developmentPriorities->priority2];
				$priorities['priority3'] = ($developmentPriorities->priority1 == -1 || !array_key_exists($developmentPriorities->priority3, $activeTickets)) ? ['id' => '-1', 'title' => 'none', 'link'=>''] : $activeTickets[$developmentPriorities->priority3];
			}
		}

		return array(
			'success' => true,
			'priorities' => $priorities,
			'numActiveTickets' => $numActiveTickets,
		);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}