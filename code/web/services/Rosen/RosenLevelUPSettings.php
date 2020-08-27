<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Rosen/RosenLevelUPSetting.php';

class RosenLevelUPSettings extends ObjectEditor
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

	function getAllObjects()
	{
		$object = new RosenLevelUPSetting();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
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

	function getAllowableRoles()
	{
		return array('opacAdmin');
	}

	function canAddNew()
	{
		return UserAccount::userHasRole('opacAdmin');
	}

	function canDelete()
	{
		return UserAccount::userHasRole('opacAdmin');
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
		return [];
	}

	function getActiveAdminSection()
	{
		return 'third_party_enrichment';
	}
}