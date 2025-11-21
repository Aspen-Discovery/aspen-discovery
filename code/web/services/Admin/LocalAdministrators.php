<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class admin_LocalAdministrators extends ObjectEditor {
	function getObjectType(): string {
		return 'User';
	}

	function getToolName(): string {
		return 'LocalAdministrators';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'Local Administrators';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		//We will only use the admin account profile
		$object = new User();
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
		return 'id desc';
	}

	function applyFilters(DataObject $object) : void {
		if ($object instanceof User) {
			$object->source = 'admin';
		}
		parent::applyFilters($object);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/LocalAdministrators', 'Local Administrators');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'admin';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manage Local Administrators');
	}

	public function getContext(): string {
		return 'localAdministrator';
	}

	function getObjectStructure($context = ''): array {
		return User::getObjectStructure($context);
	}

	function canBatchEdit() : bool {
		return false;
	}

	function canExportToCSV() : bool {
		return false;
	}

	function canCompare() : bool {
		return false;
	}


}