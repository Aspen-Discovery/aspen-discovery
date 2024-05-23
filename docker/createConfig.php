<?php

if (count($argv) < 2) {
    echo "To create new configuration files, a directory (where the files will be stored) will be necessary as argument.\n";
    die();
} elseif (count($argv) > 2) {
    echo "Too many arguments have been passed. The script just needs a directory to start\n";
    die();
}

$siteDir = $argv[1];

if (!file_exists($siteDir)) {
    echo "The directory '$siteDir' does not exist.\n";
    die();
}

if (!is_dir($siteDir)) {
    echo "'$siteDir' is not a directory.\n";
    die();
}


$variables = [
    //ASPEN
    'sitename' => getenv('SITE_NAME'),
    'servername' => preg_replace('~https?://~', '', getenv('URL')),
    'library' => getenv('LIBRARY'),
    'title' => getenv('TITLE'),
    'url' => getenv('URL'),
    'solrHost' => getenv('SOLR_HOST') ?? 'localhost',
    'solrPort' => getenv('SOLR_PORT') ?? 8080,
    'configurationDirectory' => $siteDir,
    'timezone' => getenv('TIMEZONE') ?? 'US/Central',
    'aspenAdminPassword' => getenv('ASPEN_ADMIN_PASSWORD'),
    'databaseHost' => getenv('DATABASE_HOST') ?? 'localhost',
    'databasePort' => getenv('DATABASE_PORT') ?? 3306,
    'databaseName' => getenv('DATABASE_NAME'),
    'databaseUser' => getenv('DATABASE_USER'),
    'databasePassword' => getenv('DATABASE_PASSWORD'),
    'databaseRootUser' => getenv('DATABASE_ROOT_USER'),
    'databaseRootPassword' => getenv('DATABASE_ROOT_PASSWORD'),

    'enableKoha' => strtolower(getenv('ENABLE_KOHA')),
];

if ($variables['enableKoha'] === "yes") {
    $variables['ilsDriver'] = 'Koha';
    $variables['ilsUrl'] = getenv('KOHA_OPAC_URL');
    $variables['ilsStaffUrl'] = getenv('KOHA_STAFF_URL');
    $variables['ilsDatabaseName'] = getenv('KOHA_DATABASE_NAME');
    $variables['ilsDatabaseHost'] = getenv('KOHA_DATABASE_HOST');
    $variables['ilsDatabasePort'] = getenv('KOHA_DATABASE_PORT');
    $variables['ilsDatabaseUser'] = getenv('KOHA_DATABASE_USER');
    $variables['ilsDatabaseTimeZone'] = getenv('KOHA_DATABASE_TIMEZONE') ?? 'US/Central';
    $variables['ilsClientId'] = getenv('KOHA_CLIENT_ID');
    $variables['ilsClientSecret'] = getenv('KOHA_CLIENT_SECRET');
} else {
    $variables['ilsDriver'] = ucfirst(getenv('ILS_DRIVER'));
}

$mandatory = ['sitename','servername', 'solrHost', 'solrPort', 'configurationDirectory', 'timezone', 'aspenAdminPassword', 'databaseHost', 'databasePort', 'databaseName', 'databaseUser', 'databasePassword', 'databaseRootUser', 'databaseRootPassword'];
$emptyVariables = 0;

foreach ($variables as $key => $value) {
    if (in_array($key, $mandatory) && empty($value)) {
        echo "WARNING: Mandatory variable '" . $key . "' is empty.\n";
        $emptyVariables++;
    }
}

if ($emptyVariables > 0) {
    die();
}

$templateDir = "/usr/local/aspen-discovery/sites/template.linux";
$defaultDir  = "/usr/local/aspen-discovery/sites/default";

if (!file_exists($templateDir)) {
    echo "ERROR: The template directory '" . $templateDir . "' does not exists.\n";
    die();
}
if (!file_exists($defaultDir)) {
    echo "ERROR: The default site directory '" . $defaultDir . "' does not exists.\n";
    die();
}

//Copy from template and replace variables
copy($templateDir . '/httpd-{sitename}.conf', "$siteDir/httpd-{$variables['sitename']}.conf");
recursive_copy($templateDir . '/conf', $siteDir . '/conf');
rename($siteDir . '/conf/config.pwd.ini.template', $siteDir . "/conf/config.pwd.ini");

replaceVariables($siteDir . "/httpd-{$variables['sitename']}.conf", $variables);
replaceVariables($siteDir . '/conf/config.ini', $variables);
replaceVariables($siteDir . '/conf/config.cron.ini', $variables);
replaceVariables($siteDir . '/conf/config.pwd.ini', $variables);
replaceVariables($siteDir . "/conf/crontab_settings.txt", $variables);

//Copy from default site directory
copy($defaultDir . "/conf/badBotsLocal.conf",$siteDir . "/conf/badBotsLocal.conf");
copy($defaultDir . "/conf/badBotsDefault.conf",$siteDir . "/conf/badBotsDefault.conf");

//Set timezone
exec('sudo timedatectl set-timezone "' . $variables['timezone'] . '"');
//Create temp directory
    !file_exists('/tmp') ?? mkdir('/tmp');

function replaceVariables($filename, $variables): void
{
    $contents = file($filename);
    $fHnd = fopen($filename, 'w');
    foreach ($contents as $line) {
        foreach ($variables as $name => $value) {
            $line = str_replace('{' . $name . '}', $value, $line);
        }
        fwrite($fHnd, $line);
    }
    fclose($fHnd);
}

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