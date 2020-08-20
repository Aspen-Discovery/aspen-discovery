<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360ExportLogEntry.php';

class Axis360_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): BaseLogEntry
	{
		return new Axis360ExportLogEntry();
	}

	function getTemplateName() : string
	{
		return 'axis360ExportLog.tpl';
	}

	public function getTitle(): string
	{
		return 'Axis 360 Export Log';
	}

	function getModule() : string{
		return 'Axis360';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof Axis360ExportLogEntry){
			$indexingObject->whereAdd('numProducts >= ' . $minProcessed);
		}
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}
}
