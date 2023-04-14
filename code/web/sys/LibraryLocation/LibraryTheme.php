<?php

class LibraryTheme extends DataObject {
	public $__table = 'library_themes';
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

	static function getObjectStructure($context = ''): array {
		//Load Libraries for lookup values
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$libraryList = $library->fetchAll('libraryId', 'displayName');
		$library = new Library();
		$library->orderBy('displayName');
		$allLibraryList = $library->fetchAll('libraryId', 'displayName');

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
	}

	public function canActiveUserEdit() {
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

	function getEditLink($context): string {
		if ($context == 'libraries') {
			return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId;
		} else {
			return '/Admin/Themes?objectAction=edit&id=' . $this->themeId;
		}
	}
}