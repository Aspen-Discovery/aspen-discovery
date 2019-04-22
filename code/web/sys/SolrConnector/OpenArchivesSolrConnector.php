<?php

require_once 'Solr.php';
class OpenArchivesSolrConnector extends Solr
{
    function __construct($host) {
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
}