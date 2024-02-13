<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Session/SharedSession.php';

$sharedSessions = new SharedSession();

$sessions = array_filter($sharedSessions->fetchAll('sessionId'));

$sharedSessions = null;

$numProcessed = 0;

foreach ($sessions as $session) {
	$sharedSession = new SharedSession();
	$sharedSession->setSessionId($session);
	if($sharedSession->find(true)) {
		$createdOn = $sharedSession->getCreated();
		$oneHourLater = strtotime('+1 hour', $createdOn);
		if($createdOn <= $oneHourLater) {
			$sharedSession->delete();
		}
	}
	$sharedSession->__destruct();
	$sharedSession = null;
	$numProcessed++;
}

global $aspen_db;
$aspen_db = null;
$configArray = null;

die();

/////// END OF PROCESS ///////