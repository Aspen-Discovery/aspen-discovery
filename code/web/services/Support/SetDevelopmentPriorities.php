<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Support/DevelopmentPriorities.php';
class SetDevelopmentPriorities extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'DevelopmentPriorities';
	}

	function getToolName() : string
	{
		return 'SetDevelopmentPriorities';
	}

	function getModule() : string
	{
		return 'Support';
	}

	function getPageTitle() : string
	{
		return 'Set Development Priorities';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new DevelopmentPriorities();
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
		return DevelopmentPriorities::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Support');
		$breadcrumbs[] = new Breadcrumb('/Support/SetDevelopmentPriorities', 'Set Development Priorities');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'support';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Set Development Priorities');
	}

	function canAddNew()
	{
		return $this->getNumObjects() <= 0;
	}
}