<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360LogEntry.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Setting.php';

class Axis360_IndexingLog extends Admin_IndexingLog {
	function launch() : void {
		global $interface;
		$setting = new Axis360Setting();
		$settings = $setting->fetchAll('id', 'name');
		$interface->assign('settings', $settings);
		parent::launch();
	}
	function getIndexLogEntryObject(): BaseLogEntry {
		return new Axis360LogEntry();
	}

	function getTemplateName(): string {
		return 'axis360ExportLog.tpl';
	}

	public function getTitle(): string {
		return 'Boundless Export Log';
	}

	function getModule(): string {
		return 'Axis360';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) : void {
		if ($indexingObject instanceof Axis360LogEntry) {
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
		}
	}

	/**
	 * Apply any additional filters that are custom to the log being viewed.
	 *
	 * @param DataObject $logEntry
	 * @return void
	 */
	function applyAdditionalFilters(DataObject $logEntry) : void {
		if ($logEntry instanceof Axis360LogEntry) {
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#boundless', 'Boundless');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'boundless';
	}
}
