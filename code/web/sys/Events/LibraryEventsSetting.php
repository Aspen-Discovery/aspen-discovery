<?php
require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';

class LibraryEventsSetting extends DataObject {
	public $__table = 'library_events_setting';
	public $id;
	public $settingSource;
	public $settingId;
    public $eventsFacetSettingsId;
	public $libraryId;

    private $_facetGroup = false;

    /** @return EventsFacet[] */
    public function getFacets() {
        try {
            return $this->getFacetGroup()->getFacets();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getFacetGroup(): ?EventsFacetGroup {
        try {
            if ($this->_facetGroup === false) {
                $this->_facetGroup = new EventsFacetGroup();
                $this->_facetGroup->id = $this->facetGroupId;
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