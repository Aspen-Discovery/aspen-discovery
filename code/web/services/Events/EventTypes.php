<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventType.php';

class Events_EventTypes extends ObjectEditor {
	function getObjectType(): string {
		return 'EventType';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'EventTypes';
	}

	function getPageTitle(): string {
		return 'Event Types';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new EventType();
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
		return 'title asc';
	}

	function getObjectStructure($context = ''): array {
		return EventType::getObjectStructure($context);
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

	function getOnSubmissionJS(): string {
		return 'AspenDiscovery.Events.checkEventsForType(submitForm)';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/EventTypes', 'Events Types');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	public function getViewPermissions() : array {
		return ['Administer Event Types'];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Event Types']);
	}

	public function getRequiredModule(): ?string {
		return 'Events';
	}
}