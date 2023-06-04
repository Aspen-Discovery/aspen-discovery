<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

$pendingUpdates = new ScheduledUpdate();
$pendingUpdates->status = 'pending';
$pendingUpdates->remoteUpdate = 0;
$pendingUpdates->whereAdd('dateScheduled <= ' . time()); //Only get things where the scheduled time is before right now
$pendingUpdates->orderBy('dateScheduled asc');
//Load all of them once since we update them
$updatesToRun = $pendingUpdates->fetchAll('id');

global $configArray;
global $serverName;

if (count($updatesToRun) == 0) {
	console_log("no updates to run\n");
}else {
	foreach ($updatesToRun as $id) {
		//Load the actual item
		$scheduledUpdate = new ScheduledUpdate();
		$scheduledUpdate->id = $id;
		if ($scheduledUpdate->find(true)) {
			$versionToUpdateTo = $scheduledUpdate->updateToVersion;
			$currentVersion = getGitBranch();

			if (!preg_match('/\d{2}\.\d{2}\.\d{2}/', $versionToUpdateTo)) {
				$scheduledUpdate->notes = "FAILED: Bad version to update to $versionToUpdateTo \n";
			}else{
				if (str_replace('.', '', $versionToUpdateTo) >= str_replace('.', '', $currentVersion)) {
					$operatingSystem = $configArray['System']['operatingSystem'];
					$linuxDistribution = '';
					if (strcasecmp($operatingSystem, 'windows') == 0) {
						$installDir = 'c:\web\aspen-discovery';
					} else {
						$installDir = '/usr/local/aspen-discovery';
						$osInformation = getOSInformation();
						if ($osInformation != null) {
							$linuxDistribution = $osInformation['id'];
							$scheduledUpdate->notes .= "Linux distribution is $linuxDistribution\n";
						} else {
							$scheduledUpdate->notes .= "Could not determine Linux distribution\n";
						}
					}

					//Check to see if this is a secondary site or a full site
					$systemVariables = SystemVariables::getSystemVariables();
					$isSecondarySite = false;
					if (!empty($systemVariables)) {
						$isSecondarySite = $systemVariables->doQuickUpdates;
					}
					if ($isSecondarySite) {
						if ($scheduledUpdate->updateType === 'complete') {
							doFullSecondaryUpgrade($operatingSystem, $linuxDistribution, $serverName, $versionToUpdateTo, $installDir, $scheduledUpdate);
						} elseif ($scheduledUpdate->updateType === 'patch') {
							doSecondaryUpdate($operatingSystem, $linuxDistribution, $versionToUpdateTo, $scheduledUpdate);
						}
					} else {
						if ($scheduledUpdate->updateType === 'complete') {
							doFullUpgrade($operatingSystem, $linuxDistribution, $serverName, $versionToUpdateTo, $installDir, $scheduledUpdate);
						} elseif ($scheduledUpdate->updateType === 'patch') {
							doPatchUpgrade($operatingSystem, $versionToUpdateTo, $scheduledUpdate);
						} else {
							// invalid updateType
							$scheduledUpdate->notes = "FAILED: Invalid update type\n";
						}
					}
				} else {
					$scheduledUpdate->notes = "FAILED: Must update to a version that is the same or newer than the current version of $currentVersion\n";
				}
			}

			if (hasErrors($scheduledUpdate->notes)) {
				$scheduledUpdate->status = 'failed';
			} else {
				$scheduledUpdate->status = 'complete';
			}
			$scheduledUpdate->dateRun = time();

			if (!$scheduledUpdate->update()) {
				echo("Could not update scheduled update " . $scheduledUpdate->getLastError());
			}

			if (!empty($scheduledUpdate->greenhouseId)) {
				// update greenhouse if the update was scheduled from there
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				$systemVariables = SystemVariables::getSystemVariables();
				if (!empty($systemVariables)) {
					$greenhouseUrl = $systemVariables->greenhouseUrl;
					require_once ROOT_DIR . '/sys/CurlWrapper.php';
					$curl = new CurlWrapper();
					$body = [
						'runType' => $scheduledUpdate->updateType,
						'dateScheduled' => $scheduledUpdate->dateScheduled,
						'updateToVersion' => $scheduledUpdate->updateToVersion,
						'status' => $scheduledUpdate->status,
						'greenhouseId' => $scheduledUpdate->greenhouseId,
						'notes' => $scheduledUpdate->notes,
						'dateRun' => $scheduledUpdate->dateRun,

					];
					$response = $curl->curlPostPage($greenhouseUrl . '/API/GreenhouseAPI?method=updateScheduledUpdate', $body);

					//TODO: temp debugging
					//print_r($response);
				}
			}
		}
	}
	console_log("Finished running " . count($updatesToRun) . " updates\n");
}

/**
 * @param $operatingSystem
 * @param $versionToUpdateTo
 * @param ScheduledUpdate $scheduledUpdate
 * @return void
 */
function doPatchUpgrade($operatingSystem, $versionToUpdateTo, ScheduledUpdate $scheduledUpdate): void{
	if ($operatingSystem == 'linux') {
		executeCommand("Stopping java", "pkill java", $scheduledUpdate);
	}
	updateGitAndRunDatabaseUpdates($operatingSystem, $versionToUpdateTo, $scheduledUpdate);
}

/**
 * @param $operatingSystem
 * @param $versionToUpdateTo
 * @param ScheduledUpdate $scheduledUpdate
 * @return void
 */
function updateGitAndRunDatabaseUpdates($operatingSystem, $versionToUpdateTo, ScheduledUpdate $scheduledUpdate): void {
	if (strcasecmp($operatingSystem, 'windows') == 0) {
		$installDir = 'c:\web\aspen-discovery';
	} else {
		$installDir = '/usr/local/aspen-discovery';
	}
	executeCommand("Fetching all changes from git", "cd $installDir; git fetch origin", $scheduledUpdate);
	if (!hasErrors($scheduledUpdate->notes)) {
		executeCommand("Resetting git to branch $versionToUpdateTo", "cd $installDir; git reset --hard origin/$versionToUpdateTo 2>&1", $scheduledUpdate);
	}

	if (!hasErrors($scheduledUpdate->notes)) {
		executeCommand("Pulling branch $versionToUpdateTo", "cd $installDir; git pull origin $versionToUpdateTo", $scheduledUpdate);
	}

	if (!hasErrors($scheduledUpdate->notes)) {
		runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate);
	}
}

function runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate) {
	// run db maintenance
	$scheduledUpdate->notes .= "Running database maintenance $versionToUpdateTo\n";
	require_once ROOT_DIR . '/services/API/SystemAPI.php';
	$systemAPI = new SystemAPI();
	$dbMaintenance = $systemAPI->runPendingDatabaseUpdates();
	if (!isset($dbMaintenance['success']) || $dbMaintenance['success'] == false) {
		$scheduledUpdate->status = 'failed';
	}
	if (isset($dbMaintenance['message'])) {
		$message = $dbMaintenance['message'] ?? '';
		$scheduledUpdate->notes .= $message . "\n";
	}
}

/**
 * @param $operatingSystem
 * @param $linuxDistribution
 * @param $serverName
 * @param $versionToUpdateTo
 * @param $installDir
 * @param ScheduledUpdate $scheduledUpdate
 * @return void
 */
function doFullSecondaryUpgrade($operatingSystem, $linuxDistribution, $serverName, $versionToUpdateTo, $installDir, ScheduledUpdate &$scheduledUpdate): void {
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			executeCommand("Stopping cron", "service cron stop", $scheduledUpdate);
		} else {
			executeCommand("Stopping cron", "service crond stop", $scheduledUpdate);
			executeCommand("Running system updates", "yum -y update", $scheduledUpdate);
		}
	}
	if ($operatingSystem == 'linux') {
		executeCommand("Stopping java", "pkill java", $scheduledUpdate);
	}

	// Run database updates
	runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate);

	//Run version specific upgrade script
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			if (file_exists("$installDir/install/upgrade_debian_$versionToUpdateTo.sh")) {
				executeCommand("Running version upgrade script", "cd $installDir/install; ./upgrade_debian_$versionToUpdateTo.sh $serverName", $scheduledUpdate);
			}
		} else {
			if (file_exists("$installDir/install/upgrade_$versionToUpdateTo.sh")) {
				executeCommand("Running version upgrade script", "cd $installDir/install; ./upgrade_$versionToUpdateTo.sh $serverName", $scheduledUpdate);
			}
		}
	}

	//Update Solr files
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			executeCommand("Updating Solr files", "cd $installDir/data_dir_setup; ./update_solr_files_debian.sh $serverName", $scheduledUpdate);
		} else {
			executeCommand("Updating Solr files", "cd $installDir/data_dir_setup; ./update_solr_files.sh $serverName", $scheduledUpdate);
		}
	} else {
		executeCommand("Updating Solr files", "cd $installDir/data_dir_setup; ./update_solr_files.bat $serverName", $scheduledUpdate);
	}

	//Restart services
	if ($operatingSystem == 'linux') {
		//Start cron
		if ($linuxDistribution == 'debian') {
			executeCommand("Starting cron", "service cron start", $scheduledUpdate);
		} else {
			executeCommand("Starting cron", "service crond start", $scheduledUpdate);
		}
	}
}

/**
 * @param $operatingSystem
 * @param $linuxDistribution
 * @param $versionToUpdateTo
 * @param ScheduledUpdate $scheduledUpdate
 * @return void
 */
function doSecondaryUpdate($operatingSystem, $linuxDistribution, $versionToUpdateTo, ScheduledUpdate &$scheduledUpdate): void {
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			executeCommand("Stopping cron", "service cron stop", $scheduledUpdate);
		} else {
			executeCommand("Stopping cron", "service crond stop", $scheduledUpdate);
			executeCommand("Running system updates", "yum -y update", $scheduledUpdate);
		}
	}

	if ($operatingSystem == 'linux') {
		executeCommand("Stopping java", "pkill java", $scheduledUpdate);
	}

	// Run database updates
	runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate);

	//Restart services
	if ($operatingSystem == 'linux') {
		//Start cron
		if ($linuxDistribution == 'debian') {
			executeCommand("Starting cron", "service cron start", $scheduledUpdate);
		} else {
			executeCommand("Starting cron", "service crond start", $scheduledUpdate);
		}
	}
}

/**
 * @param $operatingSystem
 * @param $linuxDistribution
 * @param $serverName
 * @param $versionToUpdateTo
 * @param $installDir
 * @param ScheduledUpdate $scheduledUpdate
 * @return void
 */
function doFullUpgrade($operatingSystem, $linuxDistribution, $serverName, $versionToUpdateTo, $installDir, ScheduledUpdate &$scheduledUpdate): void {

	//Prepare the system to be updated
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			executeCommand("Stopping cron", "service cron stop", $scheduledUpdate);
		} else {
			executeCommand("Stopping cron", "service crond stop", $scheduledUpdate);
			executeCommand("Running system updates", "yum -y update", $scheduledUpdate);
		}
	}
	if ($operatingSystem == 'linux') {
		executeCommand("Stopping java", "pkill java", $scheduledUpdate);
	}

	//Update the system
	updateGitAndRunDatabaseUpdates($operatingSystem, $versionToUpdateTo, $scheduledUpdate);

	//Run version specific upgrade script
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			if (file_exists("$installDir/install/upgrade_debian_$versionToUpdateTo.sh")) {
				executeCommand("Running version upgrade script", "cd $installDir/install; ./upgrade_debian_$versionToUpdateTo.sh $serverName", $scheduledUpdate);
			}
		} else {
			if (file_exists("$installDir/install/upgrade_$versionToUpdateTo.sh")) {
				executeCommand("Running version upgrade script", "cd $installDir/install; ./upgrade_$versionToUpdateTo.sh $serverName", $scheduledUpdate);
			}
		}
	}

	//Update Solr files
	if ($operatingSystem == 'linux') {
		if ($linuxDistribution == 'debian') {
			executeCommand("Updating Solr files", "cd $installDir/data_dir_setup; ./update_solr_files_debian.sh $serverName", $scheduledUpdate);
		} else {
			executeCommand("Updating Solr files", "cd $installDir/data_dir_setup; ./update_solr_files.sh $serverName", $scheduledUpdate);
		}
	} else {
		executeCommand("Updating Solr files", "cd $installDir/data_dir_setup; ./update_solr_files.bat $serverName", $scheduledUpdate);
	}

	//Restart services
	if ($operatingSystem == 'linux') {
		//Restart mysql
		executeCommand("Restarting MySQL", "service mysqld restart", $scheduledUpdate);
		//Restart apache
		executeCommand("Restarting apache", "apachectl graceful", $scheduledUpdate);
		//Start cron
		if ($linuxDistribution == 'debian') {
			executeCommand("Starting cron", "service cron start", $scheduledUpdate);
		} else {
			executeCommand("Starting cron", "service crond start", $scheduledUpdate);
		}
	}

	//Run git cleanup
	executeCommand("Cleaning up git", "cd $installDir; git gc", $scheduledUpdate);
}

function executeCommand(string $commandNote, string $commandToExecute, ScheduledUpdate $scheduledUpdate) {
	$scheduledUpdate->notes .= $commandNote . "\n";
	exec($commandToExecute, $execResult);
	foreach ($execResult as $result) {
		$scheduledUpdate->notes .= $result . "\n";
	}
}

function hasErrors($notes) : bool {
	$lowerNotes = strtolower($notes);
	if ((strpos($lowerNotes, 'fatal') !== false) || (strpos($lowerNotes, 'failed') !== false) || (strpos($lowerNotes, 'rejected') !== false)) {
		return true;
	} else {
		return false;
	}
}

function getOSInformation() {
	if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
		return null;
	}

	$os         = shell_exec('cat /etc/os-release');
	if (preg_match_all('/.*=/', $os, $matchListIds)) {
		$listIds    = $matchListIds[0];
	} else {
		$listIds = [];
	}

	if (preg_match_all('/=.*/', $os, $matchListVal)) {
		$listVal = $matchListVal[0];
	} else {
		$listVal = [];
	}

	array_walk($listIds, function(&$v, $k){
		$v = strtolower(str_replace('=', '', $v));
	});

	array_walk($listVal, function(&$v, $k){
		$v = preg_replace('/[="]/', '', $v);
	});

	return array_combine($listIds, $listVal);
}

function console_log($message, $prefix = '') {
	$STDERR = fopen("php://stderr", "w");
	fwrite($STDERR, $prefix.$message."\n");
	fclose($STDERR);
}