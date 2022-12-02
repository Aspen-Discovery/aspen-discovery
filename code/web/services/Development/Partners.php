<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

class Development_Partners extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenSite';
	}

	function getToolName(): string {
		return 'Partners';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Partners';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new AspenSite();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->siteType = 0;
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
		return 'name asc';
	}

	function getObjectStructure(): array {
		$structure = AspenSite::getObjectStructure();
		foreach ($structure as $propertyName => $property) {
			if (!in_array($property['property'], [
				'id',
				'name',
				'baseUrl',
			])) {
				unset($structure[$propertyName]);
			}
		}
		return $structure;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return false;
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
		$breadcrumbs[] = new Breadcrumb('/Development/Partners', 'Partners');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	protected function getDefaultRecordsPerPage() {
		return 100;
	}

	protected function showHistoryLinks() {
		return false;
	}

	function canBatchEdit() {
		return false;
	}

	public function canCompare() {
		return false;
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}