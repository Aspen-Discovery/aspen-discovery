<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_LibraryFacetSettings extends ObjectEditor {

	function getObjectType(): string {
		return 'LibraryFacetSetting';
	}

	function getToolName(): string {
		return 'LibraryFacetSettings';
	}

	function getPageTitle(): string {
		return 'Library Facets';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$facetsList = [];
		$object = new LibraryFacetSetting();
		if (isset($_REQUEST['libraryId'])) {
			$libraryId = $_REQUEST['libraryId'];
			$object->libraryId = $libraryId;
		}
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$facetsList[$object->id] = clone $object;
		}

		return $facetsList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return LibraryFacetSetting::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (isset($existingObject) && $existingObject != null) {
			$objectActions[] = [
				'text' => 'Return to Library',
				'url' => '/Admin/Libraries?objectAction=edit&id=' . $existingObject->libraryId,
			];
		}
		return $objectActions;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		if (!empty($this->activeObject) && $this->activeObject instanceof LibraryFacetSetting) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Archive Facet Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Libraries',
			'Administer Home Library',
		]);
	}
}