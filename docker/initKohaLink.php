<?php

$enableKoha = strtolower(getenv('ENABLE_KOHA'));

if ($enableKoha !== 'yes') {
    echo "Koha was not enabled\n ";
    die();
}

if (count($argv) < 3) {
    echo "To establish a connection with Koha's database, two .ini files will be necessary as arguments :\n";
    echo "First argument : An .ini file which stores a Catalog conf\n";
    echo "Second argument : An .ini file which stores a Database configuration for the ils\n";
    die();
} elseif (count($argv) > 3) {
    echo "Too many arguments have been passed. The script needs two .ini files to start\n";
    echo "First argument : An .ini file which stores a Catalog conf\n";
    echo "Second argument : An .ini file which stores a Database conf for the ils\n";
    die();
}

$catalogConfigFile = parse_ini_file($argv[1], true);
$databaseConfigFile = parse_ini_file($argv[2], true);

if (!$catalogConfigFile || !$databaseConfigFile) {
    echo "One or more required parameters are missing\n";
    die();
}

if (!$catalogConfigFile['Catalog'] || !$databaseConfigFile['Database']) {
    echo "There is no “Catalog” or “Database” configuration field in the files provided as arguments.\n ";
    die();
}

//Load Koha's Database
$variables = [
    'sitename' => $catalogConfigFile['Site']['sitename'],
    'ilsUrl' => $catalogConfigFile['Catalog']['url'],

    'aspenDBUser' => $databaseConfigFile['Database']['database_user'],
    'aspenDBPwd' => $databaseConfigFile['Database']['database_password'],
    'aspenDBName' => $databaseConfigFile['Database']['database_name'],

    'ilsDBHost' => $databaseConfigFile['Ils']['ils_database_host'],
    'ilsDBPort' => $databaseConfigFile['Ils']['ils_database_port'],
    'ilsDBUser' => $databaseConfigFile['Ils']['ils_database_user'],
    'ilsDBPwd' => $databaseConfigFile['Ils']['ils_database_password'],
    'ilsDBName' => $databaseConfigFile['Ils']['ils_database_name'],
    'ilsDBTimezone' => $databaseConfigFile['Ils']['ils_database_timezone'],
    'ilsClientId' => $databaseConfigFile['Ils']['ils_client_id'],
    'ilsClientSecret' => $databaseConfigFile['Ils']['ils_client_secret'],

];

$aspenDir = '/usr/local/aspen-discovery';

// Attempt to get the system's temp directory
    $tmp_dir = rtrim(sys_get_temp_dir(), "/");
    echo("Loading Koha information to database\r\n");
    copy("$aspenDir/install/koha_connection.sql", "$tmp_dir/koha_connection_${variables['siteName']}.sql");
    replaceVariables("$tmp_dir/koha_connection_${variables['siteName']}.sql", $variables);
    exec("mysql -u{$variables['aspenDBUser']} -p\"{$variables['aspenDBPwd']}\" {$variables['aspenDBName']} < $tmp_dir/koha_connection_${variables['siteName']}.sql");


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