<?php
require_once __DIR__ . '/../bootstrap.php';

global $configArray;
$solrBaseUrl = $configArray['Index']['url'];

//Rebuilding can take quite awhile, give each 5 minutes to complete, other than grouped works which we will give 10
set_time_limit(0);
file_get_contents($solrBaseUrl . '/grouped_works/suggest?suggest.build=true');
file_get_contents($solrBaseUrl . '/open_archives/suggest?suggest.build=true');
file_get_contents($solrBaseUrl . '/genealogy/suggest?suggest.build=true');
file_get_contents($solrBaseUrl . '/lists/suggest?suggest.build=true');
