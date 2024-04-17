<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../sys/SolrUtils.php';

SolrUtils::startSolr();

global $aspen_db;
$aspen_db = null;
$configArray = null;

die();

/////// END OF PROCESS ///////

function execInBackground($cmd) {
	/** @noinspection PhpStrFunctionsInspection */
	if (substr(php_uname(), 0, 7) == "Windows") {
		pclose(popen("start /B " . $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}