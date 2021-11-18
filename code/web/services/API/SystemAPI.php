<?php
require_once ROOT_DIR . '/Action.php';

class SystemAPI extends Action
{
	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		//Make sure the user can access the API based on the IP address
		if (!in_array($method, array('getLibraryInfo', 'getLocationInfo')) && !IPAddress::allowAPIAccessForClientIP()){
			$this->forbidAPIAccess();
		}

		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
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

	function getBreadcrumbs() : array
	{
		return [];
	}
}