<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Development/DevelopmentTask.php';

class Development_Users extends ObjectEditor {
	function getObjectType(): string {
		return 'User';
	}

	function getToolName(): string {
		return 'Users';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Development Users';
	}

	function getAllObjects($page, $recordsPerPage): array {
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

	function applyFilters(DataObject $object) {
		$object->source = 'development';
		parent::applyFilters($object);
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
		return true;
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
		$breadcrumbs[] = new Breadcrumb('/Development/Users', 'Users');
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

	public function getContext(): string {
		return 'development';
	}

	function getObjectStructure($context = ''): array {
		return User::getObjectStructure($context);
	}
}