<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/WebResourcesSetting.php';

class WebBuilder_WebResourceSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'WebResourcesSetting';
	}

	function getToolName(): string {
		return 'WebResourceSettings';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Web Resource Settings';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new WebResourcesSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$objectList = [];
		$object->find();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return WebResourcesSetting::getObjectStructure($context);
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
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/252444692/Web+Resources';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/WebResourceSettings', 'Web Resource Settings');
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Web Resources',
		];
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	function canCopy() : bool {
		return $this->canAddNew();
	}

	public function getRequiredModule(): ?string {
		return 'Web Builder';
	}
}