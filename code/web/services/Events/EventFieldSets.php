<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';
require_once ROOT_DIR . '/sys/Events/EventFieldSet.php';

class Events_EventFieldSets extends ObjectEditor {
	function getObjectType(): string {
		return 'EventFieldSet';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'EventFieldSets';
	}

	function getPageTitle(): string {
		return 'Event Field Sets';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new EventFieldSet();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$list = [];
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return EventFieldSet::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/catalog/events';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/EventFieldSets', 'Events Field Sets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Field Sets']);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Field Sets']);
	}
}