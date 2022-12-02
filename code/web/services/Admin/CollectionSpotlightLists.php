<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';

class Admin_CollectionSpotlightLists extends ObjectEditor {

	function getObjectType(): string {
		return 'CollectionSpotlightList';
	}

	function getToolName(): string {
		return 'CollectionSpotlightLists';
	}

	function getPageTitle(): string {
		return 'Collection Spotlight Lists';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new CollectionSpotlightList();
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
		return 'weight asc';
	}

	function getObjectStructure(): array {
		return CollectionSpotlightList::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/promote/spotlights';
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateBrowseSearchForSource();';
	}

	function showReturnToList() {
		return false;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('', 'Collection Spotlight List');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Collection Spotlights',
			'Administer Library Collection Spotlights',
		]);
	}
}