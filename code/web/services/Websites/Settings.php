<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';

class Websites_Settings extends ObjectEditor {
	function getObjectType(): string {
		return 'WebsiteIndexSetting';
	}

	function getToolName(): string {
		return 'Settings';
	}

	function getModule(): string {
		return 'Websites';
	}

	function getPageTitle(): string {
		return 'Website Indexing Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new WebsiteIndexSetting();
		$object->deleted = 0;
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
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
		return WebsiteIndexSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_indexer', 'Website Indexing');
		$breadcrumbs[] = new Breadcrumb('/Websites/Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'web_indexer';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Website Indexing Settings');
	}
}