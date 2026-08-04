<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/LocalHopSetting.php';

class Events_LocalHopSettings extends ObjectEditor {

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType(): string {
		return 'LocalHopSetting';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName(): string {
		return 'LocalHopSettings';
	}

	function getModule(): string {
		return 'Events';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle(): string {
		return 'LocalHop Settings';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new LocalHopSetting();
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

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure($context = ''): array {
		return LocalHopSetting::getObjectStructure($context);
	}

	/**
	 * The name of the column which defines this as unique
	 */
	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	/**
	 * The id of the column which serves to join other columns
	 */
	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/LocalHopSettings', 'LocalHop Settings');
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return ['Administer LocalHop Settings'];
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	public function getRequiredModule(): ?string {
		return 'Events';
	}
}