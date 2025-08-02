<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Holiday extends DataObject {
	public $__table = 'holiday';   // table name
	public $id;                    // int(11)  not_null primary_key auto_increment
	public $libraryId;             // int(11)
	public $date;                  // date
	public $name;                  // varchar(100)


	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(false);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the holiday within the database',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library',
			],
			'date' => [
				'property' => 'date',
				'type' => 'date',
				'label' => 'Date',
				'description' => 'The date of a holiday.',
				'required' => true,
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Holiday Name',
				'description' => 'The name of a holiday',
			],
		];
		return $structure;
	}
}