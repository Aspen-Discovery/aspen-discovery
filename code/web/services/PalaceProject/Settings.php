<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectSetting.php';

class PalaceProject_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'PalaceProjectSetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'PalaceProject';
	}

	function getPageTitle(): string {
		return 'Palace Project Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new PalaceProjectSetting();
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

	function getObjectStructure($context = ''): array {
		return PalaceProjectSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#palace_project', 'Palace Project');
		$breadcrumbs[] = new Breadcrumb('/PalaceProject/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'palace_project';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Palace Project');
	}
}