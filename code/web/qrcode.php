<?php
define ('ROOT_DIR', __DIR__);
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/sys/PEAR_Singleton.php';
PEAR_Singleton::init();
require_once ROOT_DIR . '/sys/Timer.php';
require_once ROOT_DIR . '/sys/Logger.php';
require_once ROOT_DIR . '/sys/BookCoverProcessor.php';
//Bootstrap the process
if (!function_exists('vufind_autoloader')){
	// Set up autoloader (needed for YAML)
	function vufind_autoloader($class) {
		$fullClassName = str_replace('_', '/', $class) . '.php';
		require $fullClassName;
	}
	spl_autoload_register('vufind_autoloader');
}
global $timer;
if (empty($timer)){
	$timer = new Timer(microtime(false));
}

// Retrieve values from configuration file
require_once ROOT_DIR . '/sys/ConfigArray.php';
$configArray = readConfig();
$timer->logTime("Read config");
if (isset($configArray['System']['timings'])){
	$timer->enableTimings($configArray['System']['timings']);
}

//Start a logger
$logger = new Logger();

//Update error handling
if ($configArray['System']['debug']) {
	ini_set('display_errors', true);
	error_reporting(E_ALL & ~E_DEPRECATED);
}

date_default_timezone_set($configArray['Site']['timezone']);
$timer->logTime("bootstrap");

//Create the QR Code if it doesn't exit
$type = $_REQUEST['type'];
$id = $_REQUEST['id'];
$filename = $configArray['Site']['qrcodePath'] . "/{$type}_{$id}.png";
if (!file_exists($filename)){
	include ROOT_DIR . '/sys/phpqrcode/qrlib.php';
	$codeContents = $configArray['Site']['url'] . "/{$type}/{$id}/Home";
	QRcode::png($codeContents, $filename, QR_ECLEVEL_L, 3);
}
readfile($filename);
$timer->writeTimings();
