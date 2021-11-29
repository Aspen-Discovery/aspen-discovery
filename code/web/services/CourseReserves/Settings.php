<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CourseReserves/CourseReservesIndexingSettings.php';

class CourseReserves_Settings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'CourseReservesIndexingSettings';
	}

	function getToolName() : string
	{
		return 'Settings';
	}

	function getModule() : string
	{
		return 'CourseReserves';
	}

	function getPageTitle() : string
	{
		return 'Course Reserves Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new CourseReservesIndexingSettings();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'id asc';
	}

	function getObjectStructure() : array
	{
		return CourseReservesIndexingSettings::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getAdditionalObjectActions($existingObject) : array
	{
		return [];
	}

	function getInstructions() : string
	{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#course_reserves', 'Course Reserves');
		$breadcrumbs[] = new Breadcrumb('/CourseReserves/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'course_reserves';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Course Reserves');
	}
	function canAddNew(){
		return $this->getNumObjects() == 0;
	}
}