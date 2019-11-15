<?php

require_once 'Solr.php';

class WebsiteSolrConnector extends Solr
{
	function __construct($host)
	{
		parent::__construct($host, 'website_pages');
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile()
	{
		return ROOT_DIR . '/../../sites/default/conf/websiteSearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile()
	{
		return 'websiteSearches';
	}
}