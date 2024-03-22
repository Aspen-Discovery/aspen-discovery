<?php
//TODO: This should be customizable by install or create a predefined install (phpunit.localhost?)
$_SERVER['aspen_server'] = 'model.localhost';

//TODO: load a clean database at the start of unit testing?

require_once '../../code/web/bootstrap.php';
require_once '../../code/web/bootstrap_aspen.php';

//Setup interface
global $interface;
$interface = new UInterface();

echo "Aspen Discovery PHPUnit tests starting\n";