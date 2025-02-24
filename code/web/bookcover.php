<?php
require_once 'bootstrap.php';
require_once 'bootstrap_aspen.php';

global $aspenUsage;
$aspenUsage->coverViews++;
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
try {
	if (!empty($aspenUsage->__get('id'))) {
		$aspenUsage->update();
	} else {
		$aspenUsage->insert();
	}
	//Do not need to update usage by IP here since it is done in bootstrap
} catch (Exception $e) {
	//The table is not created yet, ignore
}
