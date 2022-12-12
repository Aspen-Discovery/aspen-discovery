<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSetting.php';
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSearchSetting.php';

class EBSCO_EBSCOhostSearchSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'EBSCOhostSearchSetting';
	}

	function getToolName(): string {
		return 'EBSCOhostSearchSettings';
	}

	function getModule(): string {
		return 'EBSCO';
	}

	function getPageTitle(): string {
		return 'EBSCOhost Search Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new EBSCOhostSearchSetting();
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
		return EBSCOhostSearchSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ebscohost', 'EBSCOhost');
		$breadcrumbs[] = new Breadcrumb('/EBSCO/EBSCOhostSearchSettings', 'EBSCOhost Search Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ebscohost';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer EBSCOhost Settings');
	}
}