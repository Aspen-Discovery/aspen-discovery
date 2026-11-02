<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxExtractLogEntry.php';

class BorrowBox_IndexingLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new BorrowBoxExtractLogEntry();
	}

	function getTemplateName(): string {
		global $interface;
		$interface->assign('title', $this->getTitle());
		return 'borrowboxExtractLog.tpl';
	}

	function getTitle(): string {
		return 'BorrowBox Extract Log';
	}

	function getModule(): string {
		return 'BorrowBox';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed): void {
		if (!($indexingObject instanceof BorrowBoxExtractLogEntry)) {
			return;
		}
		$indexingObject->whereAdd('numAvailabilityChanges >= ' . $minProcessed);
		$indexingObject->whereAdd('numMetadataChanges >= ' . $minProcessed, 'OR');
		$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed, 'OR');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#borrowbox', 'BorrowBox');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'borrowbox';
	}
}
