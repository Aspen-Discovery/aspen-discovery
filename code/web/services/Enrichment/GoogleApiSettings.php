<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';

class Enrichment_GoogleApiSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'GoogleApiSetting';
	}

	function getToolName()
	{
		return 'GoogleApiSettings';
	}

	function getModule()
	{
		return 'Enrichment';
	}

	function getPageTitle()
	{
		return 'Google Api Settings';
	}

	function getAllObjects()
	{
		$object = new GoogleApiSetting();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return GoogleApiSetting::getObjectStructure();
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