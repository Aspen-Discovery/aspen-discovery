<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';

class OverDrive_Settings extends ObjectEditor
{
	function getObjectType()
	{
		return 'OverDriveSetting';
	}

	function getToolName()
	{
		return 'Settings';
	}

	function getModule()
	{
		return 'OverDrive';
	}

	function getPageTitle()
	{
		return 'OverDrive Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new OverDriveSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'id asc';
	}

	function getObjectStructure()
	{
		return OverDriveSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', 'OverDrive');
		$breadcrumbs[] = new Breadcrumb('/OverDrive/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'overdrive';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer OverDrive');
	}
}