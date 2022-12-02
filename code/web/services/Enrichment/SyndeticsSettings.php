<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';

class Enrichment_SyndeticsSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'SyndeticsSetting';
	}

	function getToolName(): string {
		return 'SyndeticsSettings';
	}

	function getModule(): string {
		return 'Enrichment';
	}

	function getPageTitle(): string {
		return 'Syndetics Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new SyndeticsSetting();
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

	function getObjectStructure(): array {
		return SyndeticsSetting::getObjectStructure();
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
		return 'https://help.aspendiscovery.org/help/integration/enrichment';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/SyndeticsSettings', 'Syndetics Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'third_party_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Third Party Enrichment API Keys');
	}

	function canAddNew() {
		return $this->getNumObjects() == 0;
	}
}