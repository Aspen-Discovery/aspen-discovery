<?php
require_once __DIR__ . '/../bootstrap.php';
require_once ROOT_DIR . '/sys/CronLogEntry.php';
$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Update Suggesters';
$cronLogEntry->insert();

global $configArray;
$solrBaseUrl = $configArray['Index']['url'];

$opts = [
	'http' => [
		'timeout' => 14400, // 4 hours
	],
];
$context = stream_context_create($opts);

set_time_limit(0);
require_once ROOT_DIR . '/sys/SystemVariables.php';
$systemVariables = SystemVariables::getSystemVariables();
if ($systemVariables->searchVersion == 2) {
	if (!file_get_contents($solrBaseUrl . '/grouped_works_v2/suggest?suggest.build=true', false, $context)) {
		$cronLogEntry->notes .= "<br/>Could not update suggesters for grouped_works_v2";
		$cronLogEntry->numErrors++;
	}else{
		$cronLogEntry->notes .= "<br/>Updated suggesters for grouped_works_v2";
	}
}
$cronLogEntry->update();
if (!file_get_contents($solrBaseUrl . '/open_archives/suggest?suggest.build=true', false, $context)) {
	$cronLogEntry->notes .= "<br/>Could not update suggesters for open_archives";
	$cronLogEntry->numErrors++;
}else{
	$cronLogEntry->notes .= "<br/>Updated suggesters for open_archives";
}
$cronLogEntry->update();
if (!file_get_contents($solrBaseUrl . '/genealogy/suggest?suggest.build=true', false, $context)) {
	$cronLogEntry->notes .= "<br/>Could not update suggesters for genealogy";
	$cronLogEntry->numErrors++;
}else{
	$cronLogEntry->notes .= "<br/>Updated suggesters for genealogy";
}
$cronLogEntry->update();
if (!file_get_contents($solrBaseUrl . '/lists/suggest?suggest.build=true', false, $context)) {
	$cronLogEntry->notes .= "<br/>Could not update suggesters for lists";
	$cronLogEntry->numErrors++;
}else{
	$cronLogEntry->notes .= "<br/>Updated suggesters for lists";
}
$cronLogEntry->endTime = time();
$cronLogEntry->update();

die();