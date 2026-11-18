<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenPWA/FirebaseSetting.php';

class AspenPWA_FirebaseSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'FirebaseSetting';
	}

	function getToolName(): string {
		return 'FirebaseSettings';
	}

	function getModule(): string {
		return 'AspenPWA';
	}

	function getPageTitle(): string {
		return 'Firebase Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new FirebaseSetting();
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return FirebaseSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen-mobile', 'Aspen Progressive Web Application(PWA)');
		$breadcrumbs[] = new Breadcrumb('/AspenPWA/FirebaseSettings', 'Firebase Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'AspenPWA';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Firebase Settings');
	}

	function getViewPermissions() : array {
		return ['Administer Firebase Settings'];
	}
}
