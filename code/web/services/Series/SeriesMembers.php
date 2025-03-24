<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Series/SeriesMember.php';

class Series_SeriesMembers extends ObjectEditor {
	function getObjectType(): string {
		return 'SeriesMember';
	}

	function getToolName(): string {
		return 'SeriesMembers';
	}

	function getModule(): string {
		return 'Series';
	}

	function getPageTitle(): string {
		return 'Series Member';
	}

	function getAllObjects($page, $recordsPerPage): array {
		if (empty($_REQUEST['id'])) {
			header('Location: /Series/AdministerSeries');
		}
		$object = new SeriesMember();
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
		return SeriesMember::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Series/AdministerSeries', 'Administer Series');
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