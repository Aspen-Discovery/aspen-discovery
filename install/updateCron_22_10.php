<?php

if (count($_SERVER['argv']) > 1){
	$serverName = $_SERVER['argv'][1];
	$fhnd = fopen("/usr/local/aspen-discovery/sites/$serverName/conf/crontab_settings.txt", 'a+');
	fwrite($fhnd, "\n#########################\n");
	fwrite($fhnd, "# Send Custom LiDA Notifications #\n");
	fwrite($fhnd, "#########################\n");
	fwrite($fhnd, "*/15 * * * * aspen php /usr/local/aspen-discovery/code/web/cron/sendLiDANotifications.php $serverName\n");

} else {
	echo "Must provide servername as first file";
	exit();
}

?>
