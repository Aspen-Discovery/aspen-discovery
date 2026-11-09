<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaSetting.php';

class Omeka_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'OmekaSetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'Omeka';
	}

	function getPageTitle(): string {
		return 'Omeka Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new OmekaSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
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
		return OmekaSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() : bool {
		return true;
	}

	function canDelete() : bool {
		return true;
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
		$breadcrumbs[] = new Breadcrumb('/Omeka/Settings', 'Settings');
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
