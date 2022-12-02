<?php

require_once 'Solr.php';

class EventsSolrConnector extends Solr {
	function __construct($host) {
		parent::__construct($host, 'events');
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile() {
		return ROOT_DIR . '/../../sites/default/conf/eventsSearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile() {
		return 'eventsSearches';
	}

	/**
	 * Get filters based on scoping for the search
	 * @param Library $searchLibrary
	 * @param Location $searchLocation
	 * @return array
	 */
	public function getScopingFilters($searchLibrary, $searchLocation) {
		global $library;
		global $solrScope;
		$filter = [];
		if (!$library) {
			//MDN: This does happen when called within migration tools
			if (isset($searchLibrary)) {
				$filter[] = "library_scopes:" . strtolower($searchLibrary->subdomain);
			}
		} else {
			$filter[] = "library_scopes:" . strtolower($library->subdomain);
		}
		return $filter;
	}

	public function getBoostFactors($searchLibrary) {
		$boostFactors = [];

		$boostFactors[] = "boost";

		return $boostFactors;
	}
}