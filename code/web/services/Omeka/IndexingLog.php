<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaExportLogEntry.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaSetting.php';

class Omeka_IndexingLog extends Admin_IndexingLog {
	function launch() : void {
		global $interface;
		$setting = new OmekaSetting();
		$settings = $setting->fetchAll('id', 'name');
		$interface->assign('settings', $settings);
		parent::launch();
	}

	function getIndexLogEntryObject(): BaseLogEntry {
		return new OmekaExportLogEntry();
	}

	function getTemplateName(): string {
		return 'omekaExportLog.tpl';
	}

	function getTitle(): string {
		return 'Omeka Export Log';
	}

	function getModule(): string {
		return 'Omeka';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) : void {
		if ($indexingObject instanceof OmekaExportLogEntry) {
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
		}
	}

	function applyAdditionalFilters(DataObject $logEntry) : void {
		if (!($logEntry instanceof OmekaExportLogEntry)) {
			return;
		}
		global $interface;
		$interface->assign('selectedSetting', -1);
		$settingToShow = $_REQUEST['settingToShow'] ?? -1;
		$specificSettingRequested = $settingToShow != -1 && is_numeric($settingToShow);
		if (!$specificSettingRequested) {
			return;
		}
		$logEntry->settingId = $settingToShow;
		$interface->assign('selectedSetting', $settingToShow);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#omeka', 'Omeka');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'omeka';
	}
}
