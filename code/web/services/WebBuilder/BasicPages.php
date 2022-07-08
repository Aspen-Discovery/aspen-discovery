<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';

class WebBuilder_BasicPages extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'BasicPage';
	}

	function getToolName() : string
	{
		return 'BasicPages';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'Basic Web Builder Pages';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new BasicPage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Basic Pages')){
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryBasicPage', 'basicPageId');
		}
		$objectList = array();
		if ($userHasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'title asc';
	}
	function getObjectStructure() : array
	{
		return BasicPage::getObjectStructure();
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
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof BasicPage && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/BasicPage?id='.$existingObject->id: $existingObject->urlAlias,
			];
		}
		return $objectActions;
	}

	function getInstructions() : string
	{
		return 'https://help.aspendiscovery.org/help/webbuilder/pages';
	}

	function getInitializationJs() : string
	{
		return 'AspenDiscovery.WebBuilder.updateWebBuilderFields()';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/BasicPages', 'Basic Pages');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Basic Pages', 'Administer Library Basic Pages']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}