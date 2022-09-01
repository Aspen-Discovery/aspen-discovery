<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Ebsco/EDSSettings.php';

class EBSCO_EDSSettings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'EDSSettings';
	}

	function getToolName() : string
	{
		return 'EDSSettings';
	}

	function getModule() : string
	{
		return 'EBSCO';
	}

	function getPageTitle() : string
	{
		return 'EBSCO EDS Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new EDSSettings();
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
		return EDSSettings::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebsco', 'EBSCO');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EDS Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ebsco';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer EBSCO EDS');
	}
}