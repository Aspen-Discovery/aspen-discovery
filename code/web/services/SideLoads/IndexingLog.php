<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadLogEntry.php';

/** @noinspection PhpUnused */
class SideLoads_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): DataObject
	{
		return new SideLoadLogEntry();
	}

	function getTemplateName() : string
	{
		return 'sideLoadLog.tpl';
	}

	function getTitle() : string
	{
		return 'Side Load Processing Log';
	}

	function getModule() : string{
		return 'SideLoads';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof SideLoadLogEntry){
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed);
		}
	}
}
