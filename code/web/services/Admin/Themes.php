<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';

class Admin_Themes extends ObjectEditor {
	function viewIndividualObject($structure): void {
		global $interface;
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			/** @var Theme $existingObject */
			$existingObject = $this->getExistingObjectById($id);
			$parentTheme = $existingObject->getParentTheme();
			if ($parentTheme == null) {
				//Get the default theme
				$parentTheme = $existingObject->getDefaultTheme();
			}
			$interface->assign('parentTheme', $parentTheme);
		}
		parent::viewIndividualObject($structure);
	}
	function getObjectType(): string {
		return 'Theme';
	}

	function getToolName(): string {
		return 'Themes';
	}

	function getPageTitle(): string {
		return 'Themes';
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer All Themes');
	}

	public function canDeleteAll(): bool {
		// Never allow "Delete All" for themes because the default theme should never be deleted.
		return false;
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new Theme();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Themes')) {
			$libraries = Library::getLibraryListAsObjects(true);
			$libraryThemeIds = [];
			foreach ($libraries as $library) {
				$libraryThemes = $library->getThemes();
				foreach ($libraryThemes as $libraryTheme) {
					$libraryThemeIds[] = $libraryTheme->themeId;
				}
			}
			$object->whereAddIn('id', $libraryThemeIds, false);
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
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/233013250/Theme+Settings';
	}

	function getExistingObjectById($id): ?DataObject {
		$existingObject = parent::getExistingObjectById($id);
		if ($existingObject instanceof Theme) {
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
					$themeBreadcrumbs[] = new Breadcrumb('', $theme->themeName, false);
				} else {
					$themeBreadcrumbs[] = new Breadcrumb('/Admin/Themes?objectAction=edit&id=' . $theme->id, $theme->themeName, false);
				}
			}
			$breadcrumbs = array_merge($breadcrumbs, array_reverse($themeBreadcrumbs));
		}
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'theme_and_layout';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Themes',
			'Administer Library Themes',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Themes',
		]);
	}

	protected function getDefaultRecordsPerPage() : int {
		return 100;
	}

	protected function showQuickFilterOnPropertiesList() : bool {
		return true;
	}

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function canShareToCommunity() : bool {
		return $this->hasCommunityConnection() && UserAccount::userHasPermission('Share Content with Community');
	}

	public function canFetchFromCommunity() : bool {
		return $this->hasCommunityConnection() && UserAccount::userHasPermission('Import Content from Community');
	}

	/** @noinspection PhpUnused */
	function addToAllLibraries(): void {
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
	function clearLibraries(): void {
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
	function addToAllLocations(): void {
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
	function clearLocations(): void {
		$themeId = $_REQUEST['id'];
		$theme = new Theme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$theme->clearLocations();
			$theme->update();
		}
		header("Location: /Admin/Themes?objectAction=edit&id=" . $themeId);
	}

	public function hasRecordLocking() : bool {
		return true;
	}
}