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

	function getAllObjects(int $page, int $recordsPerPage): array {
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

	protected function getDefaultRecordsPerPage() : int {
		return 50;
	}

	function getDefaultFilters(array $filterFields): array {
		return [
			'name' => [
				'fieldName' => 'name',
				'filterType' => 'text',
				'filterValue' => '',
				'field' => $filterFields['name'],
			],
		];
	}

	function getObjectStructure($context = ''): array {
		return Module::getObjectStructure($context);
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

	function canAddNew() : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

	function canCompare() : bool {
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