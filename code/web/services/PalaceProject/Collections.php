<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectSetting.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectCollection.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class PalaceProject_Collections extends ObjectEditor {
	function getObjectType(): string {
		return 'PalaceProjectCollection';
	}

	function getToolName(): string {
		return 'Collections';
	}

	function getModule(): string {
		return 'PalaceProject';
	}

	function getPageTitle(): string {
		return 'Palace Project Collections';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new PalaceProjectCollection();
		if (isset($_REQUEST['settingId'])) {
			$settingId = $_REQUEST['settingId'];
			$object->settingId = $settingId;
		}
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
		return 'palaceProjectName asc';
	}

	function getObjectStructure($context = ''): array {
		return PalaceProjectCollection::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return true;
	}

	function canBatchEdit() {
		return false;
	}

	function canEdit(DataObject $object) {
		return false;
	}

	function canEditList() {
		return false;
	}

	function canDelete() {
		return true;
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/integration/econtent';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#palace_project', 'Palace Project');
		if (isset($_REQUEST['settingId'])) {
			$breadcrumbs[] = new Breadcrumb('/PalaceProject/Settings?objectAction=edit&id=' . $this->activeObject->settingId, 'Settings');
			$breadcrumbs[] = new Breadcrumb('/PalaceProject/Collections?settingId=' . $this->activeObject->settingId, 'All Collections');
		}else{
			$breadcrumbs[] = new Breadcrumb('/PalaceProject/Settings', 'All Settings');
		}
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'palace_project';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Palace Project');
	}
}