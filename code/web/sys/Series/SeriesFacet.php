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

	private static array $_defaultFacets = [
		'author_display' => ['multiSelect' => false, 'numEntriesToShowByDefault' => 5, 'translate' => true, 'useMoreFacetPopup' => true],
		'audience_facet' => ['multiSelect' => true, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
		'language' => ['multiSelect' => true, 'numEntriesToShowByDefault' => 10, 'translate' => false, 'useMoreFacetPopup' => false],
		'literary_form' => ['multiSelect' => false, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
		'subject' => ['multiSelect' => true, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => true],
		'econtent_source' => ['multiSelect' => true, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
		'format' => ['multiSelect' => true, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
		'format_category' => ['multiSelect' => true, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
		'time_since_added' => ['multiSelect' => false, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
		'time_since_updated' => ['multiSelect' => false, 'numEntriesToShowByDefault' => 10, 'translate' => true, 'useMoreFacetPopup' => false],
	];

	/** @return SeriesFacet[] */
	public static function getDefaultFacets() : array {
		$availableFacets = self::getAvailableFacets();
		$defaultFacets = [];
		$weight = 1;
		foreach (self::$_defaultFacets as $facetName => $facetSettings) {
			$facet = new SeriesFacet();
			$facet->id = $weight;
			$facet->weight = $weight;
			$facet->facetName = $facetName;
			$facet->displayName = $availableFacets[$facetName];
			$facet->multiSelect = $facetSettings['multiSelect'];
			$facet->numEntriesToShowByDefault = $facetSettings['numEntriesToShowByDefault'];
			$facet->translate = $facetSettings['translate'];
			$facet->useMoreFacetPopup = $facetSettings['useMoreFacetPopup'];
			$facet->collapseByDefault = true;
			$defaultFacets[] = $facet;
			$weight++;
		}
		return $defaultFacets;
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
