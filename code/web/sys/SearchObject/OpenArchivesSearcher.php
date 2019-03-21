<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
class SearchObject_OpenArchivesSearcher extends SearchObject_SolrSearcher
{
    public function __construct(){
        parent::__construct();

        global $configArray;
        global $timer;

        $this->resultsModule = 'OpenArchives';

        $this->searchType = 'open_archives';
        $this->basicSearchType = 'open_archives';

        require_once ROOT_DIR . "/sys/SolrConnector/OpenArchivesSolrConnector.php";
        $this->indexEngine = new OpenArchivesSolrConnector($configArray['Index']['url']);
        $timer->logTime('Created Index Engine for Open Archives');

        $this->allFacetSettings = getExtraConfigArray('openArchivesFacets');
        $this->facetConfig = array();
        $facetLimit = $this->getFacetSetting('Results_Settings', 'facet_limit');
        if (is_numeric($facetLimit)) {
            $this->facetLimit = $facetLimit;
        }
        $translatedFacets = $this->getFacetSetting('Advanced_Settings', 'translated_facets');
        if (is_array($translatedFacets)) {
            $this->translatedFacets = $translatedFacets;
        }

        // Load Spelling preferences
        $this->spellcheck    = $configArray['Spelling']['enabled'];
        $this->spellingLimit = $configArray['Spelling']['limit'];
        $this->spellSimple   = $configArray['Spelling']['simple'];
        $this->spellSkipNumeric = isset($configArray['Spelling']['skip_numeric']) ?
            $configArray['Spelling']['skip_numeric'] : true;

        // Debugging
        $this->indexEngine->debug = $this->debug;
        $this->indexEngine->debugSolrQuery = $this->debugSolrQuery;

        $timer->logTime('Setup Open Archives Search Object');
    }

    /**
     * Initialise the object from the global
     *  search parameters in $_REQUEST.
     *
     * @access  public
     * @return  boolean
     */
    public function init($searchSource = null)
    {
        // Call the standard initialization routine in the parent:
        parent::init('open_archives');

        //********************
        // Check if we have a saved search to restore -- if restored successfully,
        // our work here is done; if there is an error, we should report failure;
        // if restoreSavedSearch returns false, we should proceed as normal.
        $restored = $this->restoreSavedSearch();
        if ($restored === true) {
            return true;
        } else if (PEAR_Singleton::isError($restored)) {
            return false;
        }

        //********************
        // Initialize standard search parameters
        $this->initView();
        $this->initPage();
        $this->initSort();
        $this->initFilters();

        //********************
        // Basic Search logic
        if ($this->initBasicSearch()) {
            // If we found a basic search, we don't need to do anything further.
        } else {
            $this->initAdvancedSearch();
        }

        // If a query override has been specified, log it here
        if (isset($_REQUEST['q'])) {
            $this->query = $_REQUEST['q'];
        }

        return true;
    } // End init()

    public function getSpellingSuggestions()
    {
        // TODO: Implement getSpellingSuggestions() method.
    }

    public function getBasicTypes()
    {
        return [
            'OpenArchivesKeyword' => 'Keyword',
            'OpenArchivesTitle' => 'Title',
            'OpenArchivesSubject' => 'Subject',
        ];
    }

    /**
     * Turn our results into an Excel document
     */
    public function buildExcel($result = null)
    {
        // TODO: Implement buildExcel() method.
    }

    public function getUniqueField(){
        return 'identifier';
    }

    public function getRecordDriverForResult($current)
    {
        require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';
        return new OpenArchivesRecordDriver($current);
    }
}