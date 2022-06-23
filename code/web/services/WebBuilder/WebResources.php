<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';

class WebBuilder_WebResources extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'WebResource';
	}

	function getToolName() : string
	{
		return 'WebResources';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'Library Resources';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new WebResource();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Web Resources')){
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryWebResource', 'webResourceId');
		}
		$objectList = array();
		if ($userHasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'name asc';
	}

	function getObjectStructure() : array
	{
		return WebResource::getObjectStructure();
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
		return 'https://help.aspendiscovery.org/help/webbuilder/webresources';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/WebResources', 'Web Resources');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Web Resources', 'Administer Library Web Resources']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}