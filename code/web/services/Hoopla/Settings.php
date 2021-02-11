<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaSetting.php';

class Hoopla_Settings extends ObjectEditor
{
	function getObjectType()
	{
		return 'HooplaSetting';
	}

	function getToolName()
	{
		return 'Settings';
	}

	function getModule()
	{
		return 'Hoopla';
	}

	function getPageTitle()
	{
		return 'Hoopla Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new HooplaSetting();
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
		return 'apiUrl asc';
	}

	function getObjectStructure()
	{
		return HooplaSetting::getObjectStructure();
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function canAddNew()
	{
		return true;
	}

	function canDelete()
	{
		return true;
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#hoopla', 'Hoopla');
		$breadcrumbs[] = new Breadcrumb('/Hoopla/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'hoopla';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Hoopla');
	}
}