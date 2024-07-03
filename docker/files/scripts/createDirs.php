<?php

//Check how many arguments has been passed to the script
if (count($argv) == 1) {
    echo "If an user is not passed as argument to the script, by default the owner of the directories will be www-data.\n";
    $newOwner = "www-data";
    echo "Owner : $newOwner\n";
} elseif (count($argv) == 2) {
    $newOwner = $argv[1];
    echo "Owner : $newOwner\n";
} else {
    echo "Too many arguments have been passed. The script needs exactly one argument : \n";
    echo "A user, who will be assigned owner of the created directories.\n";
    echo "If no arguments are passed, by default the owner of the directories will be www-data.\n";
    die();
}

$siteName = getenv('SITE_NAME');
echo "Site name = $siteName\n ";
$configDir = getenv('CONFIG_DIRECTORY');
echo "Config directory = $configDir\n ";

//Check if passed user is valid
exec("id $newOwner",$output,$exitCode);
if ($exitCode !== 0) {
    echo "The user doesn't exist\n ";
    die();
}

echo "Setting up data and log directories\n ";

$aspenDir = '/usr/local/aspen-discovery';

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

$subdirectories = ['images','files','fonts'];
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
    if(is_dir("$dataDir/$file")) {
        exec("rm -Rf $dataDir/$file");
    } else {
        exec("rm $dataDir/$file");
    }
}

//Assign owners and permissions

//Aspen directory
exec("chown -R $newOwner $aspenDir");
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
copy("$configDir/httpd-$siteName.conf","$apacheDir/sites-enabled/httpd-$siteName.conf");


echo "------------->Aspen is ready to use<-------------!\n\n";

function recursive_copy($src,$dst): void {
    $dir = opendir($src);
    @mkdir($dst);
    while(( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recursive_copy($src .'/'. $file, $dst .'/'. $file);
            }
            else {
                copy($src .'/'. $file,$dst .'/'. $file);
            }
        }
    }
    closedir($dir);
}