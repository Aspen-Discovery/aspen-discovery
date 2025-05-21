<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryExportLogEntry.php';

class CloudLibrary_IndexingLog extends Admin_IndexingLog {
	function launch() : void {
		global $interface;
		$setting = new CloudLibrarySetting();
		$settings = $setting->fetchAll('id', 'name');
		$interface->assign('settings', $settings);
		parent::launch();
	}
	function getIndexLogEntryObject(): BaseLogEntry {
		return new CloudLibraryExportLogEntry();
	}

	function getTemplateName(): string {
		return 'cloudLibraryExportLog.tpl';
	}

	public function getTitle(): string {
		return 'CloudLibrary Export Log';
	}

	function getModule(): string {
		return 'CloudLibrary';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) : void {
		if ($indexingObject instanceof CloudLibraryExportLogEntry) {
			$indexingObject->whereAdd('(numProducts + numAdded + numDeleted + numUpdated) >= ' . $minProcessed);
		}
	}

	/**
	 * Apply any additional filters that are custom to the log being viewed.
	 *
	 * @param DataObject $logEntry
	 * @return void
	 */
	function applyAdditionalFilters(DataObject $logEntry) : void {
		if ($logEntry instanceof CloudLibraryExportLogEntry) {
			global $interface;
			$interface->assign('selectedSetting', -1);
			if (isset($_REQUEST['settingToShow'])) {
				if ($_REQUEST['settingToShow'] != -1 && is_numeric($_REQUEST['settingToShow'])) {
					$logEntry->settingId = $_REQUEST['settingToShow'];
					$interface->assign('selectedSetting', $_REQUEST['settingToShow']);
				}
			}
		}
	}
	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloud_library', 'cloudLibrary');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cloud_library';
	}
}
