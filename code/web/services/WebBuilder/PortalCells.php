<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';

class WebBuilder_PortalCells extends ObjectEditor
{
	function launch()
	{
		global $interface;
		$interface->assign('inPageEditor', true);
		parent::launch();
	}
	function getObjectType() : string
	{
		return 'PortalCell';
	}

	function getToolName() : string
	{
		return 'PortalCells';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'WebBuilder Portal Cells';
	}

	function getAllObjects($page, $recordsPerPage) : array
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

	function getDefaultSort() : string
	{
		return 'weight asc';
	}

	function canSort() : bool
	{
		return false;
	}

	function getObjectStructure() : array
	{
		return PortalCell::getObjectStructure();
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

	function getInitializationJs() : string
	{
		return 'AspenDiscovery.WebBuilder.getPortalCellValuesForSource()';
	}

	function getInitializationAdditionalJs()
	{
		return 'return AspenDiscovery.Admin.updateMakeCellAccordion();';
	}

	function getBreadcrumbs() : array
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

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Custom Pages', 'Administer Library Custom Pages']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}