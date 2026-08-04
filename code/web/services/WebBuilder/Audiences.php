<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';

class WebBuilder_Audiences extends ObjectEditor {
	function getObjectType(): string {
		return 'WebBuilderAudience';
	}

	function getToolName(): string {
		return 'Audiences';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Audiences';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new WebBuilderAudience();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return WebBuilderAudience::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof WebBuilderAudience && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View',
				'url' => '/WebBuilder/ResourceAudience?id=' . $existingObject->id
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/232816697/Audiences+Categories';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Audiences', 'Audiences');
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return ['Administer All Web Categories'];
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	public function hasRecordLocking() : bool {
		return true;
	}

	public function getRequiredModule(): ?string {
		return 'Web Builder';
	}
}