<?php
/**
 * Command-line tool to generate sitemaps based on Solr index contents.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Utilities
 * @author   David K. Uspal <david.uspal@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/search_engine_optimization Wiki
 */

//ini_set('memory_limit', '50M');
//ini_set('max_execution_time', '3600');

/**
 * Set up util environment
 */
require_once 'util.inc.php';
require_once 'sys/ConnectionManager.php';

// Read Config file
$base = dirname(__FILE__);
$configArray = parse_ini_file($base . '/../web/conf/config.ini', true);
if (!$configArray) {
    PEAR::raiseError(new PEAR_Error("Can't open file - ../web/conf/config.ini"));
}
$sitemapArray = parse_ini_file($base . '/../web/conf/sitemap.ini', true);
if (!$sitemapArray) {
    PEAR::raiseError(new PEAR_Error("Can't open file - ../web/conf/sitemap.ini"));
}

$result_url = $configArray['Site']['url'] . "/" . "Record" . "/";
$frequency = htmlspecialchars($sitemapArray['Sitemap']['frequency']);
$countPerPage = $sitemapArray['Sitemap']['countPerPage'];
$fileStart = $sitemapArray['Sitemap']['fileLocation'] . "/" .
    $sitemapArray['Sitemap']['fileName'];

$solr = ConnectionManager::connectToIndex();

$currentPage = 1;
$last_term = '';

while (true) {
    if ($currentPage == 1) {
        $fileWhole = $fileStart . ".xml";
    } else {
        $fileWhole = $fileStart . "-" . $currentPage . ".xml";
    }

    $current_page_info_array = $solr->getTerms('id', $last_term, $countPerPage);
    if (!isset($current_page_info_array['terms']['id'])
        || count($current_page_info_array['terms']['id']) < 1
    ) {
        break;
    } else {
        $smf = openSitemapFile($fileWhole, 'urlset');
        foreach ($current_page_info_array['terms']['id'] as $item => $count) {
            $loc = htmlspecialchars($result_url . urlencode($item));
            fwrite($smf, '<url>' . "\n");
            fwrite($smf, '  <loc>' . $loc . '</loc>' . "\n");
            fwrite($smf, '  <changefreq>' . $frequency . '</changefreq>' . "\n");
            fwrite($smf, '</url>' . "\n");
            $last_term = $item;
        }

        fwrite($smf, '</urlset>');
        fclose($smf);
    }

    $currentPage++;
}

// Set-up Sitemap Index
if (isset($sitemapArray['SitemapIndex']['indexFileName'])) {
    $fileWhole = $sitemapArray['Sitemap']['fileLocation'] . "/" .
        $sitemapArray['SitemapIndex']['indexFileName']. ".xml";
    $smf = openSitemapFile($fileWhole, 'sitemapindex');

    // Add a <sitemap /> group for a static sitemap file. See sitemap.ini for more
    // information on this option.
    if (isset($sitemapArray['SitemapIndex']['baseSitemapFileName'])) {
        $baseSitemapFile = $sitemapArray['Sitemap']['fileLocation'] . "/" .
            $sitemapArray['SitemapIndex']['baseSitemapFileName'] . ".xml";
        // Only add the <sitemap /> group if the file exists in the directory where
        // the other sitemap files are saved, i.e. ['Sitemap']['fileLocation']
        if (file_exists($baseSitemapFile)) {
            writeSitemapIndexLine(
                $smf, $sitemapArray['SitemapIndex']['baseSitemapFileName']
            );
        } else {
            print "WARNING: Can't open file " . $baseSitemapFile . ". " .
                "The sitemap index will be generated without this sitemap file.\n";
        }
    }

    // Add <sitemap /> group for each sitemap file generated.
    for ($i = 1; $i < $currentPage; $i++) {
        $sitemapNumber = ($i == 1) ? "" : "-" . $i;
        writeSitemapIndexLine(
            $smf, $sitemapArray['Sitemap']['fileName'] . $sitemapNumber
        );
    }

    fwrite($smf, '</sitemapindex>');
    fclose($smf);
}

/**
 * Start writing a sitemap file (including the top-level open tag).
 *
 * @param string $filename Filename to open.
 * @param string $startTag Top-level tag in file.
 *
 * @return int             File handle of open file.
 */
function openSitemapFile($filename, $startTag)
{
    $smf = fopen($filename, 'w');
    if (!$smf) {
        PEAR::raiseError(new PEAR_Error("Can't open file - " . $filename));
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<' . $startTag . "\n" .
        '     xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n" .
        '     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n" .
        "     xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
        '     http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n\n";
    fwrite($smf, $xml);

    return $smf;
}

/**
 * Write a line to the sitemap index file.
 *
 * @param int    $smf      File handle to write to.
 * @param string $filename Filename (not including path) to store.
 *
 * @return void
 */
function writeSitemapIndexLine($smf, $filename)
{
    global $configArray;
    global $sitemapArray;

    // Pick the appropriate base URL based on the configuration files:
    if (!isset($sitemapArray['SitemapIndex']['baseSitemapUrl'])
        || empty($sitemapArray['SitemapIndex']['baseSitemapUrl'])
    ) {
        $baseUrl = $configArray['Site']['url'];
    } else {
        $baseUrl = $sitemapArray['SitemapIndex']['baseSitemapUrl'];
    }

    $loc = htmlspecialchars("{$baseUrl}/{$filename}.xml");
    $lastmod = htmlspecialchars(date("Y-m-d"));
    fwrite($smf, '  <sitemap>' . "\n");
    fwrite($smf, '    <loc>' . $loc . '</loc>' . "\n");
    fwrite($smf, '    <lastmod>' . $lastmod . '</lastmod>' . "\n");
    fwrite($smf, '  </sitemap>' . "\n");
}
?>
