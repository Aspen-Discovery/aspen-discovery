<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/OMDBSetting.php';

class Enrichment_OMDBSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'OMDBSetting';
	}

	function getToolName()
	{
		return 'OMDBSettings';
	}

	function getModule()
	{
		return 'Enrichment';
	}

	function getPageTitle()
	{
		return 'OMDB Settings';
	}

	function getAllObjects($page, $recordsPerPage)
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
	function getDefaultSort()
	{
		return 'id asc';
	}

	function getObjectStructure()
	{
		return OMDBSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/OMDBSettings', 'OMDB Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'third_party_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Third Party Enrichment API Keys');
	}

	function canAddNew()
	{
		return $this->getNumObjects() == 0;
	}
}