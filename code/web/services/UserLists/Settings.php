<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/UserLists/ListIndexingSettings.php';

class UserLists_Settings extends ObjectEditor
{
	function getObjectType()
	{
		return 'ListIndexingSettings';
	}

	function getToolName()
	{
		return 'Settings';
	}

	function getModule()
	{
		return 'UserLists';
	}

	function getPageTitle()
	{
		return 'List Indexing Settings';
	}

	function getAllObjects($page, $recordsPerPage)
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
	function getDefaultSort()
	{
		return 'id asc';
	}

	function getObjectStructure()
	{
		return ListIndexingSettings::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#user_lists', 'User Lists');
		$breadcrumbs[] = new Breadcrumb('/UserLists/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'user_lists';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer List Indexing Settings');
	}
}