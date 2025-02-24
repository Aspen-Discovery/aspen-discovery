<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class Events_Events extends ObjectEditor {
	function getObjectType(): string {
		return 'Event';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'Events';
	}

	function getPageTitle(): string {
		return 'Events';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Event();
		$object->deleted = 0;
		$object->orderBy($this->getSort());
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::userHasPermission('Administer Events for All Locations')) {
			if (!UserAccount::userHasPermission('Administer Events for Home Library Locations')) {
				//Need to use where add here so the where add in below works properly
				$object->whereAdd("locationId = $user->homeLocationId");
			} else {
				//Scope to just locations for the user based on their home library
				$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
				$object->whereAdd("libraryId = $patronLibrary->libraryId");
			}
			$additionalAdministrationLocations = $user->getAdditionalAdministrationLocations();
			if (!empty($additionalAdministrationLocations)) {
				$object->whereAddIn('locationId', array_keys($additionalAdministrationLocations), false, 'OR');
			}
		}
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
		return 'startDate asc';
	}

	function getObjectStructure($context = ''): array {
		return Event::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Events/Events', 'Events');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function canView(): bool {
		if (SystemVariables::getSystemVariables()->enableAspenEvents) {
			return UserAccount::userHasPermission([
				'Administer Events for All Locations',
				'Administer Events for Home Library Locations',
				'Administer Events for Home Location'
			]);
		}
		return false;
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer Events for All Locations',
			'Administer Events for Home Library Locations',
			'Administer Events for Home Location'
		]);
	}

	public function hasMultiStepAddNew() : bool {
		return true;
	}
}