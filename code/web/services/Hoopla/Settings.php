<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Hoopla/HooplaSetting.php';

class Hoopla_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'HooplaSetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'Hoopla';
	}

	function getPageTitle(): string {
		return 'Hoopla Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new HooplaSetting();
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
		return 'apiUrl asc';
	}

	function getObjectStructure(): array {
		return HooplaSetting::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return true;
	}

	function canDelete() {
		return true;
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/integration/econtent';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#hoopla', 'Hoopla');
		$breadcrumbs[] = new Breadcrumb('/Hoopla/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'hoopla';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Hoopla');
	}
}