<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';

class RBdigital_Scopes extends ObjectEditor
{
	function getObjectType() : string{
		return 'RBdigitalScope';
	}
	function getToolName() : string{
		return 'Scopes';
	}
	function getModule() : string{
		return 'RBdigital';
	}
	function getPageTitle() : string{
		return 'RBdigital Scopes';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new RBdigitalScope();
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
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
		return RBdigitalScope::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#rbdigital', 'RBdigital');
		if (!empty($this->activeObject) && $this->activeObject instanceof RBdigitalScope){
			$breadcrumbs[] = new Breadcrumb('/RBdigital/Settings?objectAction=edit&id=' . $this->activeObject->settingId , 'Settings');
		}
		$breadcrumbs[] = new Breadcrumb('/RBdigital/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'rbdigital';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer RBdigital');
	}
}