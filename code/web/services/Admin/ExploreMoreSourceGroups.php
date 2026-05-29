<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ExploreMoreSourceGroup.php';

class Admin_ExploreMoreSourceGroups extends ObjectEditor {
	function getObjectType(): string {
		return 'ExploreMoreSourceGroup';
	}

	function getToolName(): string {
		return 'ExploreMoreSourceGroups';
	}

	function getPageTitle(): string {
		return 'Explore More Source Groups';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		// Always return the single default group (create if missing)
		$group = new ExploreMoreSourceGroup();
		$group->orderBy('id ASC');
		$group->limit(0, 1);
		$group->find();
		if (!$group->fetch()) {
			// Create default group if missing
			$group->name = 'Explore More Sources';
			$group->insert();
		}
		$group->getExploreMoreSources();
		return [$group->id => clone $group];
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return ExploreMoreSourceGroup::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/ExploreMore?objectAction=edit&id=1', 'Explore More Source Groups');
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
}
