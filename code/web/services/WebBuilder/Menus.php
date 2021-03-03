<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderMenu.php';

class WebBuilder_Menus extends ObjectEditor
{
	function getObjectType()
	{
		return 'WebBuilderMenu';
	}

	function getToolName()
	{
		return 'Menus';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'WebBuilder Menus';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		global $library;
		$object = new WebBuilderMenu();
		$object->parentMenuId = -1;
		$object->libraryId = $library->libraryId;
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
			$subMenu = new WebBuilderMenu();
			$subMenu->parentMenuId = $object->id;
			$subMenu->libraryId = $library->libraryId;
			$subMenu->orderBy($this->getSort());
			$subMenu->find();
			while ($subMenu->fetch()) {
				$subMenu->label = "--- " . $subMenu->label;
				$objectList[$subMenu->id] = clone $subMenu;
			}
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'weight asc';
	}

	function canSort()
	{
		return false;
	}

	function getObjectStructure()
	{
		return WebBuilderMenu::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Menus', 'Menus');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Menus', 'Administer Library Menus']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}