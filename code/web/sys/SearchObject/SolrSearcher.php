<?php

require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

abstract class SearchObject_SolrSearcher extends SearchObject_BaseSearcher
{
    protected $index = null;
    // Field List
    protected $fields = '*,score';
    /** @var Solr */
    protected $indexEngine = null;
    // Result
    protected $indexResult;

    // Facets
    protected $facetLimit = 30;
    protected $facetOffset = null;
    protected $facetPrefix = null;
    protected $facetSort = null;

    // Spelling
    protected $spellingLimit = 3;
    protected $spellQuery    = array();
    protected $dictionary    = 'default';
    protected $spellSimple   = false;
    protected $spellSkipNumeric = true;

    protected $debugSolrQuery = false;

    public function __construct(){
        parent::__construct();
        global $configArray;
        if ($this->debug && $configArray['System']['debugSolrQuery'] == true) {
            $this->debugSolrQuery = true;
        }
    }

    /**
     * Load all available facet settings.  This is mainly useful for showing
     * appropriate labels when an existing search has multiple filters associated
     * with it.
     *
     * @access  public
     * @param   string|false   $preferredSection    Section to favor when loading
     *                                              settings; if multiple sections
     *                                              contain the same facet, this
     *                                              section's description will be
     *                                              favored.
     */
    public function activateAllFacets($preferredSection = false)
    {
        foreach($this->allFacetSettings as $section => $values) {
            foreach($values as $key => $value) {
                $this->addFacet($key, $value);
            }
        }

        if ($preferredSection &&
            is_array($this->allFacetSettings[$preferredSection])) {
            foreach($this->allFacetSettings[$preferredSection] as $key => $value) {
                $this->addFacet($key, $value);
            }
        }
    }

    /**
     * Use the record driver to build an array of HTML displays from the search
     * results.
     *
     * @access  public
     * @return  array   Array of HTML chunks for individual records.
     */
    public function getResultRecordHTML()
    {
        global $interface;

        $html = array();
        for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
            $current = & $this->indexResult['response']['docs'][$x];

            $interface->assign('recordIndex', $x + 1);
            $interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
            /** @var IndexRecordDriver $record */
            $record = $this->getRecordDriverForResult($current);
            if (!PEAR_Singleton::isError($record)) {
                $interface->assign('recordDriver', $record);
                $html[] = $interface->fetch($record->getSearchResult($this->view));
            } else {
                $html[] = "Unable to find record";
            }
        }
        return $html;
    }

    /**
     * Actually process and submit the search
     *
     * @access  public
     * @param   bool   $returnIndexErrors  Should we die inside the index code if
     *                                     we encounter an error (false) or return
     *                                     it for access via the getIndexError()
     *                                     method (true)?
     * @param   bool   $recommendations    Should we process recommendations along
     *                                     with the search itself?
     * @param   bool   $preventQueryModification   Should we allow the search engine
     *                                             to modify the query or is it already
     *                                             a well formatted query
     * @return  array solr result structure (for now)
     */
    public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false)
    {
        // Our search has already been processed in init()
        $search = $this->searchTerms;

        // Build a recommendations module appropriate to the current search:
        if ($recommendations) {
            $this->initRecommendations();
        }

        // Build Query
        if ($preventQueryModification){
            $query = $search;
        }else{
            $query = $this->indexEngine->buildQuery($search, false);
        }
        if (PEAR_Singleton::isError($query)) {
            return $query;
        }

        // Only use the query we just built if there isn't an override in place.
        if ($this->query == null) {
            $this->query = $query;
        }

        // Define Filter Query
        $filterQuery = $this->hiddenFilters;
        //Remove any empty filters if we get them
        //(typically happens when a subdomain has a function disabled that is enabled in the main scope)
        foreach ($this->filterList as $field => $filter) {
            if (empty ($field)){
                unset($this->filterList[$field]);
            }
        }
        foreach ($this->filterList as $field => $filter) {
            foreach ($filter as $value) {
                // Special case -- allow trailing wildcards:
                if (substr($value, -1) == '*') {
                    $filterQuery[] = "$field:$value";
                } elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)){
                    $filterQuery[] = "$field:$value";
                } else {
                    if (!empty($value)){
                        $filterQuery[] = "$field:\"$value\"";
                    }
                }
            }
        }

        // If we are only searching one field use the DisMax handler
        //    for that field. If left at null let solr take care of it
        if (count($search) == 1 && isset($search[0]['index'])) {
            $this->index = $search[0]['index'];
        }

        // Build a list of facets we want from the index
        $facetSet = array();
        if (!empty($this->facetConfig)) {
            $facetSet['limit'] = $this->facetLimit;
            foreach ($this->facetConfig as $facetField => $facetName) {
                $facetSet['field'][] = $facetField;
            }
            if ($this->facetOffset != null) {
                $facetSet['offset'] = $this->facetOffset;
            }
            if ($this->facetPrefix != null) {
                $facetSet['prefix'] = $this->facetPrefix;
            }
            if ($this->facetSort != null) {
                $facetSet['sort'] = $this->facetSort;
            }
        }

        if (!empty($this->facetOptions)){
            $facetSet['additionalOptions'] = $this->facetOptions;
        }

        // Build our spellcheck query
        if ($this->spellcheck) {
            if ($this->spellSimple) {
                $this->useBasicDictionary();
            }
            $spellcheck = $this->buildSpellingQuery();

            // If the spellcheck query is purely numeric, skip it if
            // the appropriate setting is turned on.
            if ($this->spellSkipNumeric && is_numeric($spellcheck)) {
                $spellcheck = "";
            }
        } else {
            $spellcheck = "";
        }

        // Get time before the query
        $this->startQueryTimer();

        // The "relevance" sort option is a VuFind reserved word; we need to make
        // this null in order to achieve the desired effect with Solr:
        $finalSort = ($this->sort == 'relevance') ? null : $this->sort;

        // The first record to retrieve:
        //  (page - 1) * limit = start
        $recordStart = ($this->page - 1) * $this->limit;
        $this->indexResult = $this->indexEngine->search(
            $this->query,      // Query string
            $this->index,      // DisMax Handler
            $filterQuery,      // Filter query
            $recordStart,      // Starting record
            $this->limit,      // Records per page
            $facetSet,         // Fields to facet on
            $spellcheck,       // Spellcheck query
            $this->dictionary, // Spellcheck dictionary
            $finalSort,        // Field to sort on
            $this->fields,     // Fields to return
            'POST',     // HTTP Request method
            $returnIndexErrors // Include errors in response?
        );

        // Get time after the query
        $this->stopQueryTimer();

        // How many results were there?
        if (isset($this->indexResult['response']['numFound'])){
            $this->resultsTotal = $this->indexResult['response']['numFound'];
        }else{
            $this->resultsTotal = 0;
        }

        // Process spelling suggestions if no index error resulted from the query
        if ($this->spellcheck && !isset($this->indexResult['error'])) {
            // Shingle dictionary
            $this->processSpelling();
            // Make sure we don't endlessly loop
            if ($this->dictionary == 'default') {
                // Expand against the basic dictionary
                $this->basicSpelling();
            }
        }

        // If extra processing is needed for recommendations, do it now:
        if ($recommendations && is_array($this->recommend)) {
            foreach($this->recommend as $currentSet) {
                foreach($currentSet as $current) {
                    /** @var RecommendationInterface $current */
                    $current->process();
                }
            }
        }

        //Add debug information to the results if available
        if ($this->debug && isset($this->indexResult['debug'])){
            $explainInfo = $this->indexResult['debug']['explain'];
            foreach ($this->indexResult['response']['docs'] as $key => $result){
                if (array_key_exists($result[$this->getUniqueField()], $explainInfo)){
                    $result['explain'] = $explainInfo[$result[$this->getUniqueField()]];
                    $this->indexResult['response']['docs'][$key] = $result;
                }
            }
        }

        // Return the result set
        return $this->indexResult;
    }

    /**
     * Get error message from index response, if any.  This will only work if
     * processSearch was called with $returnIndexErrors set to true!
     *
     * @access  public
     * @return  mixed       false if no error, error string otherwise.
     */
    public function getIndexError()
    {
        return isset($this->indexResult['error']) ?
            $this->indexResult['error'] : false;
    }

    /**
     * Switch the spelling dictionary to basic
     *
     * @access  public
     */
    public function useBasicDictionary() {
        $this->dictionary = 'basicSpell';
    }

    /**
     * Adapt the search query to a spelling query
     *
     * @access  protected
     * @return  string    Spelling query
     */
    protected function buildSpellingQuery()
    {
        $this->spellQuery = array();
        // Basic search
        if ($this->searchType == $this->basicSearchType) {
            // Just the search query is fine
            return $this->query;

            // Advanced search
        } else {
            foreach ($this->searchTerms as $search) {
                foreach ($search['group'] as $field) {
                    // Add just the search terms to the list
                    $this->spellQuery[] = $field['lookfor'];
                }
            }
            // Return the list put together as a string
            return join(" ", $this->spellQuery);
        }
    }

    /**
     * Process spelling suggestions from the results object
     *
     * @access  private
     */
    protected function processSpelling()
    {
        global $configArray;

        // Do nothing if spelling is disabled
        if (!$configArray['Spelling']['enabled']) {
            return;
        }

        // Do nothing if there are no suggestions
        $suggestions = isset($this->indexResult['spellcheck']['suggestions']) ?
            $this->indexResult['spellcheck']['suggestions'] : array();
        if (count($suggestions) == 0) {
            return;
        }

        // Loop through the array of search terms we have suggestions for
        $suggestionList = array();
        foreach ($suggestions as $suggestion) {
            $ourTerm = $suggestion[0];

            // Skip numeric terms if numeric suggestions are disabled
            if ($this->spellSkipNumeric && is_numeric($ourTerm)) {
                continue;
            }

            $ourHit  = $suggestion[1]['origFreq'];
            $count   = $suggestion[1]['numFound'];
            $newList = $suggestion[1]['suggestion'];

            $validTerm = true;

            // Make sure the suggestion is for a valid search term.
            // Sometimes shingling will have bridged two search fields (in
            // an advanced search) or skipped over a stopword.
            if (!$this->findSearchTerm($ourTerm)) {
                $validTerm = false;
            }

            // Unless this term had no hits
            if ($ourHit != 0) {
                // Filter out suggestions we are already using
                $newList = $this->filterSpellingTerms($newList);
            }

            // Make sure it has suggestions and is valid
            if (count($newList) > 0 && $validTerm) {
                // Did we get more suggestions then our limit?
                if ($count > $this->spellingLimit) {
                    // Cut the list at the limit
                    array_splice($newList, $this->spellingLimit);
                }
                $suggestionList[$ourTerm]['freq'] = $ourHit;
                // Format the list nicely
                foreach ($newList as $item) {
                    if (is_array($item)) {
                        $suggestionList[$ourTerm]['suggestions'][$item['word']] = $item['freq'];
                    } else {
                        $suggestionList[$ourTerm]['suggestions'][$item] = 0;
                    }
                }
            }
        }
        $this->suggestions = $suggestionList;
    }

    /**
     * Filter a list of spelling suggestions to remove suggestions
     *   we are already searching for
     *
     * @access  private
     * @param   array    $termList List of suggestions
     * @return  array    Filtered list
     */
    protected function filterSpellingTerms($termList) {
        $newList = array();
        if (count($termList) == 0) return $newList;

        foreach ($termList as $term) {
            if (!$this->findSearchTerm($term['word'])) {
                $newList[] = $term;
            }
        }
        return $newList;
    }

    /**
     * Try running spelling against the basic dictionary.
     *   This function should ensure it doesn't return
     *   single word suggestions that have been accounted
     *   for in the shingle suggestions above.
     */
    protected function basicSpelling()
    {
        // TODO: There might be a way to run the
        //   search against both dictionaries from
        //   inside solr. Investigate. Currently
        //   submitting a second search for this.

        // Create a new search object
        $newSearch = SearchObjectFactory::initSearchObject('Genealogy');
        $newSearch->deminify($this->minify());

        // Activate the basic dictionary
        $newSearch->useBasicDictionary();
        // We don't want it in the search history
        $newSearch->disableLogging();

        // Run the search
        $newSearch->processSearch();
        // Get the spelling results
        $newList = $newSearch->getRawSuggestions();

        // If there were no shingle suggestions
        if (count($this->suggestions) == 0) {
            // Just use the basic ones as provided
            $this->suggestions = $newList;

            // Otherwise
        } else {
            // For all the new suggestions
            foreach ($newList as $word => $data) {
                // Check the old suggestions
                $found = false;
                foreach ($this->suggestions as $k => $v) {
                    // Make sure it wasn't part of a shingle
                    //   which has been suggested at a higher
                    //   level.
                    $found = preg_match("/\b$word\b/", $k) ? true : $found;
                }
                if (!$found) {
                    $this->suggestions[$word] = $data;
                }
            }
        }
    }

    public function getUniqueField(){
        return 'id';
    }

    public abstract function getRecordDriverForResult($record);

    /**
     * Process facets from the results object
     *
     * @access  public
     * @param   array   $filter         Array of field => on-screen description
     *                                  listing all of the desired facet fields;
     *                                  set to null to get all configured values.
     * @param   bool    $expandingLinks If true, we will include expanding URLs
     *                                  (i.e. get all matches for a facet, not
     *                                  just a limit to the current search) in
     *                                  the return array.
     * @return  array   Facets data arrays
     */
    public function getFacetList($filter = null, $expandingLinks = false)
    {
        // If there is no filter, we'll use all facets as the filter:
        if (is_null($filter)) {
            $filter = $this->facetConfig;
        }

        // Start building the facet list:
        $list = array();

        // If we have no facets to process, give up now
        if (!isset($this->indexResult['facet_counts'])){
            return $list;
        }elseif (!is_array($this->indexResult['facet_counts']['facet_fields']) && !is_array($this->indexResult['facet_counts']['facet_dates'])) {
            return $list;
        }

        // Loop through every field returned by the result set
        $validFields = array_keys($filter);

        if (isset($this->indexResult['facet_counts']['facet_dates'])){
            $allFacets = array_merge($this->indexResult['facet_counts']['facet_fields'], $this->indexResult['facet_counts']['facet_dates']);
        }else{
            $allFacets = $this->indexResult['facet_counts']['facet_fields'];
        }

        foreach ($allFacets as $field => $data) {
            // Skip filtered fields and empty arrays:
            if (!in_array($field, $validFields) || count($data) < 1) {
                continue;
            }

            // Initialize the settings for the current field
            $list[$field] = array();
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = array();

            // Should we translate values for the current facet?
            $translate = in_array($field, $this->translatedFacets);

            // Loop through values:
            foreach ($data as $facet) {
                // Initialize the array of data about the current facet:
                $currentSettings = array();
                $currentSettings['value'] = $facet[0];
                $currentSettings['display'] = $translate ? translate($facet[0]) : $facet[0];
                $currentSettings['count'] = $facet[1];
                $currentSettings['isApplied'] = false;
                $currentSettings['url'] = $this->renderLinkWithFilter("$field:".$facet[0]);
                // If we want to have expanding links (all values matching the facet)
                // in addition to limiting links (filter current search with facet),
                // do some extra work:
                if ($expandingLinks) {
                    $currentSettings['expandUrl'] = $this->getExpandingFacetLink($field, $facet[0]);
                }

                // Is this field a current filter?
                if (in_array($field, array_keys($this->filterList))) {
                    // and is this value a selected filter?
                    if (in_array($facet[0], $this->filterList[$field])) {
                        $currentSettings['isApplied'] = true;
                        $currentSettings['removalUrl'] =  $this->renderLinkWithoutFilter("$field:{$facet[0]}");
                    }
                }

                //Setup the key to allow sorting alphabetically if needed.
                $valueKey = $facet[0];

                // Store the collected values:
                $list[$field]['list'][$valueKey] = $currentSettings;
            }

            //How many facets should be shown by default
            $list[$field]['valuesToShow'] = 5;

            //Sort the facet alphabetically?
            //Sort the system and location alphabetically unless we are in the global scope
            $list[$field]['showAlphabetically'] = false;
            if ($list[$field]['showAlphabetically']){
                ksort($list[$field]['list']);
            }
        }
        return $list;
    }
}