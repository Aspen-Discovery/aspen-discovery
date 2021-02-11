<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';

class RBdigital_Settings extends ObjectEditor
{
	function getObjectType()
	{
		return 'RBdigitalSetting';
	}

	function getToolName()
	{
		return 'Settings';
	}

	function getModule()
	{
		return 'RBdigital';
	}

	function getPageTitle()
	{
		return 'RBdigital Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new RBdigitalSetting();
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
		return RBdigitalSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#rbdigital', 'RBdigital');
		$breadcrumbs[] = new Breadcrumb('/RBdigital/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'rbdigital';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer RBdigital');
	}
}