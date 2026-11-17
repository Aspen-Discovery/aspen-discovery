<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Series/SeriesFacetGroup.php';

class LibrarySeriesFacetSetting extends DataObject {
	public $__table = 'library_series_facet_setting';
	public $id;
	public $seriesFacetGroupId;
	public $libraryId;

	private $_facetGroup = false;

	/** @return SeriesFacet[] */
	public function getFacets() : array {
		try {
			$facetGroup = $this->getFacetGroup();
			if (is_null($facetGroup)) {
				return [];
			}
			return $facetGroup->getFacets() ?? [];
		} catch (Exception) {
			return [];
		}
	}

	public function getFacetGroup(): ?SeriesFacetGroup {
		try {
			if ($this->_facetGroup !== false) {
				return $this->_facetGroup;
			}
			$this->_facetGroup = new SeriesFacetGroup();
			$this->_facetGroup->id = $this->seriesFacetGroupId;
			$groupExists = $this->_facetGroup->find(true);
			if (!$groupExists) {
				$this->_facetGroup = null;
			}
			return $this->_facetGroup;
		} catch (Exception) {
			return null;
		}
	}
}
