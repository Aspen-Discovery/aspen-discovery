<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Module.php';

class Admin_Modules extends ObjectEditor {
	function getObjectType(): string {
		return 'Module';
	}

	function getToolName(): string {
		return 'Modules';
	}

	function getPageTitle(): string {
		return 'Aspen Discovery Modules';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new Module();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure(): array {
		return Module::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Modules');
	}

	function canAddNew() {
		return false;
	}

	function canDelete() {
		return false;
	}

	function canCompare() {
		return false;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Modules');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}
}