<?php

class LocationTheme extends DataObject {
	public $__table = 'location_themes';
	public $id;
	public $locationId;
	public $themeId;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'locationId',
			'themeId',
			'weight',
		];
	}

	static function getObjectStructure($context = ''): array {
		//Load Libraries for lookup values
		$location = new Location();
		$location->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$locationList = $location->fetchAll('locationId', 'displayName');
		$location = new Location();
		$location->orderBy('displayName');
		$allLocationList = $location->fetchAll('locationId', 'displayName');

		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$theme = new Theme();
		$availableThemes = [];
		$theme->orderBy('themeName');
		$theme->find();
		while ($theme->fetch()) {
			$availableThemes[$theme->id] = $theme->themeName;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the hours within the database',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'values' => $locationList,
				'allValues' => $allLocationList,
				'label' => 'Location',
				'description' => 'A link to the location which the theme belongs to',
			],
			'themeId' => [
				'property' => 'themeId',
				'type' => 'enum',
				'label' => 'Theme',
				'values' => $availableThemes,
				'description' => 'The theme which should be used for the library',
				'permissions' => ['Library Theme Configuration'],
			],
		];
	}

	public function canActiveUserEdit() {
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			foreach ($homeLibrary->getLocations() as $location) {
				if ($location->locationId == $this->locationId) {
					return true;
				}
			}
			return false;
		}else {
			return true;
		}
	}

	function getEditLink($context): string {
		if ($context == 'locations') {
			return '/Admin/Locations?objectAction=edit&id=' . $this->locationId;
		} else {
			return '/Admin/Themes?objectAction=edit&id=' . $this->themeId;
		}
	}
}