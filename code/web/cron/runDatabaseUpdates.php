<?php
$serverNameProvidedAsArgument = empty($_SERVER['aspen_server']) && !empty($argv[1]);
if ($serverNameProvidedAsArgument) {
	$_SERVER['aspen_server'] = $argv[1];
}
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

set_time_limit(0);

require_once ROOT_DIR . '/services/API/SystemAPI.php';

$systemAPI = new SystemAPI();
$pendingUpdates = $systemAPI->getPendingDatabaseUpdates();
$noPendingUpdates = empty($pendingUpdates);
if ($noPendingUpdates) {
	echo "No pending database updates.\n";
	exit(0);
}

$numFailed = 0;
foreach ($pendingUpdates as $key => $pendingUpdate) {
	$result = $systemAPI->runDatabaseUpdate($pendingUpdates, $key);
	$updateSucceeded = $result['success'];
	if ($updateSucceeded) {
		echo "Applied: $key\n";
	} else {
		$numFailed++;
		echo "FAILED: $key - " . strip_tags($result['message'] ?? '') . "\n";
	}
}

echo count($pendingUpdates) . " updates run, $numFailed failed.\n";
exit($numFailed === 0 ? 0 : 1);
