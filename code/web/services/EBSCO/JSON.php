<?php

require_once ROOT_DIR . '/JSON_Action.php';

class EBSCO_JSON extends JSON_Action
{
	/** @noinspection PhpUnused */
	public function trackEdsUsage()
	{
		if (!isset($_REQUEST['id'])) {
			return ['success' => false, 'message' => 'ID was not provided'];
		}
		$id = $_REQUEST['id'];

		//Track usage of the record
		require_once ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php';
		$ebscoEdsRecordUsage = new EbscoEdsRecordUsage();
		$ebscoEdsRecordUsage->ebscoId = $id;
		$ebscoEdsRecordUsage->year = date('Y');
		$ebscoEdsRecordUsage->month = date('n');
		if ($ebscoEdsRecordUsage->find(true)) {
			$ebscoEdsRecordUsage->timesUsed++;
			$ret = $ebscoEdsRecordUsage->update();
			if ($ret == 0) {
				echo("Unable to update times used");
			}
		} else {
			$ebscoEdsRecordUsage->timesViewedInSearch = 0;
			$ebscoEdsRecordUsage->timesUsed = 1;
			$ebscoEdsRecordUsage->insert();
		}

		$userId = UserAccount::getActiveUserId();
		if ($userId) {
			//Track usage for the user
			require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
			$userEbscoEdsUsage = new UserEbscoEdsUsage();
			$userEbscoEdsUsage->userId = $userId;
			$userEbscoEdsUsage->year = date('Y');
			$userEbscoEdsUsage->month = date('n');

			if ($userEbscoEdsUsage->find(true)) {
				$userEbscoEdsUsage->usageCount++;
				$userEbscoEdsUsage->update();
			} else {
				$userEbscoEdsUsage->usageCount = 1;
				$userEbscoEdsUsage->insert();
			}
		}

		return ['success' => true, 'message' => 'Updated usage for EBSCO EDS record ' . $id];
	}
}