<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

if (file_exists(ROOT_DIR . '/sys/Greenhouse/CompanionSystem.php')) {
	require_once ROOT_DIR . '/sys/Greenhouse/CompanionSystem.php';
}

$pendingUpdates = new ScheduledUpdate();
$pendingUpdates->status = 'pending';
$pendingUpdates->remoteUpdate = 0;
$pendingUpdates->whereAdd('dateScheduled <= ' . time()); //Only get things where the scheduled time is before right now
$pendingUpdates->orderBy('dateScheduled asc');
//Load all of them once since we update them
$updatesToRun = $pendingUpdates->fetchAll('id');
$pendingUpdates = null;

global $configArray;
global $serverName;

if (count($updatesToRun) == 0) {
	//console_log("no updates to run\n");
}else {
	foreach ($updatesToRun as $id) {
		//Load the actual item
		$scheduledUpdate = new ScheduledUpdate();
		$scheduledUpdate->id = $id;
		if ($scheduledUpdate->find(true)) {
			$scheduledUpdate->status = 'started';
			$scheduledUpdate->update();

			$versionToUpdateTo = $scheduledUpdate->updateToVersion;
			$currentVersion = getAspenVersion();
			if (str_contains($currentVersion, ' ')) {
				$currentVersion  = substr($currentVersion, 0, strpos($currentVersion, ' '));
			}

			if (!preg_match('/\d{2}\.\d{2}\.\d{2}/', $versionToUpdateTo)) {
				$scheduledUpdate->notes = "FAILED: Bad version to update to $versionToUpdateTo \n";
			}else{
				if (str_replace('.', '', $versionToUpdateTo) >= str_replace('.', '', $currentVersion)) {
					console_log("starting upgrade to $versionToUpdateTo\n");

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

					//Prepare the system to be updated
					if ($operatingSystem == 'linux' && $scheduledUpdate->updateType === 'complete') {
						if ($linuxDistribution == 'debian') {
							executeCommand('Stopping cron', '/usr/bin/systemctl stop cron', $scheduledUpdate);
						} else {
							executeCommand('Stopping cron', '/usr/sbin/service crond stop', $scheduledUpdate);
							executeCommand('Running system updates', 'yum -y update', $scheduledUpdate);
						}
					}

					if ($scheduledUpdate->updateType === 'complete') {
						if ($operatingSystem == 'linux') {
							executeCommand('Stopping java', 'pkill -9 java', $scheduledUpdate);
						}
						doFullUpgrade($operatingSystem, $linuxDistribution, $serverName, $versionToUpdateTo, $installDir, $scheduledUpdate);
					} elseif ($scheduledUpdate->updateType === 'patch') {
						doPatchUpgrade($operatingSystem, $versionToUpdateTo, $scheduledUpdate);
					} else {
						// invalid updateType
						$scheduledUpdate->notes = "FAILED: Invalid update type\n";
					}

					//Check to see if any companion systems are configured
					$companionSystems = [];
					try {
						$companionSystem = new CompanionSystem();
						$companionSystem->find();
						while($companionSystem->fetch()) {
							$companionSystems[] = clone $companionSystem;
						}
					} catch (Exception $e) {
						//Table not created yet, ignore
					}

					if($companionSystems) {
						foreach($companionSystems as $companion) {
							/** @var CompanionSystem $companion **/
							if ($scheduledUpdate->updateType === 'complete') {
								doFullUpgrade($operatingSystem, $linuxDistribution, $companion->getServerName(), $versionToUpdateTo, $installDir, $scheduledUpdate, $companion);
							} elseif ($scheduledUpdate->updateType === 'patch') {
								doPatchUpgrade($operatingSystem, $versionToUpdateTo, $scheduledUpdate, $companion);
							} else {
								// invalid updateType
								$scheduledUpdate->notes = "FAILED: Invalid update type\n";
							}

						}
					}

					//Restart services
					if ($operatingSystem == 'linux' && $scheduledUpdate->updateType === 'complete') {
						//Restart mysql
						executeCommand('Restarting MySQL', '/usr/sbin/service mysqld restart', $scheduledUpdate);
						sleep(10);
						//Restart apache
						executeCommand('Restarting apache', '/usr/sbin/apachectl graceful', $scheduledUpdate);
						sleep(2);
						//Start cron
						if ($linuxDistribution == 'debian') {
							executeCommand('Starting cron', '/usr/bin/systemctl start cron', $scheduledUpdate);
						} else {
							executeCommand('Starting cron', '/usr/sbin/service crond start', $scheduledUpdate);
						}
						sleep(2);
					}

					//Run git cleanup
					executeCommand('Cleaning up git', "cd $installDir; git gc", $scheduledUpdate);

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

			//Re-initialize database since it may have been closed during updates
			initDatabase();
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
						'greenhouseSiteId' => $scheduledUpdate->siteId
					];
					$response = $curl->curlPostPage($greenhouseUrl . '/API/GreenhouseAPI?method=updateScheduledUpdate', $body);
					$curl = null;
					//TODO: temp debugging
					//print_r($response);
				}
				$systemVariables = null;
			}
		}
		$scheduledUpdate->__destruct();
		$scheduledUpdate = null;
	}
	console_log("Finished running " . count($updatesToRun) . " updates\n");
}

global $aspen_db;
$aspen_db = null;
$configArray = null;

die();

/////// END OF PROCESS ///////

/**
 * @param $operatingSystem
 * @param $versionToUpdateTo
 * @param ScheduledUpdate $scheduledUpdate
 * @param CompanionSystem|null $companionSystem
 * @return void
 */
function doPatchUpgrade($operatingSystem, $versionToUpdateTo, ScheduledUpdate $scheduledUpdate, ?CompanionSystem $companionSystem = null): void{
	if($companionSystem) {
		runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate, $companionSystem);
		updateCssForAllThemes($scheduledUpdate, $companionSystem);
	} else {
		updateGitAndRunDatabaseUpdates($operatingSystem, $versionToUpdateTo, $scheduledUpdate);
	}
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
		updateCssForAllThemes($scheduledUpdate);
	}
}

function updateCssForAllThemes($scheduledUpdate, ?CompanionSystem $companionSystem = null) : void {
	//Make sure we have an interface available to do the updates
	global $interface;
	$interface = new UInterface();

	$scheduledUpdate->notes .= "Updating CSS for all Themes\n";

	require_once ROOT_DIR . '/services/API/SystemAPI.php';
	$systemAPI = new SystemAPI();
	$systemAPI->updateCssForAllThemes();

	// run external db maintenance if needed
	if($companionSystem != null) {
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$curl = new CurlWrapper();
		console_log('Updating CSS for all Themes for companion system ' . $companionSystem->getServerUrl() . '/API/SystemAPI?method=updateCssForAllThemes');
		$response = json_decode($curl->curlGetPage($companionSystem->getServerUrl() . '/API/SystemAPI?method=updateCssForAllThemes'));
		if(!isset($response->success) || $response->success == false) {
			$scheduledUpdate->status = 'failed';
			$scheduledUpdate->notes .= 'Updating CSS for all Themes failed for ' . $companionSystem->getServerName();
		}

		if(isset($response->message)) {
			$message = $response->message ?? '';
			$scheduledUpdate->notes .= $message . "\n";
		}
	}
}

function runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate, ?CompanionSystem $companionSystem = null) {
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

	// run external db maintenance if needed
	if($companionSystem != null) {
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$curl = new CurlWrapper();
		console_log('Running Database Maintenance ' . $companionSystem->getServerUrl() . '/API/SystemAPI?method=runPendingDatabaseUpdates');
		$response = json_decode($curl->curlGetPage($companionSystem->getServerUrl() . '/API/SystemAPI?method=runPendingDatabaseUpdates'));
		if(!isset($response->success) || $response->success == false) {
			$scheduledUpdate->status = 'failed';
			$scheduledUpdate->notes .= 'DB maintenance failed for ' . $companionSystem->getServerName();
		}

		if(isset($response->message)) {
			$message = $response->message ?? '';
			$scheduledUpdate->notes .= $message . "\n";
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
 * @param CompanionSystem|null $companionSystem
 * @return void
 */
function doFullUpgrade($operatingSystem, $linuxDistribution, $serverName, $versionToUpdateTo, $installDir, ScheduledUpdate $scheduledUpdate, ?CompanionSystem $companionSystem = null): void {
	if($companionSystem != null) {
		//Update the companion system
		runDatabaseMaintenance($versionToUpdateTo, $scheduledUpdate, $companionSystem);
		updateCssForAllThemes($scheduledUpdate, $companionSystem);
	} else {
		//Update the system
		updateGitAndRunDatabaseUpdates($operatingSystem, $versionToUpdateTo, $scheduledUpdate);
	}

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

		if (file_exists("$installDir/install/updateCron_$versionToUpdateTo.php")) {
			executeCommand("Running cron update", "cd $installDir/install; php ./updateCron_$versionToUpdateTo.php $serverName", $scheduledUpdate);
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
}

function executeCommand(string $commandNote, string $commandToExecute, ScheduledUpdate $scheduledUpdate) {
	$scheduledUpdate->notes .= $commandNote . "\n";
	exec($commandToExecute, $execResult);
	console_log($commandToExecute);
	foreach ($execResult as $result) {
		$scheduledUpdate->notes .= $result . "\n";
		console_log($result);
	}

}

function hasErrors($notes) : bool {
	$lowerNotes = strtolower($notes);
	/** @noinspection PhpStrFunctionsInspection */
	if ((strpos($lowerNotes, 'fatal') !== false) || (preg_match('/failed[\s.]/si', $lowerNotes) === 1) || (strpos($lowerNotes, 'rejected') !== false)) {
		return true;
	} else {
		return false;
	}
}

/** @noinspection PhpUnusedParameterInspection */
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