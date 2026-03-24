<?php

/**
 * ****************************************************
 *
 * A minified search object used exclusively for trimming
 *  a search object down to it's barest minimum size
 *  before storage in a cookie or database.
 *
 * It's still contains enough data granularity to
 *  programmatically recreate search urls.
 *
 * This class isn't intended for general use, but simply
 *  a way of storing/retrieving data from a search object:
 *
 * eg. Store
 * $searchHistory[] = serialize($this->minify());
 *
 * eg. Retrieve
 * $searchObject  = SearchObjectFactory::initSearchObject();
 * $searchObject->deminify(unserialize($search));
 *
 */
class minSO {
	public $t = [];
	public $f = [];
	public $hf = [];
	public $fc = [];
	public $id, $i, $s, $r, $ty, $sr, $q, $ss;

	/**
	 * Constructor. Building minified object from the
	 *    searchObject passed in. Needs to be kept
	 *    up-to-date with the deminify() function on
	 *    searchObject.
	 * @param SearchObject_BaseSearcher $searchObject
	 * @access  public
	 */
	public function __construct($searchObject) {
		// Most values will transfer without changes
		$this->id = $searchObject->getSearchId();
		$this->i = $searchObject->getStartTime();
		$this->s = $searchObject->getQuerySpeed();
		$this->ss = $searchObject->getSearchSource();
		$this->r = $searchObject->getResultTotal();
		$this->ty = $searchObject->getSearchType();
		$this->sr = $searchObject->getSort();
		$this->q = $searchObject->getQuery();

		// Search terms, we'll shorten keys
		$tempTerms = $searchObject->getSearchTerms();
		foreach ($tempTerms as $term) {
			$newTerm = [];
			foreach ($term as $k => $v) {
				switch ($k) {
					case 'join'    :
						$newTerm['j'] = $v;
						break;
					case 'index'   :
						$newTerm['i'] = $v;
						break;
					case 'lookfor' :
						$newTerm['l'] = $v;
						break;
					case 'group' :
						$newTerm['g'] = [];
						foreach ($v as $line) {
							$search = [];
							foreach ($line as $k2 => $v2) {
								switch ($k2) {
									case 'bool'    :
										$search['b'] = $v2;
										break;
									case 'field'   :
										$search['f'] = $v2;
										break;
									case 'lookfor' :
										$search['l'] = $v2;
										break;
								}
							}
							$newTerm['g'][] = $search;
						}
						break;
				}
			}
			$this->t[] = $newTerm;
		}

		// It would be nice to shorten filter fields too, but
		//      it would be a nightmare to maintain.
		$this->f = $searchObject->getFilters();


		// Add Hidden Filters if Present
		if (method_exists($searchObject, 'getHiddenFilters')) {
			$this->hf = $searchObject->getHiddenFilters();
		}

		// Add Facet Configurations if Present
		if (method_exists($searchObject, 'getFacetConfig')) {
			$this->fc = $searchObject->getFacetConfig();
		}
	}
}