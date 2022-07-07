<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';

class WebBuilder_PortalRows extends ObjectEditor
{
	function launch()
	{
		global $interface;
		$interface->assign('inPageEditor', true);
		parent::launch();
	}

	function getInitializationJs() : string
	{
		return 'return AspenDiscovery.Admin.updateMakeRowAccordion();';
	}

	function getObjectType() : string
	{
		return 'PortalRow';
	}

	function getToolName() : string
	{
		return 'PortalRows';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'WebBuilder Portal Rows';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new PortalRow();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
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

	function getObjectStructure() : array
	{
		return PortalRow::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		if (!empty($this->activeObject) && $this->activeObject instanceof PortalRow){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages?objectAction=edit&id=' . $this->activeObject->portalPageId , 'Custom Page');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Row Content');
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