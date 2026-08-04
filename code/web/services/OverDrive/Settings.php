<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';

class OverDrive_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'OverDriveSetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'OverDrive';
	}

	function getPageTitle(): string {
		return 'OverDrive Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new OverDriveSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
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

	function getObjectStructure($context = ''): array {
		return OverDriveSetting::getObjectStructure($context);
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
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/248217616/eContent';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', 'OverDrive');
		$breadcrumbs[] = new Breadcrumb('/OverDrive/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'overdrive';
	}

	public function getViewPermissions() : array {
		return ['Administer Libby/Sora'];
	}

	public function getRequiredModule(): ?string {
		return 'OverDrive';
	}
}