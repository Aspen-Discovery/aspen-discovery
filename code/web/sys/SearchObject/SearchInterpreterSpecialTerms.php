<?php /** @noinspection PhpMissingFieldTypeInspection */

class SearchInterpreterSpecialTerms extends DataObject {
	public $__table = 'search_interpreter_special_terms';
	public $id;
	public $weight;
	public $settingId;
	public $term;
	public $processTerm;
	public $combineWithNewFilter;
	public $facetsToApply;
	public $sortToApply;


	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$groupedWorkSearcher = SearchObjectFactory::initSearchObject();
		$sortOptions = array_merge(['' => 'None'], $groupedWorkSearcher->getSortOptions());

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
				'uniqueProperty' => true,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
			],
			'term' => [
				'property' => 'term',
				'type' => 'regularExpression',
				'label' => 'Term',
				'description' => 'The term that has special handling.',
				'note' => 'This can be a regular expression',
				'default' => '',
				'maxLength' => 255
			],
			'combineWithNewFilter' => [
				'property' => 'combineWithNewFilter',
				'type' => 'checkbox',
				'label' => 'Allow New Filter',
				'description' => 'Whether the value should be checked for new searches.',
				'default' => 1,
			],
			'facetsToApply' => [
				'property' => 'facetsToApply',
				'type' => 'textarea',
				'label' => 'Facets To Apply',
				'description' => 'A list of facets that will be applied when this term matches.',
				'noteBullets' => [
					"Each Facet should be on it's own line",
					"Format each facet to apply as <em>facet_name</em>:<em>facet_value</em>"
				],
				'default' => '',
			],
			'sortToApply' => [
				'property' => 'sortToApply',
				'type' => 'enum',
				'label' => 'Sort to Apply',
				'values' => $sortOptions,
				'description' => 'The sort to apply when this term is found',
				'default' => '',
				'maxLength' => 50
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}