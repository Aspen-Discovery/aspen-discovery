<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventField.php';

class Events_EventFields extends ObjectEditor {
	function getObjectType(): string {
		return 'EventField';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'EventFields';
	}

	function getPageTitle(): string {
		return 'Event Fields';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new EventField();
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
		return EventField::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/308477977/Events';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Events.toggleEventFieldAllowableValues();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/EventFields', 'Events Fields');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	public function getViewPermissions() : array {
		return ['Administer Field Sets'];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Field Sets']);
	}

	public function getRequiredModule(): ?string {
		return 'Events';
	}
}