<?php
/** @noinspection PhpMissingFieldTypeInspection */

class LibraryTheme extends DataObject {
	public $__table = 'library_themes';
	public $__displayNameColumn = 'themeName';
	public $_themeName;
	public $id;
	public $libraryId;
	public $themeId;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'libraryId',
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
		$allLibraryList = Library::getLibraryList(false);
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the theme belongs to',
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

	public function canActiveUserEdit() : bool {
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary->libraryId == $this->libraryId) {
				return true;
			}else {
				return false;
			}
		}else {
			return true;
		}
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

	public function getEditLink(string $context): string {
		if ($context == 'libraries') {
			return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId;
		} else {
			return '/Admin/Themes?objectAction=edit&id=' . $this->themeId;
		}
	}
}