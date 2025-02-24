<?php

/*
 * Prompt for information about the new installation
 */

echo("This will create the proper directories and configuration files for a new site\r\n");

$operatingSystem = php_uname('s');
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
	$runningOnWindows = true;
}else{
	$runningOnWindows = false;
}

$linuxOS = null;
$linuxArray = ['centos', 'debian'];

$foundConfig = false;
$variables = [];
$siteOnWindows = false;
$siteOnMac = false;
$sitename = null;
$cleanSitename = null;
$macUser = 'root';

if (count($_SERVER['argv']) > 1){
	//Read the file
	$siteConfigFile = __DIR__ . '/' . $_SERVER['argv'][1];
	if (file_exists($siteConfigFile)){
		$configArray = parse_ini_file($siteConfigFile, true);
		$foundConfig = true;

		$sitename = $configArray['Site']['sitename'];
		if (empty($sitename)){
			echo "sitename not provided";
			exit();
		}

		$cleanSitename = preg_replace('/\W/', '_', $sitename);
		$variables = [
			'sitename' => $sitename,
			'cleanSitename' => $cleanSitename,
			'supportingCompany' => $configArray['Site']['supportingCompany'],
			'library' => $configArray['Site']['sitename'],
			'title' => $configArray['Site']['title'],
			'url' => $configArray['Site']['url'],
			'solrHost' => $configArray['Site']['solrHost'] ?? 'localhost',
			'solrPort' => $configArray['Site']['solrPort'],
			'timezone' => $configArray['Site']['timezone'],
			'databaseHost' => $configArray['Aspen']['DBHost'] ?? 'localhost',
			'databasePort' => $configArray['Aspen']['DBPort'] ?? 3306,
			'databaseName' => $configArray['Aspen']['DBName'],
			'databaseUser' => $configArray['Aspen']['DBUser'],
			'databasePassword' => $configArray['Aspen']['DBPwd'],
			'aspenAdminPassword' => $configArray['Aspen']['aspenAdminPwd'],
			'ils' => $configArray['Site']['ils'],
			'ilsUrl' => $configArray['ILS']['ilsUrl'],
			'ilsStaffUrl' => $configArray['ILS']['ilsStaffUrl'],
			'apacheGroup' => $configArray['Site']['apacheGroup'] ?? "_www",
		];
		if ($variables['ils'] == 'Koha') {
			$variables['ilsDriver'] = 'Koha';
			$variables['ilsDatabaseHost'] = $configArray['Koha']['DBHost'];
			$variables['ilsDatabaseName'] = $configArray['Koha']['DBName'];
			$variables['ilsDatabaseUser'] = $configArray['Koha']['DBUser'];
			$variables['ilsDatabasePassword'] = $configArray['Koha']['DBPwd'];
			$variables['ilsDatabasePort'] = $configArray['Koha']['DBPort'];
			$variables['ilsDatabaseTimezone'] = $configArray['Koha']['DBTimezone'];
			$variables['ilsClientId'] = $configArray['Koha']['ClientId'];
			$variables['ilsClientSecret'] = $configArray['Koha']['ClientSecret'];
		}elseif ($variables['ils'] == 'Symphony') {
			$variables['ilsDriver'] = 'SirsiDynixROA';
			$variables['ilsClientId'] = $configArray['Symphony']['ClientId'];
			$variables['ilsStaffUser'] = $configArray['Symphony']['StaffUser'];
			$variables['ilsStaffPassword'] = $configArray['Symphony']['StaffPassword'];
		}elseif ($variables['ils'] == 'Polaris') {
			$variables['ilsDriver'] = 'Polaris';
			$variables['ilsApiVersion'] = $configArray['Polaris']['APIVersion'];
			$variables['ilsClientId'] = $configArray['Polaris']['ClientId'];
			$variables['ilsClientSecret'] = $configArray['Polaris']['ClientSecret'];
			$variables['ilsDomain'] = $configArray['Polaris']['Domain'];
			$variables['ilsStaffUser'] = $configArray['Polaris']['StaffUser'];
			$variables['ilsStaffPassword'] = $configArray['Polaris']['StaffPassword'];
			$variables['ilsWorkstationID'] = $configArray['Polaris']['WorkstationID'];
		}elseif ($variables['ils'] == 'Sierra') {
			$variables['ilsDriver'] = 'Sierra';
			$variables['ilsClientId'] = $configArray['Sierra']['ClientId'];
			$variables['ilsClientSecret'] = $configArray['Sierra']['ClientSecret'];
			$variables['ilsDatabaseHost'] = $configArray['Sierra']['DBHost'];
			$variables['ilsDatabaseName'] = $configArray['Sierra']['DBName'];
			$variables['ilsDatabaseUser'] = $configArray['Sierra']['DBUser'];
			$variables['ilsDatabasePassword'] = $configArray['Sierra']['DBPwd'];
			$variables['ilsDatabasePort'] = $configArray['Sierra']['DBPort'];
		}else{
			$variables['ilsDriver'] = $configArray['ILS']['ilsDriver'];
		}
		$siteOnWindows = $configArray['Site']['siteOnWindows'] == 'Y' || $configArray['Site']['siteOnWindows'] == 'y';
		$siteOnMac = !empty($configArray['Site']['siteOnMac']) && ($configArray['Site']['siteOnMac'] == 'Y' || $configArray['Site']['siteOnMac'] == 'y');

		if (!$siteOnWindows && !$siteOnMac){
			$linuxOS = $configArray['Site']['operatingSystem'];
		}
		$operatingSystem = $configArray['Site']['operatingSystem'];
	} else {
		echo "Invalid configuration file ($siteConfigFile) specified";
		exit();
	}
}
if (!$foundConfig) {
	$sitename = '';
	while (empty($sitename)) {
		$sitename = readline("Enter the sitename to be setup (e.g., demo.localhost, library.production) > ");
	}

	$cleanSitename = preg_replace('/\W/', '_', $sitename);
	$variables = [
		'sitename' => $sitename,
		'cleanSitename' => $cleanSitename,
	];

	//Prompt for any information we need to set up the site

	$variables['library'] = '';
	while (empty($variables['library'])) {
		$variables['library'] = readline("Enter the library or consortium name, e.g., Aspen Public Library > ");
	}

	$variables['supportingCompany'] = readline("Enter the name of the supporting company (default: ByWater Solutions) > ");
	if (empty($variables['supportingCompany'])) {
		$variables['supportingCompany'] = "ByWater Solutions";
	}

	$variables['title'] = '';
	while (empty($variables['title'])) {
		$variables['title'] = readline("Enter the title of the site, e.g., Aspen Demo (may be same as library name) > ");
	}

	$variables['url'] = '';
	while (empty($variables['url'])) {
		$variables['url'] = readline("Enter the url where the site will be accessed, e.g., https://demo.aspendiscovery.org or http://demo.localhost > ");
	}

	$siteOnWindows = readline("Will Aspen run on Windows (y/N)? ");
	if (empty($siteOnWindows) || ($siteOnWindows != 'Y' && $siteOnWindows != 'y')){
		$siteOnWindows = false;
	}else{
		$siteOnWindows = true;
	}

	$siteOnMac = readline("Will Aspen run on a Mac (y/N)? ");
	if (empty($siteOnMac) || ($siteOnMac != 'Y' && $siteOnMac != 'y')){
		$siteOnMac = false;
	}else{
		$siteOnMac = true;
	}

	if ($siteOnMac) {
		$variables['apacheGroup'] = readline("Enter the name of the group Apache belongs to (default _www, but this may sometimes be admin). ");
		if (empty($apacheGroup)) {
			$variables['apacheGroup'] = "_www";
		}
	}

	if (!$siteOnWindows && !$siteOnMac) {
		$linuxOS = '';
		while (empty($linuxOS) || !in_array($linuxOS, $linuxArray)) {
			$linuxOS = readline("Enter the name of your Linux OS (e.g., ".implode (" / ", $linuxArray)." ) > ");
		}
	}

	$variables['solrHost'] = readline("Which host should Solr run on (typically localhost)? ");
	if (empty($variables['solrHost'])){
		$variables['solrHost'] = "localhost";
	}

	$variables['solrPort'] = readline("Which port should Solr run on (typically 8080)? ");
	if (empty($variables['solrPort'])){
		$variables['solrPort'] = "8080";
	}

	$variables['ils'] = readline("Which ILS does the library use? (default is Koha) > ");
	if (empty($variables['ils'])){
		$variables['ils'] = "Koha";
	}

	if ($variables['ils'] == 'Koha'){
		$variables['ilsDriver'] = 'Koha';
		$variables['ilsDatabaseHost'] = '';
		while (empty($variables['ilsDatabaseHost'])) {
			$variables['ilsDatabaseHost'] = readline("Database host for Koha > ");
		}
		$variables['ilsDatabasePort'] = '';
		while (empty($variables['ilsDatabasePort'])) {
			$variables['ilsDatabasePort'] = readline("Database port for Koha > ");
		}
		$variables['ilsDatabaseName'] = '';
		while (empty($variables['ilsDatabaseName'])) {
			$variables['ilsDatabaseName'] = readline("Database schema name for Koha > ");
		}
		$variables['ilsDatabaseUser'] = '';
		while (empty($variables['ilsDatabaseUser'])) {
			$variables['ilsDatabaseUser'] = readline("Database username for Koha > ");
		}
		$variables['ilsDatabasePassword'] = '';
		while (empty($variables['ilsDatabasePassword'])) {
			$variables['ilsDatabasePassword'] = readline("Database password for {$variables['ilsDatabaseUser']} for Koha > ");
		}
		$variables['ilsDatabaseTimezone'] = readline("Database timezone for Koha (e.g., US/Central) > ");
		if (empty($variables['ilsDatabaseTimezone'])){
			$variables['ilsDatabaseTimezone'] = 'US/Central';
		}
		$variables['ilsClientId'] = readline("Client ID for Koha API > ");
		$variables['ilsClientSecret'] = readline("Client Secret for Koha API > ");
	}elseif ($variables['ils'] == 'Symphony'){
		$variables['ilsDriver'] = 'SirsiDynixROA';
		$variables['ilsClientId'] = readline("Client ID for Symphony API > ");
		$variables['ilsStaffUser'] = readline("Staff Username for use with the Symphony API > ");
		$variables['ilsStaffPassword'] = readline("Staff Password for use with the Symphony API > ");
	}elseif ($variables['ils'] == 'Polaris'){
		$variables['ilsDriver'] = 'Polaris';
		$variables['ilsApiVersion'] = readline("API Version for Polaris API i.e. 7.6 > ");
		$variables['ilsClientId'] = readline("Client ID for Polaris API > ");
		$variables['ilsClientSecret'] = readline("Client Secret for Polaris API > ");
		$variables['ilsDomain'] = readline("Staff Domain for use with the Polaris API > ");
		$variables['ilsStaffUser'] = readline("Staff Username for use with the Polaris API > ");
		$variables['ilsStaffPassword'] = readline("Staff Password for use with the Polaris API > ");
		$variables['ilsWorkstationID'] = readline("Workstation ID for use with the Polaris API > ");
	}elseif ($variables['ils'] == 'Sierra'){
		$variables['ilsDriver'] = 'Sierra';
		$variables['ilsClientId'] = readline("Client Key for Sierra API > ");
		$variables['ilsClientSecret'] = readline("Client Secret for Sierra API > ");
		$variables['ilsDatabaseHost'] = readline("Database host for Sierra DNA > ");
		$variables['ilsDatabasePort'] = readline("Database port for Sierra DNA > ");
		$variables['ilsDatabaseName'] = readline("Database schema name for Sierra DNA (normally iii) > ");
		$variables['ilsDatabaseUser'] = readline("Database username for Sierra DNA > ");
		$variables['ilsDatabasePassword'] = readline("Database password for Sierra DNA User > ");
	}

	while (empty($variables['ilsDriver'])) {
		$variables['ilsDriver'] = readline("Enter the Aspen Driver for the ILS  > ");
	}

	$variables['ilsUrl'] = '';
	while (empty($variables['ilsUrl'])) {
		$variables['ilsUrl'] = readline("Enter the url of the OPAC for the ILS  > ");
	}

	//This can be blank
	$variables['ilsStaffUrl'] = readline("Enter the url of the staff client for the ILS  > ");

	$variables['databaseHost'] =  readline("Database host for Aspen (default: localhost) > ");
	if (empty($variables['databaseHost'])){
		$variables['databaseHost'] = "localhost";
	}

	$variables['databasePort'] =  readline("Database host for Aspen (default: 3306) > ");
	if (empty($variables['databasePort'])){
		$variables['databasePort'] = "3306";
	}

	$variables['databaseName'] =  readline("Database name for Aspen (default: aspen) > ");
	if (empty($variables['databaseName'])){
		$variables['databaseName'] = "aspen";
	}

	$variables['databaseUser'] =  readline("Database username for Aspen (default: root) > ");
	if (empty($variables['databaseUser'])){
		$variables['databaseUser'] = "root";
	}

	$variables['databasePassword'] = '';
	while (empty($variables['databasePassword'])) {
		$variables['databasePassword'] = readline("Database password for {$variables['databaseUser']} for Aspen > ");
	}

	$variables['timezone'] =  readline("Enter the timezone of the library (e.g. America/Los_Angeles, check https://www.php.net/manual/en/timezones.php) > ");
	if (empty($variables['timezone'])){
		$variables['timezone'] = "America/Los_Angeles";
	}

	$variables['aspenAdminPassword'] = '';
	while (empty($variables['aspenAdminPassword'])) {
		$variables['aspenAdminPassword'] = readline("Select a password for the 'aspen_admin' user > ");
	}
}

$centos = [
	'wwwUser' => 'apache',
	'service' => 'httpd',
	'mysqlConf' => '/etc/my.cnf',
	'permissions' => 'updateSitePermissions.sh',
	'apacheDir' => '/etc/httpd/conf.d'
];

$debian = [
	'wwwUser' => 'www-data',
	'service' => 'apache2',
	'mysqlConf' => '/etc/mysql/mariadb.conf.d/60-aspen.cnf',
	'permissions' => 'updateSitePermissions_debian.sh',
	'apacheDir' => '/etc/apache2/sites-available'
];

$installDir = '/usr/local/aspen-discovery';
if ($runningOnWindows){
	$installDir = 'c:/web/aspen-discovery';
}
$siteDir = $installDir . '/sites/' . $sitename;

$variables['configDir'] = $siteDir;

$clearExisting = false;
if (file_exists($siteDir)){
	$clearExisting = readline ("The site directory already exists, do you want to remove the existing configuration (y/N)? ");
	if (empty($clearExisting) || ($clearExisting != 'Y' && $clearExisting != 'y')){
		die();
	}else{
		$continue = readline("REMOVING EXISTING CONFIGURATION, continue (y/N)? ");
		if (empty($continue) || ($continue != 'Y' && $continue != 'y')){
			die();
		}else{
			recursive_rmdir($siteDir);
		}
	}
}


$variables['servername'] = preg_replace('~https?://~', '', $variables['url']);

/*
 * Set up the server
 */

//Create the basic sites directory
if ($siteOnWindows){
	recursive_copy($installDir . '/sites/template.windows', $siteDir);
} else if ($siteOnMac) {
	recursive_copy($installDir . '/sites/template.mac', $siteDir);
}
else{
	recursive_copy($installDir . '/sites/template.linux', $siteDir);
}

//Rename files appropriately based on the sitename
rename($siteDir . '/httpd-{sitename}.conf', $siteDir . "/httpd-$sitename.conf");
if ($siteOnWindows){
	rename($siteDir . '/{sitename}.bat', $siteDir . "/$sitename.bat");
}else{
	rename($siteDir . '/{sitename}.sh', $siteDir . "/$sitename.sh");
}
rename($siteDir . '/conf/config.pwd.ini.template', $siteDir . "/conf/config.pwd.ini");

replaceVariables($siteDir . "/httpd-$sitename.conf", $variables);
if ($siteOnWindows) {
	replaceVariables($siteDir . "/$sitename.bat", $variables);
}else{
	replaceVariables($siteDir . "/$sitename.sh", $variables);
}
replaceVariables($siteDir . "/conf/config.ini", $variables);
replaceVariables($siteDir . "/conf/config.cron.ini", $variables);
replaceVariables($siteDir . "/conf/config.pwd.ini", $variables);

if (!$siteOnWindows){
	replaceVariables($siteDir . "/conf/crontab_settings.txt", $variables);
	if (!$siteOnMac) {
		exec('sudo timedatectl set-timezone "' . $variables['timezone'] . '"');
	}
}

if (!$siteOnWindows) {
	$tmpDir = '/tmp';
	if (!file_exists($tmpDir)) {
		mkdir($tmpDir);
	}
}

//Import the database
if ($runningOnWindows) {
	$mysqlConnectionCommand = "mysql -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" -h\"{$variables['databaseHost']}\"";
}else{
	$mysqlConnectionCommand = "mariadb -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" -h\"{$variables['databaseHost']}\"";
}
if ($variables['databasePort'] != "3306") {
	$mysqlConnectionCommand .= " --port \"{$variables['databasePort']}\"";
}
if ($clearExisting) {
	echo("Removing existing database\r\n");
	exec("$mysqlConnectionCommand -e\"DROP DATABASE IF EXISTS {$variables['databaseName']}\"");
}
echo("Creating database\r\n");
exec("$mysqlConnectionCommand -e\"CREATE DATABASE {$variables['databaseName']} DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci\"");
echo("Loading default database\r\n");
exec("$mysqlConnectionCommand {$variables['databaseName']} < $installDir/install/aspen.sql");


//Connect to the database
$aspen_db = new PDO("mysql:dbname={$variables['databaseName']};host={$variables['databaseHost']}",$variables['databaseUser'],$variables['databasePassword']);
$updateUserStmt = $aspen_db->prepare("UPDATE user set cat_password=" . $aspen_db->quote($variables['aspenAdminPassword']) . ", password=" . $aspen_db->quote($variables['aspenAdminPassword']) . " where username = 'aspen_admin'");
$updateUserStmt->execute();

//Assign supportingCompany in the db
/** @noinspection SqlWithoutWhere */
$postSupportingCompanyStmt = $aspen_db->prepare("UPDATE system_variables set supportingCompany=" . $aspen_db->quote($variables['supportingCompany']));
$postSupportingCompanyStmt->execute();

if ($variables['ils'] == 'Koha'){
	// Attempt to get the system's temp directory
	$tmp_dir = rtrim(sys_get_temp_dir(), "/");
	echo("Loading Koha information to database\r\n");
	copy("$installDir/install/koha_connection.sql", "$tmp_dir/koha_connection_$sitename.sql");
	replaceVariables("$tmp_dir/koha_connection_$sitename.sql", $variables);
	exec("mysql -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" {$variables['databaseName']} < $tmp_dir/koha_connection_$sitename.sql");
}elseif ($variables['ils'] == 'Symphony'){
	$tmp_dir = rtrim(sys_get_temp_dir(), "/");
	echo("Loading Symphony information to database\r\n");
	copy("$installDir/install/symphony_connection.sql", "$tmp_dir/symphony_connection_$sitename.sql");
	replaceVariables("$tmp_dir/symphony_connection_$sitename.sql", $variables);
	exec("mysql -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" {$variables['databaseName']} < $tmp_dir/symphony_connection_$sitename.sql");
}elseif ($variables['ils'] == 'Polaris'){
	$tmp_dir = rtrim(sys_get_temp_dir(), "/");
	echo("Loading Polaris information to database\r\n");
	copy("$installDir/install/polaris_connection.sql", "$tmp_dir/polaris_connection_$sitename.sql");
	replaceVariables("$tmp_dir/polaris_connection_$sitename.sql", $variables);
	exec("mysql -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" {$variables['databaseName']} < $tmp_dir/polaris_connection_$sitename.sql");
}elseif ($variables['ils'] == 'Sierra'){
	$tmp_dir = rtrim(sys_get_temp_dir(), "/");
	echo("Loading Sierra information to database\r\n");
	copy("$installDir/install/sierra_connection.sql", "$tmp_dir/sierra_connection_$sitename.sql");
	replaceVariables("$tmp_dir/sierra_connection_$sitename.sql", $variables);
	exec("mysql -u{$variables['databaseUser']} -p\"{$variables['databasePassword']}\" {$variables['databaseName']} < $tmp_dir/sierra_connection_$sitename.sql");
}

$aspen_db = null;

//Make data directories
echo("Setting up data and log directories\r\n");
$dataDirBase = '/data/aspen-discovery';
if ($siteOnMac) {
	$dataDirBase = '/usr/local/data/aspen-discovery';
	$macUser = exec('whoami') ?? 'root';
	//Make sure the data directory exists
	if (!file_exists('/usr/local/data/aspen-discovery')) {
		exec('sudo mkdir -p /usr/local/data/aspen-discovery');
		exec('sudo chmod 775 /usr/local/data/aspen-discovery');
		exec('sudo chown -R ' . $macUser . ':' . $variables['apacheGroup'] . ' /usr/local/data/aspen-discovery');
	}
}
$dataDir = $dataDirBase . '/' . $sitename;
if (!file_exists($dataDir)){
	mkdir($dataDir, 0775, true);
	if (!$siteOnMac) {
		chgrp($dataDir, 'aspen_apache');
	} else {
		chgrp($dataDir, $variables['apacheGroup']);
	}
	chmod($dataDir, 0775);
}
if (!file_exists($dataDirBase . '/accelerated_reader')){
	mkdir($dataDirBase . '/accelerated_reader', 0775, true);
	if (!$siteOnMac) {
		chgrp($dataDirBase . '/accelerated_reader', 'aspen_apache');
	} else {
		chgrp($dataDirBase . '/accelerated_reader', $variables['apacheGroup']);
	}
	chmod($dataDirBase . '/accelerated_reader', 0775);
}
recursive_copy($installDir . '/data_dir_setup', $dataDir);
if ($siteOnMac) {
	exec('chown -R ' . $macUser . ':' . $variables['apacheGroup'] . ' ' . $dataDir);
}
elseif (!$runningOnWindows){
	exec('chown -R ' . $$linuxOS['wwwUser'] . ':aspen_apache ' . $dataDir);
}

//Make files directory writeable
if ($siteOnMac) {
	exec('chmod -R 755 ' . $installDir . '/code/web/files');
	exec('sudo chown -R ' . $macUser . ':' . $variables['apacheGroup'] . ' ' . $installDir . '/code/web/fonts');
}
elseif (!$runningOnWindows){
	exec('chmod -R 755 ' . $installDir . '/code/web/files');
	exec('chown -R ' . $$linuxOS['wwwUser'] . ':aspen_apache ' . $installDir . '/code/web/fonts');
}

//update conf directory permissions
if (!$runningOnWindows && !$siteOnMac) {
	exec('chown aspen:aspen_apache /usr/local/aspen-discovery/sites/' . $sitename . '/conf');
}

//update marc_recs directory permissions
if (!$runningOnWindows && !$siteOnMac) {
	exec('chown -R aspen:aspen_apache ' . $dataDir . '/ils/');
}

//Make sure the log directory exists
if ($siteOnMac && !file_exists('/var/log/aspen-discovery')) {
	exec('sudo mkdir /var/log/aspen-discovery');
	exec('sudo chmod 775 /var/log/aspen-discovery');
	exec('sudo chown -R ' . $macUser . ':' . $variables['apacheGroup'] . ' /var/log/aspen-discovery');
}

//Make log directories
$logDir = '/var/log/aspen-discovery/' . $sitename;
if (!file_exists($logDir)){
	mkdir($logDir, 0775, true);
}
$logDir2 = '/var/log/aspen-discovery/' . $sitename . '/logs';
if (!file_exists($logDir2)){
	mkdir($logDir2, 0775, true);
}

//Update file permissions
if (!$runningOnWindows && !$siteOnMac) {
	exec('./'.$$linuxOS['permissions'] . ' ' . $sitename);
}

//Link the httpd conf file
if (!$siteOnWindows && !$siteOnMac) {
	symlink($siteDir . "/httpd-$sitename.conf", $$linuxOS['apacheDir'] . "/httpd-$sitename.conf");
	if ($linuxOS == 'debian') {
		/** @noinspection SpellCheckingInspection */
		exec("a2ensite httpd-$sitename");
	}
	//Restart apache
	exec("systemctl restart " . $$linuxOS['service']);
}

//Setup solr
if (!$siteOnWindows && !$siteOnMac) {
	exec('chown -R solr:solr ' . $installDir . '/sites/default/solr-8.11.2');
	exec('chown -R solr:solr ' . $dataDir . '/solr7');
}

if ($siteOnMac) {
	exec('chmod +x ' . $siteDir . "/$sitename.sh");
}

if (!$siteOnWindows && !$siteOnMac) {
	//Start solr
	exec('chmod +x ' . $siteDir . "/$sitename.sh");
	execInBackground($siteDir . "/$sitename.sh start");
	//Link cron to /etc/cron.d folder
	exec("ln -s /usr/local/aspen-discovery/sites/$sitename/conf/crontab_settings.txt /etc/cron.d/$cleanSitename");
}

//Update my.cnf for backups
if (!$siteOnWindows && !$siteOnMac) {
	replaceVariables($$linuxOS['mysqlConf'], $variables);
}

echo("\r\n");
echo("\r\n");
echo("-------------------------------------------------------------------------\r\n");
echo("Next Steps\r\n");
$step = 1;
if ($siteOnWindows || $siteOnMac) {
	echo($step++ . ") Add Include \"$siteDir/httpd-$sitename.conf\" to the httpd.conf file\r\n");
	echo($step++ . ") Add {$variables['servername']} to the hosts file\r\n");
	echo($step++ . ") Restart apache\r\n");
	echo($step++ . ") Start Solr\r\n");
}
echo($step++ . ") Login to the server as aspen_admin and run database updates\r\n");
echo($step++ . ") Setup library(ies) within the admin interface\r\n");
echo($step++ . ") Setup location(s) within the admin interface\r\n");
echo($step++ . ") Start initial index\r\n");
echo($step++ . ") Firewall Solr port to ensure that it is not accessible to the world\r\n");

exit();

function recursive_copy($src,$dst) : void {
	$dir = opendir($src);
	@mkdir($dst);
	while(( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recursive_copy($src .'/'. $file, $dst .'/'. $file);
			} else {
				copy($src .'/'. $file,$dst .'/'. $file);
			}
		}
	}
	closedir($dir);
}

function recursive_rmdir($dir) : void {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (is_dir($dir."/".$object) && !is_link($dir."/".$object))
					recursive_rmdir($dir."/".$object);
				else
					unlink($dir."/".$object);
			}
		}
		rmdir($dir);
	}
}

function replaceVariables($filename, $variables) : void {
	$contents = file ($filename);
	$fHnd = fopen($filename, 'w');
	foreach ($contents as $line){
		foreach ($variables as $name => $value){
			$line = str_replace('{' . $name . '}', $value, $line);
		}
		fwrite($fHnd, $line);
	}
	fclose($fHnd);
}

function execInBackground($cmd) : void {
	echo ("Running $cmd\r\n");
	if (str_starts_with(php_uname(), "Windows")){
		$cmd = str_replace('/', '\\', $cmd);
		pclose(popen("start /B ". $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}
