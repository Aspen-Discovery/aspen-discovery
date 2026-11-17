<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Series/SeriesFacetGroup.php';

class Series_SeriesFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'SeriesFacetGroup';
	}

	function getModule(): string {
		return 'Series';
	}

	function getToolName(): string {
		return 'SeriesFacets';
	}

	function getPageTitle(): string {
		return 'Series Facets';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new SeriesFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$list = [];
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return SeriesFacetGroup::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#series', 'Series');
		$breadcrumbs[] = new Breadcrumb('/Series/SeriesFacets', 'Series Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'series';
	}

	public function getViewPermissions() : array {
		return ['Administer Series Facet Settings'];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission(['Administer Series Facet Settings']);
	}

	public function getRequiredModule(): ?string {
		return 'Series';
	}
}
