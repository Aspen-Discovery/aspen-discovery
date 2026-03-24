<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/API/SystemAPI.php';
require_once ROOT_DIR . '/sys/DBMaintenance/hoopla_version2_updates.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 */
class Admin_DBMaintenance extends Admin_Admin {
	function launch() : void {
		global $interface;

		$systemAPI = new SystemAPI();

		if (isset($_REQUEST['objectAction']) && $_REQUEST['objectAction'] == 'runHooplaVersion2Background') {
			require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
			SystemUtils::startBackgroundProcess('runHooplaVersion2Updates', []);
			header('Location: /Admin/DBMaintenance');
			exit();
		}

		$hooplaVersion2UpdateKeys = [];
		$hooplaVersion2Updates = getHooplaVersion2Updates();
		if (!empty($hooplaVersion2Updates)) {
			$hooplaVersion2UpdateKeys = array_keys($hooplaVersion2Updates);
		}
		//Create the Updates table if one doesn't exist already
		$this->createUpdatesTable();

		$availableUpdates = $systemAPI->getDatabaseUpdates();

		if (!empty($_REQUEST['selected'])) {
			$interface->assign('showStatus', true);

			//Process the updates
			foreach ($availableUpdates as $key => $update) {
				if (isset($_REQUEST["selected"][$key])) {
					$systemAPI->runDatabaseUpdate($availableUpdates, $key);
				}
			}
		}
		if (isset($_REQUEST['submitting'])) {
			//Also force a nightly index
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			SystemVariables::forceNightlyIndex('DB Maintenance');

			Theme::updateCssForAllThemes();

			//Clear cached values after successful maintenance
			require_once ROOT_DIR . '/sys/MemoryCache/CachedValue.php';
			CachedValue::clearAllCachedValues();
		}

		//Check to see which updates have already been performed.
		$availableUpdates = $systemAPI->checkWhichUpdatesHaveRun($availableUpdates);

		// Check for Hoopla V2 Updates
		$showHooplaVersion2BackgroundButton = false;
		foreach ($hooplaVersion2UpdateKeys as $hooplaUpdateKey) {
			if (isset($availableUpdates[$hooplaUpdateKey])) {
				$availableUpdates[$hooplaUpdateKey]['hooplaVersion2'] = true;
				if (!$showHooplaVersion2BackgroundButton && empty($availableUpdates[$hooplaUpdateKey]['alreadyRun'])) {
					$showHooplaVersion2BackgroundButton = true;
				}
			}
		}
		$interface->assign('showHooplaVersion2BackgroundButton', $showHooplaVersion2BackgroundButton);
		$interface->assign('hooplaVersion2BackgroundAction', '/Admin/DBMaintenance?objectAction=runHooplaVersion2Background');

		$interface->assign('sqlUpdates', $availableUpdates);

		$this->display('dbMaintenance.tpl', 'Database Maintenance');

	}

	private function createUpdatesTable() : void {
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
			$aspen_db->query("CREATE TABLE db_update (" . "update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," . "date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" . ") ENGINE = InnoDB");
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Database Maintenance');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Run Database Maintenance');
	}


}
