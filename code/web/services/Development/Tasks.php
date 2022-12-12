<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Development/DevelopmentTask.php';

class Development_Tasks extends ObjectEditor {
	function getObjectType(): string {
		return 'DevelopmentTask';
	}

	function getToolName(): string {
		return 'Tasks';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Development Tasks';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new DevelopmentTask();
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

	function getObjectStructure($context = ''): array {
		return DevelopmentTask::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Development/Tasks', 'Tasks');
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

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

}