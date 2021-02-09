<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Rosen/RosenLevelUPSetting.php';

class Rosen_RosenLevelUPSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'RosenLevelUPSetting';
	}

	function getToolName()
	{
		return 'RosenLevelUPSettings';
	}

	function getModule()
	{
		return 'Rosen';
	}

	function getPageTitle()
	{
		return 'Rosen LevelUP Settings';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new RosenLevelUPSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
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

	function canSort()
	{
		return false;
	}

	function getObjectStructure()
	{
		return RosenLevelUPSetting::getObjectStructure();
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
		return '/Admin/HelpManual?page=Rosen-LevelUP';
	}

	function getBreadcrumbs(){
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Rosen/RosenLevelUPSettings', 'Rosen LevelUP Settings');
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