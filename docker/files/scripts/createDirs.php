<?php

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('BACKEND');

$newOwner = "www-data"; // default user
//Check how many arguments has been passed to the script
if (count($argv) == 2) {
	$newOwner = $argv[1];
}

DockerLogger::info("Setting owner: {$newOwner}");

$siteName = getenv('SITE_NAME');
DockerLogger::info("Site name: {$siteName}");
$configDir = getenv('CONFIG_DIRECTORY');
DockerLogger::info("Config directory: {$configDir}");

//Check if passed user is valid
exec("id $newOwner", $output, $exitCode);
if ($exitCode !== 0) {
	DockerLogger::error("The requested user was not found: {$newOwner}");
}

$aspenDir = '/usr/local/aspen-discovery';

try {
	//Create temp smarty directory
	$tmpDir = "$aspenDir/tmp/smarty/compile";
	if (!file_exists($tmpDir)) {
		mkdir($tmpDir, 0755, true);
		DockerLogger::setPermissions($tmpDir, $newOwner, '755');
		DockerLogger::info("Created temp smarty directory: {$tmpDir}");
	}

	//Create data directory and sub-directories
	$dataDir = "/data/aspen-discovery/$siteName";
	if (!file_exists($dataDir)) {
		mkdir($dataDir, 0755, true);
		DockerLogger::setPermissions($dataDir, $newOwner, '755');
		DockerLogger::info("Created data directory: {$dataDir}");
	}

	$subdirectories = ['images', 'files', 'fonts'];
	foreach ($subdirectories as $subdirectory) {
		if (!file_exists("$dataDir/$subdirectory")) {
			mkdir("$dataDir/$subdirectory", 0755, true);
		}
	}

	if (!file_exists("/data/aspen-discovery/accelerated_reader")) {
		mkdir('/data/aspen-discovery/accelerated_reader', 0755, true);
	}
	DockerLogger::setPermissions("/data/aspen-discovery/accelerated_reader", $newOwner, '755');

	//Copy just necessary directories
	recursive_copy("$aspenDir/data_dir_setup/", $dataDir);
	$toDelete = [
		'solr7',
		'README.TXT',
		'update_solr_files.bat',
		'update_solr_files.sh',
		'update_solr_files_debian.sh'
	];

	foreach ($toDelete as $file) {
		if (is_dir("$dataDir/$file")) {
			recursive_delete("$dataDir/$file");
		} else {
			@unlink("$dataDir/$file");
		}
	}
	
	DockerLogger::info("Data directory structure created successfully");
	
} catch (Exception $e) {
	DockerLogger::error("Error creating directories: " . $e->getMessage());
}

try {
	//Assign owners and permissions

	//Aspen directory
	DockerLogger::setPermissions("$aspenDir/tmp", $newOwner, '755');
	DockerLogger::setPermissions($dataDir, $newOwner, '755');
	DockerLogger::setPermissions("$aspenDir/code/web", $newOwner, '755');
	DockerLogger::setPermissions("$aspenDir/sites", $newOwner, '755');
	DockerLogger::setPermissions("$aspenDir/sites/default", $newOwner, '755');

	//Data directory
	DockerLogger::setPermissions($dataDir, $newOwner, '755');
	DockerLogger::setPermissions("$dataDir/covers", $newOwner, '755');
	DockerLogger::setPermissions("$dataDir/uploads", $newOwner, '755');
	DockerLogger::setPermissions("$dataDir/sql_backup", 'root', '755');

	//Files directory
	DockerLogger::setPermissions("$aspenDir/code/web/files", $newOwner, '755');

	//Fonts directory
	DockerLogger::setPermissions("$aspenDir/code/web/fonts", $newOwner, '755');

	//Images directory
	DockerLogger::setPermissions("$aspenDir/code/web/images", $newOwner, '755');

	//Logs directory
	$logDir = "/var/log/aspen-discovery/$siteName";
	if (!file_exists($logDir)) {
		mkdir($logDir, 0755, true);
		DockerLogger::setPermissions($logDir, $newOwner, '755');
	}

	$logDir2 = "/var/log/aspen-discovery/$siteName/logs";
	if (!file_exists($logDir2)) {
		mkdir($logDir2, 0755, true);
		DockerLogger::setPermissions($logDir2, $newOwner, '755');
	}

	DockerLogger::setPermissions($logDir, $newOwner, '755');
	DockerLogger::setPermissions("$logDir/logs", $newOwner, '755');
	
	//Conf directory
	DockerLogger::setPermissions("$configDir/conf", $newOwner, '755');
	DockerLogger::setPermissions("$configDir/conf/php-fpm.conf", $newOwner, '755');
	DockerLogger::setPermissions("$configDir/httpd-$siteName.conf", 'root', '644');
	DockerLogger::setPermissions("$configDir/conf/crontab_settings.txt", 'root', '644');
	DockerLogger::setPermissions("$configDir/conf/crontab", 'root', '644');

	if (file_exists("$configDir/conf/log4j")) {
		DockerLogger::setPermissions("$configDir/conf/log4j*", $newOwner, '755');
	}
	if (file_exists("$configDir/conf/passkey")) {
		DockerLogger::setPermissions("$configDir/conf/passkey", $newOwner, '755');
	}

	//Copy the httpd conf file
	$apacheDir = "/etc/apache2";
	copy("$configDir/httpd-$siteName.conf", "$apacheDir/sites-enabled/httpd-$siteName.conf");
	
	//Copy the php-fpm config file
	$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
	$phpDir = "/etc/php/$phpVersion";
	copy("$configDir/conf/php-fpm.conf", $phpDir . "/fpm/pool.d/php-fpm.conf");

	DockerLogger::info("Permissions and ownership assigned successfully");

} catch (Exception $e) {
	DockerLogger::error("Error assigning permissions and ownership: " . $e->getMessage());
}

function recursive_delete(string $path): void {
	if (is_dir($path)) {
		$entries = scandir($path);
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			recursive_delete($path . '/' . $entry);
		}
		rmdir($path);
	} else {
		unlink($path);
	}
}

function recursive_copy($src, $dst): void {
	$dir = opendir($src);
	@mkdir($dst);
	while (($file = readdir($dir))) {
		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {
				recursive_copy($src . '/' . $file, $dst . '/' . $file);
			} else {
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}
?>
