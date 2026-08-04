<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ExploreMoreSource.php';

class Admin_ExploreMoreSource extends ObjectEditor {
	function getObjectType(): string {
		return 'ExploreMoreSource';
	}

	function getToolName(): string {
		return 'ExploreMoreSource';
	}

	function getPageTitle(): string {
		return 'Explore More Sources';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new ExploreMoreSource();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'weight asc, id asc';
	}

	function getObjectStructure($context = ''): array {
		return ExploreMoreSource::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/ExploreMore?objectAction=edit&id=1', 'Explore More Sources');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions(): array {
		return [
			'Administer All Explore More',
			'Administer Library Explore More',
		];
	}

	function canAddNew(): bool {
		return false;
	}

	function canDelete(): bool {
		return false;
	}

	/**
	 * Override the return to list URL to always go to the default group edit page.
	 */
	public function getReturnToListUrl(): string {
		return '/Admin/ExploreMore?objectAction=edit&id=1';
	}
}
