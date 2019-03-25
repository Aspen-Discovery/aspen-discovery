<?php
require_once 'bootstrap.php';

require_once ROOT_DIR . '/sys/Covers/BookCoverProcessor.php';

//Create class to handle processing of covers
$processor = new BookCoverProcessor();
$processor->loadCover($configArray, $timer, $logger);
if ($processor->error){
	header('Content-type: text/plain'); //Use for debugging notices and warnings
	$logger->log("Error processing cover " . $processor->error, PEAR_LOG_ERR);
	echo($processor->error);
}
