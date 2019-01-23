<?php
/**
 * Testing for horizon api integration
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/15/14
 * Time: 9:22 AM
 */
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'bootstrap.php';
require_once ROOT_DIR . '/Drivers/HorizonAPI.php';