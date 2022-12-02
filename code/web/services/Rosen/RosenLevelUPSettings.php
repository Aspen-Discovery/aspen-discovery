<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Rosen/RosenLevelUPSetting.php';

class Rosen_RosenLevelUPSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'RosenLevelUPSetting';
	}

	function getToolName(): string {
		return 'RosenLevelUPSettings';
	}

	function getModule(): string {
		return 'Rosen';
	}

	function getPageTitle(): string {
		return 'Rosen LevelUP Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new RosenLevelUPSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
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

	function canSort(): bool {
		return false;
	}

	function getObjectStructure(): array {
		return RosenLevelUPSetting::getObjectStructure();
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
		return '/Admin/HelpManual?page=Rosen-LevelUP';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Rosen/RosenLevelUPSettings', 'Rosen LevelUP Settings');
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