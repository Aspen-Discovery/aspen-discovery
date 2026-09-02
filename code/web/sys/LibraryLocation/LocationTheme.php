<?php
/** @noinspection PhpMissingFieldTypeInspection */

class LocationTheme extends DataObject {
	public $__table = 'location_themes';
	public $__displayNameColumn = 'themeName';
	public $_themeName;
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

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

			//Load Libraries for lookup values
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Locations'));
		$allLocationList = Location::getLocationList(false);

		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$theme = new Theme();
		$availableThemes = [];
		$theme->orderBy('themeName');
		$theme->find();
		while ($theme->fetch()) {
			$availableThemes[$theme->id] = $theme->themeName;
		}

		$structure = [
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == 'themeName') {
			if ($this->_themeName == null) {
				require_once ROOT_DIR . '/sys/Theming/Theme.php';
				$theme = new Theme();
				$theme->id = $this->themeId;
				if ($theme->find(true)) {
					$this->_themeName = $theme->themeName;
				} else {
					$this->_themeName = '';
				}
			}
			return $this->_themeName;
		}
		return parent::__get($name);
	}

	public function canActiveUserEdit() : bool {
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$adminLocations = Location::getLocationList(true);
			return array_key_exists($this->locationId, $adminLocations);
		}else {
			return true;
		}
	}

	public function getEditLink(string $context): string {
		if ($context == 'locations') {
			return '/Admin/Locations?objectAction=edit&id=' . $this->locationId;
		} else {
			return '/Admin/Themes?objectAction=edit&id=' . $this->themeId;
		}
	}
}