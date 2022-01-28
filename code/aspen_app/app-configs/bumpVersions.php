<?php
$val = getopt("r:");
if (isset($val['r'])) {
	$releaseType = $val['r'];
	if (!in_array($releaseType, array('major', 'minor', 'patch'))) {
		echo "Release type not valid. Try again with -r[major,minor,patch].\n";
		die();
	}
} else {
	echo "Release type not provided. Run again with -r[major,minor,patch].\n";
	die();
}
$dir_handle = opendir("/usr/local/aspen-discovery/code/aspen_app/app-configs/");
if(is_resource($dir_handle)) {
	echo("Directory found and opened. Reading files...\n");
	foreach (glob("*.json") as $filename) {
		$jsonFileContents = file_get_contents($filename);
		$arr = json_decode($jsonFileContents, true);
		$version = $arr["expo"]["version"];
		$version = explode(".",$version);
		$versionMajor = intval($version[0]);
		$versionMinor = intval($version[1]);
		$versionPatch = intval($version[2]);
		if($releaseType === "major") {
			$versionMajor++;
		} elseif($releaseType === "minor") {
			$versionMinor++;
		} elseif($releaseType === "patch") {
			$versionPatch++;
		}
		$newVersion = implode(".", [$versionMajor, $versionMinor, $versionPatch]);
		$arr["expo"]["version"] = $newVersion;

		$buildNum = $arr["expo"]["ios"]["buildNumber"];
		$buildNum++;
		$arr["expo"]["ios"]["buildNumber"] = $buildNum;

		$versionCode = $arr["expo"]["android"]["versionCode"];
		$versionCode++;
		$arr["expo"]["android"]["versionCode"] = $versionCode;

		$newJsonString = json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		file_put_contents($filename, $newJsonString);
	}
	echo("Files updated.\n");
	closedir($dir_handle);
} else {
	echo("Directory not found.");
	die();
}