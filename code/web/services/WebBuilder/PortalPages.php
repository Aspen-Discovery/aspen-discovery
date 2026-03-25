<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';

class WebBuilder_PortalPages extends ObjectEditor {
	function launch(): void {
		global $interface;
		$interface->assign('inPageEditor', true);
		parent::launch();
	}

	function getObjectType(): string {
		return 'PortalPage';
	}

	function getToolName(): string {
		return 'PortalPages';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Custom Web Builder Pages';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new PortalPage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer All Custom Pages')) {
			$userHasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryPortalPage', 'portalPageId');
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
		return PortalPage::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof PortalPage && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/PortalPage?id=' . $existingObject->id : $existingObject->urlAlias,
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/239206444/Basic+and+Custom+Pages';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.WebBuilder.updateWebBuilderFields()';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages', 'Custom Pages');
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Custom Pages',
		]);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	function viewIndividualObject($structure) : void {
		global $interface;
		$interface->assign('previewMode', true);
		parent::viewIndividualObject($structure);
	}

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function getCopyNotes() : string {
		return '/admin_instructions/portal_page_copy.MD';
	}

	public function hasRecordLocking() : bool {
		return true;
	}

	public function getRequiredModule(): ?string {
		return 'Web Builder';
	}
}