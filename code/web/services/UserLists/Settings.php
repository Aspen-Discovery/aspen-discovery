<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/UserLists/ListIndexingSettings.php';

class UserLists_Settings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'ListIndexingSettings';
	}

	function getToolName() : string
	{
		return 'Settings';
	}

	function getModule() : string
	{
		return 'UserLists';
	}

	function getPageTitle() : string
	{
		return 'List Indexing Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new ListIndexingSettings();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'id asc';
	}

	function getObjectStructure() : array
	{
		return ListIndexingSettings::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getAdditionalObjectActions($existingObject) : array
	{
		return [];
	}

	function getInstructions() : string
	{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#user_lists', 'User Lists');
		$breadcrumbs[] = new Breadcrumb('/UserLists/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'user_lists';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer List Indexing Settings');
	}
}