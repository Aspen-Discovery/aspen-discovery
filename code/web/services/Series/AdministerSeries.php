<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Series/Series.php';

class Series_AdministerSeries extends ObjectEditor {
	function getObjectType(): string {
		return 'Series';
	}

	function getToolName(): string {
		return 'AdministerSeries';
	}

	function getModule(): string {
		return 'Series';
	}

	function getPageTitle(): string {
		return 'Administer Series';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Series();
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
		return 'created desc';
	}

	function getObjectStructure($context = ''): array {
		return Series::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof Series && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View',
				'url' => '/Series/' . $existingObject->id
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#series', 'Series');
		$breadcrumbs[] = new Breadcrumb('/Series/AdministerSeries', 'Administer Series');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'series';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Series');
	}

	function canAddNew(): bool{
		return UserAccount::userHasPermission('Administer Series');
	}

	function getDefaultFilters(array $filterFields): array {
		return [
			'displayName' => [
				'fieldName' => 'displayName',
				'filterType' => 'text',
				'filterValue' => '',
				'field' => $filterFields['displayName'],
			],
			'author' => [
				'fieldName' => 'author',
				'filterType' => 'text',
				'filterValue' => '',
				'field' => $filterFields['author'],
			],
		];
	}
}