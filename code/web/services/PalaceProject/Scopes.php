<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectScope.php';

class PalaceProject_Scopes extends ObjectEditor {
	function getObjectType(): string {
		return 'PalaceProjectScope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'PalaceProject';
	}

	function getPageTitle(): string {
		return 'Palace Project Scopes';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new PalaceProjectScope();
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
		return PalaceProjectScope::getObjectStructure($context);
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
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#palace_project', 'PalaceProject');
		$breadcrumbs[] = new Breadcrumb('/PalaceProject/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'palace_project';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Palace Project');
	}
}