<?php

require_once ROOT_DIR . '/JSON_Action.php';

class OpenArchives_JSON extends JSON_Action
{
	/** @noinspection PhpUnused */
	public function trackUsage()
	{
		if (!isset($_REQUEST['id'])) {
			return ['success' => false, 'message' => 'ID was not provided'];
		}
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecord.php';
		$openArchivesRecord = new OpenArchivesRecord();
		$openArchivesRecord->id = $id;
		if (!$openArchivesRecord->find(true)) {
			return ['success' => false, 'message' => 'Record was not found in the database'];
		}

		//Track usage of the record
		require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php';
		$openArchivesUsage = new OpenArchivesRecordUsage();
		global $aspenUsage;
		$openArchivesUsage->instance = $aspenUsage->instance;
		$openArchivesUsage->openArchivesRecordId = $id;
		$openArchivesUsage->year = date('Y');
		$openArchivesUsage->month = date('n');
		if ($openArchivesUsage->find(true)) {
			$openArchivesUsage->timesUsed++;
			$ret = $openArchivesUsage->update();
			if ($ret == 0) {
				echo("Unable to update times used");
			}
		} else {
			$openArchivesUsage->timesViewedInSearch = 0;
			$openArchivesUsage->timesUsed = 1;
			$openArchivesUsage->insert();
		}

		$userId = UserAccount::getActiveUserId();
		if ($userId) {
			//Track usage for the user
			require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
			$userOpenArchivesUsage = new UserOpenArchivesUsage();
			global $aspenUsage;
			$userOpenArchivesUsage->instance = $aspenUsage->instance;
			$userOpenArchivesUsage->userId = $userId;
			$userOpenArchivesUsage->year = date('Y');
			$userOpenArchivesUsage->month = date('n');
			$userOpenArchivesUsage->openArchivesCollectionId = $openArchivesRecord->sourceCollection;

			if ($userOpenArchivesUsage->find(true)) {
				$userOpenArchivesUsage->usageCount++;
				$userOpenArchivesUsage->update();
			} else {
				$userOpenArchivesUsage->usageCount = 1;
				$userOpenArchivesUsage->insert();
			}
		}

		return ['success' => true, 'message' => 'Updated usage for archive record ' . $id];
	}
}