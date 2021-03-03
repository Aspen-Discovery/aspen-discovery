<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';

class WebBuilder_BasicPages extends ObjectEditor
{
	function getObjectType()
	{
		return 'BasicPage';
	}

	function getToolName()
	{
		return 'BasicPages';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Basic WebBuilder Pages';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new BasicPage();
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
		return BasicPage::getObjectStructure();
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
		if (!empty($existingObject) && $existingObject instanceof BasicPage && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/BasicPage?id='.$existingObject->id: $existingObject->urlAlias,
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
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/BasicPages', 'Basic Pages');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Basic Pages', 'Administer Library Basic Pages']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}