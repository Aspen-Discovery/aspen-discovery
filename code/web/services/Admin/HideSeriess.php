<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/HideSeries.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_HideSeriess extends ObjectEditor {
	function getObjectType(): string {
		return 'HideSeries';
	}

	function getToolName(): string {
		return 'HideSeriess';
	}

	function getPageTitle(): string {
		return 'Hidden Series';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new HideSeries();
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
		return 'SeriesTerm asc';
	}

	function getObjectStructure($context = ''): array {
		return HideSeries::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/347373607/Grouped+Works+and+Record+Display';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/HideSeriess', 'Hidden Series');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	public function getViewPermissions() : array {
		return ['Hide Metadata'];
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Searches.initAutoComplete({searchTermSelector: "seriesTerm", searchIndex: "Series"})';
	}

}