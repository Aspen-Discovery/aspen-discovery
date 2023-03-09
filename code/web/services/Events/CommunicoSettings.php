<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';

class Events_CommunicoSettings extends ObjectEditor {

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType(): string {
		return 'CommunicoSetting';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName(): string {
		return 'CommunicoSettings';
	}

	function getModule(): string {
		return 'Events';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle(): string {
		return 'Communico Settings';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects($page, $recordsPerPage): array {
		$object = new CommunicoSetting();
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
		return CommunicoSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Events/CommunicoSettings', 'Communico Settings');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Communico Settings');
	}

	function getActiveAdminSection(): string {
		return 'events';
	}
}