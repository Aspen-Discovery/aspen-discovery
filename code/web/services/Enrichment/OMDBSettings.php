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

	function getAllObjects()
	{
		$object = new OMDBSetting();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
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

	function getAllowableRoles()
	{
		return array('opacAdmin', 'libraryAdmin');
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
		return '';
	}
}