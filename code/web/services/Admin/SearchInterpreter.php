<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SearchObject/SearchInterpreterSetting.php';

class Admin_SearchInterpreter extends ObjectEditor {
	function getObjectType(): string {
		return 'SearchInterpreterSetting';
	}

	function getToolName(): string {
		return 'SearchInterpreter';
	}

	function getPageTitle(): string {
		return 'Search Interpreter Settings';
	}

	function canDelete(): bool {
		return false;
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new SearchInterpreterSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return SearchInterpreterSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/SearchInterpreter', 'Search Interpreter');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer Search Interpreter',
		]);
	}

	function canAddNew(): bool {
		return $this->getNumObjects() == 0;
	}
}