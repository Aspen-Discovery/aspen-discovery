<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Administration/USPS.php';

class Admin_USPS extends ObjectEditor {
	function getObjectType(): string {
		return 'USPS';
	}

	function getToolName(): string {
		return 'USPS';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getPageTitle(): string {
		return 'USPS Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$variableList = [];

		$variable = new USPS();
		$variable->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$variable->find();
		while ($variable->fetch()) {
			$variableList[$variable->id] = clone $variable;
		}
		return $variableList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function canSort(): bool {
		return false;
	}

	function getObjectStructure($context = ''): array {
		return USPS::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return $this->getNumObjects() == 0;
	}

	function canDelete() {
		return true;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/USPS', 'USPS Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer System Variables');
	}
}