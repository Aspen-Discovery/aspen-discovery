<?php
require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';

class LibraryEventsFacetSetting extends DataObject {
	public $__table = 'library_events_facet_setting';
	public $id;
	public $eventsFacetGroupId;
	public $libraryId;

	private $_facetGroup = false;

	/** @return EventsFacet[] */
	public function getFacets() {
		try {
			if (!is_null($this->getFacetGroup())) {
				return $this->getFacetGroup()->getFacets();
			} else {
				return [];
			}
		} catch (Exception $e) {
			return [];
		}
	}

	public function getFacetGroup(): ?EventsFacetGroup {
		try {
			if ($this->_facetGroup === false) {
				$this->_facetGroup = new EventsFacetGroup();
				$this->_facetGroup->id = $this->eventsFacetGroupId;
				if (!$this->_facetGroup->find(true)) {
					$this->_facetGroup = null;
				}
			}
			return $this->_facetGroup;
		} catch (Exception $e) {
			return null;
		}
	}
}