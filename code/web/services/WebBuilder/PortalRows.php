<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';

class WebBuilder_PortalRows extends ObjectEditor
{
	function getObjectType()
	{
		return 'PortalRow';
	}

	function getToolName()
	{
		return 'PortalRows';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'WebBuilder Portal Rows';
	}

	function getAllObjects($page, $recordsPerPage)
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

	function getDefaultSort()
	{
		return 'weight asc';
	}

	function getObjectStructure()
	{
		return PortalRow::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		if (!empty($this->activeObject) && $this->activeObject instanceof PortalRow){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages?objectAction=edit&id=' . $this->activeObject->portalPageId , 'Custom Page');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Row Content');
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