<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/ChiliFreshSetting.php';

class Enrichment_ChiliFreshSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'ChiliFreshSetting';
	}

	function getToolName(): string {
		return 'ChiliFreshSettings';
	}

	function getModule(): string {
		return 'Enrichment';
	}

	function getPageTitle(): string {
		return 'ChiliFresh Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new ChiliFreshSetting();
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
		return ChiliFreshSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/328105985/Third+Party+Enrichment';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/ChiliFreshSettings', 'ChiliFresh Settings');
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