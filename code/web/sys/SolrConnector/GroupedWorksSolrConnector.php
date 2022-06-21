<?php

require_once 'Solr.php';
require_once ROOT_DIR . '/sys/SearchObject/GroupedWorkSearcher.php';

class GroupedWorksSolrConnector extends Solr
{
	/**
	 * @return string
	 */
	function getSearchSpecsFile()
	{
		return ROOT_DIR . '/../../sites/default/conf/groupedWorksSearchSpecs.yaml';
	}

	function getRecordByBarcode($barcode)
	{
		if ($this->debug) {
			echo "<pre>Get Record by Barcode: $barcode</pre>\n";
		}

		// Query String Parameters
		$options = array('q' => "barcode:\"$barcode\"", 'fl' => SearchObject_GroupedWorkSearcher::$fields_to_return);
		$result = $this->_select('GET', $options);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		if (isset($result['response']['docs'][0])) {
			return $result['response']['docs'][0];
		} else {
			return null;
		}
	}

	function getRecordByIsbn($isbns, $fieldsToReturn = null)
	{
		// Query String Parameters
		if ($fieldsToReturn == null) {
			$fieldsToReturn = SearchObject_GroupedWorkSearcher::$fields_to_return;
		}
		$options = array('q' => 'isbn:' . implode(' OR ', $isbns), 'fl' => $fieldsToReturn);
		$result = $this->_select('GET', $options);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		if (isset($result['response']['docs'][0])) {
			return $result['response']['docs'][0];
		} else {
			return null;
		}
	}

	function searchForRecordIds($ids)
	{
		if (count($ids) == 0) {
			return array();
		}
		// Query String Parameters
		$idString = '';
		foreach ($ids as $id) {
			if (strlen($idString) > 0) {
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
	 * @access    public
	 * @param $id
	 * @param bool $availableOnly
	 * @param bool $limitFormat
	 * @param null $limit
	 * @param null $fieldsToReturn
	 * @return    array                            An array of query results
	 *
	 */
	function getMoreLikeThis($id, $availableOnly = false, $limitFormat = true, $limit = null, $fieldsToReturn = null)
	{
		$originalResult = $this->getRecord($id, 'target_audience_full,mpaa_rating,literary_form,language,isbn,upc,series');
		// Query String Parameters
		if ($fieldsToReturn == null){
			$fieldsToReturn = SearchObject_GroupedWorkSearcher::$fields_to_return;
		}
		$options = array('q' => "id:$id", 'mlt.interestingTerms' => 'details', 'rows' => 25, 'fl' => $fieldsToReturn);
		if ($originalResult) {
			$options['fq'] = array();
			if (isset($originalResult['target_audience_full'])) {
				if (is_array($originalResult['target_audience_full'])) {
					$filter = '';
					foreach ($originalResult['target_audience_full'] as $targetAudience) {
						if ($targetAudience != 'Unknown') {
							if (strlen($filter) > 0) {
								$filter .= ' OR ';
							}
							$filter .= 'target_audience_full:"' . $targetAudience . '"';
						}
					}
					if (strlen($filter) > 0) {
						$options['fq'][] = "($filter)";
					}
				} else {
					$options['fq'][] = 'target_audience_full:"' . $originalResult['target_audience_full'] . '"';
				}
			}
			if (isset($originalResult['mpaa_rating'])){
				if (is_array($originalResult['mpaa_rating'])) {
					$filter = '';
					foreach ($originalResult['mpaa_rating'] as $rating) {
						if (strlen($filter) > 0) {
							$filter .= ' OR ';
						}
						$filter .= 'mpaa_rating:"' . $rating . '"';
					}
					if (strlen($filter) > 0) {
						$options['fq'][] = "($filter)";
					}
				} else {
					$options['fq'][] = 'mpaa_rating:"' . $originalResult['mpaa_rating'] . '"';
				}
			}
			if (isset($originalResult['literary_form'])) {
				if (is_array($originalResult['literary_form'])) {
					$filter = '';
					foreach ($originalResult['literary_form'] as $literaryForm) {
						if ($literaryForm != 'Not Coded') {
							if (strlen($filter) > 0) {
								$filter .= ' OR ';
							}
							$filter .= 'literary_form:"' . $literaryForm . '"';
						}
					}
					if (strlen($filter) > 0) {
						$options['fq'][] = "($filter)";
					}
				} else {
					$options['fq'][] = 'literary_form:"' . $originalResult['literary_form'] . '"';
				}
			}
			if (isset($originalResult['language']) && count($originalResult['language']) == 1) {
				$options['fq'][] = 'language:"' . $originalResult['language'][0] . '"';
			}
			//Don't include results from the same series unless the library does not have NoveList
			require_once ROOT_DIR . '/sys/Enrichment/NovelistSetting.php';;
			$novelistSettings = new NovelistSetting();
			if ($novelistSettings->count() > 0) {
				if (isset($originalResult['series'])) {
					$options['fq'][] = '!series:"' . $originalResult['series'][0] . '"';
				}
			}
			//Don't want to get other editions of the same work (that's a different query)
		}

		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		if ($searchLibrary && $searchLocation) {
			if ($searchLibrary->ilsCode == $searchLocation->code) {
				$searchLocation = null;
			}
		}
		if ($availableOnly){
			global $solrScope;
			$options['fq'][] = "availability_toggle_{$solrScope}:available OR availability_toggle_{$solrScope}:available_online";
		}

		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);
		foreach ($scopingFilters as $filter) {
			$options['fq'][] = $filter;
		}

		if (UserAccount::isLoggedIn()){
			$options['fq'][] = '-user_rating_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_not_interested_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_reading_history_link:' . UserAccount::getActiveUserId();
		}

		$boostFactors = $this->getBoostFactors($searchLibrary);
		if (!empty($boostFactors)) {
			$options['bf'] = $boostFactors;
		}
		if ($limit != null && is_numeric($limit)){
			$options['rows'] = $limit;
		}

		$result = $this->_select('POST', $options, false, 'mlt');
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
	 * @access    public
	 *
	 * @param string[] $ids
	 * @param string $fieldsToReturn
	 * @param int $page
	 * @param int $limit
	 * @param string[] $notInterestedIds - An array of ids the patron is not interested in.  Can just load the last hour or so since they are also indexed.
	 * @return    array                            An array of query results
	 */
	function getMoreLikeThese($ids, $fieldsToReturn, $page = 1, $limit = 25, $notInterestedIds = [])
	{
		// Query String Parameters
		$idString = '';
		foreach ($ids as $index => $ratingInfo) {
			if (strlen($idString) > 0) {
				$idString .= ' OR ';
			}
			$ratingBoost = $ratingInfo['rating'];
			$idString .= "id:{$ratingInfo['workId']}^$ratingBoost";
		}
		//$idString = implode(' OR ', $ids);
		$options = array('q' => $idString, 'mlt.interestingTerms' => 'details', 'mlt.boost' => 'true', 'start' => ($page - 1) * $limit, 'rows' => $limit, 'fl' => $fieldsToReturn);

		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);

		if (UserAccount::isLoggedIn()){
			$options['fq'][] = '-user_rating_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_not_interested_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_reading_history_link:' . UserAccount::getActiveUserId();
		}
		if (count($notInterestedIds) > 0) {
			$notInterestedString = implode(' OR ', $notInterestedIds);
			$options['fq'][] = "-id:($notInterestedString)";
		}
		$options['fq'][] = "-id:($idString)";
		foreach ($scopingFilters as $filter) {
			$options['fq'][] = $filter;
		}
		$boostFactors = $this->getBoostFactors($searchLibrary);
		if (!empty($boostFactors)) {
			$options['bf'] = $boostFactors;
		}

		$result = $this->_select('POST', $options, true, 'mlt');
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
	 * @return string            The normalized sort value.
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
				if ($searchLibrary != null) {
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

	/**
	 * Load Boost factors for a query
	 *
	 * @param Library $searchLibrary
	 * @return array
	 */
	public function getBoostFactors($searchLibrary)
	{
		global $activeLanguage;

		$boostFactors = array();

		if (UserAccount::isLoggedIn()) {
			$searchPreferenceLanguage = UserAccount::getActiveUserObj()->searchPreferenceLanguage;
		} elseif (isset($_COOKIE['searchPreferenceLanguage'])) {
			$searchPreferenceLanguage = $_COOKIE['searchPreferenceLanguage'];
		} else {
			$searchPreferenceLanguage = 0;
		}

		if ($activeLanguage == null || $activeLanguage->code == 'en' || $searchPreferenceLanguage <= 0) {
			$applyHoldingsBoost = true;
			if (isset($searchLibrary) && !is_null($searchLibrary)) {
				$applyHoldingsBoost = $searchLibrary->getGroupedWorkDisplaySettings()->applyNumberOfHoldingsBoost;
			}
			if ($applyHoldingsBoost) {
				$boostFactors[] = 'product(format_boost,max(num_holdings,1),div(max(popularity,1),max(num_holdings,1)))';
			} else {
				$boostFactors[] = 'div(popularity,format_boost)';
			}
		} else {
			if ($searchPreferenceLanguage == 1) {
				//Apply a ridiculously high boost if the user wants to see foreign language materials first
				$boostFactors[] = 'product(999999999,termfreq(language,' . $activeLanguage->facetValue . '))';
			}
			$boostFactors[] = 'format_boost';
		}

		//Add rating as part of the ranking, normalize so ratings of less that 2.5 are below unrated entries.
		$boostFactors[] = 'max(rating,1)';

		global $solrScope;
		$boostFactors[] = "max(lib_boost_{$solrScope},1)";

		return $boostFactors;
	}

	/**
	 * Get filters based on scoping for the search
	 * @param Library $searchLibrary
	 * @param Location $searchLocation
	 * @return array
	 */
	public function getScopingFilters($searchLibrary, $searchLocation)
	{
		global $solrScope;

		$filter = array();

		//Simplify detecting which works are relevant to our scope
		if (!$solrScope) {
			//MDN: This does happen when called within migration tools
			if (isset($searchLocation)) {
				$filter[] = "scope_has_related_records:{$searchLocation->code}";
			} elseif (isset($searchLibrary)) {
				$filter[] = "scope_has_related_records:{$searchLibrary->subdomain}";
			}
		} else {
			$filter[] = "scope_has_related_records:$solrScope";
		}

		global $activeLanguage;
		if ($activeLanguage != null && $activeLanguage->code != 'en') {
			if (UserAccount::isLoggedIn()) {
				$searchPreferenceLanguage = UserAccount::getActiveUserObj()->searchPreferenceLanguage;
			} elseif (isset($_COOKIE['searchPreferenceLanguage'])) {
				$searchPreferenceLanguage = $_COOKIE['searchPreferenceLanguage'];
			} else {
				$searchPreferenceLanguage = 0;
			}
			if ($searchPreferenceLanguage == 2) {
				$filter[] = 'language:' . $activeLanguage->facetValue;
			}
		}

		return $filter;
	}

	protected function getHighlightOptions($fields, &$options){
		global $solrScope;
		$highlightFields = $fields . ",table_of_contents";
		$highlightFields = str_replace(",related_record_ids_$solrScope", '', $highlightFields);
		$highlightFields = str_replace(",related_items_$solrScope", '', $highlightFields);
		$highlightFields = str_replace(",format_$solrScope", '', $highlightFields);
		$highlightFields = str_replace(",format_category_$solrScope", '', $highlightFields);
		$options['hl'] = 'true';
		$options['hl.fl'] = $highlightFields;
		$options['hl.simple.pre'] = '{{{{START_HILITE}}}}';
		$options['hl.simple.post'] = '{{{{END_HILITE}}}}';
		$options['f.display_description.hl.fragsize'] = 50000;
		$options['f.title_display.hl.fragsize'] = 1000;
		$options['f.title_full.hl.fragsize'] = 1000;
	}
}