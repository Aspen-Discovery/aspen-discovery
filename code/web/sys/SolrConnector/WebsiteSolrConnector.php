<?php

require_once 'Solr.php';

class WebsiteSolrConnector extends Solr
{
	function __construct($host)
	{
		parent::__construct($host, 'website_pages');
		//$this->_highlight = true;
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

	protected function getHighlightOptions($fields, &$options){
		$options['hl'] = 'true';
		$options['hl.fl'] = 'description';
		$options['hl.simple.pre'] = '<strong>';
		$options['hl.simple.post'] = '</strong>';
	}
}