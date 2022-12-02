<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryLink.php';

class Admin_LibraryLinks extends ObjectEditor {

	function getObjectType(): string {
		return 'LibraryLink';
	}

	function getToolName(): string {
		return 'LibraryLinks';
	}

	function getPageTitle(): string {
		return 'Library Links';
	}

	function getAllObjects($page, $recordsPerPage): array {
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new LibraryLink();
		$location = new Location();
		$location->orderBy('displayName asc');
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			//Scope to just locations for the user based on home library
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$object->libraryId = $patronLibrary->libraryId;
		}

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
		$structure = LibraryLink::getObjectStructure();
		unset ($structure['weight']);
		return $structure;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		if (!empty($this->activeObject) && $this->activeObject instanceof LibraryLink) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Libraries?objectAction=edit&id=' . $this->activeObject->libraryId, 'Library');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Library Link');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'primary_configuration';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateLibraryLinksFields()';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Libraries',
			'Administer Home Library',
		]);
	}
}