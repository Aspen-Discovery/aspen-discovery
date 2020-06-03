<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';

class CloudLibrary_Settings extends ObjectEditor
{
	function getObjectType()
	{
		return 'CloudLibrarySetting';
	}

	function getToolName()
	{
		return 'Settings';
	}

	function getModule()
	{
		return 'CloudLibrary';
	}

	function getPageTitle()
	{
		return 'Cloud Library Settings';
	}

	function getAllObjects()
	{
		$object = new CloudLibrarySetting();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return CloudLibrarySetting::getObjectStructure();
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
		return array('opacAdmin', 'libraryAdmin', 'cataloging', 'superCataloger');
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