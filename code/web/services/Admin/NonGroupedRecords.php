<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_NonGroupedRecords extends ObjectEditor {
	function getObjectType(): string {
		return 'NonGroupedRecord';
	}

	function getToolName(): string {
		return 'NonGroupedRecords';
	}

	function getPageTitle(): string {
		return 'Records to Not Group';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new NonGroupedRecord();
		$object->orderBy($this->getSort() . ', recordId');
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
		return 'source asc';
	}

	function getObjectStructure($context = ''): array {
		return NonGroupedRecord::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/420741126/Grouping+and+Ungrouping+Records';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/NonGroupedRecords', 'Records To Not Group');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	public function getViewPermissions() : array {
		return ['Manually Group and Ungroup Works'];
	}
}