<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';

class WebBuilder_PortalPages extends ObjectEditor
{
	function getObjectType()
	{
		return 'PortalPage';
	}

	function getToolName()
	{
		return 'PortalPages';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'WebBuilder Custom Pages';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new PortalPage();
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
		return 'title asc';
	}

	function getObjectStructure()
	{
		return PortalPage::getObjectStructure();
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
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof PortalPage && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/PortalPage?id='.$existingObject->id: $existingObject->urlAlias,
			];
		}
		return $objectActions;
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
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages', 'Custom Pages');
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