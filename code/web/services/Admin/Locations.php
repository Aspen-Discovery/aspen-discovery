<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Locations extends ObjectEditor {

	function getObjectType(): string {
		return 'Location';
	}

	function getToolName(): string {
		return 'Locations';
	}

	function getPageTitle(): string {
		return 'Locations (Branches)';
	}

	function getAllObjects($page, $recordsPerPage): array {
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new Location();
		$object->orderBy($this->getSort());
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			if (!UserAccount::userHasPermission('Administer Home Library Locations')) {
				$object->locationId = $user->homeLocationId;
			} else {
				//Scope to just locations for the user based on home library
				$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
				$object->libraryId = $patronLibrary->libraryId;
			}
		}
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$locationList = [];
		while ($object->fetch()) {
			$locationList[$object->locationId] = clone $object;
		}
		return $locationList;
	}

	function getDefaultSort(): string {
		return 'displayName asc';
	}

	function getObjectStructure(): array {
		return Location::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'code';
	}

	function getIdKeyColumn(): string {
		return 'locationId';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if ($existingObject != null && $existingObject instanceof Location) {
			$objectActions[] = [
				'text' => 'Reset Facets To Default',
				'url' => '/Admin/Locations?objectAction=resetFacetsToDefault&amp;id=' . $existingObject->locationId,
			];
		} else {
			echo("Existing object is null");
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/admin/systemslocations';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		if (!empty($this->activeObject) && $this->activeObject instanceof Location) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('/Admin/Locations', 'Locations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Locations',
			'Administer Home Library Locations',
			'Administer Home Location',
		]);
	}

	function canAddNew() {
		return UserAccount::userHasPermission(['Administer All Locations']);
	}

	function canDelete() {
		return UserAccount::userHasPermission(['Administer All Locations']);
	}

	protected function getDefaultRecordsPerPage() {
		return 250;
	}

	protected function showQuickFilterOnPropertiesList() {
		return true;
	}
}