<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Gale/GaleSetting.php';

class Gale_GaleSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'GaleSetting';
	}

	function getToolName(): string {
		return 'GaleSettings';
	}

	function getModule(): string {
		return 'Gale';
	}

	function getPageTitle(): string {
		return 'Gale Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new GaleSetting();
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return GaleSetting::getObjectStructure($context);
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
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/534741003/Gale';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#gale', 'Gale');
		$breadcrumbs[] = new Breadcrumb('/Gale/GaleSettings', 'Gale Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'gale';
	}

	function getViewPermissions(): array {
		return ['Administer Gale'];
	}

	function viewIndividualObject($structure): void {
		//Update the list of databases when the user edits
		$id = $_REQUEST['id'] ?? '';
		if (!empty($id) && $id > 0) {
			/** @var GaleSetting $curObject */
			$curObject = $this->getExistingObjectById($id);
		}
		parent::viewIndividualObject($structure);
	}
}