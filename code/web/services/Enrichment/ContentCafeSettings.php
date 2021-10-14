<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';

class Enrichment_ContentCafeSettings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'ContentCafeSetting';
	}

	function getToolName() : string
	{
		return 'ContentCafeSettings';
	}

	function getModule() : string
	{
		return 'Enrichment';
	}

	function getPageTitle() : string
	{
		return 'ContentCafe Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new ContentCafeSetting();
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
		return ContentCafeSetting::getObjectStructure();
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
		return '/Admin/HelpManual?page=Content-Cafe';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/ContentCafeSettings', 'Content Cafe Settings');
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