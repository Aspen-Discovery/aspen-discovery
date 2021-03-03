<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Setting.php';

class Axis360_Settings extends ObjectEditor
{
	function getObjectType()
	{
		return 'Axis360Setting';
	}

	function getToolName()
	{
		return 'Settings';
	}

	function getModule()
	{
		return 'Axis360';
	}

	function getPageTitle()
	{
		return 'Axis 360 Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new Axis360Setting();
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
	function getDefaultSort()
	{
		return 'userInterfaceUrl asc';
	}

	function getObjectStructure()
	{
		return Axis360Setting::getObjectStructure();
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function getAdditionalObjectActions($existingObject)
	{
		return [];
	}

	function getInstructions()
	{
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'axis360';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Axis 360');
	}
}