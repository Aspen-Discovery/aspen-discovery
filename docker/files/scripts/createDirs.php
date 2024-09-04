<?php

//Capture any error as ErrorException
set_error_handler("customErrorHandler");

$newOwner = "www-data"; // default user
//Check how many arguments has been passed to the script
if (count($argv) == 2) {
    $newOwner = $argv[1];
}

echo "%    --> Owner: $newOwner\n";

$siteName = getenv('SITE_NAME');
echo "%    --> Site name: $siteName\n";
$configDir = getenv('CONFIG_DIRECTORY');
echo "%    --> Config directory: $configDir\n";

//Check if passed user is valid
exec("id $newOwner", $output, $exitCode);
if ($exitCode !== 0) {
    echo "%   ERROR: The requested user was not found.\n ";
    die();
}

$aspenDir = '/usr/local/aspen-discovery';

try {
    //Create temp smarty directory
    $tmpDir = "$aspenDir/tmp";
    if (!file_exists($tmpDir)) {
        exec("mkdir -p $tmpDir");
        exec("chown -R www-data $tmpDir");
        exec("chmod -R 755 $tmpDir");
    }

    //Create data directory and sub-directories
    $dataDir = "/data/aspen-discovery/$siteName";
    if (!file_exists($dataDir)) {
        exec("mkdir -p $dataDir");
        exec("chmod -R 755 $dataDir");
        exec("chown -R $newOwner $dataDir");
    }

    $subdirectories = ['images', 'files', 'fonts'];
    foreach ($subdirectories as $subdirectory) {
        if (!file_exists("$dataDir/$subdirectory")) {
            exec("mkdir -p $dataDir/$subdirectory");
        }
    }

    if (!file_exists("/data/aspen-discovery/accelerated_reader")) {
        exec("mkdir -p /data/aspen-discovery/accelerated_reader");
        exec("chmod -R 755 /data/aspen-discovery/accelerated_reader");
        exec("chown -R $newOwner /data/aspen-discovery/accelerated_reader");
    }

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
            exec("rm -Rf $dataDir/$file");
        } else {
            exec("rm $dataDir/$file");
        }
    }
} catch (ErrorException $e) {
    echo "%   ERROR CREATING DIRECTORIES: $dataDir\n";
    echo "%   ERROR MESSAGE : " . $e->getMessage() . "\n";
    echo "%   IN : " . $e->getFile() . ":" . $e->getLine() . "\n";
    die(1);
}

try {
    //Assign owners and permissions

    //Aspen directory
    exec("chown -R www-data $aspenDir/tmp");
    exec("chown -R www-data $aspenDir/code/web");
    exec("chown -R www-data $aspenDir/sites");
    exec("chown -R www-data $aspenDir/sites/default");

    //Data directory
    exec("chmod -R 755 $dataDir");
    exec("chown -R $newOwner $dataDir");

    exec("chmod -R 755 $dataDir/covers");
    exec("chown -R $newOwner $dataDir/covers");

    exec("chmod -R 755 $dataDir/uploads");
    exec("chown -R $newOwner $dataDir/uploads");

    exec("chown -R root:root $dataDir/sql_backup");

    //Files directory
    exec("chmod -R 755 $aspenDir/code/web/files");
    exec("chown -R $newOwner $aspenDir/code/web/files");

    //Fonts directory
    exec("chmod -R 755 $aspenDir/code/web/fonts");
    exec("chown -R $newOwner $aspenDir/code/web/fonts");

    //Images directory
    exec("chmod -R 755 $aspenDir/code/web/images");
    exec("chown -R $newOwner $aspenDir/code/web/images");


    //Logs directory
    $logDir = "/var/log/aspen-discovery/$siteName";
    if (!file_exists($logDir)) {
        exec("mkdir -p $logDir");
        exec("chmod -R 755 $logDir");
    }

    $logDir2 = "/var/log/aspen-discovery/$siteName/logs";
    if (!file_exists($logDir2)) {
        exec("mkdir -p $logDir2");
        exec("chmod -R 755 $logDir2");
    }

    exec("chown $newOwner $logDir/*");
    exec("chown -R $newOwner $logDir/logs");

    //Conf directory
    exec("chmod -R 755 $configDir/conf");
    exec("chown $newOwner $configDir/conf");
    exec("chown $newOwner $configDir/conf/config*");
    exec("chown root:root $configDir/httpd-$siteName.conf");
    exec("chown root:root $configDir/conf/crontab_settings.txt");
    exec("chmod 0644 $configDir/conf/crontab_settings.txt");

    if (file_exists("$configDir/conf/log4j")) {
        exec("chown $newOwner $configDir/conf/log4j*");
    }
    if (file_exists("$configDir/conf/passkey")) {
        exec("chown $newOwner $configDir/conf/passkey");
    }

    //Copy the httpd conf file
    $apacheDir = "/etc/apache2";
    copy("$configDir/httpd-$siteName.conf", "$apacheDir/sites-enabled/httpd-$siteName.conf");
} catch (ErrorException $e) {
    echo "%   ERROR ASSIGNING PERMISSIONS AND OWNERSHIPS\n";
    echo "%   ERROR MESSAGE : " . $e->getMessage() . "\n";
    echo "%   IN : " . $e->getFile() . ":" . $e->getLine() . "\n";
    die(1);
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

/**
 * @throws ErrorException
 */
function customErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting.
        return;
    }
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        // Do not throw an Exception for deprecation warnings as new or unexpected
        // deprecations would break the application.
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}