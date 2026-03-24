<?php /** @noinspection PhpMissingFieldTypeInspection */


abstract class BaseBrowsable extends DataObject {
	public $source;                    //varchar(255)
	public $searchTerm;
	public $defaultFilter;
	public $sourceListId;
	public $sourceCourseReserveId;
	public $defaultSort;

	public function getSolrSort(): string {
		// Handle Lists search source with specific sort field names.
		if ($this->source == 'Lists') {
			if ($this->defaultSort == 'relevance') {
				return 'relevance';
			} elseif ($this->defaultSort == 'newest_to_oldest') {
				return 'days_since_added asc, title_sort asc';
			} elseif ($this->defaultSort == 'oldest_to_newest') {
				return 'days_since_added desc, title_sort desc';
			} elseif ($this->defaultSort == 'newest_updated_to_oldest') {
				return 'days_since_updated asc, title_sort asc';
			} elseif ($this->defaultSort == 'oldest_updated_to_newest') {
				return 'days_since_updated desc, title_sort desc';
			} elseif ($this->defaultSort == 'title') {
				return 'title_sort';
			} else {
				return 'relevance';
			}
		}

		if ($this->defaultSort == 'relevance') {
			return 'relevance';
		} elseif ($this->defaultSort == 'popularity') {
			return 'popularity desc';
		} elseif ($this->defaultSort == 'newest_to_oldest') {
			return 'days_since_added asc';
		} elseif ($this->defaultSort == 'author') {
			return 'author';
		} elseif ($this->defaultSort == 'title') {
			return 'title';
		} elseif ($this->defaultSort == 'user_rating') {
			// Although it would be best for this to be "rating asc" (i.e., low to high) for when
			// users select a rating facet, this logic is also used for sorting titles in browse
			// categories, where it is most intuitive for the ratings to be from high to low.
			return 'rating desc';
		} elseif ($this->defaultSort == 'holds') {
			return 'total_holds desc';
		} elseif ($this->defaultSort == 'publication_year_desc') {
			return 'year desc,title asc';
		} elseif ($this->defaultSort == 'publication_year_asc') {
			return 'year asc,title asc';
		} elseif ($this->defaultSort == 'event_date') {
			return 'start_date_sort asc';
		} elseif ($this->defaultSort == 'oldest_to_newest') {
			return 'days_since_added asc';
		} elseif ($this->defaultSort == 'newest_updated_to_oldest') {
			return 'days_since_updated asc'; // Default fallback for non-Lists sources
		} elseif ($this->defaultSort == 'oldest_updated_to_newest') {
			return 'days_since_updated desc'; // Default fallback for non-Lists sources
		} else {
			return 'relevance';
		}
	}

	/**
	 * @param SearchObject_SolrSearcher $searchObj
	 *
	 * @return boolean
	 */
	public function updateFromSearch(SearchObject_BaseSearcher $searchObj): bool {
		$this->source = $searchObj->getEngineName();
		//Search terms
		$searchTerms = $searchObj->getSearchTerms();
		if (is_array($searchTerms)) {
			$this->searchTerm = $searchObj->displayQuery();
		} else {
			$this->searchTerm = $searchTerms;
		}

		//Default Filter
		$filters = $searchObj->getFilterList();
		$formattedFilters = '';
		foreach ($filters as $filter) {
			foreach ($filter as $filterValue) {
				if (strlen($formattedFilters) > 0) {
					$formattedFilters .= "\r\n";
				}
				$formattedFilters .= $filterValue['field'] . ':' . $filterValue['value'];
			}
		}
		$this->defaultFilter = $formattedFilters;

		//Default sort
		$solrSort = $searchObj->getSort();
		if ($solrSort == 'relevance') {
			$this->defaultSort = 'relevance';
		} elseif ($solrSort == 'popularity desc') {
			$this->defaultSort = 'popularity';
		} elseif ($solrSort == 'days_since_added asc') {
			$this->defaultSort = 'newest_to_oldest';
		} elseif ($solrSort == 'days_since_added desc') {
			$this->defaultSort = 'oldest_to_newest';
		} elseif ($solrSort == 'days_since_added asc, title_sort asc') {
			$this->defaultSort = 'newest_to_oldest'; // Lists Date Added Desc
		} elseif ($solrSort == 'days_since_added desc, title_sort desc') {
			$this->defaultSort = 'oldest_to_newest'; // Lists Date Added Asc
		} elseif ($solrSort == 'days_since_updated asc, title_sort asc') {
			$this->defaultSort = 'newest_updated_to_oldest'; // Lists Date Updated Desc
		} elseif ($solrSort == 'days_since_updated desc, title_sort desc') {
			$this->defaultSort = 'oldest_updated_to_newest'; // Lists Date Updated Asc
		} elseif ($solrSort == 'title_sort') {
			$this->defaultSort = 'title'; // Lists-specific format
		} elseif ($solrSort == 'author') {
			$this->defaultSort = 'author';
		} elseif ($solrSort == 'title') {
			$this->defaultSort = 'title';
		} elseif ($solrSort == 'rating desc' || $solrSort == 'rating asc') {
			// Although it is counterintuitive that choosing "User Rating (Ascending)" defaults
			// to a descending sort, the user expects a rating sort regardless, and most users
			// probably want highest-rated items first anyway, mainly for browse categories.
			$this->defaultSort = 'user_rating';
		} elseif ($solrSort == 'year desc,title asc') {
			$this->defaultSort = 'publication_year_desc';
		} elseif ($solrSort == 'year asc,title asc') {
			$this->defaultSort = 'publication_year_asc';
		} elseif ($solrSort == 'total_holds desc') {
			$this->defaultSort = 'holds';
		} elseif ($solrSort == 'start_date_sort asc') {
			$this->defaultSort = 'event_date';
		} else {
			$this->defaultSort = 'relevance';
		}
		return true;
	}

	public static function getBrowseSources() : array {
		$spotlightSources = [
			'GroupedWork' => 'Grouped Work Search',
		];
		global $enabledModules;
		if (array_key_exists('User Lists', $enabledModules)) {
			$spotlightSources['List'] = 'Public List';
			$spotlightSources['Lists'] = 'Public Lists Search';
		}
		if (array_key_exists('Course Reserves', $enabledModules)) {
			$spotlightSources['CourseReserve'] = 'Course Reserve';
			$spotlightSources['CourseReserves'] = 'Course Reserves search';
		}
		if (array_key_exists('EBSCO EDS', $enabledModules)) {
			$spotlightSources['EbscoEds'] = 'EBSCO EDS Search';
		}
		if (array_key_exists('Events', $enabledModules)) {
			$spotlightSources['Events'] = 'Events Search';
		}
		if (array_key_exists('Genealogy', $enabledModules)) {
			$spotlightSources['Genealogy'] = 'Genealogy Search';
		}
		if (array_key_exists('Open Archives', $enabledModules)) {
			$spotlightSources['OpenArchives'] = 'Open Archives Search';
		}
		if (array_key_exists('CloudSource', $enabledModules)) {
			$spotlightSources['CloudSource'] = 'CloudSource OA Search';
		}
		if (array_key_exists('Web Indexer', $enabledModules)) {
			$spotlightSources['Websites'] = 'Website Search';
		}

		return $spotlightSources;
	}
}