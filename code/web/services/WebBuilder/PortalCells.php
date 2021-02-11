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

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new PortalCell();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'weight asc';
	}

	function canSort()
	{
		return false;
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

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		if (!empty($this->activeObject) && $this->activeObject instanceof PortalCell){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages?objectAction=edit&id=' . $this->activeObject->getPortalRow()->portalPageId , 'Custom Page');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Cell Content');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Custom Pages', 'Administer Library Custom Pages']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}