<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadScope.php';

class SideLoads_Scopes extends ObjectEditor {
	function launch() : void {
		if (isset($_REQUEST['id'])) {
			$sideLoadScope = new SideLoadScope();
			$sideLoadScope->id = $_REQUEST['id'];
			if ($sideLoadScope->find(true)) {
				$sideLoadConfiguration = new SideLoad();
				$sideLoadConfiguration->id = $sideLoadScope->sideLoadId;
				if ($sideLoadConfiguration->find(true)) {
					global $interface;
					$interface->assign('sideload', $sideLoadConfiguration);
				}
			}
		}

		parent::launch();
	}

	function getObjectType(): string {
		return 'SideLoadScope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'SideLoads';
	}

	function getPageTitle(): string {
		return 'Side Loaded Content Scopes';
	}

	function getAllObjects($page, $recordsPerPage): array {
		//Get the sideloads the user has access to
		$sideLoad = new SideLoad();
		if ((UserAccount::userHasPermission('Administer Side Loads for Home Library') || UserAccount::userHasPermission('Administer Side Load Scopes for Home Library')) && !UserAccount::userHasPermission('Administer Side Loads')) {
			$libraryList = Library::getLibraryList(true);
			$sideLoad->whereAddIn("owningLibrary", array_keys($libraryList), false, "OR");
			$sideLoad->whereAdd("sharing = 1", "OR");
		}
		$sideLoad->find();
		$availableSideLoadIds = [];
		while ($sideLoad->fetch()) {
			$availableSideLoadIds[$sideLoad->id] = $sideLoad->id;
		}

		$object = new SideLoadScope();
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->whereAddIn('sideLoadId', $availableSideLoadIds, false);
		$this->applyFilters($object);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return SideLoadScope::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/integration/sideload';
	}

	/** @noinspection PhpUnused */
	function addToAllLibraries() {
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)) {
			$existingLibrariesSideLoadScopes = $sideLoadScope->getLibraries();
			$library = new Library();
			if (UserAccount::userHasPermission('Administer Side Load Scopes for Home Library') && !UserAccount::userHasPermission('Administer Side Loads')) {
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				$library->libraryId = $library == null ? -1 : $library->libraryId;
			}
			$library->find();
			while ($library->fetch()) {
				$alreadyAdded = false;
				foreach ($existingLibrariesSideLoadScopes as $librarySideLoadScope) {
					if ($librarySideLoadScope->libraryId == $library->libraryId) {
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded) {
					$newLibrarySideLoadScope = new LibrarySideLoadScope();
					$newLibrarySideLoadScope->libraryId = $library->libraryId;
					$newLibrarySideLoadScope->sideLoadScopeId = $scopeId;
					$existingLibrariesSideLoadScopes[] = $newLibrarySideLoadScope;
				}
			}
			$sideLoadScope->setLibraries($existingLibrariesSideLoadScopes);
			$sideLoadScope->update();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLibraries() {
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)) {
			$sideLoadScope->clearLibraries();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function addToAllLocations() {
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)) {
			$existingLocationSideLoadScopes = $sideLoadScope->getLocations();
			$location = new Location();
			if (UserAccount::userHasPermission('Administer Side Load Scopes for Home Library') && !UserAccount::userHasPermission('Administer Side Loads')) {
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				$library->libraryId = $library == null ? -1 : $library->libraryId;
				$location->libraryId = $library->libraryId;
			}
			$location->find();
			while ($location->fetch()) {
				$alreadyAdded = false;
				foreach ($existingLocationSideLoadScopes as $locationSideLoadScope) {
					if ($locationSideLoadScope->locationId == $location->locationId) {
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded) {
					$newLocationSideLoadScope = new LocationSideLoadScope();
					$newLocationSideLoadScope->locationId = $location->locationId;
					$newLocationSideLoadScope->sideLoadScopeId = $scopeId;
					$existingLocationSideLoadScopes[] = $newLocationSideLoadScope;
				}
			}
			$sideLoadScope->setLocations($existingLocationSideLoadScopes);
			$sideLoadScope->update();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLocations() {
		$scopeId = $_REQUEST['id'];
		$sideLoadScope = new SideLoadScope();
		$sideLoadScope->id = $scopeId;
		if ($sideLoadScope->find(true)) {
			$sideLoadScope->clearLocations();
		}
		header("Location: /SideLoads/Scopes?objectAction=edit&id=" . $scopeId);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Loads');
		if (!empty($this->activeObject) && $this->activeObject instanceof SideLoadScope) {
			$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads?objectAction=edit&id=' . $this->activeObject->sideLoadId, 'Side Load Settings');
		}
		$breadcrumbs[] = new Breadcrumb('/SideLoads/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'side_loads';
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer Side Loads',
		]);
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Side Loads', 'Administer Side Loads for Home Library', 'Administer Side Load Scopes for Home Library']);
	}
}