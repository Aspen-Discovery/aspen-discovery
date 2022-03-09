<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
class Greenhouse_Settings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'GreenhouseSettings';
	}

	function getToolName() : string
	{
		return 'Settings';
	}

	function getModule() : string
	{
		return 'Greenhouse';
	}

	function getPageTitle() : string
	{
		return 'Greenhouse Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new GreenhouseSettings();
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
		return 'id';
	}

	function getObjectStructure() : array
	{
		return GreenhouseSettings::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function canAddNew()
	{
		return $this->getNumObjects() == 0;
	}

	function canDelete()
	{
		return false;
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
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}
}