<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/LibraryLocation/FacetSetting.php';

class SeriesFacet extends FacetSetting {
	public $__table = 'series_facet';
	public $facetGroupId;

	public function getNumericColumnNames(): array {
		$numericColumns = parent::getNumericColumnNames();
		$numericColumns[] = 'facetGroupId';
		return $numericColumns;
	}

	public static function getAvailableFacets() : array {
		$availableFacets = [
			'author_display' => 'Author',
			'audience_facet' => 'Reading Level',
			'language' => 'Language',
			'literary_form' => 'Literary Form',
			'subject' => 'Subject',
			'econtent_source' => 'eContent Source',
			'format' => 'Format',
			'format_category' => 'Format Category',
			'time_since_added' => 'Added In the Last',
			'time_since_updated' => 'Updated In the Last',
		];

		asort($availableFacets);
		return $availableFacets;
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = parent::getBaseObjectStructure($context, self::getAvailableFacets());
		unset($structure['showInResults']);
		unset($structure['showInAdvancedSearch']);

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}
