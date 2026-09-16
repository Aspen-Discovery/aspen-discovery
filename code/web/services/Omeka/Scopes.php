<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaScope.php';

class Omeka_Scopes extends ObjectEditor {
	function getObjectType(): string {
		return 'OmekaScope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'Omeka';
	}

	function getPageTitle(): string {
		return 'Omeka Scopes';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new OmekaScope();
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
		return OmekaScope::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#omeka', 'Omeka');
		$breadcrumbs[] = new Breadcrumb('/Omeka/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'omeka';
	}

	public function getViewPermissions() : array {
		return ['Administer Omeka'];
	}

	public function getRequiredModule(): ?string {
		return 'Omeka';
	}
}
