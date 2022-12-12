<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/QuipuECardSetting.php';

class QuipuECardSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'QuipuECardSetting';
	}

	function getToolName(): string {
		return 'QuipuECardSettings';
	}

	function getModule(): string {
		return 'Enrichment';
	}

	function getPageTitle(): string {
		return 'Quipu eCARD Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new QuipuECardSetting();
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
		return QuipuECardSetting::getObjectStructure($context);
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
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/QuipuECardSetting', 'Quipu eCARD Settings');
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