<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';

class Enrichment_SyndeticsSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'SyndeticsSetting';
	}

	function getToolName()
	{
		return 'SyndeticsSettings';
	}

	function getModule()
	{
		return 'Enrichment';
	}

	function getPageTitle()
	{
		return 'Syndetics Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new SyndeticsSetting();
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
		return SyndeticsSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Enrichment/SyndeticsSettings', 'Syndetics Settings');
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