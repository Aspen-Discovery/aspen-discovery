<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectLogEntry.php';

class PalaceProject_IndexingLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new PalaceProjectLogEntry();
	}

	function getTemplateName(): string {
		return 'palaceProjectExportLog.tpl';
	}

	function getTitle(): string {
		return 'Palace Project Export Log';
	}

	function getModule(): string {
		return 'PalaceProject';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof PalaceProjectLogEntry) {
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#palace_project', 'Palace Project');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'palace_project';
	}
}
