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

	function getAdditionalObjectActions($existingObject)
	{
		return [];
	}

	function getInstructions()
	{
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebsco', 'EBSCO');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EDS Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ebsco';
	}
}