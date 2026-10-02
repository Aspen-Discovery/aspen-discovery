<?php

require_once 'Solr.php';
require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
require_once ROOT_DIR . '/sys/SearchObject/GroupedWorkSearcher3.php';
require_once ROOT_DIR . '/sys/SystemVariables.php';

class GroupedWorksSolrConnector3 extends GroupedWorksSolrConnector2
{
	private array $childDocFields = [];
	private array $childDocFilters = [];

	function __construct($host, $index = '')
	{
		parent::__construct($host, 'grouped_works_v3');
	}

	function getDefaultFieldsToReturn() : string {
		return SearchObject_GroupedWorkSearcher3::$fields_to_return;
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param ?array $ids A list of document to retrieve from Solr
	 * @param ?string $fieldsToReturn An optional list of fields to return separated by commas
	 * @param bool $applyScoping whether scoping should be applied to the search
	 * @return    array                            The requested resources
	 * @throws    AspenError
	 */
	function getRecords(?array $ids, ?string $fieldsToReturn = null, bool $applyScoping = false) : array {
		if (empty($ids)) {
			return [];
		}
		//Solr does not seem to be able to return more than 50 records at a time,
		//If we have more than 50 ids, we will need to make multiple calls and
		//concatenate the results.
		$records = [];
		$startIndex = 0;
		$batchSize = 40;

		$lastBatch = false;
		while (true) {
			$endIndex = $startIndex + $batchSize;
			if ($endIndex >= count($ids)) {
				$lastBatch = true;
				$endIndex = count($ids);
				$batchSize = count($ids) - $startIndex;
			}
			$tmpIds = array_slice($ids, $startIndex, $batchSize);

			// Query String Parameters
			$idString = implode(' OR ', $tmpIds);
			$options = ['q' => "id:($idString)"];
			$options['fl'] = $fieldsToReturn;
			$options['rows'] = count($tmpIds);

			if ($applyScoping) {
				global $solrScope;
				$options['fq'] = "{!parent which=\"recordtype:grouped_work\" tag=child_filter}(availability_toggle:global AND scope:$solrScope)";
			}

			// Send Request
			global $timer;
			$timer->logTime("Prepare to send get (ids)  request to solr");
			$getRecordsUrl = $this->host . "/select?" . http_build_query($options);
			$result = $this->client->curlGetPage($getRecordsUrl);
			$timer->logTime("Send data to solr for getRecords");

			if ($result) {
				$result = $this->_process($result);

				foreach ($result['response']['docs'] as $record) {
					$records[$record['id']] = $record;
				}
			}
			if ($lastBatch) {
				break;
			} else {
				$startIndex = $endIndex;
			}
		}
		//echo("Found " . count($records) . " records.	Should have found " . count($ids) . "\r\n<br/>");
		return $records;
	}

	protected $mltPrefixThese = '{!mlt qf="language^1000 subject_facet^800 topic_facet^600 awards_facet^100 authorStr^75 era^50 genre_facet^50 geographic_facet^50 personal_name_facet^50 corporate_name_facet^50 content_rating accelerated_reader_interest_level mpaa_rating" mlt.fl=language,subject_facet,topic_facet,awards_facet,authorStr,era,genre_facet,geographic_facet,personal_name_facet,corporate_name_facet,content_rating,accelerated_reader_interest_level,mpaa_rating mintf=1 mindf=2}';
	protected $mltPrefixThis = '{!mlt qf="language^1000,subject_facet^800,topic_facet^600,awards_facet^100,authorStr^75,era^50,genre_facet^50,geographic_facet^50,personal_name_facet^50,corporate_name_facet^50,content_rating,accelerated_reader_interest_level,mpaa_rating" mintf=1 mindf=2}';

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
	function getMoreLikeThis($id, $selectedAvailabilityToggle = 'global', $availableOnly = false, $limitFormat = true, $limit = null, $format = null, $fieldsToReturn = null)
	{
		$originalResult = $this->getRecord($id, 'target_audience_full,content_rating,literary_form,language,isbn,upc,series');
		// Query String Parameters
		if ($fieldsToReturn == null) {
			$fieldsToReturn = $this->getDefaultFieldsToReturn();
		}
		$options = [
			'q' => "$this->mltPrefixThis$id",
			'mlt.interestingTerms' => 'details',
			'rows' => 25,
			'fl' => $fieldsToReturn,
		];
		if ($originalResult) {
			$options['fq'] = [];
			//Apply scoping filter
			global $solrScope;
			if ($availableOnly) {
				$options['fq'][] = "{!parent which=\"recordtype:grouped_work\" tag=child_filter}((availability_toggle:\"available\") OR (availability_toggle:\"available_online\") AND scope:$solrScope)";
			} else {
				$options['fq'][] = "{!parent which=\"recordtype:grouped_work\" tag=child_filter}(availability_toggle:\"$selectedAvailabilityToggle\" AND scope:$solrScope)";
			}
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
			if (isset($originalResult['content_rating'])) {
				if (is_array($originalResult['content_rating'])) {
					$filter = '';
					foreach ($originalResult['content_rating'] as $rating) {
						if (strlen($filter) > 0) {
							$filter .= ' OR ';
						}
						$filter .= 'content_rating:"' . $rating . '"';
					}
					if (strlen($filter) > 0) {
						$options['fq'][] = "($filter)";
					}
				} else {
					$options['fq'][] = 'content_rating:"' . $originalResult['content_rating'] . '"';
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
			if (isset($originalResult['language'])) {
				if (is_array($originalResult['language'])) {
					$filter = '';
					foreach ($originalResult['language'] as $literaryForm) {
						if ($literaryForm != 'Unknown') {
							if (strlen($filter) > 0) {
								$filter .= ' OR ';
							}
							$filter .= 'language:"' . $literaryForm . '"';
						}
					}
					if (strlen($filter) > 0) {
						$options['fq'][] = "($filter)";
					}
				} else {
					$options['fq'][] = 'language:"' . $originalResult['language'] . '"';
				}
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
		global $solrScope;
		if (isset($format) && $limitFormat) {
			$options['fq'][] = 'format:"' . $solrScope . '#' . $format . '"';
		}

		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);
		//TODO: Filter by scope here?

		foreach ($scopingFilters as $filter) {
			$options['fq'][] = $filter;
		}

		if (UserAccount::isLoggedIn()) {
			$options['fq'][] = '-user_rating_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_not_interested_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_reading_history_link:' . UserAccount::getActiveUserId();
		}

		$boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation, '', 'mlt');
		if (!empty($boostFactors)) {
			$options['bf'] = $boostFactors;
		}
		if ($limit != null && is_numeric($limit)) {
			$options['rows'] = $limit;
		}

		//$options['debugQuery'] = "true";
		//$debugInfo = print_r($options, true);

		$result = $this->_select('POST', $options);
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
	 * @param array[] $ids
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
		$idStringWithoutFieldPrefix = '';
		$numIdsProcessed = 0;
		foreach ($ids as $index => $ratingInfo) {
			if (strlen($idString) > 0) {
				$idString .= ' OR ';
				$idStringWithoutFieldPrefix .= ' OR ';
			}
			if (empty($ratingInfo['rating'])) {
				$idString .= "$this->mltPrefixThese{$ratingInfo['workId']}";
			} else {
				$ratingBoost = $ratingInfo['rating'];
				$idString .= "($this->mltPrefixThese{$ratingInfo['workId']})^$ratingBoost";
			}
			$idStringWithoutFieldPrefix .= $ratingInfo['workId'];
			$numIdsProcessed++;
			//Only process up to 500 IDs at a time to avoid overwhelming solr
			if ($numIdsProcessed >= 500) {
				break;
			}
		}
		$options = [
			'q' => "$idString",
			'mlt.interestingTerms' => 'details',
			'mlt.boost' => 'true',
			'start' => ($page - 1) * $limit,
			'rows' => $limit,
			'fl' => $fieldsToReturn,
		];

		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);

		if (UserAccount::isLoggedIn()) {
			$options['fq'][] = '-user_rating_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_not_interested_link:' . UserAccount::getActiveUserId();
			$options['fq'][] = '-user_reading_history_link:' . UserAccount::getActiveUserId();
		}
		$options['fq'][] = 'recordtype:grouped_work';
		$options['fq'][] = "-id:($idStringWithoutFieldPrefix)";
		foreach ($scopingFilters as $filter) {
			$options['fq'][] = $filter;
		}
		$boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation, '', 'mlt');
		if (!empty($boostFactors)) {
			$options['bf'] = $boostFactors;
		}

		$debugInfo = print_r($options, true);

		$result = $this->_select('POST', $options, true);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	public function setChildDocFields(array $childDocFields) : void {
		$this->childDocFields = $childDocFields;
	}

	public function setChildDocFilters(array $childDocFilters) : void {
		$this->childDocFilters = $childDocFilters;
	}

	/**
	 * Normalize a sort option.
	 *
	 * @param string $sort The sort option.
	 *
	 * @return string            The normalized sort value.
	 * @access private
	 */
	protected function _normalizeSort($sort) {
		// Break apart sort into field name and sort direction (note error
		// suppression to prevent notice when direction is left blank):
		$sort = trim($sort);
		$parts = explode(' ', $sort, 2);
		$sortField = $parts[0];
		$sortDirection = $parts[1] ?? '';

		// Default sort order (may be overridden by switch below):
		$defaultSortDirection = 'asc';
		global $solrScope;

		// Translate special sort values into appropriate Solr fields:
		switch ($sortField) {
			case 'year':
			case 'publishDate':
				$sortField = 'publishDateSort';
				$defaultSortDirection = 'desc';
				break;
			case 'author':
				$sortField = 'author_sort asc, title_sort';
				break;
			case 'title':
				$sortField = 'title_sort asc, author_sort';
				break;
			case 'callnumber_sort':
				$sortField = 'callnumber_sort_' . $solrScope;
				break;
			case 'copies_available':
				return "available_copies_$solrScope desc,title_sort asc";
			case 'copies_available_asc':
				return "available_copies_$solrScope asc,title_sort asc";
		}

		// Normalize sort direction to either "asc" or "desc":
		$sortDirection = strtolower(trim($sortDirection));
		if ($sortDirection != 'desc' && $sortDirection != 'asc') {
			$sortDirection = $defaultSortDirection;
		}

		return $sortField . ' ' . $sortDirection;
	}

	/**
	 * Load Boost factors for a query
	 *
	 * @param Library $searchLibrary
	 * @return array
	 */
	public function getBoostFactors($searchLibrary, $searchLocation, $searchTerm, $searchIndex)
	{
		global $activeLanguage;

		$boostFactors = [];
		if ($this->boostingDisabled) {
			return $boostFactors;
		}

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

			$limitBoosts = $searchLibrary->getGroupedWorkDisplaySettings()->limitBoosts;
			$maxTotalBoost = $searchLibrary->getGroupedWorkDisplaySettings()->maxTotalBoost;
			if ($searchIndex != 'Keyword' && $searchIndex != 'mlt') {
				$maxTotalBoost = $maxTotalBoost / 4;
			}
			$maxPopularityBoost = $searchLibrary->getGroupedWorkDisplaySettings()->maxPopularityBoost;
			$maxFormatBoost = $searchLibrary->getGroupedWorkDisplaySettings()->maxFormatBoost;
			$maxHoldingsBoost = $searchLibrary->getGroupedWorkDisplaySettings()->maxHoldingsBoost;
			if ($applyHoldingsBoost) {
				if ($limitBoosts) {
					//Add format boost, number of holdings, popularity divided by number of holdings
					$boostFactors[] = "min($maxTotalBoost,sum(min($maxFormatBoost,format_boost),min($maxHoldingsBoost,max(num_holdings,1)),min($maxPopularityBoost,div(max(popularity,1),max(num_holdings,1)))))";
				} else {
					$boostFactors[] = "product(format_boost,max(num_holdings,1),div(max(popularity,1),max(num_holdings,1)))";
				}
			} else {
				if ($limitBoosts) {
					$boostFactors[] = "min($maxTotalBoost,product(min($maxPopularityBoost,popularity),min($maxFormatBoost,format_boost)))";
				} else {
					$boostFactors[] = "div(popularity,format_boost)";
				}
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

		$boostFactors[] = "max(lib_boost,1)";

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
		$filter = [];

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

	/**
	 * applySearchSpecs -- internal method to build query string from search parameters
	 *
	 * @access    private
	 * @param array $structure the SearchSpecs-derived structure or substructure defining the search, derived from the yaml file
	 * @param array $values the various values in an array with keys 'onephrase', 'and', 'or' (and perhaps others)
	 * @param string $joiner
	 * @return    string A search string suitable for adding to a query URL
	 * @throws    AspenError
	 * @static
	 */
	protected function _applySearchSpecs($structure, $values, $joiner = "OR") : string {
		$clauses = [];
		$childClauses = [];
		foreach ($structure as $field => $clauseArray) {
			if (is_numeric($field)) {
				// shift off the join string and weight
				$sw = array_shift($clauseArray);
				$internalJoin = ' ' . $sw[0] . ' ';
				// Build it up recursively
				$searchString = '(' . $this->_applySearchSpecs($clauseArray, $values, $internalJoin) . ')';
				// ...and add a weight if we have one
				$weight = $sw[1];
				if (!is_null($weight) && $weight && $weight > 0) {
					$searchString .= '^' . $weight;
				}
				// push it onto the stack of clauses
				$clauses[] = $searchString;
			} else {

				// Otherwise, we've got a (list of) [munge, weight] pairs to deal with
				foreach ($clauseArray as $spec) {
					$fieldValue = $values[$spec[0]];

					if ($field == 'isbn') {
						if (!preg_match('/^((?:\sOR\s)?["(]?\d{9,13}X?[\s")]*)+$/', $fieldValue)) {
							continue;
						} else {
							require_once(ROOT_DIR . '/sys/ISBN.php');
							$isbn = new ISBN($fieldValue);
							if ($isbn->isValid()) {
								$isbn10 = $isbn->get10();
								$isbn13 = $isbn->get13();
								if ($isbn10 && $isbn13) {
									$fieldValue = '(' . $isbn->get10() . ' OR ' . $isbn->get13() . ')';
								}
							}
						}
					} elseif ($field == 'id') {
						if (!preg_match('/^"?(\d+|.[boi]\d+x?|[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})(-\w{3})?"?$/i', $fieldValue)) {
							continue;
						}
					} elseif ($field == 'alternate_ids') {
						if (!preg_match('/^"?(\d+|.?[boi]\d+x?|[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}|MWT\d+|CARL\d+)"?$/i', $fieldValue)) {
							continue;
						}
					} elseif ($field == 'issn') {
						if (!preg_match('/^"?[\d\hXx-]+"?$/', $fieldValue)) {
							continue;
						}
					} elseif ($field == 'upc') {
						if (!preg_match('/^"?\d+"?$/', $fieldValue)) {
							continue;
						}
					}

					//Ignore empty searches
					if (strlen($fieldValue) == 0) {
						continue;
					}

					// build a string like title:("one two")
					if ($fieldValue[0] != '(') {
						$searchString = $field . ':(' . $fieldValue . ')';
					} else {
						$searchString = $field . ':' . $fieldValue;
					}
					//Check to make sure we don't already have this clause.  We will get the same clause if we have a single word and are doing different munges
					$okToAdd = true;
					foreach ($clauses as $clause) {
						if (strpos($clause, $searchString) === 0) {
							$okToAdd = false;
							break;
						}
					}
					if (!$okToAdd) {
						continue;
					}

					// Add the weight if we have one. Yes, I know, it's redundant code.
					$weight = $spec[1];
					if (!is_null($weight) && $weight && $weight > 0) {
						$searchString .= '^' . $weight;
					}

					// ..and push it on the stack of clauses
					if (in_array($field, $this->childDocFields)) {
						$childClauses[] = $searchString;
					} else {
						$clauses[] = $searchString;
					}
				}
			}
		}

		// Join it all together
		$search = implode(' ' . $joiner . ' ', $clauses);
		if (!empty($childClauses)) {
			if (!empty($search)) {
				$search .= ' OR ';
			}
			$search .= '_query_:"{!parent which=recordtype:grouped_work score=max v=$child_query}"';
			global $solrScope;
			//TODO: add additional child filters
			$childFilterString = '+' . implode(' +', $this->childDocFilters);
			$this->childQuery = "+recordtype:record_scoping $childFilterString +(" . implode(' ' . $joiner . ' ', $childClauses) . ')';
		}
		return $search;
	}
}