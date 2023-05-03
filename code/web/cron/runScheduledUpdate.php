<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

$pendingUpdates = new ScheduledUpdate();
$pendingUpdates->status = 'pending';
$pendingUpdates->whereAdd('dateScheduled <= ' . time()); //Only get things where the scheduled time is before right now
$pendingUpdates->orderBy('dateScheduled asc');
//Load all of them once since we update them
$updatesToRun = $pendingUpdates->fetchAll('id');

global $configArray;
global $serverName;

foreach($updatesToRun as $id) {
	//Load the actual item
	$scheduledUpdate = new ScheduledUpdate();
	$scheduledUpdate->id = $id;
	if ($scheduledUpdate->find(true)) {
		$versionToUpdateTo = $scheduledUpdate->updateToVersion;
		$currentVersion = getGitBranch();

		if (str_replace('.', '',$versionToUpdateTo) >= str_replace('.', '',$currentVersion,)) {
			if($scheduledUpdate->updateType === 'complete') {
				$scheduledUpdate->notes .= "FAILED: Complete updates are not supported yet";
			}else if($scheduledUpdate->updateType === 'patch') {
				exec("cd /usr/local/aspen-discovery; git fetch origin; git reset --hard origin/$currentVersion", $resetGitResult);
				foreach($resetGitResult as $result) {
					$scheduledUpdate->notes .= $result;
				}

				exec("cd /usr/local/aspen-discovery; sudo git pull origin $scheduledUpdate->updateToVersion", $gitResult);
				foreach($gitResult as $result) {
					$scheduledUpdate->notes .= $result;
				}

				// run db maintenance
				require_once ROOT_DIR . '/services/API/SystemAPI.php';
				$systemAPI = new SystemAPI();
				$dbMaintenance = $systemAPI->runPendingDatabaseUpdates();
				if (!$dbMaintenance['success'] || $dbMaintenance['success'] == 'false') {
					$message = $dbMaintenance['message'] ?? '';
					$scheduledUpdate->status = 'failed';
					$scheduledUpdate->notes .= $message;
				}
			} else {
				// invalid updateType
				$scheduledUpdate->notes = 'FAILED: Invalid update type';
			}
		} else {
			$scheduledUpdate->notes = "FAILED: Must update to a version that is the same or newer than the current version of $currentVersion";
		}

		$lowerNotes = strtolower($scheduledUpdate->notes);
		if((strpos($lowerNotes, 'fatal') !== false) || (strpos($lowerNotes, 'failed') !== false) || (strpos($lowerNotes, 'rejected') !== false)) {
			$scheduledUpdate->status = 'failed';
		} else {
			$scheduledUpdate->status = 'complete';
		}
		$scheduledUpdate->dateRun = time();

		$scheduledUpdate->update();

		if(!empty($scheduledUpdate->greenhouseId)) {
			// update greenhouse if the update was scheduled from there
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			if (!empty($systemVariables)) {
				$greenhouseUrl = $systemVariables->greenhouseUrl . '/Greenhouse/UpdateCenter/';
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
				print_r($response);
			}
		}
	}
}