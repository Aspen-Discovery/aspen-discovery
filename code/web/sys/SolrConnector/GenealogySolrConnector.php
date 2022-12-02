<?php

require_once 'Solr.php';

class GenealogySolrConnector extends Solr {
	function __construct($host) {
		parent::__construct($host, 'genealogy');
	}

	/**
	 * @return string
	 */
	function getSearchSpecsFile() {
		return ROOT_DIR . '/../../sites/default/conf/genealogySearchSpecs.yaml';
	}

	/** return string */
	public function getSearchesFile() {
		return 'genealogySearches';
	}
}