<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';

class CloudLibrary_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'CloudLibrarySetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'CloudLibrary';
	}

	function getPageTitle(): string {
		return 'cloudLibrary Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new CloudLibrarySetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
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
		return CloudLibrarySetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloud_library', 'cloudLibrary');
		$breadcrumbs[] = new Breadcrumb('/CloudLibrary/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cloud_library';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Cloud Library');
	}
}