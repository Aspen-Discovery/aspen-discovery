<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';

class Greenhouse_ObjectHistoryLog extends ObjectEditor {
	function getObjectType(): string {
		return 'DataObjectHistory';
	}

	function getToolName(): string {
		return 'ObjectHistoryLog';
	}

	function getModule(): string {
		return 'Greenhouse';
	}

	function getPageTitle(): string {
		return 'Object History Log (previous 90 days)';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new DataObjectHistory();
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
		return 'changeDate desc';
	}

	function getObjectStructure($context = ''): array {
		return DataObjectHistory::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() : bool {
		return false;
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
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home#greenhouse-logging', 'Logging');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/ObjectHistoryLog', 'Object History Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
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
		return ['Aspen Admin'];
	}

	protected function getDefaultRecordsPerPage() : int {
		return 100;
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Greenhouse/greenhouse-sidebar.tpl', $translateTitle = true): void {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

	public function getRequiredModule(): ?string {
		return 'Greenhouse';
	}
}