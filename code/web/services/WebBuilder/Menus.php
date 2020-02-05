<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderMenu.php';

class WebBuilder_Menus extends ObjectEditor
{
	function getObjectType()
	{
		return 'WebBuilderMenu';
	}

	function getToolName()
	{
		return 'Menus';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'WebBuilder Menus';
	}

	function getAllObjects()
	{
		$object = new WebBuilderMenu();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return WebBuilderMenu::getObjectStructure();
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
		return array('opacAdmin', 'web_builder_admin', 'web_builder_creator');
	}

	function canAddNew()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin') || UserAccount::userHasRole('web_builder_creator');
	}

	function canDelete()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole( 'web_builder_admin');
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