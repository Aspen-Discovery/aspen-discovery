<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Series/SeriesIndexingSettings.php';

class Series_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'SeriesIndexingSettings';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'Series';
	}

	function getPageTitle(): string {
		return 'Series Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new SeriesIndexingSettings();
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
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return SeriesIndexingSettings::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#series', 'Series');
		$breadcrumbs[] = new Breadcrumb('/Series/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'series';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Series');
	}

	function canAddNew() {
		return $this->getNumObjects() == 0;
	}
}