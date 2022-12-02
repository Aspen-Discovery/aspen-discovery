<?php
require_once 'bootstrap.php';
require_once 'bootstrap_aspen.php';
global $configArray;
global $library;
global $serverName;
global $interface;
header('Content-Type:application/xml');
//header('Content-Type:text/plain');
$sitemaps = [];

if (true || $configArray['Site']['isProduction']) {
	$subdomain = $library->subdomain;
	$baseUrl = $configArray['Site']['url'];
	$sitemapFiles = scandir(ROOT_DIR . '/sitemaps/');
	foreach ($sitemapFiles as $sitemapFile) {
		if (strpos($sitemapFile, 'grouped_work_site_map_' . $subdomain) === 0) {
			$sitemaps[] = $baseUrl . '/' . $sitemapFile;
		}
	}
}

echo('<?xml version="1.0" encoding="UTF-8"?>');
echo('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
foreach ($sitemaps as $sitemap) {
	echo("<sitemap><loc>$sitemap</loc></sitemap>");
}
echo('</sitemapindex>');