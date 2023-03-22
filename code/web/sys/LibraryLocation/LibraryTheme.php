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
		$library->find();
		$libraryList = [];
		while ($library->fetch()) {
			$libraryList[$library->libraryId] = $library->displayName;
		}

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
				'label' => 'Library',
				'description' => 'A link to the library which the theme belongs to',
			],
			'theme' => [
				'property' => 'themeId',
				'type' => 'enum',
				'label' => 'Theme',
				'values' => $availableThemes,
				'description' => 'The theme which should be used for the library',
				'permissions' => ['Library Theme Configuration'],
			],
		];
	}
}