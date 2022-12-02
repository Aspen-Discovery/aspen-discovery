<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDASetting.php';

class AspenLiDA extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenLiDASetting';
	}

	function getToolName(): string {
		return 'AspenLiDA';
	}

	function getPageTitle(): string {
		return 'Aspen LiDA Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new AppSetting();
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
		return 'slugName asc';
	}

	function getObjectStructure(): array {
		return AppSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/AspenLiDA', 'Aspen LiDA Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Aspen LiDA Settings');
	}

	function canAddNew() {
		return $this->getNumObjects() <= 0;
	}
}