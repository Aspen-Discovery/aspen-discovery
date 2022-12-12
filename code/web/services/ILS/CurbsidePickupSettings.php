<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';

class ILS_CurbsidePickupSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'CurbsidePickupSetting';
	}

	function getModule(): string {
		return "ILS";
	}

	function getToolName(): string {
		return 'CurbsidePickupSettings';
	}

	function getPageTitle(): string {
		return 'Curbside Pickup Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new CurbsidePickupSetting();
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
		return CurbsidePickupSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/CurbsidePickupSettings', 'Curbside Pickup Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ils_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Curbside Pickup');
	}
}