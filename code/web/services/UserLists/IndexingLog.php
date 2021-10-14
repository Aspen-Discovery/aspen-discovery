<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/UserLists/ListIndexingLogEntry.php';

class UserLists_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): BaseLogEntry
	{
		return new ListIndexingLogEntry();
	}

	function getTemplateName() : string
	{
		return 'listIndexingLog.tpl';
	}

	function getTitle() : string
	{
		return 'User List Indexing Log';
	}

	function getModule() : string{
		return 'UserLists';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof ListIndexingLogEntry){
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed);
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#user_lists', 'User Lists');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'user_lists';
	}
}
