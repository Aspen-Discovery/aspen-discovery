<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/HideSubjectFacet.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_HideSubjectFacets extends ObjectEditor {
	function getObjectType(): string {
		return 'HideSubjectFacet';
	}

	function getToolName(): string {
		return 'HideSubjectFacets';
	}

	function getPageTitle(): string {
		return 'Hidden Subjects';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new HideSubjectFacet();
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
		return 'subjectTerm asc';
	}

	function getObjectStructure(): array {
		return HideSubjectFacet::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/HideSubjectFacets', 'Hidden Subjects');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Hide Subject Facets');
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Searches.initAutoComplete({searchTermSelector: "subjectTerm", searchIndex: "Subject"})';
	}

}