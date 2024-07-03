<?php

$enableKoha = strtolower(getenv('ENABLE_KOHA'));

if ($enableKoha !== 'yes') {
    echo "Koha was not enabled\n ";
    die();
}

//Load Koha's Database
$variables = [
    'sitename' => getenv('SITE_NAME'),
    'ilsUrl' => getenv('KOHA_OPAC_URL'),
    'databaseUser' => getenv('DATABASE_USER'),
    'databasePassword' => getenv('DATABASE_PASSWORD'),
    'databaseName' => getenv('DATABASE_NAME'),
    'ilsDatabaseHost' => getenv('KOHA_DATABASE_HOST'),
    'ilsDatabasePort' => getenv('KOHA_DATABASE_PORT'),
    'ilsDatabaseUser' => getenv('KOHA_DATABASE_USER'),
    'ilsDatabasePassword' => getenv('KOHA_DATABASE_PASSWORD'),
    'ilsDatabaseName' => getenv('KOHA_DATABASE_NAME'),
    'ilsDatabaseTimezone' => getenv('KOHA_DATABASE_TIMEZONE') ?? 'US/Central',
    'ilsClientId' => getenv('KOHA_CLIENT_ID'),
    'ilsClientSecret' => getenv('KOHA_CLIENT_SECRET'),
];

$aspenDir = '/usr/local/aspen-discovery';

// Attempt to get the system's temp directory
    $tmp_dir = rtrim(sys_get_temp_dir(), "/");
    echo("Loading Koha information to database\r\n");
    copy("$aspenDir/install/koha_connection.sql", "$tmp_dir/koha_connection_${variables['sitename']}.sql");
    replaceVariables("$tmp_dir/koha_connection_${variables['sitename']}.sql", $variables);
    exec("mysql -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" {$variables['databaseName']} < $tmp_dir/koha_connection_${variables['siteName']}.sql");


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