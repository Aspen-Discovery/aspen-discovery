<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Websites_JSON extends JSON_Action
{
	/** @noinspection PhpUnused */
	public function trackUsage()
	{
		if (!isset($_REQUEST['id'])) {
			return ['success' => false, 'message' => 'ID was not provided'];
		}
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsitePage.php';
		$webPage = new WebsitePage();
		$webPage->id = $id;
		if (!$webPage->find(true)) {
			return ['success' => false, 'message' => 'Record was not found in the database'];
		}

		//Track usage of the record
		require_once ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php';
		$webPageUsage = new WebPageUsage();
		global $fullServerName;
		$webPageUsage->instance =$fullServerName;
		$webPageUsage->webPageId = $id;
		$webPageUsage->year = date('Y');
		$webPageUsage->month = date('n');
		if ($webPageUsage->find(true)) {
			$webPageUsage->timesUsed++;
			$ret = $webPageUsage->update();
			if ($ret == 0) {
				echo("Unable to update times used");
			}
		} else {
			$webPageUsage->timesViewedInSearch = 0;
			$webPageUsage->timesUsed = 1;
			$webPageUsage->insert();
		}

		$userId = UserAccount::getActiveUserId();
		if ($userId) {
			//Track usage for the user
			require_once ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php';
			$userWebsiteUsage = new UserWebsiteUsage();
			$userWebsiteUsage->userId = $userId;
			$userWebsiteUsage->year = date('Y');
			$userWebsiteUsage->month = date('n');
			$userWebsiteUsage->websiteId = $webPage->websiteId;

			if ($userWebsiteUsage->find(true)) {
				$userWebsiteUsage->usageCount++;
				$userWebsiteUsage->update();
			} else {
				$userWebsiteUsage->usageCount = 1;
				$userWebsiteUsage->insert();
			}
		}

		return ['success' => true, 'message' => 'Updated usage for webpage ' . $id];
	}
}