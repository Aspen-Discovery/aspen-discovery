<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Events/CalendarDisplaySetting.php';

class Events_CalendarDisplaySettings extends ObjectEditor {

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType(): string {
		return 'CalendarDisplaySetting';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName(): string {
		return 'CalendarDisplaySettings';
	}

	function getModule(): string {
		return 'Events';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle(): string {
		return 'Calendar Display Settings';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new CalendarDisplaySetting();
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
		return CalendarDisplaySetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Events/CalendarDisplaySettings', 'Calendar Display Settings');
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return ['Print Calendars with Header Images and Footer'];
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$actions = parent::getAdditionalObjectActions($existingObject);
		/** @var CalendarDisplaySetting $existingObject */
		if ($existingObject != null && $existingObject->id) {
			$actions[] = [
				'text' => 'View Calendar',
				'url' => '/Events/Calendar',
				'target' => '_blank',
			];
			$actions[] = [
				'text' => '<i class="fas fa-code" role="presentation"></i> Embed Calendar',
				'url' => '/Events/CalendarDisplaySettings?objectAction=view&id=' . $existingObject->id,
			];
		}
		return $actions;
	}

	public function getRequiredModule(): ?string {
		return 'Events';
	}

	function launch(): void {
		global $interface;

		$objectAction = $_REQUEST['objectAction'] ?? 'list';

		if ($objectAction === 'view') {
			if (isset($_REQUEST['id'])) {
				$object = new CalendarDisplaySetting();
				$object->id = $_REQUEST['id'];
				if ($object->find(true)) {
					$interface->assign('object', $object);
				}
			}
			$interface->assign('width', 900);
			$interface->assign('height', 700);
			$interface->assign('returnToListUrl', $this->getReturnToListUrl());
			$interface->setTemplate('../Admin/embeddedEventCalendar.tpl');
		} else {
			parent::launch();
			exit();
		}

		$this->display($interface->getTemplate(), 'Embeddable Calendar');
	}
}