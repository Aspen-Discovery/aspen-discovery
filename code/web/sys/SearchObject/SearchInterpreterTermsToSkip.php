<?php /** @noinspection PhpMissingFieldTypeInspection */

class SearchInterpreterTermsToSkip extends DataObject {
	public $__table = 'search_interpreter_terms_to_skip';
	public $id;
	public $settingId;
	public $term;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
				'uniqueProperty' => true,
			],
			'term' => [
				'property' => 'term',
				'type' => 'text',
				'label' => 'Term',
				'description' => 'The term that has special handling',
				'default' => '',
				'maxLength' => 75
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}