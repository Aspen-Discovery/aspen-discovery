<?php
require_once __DIR__ . '/../bootstrap.php';

global $configArray;
$solrBaseUrl = $configArray['Index']['url'];

$opts = array('http' =>
	array(
		'timeout' => 600
	)
);
$context  = stream_context_create($opts);

set_time_limit(0);
require_once ROOT_DIR . '/sys/SystemVariables.php';
$systemVariables = SystemVariables::getSystemVariables();
if ($systemVariables->searchVersion == 1) {
	if (!file_get_contents($solrBaseUrl . '/grouped_works/suggest?suggest.build=true', false, $context)){
		echo("Could not update suggesters for grouped_works");
	}
}else{
	if (!file_get_contents($solrBaseUrl . '/grouped_works_v2/suggest?suggest.build=true', false, $context)){
		echo("Could not update suggesters for grouped_works_v2");
	}
}
if (!file_get_contents($solrBaseUrl . '/open_archives/suggest?suggest.build=true', false, $context)){
	echo("Could not update suggesters for open_archives");
}
if (!file_get_contents($solrBaseUrl . '/genealogy/suggest?suggest.build=true', false, $context)){
	echo("Could not update suggesters for genealogy");
}
if (!file_get_contents($solrBaseUrl . '/lists/suggest?suggest.build=true', false, $context)){
	echo("Could not update suggesters for lists");
}
