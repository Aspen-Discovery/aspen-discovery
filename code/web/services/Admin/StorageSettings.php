<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Storage/StorageSetting.php';

class Admin_StorageSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'StorageSetting';
	}

	function getToolName(): string {
		return 'StorageSettings';
	}

	function getPageTitle(): string {
		return 'Storage Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new StorageSetting();
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function canSort(): bool {
		return false;
	}

	function canAddNew(): bool {
		return true;
	}

	function canDelete(): bool {
		return true;
	}

	function getObjectStructure($context = ''): array {
		return StorageSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/StorageSettings', 'Storage Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	public function getViewPermissions(): array {
		return ['Administer Storage Settings'];
	}
}
