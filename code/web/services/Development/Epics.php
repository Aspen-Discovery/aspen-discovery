<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Development/DevelopmentEpic.php';

class Development_Epics extends ObjectEditor {
	function getObjectType(): string {
		return 'DevelopmentEpic';
	}

	function getToolName(): string {
		return 'Epics';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Development Epics';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new DevelopmentEpic();
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
		return 'name';
	}

	function getObjectStructure(): array {
		return DevelopmentEpic::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Development/Epics', 'Epics');
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
		//TODO: Apply default filters
		return [

		];
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}