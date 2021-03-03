<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';

class Enrichment_NewYorkTimesSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'NewYorkTimesSetting';
	}

	function getToolName()
	{
		return 'NewYorkTimesSettings';
	}

	function getModule()
	{
		return 'Enrichment';
	}

	function getPageTitle()
	{
		return 'New York Times Api Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new NewYorkTimesSetting();
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
	function getDefaultSort()
	{
		return 'id asc';
	}

	function getObjectStructure()
	{
		return NewYorkTimesSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Enrichment/NewYorkTimesSettings', 'New York Times Settings');
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