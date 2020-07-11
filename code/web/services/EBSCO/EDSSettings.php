<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Ebsco/EDSSettings.php';

class EBSCO_EDSSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'EDSSettings';
	}

	function getToolName()
	{
		return 'EDSSettings';
	}

	function getModule()
	{
		return 'EBSCO';
	}

	function getPageTitle()
	{
		return 'EBSCO EDS Settings';
	}

	function getAllObjects()
	{
		$object = new EDSSettings();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return EDSSettings::getObjectStructure();
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