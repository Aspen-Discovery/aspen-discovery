<?php
require_once __DIR__ . '/../code/web/bootstrap.php';
require_once __DIR__ . '/../code/web/bootstrap_aspen.php';

global $configArray;

if (count($_SERVER['argv']) > 2) {
	$serverName = $_SERVER['argv'][1];
	$operatingSystem = $_SERVER['argv'][2];
}else{
	echo("Please provide 2 parameters, the first should be the name of the server to update and the second should be the operating system centos/debian\n");
	die();
}

require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
$sideLoad = new SideLoad();
$sideLoads = $sideLoad->fetchAll('name', 'marcPath');
foreach ($sideLoads as $name => $marcPath) {
	echo("Updating permissions for $name\n");
	if ($operatingSystem == 'centos') {
		exec("chown aspen:aspen_apache $marcPath/..");
		exec("chmod 775 $marcPath/..");

		exec("chown -R apache:aspen_apache $marcPath");
		exec("chmod 775 $marcPath");
	}else{
		exec("chown aspen:aspen_apache $marcPath/..");
		exec("chmod 775 $marcPath/..");

		exec("chown -R www-data:aspen_apache $marcPath");
		exec("chmod 775 $marcPath");
	}
}