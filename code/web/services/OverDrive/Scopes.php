<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';

class OverDrive_Scopes extends ObjectEditor {
	function getObjectType(): string {
		return 'OverDriveScope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'OverDrive';
	}

	function getPageTitle(): string {
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		return $readerName . ' Scopes';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new OverDriveScope();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return OverDriveScope::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', $readerName);
		$breadcrumbs[] = new Breadcrumb('/OverDrive/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'overdrive';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Libby/Sora');
	}
}