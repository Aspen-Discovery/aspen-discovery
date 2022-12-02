<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';

class AspenLiDA_BrandedAppSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'BrandedAppSetting';
	}

	function getToolName(): string {
		return 'BrandedAppSettings';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function getPageTitle(): string {
		return 'Branded App Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new BrandedAppSetting();
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
		return BrandedAppSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/BrandedAppSettings', 'Branded App Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Aspen LiDA Settings');
	}

}