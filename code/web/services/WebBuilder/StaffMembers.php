<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';

class WebBuilder_StaffMembers extends ObjectEditor
{
	function getObjectType()
	{
		return 'StaffMember';
	}

	function getToolName()
	{
		return 'StaffMembers';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Staff Members';
	}

	function getAllObjects()
	{
		$object = new StaffMember();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return StaffMember::getObjectStructure();
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
		return array('opacAdmin', 'web_builder_admin');
	}

	function canAddNew()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin');
	}

	function canDelete()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin');
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