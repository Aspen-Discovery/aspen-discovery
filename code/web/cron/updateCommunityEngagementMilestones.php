<?php
	require_once __DIR__ . '/../bootstrap.php';
	require_once __DIR__ . '/../bootstrap_aspen.php';
	global $enabledModules;
	if (array_key_exists('Community Engagement', $enabledModules)) {
		require_once ROOT_DIR . '/sys/CronLogEntry.php';
		$cronLogEntry = new CronLogEntry();
		$cronLogEntry->startTime = time();
		$cronLogEntry->name = 'Update Community Engagement Users';
		$cronLogEntry->insert();
		$userCampaigns = new UserCampaign();
		//adding 1 day to catch things that were enrolled today
		$userCampaigns->whereAdd("enrollmentDate < '" . date("Y-m-d", strtotime("+1 day")) . "'");
		$userCampaigns->unenrollmentDate = null;
		$userCampaigns->completed = 0;
		$userIds = $userCampaigns->fetchAll("userId");
		$totalUpdated = 0;
		foreach($userIds as $userID)
		{
			$user = new User();
			$user->id = $userID;
			if($user->find(true))
			{
				$cronLogEntry->notes .= "updating checkouts/holds for user: $userID<br/>";
				$user->getCheckouts();
				$user->getHolds();
				$totalUpdated++;
				$cronLogEntry->update();
			}
			// TODO do we need to do a similar check for any other milestone criteria?
		}
		$cronLogEntry->notes .= "<br/><hr/>Updating users complete: $totalUpdated users had checkouts and holds automatically checked.";
		$cronLogEntry->endTime = time();
		$cronLogEntry->update();
	}
?>