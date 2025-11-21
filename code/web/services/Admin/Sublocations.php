<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';

class Admin_Sublocations extends ObjectEditor {

	function getObjectType(): string {
		return 'Sublocation';
	}

	function getToolName(): string {
		return 'Sublocations';
	}

	function getPageTitle(): string {
		return 'Sublocations';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new Sublocation();
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			//Scope to just branches for the user based on home branch
			$object->locationId = $user->homeLocationId;
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

	function getObjectStructure($context = ''): array {
		$structure = Sublocation::getObjectStructure($context);
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
		if (!empty($this->activeObject) && $this->activeObject instanceof Sublocation) {
			$breadcrumbs[] = new Breadcrumb('/Admin/Locations?objectAction=edit&id=' . $this->activeObject->locationId, 'Location');
		}
		$breadcrumbs[] = new Breadcrumb('', 'Sublocation');
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

	function showReturnToList() : bool {
		return false;
	}
}
