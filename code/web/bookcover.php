<?php
require_once 'bootstrap.php';
require_once 'bootstrap_aspen.php';

global $aspenUsage;
$aspenUsage->incCoverViews();
require_once ROOT_DIR . '/sys/Covers/BookCoverProcessor.php';

global $configArray;
global $timer;
global $logger;

//Create class to handle processing of covers
$processor = new BookCoverProcessor();
$processor->loadCover($configArray, $timer, $logger);
if ($processor->error) {
	header('Content-type: text/plain'); //Use for debugging notices and warnings
	$logger->log("Error processing cover " . $processor->error, Logger::LOG_ERROR);
	echo($processor->error);
}
//Do not need to update aspen usage here because we are doing it atomically as values update
//Do not need to update usage by IP here since it is done in bootstrap
