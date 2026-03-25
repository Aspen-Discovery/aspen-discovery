<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/RenaissanceLearning/ARSetting.php';

class Enrichment_ARSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'ARSetting';
	}

	function getToolName(): string {
		return 'ARSettings';
	}

	function getModule(): string {
		return 'Enrichment';
	}

	function getPageTitle(): string {
		return 'Accelerated Reader Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new ARSetting();
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
		return ARSetting::getObjectStructure($context);
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
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/328105985/Third+Party+Enrichment';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/ARSettings', 'Accelerated Reader Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'third_party_enrichment';
	}

	public function getViewPermissions() : array {
		return ['Administer Third Party Enrichment API Keys'];
	}

	function canAddNew() : bool {
		return $this->getNumObjects() == 0;
	}
}