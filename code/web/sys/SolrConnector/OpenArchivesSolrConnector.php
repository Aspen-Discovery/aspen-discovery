<?php

require_once 'Solr.php';

class OpenArchivesSolrConnector extends Solr
{
	function __construct($host)
	{
		parent::__construct($host, 'open_archives');
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile()
	{
		return ROOT_DIR . '/../../sites/default/conf/openArchivesSearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile()
	{
		return 'openArchivesSearches';
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
		$filter = [];
		if (!$solrScope) {
			//MDN: This does happen when called within migration tools
			if (isset($searchLocation)) {
				$filter[] = "scope_has_related_records:" . strtolower($searchLocation->code);
			} elseif (isset($searchLibrary)) {
				$filter[] = "scope_has_related_records:" . strtolower($searchLibrary->subdomain);
			}
		} else {
			$filter[] = "scope_has_related_records:" . strtolower($solrScope);
		}
		return $filter;
	}
}