<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Development/AspenRelease.php';

class Development_AspenReleases extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenRelease';
	}

	function getToolName(): string {
		return 'AspenReleases';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Aspen Releases';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new AspenRelease();
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
		return 'name desc';
	}

	function getObjectStructure($context = ''): array {
		return AspenRelease::getObjectStructure($context);
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
		return false;
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home#greenhouse-maintenance-tools', 'Maintenance Tools');
		$breadcrumbs[] = new Breadcrumb('/Development/AspenReleases', 'Aspen Releases');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'development';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	public function getViewPermissions() : array {
		return [];
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Greenhouse/greenhouse-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

}