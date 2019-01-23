<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/14/14
 * Time: 10:10 AM
 */
date_default_timezone_set('America/Denver');
$mergeListFile = fopen("./javascript_files.txt", 'r');
$mergedFile = fopen("vufind.min.js", 'w');
while (($fileToMerge = fgets($mergeListFile)) !== false){
	$fileToMerge = trim($fileToMerge);
	if (strpos($fileToMerge, '#') !== 0){
		if (file_exists($fileToMerge)){
			fwrite($mergedFile, file_get_contents($fileToMerge, true));
			fwrite($mergedFile, "\r\n");
		}else{
			echo("$fileToMerge does not exist\r\n");
		}
	}
}
fclose($mergedFile);
fclose($mergeListFile);