<?php

require_once ROOT_DIR . '/sys/Indexing/RecordToInclude.php';

class LibraryRecordToInclude extends RecordToInclude {
	public $__table = 'library_records_to_include';    // table name
	public $libraryId;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

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