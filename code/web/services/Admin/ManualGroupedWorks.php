<?php

require_once ROOT_DIR . '/sys/Grouping/ManualGroupedWork.php';
require_once ROOT_DIR . '/sys/Grouping/ManuallyGroupedWorkRecord.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_ManualGroupedWorks extends ObjectEditor {
	function getObjectType(): string {
		return 'ManualGroupedWork';
	}

	function getToolName(): string {
		return 'ManualGroupedWorks';
	}

	function getPageTitle(): string {
		return 'Manual Grouped Works';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new ManualGroupedWork();
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
		return 'id desc';
	}

	function getObjectStructure($context = ''): array {
		return ManualGroupedWork::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/grouping';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/ManualGroupedWorks', 'Manual Grouped Works');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}

	function canAddNew(): bool {
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Manually Group and Ungroup Works');
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$actions = parent::getAdditionalObjectActions($existingObject);
		/** @var ManualGroupedWork $existingObject */
		$permanentId = ManualGroupedWork::returnGroupedWorkPermanentId($existingObject->getGroupedWorkPermanentId());
		if (!empty($permanentId)) {
			$actions[] = [
				'text' => 'View Grouped Work',
				'url' => "/GroupedWork/$permanentId",
				'target' => '_blank',
			];
		}
		return $actions;
	}
}