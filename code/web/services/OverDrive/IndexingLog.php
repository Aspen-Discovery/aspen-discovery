<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveExtractLogEntry.php';

class OverDrive_IndexingLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new OverDriveExtractLogEntry();
	}

	function getTemplateName(): string {
		global $interface;
		$interface->assign('title', $this->getTitle());
		return 'overdriveExtractLog.tpl';
	}

	function getTitle(): string {
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		return $readerName . ' Extract Log';
	}

	function getModule(): string {
		return 'OverDrive';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof OverDriveExtractLogEntry) {
			$indexingObject->whereAdd('numAvailabilityChanges >= ' . $minProcessed);
			$indexingObject->whereAdd('numMetadataChanges >= ' . $minProcessed, 'OR');
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed, 'OR');
		}
	}

	function getBreadcrumbs(): array {
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', $readerName);
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'overdrive';
	}
}
