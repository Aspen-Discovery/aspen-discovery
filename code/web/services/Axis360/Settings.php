<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Axis360/Axis360Setting.php';

class Axis360_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'Axis360Setting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'Axis360';
	}

	function getPageTitle(): string {
		return 'Axis 360 Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Axis360Setting();
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
		return 'userInterfaceUrl asc';
	}

	function getObjectStructure($context = ''): array {
		return Axis360Setting::getObjectStructure($context);
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
		return 'https://help.aspendiscovery.org/help/integration/econtent';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#axis360', 'Axis 360');
		$breadcrumbs[] = new Breadcrumb('/Axis360/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'axis360';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Axis 360');
	}
}