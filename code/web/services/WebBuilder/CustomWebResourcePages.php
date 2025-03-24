<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePage.php';

class WebBuilder_CustomWebResourcePages extends ObjectEditor {
	function getObjectType(): string {
		return 'CustomWebResourcePage';
	}

	function getToolName(): string {
		return 'CustomWebResourcePages';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Custom Web Resource Pages';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new CustomWebResourcePage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Custom Web Resource Pages')) {
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryCustomWebResourcePage', 'customResourcePageId');
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
		return CustomWebResourcePage::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof CustomWebResourcePage && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/CustomWebResourcePage?id=' . $existingObject->id : $existingObject->urlAlias,
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
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomWebResourcePages', 'Custom Web Resource Pages');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Custom Web Resource Pages',
			'Administer Library Custom Web Resource Pages',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Custom Web Resource Pages',
		]);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	public function canCopy() {
		return $this->canAddNew();
	}
}