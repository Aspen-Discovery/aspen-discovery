<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesExportLogEntry.php';

class OpenArchives_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): DataObject
	{
		return new OpenArchivesExportLogEntry();
	}

	function getTemplateName() : string
	{
		return 'openArchivesLog.tpl';
	}

	function getTitle() : string
	{
		return 'Open Archives Extract Log';
	}

	function getModule() : string{
		return 'OpenArchives';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof OpenArchivesExportLogEntry){
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed);
		}
	}
}
