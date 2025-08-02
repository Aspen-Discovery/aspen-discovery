<?php

require_once ROOT_DIR . '/sys/LibraryLocation/CombinedResultSection.php';

class LibraryCombinedResultSection extends CombinedResultSection {
	public $__table = 'library_combined_results_section';    // table name
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