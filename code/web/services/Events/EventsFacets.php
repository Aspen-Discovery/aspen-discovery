<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';

class Events_EventsFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'EventsFacetGroup';
	}

	function getToolName(): string {
		return 'EventsFacets';
	}

	function getPageTitle(): string {
		return 'Events Facets';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new EventsFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Events Facets')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());

			//TODO: What do we want to do here? Do we want each grouped work display setting to apply to events too? (i.e.
			// academic event searches can have their own facet settings)
			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->id = $library->groupedWorkDisplaySettingId;
			$groupedWorkDisplaySettings->find(true);
			$object->id = $groupedWorkDisplaySettings->facetGroupId;
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return EventsFacetGroup::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/catalog/facets';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/EventsFacets', 'Events Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Events Facet Settings']);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Events Facet Settings']);
	}
}