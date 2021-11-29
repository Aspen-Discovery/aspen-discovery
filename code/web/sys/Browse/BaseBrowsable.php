<?php


abstract class BaseBrowsable extends DataObject
{
	public $source;                    //varchar(255)
	public $searchTerm;
	public $defaultFilter;
	public $sourceListId;
	public $sourceCourseReserveId;
	public $defaultSort;

	public function getSolrSort()
	{
		if ($this->defaultSort == 'relevance') {
			return 'relevance';
		} elseif ($this->defaultSort == 'popularity') {
			return 'popularity desc';
		} elseif ($this->defaultSort == 'newest_to_oldest') {
			return 'days_since_added asc';
		} elseif ($this->defaultSort == 'author') {
			return 'author,title';
		} elseif ($this->defaultSort == 'title') {
			return 'title,author';
		} elseif ($this->defaultSort == 'user_rating') {
			return 'rating desc,title';
		} elseif ($this->defaultSort == 'holds') {
			return 'total_holds desc';
		} elseif ($this->defaultSort == 'publication_year_desc') {
			return 'year desc,title asc';
		} elseif ($this->defaultSort == 'publication_year_asc') {
			return 'year asc,title asc';
		} else {
			return 'relevance';
		}
	}

	/**
	 * @param SearchObject_SolrSearcher $searchObj
	 *
	 * @return boolean
	 */
	public function updateFromSearch($searchObj)
	{
		$this->source = $searchObj->getEngineName();
		//Search terms
		$searchTerms = $searchObj->getSearchTerms();
		if (is_array($searchTerms)) {
			if (count($searchTerms) > 1) {
				return false;
			} else {
				if (!isset($searchTerms[0]['index'])) {
					$this->searchTerm = $searchObj->displayQuery();
				} else if ($searchTerms[0]['index'] == $searchObj->getDefaultIndex()) {
					$this->searchTerm = $searchTerms[0]['lookfor'];
				} else {
					$this->searchTerm = $searchTerms[0]['index'] . ':' . $searchTerms[0]['lookfor'];
				}
			}
		} else {
			$this->searchTerm = $searchTerms;
		}

		//Default Filter
		$filters = $searchObj->getFilterList();
		$formattedFilters = '';
		foreach ($filters as $filter) {
			foreach ($filter as $filterValue){
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
		} elseif ($solrSort == 'days_since_added asc' || $solrSort == 'year desc,title asc') {
			$this->defaultSort = 'newest_to_oldest';
		} elseif ($solrSort == 'days_since_added desc') {
			$this->defaultSort = 'oldest_to_newest';
		} elseif ($solrSort == 'author,title') {
			$this->defaultSort = 'author';
		} elseif ($solrSort == 'title,author') {
			$this->defaultSort = 'title';
		} elseif ($solrSort == 'rating desc,title') {
			$this->defaultSort = 'user_rating';
		} elseif ($solrSort == 'year desc,title asc') {
			$this->defaultSort = 'publication_year_desc';
		} elseif ($solrSort == 'year asc,title asc') {
			$this->defaultSort = 'publication_year_asc';
		} elseif ($solrSort == 'total_holds desc') {
			$this->defaultSort = 'holds';
		} else {
			$this->defaultSort = 'relevance';
		}
		return true;
	}

	public static function getBrowseSources()
	{
		$spotlightSources = [
			'GroupedWork' => 'Grouped Work Search'
		];
		global $enabledModules;
		if (array_key_exists('User Lists', $enabledModules)){
			$spotlightSources['List'] = 'Public List';
		}
		if (array_key_exists('Course Reserves', $enabledModules)){
			$spotlightSources['CourseReserve'] = 'Course Reserve';
			$spotlightSources['CourseReserves'] = 'Course Reserves search';
		}
		if (array_key_exists('EBSCO EDS', $enabledModules)){
			$spotlightSources['EbscoEds'] = 'EBSCO EDS Search';
		}
		if (array_key_exists('Events', $enabledModules)){
			$spotlightSources['Events'] = 'Events Search';
		}
		if (array_key_exists('Genealogy', $enabledModules)){
			$spotlightSources['Genealogy'] = 'Genealogy Search';
		}
		if (array_key_exists('Open Archives', $enabledModules)){
			$spotlightSources['OpenArchives'] = 'Open Archives Search';
		}
		if (array_key_exists('Web Indexer', $enabledModules)){
			$spotlightSources['Websites'] = 'Website Search';
		}

		return $spotlightSources;
	}
}