<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesFacetGroup.php';

class OpenArchives_OpenArchivesFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'OpenArchivesFacetGroup';
	}

	function getToolName(): string {
		return 'OpenArchivesFacets';
	}

	function getPageTitle(): string {
		return 'Open Archives Facets';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new OpenArchivesFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Open Archives Facet Settings')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());

            $OAFacetSettingId = $library->openArchivesFacetSettingId;
			$object->id = $OAFacetSettingId->facetGroupId;
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
		return OpenArchivesFacetGroup::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/catalog/facets';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
        $breadcrumbs[] = new Breadcrumb('/Admin/Home#open_archives', 'Open Archives');
		$breadcrumbs[] = new Breadcrumb('/OpenArchives/OpenArchivesFacets', 'Open Archives Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Open Archives Facet Settings',
			'Administer Library Open Archives Facet Settings',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
            'Administer All Open Archives Facet Settings',
		]);
	}
}