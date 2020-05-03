<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';

class WebBuilder_WebResources extends ObjectEditor
{
	function getObjectType()
	{
		return 'WebResource';
	}

	function getToolName()
	{
		return 'WebResources';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Library Resources';
	}

	function getAllObjects()
	{
		$object = new WebResource();
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
		return WebResource::getObjectStructure();
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