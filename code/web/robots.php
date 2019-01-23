<?php
/**
 * Dynamic implementation of robots.txt to prevent indexing of non-production sites
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/9/14
 * Time: 11:19 AM
 */

require_once 'bootstrap.php';
global $configArray;
global $library;
global $serverName;
global $interface;
if ($configArray['Site']['isProduction']){
	echo(@file_get_contents('robots.txt'));

	if ($library != null){
		$subdomain = $library->subdomain;

		/*
		 * sitemap: <sitemap_url>
		 * */
		$file = 'robots.txt';
		// Open the file to get existing content
		$current = file_get_contents($file);

		$fileName = $subdomain . '.xml';
		$siteMap_Url = 'Sitemap: ' . $configArray['Site']['url'] . '/sitemaps/' .$fileName;
		// Append a new line char
		echo "\n";
		//Append the site map index file url
		echo $siteMap_Url . "\n";

		//Google may want this with a lower case sitemap even though they specify capitalized.  Provide both.
		$siteMap_Url2 = 'sitemap: ' . $configArray['Site']['url'] . '/sitemaps/' .$fileName;
		echo $siteMap_Url2 . "\n";
	}
}else {
	echo("User-agent: *\r\nDisallow: /\r\n");
}