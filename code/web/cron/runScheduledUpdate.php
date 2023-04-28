<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Updates/ScheduledUpdate.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

$pendingUpdates = new ScheduledUpdate();
$pendingUpdates->status = 'pending';
$updatesToRun = $pendingUpdates->fetchAll('id');

global $configArray;
global $serverName;

foreach($updatesToRun as $id) {
	$scheduledUpdate = new ScheduledUpdate();
	$scheduledUpdate->id = $id;
	if($scheduledUpdate->find(true)) {
		$siteName = "";
		$currentVersion = "";
		if($scheduledUpdate->siteId) {
			$site = new AspenSite();
			$site->id = $scheduledUpdate->siteId;
			if($site->find((true))) {
				$currentVersion = $site->version;
				$siteName = $site->name;
			}
		} else {
			global $interface;
			global $library;
			$currentVersion = $interface->getVariable('gitBranchWithCommit');
			$siteName = $library->displayName;
		}

		if($scheduledUpdate->updateType === 'complete') {
			exec("cd /usr/local/aspen-discovery; git fetch origin; git reset --hard origin/$currentVersion", $resetGitResult);
			foreach($resetGitResult as $result) {
				$scheduledUpdate->notes .= $result;
			}

			exec("cd /usr/local/aspen-discovery; sudo git pull origin $scheduledUpdate->updateToVersion", $gitResult);
			foreach($gitResult as $result) {
				$scheduledUpdate->notes .= $result;
			}
		}else if($scheduledUpdate->updateType === 'patch') {
			exec("cd /usr/local/aspen-discovery; sudo git pull origin $scheduledUpdate->updateToVersion", $gitResult);
			foreach($gitResult as $result) {
				$scheduledUpdate->notes .= $result;
			}
		} else {
			// invalid updateType
		}

		if(str_contains($scheduledUpdate->notes, 'fatal') || str_contains($scheduledUpdate->notes, 'failed') || str_contains($scheduledUpdate->notes, 'rejected')) {
			$scheduledUpdate->status = 'failed';
		} else {
			$scheduledUpdate->status = 'complete';
		}

		// run db maintenance
		require_once ROOT_DIR . '/services/API/SystemAPI.php';
		$systemAPI = new SystemAPI();
		$dbMaintenance = $systemAPI->runPendingDatabaseUpdates();
		if(!$dbMaintenance['success'] || $dbMaintenance['success'] == 'false') {
			$message = $dbMaintenance['message'] ?? '';
			$scheduledUpdate->status = 'failed';
			$scheduledUpdate->notes .= $message;
		}

		$scheduledUpdate->update();

		// send Slack notification
		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		$greenhouseAlertSlackHook = null;
		$shouldSendBuildAlert = false;
		if ($greenhouseSettings->find(true)) {
			$greenhouseAlertSlackHook = $greenhouseSettings->greenhouseAlertSlackHook;
			$shouldSendBuildAlert = $greenhouseSettings->sendBuildTrackerAlert;
		}

		if ($greenhouseAlertSlackHook && $shouldSendBuildAlert) {
			$scheduledUpdateUrl = $configArray['Site']['url'] . '/Admin/ScheduledUpdates/';
			if($scheduledUpdate->siteId) {
				// update scheduled from the greenhouse
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				$systemVariables = SystemVariables::getSystemVariables();
				if(!empty($systemVariables)) {
					$scheduledUpdateUrl = $systemVariables->greenhouseUrl . '/Greenhouse/UpdateCenter/';
				}
			}
			if($scheduledUpdate->status === 'failed') {
				$notification = "- :fire: <$scheduledUpdateUrl|Update failed> for $siteName while updating to $scheduledUpdate->updateToVersion ($scheduledUpdate->updateType)";
				$notification .= '<!here>';
			} else if($scheduledUpdate->status === 'complete') {
				$notification = "- <$scheduledUpdateUrl|Update completed> for $siteName to $scheduledUpdate->updateToVersion ($scheduledUpdate->updateType)";
			} else {
				$notification = null;
			}
			$alertText = "*$siteName* $notification\n";
			if($notification) {
				$curlWrapper = new CurlWrapper();
				$headers = [
					'Accept: application/json',
					'Content-Type: application/json',
				];
				$curlWrapper->addCustomHeaders($headers, false);
				$body = new stdClass();
				$body->text = $alertText;
				$curlWrapper->curlPostPage($greenhouseAlertSlackHook, json_encode($body));
			}
		}
	}
}