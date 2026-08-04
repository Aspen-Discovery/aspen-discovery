<?php

require_once __DIR__ . '/../bootstrap.php';
require_once ROOT_DIR . '/services/Pay360/Client.php';
require_once ROOT_DIR . '/services/Pay360/Poller.php';

$pay360SettingsId = intval($argv[2] ?? 0);
if (!$pay360SettingsId) {
	die("Pay360 settings ID required\n");
}

$paymentId = intval($argv[3] ?? 0);
if (!$paymentId) {
	die("Payment ID required\n");
}

$apiClient = new Pay360_Client($pay360SettingsId, $paymentId);
$poller = new Pay360_Poller($apiClient);

$poller->poll();
