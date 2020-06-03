<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/RenaissanceLearning/ARSetting.php';

class RenaissanceLearning_ARSettings extends ObjectEditor
{
	function getObjectType()
	{
		return 'ARSetting';
	}

	function getToolName()
	{
		return 'ARSettings';
	}

	function getModule()
	{
		return 'RenaissanceLearning';
	}

	function getPageTitle()
	{
		return 'Accelerated Reader Settings';
	}

	function getAllObjects()
	{
		$object = new ARSetting();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return ARSetting::getObjectStructure();
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
		return array('opacAdmin', 'cataloging', 'superCataloger');
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
		return '/Admin/HelpManual?page=Accelerated-Reader';
	}
}