<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/BorrowBox/BorrowBoxSetting.php';

class BorrowBox_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'BorrowBoxSetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'BorrowBox';
	}

	function getPageTitle(): string {
		return 'BorrowBox Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new BorrowBoxSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
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
		return BorrowBoxSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/integration/econtent';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#borrowbox', 'BorrowBox');
		$breadcrumbs[] = new Breadcrumb('/BorrowBox/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'borrowbox';
	}

	public function getViewPermissions(): array {
		return ['Administer BorrowBox'];
	}

	public function getRequiredModule(): ?string {
		return 'BorrowBox';
	}
}
