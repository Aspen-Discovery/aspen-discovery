<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryExportLogEntry.php';

class CloudLibrary_IndexingLog extends Admin_IndexingLog {
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

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof CloudLibraryExportLogEntry) {
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
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
