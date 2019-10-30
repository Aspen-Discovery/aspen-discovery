<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryExportLogEntry.php';

/** @noinspection PhpUnused */
class CloudLibrary_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): DataObject
	{
		return new CloudLibraryExportLogEntry();
	}

	function getTemplateName() : string
	{
		return 'cloudLibraryExportLog.tpl';
	}

	public function getTitle(): string
	{
		return 'Cloud Library Export Log';
	}

	function getModule() : string{
		return 'CloudLibrary';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof CloudLibraryExportLogEntry){
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
		}
	}
}
