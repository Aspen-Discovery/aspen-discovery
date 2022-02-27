<?php
//Copied files for pickup by the Aspen server

if (count($_SERVER['argv']) < 3) {
	die("Usage php moveUploadedMarcs.php fromUser toDirectory");
}
$copyFrom = $_SERVER['argv'][1];
$copyTo = $_SERVER['argv'][2];

//Copy full MARC exports
$marcDirName = "/home/$copyFrom/marc/";
$marcDestDirName = "/xfer/$copyTo/marc/";
if (!is_dir($marcDestDirName)){
	die("Could not find destination marc directory at $marcDestDirName \n");
}
if (!is_dir($marcDirName)) {
	die("Could not find marc directory at $marcDirName \n");
}
//We just want the latest full export.  If there are others they can be deleted.
$files = scandir($marcDirName);
if (count($files) > 0) {
	$latestFile = null;
	$latestFileModificationTime = 0;
	foreach ($files as $file) {
		if ($file != '.' && $file != '..' && is_file($marcDirName . $file)) {
			$lastModificationTime = filemtime($marcDirName . $file);
			if ($lastModificationTime > $latestFileModificationTime) {
				$latestFileModificationTime = $lastModificationTime;
				$latestFile = [
					'fullPath' => $marcDirName . $file,
					'name' => $file
				];
			}
		}
	}

	//If we got a file, check to see if it is changing
	if ($latestFile != null){
		sleep(2);
		if (filemtime($latestFile['fullPath']) == $latestFileModificationTime){
			//File is not changing, we can move it.
			rename($latestFile['fullPath'], $marcDestDirName . $latestFile['name']);

			//Delete any other files that were in the directory since they are just old files.
			$files = scandir($marcDirName);
			foreach ($files as $file) {
				if ($file != '.' && $file != '..'){
					unlink($marcDirName . $file);
				}
			}
		}
	}
}

//Copy MARC delta files
$marcDeltaDirName = "/home/$copyFrom/marc_delta/";
$marcDeltaDestDirName = "/xfer/$copyTo/marc_delta/";
if (!is_dir($marcDeltaDestDirName)){
	die("Could not find destination marc_delta directory at $marcDestDirName \n");
}
if (!is_dir($marcDeltaDirName)) {
	die("Could not find marc_delta directory at $marcDirName \n");
}
//We just want the latest full export.  If there are others they can be deleted.
$files = scandir($marcDeltaDirName);
if (count($files) > 0) {
	foreach ($files as $file) {
		if ($file != '.' && $file != '..' && is_file($marcDeltaDirName . $file)) {
			//make sure the file is not still changing.  If it is, skip for now
			$lastModificationTime = filemtime($marcDeltaDirName . $file);
			sleep(2);
			if (filemtime($marcDeltaDirName . $file) == $lastModificationTime){
				//File is not changing, we can move it.
				rename($marcDeltaDirName . $file, $marcDeltaDestDirName . $file);
			}
		}
	}
}