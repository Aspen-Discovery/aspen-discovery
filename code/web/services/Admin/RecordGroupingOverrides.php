<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/RecordGroupingOverride.php';

class Admin_RecordGroupingOverrides extends ObjectEditor {
	function getObjectType(): string {
		return 'RecordGroupingOverride';
	}

	function getToolName(): string {
		return 'RecordGroupingOverrides';
	}

	function getPageTitle(): string {
		return 'Record Grouping Overrides';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new RecordGroupingOverride();
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
		return 'date_added desc';
	}

	function canAddNew(): bool {
		return true;
	}

	function canDelete(): bool {
		return true;
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/grouping';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Cataloging');
		$breadcrumbs[] = new Breadcrumb('/Admin/RecordGroupingOverrides', 'Record Grouping Overrides');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function getObjectStructure($context = ''): array {
		return RecordGroupingOverride::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$actions = [];
		if ($existingObject && !empty($existingObject->grouped_work_permanent_id)) {
			$actions[] = [
				'text' => 'View Grouped Work',
				'url' => '/GroupedWork/' . $existingObject->grouped_work_permanent_id,
				'target' => '_blank',
				'icon' => 'fas fa-external-link-alt',
			];
		}
		return $actions;
	}
}
