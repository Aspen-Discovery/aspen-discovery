<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Indexing/RecordToInclude.php';

class LibraryRecordToInclude extends RecordToInclude {
	public $__table = 'library_records_to_include';
	public $__displayNameColumn = 'location';
	public $libraryId;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = parent::getObjectStructure($context);
		$structure['libraryId'] = [
			'property' => 'libraryId',
			'type' => 'enum',
			'values' => $libraryList,
			'label' => 'Library',
			'description' => 'The id of a library',
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}