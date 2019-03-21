<?php

require_once 'Solr.php';
class GenealogySolrConnector extends Solr
{
    function __construct($host) {
        parent::__construct($host, 'genealogy');
    }
}