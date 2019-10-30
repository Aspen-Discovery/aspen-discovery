<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveExtractLogEntry.php';

/** @noinspection PhpUnused */
class OverDrive_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): DataObject
	{
		return new OverDriveExtractLogEntry();
	}

	function getTemplateName() : string
	{
		return 'overDriveExtractLog.tpl';
	}

	function getTitle() : string
	{
		return 'OverDrive Export Log';
	}

	function getModule() : string{
		return 'OverDrive';
	}

	function applyMinProcessedFilter(DataObject $indexingObject, $minProcessed){
		if ($indexingObject instanceof OverDriveExtractLogEntry){
			$indexingObject->whereAdd('numAvailabilityChanges >= ' . $minProcessed);
			$indexingObject->whereAdd('numMetadataChanges >= ' . $minProcessed, 'OR');
			$indexingObject->whereAdd('(numAdded + numDeleted + numUpdated) >= ' . $minProcessed, 'OR');
		}
	}
}
