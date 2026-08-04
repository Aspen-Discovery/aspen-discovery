<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/AuthorAuthority.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_AuthorAuthorities extends ObjectEditor {
	function getObjectType(): string {
		return 'AuthorAuthority';
	}

	function getToolName(): string {
		return 'AuthorAuthorities';
	}

	function getPageTitle(): string {
		return 'Author Authorities';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new AuthorAuthority();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'dateAdded desc';
	}

	function getObjectStructure($context = ''): array {
		return AuthorAuthority::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/420741126/Grouping+and+Ungrouping+Records';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/AuthorAuthorities', 'Author Authorities');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	public function getViewPermissions() : array {
		return ['Manually Group and Ungroup Works'];
	}
}