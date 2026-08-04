<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/AspenEventSetting.php';

class Events_AspenEventSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenEventSetting';
	}

	function getToolName(): string {
		return 'AspenEventSettings';
	}

	function getModule(): string {
		return 'Events';
	}

	function getPageTitle(): string {
		return 'Aspen Event Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new AspenEventSetting();
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
		return AspenEventSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Events/AspenSettings', 'Aspen Settings');
		return $breadcrumbs;
	}

	function getViewPermissions(): array {
		return ['Administer Events for All Locations'];
	}

	function getActiveAdminSection(): string {
		return 'events';
	}
}