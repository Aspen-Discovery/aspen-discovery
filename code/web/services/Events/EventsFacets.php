<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';

class Events_EventsFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'EventsFacetGroup';
	}

	function getModule(): string {
		return 'Events';
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
		$hasExistingObjects = true;
		if (!UserAccount::userHasPermission('Administer Events Facet Settings')) {
			$hasExistingObjects = $this->limitToObjectsForLibrary($object, 'LibraryEventsFacetSetting', 'eventsFacetGroupId');
		}
		$list = [];
		if ($hasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
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