<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFormatSortingGroup.php';

class Admin_GroupedWorkFormatSorting extends ObjectEditor {
	function getObjectType(): string {
		return 'GroupedWorkFormatSortingGroup';
	}

	function getToolName(): string {
		return 'GroupedWorkFormatSorting';
	}

	function getPageTitle(): string {
		return 'Grouped Work Format Sorting';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new GroupedWorkFormatSortingGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Format Sorting')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->id = $library->groupedWorkDisplaySettingId;
			$groupedWorkDisplaySettings->find(true);
			$object->id = $groupedWorkDisplaySettings->formatSortingGroupId;
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
		return GroupedWorkFormatSortingGroup::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/GroupedWorkFormatSorting', 'Grouped Work Format Sorting');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Format Sorting',
			'Administer Library Format Sorting',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Format Sorting',
		]);
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.initializeFormatSort();';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (isset($existingObject) && $existingObject != null) {
			$objectActions[] = [
				'text' => 'Update Active Formats',
				'url' => '/Admin/GroupedWorkFormatSorting?objectAction=loadActiveFormats&id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function loadActiveFormats(){
		$id = $_REQUEST['id'];
		if (!empty($id) && is_numeric($id)) {
			$formatSortingGroup = new GroupedWorkFormatSortingGroup();
			$formatSortingGroup->id = $id;
			if ($formatSortingGroup->find(true)) {
				$formatSortingGroup->loadDefaultFormats();
			}
		}
		$structure = $this->getObjectStructure();
		$structure = $this->applyPermissionsToObjectStructure($structure);
		$this->viewIndividualObject($structure);
	}
}