<?php
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

//Create a template for $serverName
$templateName = "$serverName.ini";
if (!file_exists($configArray['Site']['local'] . "/../../install/templates/")){
	mkdir($configArray['Site']['local'] . "/../../install/templates/");
}
$fhnd = fopen($configArray['Site']['local'] . "/../../install/templates/$templateName", 'w');
$dbHost = empty($configArray['Database']['database_aspen_host']) ? 'localhost' : $configArray['Database']['database_aspen_host'];
$dbPort = empty($configArray['Database']['database_aspen_dbport']) ? '3306' : $configArray['Database']['database_aspen_dbport'];

fwrite($fhnd, "[Site]\n");
fwrite($fhnd, "; sitename to be setup (e.g., demo.localhost, library.production)\n");
fwrite($fhnd, "sitename = $serverName\n");
fwrite($fhnd, "; windows, centos, debian\n");
fwrite($fhnd, "operatingSystem = {$configArray['System']['operatingSystem']}\n");
fwrite($fhnd, "; library or consortium name, e.g., Aspen Public Library\n");
fwrite($fhnd, "library = {$configArray['Site']['libraryName']}\n");
fwrite($fhnd, "; title of the site, e.g., Aspen Demo (may be same as library name)\n");
fwrite($fhnd, "title = {$configArray['Site']['title']}\n");
fwrite($fhnd, "; url where the site will be accessed, e.g., https://demo.aspendiscovery.org or http://demo.localhost\n");
fwrite($fhnd, "url = {$configArray['Site']['url']}\n");
fwrite($fhnd, "; Will Aspen run on Windows (y/n)\n");
fwrite($fhnd, "siteOnWindows = " .  ($configArray['System']['operatingSystem'] == 'windows' ? 'y' : 'n') . "\n");
fwrite($fhnd, ";Which host should Solr run on (typically localhost)\n");
fwrite($fhnd, "solrHost = {$configArray['Index']['solrHost']}\n");
fwrite($fhnd, "; Which port should Solr run on (typically 8080)\n");
if (array_key_exists('solrPort', $configArray['Index'])) {
	fwrite($fhnd, "solrPort = {$configArray['Index']['solrPort']}\n");
}else{
	fwrite($fhnd, "solrPort = 8080\n");
}
fwrite($fhnd, "; Which ILS does the library use?\n");
fwrite($fhnd, "ils = {$configArray['Catalog']['driver']}\n");
fwrite($fhnd, "; timezone of the library (e.g. America/Los_Angeles, check http://www.php.net/manual/en/timezones.php)\n");
fwrite($fhnd, "timezone = {$configArray['Site']['timezone']}\n");
fwrite($fhnd, "\n");
fwrite($fhnd, "[Aspen]\n");
fwrite($fhnd, "; Database host for Aspen\n");
fwrite($fhnd, "DBHost = $dbHost\n");
fwrite($fhnd, "; Database port for Aspen\n");
fwrite($fhnd, "DBPort = $dbPort\n");
fwrite($fhnd, "; Database name for Aspen\n");
fwrite($fhnd, "DBName = {$configArray['Database']['database_aspen_dbname']}\n");
fwrite($fhnd, "; Database username for Aspen\n");
fwrite($fhnd, "DBUser = {$configArray['Database']['database_user']}\n");
fwrite($fhnd, "; Database password\n");
fwrite($fhnd, "DBPwd = {$configArray['Database']['database_password']}\n");
fwrite($fhnd, ";password for the 'aspen_admin' user\n");
//leave this blank assuming the database will be copied later
fwrite($fhnd, "aspenAdminPwd =\n");
fwrite($fhnd, "\n");
fwrite($fhnd, "[ILS]\n");
fwrite($fhnd, "; Which ILS Driver does the library use?\n");
fwrite($fhnd, "ilsDriver = {$configArray['Catalog']['driver']}\n");
fwrite($fhnd, "; url of the OPAC for the ILS\n");
fwrite($fhnd, "ilsUrl = {$configArray['Catalog']['url']}\n");
fwrite($fhnd, ";url of the staff client for the ILS\n");
fwrite($fhnd, "staffUrl = {$configArray['Catalog']['staffClientUrl']}\n");
fwrite($fhnd, "\n");
//These can all be left blank with the assumption the database will be copied later
fwrite($fhnd, "[Koha]\n");
fwrite($fhnd, "; Database host for Koha\n");
fwrite($fhnd, "DBHost =\n");
fwrite($fhnd, "; Database name for Koha\n");
fwrite($fhnd, "DBName =\n");
fwrite($fhnd, "; Database username for Koha\n");
fwrite($fhnd, "DBUser =\n");
fwrite($fhnd, "; Database password\n");
fwrite($fhnd, "DBPwd =\n");
fwrite($fhnd, "; Database port for Koha\n");
fwrite($fhnd, "DBPort =\n");
fwrite($fhnd, "; Database timezone for Koha (e.g., US/Central)\n");
fwrite($fhnd, "DBTimezone = US/Central\n");
fwrite($fhnd, "; Client ID for Koha API\n");
fwrite($fhnd, "ClientId =\n");
fwrite($fhnd, "; Client Secret for Koha API\n");
fwrite($fhnd, "ClientSecret = \n");

fclose($fhnd);
$configArray = null;
$aspen_db = null;

die();