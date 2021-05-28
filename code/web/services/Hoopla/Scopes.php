<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaScope.php';

class Hoopla_Scopes extends ObjectEditor
{
	function getObjectType() : string{
		return 'HooplaScope';
	}
	function getToolName() : string{
		return 'Scopes';
	}
	function getModule() : string{
		return 'Hoopla';
	}
	function getPageTitle() : string{
		return 'Hoopla Scopes';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new HooplaScope();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'name asc';
	}
	function getObjectStructure() : array{
		return HooplaScope::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function getAdditionalObjectActions($existingObject) : array{
		return [];
	}

	function getInstructions() : string{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#hoopla', 'Hoopla');
		$breadcrumbs[] = new Breadcrumb('/Hoopla/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'hoopla';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Hoopla');
	}
}