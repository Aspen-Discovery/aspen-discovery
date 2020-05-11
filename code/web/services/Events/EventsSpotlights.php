<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventsSpotlight.php';

class Events_EventsSpotlights extends ObjectEditor
{

	function getAllowableRoles()
	{
		return ['opacAdmin', 'libraryAdmin'];
	}

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType()
	{
		return 'EventsSpotlight';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName()
	{
		return 'EventsSpotlights';
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
		return 'Events Spotlights';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects()
	{
		$object = new EventsSpotlight();
		$object->orderBy('name');
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure()
	{
		return EventsSpotlight::getObjectStructure();
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
}