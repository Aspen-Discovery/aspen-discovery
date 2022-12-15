<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';

class CloudLibrary_Scopes extends ObjectEditor {
	function getObjectType(): string {
		return 'CloudLibraryScope';
	}

	function getToolName(): string {
		return 'Scopes';
	}

	function getModule(): string {
		return 'CloudLibrary';
	}

	function getPageTitle(): string {
		return 'cloudLibrary Scopes';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new CloudLibraryScope();
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return CloudLibraryScope::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return true;
	}

	function canDelete() {
		return true;
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	/** @noinspection PhpUnused */
	function addToAllLibraries() {
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)) {
			$existingLibrariesCloudLibraryScopes = $cloudLibraryScope->getLibraries();
			$library = new Library();
			$library->find();
			while ($library->fetch()) {
				$alreadyAdded = false;
				foreach ($existingLibrariesCloudLibraryScopes as $libraryCloudLibraryScope) {
					if ($libraryCloudLibraryScope->libraryId == $library->libraryId) {
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded) {
					$newLibraryCloudLibraryScope = new LibraryCloudLibraryScope();
					$newLibraryCloudLibraryScope->libraryId = $library->libraryId;
					$newLibraryCloudLibraryScope->scopeId = $scopeId;
					$existingLibrariesCloudLibraryScopes[] = $newLibraryCloudLibraryScope;
				}
			}
			$cloudLibraryScope->setLibraries($existingLibrariesCloudLibraryScopes);
			$cloudLibraryScope->update();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLibraries() {
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)) {
			$cloudLibraryScope->clearLibraries();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function addToAllLocations() {
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)) {
			$existingLocationCloudLibraryScopes = $cloudLibraryScope->getLocations();
			$location = new Location();
			$location->find();
			while ($location->fetch()) {
				$alreadyAdded = false;
				foreach ($existingLocationCloudLibraryScopes as $locationCloudLibraryScope) {
					if ($locationCloudLibraryScope->locationId == $location->locationId) {
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded) {
					$newLocationCloudLibraryScope = new LocationCloudLibraryScope();
					$newLocationCloudLibraryScope->locationId = $location->locationId;
					$newLocationCloudLibraryScope->scopeId = $scopeId;
					$existingLocationCloudLibraryScopes[] = $newLocationCloudLibraryScope;
				}
			}
			$cloudLibraryScope->setLocations($existingLocationCloudLibraryScopes);
			$cloudLibraryScope->update();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	/** @noinspection PhpUnused */
	function clearLocations() {
		$scopeId = $_REQUEST['id'];
		$cloudLibraryScope = new CloudLibraryScope();
		$cloudLibraryScope->id = $scopeId;
		if ($cloudLibraryScope->find(true)) {
			$cloudLibraryScope->clearLocations();
		}
		header("Location: /CloudLibrary/Scopes?objectAction=edit&id=" . $scopeId);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cloud_library', 'cloudLibrary');
		$breadcrumbs[] = new Breadcrumb('/CloudLibrary/Scopes', 'Scopes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cloud_library';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Cloud Library');
	}
}