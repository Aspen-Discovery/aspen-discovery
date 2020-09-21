<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';

class WebBuilder_PortalCells extends ObjectEditor
{
	function getObjectType()
	{
		return 'PortalCell';
	}

	function getToolName()
	{
		return 'PortalCells';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'WebBuilder Portal Cells';
	}

	function getAllObjects()
	{
		$object = new PortalCell();
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return PortalCell::getObjectStructure();
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

	/**
	 * @param string $objectAction
	 * @param DataObject $curObject
	 * @return string|null
	 */
	function getRedirectLocation($objectAction, $curObject){
		if ($curObject instanceof PortalCell){
			$portalRow = $curObject->getPortalRow();
			return '/WebBuilder/PortalPages?objectAction=edit&id=' . $portalRow->portalPageId;
		}else{
			return null;
		}
	}

	function getInitializationJs()
	{
		return 'AspenDiscovery.WebBuilder.getPortalCellValuesForSource()';
	}
}