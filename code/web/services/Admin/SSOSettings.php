<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

class SSOSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'SSOSetting';
	}

	function getToolName(): string {
		return 'SSOSettings';
	}

	function getPageTitle(): string {
		return 'Single Sign-on Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new SSOSetting();
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
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return SSOSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/SSOSettings', 'Single Sign-on Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Single Sign-on');
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.getSSOFields();';
	}
}