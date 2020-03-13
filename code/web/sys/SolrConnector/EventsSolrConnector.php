<?php

require_once 'Solr.php';

class EventsSolrConnector extends Solr
{
	function __construct($host)
	{
		parent::__construct($host, 'events');
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile()
	{
		return ROOT_DIR . '/../../sites/default/conf/eventsSearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile()
	{
		return 'eventsSearches';
	}
}