<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';

class AspenLiDA_SelfCheckSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenLiDASelfCheckSetting';
	}

	function getToolName(): string {
		return 'SelfCheckSettings';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function getPageTitle(): string {
		return 'Self-Check Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new AspenLiDASelfCheckSetting();
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
		return AspenLiDASelfCheckSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/SelfCheckSettings', 'Self-Check Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Aspen LiDA Settings');
	}

}