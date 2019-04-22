<?php

require_once 'Solr.php';
class GroupedWorksSolrConnector extends Solr
{
    /**
     * @return string
     */
    function getSearchSpecsFile()
    {
        return ROOT_DIR . '/../../sites/default/conf/groupedWorksSearchSpecs.yaml';
    }

    function getRecordByBarcode($barcode){
        if ($this->debug) {
            echo "<pre>Get Record by Barcode: $barcode</pre>\n";
        }

        // Query String Parameters
        $options = array('q' => "barcode:\"$barcode\"", 'fl' => SearchObject_GroupedWorkSearcher::$fields_to_return);
        $result = $this->_select('GET', $options);
        if ($result instanceof AspenError) {
            AspenError::raiseError($result);
        }

        if (isset($result['response']['docs'][0])){
            return $result['response']['docs'][0];
        }else{
            return null;
        }
    }

    function getRecordByIsbn($isbns, $fieldsToReturn = null){
        // Query String Parameters
        if ($fieldsToReturn == null){
            $fieldsToReturn = SearchObject_GroupedWorkSearcher::$fields_to_return;
        }
        $options = array('q' => 'isbn:' . implode(' OR ', $isbns), 'fl' => $fieldsToReturn);
        $result = $this->_select('GET', $options);
        if ($result instanceof AspenError) {
            AspenError::raiseError($result);
        }

        if (isset($result['response']['docs'][0])){
            return $result['response']['docs'][0];
        }else{
            return null;
        }
    }

    function searchForRecordIds($ids){
        if (count($ids) == 0){
            return array();
        }
        // Query String Parameters
        $idString = '';
        foreach ($ids as $id){
            if (strlen($idString) > 0){
                $idString .= ' OR ';
            }
            $idString .= "id:\"$id\"";
        }
        $options = array('q' => $idString, 'rows' => count($ids), 'fl' => SearchObject_GroupedWorkSearcher::$fields_to_return);
        $result = $this->_select('GET', $options);
        if ($result instanceof AspenError) {
            AspenError::raiseError($result);
        }
        return $result;
    }

    /**
     * Get records similar to one record
     * Uses MoreLikeThis Request Handler
     *
     * Uses SOLR MLT Query Handler
     *
     * @access	public
     * @var     string  $id             The id to retrieve similar titles for
     * @throws	object						PEAR Error
     * @return	array							An array of query results
     *
     */
    function getMoreLikeThis($id)
    {
        global $configArray;
        $originalResult = $this->getRecord($id, 'target_audience_full,target_audience_full,literary_form,language,isbn,upc,series');
        // Query String Parameters
        $options = array('q' => "id:$id", 'mlt.interestingTerms' => 'details', 'rows' => 25, 'fl' => SearchObject_GroupedWorkSearcher::$fields_to_return);
        if ($originalResult){
            $options['fq'] = array();
            if (isset($originalResult['target_audience_full'])){
                if (is_array($originalResult['target_audience_full'])){
                    $filter = '';
                    foreach ($originalResult['target_audience_full'] as $targetAudience){
                        if ($targetAudience != 'Unknown'){
                            if (strlen($filter) > 0){
                                $filter .= ' OR ';
                            }
                            $filter .= 'target_audience_full:"' . $targetAudience . '"';
                        }
                    }
                    if (strlen($filter) > 0){
                        $options['fq'][] = "($filter)";
                    }
                }else{
                    $options['fq'][] = 'target_audience_full:"' . $originalResult['target_audience_full'] . '"';
                }
            }
            if (isset($originalResult['literary_form'])){
                if (is_array($originalResult['literary_form'])){
                    $filter = '';
                    foreach ($originalResult['literary_form'] as $literaryForm){
                        if ($literaryForm != 'Not Coded'){
                            if (strlen($filter) > 0){
                                $filter .= ' OR ';
                            }
                            $filter .= 'literary_form:"' . $literaryForm . '"';
                        }
                    }
                    if (strlen($filter) > 0){
                        $options['fq'][] = "($filter)";
                    }
                }else{
                    $options['fq'][] = 'literary_form:"' . $originalResult['literary_form'] . '"';
                }
            }
            if (isset($originalResult['language'])){
                $options['fq'][] = 'language:"' . $originalResult['language'][0] . '"';
            }
            if (isset($originalResult['series'])){
                $options['fq'][] = '!series:"' . $originalResult['series'][0] . '"';
            }
            //Don't want to get other editions of the same work (that's a different query)
        }

        $searchLibrary = Library::getSearchLibrary();
        $searchLocation = Location::getSearchLocation();
        if ($searchLibrary && $searchLocation){
            if ($searchLibrary->ilsCode == $searchLocation->code){
                $searchLocation = null;
            }
        }

        $scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);
        foreach ($scopingFilters as $filter){
            $options['fq'][] = $filter;
        }
        $boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation);
        if ($configArray['Index']['enableBoosting']){
            $options['bf'] = $boostFactors;
        }

        $result = $this->_select('GET', $options, false, 'mlt');
        if ($result instanceof AspenError) {
            AspenError::raiseError($result);
        }

        return $result;
    }

    /**
     * Get records similar to one record
     * Uses MoreLikeThis Request Handler
     *
     * Uses SOLR MLT Query Handler
     *
     * @access	public
     * @var     string[]  $ids     A list of ids to return data for
     * @var     string[]  $notInterestedIds     A list of ids the user is not interested in
     * @throws	object						PEAR Error
     * @return	array							An array of query results
     *
     */
    function getMoreLikeThese($ids, $notInterestedIds)
    {
        global $configArray;
        // Query String Parameters
        $idString = implode(' OR ', $ids);
        $options = array('q' => "id:($idString)", 'qt' => 'morelikethese', 'mlt.interestingTerms' => 'details', 'rows' => 25);

        $searchLibrary = Library::getSearchLibrary();
        $searchLocation = Location::getSearchLocation();
        $scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);

        $notInterestedString = implode(' OR ', $notInterestedIds);
        if (strlen($notInterestedString) > 0){
            $idString .= ' OR ' . $notInterestedString;
        }
        $options['fq'][] = "-id:($idString)";
        foreach ($scopingFilters as $filter){
            $options['fq'][] = $filter;
        }
        $boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation);
        if ($configArray['Index']['enableBoosting']){
            $options['bf'] = $boostFactors;
        }

        $options['rows'] = 30;

        // TODO: Limit Fields
        if ($this->debug && isset($fields)) {
            $options['fl'] = $fields;
        } else {
            // This should be an explicit list
            $options['fl'] = '*,score';
        }
        $result = $this->_select('GET', $options);
        if ($result instanceof AspenError) {
            AspenError::raiseError($result);
        }

        return $result;
    }

    /**
     * Normalize a sort option.
     *
     * @param string $sort The sort option.
     *
     * @return string			The normalized sort value.
     * @access private
     */
    protected function _normalizeSort($sort)
    {
        // Break apart sort into field name and sort direction (note error
        // suppression to prevent notice when direction is left blank):
        $sort = trim($sort);
        @list($sortField, $sortDirection) = explode(' ', $sort);

        // Default sort order (may be overridden by switch below):
        $defaultSortDirection = 'asc';

        // Translate special sort values into appropriate Solr fields:
        switch ($sortField) {
            case 'year':
            case 'publishDate':
                $sortField = 'publishDateSort';
                $defaultSortDirection = 'desc';
                break;
            case 'author':
                $sortField = 'authorStr asc, title_sort';
                break;
            case 'title':
                $sortField = 'title_sort asc, authorStr';
                break;
            case 'callnumber_sort':
                $searchLibrary = Library::getSearchLibrary($this->getSearchSource());
                if ($searchLibrary != null){
                    $sortField = 'callnumber_sort_' . $searchLibrary->subdomain;
                }

                break;
        }

        // Normalize sort direction to either "asc" or "desc":
        $sortDirection = strtolower(trim($sortDirection));
        if ($sortDirection != 'desc' && $sortDirection != 'asc') {
            $sortDirection = $defaultSortDirection;
        }

        return $sortField . ' ' . $sortDirection;
    }

    /** return string */
    public function getSearchesFile()
    {
       return 'groupedWorksSearches';
    }
}