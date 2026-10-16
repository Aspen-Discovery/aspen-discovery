<?php
/** @noinspection PhpMissingFieldTypeInspection */

class AspenLiDAThemeLibrary extends DataObject {
	public $__table = 'aspen_lida_theme_libraries';
	public $__displayNameColumn = 'themeName';
	public $_themeName;
	public $id;
	public $themeId;
	public $libraryId;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'themeId',
			'libraryId',
			'weight',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$allLibraryList = Library::getLibraryList(false);
		$libraryList    = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Aspen LiDA Themes'));

		require_once ROOT_DIR . '/sys/AspenLiDA/Theme.php';
		$availableThemes = AspenLiDATheme::getThemeList();

		$structure = [
			'id' => [
				'property'    => 'id',
				'type'        => 'label',
				'label'       => 'Id',
				'description' => 'The unique id',
			],
			'libraryId' => [
				'property'    => 'libraryId',
				'type'        => 'enum',
				'values'      => $libraryList,
				'allValues'   => $allLibraryList,
				'label'       => 'Library',
				'description' => 'The library this theme is assigned to',
			],
			'themeId' => [
				'property'    => 'themeId',
				'type'        => 'enum',
				'values'      => $availableThemes,
				'label'       => 'Theme',
				'description' => 'The Aspen LiDA theme assigned to this library',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function canActiveUserEdit(): bool {
		if (UserAccount::userHasPermission('Administer All Aspen LiDA Themes')) {
			return true;
		}
		if (UserAccount::userHasPermission('Administer Library Aspen LiDA Themes')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			return $homeLibrary && $homeLibrary->libraryId == $this->libraryId;
		}
		return false;
	}

	public function __get($name) {
		if ($name == 'themeName') {
			if ($this->_themeName == null) {
				require_once ROOT_DIR . '/sys/AspenLiDA/Theme.php';
				$theme     = new AspenLiDATheme();
				$theme->id = $this->themeId;
				if ($theme->find(true)) {
					$this->_themeName = $theme->name;
				} else {
					$this->_themeName = '';
				}
			}
			return $this->_themeName;
		}
		return parent::__get($name);
	}

	public function getEditLink(string $context): string {
		return '/AspenLiDA/Themes?objectAction=edit&id=' . $this->themeId;
	}
}

