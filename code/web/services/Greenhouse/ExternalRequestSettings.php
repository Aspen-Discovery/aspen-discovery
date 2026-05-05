<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Greenhouse/ExternalRequestSettings.php';

class Greenhouse_ExternalRequestSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'ExternalRequestSettings';
	}

	function getToolName(): string {
		return 'ExternalRequestSettings';
	}

	function getModule(): string {
		return 'Greenhouse';
	}

	function getPageTitle(): string {
		return 'External Request Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new ExternalRequestSettings();
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
		return 'id';
	}

	function getObjectStructure($context = ''): array {
		return ExternalRequestSettings::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canDelete(): bool {
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
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home#greenhouse-logging', 'Logging');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/ExternalRequestSettings', 'External Request Settings');
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

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Greenhouse/greenhouse-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

	public function getRequiredModule(): ?string {
		return 'Greenhouse';
	}
}