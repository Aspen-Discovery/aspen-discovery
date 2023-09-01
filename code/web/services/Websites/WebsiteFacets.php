<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteFacetGroup.php';

class Websites_WebsiteFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'WebsiteFacetGroup';
	}

	function getToolName(): string {
		return 'WebsiteFacets';
	}

	function getModule(): string {
		return 'Websites';
	}

	function getPageTitle(): string {
		return 'Website Facets';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new WebsiteFacetGroup();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Website Facet Settings')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());

            $websiteFacetSettingId = $library->websiteIndexingFacetSettingId;
			$object->id = $websiteFacetSettingId->facetGroupId;
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
		return WebsiteFacetGroup::getObjectStructure($context);
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
        $breadcrumbs[] = new Breadcrumb('/Admin/Home#web_indexer', 'Website Indexing');
        $breadcrumbs[] = new Breadcrumb('/Websites/WebsiteFacets', 'Website Facets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Website Facet Settings',
			'Administer Library Website Facet Settings',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
            'Administer All Website Facet Settings',
		]);
	}
}