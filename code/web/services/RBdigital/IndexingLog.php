<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalExportLogEntry.php';

class RBdigital_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): DataObject
	{
		return new RBdigitalExportLogEntry();
	}

	function getTemplateName() : string
	{
		return 'rbdigitalExportLog.tpl';
	}

	function getTitle() : string
	{
		return 'RBdigital Export Log';
	}

	function getModule() : string{
		return 'RBdigital';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof RBdigitalExportLogEntry){
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed);
		}
	}
}
