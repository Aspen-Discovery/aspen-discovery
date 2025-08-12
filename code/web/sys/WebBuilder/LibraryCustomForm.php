<?php /** @noinspection PhpMissingFieldTypeInspection */


class LibraryCustomForm extends DataObject {
	public $__table = 'library_web_builder_custom_form';
	public $id;
	public $libraryId;
	public $formId;
	public $emailResultsTo;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(false);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the email list within the database',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library',
			],
			'emailResultsTo' => [
				'property' => 'emailResultsTo',
				'type' => 'text',
				'label' => 'Email Results To (separate multiple addresses with semi-colons)',
				'description' => 'Email Results To (separate multiple addresses with semi-colons)',
			],
		];
		return $structure;
	}
}