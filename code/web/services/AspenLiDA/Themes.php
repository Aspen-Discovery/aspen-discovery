<?php

use JetBrains\PhpStorm\NoReturn;

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/Theme.php';

class AspenLiDA_Themes extends ObjectEditor {
	function getObjectType(): string {
		return 'AspenLiDATheme';
	}

	function getToolName(): string {
		return 'Themes';
	}

	function getModule(): string {
		return 'AspenLiDA';
	}

	function getPageTitle(): string {
		return 'Aspen LiDA Themes';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$list = [];

		$object = new AspenLiDATheme();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);

		// Library admins only see themes assigned to their library
		if (!UserAccount::userHasPermission('Administer All Aspen LiDA Themes')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary) {
				require_once ROOT_DIR . '/sys/AspenLiDA/ThemeLibrary.php';
				$junction = new AspenLiDAThemeLibrary();
				$junction->libraryId = $homeLibrary->libraryId;
				$junction->find();
				$themeIds = [];
				while ($junction->fetch()) {
					$themeIds[] = $junction->themeId;
				}
				if (empty($themeIds)) {
					return $list;
				}
				$object->whereAddIn('id', $themeIds, false);
			}
		}

		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return AspenLiDATheme::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Themes');
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission('Administer All Aspen LiDA Themes');
	}

	/** @noinspection PhpUnused */
	function addToAllLibraries(): void {
		$themeId = $_REQUEST['id'];
		$theme = new AspenLiDATheme();
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
					$newLibraryTheme = new AspenLiDAThemeLibrary();
					$newLibraryTheme->libraryId = $library->libraryId;
					$newLibraryTheme->themeId = $themeId;
					//Make it the highest weighted theme
					$newLibraryTheme->weight = count($library->getAspenLiDAThemes());
					$newLibraryTheme->insert();
				}
			}
		}
		header("Location: /AspenLiDA/Themes?objectAction=edit&id=" . $themeId);
	}

	/** @noinspection PhpUnused */
	function addToAllLocations(): void {
		$themeId = $_REQUEST['id'];
		$theme = new AspenLiDATheme();
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
					$locationTheme = new AspenLiDAThemeLocation();
					$locationTheme->locationId = $location->locationId;
					$locationTheme->themeId = $themeId;
					//Make it the highest weighted theme
					$locationTheme->weight = count($location->getAspenLiDAThemes());
					$locationTheme->insert();
				}
			}
		}
		header("Location: /AspenLiDA/Themes?objectAction=edit&id=" . $themeId);
	}

	/** @noinspection PhpUnused */
	function clearLibraries(): void {
		$themeId = $_REQUEST['id'];
		$theme = new AspenLiDATheme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$theme->clearLibraries();
		}
		header('Location: /AspenLiDA/Themes?objectAction=edit&id=' . $themeId);
	}

	/** @noinspection PhpUnused */
	function clearLocations(): void {
		$themeId = $_REQUEST['id'];
		$theme = new AspenLiDATheme();
		$theme->id = $themeId;
		if ($theme->find(true)) {
			$theme->clearLocations();
		}
		header('Location: /AspenLiDA/Themes?objectAction=edit&id=' . $themeId);
	}

	/**
	 * Returns JSON with color and logo data from an Aspen Discovery web theme so the
	 * admin JS can pre-populate the LiDA theme form fields.
	 */
	/** @noinspection PhpUnused */
	#[NoReturn]
	function getWebThemeData(): void {
		$result = [
			'success' => false,
			'data' => []
		];
		if (!empty($_REQUEST['themeId'])) {
			require_once ROOT_DIR . '/sys/Theming/Theme.php';
			$webTheme = new Theme();
			$webTheme->id = (int)$_REQUEST['themeId'];
			if ($webTheme->find(true)) {
				$webTheme->applyDefaults();
				$result = [
					'success' => true,
					'data' => [
						'isDarkColorScheme' => (bool)$webTheme->isDarkColorScheme,
						'logoApp' => $webTheme->logoApp ?? '',
						'headerLogoApp' => $webTheme->headerLogoApp ?? '',
						'headerLogoAlignmentApp' => (int)($webTheme->headerLogoAlignmentApp ?? 2),
						'headerLogoBackgroundColorApp' => $webTheme->headerLogoBackgroundColorApp ?? '#ffffff',
						'primaryColor' => $webTheme->primaryBackgroundColor ?? Theme::$defaultPrimaryBackgroundColor,
						'primaryTextColor' => $webTheme->primaryForegroundColor ?? Theme::$defaultPrimaryForegroundColor,
						'secondaryColor' => $webTheme->secondaryBackgroundColor ?? Theme::$defaultSecondaryBackgroundColor,
						'secondaryTextColor' => $webTheme->secondaryForegroundColor ?? Theme::$defaultSecondaryForegroundColor,
						'tertiaryColor' => $webTheme->tertiaryBackgroundColor ?? Theme::$defaultTertiaryBackgroundColor,
						'tertiaryTextColor' => $webTheme->tertiaryForegroundColor ?? Theme::$defaultTertiaryForegroundColor,
					],
				];
			}
		}
		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/Themes', 'Themes');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'aspen_lida';
	}

	public function getViewPermissions(): array {
		return [
			'Administer All Aspen LiDA Themes',
			'Administer Library Aspen LiDA Themes',
		];
	}

	public function getRequiredModule(): ?string {
		return 'Aspen LiDA';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateLiDAThemeBaseColors(); ';
	}
}