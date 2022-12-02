<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Development/DevelopmentSprint.php';

class Development_Sprints extends ObjectEditor {
	function getObjectType(): string {
		return 'DevelopmentSprint';
	}

	function getToolName(): string {
		return 'Sprints';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Development Sprints';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new DevelopmentSprint();
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
		return 'startDate';
	}

	function getObjectStructure(): array {
		return DevelopmentSprint::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return true;
	}

	function canDelete() {
		return false;
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Development/Sprints', 'Sprints');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'development';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	function getDefaultFilters(array $filterFields): array {
		return [
			'active' => [
				'fieldName' => 'active',
				'filterType' => 'checkbox',
				'filterValue' => true,
				'filterValue2' => null,
				'field' => $filterFields['active'],
			],
		];
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}