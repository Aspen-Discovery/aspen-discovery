<?php

use PHPUnit\Framework\TestCase;
define('PATH_TO_ROOT', __DIR__ . '/../../../../../');

class SeriesFacetConfigTests extends TestCase {

	public function __construct(string $name) {
		parent::__construct($name);
		require_once PATH_TO_ROOT . 'code/web/sys/SearchObject/SeriesSearcher.php';
	}

	public function testDefaultFacetsAreUsedWhenNoFacetGroupIsAssigned(): void {
		global $solrScope;
		$searcher = new SearchObject_SeriesSearcher();
		$facetConfig = $searcher->getFacetConfig();

		$expectedFacetNames = [
			'author_display',
			'audience_facet',
			'language',
			'literary_form',
			'subject',
		];
		$scopedFacetNames = [
			'econtent_source',
			'format',
			'format_category',
			'time_since_added',
			'time_since_updated',
		];
		$hasSolrScope = !empty($solrScope);
		if ($hasSolrScope) {
			$scopedFacetNames = [
				"econtent_source_$solrScope",
				"format_$solrScope",
				"format_category_$solrScope",
				"local_time_since_added_$solrScope",
				"local_time_since_updated_$solrScope",
			];
		}
		$expectedFacetNames = array_merge($expectedFacetNames, $scopedFacetNames);

		$this->assertEquals($expectedFacetNames, array_keys($facetConfig));
		foreach ($facetConfig as $facetName => $facet) {
			$this->assertEquals($facetName, $facet->facetName);
			$this->assertNotEmpty($facet->displayName);
		}
	}
}
