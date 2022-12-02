<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Account/AccountProfile.php';

class Admin_AccountProfiles extends ObjectEditor {
	function getObjectType(): string {
		return 'AccountProfile';
	}

	function getToolName(): string {
		return 'AccountProfiles';
	}

	function getPageTitle(): string {
		return 'Account Profiles';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new AccountProfile();
		$object->orderBy($this->getSort() . ', name');
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'weight asc';
	}

	function getObjectStructure(): array {
		return AccountProfile::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('', 'Account Profiles');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Account Profiles');
	}
}