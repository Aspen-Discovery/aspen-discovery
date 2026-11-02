<?php

use PHPUnit\Framework\TestCase;
define('PATH_TO_ROOT', __DIR__ . '/../../../../../');

class SeriesFacetConfigTests extends TestCase {

	private $originalLibrary;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once PATH_TO_ROOT . 'code/web/sys/SearchObject/SeriesSearcher.php';
		require_once PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Library.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Series/SeriesFacetGroup.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Series/SeriesFacet.php';
		require_once PATH_TO_ROOT . 'code/web/sys/Series/LibrarySeriesFacetSetting.php';
	}

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		global $aspen_db;

		require_once PATH_TO_ROOT . 'code/web/sys/DBMaintenance/version_updates/26.11.00.php';
		$seriesFacetUpdate = getUpdates26_11_00()['series_facet_settings'];
		foreach ($seriesFacetUpdate['sql'] as $createTableStatement) {
			$aspen_db->exec($createTableStatement);
		}
	}

	protected function setUp(): void {
		parent::setUp();

		global $library;

		$this->originalLibrary = $library;
		$uncachedLibrary = new Library();
		$uncachedLibrary->libraryId = Library::getActiveLibrary()->libraryId;
		$uncachedLibrary->find(true);
		$library = $uncachedLibrary;
	}

	protected function tearDown(): void {
		global $aspen_db;
		global $library;

		$library = $this->originalLibrary;
		$aspen_db->exec("DELETE FROM library_series_facet_setting");
		$aspen_db->exec("DELETE FROM series_facet");
		$aspen_db->exec("DELETE FROM series_facet_groups WHERE name LIKE 'PHPUnit%'");

		parent::tearDown();
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

	public function testOnlyTheAssignedFacetGroupIsUsed(): void {
		$facetGroup = $this->createFacetGroup('PHPUnit Series Facets', [
			'author_display',
			'language',
		]);
		$this->assignFacetGroupToActiveLibrary($facetGroup);

		$searcher = new SearchObject_SeriesSearcher();
		$facetConfig = $searcher->getFacetConfig();

		$this->assertEquals([
			'author_display',
			'language',
		], array_keys($facetConfig));
	}

	public function testAnEmptyFacetGroupShowsNoFacets(): void {
		$facetGroup = $this->createFacetGroup('PHPUnit Empty Series Facets', []);
		$this->assignFacetGroupToActiveLibrary($facetGroup);

		$searcher = new SearchObject_SeriesSearcher();
		$facetConfig = $searcher->getFacetConfig();

		$this->assertEquals([], $facetConfig);
	}

	public function testSavingALibraryOnAnotherGroupMovesTheLibrary(): void {
		$libraryId = Library::getActiveLibrary()->libraryId;
		$firstGroup = $this->createFacetGroup('PHPUnit First Series Facets', ['author_display']);
		$firstGroup->libraries = [$libraryId];
		$firstGroup->update();
		$secondGroup = $this->createFacetGroup('PHPUnit Second Series Facets', ['language']);
		$secondGroup->libraries = [$libraryId];
		$secondGroup->update();

		$librarySeriesFacetSetting = new LibrarySeriesFacetSetting();
		$librarySeriesFacetSetting->libraryId = $libraryId;
		$this->assertEquals(1, $librarySeriesFacetSetting->count());
		$librarySeriesFacetSetting->find(true);
		$this->assertEquals($secondGroup->id, $librarySeriesFacetSetting->seriesFacetGroupId);
	}

	public function testDeletingAFacetGroupRemovesItsFacetsAndLibraryLinks(): void {
		$facetGroup = $this->createFacetGroup('PHPUnit Deleted Series Facets', [
			'author_display',
			'language',
		]);
		$this->assignFacetGroupToActiveLibrary($facetGroup);
		$facetGroupId = $facetGroup->id;

		$facetGroup->delete();

		$remainingFacet = new SeriesFacet();
		$remainingFacet->facetGroupId = $facetGroupId;
		$this->assertEquals(0, $remainingFacet->count());
		$remainingLibraryLink = new LibrarySeriesFacetSetting();
		$remainingLibraryLink->seriesFacetGroupId = $facetGroupId;
		$this->assertEquals(0, $remainingLibraryLink->count());
	}

	private function createFacetGroup(string $name, array $facetNames): SeriesFacetGroup {
		$facetGroup = new SeriesFacetGroup();
		$facetGroup->name = $name;
		$facetGroup->insert();

		$weight = 1;
		foreach ($facetNames as $facetName) {
			$facet = new SeriesFacet();
			$facet->facetGroupId = $facetGroup->id;
			$facet->facetName = $facetName;
			$facet->displayName = $facetName;
			$facet->weight = $weight;
			$facet->insert();
			$weight++;
		}
		return $facetGroup;
	}

	private function assignFacetGroupToActiveLibrary(SeriesFacetGroup $facetGroup): void {
		$librarySeriesFacetSetting = new LibrarySeriesFacetSetting();
		$librarySeriesFacetSetting->libraryId = Library::getActiveLibrary()->libraryId;
		$librarySeriesFacetSetting->seriesFacetGroupId = $facetGroup->id;
		$librarySeriesFacetSetting->insert();
	}
}
