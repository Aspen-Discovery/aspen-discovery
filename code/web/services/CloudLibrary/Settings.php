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

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new CloudLibrarySetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'userInterfaceUrl asc';
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloud_library', 'Cloud Library');
		$breadcrumbs[] = new Breadcrumb('/CloudLibrary/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'cloud_library';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Cloud Library');
	}
}