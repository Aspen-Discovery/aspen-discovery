<?php

require_once 'Solr.php';

class ListsSolrConnector extends Solr
{
	function __construct($host)
	{
		parent::__construct($host, 'lists');
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile()
	{
		return ROOT_DIR . '/../../sites/default/conf/listsSearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile()
	{
		return 'listsSearches';
	}
}