<?php
require_once ROOT_DIR . '/sys/SearchObject/AbstractGroupedWorkSearcher.php';
require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector3.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacet.php';

class SearchObject_GroupedWorkSearcher3 extends SearchObject_GroupedWorkSearcher2 {
	public static string $fields_to_return = 'auth_author2,author2-role,id,content_rating,title_display,title_full,title_short,subtitle_display,author,author_display,isbn,upc,issn,series,series_with_volume,recordtype,display_description,literary_form,literary_form_full,publisherStr,publishDate,publishDateSort,placeOfPublication,subject_facet,topic_facet,primary_isbn,primary_upc,accelerated_reader_point_value,accelerated_reader_reading_level,accelerated_reader_interest_level,lexile_code,lexile_score,fountas_pinnell,last_indexed,lc_subject,bisac_subject,format,format_category,language,ils_description,popularity,total_holds,date_added';

	public function getSolrConnector($indexUrl) : GroupedWorksSolrConnector3 {
		return new GroupedWorksSolrConnector3($indexUrl);
	}

	/**
	 * Actually process and submit the search
	 *
	 * @access  public
	 * @param bool $returnIndexErrors Should we die inside the index code if
	 *                                     we encounter an error (false) or return
	 *                                     it for access via the getIndexError()
	 *                                     method (true)?
	 * @param bool $recommendations Should we process recommendations along
	 *                                     with the search itself?
	 * @param bool $preventQueryModification Should we allow the search engine
	 *                                             to modify the query or is it already
	 *                                             a well formatted query
	 * @return  array|AspenError|null
	 */
	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) : AspenError|array|null {
		global $timer;
		global $solrScope;

		$childDocFilters = [];

		if ($this->searchSource == 'econtent') {
			$childDocFilters[] = 'econtent_source:[* TO *]';
		}

		// Our search has already been processed in init()
		$search = $this->searchTerms;

		// Build a recommendation module appropriate to the current search:
		if ($recommendations) {
			$this->initRecommendations();
		}
		$timer->logTime("initRecommendations");

		// Build Query
		if ($preventQueryModification) {
			$query = $search;
		} else {
			$query = $this->indexEngine->buildQuery($search);
		}
		$timer->logTime("build query in grouped work searcher");
		if (($query instanceof AspenError)) {
			return $query;
		}

		// Only use the query we just built if there isn't an override in place.
		if ($this->query == null) {
			$this->query = $query;
		}

		// Define Filter Query
		$filterQuery = $this->hiddenFilters;
		//restrict to our grouped works
		$filterQuery[] = 'recordtype:grouped_work';
		//Remove any empty filters if we get them
		//(typically happens when a subdomain has a function disabled that is enabled in the main scope)
		//Also fix dynamic field names
		$validFields = $this->loadValidFields();
		$dynamicFields = $this->loadDynamicFields();
		foreach ($this->filterList as $field => $filter) {
			if ($field === '') {
				unset($this->filterList[$field]);
			}
			if (str_starts_with($field, 'custom_facet_')) {
				$this->filterList[$field] = $filter;
			}else if (str_contains($field, '_')) {
				$lastUnderscore = strrpos($field, '_');
				$shortFieldName = substr($field, 0, $lastUnderscore + 1);
				$oldScope = substr($field, $lastUnderscore + 1);
				if ($oldScope != $solrScope) {
					//Correct any dynamic fields
					foreach ($dynamicFields as $dynamicField) {
						if ($shortFieldName == $dynamicField) {
							//This is a dynamic field with the wrong scope
							if ($field != ($dynamicField . $solrScope)) {
								unset($this->filterList[$field]);
								$this->filterList[$dynamicField . $solrScope] = $filter;
							}
							break;
						}
					}
				}
			}
		}

		$this->selectedAvailabilityToggleValue = null;
		$facetConfig = $this->getFacetConfig();
		$availabilityToggleId = null;
		$childDocFields = [
			'available_at',
			'availability_toggle',
			'callnumber_sort',
			'collection',
			'detailed_location',
			'econtent_source',
			'format',
			'format_category',
			'local_callnumber',
			'local_days_since_added',
			'local_time_since_added',
			'lib_boost',
			'owning_library',
			'owning_location',
			'shelf_location'
		];
		foreach ($this->filterList as $field => $filter) {
			$multiSelect = false;
			if (isset($facetConfig[$field])) {
				/** @var FacetSetting $facetInfo */
				$facetInfo = $facetConfig[$field];
				$facetName = $facetInfo->getFacetName(3);
				$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
				$multiSelect = $facetInfo->multiSelect;
			} else {
				//This is either a field we need to convert from the old schema to new schema or valid field from advanced search we aren't seeing here
				$tmpFieldName = substr($field, 0, strrpos($field, '_'));
				if (isset($facetConfig[$tmpFieldName])) {
					$facetInfo = $facetConfig[$tmpFieldName];
					$facetName = $facetInfo->getFacetName(3);
					$field = $tmpFieldName;
					$facetKey = empty($facetInfo->id) ? $facetName : $facetInfo->id;
					$multiSelect = $facetInfo->multiSelect;
				} else {
					if (in_array($field, $validFields)) {
						$facetName = $field;
					} else {
						//Unknown field
						continue;
					}
				}
			}
			$fieldValue = "";
			foreach ($filter as $value) {
				if ($facetName == 'availability_toggle') {
					$this->selectedAvailabilityToggleValue = $value;
					if (!empty($facetInfo)) {
						$availabilityToggleId = $facetInfo->id;
					}
				}

				// Special case -- allow trailing wildcards:
				$okToAdd = false;
				if (str_ends_with($value, '*')) {
					$okToAdd = true;
				} elseif (preg_match('/\\A\\[.*?\\sTO\\s.*?]\\z/', $value)) {
					$okToAdd = true;
				} elseif (preg_match('/^\\(.*?\\)$/', $value)) {
					$okToAdd = true;
				} else {
					if (!empty($value)) {
						//The value is already specified as field:value
						if (is_numeric($field)) {
							[$facetName, $fieldValue] = explode(':', $value);
							if (in_array($field, $childDocFields)) {
								$childDocFilters[] = "$facetName:$fieldValue";;
							}else {
								$filterQuery[] = "$facetName:$fieldValue";;
							}
						} else {
							$okToAdd = true;
							$value = "\"$value\"";
						}
					}
				}
				if ($okToAdd) {
					if ($multiSelect) {
						if (!empty($fieldValue)) {
							$fieldValue .= ' OR ';
						}
						$fieldValue .= $value;
					} else {
						if (in_array($facetName, $childDocFields)) {
							$childDocFilters[] = "$facetName:$value";
						}else {
							$filterQuery[] = "$facetName:$value";
						}
					}
				}
			}
			//Apply multi select filters now that we have all values grouped
			if ($multiSelect) {
				if (in_array($facetName, $childDocFields)) {
					$childDocFilters[] = str_contains($fieldValue, ' OR ') ? "$facetName:($fieldValue)" : "$facetName:$fieldValue";
				}else{
					$filterQuery[] = str_contains($fieldValue, ' OR ') ? "$facetName:($fieldValue)" : "$facetName:$fieldValue";
				}
			}
		}

		//Filter by scope
		$childDocFilters[] = "scope:$solrScope";

		//Check to see if we should apply a default filter for availability toggle
		if ($this->selectedAvailabilityToggleValue == null && !$this->disableDefaultAvailabilityToggle) {
			global $library;
			$location = Location::getSearchLocation();
			if ($location != null) {
				$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
			} else {
				$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			}
			$availabilityToggleValue = $groupedWorkDisplaySettings->defaultAvailabilityToggle;
			$this->selectedAvailabilityToggleValue = $availabilityToggleValue;

			if ($availabilityToggleId == null) {
				foreach ($facetConfig as $facetInfo) {
					$facetName = $facetInfo->getFacetName(3);
					if ($facetName == 'availability_toggle') {
						$availabilityToggleId = $facetInfo->id;
					}
				}
			}

			$filterQuery[] = '{!parent which="recordtype:grouped_work" tag=child_filter}(availability_toggle:' . $availabilityToggleValue . ' AND ' . implode(' AND ', $childDocFilters) . ')';
		}else{
			$filterQuery[] = '{!parent which="recordtype:grouped_work" tag=child_filter}(' . implode(' AND ', $childDocFilters) . ')';
		}

		$facetSet = [];

		if (empty($this->selectedAvailabilityToggleValue)) {
			$this->selectedAvailabilityToggleValue = 'global';
		}

		// If we are only searching one field use the DisMax handler
		//    for that field. If left at null let solr take care of it
		if (count($search) == 1 && isset($search[0]['index'])) {
			$this->index = $search[0]['index'];
		}

		// Build a list of facets we want from the index
		$facetConfig = $this->getFacetConfig();
		$jsonFacets = [];
		if ($recommendations && !empty($facetConfig)) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacet.php';
			$numLocations = GroupedWorkFacet::calculateDynamicFacetLimit('available_at');
			$domainInfo = [
				'blockChildren' => 'recordtype:grouped_work',
				'filter' => 'scope:' . $solrScope,
				'excludeTags' => 'child_filter'
			];

			$facetSet['limit'] = $this->facetLimit;
			foreach ($facetConfig as $facetField => $facetInfo) {
				if ($facetInfo instanceof FacetSetting) {
					$facetName = $facetInfo->getFacetName(3);

					$minCount = 1;
					$limit = $this->facetLimit;
					if ($facetName == 'series') {
						$minCount = 2;
					}elseif ($facetName == 'format') {
						$limit = GroupedWorkFacet::calculateDynamicFacetLimit('format');
					}elseif ($facetName == 'availability_at' || $facetName == 'owning_location') {
						$limit = $numLocations;
					}else{
						$limit = $facetInfo->numTotalEntriesToShowInMore;
					}

					$jsonInfoForField = [
						'type' => 'terms',
						'field' => $facetName,
						'limit' => (int)$limit,
						'mincount' => $minCount
					];
					if (in_array($facetName, $childDocFields)) {
						$jsonInfoForField['domain'] = $domainInfo;
						$jsonInfoForField['limit'] = -1;
						$jsonInfoForField['facet'] = [
							'parent_count' => 'uniqueBlock(_root_)'
						];
					}
					$jsonFacets[$facetName] = $jsonInfoForField;
				} else {
					$facetSet['field'][$facetField] = $facetInfo;
				}
			}
			if ($this->facetOffset != null) {
				$facetSet['offset'] = $this->facetOffset;
			}
			if ($this->facetLimit != null) {
				$facetSet['limit'] = $this->facetLimit;
			}
			if ($this->facetPrefix != null) {
				$facetSet['prefix'] = $this->facetPrefix;
			}
			if ($this->facetSort != null) {
				$facetSet['sort'] = $this->facetSort;
			}
			$this->facetOptions["json.facet"] = json_encode($jsonFacets);
		}
		$this->applyFacetSearch($jsonFacets);

		if (!empty($this->facetOptions)) {
			$facetSet['additionalOptions'] = $this->facetOptions;
		}
		$timer->logTime("create facets");

		// Build our spellcheckQuery query
		if ($this->spellcheckEnabled) {
			$spellcheckQuery = $this->buildSpellingQuery();

			// If the spellcheckQuery query is purely numeric, skip it if
			// the appropriate setting is turned on.
			if (is_numeric($spellcheckQuery)) {
				$spellcheckQuery = "";
			}
		} else {
			$spellcheckQuery = "";
		}
		$timer->logTime("create spell check");

		// Get time before the query
		$this->startQueryTimer();

		// The "relevance" sort option is a VuFind reserved word; we need to make
		// this null in order to achieve the desired effect with Solr:
		$finalSort = ($this->sort == 'relevance') ? null : $this->sort;
		if ($finalSort == 'days_since_added asc') {
			$finalSort = 'local_days_since_added asc';
		}

		// The first record to retrieve:
		//  (page - 1) * limit = start
		$recordStart = ($this->page - 1) * $this->limit;
		//Remove irrelevant fields based on scoping
		$fieldsToReturn = $this->getFieldsToReturn();

		$handler = $this->getSearchHandler();

		//Check the filters to make sure they are for the correct scope
		$validFields = $this->loadValidFields();
		$dynamicFields = $this->loadDynamicFields();
		global $solrScope;
		if (!empty($filterQuery)) {
			if (!is_array($filterQuery)) {
				$filterQuery = [$filterQuery];
			}

			$validFilters = [];
			foreach ($filterQuery as $id => $filterTerm) {
				//Allow the parent query through since we build it above
				if (str_starts_with($filterTerm, '{!parent')) {
					$validFilters[$id] = $filterTerm;
					continue;
				}
				[
					$fieldName,
					$term,
				] = explode(":", $filterTerm, 2);
				$tagging = '';
				if (preg_match("/({!tag=.*?})\(?(.*)/", $fieldName, $matches)) {
					$tagging = $matches[1];
					$fieldName = $matches[2];
				}
				if (!in_array($fieldName, $validFields)) {
					//Field doesn't exist, check to see if it is a dynamic field
					//Where we can replace the scope with the current scope
					foreach ($dynamicFields as $dynamicField) {
						if (preg_match("/^{$dynamicField}[^_]+$/", $fieldName)) {
							//This is a dynamic field with the wrong scope
							$validFilters[$id] = $tagging . $dynamicField . $solrScope . ":" . $term;
							break;
						}
					}
				} else {
					$validFilters[$id] = $filterTerm;
				}
			}
			$filterQuery = $validFilters;
		}

		$this->indexResult = $this->indexEngine->search($this->query,      // Query string
			$handler,      // DisMax Handler
			$filterQuery,      // Filter query
			$recordStart,      // Starting record
			$this->limit,      // Records per page
			$facetSet,         // Fields to facet on - pass blank since we define json.facet above
			$spellcheckQuery,       // Spellcheck query
			$this->dictionary, // Spellcheck dictionary
			$finalSort,        // Field to sort on
			$fieldsToReturn,   // Fields to return
			'POST',     // HTTP Request method
			$returnIndexErrors // Include errors in response?
		);
		$timer->logTime("run solr search");

		// Get time after the query
		$this->stopQueryTimer();

		// How many results were there?
		if (is_null($this->indexResult)) {
			//This happens with a timeout
			$this->resultsTotal = 0;
		} elseif (!isset($this->indexResult['response']['numFound'])) {
			//An error occurred
			$this->resultsTotal = 0;
		} else {
			$this->resultsTotal = $this->indexResult['response']['numFound'];
		}

		// If extra processing is needed for recommendations, do it now:
		if ($recommendations && is_array($this->recommend)) {
			foreach ($this->recommend as $currentSet) {
				/** @var RecommendationInterface $current */
				foreach ($currentSet as $current) {
					$current->process();
				}
			}
		}

		//Add debug information to the results if available
		if ($this->debug && isset($this->indexResult['debug'])) {
			$explainInfo = $this->indexResult['debug']['explain'];
			foreach ($this->indexResult['response']['docs'] as $key => $result) {
				if (array_key_exists($result['id'], $explainInfo)) {
					$result['explain'] = $explainInfo[$result['id']];
					$this->indexResult['response']['docs'][$key] = $result;
				}
			}
		}

		// Return the result set
		return $this->indexResult;
	}

	protected function getFieldsToReturn() : string {
		if (isset($_REQUEST['allFields'])) {
			$fieldsToReturn = '*,score';
		} elseif ($this->fieldsToReturn != null) {
			$fieldsToReturn = $this->fieldsToReturn;
		} else {
			$fieldsToReturn = SearchObject_GroupedWorkSearcher3::$fields_to_return;
			global $solrScope;
			//We should always have a scope
			$fieldsToReturn .= ',local_days_since_added';
			$fieldsToReturn .= ',local_time_since_added';
			$fieldsToReturn .= ',local_callnumber';
			$fieldsToReturn .= ',collection';
			$fieldsToReturn .= ',detailed_location';
			$fieldsToReturn .= ',owning_location';
			$fieldsToReturn .= ',available_at';
			$fieldsToReturn .= ',itype';
			$fieldsToReturn .= ',score';
			if ($solrScope !== false) {
				$fieldsToReturn .= ' [child childFilter="scope:' . $solrScope . '"]';
			}
		}
		return $fieldsToReturn;
	}

	/**
	 * No scoping is done in GroupedWorkSearcher3 so can just return the field
	 * @param string $scopedFieldName
	 * @return string
	 */
	public function getUnscopedFieldName(string $scopedFieldName): string {
		return $scopedFieldName;
	}

	/**
	 * @param string $field
	 * @return string
	 */
	protected function getScopedFieldName(string $field): string {
		global $solrScope;
		if ($solrScope) {
			if ($field === 'time_since_added') {
				$field = 'local_time_since_added';
			}
			$validFields = $this->getIndexEngine()->loadValidFields();
			if (!in_array($field, $validFields)) {
				//Check to see if we need to trim off the scope
				$tmpFieldName = substr($field, 0, strrpos($field, '_'));
				if (in_array($tmpFieldName, $validFields)) {
					$field = $tmpFieldName;
				}
			}
		}
		return $field;
	}

	/**
	 * Process facets from the results object
	 *
	 * @access  public
	 * @param array $filter Array of field => on-screen description
	 *                                  listing all the desired facet fields;
	 *                                  set to null to get all configured values.
	 * @return  array   Facets data arrays
	 */
	public function getFacetList($filter = null): array {
		global $solrScope;
		global $timer;
		// If there is no filter, we'll use all facets as the filter:
		if (is_null($filter)) {
			$filter = $this->getFacetConfig();
		}

		// Start building the facet list:
		$list = [];

		// If we have no facets to process, give up now
		//Facets can either be in facets (when using json facets) or in facet_counts (when searching with a facet)
		if (!isset($this->indexResult['facets']) && !isset($this->indexResult['facet_counts'])) {
			return $list;
		}

		// Loop through every field returned by the result set
		$validFields = array_keys($filter);

		global $locationSingleton;
		/** @var Library $currentLibrary */
		$currentLibrary = Library::getActiveLibrary();
		$activeLocationFacet = null;
		$activeLocation = $locationSingleton->getActiveLocation();
		if (!is_null($activeLocation)) {
			if (empty($activeLocation->facetLabel)) {
				$activeLocationFacet = $activeLocation->displayName;
			} else {
				$activeLocationFacet = $activeLocation->facetLabel;
			}
		} else {
			//Use the main branch for the library if we have one
			$locationsForLibrary = $currentLibrary->getLocations();
			foreach ($locationsForLibrary as $tmpLocation) {
				if ($tmpLocation->isMainBranch) {
					if (empty($tmpLocation->facetLabel)) {
						$activeLocationFacet = $tmpLocation->displayName;
					} else {
						$activeLocationFacet = $tmpLocation->facetLabel;
					}
					break;
				}
			}
		}
		$relatedLocationFacets = null;
		$relatedHomeLocationFacets = null;
		$additionalAvailableAtLocations = null;
		if (!is_null($currentLibrary)) {
			if ($currentLibrary->facetLabel == '') {
				$currentLibrary->facetLabel = $currentLibrary->displayName;
			}
			$relatedLocationFacets = $locationSingleton->getLocationsFacetsForLibrary($currentLibrary->libraryId);
			if (strlen($currentLibrary->additionalLocationsToShowAvailabilityFor) > 0) {
				$additionalAvailableAtLocations = [];
				$location = new Location();
				if ($currentLibrary->additionalLocationsToShowAvailabilityFor != ".*"){
					$locationsToLookfor = explode('|', $currentLibrary->additionalLocationsToShowAvailabilityFor);
					$location->whereAddIn('code', $locationsToLookfor, true);
				}
				$location->find();
				while ($location->fetch()) {
					if ($location->facetLabel == null){
						$location->facetLabel = $location->displayName;
					}
					$additionalAvailableAtLocations[] = $location->facetLabel;
				}
			}
		}
		$homeLibrary = Library::getPatronHomeLibrary();
		if (!is_null($homeLibrary)) {
			$relatedHomeLocationFacets = $locationSingleton->getLocationsFacetsForLibrary($homeLibrary->libraryId);
		}

		$allFacets = $this->indexResult['facets'] ?? $this->indexResult['facet_counts']['facet_fields'];
		/** @var FacetSetting $facetConfig */
		$facetConfig = $this->getFacetConfig();
		foreach ($allFacets as $field => $data) {
			// Skip filtered fields and empty arrays:
			if (!in_array($field, $validFields) ) {
				continue;
			}
			if (isset($data['buckets'])) {
				$data = $data['buckets'];
			}
			if (count($data) == 0) {
				continue;
			}
			// Initialize the settings for the current field
			$list[$field] = [];
			$list[$field]['field_name'] = $field;
			// Add the on-screen label
			if (is_object($filter[$field])) {
				$list[$field]['label'] = $filter[$field]->displayName;
			} else {
				$list[$field]['label'] = $filter[$field];
			}

			// Build our array of values for this field
			$list[$field]['list'] = [];
			$list[$field]['hasApplied'] = false;
			$list[$field]['multiSelect'] = $facetConfig[$field]->multiSelect;

			$foundInstitution = false;
			$doInstitutionProcessing = false;
			$doBranchProcessing = false;

			//Marmot specific processing to do custom resorting of facets.
			if (str_starts_with($field, 'owning_library') && isset($currentLibrary)) {
				$doInstitutionProcessing = true;
			}
			if (str_starts_with($field, 'owning_location') || str_starts_with($field, 'available_at')) {
				$doBranchProcessing = true;
			}
			// Should we translate values for the current facet?
			$translate = $facetConfig[$field]->translate;
			$numValidRelatedLocations = 0;
			$numValidLibraries = 0;

			foreach ($data as $facet) {
				// Initialize the array of data about the current facet:
				$currentSettings = [];
				$facetValue = $facet['val'] ?? $facet[0];
				$facetCount = $facet['parent_count'] ?? $facet['count'] ?? $facet[1];

				$currentSettings['value'] = $facetValue;
				$currentSettings['display'] = $translate ? translate([
					'text' => $facetValue,
					'isPublicFacing' => true,
					'isMetadata' => true,
					'escape' => true,
				]) : htmlentities($facetValue);
				$currentSettings['count'] = $facetCount;
				$currentSettings['isApplied'] = false;
				$currentSettings['url'] = $this->renderLinkWithFilter($field, $facetValue);

				// Is this field a current filter?
				if (in_array($field, array_keys($this->filterList))) {
					// and is this value a selected filter?
					if (in_array($facetValue, $this->filterList[$field])) {
						$currentSettings['isApplied'] = true;
						$list[$field]['hasApplied'] = true;
						$currentSettings['removalUrl'] = $this->renderLinkWithoutFilter("$field:$facetValue");
					}
				}

				//Set up the key to allow sorting alphabetically if needed.
				$valueKey = $facetValue;
				$okToAdd = true;
				//Don't include empty settings since they don't work properly with Solr
				if (strlen(trim($facetValue)) == 0) {
					$okToAdd = false;
				}
				if ($doInstitutionProcessing) {
					if ($facetValue == $currentLibrary->facetLabel) {
						$valueKey = '1' . $valueKey;
						$numValidLibraries++;
						$foundInstitution = true;
					} elseif ($facetValue == $currentLibrary->facetLabel . ' On Order') {
						$valueKey = '1' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					} elseif ($facetValue == 'Digital Collection') {
						$valueKey = '2' . $valueKey;
						$foundInstitution = true;
						$numValidLibraries++;
					}
				} elseif ($doBranchProcessing) {
					if (strlen($facetValue) > 0) {
						if ($activeLocationFacet != null && $facetValue == $activeLocationFacet) {
							$valueKey = '1' . $valueKey;
						} elseif (isset($currentLibrary) && ($facetValue == $currentLibrary->facetLabel . ' On Order')) {
							$valueKey = '1' . $valueKey;
						} elseif (!is_null($relatedLocationFacets) && in_array($facetValue, $relatedLocationFacets)) {
							$valueKey = '2' . $valueKey;
						} elseif (!is_null($relatedHomeLocationFacets) && in_array($facetValue, $relatedHomeLocationFacets)) {
							$valueKey = '2' . $valueKey;
						} elseif (!is_null($additionalAvailableAtLocations) && in_array($facetValue, $additionalAvailableAtLocations)) {
							$valueKey = '3' . $valueKey;
						} else {
							$valueKey = '4' . $valueKey;
						}
						$numValidRelatedLocations++;
					}
				}


				// Store the collected values:
				if ($okToAdd) {
					$list[$field]['list'][$valueKey] = $currentSettings;
				}
			}

			if (!$foundInstitution && $doInstitutionProcessing) {
				$list[$field]['list']['1' . $currentLibrary->facetLabel] = [
					'value' => $translate ? translate([
						'text' => $currentLibrary->facetLabel,
						'isPublicFacing' => true,
						'escape' => true
					]) : htmlentities($currentLibrary->facetLabel),
					'display' => $translate ? translate([
						'text' => $currentLibrary->facetLabel,
						'isPublicFacing' => true,
						'escape' => true
					]) : htmlentities($currentLibrary->facetLabel),
					'count' => 0,
					'isApplied' => false,
					'url' => null,
				];
			}

			if ($doBranchProcessing || $doInstitutionProcessing) {
				ksort($list[$field]['list']);
			}

			//How many facets should be shown by default
			//Only show one system unless we are in the global scope
			if ($field == 'owning_library_' . $solrScope && isset($currentLibrary)) {
				$list[$field]['valuesToShow'] = $numValidLibraries;
			} elseif ($field == 'owning_location_' . $solrScope && isset($relatedLocationFacets) && $numValidRelatedLocations > 0) {
				$list[$field]['valuesToShow'] = $numValidRelatedLocations;
			} elseif ($field == 'available_at_' . $solrScope) {
				$list[$field]['valuesToShow'] = count($list[$field]['list']);
			} else {
				$list[$field]['valuesToShow'] = 5;
			}

			//Sort the facet alphabetically?
			//Sort the system and location alphabetically unless we are in the global scope
			global $solrScope;
			if (in_array($field, [
					'owning_library_' . $solrScope,
					'owning_location_' . $solrScope,
					'available_at_' . $solrScope,
				]) && isset($currentLibrary)) {
				$list[$field]['showAlphabetically'] = true;
			} else {
				$list[$field]['showAlphabetically'] = false;
			}
			if ($list[$field]['showAlphabetically']) {
				ksort($list[$field]['list']);
			}
			$timer->logTime("Processed facet $field Translated? $translate Num values: " . count($data));
		}
		return $list;
	}

	public function getResultRecordSet(): array {
		return SearchObject_AbstractGroupedWorkSearcher::getResultRecordSet();
	}

	/**
	 * Retrieves a document specified by the item barcode.
	 *
	 * @param string $barcode A barcode of an item in the document to retrieve from Solr
	 * @return  ?array               The requested resource
	 * @throws  AspenError
	 */
	function getRecordByBarcode(string $barcode) : ?array  {
		return $this->indexEngine->getRecordByBarcode($barcode);
	}

	/**
	 * Retrieves a document specified by an isbn.
	 *
	 * @param string[] $isbn An array of isbns to check
	 * @return  ?array              The requested resource
	 * @throws  AspenError
	 */
	function getRecordByIsbn(array $isbn) : ?array{
		return $this->indexEngine->getRecordByIsbn($isbn, $this->getFieldsToReturn());
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string $id The document to retrieve from Solr.
	 * @return  array|null The requested resource, or null if not found.
	 * @throws  AspenError
	 */
	function getRecord($id): ?array {
		return $this->indexEngine->getRecord($id, $this->getFieldsToReturn());
	}

	function getScopedRecordIds($ids) : array {
		return $this->indexEngine->getRecords($ids, 'id', true);
	}

	public function getRecordDriverForResult($record): GroupedWorkDriver {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		return new GroupedWorkDriver($record);
	}
}
