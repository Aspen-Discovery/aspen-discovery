<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';

class Admin_Themes extends ObjectEditor {
	function getObjectType(): string {
		return 'Theme';
	}

	function getToolName(): string {
		return 'Themes';
	}

	function getPageTitle(): string {
		return 'Themes';
	}

	function canDelete() {
		return UserAccount::userHasPermission('Administer All Themes');
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new Theme();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Themes')) {
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$object->id = $library->theme;
		}
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'themeName asc';
	}

	function getObjectStructure($context = ''): array {
		return Theme::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/admin/theme';
	}

	function getExistingObjectById($id): ?DataObject {
		$existingObject = parent::getExistingObjectById($id);
		if ($existingObject != null && $existingObject instanceof Theme) {
			$existingObject->applyDefaults();
		}
		return $existingObject;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#theme_and_layout', 'Configuration Templates');
		$breadcrumbs[] = new Breadcrumb('/Admin/Themes', 'Themes');
		if (!empty($this->activeObject) && $this->activeObject instanceof Theme) {
			$themes = $this->activeObject->getAllAppliedThemes();
			$themeBreadcrumbs = [];
			foreach ($themes as $theme) {
				if ($theme->id == $this->activeObject->id) {
					$themeBreadcrumbs[] = new Breadcrumb('', $theme->themeName);
				} else {
					$themeBreadcrumbs[] = new Breadcrumb('/Admin/Themes?objectAction=edit&id=' . $theme->id, $theme->themeName);
				}
			}
			$breadcrumbs = array_merge($breadcrumbs, array_reverse($themeBreadcrumbs));
		}
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'theme_and_layout';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Themes',
			'Administer Library Themes',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Themes',
		]);
	}

	protected function getDefaultRecordsPerPage() {
		return 100;
	}

	protected function showQuickFilterOnPropertiesList() {
		return true;
	}

	public function canCopy() {
		return $this->canAddNew();
	}

	public function canShareToCommunity() {
		//TODO: This needs a permission
		return $this->hasCommunityConnection() && UserAccount::userHasPermission('Share Content with Community');
	}

	public function canFetchFromCommunity() {
		//TODO: This needs a permission
		return $this->hasCommunityConnection() && UserAccount::userHasPermission('Import Content from Community');
	}

	/** @noinspection PhpUnused */
	function addToAllLibraries() {
		$themeId = $_REQUEST['id'];
		$theme = new Theme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$existingLibraryThemes = $theme->getLibraries();
			$library = new Library();
			$library->find();
			while ($library->fetch()) {
				$alreadyAdded = false;
				foreach ($existingLibraryThemes as $libraryTheme) {
					if ($libraryTheme->libraryId == $library->libraryId) {
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded) {
					$newLibraryTheme = new LibraryTheme();
					$newLibraryTheme->libraryId = $library->libraryId;
					$newLibraryTheme->themeId = $themeId;
					//Make it the highest weighted theme
					$newLibraryTheme->weight = count($library->getThemes());
					$newLibraryTheme->insert();
				}
			}
		}
		header("Location: /Admin/Themes?objectAction=edit&id=" . $themeId);
	}

	/** @noinspection PhpUnused */
	function clearLibraries() {
		$themeId = $_REQUEST['id'];
		$theme = new Theme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$theme->clearLibraries();
			$theme->update();
		}
		header("Location: /Admin/Themes?objectAction=edit&id=" . $themeId);
	}

	/** @noinspection PhpUnused */
	function addToAllLocations() {
		$themeId = $_REQUEST['id'];
		$theme = new Theme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$existingLocationThemes = $theme->getLocations();
			$location = new Location();
			$location->find();
			while ($location->fetch()) {
				$alreadyAdded = false;
				foreach ($existingLocationThemes as $locationTheme) {
					if ($locationTheme->locationId == $location->locationId) {
						$alreadyAdded = true;
					}
				}
				if (!$alreadyAdded) {
					$locationTheme = new LocationTheme();
					$locationTheme->locationId = $location->locationId;
					$locationTheme->themeId = $themeId;
					//Make it the highest weighted theme
					$locationTheme->weight = count($location->getThemes());
					$locationTheme->insert();
				}
			}
		}
		header("Location: /Admin/Themes?objectAction=edit&id=" . $themeId);
	}

	/** @noinspection PhpUnused */
	function clearLocations() {
		$themeId = $_REQUEST['id'];
		$theme = new Theme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$theme->clearLocations();
			$theme->update();
		}
		header("Location: /Admin/Themes?objectAction=edit&id=" . $themeId);
	}

}