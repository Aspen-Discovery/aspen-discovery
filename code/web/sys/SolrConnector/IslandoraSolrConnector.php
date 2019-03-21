<?php

require_once 'Solr.php';
class IslandoraSolrConnector extends Solr
{
    function __construct($host, $core = 'islandora') {
        parent::__construct($host, $core);
    }
}