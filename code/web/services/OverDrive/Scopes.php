<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';

class OverDrive_Scopes extends ObjectEditor
{
	function getObjectType()
	{
		return 'OverDriveScope';
	}

	function getToolName()
	{
		return 'Scopes';
	}

	function getModule()
	{
		return 'OverDrive';
	}

	function getPageTitle()
	{
		return 'OverDrive Scopes';
	}

	function getAllObjects()
	{
		$object = new OverDriveScope();
		$object->orderBy('name');
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return OverDriveScope::getObjectStructure();
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
		return array('opacAdmin', 'libraryAdmin', 'cataloging');
	}

	function canAddNew()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('cataloging');
	}

	function canDelete()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('cataloging');
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