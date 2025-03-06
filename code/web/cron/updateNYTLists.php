<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/sys/Enrichment/NYTListsUpdateService.php';

// Create the updater and run it.
$updater = new NYTListsUpdateService();
$updater->update();

global $aspen_db;
$aspen_db = null;

die();