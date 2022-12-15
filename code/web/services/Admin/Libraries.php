<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Libraries extends ObjectEditor {
	function getObjectType(): string {
		return 'Library';
	}

	function getToolName(): string {
		return 'Libraries';
	}

	function getPageTitle(): string {
		return 'Library Systems';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$libraryList = [];

		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasPermission('Administer All Libraries')) {
			$object = new Library();
			$object->orderBy($this->getSort());
			$this->applyFilters($object);
			$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
			$object->find();
			while ($object->fetch()) {
				$libraryList[$object->libraryId] = clone $object;
			}
		} else {
			//This doesn't need pagination since there should only be one
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$libraryList[$patronLibrary->libraryId] = clone $patronLibrary;
		}

		return $libraryList;
	}

	function getDefaultSort(): string {
		return 'subdomain asc';
	}

	function getObjectStructure($context = ''): array {
		$objectStructure = Library::getObjectStructure($context);
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			unset($objectStructure['isDefault']);
		}
		return $objectStructure;
	}

	function getPrimaryKeyColumn(): string {
		return 'subdomain';
	}

	function getIdKeyColumn(): string {
		return 'libraryId';
	}

	function canAddNew() {
		return UserAccount::userHasPermission('Administer All Libraries');
	}

	function canDelete() {
		return UserAccount::userHasPermission('Administer All Libraries');
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	/** @noinspection PhpUnused */
	function defaultMaterialsRequestForm() {
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)) {
			$library->clearMaterialsRequestFormFields();

			$defaultFieldsToDisplay = MaterialsRequestFormFields::getDefaultFormFields($libraryId);
			$library->setMaterialsRequestFormFields($defaultFieldsToDisplay);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();

	}

	/** @noinspection PhpUnused */
	function defaultMaterialsRequestFormats() {
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)) {
			$library->clearMaterialsRequestFormats();

			$defaultMaterialsRequestFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats($libraryId);
			$library->setMaterialsRequestFormats($defaultMaterialsRequestFormats);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/admin/systemslocations';
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateMaterialsRequestFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Libraries', 'Library Systems');
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

	protected function getDefaultRecordsPerPage() {
		return 250;
	}

	protected function showQuickFilterOnPropertiesList() {
		return true;
	}
}