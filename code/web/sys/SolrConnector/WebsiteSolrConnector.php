<?php

require_once 'Solr.php';

class WebsiteSolrConnector extends Solr {
	function __construct($host) {
		parent::__construct($host, 'website_pages');
		//$this->_highlight = true;
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile() {
		return ROOT_DIR . '/../../sites/default/conf/websiteSearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile() {
		return 'websiteSearches';
	}

	protected function getHighlightOptions($fields, &$options) {
		$options['hl'] = 'true';
		$options['hl.fl'] = 'description';
		$options['hl.simple.pre'] = '<strong>';
		$options['hl.simple.post'] = '</strong>';
	}

	/**
	 * Get filters based on scoping for the search
	 * @param Library $searchLibrary
	 * @param Location $searchLocation
	 * @return array
	 */
	public function getScopingFilters($searchLibrary, $searchLocation) {
		global $solrScope;
		//For websites we only scope based on library, not location

		$filter = [];
		$filter[] = "scope_has_related_records:" . strtolower($searchLibrary->subdomain);
		return $filter;
	}
}