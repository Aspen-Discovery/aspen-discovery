<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';

class EBSCO_EBSCOhostSettings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'EBSCOhostSetting';
	}

	function getToolName() : string
	{
		return 'EBSCOhostSettings';
	}

	function getModule() : string
	{
		return 'EBSCO';
	}

	function getPageTitle() : string
	{
		return 'EBSCOhost Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new EBSCOhostSetting();
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
		return EBSCOhostSetting::getObjectStructure();
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
		return 'https://help.aspendiscovery.org/ebsco';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebscohost', 'EBSCOhost');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EBSCOhost Settings', 'EBSCOhost Settings');
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