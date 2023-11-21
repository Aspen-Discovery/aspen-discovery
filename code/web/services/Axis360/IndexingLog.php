<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360LogEntry.php';

class Axis360_IndexingLog extends Admin_IndexingLog {
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
		return 'Boundless';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof Axis360LogEntry) {
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
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
