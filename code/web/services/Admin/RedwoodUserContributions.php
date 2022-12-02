<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Redwood/UserContribution.php';

class Admin_RedwoodUserContributions extends ObjectEditor {
	function getObjectType(): string {
		return 'UserContribution';
	}

	function getToolName(): string {
		return 'RedwoodUserContributions';
	}

	function getPageTitle(): string {
		return 'Submit Material to the Archive';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new UserContribution();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'dateContributed desc';
	}

	function getObjectStructure(): array {
		return UserContribution::getObjectStructure();
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

	function getBreadcrumbs(): array {
		return [];
	}

	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Admin/admin-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, '', false);
	}

	function getActiveAdminSection(): string {
		return '';
	}

	function canView(): bool {
		return true;
	}
}