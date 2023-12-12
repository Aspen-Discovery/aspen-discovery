<?php

if (count($_SERVER['argv']) > 1) {
	$serverName = $_SERVER['argv'][1];
	//Check to see if the update already exists properly.
	$configFile = "/usr/local/aspen-discovery/sites/$serverName/conf/config.pwd.ini";
	$configArray = parse_ini_file($configFile, true);
	if ($configArray !== false) {
		$databaseUser = $configArray['Database']['database_user'];
		$databasePassword = $configArray['Database']['database_password'];

		$dbUpgradeResult = shell_exec("/usr/sbin/runuser -umysql -- /usr/bin/mariadb-upgrade -u$databaseUser -p$databasePassword");
		echo $dbUpgradeResult;
	} else {
		echo("- Could not load configuration file\n");
	}

} else {
	echo 'Must provide servername as first argument';
}
exit();
