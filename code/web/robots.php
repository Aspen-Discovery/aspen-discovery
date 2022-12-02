<?php

require_once 'bootstrap.php';
global $configArray;
global $library;
global $serverName;
global $interface;
header('Content-Type:text/plain');
if ($configArray['Site']['isProduction']) {
	echo(@file_get_contents('robots.txt'));
	$fileName = 'sitemapindex.xml';
	$siteMap_Url = 'Sitemap: ' . $configArray['Site']['url'] . '/' . $fileName;
	// Append a new line char
	echo "\n";
	//Append the site map index file url
	echo $siteMap_Url . "\n";
} else {
	echo("User-agent: *\r\nDisallow: /\r\n");
}