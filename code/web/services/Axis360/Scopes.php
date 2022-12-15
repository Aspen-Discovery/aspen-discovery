<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Scope.php';

class Axis360_Scopes extends ObjectEditor {
	function getObjectType(): string {
		return 'Axis360Scope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'Axis360';
	}

	function getPageTitle(): string {
		return 'Axis 360 Scopes';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Axis360Scope();
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
		return Axis360Scope::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		if (!empty($this->activeObject) && $this->activeObject instanceof Axis360Scope) {
			$breadcrumbs[] = new Breadcrumb('/Axis360/Settings?objectAction=edit&id=' . $this->activeObject->settingId, 'Settings');
		}
		$breadcrumbs[] = new Breadcrumb('/Axis360/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'axis360';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Axis 360');
	}
}