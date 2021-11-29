<?php

require_once ROOT_DIR . '/services/Admin/IndexingLog.php';
require_once ROOT_DIR . '/sys/CourseReserves/CourseReservesIndexingLogEntry.php';

class CourseReserves_IndexingLog extends Admin_IndexingLog
{
	function getIndexLogEntryObject(): BaseLogEntry
	{
		return new CourseReservesIndexingLogEntry();
	}

	function getTemplateName() : string
	{
		return 'courseReservesIndexingLog.tpl';
	}

	function getTitle() : string
	{
		return 'Course Reserves Indexing Log';
	}

	function getModule() : string{
		return 'CourseReserves';
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#course_reserves', 'Course Reserves');
		$breadcrumbs[] = new Breadcrumb('', 'Indexing Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'course_reserves';
	}
}
