<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/GrapesPage.php';

class WebBuilder_GrapesPages extends ObjectEditor {
	function getObjectType(): string {
		return 'GrapesPage';
	}

	function getToolName(): string {
		return 'GrapesPages';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Grapes Web Builder Pages';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new GrapesPage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Grapes Pages')) {
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryBasicPage', 'basicPageId');
		}
		$objectList = [];
		if ($userHasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'title asc';
	}

	function getObjectStructure($context = ''): array {
		return GrapesPage::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof GrapesPage) {
			$objectActions[] = [
				'text' => 'Open Editor',
				'url' => '/WebBuilder/GrapesJSEditor?objectAction=edit&id=' . $existingObject->id . '&templateId=' . $existingObject->templatesSelect,
			];
		}
		return $objectActions;	
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/webbuilder/pages';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.WebBuilder.updateWebBuilderFields()';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/GrapesPages', 'Grapes Pages');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Basic Pages',
			'Administer Library Basic Pages',
		]);
	}

	function canBatchEdit(): bool {
		return false;
	}

 
	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	public function canAddNew(){
    	return true;
	}

	public function canCopy() {
		return true;
	}

	public function canDelete() {
    	return true;
	}

	public function canExportToCSV() {
    	return false;
	}
}