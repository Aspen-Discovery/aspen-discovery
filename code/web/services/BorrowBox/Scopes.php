<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxScope.php';

class BorrowBox_Scopes extends ObjectEditor {
	function getObjectType(): string {
		return 'BorrowBoxScope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'BorrowBox';
	}

	function getPageTitle(): string {
		return 'BorrowBox Scopes';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new BorrowBoxScope();
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
		return BorrowBoxScope::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#borrowbox', 'BorrowBox');
		$breadcrumbs[] = new Breadcrumb('/BorrowBox/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'borrowbox';
	}

	public function getViewPermissions(): array {
		return ['Administer BorrowBox'];
	}

	function canAddNew(): bool {
		return false;
	}

	public function getRequiredModule(): ?string {
		return 'BorrowBox';
	}
}
