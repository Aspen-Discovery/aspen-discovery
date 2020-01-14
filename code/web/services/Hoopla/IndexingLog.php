<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaExportLogEntry.php';

class Hoopla_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): DataObject
	{
		return new HooplaExportLogEntry();
	}

	function getTemplateName() : string
	{
		return 'hooplaExportLog.tpl';
	}

	function getTitle() : string
	{
		return 'Hoopla Export Log';
	}

	function getModule() : string{
		return 'Hoopla';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof HooplaExportLogEntry){
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
		}
	}
}
