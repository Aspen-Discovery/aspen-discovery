<?php

require_once ROOT_DIR . '/sys/LibraryLocation/CombinedResultSection.php';

class LibraryCombinedResultSection extends CombinedResultSection {
	public $__table = 'library_combined_results_section';    // table name
	public $libraryId;

	static function getObjectStructure(): array {
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

		$structure = parent::getObjectStructure();
		$structure['libraryId'] = [
			'property' => 'libraryId',
			'type' => 'enum',
			'values' => $libraryList,
			'label' => 'Library',
			'description' => 'The id of a library',
		];

		return $structure;
	}
}