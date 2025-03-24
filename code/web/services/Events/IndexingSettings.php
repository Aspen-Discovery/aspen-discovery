<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventsIndexingSetting.php';

class Events_IndexingSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'EventsIndexingSetting';
	}

	function getToolName(): string {
		return 'IndexingSettings';
	}

	function getModule(): string {
		return 'Events';
	}

	function getPageTitle(): string {
		return 'Aspen Events Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new EventsIndexingSetting();
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
		return EventsIndexingSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/IndexingSettings', 'Indexing Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Events for All Locations']);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Events for All Locations']);
	}
}