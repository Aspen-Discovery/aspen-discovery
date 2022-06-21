<?php
//Copied files for pickup by the Aspen server

if (count($_SERVER['argv']) < 3) {
	die("Usage php moveUploadedMarcs.php fromUser toDirectory\n");
}
$copyFrom = $_SERVER['argv'][1];
$copyTo = $_SERVER['argv'][2];

//Copy full MARC exports
$marcDirName = "/home/$copyFrom/marc/";
$marcDestDirName = "/xfer/$copyTo/marc/";
if (!is_dir($marcDestDirName)){
	die(date('Y-m-d H:i:s') . " Could not find destination marc directory at $marcDestDirName \n");
}
if (!is_dir($marcDirName)) {
	die(date('Y-m-d H:i:s')  . " Could not find marc directory at $marcDirName \n");
}
//We just want the latest full export.  If there are others they can be deleted.
$files = scandir($marcDirName);
if (count($files) > 0) {
	$latestMarcFile = null;
	$latestMarcFileModificationTime = 0;
	$latestMarcFileSize = 0;

	$latestIdsFile = null;
	$latestIdsFileModificationTime = 0;
	$latestIdsFileSize = 0;
	foreach ($files as $file) {
		if ($file != '.' && $file != '..' && is_file($marcDirName . $file)) {
			if (strpos($file, ".mrc") > 0) {
				$lastModificationTime = filemtime($marcDirName . $file);
				if ($lastModificationTime > $latestMarcFileModificationTime) {
					$latestMarcFileModificationTime = $lastModificationTime;
					$latestMarcFileSize = filesize($marcDirName . $file);
					$latestMarcFile = [
						'fullPath' => $marcDirName . $file,
						'name' => $file
					];
				}
			}elseif (strpos($file, ".ids") > 0) {
				$lastModificationTime = filemtime($marcDirName . $file);
				if ($lastModificationTime > $latestIdsFileModificationTime) {
					$latestIdsFileModificationTime = $lastModificationTime;
					$latestIdsFileSize = filesize($marcDirName . $file);
					$latestIdsFile = [
						'fullPath' => $marcDirName . $file,
						'name' => $file
					];
				}
			}else{
				echo(date('Y-m-d H:i:s') . " unknown file type for $file\n");
			}
		}
	}

	//If we got a file, check to see if it is changing
	if ($latestMarcFile != null){
		echo(date('Y-m-d H:i:s') . "Found full export " . $latestMarcFile['fullPath'] . "\n");
		sleep(2);
		if (filemtime($latestMarcFile['fullPath']) == $latestMarcFileModificationTime && $latestMarcFileSize == filesize($latestMarcFile['fullPath'])){
			//File is not changing, we can move it.
			if (rename($latestMarcFile['fullPath'], $marcDestDirName . $latestMarcFile['name'])){
				echo(date('Y-m-d H:i:s') . " moved full export to dest dir\n");
			}else{
				echo(date('Y-m-d H:i:s') . " ERROR could not move full export to dest dir $marcDestDirName\n");
			}

			//Delete any other files that were in the directory since they are just old files.
			$files = scandir($marcDirName);
			foreach ($files as $file) {
				if ($file != '.' && $file != '..' && strpos($file, ".mrc") > 0){
					if (unlink($marcDirName . $file)){
						echo(date('Y-m-d H:i:s') . "Deleted full export " . $marcDirName . $file . " that was older than the latest\n") ;
					}
				}
			}
		}else{
			echo(date('Y-m-d H:i:s') . " full export is still changing\n");
		}
	}

	if ($latestIdsFile != null){
		echo(date('Y-m-d H:i:s') . "Found all ids export " . $latestIdsFile['fullPath'] . "\n");
		sleep(2);
		if (filemtime($latestIdsFile['fullPath']) == $latestIdsFileModificationTime && $latestIdsFileSize == filesize($latestIdsFile['fullPath'])){
			//File is not changing, we can move it.
			if (rename($latestIdsFile['fullPath'], $marcDestDirName . $latestIdsFile['name'])){
				echo(date('Y-m-d H:i:s') . " moved ids file to dest dir\n");
			}else{
				echo(date('Y-m-d H:i:s') . " ERROR could not move ids file to dest dir $marcDestDirName\n");
			}

			//Delete any other files that were in the directory since they are just old files.
			$files = scandir($marcDirName);
			foreach ($files as $file) {
				if ($file != '.' && $file != '..' && strpos($file, ".ids") > 0){
					if (unlink($marcDirName . $file)){
						echo(date('Y-m-d H:i:s') . "Deleted ids file " . $marcDirName . $file . " that was older than the latest\n") ;
					}
				}
			}
		}else{
			echo(date('Y-m-d H:i:s') . " all ids export is still changing\n");
		}
	}
}

//Copy MARC delta files
$marcDeltaDirName = "/home/$copyFrom/marc_delta/";
$marcDeltaDestDirName = "/xfer/$copyTo/marc_delta/";
if (!is_dir($marcDeltaDestDirName)){
	die(date('Y-m-d H:i:s') . " Could not find destination marc_delta directory at $marcDestDirName \n");
}
if (!is_dir($marcDeltaDirName)) {
	die(date('Y-m-d H:i:s') . " Could not find marc_delta directory at $marcDirName \n");
}
//Want all marc_delta files, not just the latest
$files = scandir($marcDeltaDirName);
if (count($files) > 0) {
	foreach ($files as $file) {
		if ($file != '.' && $file != '..' && is_file($marcDeltaDirName . $file)) {
			echo(date('Y-m-d H:i:s') . " found delta export " . $marcDeltaDirName . $file . "\n");
			//make sure the file is not still changing.  If it is, skip for now
			$lastModificationTime = filemtime($marcDeltaDirName . $file);
			$lastFileSize = filesize($marcDeltaDirName . $file);
			sleep(2);
			if (filemtime($marcDeltaDirName . $file) == $lastModificationTime && $lastFileSize == filesize($marcDeltaDirName . $file)){
				//File is not changing, we can move it.
				if (rename($marcDeltaDirName . $file, $marcDeltaDestDirName . $file)){
					echo(date('Y-m-d H:i:s') . " moved delta export to dest dir\n");
				}else{
					echo(date('Y-m-d H:i:s') . " ERROR could not move delta export to dest dir $marcDeltaDestDirName\n");
				}
			}else{
				echo(date('Y-m-d H:i:s') . " delta export is still changing\n");
			}
		}
	}
}