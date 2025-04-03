<?php

//Capture any error as ErrorException
set_error_handler("customErrorHandler");

if (count($argv) < 2) {
	echo "To create new configuration files, a directory (where the files will be stored) will be necessary as argument.\n";
	die();
} elseif (count($argv) > 2) {
	echo "Too many arguments have been passed. The script just needs a directory to start\n";
	die(1);
}

$apacheConfFile = $argv[1];

if (is_dir($apacheConfFile)) {
	echo "'$apacheConfFile' is a directory.\n";
	die(1);
}

echo "%   --> Copying apache configurations...\n";

try {
    	//Copy the httpd conf file
	    $apacheDir = "/etc/apache2";
		$dockerDir = "/usr/local/aspen-discovery/docker";
        $fileName = basename($apacheConfFile);
	    copy("$apacheConfFile", "$apacheDir/sites-enabled/$fileName");

		// Copy data-alias.conf to allow apache accessing files that are not in the served path
		$dataAliasConf = "$apacheDir/conf-enabled/data-alias.conf";
		copy("$dockerDir/files/apache/data-alias.conf", "$apacheDir/conf-available/data-alias.conf");
		if (!$dataAliasConf){
			exec('2enconf data-alias', $output);
		}


} catch (ErrorException $e) {
	echo "%   ERROR ASSIGNING PERMISSIONS AND OWNERSHIPS\n";
	echo "%   ERROR MESSAGE : " . $e->getMessage() . "\n";
	echo "%   IN : " . $e->getFile() . ":" . $e->getLine() . "\n";
	die(1);
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

function replaceVariables($filename, $variables): void {
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