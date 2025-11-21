<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkEContentSortingGroup.php';

class Admin_GroupedWorkEContentSorting extends ObjectEditor {
	function getObjectType(): string {
		return 'GroupedWorkEContentSortingGroup';
	}

	function getToolName(): string {
		return 'GroupedWorkEContentSorting';
	}

	function getPageTitle(): string {
		return 'Grouped Work eContent Sorting';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new GroupedWorkEContentSortingGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All eContent Sorting')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->id = $library->groupedWorkDisplaySettingId;
			$groupedWorkDisplaySettings->find(true);
			$object->id = $groupedWorkDisplaySettings->eContentSortingGroupId;
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return GroupedWorkEContentSortingGroup::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/GroupedWorkEContentSorting', 'Grouped Work eContent Sorting');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All eContent Sorting',
			'Administer Library eContent Sorting',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All eContent Sorting',
		]);
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateGroupedWorkEContentSortFields();';
	}
}