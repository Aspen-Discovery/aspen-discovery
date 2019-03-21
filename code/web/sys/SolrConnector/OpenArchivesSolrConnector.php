<?php

require_once 'Solr.php';
class OpenArchivesSolrConnector extends Solr
{
    function __construct($host) {
        parent::__construct($host, 'open_archives');
    }
}