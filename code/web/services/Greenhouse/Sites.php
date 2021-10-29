<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
class Greenhouse_Sites extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'AspenSite';
	}

	function getToolName() : string
	{
		return 'Sites';
	}

	function getModule() : string
	{
		return 'Greenhouse';
	}

	function getPageTitle() : string
	{
		return 'Aspen Sites';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new AspenSite();
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
		return 'name asc';
	}

	function getObjectStructure() : array
	{
		return AspenSite::getObjectStructure();
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
		return true;
	}

	function canDelete()
	{
		return true;
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
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Sites', 'Sites');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source = 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}

	protected function getDefaultRecordsPerPage()
	{
		return 100;
	}
}