<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';

class WebBuilder_Categories extends ObjectEditor
{
	function getObjectType()
	{
		return 'WebBuilderCategory';
	}

	function getToolName()
	{
		return 'Categories';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Categories';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new WebBuilderCategory();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'name asc';
	}

	function getObjectStructure()
	{
		return WebBuilderCategory::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Categories', 'Categories');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Web Categories']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}