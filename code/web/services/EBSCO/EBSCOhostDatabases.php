<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostDatabase.php';

class EBSCO_EBSCOhostDatabases extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'EBSCOhostDatabase';
	}

	function getToolName() : string
	{
		return 'EBSCOhostDatabases';
	}

	function getModule() : string
	{
		return 'EBSCO';
	}

	function getPageTitle() : string
	{
		return 'EBSCOhost Databases';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new EBSCOhostDatabase();
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
		return EBSCOhostDatabase::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebscohost', 'EBSCOhost');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EBSCOhostSearchSettings', 'EBSCOhost Search Settings');
		if (!empty($this->activeObject) && $this->activeObject instanceof EBSCOhostDatabase){
			$breadcrumbs[] = new Breadcrumb('/EBSCO/EBSCOhostSearchSettings?objectAction=edit&id=' . $this->activeObject->searchSettingId , 'EBSCOhost Search Setting');
		}
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ebscohost';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer EBSCOhost Settings');
	}
}