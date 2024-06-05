<?php
header('Content-type: text/plain');
date_default_timezone_set('America/Denver');
$mergeListFile = fopen(__DIR__ . "/javascript_files.txt", 'r');
$mergedFile = fopen(__DIR__ . "/aspen.js", 'w');
while (($fileToMerge = fgets($mergeListFile)) !== false){
	$fileToMerge = trim($fileToMerge);
	if (strpos($fileToMerge, '#') !== 0){
	    if (file_exists(__DIR__ . '/' . $fileToMerge)){
		    fwrite($mergedFile, file_get_contents(__DIR__ . '/' . $fileToMerge, true));
			fwrite($mergedFile, "\r\n");
		}else{
		    echo("$fileToMerge does not exist\r\n");
		}
	}
}
fclose($mergedFile);
fclose($mergeListFile);