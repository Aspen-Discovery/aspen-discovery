<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/AspenEventAttendeeCategory.php';

class Events_AttendeeCategories extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenEventAttendeeCategory';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'AttendeeCategories';
	}

	function getPageTitle(): string {
		return 'Attendee Categories';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new AspenEventAttendeeCategory();
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
		return AspenEventAttendeeCategory::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/AttendeeCategories', 'Attendee Categories');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	public function getViewPermissions(): array {
		return ['Administer Event Types'];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Event Types']);
	}

	public function getRequiredModule(): ?string {
		return 'Events';
	}
}
