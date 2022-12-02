<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';

class UserLists_NYTUpdatesLog extends Admin_IndexingLog {
	function getIndexLogEntryObject(): BaseLogEntry {
		return new NYTUpdateLogEntry();
	}

	function getTemplateName(): string {
		return 'nytUpdatesLog.tpl';
	}

	function getTitle(): string {
		return 'New York Times Updates Log';
	}

	function getModule(): string {
		return 'UserLists';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed) {
		if ($indexingObject instanceof NYTUpdateLogEntry) {
			$indexingObject->whereAdd('(numAdded + numUpdated) >= ' . $minProcessed);
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#user_lists', 'User Lists');
		$breadcrumbs[] = new Breadcrumb('', 'New York Times Updates Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'user_lists';
	}
}
