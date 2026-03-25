<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';

class EBSCO_EBSCOhostSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'EBSCOhostSetting';
	}

	function getToolName(): string {
		return 'EBSCOhostSettings';
	}

	function getModule(): string {
		return 'EBSCO';
	}

	function getPageTitle(): string {
		return 'EBSCOhost Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new EBSCOhostSetting();
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
		return EBSCOhostSetting::getObjectStructure($context);
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
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/344522765/Databases';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebscohost', 'EBSCOhost');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EBSCOhostSettings', 'EBSCOhost Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ebscohost';
	}

	public function getViewPermissions() : array {
		return ['Administer EBSCOhost Settings'];
	}

	function viewIndividualObject($structure): void {
		//Update the list of databases when the user edits
		$id = $_REQUEST['id'] ?? '';
		if (!empty($id) && $id > 0) {
			/** @var EBSCOhostSetting $curObject */
			$curObject = $this->getExistingObjectById($id);
			$searchSettings = $curObject->getSearchSettings();
			foreach ($searchSettings as $searchSetting) {
				$searchSetting->updateDatabasesFromEBSCOhost();
			}
		}
		parent::viewIndividualObject($structure);
	}

	public function getRequiredModule(): ?string {
		return 'EBSCOhost';
	}
}