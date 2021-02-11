<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';

class Events_LMLibraryCalendarSettings extends ObjectEditor
{

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType()
	{
		return 'LMLibraryCalendarSetting';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName()
	{
		return 'LMLibraryCalendarSettings';
	}

	function getModule()
	{
		return 'Events';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle()
	{
		return 'Library Market - Library Calendar Settings';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects($page, $recordsPerPage)
	{
		$object = new LMLibraryCalendarSetting();
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

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure()
	{
		return LMLibraryCalendarSetting::getObjectStructure();
	}

	/**
	 * The name of the column which defines this as unique
	 */
	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	/**
	 * The id of the column which serves to join other columns
	 */
	function getIdKeyColumn()
	{
		return 'id';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/LMLibraryCalendarSettings', 'Library Market - Library Calendar Settings');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Library Calendar Settings');
	}

	function getActiveAdminSection()
	{
		return 'events';
	}
}