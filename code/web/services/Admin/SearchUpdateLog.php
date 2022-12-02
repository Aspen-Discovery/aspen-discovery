<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/SearchUpdateLogEntry.php';

class Admin_SearchUpdateLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new SearchUpdateLogEntry();
	}

	function getTemplateName(): string {
		return 'searchUpdateLog.tpl';
	}

	function getTitle(): string {
		return 'Search Update Log';
	}

	function getModule(): string {
		return 'Admin';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof SearchUpdateLogEntry) {
			$indexingObject->whereAdd('(numUpdated) >= ' . $minProcessed);
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_reports', 'System Reports');
		$breadcrumbs[] = new Breadcrumb('', 'Search Update Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_reports';
	}
}
