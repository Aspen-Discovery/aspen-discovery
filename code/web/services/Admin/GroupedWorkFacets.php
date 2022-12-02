<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';

class Admin_GroupedWorkFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'GroupedWorkFacetGroup';
	}

	function getToolName(): string {
		return 'GroupedWorkFacets';
	}

	function getPageTitle(): string {
		return 'Grouped Work Facets';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new GroupedWorkFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Grouped Work Facets')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->id = $library->groupedWorkDisplaySettingId;
			$groupedWorkDisplaySettings->find(true);
			$object->id = $groupedWorkDisplaySettings->facetGroupId;
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure(): array {
		return GroupedWorkFacetGroup::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/catalog/groupedworks';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/GroupedWorkFacets', 'Grouped Work Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Grouped Work Facets',
			'Administer Library Grouped Work Facets',
		]);
	}
}