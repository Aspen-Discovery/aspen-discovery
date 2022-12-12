<?php

require_once ROOT_DIR . '/sys/Indexing/RecordOwned.php';

class LibraryRecordOwned extends RecordOwned {
	public $__table = 'library_records_owned';    // table name
	public $libraryId;

	static function getObjectStructure($context = ''): array {
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

		$structure = parent::getObjectStructure($context);
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