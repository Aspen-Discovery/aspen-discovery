<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/AspenLiDA/ThemeLibrary.php';
require_once ROOT_DIR . '/sys/AspenLiDA/ThemeLocation.php';
require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';

class AspenLiDATheme extends DataObject {
	public $__table = 'aspen_lida_themes';

	public $id;
	public $name;
	public $baseMode; // 'light' or 'dark'
	public $extendsWebThemeId; // reference to Aspen Discovery Theme used to seed this theme

	public $logo;
	public $headerLogo;
	public $headerLogoAlignment;
	public $headerLogoBackgroundColor;
	public $headerLogoBackgroundColorDefault;

	public $primaryColor;
	public $primaryColorDefault;
	public $primaryTextColor;
	public $primaryTextColorDefault;
	public $secondaryColor;
	public $secondaryColorDefault;
	public $secondaryTextColor;
	public $secondaryTextColorDefault;
	public $tertiaryColor;
	public $tertiaryColorDefault;
	public $tertiaryTextColor;
	public $tertiaryTextColorDefault;

	const LIGHT_BG = '#F5F5F5';
	const LIGHT_CARD_BG = '#ffffff';
	const LIGHT_TEXT = '#57534e';
	const DARK_BG = '#111827';
	const DARK_CARD_BG = '#1f2937';
	const DARK_TEXT = '#e5e7eb';

	const BASE_COLOR_PROPERTIES = [
		'backgroundColor',
		'textColor',
	];

	private $_libraries;
	private $_locations;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$themeLibraryStructure = AspenLiDAThemeLibrary::getObjectStructure($context);
		unset($themeLibraryStructure['themeId']);
		unset($themeLibraryStructure['weight']);

		$themeLocationStructure = AspenLiDAThemeLocation::getObjectStructure($context);
		unset($themeLocationStructure['themeId']);
		unset($themeLocationStructure['weight']);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The display name for this theme',
				'maxLength' => 100,
				'required' => true,
			],
			'extendsWebThemeId' => [
				'property' => 'extendsWebThemeId',
				'type' => 'enum',
				'values' => [-1 => 'None'] + self::getWebThemeList(),
				'label' => 'Extend from Aspen Discovery Theme',
				'description' => 'Select an Aspen Discovery web theme to pre-populate the logo and color fields below. You can modify any values after selecting.',
				'note' => 'Selecting a theme will overwrite logo settings and all color fields with values from the chosen Aspen Discovery theme.',
				'default' => -1,
				'hideInLists' => true,
				'onchange' => 'return AspenDiscovery.Admin.populateLiDAThemeFromWebTheme();',
			],
			'logo' => [
				'property' => 'logo',
				'type' => 'image',
				'label' => 'Logo (512x512 pixels)',
				'description' => 'The logo for use in Aspen LiDA',
				'required' => false,
				'thumbWidth' => 180,
				'maxWidth' => 512,
				'maxHeight' => 512,
				'hideInLists' => true,
			],
			'headerLogo' => [
				'property' => 'headerLogo',
				'type' => 'image',
				'label' => 'Logo to show above the screen title (1536x200 pixels)',
				'description' => 'The logo to display above the title in Aspen LiDA. If none provided, the app will only show the screen title.',
				'required' => false,
				'thumbWidth' => 180,
				'maxWidth' => 1536,
				'maxHeight' => 200,
				'hideInLists' => true,
			],
			'headerLogoAlignment' => [
				'property' => 'headerLogoAlignment',
				'type' => 'enum',
				'values' => [
					1 => 'Left',
					2 => 'Center',
					3 => 'Right',
				],
				'label' => 'Header Logo Alignment',
				'description' => 'The alignment of the header logo within Aspen LiDA.',
				'default' => 2,
				'hideInLists' => true,
			],
			'headerLogoBackgroundColor' => [
				'property' => 'headerLogoBackgroundColor',
				'type' => 'color',
				'label' => 'Header Logo Background Color',
				'description' => 'The background color to show behind the header logo in Aspen LiDA.',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
			],
			'baseMode' => [
				'property' => 'baseMode',
				'type' => 'enum',
				'values' => [
					'light' => 'Light',
					'dark' => 'Dark',
				],
				'label' => 'Base Mode',
				'description' => 'Whether this theme is based on light or dark mode. Determines the background colors used for contrast checking.',
				'default' => 'light',
				'required' => true,
				'onchange' => 'AspenDiscovery.Admin.updateLiDAThemeBaseColors()',
			],
			'backgroundColor' => [
				'property' => 'backgroundColor',
				'type' => 'color',
				'readOnly' => true,
				'label' => 'Page Background',
				'hideInLists' => true,
				'default' => self::LIGHT_BG,
			],
			'textColor' => [
				'property' => 'textColor',
				'type' => 'color',
				'readOnly' => true,
				'label' => 'Text Color',
				'hideInLists' => true,
				'default' => self::LIGHT_TEXT,
			],
			'primaryColor' => [
				'property' => 'primaryColor',
				'type' => 'color',
				'label' => 'Primary Color',
				'description' => 'The primary brand color, used for main buttons and key UI elements.',
				'required' => false,
				'hideInLists' => true,
				'default' => '#147ce2',
				'serverValidation' => 'validateColorContrast',
				'checkContrastWith' => 'backgroundColor',
				'checkContrastOneWay' => true,
			],
			'primaryTextColor' => [
				'property' => 'primaryTextColor',
				'type' => 'color',
				'label' => 'Primary Text Color',
				'description' => 'Text color used on top of the primary color (e.g. button labels).',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
				'checkContrastWith' => 'primaryColor',
				'checkContrastOneWay' => true,
			],
			'secondaryColor' => [
				'property' => 'secondaryColor',
				'type' => 'color',
				'label' => 'Secondary Color',
				'description' => 'The secondary brand color, used for accents and secondary UI elements.',
				'required' => false,
				'hideInLists' => true,
				'default' => '#de9d03',
				'checkContrastWith' => 'backgroundColor',
				'checkContrastOneWay' => true,
			],
			'secondaryTextColor' => [
				'property' => 'secondaryTextColor',
				'type' => 'color',
				'label' => 'Secondary Text Color',
				'description' => 'Text color used on top of the secondary color.',
				'required' => false,
				'hideInLists' => true,
				'default' => '#1c1917',
				'checkContrastWith' => 'secondaryColor',
				'checkContrastOneWay' => true,
			],
			'tertiaryColor' => [
				'property' => 'tertiaryColor',
				'type' => 'color',
				'label' => 'Tertiary Color',
				'description' => 'The tertiary brand color, used for additional accent elements.',
				'required' => false,
				'hideInLists' => true,
				'default' => '#de1f0b',
				'checkContrastWith' => 'backgroundColor',
				'checkContrastOneWay' => true,
			],
			'tertiaryTextColor' => [
				'property' => 'tertiaryTextColor',
				'type' => 'color',
				'label' => 'Tertiary Text Color',
				'description' => 'Text color used on top of the tertiary color.',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
				'checkContrastWith' => 'tertiaryColor',
				'checkContrastOneWay' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Libraries that offer this theme in Aspen LiDA. The entry with the lowest weight is the default theme for that library.',
				'keyThis' => 'id',
				'keyOther' => 'themeId',
				'subObjectType' => 'AspenLiDAThemeLibrary',
				'structure' => $themeLibraryStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'permissions' => [
					'Administer All Aspen LiDA Themes',
					'Administer Library Aspen LiDA Themes'
				],
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Libraries',
						'url' => '/AspenLiDA/Themes?id=$id&amp;objectAction=addToAllLibraries',
					],
					'clearLibraries' => [
						'text' => 'Remove From All Libraries',
						'url' => '/AspenLiDA/Themes?id=$id&amp;objectAction=clearLibraries',
						'class' => 'btn-warning',
					],
				],
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'oneToMany',
				'label' => 'Locations',
				'description' => 'Locations that offer this theme in Aspen LiDA. The entry with the lowest weight is the default theme for that location.',
				'keyThis' => 'id',
				'keyOther' => 'themeId',
				'subObjectType' => 'AspenLiDAThemeLocation',
				'structure' => $themeLocationStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'permissions' => [
					'Administer All Aspen LiDA Themes',
					'Administer Library Aspen LiDA Themes'
				],
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Locations',
						'url' => '/AspenLiDA/Themes?id=$id&amp;objectAction=addToAllLocations',
					],
					'clearLocations' => [
						'text' => 'Remove From All Locations',
						'url' => '/AspenLiDA/Themes?id=$id&amp;objectAction=clearLocations',
						'class' => 'btn-warning',
					],
				],
			],
		];

		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$structure['libraries']['additionalOneToManyActions'] = [];
		}

		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$structure['locations']['additionalOneToManyActions'] = [];
		}


		if (!SystemVariables::getSystemVariables()->enableBrandedApp) {
			unset($structure['baseMode']);
			unset($structure['backgroundColor']);
			unset($structure['textColor']);
			unset($structure['primaryColor']);
			unset($structure['secondaryColor']);
			unset($structure['tertiaryColor']);
			unset($structure['primaryTextColor']);
			unset($structure['secondaryTextColor']);
			unset($structure['tertiaryTextColor']);
		}
		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * Returns an id => name list of all available LiDA themes for use in enum dropdowns.
	 */
	public static function getThemeList(): array {
		$themes = [];
		$theme = new AspenLiDATheme();
		$theme->orderBy('name');
		$theme->find();
		while ($theme->fetch()) {
			$themes[$theme->id] = $theme->name;
		}
		return $themes;
	}

	/**
	 * Returns an id => displayName list of all non-deleted Aspen Discovery (web) themes
	 * for use in the "Extend from Aspen Discovery Theme" dropdown.
	 */
	public static function getWebThemeList(): array {
		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$themes = [];
		$webTheme = new Theme();
		$webTheme->deleted = 0;
		$webTheme->orderBy('themeName');
		$webTheme->find();
		while ($webTheme->fetch()) {
			$label = !empty($webTheme->displayName) ? $webTheme->displayName : $webTheme->themeName;
			$themes[$webTheme->id] = $label;
		}
		return $themes;
	}

	/**
	 * Validates WCAG contrast ratios for all six stored color values.
	 *
	 * Checks performed (minimum ratio: 3.0):
	 *   1–2. primaryColor   vs. page bg + card bg
	 *   3.   primaryTextColor  vs. primaryColor
	 *   4–5. secondaryColor vs. page bg + card bg
	 *   6.   secondaryTextColor vs. secondaryColor
	 *   7–8. tertiaryColor  vs. page bg + card bg
	 *   9.   tertiaryTextColor  vs. tertiaryColor
	 *
	 * @return array{validatedOk: bool, errors: string[]}
	 */
	public function validateColorContrast(): array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		$minRatio = 3.0;

		if ($this->baseMode === 'dark') {
			$pageBg = self::DARK_BG;
			$cardBg = self::DARK_CARD_BG;
		} else {
			$pageBg = self::LIGHT_BG;
			$cardBg = self::LIGHT_CARD_BG;
		}

		$colorPairs = [
			'Primary' => [
				$this->primaryColor,
				$this->primaryTextColor
			],
			'Secondary' => [
				$this->secondaryColor,
				$this->secondaryTextColor
			],
			'Tertiary' => [
				$this->tertiaryColor,
				$this->tertiaryTextColor
			],
		];

		foreach ($colorPairs as $label => [$brandColor, $textColor]) {
			if (empty($brandColor) || empty($textColor)) {
				continue;
			}

			// Brand color vs. page background
			$pageContrast = ColorUtils::calculateColorContrast($brandColor, $pageBg);
			if ($pageContrast < $minRatio) {
				$validationResults['errors'][] = "$label color does not have sufficient contrast against the page background (ratio: $pageContrast, minimum: $minRatio).";
			}

			// Brand color vs. card background
			$cardContrast = ColorUtils::calculateColorContrast($brandColor, $cardBg);
			if ($cardContrast < $minRatio) {
				$validationResults['errors'][] = "$label color does not have sufficient contrast against the card background (ratio: $cardContrast, minimum: $minRatio).";
			}

			// Text color vs. brand color
			$textContrast = ColorUtils::calculateColorContrast($textColor, $brandColor);
			if ($textContrast < $minRatio) {
				$validationResults['errors'][] = "$label text color does not have sufficient contrast against the $label color (ratio: $textContrast, minimum: $minRatio).";
			}
		}

		if (!empty($validationResults['errors'])) {
			$validationResults['validatedOk'] = false;
		}

		return $validationResults;
	}

	public function __get($name) {
		switch ($name) {
			case 'backgroundColor':
				return $this->baseMode === 'dark' ? self::DARK_BG : self::LIGHT_BG;
			case 'textColor':
				return $this->baseMode === 'dark' ? self::DARK_TEXT : self::LIGHT_TEXT;
		}
		if ($name === 'libraries') {
			return $this->getLibraries();
		}
		if ($name === 'locations') {
			return $this->getLocations();
		}
		return parent::__get($name);
	}

	public function __set($name, $value) {
		// Silently ignore any attempt to set the fixed base color properties
		if (in_array($name, self::BASE_COLOR_PROPERTIES, true)) {
			return;
		}
		if ($name === 'libraries') {
			$this->_libraries = $value;
		} elseif ($name === 'locations') {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new AspenLiDAThemeLibrary();
			$obj->themeId = $this->id;
			$obj->orderBy('weight');
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->id] = clone $obj;
			}
		}
		return $this->_libraries;
	}

	public function getLocations(): ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new AspenLiDAThemeLocation();
			$obj->themeId = $this->id;
			$obj->orderBy('weight');
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->id] = clone $obj;
			}
		}
		return $this->_locations;
	}

	public function insert(string $context = ''): int|bool {
		$ret = parent::insert();
		if ($ret !== false) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function update(string $context = ''): int|bool {
		$ret = parent::update();
		if ($ret !== false) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$this->clearOneToManyOptions('AspenLiDAThemeLibrary', 'themeId');
			$this->clearOneToManyOptions('AspenLiDAThemeLocation', 'themeId');
		}
		return $ret;
	}

	public function saveLibraries(): void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			// First, check if any libraries would be left without themes if removed.
			require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
			$librariesToDelete = [];
			$librariesWithOnlyThisTheme = [];
			$preventDeletion = false;

			foreach ($this->_libraries as $obj) {
				/** @var AspenLiDAThemeLibrary $obj */
				if ($obj->_deleteOnSave) {
					$librariesToDelete[] = $obj;
					// Check if this is the library's only theme.
					$library = new Library();
					$library->libraryId = $obj->libraryId;
					if ($library->find(true)) {
						if (!$library->hasMultipleThemes()) {
							$preventDeletion = true;
							$librariesWithOnlyThisTheme[] = $library->displayName;
						}
					}
				}
			}

			// If any libraries would be left without themes, show error message and abort.
			if ($preventDeletion) {
				if (count($librariesWithOnlyThisTheme) == 1) {
					$preventionMessage = translate([
						'text' => 'Library %1% cannot be removed from this theme because it is the only theme for the library. Before proceeding, please assign another theme to this library.',
						'isAdminFacing' => true,
						1 => $librariesWithOnlyThisTheme[0]
					]);
				} else {
					$libraryList = implode('", "', $librariesWithOnlyThisTheme);
					$preventionMessage = translate([
						'text' => 'The following libraries cannot be removed from this theme because it is their only theme: %1%. Before proceeding, please assign another theme to these libraries.',
						'isAdminFacing' => true,
						1 => $libraryList
					]);
				}

				$user = UserAccount::getActiveUserObj();
				if ($user) {
					$user->updateMessage = $preventionMessage;
					$user->updateMessageIsError = true;
					$user->update();
				}

				// Remove the libraries marked for deletion from the _deleteOnSave list.
				foreach ($librariesToDelete as $obj) {
					$obj->_deleteOnSave = false;
				}
			}

			// Continue with normal save procedure for non-deleted libraries.
			foreach ($this->_libraries as $obj) {
				/** @var AspenLiDAThemeLibrary $obj */
				if ($obj->_deleteOnSave) {
					$obj->delete();
				} else {
					if (isset($obj->{$obj->__primaryKey}) && is_numeric($obj->{$obj->__primaryKey})) {
						if ($obj->{$obj->__primaryKey} <= 0) {
							$obj->themeId = $this->{$this->__primaryKey};
							$obj->insert();
						} else {
							if ($obj->hasChanges()) {
								$obj->update();
							}
						}
					} else {
						// Set the appropriate weight for the new theme.
						$weight = 0;
						$existingThemesForLibrary = new AspenLiDAThemeLibrary();
						$existingThemesForLibrary->libraryId = $obj->libraryId;
						if ($existingThemesForLibrary->find()) {
							while ($existingThemesForLibrary->fetch()) {
								$weight = $weight + 1;
							}
						}

						$obj->themeId = $this->{$this->__primaryKey};
						$obj->weight = $weight;
						$obj->insert();
					}
				}

			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(): void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			foreach ($this->_locations as $obj) {
				/** @var AspenLiDAThemeLocation $obj */
				if ($obj->_deleteOnSave) {
					$obj->delete();
				} else {
					if (isset($obj->{$obj->__primaryKey}) && is_numeric($obj->{$obj->__primaryKey})) {
						if ($obj->{$obj->__primaryKey} <= 0) {
							$obj->themeId = $this->{$this->__primaryKey};
							$obj->insert();
						} else {
							if ($obj->hasChanges()) {
								$obj->update();
							}
						}
					} else {
						// set appropriate weight for new theme
						$weight = 0;
						$existingThemesForLocation = new AspenLiDAThemeLocation();
						$existingThemesForLocation->locationId = $obj->locationId;
						if ($existingThemesForLocation->find()) {
							while ($existingThemesForLocation->fetch()) {
								$weight = $weight + 1;
							}
						}

						$obj->themeId = $this->{$this->__primaryKey};
						$obj->weight = $weight;
						$obj->insert();
					}
				}

			}
			unset($this->_locations);
		}
	}

	public function clearLibraries(): void {
		$this->clearOneToManyOptions('AspenLiDAThemeLibrary', 'themeId');
		unset($this->_libraries);
	}

	public function clearLocations(): void {
		$this->clearOneToManyOptions('AspenLiDAThemeLocation', 'themeId');
		unset($this->_locations);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/AspenLiDA/Themes?objectAction=edit&id=' . $this->id;
	}

	protected static $_defaultTheme = null;

	/**
	 * Get the default theme or, failing that, get the first theme stored in the database
	 */
	public function getDefaultTheme(bool $resetTheme = false): AspenLiDATheme {
		if (self::$_defaultTheme != null && !$resetTheme) {
			return self::$_defaultTheme;
		}

		$defaultTheme = new AspenLiDATheme();
		$defaultTheme->name = 'default';

		if (!$defaultTheme->find(true)) {
			unset($defaultTheme->name);
			$defaultTheme->find(true);
		}

		self::$_defaultTheme = clone $defaultTheme;

		return self::$_defaultTheme;
	}

	public function getApiInfo(): array {
		$theme = [];
		$theme['id'] = $this->id;
		$theme['name'] = $this->name;
		$theme['baseMode'] = $this->baseMode;
		$theme['logo'] = $this->logo;
		$theme['header']['logo'] = $this->headerLogo;
		$theme['header']['alignment'] = $this->headerLogoAlignment;
		$theme['header']['backgroundColor'] = $this->headerLogoBackgroundColor;
		$theme['primary'] = ColorUtils::generatePalette($this->primaryColor, $this->primaryTextColor);
		$theme['secondary'] = ColorUtils::generatePalette($this->secondaryColor, $this->secondaryTextColor);
		$theme['tertiary'] = ColorUtils::generatePalette($this->tertiaryColor, $this->tertiaryTextColor);
		return $theme;
	}
}

