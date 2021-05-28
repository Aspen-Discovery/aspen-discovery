<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/OMDBSetting.php';

class Enrichment_OMDBSettings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'OMDBSetting';
	}

	function getToolName() : string
	{
		return 'OMDBSettings';
	}

	function getModule() : string
	{
		return 'Enrichment';
	}

	function getPageTitle() : string
	{
		return 'OMDB Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new OMDBSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
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
		return OMDBSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/OMDBSettings', 'OMDB Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'third_party_enrichment';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Third Party Enrichment API Keys');
	}

	function canAddNew()
	{
		return $this->getNumObjects() == 0;
	}
}