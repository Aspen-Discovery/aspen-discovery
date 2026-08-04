<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
/**
 * Purge user app request logs that are more than 24 hours old. Runs every 12 hours.
 */
require_once ROOT_DIR . '/sys/CronLogEntry.php';
$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Purge User App Request Logs';
$cronLogEntry->insert();

require_once ROOT_DIR . '/sys/SystemLogging/UserAppRequestLogEntry.php';
$logEntry = new UserAppRequestLogEntry();
$logEntry->whereAdd('time < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
$logEntry->find();
$numRemoved = 0;

$numRemoved = $logEntry->delete(true);

$cronLogEntry->notes .= "User App Request Logs Purge complete: $numRemoved entries removed.";
$cronLogEntry->endTime = time();
$cronLogEntry->update();