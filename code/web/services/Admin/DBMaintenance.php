<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 */
class Admin_DBMaintenance extends Admin_Admin
{
	function launch()
	{
		global $interface;

		//Create updates table if one doesn't exist already
		$this->createUpdatesTable();

		$availableUpdates = $this->getSQLUpdates();

		if (isset($_REQUEST['selected']) && !empty($_REQUEST['selected'])) {
			$interface->assign('showStatus', true);

			//Process the updates
			foreach ($availableUpdates as $key => $update) {
				if (isset($_REQUEST["selected"][$key])) {
					$sqlStatements = $update['sql'];
					$updateOk = true;
					foreach ($sqlStatements as $sql) {
						//Give enough time for long queries to run

						if (method_exists($this, $sql)) {
							$this->$sql($update);
						} elseif (function_exists($sql)) {
							$sql($update);
						} else {
							if (!$this->runSQLStatement($update, $sql)) {
								break;
							}
						}
					}
					if ($updateOk) {
						$this->markUpdateAsRun($key);
					}
					$availableUpdates[$key] = $update;
				}
			}
		}

		//Check to see which updates have already been performed.
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);

		$interface->assign('sqlUpdates', $availableUpdates);

		$this->display('dbMaintenance.tpl', 'Database Maintenance');

	}

	private function getSQLUpdates() : array
	{
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

		/** @noinspection SqlResolve */
		/** @noinspection SqlWithoutWhere */
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

	/** @noinspection PhpUnused */
	public function convertTablesToInnoDB(/** @noinspection PhpUnusedParameterInspection */ &$update)
	{
		global $configArray;
		$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$configArray['Database']['database_aspen_dbname']}' AND ENGINE = 'MyISAM'";

		global $aspen_db;
		$results = $aspen_db->query($sql, PDO::FETCH_ASSOC);
		$row = $results->fetchObject();
		while ($row != null) {
			/** @noinspection SqlResolve */
			$sql = "ALTER TABLE `{$row->TABLE_NAME}` ENGINE=INNODB";
			$aspen_db->query($sql);
			$row = $results->fetchObject();
		}
	}


	private function checkWhichUpdatesHaveRun($availableUpdates)
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

	private function createUpdatesTable()
	{
		global $aspen_db;
		//Check to see if the updates table exists
		$result = $aspen_db->query("SHOW TABLES");
		$tableFound = false;
		if ($result->rowCount()) {
			while ($row = $result->fetch(PDO::FETCH_NUM)) {
				if ($row[0] == 'db_update') {
					$tableFound = true;
					break;
				}
			}
		}
		if (!$tableFound) {
			//Create the table to mark which updates have been run.
			$aspen_db->query("CREATE TABLE db_update (" .
				"update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," .
				"date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" .
				") ENGINE = InnoDB");
		}
	}

	function runSQLStatement(&$update, $sql)
	{
		global $aspen_db;
		set_time_limit(500);
		$updateOk = true;
		try {
			$aspen_db->query($sql);
			if (!isset($update['status'])) {
				$update['status'] = translate('Update succeeded');
			}
		} catch (PDOException $e) {
			if (isset($update['continueOnError']) && $update['continueOnError']) {
				if (!isset($update['status'])) {
					$update['status'] = '';
				}
				$update['status'] .= 'Warning: ' . $e . '<br/>' . $sql;
			} else {
				$update['status'] = 'Update failed: ' . $e . '<br/>' . $sql;
				$updateOk = false;
			}
		}

		return $updateOk;
	}

	/** @noinspection PhpUnused */
	function createDefaultIpRanges()
	{
		require_once ROOT_DIR . 'sys/IP/IPAddress.php';
		$subnet = new IPAddress();
		$subnet->find();
		while ($subnet->fetch()) {
			$subnet->update();
		}
	}

	/** @noinspection PhpUnused */
	function updateDueDateFormat()
	{
		global $configArray;
		if (isset($configArray['Reindex']['dueDateFormat'])) {
			$ilsIndexingProfile = new IndexingProfile();
			$ilsIndexingProfile->name = 'ils';
			if ($ilsIndexingProfile->find(true)) {
				$ilsIndexingProfile->dueDateFormat = $configArray['Reindex']['dueDateFormat'];
				$ilsIndexingProfile->update();
			}

			$ilsIndexingProfile = new IndexingProfile();
			$ilsIndexingProfile->name = 'millennium';
			if ($ilsIndexingProfile->find(true)) {
				$ilsIndexingProfile->dueDateFormat = $configArray['Reindex']['dueDateFormat'];
				$ilsIndexingProfile->update();
			}
		}
	}

	/** @noinspection PhpUnused */
	function updateShowSeriesInMainDetails()
	{
		$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
		$groupedWorkDisplaySettings->find();
		while ($groupedWorkDisplaySettings->fetch()) {
			if (!count($groupedWorkDisplaySettings->showInMainDetails) == 0) {
				$groupedWorkDisplaySettings->showInMainDetails[] = 'showSeries';
				$groupedWorkDisplaySettings->update();
			}
		}
	}

	/** @noinspection PhpUnused */
	function populateNovelistSettings()
	{
		global $configArray;
		if (!empty($configArray['Novelist']['profile'])) {
			require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';
			$novelistSetting = new NovelistSetting();
			$novelistSetting->profile = $configArray['Novelist']['profile'];
			$novelistSetting->pwd = $configArray['Novelist']['pwd'];
			$novelistSetting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function populateContentCafeSettings()
	{
		global $configArray;
		if (!empty($configArray['ContentCafe']['id'])) {
			require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
			$setting = new ContentCafeSetting();
			$setting->contentCafeId = $configArray['ContentCafe']['id'];
			$setting->pwd = $configArray['ContentCafe']['pw'];
			$setting->hasSummary = ($configArray['ContentCafe']['showSummary'] == true);
			$setting->hasToc = ($configArray['ContentCafe']['showToc'] == true);
			$setting->hasExcerpt = ($configArray['ContentCafe']['showExcerpt'] == true);
			$setting->hasAuthorNotes = ($configArray['ContentCafe']['showAuthorNotes'] == true);
			$setting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function populateSyndeticsSettings()
	{
		global $configArray;
		if (!empty($configArray['Syndetics']['key'])) {
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			$setting = new SyndeticsSetting();
			$setting->syndeticsKey = $configArray['Syndetics']['key'];
			$setting->hasSummary = ($configArray['Syndetics']['showSummary'] == true);
			$setting->hasAvSummary = ($configArray['Syndetics']['showAvSummary'] == true);
			$setting->hasAvProfile = ($configArray['Syndetics']['showAvProfile'] == true);
			$setting->hasToc = ($configArray['Syndetics']['showToc'] == true);
			$setting->hasExcerpt = ($configArray['Syndetics']['showExcerpt'] == true);
			$setting->hasFictionProfile = ($configArray['Syndetics']['showFictionProfile'] == true);
			$setting->hasAuthorNotes = ($configArray['Syndetics']['showAuthorNotes'] == true);
			$setting->hasVideoClip = ($configArray['Syndetics']['showVideoClip'] == true);
			$setting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function populateRecaptchaSettings()
	{
		global $configArray;
		if (!empty($configArray['ReCaptcha']['publicKey'])) {
			require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
			$recaptchaSetting = new RecaptchaSetting();
			$recaptchaSetting->publicKey = $configArray['ReCaptcha']['publicKey'];
			$recaptchaSetting->privateKey = $configArray['ReCaptcha']['privateKey'];
			$recaptchaSetting->insert();
		}
	}

	/** @noinspection PhpUnused */
	function updateSearchableLists(){
		//Get a list of users who have permission to create searchable lists
		require_once ROOT_DIR . '/sys/Administration/Permission.php';
		require_once ROOT_DIR . '/sys/Administration/RolePermissions.php';
		require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/Account/PType.php';
		$permission = new Permission();
		$permission->name = 'Include Lists In Search Results';
		$permission->find(true);

		$permissionRoles = new RolePermissions();
		$permissionRoles->permissionId = $permission->id;
		$permissionRoles->find();
		while ($permissionRoles->fetch()){
			$userRole = new UserRoles();
			$userRole->roleId = $permissionRoles->roleId;
			$userRole->find();
			while($userRole->fetch()){
				$this->makeListsSearchableForUser($userRole->userId);
			}
		}

		//Also update based on ptype
		$pType = new PType();
		$pType->whereAdd('assignedRoleId > -1');
		$pType->find();
		while ($pType->fetch()){
			$user = new User();
			$user->patronType = $pType;
			$user->find();
			while ($user->fetch()){
				$this->makeListsSearchableForUser($user->id);
			}
		}

		//finally update nyt user
		$user = new User();
		$user->cat_username = 'nyt_user';
		if ($user->find(true)){
			$this->makeListsSearchableForUser($user->id);
		}
	}

	/**
	 * @param int $userId
	 */
	protected function makeListsSearchableForUser($userId)
	{
		$userList = new UserList();
		$userList->user_id = $userId;
		$userList->find();
		$allLists = [];
		while ($userList->fetch()) {
			$allLists[] = clone $userList;
		}
		foreach ($allLists as $list){
			if ($list->searchable == 0) {
				$list->searchable = 1;
				$list->update();
			}
		}
	}

	/** @noinspection PhpUnused */
	function createDefaultListIndexingSettings(){
		require_once ROOT_DIR . '/sys/UserLists/ListIndexingSettings.php';
		$listIndexingSettings = new ListIndexingSettings();
		$listIndexingSettings->find();
		if (!$listIndexingSettings->fetch()){
			$listIndexingSettings = new ListIndexingSettings();
			$variable = new Variable();
			$variable->name = 'last_user_list_index_time';
			if ($variable->find(true)){
				$listIndexingSettings->lastUpdateOfChangedLists = $variable->value;
				$variable->delete();
			}
			$listIndexingSettings->insert();
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Database Maintenance');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'system_admin';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Run Database Maintenance');
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
}