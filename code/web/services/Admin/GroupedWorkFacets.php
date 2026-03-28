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

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new GroupedWorkFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Grouped Work Facets')) {
			$validLibraries = Library::getLibraryListAsObjects(true);
			$validGroupedWorkDisplaySettings = [];
			foreach ($validLibraries as $library) {
				$validGroupedWorkDisplaySettings[$library->groupedWorkDisplaySettingId] = $library->groupedWorkDisplaySettingId;
			}

			$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
			$groupedWorkDisplaySettings->whereAddIn('id', $validGroupedWorkDisplaySettings, false);
			$validFormatSortingGroupIds = $groupedWorkDisplaySettings->fetchAll('formatSortingGroupId', 'formatSortingGroupId');
			$object->whereAddIn('id', $validFormatSortingGroupIds, false);
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

	function getObjectStructure($context = ''): array {
		return GroupedWorkFacetGroup::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/381648900/Facets';
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

	public function getViewPermissions() : array {
		return [
			'Administer All Grouped Work Facets',
			'Administer Library Grouped Work Facets',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Grouped Work Facets',
		]);
	}
}