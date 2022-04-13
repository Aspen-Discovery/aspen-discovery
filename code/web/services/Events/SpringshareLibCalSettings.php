<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';

class Events_SpringshareLibCalSettings extends ObjectEditor
{

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType() : string
	{
		return 'SpringshareLibCalSetting';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName() : string
	{
		return 'SpringshareLibCalSettings';
	}

	function getModule() : string
	{
		return 'Events';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle() : string
	{
		return 'Springshare LibCal Settings';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new SpringshareLibCalSetting();
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

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure() : array
	{
		return SpringshareLibCalSetting::getObjectStructure();
	}

	/**
	 * The name of the column which defines this as unique
	 */
	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	/**
	 * The id of the column which serves to join other columns
	 */
	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/SpringshareLibCalSettings', 'Springshare - LibCal Settings');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Springshare LibCal Settings');
	}

	function getActiveAdminSection() : string
	{
		return 'events';
	}
}