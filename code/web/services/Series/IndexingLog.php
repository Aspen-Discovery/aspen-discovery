<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Series/SeriesIndexingLogEntry.php';

class Series_IndexingLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new SeriesIndexingLogEntry();
	}

	function getTemplateName(): string {
		return 'seriesIndexingLog.tpl';
	}

	function getTitle(): string {
		return 'Series Indexing Log';
	}

	function getModule(): string {
		return 'Series';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof ListIndexingLogEntry) {
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed);
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#series', 'Series');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'series';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'View System Reports',
			'View Indexing Logs',
		]);
	}
}
